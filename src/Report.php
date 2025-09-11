<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\Dashboard\Widget;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\Socket;

use function Safe\mktime;

/**
 *  Report class
 *
 * @since version 0.84
 *
 * @phpstan-type ReportData array{title: string, report_id: string, report_type: string, params: array, data: array}
 **/
class Report extends CommonGLPI
{
    protected static $notable = false;
    public static $rightname         = 'reports';

    public static function getTypeName($nb = 0)
    {
        return _n('Report', 'Reports', $nb);
    }

    public static function getMenuShorcut()
    {
        return 'e';
    }

    public static function getReports(): array
    {
        global $CFG_GLPI, $PLUGIN_HOOKS;

        // Report generation
        // Default Report included
        $report_list = [];
        $report_list["default"]["name"] = __('Default report');
        $report_list["default"]["file"] = "/front/report.default.php";

        if (Contract::canView()) {
            $report_list["Contrats"]["name"] = __('By contract');
            $report_list["Contrats"]["file"] = "/front/report.contract.php";
        }
        if (Infocom::canView()) {
            $report_list["Par_annee"]["name"] = __('By year');
            $report_list["Par_annee"]["file"] = "/front/report.year.php";
            $report_list["Infocoms"]["name"]  = __('Hardware financial and administrative information');
            $report_list["Infocoms"]["file"]  = "/front/report.infocom.php";
            $report_list["Infocoms2"]["name"] = __('Other financial and administrative information (licenses, cartridges, consumables)');
            $report_list["Infocoms2"]["file"] = "/front/report.infocom.conso.php";
        }
        if (Session::haveRight("networking", READ)) {
            // Network socket report
            $report_list["Rapport prises reseau"]["name"] = __('Network report');
            $report_list["Rapport prises reseau"]["file"] = "/front/report.networking.php";
        }
        if (Session::haveRight("reservation", READ)) {
            $report_list["reservation"]["name"] = __('Loan');
            $report_list["reservation"]["file"] = "/front/report.reservation.php";
        }
        //TODO This should probably check all state_types
        if (
            Computer::canView()
            || Monitor::canView()
            || Session::haveRight("networking", READ)
            || Peripheral::canView()
            || Printer::canView()
            || Phone::canView()
        ) {
            $report_list["state"]["name"] = __('Status');
            $report_list["state"]["file"] = "/front/report.state.php";
        }

        // Handle reports from plugins
        if (isset($PLUGIN_HOOKS["reports"]) && is_array($PLUGIN_HOOKS["reports"])) {
            foreach ($PLUGIN_HOOKS["reports"] as $plug => $pages) {
                if (!Plugin::isPluginActive($plug)) {
                    continue;
                }
                if (is_array($pages) && count($pages)) {
                    foreach ($pages as $page => $name) {
                        $report_list[Plugin::getInfo($plug, 'name')][$page] = [
                            'name' => $name,
                            'file' => "{$CFG_GLPI['root_doc']}/plugins/{$plug}/{$page}",
                            'plug' => $plug,
                        ];
                    }
                }
            }
        }

        return $report_list;
    }

