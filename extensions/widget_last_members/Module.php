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
 * A widget that shows the last members :)
 */
class widget_last_members {
    private static $smp = null;
    private static $conf = null;
    
    public function __construct($smp) {
        self::$smp = $smp;
        
        include self::$smp->getRoot() . "/extensions/widget_last_members/settings.php";
        self::$conf = $conf;
        
        /* Registers with the "timeline widget" event name" */
        self::$smp->api->registerExtension("timeline widget", array("name" => "widget_last_members", "function" => "show_last_members"));
    }    
    
    public function show_last_members() {
        $lastMembersList = self::$smp->database->selectList("Accounts", "*", "activated='1'", "date_registered DESC", self::$conf["widget_last_members"]["Count"]);
        $lastMembers = Array();
        
        for($i = 0; $i < count($lastMembersList); $i++) {
            $entry = $lastMembersList[$i];        
            
            $avatarUrl = self::$smp->getTemplateWebRoot() . "/images/anonymous.png";
            $avatarUrl = "http://www.gravatar.com/avatar/" . md5($entry->email) . "?d=" . urlencode($avatarUrl) . "s=80";
            
            $lastMembers[$i] = Array(
                "account_id"    => $entry->account_id,
                "avatar_url"    => $avatarUrl,
                "avatar_text"   => sprintf(_("%s's avatar"), $entry->username)
            );
        }
        
        $html = "";
        
        foreach($lastMembers as $index => $member) {
            $html .= "<a title=\"" . $member["avatar_text"] . "\" href=\"" . self::$smp->getWebRoot() . "/timeline.php?aid=" . $member["account_id"] . "\" class=\"profile_link\"><img src=\"" . $member["avatar_url"] . "\" alt=\"" . $member["avatar_text"] . "\" /></a>";
        }
        
        $feedback = new Feedback(
            "widget_last_members", 
            "show_last_members", 
            FEEDBACK_WIDGET,
            false,
            Array(
                "title"     => _("Last members"),   // Will be displayed on top of the widget
                "content"   => $html
            )
        );
        
        self::$smp->api->sendFeedback("timeline widget", $feedback);
    }
}

?>