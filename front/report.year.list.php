<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

include("../inc/includes.php");

Session::checkRight("reports", READ);

Html::header(Report::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "report");

Report::title();

$items = $CFG_GLPI["report_types"];

// Titre
echo "<div class='center b spaced'><big>" . __('Device list') . "</big></div>";

// Request All
if (
    (isset($_POST["item_type"][0]) && ($_POST["item_type"][0] == 0))
    || !isset($_POST["item_type"])
) {
    $_POST["item_type"] = $items;
}

if (isset($_POST["item_type"]) && is_array($_POST["item_type"])) {
    $all_criteria = [];
    foreach ($_POST["item_type"] as $key => $val) {
        if (in_array($val, $items)) {
            $itemtable = getTableForItemType($val);

            $deleted_field       = "$itemtable.is_deleted";
            $location_field      = null;
            $add_leftjoin        = "";
            $template_condition  = '1';

            $criteria = [
                'SELECT'    => [
                    "$itemtable.name AS itemname",
                    'glpi_contracttypes.name AS type',
                    'glpi_infocoms.buy_date',
                    'glpi_infocoms.warranty_duration',
                    'glpi_contracts.begin_date',
                    'glpi_contracts.duration',
                    'glpi_entities.completename AS entname',
                    'glpi_entities.id AS entID'
                ],
                'FROM'      => $itemtable,
                'LEFT JOIN' => [],
                'WHERE'     => [],
                'ORDERBY'   => ['entname ASC', 'itemdeleted DESC', 'itemname ASC']
            ];

            if ($val != 'Project') {
                $location_field      = "glpi_locations.completename";
                $criteria['LEFT JOIN']['glpi_locations'] = [
                    'ON'  => [
                        $itemtable  => 'locations_id',
                        'glpi_locations.id'
                    ]
                ];
                $criteria['WHERE']["$itemtable.is_template"] = 0;
            }
            if ($val == 'SoftwareLicense') {
                $deleted_field       = "glpi_softwares.is_deleted";
                $location_field      = null;
                $criteria['LEFT JOIN']['glpi_softwares'] = [
                    'ON'  => [
                        'glpi_softwares'        => 'id',
                        'glpi_softwarelicenses' => 'softwares_id'
                    ]
                ];
                $criteria['WHERE']['glpi_softwares.is_template'] = 0;
            }
            $criteria['SELECT'][] = "$deleted_field AS itemdeleted";
            $criteria['SELECT'][] = ($location_field !== null ?
            "$location_field AS location" :
            new QueryExpression("'' AS " . $DB->quoteName('location')));

            $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + [
                'glpi_contracts_items'  => [
                    'ON'  => [
                        $itemtable              => 'id',
                        'glpi_contracts_items'  => 'items_id', [
                            'AND' => [
                                'glpi_contracts_items.itemtype' => $val
                            ]
                        ]
                    ]
                ],
                'glpi_contracts'        => [
                    'ON'  => [
                        'glpi_contracts_items'  => 'contracts_id',
                        'glpi_contracts'        => 'id', [
                            'AND' => [
                                'NOT' => ['glpi_contracts_items.contracts_id' => null]
                            ]
                        ]
                    ]
                ],
                'glpi_infocoms'         => [
                    'ON'  => [
                        $itemtable        => 'id',
                        'glpi_infocoms'   => 'items_id', [
                            'AND' => [
                                'glpi_infocoms.itemtype' => $val
                            ]
                        ]
                    ]
                ],
                'glpi_contracttypes'    => [
                    'ON'  => [
                        'glpi_contracts'     => 'contracttypes_id',
                        'glpi_contracttypes' => 'id'
                    ]
                ],
                'glpi_entities'         => [
                    'ON'  => [
                        $itemtable        => 'entities_id',
                        'glpi_entities'   => 'id'
                    ]
                ]
            ];
            $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria($itemtable);

            if (isset($_POST["year"][0]) && ($_POST["year"][0] != 0)) {
                $ors = [];
                foreach ($_POST["year"] as $val2) {
                    $ors[] = new QueryExpression("YEAR(" . $DB->quoteName('glpi_infocoms.buy_date') . ") = " . $DB->quote($val2));
                    $ors[] = new QueryExpression("YEAR(" . $DB->quoteName('glpi_contracts.begin_date') . ") = " . $DB->quote($val2));
                }
                if (count($ors)) {
                    $criteria['WHERE'][] = [
                        'OR'  => $ors
                    ];
                }
            }
        }
        $all_criteria[$val] = $criteria;
    }
}
$display_entity = Session::isMultiEntitiesMode();

if (count($all_criteria)) {
    foreach ($all_criteria as $key => $val) {
        $iterator = $DB->request($val);
        if (count($iterator)) {
            $item = new $key();
            echo "<div class='center b'>" . $item->getTypeName(1) . "</div>";
            echo "<table class='tab_cadre_fixehov'>";
            echo "<tr><th>" . __('Name') . "</th>";
            echo "<th>" . __('Deleted') . "</th>";
            if ($display_entity) {
                echo "<th>" . Entity::getTypeName(1) . "</th>";
            }
            echo "<th>" . Location::getTypeName(1) . "</th>";
            echo "<th>" . __('Date of purchase') . "</th>";
            echo "<th>" . __('Warranty expiration date') . "</th>";
            echo "<th>" . ContractType::getTypeName(1) . "</th>";
            echo "<th>" . __('Start date') . "</th>";
            echo "<th>" . __('End date') . "</th></tr>";

            foreach ($iterator as $data) {
                echo "<tr class='tab_bg_1'>";
                if ($data['itemname']) {
                    echo "<td> " . $data['itemname'] . "</td>";
                } else {
                    echo "<td>" . NOT_AVAILABLE . "</td>";
                }
                echo "<td class='center'>" . Dropdown::getYesNo($data['itemdeleted']) . "</td>";

                if ($display_entity) {
                    echo "<td>" . $data['entname'] . "</td>";
                }

                if ($data['location']) {
                    echo "<td>" . $data['location'] . "</td>";
                } else {
                    echo "<td>" . NOT_AVAILABLE . "</td>";
                }

                if ($data['buy_date']) {
                    echo "<td class='center'>" . Html::convDate($data['buy_date']) . "</td>";
                    if ($data["warranty_duration"]) {
                        echo "<td class='center'>" . Infocom::getWarrantyExpir(
                            $data["buy_date"],
                            $data["warranty_duration"]
                        ) .
                         "</td>";
                    } else {
                        echo "<td class='center'>" . NOT_AVAILABLE . "</td>";
                    }
                } else {
                    echo "<td class='center'>" . NOT_AVAILABLE . "</td>";
                    echo "<td class='center'>" . NOT_AVAILABLE . "</td>";
                }

                if ($data['type']) {
                    echo "<td>" . $data['type'] . "</td>";
                } else {
                    echo "<td>" . NOT_AVAILABLE . "</td>";
                }

                if ($data['begin_date']) {
                    echo "<td class='center'>" . Html::convDate($data['begin_date']) . "</td>";
                    if ($data["duration"]) {
                        echo "<td class='center'>" . Infocom::getWarrantyExpir(
                            $data["begin_date"],
                            $data["duration"]
                        ) . "</td>";
                    } else {
                        echo "<td class='center'>" . NOT_AVAILABLE . "</td>";
                    }
                } else {
                    echo "<td class='center'>" . NOT_AVAILABLE . "</td>";
                    echo "<td class='center'>" . NOT_AVAILABLE . "</td>";
                }

                echo "</tr>\n";
            }
            echo "</table><br><hr><br>";
        }
    }
}

Html::footer();
