<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

use Glpi\Event;

include ('../inc/includes.php');

if (empty($_GET["id"])) {
   $_GET["id"] = '';
}

Session::checkLoginUser();

$change = new Change();
if (isset($_POST["add"])) {
   $change->check(-1, CREATE, $_POST);

   $newID = $change->add($_POST);
   Event::log($newID, "change", 4, "maintain",
              //TRANS: %1$s is the user login, %2$s is the name of the item
              sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
   if ($_SESSION['glpibackcreated']) {
      Html::redirect($change->getLinkURL());
   } else {
      Html::back();
   }

} else if (isset($_POST["delete"])) {
   $change->check($_POST["id"], DELETE);

   $change->delete($_POST);
   Event::log($_POST["id"], "change", 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));
   $change->redirectToList();

} else if (isset($_POST["restore"])) {
   $change->check($_POST["id"], DELETE);

   $change->restore($_POST);
   Event::log($_POST["id"], "change", 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s restores an item'), $_SESSION["glpiname"]));
   $change->redirectToList();

} else if (isset($_POST["purge"])) {
   $change->check($_POST["id"], PURGE);
   $change->delete($_POST, 1);

   Event::log($_POST["id"], "change", 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   $change->redirectToList();

} else if (isset($_POST["update"])) {
   $change->check($_POST["id"], UPDATE);

   $change->update($_POST);
   Event::log($_POST["id"], "change", 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));

   Html::back();

} else if (isset($_POST['addme_assign'])) {
   $change_user = new Change_User();

   $change->check($_POST['changes_id'], READ);
   $input = ['changes_id'       => $_POST['changes_id'],
                  'users_id'         => Session::getLoginUserID(),
                  'use_notification' => 1,
                  'type'             => CommonITILActor::ASSIGN];
   $change_user->add($input);
   Event::log($_POST['changes_id'], "change", 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s adds an actor'), $_SESSION["glpiname"]));
   Html::redirect(Change::getFormURLWithID($_POST['changes_id']));
} else if (isset($_REQUEST['delete_document'])) {
   $doc = new Document();
   $doc->getFromDB(intval($_REQUEST['documents_id']));
   if ($doc->can($doc->getID(), UPDATE)) {
      $document_item = new Document_Item;
      $found_document_items = $document_item->find([
         'itemtype'     => 'Change',
         'items_id'     => (int)$_REQUEST['changes_id'],
         'documents_id' => $doc->getID()
      ]);
      foreach ($found_document_items  as $item) {
         $document_item->delete(Toolbox::addslashes_deep($item), true);
      }
   }
   Html::back();
} else {
   Html::header(Change::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "helpdesk", "change");
   $change->display($_REQUEST);

   if (isset($_GET['id']) && ($_GET['id'] > 0) && isset($_GET['_sol_to_kb'])) {
      Ajax::createIframeModalWindow(
         'savetokb',
         KnowbaseItem::getFormURL() . '?_in_modal=1&item_itemtype=Change&item_items_id=' . $_GET['id'],
         [
            'title'         => __('Save solution to the knowledge base'),
            'reloadonclose' => false,
         ]
      );
      echo Html::scriptBlock('$(function() {' . Html::jsGetElementbyID('savetokb') . '.dialog("open"); });');
   }

   Html::footer();
}