    /**
     * Show report selector which always appears on the top of the report pages
     **/
    public static function title()
    {
        $twig_params = [
            'title' => __('Select the report you want to generate'),
            'selected' => -1,
            'values'   => ['/front/report.php' => Dropdown::EMPTY_VALUE],
        ];

        $report_list = self::getReports();

        $fn_find_selected = static function (array $report_list) use (&$fn_find_selected): ?string {
            foreach ($report_list as $data) {
                if (!array_key_exists('file', $data)) {
                    // This is a group
                    if ($file = $fn_find_selected($data)) {
                        return $file;
                    }
                } elseif (stripos($_SERVER['REQUEST_URI'], (string) $data['file']) !== false) {
                    return $data['file'];
                }
            }
            return null;
        };
        $twig_params['selected'] = $fn_find_selected($report_list) ?? $twig_params['selected'];

        // Format reports for the dropdown so that they are grouped by plugin and the keys are the file paths
        $fn_get_dropdown_values = static function (array $report_list) use (&$fn_get_dropdown_values): array {
            $values = [];
            foreach ($report_list as $val => $data) {
                if (!array_key_exists('file', $data)) {
                    // This is a group
                    $values[$val] = $fn_get_dropdown_values($data);
                } else {
                    $values[$data['file']] = $data['name'];
                }
            }
            return $values;
        };
        $twig_params['values'] = array_merge($twig_params['values'], $fn_get_dropdown_values($report_list));

        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            <div class="card mb-3">
                <div class="card-header">
                    <div class="card-title">{{ title }}</div>
                </div>
                <div class="card-body">
                    {{ fields.dropdownArrayField('statmenu', selected, values, null, {
                        no_label: true,
                        on_change: "window.location=this.options[this.selectedIndex].value"
                    }) }}
                </div>
            </div>
TWIG, $twig_params);
    }

    /**
     * @return array<class-string<CommonDBTM>, array<string, mixed>>
     */
    private static function getAssetCounts(): array
    {
        global $CFG_GLPI, $DB;

        $items = $CFG_GLPI["asset_types"];
        $linkitems = $CFG_GLPI['directconnect_types'];
        $result = [];

        foreach ($items as $itemtype) {
            $table_item = getTableForItemType($itemtype);
            $criteria = [
                'COUNT'  => 'cpt',
                'FROM'   => $table_item,
                'WHERE'  => [
                    "$table_item.is_deleted"   => 0,
                ] + getEntitiesRestrictCriteria($table_item) + $itemtype::getSystemSQLCriteria(),
            ];

            $itemtype_object = getItemForItemtype($itemtype);
            if ($itemtype_object->maybeTemplate()) {
                $criteria["WHERE"]["$table_item.is_template"] = 0;
            }

            if (in_array($itemtype, $linkitems, true)) {
                $relation_table = Asset_PeripheralAsset::getTable();
                $criteria['LEFT JOIN'] = [
                    $relation_table => [
                        'ON' => [
                            $relation_table => 'items_id_peripheral',
                            $table_item     => 'id', [
                                'AND' => [
                                    $relation_table . '.itemtype_peripheral' => $itemtype,
                                ],
                            ],
                        ],
                    ],
                ];
            }

            $result[$itemtype] = [
                'label' => $itemtype::getTypeName(Session::getPluralNumber()),
                'count' => (int) $DB->request($criteria)->current()['cpt'],
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function getOSInstallCounts(): array
    {
        global $DB;

        $result = [];
        $iterator = $DB->request([
            'SELECT'    => [
                'COUNT' => '* AS count',
                'glpi_operatingsystems.id AS id',
                'glpi_operatingsystems.name AS name',
            ],
            'FROM'      => 'glpi_items_operatingsystems',
            'LEFT JOIN' => [
                'glpi_operatingsystems' => [
                    'ON' => [
                        'glpi_items_operatingsystems' => 'operatingsystems_id',
                        'glpi_operatingsystems'       => 'id',
                    ],
                ],
            ],
            'WHERE'     => ['is_deleted' => 0] + getEntitiesRestrictCriteria('glpi_items_operatingsystems'),
            'GROUPBY'   => 'glpi_operatingsystems.name',
        ]);

        foreach ($iterator as $data) {
            if (empty($data['name'])) {
                $data['name'] = Dropdown::EMPTY_VALUE;
            }
            $result[$data['id']] = [
                'label' => $data['name'],
                'count' => (int) $data['count'],
            ];
        }

        return $result;
    }

    private static function getAssetTypeCounts(): array
    {
        global $CFG_GLPI, $DB;

        $items = $CFG_GLPI["asset_types"];
        $linkitems = $CFG_GLPI['directconnect_types'];
        $result = [];

        $val   = array_flip($items);
        $items = array_flip($val);

        foreach ($items as $itemtype) {
            $item = getItemForItemtype($itemtype);
            if (!$item instanceof CommonDBTM) {
                throw new RuntimeException("Invalid asset type: " . $itemtype);
            }

            $typeclass = $itemtype . "Type";

            if (!class_exists($typeclass)) {
                continue;
            }

            $table_item = getTableForItemType($itemtype);
            $type_table = getTableForItemType($typeclass);
            $typefield  = getForeignKeyFieldForTable(getTableForItemType($typeclass));

            $criteria = [
                'SELECT'    => [
                    'COUNT'  => '* AS count',
                    "$table_item.id AS id",
                    "$type_table.name AS name",
                ],
                'FROM'      => $table_item,
                'LEFT JOIN' => [
                    $type_table => [
                        'ON' => [
                            $table_item => $typefield,
                            $type_table => 'id',
                        ],
                    ],
                ],
                'WHERE'     => [
                    "$table_item.is_deleted"   => 0,
                ] + getEntitiesRestrictCriteria($table_item) + $item::getSystemSQLCriteria($table_item),
                'GROUPBY'   => "$type_table.name",
            ];

            $itemtype_object = getItemForItemtype($itemtype);
            if ($itemtype_object->maybeTemplate()) {
                $criteria["WHERE"]["$table_item.is_template"] = 0;
            }

            if (in_array($itemtype, $linkitems, true)) {
                $relation_table = Asset_PeripheralAsset::getTable();
                $criteria['LEFT JOIN'][$relation_table] = [
                    'ON' => [
                        $relation_table => 'items_id_peripheral',
                        $table_item     => 'id', [
                            'AND' => [
                                $relation_table . '.itemtype_peripheral' => $itemtype,
                            ],
                        ],
                    ],
                ];
            }

            $iterator = $DB->request($criteria);
            foreach ($iterator as $data) {
                if (empty($data['name'])) {
                    $data['name'] = Dropdown::EMPTY_VALUE;
                }
                if (!array_key_exists($itemtype, $result)) {
                    $result[$itemtype] = [
                        'label' => $item::getTypeName(Session::getPluralNumber()),
                        'items' => [],
                    ];
                }
                $result[$itemtype]['items'][$data['id']] = [
                    'label' => $data['name'],
                    'count' => (int) $data['count'],
                ];
            }
        }

        return $result;
    }

    /**
     * Get the data for the default report
     * @return array
     * @phpstan-return ReportData
     */
    private static function getDefaultReport(): array
    {
        $report = [
            'title' => __('Default report'),
            'report_id' => 'default',
            'report_type' => 'count',
            'params' => [], // No user-defined parameters for this report
            'data' => [
                'items' => [],
            ],
        ];

        $report['data']['items']['assets'] = [
            'label' => _n('Asset', 'Assets', Session::getPluralNumber()),
            'items' => self::getAssetCounts(),
        ];
        $report['data']['items']['os'] = [
            'label' => OperatingSystem::getTypeName(Session::getPluralNumber()),
            'items' => self::getOSInstallCounts(),
        ];
        $report['data']['items']['types'] = [
            'label' => _n('Type', 'Types', Session::getPluralNumber()),
            'items' => self::getAssetTypeCounts(),
        ];

        return $report;
    }

    /**
     * @param array $report
     * @phpstan-param ReportData $report
     * @return void
     */
    private static function showCountReport(array $report): void
    {
        // The count report data is nested into groups, but this type of report is currently rendered as a flat table
        // We need to flatten the data structure to render it correctly
        $counts = [];
        $fn_flatten = static function (array $data, $label_carry = '') use (&$fn_flatten, &$counts) {
            // When there is a 'count' key, we are at the leaf of the data structure
            // Branches should have an 'items' key
            // A leaf's label is the concatenation of the labels of all its ancestors and its own label
            $current_label = $label_carry;
            if (!empty($current_label)) {
                $current_label .= ' > ' . $data['label'];
            } else {
                $current_label = $data['label'] ?? '';
            }
            if (array_key_exists('count', $data)) {
                $counts[$current_label] = $data['count'];
            } else {
                foreach ($data['items'] as $item) {
                    $fn_flatten($item, $current_label);
                }
            }
        };
        $fn_flatten($report['data']);

        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <div class="card">
                <div class="card-header">
                    <div class="card-title">{{ title }}</div>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        {% for label, count in counts %}
                            <tr>
                                <th>{{ label }}</th>
                                <td>{{ count }}</td>
                            </tr>
                        {% endfor %}
                    </table>
                </div>
            </div>
TWIG, ['title' => $report['title'], 'counts' => $counts]);
    }

    /**
     * Show Default Report
     *
     * @since 0.84
     **/
    public static function showDefaultReport(): void
    {
        self::showCountReport(self::getDefaultReport());
    }

    public static function showNetworkReportCriteria(bool $embeded): void
    {
        TemplateRenderer::getInstance()->display(
            'pages/tools/report/network_criteria.html.twig',
            [
                'embeded' => $embeded,
            ]
        );
    }

    private static function getNetworkCommonCriteria(): array
    {
        global $DB;
        // This SQL request matches the NetworkPort, then its NetworkName and IPAddreses. It also
        //      match opposite NetworkPort, then its NetworkName and IPAddresses.
        // Results are groupes by NetworkPort. Then all IPs are concatenated by comma as separator.
        return [
            'SELECT'       => [
                'PORT_1.itemtype AS itemtype_1',
                'PORT_1.items_id AS items_id_1',
                'PORT_1.id AS id_1',
                'PORT_1.name AS port_1',
                'PORT_1.mac AS mac_1',
                'PORT_1.logical_number AS logical_1',
                QueryFunction::groupConcat(
                    expression: 'ADDR_1.name',
                    separator: ', ',
                    alias: 'ip_1'
                ),
                'PORT_2.itemtype AS itemtype_2',
                'PORT_2.items_id AS items_id_2',
                'PORT_2.id AS id_2',
                'PORT_2.name AS port_2',
                'PORT_2.mac AS mac_2',
                QueryFunction::groupConcat(
                    expression: 'ADDR_2.name',
                    separator: ', ',
                    alias: 'ip_2'
                ),
            ],
            'INNER JOIN'   => [],
            'LEFT JOIN'    => [
                'glpi_networknames AS NAME_1' => [
                    'ON'  => [
                        'PORT_1' => 'id',
                        'NAME_1' => 'items_id', [
                            'AND'    => [
                                'NAME_1.itemtype'    => 'NetworkPort',
                                'NAME_1.is_deleted'  => 0,
                            ],
                        ],
                    ],
                ],
                'glpi_ipaddresses AS ADDR_1'  => [
                    'ON'  => [
                        'NAME_1' => 'id',
                        'ADDR_1' => 'items_id', [
                            'AND'    => [
                                'ADDR_1.itemtype'    => 'NetworkName',
                                'ADDR_1.is_deleted'  => 0,
                            ],
                        ],
                    ],
                ],
                'glpi_networkports_networkports AS LINK'  => [
                    'ON'  => [
                        'LINK'   => 'networkports_id_1',
                        'PORT_1' => 'id', [
                            'OR'     => [
                                'LINK.networkports_id_2'   => new QueryExpression($DB::quoteName('PORT_1.id')),
                            ],
                        ],
                    ],
                ],
                'glpi_networkports AS PORT_2' => [
                    'ON'  => [
                        'PORT_2' => 'id',
                        QueryFunction::if(
                            condition: new QueryExpression($DB::quoteName("LINK.networkports_id_1") . ' = ' . $DB::quoteName("PORT_1.id")),
                            true_expression: "LINK.networkports_id_2",
                            false_expression: "LINK.networkports_id_1"
                        ),
                    ],
                ],
                'glpi_networknames AS NAME_2' => [
                    'ON'  => [
                        'PORT_2' => 'id',
                        'NAME_2' => 'items_id', [
                            'AND'    => [
                                'NAME_2.itemtype'     => 'NetworkPort',
                                'NAME_2.is_deleted'   => 0,
                            ],
                        ],
                    ],
                ],
                'glpi_ipaddresses AS ADDR_2'  => [
                    'ON'  => [
                        'NAME_2' => 'id',
                        'ADDR_2' => 'items_id', [
                            'AND'    => [
                                'ADDR_2.itemtype'    => 'NetworkName',
                                'ADDR_2.is_deleted'  => 0,
                            ],
                        ],
                    ],
                ],
            ],
            'GROUPBY'      => ['PORT_1.id'],
        ];
    }

    /**
     * @param int $locations_id The ID of the location or 0 for all locations
     * @return array
     */
    private static function getNetworkLocationCriteria(int $locations_id): array
    {
        $criteria = self::getNetworkCommonCriteria();
        if ($locations_id > 0) {
            $sons = getSonsOf('glpi_locations', $locations_id);
            $criteria['WHERE'] = ['glpi_locations.id' => $sons];
        }
        $criteria['SELECT'] = array_merge($criteria['SELECT'], ['glpi_locations.completename AS extra']);
        $criteria['FROM'] = 'glpi_locations';
        $criteria['INNER JOIN']['glpi_sockets'] = [
            'ON' => [
                'glpi_sockets' => 'locations_id',
                'glpi_locations' => 'id',
            ],
        ];
        $criteria['INNER JOIN']['glpi_networkportethernets'] = [
            'ON' => [
                'glpi_networkportethernets' => 'networkports_id',
                'glpi_sockets' => 'networkports_id',
            ],
        ];
        $criteria['INNER JOIN']['glpi_networkports AS PORT_1'] = [
            'ON' => [
                'PORT_1' => 'id',
                'glpi_networkportethernets' => 'networkports_id',
            ],
        ];
        $criteria['ORDER'] = ['glpi_locations.completename', 'PORT_1.name'];

        return $criteria;
    }

    /**
     * @param positive-int $sockets_id The socket ID
     * @return array
     */
    private static function getNetworkSocketCriteria(int $sockets_id): array
    {
        $criteria = self::getNetworkCommonCriteria();
        $criteria['SELECT'] = array_merge($criteria['SELECT'], ['glpi_sockets.name AS extra']);
        $criteria['FROM'] = 'glpi_sockets';
        $criteria['INNER JOIN']['glpi_networkportethernets'] = [
            'ON' => [
                'glpi_networkportethernets' => 'networkports_id',
                'glpi_sockets' => 'networkports_id',
            ],
        ];
        $criteria['INNER JOIN']['glpi_networkports AS PORT_1'] = [
            'ON' => [
                'PORT_1' => 'id',
                'glpi_networkportethernets' => 'networkports_id',
            ],
        ];
        $criteria['LEFT JOIN']['glpi_locations'] = [
            'ON' => [
                'glpi_locations' => 'id',
                'glpi_sockets' => 'locations_id',
            ],
        ];
        $criteria['WHERE'] = ['glpi_sockets.id' => $sockets_id];
        return $criteria;
    }

    /**
     * @param positive-int $networkequipments_id The network equipment ID
     * @return array
     */
    private static function getNetworkEquipmentCriteria(int $networkequipments_id): array
    {
        $criteria = self::getNetworkCommonCriteria();
        $criteria['FROM'] = 'glpi_networkequipments AS ITEM';
        $criteria['INNER JOIN']['glpi_networkports AS PORT_1'] = [
            'ON' => [
                'PORT_1' => 'items_id',
                'ITEM' => 'id', [
                    'AND' => [
                        'PORT_1.itemtype' => 'NetworkEquipment',
                    ],
                ],
            ],
        ];
        $criteria['WHERE'] = ['ITEM.id' => $networkequipments_id];

        return $criteria;
    }

    /**
     * @param class-string<Location|NetworkEquipment|Socket> $by_itemtype
     * @param int $by_items_id
     * @return array
     * @phpstan-return ReportData
     */
    private static function getNetworkReport(string $by_itemtype, int $by_items_id): array
    {
        global $DB;

        $title = sprintf(match ($by_itemtype) {
            Location::class         => __('Network report by location: %s'),
            NetworkEquipment::class => __('Network report by hardware: %s'),
            Socket::class           => __('Network report by outlet: %s'),
            default                 => throw new InvalidArgumentException(),
        }, Dropdown::getDropdownName($by_itemtype::getTable(), $by_items_id));

        $criteria = match ($by_itemtype) {
            Location::class         => self::getNetworkLocationCriteria($by_items_id),
            NetworkEquipment::class => self::getNetworkEquipmentCriteria($by_items_id),
            Socket::class           => self::getNetworkSocketCriteria($by_items_id),
            default                 => throw new InvalidArgumentException(),
        };

        $report = [
            'title' => $title,
            'report_id' => 'network',
            'report_type' => 'network',
            'params' => [
                'by_itemtype' => $by_itemtype,
                'by_items_id' => $by_items_id,
            ],
            'data' => [
                'items' => [],
            ],
        ];

        $iterator = $DB->request($criteria);

        foreach ($iterator as $line) {
            $report['data']['items'][] = $line;
        }

        return $report;
    }

    /**
     * Show network report
     *
     * @param 'Location'|'NetworkEquioment'|'Glpi\Socket'|null $by_itemtype
     * @param int $by_items_id
     *
     * @return void
     *
     * @since 10.0.0
     **/
    public static function showNetworkReport(?string $by_itemtype, int $by_items_id): void
    {
        if ($by_itemtype === null) {
            self::showNetworkReportCriteria(false);
            return;
        }
        $report = self::getNetworkReport($by_itemtype, $by_items_id);
        $columns = [
            'extra' => $by_itemtype::getTypeName(1),
            'device_type_1' => _n('Device type', 'Device types', 1),
            'device_name_1' => __('Device name'),
            'port_number_1' => __('Port Number'),
            'port_1' => NetworkPort::getTypeName(1),
            'mac_1' => __('MAC address'),
            'ip_1' => IPAddress::getTypeName(),
            'device_type_2' => _n('Device type', 'Device types', 1),
            'device_name_2' => __('Device name'),
            'port_2' => NetworkPort::getTypeName(1),
            'mac_2' => __('MAC address'),
            'ip_2' => IPAddress::getTypeName(),
        ];
        $has_extra = false;
        $formatters = [];
        $entries = [];

        foreach ($report['data']['items'] as $line) {
            $entry = [];
            // To ensure that the NetworkEquipment remain the first item, we test its type
            if ($line['itemtype_2'] === NetworkEquipment::class) {
                $idx = 2;
            } else {
                $idx = 1;
            }

            if (array_key_exists('extra', $line)) {
                $has_extra = true;
                $entry['extra'] = $line['extra'] ?? NOT_AVAILABLE;
            }

            $itemtype = $line["itemtype_$idx"];
            if (!empty($itemtype)) {
                $entry["device_type_$idx"] = $itemtype::getTypeName(1);
                $item_name = '';
                if ($item = getItemForItemtype($itemtype)) {
                    if ($item->getFromDB($line["items_id_$idx"])) {
                        $item_name = $item->getName();
                    }
                }
                $entry["device_name_$idx"] = $item_name ?: NOT_AVAILABLE;
            } else {
                $entry["device_type_$idx"] = NOT_AVAILABLE;
                $entry["device_name_$idx"] = NOT_AVAILABLE;
            }
            $entry["port_number_$idx"] = $line["logical_$idx"] === '' ? NOT_AVAILABLE : $line["logical_$idx"];
            $entry["port_$idx"] = $line["port_$idx"] ?: NOT_AVAILABLE;
            $entry["mac_$idx"] = $line["mac_$idx"] ?: NOT_AVAILABLE;
            $entry["ip_$idx"] = $line["ip_$idx"] ?: NOT_AVAILABLE;

            if ($idx === 1) {
                $idx = 2;
            } else {
                $idx = 1;
            }

            $entry["port_$idx"] = $line["port_$idx"] ?: NOT_AVAILABLE;
            $entry["mac_$idx"] = $line["mac_$idx"] ?: NOT_AVAILABLE;
            $entry["ip_$idx"] = $line["ip_$idx"] ?: NOT_AVAILABLE;
            $itemtype = $line["itemtype_$idx"];
            if (!empty($itemtype)) {
                $entry["device_type_$idx"] = $itemtype::getTypeName(1);
                $item_name = '';
                if ($item = getItemForItemtype($itemtype)) {
                    if ($item->getFromDB($line["items_id_$idx"])) {
                        $item_name = $item->getName();
                    }
                }
                $entry["device_name_$idx"] = $item_name ?: NOT_AVAILABLE;
            } else {
                $entry["device_type_$idx"] = NOT_AVAILABLE;
                $entry["device_name_$idx"] = NOT_AVAILABLE;
            }
            $entries[] = $entry;
        }

        if (!$has_extra) {
            unset($columns['extra']);
        }
        $super_header = [
            'label' => '',
            'is_raw' => 'th_elements', // Indicates the header provided are the raw th elements
        ];
        if ($has_extra) {
            $super_header['label'] = '<th></th>';
        }
        $super_header['label'] .= '<th colspan="6">' . __s('Device 1') . '</th><th colspan="5">' . __s('Device 2') . '</th>';

        $datatable_params = [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'super_header' => $super_header,
            'columns' => $columns,
            'formatters' => $formatters,
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => false,
        ];

        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <div class="card">
                <div class="card-header">
                    <div class="card-title">{{ report['title'] }}</div>
                </div>
                <div class="card-body">
                    {% do call('Report::showNetworkReportCriteria', [true]) %}
                    <br>
                    {{ include('components/datatable.html.twig', datatable_params, with_context = false) }}
                </div>
            </div>
TWIG, ['report' => $report, 'datatable_params' => $datatable_params]);
    }

    /**
     * @param positive-int $users_id
     * @return array
     */
    private static function getReservationReport(int $users_id): array
    {
        return [
            'title' => Reservation::getTypeName(Session::getPluralNumber()),
            'report_id' => 'reservation',
            'report_type' => 'reservation',
            'params' => [
                'users_id' => $users_id,
            ],
            'data' => Reservation::getForUser($users_id),
        ];
    }

    /**
     * Show reservation report
     *
     * @param positive-int $users_id
     * @return void
     */
    public static function showReservationReport(int $users_id): void
    {
        $twig_params = [
            'report' => self::getReservationReport($users_id),
            'current_label' => __('Current and future reservations'),
            'old_label' => __('Past reservations'),
        ];

        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <div class="card">
                <div class="card-header">
                    <div class="card-title">{{ report['title'] }}</div>
                </div>
                <div class="card-body">
                    {% do call('Reservation::showReservationsAsList', [report['data']['in_progress'], current_label]) %}
                    {% do call('Reservation::showReservationsAsList', [report['data']['old'], old_label]) %}
                </div>
            </div>
TWIG, $twig_params);
    }

    /**
     * @param array<class-string<CommonDBTM>> $itemtypes
     * @param array<integer> $years
     */
    private static function getYearlyAssetsReport(array $itemtypes, array $years): array
    {
        global $CFG_GLPI, $DB;

        // Filter the itemtypes to only keep the ones that are valid
        $itemtypes = array_filter($itemtypes, static fn(string $itemtype): bool => in_array($itemtype, $CFG_GLPI['report_types'], true));

        $report = [
            'title' => __("Equipment's report by year"),
            'report_id' => 'yearly_assets',
            'report_type' => 'yearly_assets',
            'params' => [
                'itemtypes' => $itemtypes,
                'years' => $years,
            ],
            'data' => [
                'items' => [],
            ],
        ];

        foreach ($itemtypes as $key => $itemtype) {
            $itemtable = getTableForItemType($itemtype);

            $deleted_field       = "$itemtable.is_deleted";
            $location_field      = null;

            $criteria = [
                'SELECT'    => [
                    "$itemtable.name AS itemID",
                    "$itemtable.name AS itemname",
                    'glpi_contracttypes.id AS contracttypeID',
                    'glpi_contracttypes.name AS contracttypename',
                    'glpi_infocoms.buy_date',
                    'glpi_infocoms.warranty_duration',
                    'glpi_contracts.begin_date',
                    'glpi_contracts.duration',
                    'glpi_entities.completename AS entname',
                    'glpi_entities.id AS entID',
                ],
                'FROM'      => $itemtable,
                'LEFT JOIN' => [],
                'WHERE'     => [],
                'ORDERBY'   => ['entname ASC', 'itemdeleted DESC', 'itemname ASC'],
            ];

            if ($itemtype !== Project::class) {
                $location_field      = "glpi_locations.completename";
                $criteria['LEFT JOIN']['glpi_locations'] = [
                    'ON'  => [
                        $itemtable  => 'locations_id',
                        'glpi_locations.id',
                    ],
                ];
                $criteria['WHERE']["$itemtable.is_template"] = 0;
            }
            if ($itemtype === SoftwareLicense::class) {
                $deleted_field       = "glpi_softwares.is_deleted";
                $location_field      = null;
                $criteria['LEFT JOIN']['glpi_softwares'] = [
                    'ON'  => [
                        'glpi_softwares'        => 'id',
                        'glpi_softwarelicenses' => 'softwares_id',
                    ],
                ];
                $criteria['WHERE']['glpi_softwares.is_template'] = 0;
            }
            $criteria['SELECT'][] = "$deleted_field AS itemdeleted";
            $criteria['SELECT'][] = ($location_field !== null
                ? "$location_field AS locationname"
                : new QueryExpression($DB::quoteValue(''), 'locationname'));
            $criteria['SELECT'][] = ($location_field !== null
                ? "glpi_locations.id AS locationID"
                : new QueryExpression($DB::quoteValue(''), 'locationID'));

            $criteria['LEFT JOIN'] += [
                'glpi_contracts_items'  => [
                    'ON'  => [
                        $itemtable              => 'id',
                        'glpi_contracts_items'  => 'items_id', [
                            'AND' => [
                                'glpi_contracts_items.itemtype' => $itemtype,
                            ],
                        ],
                    ],
                ],
                'glpi_contracts'        => [
                    'ON'  => [
                        'glpi_contracts_items'  => 'contracts_id',
                        'glpi_contracts'        => 'id', [
                            'AND' => [
                                'NOT' => ['glpi_contracts_items.contracts_id' => null],
                            ],
                        ],
                    ],
                ],
                'glpi_infocoms'         => [
                    'ON'  => [
                        $itemtable        => 'id',
                        'glpi_infocoms'   => 'items_id', [
                            'AND' => [
                                'glpi_infocoms.itemtype' => $itemtype,
                            ],
                        ],
                    ],
                ],
                'glpi_contracttypes'    => [
                    'ON'  => [
                        'glpi_contracts'     => 'contracttypes_id',
                        'glpi_contracttypes' => 'id',
                    ],
                ],
                'glpi_entities'         => [
                    'ON'  => [
                        $itemtable        => 'entities_id',
                        'glpi_entities'   => 'id',
                    ],
                ],
            ];
            $criteria['WHERE'] += getEntitiesRestrictCriteria($itemtable);

            $ors = [];
            foreach ($years as $val2) {
                $ors[] = new QueryExpression(QueryFunction::year('glpi_infocoms.buy_date') . " = " . $DB->quote($val2));
                $ors[] = new QueryExpression(QueryFunction::year('glpi_contracts.begin_date') . " = " . $DB->quote($val2));
            }
            if (count($ors)) {
                $criteria['WHERE'][] = [
                    'OR'  => $ors,
                ];
            }

            $iterator = $DB->request($criteria);

            foreach ($iterator as $result) {
                $warranty_expiration_date = $result['warranty_duration'] ? Infocom::getWarrantyExpir($result['buy_date'], $result['warranty_duration']) : null;
                $end_date = $result['duration'] ? Infocom::getWarrantyExpir($result['begin_date'], $result['duration']) : null;
                $report['data']['items'][] = [
                    'itemtype' => $itemtype,
                    'items_id' => $result['itemID'],
                    'itemname' => $result['itemname'],
                    'is_deleted' => $result['itemdeleted'],
                    'entity' => $result['entID'],
                    'entityname' => $result['entname'],
                    'location' => $result['locationID'],
                    'locationname' => $result['locationname'],
                    'buy_date' => $result['buy_date'],
                    'warranty_duration' => $result['warranty_duration'],
                    'warranty_expiration_date' => $warranty_expiration_date,
                    'contracttype' => $result['contracttypeID'],
                    'contracttypename' => $result['contracttypename'],
                    'begin_date' => $result['begin_date'],
                    'duration' => $result['duration'],
                    'end_date' => $end_date,
                ];
            }
        }

        return $report;
    }

    /**
     * Show assets by year report
     *
     * @param array<class-string<CommonDBTM>> $itemtypes
     * @param array<integer> $years
     * @return void
     */
    public static function showYearlyAssetsReport(array $itemtypes, array $years): void
    {
        if ($itemtypes === []) {
            self::showYearlyAssetsReportCriteria(false);
            return;
        }
        $columns = [
            'name' => __('Name'),
            'is_deleted' => __('Deleted'),
        ];
        if (Session::isMultiEntitiesMode()) {
            $columns['entity'] = Entity::getTypeName(1);
        }
        $columns['location'] = Location::getTypeName(1);
        $columns['buy_date'] = __('Date of purchase');
        $columns['warranty_expiration_date'] = __('Warranty expiration date');
        $columns['contract_type'] = _n('Contract type', 'Contract types', 1);
        $columns['begin_date'] = __('Start date');
        $columns['end_date'] = __('End date');

        $report = self::getYearlyAssetsReport($itemtypes, $years);
        $datatable_params = [];

        foreach ($report['data']['items'] as $item) {
            $itemtype = $item['itemtype'];

            if (!isset($datatable_params[$itemtype])) {
                $datatable_params[$itemtype] = [
                    'is_tab' => true,
                    'nofilter' => true,
                    'nosort' => true,
                    'columns' => $columns,
                    'formatters' => [],
                    'super_header' => $itemtype::getTypeName(Session::getPluralNumber()),
                    'entries' => [],
                    'total_number' => 0,
                    'filtered_number' => 0,
                    'showmassiveactions' => false,
                ];
            }

            $datatable_params[$itemtype]['entries'][] = [
                'name' => $item['itemname'] ?: NOT_AVAILABLE,
                'is_deleted' => $item['is_deleted'] ? __('Yes') : __('No'),
                'entity' => $item['entityname'] ?: NOT_AVAILABLE,
                'location' => $item['locationname'] ?: NOT_AVAILABLE,
                'buy_date' => $item['buy_date'] ? Html::convDate($item['buy_date']) : NOT_AVAILABLE,
                'warranty_expiration_date' => $item['warranty_expiration_date'] ? Html::convDate($item['warranty_expiration_date']) : NOT_AVAILABLE,
                'contract_type' => $item['contracttypename'] ?: NOT_AVAILABLE,
                'begin_date' => $item['begin_date'] ? Html::convDate($item['begin_date']) : NOT_AVAILABLE,
                'end_date' => $item['end_date'] ? Html::convDate($item['end_date']) : NOT_AVAILABLE,
            ];
            $datatable_params[$itemtype]['total_number']++;
            $datatable_params[$itemtype]['filtered_number']++;
        }

        $twig_params = [
            'title' => $report['title'],
            'datatable_params' => $datatable_params,
        ];

        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <div class="card">
                <div class="card-header">
                    <div class="card-title">{{ title }}</div>
                </div>
                <div class="card-body">
                    {% do call('Report::showYearlyAssetsReportCriteria', [true]) %}
                    <br>
                    {% for itemtype, datatable in datatable_params %}
                        {{ include('components/datatable.html.twig', datatable, with_context = false) }}
                        <br>
                    {% endfor %}
                </div>
            </div>
TWIG, $twig_params);
    }

    /**
     * @param array<class-string<CommonDBTM>> $itemtypes
     * @param array<integer> $years
     */
    private static function getContractAssetsReport(array $itemtypes, array $years): array
    {
        global $CFG_GLPI, $DB;

        // Filter the itemtypes to only keep the ones that are valid
        $itemtypes = array_filter($itemtypes, static fn(string $itemtype): bool => in_array($itemtype, $CFG_GLPI['contract_types'], true));

        $report = [
            'title' => __('List of the hardware under contract'),
            'report_id' => 'contract_assets',
            'report_type' => 'contract_assets',
            'params' => [
                'itemtypes' => $itemtypes,
                'years' => $years,
            ],
            'data' => [
                'items' => [],
            ],
        ];

        foreach ($itemtypes as $itemtype) {
            $itemtable = getTableForItemType($itemtype);

            $criteria = [
                'SELECT' => [
                    "$itemtable.id AS itemID",
                    'glpi_contracttypes.id AS contracttypeID',
                    'glpi_contracttypes.name AS contracttypename',
                    'glpi_contracts.duration',
                    'glpi_entities.completename AS entname',
                    'glpi_entities.id AS entID',
                    'glpi_contracts.begin_date',
                ],
                'FROM'   => 'glpi_contracts_items',
                'INNER JOIN'   => [
                    'glpi_contracts'  => [
                        'ON'  => [
                            'glpi_contracts_items'  => 'contracts_id',
                            'glpi_contracts'        => 'id',
                        ],
                    ],
                    $itemtable  => [
                        'ON'  => [
                            $itemtable  => 'id',
                            'glpi_contracts_items'  => 'items_id', [
                                'AND' => [
                                    'glpi_contracts_items.itemtype' => $itemtype,
                                ],
                            ],
                        ],
                    ],
                ],
                'LEFT JOIN'    => [
                    'glpi_contracttypes' => [
                        'ON'  => [
                            'glpi_contracts'     => 'contracttypes_id',
                            'glpi_contracttypes' => 'id',
                        ],
                    ],
                    'glpi_entities'   => [
                        'ON'  => [
                            $itemtable        => 'entities_id',
                            'glpi_entities'   => 'id',
                        ],
                    ],
                ],
                'WHERE'        => getEntitiesRestrictCriteria($itemtable),
                'ORDERBY'      => ["entname ASC", 'itemdeleted DESC', "itemname ASC"],
            ];

            if ($DB->fieldExists($itemtable, 'name')) {
                $criteria['SELECT'][] = "$itemtable.name AS itemname";
            } else {
                $criteria['SELECT'][] = new QueryExpression($DB::quoteValue(''), 'itemname');
            }

            if ($itemtype === Project::class || $itemtype === SoftwareLicense::class) {
                if ($itemtype === SoftwareLicense::class) {
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
                                    'glpi_infocoms.itemtype'   => $itemtype,
                                ],
                            ],
                        ],
                    ];
                }
                if ($itemtype === Project::class) {
                    $criteria['SELECT'][] = "$itemtable.is_deleted AS itemdeleted";
                }

                if (isset($_POST["year"][0]) && ($_POST["year"][0] != 0)) {
                    $ors = [];
                    foreach ($_POST["year"] as $val2) {
                        $ors[] = new QueryExpression(QueryFunction::year('glpi_contracts.begin_date') . ' = ' . $DB->quote($val2));
                        if ($itemtype === SoftwareLicense::class) {
                            $ors[] = new QueryExpression(QueryFunction::year('glpi_infocoms.buy_date') . ' = ' . $DB->quote($val2));
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
                    'glpi_infocoms.warranty_duration',
                ]);
                $criteria['LEFT JOIN']['glpi_infocoms'] = [
                    'ON'  => [
                        $itemtable        => 'id',
                        'glpi_infocoms'   => 'items_id', [
                            'AND' => [
                                'glpi_infocoms.itemtype' => $itemtype,
                            ],
                        ],
                    ],
                ];
                if ($DB->fieldExists($itemtable, 'locations_id')) {
                    $criteria['SELECT'][] = 'glpi_locations.completename AS locationname';
                    $criteria['SELECT'][] = 'glpi_locations.id AS locationID';
                    $criteria['LEFT JOIN']['glpi_locations'] = [
                        'ON'  => [
                            $itemtable        => 'locations_id',
                            'glpi_locations'   => 'id',
                        ],
                    ];
                } else {
                    $criteria['SELECT'][] = new QueryExpression("'' AS locationname");
                    $criteria['SELECT'][] = new QueryExpression("'' AS locationID");
                }

                if ($DB->fieldExists($itemtable, 'is_template')) {
                    $criteria['WHERE'][] = ["$itemtable.is_template" => 0];
                }

                $ors = [];
                foreach ($years as $val2) {
                    $ors[] = new QueryExpression(QueryFunction::year('glpi_infocoms.buy_date') . ' = ' . $DB::quoteValue($val2));
                    $ors[] = new QueryExpression(QueryFunction::year('glpi_contracts.begin_date') . ' = ' . $DB::quoteValue($val2));
                }
                if (count($ors)) {
                    $criteria['WHERE'][] = ['OR' => $ors];
                }
            }

            $iterator = $DB->request($criteria);

            foreach ($iterator as $result) {
                $warranty_expiration_date = $result['warranty_duration'] ? Infocom::getWarrantyExpir($result['buy_date'], $result['warranty_duration']) : null;
                $end_date = $result['duration'] ? Infocom::getWarrantyExpir($result['begin_date'], $result['duration']) : null;
                $report['data']['items'][] = [
                    'itemtype' => $itemtype,
                    'items_id' => $result['itemID'],
                    'itemname' => $result['itemname'],
                    'is_deleted' => $result['itemdeleted'],
                    'entity' => $result['entID'],
                    'entityname' => $result['entname'],
                    'location' => $result['locationID'],
                    'locationname' => $result['locationname'],
                    'buy_date' => $result['buy_date'],
                    'warranty_duration' => $result['warranty_duration'],
                    'warranty_expiration_date' => $warranty_expiration_date,
                    'contracttype' => $result['contracttypeID'],
                    'contracttypename' => $result['contracttypename'],
                    'begin_date' => $result['begin_date'],
                    'duration' => $result['duration'],
                    'end_date' => $end_date,
                ];
            }
        }

        return $report;
    }

    /**
     * Show assets under contract report
     *
     * @param array<class-string<CommonDBTM>> $itemtypes
     * @param array<integer> $years
     * @return void
     */
    public static function showContractAssetsReport(array $itemtypes, array $years): void
    {
        if ($itemtypes === []) {
            self::showContractAssetsReportCriteria(false);
            return;
        }
        $columns = [
            'name' => __('Name'),
            'is_deleted' => __('Deleted'),
        ];
        if (Session::isMultiEntitiesMode()) {
            $columns['entity'] = Entity::getTypeName(1);
        }
        $columns['location'] = Location::getTypeName(1);
        $columns['buy_date'] = __('Date of purchase');
        $columns['warranty_expiration_date'] = __('Warranty expiration date');
        $columns['contract_type'] = _n('Contract type', 'Contract types', 1);
        $columns['begin_date'] = __('Start date');
        $columns['end_date'] = __('End date');

        $report = self::getContractAssetsReport($itemtypes, $years);
        $datatable_params = [];

        foreach ($report['data']['items'] as $item) {
            $itemtype = $item['itemtype'];

            if (!isset($datatable_params[$itemtype])) {
                $datatable_params[$itemtype] = [
                    'is_tab' => true,
                    'nofilter' => true,
                    'nosort' => true,
                    'columns' => $columns,
                    'formatters' => [],
                    'super_header' => $itemtype::getTypeName(Session::getPluralNumber()),
                    'entries' => [],
                    'total_number' => 0,
                    'filtered_number' => 0,
                    'showmassiveactions' => false,
                ];
            }

            $datatable_params[$itemtype]['entries'][] = [
                'name' => $item['itemname'] ?: NOT_AVAILABLE,
                'is_deleted' => $item['is_deleted'] ? __('Yes') : __('No'),
                'entity' => $item['entityname'] ?: NOT_AVAILABLE,
                'location' => $item['locationname'] ?: NOT_AVAILABLE,
                'buy_date' => $item['buy_date'] ? Html::convDate($item['buy_date']) : NOT_AVAILABLE,
                'warranty_expiration_date' => $item['warranty_expiration_date'] ? Html::convDate($item['warranty_expiration_date']) : NOT_AVAILABLE,
                'contract_type' => $item['contracttypename'] ?: NOT_AVAILABLE,
                'begin_date' => $item['begin_date'] ? Html::convDate($item['begin_date']) : NOT_AVAILABLE,
                'end_date' => $item['end_date'] ? Html::convDate($item['end_date']) : NOT_AVAILABLE,
            ];
            $datatable_params[$itemtype]['total_number']++;
            $datatable_params[$itemtype]['filtered_number']++;
        }

        $twig_params = [
            'title' => $report['title'],
            'datatable_params' => $datatable_params,
        ];

        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <div class="card">
                <div class="card-header">
                    <div class="card-title">{{ title }}</div>
                </div>
                <div class="card-body">
                    {% do call('Report::showContractAssetsReportCriteria', [true]) %}
                    <br>
                    {% for itemtype, datatable in datatable_params %}
                        {{ include('components/datatable.html.twig', datatable, with_context = false) }}
                        <br>
                    {% endfor %}
                </div>
            </div>
TWIG, $twig_params);
    }

    /**
     * @param string|null $begin
     * @param string|null $end
     * @return array
     */
    private static function handleInfocomDates(?string $begin, ?string $end): array
    {
        if (empty($begin) && empty($end)) {
            $year           = date("Y") - 1;
            $begin = date("Y-m-d", mktime(1, 0, 0, (int) date("m"), (int) date("d"), $year));
            $end = date("Y-m-d");
        }

        if (
            !empty($begin)
            && !empty($end)
            && (strcmp($end, $begin) < 0)
        ) {
            $tmp            = $begin;
            $begin = $end;
            $end = $tmp;
        }

        return [$begin, $end];
    }

    /**
     * @param ?string $begin
     * @param ?string $end
     * @return array
     */
    private static function getInfocomReport(?string $begin, ?string $end, bool $is_assets = true): array
    {
        global $CFG_GLPI;

        [$begin, $end] = self::handleInfocomDates($begin, $end);

        $report = [
            'title' => $is_assets ? Infocom::getTypeName(1) : __('Other financial and administrative information (licenses, cartridges, consumables)'),
            'report_id' => $is_assets ? 'infocom_assets' : 'infocom_other',
            'report_type' => $is_assets ? 'infocom_assets' : 'infocom_other',
            'params' => [
                'begin_date' => $begin,
                'end_date' => $end,
            ],
            'data' => [
                'items' => [],
            ],
        ];

        foreach ($CFG_GLPI['infocom_types'] as $itemtype) {
            $results = $is_assets ? Infocom::getDataForAssetInfocomReport($itemtype, $begin, $end) : Infocom::getDataForOtherInfocomReport($itemtype, $begin, $end);
            if ($results === null) {
                continue;
            }
            $item = getItemForItemtype($itemtype);

            foreach ($results as $result) {
                if (isset($result['is_global']) && $result['is_global'] && $item->getFromDB($result['items_id'])) {
                    $result['value'] = ($result['value'] ?: 0) * Asset_PeripheralAsset::countForItem($item);
                }

                $result['anv'] = Infocom::Amort(
                    $result["sink_type"],
                    $result["value"],
                    $result["sink_time"],
                    $result["sink_coeff"],
                    $result["buy_date"],
                    $result["use_date"],
                    $CFG_GLPI["date_tax"],
                    'n'
                );

                $result['amortization'] = Infocom::Amort(
                    $result["sink_type"],
                    $result["value"],
                    $result["sink_time"],
                    $result["sink_coeff"],
                    $result["buy_date"],
                    $result["use_date"],
                    $CFG_GLPI["date_tax"],
                    'all'
                );

                $result['warranty_expiration_date'] = Infocom::getWarrantyExpir($result["warranty_date"], $result["warranty_duration"]);

                $report['data']['items'][] = $result;
            }
        }

        return $report;
    }

    /**
     * Show infocom report
     *
     * @param ?string $begin
     * @param ?string $end
     * @return void
     */
    public static function showInfocomReport(?string $begin, ?string $end): void
    {
        $columns = [
            'name' => __('Name'),
        ];
        if (Session::isMultiEntitiesMode()) {
            $columns['entity'] = Entity::getTypeName(1);
        }
        $columns['value'] = _x('price', 'Value');
        $columns['anv'] = __('ANV'); // Annual Net Value?
        $columns['tco'] = __('TCO');
        $columns['buy_date'] = __('Date of purchase');
        $columns['use_date'] = __('Startup date');
        $columns['warranty_date'] = __('Start date of warranty');
        $columns['warranty_expiration_date'] = __('Warranty expiration date');

        $report = self::getInfocomReport($begin, $end);
        $datatable_params = [];
        $anv_graph_data_itemtype = [];
        $total_graph_data_itemtype = [];

        foreach ($report['data']['items'] as $item) {
            $itemtype = $item['itemtype'];

            if (!isset($datatable_params[$itemtype])) {
                $datatable_params[$itemtype] = [
                    'is_tab' => true,
                    'nofilter' => true,
                    'nosort' => true,
                    'columns' => $columns,
                    'formatters' => [
                        'value' => 'number',
                        'anv' => 'number',
                    ],
                    'super_header' => $itemtype::getTypeName(Session::getPluralNumber()),
                    'entries' => [],
                    'footers' => [
                        [
                            __('Total'),
                            '',
                            0, // Total Value
                            0, // Total Annual Net Value
                            '', '', '', '', '',
                        ],
                    ],
                    'footer_class' => 'fw-bold',
                    'total_number' => 0,
                    'filtered_number' => 0,
                    'showmassiveactions' => false,
                ];
            }

            $datatable_params[$itemtype]['entries'][] = [
                'name' => $item['itemname'] ?: NOT_AVAILABLE,
                'entity' => $item['entityname'] ?: NOT_AVAILABLE,
                'value' => $item['value'],
                'anv' => $item['anv'],
                'tco' => Infocom::showTco($item["ticket_tco"], $item["value"]),
                'buy_date' => $item['buy_date'] ? Html::convDate($item['buy_date']) : NOT_AVAILABLE,
                'use_date' => $item['use_date'] ? Html::convDate($item['use_date']) : NOT_AVAILABLE,
                'warranty_date' => $item['warranty_date'] ? Html::convDate($item['warranty_date']) : NOT_AVAILABLE,
                'warranty_expiration_date' => $item['warranty_expiration_date'] ? Html::convDate($item['warranty_expiration_date']) : NOT_AVAILABLE,
            ];
            $datatable_params[$itemtype]['total_number']++;
            $datatable_params[$itemtype]['filtered_number']++;
            $datatable_params[$itemtype]['footers'][0][2] += $item['value'];
            $datatable_params[$itemtype]['footers'][0][3] += is_numeric($item['anv']) ? $item['anv'] : 0;

            if (is_array($item['amortization']) && (count($item['amortization']) > 0)) {
                foreach ($item['amortization']["annee"] as $key => $val) {
                    if ($item['amortization']["vcnetfin"][$key] > 0) {
                        if (!isset($anv_graph_data_itemtype[$itemtype][$val])) {
                            $anv_graph_data_itemtype[$itemtype][$val] = 0;
                        }
                        $anv_graph_data_itemtype[$itemtype][$val] += $item['amortization']["vcnetdeb"][$key];
                    }
                }
            }

            if (!empty($item["buy_date"])) {
                $year = substr($item["buy_date"], 0, 4);
                if ($item["value"] > 0) {
                    if (!isset($total_graph_data_itemtype[$itemtype][$year])) {
                        $total_graph_data_itemtype[$itemtype][$year] = 0;
                    }
                    $total_graph_data_itemtype[$itemtype][$year] += $item["value"];
                }
            }
        }

        $twig_params = [
            'title' => $report['title'],
            'datatable_params' => $datatable_params,
            'graphs' => [],
        ];

        foreach ($anv_graph_data_itemtype as $itemtype => $anv_graph_data) {
            $item_object = getItemForItemtype($itemtype);
            if (!$item_object instanceof CommonGLPI) {
                throw new RuntimeException("Invalid itemtype: $itemtype");
            }

            if (count($anv_graph_data) > 0) {
                $graph_data = [];
                foreach ($anv_graph_data as $year => $value) {
                    $graph_data[] = [
                        'label' => $year,
                        'number' => round($value),
                    ];
                }

                $twig_params['graphs'][] = Widget::simpleLine([
                    'label' => sprintf(
                        __('%1$s account net value'),
                        $item_object::getTypeName(1)
                    ),
                    'data' => $graph_data,
                    'legend' => true,
                ]);
            }
        }

        foreach ($total_graph_data_itemtype as $itemtype => $total_graph_data) {
            $item_object = getItemForItemtype($itemtype);
            if (!$item_object instanceof CommonGLPI) {
                throw new RuntimeException("Invalid itemtype: $itemtype");
            }

            if (count($total_graph_data) > 0) {
                $graph_data = [];
                foreach ($total_graph_data as $year => $value) {
                    $graph_data[] = [
                        'label' => $year,
                        'number' => round($value),
                    ];
                }

                $twig_params['graphs'][] = Widget::simpleLine([
                    'label' => sprintf(
                        __('%1$s value'),
                        $item_object::getTypeName(1)
                    ),
                    'data' => $graph_data,
                    'legend' => true,
                ]);
            }
        }

        $all_itemtypes_anv_graph_data = [];
        $all_itemtypes_total_graph_data = [];
        foreach ($anv_graph_data_itemtype as $itemtype => $anv_graph_data) {
            foreach ($anv_graph_data as $year => $value) {
                if (!isset($all_itemtypes_anv_graph_data[$year])) {
                    $all_itemtypes_anv_graph_data[$year] = 0;
                }
                $all_itemtypes_anv_graph_data[$year] += $value;
            }
        }
        foreach ($total_graph_data_itemtype as $itemtype => $total_graph_data) {
            foreach ($total_graph_data as $year => $value) {
                if (!isset($all_itemtypes_total_graph_data[$year])) {
                    $all_itemtypes_total_graph_data[$year] = 0;
                }
                $all_itemtypes_total_graph_data[$year] += $value;
            }
        }

        if (count($all_itemtypes_anv_graph_data) > 0) {
            $graph_data = [];
            foreach ($all_itemtypes_anv_graph_data as $year => $value) {
                $graph_data[] = [
                    'label' => $year,
                    'number' => round($value),
                ];
            }

            $twig_params['graphs'][] = Widget::simpleLine([
                'label' => __('Total account net value'),
                'data' => $graph_data,
                'legend' => true,
            ]);
        }

        if (count($all_itemtypes_total_graph_data) > 0) {
            $graph_data = [];
            foreach ($all_itemtypes_total_graph_data as $year => $value) {
                $graph_data[] = [
                    'label' => $year,
                    'number' => round($value),
                ];
            }

            $twig_params['graphs'][] = Widget::simpleLine([
                'label' => __('Total value'),
                'data' => $graph_data,
                'legend' => true,
            ]);
        }

        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <div class="card">
                <div class="card-header">
                    <div class="card-title">{{ title }}</div>
                </div>
                <div class="card-body">
                    {% do call('Report::showInfocomReportCriteria', [true, true]) %}
                    <br>
                    {% for itemtype, datatable in datatable_params %}
                        {{ include('components/datatable.html.twig', datatable, with_context = false) }}
                        <br>
                    {% endfor %}
                    {% for graph in graphs %}
                        {{ graph|raw }}
                        <br>
                    {% endfor %}
                </div>
            </div>
TWIG, $twig_params);
    }

    /**
     * Show other (non-asset) infocom report
     *
     * @param ?string $begin
     * @param ?string $end
     * @return void
     */
    public static function showOtherInfocomReport(?string $begin, ?string $end): void
    {
        $report = self::getInfocomReport($begin, $end, false);

        $anv_graph_data_itemtype = [];
        $total_graph_data_itemtype = [];

        foreach ($report['data']['items'] as $item) {
            $itemtype = $item['itemtype'];

            if (is_array($item['amortization']) && (count($item['amortization']) > 0)) {
                foreach ($item['amortization']["annee"] as $key => $val) {
                    if ($item['amortization']["vcnetfin"][$key] > 0) {
                        if (!isset($anv_graph_data_itemtype[$itemtype][$val])) {
                            $anv_graph_data_itemtype[$itemtype][$val] = 0;
                        }
                        $anv_graph_data_itemtype[$itemtype][$val] += $item['amortization']["vcnetdeb"][$key];
                    }
                }
            }

            if (!empty($item["buy_date"])) {
                $year = substr($item["buy_date"], 0, 4);
                if ($item["value"] > 0) {
                    if (!isset($total_graph_data_itemtype[$itemtype][$year])) {
                        $total_graph_data_itemtype[$itemtype][$year] = 0;
                    }
                    $total_graph_data_itemtype[$itemtype][$year] += $item["value"];
                }
            }
        }

        $twig_params = [
            'title' => $report['title'],
            'graphs' => [],
        ];

        foreach ($anv_graph_data_itemtype as $itemtype => $anv_graph_data) {
            $item_object = getItemForItemtype($itemtype);
            if (!$item_object instanceof CommonGLPI) {
                throw new RuntimeException("Invalid itemtype: $itemtype");
            }

            if (count($anv_graph_data) > 0) {
                $graph_data = [];
                foreach ($anv_graph_data as $year => $value) {
                    $graph_data[] = [
                        'label' => $year,
                        'number' => round($value),
                    ];
                }

                $twig_params['graphs'][] = Widget::simpleLine([
                    'label' => sprintf(
                        __('%1$s account net value'),
                        $item_object::getTypeName(1)
                    ),
                    'data' => $graph_data,
                    'legend' => true,
                ]);
            }
        }

        foreach ($total_graph_data_itemtype as $itemtype => $total_graph_data) {
            $item_object = getItemForItemtype($itemtype);
            if (!$item_object instanceof CommonGLPI) {
                throw new RuntimeException("Invalid itemtype: $itemtype");
            }

            if (count($total_graph_data) > 0) {
                $graph_data = [];
                foreach ($total_graph_data as $year => $value) {
                    $graph_data[] = [
                        'label' => $year,
                        'number' => round($value),
                    ];
                }

                $twig_params['graphs'][] = Widget::simpleLine([
                    'label' => sprintf(
                        __('%1$s value'),
                        $item_object::getTypeName(1)
                    ),
                    'data' => $graph_data,
                    'legend' => true,
                ]);
            }
        }

        $all_itemtypes_anv_graph_data = [];
        $all_itemtypes_total_graph_data = [];
        foreach ($anv_graph_data_itemtype as $itemtype => $anv_graph_data) {
            foreach ($anv_graph_data as $year => $value) {
                if (!isset($all_itemtypes_anv_graph_data[$year])) {
                    $all_itemtypes_anv_graph_data[$year] = 0;
                }
                $all_itemtypes_anv_graph_data[$year] += $value;
            }
        }
        foreach ($total_graph_data_itemtype as $itemtype => $total_graph_data) {
            foreach ($total_graph_data as $year => $value) {
                if (!isset($all_itemtypes_total_graph_data[$year])) {
                    $all_itemtypes_total_graph_data[$year] = 0;
                }
                $all_itemtypes_total_graph_data[$year] += $value;
            }
        }

        if (count($all_itemtypes_anv_graph_data) > 0) {
            $graph_data = [];
            foreach ($all_itemtypes_anv_graph_data as $year => $value) {
                $graph_data[] = [
                    'label' => $year,
                    'number' => round($value),
                ];
            }

            $twig_params['graphs'][] = Widget::simpleLine([
                'label' => __('Total account net value'),
                'data' => $graph_data,
                'legend' => true,
            ]);
        }

        if (count($all_itemtypes_total_graph_data) > 0) {
            $graph_data = [];
            foreach ($all_itemtypes_total_graph_data as $year => $value) {
                $graph_data[] = [
                    'label' => $year,
                    'number' => round($value),
                ];
            }

            $twig_params['graphs'][] = Widget::simpleLine([
                'label' => __('Total value'),
                'data' => $graph_data,
                'legend' => true,
            ]);
        }

        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <div class="card">
                <div class="card-header">
                    <div class="card-title">{{ title }}</div>
                </div>
                <div class="card-body">
                    {% do call('Report::showInfocomReportCriteria', [true, false]) %}
                    <br>
                    {% for graph in graphs %}
                        {{ graph|raw }}
                        <br>
                    {% endfor %}
                </div>
            </div>
TWIG, $twig_params);
    }

    public function getRights($interface = 'central')
    {
        return [ READ => __('Read')];
    }

    public static function getIcon()
    {
        return "ti ti-report";
    }

    public static function showReservationReportCriteria(): void
    {
        $twig_params = [
            'title' => __('Loan'),
            'btn_label' => __('Display report'),
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            <div class="card">
                <div class="card-header">
                    <div class="card-title">{{ title }}</div>
                </div>
                <div class="card-body">
                    <form method="get" action="report.reservation.php" class="d-flex mt-n3">
                        {{ fields.dropdownField('User', 'id', _get['id']|default(0), 'User'|itemtype_name, {
                            right: 'reservation',
                            mb: '',
                        }) }}
                        {% set btn_el %}
                            <button type="submit" class="btn btn-primary" name="submit">{{ btn_label }}</button>
                        {% endset %}
                        {{ fields.htmlField('', btn_el, null, {
                            no_label: true,
                            mb: '',
                            add_field_class: 'ms-3'
                        }) }}
                    </form>
                </div>
            </div>
TWIG, $twig_params);
    }

    /**
     * @param bool $embeded
     * @param bool $is_assets
     * @return void
     * @used-by self::showInfocomReport()
     * @used-by self::showOtherInfocomReport()
     */
    public static function showInfocomReportCriteria(bool $embeded, bool $is_assets = true): void
    {
        [$begin, $end] = self::handleInfocomDates($_GET['date1'] ?? null, $_GET['date2'] ?? null);
        $title = $is_assets ? Infocom::getTypeName(1) : __('Other financial and administrative information (licenses, cartridges, consumables)');
        $twig_params = [
            'title' => $title,
            'btn_label' => __('Display report'),
            'start_label' => __('Start date'),
            'end_label' => __('End date'),
            'begin' => $begin,
            'end' => $end,
            'is_assets' => $is_assets,
            'embeded' => $embeded,
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {% if not embeded %}
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">{{ title }}</div>
                    </div>
                    <div class="card-body">
            {% endif %}
            <form method="get" action="{{ is_assets ? 'report.infocom.php' : 'report.infocom.conso.php' }}" class="d-flex mt-n3">
                {{ fields.dateField('date1', begin, start_label, {
                    field_class: 'col-12 col-sm-4',
                    clearable: true,
                    mb: '',
                }) }}
                {{ fields.dateField('date2', end, end_label, {
                    field_class: 'col-12 col-sm-4',
                    clearable: true,
                    mb: '',
                }) }}
                {% set btn_el %}
                    <button type="submit" class="btn btn-primary" name="submit">{{ btn_label }}</button>
                {% endset %}
                {{ fields.htmlField('', btn_el, null, {
                    field_class: 'col-12 col-sm-4',
                    no_label: true,
                    mb: '',
                    add_field_class: 'ms-3'
                }) }}
            </form>
            {% if not embeded %}
                    </div>
                </div>
            {% endif %}
TWIG, $twig_params);
    }

    public static function showYearlyAssetsReportCriteria(bool $embeded): void
    {
        $twig_params = [
            'title' => __("Equipment's report by year"),
            'btn_label' => __('Display report'),
            'itemtype_label' => __('Item type'),
            'year_label' => _n('Date', 'Dates', 1),
            'years' => [],
            'embeded' => $embeded,
        ];

        // +/- 10 years from the current year
        $current_year = (int) date('Y');
        for ($i = $current_year - 10; $i <= $current_year + 10; $i++) {
            $twig_params['years'][$i] = $i;
        }

        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {% if not embeded %}
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">{{ title }}</div>
                    </div>
                    <div class="card-body">
            {% endif %}
            <form method="get" action="report.year.php" class="mt-n3">
                <div class="d-flex">
                    {{ fields.dropdownItemTypes('item_type', '', itemtype_label, {
                        multiple: true,
                        values: config('report_types'),
                        types: config('report_types'),
                        label_class: 'col-12 col-sm-3',
                        input_class: 'col-12 col-sm-9',
                    }) }}
                    {{ fields.dropdownArrayField('year', date()|date('Y'), years, year_label, {
                        multiple: true,
                        label_class: 'col-12 col-sm-3',
                        input_class: 'col-12 col-sm-9',
                    }) }}
                </div>
                <div class="d-flex flex-row-reverse">
                    <button type="submit" class="btn btn-primary" name="submit">{{ btn_label }}</button>
                </div>
            </form>
            {% if not embeded %}
                    </div>
                </div>
            {% endif %}
TWIG, $twig_params);
    }

    public static function showContractAssetsReportCriteria(bool $embeded): void
    {
        $twig_params = [
            'title' => __('List of the hardware under contract'),
            'btn_label' => __('Display report'),
            'itemtype_label' => __('Item type'),
            'year_label' => _n('Date', 'Dates', 1),
            'years' => [],
            'embeded' => $embeded,
        ];

        // +/- 10 years from the current year
        $current_year = (int) date('Y');
        for ($i = $current_year - 10; $i <= $current_year + 10; $i++) {
            $twig_params['years'][$i] = $i;
        }

        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {% if not embeded %}
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">{{ title }}</div>
                    </div>
                    <div class="card-body">
            {% endif %}
            <form method="get" action="report.contract.php" class="mt-n3">
                <div class="d-flex">
                    {{ fields.dropdownItemTypes('item_type', '', itemtype_label, {
                        multiple: true,
                        values: config('contract_types'),
                        types: config('contract_types'),
                        label_class: 'col-12 col-sm-3',
                        input_class: 'col-12 col-sm-9',
                    }) }}
                    {{ fields.dropdownArrayField('year', date()|date('Y'), years, year_label, {
                        multiple: true,
                        label_class: 'col-12 col-sm-3',
                        input_class: 'col-12 col-sm-9',
                    }) }}
                </div>
                <div class="d-flex flex-row-reverse">
                    <button type="submit" class="btn btn-primary" name="submit">{{ btn_label }}</button>
                </div>
            </form>
            {% if not embeded %}
                    </div>
                </div>
            {% endif %}
TWIG, $twig_params);
    }
}
