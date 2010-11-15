<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkLoginUser();
$fup   = new TicketFollowup();
$track = new Ticket();

if (!isset($_GET['id'])) {
   $_GET['id'] = "";
}

if (isset($_POST["add"])) {
   $track->check(-1,'w',$_POST);

   if (isset($_POST["_my_items"]) && !empty($_POST["_my_items"])) {
      $splitter = explode("_",$_POST["_my_items"]);
      if (count($splitter) == 2) {
         $_POST["itemtype"] = $splitter[0];
         $_POST["items_id"] = $splitter[1];
      }
   }

   $track->add($_POST);

   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST['update'])) {
   $track->check($_POST['id'],'w');

   if (isset($_POST["_my_items"]) && !empty($_POST["_my_items"])) {
      $splitter = explode("_",$_POST["_my_items"]);
      if (count($splitter) == 2) {
         $_POST["itemtype"] = $splitter[0];
         $_POST["items_id"] = $splitter[1];
      }
   }

   $track->update($_POST);
   Event::log($_POST["id"], "ticket", 4, "tracking", $_SESSION["glpiname"]." ".$LANG['log'][21]);

   glpi_header($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$_POST["id"]);

} else if (isset($_POST['delete'])) {
   $track->check($_POST['id'],'d');
   $track->delete($_POST);
   Event::log($_POST["id"], "ticket", 4, "tracking", $_SESSION["glpiname"]." ".$LANG['log'][22]);

   glpi_header($CFG_GLPI["root_doc"]."/front/ticket.php");
/*
} else if (isset($_POST['add']) || isset($_POST['add_close']) || isset($_POST['add_reopen'])) {
   checkSeveralRightsOr(array('add_followups'     => '1',
                              'global_add_followups' => '1',
                              'show_assign_ticket' => '1'));
   $newID = $fup->add($_POST);

   Event::log($_POST["tickets_id"], "ticket", 4, "tracking",
              $_SESSION["glpiname"]." ".$LANG['log'][20]." $newID.");
   glpi_header($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".
               $_POST["tickets_id"]."&glpi_tab=1&itemtype=Ticket");

*/

} else if (isset($_POST['sla_delete'])) {
   $track->check($_POST["id"],'w');

   $_POST['slas_id']               = 0;
   $_POST['slalevels_id']          = 0;
   $_POST['sla_wainting_duration'] = 0;

   $track->update($_POST);
   Event::log($_POST["id"], "ticket", 4, "tracking", $_SESSION["glpiname"]." ".$LANG['log'][21]);

   glpi_header($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$_POST["id"]);

} else if (isset($_REQUEST['delete_link'])) {
   $ticket_ticket = new Ticket_Ticket();
   $ticket_ticket->check($_REQUEST['id'],'w');

   $ticket_ticket->delete($_REQUEST);

   Event::log($_REQUEST['tickets_id'], "ticket", 4, "tracking",
              $_SESSION["glpiname"]." ".$LANG['log'][120]);
   glpi_header($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$_REQUEST['tickets_id']);

} else if (isset($_REQUEST['delete_user'])) {
   $ticket_user = new Ticket_User();
   $ticket_user->check($_REQUEST['id'], 'w');
   $ticket_user->delete($_REQUEST);

   Event::log($_REQUEST['tickets_id'], "ticket", 4,
              "tracking", $_SESSION["glpiname"]." ".$LANG['log'][122]);
   glpi_header($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$_REQUEST['tickets_id']);

} else if (isset($_REQUEST['delete_group'])) {
   $group_ticket = new Group_Ticket();
   $group_ticket->check($_REQUEST['id'], 'w');
   $group_ticket->delete($_REQUEST);

   Event::log($_REQUEST['tickets_id'], "ticket", 4, "tracking",
              $_SESSION["glpiname"]." ".$LANG['log'][122]);
   glpi_header($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$_REQUEST['tickets_id']);
}

if (isset($_GET["id"]) && $_GET["id"]>0) {
   if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
      helpHeader($LANG['Menu'][5],'',$_SESSION["glpiname"]);
   } else {
      commonHeader($LANG['Menu'][5],'',"maintain","ticket");
   }
   $track->showForm($_GET["id"]);

} else {
   commonHeader($LANG['job'][13],'',"maintain","ticket");

   // Set default value...
   $values = array('_users_id_requester'  => getLoginUserID(),
                   '_groups_id_requester' => 0,
                   '_users_id_assign'     => 0,
                   '_groups_id_assign'    => 0,
                   '_users_id_observer'   => 0,
                   '_groups_id_observer'  => 0,
                   'suppliers_id_assign'     => 0,
                   'name'                    => '',
                   'content'                 => '',
                   'ticketcategories_id'     => 0,
                   'urgency'                 => 3,
                   'impact'                  => 3,
                   'priority'                => Ticket::computePriority(3,3),
                   'requesttypes_id'         => $_SESSION["glpidefault_requesttypes_id"],
                   'hour'                    => 0,
                   'minute'                  => 0,
                   'date'                    => $_SESSION["glpi_currenttime"],
                   'entities_id'             => $_SESSION["glpiactive_entity"],
                   'status'                  => 'new',
                   'followup'                => array(),
                   'itemtype'                => '',
                   'items_id'                => 0,
                   'plan'                    => array(),
                   'global_validation'       => 'none',
                   'due_date'                => '',
                   'slas_id'                 => 0,
                   'type'                    => -1);

   // Restore saved value or override with page parameter
   foreach ($values as $name => $value) {
      if (isset($_REQUEST[$name])) {
         $values[$name] = $_REQUEST[$name];
      } else if (isset($_SESSION["helpdeskSaved"][$name])) {
         $values[$name] = $_SESSION["helpdeskSaved"]["$name"];
      }
   }
   // Clean text fields
   $values['name'] = stripslashes($values['name']);
   $values['content'] = cleanPostForTextArea($values['content']);

   if (isset($_SESSION["helpdeskSaved"])) {
      unset($_SESSION["helpdeskSaved"]);
   }
   if ($values['type']<=0) {
      $values['type'] = EntityData::getUsedConfig('tickettype',$values['entities_id']);
   }

   $track->showForm(0, $values);
}


if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
   helpFooter();
} else {
   commonFooter();
}


?>
