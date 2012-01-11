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

define("AUTH_SUCCESS", 0);
define("AUTH_BAD_EMAIL", 1);
define("AUTH_BAD_USERNAME", 2);
define("AUTH_BAD_PASSWORD", 3);
define("AUTH_ACCOUNT_EXISTS", 4);
define("AUTH_WELCOME_NOT_SENT", 5);
define("AUTH_ACTIVATION_NOT_SENT", 6);
define("AUTH_INEXISTANT_ACCOUNT", 7);
define("AUTH_BAD_ACTIVATION_KEY", 8);
define("AUTH_ACTIVATION_REQUIRED", 9);
define("AUTH_NO_PROBLEM_SPECIFIED", 10);
define("AUTH_ACTIVATION_SENT", 11);
define("AUTH_PASSWORD_SENT", 12);
define("AUTH_PASSWORD_NOT_SENT", 13);
define("AUTH_BAD_CREDENTIALS", 14);
define("AUTH_CONFIRM_REQUESTED", 15);

define("REGEX_USERNAME", "/^[a-z0-9_-]{3,16}$/i");
define("REGEX_PASSWORD", "/^.*(?=.{6,})(?=.*[a-z])(?=.*[A-Z])(?=.*[\d\W]).*$/");


/**
 * Used to perform users management
 */
class Auth {
    
    private $smp = null;
    private $db = null;
    private $conf = null;
    private $api = null;
    
    public function __construct($smp) {
        $this->smp = $smp;
        $this->db = $this->smp->database; // Just a shortcut
        $this->conf = $this->smp->getConf();
        $this->api = $this->smp->api;
    }
    
    /**
     * Checks if an account exists, based on the specified emai
     */
    public function accountExists($email) {
        $test = $this->db->selectOne("Accounts", "email", "email='" . $email . "'");
        
        return $test != null; 
    }
    
    /**
     * Registers a new user
     */
    public function register() {
        $email = $_POST["email"];
        $username = $_POST["username"];
        $password = $_POST["password"];
        
        $this->api->execute("before registering", array(&$email, &$username, &$password));
        
        if(!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
            return AUTH_BAD_EMAIL;
        }
        
        if(!filter_var($_POST["username"], FILTER_VALIDATE_REGEXP, array("options" => array("regexp" => $this->conf["Regex"]["Username"])))) {
            return AUTH_BAD_USERNAME;
        }
        
        if(!filter_var($_POST["password"], FILTER_VALIDATE_REGEXP, array("options" => array("regexp" => $this->conf["Regex"]["Password"])))) {
            return AUTH_BAD_PASSWORD;
        }
        
        if($this->accountExists($email)) {
            return AUTH_ACCOUNT_EXISTS;
        }
        
        $activation_key = md5($email . microtime());
        
        $activated = 1;
        
        if($this->conf["Accounts"]["ActivationRequired"]) {
            $activated = 0;
        }
        
        $date_registered = date("Y-m-d H:i:s");
        
        $params = Array(
            "email"             => $email,
            "password"          => md5($password),
            "username"          => $username,
            "activation_key"    => $activation_key,
            "activated"         => $activated,
            "date_registered"   => $date_registered
        );
        
        $this->api->execute("before inserting account in database", array(&$email, &$username, &$password, &$activation_key, &$activated, &$date_registered));
        
        $this->db->insert("Accounts", $params); 
        
        if(!$this->sendWelcomeMessage($username, $email, $password)) {
            return AUTH_WELCOME_NOT_SENT;
        }
        
        if($this->conf["Accounts"]["ActivationRequired"] == "true") {
            if(!$this->sendActivationLink($email, $activation_key)) {
                return AUTH_ACTIVATION_NOT_SENT;
            }
        }
        
        $this->api->execute("after registering", array(&$email, &$username, &$password, &$activation_key, &$activated, &$date_registered));
        
        return AUTH_SUCCESS;
    }
    
