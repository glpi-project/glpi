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

namespace Glpi\Api\HL\Controller;

use CommonDevice;
use Dropdown;
use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Search;
use Stat;

use function Safe\mktime;
use function Safe\ob_get_clean;
use function Safe\ob_start;
use function Safe\parse_url;
use function Safe\preg_match;
use function Safe\strtotime;

class ReportController extends AbstractController
{
    protected static function getRawKnownSchemas(): array
    {
        return [
            'StatReport' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'assistance_type' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The assistance type the stats are for such as "Ticket", "Change" or "Problem"',
                    ],
                    'report_type' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The report type',
                    ],
                    'report_title' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The report title',
                    ],
                    'report_group_fields' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'The fields the report can be grouped by',
                        'items' => [
                            'type' => Doc\Schema::TYPE_STRING,
                        ],
                    ],
                ],
            ],
            'GlobalStats' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'sample_dates' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'The dates the stats are for',
                        'items' => [
                            'type' => Doc\Schema::TYPE_STRING,
                            'format' => Doc\Schema::FORMAT_STRING_DATE,
                        ],
                    ],
                    'number_open' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'The number of assistance items opened during the period',
                        'items' => [
                            'type' => Doc\Schema::TYPE_INTEGER,
                        ],
                    ],
                    'number_solved' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'The number of assistance items solved during the period',
                        'items' => [
                            'type' => Doc\Schema::TYPE_INTEGER,
                        ],
                    ],
                    'number_late' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'The number of late assistance items during the period',
                        'items' => [
                            'type' => Doc\Schema::TYPE_INTEGER,
                        ],
                    ],
                    'number_closed' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'The number of assistance items closed during the period',
                        'items' => [
                            'type' => Doc\Schema::TYPE_INTEGER,
                        ],
                    ],
                    'satisfaction_surveys_open' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'The number of satisfaction surveys opened during the period',
                        'items' => [
                            'type' => Doc\Schema::TYPE_INTEGER,
                        ],
                    ],
                    'satisfaction_surveys_answered' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'The number of satisfaction surveys answered during the period',
                        'items' => [
                            'type' => Doc\Schema::TYPE_INTEGER,
                        ],
                    ],
                    'satisfaction_surveys_avg_rating' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'The average rating of the satisfaction surveys based on the answer date',
                        'items' => [
                            'type' => Doc\Schema::TYPE_NUMBER,
                            'format' => Doc\Schema::FORMAT_NUMBER_FLOAT,
                        ],
                    ],
                    'time_solve_avg' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'The average time it took to resolve the assistance items (in seconds)',
                        'items' => [
                            'type' => Doc\Schema::TYPE_INTEGER,
                        ],
                    ],
                    'time_close_avg' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'The average time it took to close the assistance items (in seconds)',
                        'items' => [
                            'type' => Doc\Schema::TYPE_INTEGER,
                        ],
                    ],
                    'time_treatment_avg' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'The average time it took to completely treat the assistance items (in seconds)',
                        'items' => [
                            'type' => Doc\Schema::TYPE_INTEGER,
                        ],
                    ],
                ],
            ],
            'ITILStats' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'item' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'description' => 'The item the stats are grouped by',
                        'properties' => [
                            'id' => [
                                'type' => Doc\Schema::TYPE_INTEGER,
                                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                            ],
                            'name' => [
                                'type' => Doc\Schema::TYPE_STRING,
                            ],
                        ],
                    ],
                    'number_open' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The number of open assistance items',
                    ],
                    'number_solved' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The number of solved assistance items',
                    ],
                    'number_late' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The number of late assistance items',
                    ],
                    'number_closed' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The number of closed assistance items',
                    ],
                    'satisfaction_surveys_open' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The number of open satisfaction surveys',
                    ],
                    'satisfaction_surveys_answered' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The number of answered satisfaction surveys',
                    ],
                    'satisfaction_surveys_avg_rating' => [
                        'type' => Doc\Schema::TYPE_NUMBER,
                        'format' => Doc\Schema::FORMAT_NUMBER_FLOAT,
                        'description' => 'The average rating of the satisfaction surveys',
                    ],
                    'time_take_into_account_avg' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The average time it took to take the assistance items into account (in seconds)',
                    ],
                    'time_solve_avg' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The average time it took to resolve the assistance items (in seconds)',
                    ],
                    'time_close_avg' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The average time it took to close the assistance items (in seconds)',
                    ],
                    'time_treatment_avg' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The average time it took to completely treat the assistance items (in seconds)',
                    ],
                    'time_treatment_total' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The total time it took to completely treat the assistance items (in seconds)',
                    ],
                ],
            ],
            'AssetStats' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'item' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'description' => 'The item the stats are grouped by',
                        'properties' => [
                            'itemtype' => [
                                'type' => Doc\Schema::TYPE_STRING,
                                'description' => 'The itemtype of the item',
                            ],
                            'id' => [
                                'type' => Doc\Schema::TYPE_INTEGER,
                                'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                            ],
                            'name' => [
                                'type' => Doc\Schema::TYPE_STRING,
                            ],
                            'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity') + [
                                'description' => 'The entity the item belongs to',
                            ],
                            'is_deleted' => [
                                'type' => Doc\Schema::TYPE_BOOLEAN,
                                'description' => 'Whether the item is deleted or not',
                            ],
                        ],
                    ],
                    'number_open' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The number of open assistance items',
                    ],
                ],
            ],
            'AssetCharacteristicsStats' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'characteristic' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The characteristic value',
                    ],
                    'number_open' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The number of open assistance items',
                    ],
                    'number_solved' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The number of solved assistance items',
                    ],
                    'number_late' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The number of late assistance items',
                    ],
                    'number_closed' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The number of closed assistance items',
                    ],
                    'satisfaction_surveys_open' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The number of open satisfaction surveys',
                    ],
                    'satisfaction_surveys_answered' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The number of answered satisfaction surveys',
                    ],
                    'satisfaction_surveys_avg_rating' => [
                        'type' => Doc\Schema::TYPE_NUMBER,
                        'format' => Doc\Schema::FORMAT_NUMBER_FLOAT,
                        'description' => 'The average rating of the satisfaction surveys',
                    ],
                    'time_take_into_account_avg' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The average time it took to take the assistance items into account (in seconds)',
                    ],
                    'time_solve_avg' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The average time it took to resolve the assistance items (in seconds)',
                    ],
                    'time_close_avg' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The average time it took to close the assistance items (in seconds)',
                    ],
                    'time_treatment_avg' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The average time it took to completely treat the assistance items (in seconds)',
                    ],
                    'time_treatment_total' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'The total time it took to completely treat the assistance items (in seconds)',
                    ],
                ],
            ],
        ];
    }

    #[Route(path: '/Assistance/Stat', methods: ['GET'], tags: ['Statistics', 'Assistance'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'List available assistance statistics',
        responses: [
            new Doc\Response(schema: new Doc\SchemaReference('StatReport[]')),
        ]
    )]
    public function listStatisticReports(Request $request): Response
    {
        $available_reports = Stat::getAvailableStatistics();
        $results = [];
        // We cannot handle stats from plugins here. Plugins should add their own routes to handle them such as `/Assistance/Stat/PluginName/ReportName`.
        // They could even use a response middleware to modify the response of this endpoint to let users discover the new reports in the same way.
        $plugin_pattern = '/\/(plugins|marketplace)\//';
        foreach ($available_reports as $group => $reports) {
            if (is_array($reports)) {
                foreach ($reports as $key => $name) {
                    if (!preg_match($plugin_pattern, $key)) {
                        $group_fields = [];
                        if (stripos($key, '/front/stat.tracking.php') !== false) {
                            $group_fields = Stat::getITILStatFields($group);
                        } elseif (stripos($key, '/front/stat.location.php') !== false) {
                            $group_fields = Stat::getItemCharacteristicStatFields(); // Not actually about location...
                        }
                        // flatten the grouped group_fields
                        $flattened_group_fields = [];
                        foreach ($group_fields as $group_field_key => $group_field_values) {
                            foreach ($group_field_values as $field => $group_field_value) {
                                $flattened_group_fields[$field] = $group_field_key . ' - ' . $group_field_value;
                            }
                        }

                        $key_lower = strtolower($key);

                        $report_type = match (true) {
                            str_contains($key_lower, '/front/stat.global.php') => 'Global',
                            str_contains($key_lower, '/front/stat.item.php') => 'Asset',
                            str_contains($key_lower, '/front/stat.location.php') => 'AssetCharacteristics',
                            str_contains($key_lower, '/front/stat.tracking.php') => 'Characteristics',
                            default => 'unknown',
                        };

                        if ($report_type === 'unknown') {
                            continue;
                        }
                        // Assistance type is the itemtype param from the $key url or defaults to $group
                        $params = [];
                        parse_str(parse_url($key, PHP_URL_QUERY) ?? '', $params);
                        $assistance_type = $params['itemtype'] ?? $group;
                        $results[] = [
                            'assistance_type' => $assistance_type,
                            'report_type' => $report_type,
                            'report_title' => $name,
                            'report_group_fields' => $flattened_group_fields,
                        ];
                    }
                }
            }
        }
        return new JSONResponse($results);
    }

    #[Route(path: '/Assistance/Stat/{assistance_type}/Global', methods: ['GET'], requirements: [
        'assistance_type' => 'Ticket|Change|Problem',
    ], tags: ['Statistics', 'Assistance'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get global assistance statistics',
        parameters: [
            new Doc\Parameter(
                name: 'date_start',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING, format: Doc\Schema::FORMAT_STRING_DATE),
                description: 'The start date of the statistics',
                location: Doc\Parameter::LOCATION_QUERY,
                example: '2024-01-30'
            ),
            new Doc\Parameter(
                name: 'date_end',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING, format: Doc\Schema::FORMAT_STRING_DATE),
                description: 'The end date of the statistics',
                location: Doc\Parameter::LOCATION_QUERY,
                example: '2024-01-30'
            ),
        ],
        responses: [
            new Doc\Response(schema: new Doc\SchemaReference('GlobalStats')),
        ]
    )]
    public function getITILGlobalStats(Request $request): Response
    {
        $itemtype = $request->getAttribute('assistance_type');
        $date_end = $request->getQueryParams()['date_end'] ?? date('Y-m-d', strtotime($_SESSION['glpi_currenttime']));
        $date_start = $request->getQueryParams()['date_start'] ?? date('Y-m-d', strtotime('-1 year', strtotime($date_end)));

        $nb_open_stats = Stat::constructEntryValues($itemtype, 'inter_total', $date_start, $date_end);
        $nb_solved_stats = Stat::constructEntryValues($itemtype, 'inter_solved', $date_start, $date_end);
        $nb_late_stats = Stat::constructEntryValues($itemtype, 'inter_solved_late', $date_start, $date_end);
        $nb_closed_stats = Stat::constructEntryValues($itemtype, 'inter_closed', $date_start, $date_end);
        $nb_opensatisfaction_stats = Stat::constructEntryValues($itemtype, 'inter_opensatisfaction', $date_start, $date_end);
        $nb_answersatisfaction_stats = Stat::constructEntryValues($itemtype, 'inter_answersatisfaction', $date_start, $date_end);
        $avg_satisfaction_stats = Stat::constructEntryValues($itemtype, 'inter_avgsatisfaction', $date_start, $date_end);
        $avg_solvedtime_stats = Stat::constructEntryValues($itemtype, 'inter_avgsolvedtime', $date_start, $date_end);
        $avg_closedtime_stats = Stat::constructEntryValues($itemtype, 'inter_avgclosedtime', $date_start, $date_end);
        $avg_actiontime_stats = Stat::constructEntryValues($itemtype, 'inter_avgactiontime', $date_start, $date_end);
        return new JSONResponse([
            'sample_dates' => array_keys($nb_open_stats),
            'number_open' => array_values($nb_open_stats),
            'number_solved' => array_values($nb_solved_stats),
            'number_late' => array_values($nb_late_stats),
            'number_closed' => array_values($nb_closed_stats),
            'satisfaction_surveys_open' => array_values($nb_opensatisfaction_stats),
            'satisfaction_surveys_answered' => array_values($nb_answersatisfaction_stats),
            'satisfaction_surveys_avg_rating' => array_map(static fn($v) => round((float) $v, 2), array_values($avg_satisfaction_stats)),
            'time_solve_avg' => array_map(static fn($v) => (int) $v, array_values($avg_solvedtime_stats)),
            'time_close_avg' => array_map(static fn($v) => (int) $v, array_values($avg_closedtime_stats)),
            'time_treatment_avg' => array_map(static fn($v) => (int) $v, array_values($avg_actiontime_stats)),
        ]);
    }

    #[Route(path: '/Assistance/Stat/{assistance_type}/Characteristics', methods: ['GET'], requirements: [
        'assistance_type' => 'Ticket|Change|Problem',
    ], tags: ['Statistics', 'Assistance'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get assistance statistics',
        parameters: [
            new Doc\Parameter(
                name: 'date_start',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING, format: Doc\Schema::FORMAT_STRING_DATE),
                description: 'The start date of the statistics',
                location: Doc\Parameter::LOCATION_QUERY,
                example: '2024-01-30'
            ),
            new Doc\Parameter(
                name: 'date_end',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING, format: Doc\Schema::FORMAT_STRING_DATE),
                description: 'The end date of the statistics',
                location: Doc\Parameter::LOCATION_QUERY,
                example: '2024-01-30'
            ),
            new Doc\Parameter(
                name: 'field',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING),
                description: 'The field to group the statistics by',
                location: Doc\Parameter::LOCATION_QUERY,
                required: true,
            ),
        ],
        responses: [
            new Doc\Response(schema: new Doc\SchemaReference('ITILStats[]')),
        ]
    )]
    public function getITILStats(Request $request): Response
    {
        $itemtype = $request->getAttribute('assistance_type');
        $date_end = $request->hasParameter('date_end')
            ? $request->getParameter('date_end')
            : date('Y-m-d', strtotime($_SESSION['glpi_currenttime']));
        $date_start = $request->hasParameter('date_start')
            ? $request->getParameter('date_start')
            : date('Y-m-d', strtotime('-1 year', strtotime($date_end)));
        $field = $request->getParameter('field');

        $items = Stat::getItems(
            $itemtype,
            $date_start,
            $date_end,
            $field,
        );

        $results = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $fn_get_stats = (static fn($stat, $field, $items_id) => Stat::constructEntryValues($itemtype, $stat, $date_start, $date_end, $field, $items_id, ''));

            $result = [];
            if (isset($item['itemtype'])) {
                $result['item'] = [
                    'id' => $item['id'],
                    'name' => Dropdown::getDropdownName($item['itemtype']::getTable(), $item['id'], false, true, true, ''),
                ];
            } else {
                $result['item'] = [
                    'id' => $item['id'],
                    'name' => $item['link'],
                ];
            }
            $nb_answersatisfaction_stats = $fn_get_stats('inter_answersatisfaction', $field, [$item['id']]);
            $avg_satisfaction_stats = $fn_get_stats('inter_avgsatisfaction', $field, [$item['id']]);
            foreach (array_keys($avg_satisfaction_stats) as $key2) {
                $avg_satisfaction_stats[$key2] *= $nb_answersatisfaction_stats[$key2];
            }
            $nb_answersatisfaction_stats_sum = array_sum($nb_answersatisfaction_stats);

            $nb_solved_stats = $fn_get_stats('inter_solved', $field, [$item['id']]);
            $avg_actiontime_stats = $fn_get_stats('inter_avgactiontime', $field, [$item['id']]);
            foreach (array_keys($avg_actiontime_stats) as $key2) {
                if (isset($nb_solved_stats[$key2])) {
                    $avg_actiontime_stats[$key2] *= $nb_solved_stats[$key2];
                } else {
                    $avg_actiontime_stats[$key2] *= 0;
                }
            }
            $total_actiontime = array_sum($avg_actiontime_stats);

            $results[] = $result + [
                'number_open' => array_sum($fn_get_stats('inter_total', $field, [$item['id']])),
                'number_solved' => array_sum($nb_solved_stats),
                'number_late' => array_sum($fn_get_stats('inter_solved_late', $field, [$item['id']])),
                'number_closed' => array_sum($fn_get_stats('inter_closed', $field, [$item['id']])),
                'satisfaction_surveys_open' => array_sum($fn_get_stats('inter_opensatisfaction', $field, [$item['id']])),
                'satisfaction_surveys_answered' => $nb_answersatisfaction_stats_sum,
                'satisfaction_surveys_avg_rating' => $nb_answersatisfaction_stats_sum > 0 ? round(array_sum($avg_satisfaction_stats) / $nb_answersatisfaction_stats_sum, 2) : 0,
                'time_take_into_account_avg' => array_sum($fn_get_stats('inter_avgactiontime', $field, [$item['id']])),
                'time_solve_avg' => array_sum($fn_get_stats('inter_avgsolvedtime', $field, [$item['id']])),
                'time_close_avg' => array_sum($fn_get_stats('inter_avgclosedtime', $field, [$item['id']])),
                'time_treatment_avg' => array_sum($avg_actiontime_stats),
                'time_treatment_total' => $total_actiontime,
            ];
        }

        return new JSONResponse($results);
    }

    #[Route(path: '/Assistance/Stat/{assistance_type}/Characteristics/Export', methods: ['GET'], requirements: [
        'assistance_type' => 'Ticket|Change|Problem',
    ], tags: ['Statistics', 'Assistance'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Export assistance statistics',
        parameters: [
            new Doc\Parameter(
                name: 'date_start',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING, format: Doc\Schema::FORMAT_STRING_DATE),
                description: 'The start date of the statistics',
                location: Doc\Parameter::LOCATION_QUERY,
                example: '2024-01-30'
            ),
            new Doc\Parameter(
                name: 'date_end',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING, format: Doc\Schema::FORMAT_STRING_DATE),
                description: 'The end date of the statistics',
                location: Doc\Parameter::LOCATION_QUERY,
                example: '2024-01-30'
            ),
            new Doc\Parameter(
                name: 'field',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING),
                description: 'The field to group the statistics by',
                location: Doc\Parameter::LOCATION_QUERY,
                required: true,
            ),
            new Doc\Parameter(
                name: 'Accept',
                schema: new Doc\Schema(
                    type: Doc\Schema::TYPE_STRING,
                    enum: [
                        'text/csv',
                        'application/vnd.oasis.opendocument.spreadsheet',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/pdf',
                    ]
                ),
                description: 'The format to export the statistics to',
                location: Doc\Parameter::LOCATION_HEADER,
            ),
        ]
    )]
    public function exportITILStats(Request $request): Response
    {
        $format = match ($request->getHeaderLine('Accept')) {
            'text/csv' => Search::CSV_OUTPUT,
            'application/vnd.oasis.opendocument.spreadsheet' => Search::ODS_OUTPUT,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => Search::XLSX_OUTPUT,
            default => Search::PDF_OUTPUT_LANDSCAPE,
        };
        $ext = match ($format) {
            Search::CSV_OUTPUT => 'csv',
            Search::ODS_OUTPUT => 'ods',
            Search::XLSX_OUTPUT => 'xlsx',
            default => 'pdf',
        };

        $itemtype = $request->getAttribute('assistance_type');
        $start = $request->hasParameter('date_start') ? $request->getParameter('date_start') : null;
        $end = $request->hasParameter('date_end') ? $request->getParameter('date_end') : null;
        $field = $request->getParameter('field');
        $value = Stat::getItems(
            $itemtype,
            $start,
            $end,
            $field
        );
        if (empty($start) && empty($end)) {
            $start = date("Y-m-d", mktime(1, 0, 0, (int) date("m"), (int) date("d"), ((int) date("Y")) - 1));
            $end = date("Y-m-d");
        }

        ob_start();
        $_GET['display_type'] = $format;
        $_GET['export_all'] = 1;
        Stat::showTable($itemtype, $field, $start, $end, 0, $value, 0);
        $export = ob_get_clean();
        $filename = 'assistance_stats_' . date('Y-m-d_H-i-s') . '.' . $ext;
        return new Response(200, [
            'Content-Type' => match ($format) {
                Search::CSV_OUTPUT => 'text/csv',
                Search::ODS_OUTPUT => 'application/vnd.oasis.opendocument.spreadsheet',
                Search::XLSX_OUTPUT => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                default => 'application/pdf',
            },
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ], $export);
    }

    #[Route(path: '/Assistance/Stat/{assistance_type}/Asset', methods: ['GET'], requirements: [
        'assistance_type' => 'Ticket|Change|Problem',
    ], tags: ['Statistics', 'Assistance'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get assistance statistics by asset',
        parameters: [
            new Doc\Parameter(
                name: 'date_start',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING, format: Doc\Schema::FORMAT_STRING_DATE),
                description: 'The start date of the statistics',
                location: Doc\Parameter::LOCATION_QUERY,
                example: '2024-01-30'
            ),
            new Doc\Parameter(
                name: 'date_end',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING, format: Doc\Schema::FORMAT_STRING_DATE),
                description: 'The end date of the statistics',
                location: Doc\Parameter::LOCATION_QUERY,
                example: '2024-01-30'
            ),
        ],
        responses: [
            new Doc\Response(schema: new Doc\SchemaReference('AssetStats[]')),
        ]
    )]
    public function getAssetStats(Request $request): Response
    {
        $itemtype = $request->getAttribute('assistance_type');
        $date_end = $request->hasParameter('date_end')
            ? $request->getParameter('date_end')
            : date('Y-m-d', strtotime($_SESSION['glpi_currenttime']));
        $date_start = $request->hasParameter('date_start')
            ? $request->getParameter('date_start')
            : date('Y-m-d', strtotime('-1 year', strtotime($date_end)));

        $assets = Stat::getAssetsWithITIL($date_start, $date_end, $itemtype);
        $results = [];

        foreach ($assets as $asset) {
            $results[] = [
                'item' => [
                    'itemtype' => $asset['itemtype'],
                    'id' => $asset['items_id'],
                    'name' => $asset['name'],
                    'entity' => [
                        'id' => $asset['entities_id'],
                        'name' => $asset['entity_name'],
                    ],
                    'is_deleted' => (bool) $asset['is_deleted'],
                ],
                'number_open' => $asset['NB'],
            ];
        }

        return new JSONResponse($results);
    }

    #[Route(path: '/Assistance/Stat/{assistance_type}/Asset/Export', methods: ['GET'], requirements: [
        'assistance_type' => 'Ticket|Change|Problem',
    ], tags: ['Statistics', 'Assistance'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Export assistance statistics by asset',
        parameters: [
            new Doc\Parameter(
                name: 'date_start',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING, format: Doc\Schema::FORMAT_STRING_DATE),
                description: 'The start date of the statistics',
                location: Doc\Parameter::LOCATION_QUERY,
                example: '2024-01-30'
            ),
            new Doc\Parameter(
                name: 'date_end',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING, format: Doc\Schema::FORMAT_STRING_DATE),
                description: 'The end date of the statistics',
                location: Doc\Parameter::LOCATION_QUERY,
                example: '2024-01-30'
            ),
            new Doc\Parameter(
                name: 'Accept',
                schema: new Doc\Schema(
                    type: Doc\Schema::TYPE_STRING,
                    enum: [
                        'text/csv',
                        'application/vnd.oasis.opendocument.spreadsheet',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/pdf',
                    ]
                ),
                description: 'The format to export the statistics to',
                location: Doc\Parameter::LOCATION_HEADER,
            ),
        ]
    )]
    public function exportAssetStats(Request $request): Response
    {
        $format = match ($request->getHeaderLine('Accept')) {
            'text/csv' => Search::CSV_OUTPUT,
            'application/vnd.oasis.opendocument.spreadsheet' => Search::ODS_OUTPUT,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => Search::XLSX_OUTPUT,
            default => Search::PDF_OUTPUT_LANDSCAPE,
        };
        $ext = match ($format) {
            Search::CSV_OUTPUT => 'csv',
            Search::ODS_OUTPUT => 'ods',
            Search::XLSX_OUTPUT => 'xlsx',
            default => 'pdf',
        };

        $itemtype = $request->getAttribute('assistance_type');
        $start = $request->hasParameter('date_start') ? $request->getParameter('date_start') : null;
        $end = $request->hasParameter('date_end') ? $request->getParameter('date_end') : null;
        $value = Stat::getItems(
            $itemtype,
            $start,
            $end,
            'hardwares'
        );
        if (empty($start) && empty($end)) {
            $start = date("Y-m-d", mktime(1, 0, 0, (int) date("m"), (int) date("d"), ((int) date("Y")) - 1));
            $end = date("Y-m-d");
        }

        ob_start();
        $_GET['display_type'] = $format;
        $_GET['export_all'] = 1;
        Stat::showTable($itemtype, 'hardwares', $start, $end, 0, $value, 0);
        $export = ob_get_clean();
        $filename = 'assistance_asset_stats_' . date('Y-m-d_H-i-s') . '.' . $ext;
        return new Response(200, [
            'Content-Type' => match ($format) {
                Search::CSV_OUTPUT => 'text/csv',
                Search::ODS_OUTPUT => 'application/vnd.oasis.opendocument.spreadsheet',
                Search::XLSX_OUTPUT => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                default => 'application/pdf',
            },
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ], $export);
    }

    #[Route(path: '/Assistance/Stat/{assistance_type}/AssetCharacteristics', methods: ['GET'], requirements: [
        'assistance_type' => 'Ticket|Change|Problem',
    ], tags: ['Statistics', 'Assistance'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get assistance statistics by asset characteristics',
        parameters: [
            new Doc\Parameter(
                name: 'date_start',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING, format: Doc\Schema::FORMAT_STRING_DATE),
                description: 'The start date of the statistics',
                location: Doc\Parameter::LOCATION_QUERY,
                example: '2024-01-30'
            ),
            new Doc\Parameter(
                name: 'date_end',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING, format: Doc\Schema::FORMAT_STRING_DATE),
                description: 'The end date of the statistics',
                location: Doc\Parameter::LOCATION_QUERY,
                example: '2024-01-30'
            ),
            new Doc\Parameter(
                name: 'field',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING),
                description: 'The characteristic field to group the statistics by',
                location: Doc\Parameter::LOCATION_QUERY,
                required: true,
            ),
        ],
        responses: [
            new Doc\Response(schema: new Doc\SchemaReference('AssetCharacteristicsStats[]')),
        ]
    )]
    public function getAssetCharacteristicsStats(Request $request): Response
    {
        $itemtype = $request->getAttribute('assistance_type');
        $date_end = $request->hasParameter('date_end')
            ? $request->getParameter('date_end')
            : date('Y-m-d', strtotime($_SESSION['glpi_currenttime']));
        $date_start = $request->hasParameter('date_start')
            ? $request->getParameter('date_start')
            : date('Y-m-d', strtotime('-1 year', strtotime($date_end)));
        $field = $request->getParameter('field');

        $items = Stat::getItems(
            $itemtype,
            $date_start,
            $date_end,
            $field,
        );
        $param_item = getItemForItemtype($field);
        if (!$param_item) {
            return self::getInvalidParametersErrorResponse([
                'invalid' => [
                    ['name' => 'field'],
                ],
            ]);
        }
        $param = $param_item instanceof CommonDevice ? 'device' : 'comp_champ';

        $results = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $fn_get_stats = (static fn($stat, $field, $items_id) => Stat::constructEntryValues($itemtype, $stat, $date_start, $date_end, $param, $items_id, $field));

            $result = [];
            if (isset($item['itemtype'])) {
                $result['item'] = [
                    'itemtype' => $param_item::getType(),
                    'id' => $item['id'],
                    'name' => Dropdown::getDropdownName($item['itemtype']::getTable(), $item['id'], false, true, true, ''),
                ];
            } else {
                $result['item'] = [
                    'itemtype' => $param_item::getType(),
                    'id' => $item['id'],
                    'name' => $item['link'],
                ];
            }
            $nb_answersatisfaction_stats = $fn_get_stats('inter_answersatisfaction', $field, [$item['id']]);
            $avg_satisfaction_stats = $fn_get_stats('inter_avgsatisfaction', $field, [$item['id']]);
            foreach (array_keys($avg_satisfaction_stats) as $key2) {
                $avg_satisfaction_stats[$key2] *= $nb_answersatisfaction_stats[$key2];
            }
            $nb_answersatisfaction_stats_sum = array_sum($nb_answersatisfaction_stats);

            $nb_solved_stats = $fn_get_stats('inter_solved', $field, [$item['id']]);
            $avg_actiontime_stats = $fn_get_stats('inter_avgactiontime', $field, [$item['id']]);
            foreach (array_keys($avg_actiontime_stats) as $key2) {
                if (isset($nb_solved_stats[$key2])) {
                    $avg_actiontime_stats[$key2] *= $nb_solved_stats[$key2];
                } else {
                    $avg_actiontime_stats[$key2] *= 0;
                }
            }
            $total_actiontime = array_sum($avg_actiontime_stats);

            $results[] = $result + [
                'number_open' => array_sum($fn_get_stats('inter_total', $field, [$item['id']])),
                'number_solved' => array_sum($nb_solved_stats),
                'number_late' => array_sum($fn_get_stats('inter_solved_late', $field, [$item['id']])),
                'number_closed' => array_sum($fn_get_stats('inter_closed', $field, [$item['id']])),
                'satisfaction_surveys_open' => array_sum($fn_get_stats('inter_opensatisfaction', $field, [$item['id']])),
                'satisfaction_surveys_answered' => $nb_answersatisfaction_stats_sum,
                'satisfaction_surveys_avg_rating' => $nb_answersatisfaction_stats_sum > 0 ? round(array_sum($avg_satisfaction_stats) / $nb_answersatisfaction_stats_sum, 2) : 0,
                'time_take_into_account_avg' => array_sum($fn_get_stats('inter_avgactiontime', $field, [$item['id']])),
                'time_solve_avg' => array_sum($fn_get_stats('inter_avgsolvedtime', $field, [$item['id']])),
                'time_close_avg' => array_sum($fn_get_stats('inter_avgclosedtime', $field, [$item['id']])),
                'time_treatment_avg' => array_sum($avg_actiontime_stats),
                'time_treatment_total' => $total_actiontime,
            ];
        }

        return new JSONResponse($results);
    }

    #[Route(path: '/Assistance/Stat/{assistance_type}/AssetCharacteristics/Export', methods: ['GET'], requirements: [
        'assistance_type' => 'Ticket|Change|Problem',
    ], tags: ['Statistics', 'Assistance'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Export assistance statistics by asset characteristics',
        parameters: [
            new Doc\Parameter(
                name: 'date_start',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING, format: Doc\Schema::FORMAT_STRING_DATE),
                description: 'The start date of the statistics',
                location: Doc\Parameter::LOCATION_QUERY,
                example: '2024-01-30'
            ),
            new Doc\Parameter(
                name: 'date_end',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING, format: Doc\Schema::FORMAT_STRING_DATE),
                description: 'The end date of the statistics',
                location: Doc\Parameter::LOCATION_QUERY,
                example: '2024-01-30'
            ),
            new Doc\Parameter(
                name: 'field',
                schema: new Doc\Schema(type: Doc\Schema::TYPE_STRING),
                description: 'The field to group the statistics by',
                location: Doc\Parameter::LOCATION_QUERY,
                required: true,
            ),
            new Doc\Parameter(
                name: 'Accept',
                schema: new Doc\Schema(
                    type: Doc\Schema::TYPE_STRING,
                    enum: [
                        'text/csv',
                        'application/vnd.oasis.opendocument.spreadsheet',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/pdf',
                    ]
                ),
                description: 'The format to export the statistics to',
                location: Doc\Parameter::LOCATION_HEADER,
            ),
        ]
    )]
    public function exportAssetCharacteristicsStats(Request $request): Response
    {
        $format = match ($request->getHeaderLine('Accept')) {
            'text/csv' => Search::CSV_OUTPUT,
            'application/vnd.oasis.opendocument.spreadsheet' => Search::ODS_OUTPUT,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => Search::XLSX_OUTPUT,
            default => Search::PDF_OUTPUT_LANDSCAPE,
        };
        $ext = match ($format) {
            Search::CSV_OUTPUT => 'csv',
            Search::ODS_OUTPUT => 'ods',
            Search::XLSX_OUTPUT => 'xlsx',
            default => 'pdf',
        };

        $itemtype = $request->getAttribute('assistance_type');
        $start = $request->hasParameter('date_start') ? $request->getParameter('date_start') : null;
        $end = $request->hasParameter('date_end') ? $request->getParameter('date_end') : null;
        $field = $request->getParameter('field');
        $value = Stat::getItems(
            $itemtype,
            $start,
            $end,
            $field
        );
        if (empty($start) && empty($end)) {
            $start = date("Y-m-d", mktime(1, 0, 0, (int) date("m"), (int) date("d"), ((int) date("Y")) - 1));
            $end = date("Y-m-d");
        }

        ob_start();
        $_GET['display_type'] = $format;
        $_GET['export_all'] = 1;
        Stat::showTable($itemtype, $field, $start, $end, 0, $value, 0);
        $export = ob_get_clean();
        $filename = 'assistance_asset_characteristics_stats_' . date('Y-m-d_H-i-s') . '.' . $ext;
        return new Response(200, [
            'Content-Type' => match ($format) {
                Search::CSV_OUTPUT => 'text/csv',
                Search::ODS_OUTPUT => 'application/vnd.oasis.opendocument.spreadsheet',
                Search::XLSX_OUTPUT => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                default => 'application/pdf',
            },
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ], $export);
    }
}
