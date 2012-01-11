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
 * This module relies on clamav antivirus to check
 * any incoming file
 */
class clamav {
    private static $smp = null;
    private static $conf = null;
    
    public function __construct($smp) {
        self::$smp = $smp;
        
        include self::$smp->getRoot() . "/extensions/clamav/settings.php";
        self::$conf = $conf;
        
        self::$smp->api->registerExtension("before uploading", array("name" => "clamav", "function" => "check"));
        self::$smp->api->registerExtension("after uploading", array("name" => "clamav", "function" => "tag"));
    }    
    
    public function check(&$title, &$keywords, &$file, &$target, &$currentUser) {
        exec(self::$conf["clamav"]["ClamscanPath"] . " \"" . $file["tmp_name"] . "\"", $out);
        
        foreach($out as $line) {
            if(strstr($line, "Infected files:") !== false) {
                $arr = explode(":", $line);
                
                $count = $arr[1];
                
                if($count > 0) {
                    $feedback = new Feedback(
                        "clamav",
                        "check",
                        FEEDBACK_ERROR,
                        true,
                        sprintf(_("The file %s appears to be a virus !"), basename($file["name"]))
                    );
                    
                    self::$smp->api->sendFeedback("before uploading", $feedback);
                    
                    return;
                }
            }
        }
    }
    
    public function tag(&$file_id, &$title, &$keywords, &$file, &$url, &$target, &$currentUser) {
        $db = self::$smp->database;
        
        $fileEntry = $db->selectOne("Files", "*", "file_id='" . $file_id . "'");
        
        $meta = $fileEntry->meta;
        
        if($meta == "" || $meta == null) {
            $meta = Array();
        } else {
            $meta = unserialize($meta);
        }
        
        $meta["clamav"] = _("Checked using clamav antivirus");
        
        $meta = serialize($meta);
        
        $db->update("Files", array("meta" => $meta), "file_id='" . $file_id . "'");
    }
}

?>