    /**
     * Sends the welcome link to the new user
     */
    public function sendWelcomeMessage($username, $email, $password) {
        require_once("Mail.php");
    
        $subject = sprintf(_("Welcome to %s, %s !"), $this->conf["Site"]["Title"], $username);
        $to = $email;
        $from = $this->conf["Mailer"]["From"] != "" ? $this->conf["Mailer"]["From"] : $this->conf["Site"]["Admin"];
        
        $body = sprintf(_("Thank you for your registration on %s. This first message reminds you your\n" .
        "login informations. Please keep it in a safe place. Your password is : %s. Please note that\n" .
        "we never ask you for your password in the future, with the exception of the login page of our\n" .
        "website. We store it encrypted, so if you lose it, you must ask for a new password.\n\n"),
        $this->conf["Site"]["Title"], $password);
        
        $this->api->execute("before sending welcome mail", array(&$email, &$username, &$password, &$subject, &$to, &$from, &$body));
        
        $mailer =& Mail::factory($this->conf["Mailer"]["Backend"], $this->getMailerBackendConf());
        
        $headers = Array(
            "From"      => $from,
            "To"        => $to,
            "Subject"   => $subject
        );
        
        $status = $mailer->send($to, $headers, $body);
        
        $this->api->execute("after sending welcome mail", array(&$email, &$username, &$password, &$subject, &$to, &$from, &$body, &$status));        
        
        return $status;
    }
    
    /**
     * Send the activation link to the specified email
     */
    public function sendActivationLink($email, $activation_key) {
        require_once("Mail.php");
    
        $subject = sprintf(_("Your activation link for %s !"), $this->conf["Site"]["Title"]);
        $to = $email;
        $from = $this->conf["Mailer"]["From"] != "" ? $this->conf["Mailer"]["From"] : $this->conf["Site"]["Admin"];
        
        $body = sprintf(_("%s requires a valid email address. You must validate yours in order to\n" .
            "be allowed to log in. It's fairly simple : just click on the link below and you'll be done.\n\n"),
            $this->conf["Site"]["Title"]);
            
        $link = $this->smp->getWebRoot() . "/?activate&key=" . $activation_key;
        
        $body .= $link;
        
        $this->api->execute("before sending activation mail", array(&$email, &$activation_key, &$subject, &$to, &$from, &$body));
        
        $mailer =& Mail::factory($this->conf["Mailer"]["Backend"], $this->getMailerBackendConf());
        
        $headers = Array(
            "From"      => $from,
            "To"        => $to,
            "Subject"   => $subject
        );
        
        $status = $mailer->send($to, $headers, $body);
        
        $this->api->execute("after sending welcome mail", array(&$email, &$activation_key, &$subject, &$to, &$from, &$body, &$status));
        
        return $status; 
    }
    
    /**
     * Activate an account based on it's activation key
     */
    public function activate($activation_key) {
        $account = $this->db->selectOne("Accounts", "*", "activation_key='" . $activation_key . "'");
    
        if($account == null) {
            return AUTH_BAD_ACTIVATION_KEY;
        }
        
        $this->api->execute("before activating", array(&$activation_key, &$account));
    
        $this->db->update("Accounts", Array("activated" => 1), "account_id='" . $account->account_id . "'");
        
        $this->api->execute("after activating", array(&$activation_key, &$account));
    
        return AUTH_SUCCESS;
    }
    
