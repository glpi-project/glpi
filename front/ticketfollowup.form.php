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

Session::checkLoginUser();

$fup = new TicketFollowup();

if (isset($_POST["add"])) {
   $fup->check(-1,'w',$_POST);
   $fup->add($_POST);

   Event::log($fup->getField('tickets_id'), "ticket", 4, "tracking",
              $_SESSION["glpiname"]."  ".$LANG['log'][21]);
   Html::back();

} else if (isset($_POST['add_close']) || isset($_POST['add_reopen'])) {
   $ticket = new Ticket();
   if ($ticket->getFromDB($_POST["tickets_id"]) && $ticket->canApprove()) {
      $fup->add($_POST);

      Event::log($fup->getField('tickets_id'), "ticket", 4, "tracking",
                 $_SESSION["glpiname"]."  ".$LANG['log'][29]);
      Html::back();
   }

} else if (isset($_POST["update"])) {
   $fup->check($_POST['id'], 'w');
   $fup->update($_POST);

   Event::log($fup->getField('tickets_id'), "ticket", 4, "tracking",
              $_SESSION["glpiname"]."  ".$LANG['log'][21]);
   Html::redirect(Toolbox::getItemTypeFormURL('Ticket')."?id=".$fup->getField('tickets_id'));

} else if (isset($_POST["delete"])) {
   $fup->check($_POST['id'], 'w');
   $fup->delete($_POST);

   Event::log($fup->getField('tickets_id'), "ticket", 4, "tracking",
              $_SESSION["glpiname"]." ".$LANG['log'][21]);
   Html::redirect(Toolbox::getItemTypeFormURL('Ticket')."?id=".$fup->getField('tickets_id'));
}

Html::displayErrorAndDie('Lost');
?>