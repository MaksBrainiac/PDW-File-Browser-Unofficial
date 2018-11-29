<?php

if (isset($_REQUEST['PHPSESSID']))
    session_id($_REQUEST['PHPSESSID']);

require_once('../config.php');

//check if request is GET and the requested chunk exists or not. this makes testChunks work
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (!isset($_GET['flowIdentifier']))
        header("HTTP/1.0 404 Not Found");

    $flowFilename = getSafeFileName(basename($_GET['flowFilename']), $valid_chars_regex, $MAX_FILENAME_LENGTH);
    if (!$flowFilename) {
        HandleError("Invalid file name");
        exit(0);
    }

    $flowIdentifier = basename($_GET['flowIdentifier']);
    $flowChunkNumber = basename($_GET['flowChunkNumber']);

    $tempDir = sys_get_temp_dir() . "/" . session_id() . "/" . $flowIdentifier;
    $chunkFile = $tempDir . '/' . $flowFilename . '.part' . $flowChunkNumber;

    if (file_exists($chunkFile)) {
        header("HTTP/1.0 200 Ok");
    }
    else {
        header("HTTP/1.0 404 Not Found");
    }
    die();
}
// ELSE - POST!

if ((int)$_SERVER['CONTENT_LENGTH'] > $max_file_size_in_bytes) {
    HandleError("POST exceeded maximum allowed size.");
    exit(0);
}

$uploadPath = ltrim($_POST["uploadpath"], ".");
if (empty($uploadPath)) {
    HandleError("Invalid upload path");
    exit(0);
}

// Settings
$savePath = DOCUMENTROOT . $uploadPath; //The path were we will save the file (getcwd() may not be reliable and should be tested in your environment)

// Other variables
$MAX_FILENAME_LENGTH = 260;

$uploadErrors = array(
    0 => "There is no error, the file uploaded with success",
    1 => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
    2 => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
    3 => "The uploaded file was only partially uploaded",
    4 => "No file was uploaded",
    6 => "Missing a temporary folder"
);

// loop through files and move the chunks to a temporarily created directory
if (!empty($_FILES)) {

    foreach ($_FILES as $file) {
        $flowFilename = getSafeFileName(basename($_POST['flowFilename']), $valid_chars_regex, $MAX_FILENAME_LENGTH);
        if (!$flowFilename) {
            HandleError("Invalid file name");
            exit(0);
        }

            // Validate file extension
            $pathInfo = pathinfo($flowFilename);
            $fileExtension = $pathInfo["extension"];
            if (empty($pathInfo['filename'])) {
                HandleError("Invalid file name");
                exit(0);
            }

            $isValidExtension = false;
            $allowedExtensions = explode(',', $extension_whitelist);

            foreach ($allowedExtensions as $extension) {
                if (strcasecmp($fileExtension, str_replace(' ', '', $extension)) == 0) {
                    $isValidExtension = true;
                    break;
                }
            }

            if (!$isValidExtension) {
                HandleError("Invalid file extension");
                exit(0);
            }


        $targetFileName = $savePath . $flowFilename;
        if (file_exists($targetFileName)) {
            HandleError("File with this name already exists");
            exit(0);
        }

        // check the error status
        if ($file['error'] != 0) {
            HandleError($uploadErrors[$file['error']]);
            exit(0);

            ///_log('error ' . $file['error'] . ' in file ' . $flowFilename);
            ///continue;
        }

        // init the destination file (format <filename.ext>.part<#chunk>
        // the file is stored in a temporary directory

        $flowIdentifier = basename($_POST['flowIdentifier']);
        $flowFilename = basename($_POST['flowFilename']);
        $flowChunkNumber = basename($_POST['flowChunkNumber']);
        $flowChunkSize = intval($_POST['flowChunkSize']);
        $flowTotalSize = intval($_POST['flowTotalSize']);

        $tempDir = sys_get_temp_dir() . "/" . session_id() . "/" . $flowIdentifier;
        $destFile = $tempDir . '/' . $flowFilename . '.part' . $flowChunkNumber;

        // create the temporary directory
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        // move the temporary file
        if (!move_uploaded_file($file['tmp_name'], $destFile)) {
            //_log('Error saving (move_uploaded_file) chunk '.$_POST['flowChunkNumber'].' for file '.$_POST['flowFilename']);
            HandleError("File could not be saved.");
            exit(0);
        } else {
            // check if all the parts present, and create the final destination file
            createFileFromChunks($tempDir, $flowFilename, $flowChunkSize, $flowTotalSize, $targetFileName);
        }
    }
    return;

} else {
    HandleError("Empty $_FILES");
    exit(0);
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/* Handles the error output. This error message will be sent to the uploadSuccess event handler.  The event handler
will have to check for any error messages and react as needed. */
function getSafeFileName($value, $valid_chars_regex, $max) {
    // Validate file name (for our purposes we'll just remove invalid characters)
    $file_name = preg_replace('/[^'.$valid_chars_regex.']|\.+$/i', "", $value);
    if (strlen($file_name) == 0 || strlen($file_name) > $max) {
        return null;
    }
    return $file_name;
}

function HandleError($message) {
    header("HTTP/1.1 500 Internal Server Error");
	echo $message;
}

/**
 *
 * Logging operation - to a file (upload_log.txt) and to the stdout
 * @param string $str - the logging string
 */
function _log($str) {

    // log to the output
    /*$log_str = date('d.m.Y').": {$str}\r\n";
    echo $log_str;*/

    // log to file
    /*if (($fp = fopen('upload_log.txt', 'a+')) !== false) {
        fputs($fp, $log_str);
        fclose($fp);
    }*/
}

/**
 *
 * Delete a directory RECURSIVELY
 * @param string $dir - directory path
 * @link http://php.net/manual/en/function.rmdir.php
 */
function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir . "/" . $object) == "dir") {
                    rrmdir($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

/**
 *
 * Check if all the parts exist, and
 * gather all the parts of the file together
 * @param string $dir - the temporary directory holding all the parts of the file
 * @param string $fileName - the original file name
 * @param string $chunkSize - each chunk size (in bytes)
 * @param string $totalSize - original file size (in bytes)
 */
function createFileFromChunks($temp_dir, $fileName, $chunkSize, $totalSize, $destinationFileName) {

    // count all the parts of this file
    $total_files = 0;
    foreach (scandir($temp_dir) as $file) {
        if (stripos($file, $fileName) !== false) {
            $total_files++;
        }
    }

    // check that all the parts are present
    // the size of the last part is between chunkSize and 2*$chunkSize
    if ($total_files * $chunkSize >= ($totalSize - $chunkSize + 1)) {

        // create the final destination file
        if (($fp = fopen($destinationFileName, 'w')) !== false) {
            for ($i = 1; $i <= $total_files; $i++) {
                fwrite($fp, file_get_contents($temp_dir . '/' . $fileName . '.part' . $i));
                _log('writing chunk ' . $i);
            }
            fclose($fp);
        } else {
            _log('cannot create the destination file');
            return false;
        }

        // rename the temporary directory (to avoid access from other
        // concurrent chunks uploads) and than delete it
        if (rename($temp_dir, $temp_dir . '_UNUSED')) {
            rrmdir($temp_dir . '_UNUSED');
        } else {
            rrmdir($temp_dir);
        }
    }

}