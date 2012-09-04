<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

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

if (!isset($_REQUEST["uID"])) {
   if (($uid = Session::getLoginUserID())
       && !Session::haveRight("show_all_planning","1")) {
      $_REQUEST["uID"] = $uid;
   } else {
      $_REQUEST["uID"] = 0;
   }
}

if (!isset($_REQUEST["gID"])) {
   $_REQUEST["gID"] = 0;
}

if (!isset($_REQUEST["limititemtype"])) {
   $_REQUEST["limititemtype"] = "";
}

// Normal call via $_GET
if (isset($_GET['checkavailability'])) {
   Html::popHeader(__('Availability'));
   if (!isset($_GET["begin"])) {
      $_GET["begin"] = "";
   }
   if (!isset($_GET["end"])) {
      $_GET["end"] = "";
   }
   if (!isset($_GET["users_id"])) {
      $_GET["users_id"] = "";
   }
   Planning::checkAvailability($_GET['users_id'], $_GET['begin'], $_GET['end']);
   Html::popFooter();

} else if (isset($_REQUEST['genical'])) {
   if (isset($_REQUEST['token'])) {
      // Check user token
      /// TODO : complex : check if the request is valid : rights on uID / gID ?
      $user = new User();
      if ($user->getFromDBByToken($_REQUEST['token'])) {
         Planning::generateIcal($_REQUEST["uID"], $_REQUEST["gID"], $_REQUEST["itemtype"]);
      }
   }
} else {
   Html::header(__('Planning'), $_SERVER['PHP_SELF'], "maintain", "planning");

   Session::checkSeveralRightsOr(array('show_all_planning' => '1',
                                       'show_planning'     => '1'));

   if (!isset($_REQUEST["date"]) || empty($_REQUEST["date"])) {
      $_REQUEST["date"] = strftime("%Y-%m-%d");
   }
   if (!isset($_REQUEST["type"])) {
      $_REQUEST["type"] = "week";
   }

   $planning = new Planning();
   $planning->show($_REQUEST);

   Html::footer();
}
?>