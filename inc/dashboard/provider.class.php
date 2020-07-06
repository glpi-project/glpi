<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

namespace Glpi\Dashboard;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Central class
**/
class Provider extends \CommonGLPI {


   /**
    * Retrieve the number of element for a given item
    *
    * @param CommonDBTM|null object to count
    *
    * @return array :
    * - 'number'
    * - 'url'
    * - 'label'
    * - 'icon'
    */
   static function bigNumberItem(\CommonDBTM $item = null): array {
      $DB = \DBConnection::getReadConnection();

      $criteria = [];
      if (isset($item->fields['is_deleted'])) {
         $criteria['is_deleted'] = 0;
      }

      if (isset($item->fields['is_template'])) {
         $criteria['is_template'] = 0;
      }

      if ($item->isEntityAssign()) {
         $criteria += getEntitiesRestrictCriteria($item::getTable());
      }

      $iterator = $DB->request([
         'COUNT'  => 'cpt',
         'FROM'   => $item::getTable(),
         'WHERE'  => $criteria
      ]);

      $result   = $iterator->next();
      $nb_items = $result['cpt'];

      $url = $item::getSearchURL();
      $url .= (strpos($url, '?') !== false ? '&' : '?') . 'reset';

      return [
         'number' => $nb_items,
         'url'    => $url,
         'label'  => $item::getTypeName($nb_items),
         'icon'   => $item::getIcon(),
      ];
   }


