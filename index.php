<?php
/*
PDW File Browser v1.4+
Date: October 19, 2010
Date: June 24, 2018
Url: http://www.neele.name

Copyright (c) 2010 Guido Neele
Copyright (c) 2018 Maks T.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

//ob_start( 'ob_gzhandler' );
ob_start();

define('MINIFY_CACHE_DIR', dirname(__FILE__) . '/cache');

require_once('functions.php');
require_once('minify.php');

if(!empty($_COOKIE["pdw-view"])):
	$viewLayout = $_COOKIE["pdw-view"];
elseif(isset($_REQUEST['pdw-view'])):
	$viewLayout = $_REQUEST['pdw-view'];
endif;

if(!empty($_REQUEST['skin'])) {
    $skin = $_REQUEST['skin'];
} elseif(isset($_GET["skin"])){
	$skin = $_GET["skin"];
} elseif (isset($defaultSkin)) {
    $skin = $defaultSkin;
} else {
    $skin = '';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>PDW File Browser v1.4+</title>
<link rel="shortcut icon" href="mediabrowser.ico" />
<script type="text/javascript">
//<![CDATA[
    var returnID = "<?php echo isset($_GET['returnID']) ? $_GET['returnID'] : ''; ?>";
    var editor = "<?php echo $editor; ?>";
    var funcNum = "<?php echo isset($_GET['CKEditorFuncNum']) ? $_GET['CKEditorFuncNum'] : 0; ?>";
    var select_one_file = "<?php echo translate('Select only one file to insert!');?>";
    var insert_cancelled = "<?php echo translate('Insert cancelled because there is no target to insert to!');?>";
    var invalid_characters_used = "<?php echo translate('Invalid characters used!')?>";
    var rename_file = "<?php echo translate('Please give a new name for file');?>";
    var rename_folder = "<?php echo translate('Please give a new name for folder');?>";
    var rename_error = "<?php echo translate('Rename failed!');?>";
//]]>
</script>
<?php
// MINIFY JS and CSS
// Create new Minify objects.
$minifyCSS = new Minify(TYPE_CSS);
$minifyJS = new Minify(TYPE_JS);

// Specify the files to be minified.
$cssFiles = array(
    'css/mediabrowser.css',
    'css/flow.css',
    'css/buttons.css',
);

// Only load skin if $_GET["skin"] or $defaultSkin is set.
if ($skin != ""):
	$cssFiles[count($cssFiles)] = 'skins/'.$skin.'/skin.css';
endif;

$minifyCSS->addFile($cssFiles);

$jsFiles = array(
    'js/jqueryx.js',
    'js/jquery.mediabrowser.js',
    'js/jquery.plugins.js',
    'js/flow.min.js',
);

//If editor is TinyMCE then add javascript file
if ($editor == "tinymce"):
    $jsFiles[count($jsFiles)] = 'js/tiny_mce_popup.js';
endif;

$minifyJS->addFile($jsFiles);
 
// JAVASCRIPT
echo '<script type="text/javascript">';
echo '//<![CDATA[';
echo $minifyJS->combine();
echo '//]]>';
echo '</script>';

// CSS
echo '<style type="text/css">';
echo $minifyCSS->combine(); 
echo '</style>';
?>

<script type="text/javascript">
//<![CDATA[
var foldercmenu;
var filecmenu;
var imagecmenu;
var cmenu;

$(document).ready(function() {

    // Prevent text selections
    divFiles = document.getElementById('files');
    divFiles.onselectstart = function() {return false;} // ie
    divFiles.onmousedown = function() {return false;} // mozilla

    // *** Context Menu ***//
    $.contextMenu.theme = 'mb';
    $.contextMenu.shadowOpacity = .3;

    // activate folder/file selection before show
    $.contextMenu.beforeShow = function(){
        // Hide all other contextmenus
        $('table.contextmenu, div.context-menu-shadow').css({'display': 'none'});

        // Enable paste button if clipboard has items
        if($.MediaBrowser.clipboard.length > 0){
            $('table.contextmenu div.context-menu-item').removeClass('context-menu-item-disabled');
        } else {
            // Disable paste button if no items are added to the clipboard
            $('table.contextmenu div.context-menu-item[title=paste]').addClass('context-menu-item-disabled');
        }

        // Show selection of file, folder or image
        if($(this.target).hasClass('folder')){ //Folder
            $.MediaBrowser.selectFileOrFolder(this.target, $(this.target).attr('href'), 'folder', 'cmenu');	
        } else if($(this.target).hasClass('file')){ //File
            $.MediaBrowser.selectFileOrFolder(this.target, $(this.target).attr('href'), 'file', 'cmenu');	
        } else if($(this.target).hasClass('image')){ //Image
            $.MediaBrowser.selectFileOrFolder(this.target, $(this.target).attr('href'), 'image', 'cmenu');	
        }

        return true;
    }

    //Context menus
    foldercmenu = [
        {'<?php echo translate("Open");?>':{
            onclick: function(menuItem,menu) { $.MediaBrowser.loadFolder($(this).attr('href')); },
            icon:'img/contextmenu/open.png'
            }
        }
		<?php if($allowedActions['copy_paste'] === TRUE || $allowedActions['cut_paste'] === TRUE): ?>
        ,$.contextMenu.separator
		<?php endif; ?>
		<?php if($allowedActions['copy_paste'] === TRUE): ?>
        ,{'<?php echo translate("Copy");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.copy(); },
            icon:'img/contextmenu/copy.gif'
            }
        }
		<?php endif; ?>
		<?php if($allowedActions['cut_paste'] === TRUE): ?>
        ,{'<?php echo translate("Cut");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.cut(); },
            icon:'img/contextmenu/cut.gif'
            }
        }
		<?php endif; ?>
		<?php if($allowedActions['copy_paste'] === TRUE || $allowedActions['cut_paste'] === TRUE): ?>
        ,{'<?php echo translate("Paste");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.paste(); },
            icon:'img/contextmenu/paste.gif',
            disabled:true
            }
        }
		<?php endif; ?>
		<?php if($allowedActions['rename'] === TRUE): ?>
        ,$.contextMenu.separator,
        {'<?php echo translate("Rename");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.rename($(this).attr('href'), 'folder'); },
            icon:'img/contextmenu/rename.png'
            }
        }
		<?php endif; ?>         
		<?php if($allowedActions['delete'] === TRUE): ?>  		
		,$.contextMenu.separator,
		{'<?php echo translate("Delete");?>':{
            onclick:function(menuItem,menu) { 
                if(confirm('<?php echo translate("Do you really want to delete this folder and its contents?");?>')){
                    $.MediaBrowser.delete_all();
                } 
            },
            icon:'img/contextmenu/delete.gif',
            disabled:false
            }
        }
		<?php endif; ?>
    ];

    filecmenu = [
        {'<?php echo translate("Insert");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.insertFile(); },
            icon:'img/contextmenu/insert.png'
            }
        }
		<?php if($allowedActions['copy_paste'] === TRUE || $allowedActions['cut_paste'] === TRUE): ?>
        ,$.contextMenu.separator
		<?php endif; ?>
		<?php if($allowedActions['copy_paste'] === TRUE): ?>
        ,{'<?php echo translate("Copy");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.copy(); },
            icon:'img/contextmenu/copy.gif'
            }
        }
		<?php endif; ?>
		<?php if($allowedActions['cut_paste'] === TRUE): ?>
        ,{'<?php echo translate("Cut");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.cut(); },
            icon:'img/contextmenu/cut.gif'
            }
        }
		<?php endif; ?>
		<?php if($allowedActions['copy_paste'] === TRUE || $allowedActions['cut_paste'] === TRUE): ?>
        ,{'<?php echo translate("Paste");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.paste(); },
            icon:'img/contextmenu/paste.gif',
            disabled:true
            }
        }
        <?php endif; ?>
		<?php if($allowedActions['rename'] === TRUE): ?>
		,$.contextMenu.separator,
        {'<?php echo translate("Rename");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.rename($(this).attr('href'), 'file'); },
            icon:'img/contextmenu/rename.png'
            }
        }
		<?php endif; ?>
        <?php if($allowedActions['delete'] === TRUE): ?>
		,$.contextMenu.separator,
        {'<?php echo translate("Delete");?>':{
            onclick:function(menuItem,menu) {
                if(confirm('<?php echo translate("Do you really want to delete this file?");?>')){
                    $.MediaBrowser.delete_all();
                } 
            },
            icon:'img/contextmenu/delete.gif',
            disabled:false
            }
        }
		<?php endif; ?>
    ];

    imagecmenu = [
        {'<?php echo translate("Insert");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.insertFile(); },
            icon:'img/contextmenu/insert.png'
            }
        }
		<?php if($allowedActions['copy_paste'] === TRUE || $allowedActions['cut_paste'] === TRUE): ?>
		,$.contextMenu.separator
		<?php endif; ?>
		<?php if($allowedActions['copy_paste'] === TRUE): ?>
        ,{'<?php echo translate("Copy");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.copy(); },
            icon:'img/contextmenu/copy.gif'
            }
        }
		<?php endif; ?>
		<?php if($allowedActions['cut_paste'] === TRUE): ?>
		,{'<?php echo translate("Cut");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.cut(); },
            icon:'img/contextmenu/cut.gif'
            }
        }
		<?php endif; ?>
		<?php if($allowedActions['copy_paste'] === TRUE || $allowedActions['cut_paste'] === TRUE): ?>
		,{'<?php echo translate("Paste");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.paste(); },
            icon:'img/contextmenu/paste.gif',
            disabled:true
            }
        }
		<?php endif; ?>
		<?php if($allowedActions['rename'] === TRUE): ?>
		,$.contextMenu.separator,
        {'<?php echo translate("Rename");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.rename($(this).attr('href'), 'file'); },
            icon:'img/contextmenu/rename.png'
            }
        }
		<?php endif; ?>
		<?php if($allowedActions['delete'] === TRUE): ?>
		,$.contextMenu.separator,
        {'<?php echo translate("Delete");?>':{
            onclick:function(menuItem,menu) {
                if(confirm('<?php echo translate("Do you really want to delete this image?");?>')){
                    $.MediaBrowser.delete_all();
                } 
            },
            icon:'img/contextmenu/delete.gif',
            disabled:false
            }
        }
		<?php endif; ?>
    ];

    cmenu = [
        {'<?php echo translate("Large images");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.changeview('large_images'); },
            icon:'img/contextmenu/view_images_large.png'
            }
        },
        {'<?php echo translate("Small images");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.changeview('small_images'); },
            icon:'img/contextmenu/view_images_small.png'
            }
        },
        {'<?php echo translate("List");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.changeview('list'); },
            icon:'img/contextmenu/view_list.png'
            }
        },
        {'<?php echo translate("Details");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.changeview('details'); },
            icon:'img/contextmenu/view_details.png'
            }
        },
        {'<?php echo translate("Tiles");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.changeview('tiles'); },
            icon:'img/contextmenu/view_tiles.png'
            }
        },
        {'<?php echo translate("Content");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.changeview('content'); },
            icon:'img/contextmenu/view_content.png'
            }
        }
		<?php if($allowedActions['create_folder'] === TRUE): ?>
        ,$.contextMenu.separator,
        {'<?php echo translate("New folder");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.showLayer('newfolder'); },
            icon:'img/contextmenu/open.png'
            }
        }
		<?php endif; ?>
		<?php if($allowedActions['copy_paste'] === TRUE || $allowedActions['cut_paste'] === TRUE): ?>
        ,$.contextMenu.separator,
        {'<?php echo translate("Paste");?>':{
            onclick:function(menuItem,menu) { $.MediaBrowser.paste(); },
            icon:'img/contextmenu/paste.gif',
            disabled:true
            }
        }
		<?php endif; ?>
    ];


    // *** Media Browser ***//
    $.MediaBrowser.init();

    // Add context menu to the files, folders and images
    $.MediaBrowser.contextmenu();


    <?php if($allowedActions['upload'] === TRUE): ?>

        (function () {
            var r = new Flow({
                target: 'filesaver/upload.php',
                query: $.MediaBrowser.qObject,
                chunkSize: 1024*1024,
                testChunks: false
            });

            // Flow.js isn't supported, fall back on a different method
            if (!r.support) {
                $('.flow-error').show();
                $('.flow-controls').hide();
                return ;
            }
            $('.flow-error').hide();

            var mainCounter = 0;
            $('#main').on('dragenter', function(){
                mainCounter++;
                $(this).addClass('files-flow-dragover');
            });
            $('#main').on('dragend', function(){
                $(this).removeClass('files-flow-dragover');
            });
            $('#main').on('dragleave', function(){
                mainCounter--;
                if (mainCounter === 0) {
                    $(this).removeClass('files-flow-dragover');
                }
            });
            $('#main').on('drop', function(){
                $(this).removeClass('files-flow-dragover');
            });

            r.assignDrop($('#main')[0]);
            r.assignBrowse($('#browse')[0]);

            r.on('fileRemoved', function(file){
                if ($('.flow-file').length == 0)
                    $('.flow-progress, .flow-list').hide();
            });

            // Handle file add event
            r.on('fileAdded', function(file){
                $.MediaBrowser.showLayer('upload');

                // Show progress bar
                $('.flow-progress, .flow-list').show();

                $('.flow-progress .progress-resume-link').hide();
                $('.flow-progress .progress-pause-link').hide();
                $('.flow-progress .progress-cancel-link').hide();

                var splitName = file.name.split(".");
                var ext = splitName.pop();

                // Add the file to the list
                $('.flow-list').append(
                    '<li class="flow-file clearfix flow-file-'+file.uniqueIdentifier+'">' +
                    '<div class="flow-file-progress">' +
                    '<div class="progress-container"><div class="file-progress-bar"></div></div>' +
                    '</div> ' +
                    '<span class="icon"><span class="' + ext + '"></span></span> ' +
                    '<div class="flow-file-name"></div>' +
                    '<div class="flow-file-size"></div> ' +
                    '<div class="flow-file-actions">' +
                    '<a href="#" title="Resume upload" class="flow-file-resume"><i class="icon-play"></i></a>' +
                    '<a href="#" title="Pause upload" class="flow-file-pause"><i class="icon-pause"></i></a>' +
                    '<a href="#" title="Cancel upload" class="flow-file-cancel"><i class="icon-stop"></i></a>' +
                    '<a href="#" title="Delete upload" class="flow-file-delete"><i class="icon-stop"></i></a>' +
                    '</div>' +
                    '<div class="flow-file-status"></div> ' +
                    '</li>'
                );

                var $self = $('.flow-file-'+file.uniqueIdentifier);
                $self.find('.flow-file-name').text(file.name);
                $self.find('.flow-file-size').text(readablizeBytes(file.size));
                //$self.find('.flow-file-download').attr('href', '/download/' + file.uniqueIdentifier).hide();
                $self.find('.flow-file-pause').on('click', function ($event) {
                    $event.preventDefault();
                    $self.find('.flow-file-pause').hide();
                    $self.find('.flow-file-resume').show();
                    file.pause();
                });
                $self.find('.flow-file-resume').on('click', function ($event) {
                    $event.preventDefault();
                    $self.find('.flow-file-pause').show();
                    $self.find('.flow-file-resume').hide();
                    if (file.paused)
                        file.resume();
                    else
                        file.retry();
                });
                $self.find('.flow-file-delete').on('click', function ($event) {
                    $event.preventDefault();
                    file.cancel();
                    $self.remove();
                });
                $self.find('.flow-file-cancel').on('click', function ($event) {
                    $event.preventDefault();
                    file.cancel();
                    $self.remove();
                });
                $self.find('.flow-file-resume').hide();
                $self.find('.flow-file-delete').hide();
            });

            r.on('filesSubmitted', function(file) {
                r.upload();
            });

            r.on('complete', function(){
                // Hide pause/resume when the upload has completed
                $('.flow-progress .progress-resume-link').hide();
                $('.flow-progress .progress-pause-link').hide();
                $('.flow-progress .progress-cancel-link').show();

                if ($('.flow-file .error').length == 0) {
                    $('.flow-progress').hide();
                }
            });

            r.on('fileSuccess', function(file,message){
                var $self = $('.flow-file-'+file.uniqueIdentifier);
                // Reflect that the file upload has completed
                $self.find('.flow-file-status').removeClass('error').text('Upload completed');
                $self.find('.flow-file-pause, .flow-file-resume, .flow-file-cancel').remove();
                //$self.find('.flow-file-delete').show();
                //$self.find('.flow-file-download').attr('href', '/download/' + file.uniqueIdentifier).show();

                setTimeout(function(){
                    $self.animate({opacity: 0}, 1000, function(){
                        $self.remove();
                    });
                }, 2000);
            });

            r.on('fileError', function(file, message){
                // Reflect that the file upload has resulted in error
                $('.flow-file-'+file.uniqueIdentifier+' .flow-file-status').addClass('error').text(message);
                $('.flow-file-'+file.uniqueIdentifier+' .flow-file-pause').hide();
                $('.flow-file-'+file.uniqueIdentifier+' .flow-file-resume').show();
            });

            r.on('fileProgress', function(file){
                // Handle progress for both the file and the overall upload

                $('.flow-file-'+file.uniqueIdentifier+' .flow-file-status')
                    .html(Math.floor(file.progress()*100) + '% '
                        + readablizeBytes(file.averageSpeed) + '/s '
                        + secondsToStr(file.timeRemaining()) + ' remaining') ;

                $('.flow-file-'+file.uniqueIdentifier+' .file-progress-bar').css({width:Math.floor(file.progress()*100) + '%'});

                $('.progress-bar').css({width:Math.floor(r.progress()*100) + '%'});
            });

            r.on('uploadStart', function(){
                // Show pause, hide resume
                $('.flow-progress .progress-resume-link').hide();
                $('.flow-progress .progress-pause-link').show();
                $('.flow-progress .progress-cancel-link').show();
            });

            r.on('catchAll', function() {
                //console.log.apply(console, arguments);
            });

            window.r = {
                pause: function () {
                    r.pause();
                    // Show resume, hide pause
                    $('.flow-file-resume').show();
                    $('.flow-file-pause').hide();
                    $('.flow-progress .progress-resume-link').show();
                    $('.flow-progress .progress-pause-link').hide();
                },
                cancel: function() {
                    r.cancel();
                    $('.flow-file').remove();

                    $('.flow-progress, .flow-list').hide();
                },
                upload: function() {
                    $('.flow-file-pause').show();
                    $('.flow-file-resume').hide();
                    r.resume();
                },
                flow: r
            };

        })();

        function readablizeBytes(bytes) {
        var s = ['bytes', 'kB', 'MB', 'GB', 'TB', 'PB'];
        var e = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, e)).toFixed(2) + " " + s[e];
    }

        function secondsToStr (temp) {
        function numberEnding (number) {
            return (number > 1) ? 's' : '';
        }
        var years = Math.floor(temp / 31536000);
        if (years) {
            return years + ' year' + numberEnding(years);
        }
        var days = Math.floor((temp %= 31536000) / 86400);
        if (days) {
            return days + ' day' + numberEnding(days);
        }
        var hours = Math.floor((temp %= 86400) / 3600);
        if (hours) {
            return hours + ' hour' + numberEnding(hours);
        }
        var minutes = Math.floor((temp %= 3600) / 60);
        if (minutes) {
            return minutes + ' minute' + numberEnding(minutes);
        }
        var seconds = temp % 60;
        return seconds + ' second' + numberEnding(seconds);
    }

	<?php endif;?>
});	
//]]>
</script>
</head>

