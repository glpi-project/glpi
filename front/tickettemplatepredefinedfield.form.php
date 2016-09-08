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

Session ::checkRight('tickettemplate', UPDATE);

$item = new TicketTemplatePredefinedField();

// Use masiveaction system to manage add value
if (isset($_POST["massiveaction"])) {
   $item->check(-1, UPDATE, $_POST);
   if (isset($_POST['items_tickets_id']) && isset($_POST['add_items_id'])) {
      $_POST['items_tickets_id'] = $_POST['items_tickets_id']."_".$_POST['add_items_id'];
   }
   if ($item->add($_POST)) {
      Event::log($_POST["tickettemplates_id"], "tickettemplate", 4, "maintain",
                 //TRANS: %s is the user login
                 sprintf(__('%s adds predefined field'), $_SESSION["glpiname"]));
   }
   Html::back();

}

Html::displayErrorAndDie("lost");
?>