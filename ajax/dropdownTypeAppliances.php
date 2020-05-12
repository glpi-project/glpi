<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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


if (strpos($_SERVER['PHP_SELF'], "dropdownTypeAppliances.php")) {
   include '../inc/includes.php';
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkCentralAccess();

// Make a select box
if (isset($_POST["appliancetype"])) {
   $used = [];

   // Clean used array
   if (isset($_POST['used']) && is_array($_POST['used']) && (count($_POST['used']) > 0)) {
      global $DB;
      $iterator = $DB->request([
        'SELECT' => 'id',
        'FROM'   => 'glpi_appliances',
        'WHERE'  => [
            'id'                 => $_POST['used'],
            'appliancetypes_id'  => $_POST["appliancetype"]]
      ]);
      while ($row = $iterator->next()) {
         $used[$row['id']] = $row['id'];
      }
   }

   Dropdown::show(
      'Appliance', [
         'name'      => $_POST['myname'],
         'used'      => $used,
         'width'     => '50%',
         'entity'    => $_POST['entity'],
         'rand'      => $_POST['rand'],
         'condition' => ['glpi_appliances.appliancetypes_id' => $_POST["appliancetype"]]
      ]
   );
}
