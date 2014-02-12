<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/** @file
* @brief
*/

include ('../inc/includes.php');

Session::checkRight("profile", "r");

if (!isset($_GET['id'])) {
   $_GET['id'] = "";
}

$prof = new Profile();

if (isset($_POST["add"])) {
   $prof->check(-1,'w',$_POST);
   $ID = $prof->add($_POST);

   // We need to redirect to form to enter rights
   Html::redirect($CFG_GLPI["root_doc"]."/front/profile.form.php?id=$ID");

} else if (isset($_POST["delete"])) {
   $prof->check($_POST['id'],'d');
   if ($prof->delete($_POST)) {
      $prof->redirectToList();
   } else {
      Html::back();
   }

} else if (isset($_POST["update"])
           || isset($_POST["interface"])) {
   $prof->check($_POST['id'],'w');

   $prof->update($_POST);
   Html::back();
}

Html::header(Profile::getTypeName(2), $_SERVER['PHP_SELF'], "admin", "profile");

$prof->showForm($_GET["id"]);

Html::footer();
?>