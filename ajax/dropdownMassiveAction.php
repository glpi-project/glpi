<?php
/*
 * @version $Id$
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

include ('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();


if (isset($_POST["action"]) && ($_POST["action"] != '-1')
    && isset($_POST["itemtype"]) && !empty($_POST["itemtype"])) {
    if (!isset($_POST['is_deleted'])) {
      $_POST['is_deleted'] = 0;
    }

   if (!($item = getItemForItemtype($_POST['itemtype']))) {
      exit();
   }
   $checkitem = NULL;

   if (isset($_POST['check_itemtype'])) {
      if (!($checkitem = getItemForItemtype($_POST['check_itemtype']))) {
         exit();
      }
      if (isset($_POST['check_items_id'])) {
         if (!$checkitem->getFromDB($_POST['check_items_id'])) {
            exit();
         }
      echo "<input type='hidden' name='check_items_id' value='".$_POST["check_items_id"]."'>";
      }
      echo "<input type='hidden' name='check_itemtype' value='".$_POST["check_itemtype"]."'>";
   }

   $actions = $item->getAllMassiveActions($_POST['is_deleted'], $checkitem);
   if (!isset($_POST['specific_action']) || !$_POST['specific_action']) {
      echo "<input type='hidden' name='specific_action' value='0'>";
      if (!isset($actions[$_POST['action']])) {
         Html::displayRightError();
         exit();
      }
   } else {
      if (!isset($actions[$_POST['action']])) {
         echo "<input type='hidden' name='specific_action' value='1'>";
      } else {
         echo "<input type='hidden' name='specific_action' value='0'>";
      }
   }

   echo "<input type='hidden' name='action' value='".$_POST["action"]."'>";
   echo "<input type='hidden' name='itemtype' value='".$_POST["itemtype"]."'>";
   echo "<input type='hidden' name='is_deleted' value='".$_POST["is_deleted"]."'>";
   echo '&nbsp;';

   // Plugin specific actions
   $split = explode('_',$_POST["action"]);

   if (($split[0] == 'plugin') && isset($split[1])) {
      // Normalized name plugin_name_action
      // Allow hook from any plugin on any (core or plugin) type
      Plugin::doOneHook($split[1], 'MassiveActionsDisplay',
                        array('itemtype' => $_POST["itemtype"],
                              'action'   => $_POST["action"]));

//    } else if ($plug=isPluginItemType($_POST["itemtype"])) {
      // non-normalized name
      // hook from the plugin defining the type
//       Plugin::doOneHook($plug['plugin'], 'MassiveActionsDisplay', $_POST["itemtype"],
//                         $_POST["action"]);
   } else {
      $item->showMassiveActionsParameters($_POST);
   }
}
?>
