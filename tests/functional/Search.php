<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
use Group_User;
use Psr\Log\LogLevel;
use Ticket;
use User;

/* Test for inc/search.class.php */

/**
 * @engine isolate
 */
class Search extends DbTestCase
{
    private function doSearch($itemtype, $params, array $forcedisplay = [])
    {
        global $DEBUG_SQL, $CFG_GLPI;

       // check param itemtype exists (to avoid search errors)
        if ($itemtype !== 'AllAssets') {
            $this->class($itemtype)->isSubClassof('CommonDBTM');
        }

       // login to glpi if needed
        if (!isset($_SESSION['glpiname'])) {
            $this->login();
        }

        // force item lock
        if (in_array($itemtype, $CFG_GLPI['lock_lockable_objects'])) {
            $CFG_GLPI["lock_use_lock_item"] = 1;
            $CFG_GLPI["lock_item_list"] = [$itemtype];
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
     * This test will ensure that search options are using a valid datatype.
     */
    public function testSearchOptionsDatatype(): void
    {
        // Valid search options datatype
        $valid_datatypes = [
            // relational datatypes
            'dropdown',
            'itemlink',
            'itemtypename',

            // basic datatypes
            'bool',

            'number',
            'integer',
            'decimal',
            'count',

            'datetime',
            'date',

            'string',
            'text',

            // specific datatypes
            'color',
            'date_delay',
            'email',
            'language',
            'mac',
            'mio',
            'progressbar',
            'right',
            'timestamp',
            'weblink',

            'specific',
        ];

        $classes = $this->getClasses(
            false,
            [
                \CommonDBTM::class, // should be abstract
                \CommonImplicitTreeDropdown::class, // should be abstract
                \CommonITILRecurrentCron::class, // not searchable
                \Item_Devices::class, // should be abstract
                \NetworkPortMigration::class, // has no table by default
                \NetworkPortInstantiation::class, // should be abstract
                \NotificationSettingConfig::class, // not searchable
                \PendingReasonCron::class, // not searchable
            ]
        );
        foreach ($classes as $class) {
            if (!is_a($class, \CommonDBTM::class, true)) {
                continue;
            }

            $item = new $class();

            $search_options = $item->searchOptions();

            if (method_exists($class, 'rawSearchOptionsToAdd')) {
                // `rawSearchOptionsToAdd` parameters are not identical on all methods, so we can
                // only check classes on which this method has no parameters.
                $reflection = new \ReflectionMethod($class, 'rawSearchOptionsToAdd');
                if (count($reflection->getParameters()) === 0) {
                    $search_options = array_merge($search_options, $item->getSearchOptionsToAdd());
                }
            }

            foreach ($search_options as $so) {
                $this->array($so);

                if (!array_key_exists('datatype', $so)) {
                    continue; // datatype can be undefined
                }

                $this->boolean(in_array($so['datatype'], $valid_datatypes))
                    ->isTrue(sprintf('Unexpected `%s` search option datatype.', $so['datatype']));
            }
        }
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
        $table_ticket_user = 'glpi_tickets_users_019878060c6d5f06cbe3c4d7c31dec24';

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
        $this->string($user_order_1)->isEqualTo(" ORDER BY GROUP_CONCAT(DISTINCT CONCAT(
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')
                                ) ORDER BY CONCAT(
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')) ASC
                                ) ASC ");

        $user_order_2 = null;
        $this->when(
            function () use (&$user_order_2) {
                $user_order_2 = \Search::addOrderBy('Ticket', 4, 'DESC');
            }
        )->error()
         ->withType(E_USER_DEPRECATED)
         ->withMessage('The parameters for Search::addOrderBy have changed to allow sorting by multiple fields. Please update your calling code.')
            ->exists();
        $this->string($user_order_2)->isEqualTo(" ORDER BY GROUP_CONCAT(DISTINCT CONCAT(
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')
                                ) ORDER BY CONCAT(
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')) ASC
                                ) DESC ");

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
        $this->string($user_order_3)->isEqualTo(" ORDER BY GROUP_CONCAT(DISTINCT CONCAT(
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')
                                ) ORDER BY CONCAT(
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')) ASC
                                ) ASC ");

        $user_order_4 = null;
        $this->when(
            function () use (&$user_order_4) {
                $user_order_4 = \Search::addOrderBy('Ticket', 4, 'DESC');
            }
        )->error()
         ->withType(E_USER_DEPRECATED)
         ->withMessage('The parameters for Search::addOrderBy have changed to allow sorting by multiple fields. Please update your calling code.')
            ->exists();
        $this->string($user_order_4)->isEqualTo(" ORDER BY GROUP_CONCAT(DISTINCT CONCAT(
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')
                                ) ORDER BY CONCAT(
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')) ASC
                                ) DESC ");
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
        $table_ticket_user = 'glpi_tickets_users_019878060c6d5f06cbe3c4d7c31dec24';

        $_SESSION['glpinames_format'] = \User::FIRSTNAME_BEFORE;
        $user_order_1 = \Search::addOrderBy('Ticket', [
            [
                'searchopt_id' => 4,
                'order'        => 'ASC'
            ]
        ]);
        $this->string($user_order_1)->isEqualTo(" ORDER BY GROUP_CONCAT(DISTINCT CONCAT(
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')
                                ) ORDER BY CONCAT(
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')) ASC
                                ) ASC ");
        $user_order_2 = \Search::addOrderBy('Ticket', [
            [
                'searchopt_id' => 4,
                'order'        => 'DESC'
            ]
        ]);
        $this->string($user_order_2)->isEqualTo(" ORDER BY GROUP_CONCAT(DISTINCT CONCAT(
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')
                                ) ORDER BY CONCAT(
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')) ASC
                                ) DESC ");

        $_SESSION['glpinames_format'] = \User::REALNAME_BEFORE;
        $user_order_3 = \Search::addOrderBy('Ticket', [
            [
                'searchopt_id' => 4,
                'order'        => 'ASC'
            ]
        ]);
        $this->string($user_order_3)->isEqualTo(" ORDER BY GROUP_CONCAT(DISTINCT CONCAT(
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')
                                ) ORDER BY CONCAT(
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')) ASC
                                ) ASC ");
        $user_order_4 = \Search::addOrderBy('Ticket', [
            [
                'searchopt_id' => 4,
                'order'        => 'DESC'
            ]
        ]);
        $this->string($user_order_4)->isEqualTo(" ORDER BY GROUP_CONCAT(DISTINCT CONCAT(
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')
                                ) ORDER BY CONCAT(
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')) ASC
                                ) DESC ");
    }

    /**
     * Data provider for testAddOrderByUser
     */
    protected function testAddOrderByUserProvider(): iterable
    {
        $this->login('glpi', 'glpi');

        $user_1 = getItemByTypeName('User', TU_USER)->getID();
        $user_2 = getItemByTypeName('User', 'glpi')->getID();
        $group_1 = getItemByTypeName('Group', '_test_group_1')->getID();

        // Creates Changes with different requesters
        $this->createItems('Change', [
            // Test set on requester
            [
                'name' => 'testAddOrderByUser user 1 (R)',
                'content' => '',
                '_actors' => [
                    'requester' => [['itemtype' => 'User', 'items_id' => $user_1]],
                ]
            ],
            [
                'name' => 'testAddOrderByUser user 2 (R)',
                'content' => '',
                '_actors' => [
                    'requester' => [['itemtype' => 'User', 'items_id' => $user_2]],
                ]
            ],
            [
                'name' => 'testAddOrderByUser user 1 (R) + user 2 (R)',
                'content' => '',
                '_actors' => [
                    'requester' => [
                        ['itemtype' => 'User', 'items_id' => $user_1],
                        ['itemtype' => 'User', 'items_id' => $user_2],
                    ],
                ]
            ],
            [
                'name' => 'testAddOrderByUser anonymous user (R)',
                'content' => '',
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
            [
                'name' => 'testAddOrderByUser group 1 (R)',
                'content' => '',
                '_actors' => [
                    'requester' => [['itemtype' => 'Group', 'items_id' => $group_1]],
                ]
            ],
            [
                'name' => 'testAddOrderByUser user 1 (R) + group 1 (R)',
                'content' => '',
                '_actors' => [
                    'requester' => [
                        ['itemtype' => 'User', 'items_id' => $user_1],
                        ['itemtype' => 'Group', 'items_id' => $group_1],
                    ],
                ]
            ],
        ]);

        yield [
            'itemtype' => 'Change',
            'search_params' => [
                'is_deleted' => 0,
                'start' => 0,
                'criteria' => [
                    [
                        'field' => 1,
                        'searchtype' => 'contains',
                        'value' => 'testAddOrderByUser',
                    ],
                ],
                'sort' => 4,
                'order' => 'ASC',
            ],
            'expected_order' => [
                'testAddOrderByUser group 1 (R)',              //  no requester
                'testAddOrderByUser user 1 (R)',               //  _test_user
                'testAddOrderByUser user 1 (R) + group 1 (R)', //  _test_user
                'testAddOrderByUser user 1 (R) + user 2 (R)',  //  _test_user, glpi
                'testAddOrderByUser user 2 (R)',               //  glpi
                'testAddOrderByUser anonymous user (R)',       //  myemail@email.com
            ],
            'row_name' => 'ITEM_Change_1'
        ];

        yield [
            'itemtype' => 'Change',
            'search_params' => [
                'is_deleted' => 0,
                'start' => 0,
                'criteria' => [
                    [
                        'field' => 1,
                        'searchtype' => 'contains',
                        'value' => 'testAddOrderByUser',
                    ],
                ],
                'sort' => 4,
                'order' => 'DESC',
            ],
            'expected_order' => [
                'testAddOrderByUser anonymous user (R)',       //  myemail@email.com
                'testAddOrderByUser user 2 (R)',               //  glpi
                'testAddOrderByUser user 1 (R) + user 2 (R)',  //  _test_user, glpi
                'testAddOrderByUser user 1 (R)',               //  _test_user
                'testAddOrderByUser user 1 (R) + group 1 (R)', //  _test_user
                'testAddOrderByUser group 1 (R)',              //  no requester
            ],
            'row_name' => 'ITEM_Change_1'
        ];

        // Creates Peripheral with different users
        $this->createItems('Peripheral', [
            // Test set on user
            [
                'name' => 'testAddOrderByUser user 1 (U)',
                'entities_id' => 0,
                'users_id' => $user_1,
            ],
            [
                'name' => 'testAddOrderByUser user 2 (U)',
                'entities_id' => 0,
                'users_id' => $user_2,
            ],
            [
                'name' => 'testAddOrderByUser no user',
                'entities_id' => 0,
            ],
        ]);

        yield [
            'itemtype' => 'Peripheral',
            'search_params' => [
                'is_deleted' => 0,
                'start' => 0,
                'criteria' => [
                    [
                        'field' => 1,
                        'searchtype' => 'contains',
                        'value' => 'testAddOrderByUser',
                    ],
                ],
                'sort' => 70,
                'order' => 'ASC',
            ],
            'expected_order' => [
                'testAddOrderByUser no user',
                'testAddOrderByUser user 1 (U)', // _test_user
                'testAddOrderByUser user 2 (U)', // glpi
            ],
            'row_name' => 'ITEM_Peripheral_1'
        ];

        yield [
            'itemtype' => 'Peripheral',
            'search_params' => [
                'is_deleted' => 0,
                'start' => 0,
                'criteria' => [
                    [
                        'field' => 1,
                        'searchtype' => 'contains',
                        'value' => 'testAddOrderByUser',
                    ],
                ],
                'sort' => 70,
                'order' => 'DESC',
            ],
            'expected_order' => [
                'testAddOrderByUser user 2 (U)', // glpi
                'testAddOrderByUser user 1 (U)', // _test_user
                'testAddOrderByUser no user',
            ],
            'row_name' => 'ITEM_Peripheral_1'
        ];

        // Creates Problems with different writers
        // Create by glpi user
        $this->createItems('Problem', [
            [
                'name' => 'testAddOrderByUser by glpi',
                'content' => '',
            ],
        ]);

        // Create by tech user
        $this->login('tech', 'tech');
        $this->createItems('Problem', [
            [
                'name' => 'testAddOrderByUser by tech',
                'content' => '',
            ],
        ]);

        $this->login('glpi', 'glpi');

        yield [
            'itemtype' => 'Problem',
            'search_params' => [
                'is_deleted' => 0,
                'start' => 0,
                'criteria' => [
                    [
                        'field' => 1,
                        'searchtype' => 'contains',
                        'value' => 'testAddOrderByUser',
                    ],
                ],
                'sort' => 22,
                'order' => 'ASC',
            ],
            'expected_order' => [
                'testAddOrderByUser by glpi',
                'testAddOrderByUser by tech',
            ],
            'row_name' => 'ITEM_Problem_1'
        ];

        yield [
            'itemtype' => 'Problem',
            'search_params' => [
                'is_deleted' => 0,
                'start' => 0,
                'criteria' => [
                    [
                        'field' => 1,
                        'searchtype' => 'contains',
                        'value' => 'testAddOrderByUser',
                    ],
                ],
                'sort' => 22,
                'order' => 'DESC',
            ],
            'expected_order' => [
                'testAddOrderByUser by tech',
                'testAddOrderByUser by glpi',
            ],
            'row_name' => 'ITEM_Problem_1'
        ];

        // Last edit by
        yield [
            'itemtype' => 'Problem',
            'search_params' => [
                'is_deleted' => 0,
                'start' => 0,
                'criteria' => [
                    [
                        'field' => 1,
                        'searchtype' => 'contains',
                        'value' => 'testAddOrderByUser',
                    ],
                ],
                'sort' => 64,
                'order' => 'ASC',
            ],
            'expected_order' => [
                'testAddOrderByUser by glpi',
                'testAddOrderByUser by tech',
            ],
            'row_name' => 'ITEM_Problem_1'
        ];

        yield [
            'itemtype' => 'Problem',
            'search_params' => [
                'is_deleted' => 0,
                'start' => 0,
                'criteria' => [
                    [
                        'field' => 1,
                        'searchtype' => 'contains',
                        'value' => 'testAddOrderByUser',
                    ],
                ],
                'sort' => 64,
                'order' => 'DESC',
            ],
            'expected_order' => [
                'testAddOrderByUser by tech',
                'testAddOrderByUser by glpi',
            ],
            'row_name' => 'ITEM_Problem_1'
        ];
    }

    /**
     * @dataProvider testAddOrderByUserProvider
     */
    public function testAddOrderByUser(
        string $itemtype,
        array $search_params,
        array $expected_order,
        string $row_name
    ) {
        $data = $this->doSearch($itemtype, $search_params);

        // Extract items names
        $items = [];
        foreach ($data['data']['rows'] as $row) {
            $items[] = $row['raw'][$row_name];
        }

        // Validate order
        $this->array($items)->isEqualTo($expected_order);
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
            ['A&#38;B', '%A&#38;B%'],
            ['A&B', '%A&#38;B%'],
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
         ->contains("CONCAT(
                                    IFNULL(`glpi_users_users_id_recipient`.`realname`, ''),
                                    IFNULL(`glpi_users_users_id_recipient`.`firstname`, ''),
                                    IFNULL(`glpi_users_users_id_recipient`.`name`, '')
                                ) ASC");
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

        // Data set for tests on user searches
        list (
            $user_without_groups,
            $user_group_1,
            $user_group_1_and_2
        ) = $this->createItems(User::class, [
            ['name' => 'user_without_groups'],
            ['name' => 'user_group_1'],
            ['name' => 'user_group_1_and_2'],
        ]);
        $this->createItems(Group_User::class, [
            ['users_id' => $user_group_1->getID(), 'groups_id' => $group_1],
            ['users_id' => $user_group_1_and_2->getID(), 'groups_id' => $group_1],
            ['users_id' => $user_group_1_and_2->getID(), 'groups_id' => $group_2],
        ]);
        $all_users = ['user_without_groups', 'user_group_1', 'user_group_1_and_2'];
        $base_condition = [
            'link'       => 'AND',
            'field'      => 1, // Name
            'searchtype' => 'contains',
            'value'      => "user_",
        ];

        // Search users by groups
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'User',
            $base_condition,
            $all_users,
            ['user_group_1', 'user_group_1_and_2'],
            13, // Groups
            'equals',
            $group_1
        );
        yield from $this->testCriteriaWithSubqueriesProvider_getAllCombination(
            'User',
            $base_condition,
            $all_users,
            ['user_group_1', 'user_group_1_and_2'],
            13, // Groups
            'contains',
            "Group 1"
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
            $name = $row['raw']["ITEM_{$itemtype}_1"];

            // Clear extra data that is sometimes added by the search engine to handle display
            if (strpos($name, "$#$") !== false) {
                $name = substr($name, 0, strpos($name, "$#$"));
            }

            $names[] = $name;
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

    protected function containsCriterionProvider(): iterable
    {
        // Note:
        // Following datatypes are not tested as they do not support `contains` search operator:
        //  - bool
        //  - itemtypename
        //  - right
        // Some other datatypes are not tested for the `usehaving=true` case, or when a `computation` is
        // required, because there is no search option that corresponds to it yet.

        global $DB;
        $version_string = $DB->getVersion();
        $server  = preg_match('/-MariaDB/', $version_string) ? 'MariaDB' : 'MySQL';
        $version = preg_replace('/^((\d+\.?)+).*$/', '$1', $version_string);
        $is_mariadb      = $server === 'MariaDB';
        $is_mariadb_10_2 = $is_mariadb && version_compare($version, '10.3', '<');
        $is_mysql_5_7    = $server === 'MySQL' && version_compare($version, '8.0', '<');


        // Check simple values search.
        // Usage is only relevant for textual fields, so it is not tested on other fields.

        // datatype=dropdown
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 4, // type
            'value'             => 'test',
            'expected_and'      => "(`glpi_computertypes`.`name` LIKE '%test%')",
            'expected_and_not'  => "(`glpi_computertypes`.`name` NOT LIKE '%test%' OR `glpi_computertypes`.`name` IS NULL)",
        ];

        // datatype=dropdown (usehaving=true)
        yield [
            'itemtype'          => \Ticket::class,
            'search_option'     => 142, // document name
            'value'             => 'test',
            'expected_and'      => "(`ITEM_Ticket_142` LIKE '%test%')",
            'expected_and_not'  => "(`ITEM_Ticket_142` NOT LIKE '%test%' OR `ITEM_Ticket_142` IS NULL)",
        ];

        // datatype=itemlink
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 1, // name
            'value'             => 'test',
            'expected_and'      => "(`glpi_computers`.`name` LIKE '%test%')",
            'expected_and_not'  => "(`glpi_computers`.`name` NOT LIKE '%test%' OR `glpi_computers`.`name` IS NULL)",
        ];

        // datatype=itemlink (usehaving=true)
        yield [
            'itemtype'          => \Ticket::class,
            'search_option'     => 50, // parent tickets
            'value'             => 'test',
            'expected_and'      => "(`ITEM_Ticket_50` LIKE '%test%')",
            'expected_and_not'  => "(`ITEM_Ticket_50` NOT LIKE '%test%' OR `ITEM_Ticket_50` IS NULL)",
        ];

        // datatype=string
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 47, // uuid
            'value'             => 'test',
            'expected_and'      => "(`glpi_computers`.`uuid` LIKE '%test%')",
            'expected_and_not'  => "(`glpi_computers`.`uuid` NOT LIKE '%test%' OR `glpi_computers`.`uuid` IS NULL)",
        ];

        // datatype=text
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 16, // comment
            'value'             => 'test',
            'expected_and'      => "(`glpi_computers`.`comment` LIKE '%test%')",
            'expected_and_not'  => "(`glpi_computers`.`comment` NOT LIKE '%test%' OR `glpi_computers`.`comment` IS NULL)",
        ];

        // datatype=integer
        yield [
            'itemtype'          => \AuthLDAP::class,
            'search_option'     => 4, // port
            'value'             => 'test',
            'expected_and'      => "(`glpi_authldaps`.`port` LIKE '%test%')",
            'expected_and_not'  => "(`glpi_authldaps`.`port` NOT LIKE '%test%' OR `glpi_authldaps`.`port` IS NULL)",
        ];
        yield [
            'itemtype'          => \AuthLDAP::class,
            'search_option'     => 4, // port
            'value'             => '123',
            'expected_and'      => "(`glpi_authldaps`.`port` = 123)",
            'expected_and_not'  => "(`glpi_authldaps`.`port` <> 123)",
        ];

        // datatype=number
        yield [
            'itemtype'          => \AuthLDAP::class,
            'search_option'     => 32, // timeout
            'value'             => 'test',
            'expected_and'      => "(`glpi_authldaps`.`timeout` LIKE '%test%')",
            'expected_and_not'  => "(`glpi_authldaps`.`timeout` NOT LIKE '%test%' OR `glpi_authldaps`.`timeout` IS NULL)",
        ];
        yield [
            'itemtype'          => \AuthLDAP::class,
            'search_option'     => 32, // timeout
            'value'             => '30',
            'expected_and'      => "(`glpi_authldaps`.`timeout` = 30)",
            'expected_and_not'  => "(`glpi_authldaps`.`timeout` <> 30)",
        ];

        // datatype=number (usehaving=true)
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 115, // harddrive capacity
            'value'             => 'test',
            'expected_and'      => "(`ITEM_Computer_115` LIKE '%test%')",
            'expected_and_not'  => "(`ITEM_Computer_115` NOT LIKE '%test%' OR `ITEM_Computer_115` IS NULL)",
        ];
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 115, // harddrive capacity
            'value'             => '512',
            'expected_and'      => "(`ITEM_Computer_115` < 1512 AND `ITEM_Computer_115` > -488)",
            'expected_and_not'  => "(`ITEM_Computer_115` > 1512 OR `ITEM_Computer_115` < -488)",
        ];

        // datatype=decimal
        yield [
            'itemtype'          => \Budget::class,
            'search_option'     => 7, // value
            'value'             => 'test',
            'expected_and'      => "(`glpi_budgets`.`value` LIKE '%test%')",
            'expected_and_not'  => "(`glpi_budgets`.`value` NOT LIKE '%test%' OR `glpi_budgets`.`value` IS NULL)",
        ];
        yield [
            'itemtype'          => \Budget::class,
            'search_option'     => 7, // value
            'value'             => '1500',
            'expected_and'      => "(`glpi_budgets`.`value` LIKE '%1500.%')",
            'expected_and_not'  => "(`glpi_budgets`.`value` NOT LIKE '%1500.%' OR `glpi_budgets`.`value` IS NULL)",
        ];
        yield [
            'itemtype'          => \Budget::class,
            'search_option'     => 7, // value
            'value'             => '10.25',
            'expected_and'      => "(`glpi_budgets`.`value` LIKE '%10.2%')",
            'expected_and_not'  => "(`glpi_budgets`.`value` NOT LIKE '%10.2%' OR `glpi_budgets`.`value` IS NULL)",
        ];

        // datatype=decimal (usehaving=true)
        yield [
            'itemtype'          => \Contract::class,
            'search_option'     => 11, // totalcost
            'value'             => 'test',
            'expected_and'      => "(`ITEM_Contract_11` LIKE '%test%')",
            'expected_and_not'  => "(`ITEM_Contract_11` NOT LIKE '%test%' OR `ITEM_Contract_11` IS NULL)",
        ];
        yield [
            'itemtype'          => \Contract::class,
            'search_option'     => 11, // totalcost
            'value'             => '250',
            'expected_and'      => "(`ITEM_Contract_11` = 250)",
            'expected_and_not'  => "(`ITEM_Contract_11` <> 250)",
        ];

        // datatype=count (usehaving=true)
        yield [
            'itemtype'          => \Ticket::class,
            'search_option'     => 27, // number of followups
            'value'             => 'test',
            'expected_and'      => "(`ITEM_Ticket_27` LIKE '%test%')",
            'expected_and_not'  => "(`ITEM_Ticket_27` NOT LIKE '%test%' OR `ITEM_Ticket_27` IS NULL)",
        ];
        yield [
            'itemtype'          => \Ticket::class,
            'search_option'     => 27, // number of followups
            'value'             => '10',
            'expected_and'      => "(`ITEM_Ticket_27` = 10)",
            'expected_and_not'  => "(`ITEM_Ticket_27` <> 10)",
        ];

        // datatype=mio (usehaving=true)
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 111, // memory size
            'value'             => 'test',
            'expected_and'      => "(`ITEM_Computer_111` LIKE '%test%')",
            'expected_and_not'  => "(`ITEM_Computer_111` NOT LIKE '%test%' OR `ITEM_Computer_111` IS NULL)",
        ];
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 111, // memory size
            'value'             => '512',
            'expected_and'      => "(`ITEM_Computer_111` < 612 AND `ITEM_Computer_111` > 412)",
            'expected_and_not'  => "(`ITEM_Computer_111` > 612 OR `ITEM_Computer_111` < 412)",
        ];

        // datatype=progressbar (with computation)
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 152, // harddrive freepercent
            'value'             => 'test',
            'expected_and'      => "(LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.totalsize, 0)), 3, 0) LIKE '%test%')",
            'expected_and_not'  => "(LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.totalsize, 0)), 3, 0) NOT LIKE '%test%' OR LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.totalsize, 0)), 3, 0) IS NULL)",
        ];
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 152, // harddrive freepercent
            'value'             => '50',
            'expected_and'      => "(LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.totalsize, 0)), 3, 0) >= 48 AND LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.totalsize, 0)), 3, 0) <= 52)",
            'expected_and_not'  => "(LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.totalsize, 0)), 3, 0) < 48 OR LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.totalsize, 0)), 3, 0) > 52 OR LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.totalsize, 0)), 3, 0) IS NULL)",
        ];

        // datatype=timestamp
        yield [
            'itemtype'          => \CronTask::class,
            'search_option'     => 6, // frequency
            'value'             => 'test',
            'expected_and'      => "(`glpi_crontasks`.`frequency` LIKE '%test%')",
            'expected_and_not'  => "(`glpi_crontasks`.`frequency` NOT LIKE '%test%' OR `glpi_crontasks`.`frequency` IS NULL)",
        ];
        yield [
            'itemtype'          => \CronTask::class,
            'search_option'     => 6, // frequency
            'value'             => '3600',
            'expected_and'      => "(`glpi_crontasks`.`frequency` = 3600)",
            'expected_and_not'  => "(`glpi_crontasks`.`frequency` <> 3600)",
        ];

        // datatype=timestamp (usehaving=true)
        yield [
            'itemtype'          => \Ticket::class,
            'search_option'     => 49, // actiontime
            'value'             => 'test',
            'expected_and'      => "(`ITEM_Ticket_49` LIKE '%test%')",
            'expected_and_not'  => "(`ITEM_Ticket_49` NOT LIKE '%test%' OR `ITEM_Ticket_49` IS NULL)",
        ];
        yield [
            'itemtype'          => \Ticket::class,
            'search_option'     => 49, // actiontime
            'value'             => '3600',
            'expected_and'      => "(`ITEM_Ticket_49` = 3600)",
            'expected_and_not'  => "(`ITEM_Ticket_49` <> 3600)",
        ];

        // datatype=datetime
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 9, // last_inventory_update
            'value'             => 'test',
            'expected_and'      => "(CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) LIKE '%test%')",
            'expected_and_not'  => "(CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) NOT LIKE '%test%' OR CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) IS NULL)",
        ];
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 9, // last_inventory_update
            'value'             => '2023-06',
            'expected_and'      => "(CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) LIKE '%2023-06%')",
            'expected_and_not'  => "(CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) NOT LIKE '%2023-06%' OR CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) IS NULL)",
        ];

        // datatype=datetime (usehaving=true)
        yield [
            'itemtype'          => \Ticket::class,
            'search_option'     => 188, // next_escalation_level
            'value'             => 'test',
            'expected_and'      => "(`ITEM_Ticket_188` LIKE '%test%')",
            'expected_and_not'  => "(`ITEM_Ticket_188` NOT LIKE '%test%' OR `ITEM_Ticket_188` IS NULL)",
        ];
        yield [
            'itemtype'          => \Ticket::class,
            'search_option'     => 188, // next_escalation_level
            'value'             => '2023-06',
            'expected_and'      => "(`ITEM_Ticket_188` LIKE '%2023-06%')",
            'expected_and_not'  => "(`ITEM_Ticket_188` NOT LIKE '%2023-06%' OR `ITEM_Ticket_188` IS NULL)",
        ];

        // datatype=date
        yield [
            'itemtype'          => \Budget::class,
            'search_option'     => 5, // begin_date
            'value'             => 'test',
            'expected_and'      => "(CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) LIKE '%test%')",
            'expected_and_not'  => "(CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) NOT LIKE '%test%' OR CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) IS NULL)",
        ];
        yield [
            'itemtype'          => \Budget::class,
            'search_option'     => 5, // begin_date
            'value'             => '2023',
            'expected_and'      => "(CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) LIKE '%2023%')",
            'expected_and_not'  => "(CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) NOT LIKE '%2023%' OR CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) IS NULL)",
        ];

        // datatype=date_delay
        yield [
            'itemtype'          => \Contract::class,
            'search_option'     => 20, // end_date
            'value'             => 'test',
            'expected_and'      => "(ADDDATE(`glpi_contracts`.begin_date, INTERVAL (`glpi_contracts`.duration) MONTH) LIKE '%test%')",
            'expected_and_not'  => "(ADDDATE(`glpi_contracts`.begin_date, INTERVAL (`glpi_contracts`.duration) MONTH) NOT LIKE '%test%' OR ADDDATE(`glpi_contracts`.begin_date, INTERVAL (`glpi_contracts`.duration) MONTH) IS NULL)",
        ];
        if ($is_mysql_5_7) {
            // log for both AND and AND NOT cases
            $this->hasSqlLogRecordThatContains("Truncated incorrect date value: '%test%'", LogLevel::WARNING);
            $this->hasSqlLogRecordThatContains("Truncated incorrect date value: '%test%'", LogLevel::WARNING);
        }
        yield [
            'itemtype'          => \Contract::class,
            'search_option'     => 20, // end_date
            'value'             => '2023-12',
            'expected_and'      => "(ADDDATE(`glpi_contracts`.begin_date, INTERVAL (`glpi_contracts`.duration) MONTH) LIKE '%2023-12%')",
            'expected_and_not'  => "(ADDDATE(`glpi_contracts`.begin_date, INTERVAL (`glpi_contracts`.duration) MONTH) NOT LIKE '%2023-12%' OR ADDDATE(`glpi_contracts`.begin_date, INTERVAL (`glpi_contracts`.duration) MONTH) IS NULL)",
        ];
        if ($is_mysql_5_7) {
            // log for both AND and AND NOT cases
            $this->hasSqlLogRecordThatContains("Truncated incorrect date value: '%2023-12%'", LogLevel::WARNING);
            $this->hasSqlLogRecordThatContains("Truncated incorrect date value: '%2023-12%'", LogLevel::WARNING);
        }

        // datatype=email
        yield [
            'itemtype'          => \Contact::class,
            'search_option'     => 6, // email
            'value'             => 'test',
            'expected_and'      => "(`glpi_contacts`.`email` LIKE '%test%')",
            'expected_and_not'  => "(`glpi_contacts`.`email` NOT LIKE '%test%' OR `glpi_contacts`.`email` IS NULL)",
        ];

        // datatype=weblink
        yield [
            'itemtype'          => \Document::class,
            'search_option'     => 4, // link
            'value'             => 'test',
            'expected_and'      => "(`glpi_documents`.`link` LIKE '%test%')",
            'expected_and_not'  => "(`glpi_documents`.`link` NOT LIKE '%test%' OR `glpi_documents`.`link` IS NULL)",
        ];

        // datatype=mac
        yield [
            'itemtype'          => \DeviceNetworkCard::class,
            'search_option'     => 11, // mac_default
            'value'             => 'test',
            'expected_and'      => "(`glpi_devicenetworkcards`.`mac_default` LIKE '%test%')",
            'expected_and_not'  => "(`glpi_devicenetworkcards`.`mac_default` NOT LIKE '%test%' OR `glpi_devicenetworkcards`.`mac_default` IS NULL)",
        ];
        yield [
            'itemtype'          => \DeviceNetworkCard::class,
            'search_option'     => 11, // mac_default
            'value'             => 'a2:ef:00',
            'expected_and'      => "(`glpi_devicenetworkcards`.`mac_default` LIKE '%a2:ef:00%')",
            'expected_and_not'  => "(`glpi_devicenetworkcards`.`mac_default` NOT LIKE '%a2:ef:00%' OR `glpi_devicenetworkcards`.`mac_default` IS NULL)",
        ];

        // datatype=color
        yield [
            'itemtype'          => \Cable::class,
            'search_option'     => 15, // color
            'value'             => 'test',
            'expected_and'      => "(`glpi_cables`.`color` LIKE '%test%')",
            'expected_and_not'  => "(`glpi_cables`.`color` NOT LIKE '%test%' OR `glpi_cables`.`color` IS NULL)",
        ];
        yield [
            'itemtype'          => \Cable::class,
            'search_option'     => 15, // color
            'value'             => '#ffffff',
            'expected_and'      => "(`glpi_cables`.`color` LIKE '%#ffffff%')",
            'expected_and_not'  => "(`glpi_cables`.`color` NOT LIKE '%#ffffff%' OR `glpi_cables`.`color` IS NULL)",
        ];

        // datatype=language
        yield [
            'itemtype'          => \User::class,
            'search_option'     => 17, // language
            'value'             => 'test',
            'expected_and'      => "(`glpi_users`.`language` LIKE '%test%')",
            'expected_and_not'  => "(`glpi_users`.`language` NOT LIKE '%test%' OR `glpi_users`.`language` IS NULL)",
        ];
        yield [
            'itemtype'          => \User::class,
            'search_option'     => 17, // language
            'value'             => 'en_',
            'expected_and'      => "(`glpi_users`.`language` LIKE '%en\_%')",
            'expected_and_not'  => "(`glpi_users`.`language` NOT LIKE '%en\_%' OR `glpi_users`.`language` IS NULL)",
        ];

        // Check `NULL` special value
        foreach (['NULL', 'null'] as $null_value) {
            // datatype=dropdown
            yield [
                'itemtype'          => \Computer::class,
                'search_option'     => 4, // type
                'value'             => $null_value,
                'expected_and'      => "(`glpi_computertypes`.`name` IS NULL OR `glpi_computertypes`.`name` = '')",
                'expected_and_not'  => "(`glpi_computertypes`.`name` IS NOT NULL AND `glpi_computertypes`.`name` <> '')",
            ];

            // datatype=dropdown (usehaving=true)
            yield [
                'itemtype'          => \Ticket::class,
                'search_option'     => 142, // document name
                'value'             => $null_value,
                'expected_and'      => "(`ITEM_Ticket_142` IS NULL OR `ITEM_Ticket_142` = '')",
                'expected_and_not'  => "(`ITEM_Ticket_142` IS NOT NULL AND `ITEM_Ticket_142` <> '')",
            ];

            // datatype=itemlink
            yield [
                'itemtype'          => \Computer::class,
                'search_option'     => 1, // name
                'value'             => $null_value,
                'expected_and'      => "(`glpi_computers`.`name` IS NULL OR `glpi_computers`.`name` = '')",
                'expected_and_not'  => "(`glpi_computers`.`name` IS NOT NULL AND `glpi_computers`.`name` <> '')",
            ];

            // datatype=itemlink (usehaving=true)
            yield [
                'itemtype'          => \Ticket::class,
                'search_option'     => 50, // parent tickets
                'value'             => $null_value,
                'expected_and'      => "(`ITEM_Ticket_50` IS NULL OR `ITEM_Ticket_50` = '')",
                'expected_and_not'  => "(`ITEM_Ticket_50` IS NOT NULL AND `ITEM_Ticket_50` <> '')",
            ];

            // datatype=string
            yield [
                'itemtype'          => \Computer::class,
                'search_option'     => 47, // uuid
                'value'             => $null_value,
                'expected_and'      => "(`glpi_computers`.`uuid` IS NULL OR `glpi_computers`.`uuid` = '')",
                'expected_and_not'  => "(`glpi_computers`.`uuid` IS NOT NULL AND `glpi_computers`.`uuid` <> '')",
            ];

            // datatype=text
            yield [
                'itemtype'          => \Computer::class,
                'search_option'     => 16, // comment
                'value'             => $null_value,
                'expected_and'      => "(`glpi_computers`.`comment` IS NULL OR `glpi_computers`.`comment` = '')",
                'expected_and_not'  => "(`glpi_computers`.`comment` IS NOT NULL AND `glpi_computers`.`comment` <> '')",
            ];

            // datatype=integer
            yield [
                'itemtype'          => \AuthLDAP::class,
                'search_option'     => 4, // port
                'value'             => $null_value,
                'expected_and'      => "(`glpi_authldaps`.`port` IS NULL OR `glpi_authldaps`.`port` = '')",
                'expected_and_not'  => "(`glpi_authldaps`.`port` IS NOT NULL AND `glpi_authldaps`.`port` <> '')",
            ];
            // log for both AND and AND NOT cases
            if ($is_mariadb_10_2) {
                $this->hasSqlLogRecordThatContains("Truncated incorrect DOUBLE value: ''", LogLevel::WARNING);
                $this->hasSqlLogRecordThatContains("Truncated incorrect DOUBLE value: ''", LogLevel::WARNING);
            } elseif ($is_mariadb) {
                $this->hasSqlLogRecordThatContains("Truncated incorrect DECIMAL value: ''", LogLevel::WARNING);
                $this->hasSqlLogRecordThatContains("Truncated incorrect DECIMAL value: ''", LogLevel::WARNING);
            }

            // datatype=number
            yield [
                'itemtype'          => \AuthLDAP::class,
                'search_option'     => 32, // timeout
                'value'             => $null_value,
                'expected_and'      => "(`glpi_authldaps`.`timeout` IS NULL OR `glpi_authldaps`.`timeout` = '')",
                'expected_and_not'  => "(`glpi_authldaps`.`timeout` IS NOT NULL AND `glpi_authldaps`.`timeout` <> '')",
            ];
            // log for both AND and AND NOT cases
            if ($is_mariadb_10_2) {
                $this->hasSqlLogRecordThatContains("Truncated incorrect DOUBLE value: ''", LogLevel::WARNING);
                $this->hasSqlLogRecordThatContains("Truncated incorrect DOUBLE value: ''", LogLevel::WARNING);
            } elseif ($is_mariadb) {
                $this->hasSqlLogRecordThatContains("Truncated incorrect DECIMAL value: ''", LogLevel::WARNING);
                $this->hasSqlLogRecordThatContains("Truncated incorrect DECIMAL value: ''", LogLevel::WARNING);
            }

            // datatype=number (usehaving=true)
            yield [
                'itemtype'          => \Computer::class,
                'search_option'     => 115, // harddrive capacity
                'value'             => $null_value,
                'expected_and'      => "(`ITEM_Computer_115` IS NULL OR `ITEM_Computer_115` = '')",
                'expected_and_not'  => "(`ITEM_Computer_115` IS NOT NULL AND `ITEM_Computer_115` <> '')",
            ];

            // datatype=decimal
            yield [
                'itemtype'          => \Budget::class,
                'search_option'     => 7, // value
                'value'             => $null_value,
                'expected_and'      => "(`glpi_budgets`.`value` IS NULL OR `glpi_budgets`.`value` = '')",
                'expected_and_not'  => "(`glpi_budgets`.`value` IS NOT NULL AND `glpi_budgets`.`value` <> '')",
            ];
            // log for both AND and AND NOT cases
            $this->hasSqlLogRecordThatContains("Truncated incorrect DECIMAL value: ''", LogLevel::WARNING);
            $this->hasSqlLogRecordThatContains("Truncated incorrect DECIMAL value: ''", LogLevel::WARNING);

            // datatype=decimal (usehaving=true)
            yield [
                'itemtype'          => \Contract::class,
                'search_option'     => 11, // totalcost
                'value'             => $null_value,
                'expected_and'      => "(`ITEM_Contract_11` IS NULL OR `ITEM_Contract_11` = '')",
                'expected_and_not'  => "(`ITEM_Contract_11` IS NOT NULL AND `ITEM_Contract_11` <> '')",
            ];

            // datatype=count (usehaving=true)
            yield [
                'itemtype'          => \Ticket::class,
                'search_option'     => 27, // number of followups
                'value'             => $null_value,
                'expected_and'      => "(`ITEM_Ticket_27` IS NULL OR `ITEM_Ticket_27` = '')",
                'expected_and_not'  => "(`ITEM_Ticket_27` IS NOT NULL AND `ITEM_Ticket_27` <> '')",
            ];
            // log for both AND and AND NOT cases
            if ($is_mariadb_10_2) {
                $this->hasSqlLogRecordThatContains("Truncated incorrect DOUBLE value: ''", LogLevel::WARNING);
                $this->hasSqlLogRecordThatContains("Truncated incorrect DOUBLE value: ''", LogLevel::WARNING);
            } elseif ($is_mariadb) {
                $this->hasSqlLogRecordThatContains("Truncated incorrect DECIMAL value: ''", LogLevel::WARNING);
                $this->hasSqlLogRecordThatContains("Truncated incorrect DECIMAL value: ''", LogLevel::WARNING);
            }

            // datatype=mio (usehaving=true)
            yield [
                'itemtype'          => \Computer::class,
                'search_option'     => 111, // memory size
                'value'             => $null_value,
                'expected_and'      => "(`ITEM_Computer_111` IS NULL OR `ITEM_Computer_111` = '')",
                'expected_and_not'  => "(`ITEM_Computer_111` IS NOT NULL AND `ITEM_Computer_111` <> '')",
            ];

            // datatype=progressbar (with computation)
            yield [
                'itemtype'          => \Computer::class,
                'search_option'     => 152, // harddrive freepercent
                'value'             => $null_value,
                'expected_and'      => "(LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.totalsize, 0)), 3, 0) IS NULL OR LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.totalsize, 0)), 3, 0) = '')",
                'expected_and_not'  => "(LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.totalsize, 0)), 3, 0) IS NOT NULL AND LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.totalsize, 0)), 3, 0) <> '')",
            ];

            // datatype=timestamp
            yield [
                'itemtype'          => \CronTask::class,
                'search_option'     => 6, // frequency
                'value'             => $null_value,
                'expected_and'      => "(`glpi_crontasks`.`frequency` IS NULL OR `glpi_crontasks`.`frequency` = '')",
                'expected_and_not'  => "(`glpi_crontasks`.`frequency` IS NOT NULL AND `glpi_crontasks`.`frequency` <> '')",
            ];
            // log for both AND and AND NOT cases
            if ($is_mariadb_10_2) {
                $this->hasSqlLogRecordThatContains("Truncated incorrect DOUBLE value: ''", LogLevel::WARNING);
                $this->hasSqlLogRecordThatContains("Truncated incorrect DOUBLE value: ''", LogLevel::WARNING);
            } elseif ($is_mariadb) {
                $this->hasSqlLogRecordThatContains("Truncated incorrect DECIMAL value: ''", LogLevel::WARNING);
                $this->hasSqlLogRecordThatContains("Truncated incorrect DECIMAL value: ''", LogLevel::WARNING);
            }

            // datatype=timestamp (usehaving=true)
            yield [
                'itemtype'          => \Ticket::class,
                'search_option'     => 49, // actiontime
                'value'             => $null_value,
                'expected_and'      => "(`ITEM_Ticket_49` IS NULL OR `ITEM_Ticket_49` = '')",
                'expected_and_not'  => "(`ITEM_Ticket_49` IS NOT NULL AND `ITEM_Ticket_49` <> '')",
            ];

            // datatype=datetime
            yield [
                'itemtype'          => \Computer::class,
                'search_option'     => 9, // last_inventory_update
                'value'             => $null_value,
                'expected_and'      => "(CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) IS NULL OR CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) = '')",
                'expected_and_not'  => "(CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) IS NOT NULL AND CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) <> '')",
            ];

            // datatype=datetime computed field
            yield [
                'itemtype'          => \Ticket::class,
                'search_option'     => 188, // next_escalation_level
                'value'             => $null_value,
                'expected_and'      => "(`ITEM_Ticket_188` IS NULL OR `ITEM_Ticket_188` = '')",
                'expected_and_not'  => "(`ITEM_Ticket_188` IS NOT NULL AND `ITEM_Ticket_188` <> '')",
            ];

            // datatype=date
            yield [
                'itemtype'          => \Budget::class,
                'search_option'     => 5, // begin_date
                'value'             => $null_value,
                'expected_and'      => "(CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) IS NULL OR CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) = '')",
                'expected_and_not'  => "(CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) IS NOT NULL AND CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) <> '')",
            ];

            // datatype=date_delay
            /*
             * FIXME Following search fails due to the following SQL error: `Error: Incorrect DATE value: ''`.
            yield [
                'itemtype'          => \Contract::class,
                'search_option'     => 20, // end_date
                'value'             => $null_value,
                'expected_and'      => "(ADDDATE(`glpi_contracts`.begin_date, INTERVAL (`glpi_contracts`.duration ) MONTH) IS  NULL  OR ADDDATE(`glpi_contracts`.begin_date, INTERVAL (`glpi_contracts`.duration ) MONTH) = '')",
                'expected_and_not'  => "(ADDDATE(`glpi_contracts`.begin_date, INTERVAL (`glpi_contracts`.duration ) MONTH) IS NOT NULL  OR ADDDATE(`glpi_contracts`.begin_date, INTERVAL (`glpi_contracts`.duration ) MONTH) = '')",
            ];
            */

            // datatype=email
            yield [
                'itemtype'          => \Contact::class,
                'search_option'     => 6, // email
                'value'             => $null_value,
                'expected_and'      => "(`glpi_contacts`.`email` IS NULL OR `glpi_contacts`.`email` = '')",
                'expected_and_not'  => "(`glpi_contacts`.`email` IS NOT NULL AND `glpi_contacts`.`email` <> '')",
            ];

            // datatype=weblink
            yield [
                'itemtype'          => \Document::class,
                'search_option'     => 4, // link
                'value'             => $null_value,
                'expected_and'      => "(`glpi_documents`.`link` IS NULL OR `glpi_documents`.`link` = '')",
                'expected_and_not'  => "(`glpi_documents`.`link` IS NOT NULL AND `glpi_documents`.`link` <> '')",
            ];

            // datatype=mac
            yield [
                'itemtype'          => \DeviceNetworkCard::class,
                'search_option'     => 11, // mac_default
                'value'             => $null_value,
                'expected_and'      => "(`glpi_devicenetworkcards`.`mac_default` IS NULL OR `glpi_devicenetworkcards`.`mac_default` = '')",
                'expected_and_not'  => "(`glpi_devicenetworkcards`.`mac_default` IS NOT NULL AND `glpi_devicenetworkcards`.`mac_default` <> '')",
            ];

            // datatype=color
            yield [
                'itemtype'          => \Cable::class,
                'search_option'     => 15, // color
                'value'             => $null_value,
                'expected_and'      => "(`glpi_cables`.`color` IS NULL OR `glpi_cables`.`color` = '')",
                'expected_and_not'  => "(`glpi_cables`.`color` IS NOT NULL AND `glpi_cables`.`color` <> '')",
            ];

            // datatype=language
            yield [
                'itemtype'          => \User::class,
                'search_option'     => 17, // language
                'value'             => $null_value,
                'expected_and'      => "(`glpi_users`.`language` IS NULL OR `glpi_users`.`language` = '')",
                'expected_and_not'  => "(`glpi_users`.`language` IS NOT NULL AND `glpi_users`.`language` <> '')",
            ];
        }

        // Check `^` and `$` operators.
        // Usage is only relevant for textual fields, so it is not tested on other fields.

        // datatype=dropdown
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 4, // type
            'value'             => '^test',
            'expected_and'      => "(`glpi_computertypes`.`name` LIKE 'test%')",
            'expected_and_not'  => "(`glpi_computertypes`.`name` NOT LIKE 'test%' OR `glpi_computertypes`.`name` IS NULL)",
        ];
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 4, // type
            'value'             => 'test$',
            'expected_and'      => "(`glpi_computertypes`.`name` LIKE '%test')",
            'expected_and_not'  => "(`glpi_computertypes`.`name` NOT LIKE '%test' OR `glpi_computertypes`.`name` IS NULL)",
        ];
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 4, // type
            'value'             => '^test$',
            'expected_and'      => "(`glpi_computertypes`.`name` LIKE 'test')",
            'expected_and_not'  => "(`glpi_computertypes`.`name` NOT LIKE 'test' OR `glpi_computertypes`.`name` IS NULL)",
        ];

        // datatype=dropdown (usehaving=true)
        yield [
            'itemtype'          => \Ticket::class,
            'search_option'     => 142, // document name
            'value'             => '^test',
            'expected_and'      => "(`ITEM_Ticket_142` LIKE 'test%')",
            'expected_and_not'  => "(`ITEM_Ticket_142` NOT LIKE 'test%' OR `ITEM_Ticket_142` IS NULL)",
        ];
        yield [
            'itemtype'          => \Ticket::class,
            'search_option'     => 142, // document name
            'value'             => 'test$',
            'expected_and'      => "(`ITEM_Ticket_142` LIKE '%test')",
            'expected_and_not'  => "(`ITEM_Ticket_142` NOT LIKE '%test' OR `ITEM_Ticket_142` IS NULL)",
        ];
        yield [
            'itemtype'          => \Ticket::class,
            'search_option'     => 142, // document name
            'value'             => '^test$',
            'expected_and'      => "(`ITEM_Ticket_142` LIKE 'test')",
            'expected_and_not'  => "(`ITEM_Ticket_142` NOT LIKE 'test' OR `ITEM_Ticket_142` IS NULL)",
        ];

        // datatype=itemlink
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 1, // name
            'value'             => '^test',
            'expected_and'      => "(`glpi_computers`.`name` LIKE 'test%')",
            'expected_and_not'  => "(`glpi_computers`.`name` NOT LIKE 'test%' OR `glpi_computers`.`name` IS NULL)",
        ];
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 1, // name
            'value'             => 'test$',
            'expected_and'      => "(`glpi_computers`.`name` LIKE '%test')",
            'expected_and_not'  => "(`glpi_computers`.`name` NOT LIKE '%test' OR `glpi_computers`.`name` IS NULL)",
        ];
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 1, // name
            'value'             => '^test$',
            'expected_and'      => "(`glpi_computers`.`name` LIKE 'test')",
            'expected_and_not'  => "(`glpi_computers`.`name` NOT LIKE 'test' OR `glpi_computers`.`name` IS NULL)",
        ];

        // datatype=itemlink (usehaving=true)
        yield [
            'itemtype'          => \Ticket::class,
            'search_option'     => 50, // parent tickets
            'value'             => '^test',
            'expected_and'      => "(`ITEM_Ticket_50` LIKE 'test%')",
            'expected_and_not'  => "(`ITEM_Ticket_50` NOT LIKE 'test%' OR `ITEM_Ticket_50` IS NULL)",
        ];
        yield [
            'itemtype'          => \Ticket::class,
            'search_option'     => 50, // parent tickets
            'value'             => 'test$',
            'expected_and'      => "(`ITEM_Ticket_50` LIKE '%test')",
            'expected_and_not'  => "(`ITEM_Ticket_50` NOT LIKE '%test' OR `ITEM_Ticket_50` IS NULL)",
        ];
        yield [
            'itemtype'          => \Ticket::class,
            'search_option'     => 50, // parent tickets
            'value'             => '^test$',
            'expected_and'      => "(`ITEM_Ticket_50` LIKE 'test')",
            'expected_and_not'  => "(`ITEM_Ticket_50` NOT LIKE 'test' OR `ITEM_Ticket_50` IS NULL)",
        ];

        // datatype=string
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 47, // uuid
            'value'             => '^test',
            'expected_and'      => "(`glpi_computers`.`uuid` LIKE 'test%')",
            'expected_and_not'  => "(`glpi_computers`.`uuid` NOT LIKE 'test%' OR `glpi_computers`.`uuid` IS NULL)",
        ];
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 47, // uuid
            'value'             => 'test$',
            'expected_and'      => "(`glpi_computers`.`uuid` LIKE '%test')",
            'expected_and_not'  => "(`glpi_computers`.`uuid` NOT LIKE '%test' OR `glpi_computers`.`uuid` IS NULL)",
        ];
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 47, // uuid
            'value'             => '^test$',
            'expected_and'      => "(`glpi_computers`.`uuid` LIKE 'test')",
            'expected_and_not'  => "(`glpi_computers`.`uuid` NOT LIKE 'test' OR `glpi_computers`.`uuid` IS NULL)",
        ];

        // datatype=text
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 16, // comment
            'value'             => '^test',
            'expected_and'      => "(`glpi_computers`.`comment` LIKE 'test%')",
            'expected_and_not'  => "(`glpi_computers`.`comment` NOT LIKE 'test%' OR `glpi_computers`.`comment` IS NULL)",
        ];
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 16, // comment
            'value'             => 'test$',
            'expected_and'      => "(`glpi_computers`.`comment` LIKE '%test')",
            'expected_and_not'  => "(`glpi_computers`.`comment` NOT LIKE '%test' OR `glpi_computers`.`comment` IS NULL)",
        ];
        yield [
            'itemtype'          => \Computer::class,
            'search_option'     => 16, // comment
            'value'             => '^test$',
            'expected_and'      => "(`glpi_computers`.`comment` LIKE 'test')",
            'expected_and_not'  => "(`glpi_computers`.`comment` NOT LIKE 'test' OR `glpi_computers`.`comment` IS NULL)",
        ];

        // datatype=email
        yield [
            'itemtype'          => \Contact::class,
            'search_option'     => 6, // email
            'value'             => '^myname@',
            'expected_and'      => "(`glpi_contacts`.`email` LIKE 'myname@%')",
            'expected_and_not'  => "(`glpi_contacts`.`email` NOT LIKE 'myname@%' OR `glpi_contacts`.`email` IS NULL)",
        ];
        yield [
            'itemtype'          => \Contact::class,
            'search_option'     => 6, // email
            'value'             => '@domain.tld$',
            'expected_and'      => "(`glpi_contacts`.`email` LIKE '%@domain.tld')",
            'expected_and_not'  => "(`glpi_contacts`.`email` NOT LIKE '%@domain.tld' OR `glpi_contacts`.`email` IS NULL)",
        ];
        yield [
            'itemtype'          => \Contact::class,
            'search_option'     => 6, // email
            'value'             => '^myname@domain.tld$',
            'expected_and'      => "(`glpi_contacts`.`email` LIKE 'myname@domain.tld')",
            'expected_and_not'  => "(`glpi_contacts`.`email` NOT LIKE 'myname@domain.tld' OR `glpi_contacts`.`email` IS NULL)",
        ];

        // datatype=weblink
        yield [
            'itemtype'          => \Document::class,
            'search_option'     => 4, // link
            'value'             => '^ftp://',
            'expected_and'      => "(`glpi_documents`.`link` LIKE 'ftp://%')",
            'expected_and_not'  => "(`glpi_documents`.`link` NOT LIKE 'ftp://%' OR `glpi_documents`.`link` IS NULL)",
        ];
        yield [
            'itemtype'          => \Document::class,
            'search_option'     => 4, // link
            'value'             => '.pdf$',
            'expected_and'      => "(`glpi_documents`.`link` LIKE '%.pdf')",
            'expected_and_not'  => "(`glpi_documents`.`link` NOT LIKE '%.pdf' OR `glpi_documents`.`link` IS NULL)",
        ];
        yield [
            'itemtype'          => \Document::class,
            'search_option'     => 4, // link
            'value'             => '^ftp://domain.tld/document.pdf$',
            'expected_and'      => "(`glpi_documents`.`link` LIKE 'ftp://domain.tld/document.pdf')",
            'expected_and_not'  => "(`glpi_documents`.`link` NOT LIKE 'ftp://domain.tld/document.pdf' OR `glpi_documents`.`link` IS NULL)",
        ];

        // datatype=mac
        yield [
            'itemtype'          => \DeviceNetworkCard::class,
            'search_option'     => 11, // mac_default
            'value'             => '^a2:e5:aa',
            'expected_and'      => "(`glpi_devicenetworkcards`.`mac_default` LIKE 'a2:e5:aa%')",
            'expected_and_not'  => "(`glpi_devicenetworkcards`.`mac_default` NOT LIKE 'a2:e5:aa%' OR `glpi_devicenetworkcards`.`mac_default` IS NULL)",
        ];
        yield [
            'itemtype'          => \DeviceNetworkCard::class,
            'search_option'     => 11, // mac_default
            'value'             => 'a2:e5:aa$',
            'expected_and'      => "(`glpi_devicenetworkcards`.`mac_default` LIKE '%a2:e5:aa')",
            'expected_and_not'  => "(`glpi_devicenetworkcards`.`mac_default` NOT LIKE '%a2:e5:aa' OR `glpi_devicenetworkcards`.`mac_default` IS NULL)",
        ];
        yield [
            'itemtype'          => \DeviceNetworkCard::class,
            'search_option'     => 11, // mac_default
            'value'             => '^15:f4:q4:a2:e5:aa$',
            'expected_and'      => "(`glpi_devicenetworkcards`.`mac_default` LIKE '15:f4:q4:a2:e5:aa')",
            'expected_and_not'  => "(`glpi_devicenetworkcards`.`mac_default` NOT LIKE '15:f4:q4:a2:e5:aa' OR `glpi_devicenetworkcards`.`mac_default` IS NULL)",
        ];

        // datatype=color
        yield [
            'itemtype'          => \Cable::class,
            'search_option'     => 15, // color
            'value'             => '^#00',
            'expected_and'      => "(`glpi_cables`.`color` LIKE '#00%')",
            'expected_and_not'  => "(`glpi_cables`.`color` NOT LIKE '#00%' OR `glpi_cables`.`color` IS NULL)",
        ];
        yield [
            'itemtype'          => \Cable::class,
            'search_option'     => 15, // color
            'value'             => 'ff$',
            'expected_and'      => "(`glpi_cables`.`color` LIKE '%ff')",
            'expected_and_not'  => "(`glpi_cables`.`color` NOT LIKE '%ff' OR `glpi_cables`.`color` IS NULL)",
        ];
        yield [
            'itemtype'          => \Cable::class,
            'search_option'     => 15, // color
            'value'             => '^#00aaff$',
            'expected_and'      => "(`glpi_cables`.`color` LIKE '#00aaff')",
            'expected_and_not'  => "(`glpi_cables`.`color` NOT LIKE '#00aaff' OR `glpi_cables`.`color` IS NULL)",
        ];

        // datatype=language
        yield [
            'itemtype'          => \User::class,
            'search_option'     => 17, // language
            'value'             => '^en_',
            'expected_and'      => "(`glpi_users`.`language` LIKE 'en\_%')",
            'expected_and_not'  => "(`glpi_users`.`language` NOT LIKE 'en\_%' OR `glpi_users`.`language` IS NULL)",
        ];
        yield [
            'itemtype'          => \User::class,
            'search_option'     => 17, // language
            'value'             => '_GB$',
            'expected_and'      => "(`glpi_users`.`language` LIKE '%\_GB')",
            'expected_and_not'  => "(`glpi_users`.`language` NOT LIKE '%\_GB' OR `glpi_users`.`language` IS NULL)",
        ];
        yield [
            'itemtype'          => \User::class,
            'search_option'     => 17, // language
            'value'             => '^en_GB$',
            'expected_and'      => "(`glpi_users`.`language` LIKE 'en\_GB')",
            'expected_and_not'  => "(`glpi_users`.`language` NOT LIKE 'en\_GB' OR `glpi_users`.`language` IS NULL)",
        ];

        // Check `>`, `>=`, `<` and `<=` operators on textual fields.
        // Operator has no meaning and is considered as a term to search for.
        foreach (['>', '>=', '<', '<='] as $operator) {
            foreach (['', ' '] as $spacing) {
                $searched_value = "{$operator}{$spacing}15";

                // datatype=dropdown
                yield [
                    'itemtype'          => \Computer::class,
                    'search_option'     => 4, // type
                    'value'             => $searched_value,
                    'expected_and'      => "(`glpi_computertypes`.`name` LIKE '%{$searched_value}%')",
                    'expected_and_not'  => "(`glpi_computertypes`.`name` NOT LIKE '%{$searched_value}%' OR `glpi_computertypes`.`name` IS NULL)",
                ];

                // datatype=dropdown (usehaving=true)
                yield [
                    'itemtype'          => \Ticket::class,
                    'search_option'     => 142, // document name
                    'value'             => $searched_value,
                    'expected_and'      => "(`ITEM_Ticket_142` LIKE '%{$searched_value}%')",
                    'expected_and_not'  => "(`ITEM_Ticket_142` NOT LIKE '%{$searched_value}%' OR `ITEM_Ticket_142` IS NULL)",
                ];

                // datatype=itemlink
                yield [
                    'itemtype'          => \Computer::class,
                    'search_option'     => 1, // name
                    'value'             => $searched_value,
                    'expected_and'      => "(`glpi_computers`.`name` LIKE '%{$searched_value}%')",
                    'expected_and_not'  => "(`glpi_computers`.`name` NOT LIKE '%{$searched_value}%' OR `glpi_computers`.`name` IS NULL)",
                ];

                // datatype=itemlink (usehaving=true)
                yield [
                    'itemtype'          => \Ticket::class,
                    'search_option'     => 50, // parent tickets
                    'value'             => $searched_value,
                    'expected_and'      => "(`ITEM_Ticket_50` LIKE '%{$searched_value}%')",
                    'expected_and_not'  => "(`ITEM_Ticket_50` NOT LIKE '%{$searched_value}%' OR `ITEM_Ticket_50` IS NULL)",
                ];

                // datatype=string
                yield [
                    'itemtype'          => \Computer::class,
                    'search_option'     => 47, // uuid
                    'value'             => $searched_value,
                    'expected_and'      => "(`glpi_computers`.`uuid` LIKE '%{$searched_value}%')",
                    'expected_and_not'  => "(`glpi_computers`.`uuid` NOT LIKE '%{$searched_value}%' OR `glpi_computers`.`uuid` IS NULL)",
                ];

                // datatype=text
                yield [
                    'itemtype'          => \Computer::class,
                    'search_option'     => 16, // comment
                    'value'             => $searched_value,
                    'expected_and'      => "(`glpi_computers`.`comment` LIKE '%{$searched_value}%')",
                    'expected_and_not'  => "(`glpi_computers`.`comment` NOT LIKE '%{$searched_value}%' OR `glpi_computers`.`comment` IS NULL)",
                ];

                // datatype=email
                yield [
                    'itemtype'          => \Contact::class,
                    'search_option'     => 6, // email
                    'value'             => $searched_value,
                    'expected_and'      => "(`glpi_contacts`.`email` LIKE '%{$searched_value}%')",
                    'expected_and_not'  => "(`glpi_contacts`.`email` NOT LIKE '%{$searched_value}%' OR `glpi_contacts`.`email` IS NULL)",
                ];

                // datatype=weblink
                yield [
                    'itemtype'          => \Document::class,
                    'search_option'     => 4, // link
                    'value'             => $searched_value,
                    'expected_and'      => "(`glpi_documents`.`link` LIKE '%{$searched_value}%')",
                    'expected_and_not'  => "(`glpi_documents`.`link` NOT LIKE '%{$searched_value}%' OR `glpi_documents`.`link` IS NULL)",
                ];

                // datatype=mac
                yield [
                    'itemtype'          => \DeviceNetworkCard::class,
                    'search_option'     => 11, // mac_default
                    'value'             => $searched_value,
                    'expected_and'      => "(`glpi_devicenetworkcards`.`mac_default` LIKE '%{$searched_value}%')",
                    'expected_and_not'  => "(`glpi_devicenetworkcards`.`mac_default` NOT LIKE '%{$searched_value}%' OR `glpi_devicenetworkcards`.`mac_default` IS NULL)",
                ];

                // datatype=color
                yield [
                    'itemtype'          => \Cable::class,
                    'search_option'     => 15, // color
                    'value'             => $searched_value,
                    'expected_and'      => "(`glpi_cables`.`color` LIKE '%{$searched_value}%')",
                    'expected_and_not'  => "(`glpi_cables`.`color` NOT LIKE '%{$searched_value}%' OR `glpi_cables`.`color` IS NULL)",
                ];

                // datatype=language
                yield [
                    'itemtype'          => \User::class,
                    'search_option'     => 17, // language
                    'value'             => $searched_value,
                    'expected_and'      => "(`glpi_users`.`language` LIKE '%{$searched_value}%')",
                    'expected_and_not'  => "(`glpi_users`.`language` NOT LIKE '%{$searched_value}%' OR `glpi_users`.`language` IS NULL)",
                ];
            }
        }

        // Check `>`, `>=`, `<` and `<=` operators on numeric fields.
        // It should result in usage of the corresponding SQL operator.

        foreach (['>', '>=', '<', '<='] as $operator) {
            foreach ([15, 2.3, 1.125] as $value) {
                $searched_values = [
                    // positive values, with or without spaces
                    "{$operator}{$value}"       => "{$value}",
                    " {$operator}  {$value} "   => "{$value}",

                    // negative values, with or without spaces
                    "{$operator}-{$value}"      => "-{$value}",
                    " {$operator} -{$value} "   => "-{$value}",
                    "{$operator} - {$value} "   => "-{$value}",
                ];
                $not_operator   = str_contains($operator, '>') ? str_replace('>', '<', $operator) : str_replace('<', '>', $operator);

                foreach ($searched_values as $searched_value => $signed_value) {
                    // datatype=integer
                    yield [
                        'itemtype'          => \AuthLDAP::class,
                        'search_option'     => 4, // port
                        'value'             => $searched_value,
                        'expected_and'      => "(`glpi_authldaps`.`port` {$operator} {$signed_value})",
                        'expected_and_not'  => "(`glpi_authldaps`.`port` {$not_operator} {$signed_value})",
                    ];

                    // datatype=number
                    yield [
                        'itemtype'          => \AuthLDAP::class,
                        'search_option'     => 32, // timeout
                        'value'             => $searched_value,
                        'expected_and'      => "(`glpi_authldaps`.`timeout` {$operator} {$signed_value})",
                        'expected_and_not'  => "(`glpi_authldaps`.`timeout` {$not_operator} {$signed_value})",
                    ];

                    // datatype=number (usehaving=true)
                    yield [
                        'itemtype'          => \Computer::class,
                        'search_option'     => 115, // harddrive capacity
                        'value'             => $searched_value,
                        'expected_and'      => "(`ITEM_Computer_115` {$operator} {$signed_value})",
                        'expected_and_not'  => "(`ITEM_Computer_115` {$not_operator} {$signed_value})",
                    ];

                    // datatype=decimal
                    yield [
                        'itemtype'          => \Budget::class,
                        'search_option'     => 7, // value
                        'value'             => $searched_value,
                        'expected_and'      => "(`glpi_budgets`.`value` {$operator} {$signed_value})",
                        'expected_and_not'  => "(`glpi_budgets`.`value` {$not_operator} {$signed_value})",
                    ];

                    // datatype=decimal (usehaving=true)
                    yield [
                        'itemtype'          => \Contract::class,
                        'search_option'     => 11, // totalcost
                        'value'             => $searched_value,
                        'expected_and'      => "(`ITEM_Contract_11` {$operator} {$signed_value})",
                        'expected_and_not'  => "(`ITEM_Contract_11` {$not_operator} {$signed_value})",
                    ];

                    // datatype=count (usehaving=true)
                    yield [
                        'itemtype'          => \Ticket::class,
                        'search_option'     => 27, // number of followups
                        'value'             => $searched_value,
                        'expected_and'      => "(`ITEM_Ticket_27` {$operator} {$signed_value})",
                        'expected_and_not'  => "(`ITEM_Ticket_27` {$not_operator} {$signed_value})",
                    ];

                    // datatype=mio (usehaving=true)
                    yield [
                        'itemtype'          => \Computer::class,
                        'search_option'     => 111, // memory size
                        'value'             => $searched_value,
                        'expected_and'      => "(`ITEM_Computer_111` {$operator} {$signed_value})",
                        'expected_and_not'  => "(`ITEM_Computer_111` {$not_operator} {$signed_value})",
                    ];

                    // datatype=progressbar (with computation)
                    yield [
                        'itemtype'          => \Computer::class,
                        'search_option'     => 152, // harddrive freepercent
                        'value'             => $searched_value,
                        'expected_and'      => "(LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.totalsize, 0)), 3, 0) {$operator} {$signed_value})",
                        'expected_and_not'  => "(LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.totalsize, 0)), 3, 0) {$not_operator} {$signed_value})",
                    ];

                    // datatype=timestamp
                    yield [
                        'itemtype'          => \CronTask::class,
                        'search_option'     => 6, // frequency
                        'value'             => $searched_value,
                        'expected_and'      => "(`glpi_crontasks`.`frequency` {$operator} {$signed_value})",
                        'expected_and_not'  => "(`glpi_crontasks`.`frequency` {$not_operator} {$signed_value})",
                    ];

                    // datatype=timestamp (usehaving=true)
                    yield [
                        'itemtype'          => \Ticket::class,
                        'search_option'     => 49, // actiontime
                        'value'             => $searched_value,
                        'expected_and'      => "(`ITEM_Ticket_49` {$operator} {$signed_value})",
                        'expected_and_not'  => "(`ITEM_Ticket_49` {$not_operator} {$signed_value})",
                    ];
                }
            }
        }

        // Check `>`, `>=`, `<` and `<=` operators on date and datetime fields.
        // It should result in a criterion based on a relative date expressed in months, and using the corresponding SQL operator.

        foreach (['>', '>=', '<', '<='] as $operator) {
            foreach ([3, 6.5] as $value) {
                $searched_values = [
                    // positive values, with or without spaces
                    "{$operator}{$value}"       => "{$value}",
                    " {$operator}  {$value} "   => "{$value}",

                    // negative values, with or without spaces
                    "{$operator}-{$value}"      => "-{$value}",
                    " {$operator} -{$value} "   => "-{$value}",
                    "{$operator} - {$value} "   => "-{$value}",
                ];
                $not_operator   = str_contains($operator, '>') ? str_replace('>', '<', $operator) : str_replace('<', '>', $operator);

                foreach ($searched_values as $searched_value => $signed_value) {
                    // datatype=datetime
                    yield [
                        'itemtype'          => \Computer::class,
                        'search_option'     => 9, // last_inventory_update
                        'value'             => $searched_value,
                        'expected_and'      => "(CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) {$operator} ADDDATE(NOW(), INTERVAL {$signed_value} MONTH))",
                        'expected_and_not'  => "(CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) {$not_operator} ADDDATE(NOW(), INTERVAL {$signed_value} MONTH))",
                    ];

                    // datatype=datetime computed field
                    $like_value = trim(str_replace('  ', ' ', $searched_value));
                    yield [
                        'itemtype'          => \Ticket::class,
                        'search_option'     => 188, // next_escalation_level
                        'value'             => $searched_value,
                        'expected_and'      => "(`ITEM_Ticket_188` LIKE '%{$like_value}%')",
                        'expected_and_not'  => "(`ITEM_Ticket_188` NOT LIKE '%{$like_value}%' OR `ITEM_Ticket_188` IS NULL)",
                    ];

                    // datatype=date
                    yield [
                        'itemtype'          => \Budget::class,
                        'search_option'     => 5, // begin_date
                        'value'             => $searched_value,
                        'expected_and'      => "(CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) {$operator} ADDDATE(NOW(), INTERVAL {$signed_value} MONTH))",
                        'expected_and_not'  => "(CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) {$not_operator} ADDDATE(NOW(), INTERVAL {$signed_value} MONTH))",
                    ];

                    // datatype=date_delay
                    yield [
                        'itemtype'          => \Contract::class,
                        'search_option'     => 20, // end_date
                        'value'             => $searched_value,
                        'expected_and'      => "(ADDDATE(`glpi_contracts`.begin_date, INTERVAL (`glpi_contracts`.duration) MONTH) {$operator} ADDDATE(NOW(), INTERVAL {$signed_value} MONTH))",
                        'expected_and_not'  => "(ADDDATE(`glpi_contracts`.begin_date, INTERVAL (`glpi_contracts`.duration) MONTH) {$not_operator} ADDDATE(NOW(), INTERVAL {$signed_value} MONTH))",
                    ];
                }
            }
        }
    }

    /**
     * @dataprovider containsCriterionProvider
     */
    public function testContainsCriterion(
        string $itemtype,
        int $search_option,
        string $value,
        string $expected_and,
        string $expected_and_not
    ): void {
        $cases = [
            'AND'       => $expected_and,
            'AND NOT'   => $expected_and_not,
        ];

        foreach ($cases as $link => $expected_where) {
            $search_params = [
                'is_deleted' => 0,
                'start'      => 0,
                'criteria'   => [
                    0 => [
                        'link'       => $link,
                        'field'      => $search_option,
                        'searchtype' => 'contains',
                        'value'      => $value,
                    ]
                ],
            ];

            $data = $this->doSearch($itemtype, $search_params);

            $this->array($data)->hasKey('sql');
            $this->array($data['sql'])->hasKey('search');
            $this->string($data['sql']['search']);

            $this->string($this->cleanSQL($data['sql']['search']))->contains($expected_where);
        }
    }

    public function testDCRoomSearchOption()
    {
        global $CFG_GLPI;
        foreach ($CFG_GLPI['rackable_types'] as $rackable_type) {
            $item = new $rackable_type();
            $so = $item->rawSearchOptions();
            //check if search option separator 'dcroom' exist
            $this->variable(array_search('dcroom', array_column($so, 'id')))->isNotEqualTo(false, $item->getTypeName() . ' should use \'$tab = array_merge($tab, DCRoom::rawSearchOptionsToAdd());');
        }
    }

    public function testDataCenterSearchOption()
    {
        global $CFG_GLPI;
        foreach ($CFG_GLPI['rackable_types'] as $rackable_type) {
            $item = new $rackable_type();
            $so = $item->rawSearchOptions();
            //check if search option separator 'datacenter' exist
            $this->variable(array_search('datacenter', array_column($so, 'id')))->isNotEqualTo(false, $item->getTypeName() . ' should use \'$tab = array_merge($tab, DataCenter::rawSearchOptionsToAdd());');
        }
    }

    protected function testRichTextProvider(): iterable
    {
        $this->login('glpi', 'glpi');

        $this->createItems('Ticket', [
            [
                'name' => 'Ticket 1',
                'content' => '<p>This is a test ticket</p>'
            ],
            [
                'name' => 'Ticket 2',
                'content' => '<p>This is a test ticket with &amp; in description</p>'
            ],
            [
                'name' => 'Ticket 3',
                'content' => '<p>This is a test ticket with matching followup</p>'
            ],
            [
                'name' => 'Ticket 4',
                'content' => '<p>This is a test ticket with task</p>'
            ],
            [
                'name' => 'Ticket & 5',
                'content' => '<p>This is a test ticket</p>'
            ],
            [
                'name' => 'Ticket > 6',
                'content' => '<p>This is a test ticket</p>'
            ],
        ]);

        $this->createItem('ITILFollowup', [
            'itemtype' => 'Ticket',
            'items_id' => getItemByTypeName('Ticket', 'Ticket 1')->getID(),
            'content' => '<p>This is a followup</p>'
        ]);
        $this->createItem('ITILFollowup', [
            'itemtype' => 'Ticket',
            'items_id' => getItemByTypeName('Ticket', 'Ticket 3')->getID(),
            'content' => '<p>This is a followup with &amp; in description</p>'
        ]);

        $this->createItem('TicketTask', [
            'tickets_id' => getItemByTypeName('Ticket', 'Ticket 1')->getID(),
            'content' => '<p>This is a task</p>'
        ]);
        $this->createItem('TicketTask', [
            'tickets_id' => getItemByTypeName('Ticket', 'Ticket 4')->getID(),
            'content' => '<p>This is a task with &amp; in description</p>'
        ]);

        // When user searches for a `&`, the criteria is sanitized and its value is therefore `&#38;`
        $sanitized_ampersand_criteria = '&#38;';

        yield [
            'search_params' => [
                'is_deleted' => 0,
                'start'      => 0,
                'criteria'   => [
                    0 => [
                        'link'       => 'AND',
                        'field'      => 1, // title
                        'searchtype' => 'contains',
                        'value'      => $sanitized_ampersand_criteria
                    ]
                ],
            ],
            'expected' => [
                'Ticket &#38; 5'
            ]
        ];

        yield [
            'search_params' => [
                'is_deleted' => 0,
                'start'      => 0,
                'criteria'   => [
                    0 => [
                        'link'       => 'AND',
                        'field'      => 21, // ticket content
                        'searchtype' => 'contains',
                        'value'      => $sanitized_ampersand_criteria
                    ]
                ],
            ],
            'expected' => [
                'Ticket 2'
            ]
        ];

        yield [
            'search_params' => [
                'is_deleted' => 0,
                'start'      => 0,
                'criteria'   => [
                    0 => [
                        'link'       => 'AND',
                        'field'      => 25, // followup content
                        'searchtype' => 'contains',
                        'value'      => $sanitized_ampersand_criteria
                    ]
                ],
            ],
            'expected' => [
                'Ticket 3'
            ]
        ];

        yield [
            'search_params' => [
                'is_deleted' => 0,
                'start'      => 0,
                'criteria'   => [
                    0 => [
                        'link'       => 'AND',
                        'field'      => 26, // task content
                        'searchtype' => 'contains',
                        'value'      => $sanitized_ampersand_criteria
                    ]
                ],
            ],
            'expected' => [
                'Ticket 4'
            ]
        ];

        yield [
            'search_params' => [
                'is_deleted' => 0,
                'start' => 0,
                'criteria' => [
                    0 => [
                        'link' => 'AND',
                        'field' => 'view', // items seen
                        'searchtype' => 'contains',
                        'value' => $sanitized_ampersand_criteria
                    ],
                    1 => [
                        'link' => 'AND',
                        'field' => 1, //title
                        'searchtype' => 'contains',
                        'value' => ''
                    ],
                    2 => [
                        'link' => 'AND',
                        'field' => 21, // ticket content
                        'searchtype' => 'contains',
                        'value' => ''
                    ],
                    3 => [
                        'link' => 'AND',
                        'field' => 25, // followup content
                        'searchtype' => 'contains',
                        'value' => ''
                    ],
                    4 => [
                        'link' => 'AND',
                        'field' => 26, // task content
                        'searchtype' => 'contains',
                        'value' => ''
                    ],
                ],
            ],
            'expected' => [
                'Ticket 2',
                'Ticket 3',
                'Ticket 4',
                'Ticket &#38; 5'
            ]
        ];
    }

    /**
     * @dataprovider testRichTextProvider
     */
    public function testRichText(
        array $search_params,
        array $expected
    ): void {
        $data = $this->doSearch(\Ticket::class, $search_params);

        // Extract items names
        $items = [];
        foreach ($data['data']['rows'] as $row) {
            $items[] = $row['raw']['ITEM_Ticket_1'];
        }

        $this->array($items)->isEqualTo($expected);
    }

    public function testTicketValidationStatus()
    {
        $search_params = [
            'reset'        => 'reset',
            'is_deleted'   => 0,
            'start'        => 0,
            'search'       => 'Search',
            'criteria'     => [
                0 => [
                    'field' => 'view', // items seen
                    'searchtype' => 'contains',
                    'value' => 'any string'
                ]
            ]
        ];

        $displaypref = new \DisplayPreference();
        $input = [
            'itemtype'  => 'Ticket',
            'users_id'  => \Session::getLoginUserID(),
            'num'       => 55, //Ticket glpi_ticketvalidations.status
        ];
        $this->integer((int)$displaypref->add($input))->isGreaterThan(0);


        $data = $this->doSearch('Ticket', $search_params);

        $this->string($data['sql']['search'])->notContains("`glpi_ticketvalidations`.`status` IN");

        $search_params['criteria'][0]['value'] = 1;
        $data = $this->doSearch('Ticket', $search_params);
        $this->string($data['sql']['search'])->contains("`glpi_ticketvalidations`.`status` IN");

        $search_params['criteria'][0]['value'] = 'all';
        $data = $this->doSearch('Ticket', $search_params);
        $this->string($data['sql']['search'])->notContains("`glpi_ticketvalidations`.`status` IN");

        $search_params['criteria'][0]['value'] = 'can';
        $data = $this->doSearch('Ticket', $search_params);
        $this->string($data['sql']['search'])->contains("`glpi_ticketvalidations`.`status` IN");
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