    /**
     * Performs log in
     */
    public function login() {
        $email = $_POST["email"];
        $password = $_POST["password"];
        
        $this->api->execute("before loging in", array($email, $password));
        
        if(!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
            return AUTH_BAD_EMAIL;
        }
        
        if(!filter_var($_POST["password"], FILTER_VALIDATE_REGEXP, array("options" => array("regexp" => $this->conf["Regex"]["Password"])))) {
            return AUTH_BAD_PASSWORD;
        }
        
        if(!$this->accountExists($email)) {
            return AUTH_INEXISTANT_ACCOUNT;
        }
        
        $account = $this->getAccountByEmail($email);
        
        if($account->password != md5($password)) {
            return AUTH_BAD_CREDENTIALS;
        }
        
        if($this->conf["Accounts"]["ActivationRequired"] == "true") {
            if($account->activated == 0) {
                return AUTH_ACTIVATION_REQUIRED;
            }
        }
        
        session_regenerate_id();
    
        $values = Array(
            "last_activity"     => date("Y-m-d H:i:s"),
            "last_session_id"   => session_id()
        );
        
        $this->db->update("Accounts", $values, "account_id='" . $account->account_id . "'");
        
        $this->api->execute("after loging in", array(&$account));
        
        return AUTH_SUCCESS;
    }
    
    /**
     * Determines if current user is logged in. If so, updates his last
     * activity
     */
    public function isLoggedIn() {
        $account = $this->db->selectOne("Accounts", "*", "last_session_id='" . session_id() . "'");
        
        if($account == null) {
            return false;
        }
        
        $this->db->update("Accounts", Array("last_activity" => date("Y-m-d H:i:s")), "account_id='" . $account->account_id . "'");
        
        return true;
    }
    
    /**
     * Gets the currently logged in user
     */
    public function getLoggedInUser() {
        return $this->db->selectOne("Accounts", "*", "last_session_id='" . session_id() . "'");
    }
    
    /**
     * Ends current user's session
     */
    public function logout() {
        $this->db->update("Accounts", Array("last_session_id" => ""), "last_session_id='" . session_id() . "'");
    }
    
    /**
     * Retrieves a user account based on email
     */
    public function getAccountByEmail($email) {
        return $this->db->selectOne("Accounts", "*", "email='" . $email . "'");
    }
    
    /**
     * Retrieves a user account based on id
     */
    public function getAccountById($id) {
        return $this->db->selectOne("Accounts", "*", "account_id='" . $id . "'");
    }
    
    /**
     * Transforms SMP's settings into PEAR Mail backend conf array
     */
    public function getMailerBackendConf() {
        $params = Array();
        
        if($this->conf["Mailer"]["Backend"] == "sendmail") {
            $params = Array(
                "sendmail_path"     => $this->conf["Mailer"]["BackendConf"]["sendmail_path"],
                "sendmail_args"     => $this->conf["Mailer"]["BackendConf"]["sendmail_args"]
            );
        } else if($this->conf["Mailer"]["Backend"] == "smtp") {
            $params = Array(
                "host"              => $this->conf["Mailer"]["BackendConf"]["host"],
                "port"              => $this->conf["Mailer"]["BackendConf"]["port"],
                "auth"              => $this->conf["Mailer"]["BackendConf"]["auth"],
                "username"          => $this->conf["Mailer"]["BackendConf"]["username"],
                "password"          => $this->conf["Mailer"]["BackendConf"]["password"]
            );
        }
        
        return $params;
    }
    
    /**
     * Use the trouble form to solve account issues
     */
    public function solveProblem() {
        $email = $_POST["email"];
        
        if(!isset($_POST["problem"])) {
            return AUTH_NO_PROBLEM_SPECIFIED;
        }
        
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return AUTH_BAD_EMAIL;
        }
        
        if(!$this->accountExists($email)) {
            return AUTH_INEXISTANT_ACCOUNT;
        }
        
        $account = $this->getAccountByEmail($email);
        $oldMd5 = $account->password;
        