<body>

<input type="hidden" id="currentfolder" value="<?php echo $uploadpath;?>" />
<input type="hidden" id="currentview" value="<?php echo $viewLayout;?>" />

<!--
+++++++++++++++++++++++++++++++++
+     Address Bar & Search      +
+++++++++++++++++++++++++++++++++
-->
<?php $rootname = array_pop((explode("/", trim($uploadpath,"/")))); ?>
<div id="addressbar" class="ab">
  <ol>
        <li class="root"><span>&nbsp;</span></li>
        <li><a href="<?php echo $uploadpath;?>" title="<?php echo $rootname;?>"><span><?php echo $rootname;?></span></a></li>
    </ol>
    <div id="searchbar">
        <div class="cap"></div>
        <input name="search" id="search" value="<?php echo translate('Search');?>" />
        <div class="button"></div>
    </div>
</div>


<!--
+++++++++++++++++++++++++++++++++
+           Menu Bar            +
+++++++++++++++++++++++++++++++++
-->
<div id="navbar" class="nb">
    <ul class="left">
        <?php if($allowedActions['create_folder'] === TRUE): ?><li><a href="#" onclick="return $.MediaBrowser.showLayer('newfolder');" title="<?php echo translate('New folder');?>"><span><?php echo translate("New folder");?></span></a></li><?php endif; ?>
        <?php if($allowedActions['upload'] === TRUE): ?><li><a href="#" onclick="return $.MediaBrowser.showLayer('upload');" title="<?php echo translate('Upload');?>"><span><?php echo translate("Upload");?></span></a></li><?php endif; ?>
        <li class="label"><a href="#" onclick="return $.MediaBrowser.printClipboard();" title="<?php echo translate('Clipboard');?>"><span><?php echo translate("Clipboard");?>&nbsp;(&nbsp;<div id="cbItems">0</div>&nbsp;<?php echo translate("items");?>&nbsp;)</span></a></li>
    </ul>
    <ul class="right">
        <li><a href="#" title="<?php echo translate("Change view");?>"><span><?php echo translate("View");?></span></a>
            <ul>
                <li><a href="#" onclick="return $.MediaBrowser.changeview('large_images');" title="<?php echo translate('Large images');?>"><span class="icon large"></span><?php echo translate("Large images");?></a></li>
                <li><a href="#" onclick="return $.MediaBrowser.changeview('small_images');" title="<?php echo translate('Small images');?>"><span class="icon small"></span><?php echo translate("Small images");?></a></li>
                <li><a href="#" onclick="return $.MediaBrowser.changeview('list');" title="<?php echo translate('List');?>"><span class="icon list"></span><?php echo translate("List");?></a></li>
                <li><a href="#" onclick="return $.MediaBrowser.changeview('details');" title="<?php echo translate('Details');?>"><span class="icon details"></span><?php echo translate("Details");?></a></li>
                <li><a href="#" onclick="return $.MediaBrowser.changeview('tiles');" title="<?php echo translate('Tiles');?>"><span class="icon tiles"></span><?php echo translate("Tiles");?></a></li>
                <li><a href="#" onclick="return $.MediaBrowser.changeview('content');" title="<?php echo translate('Content');?>"><span class="icon content"></span><?php echo translate("Content");?></a></li>                
            </ul>
        </li>
        <?php if($allowedActions['settings'] === TRUE): ?><li><a href="#" onclick="return $.MediaBrowser.showLayer('settings');" class="settings" title="<?php echo translate('Settings');?>"><span><img src="img/gear.png" alt="<?php echo translate('Settings');?>" /></span></a></li><?php endif; ?>
		<li><a href="#" onclick="return $.MediaBrowser.showLayer('help');" class="help" title="<?php echo translate('Help');?>"><span><img src="img/help.png" alt="<?php echo translate('Help');?>" /></span></a></li>
    </ul>