   /**
    * @method self::bigNumberItem
    * @method self::nbItemByFk
    */
   public static function __callStatic(string $name = "", array $arguments = []) {
      if (strpos($name, 'bigNumber') !== false) {
         $itemtype = str_replace('bigNumber', '', $name);
         if (is_subclass_of($itemtype, 'CommonDBTM')) {
            $item = new $itemtype;
            $item->getEmpty();
            return self::bigNumberItem($item);
         }
      }

      if (strpos($name, 'multipleNumber') !== false
          && strpos($name, 'By') !== false) {
         $tmp = str_replace('multipleNumber', '', $name);
         $tmp = explode('By', $tmp);

         if (count($tmp) === 2) {
            $itemtype    = $tmp[0];
            $fk_itemtype = $tmp[1];

            return self::nbItemByFk(
               new $itemtype,
               new $fk_itemtype,
               $arguments[0] ?? []
            );
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
    *
    * @return array :
    * - 'number'
    * - 'url'
    * - 'label'
    * - 'icon'
    */
   static function nbTicketsGeneric(
      string $case = "",
      array $params = []
   ):array {
      $DBread = \DBConnection::getReadConnection();

      $default_params = [
         'label' => "",
         'icon'  => \Ticket::getIcon(),
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

      $table = \Ticket::getTable();
      $query_criteria = [
         'FROM'    => $table,
         'WHERE'   => getEntitiesRestrictCriteria($table) + [
            "$table.is_deleted" => 0,
         ],
         'GROUPBY' => "$table.id"
      ];

      switch ($case) {
         case 'notold':
            $search_criteria = [$notold];
            $query_criteria['WHERE']+= [
               "$table.status" => \Ticket::getNotSolvedStatusArray(),
            ];
         break;

         case 'late':
            $params['icon']  = "far fa-clock";
            $params['label']  = __("Late tickets");
            $search_criteria = array_merge([$notold], [
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
                     ]
                  ]
               ]
            ]);
            $query_criteria['WHERE']+= [
               "$table.status" => \Ticket::getNotSolvedStatusArray(),
               'OR' => [
                  'time_to_resolve'          => ['<', new \QueryExpression('NOW()')],
                  'time_to_own'              => ['<', new \QueryExpression('NOW()')],
                  'internal_time_to_own'     => ['<', new \QueryExpression('NOW()')],
                  'internal_time_to_resolve' => ['<', new \QueryExpression('NOW()')],
               ]
            ];
            break;

         case 'waiting_validation':
            $params['icon']  = "far fa-eye";
            $params['label'] = __("Tickets waiting your validation");
            $search_criteria = [
               [
                  'field'      => 55,
                  'searchtype' => 'equals',
                  'value'      => \CommonITILValidation::WAITING,
               ],  [
                  'link'       => 'AND',
                  'field'      => 59,
                  'searchtype' => 'equals',
                  'value'      => \Session::getLoginUserID(),
               ]
            ];
            $query_criteria = array_merge_recursive($query_criteria, [
               'LEFT JOIN' => [
                  'glpi_ticketvalidations' => [
                     'ON' => [
                        'glpi_ticketvalidations' => 'tickets_id',
                        $table                   => 'id'
                     ]
                  ]
               ],
               'WHERE' => [
                  'glpi_ticketvalidations.status'            => \CommonITILValidation::WAITING,
                  'glpi_ticketvalidations.users_id_validate' => \Session::getLoginUserID()
               ]
            ]);
            break;

         // Statuses speciale cases (no break)
         case 'incoming':
            $status = \Ticket::INCOMING;
            $params['icon']  = \Ticket::getIcon();
            $params['label'] = __("Incoming tickets");
            $skip = true;
         case 'waiting':
            if (!$skip) {
               $status =\Ticket::WAITING;
               $params['icon']  = "fas fa-pause-circle";
               $params['label'] = __("Pending tickets");
               $skip = true;
            }
         case 'assigned':
            if (!$skip) {
               $status = \Ticket::ASSIGNED;
               $params['icon']  = "fas fa-users";
               $params['label'] = __("Assigned tickets");
               $skip = true;
            }
         case 'planned':
            if (!$skip) {
               $status = \Ticket::PLANNED;
               $params['icon']  = "fas fa-calendar-check";
               $params['label'] = __("Planned tickets");
               $skip = true;
            }
         case 'solved':
            if (!$skip) {
               $status = \Ticket::SOLVED;
               $params['icon']  = "far fa-check-square";
               $params['label'] = __("Solved tickets");
               $skip = true;
            }
         case 'closed':
            if (!$skip) {
               $status = \Ticket::CLOSED;
               $params['icon']  = "fas fa-archive";
               $params['label'] = __("Closed tickets");
               $skip = true;
            }
         case 'status':
            if (!$skip) {
               $status = \Ticket::INCOMING;
            }
            $search_criteria = [
               [
                  'field'      => 12,
                  'searchtype' => 'equals',
                  'value'      => $status,
               ]
            ];
            $query_criteria = array_merge_recursive($query_criteria, [
               'WHERE' => [
                  "$table.status" => $status,
               ]
            ]);
            break;
      }

      $url = \Ticket::getSearchURL()."?".\Toolbox::append_params([
         'criteria' => $search_criteria,
         'reset'    => 'reset'
      ]);

      $iterator   = $DBread->request($query_criteria);
      if ($nb_tickets === 0) {
         $nb_tickets = count($iterator);
      }

      return [
         'number'     => $nb_tickets,
         'url'        => $url,
         'label'      => $params['label'],
         'icon'       => $params['icon'],
         's_criteria' => $search_criteria,
         'itemtype'   => 'Ticket',
      ];
   }


   /**
    * Get multiple counts of computer by a specific foreign key
    *
    * @param \CommonDBTM $item main item to count
    * @param \CommonDBTM $fk_item groupby by this item (we will find the foreign key in the main item)
    * @param array $params values for:
    * - 'title' of the card
    * - 'icon' of the card
    * - 'searchoption_id' id corresponding to FK search option
    * - 'limit' max data to return
    * - 'join_key' LEFT, INNER, etc JOIN
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
      \CommonDBTM $item = null,
      \CommonDBTM $fk_item = null,
      array $params = []
   ): array {
      $DB = \DBConnection::getReadConnection();

      $c_table     = $item::getTable();
      $fk_table    = $fk_item::getTable();
      $fk_itemtype = $fk_item::getType();

      // try to autodetect searchoption id
      $searchoptions = $item->rawSearchOptions();
      $found_so = array_filter($searchoptions, function($searchoption) use($fk_table) {
         return isset($searchoption['table']) && $searchoption['table'] === $fk_table;
      });
      $found_so = array_shift($found_so);
      $found_so_id = $found_so['id'] ?? 0;

      $default_params = [
         'label'           => "",
         'searchoption_id' => $found_so_id,
         'icon'            => $fk_item::getIcon() ?? $item::getIcon(),
         'limit'           => 50,
         'join_key'        => 'LEFT JOIN',
      ];
      $params = array_merge($default_params, $params);

      $where = [];
      if ($item->maybeDeleted()) {
         $where["$c_table.is_deleted"] = 0;
      }
      if ($item->maybeTemplate()) {
         $where["$c_table.is_template"] = 0;
      }

      $name = 'name';
      if ($fk_item instanceof \CommonTreeDropdown) {
         $name = 'completename';
      }

      if ($item->isEntityAssign()) {
         $where += getEntitiesRestrictCriteria($c_table, '', '', $item->maybeRecursive());
      }

      $iterator = $DB->request([
         'SELECT'    => [
            "$fk_table.$name AS fk_name",
            "$fk_table.id AS fk_id",
            'COUNT' => "$c_table.id AS cpt",
         ],
         'DISTINCT'  => true,
         'FROM'      => $c_table,
         $params['join_key'] => [
            $fk_table => [
               'ON' => [
                  $fk_table => 'id',
                  $c_table  => getForeignKeyFieldForItemType($fk_itemtype),
               ]
            ]
         ],
         'WHERE'     => $where,
         'GROUPBY'   => "$fk_table.$name",
         'ORDERBY'   => "cpt DESC",
         'LIMIT'     => $params['limit'],
      ]);

      $search_criteria = [
         'criteria' => [
            [
               'field'      => $params['searchoption_id'],
               'searchtype' => 'equals',
               'value'      => 0
            ]
            ],
            'reset' => 'reset',
      ];

      $url = $item::getSearchURL();
      $url .= (strpos($url, '?') !== false ? '&' : '?') . 'reset';

      $data = [];
      foreach ($iterator as $result) {
         $search_criteria['criteria'][0]['value'] = $result['fk_id'] ?? 0;
         $data[] = [
            'number' => $result['cpt'],
            'label'  => $result['fk_name'] ?? __("without"),
            'url'    => $url . '&' . \Toolbox::append_params($search_criteria),
         ];
      }

      if (count($data) === 0) {
         $data = [
            'nodata' => true
         ];
      }

      return [
         'data'  => $data,
         'label' => $params['label'],
         'icon'  => $params['icon'],
      ];
   }


   /**
    * get multiple count of ticket by month
    *
    * @param array $params default values for
    * - 'title' of the card
    * - 'icon' of the card
    *
    * @return array
    */
   public static function ticketsOpened(array $params = []): array {
      $DB = \DBConnection::getReadConnection();
      $default_params = [
         'label' => "",
         'icon'  => \Ticket::getIcon(),
      ];
      $params = array_merge($default_params, $params);

      $t_table = \Ticket::getTable();
      $iterator = $DB->request([
         'SELECT' => [
            'COUNT' => 'id as nb_tickets',
            new \QueryExpression("DATE_FORMAT(".$DB->quoteName("date").", '%Y-%m') AS ticket_month")
         ],
         'FROM' => $t_table,
         'GROUPBY' => 'ticket_month',
         'ORDER'   => 'ticket_month ASC'
      ]);

      $s_criteria = [
         'criteria' => [
            [
               'link'       => 'AND',
               'field'      => 15,
               'searchtype' => 'morethan',
               'value'      => null
            ], [
               'link'       => 'AND',
               'field'      => 15,
               'searchtype' => 'lessthan',
               'value'      => null
            ],
         ],
         'reset' => 'reset'
      ];

      $data = [];
      foreach ($iterator as $result) {
         list($start_day, $end_day) = self::formatMonthyearDates($result['ticket_month']);

         $s_criteria['criteria'][0]['value'] = $start_day;
         $s_criteria['criteria'][1]['value'] = $end_day;

         $data[] = [
            'number' => $result['nb_tickets'],
            'label'  => $result['ticket_month'],
            'url'    => \Ticket::getSearchURL()."?".\Toolbox::append_params($s_criteria),
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
    *
    * @return array
    */
   public static function getTicketsEvolution(array $params = []): array {
      $default_params = [
         'label' => "",
         'icon'  => \Ticket::getIcon(),
      ];
      $params = array_merge($default_params, $params);

      $year   = date("Y")-15;
      $begin  = date("Y-m-d", mktime(1, 0, 0, (int)date("m"), (int)date("d"), $year));
      $end    = date("Y-m-d");

      $total = \Stat::constructEntryValues('Ticket', "inter_total", $begin, $end);
      $monthsyears = array_keys($total);
      $series = [
         [
            'name' => _nx('ticket', 'Opened', 'Opened', 2),
            'data' => array_values($total),
            'search' => [
               'criteria' => [
                  [
                     'link'       => 'AND',
                     'field'      => 15, // creation date
                     'searchtype' => 'morethan',
                     'value'      => null
                  ], [
                     'link'       => 'AND',
                     'field'      => 15, // creation date
                     'searchtype' => 'lessthan',
                     'value'      => null
                  ]
               ],
               'reset' => 'reset'
            ]
         ], [
            'name' => _nx('ticket', 'Solved', 'Solved', 2),
            'data' => array_values(\Stat::constructEntryValues('Ticket', "inter_solved", $begin, $end)),
            'search' => [
               'criteria' => [
                  [
                     'link'       => 'AND',
                     'field'      => 17, // solve date
                     'searchtype' => 'morethan',
                     'value'      => null
                  ], [
                     'link'       => 'AND',
                     'field'      => 17, // solve date
                     'searchtype' => 'lessthan',
                     'value'      => null
                  ]
               ],
               'reset' => 'reset'
            ]
         ], [
            'name' => __('Late'),
            'data' => array_values(\Stat::constructEntryValues('Ticket', "inter_solved_late", $begin, $end)),
            'search' => [
               'criteria' => [
                  [
                     'link'       => 'AND',
                     'field'      => 17, // solve date
                     'searchtype' => 'morethan',
                     'value'      => null
                  ], [
                     'link'       => 'AND',
                     'field'      => 17, // solve date
                     'searchtype' => 'lessthan',
                     'value'      => null
                  ], [
                     'link'       => 'AND',
                     'field'      => 82, // time_to_resolve exceed solve date
                     'searchtype' => 'equals',
                     'value'      => 1
                  ]
               ],
               'reset' => 'reset'
            ]
         ], [
            'name' => __('Closed'),
            'data' => array_values(\Stat::constructEntryValues('Ticket', "inter_closed", $begin, $end)),
            'search' => [
               'criteria' => [
                  [
                     'link'       => 'AND',
                     'field'      => 16, // close date
                     'searchtype' => 'morethan',
                     'value'      => null
                  ], [
                     'link'       => 'AND',
                     'field'      => 16, // close date
                     'searchtype' => 'lessthan',
                     'value'      => null
                  ]
               ],
               'reset' => 'reset'
            ]
         ],
      ];

      foreach ($series as &$serie) {
         $numbers = $serie['data'];
         $serie['data'] = [];

         foreach ($numbers as $index => $number) {
            $current_monthyear = $monthsyears[$index];
            list($start_day, $end_day) = self::formatMonthyearDates($current_monthyear);
            $serie['search']['criteria'][0]['value'] = $start_day;
            $serie['search']['criteria'][1]['value'] = $end_day;

            $serie['data'][$index] = [
               'value' => $number,
               'url'   => \Ticket::getSearchURL()."?".\Toolbox::append_params($serie['search']),
            ];
         }

         unset($serie['search']);
      }

      return [
         'data'  => [
            'labels' => $monthsyears,
            'series' => $series,
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
    *
    * @return array
    */
   public static function getTicketsStatus(array $params = []): array {
      $DB = \DBConnection::getReadConnection();

      $default_params = [
         'label' => "",
         'icon'  => \Ticket::getIcon(),
      ];
      $params = array_merge($default_params, $params);

      $statuses = \ticket::getAllStatusArray();

      $t_table = \Ticket::getTable();
      $iterator = $DB->request([
         'DISTINCT' => true,
         'SELECT'   => [
            new \QueryExpression(
               "FROM_UNIXTIME(UNIX_TIMESTAMP(".$DB->quoteName("$t_table.date")."),'%Y-%m') AS period"
            ),
            new \QueryExpression(
               "SUM(IF($t_table.status = ".\Ticket::INCOMING.", 1, 0)) as ".$DB->quoteValue(_x('status', 'New'))
            ),
            new \QueryExpression(
               "SUM(IF($t_table.status = ".\Ticket::ASSIGNED.", 1, 0)) as ".$DB->quoteValue(_x('status', 'Processing (assigned)'))
            ),
            new \QueryExpression(
               "SUM(IF($t_table.status = ".\Ticket::PLANNED.", 1, 0)) as ".$DB->quoteValue(_x('status', 'Processing (planned)'))
            ),
            new \QueryExpression(
               "SUM(IF($t_table.status = ".\Ticket::WAITING.", 1, 0)) as ".$DB->quoteValue(__('Pending'))
            ),
            new \QueryExpression(
               "SUM(IF($t_table.status = ".\Ticket::SOLVED.", 1, 0)) as ".$DB->quoteValue(_x('status', 'Solved'))
            ),
            new \QueryExpression(
               "SUM(IF($t_table.status = ".\Ticket::CLOSED.", 1, 0)) as ".$DB->quoteValue(_x('status', 'Closed'))
            ),
         ],
         'FROM'     => $t_table,
         'WHERE'    => [
            "$t_table.is_deleted" => 0,
         ] + getEntitiesRestrictCriteria($t_table),
         'ORDER'   => 'period ASC',
         'GROUP'    => ['period']
      ]);

      $s_criteria = [
         'criteria' => [
            [
               'link'       => 'AND',
               'field'      => 12, // status
               'searchtype' => 'equals',
               'value'      => null
            ], [
               'link'       => 'AND',
               'field'      => 15, // creation date
               'searchtype' => 'morethan',
               'value'      => null
            ], [
               'link'       => 'AND',
               'field'      => 15, // creation date
               'searchtype' => 'lessthan',
               'value'      => null
            ],
         ],
         'reset' => 'reset'
      ];

      $data = [
         'labels' => [],
         'series' => []
      ];
      foreach ($iterator as $result) {
         list($start_day, $end_day) = self::formatMonthyearDates($result['period']);
         $s_criteria['criteria'][1]['value'] = $start_day;
         $s_criteria['criteria'][2]['value'] = $end_day;

         $data['labels'][] = $result['period'];
         $tmp = $result;
         unset($tmp['period'], $tmp['nb_tickets']);

         $i = 0;
         foreach ($tmp as $label2 => $value) {
            $status_key = array_search($label2, $statuses);
            $s_criteria['criteria'][0]['value'] = $status_key;

            $data['series'][$i]['name'] = $label2;
            $data['series'][$i]['data'][] = [
               'value' => (int) $value,
               'url'   => \Ticket::getSearchURL()."?".\Toolbox::append_params($s_criteria),
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
    *
    * @return array
    */
   public static function nbTicketsActor(
      string $case = "",
      array $params = []
   ):array {
      $DBread = \DBConnection::getReadConnection();
      $default_params = [
         'label' => "",
         'icon'  => null,
      ];
      $params = array_merge($default_params, $params);

      $t_table  = \Ticket::getTable();
      $li_table = \Ticket_User::getTable();
      $ug_table = \User::getTable();
      $n_fields = [
         "$ug_table.firstname as first",
         "$ug_table.realname as second",
      ];

      $where = [
         "$t_table.is_deleted" => 0,
      ];

      $case_array = explode('_', $case);
      if ($case_array[0] == 'user') {
         $where["$ug_table.is_deleted"]  = 0;
         $params['icon'] = $params['icon'] ?? \User::getIcon();
      } else if ($case_array[0] == 'group') {
         $li_table = \Group_Ticket::getTable();
         $ug_table = \Group::getTable();
         $n_fields = [
            "$ug_table.completename as first"
         ];
         $params['icon'] = $params['icon'] ?? \Group::getIcon();
      }

      $type = 0;
      switch ($case) {
         case "user_requester":
            $type     = \CommonITILActor::REQUESTER;
            $soption  = 4;
            break;
         case "group_requester":
            $type     = \CommonITILActor::REQUESTER;
            $soption  = 71;
            break;
         case "user_observer":
            $type     = \CommonITILActor::OBSERVER;
            $soption  = 66;
            break;
         case "group_observer":
            $type     = \CommonITILActor::OBSERVER;
            $soption  = 65;
            break;
         case "user_assign":
            $type     = \CommonITILActor::ASSIGN;
            $soption  = 4;
            break;
         case "group_assign":
            $type     = \CommonITILActor::OBSERVER;
            $soption  = 8;
            break;
      }

      $iterator = $DBread->request([
         'SELECT' => array_merge([
            'COUNT' => "$t_table.id AS nb_tickets",
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
                        "$li_table.type" => $type
                     ]
                  ]
               ]
            ],
            $ug_table => [
               'ON' => [
                  $li_table => getForeignKeyFieldForTable($ug_table),
                  $ug_table  => 'id'
               ]
            ]
         ],
         'GROUPBY' => "$ug_table.id",
         'ORDER'   => 'nb_tickets DESC',
         'WHERE'   => $where + getEntitiesRestrictCriteria($t_table),
      ]);
      $s_criteria = [
         'criteria' => [
            [
               'link'       => 'AND',
               'field'      => $soption,
               'searchtype' => 'equals',
               'value'      => null
            ],
         ],
         'reset' => 'reset'
      ];
      $data = [];
      foreach ($iterator as $result) {
         $s_criteria['criteria'][0]['value'] = $result['actor_id'];
         $data[] = [
            'number' => $result['nb_tickets'],
            'label'  => $result['first']." ".($result['second'] ?? ""),
            'url'    => \Ticket::getSearchURL()."?".\Toolbox::append_params($s_criteria),
         ];
      }
      return [
         'data'  => $data,
         'label' => $params['label'],
         'icon'  => $params['icon'],
      ];
   }


   public static function averageTicketTimes(array $params = []) {
      $DBread = \DBConnection::getReadConnection();
      $default_params = [
         'label' => "",
         'icon'  => "fas fa-stopwatch",
      ];
      $params = array_merge($default_params, $params);

      $t_table = \Ticket::getTable();
      $iterator = $DBread->request([
         'SELECT' => [
            new \QueryExpression("DATE_FORMAT(".$DBread->quoteName("date").", '%Y-%m') AS period"),
            new \QueryExpression("AVG(".$DBread->quoteName("takeintoaccount_delay_stat").") AS avg_takeintoaccount_delay_stat"),
            new \QueryExpression("AVG(".$DBread->quoteName("waiting_duration").") AS avg_waiting_duration"),
            new \QueryExpression("AVG(".$DBread->quoteName("solve_delay_stat").") AS avg_solve_delay_stat"),
            new \QueryExpression("AVG(".$DBread->quoteName("close_delay_stat").") AS close_delay_stat"),
         ],
         'FROM' => $t_table,
         'WHERE' => [
            'is_deleted' => 0,
         ] + getEntitiesRestrictCriteria($t_table),
         'ORDER' => 'period ASC',
         'GROUP' => ['period']
      ]);

      $data = [
         'labels' => [],
         'series' => [
            [
               'name' => __("Time to own"),
               'data' => []
            ], [
               'name' => __("Waiting time"),
               'data' => []
            ], [
               'name' => __("Time to resolve"),
               'data' => []
            ], [
               'name' => __("Time to close"),
               'data' => []
            ]
         ]
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


   public static function formatMonthyearDates(string $monthyear): array {
      $rawdate = explode('-', $monthyear);
      $year    = $rawdate[0];
      $month   = $rawdate[1];
      $monthtime = mktime(0, 0, 0, $month, 1, $year);

      $start_day = date("Y-m-d H:i:s", strtotime("first day of this month", $monthtime));
      $end_day   = date("Y-m-d H:i:s", strtotime("first day of next month", $monthtime));

      return [$start_day, $end_day];
   }

}
