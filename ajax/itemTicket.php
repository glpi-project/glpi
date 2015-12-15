<?php
/*
 * @version $Id: updateTrackingDeviceType_1.php 22657 2014-02-12 16:17:54Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

$AJAX_INCLUDE = 1;
include ('../inc/includes.php');

header('Content-Type: application/json; charset=UTF-8"');
Html::header_nocache();

Session::checkLoginUser();
$item_ticket = new Item_Ticket();
Toolbox::logDebug($_GET);
switch ($_GET['action']) {
   case 'addItem':
      if (isset($_GET['my_items']) && !empty($_GET['my_items'])) {
         list($_GET['itemtype'], $_GET['items_id']) = explode('_', $_GET['my_items']);
      }
      if (isset($_GET['items_id']) && !empty($_GET['items_id'])) {
         $added = true;
         if ($_GET['tickets_id'] > 0) {
            $added = $item_ticket->add($_GET);
         }
         $result = null;
         if ($added) {
            $result = Item_Ticket::showItemToAdd($_GET['tickets_id'], $_GET['itemtype'], $_GET['items_id'], array('rand' => $_GET['rand']));
         }

         echo json_encode(array('itemtype' => $_GET['itemtype'], 'items_id' => $_GET['items_id'], 'result' => $result));
      }
      break;
      
//   case 'deleteItem':
//      $result = false;
//      if (isset($_GET['items_id']) && !empty($_GET['items_id'])) {
//         $result = $item_ticket->deleteByCriteria(array('items_id' => $_GET['items_id'], 'itemtype' => $_GET['itemtype']));
//      }
//      echo json_encode(array('itemtype' => $_GET['itemtype'], 'items_id' => $_GET['items_id'], 'result' => $result));
//      break;
}

?>