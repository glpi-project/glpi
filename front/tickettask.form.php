<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------


define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkCentralAccess();

$task = new TicketTask();

if (isset($_POST["add"])) {
   $task->check(-1,'w',$_POST);
   $task->add($_POST);

   Event::log($task->getField('tickets_id'), "ticket", 4, "tracking",
              $_SESSION["glpiname"]."  ".$LANG['log'][21]);
   glpi_header(getItemTypeFormURL('Ticket')."?id=".$task->getField('tickets_id'));

} else if (isset($_POST["delete"])) {
   $task->check($_POST['id'], 'd');
   $task->delete($_POST);

   Event::log($task->getField('tickets_id'), "ticket", 4, "tracking",
              $_SESSION["glpiname"]." ".$LANG['log'][21]);
   glpi_header(getItemTypeFormURL('Ticket')."?id=".$task->getField('tickets_id'));

} else if (isset($_POST["update"])) {
   $task->check($_POST["id"],'w');
   $task->update($_POST);

   Event::log($task->getField('tickets_id'), "ticket", 4, "tracking",
              $_SESSION["glpiname"]." ".$LANG['log'][21]);
   glpi_header($_SERVER['HTTP_REFERER']);

}

displayErrorAndDie('Lost');

?>
