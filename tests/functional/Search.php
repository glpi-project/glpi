<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace tests\units;

use CommonDBTM;
use CommonITILActor;
use DBConnection;
use DbTestCase;
use Glpi\Toolbox\Sanitizer;
use Ticket;

/* Test for inc/search.class.php */

/**
 * @engine isolate
 */
class Search extends DbTestCase
{
    private function doSearch($itemtype, $params, array $forcedisplay = [])
    {
        global $DEBUG_SQL;

       // check param itemtype exists (to avoid search errors)
        if ($itemtype !== 'AllAssets') {
            $this->class($itemtype)->isSubClassof('CommonDBTM');
        }

       // login to glpi if needed
        if (!isset($_SESSION['glpiname'])) {
            $this->login();
        }

       // force session in debug mode (to store & retrieve sql errors)
        $glpi_use_mode             = $_SESSION['glpi_use_mode'];
        $_SESSION['glpi_use_mode'] = \Session::DEBUG_MODE;

       // don't compute last request from session
        $params['reset'] = 'reset';

       // do search
        $params = \Search::manageParams($itemtype, $params);
        $data   = \Search::getDatas($itemtype, $params, $forcedisplay);

       // append existing errors to returned data
        $data['last_errors'] = [];
        if (isset($DEBUG_SQL['errors'])) {
            $data['last_errors'] = implode(', ', $DEBUG_SQL['errors']);
            unset($DEBUG_SQL['errors']);
        }

       // restore glpi mode to previous
        $_SESSION['glpi_use_mode'] = $glpi_use_mode;

       // do not store this search from session
        \Search::resetSaveSearch();

        $this->checkSearchResult($data);

        return $data;
    }

    public function testMetaComputerOS()
    {
        $search_params = ['is_deleted'   => 0,
            'start'        => 0,
            'criteria'     => [0 => ['field'      => 'view',
                'searchtype' => 'contains',
                'value'      => ''
            ]
            ],
            'metacriteria' => [0 => ['link'       => 'AND',
                'itemtype'   => 'OperatingSystem',
                'field'      => 1, //name
                'searchtype' => 'contains',
                'value'      => 'windows'
            ]
            ]
        ];

        $data = $this->doSearch('Computer', $search_params);

       //try to find LEFT JOIN clauses
        $this->string($data['sql']['search'])
         ->matches("/"
         . "LEFT\s*JOIN\s*`glpi_items_operatingsystems`\s*AS\s*`glpi_items_operatingsystems_OperatingSystem`\s*"
         . "ON\s*\(`glpi_items_operatingsystems_OperatingSystem`\.`items_id`\s*=\s*`glpi_computers`\.`id`\s*"
         . "AND `glpi_items_operatingsystems_OperatingSystem`\.`itemtype`\s*=\s*'Computer'\s*"
         . "AND `glpi_items_operatingsystems_OperatingSystem`\.`is_deleted`\s*=\s*0\s*\)\s*"
         . "LEFT\s*JOIN\s*`glpi_operatingsystems`\s*"
         . "ON\s*\(`glpi_items_operatingsystems_OperatingSystem`\.`operatingsystems_id`\s*=\s*`glpi_operatingsystems`\.`id`\s*\)"
         . "/im");

       //try to match WHERE clause
        $this->string($data['sql']['search'])
         ->matches("/(\(`glpi_operatingsystems`\.`name`\s*LIKE\s*'%windows%'\s*\)\s*\))/im");
    }


    public function testMetaComputerSoftwareLicense()
    {
        $search_params = ['is_deleted'   => 0,
            'start'        => 0,
            'criteria'     => [0 => ['field'      => 'view',
                'searchtype' => 'contains',
                'value'      => ''
            ]
            ],
            'metacriteria' => [0 => ['link'       => 'AND',
                'itemtype'   => 'Software',
                'field'      => 163,
                'searchtype' => 'contains',
                'value'      => '>0'
            ],
                1 => ['link'       => 'AND',
                    'itemtype'   => 'Software',
                    'field'      => 160,
                    'searchtype' => 'contains',
                    'value'      => 'firefox'
                ]
            ]
        ];

        $data = $this->doSearch('Computer', $search_params);

        $this->string($data['sql']['search'])
         ->matches('/'
            . 'LEFT JOIN\s*`glpi_items_softwareversions`\s*AS\s*`glpi_items_softwareversions_[^`]+_Software`\s*ON\s*\('
            . '`glpi_items_softwareversions_[^`]+_Software`\.`items_id`\s*=\s*`glpi_computers`.`id`'
            . '\s*AND\s*`glpi_items_softwareversions_[^`]+_Software`\.`itemtype`\s*=\s*\'Computer\''
            . '\s*AND\s*`glpi_items_softwareversions_[^`]+_Software`\.`is_deleted`\s*=\s*0'
            . '\)/im');
    }

    public function testSoftwareLinkedToAnyComputer()
    {
        $search_params = [
            'is_deleted'   => 0,
            'start'        => 0,
            'criteria'     => [
                [
                    'field'      => 'view',
                    'searchtype' => 'contains',
                    'value'      => '',
                ],
            ],
            'metacriteria' => [
                [
                    'link'       => 'AND NOT',
                    'itemtype'   => 'Computer',
                    'field'      => 2,
                    'searchtype' => 'contains',
                    'value'      => '^$', // search for "null" id
                ],
            ],
        ];

        $data = $this->doSearch('Software', $search_params);

        $this->string($data['sql']['search'])
         ->matches("/HAVING\s*\(`ITEM_Computer_2`\s+IS\s+NOT\s+NULL\s*\)/");
    }

    public function testMetaComputerUser()
    {
        $search_params = ['is_deleted'   => 0,
            'start'        => 0,
            'search'       => 'Search',
            'criteria'     => [0 => ['field'      => 'view',
                'searchtype' => 'contains',
                'value'      => ''
            ]
            ],
                                           // user login
            'metacriteria' => [0 => ['link'       => 'AND',
                'itemtype'   => 'User',
                'field'      => 1,
                'searchtype' => 'equals',
                'value'      => 2
            ],
                                           // user profile
                1 => ['link'       => 'AND',
                    'itemtype'   => 'User',
                    'field'      => 20,
                    'searchtype' => 'equals',
                    'value'      => 4
                ],
                                           // user entity
                2 => ['link'       => 'AND',
                    'itemtype'   => 'User',
                    'field'      => 80,
                    'searchtype' => 'equals',
                    'value'      => 0
                ],
                                           // user profile
                3 => ['link'       => 'AND',
                    'itemtype'   => 'User',
                    'field'      => 13,
                    'searchtype' => 'equals',
                    'value'      => 1
                ]
            ]
        ];

        $this->doSearch('Computer', $search_params);
    }

    public function testSubMetaTicketComputer()
    {
        $search_params = [
            'is_deleted'   => 0,
            'start'        => 0,
            'search'       => 'Search',
            'criteria'     => [
                0 => [
                    'field'      => 12,
                    'searchtype' => 'equals',
                    'value'      => 'notold'
                ],
                1 => [
                    'link'       => 'AND',
                    'criteria'   => [
                        0 => [
                            'field'      => 'view',
                            'searchtype' => 'contains',
                            'value'      => 'test1'
                        ],
                        1 => [
                            'link'       => 'OR',
                            'field'      => 'view',
                            'searchtype' => 'contains',
                            'value'      => 'test2'
                        ],
                        2 => [
                            'link'       => 'OR',
                            'meta'       => true,
                            'itemtype'   => 'Computer',
                            'field'      => 1,
                            'searchtype' => 'contains',
                            'value'      => 'test3'
                        ],
                    ]
                ],
            ],
        ];

        $this->doSearch('Ticket', $search_params);
    }

    public function testFlagMetaComputerUser()
    {
        $search_params = [
            'reset'        => 'reset',
            'is_deleted'   => 0,
            'start'        => 0,
            'search'       => 'Search',
            'criteria'     => [
                0 => [
                    'field'      => 'view',
                    'searchtype' => 'contains',
                    'value'      => ''
                ],
            // user login
                1 => [
                    'link'       => 'AND',
                    'itemtype'   => 'User',
                    'field'      => 1,
                    'meta'       => 1,
                    'searchtype' => 'equals',
                    'value'      => 2
                ],
            // user profile
                2 => [
                    'link'       => 'AND',
                    'itemtype'   => 'User',
                    'field'      => 20,
                    'meta'       => 1,
                    'searchtype' => 'equals',
                    'value'      => 4
                ],
            // user entity
                3 => [
                    'link'       => 'AND',
                    'itemtype'   => 'User',
                    'field'      => 80,
                    'meta'       => 1,
                    'searchtype' => 'equals',
                    'value'      => 0
                ],
            // user profile
                4 => [
                    'link'       => 'AND',
                    'itemtype'   => 'User',
                    'field'      => 13,
                    'meta'       => 1,
                    'searchtype' => 'equals',
                    'value'      => 1
                ]
            ]
        ];

        $data = $this->doSearch('Computer', $search_params);

        $this->string($data['sql']['search'])
         ->contains("LEFT JOIN  `glpi_users`")
         ->contains("LEFT JOIN `glpi_profiles`  AS `glpi_profiles_")
         ->contains("LEFT JOIN `glpi_entities`  AS `glpi_entities_");
    }

