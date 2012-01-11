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

include "library/class.SMP.php";

$SMP = new SMP();

$template = $SMP->getTemplate();
$conf = $SMP->getConf();

$SMP->setPageTitle(_("File's details"));

$SMP->registerStyleSheet($SMP->getTemplateWebRoot() . "/css/f.css");

$error = "";
$info = "";

$file_title = "";
$thumbnail = "";

$uploader = "";
$date = "";
$filetype = "";
$filesize = "";
$meta = Array();
$file_url = "";
$tags = "";
$widgets = Array();

if(is_numeric($_GET["i"])) {
    $file = $SMP->upload->getFileById($_GET["i"]);
    
    if($file != null) {
        /* Widgets */
        
        $SMP->api->execute("file widget", Array(&$file));
        
        $feedbacks = $SMP->api->getFeedbacks("file widget");
        
        $widgets = Array();
        
        if(count($feedbacks) > 0) {
            foreach($feedbacks as $feedback) {
                $widgets[] = $feedback->message;
            }
        }

        $file_title = $file->title;
        $filePath = $SMP->getRoot() . "/upload/" . $file->account_id . "/" . $file->filename;
        $thumbnail = $SMP->upload->getThumbnail($file->file_id, $conf["Images"]["Sizes"]["in_page"]);
        
        $account = $SMP->auth->getAccountById($file->account_id);
        
        $uploader = "<a href=\"" . $SMP->getWebRoot() . "/timeline.php?aid=" . $account->account_id . "\">" . $account->username . "</a>";
        $date = $SMP->formatSQLDate($file->uploaded);
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $filetype = finfo_file($finfo, $filePath);
        
        $filesize = $SMP->upload->getFileSize($filePath);
        
        if($file->meta != "" && $file->meta != null) {
            $meta = unserialize($file->meta);
        }
        
        $file_url = $SMP->getWebRoot() . "/upload/" . $file->account_id . "/" . $file->filename;
        
        $keywordsList = $SMP->database->selectList("Keywords AS k, FilesKeywords AS fk", "k.*, fk.*", "k.keyword_id=fk.keyword_id AND fk.file_id='" . $file->file_id . "'", "k.keyword ASC");
        $keywords = Array();    
        
        foreach($keywordsList as $keyword) {
            $keywords[] = "<a class=\"keyword\" href=\"" . $SMP->getWebRoot() . "/timeline.php?q=" . $keyword->keyword . "\">" . $keyword->keyword . "</a>";
        }
        
        $tags = implode(" ", $keywords);
    } else {
        $error = _("The specified file does not exists");
    }
} else {
    $error = _("The specified file identifier is invalid");
}

$template->assign("error", $error);
$template->assign("info", $info);

$template->assign("widgets", $widgets);

$template->assign("file_title", $file_title);
$template->assign("thumbnail", $thumbnail);

$template->assign("file_url", $file_url);

$template->assign("click_to_enlarge", _("Click on the picture to get to the original file"));

$template->assign("file_infos_title", _("File details"));
$template->assign("uploader_label", _("Uploader"));
$template->assign("uploader", $uploader);
$template->assign("date_label", _("Date uploaded"));
$template->assign("date", $date);
$template->assign("filetype_label", _("File type"));
$template->assign("filetype", $filetype);
$template->assign("filesize_label", _("File size"));
$template->assign("filesize", $filesize);
$template->assign("meta_label", _("Other informations"));
$template->assign("meta", $meta);
$template->assign("tags_label", _("Keywords"));
$template->assign("tags", $tags);

$SMP->show();

?>