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

namespace Glpi\Dashboard;

use CommonDBTM;
use CommonDevice;
use CommonITILActor;
use CommonITILObject;
use CommonITILValidation;
use CommonTreeDropdown;
use Config;
use DBConnection;
use ExtraVisibilityCriteria;
use Glpi\Dashboard\Filters\{
    DatesFilter,
    GroupTechFilter,
    UserTechFilter,
};
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\DBAL\QuerySubQuery;
use Glpi\Debug\Profiler;
use Glpi\Search\Input\QueryBuilder;
use Glpi\Search\SearchOption;
use Group;
use Group_Ticket;
use Profile_User;
use Session;
use Stat;
use Ticket;
use Ticket_User;
use TicketValidation;
use Toolbox;
use User;

use function Safe\mktime;
use function Safe\strtotime;

/**
 * Provider class
 **/
class Provider
{
    /**
     * Retrieve the number of element for a given item
     *
     * @param CommonDBTM|null $item object to count
     *
     * @param array $params default values for
     * - 'apply_filters' values from dashboard filters
     *
     * @return array :
     * - 'number'
     * - 'url'
     * - 'label'
     * - 'icon'
     */
    public static function bigNumberItem(?CommonDBTM $item = null, array $params = []): array
    {
        $DB = DBConnection::getReadConnection();

        $default_params = [
            'apply_filters'  => [],
        ];
        $params = array_merge($default_params, $params);

        $i_table = $item::getTable();

        Profiler::getInstance()->start(__METHOD__ . ' build SQL criteria');
        $where = $item::getSystemSQLCriteria();

        if (isset($item->fields['is_deleted'])) {
            $where['is_deleted'] = 0;
        }

        if (isset($item->fields['is_template'])) {
            $where['is_template'] = 0;
        }

        if ($item instanceof User) {
            $where += getEntitiesRestrictCriteria(Profile_User::getTable(), '', '', true);
            $request = [
                'SELECT' => ['COUNT DISTINCT' => $item::getTableField($item::getIndexName()) . ' as cpt'],
                'FROM'   => $i_table,
                'INNER JOIN' => [
                    Profile_User::getTable() => [
                        'FKEY' => [
                            Profile_User::getTable() => 'users_id',
                            User::getTable() => 'id',
                        ],
                    ],
                ],
                'WHERE'  => $where,
            ];
        } else {
            if ($item->isEntityAssign()) {
                $where += getEntitiesRestrictCriteria($item::getTable(), '', '', $item->maybeRecursive());
            }
            $request = [
                'SELECT' => ['COUNT DISTINCT' => $item::getTableField($item::getIndexName()) . ' as cpt'],
                'FROM'   => $i_table,
                'WHERE'  => $where,
            ];
        }

        $criteria = array_merge_recursive(
            $request,
            self::getFiltersCriteria($i_table, $params['apply_filters']),
            $item instanceof Ticket ? Ticket::getCriteriaFromProfile() : []
        );
        Profiler::getInstance()->stop(__METHOD__ . ' build SQL criteria');
        $iterator = $DB->request($criteria);

        $result   = $iterator->current();
        $nb_items = $result['cpt'];

        $search_criteria = self::getSearchFiltersCriteria($i_table, $params['apply_filters'], true)['criteria'] ?? [];

        $search_url = $item::getSearchURL();
        $url = $search_url . (str_contains($search_url, '?') ? '&' : '?') . Toolbox::append_params([
            'criteria' => $search_criteria,
            'reset'    => 'reset',
        ]);

        return [
            'number' => $nb_items,
            'url'    => $url,
            'label'  => $item::getTypeName($nb_items),
            'icon'   => $item::getIcon(),
        ];
    }


    /**
     * @method array bigNumberItem(CommonDBTM $item, array $params = [])
     * @method array nbItemByFk(CommonDBTM $item, array $params = [])
     */
    public static function __callStatic(string $name = "", array $arguments = [])
    {
        if (str_contains($name, 'bigNumber')) {
            $itemtype = str_replace('bigNumber', '', $name);
            if (is_subclass_of($itemtype, 'CommonDBTM')) {
                $item = new $itemtype();
                $item->getEmpty();
                return static::bigNumberItem($item, $arguments[0] ?? []);
            }
        }

        if (
            str_contains($name, 'multipleNumber')
            && str_contains($name, 'By')
        ) {
            $tmp = str_replace('multipleNumber', '', $name);
            $tmp = explode('By', $tmp);

            if (count($tmp) === 2) {
                $itemtype    = $tmp[0];
                $fk_itemtype = $tmp[1];

                return static::nbItemByFk(
                    getItemForItemtype($itemtype),
                    getItemForItemtype($fk_itemtype),
                    $arguments[0] ?? []
                );
            }
        }

        if (str_contains($name, 'getArticleList')) {
            $itemtype = str_replace('getArticleList', '', $name);
            if (is_subclass_of($itemtype, 'CommonDBTM')) {
                $item = new $itemtype();
                $item->getEmpty();
                return static::articleListItem($item, $arguments[0] ?? []);
            }
        }
    }


