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

ini_set("display_errors", "on");

include "settings.php";

include "tmhOAuth/tmhOAuth.php";
include "tmhOAuth/tmhUtilities.php";

include "../../library/class.SMP.php";

$tmhOAuth = new tmhOAuth(array(
    'consumer_key'    => $conf["twitter"]["Consumer"],
    'consumer_secret' => $conf["twitter"]["ConsumerSecret"]
));

$SMP = new SMP();

if(!isset($_REQUEST["oauth_verifier"])) {
    $code = $tmhOAuth->request(
        'POST',
        $tmhOAuth->url('oauth/request_token', ''),
        array(
            'oauth_callback' => tmhUtilities::php_self()
        )
    );

    if ($code == 200) {
        $_SESSION['oauth'] = $tmhOAuth->extract_params($tmhOAuth->response['response']);
        
        $SMP->go_to($tmhOAuth->url("oauth/authorize", '') .  "?oauth_token={$_SESSION['oauth']['oauth_token']}");
    }
} else {
    $tmhOAuth->config['user_token']  = $_SESSION['oauth']['oauth_token'];
    $tmhOAuth->config['user_secret'] = $_SESSION['oauth']['oauth_token_secret'];

    $code = $tmhOAuth->request(
        'POST',
        $tmhOAuth->url('oauth/access_token', ''),
        array(
            'oauth_verifier' => $_REQUEST['oauth_verifier']
        )
    );

    if ($code == 200) {
        $_SESSION['access_token'] = $tmhOAuth->extract_params($tmhOAuth->response['response']);
        
        $currentUser = $SMP->auth->getLoggedInUser();
    
        $meta = $currentUser->meta;
        $tmpArr = Array();
    
        if($meta != null && $meta != "") {
            $tmpArr = unserialize($meta);
        }
    
        $tmpArr["twitter_access_token"] = $_SESSION['access_token']['oauth_token'];
        $tmpArr["twitter_access_secret"] = $_SESSION['access_token']['oauth_token_secret'];
    
        $SMP->database->update("Accounts", Array("meta" => serialize($tmpArr)), "account_id='" . $currentUser->account_id . "'");
        
        unset($_SESSION['oauth']);
    
        $SMP->go_to("../../?view_profile");
    }
}

?>