        switch($_POST["problem"]) {
            case "no_activation_link":
                if(!$this->sendActivationLink($email, $account->activation_key)) {
                    return AUTH_ACTIVATION_NOT_SENT;
                }
                
                return AUTH_ACTIVATION_SENT;
                break;
                
            case "password_forgotten":
                if(!$this->sendNewPassword($email)) {
                    $this->db->update("Accounts", Array("password" => $oldMd5), "email='" . $email . "'");
                    return AUTH_PASSWORD_NOT_SENT;
                }
                
                return AUTH_PASSWORD_SENT;
                break;
        }
    }
    
    /**
     * Sends a new password to the specified email
     */
    private function sendNewPassword($email) {
        $newPassword = $this->generatePassword();
        $md5 = md5($newPassword);
        
        $this->db->update("Accounts", Array("password" => $md5), "email='" . $email . "'");
        
        require_once("Mail.php");
    
        $subject = sprintf(_("Your new password for %s !"), $this->conf["Site"]["Title"]);
        $to = $email;
        $from = $this->conf["Mailer"]["From"] != "" ? $this->conf["Mailer"]["From"] : $this->conf["Site"]["Admin"];
        
        $body = sprintf(_("You have requested a new password. Here it is : %s. You can\n" . 
            "now log in to %s, and you should quickly change your password.\n\n"),
            $newPassword, $this->conf["Site"]["Title"]);
        
        $mailer =& Mail::factory($this->conf["Mailer"]["Backend"], $this->getMailerBackendConf());
        
        $headers = Array(
            "From"      => $from,
            "To"        => $to,
            "Subject"   => $subject
        );
        
        $status = $mailer->send($to, $headers, $body);
        
        return $status; 
    }
    
    /**
     * Updates the currently logged in user's account
     */
    public function updateAccount() {
        $this->api->execute("before updating account", array(&$email, &$username, &$password));
        
        $email = $_POST["email"];
        $username = $_POST["username"];
        $password = $_POST["password"];
        
        if(!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
            return AUTH_BAD_EMAIL;
        }
        
        if(!filter_var($_POST["username"], FILTER_VALIDATE_REGEXP, array("options" => array("regexp" => $this->conf["Regex"]["Username"])))) {
            return AUTH_BAD_USERNAME;
        }
        
        if(!filter_var($_POST["password"], FILTER_VALIDATE_REGEXP, array("options" => array("regexp" => $this->conf["Regex"]["Password"])))) {
            return AUTH_BAD_PASSWORD;
        }
        
        $params = Array(
            "email"             => $email,
            "password"          => md5($password),
            "username"          => $username
        );
        
        $this->api->execute("before updating account in database", array(&$email, &$username, &$password));
        
        $this->db->update("Accounts", $params, "email='" . $email . "'");
        
        $this->api->execute("after updating account", array(&$email, &$username, &$password, &$activation_key, &$activated, &$date_registered));
        
        return AUTH_SUCCESS;
    }
    
    /**
     * Removes the currently logged in user
     */
    public function deleteAccount() {
        $remove_files = isset($_POST["remove_files"]) ? $_POST["remove_files"] : false;
        $confirm = isset($_POST["delete_account_confirm"]) ? $_POST["delete_account_confirm"] : false;
        $password = $_POST["password"];
        
        if($confirm != "on") {
            return AUTH_CONFIRM_REQUESTED;
        }
        
        $account = $this->getLoggedInUser();
        
        if(md5($password) != $account->password) {
            return AUTH_BAD_CREDENTIALS;
        }
        
        if($remove_files == "on") {
            $error = false;
            
            $target = $this->smp->getRoot() . "/upload/" . $account->account_id;
            
            foreach(glob($target . "/*") as $file) {
                @unlink($file);
            }
            
            @rmdir($target);
        }
        
        $files = $this->db->selectList("Files", "*", "account_id='" . $account->account_id . "'");
        
        foreach($files as $file) {
            $this->db->delete("FilesKeywords", "file_id='" . $file->file_id . "'");
        }
        
        $this->db->delete("Files", "account_id='" . $account->account_id . "'");
        $this->db->delete("Accounts", "account_id='" . $account->account_id . "'");
        
        return AUTH_SUCCESS;
    }
    
    /**
     * Generates a new password :)
     */
    private function generatePassword() {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        
        $newPassword = "";
        
        for($i = 0; $i < 8; $i++) {
            $newPassword .= $chars[rand(0, 61)];
        }
        
        return $newPassword;
    }
}

?>