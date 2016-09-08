<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

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

include ('../inc/includes.php');

Session::checkRight("consumable", READ);

$con      = new Consumable();
$constype = new ConsumableItem();

if (isset($_POST["add_several"])) {
   $constype->check($_POST["consumableitems_id"], UPDATE);

   for ($i=0 ; $i<$_POST["to_add"] ; $i++) {
      unset($con->fields["id"]);
      $con->add($_POST);
   }
   Event::log($_POST["consumableitems_id"], "consumableitems", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s adds consumables'), $_SESSION["glpiname"]));

   Html::back();

} else {
   Html::back();
}
?>