    /**
     * Count number of tickets for a given case
     *
     * @param string $case:
     * - 'notold': not closed or solved tickets
     * - 'late': late tickets
     * - 'waiting_validation': tickets waiting validation for connected user
     * - 'incoming': ticket with incoming status
     * - 'waiting': ticket with waiting status
     * - 'assigned': ticket with assigned status
     * - 'planned': ticket with planned status
     * - 'solved': ticket with solved status
     * - 'closed': ticket with closed status
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array :
     * - 'number'
     * - 'url'
     * - 'label'
     * - 'icon'
     */
    public static function nbTicketsGeneric(
        string $case = "",
        array $params = []
    ): array {
        $DBread = DBConnection::getReadConnection();

        $default_params = [
            'label'                 => "",
            'icon'                  => Ticket::getIcon(),
            'apply_filters'         => [],
            'validation_check_user' => false,
        ];
        $params = array_merge($default_params, $params);

        $nb_tickets      = 0;
        $query_criteria  = [];
        $search_criteria = [];
        $skip = false;

        $notold = [
            'field'      => 12,
            'searchtype' => 'equals',
            'value'      => 'notold',
        ];

        $table = Ticket::getTable();
        Profiler::getInstance()->start(__METHOD__ . ' build SQL criteria');
        $query_criteria = [
            'SELECT'    => [
                'COUNT DISTINCT' => "$table.id AS cpt",
            ],
            'FROM'   => $table,
            'WHERE'  => [
                "$table.is_deleted" => 0,
            ] + getEntitiesRestrictCriteria($table),
        ];

        $query_criteria = array_merge_recursive(
            $query_criteria,
            Ticket::getCriteriaFromProfile(),
            self::getFiltersCriteria($table, $params['apply_filters'])
        );

        switch ($case) {
            case 'notold':
                $search_criteria = [$notold];
                $params['label']  = _x('status', 'Not solved');
                $query_criteria['WHERE'] += [
                    "$table.status" => Ticket::getNotSolvedStatusArray(),
                ];
                break;

            case 'late':
                $params['icon']  = "ti ti-clock";
                $params['label']  = __("Late tickets");
                $search_criteria = [
                    $notold,
                    [
                        'link'       => 'AND',
                        'criteria'   => [
                            [
                                'field'      => 82,
                                'searchtype' => 'equals',
                                'value'      => 1,
                            ], [
                                'link'       => 'OR',
                                'field'      => 182,
                                'searchtype' => 'equals',
                                'value'      => 1,
                            ], [
                                'link'       => 'OR',
                                'field'      => 159,
                                'searchtype' => 'equals',
                                'value'      => 1,
                            ], [
                                'link'       => 'OR',
                                'field'      => 187,
                                'searchtype' => 'equals',
                                'value'      => 1,
                            ],
                        ],
                    ],
                ];
                $query_criteria['WHERE']["$table.status"] = Ticket::getNotSolvedStatusArray();
                $query_criteria['WHERE'][] = [
                    'OR' => [
                        CommonITILObject::generateSLAOLAComputation('time_to_resolve', 'glpi_tickets'),
                        CommonITILObject::generateSLAOLAComputation('internal_time_to_resolve', 'glpi_tickets'),
                        CommonITILObject::generateSLAOLAComputation('time_to_own', 'glpi_tickets'),
                        CommonITILObject::generateSLAOLAComputation('internal_time_to_own', 'glpi_tickets'),
                    ],
                ];
                break;

            case 'waiting_validation':
                $params['icon']  = "ti ti-eye";
                $params['label'] = __("Tickets waiting for approval");
                $search_criteria = [
                    [
                        'field'      => 55,
                        'searchtype' => 'equals',
                        'value'      => CommonITILValidation::WAITING,
                    ],
                    $notold,
                ];

                if ($params['validation_check_user']) {
                    $search_criteria[] = [
                        'link'       => 'AND',
                        'field'      => 59,
                        'searchtype' => 'equals',
                        'value'      => Session::getLoginUserID(),
                    ];
                }

                $where = [
                    'NOT' => ['glpi_tickets.status' => [...Ticket::getSolvedStatusArray(), ...Ticket::getClosedStatusArray()]],
                    'glpi_ticketvalidations.status' => CommonITILValidation::WAITING,
                ];

                if ($params['validation_check_user']) {
                    $where[] = TicketValidation::getTargetCriteriaForUser(Session::getLoginUserID());
                }

                $query_criteria = array_merge_recursive($query_criteria, [
                    'LEFT JOIN' => [
                        'glpi_ticketvalidations' => [
                            'ON' => [
                                'glpi_ticketvalidations' => 'tickets_id',
                                $table                   => 'id',
                            ],
                        ],
                    ],
                    'WHERE' => $where,
                ]);
                break;

                // Statuses speciale cases (no break)
            case 'incoming':
                $status = Ticket::INCOMING;
                $params['icon']  = Ticket::getIcon();
                $params['label'] = __("Incoming tickets");
                $skip = true;
                //no break
            case 'waiting':
                if (!$skip) {
                    $status = Ticket::WAITING;
                    $params['icon']  = "ti ti-player-pause-filled";
                    $params['label'] = __("Pending tickets");
                    $skip = true;
                }
                //no break
            case 'assigned':
                if (!$skip) {
                    $status = Ticket::ASSIGNED;
                    $params['icon']  = "ti ti-users";
                    $params['label'] = __("Assigned tickets");
                    $skip = true;
                }
                //no break
            case 'planned':
                if (!$skip) {
                    $status = Ticket::PLANNED;
                    $params['icon']  = "ti ti-calendar";
                    $params['label'] = __("Planned tickets");
                    $skip = true;
                }
                //no break
            case 'solved':
                if (!$skip) {
                    $status = Ticket::SOLVED;
                    $params['icon']  = "ti ti-checkbox";
                    $params['label'] = __("Solved tickets");
                    $skip = true;
                }
                //no break
            case 'closed':
                if (!$skip) {
                    $status = Ticket::CLOSED;
                    $params['icon']  = "ti ti-archive";
                    $params['label'] = __("Closed tickets");
                    $skip = true;
                }
                //no break
            case 'status':
                if (!$skip) {
                    $status = Ticket::INCOMING;
                }
                $search_criteria = [
                    [
                        'field'      => 12,
                        'searchtype' => 'equals',
                        'value'      => $status,
                    ],
                ];
                $query_criteria = array_merge_recursive($query_criteria, [
                    'WHERE' => [
                        "$table.status" => $status,
                    ],
                ]);
                break;
        }

        $filter_criteria = self::getSearchFiltersCriteria($table, $params['apply_filters'], count($search_criteria) === 0);

        $search_criteria = array_merge(
            $search_criteria,
            $filter_criteria['criteria'] ?? [],
        );

        $url = Ticket::getSearchURL() . "?" . Toolbox::append_params([
            'criteria' => $search_criteria,
            'reset'    => 'reset',
        ]);

        Profiler::getInstance()->stop(__METHOD__ . ' build SQL criteria');
        $iterator   = $DBread->request($query_criteria);
        $result     = $iterator->current();
        $nb_tickets = $result['cpt'];

        return [
            'number'     => $nb_tickets,
            'url'        => $url,
            'label'      => $params['label'],
            'icon'       => $params['icon'],
            's_criteria' => $search_criteria,
            'itemtype'   => 'Ticket',
        ];
    }


