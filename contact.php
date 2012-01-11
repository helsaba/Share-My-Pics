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

$SMP->setPageTitle(_("Contact"));

$SMP->registerStyleSheet($SMP->getTemplateWebRoot() . "/css/contact.css");

$email = "";

$error = "";
$info = "";

if($SMP->auth->isLoggedIn()) {
    $currentUser = $SMP->auth->getLoggedInUser();
    $email = $currentUser->email;
}

if(isset($_POST["send"])) {
    if($SMP->sendContactForm()) {
        $info = _("Your message was successfully sent. Thank you for your interest !");
    } else {
        $error = _("Your message was not sent, due to temporary mail system failure, please try again later, and sorry for convenience.");
    }
}

$template->assign("error", $error);
$template->assign("info", $info);

$template->assign("email_label", _("Your email address (required)"));
$template->assign("email", $email);
$template->assign("subject_label", _("Subject"));
$template->assign("message_label", _("Your message (required)"));
$template->assign("send_button", _("Send"));

$SMP->show();

?>