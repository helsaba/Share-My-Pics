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
 * This module lets users to link a facebook account to their SMP account
 */
class facebook {
    private static $smp = null;
    private static $conf = null;
    
    private static $facebook = null;
    
    public function __construct($smp) {
        self::$smp = $smp;
        
        include self::$smp->getRoot() . "/extensions/facebook/settings.php";
        self::$conf = $conf;
        
        self::$smp->api->registerExtension("social network show status", array("name" => "facebook", "function" => "show_status"));
        self::$smp->api->registerExtension("after uploading", array("name" => "facebook", "function" => "publish"));
    }
    
    public function show_status() {
        $linked = false;
        $html = "";
        $defaultLink = "<img src=\"" . self::$smp->getWebRoot() . "/extensions/facebook/facebook.png\" alt=\"facebook\" class=\"social_network_icon\" /> <a href=\"" . self::$smp->getWebRoot() . "/extensions/facebook/login.php\">" . _("Click here to link your facebook account") . "</a>";
        
        $currentUser = self::$smp->auth->getLoggedInUser();
        
        if($currentUser->meta != null && $currentUser->meta != "") {
            $meta = unserialize($currentUser->meta);
        
            if(array_key_exists("facebook_access_token", $meta) && $meta["facebook_access_token"] != null && $meta["facebook_access_token"] != "") {
                $linked = true;
            }
        }
        
        if($linked) {
            $graph_url = "https://graph.facebook.com/me?access_token=" . $meta['facebook_access_token'];
            $content = @file_get_contents($graph_url);
            
            if($content != null && $content != "") {
                $user = json_decode($content);
            
                $smpConf = self::$smp->getConf();
            
                $html = "<img src=\"" . self::$smp->getWebRoot() . "/extensions/facebook/facebook.png\" alt=\"facebook\" class=\"social_network_icon\" /> " . sprintf(_("Account \"%s\" linked with %s"), $user->name, $smpConf["Site"]["Title"]) . " <a href=\"" . self::$smp->getWebRoot() . "/extensions/facebook/logout.php\">" . _("Click here to unlink your facebook account") . "</a>";
            } else {
                $html = $defaultLink;
            }
        } else {
            $html = $defaultLink;
        }
        
        $feedback = new Feedback(
            "facebook",
            "show_status",
            FEEDBACK_INFO,
            false,
            $html
        );
        
        self::$smp->api->sendFeedback("social network show status", $feedback);
    }
    
    public function publish(&$file_id, &$title, &$keywords, &$file, &$url, &$target, &$currentUser) {
        $currentUser = self::$smp->auth->getLoggedInUser();
        
        if($currentUser->meta != null && $currentUser->meta != "") {
            $meta = unserialize($currentUser->meta);
            
            $fields = Array(
                "access_token"  => $meta["facebook_access_token"],
                "message"       => $title . " : " . $url
            );
        
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_URL,  "https://graph.facebook.com/" . $meta["facebook_user_id"] . "/feed");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $id = curl_exec($ch);
        }
    }
}

?>