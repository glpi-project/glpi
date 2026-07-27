<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Appliance;
use Appliance_Item;
use Change;
use CommonDBTM;
use Computer;
use DBConnection;
use Document;
use Document_Item;
use DropdownTranslation;
use Entity;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasDocumentsCapacity;
use Glpi\DBAL\Operator;
use Glpi\DBAL\Parts\Select;
use Glpi\DBAL\QueryExpression;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AnswersSet_FormDestinationItem;
use Glpi\Form\Form;
use Glpi\Search\SearchOption;
use Glpi\Tests\DbTestCase;
use Group;
use Group_Item;
use Group_User;
use Location;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LogLevel;
use Session;
use TaskCategory;
use Ticket;
use User;

use function Safe\ob_get_clean;
use function Safe\ob_start;
use function Safe\preg_match;
use function Safe\strtotime;

class SearchTest extends DbTestCase
{
    private function doSearch($itemtype, $params, array $forcedisplay = [])
    {
        global $CFG_GLPI;

        // check param itemtype exists (to avoid search errors)
        if ($itemtype !== 'AllAssets') {
            $this->assertTrue(is_subclass_of($itemtype, CommonDBTM::class));
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

        // don't compute last request from session
        $params['reset'] = 'reset';

        // do search
        $params = \Search::manageParams($itemtype, $params);
        $data   = \Search::getDatas($itemtype, $params, $forcedisplay);

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
                'value'      => '',
            ],
            ],
            'metacriteria' => [0 => ['link'       => 'AND',
                'itemtype'   => 'OperatingSystem',
                'field'      => 1, //name
                'searchtype' => 'contains',
                'value'      => 'windows',
            ],
            ],
        ];

        $data = $this->doSearch(Computer::class, $search_params);

        //try to find LEFT JOIN clauses
        $this->assertMatchesRegularExpression(
            "/"
            . "LEFT\s*JOIN\s*`glpi_items_operatingsystems`\s*AS\s*`glpi_items_operatingsystems_OperatingSystem`\s*"
            . "ON\s*\(`glpi_items_operatingsystems_OperatingSystem`\.`items_id`\s*=\s*`glpi_computers`\.`id`\s*"
            . "AND `glpi_items_operatingsystems_OperatingSystem`\.`itemtype`\s*=\s*\?\s*"
            . "AND `glpi_items_operatingsystems_OperatingSystem`\.`is_deleted`\s*=\s*\?\s*\)\s*"
            . "LEFT\s*JOIN\s*`glpi_operatingsystems`\s*"
            . "ON\s*\(`glpi_items_operatingsystems_OperatingSystem`\.`operatingsystems_id`\s*=\s*`glpi_operatingsystems`\.`id`\s*\)"
            . "/im",
            $data['sql']['search']->getQuery()
        );

        //try to match WHERE clause
        $this->assertMatchesRegularExpression(
            "/`glpi_operatingsystems`\.`name`\s*LIKE\s*\?/im",
            $data['sql']['search']->getQuery()
        );

        $this->assertContains(Computer::class, $data['sql']['search']->getParams());
        $this->assertContains('%windows%', $data['sql']['search']->getParams());
    }


    public function testMetaComputerSoftwareLicense()
    {
        $search_params = ['is_deleted'   => 0,
            'start'        => 0,
            'criteria'     => [0 => ['field'      => 'view',
                'searchtype' => 'contains',
                'value'      => '',
            ],
            ],
            'metacriteria' => [0 => ['link'       => 'AND',
                'itemtype'   => 'Software',
                'field'      => 163,
                'searchtype' => 'contains',
                'value'      => '>0',
            ],
                1 => ['link'       => 'AND',
                    'itemtype'   => 'Software',
                    'field'      => 160,
                    'searchtype' => 'contains',
                    'value'      => 'firefox',
                ],
            ],
        ];

        $data = $this->doSearch(Computer::class, $search_params);

        $this->assertMatchesRegularExpression(
            '/'
            . 'LEFT JOIN\s*`glpi_items_softwareversions`\s*AS\s*`glpi_items_softwareversions_[^`]+_Software`\s*ON\s*\('
            . '`glpi_items_softwareversions_[^`]+_Software`\.`items_id`\s*=\s*`glpi_computers`.`id`'
            . '\s*AND\s*`glpi_items_softwareversions_[^`]+_Software`\.`itemtype`\s*=\s*\?'
            . '\s*AND\s*`glpi_items_softwareversions_[^`]+_Software`\.`is_deleted`\s*=\s*\?'
            . '\)/im',
            $data['sql']['search']->getQuery()
        );
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
                    'itemtype'   => Computer::class,
                    'field'      => 2,
                    'searchtype' => 'contains',
                    'value'      => '^$', // search for "null" id
                ],
            ],
        ];

        $data = $this->doSearch('Software', $search_params);

        $this->assertMatchesRegularExpression(
            "/HAVING\s*\(\s+NOT\s+\(`ITEM_Computer_2`\s+IS\s+NULL\s*\)/",
            $data['sql']['search']->getQuery()
        );
    }

    public function testMetaComputerUser()
    {
        $search_params = ['is_deleted'   => 0,
            'start'        => 0,
            'search'       => 'Search',
            'criteria'     => [0 => ['field'      => 'view',
                'searchtype' => 'contains',
                'value'      => '',
            ],
            ],
            // user login
            'metacriteria' => [0 => ['link'       => 'AND',
                'itemtype'   => 'User',
                'field'      => 1,
                'searchtype' => 'equals',
                'value'      => 2,
            ],
                // user profile
                1 => ['link'       => 'AND',
                    'itemtype'   => 'User',
                    'field'      => 20,
                    'searchtype' => 'equals',
                    'value'      => 4,
                ],
                // user entity
                2 => ['link'       => 'AND',
                    'itemtype'   => 'User',
                    'field'      => 80,
                    'searchtype' => 'equals',
                    'value'      => 0,
                ],
                // user profile
                3 => ['link'       => 'AND',
                    'itemtype'   => 'User',
                    'field'      => 13,
                    'searchtype' => 'equals',
                    'value'      => 1,
                ],
            ],
        ];

        $this->doSearch(Computer::class, $search_params);
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
                    'value'      => 'notold',
                ],
                1 => [
                    'link'       => 'AND',
                    'criteria'   => [
                        0 => [
                            'field'      => 'view',
                            'searchtype' => 'contains',
                            'value'      => 'test1',
                        ],
                        1 => [
                            'link'       => 'OR',
                            'field'      => 'view',
                            'searchtype' => 'contains',
                            'value'      => 'test2',
                        ],
                        2 => [
                            'link'       => 'OR',
                            'meta'       => true,
                            'itemtype'   => Computer::class,
                            'field'      => 1,
                            'searchtype' => 'contains',
                            'value'      => 'test3',
                        ],
                    ],
                ],
            ],
        ];

        $this->doSearch(Ticket::class, $search_params);
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
                    'value'      => '',
                ],
                // user login
                1 => [
                    'link'       => 'AND',
                    'itemtype'   => 'User',
                    'field'      => 1,
                    'meta'       => 1,
                    'searchtype' => 'equals',
                    'value'      => 2,
                ],
                // user profile
                2 => [
                    'link'       => 'AND',
                    'itemtype'   => 'User',
                    'field'      => 20,
                    'meta'       => 1,
                    'searchtype' => 'equals',
                    'value'      => 4,
                ],
                // user entity
                3 => [
                    'link'       => 'AND',
                    'itemtype'   => 'User',
                    'field'      => 80,
                    'meta'       => 1,
                    'searchtype' => 'equals',
                    'value'      => 0,
                ],
                // user profile
                4 => [
                    'link'       => 'AND',
                    'itemtype'   => 'User',
                    'field'      => 13,
                    'meta'       => 1,
                    'searchtype' => 'equals',
                    'value'      => 1,
                ],
            ],
        ];

        $data = $this->doSearch(Computer::class, $search_params);

        $this->assertStringContainsString(
            "LEFT JOIN `glpi_users`",
            $data['sql']['search']->getQuery()
        );
        $this->assertStringContainsString(
            "LEFT JOIN `glpi_profiles` AS `glpi_profiles_",
            $data['sql']['search']->getQuery()
        );
        $this->assertStringContainsString(
            "LEFT JOIN `glpi_entities` AS `glpi_entities_",
            $data['sql']['search']->getQuery()
        );
    }

    public function testNestedAndMetaComputer()
    {
        $test_root       = getItemByTypeName('Entity', '_test_root_entity', true);
        $test_child_1    = getItemByTypeName('Entity', '_test_child_1', true);
        $test_child_2    = getItemByTypeName('Entity', '_test_child_2', true);
        $test_child_3    = getItemByTypeName('Entity', '_test_child_3', true);

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
                            'field'      => 5, //serial
                            'searchtype' => 'contains',
                            'value'      => 'test',
                        ], [
                            'link'       => 'OR',
                            'field'      => 5, //serial
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
                                ],
                            ],
                        ],
                    ],
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
                ],
            ],
        ];

        $data = $this->doSearch(Computer::class, $search_params);

        $regexps = [
            // join parts
            '/LEFT JOIN\s*`glpi_items_softwareversions`\s*AS `glpi_items_softwareversions_Software`/im',
            '/LEFT JOIN\s*`glpi_softwareversions`\s*AS `glpi_softwareversions_Software`/im',
            '/LEFT JOIN\s*`glpi_softwares`\s*ON\s*\(`glpi_softwareversions_Software`\.`softwares_id`\s*=\s*`glpi_softwares`\.`id`\)/im',
            '/LEFT JOIN\s*`glpi_infocoms`\s*AS\s*`glpi_infocoms_Budget`\s*ON\s*\(`glpi_computers`\.`id`\s*=\s*`glpi_infocoms_Budget`\.`items_id`\s*AND\s*`glpi_infocoms_Budget`.`itemtype`\s*=\s*\?\)/im',
            '/LEFT JOIN\s*`glpi_budgets`\s*ON\s*\(`glpi_infocoms_Budget`\.`budgets_id`\s*=\s*`glpi_budgets`\.`id`/im',
            '/LEFT JOIN\s*`glpi_assets_assets_peripheralassets`\s*AS `glpi_assets_assets_peripheralassets_Printer`\s*ON\s*\(`glpi_assets_assets_peripheralassets_Printer`\.`items_id_asset`\s*=\s*`glpi_computers`\.`id`\s*AND\s*`glpi_assets_assets_peripheralassets_Printer`.`itemtype_asset`\s*=\s*\?\s*AND\s*`glpi_assets_assets_peripheralassets_Printer`.`itemtype_peripheral`\s*=\s*\?\s*AND\s*`glpi_assets_assets_peripheralassets_Printer`.`is_deleted`\s*=\s*\?\)/im',
            '/LEFT JOIN\s*`glpi_printers`\s*ON\s*\(`glpi_assets_assets_peripheralassets_Printer`\.`items_id_peripheral`\s*=\s*`glpi_printers`\.`id`/im',
            // match having
            "/HAVING\s*`ITEM_Budget_2`\s+<>\s+\?\s+AND\s+\(\(\(\(\s+NOT\s+\(`ITEM_Printer_1`\s+LIKE\s+\?\)\)\)\s+OR\s+\(`ITEM_Printer_1`\s+IS NULL\)\)\)/",
        ];

        foreach ($regexps as $regexp) {
            $this->assertMatchesRegularExpression(
                $regexp,
                $data['sql']['search']->getQuery()
            );
        }

        // match where parts
        $contains = [
            "`glpi_computers`.`is_deleted` = ?",
            "AND `glpi_computers`.`is_template` = ?",
            "`glpi_computers`.`entities_id` IN (?, ?, ?, ?)",
            "OR (`glpi_computers`.`is_recursive` = ? AND `glpi_computers`.`entities_id` IN (?))",
            "`glpi_computers`.`name` LIKE ?",
            "AND `glpi_softwares`.`id` = ?",
            "OR (`glpi_computers`.`serial` LIKE ?",
            "AND (`glpi_locations`.`id` = ?)",
            "(`glpi_users`.`id` = ?)",
            "OR (`glpi_users`.`id` = ?)",
        ];

        foreach ($contains as $contain) {
            $this->assertStringContainsString(
                $contain,
                $data['sql']['search']->getQuery()
            );
        }
    }

    public function testViewCriterion()
    {
        $test_root       = getItemByTypeName('Entity', '_test_root_entity', true);
        $test_child_1    = getItemByTypeName('Entity', '_test_child_1', true);
        $test_child_2    = getItemByTypeName('Entity', '_test_child_2', true);
        $test_child_3    = getItemByTypeName('Entity', '_test_child_3', true);

        $data = $this->doSearch(Computer::class, [
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
            ],
        ]);

        $default_charset = DBConnection::getDefaultCharset();

        $contains = [
            "`glpi_computers`.`is_deleted` = ?",
            "AND `glpi_computers`.`is_template` = ?",
            "`glpi_computers`.`entities_id` IN (?, ?, ?, ?)",
            "OR (`glpi_computers`.`is_recursive` = ? AND `glpi_computers`.`entities_id` IN (?))",
        ];

        foreach ($contains as $contain) {
            $this->assertStringContainsString(
                $contain,
                $data['sql']['search']->getQuery()
            );
        }

        $regexps = [
            "/`glpi_computers`\.`name` LIKE ?/",
            "/OR\s*\(`glpi_entities`\.`completename`\s*LIKE \?\s*\)/",
            "/OR\s*\(`glpi_states`\.`completename`\s*LIKE \?\s*\)/",
            "/OR\s*\(`glpi_manufacturers`\.`name`\s*LIKE \?\s*\)/",
            "/OR\s*\(`glpi_computers`\.`serial`\s*LIKE \?\s*\)/",
            "/OR\s*\(`glpi_computertypes`\.`name`\s*LIKE \?\s*\)/",
            "/OR\s*\(`glpi_computermodels`\.`name`\s*LIKE \?\s*\)/",
            "/OR\s*\(`glpi_locations`\.`completename`\s*LIKE \?\s*\)/",
        ];

        foreach ($regexps as $regexp) {
            $this->assertMatchesRegularExpression(
                $regexp,
                $data['sql']['search']->getQuery()
            );
        }

        $this->assertDoesNotMatchRegularExpression(
            "/OR\s*\(CONVERT\(`glpi_computers`\.`date_mod` USING {$default_charset}\)\s*LIKE ?\s*\)\)/",
            $data['sql']['search']->getQuery()
        );
    }

    public static function viewCriterionProvider(): array
    {
        return [
            [
                'itemtype' => Computer::class,
                'criteria' => [
                    [
                        'link'       => 'AND',
                        'field'      => 'view',
                        'searchtype' => 'contains',
                        'value'      => 'test',
                    ],
                ],
                'expected' => 9,
            ],
            [
                'itemtype' => Computer::class,
                'criteria' => [
                    [
                        'link'       => 'AND',
                        'field'      => 'view',
                        'searchtype' => 'contains',
                        'value'      => '_test_pc01',
                    ],
                ],
                'expected' => 1,
            ],
            [
                'itemtype' => Computer::class,
                'criteria' => [
                    [
                        'link'       => 'AND',
                        'field'      => 'view',
                        'searchtype' => 'notcontains',
                        'value'      => 'test',
                    ],
                ],
                'expected' => 0,
            ],
            [
                'itemtype' => Computer::class,
                'criteria' => [
                    [
                        'link'       => 'AND',
                        'field'      => 'view',
                        'searchtype' => 'notcontains',
                        'value'      => '_test_pc01',
                    ],
                ],
                'expected' => 8,
            ],
            [
                'itemtype' => Computer::class,
                'criteria' => [
                    [
                        'link'       => 'AND NOT',
                        'field'      => 'view',
                        'searchtype' => 'contains',
                        'value'      => 'test',
                    ],
                ],
                'expected' => 0,
            ],
            [
                'itemtype' => Computer::class,
                'criteria' => [
                    [
                        'link'       => 'AND NOT',
                        'field'      => 'view',
                        'searchtype' => 'contains',
                        'value'      => '_test_pc01',
                    ],
                ],
                'expected' => 8,
            ],
            [
                'itemtype' => Computer::class,
                'criteria' => [
                    [
                        'link'       => 'AND NOT',
                        'field'      => 'view',
                        'searchtype' => 'notcontains',
                        'value'      => 'test',
                    ],
                ],
                'expected' => 9,
            ],
            [
                'itemtype' => Computer::class,
                'criteria' => [
                    [
                        'link'       => 'AND NOT',
                        'field'      => 'view',
                        'searchtype' => 'notcontains',
                        'value'      => '_test_pc01',
                    ],
                ],
                'expected' => 1,
            ],
            [
                'itemtype' => Computer::class,
                'criteria' => [
                    [
                        'link'       => 'OR',
                        'field'      => 'view',
                        'searchtype' => 'contains',
                        'value'      => 'test',
                    ],
                ],
                'expected' => 9,
            ],
            [
                'itemtype' => Computer::class,
                'criteria' => [
                    [
                        'link'       => 'OR',
                        'field'      => 'view',
                        'searchtype' => 'contains',
                        'value'      => '_test_pc01',
                    ],
                ],
                'expected' => 1,
            ],
            [
                'itemtype' => Computer::class,
                'criteria' => [
                    [
                        'link'       => 'OR',
                        'field'      => 'view',
                        'searchtype' => 'notcontains',
                        'value'      => 'test',
                    ],
                ],
                'expected' => 0,
            ],
            [
                'itemtype' => Computer::class,
                'criteria' => [
                    [
                        'link'       => 'OR',
                        'field'      => 'view',
                        'searchtype' => 'notcontains',
                        'value'      => '_test_pc01',
                    ],
                ],
                'expected' => 8,
            ],
            [
                'itemtype' => Computer::class,
                'criteria' => [
                    [
                        'link'       => 'OR NOT',
                        'field'      => 'view',
                        'searchtype' => 'contains',
                        'value'      => 'test',
                    ],
                ],
                'expected' => 0,
            ],
            [
                'itemtype' => Computer::class,
                'criteria' => [
                    [
                        'link'       => 'OR NOT',
                        'field'      => 'view',
                        'searchtype' => 'contains',
                        'value'      => '_test_pc01',
                    ],
                ],
                'expected' => 8,
            ],
            [
                'itemtype' => Computer::class,
                'criteria' => [
                    [
                        'link'       => 'OR NOT',
                        'field'      => 'view',
                        'searchtype' => 'notcontains',
                        'value'      => 'test',
                    ],
                ],
                'expected' => 9,
            ],
            [
                'itemtype' => Computer::class,
                'criteria' => [
                    [
                        'link'       => 'OR NOT',
                        'field'      => 'view',
                        'searchtype' => 'notcontains',
                        'value'      => '_test_pc01',
                    ],
                ],
                'expected' => 1,
            ],
        ];
    }

    #[DataProvider('viewCriterionProvider')]
    public function testViewCriterionNew(string $itemtype, array $criteria, int $expected)
    {
        $data = $this->doSearch($itemtype, [
            'reset'      => 'reset',
            'is_deleted' => 0,
            'start'      => 0,
            'search'     => 'Search',
            'criteria'   => $criteria,
        ]);

        $this->assertSame($expected, $data['data']['totalcount']);
    }

    public function testAllCriterionWithEmptyValue()
    {
        global $CFG_GLPI;
        $cfg_backup = $CFG_GLPI;
        $CFG_GLPI['allow_search_all'] = 1;

        $data = $this->doSearch(Ticket::class, [
            'reset'      => 'reset',
            'is_deleted' => 0,
            'start'      => 0,
            'search'     => 'Search',
            'criteria'   => [
                [
                    'link'       => 'AND',
                    'field'      => 'all',
                    'searchtype' => 'contains',
                    'value'      => '',
                ],
            ],
        ]);

        $CFG_GLPI = $cfg_backup;

        $this->assertArrayHasKey('totalcount', $data['data']);
    }

    public static function allCriterionProvider(): array
    {
        $cases = [];
        foreach (['AND', 'AND NOT', 'OR', 'OR NOT'] as $link) {
            foreach (['contains', 'notcontains'] as $searchtype) {
                $cases["$link $searchtype"] = [
                    'link'       => $link,
                    'searchtype' => $searchtype,
                ];
            }
        }
        return $cases;
    }

    #[DataProvider('allCriterionProvider')]
    public function testAllCriterionNew(string $link, string $searchtype)
    {
        global $CFG_GLPI;
        $cfg_backup = $CFG_GLPI;
        $CFG_GLPI['allow_search_all'] = 1;

        $this->createItem('Project', [
            'name'        => 'test_all_search_criterion',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);

        $data = $this->doSearch('Project', [
            'reset'      => 'reset',
            'is_deleted' => 0,
            'start'      => 0,
            'search'     => 'Search',
            'criteria'   => [
                [
                    'link'       => $link,
                    'field'      => 'all',
                    'searchtype' => $searchtype,
                    'value'      => 'test_all_search_criterion',
                ],
            ],
        ]);

        $CFG_GLPI = $cfg_backup;

        // Search must complete without error
        $this->assertArrayHasKey('totalcount', $data['data']);

        // For "AND/OR contains" (without NOT), the created project must be found
        if ($searchtype === 'contains' && !str_contains($link, 'NOT')) {
            $this->assertGreaterThan(0, $data['data']['totalcount']);
        }
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
            ],
        ]);

        $this->assertStringContainsString(
            "`glpi_changes`.`id` AS `ITEM_Change_Ticket_3`",
            $data['sql']['search']->getQuery()
        );
        $this->assertStringContainsString(
            "`glpi_changes_tickets`.`changes_id` = `glpi_changes`.`id`",
            $data['sql']['search']->getQuery()
        );
        $this->assertStringContainsString(
            "`glpi_changes`.`id` = ?",
            $data['sql']['search']->getQuery()
        );
    }

    public function testUser()
    {
        $search_params = ['is_deleted'   => 0,
            'start'        => 0,
            'search'       => 'Search',
            // profile
            'criteria'     => [0 => ['field'      => '20',
                'searchtype' => 'contains',
                'value'      => 'super-admin',
            ],
                // login
                1 => ['link'       => 'AND',
                    'field'      => '1',
                    'searchtype' => 'contains',
                    'value'      => 'glpi',
                ],
                // entity
                2 => ['link'       => 'AND',
                    'field'      => '80',
                    'searchtype' => 'equals',
                    'value'      => 0,
                ],
                // is not not active
                3 => ['link'       => 'AND',
                    'field'      => '8',
                    'searchtype' => 'notequals',
                    'value'      => 0,
                ],
            ],
        ];
        $data = $this->doSearch('User', $search_params);

        //expecting one result
        $this->assertSame(1, $data['data']['totalcount']);
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
                CommonDBTM::class, // should be abstract
                \CommonImplicitTreeDropdown::class, // should be abstract
                \CommonITILRecurrentCron::class, // not searchable
                \CommonITILValidationCron::class, // not searchable
                \Item_Devices::class, // should be abstract
                \NetworkPortInstantiation::class, // should be abstract
                \NotificationSettingConfig::class, // not searchable
                \PendingReasonCron::class, // not searchable
                '/^[A-z]+Stencil/', // not searchable
            ]
        );
        foreach ($classes as $class) {
            if (!is_a($class, CommonDBTM::class, true)) {
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
                $this->assertIsArray($so);

                if (!array_key_exists('datatype', $so)) {
                    continue; // datatype can be undefined
                }

                $this->assertTrue(
                    in_array($so['datatype'], $valid_datatypes),
                    sprintf('Unexpected `%s` search option datatype in `%s` class.', $so['datatype'], $class)
                );

                if ($so['datatype'] === 'count') {
                    // Must have `usehaving` = true because an aggregate function will be used
                    $this->assertTrue($so['usehaving'] ?? false);
                }
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
        $count = 0; //counter for data provider
        foreach ($classes as $class) {
            $item = new $class();

            //load all options; so rawSearchOptionsToAdd to be tested
            $options = \Search::getCleanedOptions($item->getType());

            $multi_criteria = [];
            $count_options = 0;
            foreach ($options as $key => $data) {
                if (!is_int($key) || ($criterion_params = $this->getCriterionParams($item, $key, $data)) === null) {
                    continue;
                }

                $provider_information = sprintf(
                    'Error on dataProvider #%s - %s option #%s',
                    $count,
                    $class,
                    $count_options
                );

                try {
                    // do a search query based on current search option
                    $this->doSearch(
                        $class,
                        [
                            'is_deleted' => 0,
                            'start' => 0,
                            'criteria' => [$criterion_params],
                            'metacriteria' => [],
                        ]
                    );
                } catch (\Throwable $e) {
                    echo $provider_information . "\n";
                    throw $e;
                }

                $multi_criteria[] = $criterion_params;

                ++$count_options;
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
                'metacriteria' => [],
            ];
            $this->doSearch($class, $search_params);
            ++$count;
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

        // Keep track of meta-itemtypes that have already been fully tested.
        // Once all search options of a meta-itemtype have been verified at least once,
        // we only need to test it again with a single option for subsequent base itemtypes
        // to confirm the meta-join relationship itself.
        $fully_tested_metatypes = [];
        foreach ($itemtype_criteria as $itemtype => $criteria) {
            if ($criteria === []) {
                continue;
            }

            $first_criteria_by_metatype = [];

            $count_criteria = 0;
            // Search with each meta criteria independently.
            foreach ($criteria as $criterion_params) {
                $metatype = $criterion_params['itemtype'];
                $is_first_of_metatype = !array_key_exists($metatype, $first_criteria_by_metatype);

                if ($is_first_of_metatype) {
                    $first_criteria_by_metatype[$metatype] = $criterion_params;
                }

                // If this meta-itemtype was already fully tested for a previous base itemtype,
                // we only repeat the search for the FIRST option of this meta-itemtype to verify
                // the JOIN between the current $itemtype and the meta $metatype.
                if (in_array($metatype, $fully_tested_metatypes) && !$is_first_of_metatype) {
                    continue;
                }

                $search_params = ['is_deleted'   => 0,
                    'start'        => 0,
                    'criteria'     => [0 => ['field'      => 'view',
                        'searchtype' => 'contains',
                        'value'      => '',
                    ],
                    ],
                    'metacriteria' => [$criterion_params],
                ];
                try {
                    $this->doSearch($itemtype, $search_params);
                } catch (\Throwable $e) {
                    echo sprintf(
                        'Error on Itemtype %s - criteria #%s',
                        $itemtype,
                        $count_criteria
                    );
                    throw $e;
                }
                ++$count_criteria;
            }

            // Mark all meta-itemtypes encountered for this base itemtype as "fully tested".
            $fully_tested_metatypes = array_unique(array_merge($fully_tested_metatypes, array_keys($first_criteria_by_metatype)));

            // Search with criteria related to multiple meta items.
            // Limit criteria count to 5 to prevent performances issues (mainly on MariaDB).
            // Test would take hours if done using too many criteria on each request.
            // Thus, using 5 different meta items on a request seems already more than a normal usage.
            foreach (array_chunk($first_criteria_by_metatype, 3) as $criteria_chunk) {
                $search_params = ['is_deleted'   => 0,
                    'start'        => 0,
                    'criteria'     => [0 => ['field'      => 'view',
                        'searchtype' => 'contains',
                        'value'      => '',
                    ],
                    ],
                    'metacriteria' => $criteria_chunk,
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
            'value'      => $val,
        ];
    }

    public function testIsNotifyComputerGroup()
    {
        $search_params = ['is_deleted'   => 0,
            'start'        => 0,
            'search'       => 'Search',
            'criteria'     => [
                0 => [
                    'field'      => 'view',
                    'searchtype' => 'contains',
                    'value'      => '',
                ],
            ],
            // group is_notify
            'metacriteria' => [0 => ['link'       => 'AND',
                'itemtype'   => 'Group',
                'field'      => 20,
                'searchtype' => 'equals',
                'value'      => 1,
            ],
            ],
        ];
        $this->login();
        $this->setEntity('_test_root_entity', true);

        $data = $this->doSearch(Computer::class, $search_params);

        //expecting no result
        $this->assertSame(0, $data['data']['totalcount']);

        $computer1 = getItemByTypeName(Computer::class, '_test_pc01');

        //create group that can be notified
        $group = new Group();
        $gid = $group->add(
            [
                'name'         => '_test_group01',
                'is_notify'    => '1',
                'entities_id'  => $computer1->fields['entities_id'],
                'is_recursive' => 1,
            ]
        );
        $this->assertGreaterThan(0, $gid);

        //attach group to computer
        $updated = $computer1->update(
            [
                'id'        => $computer1->getID(),
                'groups_id' => $gid,
            ]
        );
        $this->assertTrue($updated);

        $data = $this->doSearch(Computer::class, $search_params);

        //reset computer
        $updated = $computer1->update(
            [
                'id'        => $computer1->getID(),
                'groups_id' => 0,
            ]
        );
        $this->assertTrue($updated);

        $this->assertSame(1, $data['data']['totalcount']);
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
                    'value'      => '',
                ],
                // creation date
                1 => [
                    'link'       => 'AND',
                    'field'      => '15',
                    'searchtype' => 'morethan',
                    'value'      => '-1WEEK',
                ],
            ],
        ];

        $data = $this->doSearch(Ticket::class, $search_params);

        $this->assertGreaterThan(1, $data['data']['totalcount']);

        //negate previous search
        $search_params['criteria'][1]['link'] = 'AND NOT';
        $data = $this->doSearch(Ticket::class, $search_params);

        $this->assertSame(0, $data['data']['totalcount']);
    }

    /**
     * Test that searchOptions throws an exception when it finds a duplicate
     *
     * @return void
     */
    public function testGetSearchOptionsWException()
    {
        $error = 'Duplicate key `12` (`One search option`/`Any option`) in `tests\units\DupSearchOpt` search options.';

        $item = new DupSearchOpt();
        $item->searchOptions();
        $this->hasPhpLogRecordThatContains(
            $error,
            LogLevel::WARNING
        );
    }

    public function testEmptyOrNot()
    {
        $fname = __FUNCTION__;

        // Create 1 computer with data not empty
        $computer = new Computer();
        $computer_id = $computer->add([
            'name' => $fname,
            'entities_id' => 0,
            'is_recursive' => 1,
            'users_id' => 2,
            'uuid' => 'c37f7ce8-af95-4676-b454-0959f2c5e162',
            'comment' => 'This is a test comment',
            'last_inventory_update' => date('Y-m-d H:i:00'),
        ]);
        $this->assertGreaterThan(0, $computer_id);

        $cvm = new \ItemVirtualMachine();
        $cvm_id = $cvm->add([
            'itemtype' => Computer::class,
            'items_id' => $computer_id,
            'name'         => $fname,
            'vcpu'         => 1,
        ]);
        $this->assertGreaterThan(0, $cvm_id);

        // Create 2 computers with empty data
        $computer_id = $computer->add([
            'name' => $fname,
            'entities_id' => 0,
            'is_recursive' => 1,
        ]);
        $this->assertGreaterThan(0, $computer_id);
        $cvm_id = $cvm->add([
            'itemtype' => Computer::class,
            'items_id' => $computer_id,
            'name'         => $fname,
        ]);
        $this->assertGreaterThan(0, $cvm_id);

        $computer_id = $computer->add([
            'name' => $fname,
            'entities_id' => 0,
            'is_recursive' => 1,
        ]);
        $this->assertGreaterThan(0, $computer_id);

        // Create 1 monitor with data not empty
        $monitor = new \Monitor();
        $monitor_id = $monitor->add([
            'name' => $fname,
            'entities_id' => 0,
            'is_recursive' => 1,
            'size' => 54.4,
        ]);
        $this->assertGreaterThan(0, $monitor_id);

        // Create 2 monitors with empty data
        $monitor = new \Monitor();
        $monitor_id = $monitor->add([
            'name' => $fname,
            'entities_id' => 0,
            'is_recursive' => 1,
        ]);
        $this->assertGreaterThan(0, $monitor_id);

        $monitor = new \Monitor();
        $monitor_id = $monitor->add([
            'name' => $fname,
            'entities_id' => 0,
            'is_recursive' => 1,
        ]);
        $this->assertGreaterThan(0, $monitor_id);

        $monitor = new \Monitor();
        $monitor_id = $monitor->add([
            'name' => '',
            'entities_id' => 0,
            'is_recursive' => 1,
        ]);
        $this->assertGreaterThan(0, $monitor_id);

        $expected_counters = [
            [
                'field'    => 70, //user (itemlink)
                'itemtype' => Computer::class,
                'empty'    => 2,
                'notempty' => 1,
            ],
            [
                'field'    => 47, //uuid (varchar)
                'itemtype' => Computer::class,
                'empty'    => 2,
                'notempty' => 1,
            ],
            [
                'field'    => 16, //comment (text)
                'itemtype' => Computer::class,
                'empty'    => 2,
                'notempty' => 1,
            ],
            [
                'field'    => 9, //last inventory date (timestamp)
                'itemtype' => Computer::class,
                'empty'    => 2,
                'notempty' => 1,
            ],
            [
                'field'    => 164, //VCPU (integer)
                'itemtype' => Computer::class,
                'empty'    => 2,
                'notempty' => 1,
            ],
            [
                'field'    => 11, //Size (decimal)
                'itemtype' => 'Monitor',
                'empty'    => 2,
                'notempty' => 1,
            ],
            [
                'field'    => 1, //Name (itemlink)
                'itemtype' => 'Monitor',
                'empty'    => 1,
                'notempty' => 1,
            ],
        ];

        foreach ($expected_counters as $expected) {
            if ($expected['field'] == 1) {
                $criteria = [
                    0 => [
                        'field'      => $expected['field'],
                        'searchtype' => 'empty',
                        'value'      => 'null',
                    ],
                ];
            } else {
                $criteria = [
                    0 => [
                        'field'      => 'view',
                        'searchtype' => 'contains',
                        'value'      => $fname,
                    ],
                    1 => [
                        'field'      => $expected['field'],
                        'searchtype' => 'empty',
                        'value'      => 'null',
                    ],
                ];
            }
            $search_params = [
                'is_deleted'   => 0,
                'start'        => 0,
                'criteria'     => $criteria,
            ];
            $data = $this->doSearch($expected['itemtype'], $search_params);
            $this->assertSame($expected['empty'], $data['data']['totalcount']);

            //negate previous search
            $search_params['criteria'][1]['link'] = 'AND NOT';
            $data = $this->doSearch($expected['itemtype'], $search_params);
            $this->assertSame($expected['notempty'], $data['data']['totalcount']);
        }
    }

    public function testManageParams()
    {
        // let's use TU_USER
        $this->login();
        $uid =  getItemByTypeName('User', TU_USER, true);

        $search = \Search::manageParams(Ticket::class, ['reset' => 1], false, false);
        $this->assertEquals(
            [
                'reset'        => 1,
                'itemtype'     => Ticket::class,
                'start'        => 0,
                'order'        => ['DESC'],
                'sort'         => [19],
                'is_deleted'   => 0,
                'criteria'     => [
                    0 => [
                        'field' => 12,
                        'searchtype' => 'equals',
                        'value' => 'notold',
                    ],
                ],
                'metacriteria' => [],
                'as_map'       => 0,
                'browse'       => 0,
                'unpublished'  => 1,
            ],
            $search
        );

        // now add a bookmark on Ticket view
        $bk = new \SavedSearch();
        $this->assertTrue(
            (bool) $bk->add(['name'         => 'All my tickets',
                'type'         => 1,
                'itemtype'     => Ticket::class,
                'users_id'     => $uid,
                'is_private'   => 1,
                'entities_id'  => 0,
                'is_recursive' => 1,
                'url'         => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $uid,
            ])
        );

        $bk_id = $bk->fields['id'];

        $bk_user = new \SavedSearch_User();
        $this->assertTrue(
            (bool) $bk_user->add(['users_id' => $uid,
                'itemtype' => Ticket::class,
                'savedsearches_id' => $bk_id,
            ])
        );

        // SavedSearch::load() sets this session flag before redirecting to the search page
        $_SESSION['glpi_loaded_savedsearch'] = $bk_id;

        $search = \Search::manageParams(Ticket::class, ['reset' => 1, 'savedsearches_id' => $bk_id], true, false);
        $this->assertEquals(
            [
                'reset'        => 1,
                'start'        => 0,
                'order'        => ['DESC'],
                'sort'         => [2],
                'is_deleted'   => 0,
                'criteria'     => [
                    0 => [
                        'field' => '5',
                        'searchtype' => 'equals',
                        'value' => $uid,
                    ],
                ],
                'metacriteria' => [],
                'itemtype' => Ticket::class,
                'savedsearches_id' => $bk_id,
                'as_map'           => 0,
                'browse'           => 0,
                'unpublished'      => 1,
            ],
            $search
        );

        // no stale 'reset' flag must remain in session after loading a saved search
        $this->assertEquals($bk_id, $_SESSION['glpi_loaded_savedsearch']);
        $this->assertArrayNotHasKey('reset', $_SESSION['glpisearch']['Ticket']);

        // saved search criteria must survive a subsequent unrelated request (sort/pagination)
        \Search::manageParams('Ticket', ['sort' => 6, 'order' => 'ASC'], true, false);
        $this->assertEquals(
            [
                0 => [
                    'field' => '5',
                    'searchtype' => 'equals',
                    'value' => $uid,
                ],
            ],
            $_SESSION['glpisearch']['Ticket']['criteria']
        );
        $this->assertEquals($bk_id, $_SESSION['glpi_loaded_savedsearch']);

        // let's test for Computers
        $search = \Search::manageParams(Computer::class, ['reset' => 1], false, false);
        $this->assertEquals(
            [
                'reset'      => 1,
                'itemtype'   => Computer::class,
                'start'      => 0,
                'order'      => ['ASC'],
                'sort'       => [0],
                'is_deleted' => 0,
                'criteria'   => [
                    [
                        'link'       => 'AND',
                        'field'      => 'view',
                        'searchtype' => 'contains',
                        'value'      => '',
                    ],
                ],
                'metacriteria'              => [],
                'as_map'                    => 0,
                'browse'                    => 0,
                'disable_order_by_fallback' => true,
                'unpublished'               => true,
            ],
            $search
        );

        // now add a bookmark on Computer view
        $bk = new \SavedSearch();
        $this->assertTrue(
            (bool) $bk->add(['name'         => 'Computer test',
                'type'         => 1,
                'itemtype'     => Computer::class,
                'users_id'     => $uid,
                'is_private'   => 1,
                'entities_id'  => 0,
                'is_recursive' => 1,
                'url'         => 'front/computer.php?itemtype=Computer&sort=31&order=DESC&criteria%5B0%5D%5Bfield%5D=view&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=test',
            ])
        );

        $bk_id = $bk->fields['id'];

        $bk_user = new \SavedSearch_User();
        $this->assertTrue(
            (bool) $bk_user->add(['users_id' => $uid,
                'itemtype' => Computer::class,
                'savedsearches_id' => $bk_id,
            ])
        );

        // SavedSearch::load() sets this session flag before redirecting to the search page
        $_SESSION['glpi_loaded_savedsearch'] = $bk_id;

        $search = \Search::manageParams(Computer::class, ['reset' => 1, 'savedsearches_id' => $bk_id], true, false);
        $this->assertEquals(
            [
                'reset'        => 1,
                'start'        => 0,
                'order'        => ['DESC'],
                'sort'         => [31],
                'is_deleted'   => 0,
                'criteria'     => [
                    0 => [
                        'field' => 'view',
                        'searchtype' => 'contains',
                        'value' => 'test',
                    ],
                ],
                'metacriteria' => [],
                'itemtype' => Computer::class,
                'savedsearches_id' => $bk_id,
                'as_map'           => 0,
                'browse'           => 0,
                'unpublished'               => 1,
                'disable_order_by_fallback' => true,
            ],
            $search
        );

        // no stale 'reset' flag must remain in session after loading a saved search
        $this->assertEquals($bk_id, $_SESSION['glpi_loaded_savedsearch']);
        $this->assertArrayNotHasKey('reset', $_SESSION['glpisearch']['Computer']);

        // saved search criteria must survive a subsequent unrelated request (sort/pagination)
        \Search::manageParams('Computer', ['sort' => 1, 'order' => 'ASC'], true, false);
        $this->assertEquals(
            [
                0 => [
                    'field' => 'view',
                    'searchtype' => 'contains',
                    'value' => 'test',
                ],
            ],
            $_SESSION['glpisearch']['Computer']['criteria']
        );
        $this->assertEquals($bk_id, $_SESSION['glpi_loaded_savedsearch']);
    }

    public static function addSelectProvider()
    {
        return [
            'special_fk' => [[
                'itemtype'  => Computer::class,
                'ID'        => 24, // users_id_tech
                'sql'       => '`glpi_users_users_id_tech`.`name` AS `ITEM_Computer_24`, `glpi_users_users_id_tech`.`realname` AS `ITEM_Computer_24_realname`,
                           `glpi_users_users_id_tech`.`id` AS `ITEM_Computer_24_id`, `glpi_users_users_id_tech`.`firstname` AS `ITEM_Computer_24_firstname`,',
            ],
            ],
            'regular_fk' => [[
                'itemtype'  => Computer::class,
                'ID'        => 70, // users_id
                'sql'       => '`glpi_users`.`name` AS `ITEM_Computer_70`, `glpi_users`.`realname` AS `ITEM_Computer_70_realname`,
                           `glpi_users`.`id` AS `ITEM_Computer_70_id`, `glpi_users`.`firstname` AS `ITEM_Computer_70_firstname`,',
            ],
            ],
        ];
    }

    #[DataProvider('addSelectProvider')]
    public function testAddSelect($provider)
    {
        $sql_select = \Search::addSelect($provider['itemtype'], $provider['ID']);

        $this->assertEquals(
            $this->cleanSQL($provider['sql']),
            $this->cleanSQL($sql_select)
        );
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function addLeftJoinProvider(): array
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
                        ],
                    ],
                ],
                'sql' => "LEFT JOIN `glpi_projectteams`
                        ON (`glpi_projects`.`id` = `glpi_projectteams`.`projects_id`)
                      LEFT JOIN `glpi_contacts`  AS `glpi_contacts_id_d36f89b191ea44cf6f7c8414b12e1e50`
                        ON (`glpi_contacts_id_d36f89b191ea44cf6f7c8414b12e1e50`.`id` = `glpi_projectteams`.`items_id`
                        AND `glpi_projectteams`.`itemtype` = ?)",
                'values' => ['Contact'],
            ],
            ],
            'special_fk' => [[
                'itemtype'           => Computer::class,
                'table'              => User::getTable(),
                'field'              => 'name',
                'linkfield'          => 'users_id_tech',
                'meta'               => false,
                'meta_type'          => null,
                'joinparams'         => [],
                'sql' => "LEFT JOIN `glpi_users` AS `glpi_users_users_id_tech` ON (`glpi_computers`.`users_id_tech` = `glpi_users_users_id_tech`.`id`)",
                'values' => [],
            ],
            ],
            'regular_fk' => [[
                'itemtype'           => Computer::class,
                'table'              => User::getTable(),
                'field'              => 'name',
                'linkfield'          => 'users_id',
                'meta'               => false,
                'meta_type'          => null,
                'joinparams'         => [],
                'sql' => "LEFT JOIN `glpi_users` ON (`glpi_computers`.`users_id` = `glpi_users`.`id`)",
                'values' => [],
            ],
            ],

            'linkfield in beforejoin' => [[
                'itemtype'           => Ticket::class,
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
                                'table'              => User::getTable(),
                                'linkfield'          => 'users_id_validate',
                                'joinparams'             => [
                                    'beforejoin'             => [
                                        'table'                  => \TicketValidation::getTable(),
                                        'joinparams'                 => [
                                            'jointype'                   => 'child',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                // This is a real use case. Ensure the LEFT JOIN chain uses consistent table names (see glpi_users_users_id_validate)
                'sql' => "LEFT JOIN `glpi_ticketvalidations` "
                . "ON (`glpi_tickets`.`id` = `glpi_ticketvalidations`.`tickets_id`) "
                . "LEFT JOIN `glpi_users` AS `glpi_users_users_id_validate_57751ba960bd8511d2ad8a01bd8487f4` "
                . "ON (`glpi_ticketvalidations`.`users_id_validate` = `glpi_users_users_id_validate_57751ba960bd8511d2ad8a01bd8487f4`.`id`) "
                . "LEFT JOIN `glpi_validatorsubstitutes` AS `glpi_validatorsubstitutes_f1e9cbef8429d6d41e308371824d1632` "
                . "ON (`glpi_users_users_id_validate_57751ba960bd8511d2ad8a01bd8487f4`.`id` = `glpi_validatorsubstitutes_f1e9cbef8429d6d41e308371824d1632`.`users_id`) "
                . "LEFT JOIN `glpi_validatorsubstitutes` AS `glpi_validatorsubstitutes_c9b716cdcdcfe62bc267613fce4d1f48` "
                . "ON (`glpi_validatorsubstitutes_f1e9cbef8429d6d41e308371824d1632`.`validatorsubstitutes_id` = `glpi_validatorsubstitutes_c9b716cdcdcfe62bc267613fce4d1f48`.`id`)",
                'values' => [],
            ],
            ],
        ];
    }

    #[DataProvider('addLeftJoinProvider')]
    public function testAddLeftJoin($lj_provider)
    {
        $already_link_tables = [];

        $ljoin = \Search::addLeftJoin(
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

        $this->assertEquals(
            $this->cleanSQL($lj_provider['sql']),
            $this->cleanSQL($ljoin->getQuery())
        );
        $this->assertEquals($lj_provider['values'], $ljoin->getParams());
    }

    public static function addOrderByProvider(): array
    {
        return [
            // Generic examples
            [
                Computer::class,
                [
                    [
                        'searchopt_id' => 5,
                        'order'        => 'ASC',
                    ],
                ], ' ORDER BY `ITEM_Computer_5` ASC',
            ],
            [
                Computer::class,
                [
                    [
                        'searchopt_id' => 5,
                        'order'        => 'DESC',
                    ],
                ], ' ORDER BY `ITEM_Computer_5` DESC',
            ],
            [
                Computer::class,
                [
                    [
                        'searchopt_id' => 5,
                        'order'        => 'INVALID',
                    ],
                ], ' ORDER BY `ITEM_Computer_5` DESC',
            ],
            [
                Computer::class,
                [
                    [
                        'searchopt_id' => 5,
                    ],
                ], ' ORDER BY `ITEM_Computer_5` ASC',
            ],
            // Simple Hard-coded cases
            [
                'IPAddress',
                [
                    [
                        'searchopt_id' => 1,
                        'order'        => 'ASC',
                    ],
                ], ' ORDER BY INET6_ATON(`glpi_ipaddresses`.`name`) ASC',
            ],
            [
                'IPAddress',
                [
                    [
                        'searchopt_id' => 1,
                        'order'        => 'DESC',
                    ],
                ], ' ORDER BY INET6_ATON(`glpi_ipaddresses`.`name`) DESC',
            ],
            [
                'User',
                [
                    [
                        'searchopt_id' => 1,
                        'order'        => 'ASC',
                    ],
                ], ' ORDER BY `glpi_users`.`name` ASC',
            ],
            [
                'User',
                [
                    [
                        'searchopt_id' => 1,
                        'order'        => 'DESC',
                    ],
                ], ' ORDER BY `glpi_users`.`name` DESC',
            ],
            // Multiple sort cases
            [
                Computer::class,
                [
                    [
                        'searchopt_id' => 5,
                        'order'        => 'ASC',
                    ],
                    [
                        'searchopt_id' => 6,
                        'order'        => 'ASC',
                    ],
                ], ' ORDER BY `ITEM_Computer_5` ASC, `ITEM_Computer_6` ASC',
            ],
            [
                Computer::class,
                [
                    [
                        'searchopt_id' => 5,
                        'order'        => 'ASC',
                    ],
                    [
                        'searchopt_id' => 6,
                        'order'        => 'DESC',
                    ],
                ], ' ORDER BY `ITEM_Computer_5` ASC, `ITEM_Computer_6` DESC',
            ],
        ];
    }

    #[DataProvider('addOrderByProvider')]
    public function testAddOrderBy($itemtype, $sort_fields, $expected)
    {
        $result = \Search::addOrderBy($itemtype, $sort_fields);
        $this->assertEquals($expected, $result);

        // Complex cases
        $table_addtable = 'glpi_users_1c66c681d52ccf761c59cdbbb63863e7';
        $table_ticket_user = 'glpi_tickets_users_34a3592581e8bd36564c0c7eb1bf3dc0';

        $_SESSION['glpinames_format'] = User::FIRSTNAME_BEFORE;
        $user_order_1 = \Search::addOrderBy(Ticket::class, [
            [
                'searchopt_id' => 4,
                'order'        => 'ASC',
            ],
        ]);
        $this->assertEquals(
            $this->cleanSQL(" ORDER BY GROUP_CONCAT(DISTINCT CONCAT(
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')
                                ) ORDER BY CONCAT(
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')) ASC
                                ) ASC"),
            $this->cleanSQL($user_order_1)
        );
        $user_order_2 = \Search::addOrderBy(Ticket::class, [
            [
                'searchopt_id' => 4,
                'order'        => 'DESC',
            ],
        ]);
        $this->assertEquals(
            $this->cleanSQL(" ORDER BY GROUP_CONCAT(DISTINCT CONCAT(
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')
                                ) ORDER BY CONCAT(
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')) ASC
                                ) DESC"),
            $this->cleanSQL($user_order_2)
        );

        $_SESSION['glpinames_format'] = User::REALNAME_BEFORE;
        $user_order_3 = \Search::addOrderBy(Ticket::class, [
            [
                'searchopt_id' => 4,
                'order'        => 'ASC',
            ],
        ]);
        $this->assertEquals(
            $this->cleanSQL(" ORDER BY GROUP_CONCAT(DISTINCT CONCAT(
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')
                                ) ORDER BY CONCAT(
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')) ASC
                                ) ASC"),
            $this->cleanSQL($user_order_3)
        );
        $user_order_4 = \Search::addOrderBy(Ticket::class, [
            [
                'searchopt_id' => 4,
                'order'        => 'DESC',
            ],
        ]);
        $this->assertEquals(
            $this->cleanSQL(" ORDER BY GROUP_CONCAT(DISTINCT CONCAT(
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')
                                ) ORDER BY CONCAT(
                                    IFNULL(`$table_addtable`.`realname`, ''),
                                    IFNULL(`$table_addtable`.`firstname`, ''),
                                    IFNULL(`$table_addtable`.`name`, ''),
                                IFNULL(`$table_ticket_user`.`alternative_email`, '')) ASC
                                ) DESC"),
            $this->cleanSQL($user_order_4)
        );
    }

    /**
     * Data provider for testAddOrderByUser
     */
    protected function testAddOrderByUserProvider(): iterable
    {
        global $DB;

        $user_1 = getItemByTypeName('User', TU_USER)->getID();
        $user_2 = getItemByTypeName('User', 'glpi')->getID();
        $group_1 = getItemByTypeName('Group', '_test_group_1')->getID();

        $this->assertTrue($DB->delete(Change::getTable(), [new QueryExpression('true')]));

        // Creates Changes with different requesters
        $this->createItems('Change', [
            // Test set on requester
            [
                'name' => 'testAddOrderByUser user 1 (R)',
                'content' => '',
                '_actors' => [
                    'requester' => [['itemtype' => 'User', 'items_id' => $user_1]],
                ],
            ],
            [
                'name' => 'testAddOrderByUser user 2 (R)',
                'content' => '',
                '_actors' => [
                    'requester' => [['itemtype' => 'User', 'items_id' => $user_2]],
                ],
            ],
            [
                'name' => 'testAddOrderByUser user 1 (R) + user 2 (R)',
                'content' => '',
                '_actors' => [
                    'requester' => [
                        ['itemtype' => 'User', 'items_id' => $user_1],
                        ['itemtype' => 'User', 'items_id' => $user_2],
                    ],
                ],
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
                            'use_notification' => true,
                        ],
                    ],
                ],
            ],
            [
                'name' => 'testAddOrderByUser group 1 (R)',
                'content' => '',
                '_actors' => [
                    'requester' => [['itemtype' => 'Group', 'items_id' => $group_1]],
                ],
            ],
            [
                'name' => 'testAddOrderByUser user 1 (R) + group 1 (R)',
                'content' => '',
                '_actors' => [
                    'requester' => [
                        ['itemtype' => 'User', 'items_id' => $user_1],
                        ['itemtype' => 'Group', 'items_id' => $group_1],
                    ],
                ],
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
            'row_name' => 'ITEM_Change_1',
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
            'row_name' => 'ITEM_Change_1',
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
            'row_name' => 'ITEM_Peripheral_1',
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
            'row_name' => 'ITEM_Peripheral_1',
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
            'row_name' => 'ITEM_Problem_1',
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
            'row_name' => 'ITEM_Problem_1',
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
            'row_name' => 'ITEM_Problem_1',
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
            'row_name' => 'ITEM_Problem_1',
        ];
    }

    public function testAddOrderByUser()
    {
        $this->login('glpi', 'glpi');
        $values = $this->testAddOrderByUserProvider();
        foreach ($values as $value) {
            $itemtype = $value['itemtype'];
            $search_params = $value['search_params'];
            $expected_order = $value['expected_order'];
            $row_name = $value['row_name'];

            $data = $this->doSearch($itemtype, $search_params);

            // Extract items names
            $items = [];
            foreach ($data['data']['rows'] as $row) {
                $items[] = $row['raw'][$row_name];
            }

            // Validate order
            $this->assertEquals($expected_order, $items);
        }
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
            'date_mod',
            'manufacturers_id',
            'entities_id',
        ];

        foreach ($CFG_GLPI["asset_types"] as $itemtype) {
            $table = getTableForItemType($itemtype);

            foreach ($needed_fields as $field) {
                $this->assertTrue(
                    $DB->fieldExists($table, $field),
                    "$table.$field is missing"
                );
            }
        }
    }

    public function testTickets()
    {
        $tech_users_id = getItemByTypeName('User', "tech", true);

        // reduce the right of tech profile
        // to have only the right of display their own tickets
        \ProfileRight::updateProfileRights(getItemByTypeName('Profile', "Technician", true), [
            Ticket::class => (Ticket::READMY),
        ]);

        // add a group for tech user
        $group = new Group();
        $groups_id = $group->add([
            'name' => "test group for tech user",
        ]);
        $this->assertGreaterThan(0, (int) $groups_id);
        $group_user = new Group_User();
        $this->assertGreaterThan(
            0,
            (int) $group_user->add([
                'groups_id' => $groups_id,
                'users_id'  => $tech_users_id,
            ])
        );

        // create a ticket
        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'         => "test ticket visibility for tech user with READNEWTICKET right",
                'content'      => "test ticket visibility for tech user with READNEWTICKET right",
            ])
        );

        // let's use tech user
        $this->login('tech', 'tech');

        // do search and check presence of the created problem
        $data = \Search::prepareDatasForSearch(Ticket::class, ['reset' => 'reset']);
        \Search::constructSQL($data);
        \Search::constructData($data);

        $this->assertEquals(0, $data['data']['totalcount']);

        // update the right of tech profile
        // to have only the right of display their own tickets and tickets with incoming status
        \ProfileRight::updateProfileRights(getItemByTypeName('Profile', "Technician", true), [
            Ticket::class => (Ticket::READMY + Ticket::READNEWTICKET),
        ]);

        // reload current profile to take into account the new rights
        $this->login('tech', 'tech');

        // do search and check presence of the created problem
        $data = \Search::prepareDatasForSearch(Ticket::class, ['reset' => 'reset']);
        \Search::constructSQL($data);
        \Search::constructData($data);

        foreach ($data['data']['rows'][0]['raw'] as $key => $value) {
            if (str_ends_with($key, 'status')) {
                $this->assertIsArray($data);
                $this->assertIsArray($data['data']);
                $this->assertIsArray($data['data']['rows']);
                $this->assertIsArray($data['data']['rows'][0]['raw']);
                $this->assertEquals(Ticket::INCOMING, $data['data']['rows'][0]['raw'][$key]);
            }
        }
    }

    public function testProblems()
    {
        $tech_users_id = getItemByTypeName('User', "tech", true);

        // reduce the right of tech profile
        // to have only the right of display their own problems (created, assign)
        \ProfileRight::updateProfileRights(getItemByTypeName('Profile', "Technician", true), [
            'Problem' => (\Problem::READMY + READNOTE + UPDATENOTE),
        ]);

        // add a group for tech user
        $group = new Group();
        $groups_id = $group->add([
            'name' => "test group for tech user",
        ]);
        $this->assertGreaterThan(0, $groups_id);
        $group_user = new Group_User();
        $this->assertGreaterThan(
            0,
            $group_user->add([
                'groups_id' => $groups_id,
                'users_id'  => $tech_users_id,
            ])
        );

        // create a problem and assign group with tech user
        $problem = new \Problem();
        $this->assertGreaterThan(
            0,
            $problem->add([
                'name'              => "test problem visibility for tech",
                'content'           => "test problem visibility for tech",
                '_groups_id_assign' => $groups_id,
            ])
        );

        // let's use tech user
        $this->login('tech', 'tech');

        // do search and check presence of the created problem
        $data = \Search::prepareDatasForSearch('Problem', ['reset' => 'reset']);
        \Search::constructSQL($data);
        \Search::constructData($data);

        $this->assertEquals(1, $data['data']['totalcount']);
        $this->assertIsArray($data);
        $this->assertIsArray($data['data']);
        $this->assertIsArray($data['data']['rows']);
        $this->assertIsArray($data['data']['rows'][0]);
        $this->assertIsArray($data['data']['rows'][0]['raw']);
        $this->assertEquals(
            'test problem visibility for tech',
            $data['data']['rows'][0]['raw']['ITEM_Problem_1']
        );
    }

    public function testChanges()
    {
        $tech_users_id = getItemByTypeName('User', "tech", true);

        // reduce the right of tech profile
        // to have only the right of display their own changes (created, assign)
        \ProfileRight::updateProfileRights(getItemByTypeName('Profile', "Technician", true), [
            'Change' => (Change::READMY + READNOTE + UPDATENOTE),
        ]);

        // add a group for tech user
        $group = new Group();
        $groups_id = $group->add([
            'name' => "test group for tech user",
        ]);
        $this->assertGreaterThan(0, $groups_id);

        $group_user = new Group_User();
        $this->assertGreaterThan(
            0,
            $group_user->add([
                'groups_id' => $groups_id,
                'users_id'  => $tech_users_id,
            ])
        );

        // create a Change and assign group with tech user
        $change = new Change();
        $this->assertGreaterThan(
            0,
            $change->add([
                'name'              => "test Change visibility for tech",
                'content'           => "test Change visibility for tech",
                '_groups_id_assign' => $groups_id,
            ])
        );

        // let's use tech user
        $this->login('tech', 'tech');

        // do search and check presence of the created Change
        $data = \Search::prepareDatasForSearch('Change', ['reset' => 'reset']);
        \Search::constructSQL($data);
        \Search::constructData($data);

        $this->assertEquals(1, $data['data']['totalcount']);
        $this->assertIsArray($data);
        $this->assertIsArray($data['data']);
        $this->assertIsArray($data['data']['rows']);
        $this->assertIsArray($data['data']['rows'][0]);
        $this->assertIsArray($data['data']['rows'][0]['raw']);
        $this->assertEquals(
            'test Change visibility for tech',
            $data['data']['rows'][0]['raw']['ITEM_Change_1']
        );
    }

    public function testSearchDdTranslation()
    {
        $this->login();

        $state = new \State();
        $this->assertTrue($state->maybeTranslated());

        $sid = $state->add([
            'name'         => 'A test state',
            'is_recursive' => 1,
        ]);
        $this->assertGreaterThan(0, $sid);

        $ddtrans = new DropdownTranslation();
        $this->assertGreaterThan(
            0,
            $ddtrans->add([
                'itemtype'  => $state->getType(),
                'items_id'  => $state->fields['id'],
                'language'  => 'fr_FR',
                'field'     => 'completename',
                'value'     => 'Un status de test',
            ])
        );

        $_SESSION['glpi_dropdowntranslations'] = [$state->getType() => ['completename' => '']];

        $search_params = [
            'is_deleted'   => 0,
            'start'        => 0,
            'criteria'     => [
                0 => [
                    'field'      => 'view',
                    'searchtype' => 'contains',
                    'value'      => 'test',
                ],
            ],
            'metacriteria' => [],
        ];

        $data = $this->doSearch('State', $search_params);

        $this->assertSame(1, $data['data']['totalcount']);

        unset($_SESSION['glpi_dropdowntranslations']);
    }

    public static function dataInfocomOptions()
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

    #[DataProvider('dataInfocomOptions')]
    public function testIsInfocomOption($index, $expected)
    {
        $this->assertSame($expected, \Search::isInfocomOption(Computer::class, $index));
    }

    public static function makeTextSearchValueProvider()
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
            ['snake_case', '%snake\_case%'], // _ is a wildcard that must be escaped
            ['quot\'ed', '%quot\'ed%'], // quotes should not be escaped by this method
            ['<PROD-15>$', '%<PROD-15>'],
            ['A&B', '%A&B%'],
            ["backslashes \\ \\\\ are twice escaped when not used in ', \n, \r, ... ", "%backslashes \\\\ \\\\\\\\ are twice escaped when not used in ', \n, \r, ...%"],
        ];
    }

    #[DataProvider('makeTextSearchValueProvider')]
    public function testMakeTextSearchValue($value, $expected)
    {
        $this->assertSame($expected, \Search::makeTextSearchValue($value));
    }

    /**
     * @return array{
     *     array{
     *         link: string,
     *         nott: int,
     *         itemtype: class-string,
     *         ID: int,
     *         searchtype: string,
     *         val: string,
     *         meta: bool,
     *         expected: string,
     *         expected_values: array,
     *     }
     * }
     */
    public static function providerAddWhere(): array
    {
        return [
            [
                'link' => Operator::NONE,
                'nott' => 0,
                'itemtype' => User::class,
                'ID' => 99,
                'searchtype' => 'equals',
                'val' => '5',
                'meta' => false,
                'expected' => "(`glpi_users_users_id_supervisor`.`id` = ?)",
                'expected_values' => [5],
            ],
            [
                'link' => Operator::AND,
                'nott' => 0,
                'itemtype' => \CartridgeItem::class,
                'ID' => 24,
                'searchtype' => 'equals',
                'val' => '2',
                'meta' => false,
                'expected' => "AND (`glpi_users_users_id_tech`.`id` = ?)",
                'expected_values' => [2],
            ],
            [
                'link' => Operator::AND,
                'nott' => 0,
                'itemtype' => \Monitor::class,
                'ID' => 11, // Search ID 11 (size field)
                'searchtype' => 'contains',
                'val' => '70',
                'meta' => false,
                'expected' => "AND (`glpi_monitors`.`size` LIKE ?)",
                'expected_values' => ['%70.%'],
            ],
            [
                'link' => Operator::AND,
                'nott' => 0,
                'itemtype' => \Monitor::class,
                'ID' => 11, // Search ID 11 (size field)
                'searchtype' => 'contains',
                'val' => '70.5',
                'meta' => false,
                'expected' => "AND (`glpi_monitors`.`size` LIKE ?)",
                'expected_values' => ['%70.5%'],
            ],
            [
                'link' => Operator::AND,
                'nott' => 0,
                'itemtype' => Computer::class,
                'ID' => 121, // Search ID 121 (date_creation field)
                'searchtype' => 'contains',
                'val' => '>2022-10-25',
                'meta' => false,
                'expected' => "AND CONVERT(`glpi_computers`.`date_creation` USING utf8mb4) > ?",
                'expected_values' => ['2022-10-25'],
            ],
            [
                'link' => Operator::AND,
                'nott' => 0,
                'itemtype' => Computer::class,
                'ID' => 121, // Search ID 121 (date_creation field)
                'searchtype' => 'contains',
                'val' => '<2022-10-25',
                'meta' => false,
                'expected' => "AND CONVERT(`glpi_computers`.`date_creation` USING utf8mb4) < ?",
                'expected_values' => ['2022-10-25'],
            ],
            [
                'link' => Operator::AND,
                'nott' => 0,
                'itemtype' => Computer::class,
                'ID' => 151, // Search ID 151 (Item_Disk freesize field)
                'searchtype' => 'contains',
                'val' => '>100',
                'meta' => false,
                'expected' => "AND `glpi_items_disks`.`freesize` > ?",
                'expected_values' => [100],
            ],
            [
                'link' => Operator::AND,
                'nott' => 0,
                'itemtype' => Computer::class,
                'ID' => 151, // Search ID 151 (Item_Disk freesize field)
                'searchtype' => 'contains',
                'val' => '<10000',
                'meta' => false,
                'expected' => "AND `glpi_items_disks`.`freesize` < ?",
                'expected_values' => [10000],
            ],
            [
                'link' => Operator::AND,
                'nott' => 0,
                'itemtype' => \NetworkName::class,
                'ID' => 13, // Search ID 13 (IPAddress name field)
                'searchtype' => 'contains',
                'val' => '< 192.168.1.10',
                'meta' => false,
                'expected' => "AND (INET_ATON(`glpi_ipaddresses`.`name`) < INET_ATON(?))",
                'expected_values' => ['192.168.1.10'],
            ],
            [
                'link' => Operator::AND,
                'nott' => 0,
                'itemtype' => \NetworkName::class,
                'ID' => 13, // Search ID 13 (IPAddress name field)
                'searchtype' => 'contains',
                'val' => '> 192.168.1.10',
                'meta' => false,
                'expected' => "AND (INET_ATON(`glpi_ipaddresses`.`name`) > INET_ATON(?))",
                'expected_values' => ['192.168.1.10'],
            ],
            [
                'link' => Operator::NONE,
                'nott' => 0,
                'itemtype' => Computer::class,
                'ID' => 1,
                'searchtype' => 'empty',
                'val' => 'null',
                'meta' => false,
                'expected' => "((`glpi_computers`.`name` = ?) OR `glpi_computers`.`name` IS NULL)",
                'expected_values' => [''],
            ],
        ];
    }

    #[DataProvider('providerAddWhere')]
    public function testAddWhere($link, $nott, $itemtype, $ID, $searchtype, $val, $meta, $expected, $expected_values)
    {
        $where = \Search::addWhere($link, $nott, $itemtype, $ID, $searchtype, $val, $meta);
        $this->assertEquals($expected, $this->cleanSQL($where->getQuery()));
        $this->assertEquals($expected_values, $where->getParams());

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
                    'value'      => $val,
                ],
            ],
            'metacriteria' => [],
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
                'value'      => 'pc',
            ],
            ],
        ];
        $data = $this->doSearch(Computer::class, $search_params);

        $this->assertSame(9, $data['data']['totalcount']);

        $displaypref = new \DisplayPreference();
        $input = [
            'itemtype'  => Computer::class,
            'users_id'  => Session::getLoginUserID(),
            'num'       => 49, //Computer groups_id_tech SO
        ];
        $this->assertGreaterThan(0, $displaypref->add($input));

        $data = $this->doSearch(Computer::class, $search_params);

        $this->assertSame(9, $data['data']['totalcount']);
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
                ],
            ],
        ];
        $data = $this->doSearch(Ticket::class, $search_params);

        $contains = [
            // Check that we have two different joins
            "LEFT JOIN `glpi_users` AS `glpi_users_users_id_lastupdater`",
            "LEFT JOIN `glpi_users` AS `glpi_users_users_id_recipient`",

            // Check that SELECT criteria applies on corresponding table alias
            "`glpi_users_users_id_lastupdater`.`realname` AS `ITEM_Ticket_64_realname`",
            "`glpi_users_users_id_recipient`.`realname` AS `ITEM_Ticket_22_realname`",

            // Check that WHERE criteria applies on corresponding table alias
            "`glpi_users_users_id_lastupdater`.`id` = ?",
            "`glpi_users_users_id_recipient`.`id` = ?",

            // Check that ORDER applies on corresponding table alias
            "CONCAT(
                                    IFNULL(`glpi_users_users_id_recipient`.`realname`, ''),
                                    IFNULL(`glpi_users_users_id_recipient`.`firstname`, ''),
                                    IFNULL(`glpi_users_users_id_recipient`.`name`, '')
                                ) ASC",
        ];
        foreach ($contains as $contain) {
            $this->assertStringContainsString($contain, $data['sql']['search']->getQuery());
        }
    }

    public function testSearchAllAssets()
    {
        $test_root       = getItemByTypeName('Entity', '_test_root_entity', true);
        $test_child_1    = getItemByTypeName('Entity', '_test_child_1', true);
        $test_child_2    = getItemByTypeName('Entity', '_test_child_2', true);
        $test_child_3    = getItemByTypeName('Entity', '_test_child_3', true);

        $search_params = [
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
            ],
        ];
        $data = $this->doSearch('AllAssets', $search_params);

        $this->assertMatchesRegularExpression(
            "/OR\s*\(`glpi_entities`\.`completename`\s*LIKE \?\s*\)/",
            $data['sql']['search']->getQuery()
        );
        $this->assertMatchesRegularExpression(
            "/OR\s*\(`glpi_states`\.`completename`\s*LIKE \?\s*\)/",
            $data['sql']['search']->getQuery()
        );

        $types = [
            Computer::getTable(),
            \Monitor::getTable(),
            \NetworkEquipment::getTable(),
            \Peripheral::getTable(),
            \Phone::getTable(),
            \Printer::getTable(),
        ];

        foreach ($types as $type) {
            $this->assertStringContainsString(
                "`$type`.`is_deleted` = ?",
                $data['sql']['search']->getQuery()
            );
            $this->assertStringContainsString(
                "AND `$type`.`is_template` = ?",
                $data['sql']['search']->getQuery()
            );
            $this->assertStringContainsString(
                "`$type`.`entities_id` IN (?, ?, ?, ?)",
                $data['sql']['search']->getQuery()
            );
            $this->assertStringContainsString(
                "OR (`$type`.`is_recursive` = ? AND `$type`.`entities_id` IN (?))",
                $data['sql']['search']->getQuery()
            );
            $this->assertMatchesRegularExpression(
                "/`$type`\.`name` LIKE \?/m",
                $data['sql']['search']->getQuery()
            );
        }

        $entities = [
            $test_root,
            $test_child_1,
            $test_child_2,
            $test_child_3,
        ];
        foreach ($entities as $entity) {
            foreach ([true, false] as $is_recursive) {
                Session::loadEntity($entity, $is_recursive);
                $data = $this->doSearch('AllAssets', $search_params);

                // Check that all returned items are viewable
                foreach ($data['data']['rows'] as $row) {
                    $asset = new $row['TYPE']();
                    $asset->getFromDB($row['id']);
                    $this->assertTrue($asset->canViewItem());
                }
            }
        }
    }

    /**
     * A search option holding a `computation` may bind parameters in the SELECT clause
     *
     * For union searches (AllAssets, ReservationItem), the same SELECT is reused for each
     * sub-query of the UNION; its bound values (SELECT_VALUES) must therefore be repeated in
     * every sub-query parameters set. Otherwise the number of bound values no longer matches
     * the number of placeholders and the prepared statement fails.
     *
     * @see SQLProvider::constructSQL() union branches (AllAssets and reference table cases)
     */
    public function testSearchUnionWithComputedSelectParams()
    {
        $this->login();

        // A computed search option that binds a parameter in the SELECT clause.
        $custom_id = 990001;
        $custom_opt = [
            'id'            => (string) $custom_id,
            'field'         => 'id',
            'name'          => 'Test computed select',
            'datatype'      => 'bool',
            'massiveaction' => false,
            'nosearch'      => true,
            'computation'   => new QueryExpression('IF(? = 1, 1, 0)', null, [1]),
            'joinparams'    => [],
            'linkfield'     => '',
        ];

        $itemtypes = [
            'AllAssets'       => Computer::getTable(),
            'ReservationItem' => \ReservationItem::getTable(),
        ];

        foreach ($itemtypes as $itemtype => $table) {
            // Register the computed option through the search option cache.
            $options = SearchOption::getOptionsForItemtype($itemtype);
            $options[$custom_id] = ['table' => $table] + $custom_opt;

            $cache_property = new \ReflectionProperty(SearchOption::class, 'search_options_cache');
            $cache = $cache_property->getValue();
            $cache["{$itemtype}_1"] = $options;
            $cache["{$itemtype}_0"] = $options;
            $cache_property->setValue(null, $cache);

            try {
                $search_params = [
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
                    ],
                ];

                // Would throw a "number of placeholders does not match number of values"
                // StatementException if SELECT_VALUES were dropped from the union sub-queries.
                $data = $this->doSearch($itemtype, $search_params, [$custom_id]);

                $query = $data['sql']['search']->getQuery();
                $this->assertSame(
                    substr_count($query, '?'),
                    count($data['sql']['search']->getParams()),
                    "$itemtype: bound values count does not match placeholders count"
                );
            } finally {
                SearchOption::clearSearchOptionCache($itemtype);
            }
        }
    }

    public function testSearchWithNamespacedItem()
    {
        global $CFG_GLPI;

        $search_params = [
            'is_deleted'   => 0,
            'start'        => 0,
            'search'       => 'Search',
        ];
        $this->login();
        $this->setEntity('_test_root_entity', true);

        $CFG_GLPI['state_types'][] = \SearchTest\Computer::class;
        $data = $this->doSearch(\SearchTest\Computer::class, $search_params);

        $this->assertStringContainsString(
            "`glpi_computers`.`name` AS `ITEM_SearchTest\Computer_1`",
            $data['sql']['search']->getQuery()
        );
        $this->assertStringContainsString(
            "`glpi_computers`.`id` AS `ITEM_SearchTest\Computer_1_id`",
            $data['sql']['search']->getQuery()
        );
        $this->assertStringContainsString(
            "ORDER BY `id`",
            $data['sql']['search']->getQuery()
        );
    }

    public function testGroupParamAfterMeta()
    {
        // Try to run this query without warnings
        $this->doSearch(Ticket::class, [
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
                    'itemtype'   => Computer::class,
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
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Check that search result is valid.
     *
     * @param array $result
     */
    private function checkSearchResult($result)
    {
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('count', $result['data']);
        $this->assertArrayHasKey('begin', $result['data']);
        $this->assertArrayHasKey('end', $result['data']);
        $this->assertArrayHasKey('totalcount', $result['data']);
        $this->assertArrayHasKey('cols', $result['data']);
        $this->assertArrayHasKey('rows', $result['data']);
        $this->assertArrayHasKey('items', $result['data']);
        $this->assertIsInt($result['data']['count']);
        $this->assertIsInt($result['data']['begin']);
        $this->assertIsInt($result['data']['end']);
        $this->assertIsInt($result['data']['totalcount']);
        $this->assertIsArray($result['data']['cols']);
        $this->assertIsArray($result['data']['rows']);
        $this->assertIsArray($result['data']['items']);

        $this->assertArrayHasKey('sql', $result);
        $this->assertArrayHasKey('search', $result['sql']);
        $this->assertInstanceOf(Select::class, $result['sql']['search']);
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
                'NotificationSettingConfig', // Stores its data in glpi_configs, does not acts as a CommonDBTM
                'PendingReasonCron',
                '/^[A-z]+Stencil/',
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

    public static function namesOutputProvider(): array
    {
        return [
            [
                'params' => [
                    'display_type' => \Search::NAMES_OUTPUT,
                    'export_all'   => 1,
                    'criteria'     => [],
                    'item_type'    => Ticket::class,
                    'is_deleted'   => 0,
                    'as_map'       => 0,
                ],
                'expected' => [
                    '_ticket01',
                    '_ticket02',
                    '_ticket03',
                    '_ticket100',
                    '_ticket101',
                ],
            ],
            [
                'params' => [
                    'display_type' => \Search::NAMES_OUTPUT,
                    'export_all'   => 1,
                    'criteria'     => [],
                    'item_type'    => Computer::class,
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
                ],
            ],
        ];
    }

    #[DataProvider('namesOutputProvider')]
    public function testNamesOutput(array $params, array $expected)
    {
        $this->login();

        // Run search and capture results
        ob_start();
        \Search::showList($params['item_type'], $params);
        $names = ob_get_clean();

        // Convert results to array
        $names = explode("\n", trim($names));

        // Check results
        $this->assertCount(count($expected), $names);
        foreach ($expected as $name) {
            $this->assertContains($name, $names);
        }
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
            $this->assertGreaterThan(0, $tickets_id);
            $actors = $ticket->getITILActors();
            $this->assertEquals(\CommonITILActor::OBSERVER, $actors[$params['observer']][0]);
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
                    ],
                ],
                'expected' => [
                    'testMyselfSearchCriteriaProvider 1',
                    'testMyselfSearchCriteriaProvider 2',
                    'testMyselfSearchCriteriaProvider 3',
                ],
            ],
            // Case 2: Search for tickets where 'tech' is an observer
            [
                'criteria' => [
                    [
                        'link'       => 'AND',
                        'field'      => 66, // Observer search option
                        'searchtype' => 'equals',
                        'value'      => $tech_users_id,
                    ],
                ],
                'expected' => [
                    'testMyselfSearchCriteriaProvider 4',
                ],
            ],
            // Case 3: Search for tickets where the current user (TU_USER) is an observer
            [
                'criteria' => [
                    [
                        'link'       => 'AND',
                        'field'      => 66, // Observer search option
                        'searchtype' => 'equals',
                        'value'      => 'myself',
                    ],
                ],
                'expected' => [
                    'testMyselfSearchCriteriaProvider 1',
                    'testMyselfSearchCriteriaProvider 2',
                    'testMyselfSearchCriteriaProvider 3',
                ],
            ],
        ];
    }

    /**
     * Functional test for the 'myself' search criteria.
     * We use the output type "Search::NAMES_OUTPUT" during the test as it make
     * it easy to parse the results.
     */
    public function testMyselfSearchCriteria()
    {
        $this->login();

        $data = $this->testMyselfSearchCriteriaProvider();
        foreach ($data as $row) {
            $criteria = $row['criteria'];
            $expected = $row['expected'];

            // Run search and capture results
            ob_start();
            \Search::showList(Ticket::class, [
                'display_type' => \Search::NAMES_OUTPUT,
                'export_all' => 1,
                'criteria' => $criteria,
                'item_type' => Ticket::class,
                'is_deleted' => 0,
                'as_map' => 0,
            ]);
            $names = ob_get_clean();

            // Convert results to array and remove last row (always empty for NAMES_OUTPUT)
            $names = explode("\n", $names);
            array_pop($names);

            // Check results
            $this->assertEquals($expected, $names);
        }
    }

    public static function isVirtualFieldProvider(): array
    {
        return [
            ['name', false],
            ['name_virtual', false],
            ['_virtual', true],
            ['_virtual_name', true],
        ];
    }

    /**
     * @param string $field
     * @param bool $expected
     * @return void
     */
    #[DataProvider('isVirtualFieldProvider')]
    public function testIsVirtualField(string $field, bool $expected): void
    {
        $this->assertEquals($expected, \Search::isVirtualField($field));
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
        $is_mariadb = preg_match('/-MariaDB/', $DB->getVersion()) === 1;

        // Check simple values search.
        // Usage is only relevant for textual fields, so it is not tested on other fields.

        // datatype=dropdown
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 4, // type
            'value'             => 'test',
            'expected_and'      => "(`glpi_computertypes`.`name` LIKE ?)",
            'expected_and_not'  => "(((NOT (`glpi_computertypes`.`name` LIKE ?))) OR (`glpi_computertypes`.`name` IS NULL))",
            'expected_values'    => [Computer::class, Computer::class, '%test%'],
        ];

        // datatype=dropdown (usehaving=true)
        yield [
            'itemtype'          => Ticket::class,
            'search_option'     => 142, // document name
            'value'             => 'test',
            'expected_and'      => "(`ITEM_Ticket_142` LIKE ?)",
            'expected_and_not'  => "(((NOT (`ITEM_Ticket_142` LIKE ?))) OR (`ITEM_Ticket_142` IS NULL))",
            'expected_values'    => ['1', Ticket::class, '%test%'],
        ];

        // datatype=itemlink
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 1, // name
            'value'             => 'test',
            'expected_and'      => "(`glpi_computers`.`name` LIKE ?)",
            'expected_and_not'  => "(((NOT (`glpi_computers`.`name` LIKE ?))) OR (`glpi_computers`.`name` IS NULL))",
            'expected_values'    => [Computer::class, Computer::class, '%test%'],
        ];

        // datatype=itemlink (usehaving=true)
        yield [
            'itemtype'          => Ticket::class,
            'search_option'     => 50, // parent tickets
            'value'             => 'test',
            'expected_and'      => "(`ITEM_Ticket_50` LIKE ?)",
            'expected_and_not'  => "(((NOT (`ITEM_Ticket_50` LIKE ?))) OR (`ITEM_Ticket_50` IS NULL))",
            'expected_values'    => ['1', '3', '%test%'],
        ];

        // datatype=string
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 47, // uuid
            'value'             => 'test',
            'expected_and'      => "(`glpi_computers`.`uuid` LIKE ?)",
            'expected_and_not'  => "(((NOT (`glpi_computers`.`uuid` LIKE ?))) OR (`glpi_computers`.`uuid` IS NULL)))",
            'expected_values'    => [Computer::class, Computer::class, '%test%'],
        ];

        // datatype=text
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 16, // comment
            'value'             => 'test',
            'expected_and'      => "(`glpi_computers`.`comment` LIKE ?)",
            'expected_and_not'  => "(((NOT (`glpi_computers`.`comment` LIKE ?))) OR (`glpi_computers`.`comment` IS NULL))",
            'expected_values'    => [Computer::class, Computer::class, '%test%'],
        ];

        // datatype=integer
        yield [
            'itemtype'          => \AuthLDAP::class,
            'search_option'     => 4, // port
            'value'             => 'test',
            'expected_and'      => "false",
            'expected_and_not'  => "false",
            'expected_values'   => [],
        ];
        yield [
            'itemtype'          => \AuthLDAP::class,
            'search_option'     => 4, // port
            'value'             => '123',
            'expected_and'      => "`glpi_authldaps`.`port` = ?",
            'expected_and_not'  => "`glpi_authldaps`.`port` <> ?",
            'expected_values'   => [123.0],
        ];

        // datatype=number
        yield [
            'itemtype'          => \AuthLDAP::class,
            'search_option'     => 32, // timeout
            'value'             => 'test',
            'expected_and'      => "false",
            'expected_and_not'  => "false",
            'expected_values'   => [],
        ];
        yield [
            'itemtype'          => \AuthLDAP::class,
            'search_option'     => 32, // timeout
            'value'             => '30',
            'expected_and'      => "`glpi_authldaps`.`timeout` = ?",
            'expected_and_not'  => "`glpi_authldaps`.`timeout` <> ?",
            'expected_values'   => [30.0],
        ];

        // datatype=number (usehaving=true)
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 115, // harddrive capacity
            'value'             => 'test',
            'expected_and'      => "(`ITEM_Computer_115` LIKE ?)",
            'expected_and_not'  => "(((NOT (`ITEM_Computer_115` LIKE ?))) OR (`ITEM_Computer_115` IS NULL))",
            'expected_values'   => [Computer::class, Computer::class, Computer::class, '%test%'],
        ];
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 115, // harddrive capacity
            'value'             => '512',
            'expected_and'      => "(`ITEM_Computer_115` < ?) AND (`ITEM_Computer_115` > ?)",
            'expected_and_not'  => "((`ITEM_Computer_115` > ?) OR (`ITEM_Computer_115` < ?))",
            'expected_values'   => [Computer::class, Computer::class, Computer::class, 1512, -488],
        ];

        // datatype=decimal
        yield [
            'itemtype'          => \Budget::class,
            'search_option'     => 7, // value
            'value'             => 'test',
            'expected_and'      => "false",
            'expected_and_not'  => "false",
        ];
        yield [
            'itemtype'          => \Budget::class,
            'search_option'     => 7, // value
            'value'             => '1500',
            'expected_and'      => "(`glpi_budgets`.`value` LIKE ?)",
            'expected_and_not'  => "(((NOT (`glpi_budgets`.`value` LIKE ?))) OR (`glpi_budgets`.`value` IS NULL))",
            'expected_values'   => ['%1500.%'],
        ];
        yield [
            'itemtype'          => \Budget::class,
            'search_option'     => 7, // value
            'value'             => '10.25',
            'expected_and'      => "(`glpi_budgets`.`value` LIKE ?)",
            'expected_and_not'  => "(((NOT (`glpi_budgets`.`value` LIKE ?))) OR (`glpi_budgets`.`value` IS NULL))",
            'expected_values'   => ['%10.2%'],
        ];

        // datatype=decimal (usehaving=true)
        yield [
            'itemtype'          => \Contract::class,
            'search_option'     => 11, // totalcost
            'value'             => 'test',
            'expected_and'      => "(`ITEM_Contract_11` LIKE ?)",
            'expected_and_not'  => "(((NOT (`ITEM_Contract_11` LIKE ?))) OR (`ITEM_Contract_11` IS NULL))",
            'expected_values'    => ['%test%'],
        ];
        yield [
            'itemtype'          => \Contract::class,
            'search_option'     => 11, // totalcost
            'value'             => '250',
            'expected_and'      => "`ITEM_Contract_11` = ?",
            'expected_and_not'  => "`ITEM_Contract_11` <> ?",
            'expected_values'    => [250],
        ];

        // datatype=count (usehaving=true)
        yield [
            'itemtype'          => Ticket::class,
            'search_option'     => 27, // number of followups
            'value'             => 'test',
            'expected_and'      => "(`ITEM_Ticket_27` LIKE ?)",
            'expected_and_not'  => "(((NOT (`ITEM_Ticket_27` LIKE ?))) OR (`ITEM_Ticket_27` IS NULL))",
            'expected_values'    => ['1', Ticket::class, '%test%'],
        ];
        yield [
            'itemtype'          => Ticket::class,
            'search_option'     => 27, // number of followups
            'value'             => '10',
            'expected_and'      => "`ITEM_Ticket_27` = ?",
            'expected_and_not'  => "`ITEM_Ticket_27` <> ?",
            'expected_values'    => ['1', Ticket::class, 10],
        ];

        // datatype=mio (usehaving=true)
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 111, // memory size
            'value'             => 'test',
            'expected_and'      => "(`ITEM_Computer_111` LIKE ?)",
            'expected_and_not'  => "(((NOT (`ITEM_Computer_111` LIKE ?))) OR (`ITEM_Computer_111` IS NULL))",
            'expected_values'    => [Computer::class, Computer::class, Computer::class, '%test%'],
        ];
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 111, // memory size
            'value'             => '512',
            'expected_and'      => "(`ITEM_Computer_111` < ?) AND (`ITEM_Computer_111` > ?)",
            'expected_and_not'  => "((`ITEM_Computer_111` > ?) OR (`ITEM_Computer_111` < ?))",
            'expected_values'    => [Computer::class, Computer::class, Computer::class, 612, 412],
        ];

        // datatype=progressbar (with computation)
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 152, // harddrive freepercent
            'value'             => 'test',
            'expected_and'      => "LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.`totalsize`, 0), 0), 3, '0') LIKE ?",
            'expected_and_not'  => "LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.`totalsize`, 0), 0), 3, '0') NOT LIKE ? OR LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.`totalsize`, 0), 0), 3, '0') IS NULL",
            'expected_values'    => [Computer::class, Computer::class, Computer::class, '%test%'],
        ];
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 152, // harddrive freepercent
            'value'             => '50',
            'expected_and'      => "LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.`totalsize`, 0), 0), 3, '0') >= ? AND LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.`totalsize`, 0), 0), 3, '0') <= ?",
            'expected_and_not'  => "LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.`totalsize`, 0), 0), 3, '0') < ? OR LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.`totalsize`, 0), 0), 3, '0') > ? OR LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.`totalsize`, 0), 0), 3, '0') IS NULL",
            'expected_values'    => [Computer::class, Computer::class, Computer::class, 48.0, 52.0],
        ];

        // datatype=timestamp
        yield [
            'itemtype'          => \CronTask::class,
            'search_option'     => 6, // frequency
            'value'             => 'test',
            'expected_and'      => "false",
            'expected_and_not'  => "false",
        ];
        yield [
            'itemtype'          => \CronTask::class,
            'search_option'     => 6, // frequency
            'value'             => '3600',
            'expected_and'      => "`glpi_crontasks`.`frequency` = ?",
            'expected_and_not'  => "`glpi_crontasks`.`frequency` <> ?",
            'expected_values'    => [3600.0],
        ];

        // datatype=timestamp (usehaving=true)
        yield [
            'itemtype'          => Ticket::class,
            'search_option'     => 49, // actiontime
            'value'             => 'test',
            'expected_and'      => "(`ITEM_Ticket_49` LIKE ?)",
            'expected_and_not'  => "(((NOT (`ITEM_Ticket_49` LIKE ?))) OR (`ITEM_Ticket_49` IS NULL))",
            'expected_values'    => ['1', '%test%'],
        ];
        yield [
            'itemtype'          => Ticket::class,
            'search_option'     => 49, // actiontime
            'value'             => '3600',
            'expected_and'      => "`ITEM_Ticket_49` = ?",
            'expected_and_not'  => "`ITEM_Ticket_49` <> ?",
            'expected_values'    => ['1', 3600],
        ];

        // datatype=datetime
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 9, // last_inventory_update
            'value'             => 'test',
            'expected_and'      => "false",
            'expected_and_not'  => "false",
            'expected_values'    => [Computer::class, Computer::class],
        ];
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 9, // last_inventory_update
            'value'             => '2023-06',
            'expected_and'      => "(CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) LIKE ?)",
            'expected_and_not'  => "(CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) NOT LIKE ? OR CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) IS NULL)",
            'expected_values'    => [Computer::class, Computer::class, '%2023-06%'],
        ];

        // datatype=datetime (usehaving=true)
        yield [
            'itemtype'          => Ticket::class,
            'search_option'     => 188, // next_escalation_level
            'value'             => 'test',
            'expected_and'      => "(`ITEM_Ticket_188` LIKE ?)",
            'expected_and_not'  => "(((NOT (`ITEM_Ticket_188` LIKE ?))) OR (`ITEM_Ticket_188` IS NULL))",
            'expected_values'    => [0, 0, '1', '%test%'],
        ];
        yield [
            'itemtype'          => Ticket::class,
            'search_option'     => 188, // next_escalation_level
            'value'             => '2023-06',
            'expected_and'      => "(`ITEM_Ticket_188` LIKE ?)",
            'expected_and_not'  => "(((NOT (`ITEM_Ticket_188` LIKE ?))) OR (`ITEM_Ticket_188` IS NULL))",
            'expected_values'    => [0, 0, '1', '%2023-06%'],
        ];

        // datatype=date
        yield [
            'itemtype'          => \Budget::class,
            'search_option'     => 5, // begin_date
            'value'             => 'test',
            'expected_and'      => "false",
            'expected_and_not'  => "false",
        ];
        yield [
            'itemtype'          => \Budget::class,
            'search_option'     => 5, // begin_date
            'value'             => '2023',
            'expected_and'      => "(CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) LIKE ?)",
            'expected_and_not'  => "(CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) NOT LIKE ? OR CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) IS NULL)",
            'expected_values'    => ['%2023%'],
        ];

        // datatype=date_delay
        yield [
            'itemtype'          => \Contract::class,
            'search_option'     => 20, // end_date
            'value'             => 'test',
            'expected_and'      => "false",
            'expected_and_not'  => "false",
        ];
        yield [
            'itemtype'          => \Contract::class,
            'search_option'     => 20, // end_date
            'value'             => '2023-12',
            'expected_and'      => "(DATE_ADD(`glpi_contracts`.`begin_date`, INTERVAL `glpi_contracts`.`duration` MONTH) LIKE ?)",
            'expected_and_not'  => "(DATE_ADD(`glpi_contracts`.`begin_date`, INTERVAL `glpi_contracts`.`duration` MONTH) NOT LIKE ? OR DATE_ADD(`glpi_contracts`.`begin_date`, INTERVAL `glpi_contracts`.`duration` MONTH) IS NULL)",
            'expected_values'    => ['%2023-12%'],
        ];

        // datatype=email
        yield [
            'itemtype'          => \Contact::class,
            'search_option'     => 6, // email
            'value'             => 'test',
            'expected_and'      => "(`glpi_contacts`.`email` LIKE ?)",
            'expected_and_not'  => "(((NOT (`glpi_contacts`.`email` LIKE ?))) OR (`glpi_contacts`.`email` IS NULL))",
            'expected_values'    => ['%test%'],
        ];

        // datatype=weblink
        yield [
            'itemtype'          => Document::class,
            'search_option'     => 4, // link
            'value'             => 'test',
            'expected_and'      => "(`glpi_documents`.`link` LIKE ?)",
            'expected_and_not'  => "(((NOT (`glpi_documents`.`link` LIKE ?))) OR (`glpi_documents`.`link` IS NULL))",
            'expected_values'    => ['%test%'],
        ];

        // datatype=mac
        yield [
            'itemtype'          => \DeviceNetworkCard::class,
            'search_option'     => 11, // mac_default
            'value'             => 'test',
            'expected_and'      => "(`glpi_devicenetworkcards`.`mac_default` LIKE ?)",
            'expected_and_not'  => "(((NOT (`glpi_devicenetworkcards`.`mac_default` LIKE ?))) OR (`glpi_devicenetworkcards`.`mac_default` IS NULL))",
            'expected_values'    => ['%test%'],
        ];
        yield [
            'itemtype'          => \DeviceNetworkCard::class,
            'search_option'     => 11, // mac_default
            'value'             => 'a2:ef:00',
            'expected_and'      => "(`glpi_devicenetworkcards`.`mac_default` LIKE ?)",
            'expected_and_not'  => "(((NOT (`glpi_devicenetworkcards`.`mac_default` LIKE ?))) OR (`glpi_devicenetworkcards`.`mac_default` IS NULL))",
            'expected_values'    => ['%a2:ef:00%'],
        ];

        // datatype=color
        yield [
            'itemtype'          => \Cable::class,
            'search_option'     => 15, // color
            'value'             => 'test',
            'expected_and'      => "false",
            'expected_and_not'  => "false",
        ];
        yield [
            'itemtype'          => \Cable::class,
            'search_option'     => 15, // color
            'value'             => '#ffffff',
            'expected_and'      => "(`glpi_cables`.`color` LIKE ?)",
            'expected_and_not'  => "(((NOT (`glpi_cables`.`color` LIKE ?))) OR (`glpi_cables`.`color` IS NULL))",
            'expected_values'    => ['%#ffffff%'],
        ];

        // datatype=language
        yield [
            'itemtype'          => User::class,
            'search_option'     => 17, // language
            'value'             => 'test',
            'expected_and'      => "(`glpi_users`.`language` LIKE ?)",
            'expected_and_not'  => "(((NOT (`glpi_users`.`language` LIKE ?))) OR (`glpi_users`.`language` IS NULL))",
            'expected_values'    => ['%test%'],
        ];
        yield [
            'itemtype'          => User::class,
            'search_option'     => 17, // language
            'value'             => 'en_',
            'expected_and'      => "(`glpi_users`.`language` LIKE ?)",
            'expected_and_not'  => "(((NOT (`glpi_users`.`language` LIKE ?))) OR (`glpi_users`.`language` IS NULL))",
            'expected_values'    => ['%en\_%'],
        ];

        // Check `NULL` special value
        foreach (['NULL', 'null'] as $null_value) {
            // datatype=dropdown
            yield [
                'itemtype'          => Computer::class,
                'search_option'     => 4, // type
                'value'             => $null_value,
                'expected_and'      => "(((`glpi_computertypes`.`name` IS NULL)) OR (`glpi_computertypes`.`name` = ?))",
                'expected_and_not'  => "(NOT ((((`glpi_computertypes`.`name` IS NULL)) OR (`glpi_computertypes`.`name` = ?)))",
                'expected_values'    => [Computer::class, Computer::class, ''],
            ];

            // datatype=dropdown (usehaving=true)
            yield [
                'itemtype'          => Ticket::class,
                'search_option'     => 142, // document name
                'value'             => $null_value,
                'expected_and'      => "(((`ITEM_Ticket_142` IS NULL)) OR (`ITEM_Ticket_142` = ?))",
                'expected_and_not'  => "(NOT ((((`ITEM_Ticket_142` IS NULL)) OR (`ITEM_Ticket_142` = ?)))",
                'expected_values'    => ['1', Ticket::class, ''],
            ];

            // datatype=itemlink
            yield [
                'itemtype'          => Computer::class,
                'search_option'     => 1, // name
                'value'             => $null_value,
                'expected_and'      => "(((`glpi_computers`.`name` IS NULL)) OR (`glpi_computers`.`name` = ?))",
                'expected_and_not'  => "(NOT ((((`glpi_computers`.`name` IS NULL)) OR (`glpi_computers`.`name` = ?)))",
                'expected_values'    => [Computer::class, Computer::class, ''],
            ];

            // datatype=itemlink (usehaving=true)
            yield [
                'itemtype'          => Ticket::class,
                'search_option'     => 50, // parent tickets
                'value'             => $null_value,
                'expected_and'      => "(((`ITEM_Ticket_50` IS NULL)) OR (`ITEM_Ticket_50` = ?))",
                'expected_and_not'  => "(NOT ((((`ITEM_Ticket_50` IS NULL)) OR (`ITEM_Ticket_50` = ?)))",
                'expected_values'    => ['1', '3', ''],
            ];

            // datatype=string
            yield [
                'itemtype'          => Computer::class,
                'search_option'     => 47, // uuid
                'value'             => $null_value,
                'expected_and'      => "(((`glpi_computers`.`uuid` IS NULL)) OR (`glpi_computers`.`uuid` = ?))",
                'expected_and_not'  => "(NOT ((((`glpi_computers`.`uuid` IS NULL)) OR (`glpi_computers`.`uuid` = ?)))",
                'expected_values'    => [Computer::class, Computer::class, ''],
            ];

            // datatype=text
            yield [
                'itemtype'          => Computer::class,
                'search_option'     => 16, // comment
                'value'             => $null_value,
                'expected_and'      => "(((`glpi_computers`.`comment` IS NULL)) OR (`glpi_computers`.`comment` = ?))",
                'expected_and_not'  => "(NOT ((((`glpi_computers`.`comment` IS NULL)) OR (`glpi_computers`.`comment` = ?)))",
                'expected_values'    => [Computer::class, Computer::class, ''],
            ];

            // datatype=integer
            yield [
                'itemtype'          => \AuthLDAP::class,
                'search_option'     => 4, // port
                'value'             => $null_value,
                'expected_and'      => "(((`glpi_authldaps`.`port` IS NULL)) OR (`glpi_authldaps`.`port` = ?))",
                'expected_and_not'  => "(NOT ((((`glpi_authldaps`.`port` IS NULL)) OR (`glpi_authldaps`.`port` = ?)))",
                'expected_values'    => [''],
            ];
            // log for both AND and AND NOT cases
            /*if ($is_mariadb) {
                $this->hasPhpLogRecordThatContains("Truncated incorrect DECIMAL value: ''", LogLevel::WARNING);
                $this->hasPhpLogRecordThatContains("Truncated incorrect DECIMAL value: ''", LogLevel::WARNING);
            }*/

            // datatype=number
            yield [
                'itemtype'          => \AuthLDAP::class,
                'search_option'     => 32, // timeout
                'value'             => $null_value,
                'expected_and'      => "(((`glpi_authldaps`.`timeout` IS NULL)) OR (`glpi_authldaps`.`timeout` = ?))",
                'expected_and_not'  => "(NOT ((((`glpi_authldaps`.`timeout` IS NULL)) OR (`glpi_authldaps`.`timeout` = ?)))",
                'expected_values'    => [''],
            ];
            // log for both AND and AND NOT cases
            /*if ($is_mariadb) {
                $this->hasPhpLogRecordThatContains("Truncated incorrect DECIMAL value: ''", LogLevel::WARNING);
                $this->hasPhpLogRecordThatContains("Truncated incorrect DECIMAL value: ''", LogLevel::WARNING);
            }*/

            // datatype=number (usehaving=true)
            yield [
                'itemtype'          => Computer::class,
                'search_option'     => 115, // harddrive capacity
                'value'             => $null_value,
                'expected_and'      => "(((`ITEM_Computer_115` IS NULL)) OR (`ITEM_Computer_115` = ?))",
                'expected_and_not'  => "(NOT ((((`ITEM_Computer_115` IS NULL)) OR (`ITEM_Computer_115` = ?)))",
                'expected_values'    => [Computer::class, Computer::class, Computer::class, ''],
            ];

            // datatype=decimal
            yield [
                'itemtype'          => \Budget::class,
                'search_option'     => 7, // value
                'value'             => $null_value,
                'expected_and'      => "(((`glpi_budgets`.`value` IS NULL)) OR (`glpi_budgets`.`value` = ?))",
                'expected_and_not'  => "(NOT ((((`glpi_budgets`.`value` IS NULL)) OR (`glpi_budgets`.`value` = ?)))",
                'expected_values'    => [''],
            ];
            // log for both AND and AND NOT cases
            /*$this->hasPhpLogRecordThatContains("Truncated incorrect DECIMAL value: ''", LogLevel::WARNING);
            $this->hasPhpLogRecordThatContains("Truncated incorrect DECIMAL value: ''", LogLevel::WARNING);*/

            // datatype=decimal (usehaving=true)
            yield [
                'itemtype'          => \Contract::class,
                'search_option'     => 11, // totalcost
                'value'             => $null_value,
                'expected_and'      => "(((`ITEM_Contract_11` IS NULL)) OR (`ITEM_Contract_11` = ?))",
                'expected_and_not'  => "(NOT ((((`ITEM_Contract_11` IS NULL)) OR (`ITEM_Contract_11` = ?)))",
                'expected_values'    => [''],
            ];

            // datatype=count (usehaving=true)
            yield [
                'itemtype'          => Ticket::class,
                'search_option'     => 27, // number of followups
                'value'             => $null_value,
                'expected_and'      => "(((`ITEM_Ticket_27` IS NULL)) OR (`ITEM_Ticket_27` = ?))",
                'expected_and_not'  => "(NOT ((((`ITEM_Ticket_27` IS NULL)) OR (`ITEM_Ticket_27` = ?)))",
                'expected_values'    => ['1', Ticket::class, ''],
            ];
            // log for both AND and AND NOT cases
            /*if ($is_mariadb) {
                $this->hasPhpLogRecordThatContains("Truncated incorrect DECIMAL value: ''", LogLevel::WARNING);
                $this->hasPhpLogRecordThatContains("Truncated incorrect DECIMAL value: ''", LogLevel::WARNING);
            }*/

            // datatype=mio (usehaving=true)
            yield [
                'itemtype'          => Computer::class,
                'search_option'     => 111, // memory size
                'value'             => $null_value,
                'expected_and'      => "(((`ITEM_Computer_111` IS NULL)) OR (`ITEM_Computer_111` = ?))",
                'expected_and_not'  => "(NOT ((((`ITEM_Computer_111` IS NULL)) OR (`ITEM_Computer_111` = ?)))",
                'expected_values'    => [Computer::class, Computer::class, Computer::class, ''],
            ];

            // datatype=progressbar (with computation)
            yield [
                'itemtype'          => Computer::class,
                'search_option'     => 152, // harddrive freepercent
                'value'             => $null_value,
                'expected_and'      => "(LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.`totalsize`, 0), 0), 3, '0') IS NULL OR LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.`totalsize`, 0), 0), 3, '0') = ?)",
                'expected_and_not'  => "(LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.`totalsize`, 0), 0), 3, '0') IS NOT NULL AND LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.`totalsize`, 0), 0), 3, '0') <> ?)",
                'expected_values'    => [Computer::class, Computer::class, Computer::class, ''],
            ];

            // datatype=timestamp
            yield [
                'itemtype'          => \CronTask::class,
                'search_option'     => 6, // frequency
                'value'             => $null_value,
                'expected_and'      => "(((`glpi_crontasks`.`frequency` IS NULL)) OR (`glpi_crontasks`.`frequency` = ?))",
                'expected_and_not'  => "(NOT ((((`glpi_crontasks`.`frequency` IS NULL)) OR (`glpi_crontasks`.`frequency` = ?)))",
                'expected_values'    => [''],
            ];
            // log for both AND and AND NOT cases
            /*if ($is_mariadb) {
                $this->hasPhpLogRecordThatContains("Truncated incorrect DECIMAL value: ''", LogLevel::WARNING);
                $this->hasPhpLogRecordThatContains("Truncated incorrect DECIMAL value: ''", LogLevel::WARNING);
            }*/

            // datatype=timestamp (usehaving=true)
            yield [
                'itemtype'          => Ticket::class,
                'search_option'     => 49, // actiontime
                'value'             => $null_value,
                'expected_and'      => "(((`ITEM_Ticket_49` IS NULL)) OR (`ITEM_Ticket_49` = ?))",
                'expected_and_not'  => "(NOT ((((`ITEM_Ticket_49` IS NULL)) OR (`ITEM_Ticket_49` = ?)))",
                'expected_values'    => ['1', ''],
            ];

            // datatype=datetime
            yield [
                'itemtype'          => Computer::class,
                'search_option'     => 9, // last_inventory_update
                'value'             => $null_value,
                'expected_and'      => "(CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) IS NULL OR CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) = ?)",
                'expected_and_not'  => "(CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) IS NOT NULL AND CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) <> ?)",
                'expected_values'    => [Computer::class, Computer::class, ''],
            ];

            // datatype=datetime computed field
            yield [
                'itemtype'          => Ticket::class,
                'search_option'     => 188, // next_escalation_level
                'value'             => $null_value,
                'expected_and'      => "(((`ITEM_Ticket_188` IS NULL)) OR (`ITEM_Ticket_188` = ?))",
                'expected_and_not'  => "(NOT ((((`ITEM_Ticket_188` IS NULL)) OR (`ITEM_Ticket_188` = ?)))",
                'expected_values'    => [0, 0, '1', ''],
            ];

            // datatype=date
            yield [
                'itemtype'          => \Budget::class,
                'search_option'     => 5, // begin_date
                'value'             => $null_value,
                'expected_and'      => "(CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) IS NULL OR CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) = ?)",
                'expected_and_not'  => "(CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) IS NOT NULL AND CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) <> ?)",
                'expected_values'    => [''],
            ];

            // datatype=date_delay
            /*
             * FIXME Following search fails due to the following SQL error: `Error: Incorrect DATE value: ''`.
            yield [
                'itemtype'          => \Contract::class,
                'search_option'     => 20, // end_date
                'value'             => $null_value,
                'expected_and'      => "(DATE_ADD(`glpi_contracts`.`begin_date`, INTERVAL `glpi_contracts`.`duration` MONTH) IS  NULL  OR DATE_ADD(`glpi_contracts`.`begin_date`, INTERVAL `glpi_contracts`.`duration` MONTH) = ?)",
                'expected_and_not'  => "(DATE_ADD(`glpi_contracts`.`begin_date`, INTERVAL `glpi_contracts`.`duration` MONTH) IS NOT NULL  OR DATE_ADD(`glpi_contracts`.`begin_date`, INTERVAL `glpi_contracts`.`duration` MONTH) = ?)",
            ];
            */

            // datatype=email
            yield [
                'itemtype'          => \Contact::class,
                'search_option'     => 6, // email
                'value'             => $null_value,
                'expected_and'      => "(((`glpi_contacts`.`email` IS NULL)) OR (`glpi_contacts`.`email` = ?))",
                'expected_and_not'  => "(NOT ((((`glpi_contacts`.`email` IS NULL)) OR (`glpi_contacts`.`email` = ?)))",
                'expected_values'    => [''],
            ];

            // datatype=weblink
            yield [
                'itemtype'          => Document::class,
                'search_option'     => 4, // link
                'value'             => $null_value,
                'expected_and'      => "(((`glpi_documents`.`link` IS NULL)) OR (`glpi_documents`.`link` = ?))",
                'expected_and_not'  => "(NOT ((((`glpi_documents`.`link` IS NULL)) OR (`glpi_documents`.`link` = ?)))",
                'expected_values'    => [''],
            ];

            // datatype=mac
            yield [
                'itemtype'          => \DeviceNetworkCard::class,
                'search_option'     => 11, // mac_default
                'value'             => $null_value,
                'expected_and'      => "(((`glpi_devicenetworkcards`.`mac_default` IS NULL)) OR (`glpi_devicenetworkcards`.`mac_default` = ?))",
                'expected_and_not'  => "(NOT ((((`glpi_devicenetworkcards`.`mac_default` IS NULL)) OR (`glpi_devicenetworkcards`.`mac_default` = ?)))",
                'expected_values'    => [''],
            ];

            // datatype=color
            yield [
                'itemtype'          => \Cable::class,
                'search_option'     => 15, // color
                'value'             => $null_value,
                'expected_and'      => "(((`glpi_cables`.`color` IS NULL)) OR (`glpi_cables`.`color` = ?))",
                'expected_and_not'  => "(NOT ((((`glpi_cables`.`color` IS NULL)) OR (`glpi_cables`.`color` = ?)))",
                'expected_values'    => [''],
            ];

            // datatype=language
            yield [
                'itemtype'          => User::class,
                'search_option'     => 17, // language
                'value'             => $null_value,
                'expected_and'      => "(((`glpi_users`.`language` IS NULL)) OR (`glpi_users`.`language` = ?))",
                'expected_and_not'  => "(NOT ((((`glpi_users`.`language` IS NULL)) OR (`glpi_users`.`language` = ?)))",
                'expected_values'    => [''],
            ];
        }

        // Check `^` and `$` operators.
        // Usage is only relevant for textual fields, so it is not tested on other fields.

        // datatype=dropdown
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 4, // type
            'value'             => '^test',
            'expected_and'      => "(`glpi_computertypes`.`name` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_computertypes`.`name` LIKE ?))) OR (`glpi_computertypes`.`name` IS NULL))",
            'expected_values'    => [Computer::class, Computer::class, 'test%'],
        ];
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 4, // type
            'value'             => 'test$',
            'expected_and'      => "(`glpi_computertypes`.`name` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_computertypes`.`name` LIKE ?))) OR (`glpi_computertypes`.`name` IS NULL))",
            'expected_values'    => [Computer::class, Computer::class, '%test'],
        ];
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 4, // type
            'value'             => '^test$',
            'expected_and'      => "(`glpi_computertypes`.`name` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_computertypes`.`name` LIKE ?))) OR (`glpi_computertypes`.`name` IS NULL))",
            'expected_values'    => [Computer::class, Computer::class, 'test'],
        ];

        // datatype=dropdown (usehaving=true)
        yield [
            'itemtype'          => Ticket::class,
            'search_option'     => 142, // document name
            'value'             => '^test',
            'expected_and'      => "(`ITEM_Ticket_142` LIKE ?)",
            'expected_and_not'  => "(NOT (`ITEM_Ticket_142` LIKE ?))) OR (`ITEM_Ticket_142` IS NULL))",
            'expected_values'    => ['1', Ticket::class, 'test%'],
        ];
        yield [
            'itemtype'          => Ticket::class,
            'search_option'     => 142, // document name
            'value'             => 'test$',
            'expected_and'      => "(`ITEM_Ticket_142` LIKE ?)",
            'expected_and_not'  => "(NOT (`ITEM_Ticket_142` LIKE ?))) OR (`ITEM_Ticket_142` IS NULL))",
            'expected_values'    => ['1', Ticket::class, '%test'],
        ];
        yield [
            'itemtype'          => Ticket::class,
            'search_option'     => 142, // document name
            'value'             => '^test$',
            'expected_and'      => "(`ITEM_Ticket_142` LIKE ?)",
            'expected_and_not'  => "(NOT (`ITEM_Ticket_142` LIKE ?))) OR (`ITEM_Ticket_142` IS NULL))",
            'expected_values'    => ['1', Ticket::class, 'test'],
        ];

        // datatype=itemlink
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 1, // name
            'value'             => '^test',
            'expected_and'      => "(`glpi_computers`.`name` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_computers`.`name` LIKE ?))) OR (`glpi_computers`.`name` IS NULL))",
            'expected_values'    => [Computer::class, Computer::class, 'test%'],
        ];
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 1, // name
            'value'             => 'test$',
            'expected_and'      => "(`glpi_computers`.`name` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_computers`.`name` LIKE ?))) OR (`glpi_computers`.`name` IS NULL))",
            'expected_values'    => [Computer::class, Computer::class, '%test'],
        ];
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 1, // name
            'value'             => '^test$',
            'expected_and'      => "(`glpi_computers`.`name` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_computers`.`name` LIKE ?))) OR (`glpi_computers`.`name` IS NULL))",
            'expected_values'    => [Computer::class, Computer::class, 'test'],
        ];

        // datatype=itemlink (usehaving=true)
        yield [
            'itemtype'          => Ticket::class,
            'search_option'     => 50, // parent tickets
            'value'             => '^test',
            'expected_and'      => "(`ITEM_Ticket_50` LIKE ?)",
            'expected_and_not'  => "(NOT (`ITEM_Ticket_50` LIKE ?))) OR (`ITEM_Ticket_50` IS NULL))",
            'expected_values'    => ['1', '3', 'test%'],
        ];
        yield [
            'itemtype'          => Ticket::class,
            'search_option'     => 50, // parent tickets
            'value'             => 'test$',
            'expected_and'      => "(`ITEM_Ticket_50` LIKE ?)",
            'expected_and_not'  => "(NOT (`ITEM_Ticket_50` LIKE ?))) OR (`ITEM_Ticket_50` IS NULL))",
            'expected_values'    => ['1', '3', '%test'],
        ];
        yield [
            'itemtype'          => Ticket::class,
            'search_option'     => 50, // parent tickets
            'value'             => '^test$',
            'expected_and'      => "(`ITEM_Ticket_50` LIKE ?)",
            'expected_and_not'  => "(NOT (`ITEM_Ticket_50` LIKE ?))) OR (`ITEM_Ticket_50` IS NULL))",
            'expected_values'    => ['1', '3', 'test'],
        ];

        // datatype=string
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 47, // uuid
            'value'             => '^test',
            'expected_and'      => "(`glpi_computers`.`uuid` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_computers`.`uuid` LIKE ?))) OR (`glpi_computers`.`uuid` IS NULL))",
            'expected_values'    => [Computer::class, Computer::class, 'test%'],
        ];
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 47, // uuid
            'value'             => 'test$',
            'expected_and'      => "(`glpi_computers`.`uuid` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_computers`.`uuid` LIKE ?))) OR (`glpi_computers`.`uuid` IS NULL))",
            'expected_values'    => [Computer::class, Computer::class, '%test'],
        ];
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 47, // uuid
            'value'             => '^test$',
            'expected_and'      => "(`glpi_computers`.`uuid` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_computers`.`uuid` LIKE ?))) OR (`glpi_computers`.`uuid` IS NULL))",
            'expected_values'    => [Computer::class, Computer::class, 'test'],
        ];

        // datatype=text
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 16, // comment
            'value'             => '^test',
            'expected_and'      => "(`glpi_computers`.`comment` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_computers`.`comment` LIKE ?))) OR (`glpi_computers`.`comment` IS NULL))",
            'expected_values'    => [Computer::class, Computer::class, 'test%'],
        ];
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 16, // comment
            'value'             => 'test$',
            'expected_and'      => "(`glpi_computers`.`comment` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_computers`.`comment` LIKE ?))) OR (`glpi_computers`.`comment` IS NULL))",
            'expected_values'    => [Computer::class, Computer::class, '%test'],
        ];
        yield [
            'itemtype'          => Computer::class,
            'search_option'     => 16, // comment
            'value'             => '^test$',
            'expected_and'      => "(`glpi_computers`.`comment` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_computers`.`comment` LIKE ?))) OR (`glpi_computers`.`comment` IS NULL))",
            'expected_values'    => [Computer::class, Computer::class, 'test'],
        ];

        // datatype=email
        yield [
            'itemtype'          => \Contact::class,
            'search_option'     => 6, // email
            'value'             => '^myname@',
            'expected_and'      => "(`glpi_contacts`.`email` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_contacts`.`email` LIKE ?))) OR (`glpi_contacts`.`email` IS NULL))",
            'expected_values'    => ['myname@%'],
        ];
        yield [
            'itemtype'          => \Contact::class,
            'search_option'     => 6, // email
            'value'             => '@domain.tld$',
            'expected_and'      => "(`glpi_contacts`.`email` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_contacts`.`email` LIKE ?))) OR (`glpi_contacts`.`email` IS NULL))",
            'expected_values'    => ['%@domain.tld'],
        ];
        yield [
            'itemtype'          => \Contact::class,
            'search_option'     => 6, // email
            'value'             => '^myname@domain.tld$',
            'expected_and'      => "(`glpi_contacts`.`email` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_contacts`.`email` LIKE ?))) OR (`glpi_contacts`.`email` IS NULL))",
            'expected_values'    => ['myname@domain.tld'],
        ];

        // datatype=weblink
        yield [
            'itemtype'          => Document::class,
            'search_option'     => 4, // link
            'value'             => '^ftp://',
            'expected_and'      => "(`glpi_documents`.`link` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_documents`.`link` LIKE ?))) OR (`glpi_documents`.`link` IS NULL))",
            'expected_values'    => ['ftp://%'],
        ];
        yield [
            'itemtype'          => Document::class,
            'search_option'     => 4, // link
            'value'             => '.pdf$',
            'expected_and'      => "(`glpi_documents`.`link` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_documents`.`link` LIKE ?))) OR (`glpi_documents`.`link` IS NULL))",
            'expected_values'    => ['%.pdf'],
        ];
        yield [
            'itemtype'          => Document::class,
            'search_option'     => 4, // link
            'value'             => '^ftp://domain.tld/document.pdf$',
            'expected_and'      => "(`glpi_documents`.`link` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_documents`.`link` LIKE ?))) OR (`glpi_documents`.`link` IS NULL))",
            'expected_values'    => ['ftp://domain.tld/document.pdf'],
        ];

        // datatype=mac
        yield [
            'itemtype'          => \DeviceNetworkCard::class,
            'search_option'     => 11, // mac_default
            'value'             => '^a2:e5:aa',
            'expected_and'      => "(`glpi_devicenetworkcards`.`mac_default` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_devicenetworkcards`.`mac_default` LIKE ?))) OR (`glpi_devicenetworkcards`.`mac_default` IS NULL))",
            'expected_values'    => ['a2:e5:aa%'],
        ];
        yield [
            'itemtype'          => \DeviceNetworkCard::class,
            'search_option'     => 11, // mac_default
            'value'             => 'a2:e5:aa$',
            'expected_and'      => "(`glpi_devicenetworkcards`.`mac_default` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_devicenetworkcards`.`mac_default` LIKE ?))) OR (`glpi_devicenetworkcards`.`mac_default` IS NULL))",
            'expected_values'    => ['%a2:e5:aa'],
        ];
        yield [
            'itemtype'          => \DeviceNetworkCard::class,
            'search_option'     => 11, // mac_default
            'value'             => '^15:f4:q4:a2:e5:aa$',
            'expected_and'      => "(`glpi_devicenetworkcards`.`mac_default` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_devicenetworkcards`.`mac_default` LIKE ?))) OR (`glpi_devicenetworkcards`.`mac_default` IS NULL))",
            'expected_values'    => ['15:f4:q4:a2:e5:aa'],
        ];

        // datatype=color
        yield [
            'itemtype'          => \Cable::class,
            'search_option'     => 15, // color
            'value'             => '^#00',
            'expected_and'      => "(`glpi_cables`.`color` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_cables`.`color` LIKE ?))) OR (`glpi_cables`.`color` IS NULL))",
            'expected_values'    => ['#00%'],
        ];
        yield [
            'itemtype'          => \Cable::class,
            'search_option'     => 15, // color
            'value'             => 'ff$',
            'expected_and'      => "(`glpi_cables`.`color` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_cables`.`color` LIKE ?))) OR (`glpi_cables`.`color` IS NULL))",
            'expected_values'    => ['%ff'],
        ];
        yield [
            'itemtype'          => \Cable::class,
            'search_option'     => 15, // color
            'value'             => '^#00aaff$',
            'expected_and'      => "(`glpi_cables`.`color` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_cables`.`color` LIKE ?))) OR (`glpi_cables`.`color` IS NULL))",
            'expected_values'    => ['#00aaff'],
        ];

        // datatype=language
        yield [
            'itemtype'          => User::class,
            'search_option'     => 17, // language
            'value'             => '^en_',
            'expected_and'      => "(`glpi_users`.`language` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_users`.`language` LIKE ?))) OR (`glpi_users`.`language` IS NULL))",
            'expected_values'    => ['en\_%'], //FIXME?
        ];
        yield [
            'itemtype'          => User::class,
            'search_option'     => 17, // language
            'value'             => '_GB$',
            'expected_and'      => "(`glpi_users`.`language` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_users`.`language` LIKE ?))) OR (`glpi_users`.`language` IS NULL))",
            'expected_values'    => ['%\_GB'], //FIXME?
        ];
        yield [
            'itemtype'          => User::class,
            'search_option'     => 17, // language
            'value'             => '^en_GB$',
            'expected_and'      => "(`glpi_users`.`language` LIKE ?)",
            'expected_and_not'  => "(NOT (`glpi_users`.`language` LIKE ?))) OR (`glpi_users`.`language` IS NULL))",
            'expected_values'    => ['en\_GB'], //FIXME?
        ];

        // Check `>`, `>=`, `<` and `<=` operators on textual fields.
        // Operator has no meaning and is considered as a term to search for.
        foreach (['>', '>=', '<', '<='] as $operator) {
            foreach (['', ' '] as $spacing) {
                $searched_value = "{$operator}{$spacing}15";

                // datatype=dropdown
                yield [
                    'itemtype'          => Computer::class,
                    'search_option'     => 4, // type
                    'value'             => $searched_value,
                    'expected_and'      => "(`glpi_computertypes`.`name` LIKE ?)",
                    'expected_and_not'  => "(NOT (`glpi_computertypes`.`name` LIKE ?))) OR (`glpi_computertypes`.`name` IS NULL))",
                    'expected_values'   => [Computer::class, Computer::class, '%' . $searched_value . '%'],
                ];

                // datatype=dropdown (usehaving=true)
                yield [
                    'itemtype'          => Ticket::class,
                    'search_option'     => 142, // document name
                    'value'             => $searched_value,
                    'expected_and'      => "(`ITEM_Ticket_142` LIKE ?)",
                    'expected_and_not'  => "(NOT (`ITEM_Ticket_142` LIKE ?))) OR (`ITEM_Ticket_142` IS NULL))",
                    'expected_values'   => ['1', Ticket::class, '%' . $searched_value . '%'],
                ];

                // datatype=itemlink
                yield [
                    'itemtype'          => Computer::class,
                    'search_option'     => 1, // name
                    'value'             => $searched_value,
                    'expected_and'      => "(`glpi_computers`.`name` LIKE ?)",
                    'expected_and_not'  => "(NOT (`glpi_computers`.`name` LIKE ?))) OR (`glpi_computers`.`name` IS NULL))",
                    'expected_values'   => [Computer::class, Computer::class, '%' . $searched_value . '%'],
                ];

                // datatype=itemlink (usehaving=true)
                yield [
                    'itemtype'          => Ticket::class,
                    'search_option'     => 50, // parent tickets
                    'value'             => $searched_value,
                    'expected_and'      => "(`ITEM_Ticket_50` LIKE ?)",
                    'expected_and_not'  => "(NOT (`ITEM_Ticket_50` LIKE ?))) OR (`ITEM_Ticket_50` IS NULL))",
                    'expected_values'   => ['1', '3', '%' . $searched_value . '%'],
                ];

                // datatype=string
                yield [
                    'itemtype'          => Computer::class,
                    'search_option'     => 47, // uuid
                    'value'             => $searched_value,
                    'expected_and'      => "(`glpi_computers`.`uuid` LIKE ?)",
                    'expected_and_not'  => "(NOT (`glpi_computers`.`uuid` LIKE ?))) OR (`glpi_computers`.`uuid` IS NULL))",
                    'expected_values'   => [Computer::class, Computer::class, '%' . $searched_value . '%'],
                ];

                // datatype=text
                yield [
                    'itemtype'          => Computer::class,
                    'search_option'     => 16, // comment
                    'value'             => $searched_value,
                    'expected_and'      => "(`glpi_computers`.`comment` LIKE ?)",
                    'expected_and_not'  => "(NOT (`glpi_computers`.`comment` LIKE ?))) OR (`glpi_computers`.`comment` IS NULL))",
                    'expected_values'   => [Computer::class, Computer::class, '%' . $searched_value . '%'],
                ];

                // datatype=email
                yield [
                    'itemtype'          => \Contact::class,
                    'search_option'     => 6, // email
                    'value'             => $searched_value,
                    'expected_and'      => "(`glpi_contacts`.`email` LIKE ?)",
                    'expected_and_not'  => "(NOT (`glpi_contacts`.`email` LIKE ?))) OR (`glpi_contacts`.`email` IS NULL))",
                    'expected_values'   => ['%' . $searched_value . '%'],
                ];

                // datatype=weblink
                yield [
                    'itemtype'          => Document::class,
                    'search_option'     => 4, // link
                    'value'             => $searched_value,
                    'expected_and'      => "(`glpi_documents`.`link` LIKE ?)",
                    'expected_and_not'  => "(NOT (`glpi_documents`.`link` LIKE ?))) OR (`glpi_documents`.`link` IS NULL))",
                    'expected_values'   => ['%' . $searched_value . '%'],
                ];

                // datatype=mac
                yield [
                    'itemtype'          => \DeviceNetworkCard::class,
                    'search_option'     => 11, // mac_default
                    'value'             => $searched_value,
                    'expected_and'      => "(`glpi_devicenetworkcards`.`mac_default` LIKE ?)",
                    'expected_and_not'  => "(NOT (`glpi_devicenetworkcards`.`mac_default` LIKE ?))) OR (`glpi_devicenetworkcards`.`mac_default` IS NULL))",
                    'expected_values'   => ['%' . $searched_value . '%'],
                ];

                // datatype=color
                yield [
                    'itemtype'          => \Cable::class,
                    'search_option'     => 15, // color
                    'value'             => $searched_value,
                    'expected_and'      => "false", // invalid pattern
                    'expected_and_not'  => "false", // invalid pattern
                    'expected_values'   => [],
                ];

                // datatype=language
                yield [
                    'itemtype'          => User::class,
                    'search_option'     => 17, // language
                    'value'             => $searched_value,
                    'expected_and'      => "(`glpi_users`.`language` LIKE ?)",
                    'expected_and_not'  => "(NOT (`glpi_users`.`language` LIKE ?))) OR (`glpi_users`.`language` IS NULL))",
                    'expected_values'   => ['%' . $searched_value . '%'],
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
                        'expected_and'      => "`glpi_authldaps`.`port` {$operator} ?",
                        'expected_and_not'  => "`glpi_authldaps`.`port` {$not_operator} ?",
                        'expected_values'   => [$signed_value],
                    ];

                    // datatype=number
                    yield [
                        'itemtype'          => \AuthLDAP::class,
                        'search_option'     => 32, // timeout
                        'value'             => $searched_value,
                        'expected_and'      => "`glpi_authldaps`.`timeout` {$operator} ?",
                        'expected_and_not'  => "`glpi_authldaps`.`timeout` {$not_operator} ?",
                        'expected_values'   => [$signed_value],
                    ];

                    // datatype=number (usehaving=true)
                    yield [
                        'itemtype'          => Computer::class,
                        'search_option'     => 115, // harddrive capacity
                        'value'             => $searched_value,
                        'expected_and'      => "`ITEM_Computer_115` {$operator} ?",
                        'expected_and_not'  => "`ITEM_Computer_115` {$not_operator} ?",
                        'expected_values'   => [Computer::class, Computer::class, Computer::class, (float) $signed_value],
                    ];

                    // datatype=decimal
                    yield [
                        'itemtype'          => \Budget::class,
                        'search_option'     => 7, // value
                        'value'             => $searched_value,
                        'expected_and'      => "`glpi_budgets`.`value` {$operator} ?",
                        'expected_and_not'  => "`glpi_budgets`.`value` {$not_operator} ?",
                        'expected_values'   => [$signed_value],
                    ];

                    // datatype=decimal (usehaving=true)
                    yield [
                        'itemtype'          => \Contract::class,
                        'search_option'     => 11, // totalcost
                        'value'             => $searched_value,
                        'expected_and'      => "`ITEM_Contract_11` {$operator} ?",
                        'expected_and_not'  => "`ITEM_Contract_11` {$not_operator} ?",
                        'expected_values'   => [(float) $signed_value],
                    ];

                    // datatype=count (usehaving=true)
                    yield [
                        'itemtype'          => Ticket::class,
                        'search_option'     => 27, // number of followups
                        'value'             => $searched_value,
                        'expected_and'      => "`ITEM_Ticket_27` {$operator} ?",
                        'expected_and_not'  => "`ITEM_Ticket_27` {$not_operator} ?",
                        'expected_values'   => ['1', Ticket::class, (float) $signed_value],
                    ];

                    // datatype=mio (usehaving=true)
                    yield [
                        'itemtype'          => Computer::class,
                        'search_option'     => 111, // memory size
                        'value'             => $searched_value,
                        'expected_and'      => "`ITEM_Computer_111` {$operator} ?",
                        'expected_and_not'  => "`ITEM_Computer_111` {$not_operator} ?",
                        'expected_values'   => [Computer::class, Computer::class, Computer::class, (float) $signed_value],
                    ];

                    // datatype=progressbar (with computation)
                    // progressbar: the value is inlined (not bound) to avoid a lexicographic
                    // comparison with the zero-padded LPAD() output, hence no bound value.
                    $signed_value_unquoted = trim($signed_value, "'");
                    yield [
                        'itemtype'          => Computer::class,
                        'search_option'     => 152, // harddrive freepercent
                        'value'             => $searched_value,
                        'expected_and'      => "LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.`totalsize`, 0), 0), 3, '0') {$operator} {$signed_value_unquoted}",
                        'expected_and_not'  => "LPAD(ROUND(100*`glpi_items_disks`.freesize/NULLIF(`glpi_items_disks`.`totalsize`, 0), 0), 3, '0') {$not_operator} {$signed_value_unquoted}",
                        'expected_values'   => [],
                    ];

                    // datatype=timestamp
                    yield [
                        'itemtype'          => \CronTask::class,
                        'search_option'     => 6, // frequency
                        'value'             => $searched_value,
                        'expected_and'      => "`glpi_crontasks`.`frequency` {$operator} ?",
                        'expected_and_not'  => "`glpi_crontasks`.`frequency` {$not_operator} ?",
                        'expected_values'   => [$signed_value],
                    ];

                    // datatype=timestamp (usehaving=true)
                    yield [
                        'itemtype'          => Ticket::class,
                        'search_option'     => 49, // actiontime
                        'value'             => $searched_value,
                        'expected_and'      => "`ITEM_Ticket_49` {$operator} ?",
                        'expected_and_not'  => "`ITEM_Ticket_49` {$not_operator} ?",
                        'expected_values'   => ['1', (float) $signed_value],
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
                        'itemtype'          => Computer::class,
                        'search_option'     => 9, // last_inventory_update
                        'value'             => $searched_value,
                        'expected_and'      => "CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) {$operator} DATE_ADD(NOW(), INTERVAL {$signed_value} MONTH)",
                        'expected_and_not'  => "CONVERT(`glpi_computers`.`last_inventory_update` USING utf8mb4) {$not_operator} DATE_ADD(NOW(), INTERVAL {$signed_value} MONTH)",
                        'expected_values'   => [Computer::class, Computer::class],
                    ];

                    // datatype=datetime computed field
                    yield [
                        'itemtype'          => Ticket::class,
                        'search_option'     => 188, // next_escalation_level
                        'value'             => $searched_value,
                        'expected_and'      => "(`ITEM_Ticket_188` LIKE ?)",
                        'expected_and_not'  => "(NOT (`ITEM_Ticket_188` LIKE ?))) OR (`ITEM_Ticket_188` IS NULL))",
                        'expected_values'   => [0, 0, '1', '%' . trim($searched_value) . '%'],
                    ];

                    // datatype=date
                    yield [
                        'itemtype'          => \Budget::class,
                        'search_option'     => 5, // begin_date
                        'value'             => $searched_value,
                        'expected_and'      => "CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) {$operator} DATE_ADD(NOW(), INTERVAL {$signed_value} MONTH)",
                        'expected_and_not'  => "CONVERT(`glpi_budgets`.`begin_date` USING utf8mb4) {$not_operator} DATE_ADD(NOW(), INTERVAL {$signed_value} MONTH)",
                    ];

                    // datatype=date_delay
                    yield [
                        'itemtype'          => \Contract::class,
                        'search_option'     => 20, // end_date
                        'value'             => $searched_value,
                        'expected_and'      => "DATE_ADD(`glpi_contracts`.`begin_date`, INTERVAL `glpi_contracts`.`duration` MONTH) {$operator} DATE_ADD(NOW(), INTERVAL {$signed_value} MONTH)",
                        'expected_and_not'  => "DATE_ADD(`glpi_contracts`.`begin_date`, INTERVAL `glpi_contracts`.`duration` MONTH) {$not_operator} DATE_ADD(NOW(), INTERVAL {$signed_value} MONTH)",
                    ];
                }
            }
        }
    }

    public function testContainsCriterion(): void
    {
        $provider = $this->containsCriterionProvider();
        $count = 0; //counter for data provider
        foreach ($provider as $row) {
            $itemtype = $row['itemtype'];
            $search_option = $row['search_option'];
            $value = $row['value'];
            $expected_and = $row['expected_and'];
            $expected_and_not = $row['expected_and_not'];
            $expected_values = $row['expected_values'] ?? [];

            $cases = [
                'AND' => $expected_and,
                'AND NOT' => $expected_and_not,
            ];

            foreach ($cases as $link => $expected_where) {
                $search_params = [
                    'is_deleted' => 0,
                    'start' => 0,
                    'criteria' => [
                        0 => [
                            'link' => $link,
                            'field' => $search_option,
                            'searchtype' => 'contains',
                            'value' => $value,
                        ],
                    ],
                ];

                $provider_information = sprintf(
                    'Error on dataProvider #%s - %s:%s: %s',
                    $count,
                    $itemtype,
                    $search_option,
                    $value
                );

                try {
                    $data = $this->doSearch($itemtype, $search_params);

                    $this->assertArrayHasKey('sql', $data);
                    $this->assertArrayHasKey('search', $data['sql']);
                    $this->assertInstanceOf(Select::class, $data['sql']['search']);

                    $this->assertStringContainsString(
                        $expected_where,
                        $this->cleanSQL($data['sql']['search']->getQuery()),
                        $provider_information
                    );
                    foreach ($expected_values as $expected_value) {
                        $this->assertContains(
                            $expected_value,
                            $data['sql']['search']->getParams(),
                            $provider_information
                        );
                    }
                    ++$count;
                } catch (\Throwable $e) {
                    echo $provider_information . "\n";
                    throw $e;
                }
            }
        }
    }

    protected function customAssetsProvider(): iterable
    {
        $root_entity_id = getItemByTypeName('Entity', '_test_root_entity', true);

        $document_1 = $this->createTxtDocument();
        $document_2 = $this->createTxtDocument();
        $document_3 = $this->createTxtDocument();

        $definition_1  = $this->initAssetDefinition(capacities: [new Capacity(name: HasDocumentsCapacity::class)]);
        $asset_class_1 = $definition_1->getAssetClassName();

        $definition_2  = $this->initAssetDefinition(capacities: [new Capacity(name: HasDocumentsCapacity::class)]);
        $asset_class_2 = $definition_2->getAssetClassName();

        // Assets for first class
        $asset_1_1 = $this->createItem(
            $asset_class_1,
            [
                'name'        => 'Asset 1.1',
                'entities_id' => $root_entity_id,
            ]
        );
        $asset_1_2 = $this->createItem(
            $asset_class_1,
            [
                'name'        => 'Asset 1.2',
                'entities_id' => $root_entity_id,
            ]
        );
        $asset_1_3 = $this->createItem(
            $asset_class_1,
            [
                'name'        => 'Asset 1.3 (deleted)',
                'entities_id' => $root_entity_id,
                'is_deleted'  => true,
            ]
        );

        // Assets for second class
        $asset_2_1 = $this->createItem(
            $asset_class_2,
            [
                'name'        => 'Asset 2.1 (deleted)',
                'entities_id' => $root_entity_id,
                'is_deleted'  => true,
            ]
        );
        $asset_2_2 = $this->createItem(
            $asset_class_2,
            [
                'name'        => 'Asset 2.2',
                'entities_id' => $root_entity_id,
            ]
        );
        $asset_2_3 = $this->createItem(
            $asset_class_2,
            [
                'name'        => 'Asset 2.3 (deleted)',
                'entities_id' => $root_entity_id,
                'is_deleted'  => true,
            ]
        );

        // Attached documents
        $this->createItems(
            Document_Item::class,
            [
                [
                    'documents_id' => $document_1->getID(),
                    'itemtype'     => $asset_1_1->getType(),
                    'items_id'     => $asset_1_1->getID(),
                ],
                [
                    'documents_id' => $document_1->getID(),
                    'itemtype'     => $asset_1_2->getType(),
                    'items_id'     => $asset_1_2->getID(),
                ],
                [
                    'documents_id' => $document_2->getID(),
                    'itemtype'     => $asset_1_2->getType(),
                    'items_id'     => $asset_1_2->getID(),
                ],
                [
                    'documents_id' => $document_1->getID(),
                    'itemtype'     => $asset_2_1->getType(),
                    'items_id'     => $asset_2_1->getID(),
                ],
            ]
        );

        // Check search on custom assets.
        // Validates that searching on assets of class A will not return some assets of class B in results.
        $asset_search_params = [
            'criteria' => [
                [
                    'field'      => 'view',
                    'searchtype' => 'contains',
                    'value'      => 'Asset',
                ],
            ],
        ];

        yield [
            'class'    => $asset_class_1,
            'params'   => $asset_search_params + ['is_deleted' => 0],
            'expected' => [$asset_1_1, $asset_1_2],
        ];
        yield [
            'class'    => $asset_class_1,
            'params'   => $asset_search_params + ['is_deleted' => 1],
            'expected' => [$asset_1_3],
        ];
        yield [
            'class'    => $asset_class_2,
            'params'   => $asset_search_params + ['is_deleted' => 0],
            'expected' => [$asset_2_2],
        ];
        yield [
            'class'    => $asset_class_2,
            'params'   => $asset_search_params + ['is_deleted' => 1],
            'expected' => [$asset_2_1, $asset_2_3],
        ];

        // Check search on documents using a custom assets as meta criteria.
        yield [
            'class'    => Document::class,
            'params'   => [
                'criteria' => [
                    [
                        'field'      => 'view',
                        'searchtype' => 'contains',
                        'value'      => '',
                    ],
                    [
                        'link'       => 'AND',
                        'itemtype'   => $asset_class_1,
                        'meta'       => true,
                        'field'      => '1', // name
                        'searchtype' => 'contains',
                        'value'      => 'Asset',
                    ],
                ],
            ],
            'expected' => [$document_1, $document_2],
        ];
        yield [
            'class'    => Document::class,
            'params'   => [
                'criteria' => [
                    [
                        'field'      => 'view',
                        'searchtype' => 'contains',
                        'value'      => '',
                    ],
                    [
                        'link'       => 'AND NOT',
                        'itemtype'   => $asset_class_1,
                        'meta'       => true,
                        'field'      => '1', // name
                        'searchtype' => 'contains',
                        'value'      => 'Asset',
                    ],
                ],
            ],
            'expected' => [$document_3],
        ];
        yield [
            'class'    => Document::class,
            'params'   => [
                'criteria' => [
                    [
                        'field'      => 'view',
                        'searchtype' => 'contains',
                        'value'      => '',
                    ],
                    [
                        'link'       => 'AND',
                        'itemtype'   => $asset_class_1,
                        'meta'       => true,
                        'field'      => '1', // name
                        'searchtype' => 'contains',
                        'value'      => 'Asset',
                    ],
                    [
                        'link'       => 'OR',
                        'itemtype'   => $asset_class_2,
                        'meta'       => true,
                        'field'      => '1', // name
                        'searchtype' => 'contains',
                        'value'      => 'Asset',
                    ],
                ],
            ],
            'expected' => [$document_1, $document_2],
        ];
        yield [
            'class'    => Document::class,
            'params'   => [
                'criteria' => [
                    [
                        'field'      => 'view',
                        'searchtype' => 'contains',
                        'value'      => '',
                    ],
                    [
                        'link'       => 'AND',
                        'itemtype'   => $asset_class_1,
                        'meta'       => true,
                        'field'      => '1', // name
                        'searchtype' => 'contains',
                        'value'      => 'Asset',
                    ],
                    [
                        'link'       => 'AND',
                        'itemtype'   => $asset_class_2,
                        'meta'       => true,
                        'field'      => '1', // name
                        'searchtype' => 'contains',
                        'value'      => 'Asset',
                    ],
                ],
            ],
            'expected' => [$document_1],
        ];
    }

    public function testCustomAssetSearch(): void
    {
        $provider = $this->customAssetsProvider();
        foreach ($provider as $row) {
            $class = $row['class'];
            $params = $row['params'];
            $expected = $row['expected'];

            $data = $this->doSearch($class, $params);
            foreach ($expected as $key => $item) {
                $this->assertEquals(
                    $item->fields['name'],
                    $data['data']['rows'][$key]['raw'][sprintf('ITEM_%s_1', $class)]
                );
                $this->assertEquals($item->getID(), $data['data']['rows'][$key]['raw']['id']);
            }
            $this->assertEquals(count($expected), $data['data']['totalcount']);
        }
    }

    public function testDCRoomSearchOption()
    {
        global $CFG_GLPI;
        foreach ($CFG_GLPI['rackable_types'] as $rackable_type) {
            $item = new $rackable_type();
            $so = $item->rawSearchOptions();
            //check if search option separator 'dcroom' exist
            $this->assertNotEquals(
                false,
                array_search('dcroom', array_column($so, 'id')),
                $item->getTypeName() . ' should use \'$tab = array_merge($tab, DCRoom::rawSearchOptionsToAdd());'
            );
        }
    }

    public function testDataCenterSearchOption()
    {
        global $CFG_GLPI;
        foreach ($CFG_GLPI['rackable_types'] as $rackable_type) {
            $item = new $rackable_type();
            $so = $item->rawSearchOptions();
            //check if search option separator 'datacenter' exist
            $this->assertNotEquals(
                false,
                array_search('datacenter', array_column($so, 'id')),
                $item->getTypeName() . ' should use \'$tab = array_merge($tab, DataCenter::rawSearchOptionsToAdd());'
            );
        }
    }

    protected function testRichTextProvider(): iterable
    {
        $this->login('glpi', 'glpi');

        $this->createItems(Ticket::class, [
            [
                'name' => 'Ticket 1',
                'content' => '<p>This is a test ticket</p>',
            ],
            [
                'name' => 'Ticket 2',
                'content' => '<p>This is a test ticket with &amp; in description</p>',
            ],
            [
                'name' => 'Ticket 3',
                'content' => '<p>This is a test ticket with matching followup</p>',
            ],
            [
                'name' => 'Ticket 4',
                'content' => '<p>This is a test ticket with task</p>',
            ],
            [
                'name' => 'Ticket & 5',
                'content' => '<p>This is a test ticket</p>',
            ],
            [
                'name' => 'Ticket > 6',
                'content' => '<p>This is a test ticket</p>',
            ],
        ]);

        $this->createItem('ITILFollowup', [
            'itemtype' => Ticket::class,
            'items_id' => getItemByTypeName(Ticket::class, 'Ticket 1')->getID(),
            'content' => '<p>This is a followup</p>',
        ]);
        $this->createItem('ITILFollowup', [
            'itemtype' => Ticket::class,
            'items_id' => getItemByTypeName(Ticket::class, 'Ticket 3')->getID(),
            'content' => '<p>This is a followup with &amp; in description</p>',
        ]);

        $this->createItem('TicketTask', [
            'tickets_id' => getItemByTypeName(Ticket::class, 'Ticket 1')->getID(),
            'content' => '<p>This is a task</p>',
        ]);
        $this->createItem('TicketTask', [
            'tickets_id' => getItemByTypeName(Ticket::class, 'Ticket 4')->getID(),
            'content' => '<p>This is a task with &amp; in description</p>',
        ]);

        yield [
            'search_params' => [
                'is_deleted' => 0,
                'start'      => 0,
                'criteria'   => [
                    0 => [
                        'link'       => 'AND',
                        'field'      => 1, // title
                        'searchtype' => 'contains',
                        'value'      => '&',
                    ],
                ],
            ],
            'expected' => [
                'Ticket & 5',
            ],
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
                        'value'      => '&',
                    ],
                ],
            ],
            'expected' => [
                'Ticket 2',
            ],
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
                        'value'      => '&',
                    ],
                ],
            ],
            'expected' => [
                'Ticket 3',
            ],
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
                        'value'      => '&',
                    ],
                ],
            ],
            'expected' => [
                'Ticket 4',
            ],
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
                        'value'      => '&',
                    ],
                    1 => [
                        'link' => 'AND',
                        'field' => 1, //title
                        'searchtype' => 'contains',
                        'value' => '',
                    ],
                    2 => [
                        'link' => 'AND',
                        'field' => 21, // ticket content
                        'searchtype' => 'contains',
                        'value' => '',
                    ],
                    3 => [
                        'link' => 'AND',
                        'field' => 25, // followup content
                        'searchtype' => 'contains',
                        'value' => '',
                    ],
                    4 => [
                        'link' => 'AND',
                        'field' => 26, // task content
                        'searchtype' => 'contains',
                        'value' => '',
                    ],
                ],
            ],
            'expected' => [
                'Ticket 2',
                'Ticket 3',
                'Ticket 4',
                'Ticket & 5',
            ],
        ];
    }

    public function testRichText(): void
    {
        $provider = $this->testRichTextProvider();
        foreach ($provider as $row) {
            $search_params = $row['search_params'];
            $expected = $row['expected'];

            $data = $this->doSearch(Ticket::class, $search_params);

            // Extract items names
            $items = [];
            foreach ($data['data']['rows'] as $row) {
                $items[] = $row['raw']['ITEM_Ticket_1'];
            }

            $this->assertEquals($expected, $items);
        }
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
                    'value' => 'any string',
                ],
            ],
        ];

        $displaypref = new \DisplayPreference();
        $input = [
            'itemtype'  => Ticket::class,
            'users_id'  => Session::getLoginUserID(),
            'num'       => 55, //Ticket glpi_ticketvalidations.status
        ];
        $this->assertGreaterThan(
            0,
            $displaypref->add($input)
        );

        $data = $this->doSearch(Ticket::class, $search_params);

        $this->assertStringNotContainsString(
            "`glpi_ticketvalidations`.`status` IN",
            $data['sql']['search']->getQuery()
        );

        $search_params['criteria'][0]['value'] = 1;
        $data = $this->doSearch(Ticket::class, $search_params);
        $this->assertStringContainsString(
            "`glpi_ticketvalidations`.`status` IN",
            $data['sql']['search']->getQuery()
        );

        $search_params['criteria'][0]['value'] = 'all';
        $data = $this->doSearch(Ticket::class, $search_params);
        $this->assertStringNotContainsString(
            "`glpi_ticketvalidations`.`status` IN",
            $data['sql']['search']->getQuery()
        );

        $search_params['criteria'][0]['value'] = 'can';
        $data = $this->doSearch(Ticket::class, $search_params);
        $this->assertStringContainsString(
            "`glpi_ticketvalidations`.`status` IN",
            $data['sql']['search']->getQuery()
        );
    }

    public function testCommonITILSatisfactionEndDate()
    {
        $this->login('glpi', 'glpi');

        $entity_root_id = getItemByTypeName('Entity', '_test_root_entity', true);
        $entity_child_1_id = getItemByTypeName('Entity', '_test_child_1', true);
        $entity_child_2_id = getItemByTypeName('Entity', '_test_child_2', true);
        $user_id = $_SESSION['glpiID'];

        $this->updateItem(Entity::class, $entity_root_id, [
            'inquest_config'   => 1,
            'inquest_duration' => 0,
        ]);
        $this->updateItem(Entity::class, $entity_child_1_id, [
            'inquest_config'   => 1,
            'inquest_duration' => 2,
        ]);
        $this->updateItem(Entity::class, $entity_child_2_id, [
            'inquest_config'   => 1,
            'inquest_duration' => 4,
        ]);
        // Create sub entity for child 2
        $entity_child_2_1_id = $this->createItem(Entity::class, [
            'name'         => '_test_child_2_1',
            'entities_id'  => $entity_child_2_id,
        ])->getID();
        // Create sub sub entity for child 2
        $entity_child_2_1_1_id = $this->createItem(Entity::class, [
            'name'         => '_test_child_2_1_1',
            'entities_id'  => $entity_child_2_1_id,
        ])->getID();

        // Create closed tickets
        $tickets = $this->createItems(Ticket::class, [
            [
                'entities_id' => $entity_root_id,
                'name' => __FUNCTION__ . ' 1',
                'content' => __FUNCTION__ . ' 1 content',
                'solvedate' => $_SESSION['glpi_currenttime'],
                'status' => \CommonITILObject::CLOSED,
                'users_id_recipient' => $user_id,
            ],
            [
                'entities_id' => $entity_child_1_id,
                'name' => __FUNCTION__ . ' 2',
                'content' => __FUNCTION__ . ' 2 content',
                'solvedate' => $_SESSION['glpi_currenttime'],
                'status' => \CommonITILObject::CLOSED,
                'users_id_recipient' => $user_id,
            ],
            [
                'entities_id' => $entity_child_2_id,
                'name' => __FUNCTION__ . ' 3',
                'content' => __FUNCTION__ . ' 3 content',
                'solvedate' => $_SESSION['glpi_currenttime'],
                'status' => \CommonITILObject::CLOSED,
                'users_id_recipient' => $user_id,
            ],
            [
                'entities_id' => $entity_child_2_1_id,
                'name' => __FUNCTION__ . ' 4',
                'content' => __FUNCTION__ . ' 4 content',
                'solvedate' => $_SESSION['glpi_currenttime'],
                'status' => \CommonITILObject::CLOSED,
                'users_id_recipient' => $user_id,
            ],
            [
                'entities_id' => $entity_child_2_1_1_id,
                'name' => __FUNCTION__ . ' 5',
                'content' => __FUNCTION__ . ' 5 content',
                'solvedate' => $_SESSION['glpi_currenttime'],
                'status' => \CommonITILObject::CLOSED,
                'users_id_recipient' => $user_id,
            ],
        ]);

        // Add satisfactions
        $this->createItems(\TicketSatisfaction::class, [
            [
                'tickets_id' => $tickets[0]->getID(),
                'type' => \CommonITILSatisfaction::TYPE_INTERNAL,
                'date_begin' => $_SESSION['glpi_currenttime'],
            ],
            [
                'tickets_id' => $tickets[1]->getID(),
                'type' => \CommonITILSatisfaction::TYPE_INTERNAL,
                'date_begin' => $_SESSION['glpi_currenttime'],
            ],
            [
                'tickets_id' => $tickets[2]->getID(),
                'type' => \CommonITILSatisfaction::TYPE_INTERNAL,
                'date_begin' => $_SESSION['glpi_currenttime'],
            ],
            [
                'tickets_id' => $tickets[3]->getID(),
                'type' => \CommonITILSatisfaction::TYPE_INTERNAL,
                'date_begin' => $_SESSION['glpi_currenttime'],
            ],
            [
                'tickets_id' => $tickets[4]->getID(),
                'type' => \CommonITILSatisfaction::TYPE_INTERNAL,
                'date_begin' => $_SESSION['glpi_currenttime'],
            ],
        ]);

        $search_params = [
            'is_deleted' => 0,
            'start' => 0,
            'criteria' => [
                [
                    'field' => 1, // name
                    'searchtype' => 'contains',
                    'value' => __FUNCTION__,
                ],
                [
                    'field' => 75, // satisfaction end date
                    'searchtype' => 'contains',
                    'value' => '',
                ],
            ],
            'order' => 1,
        ];

        $data = $this->doSearch(Ticket::class, $search_params);

        $items = [];
        foreach ($data['data']['rows'] as $row) {
            $items[] = [
                $row['raw']['ITEM_Ticket_2'],
                $row['raw']['ITEM_Ticket_75'],
            ];
        }
        $expected = [
            [
                $tickets[0]->getID(),
                '',
            ],
            [
                $tickets[1]->getID(),
                date('Y-m-d H:i:s', strtotime('+2 days', strtotime($_SESSION['glpi_currenttime']))),
            ],
            [
                $tickets[2]->getID(),
                date('Y-m-d H:i:s', strtotime('+4 days', strtotime($_SESSION['glpi_currenttime']))),
            ],
            [
                $tickets[3]->getID(),
                date('Y-m-d H:i:s', strtotime('+4 days', strtotime($_SESSION['glpi_currenttime']))),
            ],
            [
                $tickets[4]->getID(),
                date('Y-m-d H:i:s', strtotime('+4 days', strtotime($_SESSION['glpi_currenttime']))),
            ],
        ];
        $this->assertEquals($expected, $items);
    }

    public function testSatisfactionSurveySearch()
    {
        $this->login('glpi', 'glpi');

        $entity_id = getItemByTypeName('Entity', '_test_root_entity', true);
        $user_id = $_SESSION['glpiID'];

        // Configure entity for satisfaction survey
        $this->updateItem(Entity::class, $entity_id, [
            'inquest_config'   => 1,
            'inquest_duration' => 5,
        ]);

        // Create a closed ticket
        $ticket = $this->createItem(Ticket::class, [
            'entities_id' => $entity_id,
            'name' => __FUNCTION__ . ' ticket',
            'content' => __FUNCTION__ . ' ticket content',
            'solvedate' => $_SESSION['glpi_currenttime'],
            'status' => \CommonITILObject::CLOSED,
            'users_id_recipient' => $user_id,
        ]);

        // Add satisfaction survey (not answered)
        $this->createItem(\TicketSatisfaction::class, [
            'tickets_id' => $ticket->getID(),
            'type' => \CommonITILSatisfaction::TYPE_INTERNAL,
            'date_begin' => $_SESSION['glpi_currenttime'],
            'date_answered' => null,
        ]);

        // This search simulates clicking on "Satisfaction surveys to answer" in personal view
        // It should not throw SQL error about unknown column 'glpi_entities.id'
        $search_params = [
            'is_deleted' => 0,
            'start' => 0,
            'criteria' => [
                [
                    'field' => 12, // status
                    'searchtype' => 'equals',
                    'value' => \CommonITILObject::CLOSED,
                ],
                [
                    'field' => 60, // satisfaction date_begin (not null)
                    'searchtype' => 'contains',
                    'value' => '^.+$', // not empty regex
                ],
                [
                    'field' => 61, // satisfaction date_answered (null)
                    'searchtype' => 'contains',
                    'value' => 'NULL',
                ],
                [
                    'field' => 75, // satisfaction end date - this was causing the SQL error
                    'searchtype' => 'morethan',
                    'value' => $_SESSION['glpi_currenttime'],
                ],
            ],
        ];

        // This should not throw any SQL error
        $data = $this->doSearch(Ticket::class, $search_params);

        // Verify the query executed successfully
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('rows', $data['data']);
    }

    public function testInvalidCriteria()
    {
        $search_params = [
            'is_deleted'   => 0,
            'criteria'     => [
                [
                    'field' => 10000, // Not a valid search option
                    'searchtype' => 'contains',
                    'value' => 'any string',
                ],
            ],
        ];

        $data = $this->doSearch(Computer::class, $search_params);
        $this->assertGreaterThan(8, $data['data']['totalcount']);
        $this->hasSessionMessages(WARNING, ['Some search criteria were removed because they are invalid']);

        $search_params = [
            'is_deleted'   => 0,
            'criteria'     => [
                [
                    'field' => 10000, // Not a valid search option
                    'searchtype' => 'contains',
                    'value' => 'any string',
                ],
                [
                    'field' => 1, // name
                    'searchtype' => 'contains',
                    'value' => '_test_pc_with_encoded_comment',
                ],
            ],
        ];

        $data = $this->doSearch(Computer::class, $search_params);
        // Only the valid 'name' criterion should be taken into account
        $this->assertEquals(1, $data['data']['totalcount']);
        $this->hasSessionMessages(WARNING, ['Some search criteria were removed because they are invalid']);
    }

    public function testInvalidMetacriteria()
    {
        $search_params = [
            'is_deleted'   => 0,
            'criteria'     => [
                [
                    'itemtype' => 'Agent',
                    'field' => 10000, // Not a valid search option
                    'searchtype' => 'contains',
                    'value' => 'any string',
                ],
            ],
        ];

        $data = $this->doSearch(Computer::class, $search_params);
        $this->assertGreaterThan(8, $data['data']['totalcount']);
        $this->hasSessionMessages(WARNING, ['Some search criteria were removed because they are invalid']);
    }

    public function testInvalidSort()
    {
        $search_params = [
            'is_deleted'   => 0,
            'sort'         => [10000], // Not a valid search option
            'order' => ['ASC'],
        ];

        $data = $this->doSearch(Computer::class, $search_params);
        $this->assertEquals([0], $data['search']['sort']);
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
    private static function provideCriteriaWithSubqueries_getAllCombination(
        string $itemtype,
        array $base_condition,
        array $all,
        array $expected,
        int $field,
        string $searchtype,
        mixed $value,
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
                ],
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
                ],
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
                        ],
                    ],
                ],
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
                        ],
                    ],
                ],
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
                        ],
                    ],
                ],
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
                        ],
                    ],
                ],
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
                ],
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
                ],
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
                        ],
                    ],
                ],
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
                        ],
                    ],
                ],
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
                        ],
                    ],
                ],
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
                        ],
                    ],
                ],
            ],
            'expected' => $not_expected,
        ];
    }

    public static function provideCriteriaWithSubqueries(): iterable
    {
        $category   = fn() => getItemByTypeName('ITILCategory', 'Test Criteria With Subqueries', true);
        $group_1    = fn() => getItemByTypeName('Group', 'Group 1', true);
        $supplier_1 = fn() => getItemByTypeName('Supplier', 'Supplier 1', true);
        $user_1     = fn() => getItemByTypeName('User', TU_USER, true);

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
            'itemtype' => Ticket::class,
            'criteria' => [$base_condition],
            'expected' => $all_tickets,
        ];

        // Run tests for watcher group
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (W)', 'Ticket group 1 (W) + group 2 (W)'],
            65, // Watcher group
            'equals',
            $group_1
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (W)', 'Ticket group 1 (W) + group 2 (W)', 'Ticket group 1A (W) + group 2 (W)'],
            65, // Watcher group
            'contains',
            "group 1"
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (W)', 'Ticket group 1 (W) + group 2 (W)', 'Ticket group 1A (W) + group 2 (W)'],
            65, // Watcher group
            'under',
            $group_1
        );

        // Run test for assigned groups
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (A)'],
            8, // Assigned group
            'equals',
            $group_1
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (A)'],
            8, // Assigned group
            'contains',
            "group 1"
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (A)'],
            8, // Assigned group
            'under',
            $group_1
        );

        // Run test for requester group
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (R)'],
            71, // Requester group
            'equals',
            $group_1
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (R)'],
            71, // Requester group
            'contains',
            "group 1"
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (R)'],
            71, // Requester group
            'under',
            $group_1
        );

        // Run tests for 'mygroup'
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (R)'],
            71, // Requester group
            'equals',
            'mygroups'
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (A)'],
            8, // Assigned group
            'equals',
            'mygroups'
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (W)', 'Ticket group 1 (W) + group 2 (W)'],
            65, // Watcher group
            'equals',
            'mygroups'
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (R)'],
            71, // Requester group
            'under',
            'mygroups'
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (A)'],
            8, // Assigned group
            'under',
            'mygroups'
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket group 1 (W)', 'Ticket group 1 (W) + group 2 (W)', 'Ticket group 1A (W) + group 2 (W)'],
            65, // Watcher group
            'under',
            'mygroups'
        );

        // Run tests for suppliers
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket supplier 1', 'Ticket supplier 1 + supplier 2'],
            6, // Supplier
            'equals',
            $supplier_1
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket supplier 1', 'Ticket supplier 1 + supplier 2'],
            6, // Supplier
            'contains',
            "Supplier 1"
        );

        // Test empty group search
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            // Every ticket without a watcher group
            array_diff($all_tickets, ['Ticket group 1 (W)', 'Ticket group 2 (W)', 'Ticket group 1 (W) + group 2 (W)', 'Ticket group 1A (W) + group 2 (W)']),
            65, // Watcher group
            'equals',
            0
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
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
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket user 1 (R)', 'Ticket user 1 (R) + user 2 (R)'],
            4, // Requester
            'equals',
            $user_1
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket user 1 (R)', 'Ticket user 1 (R) + user 2 (R)'],
            4, // Requester
            'contains',
            TU_USER
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket user 1 (R)', 'Ticket user 1 (R) + user 2 (R)'],
            4, // Requester
            'contains',
            "Firstname"
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket user 1 (R)', 'Ticket user 1 (R) + user 2 (R)'],
            4, // Requester
            'contains',
            "Lastname"
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket user 1 (R)', 'Ticket user 1 (R) + user 2 (R)'],
            4, // Requester
            'contains',
            "Lastname Firstname"
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket anonymous user (R)'],
            4, // Requester
            'contains',
            "myemail@email.com"
        );

        // Run tests for watcher
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket user 1 (W)'],
            66, // Watcher
            'equals',
            $user_1
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket user 1 (W)'],
            66, // Watcher
            'contains',
            TU_USER
        );

        // Run tests for requester
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket user 1 (A)'],
            5, // Assign
            'equals',
            $user_1
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket user 1 (A)'],
            5, // Assign
            'contains',
            TU_USER
        );

        // Run test for "myself" special criteria
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            ['Ticket user 1 (R)', 'Ticket user 1 (R) + user 2 (R)'],
            4, // Requester
            'equals',
            'myself'
        );

        // Test empty requester search
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
            $base_condition,
            $all_tickets,
            // Every ticket without a requester group
            array_diff($all_tickets, ['Ticket user 1 (R)', 'Ticket user 2 (R)', 'Ticket user 1 (R) + user 2 (R)', 'Ticket anonymous user (R)']),
            4, // Requester
            'equals',
            0
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Ticket::class,
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
        $all_users = ['user_without_groups', 'user_group_1', 'user_group_1_and_2'];
        $base_condition = [
            'link'       => 'AND',
            'field'      => 1, // Name
            'searchtype' => 'contains',
            'value'      => "user_",
        ];

        // Search users by groups
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            'User',
            $base_condition,
            $all_users,
            ['user_group_1', 'user_group_1_and_2'],
            13, // Groups
            'equals',
            $group_1
        );
        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            'User',
            $base_condition,
            $all_users,
            ['user_group_1', 'user_group_1_and_2'],
            13, // Groups
            'contains',
            "Group 1"
        );

        $all_computers = ['computer_without_appliance', 'computer_appliance_1', 'computer_appliance_1_and_2'];
        $base_condition = [
            'link'       => 'AND',
            'field'      => 1, // Name
            'searchtype' => 'contains',
            'value'      => "computer_",
        ];
        $appliance1 = static fn() => getItemByTypeName('Appliance', 'appliance1', true);

        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Computer::class,
            $base_condition,
            $all_computers,
            ['computer_appliance_1', 'computer_appliance_1_and_2'],
            1210,
            'equals',
            $appliance1,
        );

        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Computer::class,
            $base_condition,
            $all_computers,
            ['computer_appliance_1_and_2'],
            1210,
            'contains',
            'appliance2',
        );

        yield from self::provideCriteriaWithSubqueries_getAllCombination(
            Computer::class,
            $base_condition,
            $all_computers,
            ['computer_appliance_1', 'computer_appliance_1_and_2'],
            1210,
            'contains',
            'appliance',
        );
    }

    private function provideCriteriaWithSubqueries_Dataset(): void
    {
        $this->login();
        $root = getItemByTypeName('Entity', '_test_root_entity', true);

        // All our test set will be assigned to this category
        $category = $this->createItem('ITILCategory', [
            'name' => 'Test Criteria With Subqueries',
            'entities_id' => $root,
        ])->getId();

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

        $this->createItems(Ticket::class, [
            // Test set on watcher group
            [
                'name' => 'Ticket group 1 (W)',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'observer' => [['itemtype' => 'Group', 'items_id' => $group_1]],
                ],
            ],
            [
                'name' => 'Ticket group 2 (W)',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'observer' => [['itemtype' => 'Group', 'items_id' => $group_2]],
                ],
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
                    ],
                ],
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
                    ],
                ],
            ],

            // Test set on assigned group
            [
                'name' => 'Ticket group 1 (A)',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'assign' => [['itemtype' => 'Group', 'items_id' => $group_1]],
                ],
            ],

            // Test set on requester group
            [
                'name' => 'Ticket group 1 (R)',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'requester' => [['itemtype' => 'Group', 'items_id' => $group_1]],
                ],
            ],

            // Test set on supplier
            [
                'name' => 'Ticket supplier 1',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'assign' => [['itemtype' => 'Supplier', 'items_id' => $supplier_1]],
                ],
            ],
            [
                'name' => 'Ticket supplier 2',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'assign' => [['itemtype' => 'Supplier', 'items_id' => $supplier_2]],
                ],
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
                ],
            ],

            // Test set on requester
            [
                'name' => 'Ticket user 1 (R)',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'requester' => [['itemtype' => 'User', 'items_id' => $user_1]],
                ],
            ],
            [
                'name' => 'Ticket user 2 (R)',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'requester' => [['itemtype' => 'User', 'items_id' => $user_2]],
                ],
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
                ],
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
                            'use_notification' => true,
                        ],
                    ],
                ],
            ],

            // Test set on watcher
            [
                'name' => 'Ticket user 1 (W)',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'observer' => [['itemtype' => 'User', 'items_id' => $user_1]],
                ],
            ],

            // Test set on assigned
            [
                'name' => 'Ticket user 1 (A)',
                'content' => '',
                'entities_id' => $root,
                'itilcategories_id' => $category,
                '_actors' => [
                    'assign' => [['itemtype' => 'User', 'items_id' => $user_1]],
                ],
            ],
        ]);

        [
            $user_without_groups,
            $user_group_1,
            $user_group_1_and_2
        ] = $this->createItems(User::class, [
            ['name' => 'user_without_groups'],
            ['name' => 'user_group_1'],
            ['name' => 'user_group_1_and_2'],
        ]);
        $this->createItems(Group_User::class, [
            ['users_id' => $user_group_1->getID(), 'groups_id' => $group_1],
            ['users_id' => $user_group_1_and_2->getID(), 'groups_id' => $group_1],
            ['users_id' => $user_group_1_and_2->getID(), 'groups_id' => $group_2],
        ]);

        [$computer_without_appliance, $computer_appliance_1, $computer_appliance_1_and_2] = $this->createItems(Computer::class, [
            ['name' => 'computer_without_appliance', 'entities_id' => $root],
            ['name' => 'computer_appliance_1', 'entities_id' => $root],
            ['name' => 'computer_appliance_1_and_2', 'entities_id' => $root],
        ]);

        [$appliance1, $appliance2] = $this->createItems(Appliance::class, [
            ['name' => 'appliance1', 'entities_id' => $root],
            ['name' => 'appliance2', 'entities_id' => $root],
        ]);

        $this->createItems(Appliance_Item::class, [
            ['items_id' => $computer_appliance_1->getID(), 'appliances_id' => $appliance1->getID(), 'itemtype' => Computer::class],
            ['items_id' => $computer_appliance_1_and_2->getID(), 'appliances_id' => $appliance1->getID(), 'itemtype' => Computer::class],
            ['items_id' => $computer_appliance_1_and_2->getID(), 'appliances_id' => $appliance2->getID(), 'itemtype' => Computer::class],
        ]);
    }

    #[DataProvider('provideCriteriaWithSubqueries')]
    public function testCriteriaWithSubqueries(
        string $itemtype,
        array $criteria,
        array $expected
    ): void {
        // Init datas
        $this->provideCriteriaWithSubqueries_Dataset();

        // Process closure values
        array_walk_recursive($criteria, function (&$value) {
            if (is_callable($value)) {
                $value = $value();
            }
        });

        // Run search
        $data = \Search::getDatas($itemtype, [
            'criteria' => $criteria,
        ]);

        // Parse results
        $names = [];
        foreach ($data['data']['rows'] as $row) {
            // Some names may be force-grouped so the actual name is in the first part before the SHORTSEP
            $n = $row['raw']["ITEM_{$itemtype}_1"];
            $names[] = explode('$#$', $n)[0];
        }

        // Sort both array for comparison
        sort($names);
        sort($expected);

        // Validate results
        $this->assertEquals(
            $expected,
            $names,
            "Search query failed. Raw SQL WHERE clause: " . ($data['sql']['raw']['WHERE'] ?? 'N/A')
        );
    }

    /**
     * Regression test to validate that external users requesters email are correctly rendered.
     *
     * @see https://github.com/glpi-project/glpi/pull/21348
     */
    public function testExternalRequesterColumnRenderingInSearchResults(): void
    {
        $this->login();

        $this->createItem(
            Ticket::class,
            [
                'name'          => __FUNCTION__,
                'content'       => __FUNCTION__,
                'entities_id'   => $this->getTestRootEntity(true),
                '_actors'       => [
                    'requester' => [
                        [
                            'itemtype'          => User::class,
                            'items_id'          => 0,
                            'use_notification'  => 1,
                            'alternative_email' => 'external-requester1@example.com',
                        ],
                        [
                            'itemtype'          => User::class,
                            'items_id'          => 0,
                            'use_notification'  => 1,
                            'alternative_email' => 'external-requester2@example.com',
                        ],
                    ],
                ],
            ]
        );

        $result = \Search::getDatas(
            Ticket::class,
            [
                'criteria' => [
                    [
                        'field'      => '1',
                        'searchtype' => 'contains',
                        'value'      => __FUNCTION__,
                    ],
                ],
            ]
        );

        $this->assertTrue(isset($result['data']['rows'][0]['Ticket_4']['displayname']));
        $this->assertEquals(
            '<a href=\'mailto:external-requester1@example.com\'>external-requester1@example.com</a>'
                . '#LBBR##LBBR#'
                . '<a href=\'mailto:external-requester2@example.com\'>external-requester2@example.com</a>',
            $result['data']['rows'][0]['Ticket_4']['displayname']
        );
    }

    /**
     * Validate that dropdown complete names are correctly rendered.
     */
    public function testCompletenameColumnRenderingInSearchResults(): void
    {
        $this->login();

        $cat_1_id    = \getItemByTypeName(TaskCategory::class, '_cat_1', true);
        $subcat_1_id = \getItemByTypeName(TaskCategory::class, '_subcat_1', true);
        $rnd_cat_id  = \getItemByTypeName(TaskCategory::class, 'R&D', true);

        $new_subcat = $this->createItem(
            TaskCategory::class,
            [
                'name'              => 'subcat with <&> chars',
                'taskcategories_id' => $cat_1_id,
                'entities_id'       => $this->getTestRootEntity(true),
            ]
        );

        $result = \Search::getDatas(
            TaskCategory::class,
            [
                'criteria' => [
                    [
                        'field'      => '1',
                        'searchtype' => 'contains',
                        'value'      => '_cat_1',
                    ],
                ],
            ]
        );

        $expected = [
            "<a id='TaskCategory_{$cat_1_id}_{$cat_1_id}' href='/front/taskcategory.form.php?id={$cat_1_id}'><span >_cat_1</span></a>",
            "<a id='TaskCategory_{$subcat_1_id}_{$subcat_1_id}' href='/front/taskcategory.form.php?id={$subcat_1_id}'><span class=\"text-muted\">_cat_1</span> &gt; <span >_subcat_1</span></a>",
            "<a id='TaskCategory_{$rnd_cat_id}_{$rnd_cat_id}' href='/front/taskcategory.form.php?id={$rnd_cat_id}'><span class=\"text-muted\">_cat_1</span> &gt; <span >R&amp;D</span></a>",
            "<a id='TaskCategory_{$new_subcat->getID()}_{$new_subcat->getID()}' href='/front/taskcategory.form.php?id={$new_subcat->getID()}'><span class=\"text-muted\">_cat_1</span> &gt; <span >subcat with &lt;&amp;&gt; chars</span></a>",
        ];

        foreach ($expected as $key => $displayname) {
            $this->assertTrue(isset($result['data']['rows'][$key]['TaskCategory_1']['displayname']));
            $this->assertEquals($displayname, $result['data']['rows'][$key]['TaskCategory_1']['displayname']);
        }
    }

    /**
     * Validate that dropdown complete names are correctly translated.
     */
    public function testCompletenameColumnTranslationsInSearchResults(): void
    {
        $this->login();

        $cat_1_id    = \getItemByTypeName(TaskCategory::class, '_cat_1', true);
        $subcat_1_id = \getItemByTypeName(TaskCategory::class, '_subcat_1', true);
        $rnd_cat_id  = \getItemByTypeName(TaskCategory::class, 'R&D', true);

        $_SESSION['glpilanguage'] = 'fr_FR';
        $_SESSION['glpi_dropdowntranslations'] = DropdownTranslation::getAvailableTranslations('fr_FR');

        $result = \Search::getDatas(
            TaskCategory::class,
            [
                'criteria' => [
                    [
                        'field'      => '1',
                        'searchtype' => 'contains',
                        'value'      => '_cat_1',
                    ],
                ],
            ]
        );

        $expected = [
            "<a id='TaskCategory_{$cat_1_id}_{$cat_1_id}' href='/front/taskcategory.form.php?id={$cat_1_id}'><span >FR - _cat_1</span></a>",
            "<a id='TaskCategory_{$subcat_1_id}_{$subcat_1_id}' href='/front/taskcategory.form.php?id={$subcat_1_id}'><span class=\"text-muted\">FR - _cat_1</span> &gt; <span >FR - _subcat_1</span></a>",
            "<a id='TaskCategory_{$rnd_cat_id}_{$rnd_cat_id}' href='/front/taskcategory.form.php?id={$rnd_cat_id}'><span class=\"text-muted\">FR - _cat_1</span> &gt; <span >R&amp;D</span></a>",
        ];

        foreach ($expected as $key => $displayname) {
            $this->assertTrue(isset($result['data']['rows'][$key]['TaskCategory_1']['displayname']));
            $this->assertEquals($displayname, $result['data']['rows'][$key]['TaskCategory_1']['displayname']);
        }
    }

    /**
     * Validate that `use_flat_dropdowntree_on_search_result` is correctly applied for `completename+itemlink`.
     */
    public function testForeignItemlinkCompletenameColumnRenderingInSearchResults(): void
    {
        $this->login();

        $user_id    = \getItemByTypeName(User::class, 'glpi', true);
        $group_1_id = \getItemByTypeName(Group::class, '_test_group_1', true);
        $group_2_id = \getItemByTypeName(Group::class, '_test_group_2', true);

        $this->createItems(
            Group_User::class,
            [
                [
                    'users_id'  => $user_id,
                    'groups_id' => $group_1_id,
                ],
                [
                    'users_id'  => $user_id,
                    'groups_id' => $group_2_id,
                ],
            ]
        );

        // Test with `use_flat_dropdowntree_on_search_result=1`
        $_SESSION['glpiuse_flat_dropdowntree_on_search_result'] = 1;
        $result = \Search::getDatas(
            User::class,
            [
                'criteria' => [
                    [
                        'field'      => '1',
                        'searchtype' => 'contains',
                        'value'      => 'glpi',
                    ],
                ],
                'forcetoview' => [13],
            ]
        );

        $this->assertTrue(isset($result['data']['rows'][0]['User_13']['displayname']));
        $this->assertEquals(
            "<a id='Group_{$user_id}_{$group_1_id}' href='/front/group.form.php?id={$group_1_id}'><span >_test_group_1</span></a>"
                . "#LBBR#"
                . "<a id='Group_{$user_id}_{$group_2_id}' href='/front/group.form.php?id={$group_2_id}'><span class=\"text-muted\">_test_group_1</span> &gt; <span >_test_group_2</span></a>",
            $result['data']['rows'][0]['User_13']['displayname']
        );

        // Test with `use_flat_dropdowntree_on_search_result=0`
        $_SESSION['glpiuse_flat_dropdowntree_on_search_result'] = 0;
        $result = \Search::getDatas(
            User::class,
            [
                'criteria' => [
                    [
                        'field'      => '1',
                        'searchtype' => 'contains',
                        'value'      => 'glpi',
                    ],
                ],
                'forcetoview' => [13],
            ]
        );

        $this->assertTrue(isset($result['data']['rows'][0]['User_13']['displayname']));
        $this->assertEquals(
            "<a id='Group_{$user_id}_{$group_1_id}' href='/front/group.form.php?id={$group_1_id}'><span >_test_group_1</span></a>"
                . "#LBBR#"
                . "<a id='Group_{$user_id}_{$group_2_id}' href='/front/group.form.php?id={$group_2_id}'><span >_test_group_2</span></a>",
            $result['data']['rows'][0]['User_13']['displayname']
        );
    }

    /**
     * Validate that `use_flat_dropdowntree_on_search_result` is correctly applied for `completename+dropdown` SO with unique value.
     */
    public function testForeignDropdownCompletenameColumnRenderingInSearchResults(): void
    {
        $this->login();

        $this->createItem(
            Computer::class,
            [
                'name'         => __FUNCTION__,
                'locations_id' => \getItemByTypeName(Location::class, '_location02 > _sublocation04', true),
                'entities_id'  => $this->getTestRootEntity(true),
            ]
        );

        // Test with `use_flat_dropdowntree_on_search_result=1`
        $_SESSION['glpiuse_flat_dropdowntree_on_search_result'] = 1;
        $result = \Search::getDatas(
            Computer::class,
            [
                'criteria' => [
                    [
                        'field'      => '1',
                        'searchtype' => 'contains',
                        'value'      => __FUNCTION__,
                    ],
                ],
            ]
        );

        $this->assertTrue(isset($result['data']['rows'][0]['Computer_3']['displayname']));
        $this->assertEquals('_location02 &gt; _sublocation04', $result['data']['rows'][0]['Computer_3']['displayname']);

        // Test with `use_flat_dropdowntree_on_search_result=0`
        $_SESSION['glpiuse_flat_dropdowntree_on_search_result'] = 0;
        $result = \Search::getDatas(
            Computer::class,
            [
                'criteria' => [
                    [
                        'field'      => '1',
                        'searchtype' => 'contains',
                        'value'      => __FUNCTION__,
                    ],
                ],
            ]
        );

        $this->assertTrue(isset($result['data']['rows'][0]['Computer_3']['displayname']));
        $this->assertEquals('_sublocation04', $result['data']['rows'][0]['Computer_3']['displayname']);
    }

    /**
     * Validate that `use_flat_dropdowntree_on_search_result` is correctly applied for `completename+dropdown` SO with multiple values.
     */
    public function testMultipleForeignDropdownCompletenameColumnRenderingInSearchResults(): void
    {
        $this->login();

        $computer = $this->createItem(
            Computer::class,
            [
                'name'        => __FUNCTION__,
                'entities_id' => $this->getTestRootEntity(true),
            ]
        );
        $this->createItems(
            Group_Item::class,
            [
                [
                    'groups_id' => \getItemByTypeName(Group::class, '_test_group_1', true),
                    'itemtype'  => Computer::class,
                    'items_id'  => $computer->getID(),
                    'type'      => Group_Item::GROUP_TYPE_NORMAL,
                ],
                [
                    'groups_id' => \getItemByTypeName(Group::class, '_test_group_2', true),
                    'itemtype'  => Computer::class,
                    'items_id'  => $computer->getID(),
                    'type'      => Group_Item::GROUP_TYPE_NORMAL,
                ],
            ]
        );

        // Test with `use_flat_dropdowntree_on_search_result=1`
        $_SESSION['glpiuse_flat_dropdowntree_on_search_result'] = 1;
        $result = \Search::getDatas(
            Computer::class,
            [
                'criteria' => [
                    [
                        'field'      => '1',
                        'searchtype' => 'equals',
                        'value'      => $computer->getID(),
                    ],
                ],
                'forcetoview' => [71],
            ]
        );

        $this->assertTrue(isset($result['data']['rows'][0]['Computer_71']['displayname']));
        $this->assertEquals(
            '_test_group_1#LBBR#_test_group_1 &gt; _test_group_2',
            $result['data']['rows'][0]['Computer_71']['displayname']
        );

        // Test with `use_flat_dropdowntree_on_search_result=0`
        $_SESSION['glpiuse_flat_dropdowntree_on_search_result'] = 0;
        $result = \Search::getDatas(
            Computer::class,
            [
                'criteria' => [
                    [
                        'field'      => '1',
                        'searchtype' => 'equals',
                        'value'      => $computer->getID(),
                    ],
                ],
                'forcetoview' => [71],
            ]
        );

        $this->assertTrue(isset($result['data']['rows'][0]['Computer_71']['displayname']));
        $this->assertEquals(
            '_test_group_1#LBBR#_test_group_2',
            $result['data']['rows'][0]['Computer_71']['displayname']
        );
    }

    /**
     * Validate that the `notcontains` search on "user" itemlink column does not fail.
     */
    public function testUsernameNotContainsSearch(): void
    {
        $this->login();

        $result = \Search::getDatas(
            Computer::class,
            [
                'criteria' => [
                    [
                        'field'      => '24', // users_id_tech
                        'searchtype' => 'notcontains',
                        'value'      => 'whatever',
                    ],
                ],
            ]
        );

        // we just check that the search did not failed with an exception
        $this->assertTrue(isset($result['data']['totalcount']));
    }

    public function testMetaTicketForm()
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        // Create a form
        $form = $this->createItem(Form::class, [
            'name'        => '_test_form_for_meta_search',
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'is_active'   => true,
        ]);

        // Create a ticket
        $ticket = $this->createItem(Ticket::class, [
            'name'        => '_test_ticket_for_meta_search',
            'content'     => 'test',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);

        // Create an AnswersSet linked to the form
        $answers_set = $this->createItem(AnswersSet::class, [
            'forms_forms_id' => $form->getID(),
            'entities_id'    => $this->getTestRootEntity(only_id: true),
            'name'           => '_test_answerset_for_meta_search',
            'answers'        => '{}',
        ]);

        // Link the ticket to the form via AnswersSet_FormDestinationItem
        $this->createItem(AnswersSet_FormDestinationItem::class, [
            'forms_answerssets_id' => $answers_set->getID(),
            'itemtype'             => Ticket::class,
            'items_id'             => $ticket->getID(),
        ]);

        // Search tickets with meta-criteria on Form ID
        $search_params = [
            'is_deleted' => 0,
            'start'      => 0,
            'criteria'   => [
                0 => [
                    'field'      => '12',
                    'searchtype' => 'equals',
                    'value'      => 'all',
                    'link'       => 'AND',
                ],
            ],
            'metacriteria' => [
                0 => [
                    'link'       => 'AND',
                    'itemtype'   => Form::class,
                    'field'      => 2, // ID
                    'searchtype' => 'equals',
                    'value'      => $form->getID(),
                ],
            ],
        ];

        $data = $this->doSearch('Ticket', $search_params);

        // Validate generated SQL contains the expected JOINs
        $this->assertMatchesRegularExpression(
            '/LEFT\s*JOIN.*glpi_forms_destinations_answerssets_formdestinationitems/im',
            $data['sql']['search']
        );
        $this->assertMatchesRegularExpression(
            '/LEFT\s*JOIN.*glpi_forms_answerssets/im',
            $data['sql']['search']
        );
        $this->assertMatchesRegularExpression(
            '/LEFT\s*JOIN.*glpi_forms_forms/im',
            $data['sql']['search']
        );

        // Validate the search found the linked ticket
        $this->assertSame(1, $data['data']['totalcount']);

        // Search with a non-existing form ID should return no result
        $search_params['metacriteria'][0]['value'] = 99999999;
        $data = $this->doSearch('Ticket', $search_params);
        $this->assertSame(0, $data['data']['totalcount']);
    }

    public function testMetaFormTicket()
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        // Create a form
        $form = $this->createItem(Form::class, [
            'name'        => '_test_form_for_meta_search_reverse',
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'is_active'   => true,
        ]);

        // Create a ticket
        $ticket = $this->createItem(Ticket::class, [
            'name'        => '_test_ticket_for_meta_search_reverse',
            'content'     => 'test',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);

        // Create an AnswersSet linked to the form
        $answers_set = $this->createItem(AnswersSet::class, [
            'forms_forms_id' => $form->getID(),
            'entities_id'    => $this->getTestRootEntity(only_id: true),
            'name'           => '_test_answerset_for_meta_search_reverse',
            'answers'        => '{}',
        ]);

        // Link the ticket to the form via AnswersSet_FormDestinationItem
        $this->createItem(AnswersSet_FormDestinationItem::class, [
            'forms_answerssets_id' => $answers_set->getID(),
            'itemtype'             => Ticket::class,
            'items_id'             => $ticket->getID(),
        ]);

        // Search forms with meta-criteria on Ticket ID
        $search_params = [
            'is_deleted' => 0,
            'start'      => 0,
            'criteria'   => [
                0 => [
                    'field'      => 'view',
                    'searchtype' => 'contains',
                    'value'      => '',
                ],
            ],
            'metacriteria' => [
                0 => [
                    'link'       => 'AND',
                    'itemtype'   => Ticket::class,
                    'field'      => 2, // ID
                    'searchtype' => 'equals',
                    'value'      => $ticket->getID(),
                ],
            ],
        ];

        $data = $this->doSearch(Form::class, $search_params);

        // Validate generated SQL contains the expected JOINs
        $this->assertMatchesRegularExpression(
            '/LEFT\s*JOIN.*glpi_forms_answerssets/im',
            $data['sql']['search']
        );
        $this->assertMatchesRegularExpression(
            '/LEFT\s*JOIN.*glpi_forms_destinations_answerssets_formdestinationitems/im',
            $data['sql']['search']
        );

        // Validate the search found the linked form
        $this->assertSame(1, $data['data']['totalcount']);

        // Search with a non-existing ticket ID should return no result
        $search_params['metacriteria'][0]['value'] = 99999999;
        $data = $this->doSearch(Form::class, $search_params);
        $this->assertSame(0, $data['data']['totalcount']);
    }

    /**
     * Regression test: numeric operators (< > <= >=) on progressbar fields (e.g. "Free percentage")
     * must produce a numeric SQL comparison, not a lexicographic one.
     *
     * Without the fix, LPAD() returns a zero-padded string such as '067'.
     * Comparing '067' < '20' lexicographically is TRUE (because '0' < '2'), so every
     * computer would wrongly match a "< 20" filter regardless of its actual free space.
     */
    public function testProgressbarNumericOperatorSearch(): void
    {
        $this->login();

        $unique     = uniqid('freepct-', true);

        $entity_id  = $this->getTestRootEntity(true);

        $computer_low = $this->createItem(Computer::class, [
            'name'        => $unique . '-low',
            'entities_id' => $entity_id,
        ]);
        $computer_high = $this->createItem(Computer::class, [
            'name'        => $unique . '-high',
            'entities_id' => $entity_id,
        ]);

        // 10% free space  (freesize / totalsize = 10/100)
        $this->createItem(\Item_Disk::class, [
            'itemtype'   => Computer::class,
            'items_id'   => $computer_low->getID(),
            'name'       => 'disk-low',
            'mountpoint' => '/',
            'totalsize'  => 100,
            'freesize'   => 10,
        ]);

        // 67% free space  (freesize / totalsize = 67/100)
        $this->createItem(\Item_Disk::class, [
            'itemtype'   => Computer::class,
            'items_id'   => $computer_high->getID(),
            'name'       => 'disk-high',
            'mountpoint' => '/',
            'totalsize'  => 100,
            'freesize'   => 67,
        ]);

        $search_field = 152; // Volumes - Free percentage

        // "contains < 20": only the 10%-free computer must match (name filter isolates our fixtures)
        $data = $this->doSearch(Computer::class, [
            'is_deleted' => 0,
            'start'      => 0,
            'criteria'   => [
                [
                    'field'      => 1, // name
                    'searchtype' => 'contains',
                    'value'      => $unique,
                ],
                [
                    'link'       => 'AND',
                    'field'      => $search_field,
                    'searchtype' => 'contains',
                    'value'      => '< 20',
                ],
            ],
        ]);

        $ids_found = array_column(array_column($data['data']['rows'], 'raw'), 'id');
        $this->assertContains($computer_low->getID(), $ids_found, 'Computer with 10% free space should match "< 20"');
        $this->assertNotContains($computer_high->getID(), $ids_found, 'Computer with 67% free space must NOT match "< 20"');

        // "contains >= 67": only the 67%-free computer must match
        $data = $this->doSearch(Computer::class, [
            'is_deleted' => 0,
            'start'      => 0,
            'criteria'   => [
                [
                    'field'      => 1, // name
                    'searchtype' => 'contains',
                    'value'      => $unique,
                ],
                [
                    'link'       => 'AND',
                    'field'      => $search_field,
                    'searchtype' => 'contains',
                    'value'      => '>= 67',
                ],
            ],
        ]);

        $ids_found = array_column(array_column($data['data']['rows'], 'raw'), 'id');
        $this->assertContains($computer_high->getID(), $ids_found, 'Computer with 67% free space should match ">= 67"');
        $this->assertNotContains($computer_low->getID(), $ids_found, 'Computer with 10% free space must NOT match ">= 67"');
    }

    public function testNumericMorethanLessthanSearch(): void
    {
        $this->login();

        $unique    = uniqid('numsearch-', true);
        $entity_id = $this->getTestRootEntity(true);

        $computer_low = $this->createItem(Computer::class, [
            'name'        => $unique . '-low',
            'entities_id' => $entity_id,
        ]);
        $computer_high = $this->createItem(Computer::class, [
            'name'        => $unique . '-high',
            'entities_id' => $entity_id,
        ]);

        $low_id  = $computer_low->getID();
        $high_id = $computer_high->getID();
        $this->assertLessThan($high_id, $low_id);

        $base_criteria = [
            [
                'field'      => 1, // name
                'searchtype' => 'contains',
                'value'      => $unique,
            ],
        ];

        // morethan + 0 must not be treated as an equality search
        $data = $this->doSearch(Computer::class, [
            'is_deleted' => 0,
            'start'      => 0,
            'criteria'   => array_merge($base_criteria, [[
                'link'       => 'AND',
                'field'      => 2, // ID
                'searchtype' => 'morethan',
                'value'      => 0,
            ]]),
        ]);
        $ids_found = array_column(array_column($data['data']['rows'], 'raw'), 'id');
        $this->assertContains($low_id, $ids_found);
        $this->assertContains($high_id, $ids_found);

        // lessthan + high_id must exclude the highest matching ID
        $data = $this->doSearch(Computer::class, [
            'is_deleted' => 0,
            'start'      => 0,
            'criteria'   => array_merge($base_criteria, [[
                'link'       => 'AND',
                'field'      => 2,
                'searchtype' => 'lessthan',
                'value'      => $high_id,
            ]]),
        ]);
        $ids_found = array_column(array_column($data['data']['rows'], 'raw'), 'id');
        $this->assertContains($low_id, $ids_found);
        $this->assertNotContains($high_id, $ids_found);

        // morethanorequal + low_id must include the boundary value
        $data = $this->doSearch(Computer::class, [
            'is_deleted' => 0,
            'start'      => 0,
            'criteria'   => array_merge($base_criteria, [[
                'link'       => 'AND',
                'field'      => 2,
                'searchtype' => 'morethanorequal',
                'value'      => $low_id,
            ]]),
        ]);
        $ids_found = array_column(array_column($data['data']['rows'], 'raw'), 'id');
        $this->assertContains($low_id, $ids_found);
        $this->assertContains($high_id, $ids_found);

        // lessthanorequal + high_id must include the boundary value
        $data = $this->doSearch(Computer::class, [
            'is_deleted' => 0,
            'start'      => 0,
            'criteria'   => array_merge($base_criteria, [[
                'link'       => 'AND',
                'field'      => 2,
                'searchtype' => 'lessthanorequal',
                'value'      => $high_id,
            ]]),
        ]);
        $ids_found = array_column(array_column($data['data']['rows'], 'raw'), 'id');
        $this->assertContains($low_id, $ids_found);
        $this->assertContains($high_id, $ids_found);

        // NOT morethan must invert to <=
        $data = $this->doSearch(Computer::class, [
            'is_deleted' => 0,
            'start'      => 0,
            'criteria'   => array_merge($base_criteria, [[
                'link'       => 'AND NOT',
                'field'      => 2,
                'searchtype' => 'morethan',
                'value'      => $low_id,
            ]]),
        ]);
        $ids_found = array_column(array_column($data['data']['rows'], 'raw'), 'id');
        $this->assertContains($low_id, $ids_found);
        $this->assertNotContains($high_id, $ids_found);

        // NOT morethanorequal must invert to <
        $data = $this->doSearch(Computer::class, [
            'is_deleted' => 0,
            'start'      => 0,
            'criteria'   => array_merge($base_criteria, [[
                'link'       => 'AND NOT',
                'field'      => 2,
                'searchtype' => 'morethanorequal',
                'value'      => $low_id,
            ]]),
        ]);
        $ids_found = array_column(array_column($data['data']['rows'], 'raw'), 'id');
        $this->assertNotContains($low_id, $ids_found);
        $this->assertNotContains($high_id, $ids_found);

        // NOT lessthanorequal must invert to >
        $data = $this->doSearch(Computer::class, [
            'is_deleted' => 0,
            'start'      => 0,
            'criteria'   => array_merge($base_criteria, [[
                'link'       => 'AND NOT',
                'field'      => 2,
                'searchtype' => 'lessthanorequal',
                'value'      => $low_id,
            ]]),
        ]);
        $ids_found = array_column(array_column($data['data']['rows'], 'raw'), 'id');
        $this->assertNotContains($low_id, $ids_found);
        $this->assertContains($high_id, $ids_found);

        // Regression: contains + >0 syntax must still work
        $data = $this->doSearch(Computer::class, [
            'is_deleted' => 0,
            'start'      => 0,
            'criteria'   => array_merge($base_criteria, [[
                'link'       => 'AND',
                'field'      => 2,
                'searchtype' => 'contains',
                'value'      => '>0',
            ]]),
        ]);
        $ids_found = array_column(array_column($data['data']['rows'], 'raw'), 'id');
        $this->assertContains($low_id, $ids_found);
        $this->assertContains($high_id, $ids_found);
    }

    public function testCertificateRawSearchOptionsInheritance(): void
    {
        $item = new \Certificate();
        $so = $item->rawSearchOptions();
        $ids = array_column($so, 'id');

        // No duplicate numeric IDs — would indicate parent options being re-added manually
        $numeric_ids = array_values(array_filter($ids, 'is_numeric'));
        $this->assertCount(count($numeric_ids), array_unique($numeric_ids), 'Certificate::rawSearchOptions() contains duplicate numeric IDs');

        // assert that inherited options are present
        $this->assertContains(1, $ids);
        $this->assertContains(86, $ids);
    }

    public function testSoftwareLicenseRawSearchOptionsInheritance(): void
    {
        $item = new \SoftwareLicense();
        $so = $item->rawSearchOptions();
        $ids = array_column($so, 'id');

        // No duplicate numeric IDs — would indicate parent options being re-added manually
        $numeric_ids = array_values(array_filter($ids, 'is_numeric'));
        $this->assertCount(count($numeric_ids), array_unique($numeric_ids), 'SoftwareLicense::rawSearchOptions() contains duplicate numeric IDs');

        // assert that inherited options are present
        $this->assertContains('1', $ids);
        $this->assertContains('2', $ids);
        $this->assertContains('13', $ids);
        $this->assertContains('14', $ids);
        $this->assertContains('19', $ids);
        $this->assertContains('16', $ids);
        $this->assertContains('121', $ids);
        $this->assertContains('80', $ids);
        $this->assertContains('86', $ids);
    }

    public function testTypeHasAssetUrlSearchOption(): void
    {
        global $CFG_GLPI;

        foreach ($CFG_GLPI["asset_types"] as $itemtype) {
            $item = new $itemtype();
            $options = $item->rawSearchOptions();

            $filtered_options = array_filter($options, function ($option) {
                return isset($option['id']) && $option['id'] === 290
                    && isset($option['field']) && $option['field'] === 'asset_url';
            });

            $this->assertEquals(
                1,
                count($filtered_options),
                "Itemtype $itemtype does not have the asset_url search option (id=290)"
            );
        }
    }

    public function testSearchByAssetUrl(): void
    {
        global $CFG_GLPI;

        $this->login();

        $computer = $this->createItem(Computer::class, [
            'name'        => '_test_computer_for_asset_url_search',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $expected_url = $CFG_GLPI['url_base'] . Computer::getFormURL(false) . '?id=' . $computer->getID();

        //search for the computer by its asset URL
        $result = \Search::getDatas(
            Computer::class,
            [
                'criteria' => [
                    [
                        'field'      => 290,
                        'searchtype' => 'contains',
                        'value'      => $expected_url,
                    ],
                ],
                'forcetoview' => [1, 290],
            ]
        );

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(1, $result['data']['totalcount']);
        $this->assertEquals($computer->getID(), $result['data']['rows'][0]['raw']['id']);

        //sreach for a non-existing asset URL
        $result = \Search::getDatas(
            Computer::class,
            [
                'criteria' => [
                    [
                        'field'      => 290,
                        'searchtype' => 'contains',
                        'value'      => Computer::getFormURL(false) . '?id=99999999',
                    ],
                ],
                'forcetoview' => [1, 290],
            ]
        );

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(0, $result['data']['totalcount'], 'Should find no computer for a non-existing asset URL');
    }

    /**
     * The deferred-join pagination optimisation must produce, for a grouped search (here a
     * Computer search displaying the 1:N "Group in charge" column, option 49), the exact same
     * total count and the same ordered page of ids as a non-paginated reference run.
     */
    public function testDeferredJoinPaginationConsistency()
    {
        $this->login();
        $entity = (int) $_SESSION['glpiactive_entity'];

        $token    = 'deferredjoin_' . mt_rand();
        $computer = new Computer();
        $created  = [];
        for ($i = 0; $i < 5; $i++) {
            $id = $computer->add([
                'name'        => sprintf('%s %02d', $token, $i),
                'entities_id' => $entity,
            ]);
            $this->assertGreaterThan(0, $id);
            $created[] = (int) $id;
        }
        sort($created);

        // option 49 ("Group in charge") is forcegroupby, so forcing its display triggers the
        // GROUP BY and therefore the deferred-join optimisation; criterion on the main-table
        // name (option 1) isolates our 5 computers.
        $base = [
            'reset'    => 'reset',
            'sort'     => [2], // id (plain main-table column)
            'order'    => ['ASC'],
            'criteria' => [
                ['field' => 1, 'searchtype' => 'contains', 'value' => $token],
            ],
        ];
        $get_ids = static fn(array $d): array => array_values(
            array_map('intval', array_column(array_column($d['data']['rows'], 'raw'), 'id'))
        );

        // Reference: one large page.
        $ref = \Search::getDatas(Computer::class, $base + ['start' => 0, 'list_limit' => 1000], [49]);
        $query = $ref['sql']['search']->getQuery();
        $this->assertStringContainsString('deferred_page', $query, 'deferred-join optimisation should be active');
        $this->assertSame(
            substr_count($query, '?'),
            count($ref['sql']['search']->getParams()),
            'placeholder/param count mismatch: ' . $query
        );
        $this->assertSame(5, $ref['data']['totalcount']);
        $this->assertSame($created, $get_ids($ref));

        // Paginated: 2 + 2 + 1 must equal the reference slices, with a stable total count.
        $page1 = \Search::getDatas(Computer::class, $base + ['start' => 0, 'list_limit' => 2], [49]);
        $page2 = \Search::getDatas(Computer::class, $base + ['start' => 2, 'list_limit' => 2], [49]);
        $page3 = \Search::getDatas(Computer::class, $base + ['start' => 4, 'list_limit' => 2], [49]);

        foreach ([$page1, $page2, $page3] as $page) {
            $this->assertSame(5, $page['data']['totalcount']);
        }
        $this->assertSame(array_slice($created, 0, 2), $get_ids($page1));
        $this->assertSame(array_slice($created, 2, 2), $get_ids($page2));
        $this->assertSame(array_slice($created, 4, 1), $get_ids($page3));
    }

    /**
     * A criterion on a forcegroupby (1:N) field produces a HAVING clause: the deferred-join
     * inner subquery must then expose the aggregated SELECT aliases referenced by HAVING and
     * keep a consistent placeholder/parameter count, and the whole thing must execute.
     */
    public function testDeferredJoinSkippedForHavingAndAggregateSort()
    {
        $this->login();

        // Filtering on a HAVING (usehaving) field — Ticket option 188 "Next escalation level":
        // the aggregate must be computed over the whole relation, so deferral is skipped.
        $having = \Search::prepareDatasForSearch(Ticket::class, [
            'reset'    => 'reset',
            'sort'     => [2],
            'order'    => ['ASC'],
            'criteria' => [['field' => 188, 'searchtype' => 'contains', 'value' => '2020']],
        ]);
        \Search::constructSQL($having);
        $this->assertStringNotContainsString('deferred_page', $having['sql']['search']->getQuery());

        // Sorting on a 1:N aggregate — Software option 72 "Number of installations": same reason,
        // deferral is skipped and the regular query is emitted.
        $agg = \Search::prepareDatasForSearch(\Software::class, [
            'reset'    => 'reset',
            'sort'     => [72],
            'order'    => ['DESC'],
            'criteria' => [['field' => 'view', 'searchtype' => 'contains', 'value' => '']],
        ]);
        \Search::constructSQL($agg);
        $this->assertStringNotContainsString('deferred_page', $agg['sql']['search']->getQuery());
    }

    /**
     * The optimisation must be skipped for union itemtypes and for full exports, falling back
     * to the regular (non deferred) query.
     */
    public function testDeferredJoinSkippedForUnionAndExport()
    {
        $this->login();

        // Union itemtype: never deferred.
        $union = \Search::getDatas(\AllAssets::class, [
            'reset'      => 'reset',
            'start'      => 0,
            'list_limit' => 10,
            'criteria'   => [['field' => 'view', 'searchtype' => 'contains', 'value' => '']],
        ]);
        $this->assertStringNotContainsString('deferred_page', $union['sql']['search']->getQuery());

        // Full export: LIMIT is cleared, so no SQL pagination / deferral.
        $data = \Search::prepareDatasForSearch(Computer::class, [
            'reset'    => 'reset',
            'criteria' => [['field' => 1, 'searchtype' => 'contains', 'value' => '']],
        ]);
        $data['search']['export_all'] = true;
        \Search::constructSQL($data);
        $this->assertStringNotContainsString('deferred_page', $data['sql']['search']->getQuery());
        $this->assertFalse($data['sql']['has_sql_limit']);
    }

    /**
     * Sorting on a computed-ORDER column must not be deferred. Contract "End date" (option 20,
     * a `date_delay` whose `field` is the non-existent column `end_date`) previously reached the
     * deferred inner subquery, which emitted `ORDER BY glpi_contracts.end_date` and crashed with
     * "Unknown column". The optimisation must now be skipped for such fields: the regular query
     * (ordering by DATE_ADD()) is emitted and returns correctly ordered rows.
     */
    public function testDeferredJoinSkippedForComputedOrderColumn()
    {
        $this->login();
        $entity = (int) $_SESSION['glpiactive_entity'];

        $token    = 'deferredorder_' . mt_rand();
        $contract = new \Contract();
        // Same begin_date, different duration => distinct computed end dates.
        $c_short = (int) $contract->add([
            'name'        => $token . ' short',
            'entities_id' => $entity,
            'begin_date'  => '2020-01-01',
            'duration'    => 12, // ends 2021-01-01
        ]);
        $c_long = (int) $contract->add([
            'name'        => $token . ' long',
            'entities_id' => $entity,
            'begin_date'  => '2020-01-01',
            'duration'    => 24, // ends 2022-01-01
        ]);
        $this->assertGreaterThan(0, $c_short);
        $this->assertGreaterThan(0, $c_long);

        // Displaying option 29 (Associated supplier, a forcegroupby 1:N column) forces GROUP BY
        // and thus the deferred-join optimisation; the criterion on the main-table name isolates
        // our contracts.
        $base = [
            'reset'    => 'reset',
            'sort'     => [20], // End date (date_delay, main table)
            'criteria' => [
                ['field' => 1, 'searchtype' => 'contains', 'value' => $token],
            ],
        ];
        $get_ids = static fn(array $d): array => array_values(
            array_map('intval', array_column(array_column($d['data']['rows'], 'raw'), 'id'))
        );

        $asc = \Search::getDatas(\Contract::class, $base + ['order' => ['ASC'], 'start' => 0, 'list_limit' => 30], [29]);
        $query = $asc['sql']['search']->getQuery();
        // Computed-ORDER field => deferral skipped, regular query with the DATE_ADD expression.
        $this->assertStringNotContainsString('deferred_page', $query);
        $this->assertStringContainsStringIgnoringCase('DATE_ADD', $query);
        $this->assertSame(2, $asc['data']['totalcount']);
        $this->assertSame([$c_short, $c_long], $get_ids($asc));

        $desc = \Search::getDatas(\Contract::class, $base + ['order' => ['DESC'], 'start' => 0, 'list_limit' => 30], [29]);
        $this->assertSame([$c_long, $c_short], $get_ids($desc));
    }

    /**
     * Filtering on a forcegroupby (1:N) field puts a condition on its joined table in the WHERE,
     * so that join must be kept in the minimal FROM (its searchopt id is collected). A former
     * int-vs-string strict comparison never matched, so the join was deferred out and
     * minimalFromCoversWhere() silently disabled the optimisation. It must stay active.
     */
    public function testDeferredJoinKeepsForcegroupbyFilterJoin()
    {
        $this->login();

        // Computer option 49 = "Group in charge" (forcegroupby, no usehaving) => WHERE condition.
        $data = \Search::prepareDatasForSearch(Computer::class, [
            'reset'    => 'reset',
            'sort'     => [2], // id (plain main-table column)
            'order'    => ['ASC'],
            'criteria' => [
                ['field' => 49, 'searchtype' => 'contains', 'value' => 'whatever'],
            ],
        ]);
        \Search::constructSQL($data);
        $query = $data['sql']['search']->getQuery();

        $this->assertStringContainsString(
            'deferred_page',
            $query,
            'filtering a forcegroupby field must not disable the deferred-join optimisation'
        );
        // The filtered group join must be present (inside the inner subquery / minimal FROM).
        $this->assertStringContainsString('glpi_groups', $query);
        $this->assertSame(
            substr_count($query, '?'),
            count($data['sql']['search']->getParams()),
            'placeholder/param count mismatch: ' . $query
        );
    }

    /**
     * Requesting a page past the last one under an active SQL LIMIT (stale bookmark / edited URL
     * / API) must not error: it falls back to the first page, matching the pre-optimization
     * behaviour where the full result was fetched and the start clamped.
     */
    public function testActiveSearchOutOfRangeStart()
    {
        $this->login();
        $entity = (int) $_SESSION['glpiactive_entity'];

        $token    = 'oorstart_' . mt_rand();
        $computer = new Computer();
        $id = (int) $computer->add(['name' => $token, 'entities_id' => $entity]);
        $this->assertGreaterThan(0, $id);

        $data = \Search::getDatas(Computer::class, [
            'reset'      => 'reset',
            'sort'       => [2],
            'order'      => ['ASC'],
            'start'      => 50, // far past the single match
            'list_limit' => 20,
            'criteria'   => [
                ['field' => 1, 'searchtype' => 'contains', 'value' => $token],
            ],
        ]);

        $this->assertTrue($data['sql']['has_sql_limit']);
        $this->assertSame(1, $data['data']['totalcount']);
        $ids = array_values(array_map('intval', array_column(array_column($data['data']['rows'], 'raw'), 'id')));
        $this->assertSame([$id], $ids);
    }
}

// @codingStandardsIgnoreStart
class DupSearchOpt extends CommonDBTM
{
    // @codingStandardsIgnoreEnd
    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'     => '12',
            'name'   => 'One search option',
        ];

        $tab[] = [
            'id'     => '12',
            'name'   => 'Any option',
        ];

        return $tab;
    }
}

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
