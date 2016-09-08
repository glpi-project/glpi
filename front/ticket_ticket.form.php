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
* @since version 0.84
*/

include ('../inc/includes.php');

$ticket_ticket = new Ticket_Ticket();

Session ::checkCentralAccess();

if (isset($_POST['purge'])) {
   $ticket_ticket->check($_POST['id'], PURGE);

   $ticket_ticket->delete($_POST, 1);

   Event::log($_POST['tickets_id'], "ticket", 4, "tracking",
              //TRANS: %s is the user login
              sprintf(__('%s purges link between tickets'), $_SESSION["glpiname"]));
   Html::redirect($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$_POST['tickets_id']);

}
Html::displayErrorAndDie("lost");
?>