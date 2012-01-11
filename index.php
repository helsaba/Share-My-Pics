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

$SMP->setPageTitle(_("Home"));

$SMP->registerStyleSheet($SMP->getTemplateWebRoot() . "/css/index.css");

/***** Defining some variables ************************************************/

$current_status = "not logged in";
$currentUser = null;
$avatarUrl = $SMP->getTemplateWebRoot() . "/images/anonymous.png";

$text_step1_header = _("Step 1");
$step1_class = "step";

$text_step2_header = _("Step 2");
$step2_class = "step";

$text_step3_header = _("Step 3");
$step3_class = "step";

if($SMP->auth->isLoggedIn()) {
    $current_status = "can upload";
    $text_step1_header = _("Your account");
    $currentUser = $SMP->auth->getLoggedInUser();
    $avatarUrl = "http://www.gravatar.com/avatar/" . md5($currentUser->email) . "?d=" . urlencode($avatarUrl) . "s=80";
}

$error = "";
$info = "";
$deleteError = "";

$file_url = "";

$usernameValue = "";
$emailValue = "";

/***** Treating URL parameters ************************************************/

if(isset($_GET["register"])) {
    $current_status = "registering";
}

if(isset($_GET["activate"])) {
    $activation_key = filter_var($_GET["key"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    $status = $SMP->auth->activate($activation_key);
    
    switch($status) {
        case AUTH_BAD_ACTIVATION_KEY:
            $error = _("You specified a wrong activation key. Please check your activation link in your mailbox, or request a new activation link");
            break;
        case AUTH_SUCCESS:
            $info = _("You have successfuly activated your account. You can now log in !");
            break;
    }
}

if(isset($_GET["view_profile"])) {
    if(!$SMP->auth->isLoggedIn()) {
        $SMP->go_to($SMP->getWebRoot());
    }
    
    $current_status = "view profile";
    $usernameValue = $currentUser->username;
    $emailValue = $currentUser->email;
}

if(isset($_GET["logout"])) {
    $SMP->auth->logout();
    $SMP->go_to($SMP->getWebRoot());
}

/***** Treating forms *********************************************************/

/* Registering */

if(isset($_POST["register"])) {
    $status = $SMP->auth->register();
    
    switch($status) {
        case AUTH_SUCCESS:
            $info = _("You have successfully registered !");
            
            if($conf["Accounts"]["ActivationRequired"]) {
                $info .= _(" You must now check your inbox for your activation link. Just click on the provided link to activate, then you will be able to log in");
            } 
            
            $current_status = "registering";
            break;
        case AUTH_BAD_EMAIL:
            $error = _("Specified email is not valid");
            break;
        case AUTH_BAD_USERNAME:
            $error = _("Specified username is not valid");
            break;
        case AUTH_BAD_PASSWORD:
            $error = _("Specified password is not valid");
            break;
        case AUTH_ACCOUNT_EXISTS:
            $error = _("Account already exists");
            break;
        case AUTH_WELCOME_NOT_SENT:
            $error = _("Your welcome mail was not sent. It only contained your password, so for now on, you must not forget it :)");
            break;
        case AUTH_ACTIVATION_NOT_SENT:
            $error = _("Your activation link was not sent. You should wait for a couple of time before asking for a new activation link");
            break;
    }
}

/* Logging in */

if(isset($_POST["login"])) {
    $status = $SMP->auth->login();
    
    switch($status) {
        case AUTH_SUCCESS:
            $SMP->go_to($SMP->getWebRoot());
            break;
        case AUTH_BAD_EMAIL:
            $error = _("Specified email is not valid");
            break;
        case AUTH_BAD_PASSWORD:
            $error = _("Specified password is not valid");
            break;
        case AUTH_INEXISTANT_ACCOUNT:
            $error = _("Account does not exists");
            break;
        case AUTH_ACTIVATION_REQUIRED:
            $error = _("You must activate your account by clicking on the link provided with your activation email. If you have not received this email, please request a new activation link");
            break;
        case AUTH_BAD_CREDENTIALS:
            $error = _("You submitted a wrong password");
            break;
    }
}

/* Uploading */

if(isset($_POST["upload"])) {
    $status = $SMP->upload->upload();
    
    switch($status) {
        case UPLOAD_CANCELLED:
            $feedbacks = $SMP->api->getFeedbacks("before uploading");
            
            $error = _("Upload was cancelled.");
            
            if(count($feedbacks) == 1) {
                $error .= " " . _("The reason was : ");
                $error .= $feedbacks[0]->message;
            } else {
                $error .= " " . _("The reasons were : ");
                
                foreach($feedbacks as $feedback) {
                    $error .= $feedback->message . " | ";
                }
            }
            break;
        case UPLOAD_NO_FILE:
            $error = _("You must choose a file to upload !");
            break;
        case UPLOAD_SUCCESS:
            $current_status = "file uploaded";
            $file = $SMP->upload->getFileById($SMP->upload->lastUpload);
            $file_url = $file->url;
            break;
        case UPLOAD_EXTENSION_DISALLOWED:
            $error = _("This extension is not allowed");
            break;
        case UPLOAD_TOO_BIG:
            $error = _("Your file is bigger than allowed size");
            break;
    }
}

/* Updating account */

if(isset($_POST["update-account"])) {
    $current_status = "view profile";
    $status = $SMP->auth->updateAccount();
    
    switch($status) {
        case AUTH_SUCCESS:
            $info = _("Your account was successfully updated !");
            break;
        case AUTH_BAD_EMAIL:
            $error = _("Specified email is not valid");
            break;
        case AUTH_BAD_USERNAME:
            $error = _("Specified username is not valid");
            break;
        case AUTH_BAD_PASSWORD:
            $error = _("Specified password is not valid");
            break;
        case AUTH_ACCOUNT_EXISTS:
            $error = _("Account already exists");
            break;
        case AUTH_WELCOME_NOT_SENT:
            $error = _("Your welcome mail was not sent. It only contained your password, so for now on, you must not forget it :)");
            break;
        case AUTH_ACTIVATION_NOT_SENT:
            $error = _("Your activation link was not sent. You should wait for a couple of time before asking for a new activation link");
            break;
    }
}

/* Deleting account */

if(isset($_POST["delete-account"])) {
    $status = $SMP->auth->deleteAccount();
    
    switch($status) {
        case AUTH_CONFIRM_REQUESTED:
            $deleteError = _("You must confirm you want to remove your account");
            break;
        case AUTH_BAD_CREDENTIALS:
            $deleteError = _("You submitted a wrong password");
            break;
        case AUTH_SUCCESS:
            $SMP->go_to($SMP->getWebRoot());
            break;
    }
}

/***** Updating variables accordingly to previous treatment *******************/

switch($current_status) {
    case "not logged in":
    case "registering":
    case "view profile":
        $step1_class .= " selected";
        break;
        
    case "can upload":
        $step2_class .= " selected";
        break;
        
    case "file uploaded":
        $step3_class .= " selected";
        break;
}

$sn_status = Array();

if($current_status == "view profile") {
    $SMP->api->execute("social network show status");

    $feedbacks = $SMP->api->getFeedbacks("social network show status");
    
    $sn_status = Array();
    
    if(count($feedbacks) > 0) {
        foreach($feedbacks as $feedback) {
            $sn_status[] = $feedback->message;
        }
    }
}

/***** Templating *************************************************************/

$template->assign("current_status", $current_status);

$template->assign("error", $error);
$template->assign("info", $info);

/* Step 1 */

$template->assign("step1_class", $step1_class);
$template->assign("text_step1_header", $text_step1_header);
$template->assign("text_login_or_register", sprintf(_("Login or register on %s :"), $conf["Site"]["Title"]));
$template->assign("text_logged_in", sprintf(_("You are currently logged in. %sClick here%s to log out, or to link a social network account."), "<a href=\"?view_profile\">", "</a>"));
$template->assign("text_in_trouble_link", _("Click here if you encounter difficulties with your account"));   
   
$template->assign("text_login_link", _("Login"));
$template->assign("text_register_link", _("Register"));  

$template->assign("email_label", _("Email address"));
$template->assign("username_label", _("Username"));
$template->assign("password_label", _("Password"));

$template->assign("login_button", _("Login"));  
$template->assign("register_button", _("Register"));

$template->assign("text_logout_link", _("Logout"));
$template->assign("avatar_url", $avatarUrl);
$template->assign("text_social_networks_intro", sprintf(_("Here, you can link your social networks accounts, in order to automatically publish on them the pictures and videos you will upload on %s. Just click on the links below, and follow the instructions for each network you want to link."), $conf["Site"]["Title"]));
$template->assign("text_goback_link", _("Go back and upload something !"));

$template->assign("accounts_details", _("Account's details"));
$template->assign("email_value", $emailValue);
$template->assign("username_value", $usernameValue);
$template->assign("update_account_button", _("Update"));

$template->assign("sn_status", $sn_status);

$template->assign("delete_account", _("Deleting your account"));
$template->assign("delete_warning", _("If you delete your account, you won't be able to login again. If you check the case below, your uploaded files will be removed too. They will appear as a removed profile. No data will remain here after you deleted your account."));
$template->assign("remove_files_label", _("Remove my files too"));
$template->assign("delete_account_confirm_label", _("I confirm my demand of deleting my account !"));
$template->assign("delete_account_button", _("Delete"));
$template->assign("delete_error", $deleteError);

/* Step 2 */

$template->assign("step2_class", $step2_class);
$template->assign("text_step2_header", $text_step2_header);
$template->assign("text_upload", _("Upload your file (and, optionnally, tag it)"));

$template->assign("upload_dir_writable", is_dir($SMP->getRoot() . "/upload") && is_writable($SMP->getRoot() . "/upload"));

$template->assign("upload_title_label", _("The title of your file (optional)"));
$template->assign("upload_keywords_label", _("Some keywords (optional)"));
$template->assign("upload_file_label", _("The file to upload (required)"));
$template->assign("upload_button", _("Send"));
$template->assign("text_extensions", sprintf(_("Allowed extensions : %s"), implode(", ", $conf["Upload"]["AllowedExtensions"])));
$template->assign("text_max_filesize", sprintf(_("Maximum filesize : %s"), ini_get("upload_max_filesize")));

/* Step 3 */

$template->assign("step3_class", $step3_class);
$template->assign("text_step3_header", $text_step3_header);
$template->assign("text_grab_link", _("Grab your link, and share it with the world !"));
$template->assign("your_link_label", _("Your link to your file is :"));
$template->assign("file_url", $file_url);

$SMP->show();

?>