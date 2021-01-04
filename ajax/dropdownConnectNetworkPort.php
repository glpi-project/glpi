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

/**
 * @since 0.84
 */

$AJAX_INCLUDE = 1;

include ('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight("networking", UPDATE);

// Make a select box
if (class_exists($_POST["itemtype"])
    && isset($_POST["item"])) {
   $table = getTableForItemType($_POST["itemtype"]);

   $joins = [];
   $name_field = new QueryExpression("'' AS " . $DB->quoteName('npname'));

   if ($_POST['instantiation_type'] == 'NetworkPortEthernet') {
      $name_field = 'glpi_netpoints.name AS npname';
      $joins = [
         'glpi_networkportethernets'   => [
            'ON'  => [
               'glpi_networkportethernets'   => 'id',
               'glpi_networkports'           => 'id'
            ]
         ],
         'glpi_netpoints'              => [
            'ON'  => [
               'glpi_networkportethernets'   => 'netpoints_id',
               'glpi_netpoints'              => 'id'
            ]
         ]
      ];
   }

   $criteria = [
      'SELECT'    => [
         'glpi_networkports_networkports.id AS wid',
         'glpi_networkports.id AS did',
         "$table.name AS cname",
         'glpi_networkports.name AS nname',
         $name_field
      ],
      'DISTINCT'  => true,
      'FROM'      => $table,
      'LEFT JOIN' => [
         'glpi_networkports'  => [
            'ON'  => [
               'glpi_networkports'  => 'items_id',
               $table               => 'id', [
                  'AND' => [
                     'glpi_networkports.items_id'           => $_POST['item'],
                     'glpi_networkports.itemtype'           => $_POST["itemtype"],
                     'glpi_networkports.instantiation_type' => $_POST['instantiation_type']
                  ]
               ]
            ]
         ],
         'glpi_networkports_networkports' => [
            'ON'  => [
               'glpi_networkports_networkports' => 'networkports_id_1',
               'glpi_networkports'              => 'id', [
                  'OR'  => [
                     'glpi_networkports_networkports.networkports_id_2' => new QueryExpression($DB->quoteName('glpi_networkports.id'))
                  ]
               ]
            ]
         ]
      ] + $joins,
      'WHERE'     => [
         'glpi_networkports_networkports.id' => null,
         'NOT'                               => ['glpi_networkports.id' => null],
         'glpi_networkports.id'              => ['<>', $_POST['networkports_id']],
         "$table.is_deleted"                 => 0,
         "$table.is_template"                => 0
      ],
      'ORDERBY'   => 'glpi_networkports.id'
   ];
   $iterator = $DB->request($criteria);

   echo "<br>";

   $values = [];
   while ($data = $iterator->next()) {
      // Device name + port name
      $output = $output_long = $data['cname'];

      if (!empty($data['nname'])) {
         $output      = sprintf(__('%1$s - %2$s'), $output, $data['nname']);
         //TRANS: %1$s is device name, %2$s is port name
         $output_long = sprintf(__('%1$s - The port %2$s'), $output_long, $data['nname']);
      }

      // display netpoint (which will be copied)
      if (!empty($data['npname'])) {
         $output      = sprintf(__('%1$s - %2$s'), $output, $data['npname']);
         //TRANS: %1$s is a string (device name - port name...), %2$s is network outlet name
         $output_long = sprintf(__('%1$s - Network outlet %2$s'), $output_long, $data['npname']);
      }
      $ID = $data['did'];

      if ($_SESSION["glpiis_ids_visible"] || empty($output) || empty($output_long)) {
         $output      = sprintf(__('%1$s (%2$s)'), $output, $ID);
         $output_long = sprintf(__('%1$s (%2$s)'), $output_long, $ID);
      }
      $values[$ID] = $output_long;
   }
   Dropdown::showFromArray($_POST['myname'], $values, ['display_emptychoice' => true]);
}
