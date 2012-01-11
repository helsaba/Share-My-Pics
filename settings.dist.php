<?php

$conf["Site"]["Title"]                  = "Share My Pics";
$conf["Site"]["Template"]               = "default";

$conf["Site"]["CopyrightNote"]          = "Uploaded files becomes available under Creative Commons licence";

$conf["Site"]["DateFormat"]             = "d/m/Y H:i:s";

$conf["Site"]["Description"]            = "";
$conf["Site"]["Keywords"]               = "";
$conf["Site"]["Logo"]                   = "";

/**
 * Set this to null to autodetect
 */
$conf["Site"]["Lang"]                   = "fr_FR";

$conf["Site"]["Admin"]                  = "";

/**
 * SMP relies on PEAR MDB2 package. You can specify here
 * which backend you would like to use.
 * See http://pear.php.net/manual/en/package.database.mdb2.php
 * for more informations on how to configure your backend
 */
$conf["Storage"]["DSN"]                 = "mysql://user:password@localhost/database";
$conf["Storage"]["Options"]             = Array();

/**
 * Note about regular expressions below
 * If you change this behaviour, remember to change the message
 * in index.php file in case of eroneous user input
 */

/**
 * Regular expression used to validate a username.
 * This one allows letters, numbers, underscores and hyphens,
 * a minimum of 3 and a maximum of 16 characters
 */
$conf["Regex"]["Username"]      = "/^[a-z0-9_-\s]{3,16}$/i";

/**
 * The same for password validation. Must contains at least one digit, one lower case letter,
 * one upper case letter, and optionally a symbol, with a minimum length of 6
 */
$conf["Regex"]["Password"]      = "/^.*(?=.{6,})(?=.*[a-z])(?=.*[A-Z])(?=.*[\d\W]).*$/";

/**
 * Modules loaded at runtime
 * Please check modules dir to see the modules list
 * Please note that each module stores it's own settings
 * in it's own directory
 * Please note that widgets will be displayed with the same order
 * than here
 */
$conf["Extensions"]                        = Array(
    "clamav",
    "thumbnailer",
    "flowplayer",
    "facebook",
    "twitter",
    /***** Timeline Widgets *****/
    "widget_last_members",
    "widget_tags_cloud",
    /***** File Widgets *****/
    "widget_exif"
);

$conf["Accounts"]["ActivationRequired"]      = true;

/**
 * Mail settings
 */
 
/* Email address from which administrative mails are sent to users */
$conf["Mailer"]["From"]                 = "";
 
/* Mailer backend, see http://pear.php.net/manual/en/package.mail.mail.intro.php */
$conf["Mailer"]["Backend"]              = "smtp";

/* Mailer backend configuration. See http://pear.php.net/package/Mail */
/* Below is example settings for SMTP backend */
$conf["Mailer"]["BackendConf"]          = Array(
    "host"          => "localhost",
    "port"          => 25,
    "auth"          => false,
    "username"      => "",
    "password"      => ""    
);

/**
 * Set this to null to allow every file type, including
 * archives, docs, etc. but remember that SMP's purpose is
 * to share pictures and videos, so, unless having a right
 * module to handle that files, it can be useless to allow
 * such extensions
 */
$conf["Upload"]["AllowedExtensions"]    = Array(
    "bmp",
    "jpg",
    "jpeg",
    "png",
    "gif",
    "flv"
);

$conf["Images"]["Sizes"] = Array(
    "thumbnail"     => 128,
    "in_page"       => 800
);

?>