    public function testNestedAndMetaComputer()
    {
        $search_params = [
            'reset'      => 'reset',
            'is_deleted' => 0,
            'start'      => 0,
            'search'     => 'Search',
            'criteria'   => [
                [
                    'link'       => 'AND',
                    'field'      => 1,
                    'searchtype' => 'contains',
                    'value'      => 'test',
                ], [
                    'link'       => 'AND',
                    'itemtype'   => 'Software',
                    'meta'       => 1,
                    'field'      => 1,
                    'searchtype' => 'equals',
                    'value'      => 10784,
                ], [
                    'link'       => 'OR',
                    'criteria'   => [
                        [
                            'link'       => 'AND',
                            'field'      => 2,
                            'searchtype' => 'contains',
                            'value'      => 'test',
                        ], [
                            'link'       => 'OR',
                            'field'      => 2,
                            'searchtype' => 'contains',
                            'value'      => 'test2',
                        ], [
                            'link'       => 'AND',
                            'field'      => 3,
                            'searchtype' => 'equals',
                            'value'      => 11,
                        ], [
                            'link'       => 'AND',
                            'criteria'   => [
                                [
                                    'field'      => 70,
                                    'searchtype' => 'equals',
                                    'value'      => 2,
                                ], [
                                    'link'       => 'OR',
                                    'field'      => 70,
                                    'searchtype' => 'equals',
                                    'value'      => 3,
                                ]
                            ]
                        ]
                    ]
                ], [
                    'link'       => 'AND NOT',
                    'itemtype'   => 'Budget',
                    'meta'       => 1,
                    'field'      => 2,
                    'searchtype' => 'contains',
                    'value'      => 5,
                ], [
                    'link'       => 'AND NOT',
                    'itemtype'   => 'Printer',
                    'meta'       => 1,
                    'field'      => 1,
                    'searchtype' => 'contains',
                    'value'      => 'HP',
                ]
            ]
        ];

        $data = $this->doSearch('Computer', $search_params);

        $this->string($data['sql']['search'])
         // join parts
         ->matches('/LEFT JOIN\s*`glpi_items_softwareversions`\s*AS `glpi_items_softwareversions_Software`/im')
         ->matches('/LEFT JOIN\s*`glpi_softwareversions`\s*AS `glpi_softwareversions_Software`/im')
         ->matches('/LEFT JOIN\s*`glpi_softwares`\s*ON\s*\(`glpi_softwareversions_Software`\.`softwares_id`\s*=\s*`glpi_softwares`\.`id`\)/im')
         ->matches('/LEFT JOIN\s*`glpi_infocoms`\s*AS\s*`glpi_infocoms_Budget`\s*ON\s*\(`glpi_computers`\.`id`\s*=\s*`glpi_infocoms_Budget`\.`items_id`\s*AND\s*`glpi_infocoms_Budget`.`itemtype`\s*=\s*\'Computer\'\)/im')
         ->matches('/LEFT JOIN\s*`glpi_budgets`\s*ON\s*\(`glpi_infocoms_Budget`\.`budgets_id`\s*=\s*`glpi_budgets`\.`id`/im')
         ->matches('/LEFT JOIN\s*`glpi_computers_items`\s*AS `glpi_computers_items_Printer`\s*ON\s*\(`glpi_computers_items_Printer`\.`computers_id`\s*=\s*`glpi_computers`\.`id`\s*AND\s*`glpi_computers_items_Printer`.`itemtype`\s*=\s*\'Printer\'\s*AND\s*`glpi_computers_items_Printer`.`is_deleted`\s*=\s*0\)/im')
         ->matches('/LEFT JOIN\s*`glpi_printers`\s*ON\s*\(`glpi_computers_items_Printer`\.`items_id`\s*=\s*`glpi_printers`\.`id`/im')
         // match where parts
         ->contains("`glpi_computers`.`is_deleted` = 0")
         ->contains("AND `glpi_computers`.`is_template` = 0")
         ->contains("`glpi_computers`.`entities_id` IN ('1', '2', '3')")
         ->contains("OR (`glpi_computers`.`is_recursive`='1'" .
                    " AND `glpi_computers`.`entities_id` IN (0))")
         ->contains("`glpi_computers`.`name`  LIKE '%test%'")
         ->contains("AND (`glpi_softwares`.`id` = '10784')")
         ->contains("OR (`glpi_computers`.`id`  LIKE '%test2%'")
         ->contains("AND (`glpi_locations`.`id` = '11')")
         ->contains("(`glpi_users`.`id` = '2')")
         ->contains("OR (`glpi_users`.`id` = '3')")
         // match having
         ->matches("/HAVING\s*\(`ITEM_Budget_2`\s+<>\s+5\)\s+AND\s+\(\(`ITEM_Printer_1`\s+NOT LIKE\s+'%HP%'\s+OR\s+`ITEM_Printer_1`\s+IS NULL\)\s*\)/");
    }

    public function testViewCriterion()
    {
        $data = $this->doSearch('Computer', [
            'reset'      => 'reset',
            'is_deleted' => 0,
            'start'      => 0,
            'search'     => 'Search',
            'criteria'   => [
                [
                    'link'       => 'AND',
                    'field'      => 'view',
                    'searchtype' => 'contains',
                    'value'      => 'test',
                ],
            ]
        ]);

        $default_charset = DBConnection::getDefaultCharset();

        $this->string($data['sql']['search'])
         ->contains("`glpi_computers`.`is_deleted` = 0")
         ->contains("AND `glpi_computers`.`is_template` = 0")
         ->contains("`glpi_computers`.`entities_id` IN ('1', '2', '3')")
         ->contains("OR (`glpi_computers`.`is_recursive`='1'" .
                    " AND `glpi_computers`.`entities_id` IN (0))")
         ->matches("/`glpi_computers`\.`name`  LIKE '%test%'/")
         ->matches("/OR\s*\(`glpi_entities`\.`completename`\s*LIKE '%test%'\s*\)/")
         ->matches("/OR\s*\(`glpi_states`\.`completename`\s*LIKE '%test%'\s*\)/")
         ->matches("/OR\s*\(`glpi_manufacturers`\.`name`\s*LIKE '%test%'\s*\)/")
         ->matches("/OR\s*\(`glpi_computers`\.`serial`\s*LIKE '%test%'\s*\)/")
         ->matches("/OR\s*\(`glpi_computertypes`\.`name`\s*LIKE '%test%'\s*\)/")
         ->matches("/OR\s*\(`glpi_computermodels`\.`name`\s*LIKE '%test%'\s*\)/")
         ->matches("/OR\s*\(`glpi_locations`\.`completename`\s*LIKE '%test%'\s*\)/")
         ->matches("/OR\s*\(CONVERT\(`glpi_computers`\.`date_mod` USING {$default_charset}\)\s*LIKE '%test%'\s*\)\)/");
    }

    public function testSearchOnRelationTable()
    {
        $data = $this->doSearch(\Change_Ticket::class, [
            'reset'      => 'reset',
            'is_deleted' => 0,
            'start'      => 0,
            'search'     => 'Search',
            'criteria'   => [
                [
                    'link'       => 'AND',
                    'field'      => '3',
                    'searchtype' => 'equals',
                    'value'      => '1',
                ],
            ]
        ]);

        $this->string($data['sql']['search'])
         ->contains("`glpi_changes`.`id` AS `ITEM_Change_Ticket_3`")
         ->contains("`glpi_changes_tickets`.`changes_id` = `glpi_changes`.`id`")
         ->contains("`glpi_changes`.`id` = '1'");
    }

    public function testUser()
    {
        $search_params = ['is_deleted'   => 0,
            'start'        => 0,
            'search'       => 'Search',
                                                     // profile
            'criteria'     => [0 => ['field'      => '20',
                'searchtype' => 'contains',
                'value'      => 'super-admin'
            ],
                                           // login
                1 => ['link'       => 'AND',
                    'field'      => '1',
                    'searchtype' => 'contains',
                    'value'      => 'glpi'
                ],
                                           // entity
                2 => ['link'       => 'AND',
                    'field'      => '80',
                    'searchtype' => 'equals',
                    'value'      => 0
                ],
                                           // is not not active
                3 => ['link'       => 'AND',
                    'field'      => '8',
                    'searchtype' => 'notequals',
                    'value'      => 0
                ]
            ]
        ];
        $data = $this->doSearch('User', $search_params);

       //expecting one result
        $this->integer($data['data']['totalcount'])->isIdenticalTo(1);
    }

    /**
     * This test will add all searchoptions in each itemtype and check if the
     * search give a SQL error
     *
     * @return void
     */
    public function testSearchOptions()
    {
        $classes = $this->getSearchableClasses();
        foreach ($classes as $class) {
            $item = new $class();

           //load all options; so rawSearchOptionsToAdd to be tested
            $options = \Search::getCleanedOptions($item->getType());

            $multi_criteria = [];
            foreach ($options as $key => $data) {
                if (!is_int($key) || ($criterion_params = $this->getCriterionParams($item, $key, $data)) === null) {
                    continue;
                }

                // do a search query based on current search option
                $this->doSearch(
                    $class,
                    [
                        'is_deleted'   => 0,
                        'start'        => 0,
                        'criteria'     => [$criterion_params],
                        'metacriteria' => []
                    ]
                );

                $multi_criteria[] = $criterion_params;

                if (count($multi_criteria) > 50) {
                     // Limit criteria count to 50 to prevent performances issues
                     // and also prevent exceeding of MySQL join limit.
                     break;
                }
            }

          // do a search query with all criteria at the same time
            $search_params = ['is_deleted'   => 0,
                'start'        => 0,
                'criteria'     => $multi_criteria,
                'metacriteria' => []
            ];
            $this->doSearch($class, $search_params);
        }
    }

    /**
     * Test search with all meta to not have SQL errors
     *
     * @return void
     */
    public function testSearchAllMeta()
    {

        $classes = $this->getSearchableClasses();

       // extract metacriteria
        $itemtype_criteria = [];
        foreach ($classes as $class) {
            $itemtype = $class::getType();
            $itemtype_criteria[$itemtype] = [];
            $metaList = \Search::getMetaItemtypeAvailable($itemtype);
            foreach ($metaList as $metaitemtype) {
                $item = getItemForItemtype($metaitemtype);
                foreach ($item->searchOptions() as $key => $data) {
                    if (is_array($data) && array_key_exists('nometa', $data) && $data['nometa'] === true) {
                        continue;
                    }
                    if (!is_int($key) || ($criterion_params = $this->getCriterionParams($item, $key, $data)) === null) {
                        continue;
                    }

                    $criterion_params['itemtype'] = $metaitemtype;
                    $criterion_params['link'] = 'AND';

                    $itemtype_criteria[$itemtype][] = $criterion_params;
                }
            }
        }

        foreach ($itemtype_criteria as $itemtype => $criteria) {
            if (empty($criteria)) {
                continue;
            }

            $first_criteria_by_metatype = [];

           // Search with each meta criteria independently.
            foreach ($criteria as $criterion_params) {
                if (!array_key_exists($criterion_params['itemtype'], $first_criteria_by_metatype)) {
                    $first_criteria_by_metatype[$criterion_params['itemtype']] = $criterion_params;
                }

                $search_params = ['is_deleted'   => 0,
                    'start'        => 0,
                    'criteria'     => [0 => ['field'      => 'view',
                        'searchtype' => 'contains',
                        'value'      => ''
                    ]
                    ],
                    'metacriteria' => [$criterion_params]
                ];
                $this->doSearch($itemtype, $search_params);
            }

           // Search with criteria related to multiple meta items.
           // Limit criteria count to 5 to prevent performances issues (mainly on MariaDB).
           // Test would take hours if done using too many criteria on each request.
           // Thus, using 5 different meta items on a request seems already more than a normal usage.
            foreach (array_chunk($first_criteria_by_metatype, 3) as $criteria_chunk) {
                $search_params = ['is_deleted'   => 0,
                    'start'        => 0,
                    'criteria'     => [0 => ['field'      => 'view',
                        'searchtype' => 'contains',
                        'value'      => ''
                    ]
                    ],
                    'metacriteria' => $criteria_chunk
                ];
                $this->doSearch($itemtype, $search_params);
            }
        }
    }