    public static function nbTicketsByAgreementStatusAndTechnician(array $params = []): array
    {
        global $DB;

        $DBread = DBConnection::getReadConnection();

        $default_params = [
            'label'         => "",
            'icon'          => 'ti ti-stopwatch',
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        Profiler::getInstance()->start(__METHOD__ . ' build SQL criteria');
        $query_criteria  = [];

        $table = Ticket::getTable();
        $ticketUserTable = Ticket_User::getTable();
        $userTable = User::getTable();

        $ownExceeded = Ticket::generateSLAOLAComputation('time_to_own', $table);
        $resolveExceeded = Ticket::generateSLAOLAComputation('time_to_resolve', $table);
        $slaState = QueryFunction::if(
            condition: [$ownExceeded, $resolveExceeded],
            true_expression: new QueryExpression('3'),
            false_expression: QueryFunction::if(
                condition: $resolveExceeded,
                true_expression: new QueryExpression('2'),
                false_expression: QueryFunction::if(
                    condition: $ownExceeded,
                    true_expression: new QueryExpression('1'),
                    false_expression: new QueryExpression('0')
                )
            ),
            alias: 'sla_state'
        );
        $config = Config::getConfigurationValues('core');
        if ($config['names_format'] == User::FIRSTNAME_BEFORE) {
            $first = "firstname";
            $second = "realname";
        } else {
            $first = "realname";
            $second = "firstname";
        }

        $query_criteria = [
            'COUNT' => 'cpt',
            'SELECT'    => [
                QueryFunction::concat(["{$userTable}.{$first}", new QueryExpression($DB::quoteValue(' ')), "{$userTable}.{$second}"], 'username'),
                "$userTable.name",
                $slaState,
            ],
            'FROM'   => $table,
            'INNER JOIN' => [
                "$ticketUserTable as ul" => [
                    'FKEY' => [
                        'ul' => 'tickets_id',
                        $table => 'id',
                        [
                            'AND' => [
                                "ul.type" => Ticket_User::ASSIGN,
                            ],
                        ],
                    ],
                ],
                $userTable => [
                    'FKEY' => [
                        $userTable => 'id',
                        'ul' => 'users_id',
                    ],
                ],
            ],
            'WHERE'  => [
                "$table.is_deleted" => 0,
            ] + getEntitiesRestrictCriteria($table),
            'GROUPBY' => [
                'sla_state',
                "$userTable.id",
            ],
            'ORDER' => [
                'sla_state DESC',
                'cpt DESC',
                "$userTable.id",
            ],
        ];

        unset($params['apply_filters'][GroupTechFilter::getId()]);
        $query_filter = self::getFiltersCriteria($table, $params['apply_filters']);
        unset($query_filter['LEFT JOIN']["$ticketUserTable as ul"]);

        $query_criteria = array_merge_recursive(
            $query_criteria,
            Ticket::getCriteriaFromProfile(),
            $query_filter
        );

        $allLate = [];
        $resolveLate = [];
        $ownLate = [];
        $onTime = [];
        $names = [];
        $data = [];

        Profiler::getInstance()->stop(__METHOD__ . ' build SQL criteria');
        // Get data and sort by is_late status
        $iterator   = $DBread->request($query_criteria);
        foreach ($iterator as $row) {
            switch ($row['sla_state']) {
                case 3: // Own and resolve are both late
                    $allLate[$row['name']] = $row['cpt'];
                    break;
                case 2: // Resolve is late
                    $resolveLate[$row['name']] = $row['cpt'];
                    break;
                case 1: // own is late
                    $ownLate[$row['name']] = $row['cpt'];
                    break;
                case 0:
                    $onTime[$row['name']] = $row['cpt'];
            }
            $names[$row['name']] = $row['username'];
        }

        // set legend for each serie
        $data['series'][0]['name'] = __('Late own and resolve');
        $data['series'][1]['name'] = __('Late resolve');
        $data['series'][2]['name'] = __('Late own');
        $data['series'][3]['name'] = __('On time');
        $data['series'][0]['data'] = [];
        $data['series'][1]['data'] = [];
        $data['series'][2]['data'] = [];
        $data['series'][3]['data'] = [];
        // ensure thare are 2 values per user (late and in time)
        foreach ($names as $name => $username) {
            if (!isset($allLate[$name])) {
                $allLate[$name] = 0;
            }
            if (!isset($resolveLate[$name])) {
                $resolveLate[$name] = 0;
            }
            if (!isset($ownLate[$name])) {
                $ownLate[$name] = 0;
            }
            if (!isset($onTime[$name])) {
                $onTime[$name] = 0;
            }
            $label = $username ?? $name;
            $data['labels'][] = $label;
            $data['series'][0]['data'][] = $allLate[$name];
            $data['series'][1]['data'][] = $resolveLate[$name];
            $data['series'][2]['data'][] = $ownLate[$name];
            $data['series'][3]['data'][] = $onTime[$name];
        }

        if (count($data['series'][0]['data']) < 1) {
            $data['series'][0]['data'] = [];
            $data['series'][1]['data'] = [];
            $data['series'][2]['data'] = [];
            $data['series'][3]['data'] = [];
            $data['labels'] = [];
            $data['nodata'] = true;
        }

        return [
            'label' => __('Tickets by SLA status and by technician'),
            'data' => $data,
            'icon' => $params['icon'],
        ];
    }

    public static function nbTicketsByAgreementStatusAndTechnicianGroup(
        array $params = []
    ): array {
        $DBread = DBConnection::getReadConnection();

        $default_params = [
            'label'         => "",
            'icon'          => 'ti ti-stopwatch',
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        Profiler::getInstance()->start(__METHOD__ . ' build SQL criteria');
        $query_criteria  = [];

        $table = Ticket::getTable();
        $ticketGroupTable = Group_Ticket::getTable();
        $groupTable = Group::getTable();

        $ownExceeded = Ticket::generateSLAOLAComputation('time_to_own', $table);
        $resolveExceeded = Ticket::generateSLAOLAComputation('time_to_resolve', $table);
        $slaState = "IF ($ownExceeded AND $resolveExceeded, 3, IF ($resolveExceeded, 2, IF ($ownExceeded, 1, 0)))";

        $query_criteria = [
            'COUNT' => 'cpt',
            'SELECT'    => [
                "$groupTable.name",
                new QueryExpression("$slaState as `sla_state`"),
            ],
            'FROM'   => $table,
            'INNER JOIN' => [
                "$ticketGroupTable as gl" => [
                    'FKEY' => [
                        'gl' => 'tickets_id',
                        $table => 'id',
                        [
                            'AND' => [
                                "gl.type" => Ticket_User::ASSIGN,
                            ],
                        ],
                    ],
                ],
                $groupTable => [
                    'FKEY' => [
                        $groupTable => 'id',
                        'gl' => 'groups_id',
                    ],
                ],
            ],
            'WHERE'  => [
                "$table.is_deleted" => 0,
            ] + getEntitiesRestrictCriteria($table),
            'GROUPBY' => [
                'sla_state',
                "$groupTable.id",
            ],
            'ORDER' => [
                'sla_state DESC',
                'cpt DESC',
                "$groupTable.id",
            ],
        ];

        unset($params['apply_filters'][UserTechFilter::getId()]);
        $query_filter = self::getFiltersCriteria($table, $params['apply_filters']);
        unset($query_filter['LEFT JOIN']["$ticketGroupTable as gl"]);

        $query_criteria = array_merge_recursive(
            $query_criteria,
            Ticket::getCriteriaFromProfile(),
            $query_filter
        );

        $allLate = [];
        $resolveLate = [];
        $ownLate = [];
        $onTime = [];
        $names = [];
        $data = [];

        Profiler::getInstance()->stop(__METHOD__ . ' build SQL criteria');
        // Get data and sort by is_late status
        $iterator   = $DBread->request($query_criteria);
        foreach ($iterator as $row) {
            switch ($row['sla_state']) {
                case 3: // Own and resolve are both late
                    $allLate[$row['name']] = $row['cpt'];
                    break;
                case 2: // Resolve is late
                    $resolveLate[$row['name']] = $row['cpt'];
                    break;
                case 1: // own is late
                    $ownLate[$row['name']] = $row['cpt'];
                    break;
                case 0:
                    $onTime[$row['name']] = $row['cpt'];
            }
            $names[$row['name']] = $row['name'];
        }

        // set legend for each serie
        $data['series'][0]['name'] = __('Late own and resolve');
        $data['series'][1]['name'] = __('Late resolve');
        $data['series'][2]['name'] = __('Late own');
        $data['series'][3]['name'] = __('On time');
        $data['series'][0]['data'] = [];
        $data['series'][1]['data'] = [];
        $data['series'][2]['data'] = [];
        $data['series'][3]['data'] = [];
        // ensure thare are 2 values per user (late and in time)
        foreach ($names as $name => $username) {
            if (!isset($allLate[$name])) {
                $allLate[$name] = 0;
            }
            if (!isset($resolveLate[$name])) {
                $resolveLate[$name] = 0;
            }
            if (!isset($ownLate[$name])) {
                $ownLate[$name] = 0;
            }
            if (!isset($onTime[$name])) {
                $onTime[$name] = 0;
            }
            $label = $username ?? $name;
            $data['labels'][] = $label;
            $data['series'][0]['data'][] = $allLate[$name];
            $data['series'][1]['data'][] = $resolveLate[$name];
            $data['series'][2]['data'][] = $ownLate[$name];
            $data['series'][3]['data'][] = $onTime[$name];
        }

        if (count($data['series'][0]['data']) < 1) {
            $data['series'][0]['data'] = [];
            $data['series'][1]['data'] = [];
            $data['series'][2]['data'] = [];
            $data['series'][3]['data'] = [];
            $data['labels'] = [];
            $data['nodata'] = true;
        }

        return [
            'label' => __('Tickets by SLA status and by technician group'),
            'data' => $data,
            'icon' => $params['icon'],
        ];
    }


    /**
     * Get multiple counts of computer by a specific foreign key
     *
     * @param CommonDBTM $item main item to count
     * @param CommonDBTM $fk_item groupby by this item (we will find the foreign key in the main item)
     * @param array $params values for:
     * - 'title' of the card
     * - 'icon' of the card
     * - 'searchoption_id' id corresponding to FK search option
     * - 'limit' max data to return
     * - 'join_key' LEFT, INNER, etc JOIN
     * - 'apply_filters' values from dashboard filters
     *
     * @return array :
     * - 'data': [
     *    'url'
     *    'number'
     *    'label'
     * ]
     * - 'label'
     * - 'icon'
     */
    public static function nbItemByFk(
        ?CommonDBTM $item = null,
        ?CommonDBTM $fk_item = null,
        array $params = []
    ): array {
        $DB = DBConnection::getReadConnection();

        $c_table     = $item::getTable();
        $fk_table    = $fk_item::getTable();
        $fk_itemtype = $fk_item::getType();

        // try to autodetect searchoption id
        $searchoptions = $item->rawSearchOptions();
        $found_so = array_filter($searchoptions, fn($searchoption) => isset($searchoption['table']) && $searchoption['table'] === $fk_table);
        $found_so = array_shift($found_so);
        $found_so_id = $found_so['id'] ?? 0;

        $default_params = [
            'label'           => "",
            'searchoption_id' => $found_so_id,
            'icon'            => $fk_item::getIcon() ?? $item::getIcon(),
            'limit'           => 50,
            'join_key'        => 'LEFT JOIN',
            'apply_filters'   => [],
        ];
        $params = array_merge($default_params, $params);

        Profiler::getInstance()->start(__METHOD__ . ' build SQL criteria');
        $where = $item->getSystemSQLCriteria($c_table);

        if ($item->maybeDeleted()) {
            $where["$c_table.is_deleted"] = 0;
        }
        if ($item->maybeTemplate()) {
            $where["$c_table.is_template"] = 0;
        }

        $name = 'name';
        if ($fk_item instanceof CommonTreeDropdown) {
            $name = 'completename';
        }
        if ($fk_item instanceof CommonDevice) {
            $name = 'designation';
        }
        if ($item->isEntityAssign()) {
            $where += getEntitiesRestrictCriteria($c_table, '', '', $item->maybeRecursive());
        }

        $criteria = array_merge_recursive(
            [
                'SELECT'    => [
                    "$fk_table.$name AS fk_name",
                    "$fk_table.id AS fk_id",
                    'COUNT DISTINCT' => "$c_table.id AS cpt",
                ],
                'FROM'      => $c_table,
                $params['join_key'] => [
                    $fk_table => [
                        'ON' => [
                            $fk_table => 'id',
                            $c_table  => getForeignKeyFieldForItemType($fk_itemtype),
                        ],
                    ],
                ],
                'GROUPBY'   => "$fk_table.$name",
                'ORDERBY'   => "cpt DESC",
                'LIMIT'     => $params['limit'],
            ],
            count($where) ? ['WHERE' => $where] : [],
            self::getFiltersCriteria($c_table, $params['apply_filters']),
            $item instanceof Ticket ? Ticket::getCriteriaFromProfile() : []
        );
        Profiler::getInstance()->stop(__METHOD__ . ' build SQL criteria');
        $iterator = $DB->request($criteria);

        $search_criteria = self::getSearchFiltersCriteria($fk_table, $params['apply_filters'])['criteria'] ?? [];

        $url = $item::getSearchURL();

        $data = [];
        foreach ($iterator as $result) {
            $result_criteria = $search_criteria;
            $result_criteria[] = [
                'field'      => $params['searchoption_id'],
                'searchtype' => 'equals',
                'value'      => $result['fk_id'] ?? 0,
            ];
            $data[] = [
                'number' => $result['cpt'],
                'label'  => $result['fk_name'] ?? __("without"),
                'url'    => $url . (str_contains($url, '?') ? '&' : '?') . Toolbox::append_params([
                    'criteria' => $result_criteria,
                    'reset' => 'reset',
                ]),
            ];
        }

        if (count($data) === 0) {
            $data = [
                'nodata' => true,
            ];
        }

        return [
            'data'  => $data,
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }


    /**
     * Get a list of article for an compatible item (with date,name,text fields)
     *
     * @param CommonDBTM $item the itemtype to list
     * @param array   $params default values for
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function articleListItem(?CommonDBTM $item = null, array $params = []): array
    {
        $DB = DBConnection::getReadConnection();

        $default_params = [
            'icon'          => $item::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        Profiler::getInstance()->start(__METHOD__ . ' build SQL criteria');
        $i_table           = $item::getTable();
        $criteria = array_merge_recursive(
            [
                'SELECT' => "$i_table.*",
                'FROM'   => $i_table,
            ],
            self::getFiltersCriteria($i_table, $params['apply_filters']),
            $item instanceof ExtraVisibilityCriteria ? $item::getVisibilityCriteria() : []
        );
        Profiler::getInstance()->stop(__METHOD__ . ' build SQL criteria');

        $iterator = $DB->request($criteria);

        $data = [];
        foreach ($iterator as $line) {
            $data[] = [
                'date'    => $line['date'] ?? '',
                'label'   => $line['name'] ?? '',
                'content' => $line['text'] ?? '',
                'author'  => User::getFriendlyNameById($line['users_id'] ?? 0),
                'url'     => $item::getFormURLWithID($line['id']),
            ];
        }

        $nb_items = count($data);
        if ($nb_items === 0) {
            $data = [
                'nodata' => true,
            ];
        }

        return [
            'data'   => $data,
            'number' => $nb_items,
            'url'    => $item::getSearchURL(),
            'label'  => $item::getTypeName($nb_items),
            'icon'   => $item::getIcon(),
        ];
    }


    /**
     * get multiple count of ticket by month
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function ticketsOpened(array $params = []): array
    {
        $DB = DBConnection::getReadConnection();
        $default_params = [
            'label'         => "",
            'icon'          => Ticket::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        Profiler::getInstance()->start(__METHOD__ . ' build SQL criteria');
        $t_table = Ticket::getTable();
        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    'COUNT DISTINCT' => "$t_table.id as nb_tickets",
                    QueryFunction::dateFormat('date', '%Y-%m', 'ticket_month'),
                ],
                'FROM'    => $t_table,
                'WHERE'    => [
                    "$t_table.is_deleted" => 0,
                ] + getEntitiesRestrictCriteria($t_table),
                'GROUPBY' => 'ticket_month',
                'ORDER'   => 'ticket_month ASC',
            ],
            Ticket::getCriteriaFromProfile(),
            self::getFiltersCriteria($t_table, $params['apply_filters'])
        );
        Profiler::getInstance()->stop(__METHOD__ . ' build SQL criteria');
        $iterator = $DB->request($criteria);

        $s_criteria = [
            'criteria' => [
                [
                    'link'       => 'AND',
                    'field'      => 15,
                    'searchtype' => 'morethan',
                    'value'      => null,
                ], [
                    'link'       => 'AND',
                    'field'      => 15,
                    'searchtype' => 'lessthan',
                    'value'      => null,
                ],
            ],
            'reset' => 'reset',
        ];

        $data = [];
        foreach ($iterator as $result) {
            [$start_day, $end_day] = self::formatMonthyearDates($result['ticket_month']);

            $s_criteria['criteria'][0]['value'] = $start_day;
            $s_criteria['criteria'][1]['value'] = $end_day;

            $data[] = [
                'number' => $result['nb_tickets'],
                'label'  => $result['ticket_month'],
                'url'    => Ticket::getSearchURL() . "?" . Toolbox::append_params($s_criteria),
            ];
        }

        return [
            'data'        => $data,
            'distributed' => false,
            'label'       => $params['label'],
            'icon'        => $params['icon'],
        ];
    }


    /**
     * Get ticket evolution by opened, solved, closed, late series and months group
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function getTicketsEvolution(array $params = []): array
    {
        $default_params = [
            'label'         => "",
            'icon'          => Ticket::getIcon(),
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $year   = date("Y") - 15;
        $begin  = date("Y-m-d", mktime(1, 0, 0, (int) date("m"), (int) date("d"), $year));
        $end    = date("Y-m-d");

        if (
            isset($params['apply_filters'][DatesFilter::getId()])
            && count($params['apply_filters'][DatesFilter::getId()]) == 2
        ) {
            $begin = date("Y-m-d", strtotime($params['apply_filters'][DatesFilter::getId()][0]));
            $end   = date("Y-m-d", strtotime($params['apply_filters'][DatesFilter::getId()][1]));
            unset($params['apply_filters'][DatesFilter::getId()]);
        }

        $t_table   = Ticket::getTable();

        $base_search_criteria = self::getSearchFiltersCriteria($t_table, $params['apply_filters'])['criteria'] ?? [];

        $series = [

            'inter_total' => [
                'name'   => _nx('ticket', 'Opened', 'Opened', Session::getPluralNumber()),
                'search' => [
                    'criteria' => array_merge(
                        [
                            [
                                'link'       => 'AND',
                                'field'      => 15, // creation date
                                'searchtype' => 'morethan',
                                'value'      => null,
                            ], [
                                'link'       => 'AND',
                                'field'      => 15, // creation date
                                'searchtype' => 'lessthan',
                                'value'      => null,
                            ],
                        ],
                        $base_search_criteria
                    ),
                    'reset' => 'reset',
                ],
            ],
            'inter_solved' => [
                'name'   => _nx('ticket', 'Solved', 'Solved', Session::getPluralNumber()),
                'search' => [
                    'criteria' => array_merge(
                        [
                            [
                                'link'       => 'AND',
                                'field'      => 17, // solve date
                                'searchtype' => 'morethan',
                                'value'      => null,
                            ], [
                                'link'       => 'AND',
                                'field'      => 17, // solve date
                                'searchtype' => 'lessthan',
                                'value'      => null,
                            ],
                        ],
                        $base_search_criteria
                    ),
                    'reset' => 'reset',
                ],
            ],
            'inter_solved_late' => [
                'name'   => __('Late'),
                'search' => [
                    'criteria' => array_merge(
                        [
                            [
                                'link'       => 'AND',
                                'field'      => 17, // solve date
                                'searchtype' => 'morethan',
                                'value'      => null,
                            ], [
                                'link'       => 'AND',
                                'field'      => 17, // solve date
                                'searchtype' => 'lessthan',
                                'value'      => null,
                            ], [
                                'link'       => 'AND',
                                'field'      => 82, // time_to_resolve exceed solve date
                                'searchtype' => 'equals',
                                'value'      => 1,
                            ],
                        ],
                        $base_search_criteria
                    ),
                    'reset' => 'reset',
                ],
            ],
            'inter_closed' => [
                'name'   => __('Closed'),
                'search' => [
                    'criteria' => array_merge(
                        [
                            [
                                'link'       => 'AND',
                                'field'      => 16, // close date
                                'searchtype' => 'morethan',
                                'value'      => null,
                            ], [
                                'link'       => 'AND',
                                'field'      => 16, // close date
                                'searchtype' => 'lessthan',
                                'value'      => null,
                            ],
                        ],
                        $base_search_criteria
                    ),
                    'reset' => 'reset',
                ],
            ],
        ];

        $filters = array_merge_recursive(
            Ticket::getCriteriaFromProfile(),
            self::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $i = 0;
        $monthsyears = [];
        foreach ($series as $stat_type => &$serie) {
            $values = Stat::constructEntryValues(
                'Ticket',
                $stat_type,
                $begin,
                $end,
                "",
                "",
                "",
                $filters
            );

            if ($i === 0) {
                $monthsyears = array_keys($values);
            }
            $values = array_values($values);

            foreach ($values as $index => $number) {
                $current_monthyear = $monthsyears[$index];
                [$start_day, $end_day] = self::formatMonthyearDates($current_monthyear);
                $serie['search']['criteria'][0]['value'] = $start_day;
                $serie['search']['criteria'][1]['value'] = $end_day;

                $serie['data'][$index] = [
                    'value' => $number,
                    'url'   => Ticket::getSearchURL() . "?" . Toolbox::append_params($serie['search']),
                ];
            }

            $i++;
        }

        return [
            'data'  => [
                'labels' => $monthsyears,
                'series' => array_values($series),
            ],
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }


    /**
     * get ticket by their curent status and their opening date
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function getTicketsStatus(array $params = []): array
    {
        $DB = DBConnection::getReadConnection();

        $default_params = [
            'label'          => "",
            'icon'           => Ticket::getIcon(),
            'apply_filters'  => [],
        ];
        $params = array_merge($default_params, $params);

        Profiler::getInstance()->start(__METHOD__ . ' build SQL criteria');
        $statuses = Ticket::getAllStatusArray();
        $t_table  = Ticket::getTable();

        $sub_query = array_merge_recursive(
            [
                'DISTINCT' => true,
                'SELECT'   => ["$t_table.*"],
                'FROM'     => $t_table,
                'WHERE'    => [
                    "$t_table.is_deleted" => 0,
                ] + getEntitiesRestrictCriteria($t_table),
            ],
            // limit count for profiles with limited rights
            Ticket::getCriteriaFromProfile(),
            self::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $criteria = [
            'SELECT'   => [
                QueryFunction::fromUnixtime(
                    expression: QueryFunction::unixTimestamp("{$t_table}_distinct.date"),
                    format: new QueryExpression($DB::quoteValue('%Y-%m')),
                    alias: 'period'
                ),
                QueryFunction::sum(
                    expression: QueryFunction::if(
                        condition: ["{$t_table}_distinct.status" => Ticket::INCOMING],
                        true_expression: new QueryExpression('1'),
                        false_expression: new QueryExpression('0')
                    ),
                    alias: _x('status', 'New')
                ),
                QueryFunction::sum(
                    expression: QueryFunction::if(
                        condition: ["{$t_table}_distinct.status" => Ticket::ASSIGNED],
                        true_expression: new QueryExpression('1'),
                        false_expression: new QueryExpression('0')
                    ),
                    alias: _x('status', 'Processing (assigned)')
                ),
                QueryFunction::sum(
                    expression: QueryFunction::if(
                        condition: ["{$t_table}_distinct.status" => Ticket::PLANNED],
                        true_expression: new QueryExpression('1'),
                        false_expression: new QueryExpression('0')
                    ),
                    alias: _x('status', 'Processing (planned)')
                ),
                QueryFunction::sum(
                    expression: QueryFunction::if(
                        condition: ["{$t_table}_distinct.status" => Ticket::WAITING],
                        true_expression: new QueryExpression('1'),
                        false_expression: new QueryExpression('0')
                    ),
                    alias: __('Pending')
                ),
                QueryFunction::sum(
                    expression: QueryFunction::if(
                        condition: ["{$t_table}_distinct.status" => Ticket::SOLVED],
                        true_expression: new QueryExpression('1'),
                        false_expression: new QueryExpression('0')
                    ),
                    alias: _x('status', 'Solved')
                ),
                QueryFunction::sum(
                    expression: QueryFunction::if(
                        condition: ["{$t_table}_distinct.status" => Ticket::CLOSED],
                        true_expression: new QueryExpression('1'),
                        false_expression: new QueryExpression('0')
                    ),
                    alias: _x('status', 'Closed')
                ),
            ],
            'FROM' => new QuerySubQuery($sub_query, "{$t_table}_distinct"),
            'ORDER'   => 'period ASC',
            'GROUP'    => ['period'],
        ];

        Profiler::getInstance()->stop(__METHOD__ . ' build SQL criteria');
        $iterator = $DB->request($criteria);

        $s_params = [
            'criteria' => array_merge(
                [
                    [
                        'link'       => 'AND',
                        'field'      => 12, // status
                        'searchtype' => 'equals',
                        'value'      => null,
                    ], [
                        'link'       => 'AND',
                        'field'      => 15, // creation date
                        'searchtype' => 'morethan',
                        'value'      => null,
                    ], [
                        'link'       => 'AND',
                        'field'      => 15, // creation date
                        'searchtype' => 'lessthan',
                        'value'      => null,
                    ],
                ],
                self::getSearchFiltersCriteria($t_table, $params['apply_filters'])['criteria'] ?? []
            ),
            'reset' => 'reset',
        ];

        $data = [
            'labels' => [],
            'series' => [],
        ];
        foreach ($iterator as $result) {
            [$start_day, $end_day] = self::formatMonthyearDates($result['period']);
            $s_params['criteria'][1]['value'] = $start_day;
            $s_params['criteria'][2]['value'] = $end_day;

            $data['labels'][] = $result['period'];
            $tmp = $result;
            unset($tmp['period'], $tmp['nb_tickets']);

            $i = 0;
            foreach ($tmp as $label2 => $value) {
                $status_key = array_search($label2, $statuses);
                $s_params['criteria'][0]['value'] = $status_key;

                $data['series'][$i]['name'] = $label2;
                $data['series'][$i]['data'][] = [
                    'value' => (int) $value,
                    'url'   => Ticket::getSearchURL() . "?" . Toolbox::append_params($s_params),
                ];
                $i++;
            }
        }

        return [
            'data'  => $data,
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }


    /**
     * Get numbers of tickets grouped by actors
     *
     * @param string $case cound be:
     * - user_requester
     * - group_requester
     * - user_observer
     * - group_observer
     * - user_assign
     * - group_assign
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function nbTicketsActor(
        string $case = "",
        array $params = []
    ): array {
        $DBread = DBConnection::getReadConnection();
        $default_params = [
            'label'         => "",
            'icon'          => null,
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        Profiler::getInstance()->start(__METHOD__ . ' build SQL criteria');
        $t_table  = Ticket::getTable();
        $li_table = Ticket_User::getTable();
        $ug_table = User::getTable();
        $n_fields = [
            "$ug_table.firstname as first",
            "$ug_table.realname as second",
            "$ug_table.name as username",
        ];

        $where = [
            "$t_table.is_deleted" => 0,
        ];

        $case_array = explode('_', $case);
        if ($case_array[0] == 'user') {
            $where["$ug_table.is_deleted"]  = 0;
            $params['icon'] ??= User::getIcon();
        } elseif ($case_array[0] == 'group') {
            $li_table = Group_Ticket::getTable();
            $ug_table = Group::getTable();
            $n_fields = [
                "$ug_table.completename as first",
            ];
            $params['icon'] ??= Group::getIcon();
        }

        $type = 0;
        $soption = 0;
        switch ($case) {
            case "user_requester":
                $type     = CommonITILActor::REQUESTER;
                $soption  = 4;
                break;
            case "group_requester":
                $type     = CommonITILActor::REQUESTER;
                $soption  = 71;
                break;
            case "user_observer":
                $type     = CommonITILActor::OBSERVER;
                $soption  = 66;
                break;
            case "group_observer":
                $type     = CommonITILActor::OBSERVER;
                $soption  = 65;
                break;
            case "user_assign":
                $type     = CommonITILActor::ASSIGN;
                $soption  = 5;
                break;
            case "group_assign":
                $type     = CommonITILActor::ASSIGN;
                $soption  = 8;
                break;
        }

        $criteria = array_merge_recursive(
            [
                'SELECT' => array_merge([
                    'COUNT DISTINCT' => "$t_table.id AS nb_tickets",
                    "$ug_table.id as actor_id",
                ], $n_fields),
                'FROM' => $t_table,
                'INNER JOIN' => [
                    $li_table => [
                        'ON' => [
                            $li_table => getForeignKeyFieldForItemType("Ticket"),
                            $t_table  => 'id',
                            [
                                'AND' => [
                                    "$li_table.type" => $type,
                                ],
                            ],
                        ],
                    ],
                    $ug_table => [
                        'ON' => [
                            $li_table => getForeignKeyFieldForTable($ug_table),
                            $ug_table  => 'id',
                        ],
                    ],
                ],
                'GROUPBY' => "$ug_table.id",
                'ORDER'   => 'nb_tickets DESC',
                'WHERE'   => $where + getEntitiesRestrictCriteria($t_table),
            ],
            Ticket::getCriteriaFromProfile(),
            self::getFiltersCriteria($t_table, $params['apply_filters'])
        );
        Profiler::getInstance()->stop(__METHOD__ . ' build SQL criteria');
        $iterator = $DBread->request($criteria);

        $s_params = [
            'criteria' => array_merge(
                [
                    [
                        'link'       => 'AND',
                        'field'      => $soption,
                        'searchtype' => 'equals',
                        'value'      => null,
                    ],
                ],
                self::getSearchFiltersCriteria($t_table, $params['apply_filters'])['criteria'] ?? []
            ),
            'reset' => 'reset',
        ];
        $data = [];
        foreach ($iterator as $result) {
            $s_params['criteria'][0]['value'] = $result['actor_id'];
            $data[] = [
                'number' => $result['nb_tickets'],
                'label'  => formatUserName($result['actor_id'], $result['username'], $result['second'], $result['first']),
                'url'    => Ticket::getSearchURL() . "?" . Toolbox::append_params($s_params),
            ];
        }
        return [
            'data'  => $data,
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }


    /**
     * get average stats (takeintoaccoutn, solve/close delay, waiting) of ticket by month
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function averageTicketTimes(array $params = [])
    {
        $DBread = DBConnection::getReadConnection();
        $default_params = [
            'label'         => "",
            'icon'          => "ti ti-stopwatch",
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        Profiler::getInstance()->start(__METHOD__ . ' build SQL criteria');
        $t_table  = Ticket::getTable();
        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    QueryFunction::dateFormat('date', '%Y-%m', 'period'),
                    QueryFunction::avg('takeintoaccount_delay_stat', 'avg_takeintoaccount_delay_stat'),
                    QueryFunction::avg('waiting_duration', 'avg_waiting_duration'),
                    QueryFunction::avg('solve_delay_stat', 'avg_solve_delay_stat'),
                    QueryFunction::avg('close_delay_stat', 'close_delay_stat'),
                ],
                'FROM' => $t_table,
                'WHERE' => [
                    'is_deleted' => 0,
                ] + getEntitiesRestrictCriteria($t_table),
                'ORDER' => 'period ASC',
                'GROUP' => ['period'],
            ],
            Ticket::getCriteriaFromProfile(),
            self::getFiltersCriteria($t_table, $params['apply_filters'])
        );
        Profiler::getInstance()->stop(__METHOD__ . ' build SQL criteria');
        $iterator = $DBread->request($criteria);

        $data = [
            'labels' => [],
            'series' => [
                [
                    'name' => __("Time to own"),
                    'data' => [],
                ], [
                    'name' => __("Waiting time"),
                    'data' => [],
                ], [
                    'name' => __("Time to resolve"),
                    'data' => [],
                ], [
                    'name' => __("Time to close"),
                    'data' => [],
                ],
            ],
        ];
        foreach ($iterator as $r) {
            $data['labels'][] = $r['period'];
            $tmp = $r;
            unset($tmp['period']);

            $data['series'][0]['data'][] = round($r['avg_takeintoaccount_delay_stat'] / HOUR_TIMESTAMP, 1);
            $data['series'][1]['data'][] = round($r['avg_waiting_duration'] / HOUR_TIMESTAMP, 1);
            $data['series'][2]['data'][] = round($r['avg_solve_delay_stat'] / HOUR_TIMESTAMP, 1);
            $data['series'][3]['data'][] = round($r['close_delay_stat'] / HOUR_TIMESTAMP, 1);
        }

        return [
            'data'  => $data,
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }


    /**
     * get multiple count of ticket by status and month
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function getTicketSummary(array $params = [])
    {
        $default_params = [
            'label'         => "",
            'icon'          => "",
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $incoming   = self::nbTicketsGeneric('incoming', $params);
        $assigned   = self::nbTicketsGeneric('assigned', $params);
        $waiting    = self::nbTicketsGeneric('waiting', $params);
        $tovalidate = self::nbTicketsGeneric('waiting_validation', $params);
        $closed     = self::nbTicketsGeneric('closed', $params);
        $solved     = self::nbTicketsGeneric('solved', $params);

        return [
            'data'  => [
                [
                    'number' => $incoming['number'],
                    'label'  => __("New"),
                    'url'    => $incoming['url'],
                    'color'  => '#3bc519',
                ], [
                    'number' => $assigned['number'],
                    'label'  => __("Assigned"),
                    'url'    => $assigned['url'],
                    'color'  => '#f1cd29',
                ], [
                    'number' => $waiting['number'],
                    'label'  => __("Pending"),
                    'url'    => $waiting['url'],
                    'color'  => '#f1a129',
                ], [
                    'number' => $tovalidate['number'],
                    'label'  => __("To approve"),
                    'url'    => $tovalidate['url'],
                    'color'  => '#266ae9',
                ], [
                    'number' => $solved['number'],
                    'label'  => __("Solved"),
                    'url'    => $solved['url'],
                    'color'  => '#edc949',
                ], [
                    'number' => $closed['number'],
                    'label'  => __("Closed"),
                    'url'    => $closed['url'],
                    'color'  => '#555555',
                ],
            ],
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }


    public static function formatMonthyearDates(string $monthyear): array
    {
        $rawdate = explode('-', $monthyear);
        $year    = (int) $rawdate[0];
        $month   = (int) $rawdate[1];
        $monthtime = mktime(0, 0, 0, $month, 1, $year);

        $start_day = date("Y-m-d H:i:s", strtotime("first day of this month", $monthtime));
        $end_day   = date("Y-m-d H:i:s", strtotime("first day of next month", $monthtime));

        return [$start_day, $end_day];
    }

    /**
     * Get search criteria based on given filters.
     *
     * @param string $table                     Related itemtype table.
     * @param array $apply_filters              Dashboard filters.
     * @param bool $default_criteria_on_empty   Return default criteria if filters are not producing any criteria.
     *
     * @return array An empty array, or an array containing a `criteria` key that contains search criteria.
     *
     * @FIXME Remove `criteria` key encapsulation. It cannot be done in 10.0 as some plugins are relying on current signature.
     */
    final public static function getSearchFiltersCriteria(string $table = "", array $apply_filters = [], bool $default_criteria_on_empty = false)
    {
        $s_criteria = [];
        Profiler::getInstance()->start(__METHOD__);
        $filters = Filter::getRegisteredFilterClasses();

        foreach ($filters as $filter) {
            if (!$filter::canBeApplied($table) || !array_key_exists($filter::getId(), $apply_filters)) {
                continue;
            }
            $filter_criteria = $filter::getSearchCriteria($table, $apply_filters[$filter::getId()]);
            array_push($s_criteria, ...$filter_criteria);
        }

        $itemtype = getItemTypeForTable($table);
        if (is_a($itemtype, CommonDBTM::class, true) && $default_criteria_on_empty === true && count($s_criteria) === 0) {
            $s_criteria = QueryBuilder::getDefaultCriteria($itemtype);
        }

        Profiler::getInstance()->stop(__METHOD__);
        return ['criteria' => $s_criteria];
    }

    public static function getFiltersCriteria(string $table = "", array $apply_filters = [])
    {
        $where = [];
        $join  = [];

        $filters = Filter::getRegisteredFilterClasses();

        foreach ($filters as $filter) {
            if (!$filter::canBeApplied($table) || !array_key_exists($filter::getId(), $apply_filters)) {
                continue;
            }
            $filter_criteria = $filter::getCriteria($table, $apply_filters[$filter::getId()]);
            if (isset($filter_criteria['WHERE'])) {
                $where = array_merge($where, $filter_criteria['WHERE']);
            }
            if (isset($filter_criteria['JOIN'])) {
                $join = array_merge($join, $filter_criteria['JOIN']);
            }
        }

        $criteria = [];
        if (count($where)) {
            $criteria['WHERE'] = $where;
        }
        if (count($join)) {
            $criteria['LEFT JOIN'] = $join;
        }

        return $criteria;
    }
}
