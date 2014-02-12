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
* @since version 0.84
*/

include ('../inc/includes.php');

Session::checkCentralAccess();

if (isset($_POST["add"])) {
   if (isset($_POST['devicetype'])) {
      if ($link = getItemForItemtype('Item_'.$_POST['devicetype'])) {
         $link->addDevices(1, $_POST['itemtype'], $_POST['items_id'], $_POST['devices_id']);
      }
   }
   Html::back();
} else if (isset($_POST["updateall"])) {
   Item_Devices::updateAll($_POST, false);
   Html::back();
} else if (isset($_POST["delete"])) {
   Item_Devices::updateAll($_POST, true);
   Html::back();
}
Html::displayErrorAndDie('Lost');
?>