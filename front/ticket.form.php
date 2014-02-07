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

/** @file
* @brief
*/

include ('../inc/includes.php');

Session::checkLoginUser();
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
   Html::back();

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
   Event::log($_POST["id"], "ticket", 4, "tracking",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));

   // Copy solution to KB redirect to KB
   if (isset($_POST['_sol_to_kb']) && $_POST['_sol_to_kb']) {
      Html::redirect($CFG_GLPI["root_doc"].
                     "/front/knowbaseitem.form.php?id=new&item_itemtype=Ticket&item_items_id=".$_POST["id"]);
   } else {
      if ($track->can($_POST["id"],'r')) {
         Html::redirect($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$_POST["id"]);
      }
      Session::addMessageAfterRedirect(__('You have been redirected because you no longer have access to this ticket'),
                                       true, ERROR);
      Html::redirect($CFG_GLPI["root_doc"]."/front/ticket.php");
   }

} else if (isset($_POST['delete'])) {
   $track->check($_POST['id'],'d');
   if ($track->delete($_POST)) {
      Event::log($_POST["id"], "ticket", 4, "tracking",
                 //TRANS: %s is the user login
                 sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));

   }
   $track->redirectToList();

} else if (isset($_POST['purge'])) {
   $track->check($_POST['id'],'d');
   if ($track->delete($_POST, 1)) {
      Event::log($_POST["id"], "ticket", 4, "tracking",
                 //TRANS: %s is the user login
                 sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   }
   $track->redirectToList();

} else if (isset($_POST["restore"])) {
   $track->check($_POST['id'], 'd');
   if ($track->restore($_POST)) {
      Event::log($_POST["id"], "ticket", 4, "tracking",
                 //TRANS: %s is the user login
                 sprintf(__('%s restores an item'), $_SESSION["glpiname"]));
   }
   $track->redirectToList();

} else if (isset($_POST['sla_delete'])) {
   $track->check($_POST["id"],'w');

   $track->deleteSLA($_POST["id"]);
   Event::log($_POST["id"], "ticket", 4, "tracking",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));

   Html::redirect($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$_POST["id"]);

} else if (isset($_POST['delete_user'])) {
   ///TODO try to move it to specific form page
   $ticket_user = new Ticket_User();
   $ticket_user->check($_POST['id'], 'd');
   $ticket_user->delete($_POST);

   Event::log($_POST['tickets_id'], "ticket", 4, "tracking",
              //TRANS: %s is the user login
              sprintf(__('%s deletes an actor'), $_SESSION["glpiname"]));

   if ($track->can($_POST["id"],'r')) {
      Html::redirect($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$_POST["tickets_id"]);
   }
   Session::addMessageAfterRedirect(__('You have been redirected because you no longer have access to this item'),
                                    true, ERROR);
   Html::redirect($CFG_GLPI["root_doc"]."/front/ticket.php");

} else if (isset($_POST['delete_group'])) {
   $group_ticket = new Group_Ticket();
   $group_ticket->check($_POST['id'], 'd');
   $group_ticket->delete($_POST);

   Event::log($_POST['tickets_id'], "ticket", 4, "tracking",
              sprintf(__('%s deletes an actor'), $_SESSION["glpiname"]));

   if ($track->can($_POST["id"],'r')) {
      Html::redirect($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$_POST["tickets_id"]);
   }
   Session::addMessageAfterRedirect(__('You have been redirected because you no longer have access to this item'),
                                    true, ERROR);
   Html::redirect($CFG_GLPI["root_doc"]."/front/ticket.php");


} else if (isset($_POST['delete_supplier'])) {
   $supplier_ticket = new Supplier_Ticket();
   $supplier_ticket->check($_POST['id'], 'd');
   $supplier_ticket->delete($_POST);

   Event::log($_POST['tickets_id'], "ticket", 4, "tracking",
              sprintf(__('%s deletes an actor'), $_SESSION["glpiname"]));
   Html::redirect($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$_POST['tickets_id']);

} else if (isset($_POST['addme_observer'])) {
   $ticket_user = new Ticket_User();
   $track->check($_POST['tickets_id'], 'r');
   $input = array('tickets_id'       => $_POST['tickets_id'],
                  'users_id'         => Session::getLoginUserID(),
                  'use_notification' => 1,
                  'type'             => CommonITILActor::OBSERVER);
   $ticket_user->add($input);

   Event::log($_POST['tickets_id'], "ticket", 4, "tracking",
              //TRANS: %s is the user login
              sprintf(__('%s adds an actor'), $_SESSION["glpiname"]));
   Html::redirect($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$_POST['tickets_id']);

}

if (isset($_GET["id"]) && ($_GET["id"] > 0)) {
   if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
      Html::helpHeader(Ticket::getTypeName(2), '', $_SESSION["glpiname"]);
   } else {
      Html::header(Ticket::getTypeName(2), '', "maintain", "ticket");
   }

   $available_options = array('load_kb_sol');
   $options           = array();
   foreach ($available_options as $key) {
      if (isset($_GET[$key])) {
         $options[$key] = $_GET[$key];
      }
   }
   $track->showForm($_GET["id"],$options);

} else {
   Html::header(__('New ticket'),'',"maintain","ticket");

   $track->showForm(0);
}


if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
   Html::helpFooter();
} else {
   Html::footer();
}
?>
