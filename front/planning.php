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


if (!isset($_GET["uID"])) {
   if (($uid=Session::getLoginUserID()) && !Session::haveRight("show_all_planning","1")) {
      $_GET["uID"] = $uid;
   } else {
      $_GET["uID"] = 0;
   }
}

if (!isset($_GET["gID"])) {
   $_GET["gID"] = 0;
}

if (!isset($_GET["usertype"])) {
   $_GET["usertype"] = "user";
}



if (isset($_REQUEST['checkavailability'])) {
   Html::popHeader($LANG['common'][75]);
   if (!isset($_REQUEST["begin"])) {
      $_REQUEST["begin"] = "";
   }
   if (!isset($_REQUEST["end"])) {
      $_REQUEST["end"] = "";
   }   
   if (!isset($_REQUEST["users_id"])) {
      $_REQUEST["users_id"] = "";
   }  
   Planning::checkAvailability($_REQUEST['users_id'], $_REQUEST['begin'], $_REQUEST['end']);
   Html::popFooter();

} else if (isset($_GET['genical'])) {
   if (isset($_GET['token'])) {
      // Check user token
      /// TODO : complex : check if the request is valid : rights on uID / gID ?
      $user = new User();
      if ($user->getFromDBByToken($_GET['token'])) {
         if (isset($_GET['entities_id']) && isset($_GET['is_recursive'])) {
            $user->loadMinimalSession($_GET['entities_id'], $_GET['is_recursive']);
         }

         Planning::generateIcal($_GET["uID"], $_GET["gID"]);
      }
   }
} else {
   switch ($_GET["usertype"]) {
      case "user" :
         $_GET['gID'] = 0;
         break;

      case "group" :
         $_GET['uID'] = 0;
         break;

      case "user_group" :
         $_GET['gID'] = "mine";
         $_GET['uID'] = Session::getLoginUserID();
         break;
   }
   Html::header(Toolbox::ucfirst($LANG['log'][16]), $_SERVER['PHP_SELF'], "maintain", "planning");

   Session::checkSeveralRightsOr(array('show_all_planning' => '1',
                                       'show_planning'     => '1'));

   if (!isset($_GET["date"]) || empty($_GET["date"])) {
      $_GET["date"] = strftime("%Y-%m-%d");
   }
   if (!isset($_GET["type"])) {
      $_GET["type"] = "week";
   }

   Planning::showSelectionForm($_GET['type'], $_GET['date'], $_GET["usertype"], $_GET["uID"],
                               $_GET["gID"]);

   Planning::show($_GET['uID'], $_GET['gID'], $_GET["date"], $_GET["type"]);

   Html::footer();
}
?>