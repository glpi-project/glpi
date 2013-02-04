<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

$user = new User();


// Manage lost password
if (isset($_GET['lostpassword'])) {
   Html::nullHeader();
   if (isset($_GET['password_forget_token'])) {
      User::showPasswordForgetChangeForm($_GET['password_forget_token']);
   } else {
      User::showPasswordForgetRequestForm();
   }
   Html::nullFooter();
   exit();
}


Session::checkLoginUser();

if (isset($_POST["update"]) && $_POST["id"] === Session::getLoginUserID()) {
   $user->update($_POST);
   Event::log(0, "users", 5, "setup", $_SESSION["glpiname"] . "  " .
              $LANG['log'][21] . "  " . $_SESSION["glpiname"] . ".");
   Html::back();

} else {
   if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
      Html::header($LANG['title'][13], $_SERVER['PHP_SELF'],'preference');
   } else {
      Html::helpHeader($LANG['title'][13], $_SERVER['PHP_SELF']);
   }

   $pref = new Preference();
   $pref->show();

   if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
      Html::footer();
   } else {
      Html::helpFooter();
   }
}
?>