    /**
     * Get criterion params for corresponding SO.
     *
     * @param CommonDBTM $item
     * @param int $so_key
     * @param array $so_data
     * @return null|array
     */
    private function getCriterionParams(CommonDBTM $item, int $so_key, array $so_data): ?array
    {
        global $DB;

        if ((array_key_exists('nosearch', $so_data) && $so_data['nosearch'])) {
            return null;
        }
        $actions = \Search::getActionsFor($item->getType(), $so_key);
        $searchtype = array_keys($actions)[0];

        switch ($so_data['datatype'] ?? null) {
            case 'bool':
            case 'integer':
            case 'number':
                $val = 0;
                break;
            case 'date':
            case 'date_delay':
                $val = date('Y-m-d');
                break;
            case 'datetime':
               // Search class expects seconds to be ":00".
                $val = date('Y-m-d H:i:00');
                break;
            case 'right':
                $val = READ;
                break;
            default:
                if (array_key_exists('table', $so_data) && array_key_exists('field', $so_data)) {
                    $field = $DB->tableExists($so_data['table']) ? $DB->getField($so_data['table'], $so_data['field']) : null;
                    if (preg_match('/int(\(\d+\))?( unsigned)?$/', $field['Type'] ?? '')) {
                        $val = 1;
                        break;
                    }
                }

                $val = 'val';
                break;
        }

        return [
            'field'      => $so_key,
            'searchtype' => $searchtype,
            'value'      => $val
        ];
    }

    public function testIsNotifyComputerGroup()
    {
        $search_params = ['is_deleted'   => 0,
            'start'        => 0,
            'search'       => 'Search',
            'criteria'     => [0 => ['field'      => 'view',
                'searchtype' => 'contains',
                'value'      => ''
            ]
            ],
                                                     // group is_notify
            'metacriteria' => [0 => ['link'       => 'AND',
                'itemtype'   => 'Group',
                'field'      => 20,
                'searchtype' => 'equals',
                'value'      => 1
            ]
            ]
        ];
        $this->login();
        $this->setEntity('_test_root_entity', true);

        $data = $this->doSearch('Computer', $search_params);

       //expecting no result
        $this->integer($data['data']['totalcount'])->isIdenticalTo(0);

        $computer1 = getItemByTypeName('Computer', '_test_pc01');

       //create group that can be notified
        $group = new \Group();
        $gid = $group->add(
            [
                'name'         => '_test_group01',
                'is_notify'    => '1',
                'entities_id'  => $computer1->fields['entities_id'],
                'is_recursive' => 1
            ]
        );
        $this->integer($gid)->isGreaterThan(0);

       //attach group to computer
        $updated = $computer1->update(
            [
                'id'        => $computer1->getID(),
                'groups_id' => $gid
            ]
        );
        $this->boolean($updated)->isTrue();

        $data = $this->doSearch('Computer', $search_params);

       //reset computer
        $updated = $computer1->update(
            [
                'id'        => $computer1->getID(),
                'groups_id' => 0
            ]
        );
        $this->boolean($updated)->isTrue();

        $this->integer($data['data']['totalcount'])->isIdenticalTo(1);
    }

    public function testDateBeforeOrNot()
    {
       //tickets created since one week
        $search_params = [
            'is_deleted'   => 0,
            'start'        => 0,
            'criteria'     => [
                0 => [
                    'field'      => 'view',
                    'searchtype' => 'contains',
                    'value'      => ''
                ],
            // creation date
                1 => [
                    'link'       => 'AND',
                    'field'      => '15',
                    'searchtype' => 'morethan',
                    'value'      => '-1WEEK'
                ]
            ]
        ];

        $data = $this->doSearch('Ticket', $search_params);

        $this->integer($data['data']['totalcount'])->isGreaterThan(1);

       //negate previous search
        $search_params['criteria'][1]['link'] = 'AND NOT';
        $data = $this->doSearch('Ticket', $search_params);

        $this->integer($data['data']['totalcount'])->isIdenticalTo(0);
    }

