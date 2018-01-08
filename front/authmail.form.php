<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

include ('../inc/includes.php');

Session::checkRight("config", UPDATE);

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

$config_mail = new AuthMail();

//IMAP/POP Server add/update/delete
if (isset($_POST["update"])) {
   $config_mail->update($_POST);
   Html::back();

} else if (isset($_POST["add"])) {
   //If no name has been given to this configuration, then go back to the page without adding
   if ($_POST["name"] != "") {
      if (($newID = $config_mail->add($_POST))
          && $_SESSION['glpibackcreated']) {
         Html::redirect($config_mail->getLinkURL());
      }
   }
   Html::back();

} else if (isset($_POST["purge"])) {
   $config_mail->delete($_POST, 1);
   $_SESSION['glpi_authconfig'] = 2;
   $config_mail->redirectToList();

} else if (isset($_POST["test"])) {
   if (AuthMail::testAuth($_POST["imap_string"], $_POST["imap_login"], $_POST["imap_password"])) {
      Session::addMessageAfterRedirect(__('Test successful'));
   } else {
      Session::addMessageAfterRedirect(__('Test failed'), false, ERROR);
   }
   Html::back();
}

Html::header(AuthMail::getTypeName(1), $_SERVER['PHP_SELF'], "config", "auth", "imap");

$config_mail->display(['id' => $_GET["id"]]);

Html::footer();

