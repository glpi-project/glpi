<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

if (strpos($_SERVER['PHP_SELF'], "dropdownInstallVersion.php")) {
   $AJAX_INCLUDE = 1;
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkRight("software", UPDATE);

if ($_POST['softwares_id'] > 0) {
   if (!isset($_POST['value'])) {
      $_POST['value'] = 0;
   }

   $where = [];
   if (isset($_POST['used'])) {
      $used = $_POST['used'];
      if (count($used)) {
         $where = ['NOT' => ['glpi_softwareversions.id' => $used]];
      }
   }
   // Make a select box
   $iterator = $DB->request([
      'SELECT'    => ['glpi_softwareversions.*', 'glpi_states.name AS sname'],
      'DISTINCT'  => true,
      'FROM'      => 'glpi_softwareversions',
      'LEFT JOIN' => [
         'glpi_states'  => [
            'ON'  => [
               'glpi_softwareversions' => 'states_id',
               'glpi_states'           => 'id'
            ]
         ]
      ],
      'WHERE'     => ['glpi_softwareversions.softwares_id' => $_POST['softwares_id']] + $where
   ]);
   $number = count($iterator);

   $values = [];
   while ($data = $iterator->next()) {
      $ID = $data['id'];
      $output = $data['name'];

      if (empty($output) || $_SESSION['glpiis_ids_visible']) {
         $output = sprintf(__('%1$s (%2$s)'), $output, $ID);
      }
      if (!empty($data['sname'])) {
         $output = sprintf(__('%1$s - %2$s'), $output, $data['sname']);
      }
      $values[$ID] = $output;

   }

   Dropdown::showFromArray($_POST['myname'], $values, ['display_emptychoice' => true]);
}
