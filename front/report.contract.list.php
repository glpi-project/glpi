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

include ('../inc/includes.php');

Session::checkRight("reports", READ);

Html::header(Report::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "report");

Report::title();

$items = $CFG_GLPI["contract_types"];

// Titre
echo "<div class='center'>";
echo "<span class='big b'>".__('List of the hardware under contract')."</span><br><br>";
echo "</div>";
// Request All
if ((isset($_POST["item_type"][0]) && ($_POST["item_type"][0] == '0'))
    || !isset($_POST["item_type"])) {
   $_POST["item_type"] = $items;
}

if (isset($_POST["item_type"]) && is_array($_POST["item_type"])) {
   $query = [];
   $all_criteria = [];
   foreach ($_POST["item_type"] as $key => $val) {
      if (!in_array($val, $items)) {
         continue;
      }

      $itemtable = getTableForItemType($val);
      $criteria = [
         'SELECT' => [
            'glpi_contracttypes.name AS type',
            'glpi_contracts.duration',
            'glpi_entities.completename AS entname',
            'glpi_entities.id AS entID',
            'glpi_contracts.begin_date'
         ],
         'FROM'   => 'glpi_contracts_items',
         'INNER JOIN'   => [
            'glpi_contracts'  => [
               'ON'  => [
                  'glpi_contracts_items'  => 'contracts_id',
                  'glpi_contracts'        => 'id'
               ]
            ],
            $itemtable  => [
               'ON'  => [
                  $itemtable  => 'id',
                  'glpi_contracts_items'  => 'items_id', [
                     'AND' => [
                        'glpi_contracts_items.itemtype' => $val
                     ]
                  ]
               ]
            ]
         ],
         'LEFT JOIN'    => [
            'glpi_contracttypes' => [
               'ON'  => [
                  'glpi_contracts'     => 'contracttypes_id',
                  'glpi_contracttypes' => 'id'
               ]
            ],
            'glpi_entities'   => [
               'ON'  => [
                  $itemtable        => 'entities_id',
                  'glpi_entities'   => 'id'
               ]
            ]
         ],
         'WHERE'        => getEntitiesRestrictCriteria($itemtable),
         'ORDERBY'      => ["entname ASC", 'itemdeleted DESC', "itemname ASC"]
      ];

      if ($DB->fieldExists($itemtable, 'name')) {
         $criteria['SELECT'][] = "$itemtable.name AS itemname";
      } else {
         $criteria['SELECT'][] = new QueryExpression("'' AS ".$DB->quoteName('itemname'));
      }

      if (($val == 'Project')
            || ($val == 'SoftwareLicense')) {

         if ($val == 'SoftwareLicense') {
            $criteria['ORDERBY'] = ["entname ASC", "itemname ASC"];
            $criteria['SELECT'] = array_merge(
               $criteria['SELECT'],
               ['glpi_infocoms.buy_date', 'glpi_infocoms.warranty_duration']
            );
            $criteria['LEFT JOIN']['glpi_infocoms'] = [
               'ON'  => [
                  'glpi_infocoms' => 'items_id',
                  $itemtable     => 'id', [
                     'AND' => [
                        'glpi_infocoms.itemtype'   => $val
                     ]
                  ]
               ]
            ];
         }
         if ($val == 'Project') {
            $criteria['SELECT'][] = "$itemtable.is_deleted AS itemdeleted";
         }

         if (isset($_POST["year"][0]) && ($_POST["year"][0] != 0)) {
            $ors = [];
            foreach ($_POST["year"] as $val2) {
               $ors[] = new QueryExpression('YEAR('.$DB->quoteName('glpi_contracts.begin_date').') = '.$DB->quote($val2));
               if ($val == 'SoftwareLicense') {
                  $ors[] = new QueryExpression('YEAR('.$DB->quoteName('glpi_infocoms.buy_date').') = '.$DB->quote($val2));
               }
            }
            if (count($ors)) {
               $criteria['WHERE'][] = ['OR' => $ors];
            }
         }

      } else {
         $criteria['SELECT'] = array_merge($criteria['SELECT'], [
            "$itemtable.is_deleted AS itemdeleted",
            'glpi_infocoms.buy_date',
            'glpi_infocoms.warranty_duration'
         ]);
         $criteria['LEFT JOIN']['glpi_infocoms'] = [
            'ON'  => [
               $itemtable        => 'id',
               'glpi_infocoms'   => 'items_id', [
                  'AND' => [
                     'glpi_infocoms.itemtype' => $val
                  ]
               ]
            ]
         ];
         if ($DB->fieldExists($itemtable, 'locations_id')) {
            $criteria['SELECT'][] = 'glpi_locations.completename AS location';
            $criteria['LEFT JOIN']['glpi_locations'] = [
               'ON'  => [
                  $itemtable        => 'locations_id',
                  'glpi_locations'   => 'id'
               ]
            ];
         } else {
            $criteria['SELECT'][] = new QueryExpression("'' AS location");
         }

         if ($DB->fieldExists($itemtable, 'is_template')) {
            $criteria['WHERE'][] = ["$itemtable.is_template" => 0];
         }

         if (isset($_POST["year"][0]) && ($_POST["year"][0] != 0)) {
            foreach ($_POST["year"] as $val2) {
               $ors[] = new QueryExpression('YEAR('.$DB->quoteName('glpi_infocoms.buy_date').') = '.$DB->quoteValue($val2));
               $ors[] = new QueryExpression('YEAR('.$DB->quoteName('glpi_contracts.begin_date').') = '.$DB->quoteValue($val2));
            }
            if (count($ors)) {
               $criteria['WHERE'][] = ['OR' => $ors];
            }
         }
      }
      $all_criteria[$val] = $criteria;
   }
}

