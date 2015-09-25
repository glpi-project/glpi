<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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

Session::checkLoginUser();
$fup   = new TicketFollowup();
$track = new Ticket();

if (!isset($_GET['id'])) {
   $_GET['id'] = "";
}

if (isset($_POST["add"])) {
   $track->check(-1, CREATE, $_POST);

   if (isset($_POST["my_items"]) && !empty($_POST["my_items"])) {
      $splitter = explode("_",$_POST["my_items"]);
      if (count($splitter) == 2) {
         $_POST["itemtype"] = $splitter[0];
         $_POST["items_id"] = $splitter[1];
      }
   }
   if ($id = $track->add($_POST)) {
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($track->getFormURL()."?id=".$id);
      }
   }
   Html::back();

} else if (isset($_POST['update'])) {
   $track->check($_POST['id'], UPDATE);


   $track->update($_POST);
   Event::log($_POST["id"], "ticket", 4, "tracking",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));


   if ($track->can($_POST["id"], READ)) {
      $toadd = '';
      // Copy solution to KB redirect to KB
      if (isset($_POST['_sol_to_kb']) && $_POST['_sol_to_kb']) {
         $toadd = "&_sol_to_kb=1";
      }
      Html::redirect($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$_POST["id"].$toadd);
   }
   Session::addMessageAfterRedirect(__('You have been redirected because you no longer have access to this ticket'),
                                    true, ERROR);
   Html::redirect($CFG_GLPI["root_doc"]."/front/ticket.php");

} else if (isset($_POST['delete'])) {
   $track->check($_POST['id'], DELETE);
   if ($track->delete($_POST)) {
      Event::log($_POST["id"], "ticket", 4, "tracking",
                 //TRANS: %s is the user login
                 sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));

   }
   $track->redirectToList();

} else if (isset($_POST['purge'])) {
   $track->check($_POST['id'], PURGE);
   if ($track->delete($_POST, 1)) {
      Event::log($_POST["id"], "ticket", 4, "tracking",
                 //TRANS: %s is the user login
                 sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   }
   $track->redirectToList();

} else if (isset($_POST["restore"])) {
   $track->check($_POST['id'], DELETE);
   if ($track->restore($_POST)) {
      Event::log($_POST["id"], "ticket", 4, "tracking",
                 //TRANS: %s is the user login
                 sprintf(__('%s restores an item'), $_SESSION["glpiname"]));
   }
   $track->redirectToList();

} else if (isset($_POST['sla_delete'])) {
   $track->check($_POST["id"], UPDATE);

   $track->deleteSLA($_POST["id"], $_POST['delete_due_date']);
   Event::log($_POST["id"], "ticket", 4, "tracking",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));

   Html::redirect($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$_POST["id"]);

} else if (isset($_POST['addme_observer'])) {
   $ticket_user = new Ticket_User();
   $track->check($_POST['tickets_id'], READ);
   $input = array('tickets_id'       => $_POST['tickets_id'],
                  'users_id'         => Session::getLoginUserID(),
                  'use_notification' => 1,
                  'type'             => CommonITILActor::OBSERVER);
   $ticket_user->add($input);

   Event::log($_POST['tickets_id'], "ticket", 4, "tracking",
              //TRANS: %s is the user login
              sprintf(__('%s adds an actor'), $_SESSION["glpiname"]));
   Html::redirect($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$_POST['tickets_id']);

} else if (isset($_POST['addme_assign'])) {
   $ticket_user = new Ticket_User();

   $track->check($_POST['tickets_id'], READ);
   $input = array('tickets_id'       => $_POST['tickets_id'],
                  'users_id'         => Session::getLoginUserID(),
                  'use_notification' => 1,
                  'type'             => CommonITILActor::ASSIGN);
   $ticket_user->add($input);
   Event::log($_POST['tickets_id'], "ticket", 4, "tracking",
              //TRANS: %s is the user login
              sprintf(__('%s adds an actor'), $_SESSION["glpiname"]));
   Html::redirect($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$_POST['tickets_id']);
} else if (isset($_REQUEST['delete_document'])) {
   $document_item = new Document_Item;
   $found_document_items = $document_item->find("itemtype = 'Ticket' ".
                                                " AND items_id = ".intval($_REQUEST['tickets_id']).
                                                " AND documents_id = ".intval($_REQUEST['documents_id']));
   foreach ($found_document_items  as $item) {
      $document_item->delete($item, true);
   }

   Html::back();
}

if (isset($_GET["id"]) && ($_GET["id"] > 0)) {
   if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
      Html::helpHeader(Ticket::getTypeName(Session::getPluralNumber()), '', $_SESSION["glpiname"]);
   } else {
      Html::header(Ticket::getTypeName(Session::getPluralNumber()), '', "helpdesk", "ticket");
   }

   $available_options = array('load_kb_sol', '_openfollowup');
   $options           = array();
   foreach ($available_options as $key) {
      if (isset($_GET[$key])) {
         $options[$key] = $_GET[$key];
      }
   }


   $options['id'] = $_GET["id"];
   $track->display($options);

   if (isset($_GET['_sol_to_kb'])) {
      Ajax::createIframeModalWindow('savetokb',
                                    $CFG_GLPI["root_doc"].
                                     "/front/knowbaseitem.form.php?_in_modal=1&item_itemtype=Ticket&item_items_id=".
                                     $_GET["id"],
                                    array('title'         => __('Save solution to the knowledge base'),
                                          'reloadonclose' => false));
      echo Html::scriptBlock(Html::jsGetElementbyID('savetokb').".dialog('open');");
   }

} else {
   Html::header(__('New ticket'),'',"helpdesk","ticket");
   unset($_REQUEST['id']);
   $track->display($_REQUEST);
}


if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
   Html::helpFooter();
} else {
   Html::footer();
}
?>
