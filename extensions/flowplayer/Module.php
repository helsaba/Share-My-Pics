<?php

/*******************************************************************************
    ShareMyPics, a free twitpic clone
    Copyright (C) 2012 Jimmy Rudolf

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*******************************************************************************/

/**
 * This module, instead of thumbnails, provides a video player for flv files
 */
class flowplayer {
    private static $smp = null;
    private static $conf = null;
    
    public function __construct($smp) {
        self::$smp = $smp;
        
        self::$smp->registerJavaScript(self::$smp->getWebRoot() . "/extensions/flowplayer/flowplayer-3.2.6.min.js");
        
        self::$smp->api->registerExtension("getting thumbnail", array("name" => "flowplayer", "function" => "get_thumbnail"));
    }    
    
    public function get_thumbnail(&$file, &$fullpath, &$thumbHTML, $size) {
        $extension = substr($file->filename, strrpos($file->filename, ".") + 1);
            
        if($size > 300) {

            $html = $thumbHTML;

            switch(strtolower($extension)) {
                case "flv":
                    $html = '<a href="' . self::$smp->getWebRoot() . "/upload/" . $file->account_id . "/" . $file->filename . '" id="player" style="display: block;width:' . $size . 'px;height: ' . ($size/2) . 'px"></a>';
                    $html .= '<script>flowplayer("player", "' . self::$smp->getWebRoot() . '/extensions/flowplayer/flowplayer-3.2.7.swf");</script>';
                    break;
            }

            $thumbHTML = $html;
        }
        else {  
            switch(strtolower($extension)) {
                case "flv":
                    $thumbHTML = "<img src=\"" . self::$smp->getWebRoot() . "/extensions/flowplayer/video.png\" alt=\"" . $file->filename . "\" />";            
                    break;
            }
        }
    }
}

?>