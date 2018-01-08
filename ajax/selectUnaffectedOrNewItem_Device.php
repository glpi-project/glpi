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

/**
 * @since 0.85
 */

include ('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkCentralAccess();

// Make a select box
if ($_POST['items_id']
    && $_POST['itemtype'] && class_exists($_POST['itemtype'])) {
   $devicetype = $_POST['itemtype'];
   $linktype   = $devicetype::getItem_DeviceType();

   if (count($linktype::getSpecificities())) {
      $name_field = "CONCAT_WS(' - ', `".implode('`, `',
                                                 array_keys($linktype::getSpecificities()))."`)";
   } else {
      $name_field = "`id`";
   }
   $query = "SELECT `id`, $name_field AS name
             FROM `".$linktype::getTable()."`
             WHERE `".$devicetype::getForeignKeyField()."` = '".$_POST['items_id']."'
                    AND `itemtype` = ''";
   $result = $DB->request($query);
   echo "<table width='100%'><tr><td>" . __('Choose an existing device') . "</td><td rowspan='2'>" .
        __('and/or') . "</td><td>" . __('Add new devices') . '</td></tr>';
   echo "<tr><td>";
   if ($result->numrows() == 0) {
      echo __('No unaffected device !');
   } else {
      $devices = [];
      foreach ($result as $row) {
         $name = $row['name'];
         if (empty($name)) {
            $name = $row['id'];
         }
         $devices[$row['id']] = $name;

      }
      dropdown::showFromArray($linktype::getForeignKeyField(), $devices, ['multiple' => true]);
   }
   echo "</td><td>";
   Dropdown::showNumber('new_devices', ['min'   => 0, 'max'   => 10]);
   echo "</td></tr></table>";

}
