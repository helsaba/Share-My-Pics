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

session_start();

/**
 * Main application
 */ 
class SMP {
    private $javascripts = Array();         // Array of scripts to load
    private $stylesheets = Array();         // Array of stylesheets to load
    
    private $root = "";                     // Site's physical root
    public function getRoot() { return $this->root; }
    
    private $webroot = "";                  // Site's root url
    public function getWebRoot() { return $this->webroot; }
    
    private $conf = null;                   // Settings
    public function getConf() { return $this->conf; }
    
    private $template = null;               // Smarty's template object
    public function getTemplate() { return $this->template; }    
    
    private $templateWebRoot = null;        // Current template root
    public function getTemplateWebRoot() { return $this->templateWebRoot; } 
    
    private $page_description = null;       // Self explanatory
    public function getPageDescription() { return $this->page_description; }
    
    private $page_title = null;             // The same
    public function getPageTitle() { return $this->page_title; }
    
    private $page_keywords = null;          // The same
    public function getPageKeywords() { return $this->page_keywords; }
    
    public $api = null;                     // SMP's publicly visible API
    public $auth = null;                    // Publicly visible auth object
    public $database = null;                // Publicly visible database object
    public $upload = null;                  // Publicly visible upload object;
    
    public function __construct() {
        $this->generatePaths();
        
        include $this->root . "/library/class.API.php";
        include $this->root . "/library/class.Database.php";
        include $this->root . "/library/class.Auth.php";
        include $this->root . "/library/class.Upload.php";
        include $this->root . "/library/smarty/Smarty.class.php";
        
        $this->readConfiguration();
        
        $this->setupLang();
        
        $this->api = new API();
        
        $this->loadExtensions();
        
        $this->setTemplate();
        
        $this->database = new Database($this->conf["Storage"]["DSN"], $this->conf["Storage"]["Options"]);
        $this->auth = new Auth($this);
        
        $this->upload = new Upload($this);
    }
    
    /***** BEGIN : private functions ******************************************/
    
    /**
     * Sets the root and webroot variables
     */
    private function generatePaths() {
        $this->root = str_replace("/library", "", dirname(__FILE__));
        
        $webDir = str_replace($_SERVER["DOCUMENT_ROOT"], "", dirname($_SERVER["SCRIPT_FILENAME"]));
    
        $this->webroot = 'http';
        
        if(array_key_exists("HTTPS", $_SERVER) && $_SERVER['HTTPS'] == 'on') 
            $this->webroot .=  's';
        
        $this->webroot .=  '://';
    
        if($_SERVER['SERVER_PORT'] != '80') 
            $this->webroot .= $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . $webDir;        
        else
            $this->webroot .= $_SERVER['HTTP_HOST'] . $webDir;
    }
    
    /**
     * Reads a configuration file
     */
    private function readConfiguration($path = null) {
        if($path == null)
            $path = $this->root . "/settings.php";
            
        if(!is_file($path)) {
            
        }
        
        include $path;
        
        $this->conf = $conf;
        
        $this->templateWebRoot = $this->webroot . "/templates/" . $this->conf["Site"]["Template"];        
        
        $this->page_description = $this->conf["Site"]["Description"];
        $this->page_keywords = $this->conf["Site"]["Keywords"];
        
        if($this->conf["Site"]["Logo"] == "") {
            $this->conf["Site"]["Logo"] = $this->templateWebRoot . "/images/logo.png";
        }
    }
    
    /**
     * Loading extensions
     */
    private function loadExtensions() {
        if(array_key_exists("Extensions", $this->conf)) {
            if(is_array($this->conf["Extensions"])) {
                foreach($this->conf["Extensions"] as $extension) {
                    $this->loadExtension($extension);
                }
            }
        }
    }
    
    /**
     * Loading a single extension
     */
    private function loadExtension($extension) {
        include $this->root . "/extensions/" . $extension . "/Module.php";
        new $extension($this);
    }
    
    /**
     * Create's a smarty object and register first templates elements
     */
    private function setTemplate() {
        $this->template = new Smarty();
        $this->template->setTemplateDir($this->root . "/templates/" . $this->conf["Site"]["Template"] . "/html");
        
        $this->api->execute("before assigning template basics");
        
        $this->registerStyleSheet($this->templateWebRoot . "/css/main.css");
        
        /* Header's stuff */
        $this->template->assign("site_title", $this->conf["Site"]["Title"]);
        $this->template->assign("base_url", $this->webroot);
        $this->template->assign("stylesheets", $this->stylesheets);
        $this->template->assign("javascripts", $this->javascripts);
        
        /* Common to all pages */
        $this->template->assign("title_home_link", $this->conf["Site"]["Title"] . " - " . _("Home"));
        $this->template->assign("alt_logo", $this->conf["Site"]["Title"]);
        $this->template->assign("logo_src", $this->conf["Site"]["Logo"]);
        
        $this->template->assign("template_root", $this->templateWebRoot);
        
        /* Meta tags */
        $this->template->assign("page_description", $this->page_description);
        $this->template->assign("page_keywords", $this->page_keywords);        
        
        $this->template->assign("licence", $this->conf["Site"]["CopyrightNote"]);
        
        /* Main links */
        $this->template->assign("text_home_link", _("Home"));
        $this->template->assign("text_timeline_link", _("Public timeline"));
        $this->template->assign("title_timeline_link", _("See what others have uploaded"));
        $this->template->assign("text_search_link", _("Search"));
        $this->template->assign("title_search_link", _("Search for your interests"));
        $this->template->assign("text_contact_link", _("Contact"));
        $this->template->assign("title_contact_link", _("Contact us"));
        
        $this->api->execute("after assigning template basics");
    }
    
