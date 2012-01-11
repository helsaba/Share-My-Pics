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
 * This module lets users to link a twitter account to their SMP account
 */
class twitter {
    private static $smp = null;
    private static $conf = null;
    
    private static $facebook = null;
    
    public function __construct($smp) {
        self::$smp = $smp;
        
        include self::$smp->getRoot() . "/extensions/twitter/settings.php";
        self::$conf = $conf;
        
        self::$smp->api->registerExtension("social network show status", array("name" => "twitter", "function" => "show_status"));
        self::$smp->api->registerExtension("after uploading", array("name" => "twitter", "function" => "publish"));
    }
    
    public function show_status() {
        $linked = false;
        $html = "";
        $defaultLink = "<img src=\"" . self::$smp->getWebRoot() . "/extensions/twitter/twitter.png\" alt=\"twitter\" class=\"social_network_icon\" /> <a href=\"" . self::$smp->getWebRoot() . "/extensions/twitter/login.php\">" . _("Click here to link your twitter account") . "</a>";
        
        $currentUser = self::$smp->auth->getLoggedInUser();
        
        if($currentUser->meta != null && $currentUser->meta != "") {
            $meta = unserialize($currentUser->meta);
        
            if(array_key_exists("twitter_access_token", $meta) && $meta["twitter_access_token"] != null && $meta["twitter_access_token"] != "" &&
                array_key_exists("twitter_access_secret", $meta) && $meta["twitter_access_secret"] != null && $meta["twitter_access_secret"] != "") {
                $linked = true;
            }
        }
        
        if($linked) {
            include "tmhOAuth/tmhOAuth.php";
            include "tmhOAuth/tmhUtilities.php";

            $tmhOAuth = new tmhOAuth(array(
                'consumer_key'    => self::$conf["twitter"]["Consumer"],
                'consumer_secret' => self::$conf["twitter"]["ConsumerSecret"],
                'user_token'      => $meta["twitter_access_token"], 
                'user_secret'     => $meta["twitter_access_secret"]
            ));
            
            $code = $tmhOAuth->request('GET', $tmhOAuth->url('1/account/verify_credentials'));
            
            $details = json_decode($tmhOAuth->response['response']);
            
            $smpConf = self::$smp->getConf();
            
            $html = "<img src=\"" . self::$smp->getWebRoot() . "/extensions/twitter/twitter.png\" alt=\"twitter\" class=\"social_network_icon\" /> " . sprintf(_("Account \"%s\" linked with %s"), $details->screen_name, $smpConf["Site"]["Title"]) . " <a href=\"" . self::$smp->getWebRoot() . "/extensions/twitter/logout.php\">" . _("Click here to unlink your twitter account") . "</a>";
        } else {
            $html = $defaultLink;
        }
        
        $feedback = new Feedback(
            "twitter",
            "show_status",
            FEEDBACK_INFO,
            false,
            $html
        );
        
        self::$smp->api->sendFeedback("social network show status", $feedback);
    }
    
    public function publish(&$file_id, &$title, &$keywords, &$file, &$url, &$target, &$currentUser) {
        include "tmhOAuth/tmhOAuth.php";
        include "tmhOAuth/tmhUtilities.php";
        
        $meta = Array();
        
        $currentUser = self::$smp->auth->getLoggedInUser();
        
        if($currentUser->meta != null && $currentUser->meta != "") {
            $meta = unserialize($currentUser->meta);
        }

        $tmhOAuth = new tmhOAuth(array(
            'consumer_key'    => self::$conf["twitter"]["Consumer"],
            'consumer_secret' => self::$conf["twitter"]["ConsumerSecret"],
            'user_token'      => $meta["twitter_access_token"], 
            'user_secret'     => $meta["twitter_access_secret"]
        ));
        
        $message = $title . " " . $url;
        
        if($keywords != "") {
            $keywordsList = preg_split("/[\s,]+/", $keywords);
            
            $arr = Array();
            
            foreach($keywordsList as $k) {
                $arr[] = "#" . $k;
            }
            
            $message .= " " . implode(" ", $arr);
        }        
        
        $code = $tmhOAuth->request('POST', $tmhOAuth->url('1/statuses/update'), array(
            'status' => $message
        ));
        
        if($code != 200) {        
            $feedback = new Feedback(
                "twitter",
                "publish",
                FEEDBACK_ERROR,
                false,
                _("Message not sent to twitter")
            );
        }
    }
}

?>