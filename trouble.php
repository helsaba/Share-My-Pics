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

$SMP->setPageTitle(_("In trouble"));

$SMP->registerStyleSheet($SMP->getTemplateWebRoot() . "/css/index.css");

$email = "";

$error = "";
$info = "";

if(isset($_POST["resolve"])) {
    $email = filter_var($_POST["email"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    switch($SMP->auth->solveProblem()) {
        case "AUTH_NO_PROBLEM_SPECIFIED":
            $error = _("You have not specified a problem");
            break;
        case AUTH_BAD_EMAIL:
            $error = _("Specified email is not valid");
            break;
        case AUTH_INEXISTANT_ACCOUNT:
            $error = _("Account does not exists");
            break;
        case AUTH_ACTIVATION_NOT_SENT:
            $error = _("We failed to send you your activation email. Please retry later, and sorry for convenience");
            break;
        case AUTH_PASSWORD_NOT_SENT:
            $error = _("We failed to send you your new password. Your current password had not changed. Please retry later, and sorry for convenience");
            break;
        case AUTH_ACTIVATION_SENT:
            $info = _("Your activation message was successfully sent");
            break;
        case AUTH_PASSWORD_SENT:
            $info = _("Your new password was sent to your email address");
            break;
    }
}

$template->assign("error", $error);
$template->assign("info", $info);

$template->assign("title_text", _("What's your problem ?"));
$template->assign("no_activation_link_label", _("I havn't received my activation link"));
$template->assign("password_forgotten_label", _("I have forgotten my password"));
$template->assign("email_label", _("Your email (required)"));
$template->assign("email", $email);
$template->assign("resolve", _("Resolve my problem !"));

$SMP->show();

?>