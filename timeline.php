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

$SMP->setPageTitle(_("Public timeline"));

$SMP->registerStyleSheet($SMP->getTemplateWebRoot() . "/css/timeline.css");

/* Widgets */

$SMP->api->execute("timeline widget");

$feedbacks = $SMP->api->getFeedbacks("timeline widget");

$widgets = Array();

if(count($feedbacks) > 0) {
    foreach($feedbacks as $feedback) {
        $widgets[] = $feedback->message;
    }
}

/* Timeline(s) */

$where = "f.account_id=a.account_id";

if(isset($_GET["aid"])) {
    if(is_numeric($_GET["aid"])) {
        $account = $SMP->database->selectOne("Accounts", "*", "account_id='" . $_GET["aid"] . "'");

        if($account != null) {
            $where .= " AND a.account_id='" . $_GET["aid"] . "'";
            $SMP->setPageTitle(sprintf(_("%s's timeline"), $account->username));
            
            $isPublicTimeLine = false;
        } else {
            $SMP->go_to($SMP->getWebRoot() . "/timeline.php");
        }
    }
}

$list = null;
$q = "";

if(isset($_GET["q"])) {    
    $q = filter_var($_GET["q"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    $SMP->setPageTitle(sprintf(_("Search results for \"%s\""), $q));
    
    $_list = $SMP->database->selectList("Keywords AS k, FilesKeywords AS fk", "k.*, fk.*", "k.keyword_id=fk.keyword_id AND k.keyword LIKE '%" . $q . "%'", "k.keyword ASC");
    
    $list = Array();
    
    foreach($_list as $item) {
        $list[] = $SMP->database->selectOne("Files AS f, Accounts AS a", "f.*, a.*", $where . " AND f.file_id='" . $item->file_id . "'");
    }   
} else {
    $list = $SMP->database->selectList("Files AS f, Accounts AS a", "f.*, a.*", $where, "f.uploaded DESC");
}

$updates = Array();

for($i = 0; $i < count($list); $i++) {
    $entry = $list[$i];        
    
    $avatarUrl = $SMP->getTemplateWebRoot() . "/images/anonymous.png";
    $avatarUrl = "http://www.gravatar.com/avatar/" . md5($entry->email) . "?d=" . urlencode($avatarUrl) . "s=80";
    
    $date = $SMP->formatSQLDate($entry->uploaded);
    
    $keywordsList = $SMP->database->selectList("Keywords AS k, FilesKeywords AS fk", "k.*, fk.*", "k.keyword_id=fk.keyword_id AND fk.file_id='" . $entry->file_id . "'", "k.keyword ASC");
    $keywords = Array();    
    
    foreach($keywordsList as $keyword) {
        $keywords[] = "<a class=\"keyword\" href=\"" . $SMP->getWebRoot() . "/timeline.php?q=" . $keyword->keyword . "\">" . $keyword->keyword . "</a>";
    }
    
    $updates[$i] = Array(
        "username"      => "<a href=\"" . $SMP->getWebRoot() . "/timeline.php?aid=" . $entry->account_id . "\">" . $entry->username . "</a>",
        "title"         => "<a href=\"" . $entry->url . "\">" . ($entry->title != "" ? $entry->title : $entry->filename) . "</a>",
        "avatar_url"    => $avatarUrl,
        "avatar_text"   => sprintf(_("%s's avatar"), $entry->username),
        "date"          => $date,
        "keywords"      => implode(" ", $keywords),
        "thumbnail"     => $SMP->upload->getThumbnail($entry->file_id, $conf["Images"]["Sizes"]["thumbnail"])
    );
}

/* Template */

$template->assign("widgets", $widgets);

$template->assign("updates", $updates);

$template->assign("timeline_url", $SMP->getWebRoot() . "/timeline.php");
$template->assign("search_button", _("Search"));
$template->assign("search_keywords", $q);

$template->assign("no_result", _("This timeline is still empty !"));
$template->assign("timeline_name", $SMP->getPageTitle());

$SMP->show();

?>