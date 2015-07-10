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

Session ::checkLoginUser();

$item = new Item_Ticket();

if (isset($_POST["add"])) {
   if (isset($_POST['my_items']) && !empty($_POST['my_items'])) {
      list($_POST['itemtype'], $_POST['items_id']) = explode('_', $_POST['my_items']);
   }

   if(!isset($_POST['items_id']) || empty($_POST['items_id'])){
      $message = sprintf(__('Mandatory fields are not filled. Please correct: %s'),
                         _n('Associated element', 'Associated elements', 1));
      Session::addMessageAfterRedirect($message, false, ERROR);
      Html::back();
   }
   
   $item->check(-1, CREATE, $_POST);

   if ($item->add($_POST)) {
      Event::log($_POST["tickets_id"], "ticket", 4, "tracking",
                  //TRANS: %s is the user login
                  sprintf(__('%s adds a link with an item'), $_SESSION["glpiname"]));
   }
   Html::back();

}

Html::displayErrorAndDie("lost");
?>