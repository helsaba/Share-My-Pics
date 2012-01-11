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

include "settings.php";

include "../../library/class.SMP.php";

$SMP = new SMP();

$return_url = "http://smp.ingnu.fr/extensions/facebook/login.php";

$code = "";

if(isset($_REQUEST["code"])) {
    $code = $_REQUEST["code"];
}

if(empty($code)) {
    $_SESSION['state'] = md5(uniqid(rand(), TRUE)); //CSRF protection
 
    $dialog_url = "http://www.facebook.com/dialog/oauth?client_id=" . $conf["facebook"]["AppID"] . "&redirect_uri=" . urlencode($return_url) . "&state=" . $_SESSION['state'] . "&scope=publish_stream,user_online_presence,offline_access";

    echo("<script> top.location.href='" . $dialog_url . "'</script>");
}

if($_REQUEST['state'] == $_SESSION['state']) {
    $token_url = "https://graph.facebook.com/oauth/access_token?client_id=" . $conf["facebook"]["AppID"] . "&redirect_uri=" . urlencode($return_url) . "&client_secret=" . $conf["facebook"]["AppSecret"] . "&code=" . $code;

    $response = @file_get_contents($token_url);
    $params = null;
    parse_str($response, $params);
    
    $currentUser = $SMP->auth->getLoggedInUser();
    
    $meta = $currentUser->meta;
    $tmpArr = Array();
    
    if($meta != null && $meta != "") {
        $tmpArr = unserialize($meta);
    }
    
    $tmpArr["facebook_access_token"] = $params["access_token"];
    
    $graph_url = "https://graph.facebook.com/me?access_token=" . $tmpArr['facebook_access_token'];
    $content = @file_get_contents($graph_url);
    
    if($content != null && $content != "") {
        $user = json_decode($content);
    
        $tmpArr["facebook_user_id"] = $user->id;
    }
    
    $SMP->database->update("Accounts", Array("meta" => serialize($tmpArr)), "account_id='" . $currentUser->account_id . "'");
    
    $SMP->go_to("../../?view_profile");
}

?>