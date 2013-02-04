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

Session::checkCentralAccess();
Session::checkRight("reservation_central", "w");

$ri = new ReservationItem();

if (isset($_REQUEST["add"])) {
   if ($newID = $ri->add($_REQUEST)) {
      Event::log($newID, "reservationitem", 4, "inventory",
                 $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_REQUEST["itemtype"]."-".
                     $_REQUEST["items_id"].".");
   }
   Html::back();

} else if (isset($_REQUEST["delete"])) {
   $ri->delete($_REQUEST);
   Event::log($_REQUEST['id'], "reservationitem", 4, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][22]);
   Html::back();

} else if (isset($_REQUEST["update"])) {
   // from reservation form
   if (isset($_POST["id"])) {
      $_REQUEST = $_POST;
   } // else from object from
   $ri->update($_REQUEST);
   Event::log($_REQUEST['id'], "reservationitem", 4, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][21]);
   Html::back();

} else {
   Html::header($LANG['Menu'][17], $_SERVER['PHP_SELF'], "utils", "reservation");
   $ri->showForm($_GET["id"]);
}

Html::footer();
?>