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
 * This module shows the picture's exif informations
 */
class widget_exif {
    private static $smp = null;
    private static $conf = null;
    
    public function __construct($smp) {
        self::$smp = $smp;
        
        include self::$smp->getRoot() . "/extensions/widget_exif/settings.php";
        self::$conf = $conf;
        
        self::$smp->api->registerExtension("file widget", array("name" => "widget_exif", "function" => "get_exif"));
    }    
    
    public function get_exif(&$file) {
        $extension = substr($file->filename, strrpos($file->filename, ".") + 1);
        
        $html = "";
        
        switch(strtolower($extension)) {
            case "jpg":
            case "jpeg":
            case "bmp":
            case "gif":
            case "png":
                $originalPath = self::$smp->getRoot() . "/upload/" . $file->account_id . "/" . $file->filename; 
                
                $exif = exif_read_data($originalPath, null, true);
                
                foreach($exif as $type => $datas) {
                    $html .= "<h3>" . $type . "</h3>";
                    
                    $html .= "<table>";
                    
                    foreach($datas as $key => $val) {
                        if(in_array($key, self::$conf["widget_exif"]["Datas"])) {
                            $html .= "<tr><th>" . $key . "</th><td>" . $val . "</td></tr>";
                        }
                    }
                    
                    $html .= "</table>";
                }
                break;
        }
        
        if($html != "") {
            $feedback = new Feedback(
                "widget_exit", 
                "get_exif", 
                FEEDBACK_WIDGET,
                false,
                Array(
                    "title"     => _("Exif datas"),   // Will be displayed on top of the widget
                    "content"   => $html
                )
            );
            
            self::$smp->api->sendFeedback("file widget", $feedback);
        }
    }
}

?>