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

include ('../inc/includes.php');

Session::checkLoginUser();

$comment = new KnowbaseItem_Comment();
if (!isset($_POST['knowbaseitems_id'])) {
   $message = __('Mandatory fields are not filled!');
   Session::addMessageAfterRedirect($message, false, ERROR);
   Html::back();
}
$kbitem = new KnowbaseItem();
$kbitem->getFromDB($_POST['knowbaseitems_id']);
if (!$kbitem->canComment()) {
    Html::displayRightError();
}

if (isset($_POST["add"])) {
   if (!isset($_POST['knowbaseitems_id']) || !isset($_POST['comment'])) {
      $message = __('Mandatory fields are not filled!');
      Session::addMessageAfterRedirect($message, false, ERROR);
      Html::back();
   }

   if ($newid = $comment->add($_POST)) {
      Event::log($_POST["knowbaseitems_id"], "knowbaseitem_comment", 4, "tracking",
                  sprintf(__('%s adds a comment on knowledge base'), $_SESSION["glpiname"]));
      Session::addMessageAfterRedirect(
         "<a href='#kbcomment$newid'>" . __('Your comment has been added') . "</a>",
         false,
         INFO
      );
   }
   Html::back();
}

if (isset($_POST["edit"])) {
   if (!isset($_POST['knowbaseitems_id']) || !isset($_POST['id']) || !isset($_POST['comment'])) {
      $message = __('Mandatory fields are not filled!');
      Session::addMessageAfterRedirect($message, false, ERROR);
      Html::back();
   }

   $comment->getFromDB($_POST['id']);
   $data = array_merge($comment->fields, $_POST);
   if ($comment->update($data)) {
      Event::log($_POST["knowbaseitems_id"], "knowbaseitem_comment", 4, "tracking",
                  sprintf(__('%s edit a comment on knowledge base'), $_SESSION["glpiname"]));
      Session::addMessageAfterRedirect(
         "<a href='#kbcomment{$comment->getID()}'>" . __('Your comment has been edited') . "</a>",
         false,
         INFO
      );
   }
   Html::back();
}

Html::displayErrorAndDie("lost");