    /**
     * Test that searchOptions throws an exception when it finds a duplicate
     *
     * @return void
     */
    public function testGetSearchOptionsWException()
    {
        $error = 'Duplicate key 12 (One search option/Any option) in tests\units\DupSearchOpt searchOptions!';

        $this->when(
            function () {
                $item = new DupSearchOpt();
                $item->searchOptions();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage($error)
         ->exists();
    }

    public function testManageParams()
    {
       // let's use TU_USER
        $this->login();
        $uid =  getItemByTypeName('User', TU_USER, true);

        $search = \Search::manageParams('Ticket', ['reset' => 1], false, false);
        $this->array(
            $search
        )->isEqualTo([
            'reset'        => 1,
            'start'        => 0,
            'order'        => 'DESC',
            'sort'         => 19,
            'is_deleted'   => 0,
            'criteria'     => [
                0 => [
                    'field' => 12,
                    'searchtype' => 'equals',
                    'value' => 'notold'
                ],
            ],
            'metacriteria' => [],
            'as_map'       => 0,
            'browse'       => 0,
        ]);

       // now add a bookmark on Ticket view
        $bk = new \SavedSearch();
        $this->boolean(
            (bool)$bk->add(['name'         => 'All my tickets',
                'type'         => 1,
                'itemtype'     => 'Ticket',
                'users_id'     => $uid,
                'is_private'   => 1,
                'entities_id'  => 0,
                'is_recursive' => 1,
                'url'         => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $uid
            ])
        )->isTrue();

        $bk_id = $bk->fields['id'];

        $bk_user = new \SavedSearch_User();
        $this->boolean(
            (bool)$bk_user->add(['users_id' => $uid,
                'itemtype' => 'Ticket',
                'savedsearches_id' => $bk_id
            ])
        )->isTrue();

        $search = \Search::manageParams('Ticket', ['reset' => 1], true, false);
        $this->array(
            $search
        )->isEqualTo([
            'reset'        => 1,
            'start'        => 0,
            'order'        => 'DESC',
            'sort'         => 2,
            'is_deleted'   => 0,
            'criteria'     => [
                0 => [
                    'field' => '5',
                    'searchtype' => 'equals',
                    'value' => $uid
                ],
            ],
            'metacriteria' => [],
            'itemtype' => 'Ticket',
            'savedsearches_id' => $bk_id,
            'as_map'           => 0,
            'browse'           => 0,
        ]);

       // let's test for Computers
        $search = \Search::manageParams('Computer', ['reset' => 1], false, false);
        $this->array(
            $search
        )->isEqualTo([
            'reset'        => 1,
            'start'        => 0,
            'order'        => 'ASC',
            'sort'         => 1,
            'is_deleted'   => 0,
            'criteria'     => [
                0 => [
                    'field' => 'view',
                    'link'  => 'contains',
                    'value' => '',
                ]
            ],
            'metacriteria' => [],
            'as_map'       => 0,
            'browse'       => 0,
        ]);

       // now add a bookmark on Computer view
        $bk = new \SavedSearch();
        $this->boolean(
            (bool)$bk->add(['name'         => 'Computer test',
                'type'         => 1,
                'itemtype'     => 'Computer',
                'users_id'     => $uid,
                'is_private'   => 1,
                'entities_id'  => 0,
                'is_recursive' => 1,
                'url'         => 'front/computer.php?itemtype=Computer&sort=31&order=DESC&criteria%5B0%5D%5Bfield%5D=view&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=test'
            ])
        )->isTrue();

        $bk_id = $bk->fields['id'];

        $bk_user = new \SavedSearch_User();
        $this->boolean(
            (bool)$bk_user->add(['users_id' => $uid,
                'itemtype' => 'Computer',
                'savedsearches_id' => $bk_id
            ])
        )->isTrue();

        $search = \Search::manageParams('Computer', ['reset' => 1], true, false);
        $this->array(
            $search
        )->isEqualTo([
            'reset'        => 1,
            'start'        => 0,
            'order'        => 'DESC',
            'sort'         => 31,
            'is_deleted'   => 0,
            'criteria'     => [
                0 => [
                    'field' => 'view',
                    'searchtype' => 'contains',
                    'value' => 'test'
                ],
            ],
            'metacriteria' => [],
            'itemtype' => 'Computer',
            'savedsearches_id' => $bk_id,
            'as_map'           => 0,
            'browse'           => 0,
        ]);
    }

    public function addSelectProvider()
    {
        return [
            'special_fk' => [[
                'itemtype'  => 'Computer',
                'ID'        => 24, // users_id_tech
                'sql'       => '`glpi_users_users_id_tech`.`name` AS `ITEM_Computer_24`, `glpi_users_users_id_tech`.`realname` AS `ITEM_Computer_24_realname`,
                           `glpi_users_users_id_tech`.`id` AS `ITEM_Computer_24_id`, `glpi_users_users_id_tech`.`firstname` AS `ITEM_Computer_24_firstname`,'
            ]
            ],
            'regular_fk' => [[
                'itemtype'  => 'Computer',
                'ID'        => 70, // users_id
                'sql'       => '`glpi_users`.`name` AS `ITEM_Computer_70`, `glpi_users`.`realname` AS `ITEM_Computer_70_realname`,
                           `glpi_users`.`id` AS `ITEM_Computer_70_id`, `glpi_users`.`firstname` AS `ITEM_Computer_70_firstname`,'
            ]
            ],
        ];
    }

    /**
     * @dataProvider addSelectProvider
     */
    public function testAddSelect($provider)
    {
        $sql_select = \Search::addSelect($provider['itemtype'], $provider['ID']);

        $this->string($this->cleanSQL($sql_select))
         ->isEqualTo($this->cleanSQL($provider['sql']));
    }

    public function addLeftJoinProvider()
    {
        return [
            'itemtype_item_revert' => [[
                'itemtype'           => 'Project',
                'table'              => \Contact::getTable(),
                'field'              => 'name',
                'linkfield'          => 'id',
                'meta'               => false,
                'meta_type'          => null,
                'joinparams'         => [
                    'jointype'          => 'itemtype_item_revert',
                    'specific_itemtype' => 'Contact',
                    'beforejoin'        => [
                        'table'      => \ProjectTeam::getTable(),
                        'joinparams' => [
                            'jointype' => 'child',
                        ]
                    ]
                ],
                'sql' => "LEFT JOIN `glpi_projectteams`
                        ON (`glpi_projects`.`id` = `glpi_projectteams`.`projects_id`
                            )
                      LEFT JOIN `glpi_contacts`  AS `glpi_contacts_id_d36f89b191ea44cf6f7c8414b12e1e50`
                        ON (`glpi_contacts_id_d36f89b191ea44cf6f7c8414b12e1e50`.`id` = `glpi_projectteams`.`items_id`
                        AND `glpi_projectteams`.`itemtype` = 'Contact'
                         )"
            ]
            ],
            'special_fk' => [[
                'itemtype'           => 'Computer',
                'table'              => \User::getTable(),
                'field'              => 'name',
                'linkfield'          => 'users_id_tech',
                'meta'               => false,
                'meta_type'          => null,
                'joinparams'         => [],
                'sql' => "LEFT JOIN `glpi_users` AS `glpi_users_users_id_tech` ON (`glpi_computers`.`users_id_tech` = `glpi_users_users_id_tech`.`id` )"
            ]
            ],
            'regular_fk' => [[
                'itemtype'           => 'Computer',
                'table'              => \User::getTable(),
                'field'              => 'name',
                'linkfield'          => 'users_id',
                'meta'               => false,
                'meta_type'          => null,
                'joinparams'         => [],
                'sql' => "LEFT JOIN `glpi_users` ON (`glpi_computers`.`users_id` = `glpi_users`.`id` )"
            ]
            ],

            'linkfield in beforejoin' => [[
                'itemtype'           => 'Ticket',
                'table'              => 'glpi_validatorsubstitutes',
                'field'              => 'name',
                'linkfield'          => 'validatorsubstitutes_id',
                'meta'               => false,
                'meta_type'          => null,
                'joinparams'         => [
                    'beforejoin'         => [
                        'table'          => 'glpi_validatorsubstitutes',
                        'joinparams'         => [
                            'jointype'           => 'child',
                            'beforejoin'         => [
                                'table'              => \User::getTable(),
                                'linkfield'          => 'users_id_validate',
                                'joinparams'             => [
                                    'beforejoin'             => [
                                        'table'                  => \TicketValidation::getTable(),
                                        'joinparams'                 => [
                                            'jointype'                   => 'child',
                                        ]
                                    ]
                                ]
                            ]
                        ],
                    ]
                ],
                // This is a real use case. Ensure the LEFT JOIN chain uses consistent table names (see glpi_users_users_id_validate)
                'sql' => "LEFT JOIN `glpi_ticketvalidations` "
                . "ON (`glpi_tickets`.`id` = `glpi_ticketvalidations`.`tickets_id` )"
                . "LEFT JOIN `glpi_users` AS `glpi_users_users_id_validate_57751ba960bd8511d2ad8a01bd8487f4` "
                . "ON (`glpi_ticketvalidations`.`users_id_validate` = `glpi_users_users_id_validate_57751ba960bd8511d2ad8a01bd8487f4`.`id` ) "
                . "LEFT JOIN `glpi_validatorsubstitutes` AS `glpi_validatorsubstitutes_f1e9cbef8429d6d41e308371824d1632` "
                . "ON (`glpi_users_users_id_validate_57751ba960bd8511d2ad8a01bd8487f4`.`id` = `glpi_validatorsubstitutes_f1e9cbef8429d6d41e308371824d1632`.`users_id` )"
                . "LEFT JOIN `glpi_validatorsubstitutes` AS `glpi_validatorsubstitutes_c9b716cdcdcfe62bc267613fce4d1f48` "
                . "ON (`glpi_validatorsubstitutes_f1e9cbef8429d6d41e308371824d1632`.`validatorsubstitutes_id` = `glpi_validatorsubstitutes_c9b716cdcdcfe62bc267613fce4d1f48`.`id` )"
            ]
            ],
        ];
    }

    /**
     * @dataProvider addLeftJoinProvider
     */
    public function testAddLeftJoin($lj_provider)
    {
        $already_link_tables = [];

        $sql_join = \Search::addLeftJoin(
            $lj_provider['itemtype'],
            getTableForItemType($lj_provider['itemtype']),
            $already_link_tables,
            $lj_provider['table'],
            $lj_provider['linkfield'],
            $lj_provider['meta'],
            $lj_provider['meta_type'],
            $lj_provider['joinparams'],
            $lj_provider['field']
        );

        $this->string($this->cleanSQL($sql_join))
           ->isEqualTo($this->cleanSQL($lj_provider['sql']));
    }

    protected function addOrderByBCProvider(): array
    {
        return [
         // Generic examples
            [
                'Computer', 5, 'ASC',
                ' ORDER BY `ITEM_Computer_5` ASC '
            ],
            [
                'Computer', 5, 'DESC',
                ' ORDER BY `ITEM_Computer_5` DESC '
            ],
            [
                'Computer', 5, 'INVALID',
                ' ORDER BY `ITEM_Computer_5` DESC '
            ],
         // Simple Hard-coded cases
            [
                'IPAddress', 1, 'ASC',
                ' ORDER BY INET6_ATON(`glpi_ipaddresses`.`name`) ASC '
            ],
            [
                'IPAddress', 1, 'DESC',
                ' ORDER BY INET6_ATON(`glpi_ipaddresses`.`name`) DESC '
            ],
            [
                'User', 1, 'ASC',
                ' ORDER BY `glpi_users`.`name` ASC '
            ],
            [
                'User', 1, 'DESC',
                ' ORDER BY `glpi_users`.`name` DESC '
            ],
        ];
    }

    protected function addOrderByProvider(): array
    {
        return [
         // Generic examples
            [
                'Computer',
                [
                    [
                        'searchopt_id' => 5,
                        'order'        => 'ASC'
                    ]
                ], ' ORDER BY `ITEM_Computer_5` ASC '
            ],
            [
                'Computer',
                [
                    [
                        'searchopt_id' => 5,
                        'order'        => 'DESC'
                    ]
                ], ' ORDER BY `ITEM_Computer_5` DESC '
            ],
            [
                'Computer',
                [
                    [
                        'searchopt_id' => 5,
                        'order'        => 'INVALID'
                    ]
                ], ' ORDER BY `ITEM_Computer_5` DESC '
            ],
            [
                'Computer',
                [
                    [
                        'searchopt_id' => 5,
                    ]
                ], ' ORDER BY `ITEM_Computer_5` ASC '
            ],
         // Simple Hard-coded cases
            [
                'IPAddress',
                [
                    [
                        'searchopt_id' => 1,
                        'order'        => 'ASC'
                    ]
                ], ' ORDER BY INET6_ATON(`glpi_ipaddresses`.`name`) ASC '
            ],
            [
                'IPAddress',
                [
                    [
                        'searchopt_id' => 1,
                        'order'        => 'DESC'
                    ]
                ], ' ORDER BY INET6_ATON(`glpi_ipaddresses`.`name`) DESC '
            ],
            [
                'User',
                [
                    [
                        'searchopt_id' => 1,
                        'order'        => 'ASC'
                    ]
                ], ' ORDER BY `glpi_users`.`name` ASC '
            ],
            [
                'User',
                [
                    [
                        'searchopt_id' => 1,
                        'order'        => 'DESC'
                    ]
                ], ' ORDER BY `glpi_users`.`name` DESC '
            ],
         // Multiple sort cases
            [
                'Computer',
                [
                    [
                        'searchopt_id' => 5,
                        'order'        => 'ASC'
                    ],
                    [
                        'searchopt_id' => 6,
                        'order'        => 'ASC'
                    ],
                ], ' ORDER BY `ITEM_Computer_5` ASC, `ITEM_Computer_6` ASC '
            ],
            [
                'Computer',
                [
                    [
                        'searchopt_id' => 5,
                        'order'        => 'ASC'
                    ],
                    [
                        'searchopt_id' => 6,
                        'order'        => 'DESC'
                    ],
                ], ' ORDER BY `ITEM_Computer_5` ASC, `ITEM_Computer_6` DESC '
            ],
        ];
    }

    /**
     * @dataProvider addOrderByBCProvider
     */
    public function testAddOrderByBC($itemtype, $id, $order, $expected)
    {
        $result = null;
        $this->when(
            function () use (&$result, $itemtype, $id, $order) {
                $result = \Search::addOrderBy($itemtype, $id, $order);
            }
        )->error()
         ->withType(E_USER_DEPRECATED)
         ->withMessage('The parameters for Search::addOrderBy have changed to allow sorting by multiple fields. Please update your calling code.')
            ->exists();
        $this->string($result)->isEqualTo($expected);

       // Complex cases
        $table_addtable = 'glpi_users_af1042e23ce6565cfe58c6db91f84692';

        $_SESSION['glpinames_format'] = \User::FIRSTNAME_BEFORE;
        $user_order_1 = null;
        $this->when(
            function () use (&$user_order_1) {
                $user_order_1 = \Search::addOrderBy('Ticket', 4, 'ASC');
            }
        )->error()
         ->withType(E_USER_DEPRECATED)
         ->withMessage('The parameters for Search::addOrderBy have changed to allow sorting by multiple fields. Please update your calling code.')
            ->exists();
        $this->string($user_order_1)->isEqualTo(" ORDER BY `$table_addtable`.`firstname` ASC,
                                 `$table_addtable`.`realname` ASC,
                                 `$table_addtable`.`name` ASC ");

        $user_order_2 = null;
        $this->when(
            function () use (&$user_order_2) {
                $user_order_2 = \Search::addOrderBy('Ticket', 4, 'DESC');
            }
        )->error()
         ->withType(E_USER_DEPRECATED)
         ->withMessage('The parameters for Search::addOrderBy have changed to allow sorting by multiple fields. Please update your calling code.')
            ->exists();
        $this->string($user_order_2)->isEqualTo(" ORDER BY `$table_addtable`.`firstname` DESC,
                                 `$table_addtable`.`realname` DESC,
                                 `$table_addtable`.`name` DESC ");

        $_SESSION['glpinames_format'] = \User::REALNAME_BEFORE;
        $user_order_3 = null;
        $this->when(
            function () use (&$user_order_3) {
                $user_order_3 = \Search::addOrderBy('Ticket', 4, 'ASC');
            }
        )->error()
         ->withType(E_USER_DEPRECATED)
         ->withMessage('The parameters for Search::addOrderBy have changed to allow sorting by multiple fields. Please update your calling code.')
            ->exists();
        $this->string($user_order_3)->isEqualTo(" ORDER BY `$table_addtable`.`realname` ASC,
                                 `$table_addtable`.`firstname` ASC,
                                 `$table_addtable`.`name` ASC ");
        $user_order_4 = null;
        $this->when(
            function () use (&$user_order_4) {
                $user_order_4 = \Search::addOrderBy('Ticket', 4, 'DESC');
            }
        )->error()
         ->withType(E_USER_DEPRECATED)
         ->withMessage('The parameters for Search::addOrderBy have changed to allow sorting by multiple fields. Please update your calling code.')
            ->exists();
        $this->string($user_order_4)->isEqualTo(" ORDER BY `$table_addtable`.`realname` DESC,
                                 `$table_addtable`.`firstname` DESC,
                                 `$table_addtable`.`name` DESC ");
    }

    /**
     * @dataProvider addOrderByProvider
     */
    public function testAddOrderBy($itemtype, $sort_fields, $expected)
    {
        $result = \Search::addOrderBy($itemtype, $sort_fields);
        $this->string($result)->isEqualTo($expected);

       // Complex cases
        $table_addtable = 'glpi_users_af1042e23ce6565cfe58c6db91f84692';

        $_SESSION['glpinames_format'] = \User::FIRSTNAME_BEFORE;
        $user_order_1 = \Search::addOrderBy('Ticket', [
            [
                'searchopt_id' => 4,
                'order'        => 'ASC'
            ]
        ]);
        $this->string($user_order_1)->isEqualTo(" ORDER BY `$table_addtable`.`firstname` ASC,
                                 `$table_addtable`.`realname` ASC,
                                 `$table_addtable`.`name` ASC ");
        $user_order_2 = \Search::addOrderBy('Ticket', [
            [
                'searchopt_id' => 4,
                'order'        => 'DESC'
            ]
        ]);
        $this->string($user_order_2)->isEqualTo(" ORDER BY `$table_addtable`.`firstname` DESC,
                                 `$table_addtable`.`realname` DESC,
                                 `$table_addtable`.`name` DESC ");

        $_SESSION['glpinames_format'] = \User::REALNAME_BEFORE;
        $user_order_3 = \Search::addOrderBy('Ticket', [
            [
                'searchopt_id' => 4,
                'order'        => 'ASC'
            ]
        ]);
        $this->string($user_order_3)->isEqualTo(" ORDER BY `$table_addtable`.`realname` ASC,
                                 `$table_addtable`.`firstname` ASC,
                                 `$table_addtable`.`name` ASC ");
        $user_order_4 = \Search::addOrderBy('Ticket', [
            [
                'searchopt_id' => 4,
                'order'        => 'DESC'
            ]
        ]);
        $this->string($user_order_4)->isEqualTo(" ORDER BY `$table_addtable`.`realname` DESC,
                                 `$table_addtable`.`firstname` DESC,
                                 `$table_addtable`.`name` DESC ");
    }

    private function cleanSQL($sql)
    {
        // Clean whitespaces
        $sql = preg_replace('/\s+/', ' ', $sql);

        // Remove whitespaces around parenthesis
        $sql = preg_replace('/\(\s+/', '(', $sql);
        $sql = preg_replace('/\s+\)/', ')', $sql);

        $sql = trim($sql);

        return $sql;
    }

    public function testAllAssetsFields()
    {
        global $CFG_GLPI, $DB;

        $needed_fields = [
            'id',
            'name',
            'states_id',
            'locations_id',
            'serial',
            'otherserial',
            'comment',
            'users_id',
            'contact',
            'contact_num',
            'groups_id',
            'date_mod',
            'manufacturers_id',
            'groups_id_tech',
            'entities_id',
        ];

        foreach ($CFG_GLPI["asset_types"] as $itemtype) {
            $table = getTableForItemType($itemtype);

            foreach ($needed_fields as $field) {
                $this->boolean($DB->fieldExists($table, $field))
                 ->isTrue("$table.$field is missing");
            }
        }
    }

    public function testProblems()
    {
        $tech_users_id = getItemByTypeName('User', "tech", true);

       // reduce the right of tech profile
       // to have only the right of display their own problems (created, assign)
        \ProfileRight::updateProfileRights(getItemByTypeName('Profile', "Technician", true), [
            'Problem' => (\Problem::READMY + READNOTE + UPDATENOTE)
        ]);

       // add a group for tech user
        $group = new \Group();
        $groups_id = $group->add([
            'name' => "test group for tech user"
        ]);
        $this->integer((int)$groups_id)->isGreaterThan(0);
        $group_user = new \Group_User();
        $this->integer(
            (int)$group_user->add([
                'groups_id' => $groups_id,
                'users_id'  => $tech_users_id
            ])
        )->isGreaterThan(0);

       // create a problem and assign group with tech user
        $problem = new \Problem();
        $this->integer(
            (int)$problem->add([
                'name'              => "test problem visibility for tech",
                'content'           => "test problem visibility for tech",
                '_groups_id_assign' => $groups_id
            ])
        )->isGreaterThan(0);

       // let's use tech user
        $this->login('tech', 'tech');

       // do search and check presence of the created problem
        $data = \Search::prepareDatasForSearch('Problem', ['reset' => 'reset']);
        \Search::constructSQL($data);
        \Search::constructData($data);

        $this->integer($data['data']['totalcount'])->isEqualTo(1);
        $this->array($data)
         ->array['data']
         ->array['rows']
         ->array[0]
         ->array['raw']
         ->string['ITEM_Problem_1']->isEqualTo('test problem visibility for tech');
    }

    public function testChanges()
    {
        $tech_users_id = getItemByTypeName('User', "tech", true);

       // reduce the right of tech profile
       // to have only the right of display their own changes (created, assign)
        \ProfileRight::updateProfileRights(getItemByTypeName('Profile', "Technician", true), [
            'Change' => (\Change::READMY + READNOTE + UPDATENOTE)
        ]);

       // add a group for tech user
        $group = new \Group();
        $groups_id = $group->add([
            'name' => "test group for tech user"
        ]);
        $this->integer((int)$groups_id)->isGreaterThan(0);

        $group_user = new \Group_User();
        $this->integer(
            (int)$group_user->add([
                'groups_id' => $groups_id,
                'users_id'  => $tech_users_id
            ])
        )->isGreaterThan(0);

       // create a Change and assign group with tech user
        $change = new \Change();
        $this->integer(
            (int)$change->add([
                'name'              => "test Change visibility for tech",
                'content'           => "test Change visibility for tech",
                '_groups_id_assign' => $groups_id
            ])
        )->isGreaterThan(0);

       // let's use tech user
        $this->login('tech', 'tech');

       // do search and check presence of the created Change
        $data = \Search::prepareDatasForSearch('Change', ['reset' => 'reset']);
        \Search::constructSQL($data);
        \Search::constructData($data);

        $this->integer($data['data']['totalcount'])->isEqualTo(1);
        $this->array($data)
         ->array['data']
         ->array['rows']
         ->array[0]
         ->array['raw']
         ->string['ITEM_Change_1']->isEqualTo('test Change visibility for tech');
    }

    public function testSearchDdTranslation()
    {
        global $CFG_GLPI;

        $this->login();
        $conf = new \Config();
        $conf->setConfigurationValues('core', ['translate_dropdowns' => 1]);
        $CFG_GLPI['translate_dropdowns'] = 1;

        $state = new \State();
        $this->boolean($state->maybeTranslated())->isTrue();

        $sid = $state->add([
            'name'         => 'A test state',
            'is_recursive' => 1
        ]);
        $this->integer($sid)->isGreaterThan(0);

        $ddtrans = new \DropdownTranslation();
        $this->integer(
            $ddtrans->add([
                'itemtype'  => $state->getType(),
                'items_id'  => $state->fields['id'],
                'language'  => 'fr_FR',
                'field'     => 'completename',
                'value'     => 'Un status de test'
            ])
        )->isGreaterThan(0);

        $_SESSION['glpi_dropdowntranslations'] = [$state->getType() => ['completename' => '']];

        $search_params = [
            'is_deleted'   => 0,
            'start'        => 0,
            'criteria'     => [
                0 => [
                    'field'      => 'view',
                    'searchtype' => 'contains',
                    'value'      => 'test'
                ]
            ],
            'metacriteria' => []
        ];

        $data = $this->doSearch('State', $search_params);

        $this->integer($data['data']['totalcount'])->isIdenticalTo(1);

        $conf->setConfigurationValues('core', ['translate_dropdowns' => 0]);
        $CFG_GLPI['translate_dropdowns'] = 0;
        unset($_SESSION['glpi_dropdowntranslations']);
    }

    public function dataInfocomOptions()
    {
        return [
            [1, false],
            [2, false],
            [4, false],
            [40, false],
            [31, false],
            [80, false],
            [25, true],
            [26, true],
            [27, true],
            [28, true],
            [37, true],
            [38, true],
            [50, true],
            [51, true],
            [52, true],
            [53, true],
            [54, true],
            [55, true],
            [56, true],
            [57, true],
            [58, true],
            [59, true],
            [120, true],
            [122, true],
            [123, true],
            [124, true],
            [125, true],
            [142, true],
            [159, true],
            [173, true],
        ];
    }

    /**
     * @dataProvider dataInfocomOptions
     */
    public function testIsInfocomOption($index, $expected)
    {
        $this->boolean(\Search::isInfocomOption('Computer', $index))->isIdenticalTo($expected);
    }

    protected function makeTextSearchValueProvider()
    {
        return [
            ['NULL', null],
            ['null', null],
            ['', ''],
            ['^', '%'],
            ['$', ''],
            ['^$', ''],
            ['$^', '%$^%'], // inverted ^ and $
            ['looking for', '%looking for%'],
            ['^starts with', 'starts with%'],
            ['ends with$', '%ends with'],
            ['^exact string$', 'exact string'],
            ['a ^ in the middle$', '%a ^ in the middle'],
            ['^and $ not at the end', 'and $ not at the end%'],
            ['45$^ab5', '%45$^ab5%'],
            ['^ ltrim', 'ltrim%'],
            ['rtim this   $', '%rtim this'],
            ['  extra spaces ', '%extra spaces%'],
            ['^ exactval $', 'exactval'],
            ['snake_case', '%snake\\_case%'], // _ is a wildcard that must be escaped
            ['quot\'ed', '%quot\\\'ed%'],
            ['quot\\\'ed', '%quot\\\'ed%'], // already escaped value should not produce double escaping
            ['^&#60;PROD-15&#62;', '<PROD-15>%'],
            ['<PROD-15>$', '%<PROD-15>'],
            ['A&#38;B', '%A&B%'],
            ['A&B', '%A&B%'],
            ["backslashes \\ \\\\ are twice escaped when not used in ', \n, \r, ... ", "%backslashes \\\\\\\\ \\\\\\\\\\\\\\\\ are twice escaped when not used in \', \\n, \\r, ...%"],
        ];
    }

    /**
     * @dataProvider makeTextSearchValueProvider
     */
    public function testMakeTextSearchValue($value, $expected)
    {
        $this->variable(\Search::makeTextSearchValue($value))->isIdenticalTo($expected);
    }

    public function providerAddWhere()
    {
        return [
            [
                'link' => ' ',
                'nott' => 0,
                'itemtype' => \User::class,
                'ID' => 99,
                'searchtype' => 'equals',
                'val' => '5',
                'meta' => false,
                'expected' => "(`glpi_users_users_id_supervisor`.`id` = '5')",
            ],
            [
                'link' => ' AND ',
                'nott' => 0,
                'itemtype' => \CartridgeItem::class,
                'ID' => 24,
                'searchtype' => 'equals',
                'val' => '2',
                'meta' => false,
                'expected' => "AND (`glpi_users_users_id_tech`.`id` = '2')",
            ],
            [
                'link' => ' AND ',
                'nott' => 0,
                'itemtype' => \Monitor::class,
                'ID' => 11, // Search ID 11 (size field)
                'searchtype' => 'contains',
                'val' => '70',
                'meta' => false,
                'expected' => "AND (`glpi_monitors`.`size` LIKE '%70.%')",
            ],
            [
                'link' => ' AND ',
                'nott' => 0,
                'itemtype' => \Monitor::class,
                'ID' => 11, // Search ID 11 (size field)
                'searchtype' => 'contains',
                'val' => '70.5',
                'meta' => false,
                'expected' => "AND (`glpi_monitors`.`size` LIKE '%70.5%')",
            ],
            [
                'link' => ' AND ',
                'nott' => 0,
                'itemtype' => \Monitor::class,
                'ID' => 11, // Search ID 11 (size field)
                'searchtype' => 'contains',
                'val' => '70.5%',
                'meta' => false,
                'expected' => "AND (`glpi_monitors`.`size` LIKE '%70.5%')",
            ],
            [
                'link' => ' AND ',
                'nott' => 0,
                'itemtype' => \Computer::class,
                'ID' => 121, // Search ID 121 (date_creation field)
                'searchtype' => 'contains',
                'val' => Sanitizer::sanitize('>2022-10-25'),
                'meta' => false,
                'expected' => "AND CONVERT(`glpi_computers`.`date_creation` USING utf8mb4) > '2022-10-25'",
            ],
            [
                'link' => ' AND ',
                'nott' => 0,
                'itemtype' => \Computer::class,
                'ID' => 121, // Search ID 121 (date_creation field)
                'searchtype' => 'contains',
                'val' => Sanitizer::sanitize('<2022-10-25'),
                'meta' => false,
                'expected' => "AND CONVERT(`glpi_computers`.`date_creation` USING utf8mb4) < '2022-10-25'",
            ],
            [
                'link' => ' AND ',
                'nott' => 0,
                'itemtype' => \Computer::class,
                'ID' => 151, // Search ID 151 (Item_Disk freesize field)
                'searchtype' => 'contains',
                'val' => Sanitizer::sanitize('>100'),
                'meta' => false,
                'expected' => "AND (`glpi_items_disks`.`freesize` > 100)",
            ],
            [
                'link' => ' AND ',
                'nott' => 0,
                'itemtype' => \Computer::class,
                'ID' => 151, // Search ID 151 (Item_Disk freesize field)
                'searchtype' => 'contains',
                'val' => Sanitizer::sanitize('<10000'),
                'meta' => false,
                'expected' => "AND (`glpi_items_disks`.`freesize` < 10000)",
            ],
            [
                'link' => ' AND ',
                'nott' => 0,
                'itemtype' => \NetworkName::class,
                'ID' => 13, // Search ID 13 (IPAddress name field)
                'searchtype' => 'contains',
                'val' => Sanitizer::sanitize('< 192.168.1.10'),
                'meta' => false,
                'expected' => "AND (INET_ATON(`glpi_ipaddresses`.`name`) < INET_ATON('192.168.1.10'))",
            ],
            [
                'link' => ' AND ',
                'nott' => 0,
                'itemtype' => \NetworkName::class,
                'ID' => 13, // Search ID 13 (IPAddress name field)
                'searchtype' => 'contains',
                'val' => Sanitizer::sanitize('> 192.168.1.10'),
                'meta' => false,
                'expected' => "AND (INET_ATON(`glpi_ipaddresses`.`name`) > INET_ATON('192.168.1.10'))",
            ],
        ];
    }

    /**
     * @dataProvider providerAddWhere
     */
    public function testAddWhere($link, $nott, $itemtype, $ID, $searchtype, $val, $meta, $expected)
    {
        $output = \Search::addWhere($link, $nott, $itemtype, $ID, $searchtype, $val, $meta);
        $this->string($this->cleanSQL($output))->isEqualTo($expected);

        if ($meta) {
            return; // Do not know how to run search on meta here
        }

        $search_params = [
            'is_deleted'   => 0,
            'start'        => 0,
            'criteria'     => [
                [
                    'field'      => $ID,
                    'searchtype' => $searchtype,
                    'value'      => $val
                ]
            ],
            'metacriteria' => []
        ];

       // Run a search to trigger a test failure if anything goes wrong.
        $this->doSearch($itemtype, $search_params);
    }

    public function testSearchWGroups()
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        $search_params = ['is_deleted'   => 0,
            'start'        => 0,
            'search'       => 'Search',
            'criteria'     => [0 => ['field'      => 'view',
                'searchtype' => 'contains',
                'value'      => 'pc'
            ]
            ]
        ];
        $data = $this->doSearch('Computer', $search_params);

        $this->integer($data['data']['totalcount'])->isIdenticalTo(9);

        $displaypref = new \DisplayPreference();
        $input = [
            'itemtype'  => 'Computer',
            'users_id'  => \Session::getLoginUserID(),
            'num'       => 49, //Computer groups_id_tech SO
        ];
        $this->integer((int)$displaypref->add($input))->isGreaterThan(0);

        $data = $this->doSearch('Computer', $search_params);

        $this->integer($data['data']['totalcount'])->isIdenticalTo(9);
    }

    public function testSearchWithMultipleFkeysOnSameTable()
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        $user_tech_id   = getItemByTypeName('User', 'tech', true);
        $user_normal_id = getItemByTypeName('User', 'normal', true);

        $search_params = [
            'is_deleted'   => 0,
            'start'        => 0,
            'sort'         => 22,
            'order'        => 'ASC',
            'search'       => 'Search',
            'criteria'     => [
                0 => [
                    'link'       => 'AND',
                    'field'      => '64', // Last updater
                    'searchtype' => 'equals',
                    'value'      => $user_tech_id,
                ],
                1 => [
                    'link'       => 'AND',
                    'field'      => '22', // Recipient
                    'searchtype' => 'equals',
                    'value'      => $user_normal_id,
                ]
            ]
        ];
        $data = $this->doSearch('Ticket', $search_params);

        $this->string($data['sql']['search'])
         // Check that we have two different joins
         ->contains("LEFT JOIN `glpi_users`  AS `glpi_users_users_id_lastupdater`")
         ->contains("LEFT JOIN `glpi_users`  AS `glpi_users_users_id_recipient`")

         // Check that SELECT criteria applies on corresponding table alias
         ->contains("`glpi_users_users_id_lastupdater`.`realname` AS `ITEM_Ticket_64_realname`")
         ->contains("`glpi_users_users_id_recipient`.`realname` AS `ITEM_Ticket_22_realname`")

         // Check that WHERE criteria applies on corresponding table alias
         ->contains("`glpi_users_users_id_lastupdater`.`id` = '{$user_tech_id}'")
         ->contains("`glpi_users_users_id_recipient`.`id` = '{$user_normal_id}'")

         // Check that ORDER applies on corresponding table alias
         ->contains("`glpi_users_users_id_recipient`.`name` ASC");
    }

    public function testSearchAllAssets()
    {
        $data = $this->doSearch('AllAssets', [
            'reset'      => 'reset',
            'is_deleted' => 0,
            'start'      => 0,
            'search'     => 'Search',
            'criteria'   => [
                [
                    'link'       => 'AND',
                    'field'      => 'view',
                    'searchtype' => 'contains',
                    'value'      => 'test',
                ],
            ]
        ]);

        $this->string($data['sql']['search'])
         ->matches("/OR\s*\(`glpi_entities`\.`completename`\s*LIKE '%test%'\s*\)/")
         ->matches("/OR\s*\(`glpi_states`\.`completename`\s*LIKE '%test%'\s*\)/");

        $types = [
            \Computer::getTable(),
            \Monitor::getTable(),
            \NetworkEquipment::getTable(),
            \Peripheral::getTable(),
            \Phone::getTable(),
            \Printer::getTable(),
        ];

        foreach ($types as $type) {
            $this->string($data['sql']['search'])
            ->contains("`$type`.`is_deleted` = 0")
            ->contains("AND `$type`.`is_template` = 0")
            ->contains("`$type`.`entities_id` IN ('1', '2', '3')")
            ->contains("OR (`$type`.`is_recursive`='1'" .
                        " AND `$type`.`entities_id` IN (0))")
             ->matches("/`$type`\.`name`  LIKE '%test%'/");
        }
    }

    public function testSearchWithNamespacedItem()
    {
        $search_params = [
            'is_deleted'   => 0,
            'start'        => 0,
            'search'       => 'Search',
        ];
        $this->login();
        $this->setEntity('_test_root_entity', true);

        $data = $this->doSearch('SearchTest\\Computer', $search_params);

        $this->string($data['sql']['search'])
         ->contains("`glpi_computers`.`name` AS `ITEM_SearchTest\Computer_1`")
         ->contains("`glpi_computers`.`id` AS `ITEM_SearchTest\Computer_1_id`")
         ->contains("ORDER BY `ITEM_SearchTest\Computer_1` ASC");
    }

    public function testGroupParamAfterMeta()
    {
       // Try to run this query without warnings
        $this->doSearch('Ticket', [
            'reset'      => 'reset',
            'is_deleted' => 0,
            'start'      => 0,
            'search'     => 'Search',
            'criteria'   => [
                [
                    'link'       => 'AND',
                    'field'      => 12,
                    'searchtype' => 'equals',
                    'value'      => 'notold',
                ],
                [
                    'link'       => 'AND',
                    'itemtype'   => 'Computer',
                    'meta'       => true,
                    'field'      => 1,
                    'searchtype' => 'contains',
                    'value'      => 'ù',
                ],
                [
                    'link' => 'AND',
                    'criteria' => [
                        [
                            'link'       => 'AND+NOT',
                            'field'      => 'view',
                            'searchtype' => 'contains',
                            'value'      => '233',
                        ]
                    ]
                ]
            ]
        ]);
    }

    /**
     * Check that search result is valid.
     *
     * @param array $result
     */
    private function checkSearchResult($result)
    {
        $this->array($result)->hasKey('data');
        $this->array($result['data'])->hasKeys(['count', 'begin', 'end', 'totalcount', 'cols', 'rows', 'items']);
        $this->integer($result['data']['count']);
        $this->integer($result['data']['begin']);
        $this->integer($result['data']['end']);
        $this->integer($result['data']['totalcount']);
        $this->array($result['data']['cols']);
        $this->array($result['data']['rows']);
        $this->array($result['data']['items']);

       // No errors
        $this->array($result)->hasKey('last_errors');
        $this->array($result['last_errors'])->isIdenticalTo([]);

        $this->array($result)->hasKey('sql');
        $this->array($result['sql'])->hasKey('search');
        $this->string($result['sql']['search']);
    }

    /**
     * Returns list of searchable classes.
     *
     * @return array
     */
    private function getSearchableClasses(): array
    {
        $classes = $this->getClasses(
            'searchOptions',
            [
                '/^Common.*/', // Should be abstract
                'NetworkPortInstantiation', // Should be abstract (or have $notable = true)
                'NetworkPortMigration', // Tables only exists in specific cases
                'NotificationSettingConfig', // Stores its data in glpi_configs, does not acts as a CommonDBTM
                'PendingReasonCron'
            ]
        );
        $searchable_classes = [];
        foreach ($classes as $class) {
            $item_class = new \ReflectionClass($class);
            if ($item_class->isAbstract() || $class::getTable() === '' || !is_a($class, CommonDBTM::class, true)) {
                // abstract class or class with "static protected $notable = true;" (which is a kind of abstract)
                continue;
            }

            $searchable_classes[] = $class;
        }
        sort($searchable_classes);

        return $searchable_classes;
    }

    protected function testNamesOutputProvider(): array
    {
        return [
            [
                'params' => [
                    'display_type' => \Search::NAMES_OUTPUT,
                    'export_all'   => 1,
                    'criteria'     => [],
                    'item_type'    => 'Ticket',
                    'is_deleted'   => 0,
                    'as_map'       => 0,
                ],
                'expected' => [
                    '_ticket01',
                    '_ticket02',
                    '_ticket03',
                    '_ticket100',
                    '_ticket101',
                ]
            ],
            [
                'params' => [
                    'display_type' => \Search::NAMES_OUTPUT,
                    'export_all'   => 1,
                    'criteria'     => [],
                    'item_type'    => 'Computer',
                    'is_deleted'   => 0,
                    'as_map'       => 0,
                ],
                'expected' => [
                    '_test_pc_with_encoded_comment',
                    '_test_pc01',
                    '_test_pc02',
                    '_test_pc03',
                    '_test_pc11',
                    '_test_pc12',
                    '_test_pc13',
                    '_test_pc21',
                    '_test_pc22',
                ]
            ],
        ];
    }

    /**
     * @dataProvider testNamesOutputProvider
     */
    public function testNamesOutput(array $params, array $expected)
    {
        $this->login();

       // Run search and capture results
        ob_start();
        \Search::showList($params['item_type'], $params);
        $names = ob_get_contents();
        ob_end_clean();

       // Convert results to array and remove last row (always empty)
        $names = explode("\n", $names);
        array_pop($names);

       // Check results
        $this->array($names)->isEqualTo($expected);
    }

    protected function testMyselfSearchCriteriaProvider(): array
    {
        $TU_USER_users_id = getItemByTypeName('User', TU_USER, true);
        $tech_users_id = getItemByTypeName('User', 'tech', true);
        $root_entity = getItemByTypeName('Entity', '_test_root_entity', true);

       // Create test data
        $to_create = [
            [
                'name' => 'testMyselfSearchCriteriaProvider 1',
                'observer' => $TU_USER_users_id,
            ],
            [
                'name' => 'testMyselfSearchCriteriaProvider 2',
                'observer' => $TU_USER_users_id,
            ],
            [
                'name' => 'testMyselfSearchCriteriaProvider 3',
                'observer' => $TU_USER_users_id,
            ],
            [
                'name' => 'testMyselfSearchCriteriaProvider 4',
                'observer' => $tech_users_id,
            ],
        ];

        foreach ($to_create as $params) {
            $ticket = new Ticket();
            $tickets_id = $ticket->add([
                'name'               => $params['name'],
                'content'            => 'testMyselfSearchCriteriaProvider',
                '_users_id_observer' => $params['observer'],
                'entities_id'        => $root_entity,
            ]);
            $this->integer($tickets_id)->isGreaterThan(0);
            $actors = $ticket->getITILActors();
            $this->integer($actors[$params['observer']][0])->isEqualTo(CommonITILActor::OBSERVER);
        }

        return [
         // Case 1: Search for tickets where 'TU_USER' is an observer
            [
                'criteria' => [
                    [
                        'link'       => 'AND',
                        'field'      => 66, // Observer search option
                        'searchtype' => 'equals',
                        'value'      => $TU_USER_users_id,
                    ]
                ],
                'expected' => [
                    'testMyselfSearchCriteriaProvider 1',
                    'testMyselfSearchCriteriaProvider 2',
                    'testMyselfSearchCriteriaProvider 3',
                ]
            ],
         // Case 2: Search for tickets where 'tech' is an observer
            [
                'criteria' => [
                    [
                        'link'       => 'AND',
                        'field'      => 66, // Observer search option
                        'searchtype' => 'equals',
                        'value'      => $tech_users_id,
                    ]
                ],
                'expected' => [
                    'testMyselfSearchCriteriaProvider 4',
                ]
            ],
         // Case 3: Search for tickets where the current user (TU_USER) is an observer
            [
                'criteria' => [
                    [
                        'link'       => 'AND',
                        'field'      => 66, // Observer search option
                        'searchtype' => 'equals',
                        'value'      => 'myself',
                    ]
                ],
                'expected' => [
                    'testMyselfSearchCriteriaProvider 1',
                    'testMyselfSearchCriteriaProvider 2',
                    'testMyselfSearchCriteriaProvider 3',
                ]
            ],
        ];
    }

    /**
     * Functional test for the 'myself' search criteria.
     * We use the output type "Search::NAMES_OUTPUT" during the test as it make
     * it easy to parse the results.
     *
     * @dataProvider testMyselfSearchCriteriaProvider
     */
    public function testMyselfSearchCriteria(array $criteria, array $expected)
    {
        $this->login();

       // Run search and capture results
        ob_start();
        \Search::showList('Ticket', [
            'display_type' => \Search::NAMES_OUTPUT,
            'export_all'   => 1,
            'criteria'     => $criteria,
            'item_type'    => 'Ticket',
            'is_deleted'   => 0,
            'as_map'       => 0,
        ]);
        $names = ob_get_contents();
        ob_end_clean();

       // Convert results to array and remove last row (always empty for NAMES_OUTPUT)
        $names = explode("\n", $names);
        array_pop($names);

       // Check results
        $this->array($names)->isEqualTo($expected);
    }

    /**
     * Test all possible combination of operators for a given criteria
     *
     * @param string     $itemtype       Itemtype being searched for
     * @param array      $base_condition Common condition for all searches
     * @param array      $all            Search results for the base condition
     * @param array      $expected       Items names that must be found if the condition is positive
     * @param int        $field          Search option id
     * @param string     $searchtype     Positive searchtype (equals, under, contains, ...)
     * @param mixed      $value          Value being searched
     * @param null|array $not_expected   Optional, item expected to be found is the condition is negative
     *                               If null, will be computed from $all and $expected
     */
    protected function testCriteriaWithSubqueriesProvider_getAllCombination(
        string $itemtype,
        array $base_condition,
        array $all,
        array $expected,
        int $field,
        string $searchtype,
        $value,
        ?array $not_expected = null
    ): iterable {
        if (is_null($not_expected)) {
            // Invert expected items
            $not_expected = array_diff($all, $expected);
        }

        // Inverted criteria
        $not_searchtype = "not$searchtype";

        // All possible combinations of operators leading to a positive condition
        yield [
            'itemtype' => $itemtype,
            'criteria' => [
                $base_condition,
                [
                    'link'       => 'AND',
                    'field'      => $field,
                    'searchtype' => $searchtype,
                    'value'      => $value,
                ]
            ],
            'expected' => $expected,
        ];
        yield [
            'itemtype' => $itemtype,
            'criteria' => [
                $base_condition,
                [
                    'link'       => 'AND NOT',
                    'field'      => $field,
                    'searchtype' => $not_searchtype,
                    'value'      => $value,
                ]
            ],
            'expected' => $expected,
        ];
        yield [
            'itemtype' => $itemtype,
            'criteria' => [
                $base_condition,
                [
                    'link' => 'AND',
                    'criteria' => [
                        [
                            'link'       => 'AND',
                            'field'      => $field,
                            'searchtype' => $searchtype,
                            'value'      => $value,
                        ]
                    ]
                ]
            ],
            'expected' => $expected,
        ];
        yield [
            'itemtype' => $itemtype,
            'criteria' => [
                $base_condition,
                [
                    'link' => 'AND',
                    'criteria' => [
                        [
                            'link'       => 'AND NOT',
                            'field'      => $field,
                            'searchtype' => $not_searchtype,
                            'value'      => $value,
                        ]
                    ]
                ]
            ],
            'expected' => $expected,
        ];
        yield [
            'itemtype' => $itemtype,
            'criteria' => [
                $base_condition,
                [
                    'link' => 'AND NOT',
                    'criteria' => [
                        [
                            'link'       => 'AND',
                            'field'      => $field,
                            'searchtype' => $not_searchtype,
                            'value'      => $value,
                        ]
                    ]
                ]
            ],
            'expected' => $expected,
        ];
        yield [
            'itemtype' => $itemtype,
            'criteria' => [
                $base_condition,
                [
                    'link' => 'AND NOT',
                    'criteria' => [
                        [
                            'link'       => 'AND NOT',
                            'field'      => $field,
                            'searchtype' => $searchtype,
                            'value'      => $value,
                        ]
                    ]
                ]
            ],
            'expected' => $expected,
        ];

        // All possible combinations of operators leading to a negative condition
        yield [
            'itemtype' => $itemtype,
            'criteria' => [
                $base_condition,
                [
                    'link'       => 'AND NOT',
                    'field'      => $field,
                    'searchtype' => $searchtype,
                    'value'      => $value,
                ]
            ],
            'expected' => $not_expected,
        ];
        yield [
            'itemtype' => $itemtype,
            'criteria' => [
                $base_condition,
                [
                    'link'       => 'AND',
                    'field'      => $field,
                    'searchtype' => $not_searchtype,
                    'value'      => $value,
                ]
            ],
            'expected' => $not_expected,
        ];
        yield [
            'itemtype' => $itemtype,
            'criteria' => [
                $base_condition,
                [
                    'link' => 'AND NOT',
                    'criteria' => [
                        [
                            'link'       => 'AND NOT',
                            'field'      => $field,
                            'searchtype' => $not_searchtype,
                            'value'      => $value,
                        ]
                    ]
                ]
            ],
            'expected' => $not_expected,
        ];
        yield [
            'itemtype' => $itemtype,
            'criteria' => [
                $base_condition,
                [
                    'link' => 'AND NOT',
                    'criteria' => [
                        [
                            'link'       => 'AND',
                            'field'      => $field,
                            'searchtype' => $searchtype,
                            'value'      => $value,
                        ]
                    ]
                ]
            ],
            'expected' => $not_expected,
        ];
        yield [
            'itemtype' => $itemtype,
            'criteria' => [
                $base_condition,
                [
                    'link' => 'NOT',
                    'criteria' => [
                        [
                            'link'       => 'AND NOT',
                            'field'      => $field,
                            'searchtype' => $searchtype,
                            'value'      => $value,
                        ]
                    ]
                ]
            ],
            'expected' => $not_expected,
        ];
        yield [
            'itemtype' => $itemtype,
            'criteria' => [
                $base_condition,
                [
                    'link' => 'NOT',
                    'criteria' => [
                        [
                            'link'       => 'NOT',
                            'field'      => $field,
                            'searchtype' => $not_searchtype,
                            'value'      => $value,
                        ]
                    ]
                ]
            ],
            'expected' => $not_expected,
        ];
    }

    protected function testCriteriaWithSubqueriesProvider(): iterable
    {
        $this->login();
        $root = getItemByTypeName('Entity', '_test_root_entity', true);

        // All our test set will be assigned to this category
        $category = $this->createItem('ITILCategory', [
            'name' => 'Test Criteria With Subqueries',
            'entities_id' => $root,
        ])->getId();

        // Check that our test set is empty
        yield [
            'itemtype' => 'Ticket',
            'criteria' => [
                [
                    'link'       => 'AND',
                    'field'      => 7, // Category
                    'searchtype' => 'equals',
                    'value'      => $category,
                ]
            ],
            'expected' => [],
        ];

        // Get tests users
        $user_1 = getItemByTypeName('User', TU_USER, true);
        $user_2 = getItemByTypeName('User', 'glpi', true);

        // Set name to user_1 so we can test searching for ticket on firstname / lastname
        $this->updateItem('User', $user_1, [
            'firstname' => 'Firstname',
            'realname'  => 'Lastname',
        ]);

        // Create test groups
        $this->createItems('Group', [
            [
                'name' => 'Group 1',
                'entities_id' => $root,
            ],
            [
                'name' => 'Group 2',
                'entities_id' => $root,
            ],
        ]);
        $group_1 = getItemByTypeName('Group', 'Group 1', true);
        $group_2 = getItemByTypeName('Group', 'Group 2', true);

        $this->createItem('Group', [
            'name' => 'Group 1A',
            'entities_id' => $root,
            'groups_id' => getItemByTypeName('Group', 'Group 1', true),
        ]);
        $group_1A = getItemByTypeName('Group', 'Group 1A', true);

        // Assign ourself to group 2 (special case to valide "mygroups" criteria)
        $this->createItem('Group_User', [
            'users_id'  => getItemByTypeName('User', TU_USER, true),
            'groups_id' => $group_1,
        ]);
        $_SESSION['glpigroups'] = [$group_1];

        // Create test suppliers
        $this->createItems('Supplier', [
            [
                'name' => 'Supplier 1',
                'entities_id' => $root,
            ],
            [
                'name' => 'Supplier 2',
                'entities_id' => $root,
            ],
        ]);
        $supplier_1 = getItemByTypeName('Supplier', 'Supplier 1', true);
        $supplier_2 = getItemByTypeName('Supplier', 'Supplier 2', true);

        $this->createItems('Ticket', [
            // Test set on watcher group
            [
                'name' => 'Ticket group 1 (W)',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'observer' => [['itemtype' => 'Group', 'items_id' => $group_1]],
                ]
            ],
            [
                'name' => 'Ticket group 2 (W)',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'observer' => [['itemtype' => 'Group', 'items_id' => $group_2]]
                ]
            ],
            [
                'name' => 'Ticket group 1 (W) + group 2 (W)',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'observer' => [
                        ['itemtype' => 'Group', 'items_id' => $group_1],
                        ['itemtype' => 'Group', 'items_id' => $group_2],
                    ]
                ]
            ],
            [
                'name' => 'Ticket group 1A (W) + group 2 (W)',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'observer' => [
                        ['itemtype' => 'Group', 'items_id' => $group_1A],
                        ['itemtype' => 'Group', 'items_id' => $group_2],
                    ]
                ]
            ],

            // Test set on assigned group
            [
                'name' => 'Ticket group 1 (A)',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'assign' => [['itemtype' => 'Group', 'items_id' => $group_1]],
                ]
            ],

            // Test set on requester group
            [
                'name' => 'Ticket group 1 (R)',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'requester' => [['itemtype' => 'Group', 'items_id' => $group_1]],
                ]
            ],

            // Test set on supplier
            [
                'name' => 'Ticket supplier 1',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'assign' => [['itemtype' => 'Supplier', 'items_id' => $supplier_1]],
                ]
            ],
            [
                'name' => 'Ticket supplier 2',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'assign' => [['itemtype' => 'Supplier', 'items_id' => $supplier_2]],
                ]
            ],
            [
                'name' => 'Ticket supplier 1 + supplier 2',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'assign' => [
                        ['itemtype' => 'Supplier', 'items_id' => $supplier_1],
                        ['itemtype' => 'Supplier', 'items_id' => $supplier_2],
                    ],
                ]
            ],

            // Test set on requester
            [
                'name' => 'Ticket user 1 (R)',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'requester' => [['itemtype' => 'User', 'items_id' => $user_1]],
                ]
            ],
            [
                'name' => 'Ticket user 2 (R)',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'requester' => [['itemtype' => 'User', 'items_id' => $user_2]],
                ]
            ],
            [
                'name' => 'Ticket user 1 (R) + user 2 (R)',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'requester' => [
                        ['itemtype' => 'User', 'items_id' => $user_1],
                        ['itemtype' => 'User', 'items_id' => $user_2],
                    ],
                ]
            ],
            [
                'name' => 'Ticket anonymous user (R)',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'requester' => [
                        [
                            'itemtype' => 'User',
                            'items_id' => 0,
                            "alternative_email" => "myemail@email.com",
                            'use_notification' => true
                        ]
                    ],
                ]
            ],

            // Test set on watcher
            [
                'name' => 'Ticket user 1 (W)',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'observer' => [['itemtype' => 'User', 'items_id' => $user_1]],
                ]
            ],

            // Test set on assigned
            [
                'name' => 'Ticket user 1 (A)',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'assign' => [['itemtype' => 'User', 'items_id' => $user_1]],
                ]
            ],
        ]);

        // Validate all items are here as expected
        $base_condition = [
            'link'       => 'AND',
            'field'      => 7, // Category
            'searchtype' => 'equals',
            'value'      => $category,
        ];
        $all_tickets = [
            // Test set on watcher group
            'Ticket group 1 (W)',
            'Ticket group 2 (W)',
            'Ticket group 1 (W) + group 2 (W)',
            'Ticket group 1A (W) + group 2 (W)',

            // Test set on assigned group
            'Ticket group 1 (A)',

            // Test set on requester group
            'Ticket group 1 (R)',

            // Test set on supplier
            'Ticket supplier 1',
            'Ticket supplier 2',
            'Ticket supplier 1 + supplier 2',

            // Test set on requester
            'Ticket user 1 (R)',
            'Ticket user 2 (R)',
            'Ticket user 1 (R) + user 2 (R)',
            'Ticket anonymous user (R)',

            // Test set on watcher
            'Ticket user 1 (W)',

            // Test set on assigned
            'Ticket user 1 (A)',
        ];
        yield [
            'itemtype' => 'Ticket',
            'criteria' => [$base_condition],
            'expected' => $all_tickets,
        ];

        // Run tests for watcher group
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (W)', 'Ticket group 1 (W) + group 2 (W)'],
            65, // Watcher group
            'equals',
            $group_1
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (W)', 'Ticket group 1 (W) + group 2 (W)', 'Ticket group 1A (W) + group 2 (W)'],
            65, // Watcher group
            'contains',
            "group 1"
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (W)', 'Ticket group 1 (W) + group 2 (W)', 'Ticket group 1A (W) + group 2 (W)'],
            65, // Watcher group
            'under',
            $group_1
        );

        // Run test for assigned groups
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (A)'],
            8, // Assigned group
            'equals',
            $group_1
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (A)'],
            8, // Assigned group
            'contains',
            "group 1"
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (A)'],
            8, // Assigned group
            'under',
            $group_1
        );

        // Run test for requester group
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (R)'],
            71, // Requester group
            'equals',
            $group_1
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (R)'],
            71, // Requester group
            'contains',
            "group 1"
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (R)'],
            71, // Requester group
            'under',
            $group_1
        );

        // Run tests for 'mygroup'
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (R)'],
            71, // Requester group
            'equals',
            'mygroups'
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (A)'],
            8, // Assigned group
            'equals',
            'mygroups'
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (W)', 'Ticket group 1 (W) + group 2 (W)'],
            65, // Watcher group
            'equals',
            'mygroups'
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (R)'],
            71, // Requester group
            'under',
            'mygroups'
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (A)'],
            8, // Assigned group
            'under',
            'mygroups'
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (W)', 'Ticket group 1 (W) + group 2 (W)', 'Ticket group 1A (W) + group 2 (W)'],
            65, // Watcher group
            'under',
            'mygroups'
        );

        // Run tests for suppliers
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket supplier 1', 'Ticket supplier 1 + supplier 2'],
            6, // Supplier
            'equals',
            $supplier_1
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket supplier 1', 'Ticket supplier 1 + supplier 2'],
            6, // Supplier
            'contains',
            "Supplier 1"
        );

        // Test empty group search
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            // Every ticket without a watcher group
            array_diff($all_tickets, ['Ticket group 1 (W)', 'Ticket group 2 (W)', 'Ticket group 1 (W) + group 2 (W)', 'Ticket group 1A (W) + group 2 (W)']),
            65, // Watcher group
            'equals',
            0
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            // Every tickets (note that it isn't consistent with the previous criteria "equals 0")
            $all_tickets,
            65, // Watcher group
            'contains',
            "",
            // Not very logical but GLPI return the same results for a contains "" and not contains "" queries
            $all_tickets
        );

        // Run tests for requester
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket user 1 (R)', 'Ticket user 1 (R) + user 2 (R)'],
            4, // Requester
            'equals',
            $user_1
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket user 1 (R)', 'Ticket user 1 (R) + user 2 (R)'],
            4, // Requester
            'contains',
            TU_USER
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket user 1 (R)', 'Ticket user 1 (R) + user 2 (R)'],
            4, // Requester
            'contains',
            "Firstname"
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket user 1 (R)', 'Ticket user 1 (R) + user 2 (R)'],
            4, // Requester
            'contains',
            "Lastname"
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket user 1 (R)', 'Ticket user 1 (R) + user 2 (R)'],
            4, // Requester
            'contains',
            "Lastname Firstname"
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket anonymous user (R)'],
            4, // Requester
            'contains',
            "myemail@email.com"
        );

        // Run tests for watcher
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket user 1 (W)'],
            66, // Watcher
            'equals',
            $user_1
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket user 1 (W)'],
            66, // Watcher
            'contains',
            TU_USER
        );

        // Run tests for requester
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket user 1 (A)'],
            5, // Assign
            'equals',
            $user_1
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket user 1 (A)'],
            5, // Assign
            'contains',
            TU_USER
        );

        // Run test for "myself" special criteria
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            ['Ticket user 1 (R)', 'Ticket user 1 (R) + user 2 (R)'],
            4, // Requester
            'equals',
            'myself'
        );

        // Test empty requester search
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            // Every ticket without a requester group
            array_diff($all_tickets, ['Ticket user 1 (R)', 'Ticket user 2 (R)', 'Ticket user 1 (R) + user 2 (R)', 'Ticket anonymous user (R)']),
            4, // Requester
            'equals',
            0
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'Ticket',
            $base_condition,
            $all_tickets,
            // Every tickets (note that it isn't consistent with the previous criteria "equals 0")
            $all_tickets,
            4, // Requester
            'contains',
            "",
            // Not very logical but GLPI return the same results for a contains "" and not contains "" queries
            $all_tickets
        );
    }

    /**
     * @dataprovider testCriteriaWithSubqueriesProvider
     */
    public function testCriteriaWithSubqueries(
        string $itemtype,
        array $criteria,
        array $expected
    ): void {
        // Run search
        $data = \Search::getDatas($itemtype, [
            'criteria' => $criteria
        ]);

        // Parse results
        $names = [];
        foreach ($data['data']['rows'] as $row) {
            $names[] = $row['raw']['ITEM_Ticket_1'];
        }

        // Sort both array as atoum is "position sensitive"
        sort($names);
        sort($expected);

        // Debug, print the last failed request
        // As there is a lot of test sets, some extra context on failure can go a long way
        $this->executeOnFailure(
            function () use ($data, $names, $expected) {
                if ($names != $expected) {
                    var_dump($data['sql']['raw']['WHERE']);
                }
            }
        );

        // Validate results
        $this->array($names)->isEqualTo($expected);
    }
}

// @codingStandardsIgnoreStart
class DupSearchOpt extends \CommonDBTM
{
    // @codingStandardsIgnoreEnd
    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'     => '12',
            'name'   => 'One search option'
        ];

        $tab[] = [
            'id'     => '12',
            'name'   => 'Any option'
        ];

        return $tab;
    }
}

// phpcs:ignore SlevomatCodingStandard.Namespaces
namespace SearchTest;

// @codingStandardsIgnoreStart
class Computer extends \Computer
{
    // @codingStandardsIgnoreEnd
    public static function getTable($classname = null)
    {
        return 'glpi_computers';
    }
}
