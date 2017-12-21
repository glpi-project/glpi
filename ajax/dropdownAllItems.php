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

Session::checkCentralAccess();

// Make a select box
if ($_POST["idtable"] && class_exists($_POST["idtable"])) {
   $table = getTableForItemType($_POST["idtable"]);

   // Link to user for search only > normal users
   $link = "getDropdownValue.php";

   if ($_POST["idtable"] == 'User') {
      $link = "getDropdownUsers.php";
   }

   $rand     = mt_rand();
   if (isset($_POST['rand'])) {
      $rand = $_POST['rand'];
   }

   $field_id = Html::cleanId("dropdown_".$_POST["name"].$rand);

   $p        = ['value'               => 0,
                     'valuename'           => Dropdown::EMPTY_VALUE,
                     'itemtype'            => $_POST["idtable"],
                     'display_emptychoice' => true,
                     'displaywith'         => ['otherserial', 'serial']];
   if (isset($_POST['value'])) {
      $p['value'] = $_POST['value'];
   }
   if (isset($_POST['entity_restrict'])) {
      $p['entity_restrict'] = $_POST['entity_restrict'];
   }
   if (isset($_POST['condition'])) {
      $p['condition'] = $_POST['condition'];
   }
   if (isset($_POST['used'])) {
      $_POST['used'] = Toolbox::jsonDecode($_POST['used'], true);
   }
   if (isset($_POST['used'][$_POST['idtable']])) {
      $p['used'] = $_POST['used'][$_POST['idtable']];
   }

   echo  Html::jsAjaxDropdown($_POST["name"], $field_id,
                              $CFG_GLPI['root_doc']."/ajax/".$link,
                              $p);

   if (!empty($_POST['showItemSpecificity'])) {
      $params = ['items_id' => '__VALUE__',
                      'itemtype' => $_POST["idtable"]];
      if (isset($_POST['entity_restrict'])) {
         $params['entity_restrict'] = $_POST['entity_restrict'];
      }

      Ajax::updateItemOnSelectEvent($field_id, "showItemSpecificity_".$_POST["name"]."$rand",
                                    $_POST['showItemSpecificity'], $params);

      echo "<br><span id='showItemSpecificity_".$_POST["name"]."$rand'>&nbsp;</span>\n";
   }
}
