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
 * This module produces various thumbnails
 */
class thumbnailer {
    private static $smp = null;
    private static $conf = null;
    
    public function __construct($smp) {
        self::$smp = $smp;
        
        include self::$smp->getRoot() . "/extensions/thumbnailer/settings.php";
        self::$conf = $conf;
        
        self::$smp->api->registerExtension("getting thumbnail", array("name" => "thumbnailer", "function" => "get_thumbnail"));
    }    
    
    public function get_thumbnail(&$file, &$fullpath, &$thumbHTML, $size) {
        $extension = substr($file->filename, strrpos($file->filename, ".") + 1);
        
        $html = $thumbHTML;
        
        switch(strtolower($extension)) {
            case "jpg":
            case "jpeg":
            case "bmp":
            case "gif":
            case "png":
                $originalPath = self::$smp->getRoot() . "/upload/" . $file->account_id . "/" . $file->filename; 
                $thumbPath = self::$smp->getRoot() . "/upload/" . $file->account_id . "/" . basename($fullpath, "." . $extension) . "_" . $size . "." . $extension;
                $thumbWebPath = self::$smp->getWebRoot() . "/upload/" . $file->account_id . "/" . basename($fullpath, "." . $extension) . "_" . $size . "." . $extension; 
                
                if(!is_file($thumbPath)) {
                    $im = new Imagick($originalPath);
                    
                    if($im->getImageWidth() > $size) {
                        $im->thumbnailImage($size, null);
                        $im->writeImage($thumbPath);
                
                        $html = "<img src=\"" . $thumbWebPath . "\" alt=\"" . $file->filename . "\" />";
                    }
                    
                    $im->clear();                        
                    $im->destroy();
                }
                break;
        }
        
        $thumbHTML = $html;
    }
}

?>