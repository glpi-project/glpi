<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
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

if (!defined('GLPI_ROOT')) {
   include ('../inc/includes.php');
}


Html::popHeader(__('Setup'), $_SERVER['PHP_SELF'], true);

if (!isset($_GET["type"])) {
   $_GET["type"] = -1;
}

if (!isset($_GET["itemtype"])) {
   $_GET["itemtype"] = -1;
}

if (!isset($_GET["url"])) {
   $_GET["url"] = "";
}

if (!isset($_GET["action"])) {
   $_GET["action"] = "";
}

$bookmark = new Bookmark();


if (isset($_POST["add"])) {
   $bookmark->check(-1, CREATE, $_POST);

   $bookmark->add($_POST);

} else if (isset($_POST["update"])) {
   $bookmark->check($_POST["id"], UPDATE);   // Right to update the bookmark
   $bookmark->check(-1, CREATE, $_POST);     // Right when entity change

   $bookmark->update($_POST);
   $_GET["action"] = "";

} else if ($_GET["action"] == "edit"
           && isset($_GET['mark_default'])
           && isset($_GET["id"])) {
   $bookmark->check($_GET["id"], READ);

   if ($_GET["mark_default"] > 0) {
      $bookmark->mark_default($_GET["id"]);
   } else if ($_GET["mark_default"] == 0) {
      $bookmark->unmark_default($_GET["id"]);
   }
   $_GET["action"] = "";

} else if (($_GET["action"] == "load")
           && isset($_GET["id"]) && ($_GET["id"] > 0)) {
   $bookmark->check($_GET["id"], READ);
   $bookmark->load($_GET["id"]);
   $_GET["action"] = "";

} else if (isset($_POST["action"])
           && (($_POST["action"] == "up") || ($_POST["action"] == "down"))) {
   Session::checkLoginUser();
   $bookmark->changeBookmarkOrder($_POST['id'], $_POST["action"]);
   $_GET["action"] = "";

} else if (isset($_POST["purge"])) {
   $bookmark->check($_POST["id"], PURGE);
   $bookmark->delete($_POST, 1);
   $_GET["action"] = "";
}

if ($_GET["action"] == "edit") {

   if (isset($_GET['id']) && ($_GET['id'] > 0)) {
      // Modify
      $bookmark->check($_GET["id"], UPDATE);
      $bookmark->showForm($_GET['id']);
   } else {
      // Create
      $bookmark->check(-1, CREATE);
      $bookmark->showForm(0, array('type'     => $_GET["type"],
                                   'url'      => rawurldecode($_GET["url"]),
                                   'itemtype' => $_GET["itemtype"]));
   }
} else {
   $bookmark->display();
}

Html::popFooter();
?>