$display_entity = Session::isMultiEntitiesMode();

if (count($all_criteria)) {
   foreach ($all_criteria as $key => $criteria) {
      $iterator = $DB->request($criteria);
      if (count($iterator)) {
         $item = new $key();
         echo "<div class='center'><span class='b'>".$item->getTypeName(1)."</span></div>";
         echo "<table class='tab_cadrehov'>";
         echo "<tr><th>".__('Name')."</th>";
         echo "<th>".__('Deleted')."</th>";
         if ($display_entity) {
            echo "<th>".Entity::getTypeName(1)."</th>";
         }
         echo "<th>".Location::getTypeName(1)."</th>";
         echo "<th>".__('Date of purchase')."</th>";
         echo "<th>".__('Warranty expiration date')."</th>";
         echo "<th>".ContractType::getTypeName(1)."</th>";
         echo "<th>".__('Start date')."</th>";
         echo "<th>".__('End date')."</th>";
         echo "</tr>";
         while ($data = $iterator->next()) {
            echo "<tr class='tab_bg_1'>";
            if ($data['itemname']) {
               echo "<td> ".$data['itemname']." </td>";
            } else {
               echo "<td> ".NOT_AVAILABLE." </td>";
            }
            if (!isset($data['itemdeleted'])) {
               $data['itemdeleted'] = 0;
            }
            if (!isset($data['buy_date'])) {
               $data['buy_date'] = '';
            }
            if (!isset($data['warranty_duration'])) {
               $data['warranty_duration'] = 0;
            }

            echo "<td> ".Dropdown::getYesNo($data['itemdeleted'])." </td>";

            if ($display_entity) {
               echo "<td>".$data['entname']."</td>";
            }

            if ($data['location']) {
               echo "<td> ".$data['location']." </td>";
            } else {
               echo "<td> ".NOT_AVAILABLE." </td>";
            }

            if ($data['buy_date']) {
               echo "<td> ".Html::convDate($data['buy_date'])." </td>";
               if ($data["warranty_duration"]) {
                  echo "<td> ".Infocom::getWarrantyExpir($data["buy_date"],
                                                         $data["warranty_duration"])." </td>";
               } else {
                  echo "<td> ".NOT_AVAILABLE." </td>";
               }
            } else {
               echo "<td> ".NOT_AVAILABLE." </td><td> ".NOT_AVAILABLE." </td>";
            }

            if ($data['type']) {
               echo "<td class='b'> ".$data['type']." </td>";
            } else {
               echo "<td> ".NOT_AVAILABLE." </td>";
            }

            if ($data['begin_date']) {
               echo "<td> ".Html::convDate($data['begin_date'])." </td>";
               if ($data["duration"]) {
                  echo "<td> ".Infocom::getWarrantyExpir($data["begin_date"],
                                                         $data["duration"])." </td>";
               } else {
                  echo "<td> ".NOT_AVAILABLE." </td>";
               }
            } else {
               echo "<td> ".NOT_AVAILABLE." </td><td> ".NOT_AVAILABLE." </td>";
            }
            echo "</tr>\n";
         }
         echo "</table><br><hr><br>";
      }
   }
}

Html::footer();