</div>

<div id="message"></div>

<div id="explorer">

    <!--
    +++++++++++++++++++++++++++++++++
    +           Treeview            +
    +++++++++++++++++++++++++++++++++
    -->
    <div id="tree">
        <?php
            require_once("treeview.php");
        ?>
    </div>

    <div id="vertical-resize-handler" class="resize-grip"></div>

    <div id="main">


        <!--
        +++++++++++++++++++++++++++++++++
        +        Files & Folders        +
        +++++++++++++++++++++++++++++++++
        -->
        <div id="filelist" class="layer">
            <h2><?php echo $rootname?></h2>
            <select id="filters">
                <option value=""><?php echo translate("All files");?> (*.*)&nbsp;</option>
                <?php /* <option<?php echo (isset($_GET["filter"]) && $_GET["filter"] == "flash" ? ' selected="selected"' : '');?> value=".swf|.flv|.fla">Flash&nbsp;</option> */ ?>
                <option<?php echo (isset($_GET["filter"]) && $_GET["filter"] == "image" ? ' selected="selected"' : '');?> value=".bmp|.gif|.jpg|.jpeg|.png">Images&nbsp;</option>
                <option<?php echo (isset($_GET["filter"]) && $_GET["filter"] == "media" ? ' selected="selected"' : '');?> value=".avi|.flv|.mov|.mp3|.mp4|.mpeg|.mpg|.ogg|.wav|.wma|.wmv">Media&nbsp;</option>
                <?php
				    if(isset($customFilters)):
				    	foreach($customFilters as $key => $value){
				    		echo '<option value="'.$value.'">'.$key.'&nbsp;</option>'."\n";				
				    	}
				    endif;
				?>
			</select>
            <hr />
            <div id="files">
                <?php
                    // Get all folders in root upload folder but don't iterate
                    $dirs = getDirTree(STARTINGPATH, true, false);
                    
                    switch($viewLayout){
                        case 'large_images': 
                            require_once("view_images_large.php");
                            break;
                        case 'small_images': 
                            require_once("view_images_small.php");
                            break;
                        case 'list': 
                            require_once("view_list.php");
                            break;
                        case 'details': 
                            require_once("view_details.php");
                            break;
                        case 'tiles':
                            require_once("view_tiles.php");
                            break;
                        default: //Content
                            require_once("view_content.php");
                            break;
                    }
                ?>
            </div>
        </div>


        <!--
        +++++++++++++++++++++++++++++++++
        +      Create a new folder      +
        +++++++++++++++++++++++++++++++++
        -->
		<?php if($allowedActions['create_folder'] === TRUE): ?>
        <div id="newfolder" class="layer">
            <h2><?php echo translate("Add a new folder")?></h2>
            <a href="#" class="close" onclick="$.MediaBrowser.hideLayer(); $.MediaBrowser.loadFolder($.MediaBrowser.currentFolder); return false;">X</a>
            <hr />
            <div class="window">
				<form id="newfolderform" name="newfolderform" onsubmit="$.MediaBrowser.newFolder(); return false;">
	            <div class="padding10">	
	                <div class="height20">
	                	<label for="folderpath"><?php echo translate("New folder is created in");?>: <input class="path" type="text" name="folderpath" id="folderpath" readonly="readonly"/></label>
	                </div>
	                <div class="paddingtop10 height20 marginbottom5">
	                    <label for="newfoldername"><?php echo translate("Name of the new folder");?>: <input class="path border" type="text" name="foldername" id="foldername" /></label>
	                </div>
	                <div class="paddingtop10 height20 marginbottom5">
	                    <button class="btn" type="submit"><?php echo translate("Create folder");?></button>
	                    <button class="btn" type="button" onclick="$.MediaBrowser.hideLayer(); $.MediaBrowser.loadFolder($.MediaBrowser.currentFolder); return false;"><?php echo translate("Close");?></button>
	                </div>
	            </div>
	            </form>
			</div>
        </div>
		<?php endif; ?>


        <!--
        +++++++++++++++++++++++++++++++++
        +      Upload a new file        +
        +++++++++++++++++++++++++++++++++
        -->
		<?php if($allowedActions['upload'] === TRUE): ?>
        <div id="upload" class="layer">
            <h2><?php echo translate("Upload a new file")?></h2>
            <a href="#" class="close" onclick="r.cancel(); $.MediaBrowser.hideLayer(); $.MediaBrowser.loadFolder($.MediaBrowser.currentFolder); return false;">X</a>
            <hr />
            <div class="window">

                <div class="flow-error">
                    Your browser, unfortunately, is not supported by Flow.js. The library requires support for <a href="http://www.w3.org/TR/FileAPI/">the HTML5 File API</a> along with <a href="http://www.w3.org/TR/FileAPI/#normalization-of-params">file slicing</a>.
                </div>
                <div class="flow-controls">
                    <div class="flow-controls-inside">

                        <div class="flow-uploadp">
                            <label for="uploadpath"><?php echo translate("Currently uploading in folder");?>: <input class="path" type="text" name="uploadpath" id="uploadpath" readonly="readonly" /></label>
                        </div>

                        <div class="flow-browse">
                            <input type="button" value="<?php echo translate("Select your file");?>" id="browse" class="btn" /> <?php echo translate("or Drag and Drop your files");?>
                            <span class="note">(<?php echo sprintf(translate("Upload limited to %d MB!"), ($max_file_size_in_bytes/(1024*1024)));?>)</span>
                        </div>

                    <?php /*
                        <div class="fieldset flash" id="fsUploadProgress">
	                    <span class="legend"><?php echo translate("Upload queue");?></span>
	                </div>
	                <div class="paddingleft10">
	                	<button id="btnCancel" type="button"><?php echo translate('Cancel all uploads');?></button>
	                    <button type="button" onclick="$.MediaBrowser.hideLayer(); $.MediaBrowser.loadFolder($.MediaBrowser.currentFolder); return false;"><?php echo translate('Close');?></button>
	                </div>
                    */ ?>

                    <div class="flow-progress" style="display: none">
                        <div class="progress-container"><div class="progress-bar"></div></div>
                        <div class="progress-pause">
                            <a href="#" onclick="r.upload(); return(false);" class="btn btn-info progress-resume-link"><i class="icon-play icon-white"></i> Resume upload</a>
                            <a href="#" onclick="r.pause(); return(false);" class="btn btn-info progress-pause-link"><i class="icon-pause icon-white"></i> Pause upload</a>
                            <a href="#" onclick="r.cancel(); return(false);" class="btn btn-danger progress-cancel-link"><i class="icon-stop icon-white"></i> Cancel upload</a>
                        </div>
                        <div class="progress-text"></div>
                    </div>

                    <div class="flow-file-list">
                        <ul class="flow-list"></ul>
                    </div>


                    </div>
                </div>
			</div>
        </div>
		<?php endif; ?>


        <!--
        +++++++++++++++++++++++++++++++++
        +            Settings           +
        +++++++++++++++++++++++++++++++++
        -->
		<?php if($allowedActions['settings'] === TRUE): ?>
        <div id="settings" class="layer" style="display:none;">
            <h2><?php echo translate("Settings"); ?></h2>
            <a href="#" class="btn close" onclick="$.MediaBrowser.hideLayer(); return false;" title="<?php echo translate('Close')?>"><?php echo translate("Close")?></a>
            <hr />
            <div class="window">
            	<div class="padding10">
                    <dl>
                        <dt><?php echo translate("Language");?></dt>
                            <dd>
                                <select id="settings_language">
                                    <?php
                                       require_once('lang/languages.php');
                                       
                                       foreach($languages as $key => $value){
                                           printf('<option%s value="%s">%s</option>',($language == $value ? ' selected="selected"' : '') , $value, $key);
                                       }
                                    ?>
                                </select>
                            </dd>
                        <dt><?php echo translate("Theme");?></dt>
                            <dd>
                            	<select id="settings_skin">
									<?php
									   require_once('skins/skins.php');
									   
									   $skins["Redmond"] = "";
									   asort($skins);
									   
									   foreach($skins as $key => $value){
									       printf('<option%s value="%s">%s</option>', ($skin == $value ? ' selected="selected"' : ''), $value, $key);
									   }
									?>
                            	</select>
                            </dd>
                    </dl>
					<p><?php echo translate("Cookies need to be enabled to save your settings!");?></p>
					<hr />
					<button type="button" onclick="$.MediaBrowser.saveSettings(); return false;"><?php echo translate("Save settings");?></button>
					<button type="button" onclick="$.MediaBrowser.hideLayer(); return false;"><?php echo translate("Close");?></button>
				</div>
            </div> 
        </div>
		<?php endif; ?>
		

        <!--
        +++++++++++++++++++++++++++++++++
        +              Help             +
        +++++++++++++++++++++++++++++++++
        -->
        <div id="help" class="layer" style="display:none;">
            <h2>PDW File Browser v1.4+</h2>
            <a href="#" class="close" onclick="$.MediaBrowser.hideLayer(); return false;" title="<?php echo translate("Close")?>"><?php echo translate("Close")?></a>
            <hr />
            <div class="window">
				<div class="padding10">
	                <p>Author: Guido Neele<br />
	                Date: October 10, 2010<br />
                    Date: June 24, 2018<br />
	                Url: http://www.neele.name</p>
	                <p>Copyright (c) 2010 Guido Neele</p>
                    <p>Copyright (c) 2018 Maks T.</p>
                    <p>Permission is hereby granted, free of charge, to any person obtaining a copy
	                of this software and associated documentation files (the "Software"), to deal
	                in the Software without restriction, including without limitation the rights
	                to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	                copies of the Software, and to permit persons to whom the Software is
	                furnished to do so, subject to the following conditions:</p>
	                <p>The above copyright notice and this permission notice shall be included in
	                all copies or substantial portions of the Software.</p>
	                <p>THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	                IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	                FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	                AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	                LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	                OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	                THE SOFTWARE.</p>
	                <p>This plugin makes use of:</p>
	                <ul>
	                    <li>jQuery (jquery.com)</li>
	                    <li>jQuery.contextmenu - Matt Kruse (javascripttoolbox.com)</li>
	                    <li>Javascript functions urlencode/urldecode - (phpjs.org)</li>
	                    <li>Flow.js is a JavaScript library providing multiple simultaneous, stable and resumable uploads via the HTML5 File API</li>
	                    <li>createCookie - Peter-Paul Koch (http://www.quirksmode.org/js/cookies.html)</li>
	                    <li>Javascript function printf - Dav Glass extension for the Yahoo UI Library</li>
						<li>Modified version of Slimbox 2 - Christophe Beyls (http://www.digitalia.be)</li>
	                </ul>
	                <p><button type="button" class="btn" onclick="$.MediaBrowser.hideLayer(); return false;"><?php echo translate("Close");?></button></p>
	            </div>
			</div> 
        </div>
    </div>
</div>


<!--
+++++++++++++++++++++++++++++++++
+     File Information Pane     +
+++++++++++++++++++++++++++++++++
-->
<div id="file-specs">
    <div id="info">
    <?php
        require_once("file_specs.php");
    ?>
    </div>

    <form id="fileform" name="fileform" onsubmit="$.MediaBrowser.insertFile(); return false;">
        <label for="file"><?php echo translate("File");?></label>
        <input type="text" name="file" id="file" readonly="readonly" value="" style="width: 400px;"/>

        <?php if ($editor != "standalone"): ?>
            <button type="submit"><?php echo translate("Insert");?></button>
        <?php endif; ?>

		<div>
            <?php
                $checked = isset($_COOKIE["absoluteURL"]) ? $_COOKIE["absoluteURL"] : $absolute_url;
            ?>
			<label for="absolute_url"><input class="checkbox" type="checkbox" id="absolute_url" <?php echo $absolute_url_disabled ? 'disabled="disabled" ' : '';?><?php echo $checked ? 'checked="checked" ' : '';?>/><?php echo translate("Absolute URL with hostname");?></label>
		</div>
    </form>
</div>
</body>
</html>