    /**
     * Initialize language settings
     */
    private function setupLang() {
        $lang = $this->conf["Site"]["Lang"];
        
        if($lang == "" || $lang == null) {
            $lang = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        }
        
        putenv("LANG=" . $lang);
        setlocale(LC_ALL, $lang);
        
        bindtextdomain("smp", $this->root . "/languages");
        bind_textdomain_codeset("smp", 'UTF-8');
        textdomain("smp");
    }
    
    /***** END : private functions ********************************************/
    
    /***** BEGIN : public functions *******************************************/
    
    /**
     * Allows to add javascript to the site's header
     */
    public function registerJavascript($path) {
        $this->api->execute("before registering javascript", array($path));
        $this->javascripts[] = $path;
        
        if($this->template != null) {
            $this->template->assign("javascripts", $this->javascripts);
        }
        
        $this->api->execute("after registering javascript");
    }
    
    /**
     * Allows to add stylesheets to the site's header
     */
    public function registerStyleSheet($path, $media = "screen") {
        $this->api->execute("before registering stylesheet", array($path, $media));
        $this->stylesheets[] = Array("path" => $path, "media" => $media);
        
        if($this->template != null) {
            $this->template->assign("stylesheets", $this->stylesheets);
        }
        
        $this->api->execute("after registering stylesheet");
    }
    
    /**
     * Sets the page title
     */
    public function setPageTitle($title) {        
        $this->api->execute("before setting page title", array($title));
        $this->page_title = $title;
        
        if($this->template != null) {
            $this->template->assign("page_title", $this->page_title);
        }
        
        $this->api->execute("after setting page title");
    }
    
    /**
     * Sets the page description
     */
    public function setPageDescription($description) {
        $this->api->execute("before setting page description", array($description));
        $this->page_description = $description;
        
        if($this->template != null) {            
            $this->template->assign("page_description", $this->page_description);
        }
        
        $this->api->execute("after setting page description");
    }
    
    /**
     * Sets the page keywords
     */
    public function setPageKeywords($keywords) {
        $this->api->execute("before setting page keywords", array($keywords));
        if(is_array($keywords)) {
            $keywords = implode(",", $keywords);
        }
        
        if($this->page_keywords != "") {
            $keywords = "," . $keywords;
        }
        
        $this->page_keywords .= $keywords;
        
        if($this->template != null) {
            $this->template->assign("page_keywords", $this->page_keywords);
        }
        
        $this->api->execute("after setting page keywords");
    }
    
    /**
     * Load the template
     */
    public function show() {
        $this->api->execute("before showing template");
        $currentPage = basename($_SERVER["PHP_SELF"], "php") . "html";
        
        $this->template->display("header.html");
        $this->template->display($currentPage);
        $this->template->display("footer.html");
        $this->api->execute("after showing template");
    }
    
    /**
     * Redirects to the specified url
     */
    public function go_to($url) {
        header("Location: " . $url);
        exit;
    }
    
    /**
     * Sends an email with the content of the contact form
     */
    public function sendContactForm() {
        require_once("Mail.php");
        
        $from = $_POST["email"];
        $subject = $_POST["subject"];
        $body = $_POST["message"];
        $to = $this->conf["Site"]["Admin"];
        
        $body .= sprintf(_("\n\nThis message was sent through %s"), $this->conf["Site"]["Title"]);
        
        $this->api->execute("before sending contact mail", array(&$from, &$subject, &$message, &$to));
        
        $mailer =& Mail::factory($this->conf["Mailer"]["Backend"], $this->auth->getMailerBackendConf());
        
        $headers = Array(
            "From"      => $from,
            "To"        => $to,
            "Subject"   => $subject
        );
        
        $status = $mailer->send($to, $headers, $body);
        
        $this->api->execute("after sending contact mail", array(&$from, &$subject, &$message, &$to, &$status));        
        
        return $status;
    }
    
    /**
     * Format a date using format specified in settings
     */
    public function formatSQLDate($sqldate) {
        $dateTimeTokens = explode(" ", $sqldate);    
        $dateTokens = explode("-", $dateTimeTokens[0]);
        $timeTokens = explode(":", $dateTimeTokens[1]);
        
        $date = date($this->conf["Site"]["DateFormat"], mktime(
            $timeTokens[0],
            $timeTokens[1],
            $timeTokens[2],
            $dateTokens[1],
            $dateTokens[2],
            $dateTokens[0]
        ));
        
        return $date;
    }
    
    /***** END : public functions *********************************************/
}

?>