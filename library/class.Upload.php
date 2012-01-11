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

define("UPLOAD_SUCCESS", 0);
define("UPLOAD_NO_FILE", 1);
define("UPLOAD_CANCELLED", 2);
define("UPLOAD_EXTENSION_DISALLOWED", 4);
define("UPLOAD_TOO_BIG", 8);

/**
 * A class to handle file uploads
 */
class Upload {
    
    private $smp = null;
    private $db = null;
    private $conf = null;
    private $api = null;
    
    public $lastUpload = null;
    
    public function __construct($smp) {
        $this->smp = $smp;
        $this->db = $this->smp->database; // Just a shortcut
        $this->conf = $this->smp->getConf();
        $this->api = $this->smp->api;
    }
    
    /**
     * Upload a file based on the form values
     */
    public function upload() {
        $title = filter_var($_POST["title"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $keywords = filter_var($_POST["keywords"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $file = $_FILES["file"];
        
        if($file["error"] == UPLOAD_ERR_NO_FILE) {
            return UPLOAD_NO_FILE;
        }
        
        if($file["error"] == UPLOAD_ERR_INI_SIZE) {
            return UPLOAD_TOO_BIG;
        }
        
        $extension = substr($file["name"], strrpos($file["name"], ".") + 1);
        
        if(!in_array(strtolower($extension), $this->conf["Upload"]["AllowedExtensions"])) {
            return UPLOAD_EXTENSION_DISALLOWED;
        }
        
        $currentUser = $this->smp->auth->getLoggedInUser();
        
        $target = $this->smp->getRoot() . "/upload/" . $currentUser->account_id;
        
        if(!is_dir($target)) {
            mkdir($target);
        }
        
        $target .= "/" . basename($file["name"]);
        
        $this->api->execute("before uploading", array(&$title, &$keywords, &$file, &$target, &$currentUser));
        
        if($this->api->feedbacksExist("before uploading")) {
            foreach($this->api->getFeedbacks("before uploading") as $feedback) {
                if($feedback->cancelAction) {
                    return UPLOAD_CANCELLED;
                }
            }
        }
        
        move_uploaded_file($file["tmp_name"], $target);
        
        $file_id = $this->db->insert("Files", Array(
            "account_id"    => $currentUser->account_id,
            "filename"      => basename($target),
            "title"         => $title,
            "uploaded"      => date("Y-m-d H:i:s")
        ));
        
        $url = $this->smp->getWebRoot() . "/f.php?i=" . $file_id;
        
        $this->api->execute("before inserting file url", array(&$file_id, &$url));
        
        $this->smp->database->update("Files", Array("url" => $url), "file_id='" . $file_id . "'");
        
        if($keywords != "") {
            $keywordsList = preg_split("/[\s,]+/", $keywords);
            
            foreach($keywordsList as $keyword) {
                $keyword = filter_var($keyword, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $keyword_id = 0;
                
                $test = $this->db->selectOne("Keywords", "*", "keyword='" . $keyword . "'");
                
                if($test != null) {
                    $keyword_id = $test->keyword_id;
                } else {
                    $keyword_id = $this->db->insert("Keywords", array("keyword" => $keyword));
                }
                
                $this->db->insert("FilesKeywords", Array("file_id" => $file_id, "keyword_id" => $keyword_id));
            }
        }
        
        $this->api->execute("after uploading", array(&$file_id, &$title, &$keywords, &$file, &$url, &$target, &$currentUser));
        
        $this->lastUpload = $file_id;
        
        return UPLOAD_SUCCESS;
    }
    
    /**
     * Retrieves a file using it's id
     */
    public function getFileById($id) {
        return $this->smp->database->selectOne("Files", "*", "file_id='" . $id . "'");
    }
    
    /**
     * Gets the thumbnail of the specified file
     */
    public function getThumbnail($file_id, $size) {
        $file = $this->getFileById($file_id);
        
        $fullpath = $this->smp->getWebRoot() . "/upload/" . $file->account_id . "/" . $file->filename;
        
        $thumbHTML = "<img class=\"thumbnail_img\" src=\"" . $fullpath . "\" alt=\"" . _("Thumbnail") . "\" />";
        
        $this->api->execute("getting thumbnail", Array(&$file, &$fullpath, &$thumbHTML, $size));
        
        return $thumbHTML;
    }

    /**
     * Gets a readable filesize
     * http://fr.php.net/manual/fr/function.filesize.php#106569
     */
    public function getFileSize($fullpath) {
        $size = filesize($fullpath);
        $decimals = 2;

        $sz = 'BKMGTP';
        $factor = floor((strlen($size) - 1) / 3);
        
        return sprintf("%.{$decimals}f", $size / pow(1024, $factor)) . @$sz[$factor];
    }
}

?>