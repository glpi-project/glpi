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
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

// Make a select box
if (isset($_POST["itemtype"])
    && CommonITILObject::isPossibleToAssignType($_POST["itemtype"])) {
   $table = getTableForItemType($_POST["itemtype"]);

   $rand = mt_rand();
   if (isset($_POST["rand"])) {
      $rand = $_POST["rand"];
   }

   // Message for post-only
   if (!isset($_POST["admin"]) || ($_POST["admin"] == 0)) {
      echo "<br>".__('Enter the first letters (user, item name, serial or asset number)');
   }
   echo "<br>";
   $field_id = Html::cleanId("dropdown_".$_POST['myname'].$rand);
   $p = ['itemtype'            => $_POST["itemtype"],
              'entity_restrict'     => $_POST['entity_restrict'],
              'table'               => $table,
              'multiple'            => $_POST["multiple"],
              'myname'              => $_POST["myname"],
              'rand'                => $_POST["rand"]];

   if (isset($_POST["used"]) && !empty($_POST["used"])) {
      if (isset($_POST["used"][$_POST["itemtype"]])) {
         $p["used"] = $_POST["used"][$_POST["itemtype"]];
      }
   }

   echo Html::jsAjaxDropdown($_POST['myname'], $field_id,
                             $CFG_GLPI['root_doc']."/ajax/getDropdownFindNum.php",
                             $p);

   // Auto update summary of active or just solved tickets
   $params = ['items_id' => '__VALUE__',
                   'itemtype' => $_POST['itemtype']];
   Ajax::updateItemOnSelectEvent($field_id, "item_ticket_selection_information$rand",
                                 $CFG_GLPI["root_doc"]."/ajax/ticketiteminformation.php",
                                 $params);
}
