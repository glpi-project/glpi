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

$problem = new Problem();
if (isset($_POST["add"])) {
   $problem->check(-1, CREATE, $_POST);

   if ($newID = $problem->add($_POST)) {
      Event::log($newID, "problem", 4, "maintain",
                 sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($problem->getLinkURL());
      }
   }
   Html::back();

} else if (isset($_POST["delete"])) {
   $problem->check($_POST["id"], DELETE);

   $problem->delete($_POST);
   Event::log($_POST["id"], "problem", 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));
   $problem->redirectToList();

} else if (isset($_POST["restore"])) {
   $problem->check($_POST["id"], DELETE);

   $problem->restore($_POST);
   Event::log($_POST["id"], "problem", 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s restores an item'), $_SESSION["glpiname"]));
   $problem->redirectToList();

} else if (isset($_POST["purge"])) {
   $problem->check($_POST["id"], PURGE);

   $problem->delete($_POST, 1);
   Event::log($_POST["id"], "problem", 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   $problem->redirectToList();

} else if (isset($_POST["update"])) {
   $problem->check($_POST["id"], UPDATE);

   $problem->update($_POST);
   Event::log($_POST["id"], "problem", 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));

   // Copy solution to KB redirect to KB
   if (isset($_POST['_sol_to_kb']) && $_POST['_sol_to_kb']) {
      Html::redirect(KnowbaseItem::getFormURL()."?id=new&item_itemtype=Problem&item_items_id=". $_POST["id"]);
   } else {
      Html::back();
   }

} else if (isset($_POST['addme_assign'])) {
   $problem_user = new Problem_User();
   $problem->check($_POST['problems_id'], READ);
   $input = ['problems_id'       => $_POST['problems_id'],
                  'users_id'         => Session::getLoginUserID(),
                  'use_notification' => 1,
                  'type'             => CommonITILActor::ASSIGN];
   $problem_user->add($input);
   Event::log($_POST['problems_id'], "problem", 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s adds an actor'), $_SESSION["glpiname"]));
   Html::redirect($problem->getFormURLWithID($_POST['problems_id']));
} else {
   Html::header(Problem::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "helpdesk", "problem");
   $problem->display($_GET);

   if (isset($_GET['id']) && ($_GET['id'] > 0) && isset($_GET['_sol_to_kb'])) {
      Ajax::createIframeModalWindow(
         'savetokb',
         KnowbaseItem::getFormURL() . '?_in_modal=1&item_itemtype=Problem&item_items_id=' . $_GET['id'],
         [
            'title'         => __('Save solution to the knowledge base'),
            'reloadonclose' => false,
         ]
      );
      echo Html::scriptBlock('$(function() {' . Html::jsGetElementbyID('savetokb') . '.dialog("open"); });');
   }

   Html::footer();
}
