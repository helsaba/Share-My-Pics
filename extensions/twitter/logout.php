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

include "../../library/class.SMP.php";

$SMP = new SMP();

$currentUser = $SMP->auth->getLoggedInUser();

$meta = $currentUser->meta;
$tmpArr = Array();

if($meta != null && $meta != "") {
    $tmpArr = unserialize($meta);
}

$tmpArr["twitter_access_token"] = null;
$tmpArr["twitter_access_secret"] = null;

$SMP->database->update("Accounts", Array("meta" => serialize($tmpArr)), "account_id='" . $currentUser->account_id . "'");

$SMP->go_to("../../?view_profile");

?>