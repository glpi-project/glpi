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
use Glpi\ContentTemplates\Parameters\CommonITILObjectParameters;
use Glpi\ContentTemplates\Parameters\ProblemParameters;
use Glpi\DBAL\QueryExpression;
use Glpi\RichText\RichText;
use Glpi\Search\DefaultSearchRequestInterface;

/**
 * Problem class
 **/
class Problem extends CommonITILObject implements DefaultSearchRequestInterface
{
    // From CommonDBTM
    public $dohistory = true;
    protected static $forward_entity_to = ['ProblemCost'];

    // From CommonITIL
    public $userlinkclass        = 'Problem_User';
    public $grouplinkclass       = 'Group_Problem';
    public $supplierlinkclass    = 'Problem_Supplier';

    public static $rightname            = 'problem';
    protected $usenotepad        = true;


    public const MATRIX_FIELD         = 'priority_matrix';
    public const URGENCY_MASK_FIELD   = 'urgency_mask';
    public const IMPACT_MASK_FIELD    = 'impact_mask';
    public const STATUS_MATRIX_FIELD  = 'problem_status';

    public static function getTypeName($nb = 0)
    {
        return _n('Problem', 'Problems', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['helpdesk', self::class];
    }

    public function canSolve()
    {

        return (self::isAllowedStatus($this->fields['status'], self::SOLVED)
              // No edition on closed status
              && !in_array($this->fields['status'], static::getClosedStatusArray())
              && (Session::haveRight(self::$rightname, UPDATE)
                  || (Session::haveRight(self::$rightname, self::READMY)
                      && ($this->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                          || (isset($_SESSION["glpigroups"])
                              && $this->haveAGroup(
                                  CommonITILActor::ASSIGN,
                                  $_SESSION["glpigroups"]
                              ))))));
    }


    public static function canView(): bool
    {
        return Session::haveRightsOr(self::$rightname, [self::READALL, self::READMY]);
    }


    /**
     * Is the current user have right to show the current problem ?
     *
     * @return boolean
     **/
    public function canViewItem(): bool
    {

        if (!Session::haveAccessToEntity($this->getEntityID(), $this->isRecursive())) {
            return false;
        }
        return (Session::haveRight(self::$rightname, self::READALL)
              || (Session::haveRight(self::$rightname, self::READMY)
                  && ($this->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
                      || $this->isUser(CommonITILActor::OBSERVER, Session::getLoginUserID())
                      || (isset($_SESSION["glpigroups"])
                          && ($this->haveAGroup(CommonITILActor::REQUESTER, $_SESSION["glpigroups"])
                              || $this->haveAGroup(
                                  CommonITILActor::OBSERVER,
                                  $_SESSION["glpigroups"]
                              )))
                      || ($this->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                          || (isset($_SESSION["glpigroups"])
                              && $this->haveAGroup(
                                  CommonITILActor::ASSIGN,
                                  $_SESSION["glpigroups"]
                              ))))));
    }


    /**
     * Is the current user have right to create the current problem ?
     *
     * @return boolean
     **/
    public function canCreateItem(): bool
    {

        if (!Session::haveAccessToEntity($this->getEntityID())) {
            return false;
        }
        return Session::haveRight(self::$rightname, CREATE);
    }


    /**
     * is the current user could reopen the current problem
     *
     * @since 9.4.0
     *
     * @return boolean
     */
    public function canReopen()
    {
        return Session::haveRight('followup', CREATE)
             && in_array($this->fields["status"], static::getClosedStatusArray())
             && ($this->isAllowedStatus($this->fields['status'], self::INCOMING)
                 || $this->isAllowedStatus($this->fields['status'], self::ASSIGNED));
    }


    public function pre_deleteItem()
    {
        global $CFG_GLPI;

        if (!isset($this->input['_disablenotif']) && $CFG_GLPI['use_notifications']) {
            NotificationEvent::raiseEvent('delete', $this);
        }
        return true;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (static::canView()) {
            switch ($item::class) {
                case self::class:
                    $ong = [];
                    if ($item->canUpdate()) {
                        $ong[1] = static::createTabEntry(__('Statistics'), 0, null, 'ti ti-chart-pie');
                    }

                    return $ong;

                case User::class:
                    $nb = 0;
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            ['glpi_problems', 'glpi_problems_users'],
                            [
                                'glpi_problems_users.problems_id'  => new QueryExpression(DBmysql::quoteName('glpi_problems.id')),
                                'glpi_problems_users.users_id'    => $item->getID(),
                                'glpi_problems_users.type'        => CommonITILActor::REQUESTER,
                                'glpi_problems.is_deleted'        => 0,
                            ] + getEntitiesRestrictCriteria(self::getTable())
                        );
                    }
                    return self::createTabEntry(__('Created problems'), $nb, $item::getType());

                case Group::class:
                    $nb = 0;
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            ['glpi_problems', 'glpi_groups_problems'],
                            [
                                'glpi_groups_problems.problems_id' => new QueryExpression(DBmysql::quoteName('glpi_problems.id')),
                                'glpi_groups_problems.groups_id'  => $item->getID(),
                                'glpi_groups_problems.type'       => CommonITILActor::REQUESTER,
                                'glpi_problems.is_deleted'        => 0,
                            ] + getEntitiesRestrictCriteria(self::getTable())
                        );
                    }
                    return self::createTabEntry(__('Created problems'), $nb, $item::getType());
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch (get_class($item)) {
            case self::class:
                switch ($tabnum) {
                    case 1:
                        $item->showStats();
                        break;
                }
                break;

            case User::class:
            case Group::class:
                return self::showListForItem($item, $withtemplate);
        }
        return true;
    }


    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(self::class, $ong, $options);
        $this->addStandardTab(Problem_Ticket::class, $ong, $options);
        $this->addStandardTab(Change_Problem::class, $ong, $options);
        $this->addStandardTab(ProblemCost::class, $ong, $options);
        $this->addStandardTab(Itil_Project::class, $ong, $options);
        $this->addStandardTab(Item_Problem::class, $ong, $options);
        if ($this->hasImpactTab()) {
            $this->addStandardTab(Impact::class, $ong, $options);
        }
        $this->addStandardTab(Notepad::class, $ong, $options);
        $this->addStandardTab(KnowbaseItem_Item::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }


    public function cleanDBonPurge()
    {
        // CommonITILTask does not extends CommonDBConnexity
        $pt = new ProblemTask();
        $pt->deleteByCriteria(['problems_id' => $this->fields['id']]);

        $this->deleteChildrenAndRelationsFromDb(
            [
                Change_Problem::class,
                // Done by parent: Group_Problem::class,
                Item_Problem::class,
                // Done by parent: ITILSolution::class,
                // Done by parent: Problem_Supplier::class,
                Problem_Ticket::class,
                // Done by parent: Problem_User::class,
                ProblemCost::class,
                Problem_Problem::class,
            ]
        );

        parent::cleanDBonPurge();
    }


    public function post_updateItem($history = true)
    {
        global $CFG_GLPI;

        parent::post_updateItem($history);

        $donotif = count($this->updates);

        if (isset($this->input['_forcenotif'])) {
            $donotif = true;
        }

        if (isset($this->input['_disablenotif'])) {
            $donotif = false;
        }

        if ($donotif && $CFG_GLPI["use_notifications"]) {
            $mailtype = "update";
            if (
                isset($this->input["status"]) && $this->input["status"]
                && in_array("status", $this->updates)
                && in_array($this->input["status"], static::getSolvedStatusArray())
            ) {
                $mailtype = "solved";
            }

            if (
                isset($this->input["status"])
                && $this->input["status"]
                && in_array("status", $this->updates)
                && in_array($this->input["status"], static::getClosedStatusArray())
            ) {
                $mailtype = "closed";
            }

            // Read again problem to be sure that all data are up to date
            $this->getFromDB($this->fields['id']);
            NotificationEvent::raiseEvent($mailtype, $this);
        }
    }


    public function prepareInputForAdd($input)
    {
        $input =  parent::prepareInputForAdd($input);
        if ($input === false) {
            return false;
        }

        $this->processRules(RuleCommonITILObject::ONADD, $input);

        if (!isset($input['_skip_auto_assign']) || $input['_skip_auto_assign'] === false) {
            // Manage auto assign
            $auto_assign_mode = Entity::getUsedConfig('auto_assign_mode', $input['entities_id']);

            switch ($auto_assign_mode) {
                case Entity::CONFIG_NEVER:
                    break;

                case Entity::AUTO_ASSIGN_HARDWARE_CATEGORY:
                case Entity::AUTO_ASSIGN_CATEGORY_HARDWARE:
                    // Auto assign tech/group from Category
                    // Problems are not associated to a hardware then both settings behave the same way
                    $input = $this->setTechAndGroupFromItilCategory($input);
                    break;
            }
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->transformActorsInput($input);

        $entid = $input['entities_id'] ?? $this->fields['entities_id'];
        $this->processRules(RuleCommonITILObject::ONUPDATE, $input, $entid);

        $input = parent::prepareInputForUpdate($input);
        return $input;
    }


    public function post_addItem()
    {
        global $DB;

        parent::post_addItem();

        if (isset($this->input['_tickets_id'])) {
            $ticket = new Ticket();
            if ($ticket->getFromDB($this->input['_tickets_id'])) {
                $pt = new Problem_Ticket();
                $pt->add(['tickets_id'  => $this->input['_tickets_id'],
                    'problems_id' => $this->fields['id'],
                ]);

                if (
                    !empty($ticket->fields['itemtype'])
                    && ($ticket->fields['items_id'] > 0)
                ) {
                    $it = new Item_Problem();
                    $it->add(['problems_id' => $this->fields['id'],
                        'itemtype'    => $ticket->fields['itemtype'],
                        'items_id'    => $ticket->fields['items_id'],
                    ]);
                }

                //Copy associated elements
                $iterator = $DB->request([
                    'FROM'   => Item_Ticket::getTable(),
                    'WHERE'  => [
                        'tickets_id'   => $this->input['_tickets_id'],
                    ],
                ]);
                $assoc = new Item_Problem();
                foreach ($iterator as $row) {
                    unset($row['tickets_id']);
                    unset($row['id']);
                    $row['problems_id'] = $this->fields['id'];
                    $assoc->add($row);
                }
            }
        }

        $this->handleNewItemNotifications();

        if (
            isset($this->input['_from_items_id'])
            && isset($this->input['_from_itemtype'])
        ) {
            $item_problem = new Item_Problem();
            $item_problem->add([
                'items_id'      => (int) $this->input['_from_items_id'],
                'itemtype'      => $this->input['_from_itemtype'],
                'problems_id'   => $this->fields['id'],
                '_disablenotif' => true,
            ]);
        }
    }

    #[Override]
    public static function getDefaultSearchRequest(): array
    {

        $search = ['criteria' => [0 => ['field'      => 12,
            'searchtype' => 'equals',
            'value'      => 'notold',
        ],
        ],
            'sort'     => 19,
            'order'    => 'DESC',
        ];

        return $search;
    }


    public function getSpecificMassiveActions($checkitem = null)
    {
        $actions = parent::getSpecificMassiveActions($checkitem);
        if (ProblemTask::canCreate()) {
            $actions[self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'add_task'] = __s('Add a new task');
        }
        if ($this->canAdminActors()) {
            $actions[self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'add_actor'] = __s('Add an actor');
            $actions[self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'update_notif']
               = __s('Set notifications for all actors');
        }

        return $actions;
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab = array_merge($tab, $this->getSearchOptionsMain());

        $tab[] = [
            'id'                 => '63',
            'table'              => 'glpi_items_problems',
            'field'              => 'id',
            'name'               => _x('quantity', 'Number of items'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'count',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => 'glpi_items_problems',
            'field'              => 'items_id',
            'name'               => _n('Associated element', 'Associated elements', Session::getPluralNumber()),
            'datatype'           => 'specific',
            'comments'           => true,
            'nosort'             => true,
            'nosearch'           => true,
            'additionalfields'   => ['itemtype'],
            'joinparams'         => [
                'jointype'           => 'child',
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '131',
            'table'              => 'glpi_items_problems',
            'field'              => 'itemtype',
            'name'               => _n('Associated item type', 'Associated item types', Session::getPluralNumber()),
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'ticket_types',
            'nosort'             => true,
            'additionalfields'   => ['itemtype'],
            'joinparams'         => [
                'jointype'           => 'child',
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false,
        ];

        $tab = array_merge($tab, $this->getSearchOptionsActors());

        $tab[] = [
            'id'                 => 'analysis',
            'name'               => __('Analysis'),
        ];

        $tab[] = [
            'id'                 => '60',
            'table'              => $this->getTable(),
            'field'              => 'impactcontent',
            'name'               => __('Impacts'),
            'massiveaction'      => false,
            'datatype'           => 'text',
            'htmltext'           => true,
        ];

        $tab[] = [
            'id'                 => '61',
            'table'              => $this->getTable(),
            'field'              => 'causecontent',
            'name'               => __('Causes'),
            'massiveaction'      => false,
            'datatype'           => 'text',
            'htmltext'           => true,
        ];

        $tab[] = [
            'id'                 => '62',
            'table'              => $this->getTable(),
            'field'              => 'symptomcontent',
            'name'               => __('Symptoms'),
            'massiveaction'      => false,
            'datatype'           => 'text',
            'htmltext'           => true,
        ];

        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        $tab = array_merge($tab, ITILFollowup::rawSearchOptionsToAdd());

        $tab = array_merge($tab, ProblemTask::rawSearchOptionsToAdd());

        $tab = array_merge($tab, $this->getSearchOptionsSolution());

        $tab = array_merge($tab, $this->getSearchOptionsStats());

        $tab = array_merge($tab, ProblemCost::rawSearchOptionsToAdd());

        $tab[] = [
            'id'                 => 'ticket',
            'name'               => Ticket::getTypeName(Session::getPluralNumber()),
        ];

        $tab[] = [
            'id'                 => '141',
            'table'              => 'glpi_problems_tickets',
            'field'              => 'id',
            'name'               => _x('quantity', 'Number of tickets'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'count',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
        ];

        if (Session::haveRight('change', READ)) {
            $tab = array_merge($tab, Change::rawSearchOptionsToAdd('Problem'));
        }

        return $tab;
    }


    public static function rawSearchOptionsToAdd(string $itemtype)
    {
        global $CFG_GLPI;

        $tab = [];

        if ($itemtype == "Ticket") {
            $tab[] = [
                'id'                 => 'problem',
                'name'               => __('Problems'),
            ];

            //FIXME: Fix the search options for linked ITIL objects
            $tab[] = [
                'id'                 => '200',
                'table'              => 'glpi_problems_tickets',
                'field'              => 'id',
                'name'               => _x('quantity', 'Number of problems'),
                'forcegroupby'       => true,
                'usehaving'          => true,
                'datatype'           => 'count',
                'massiveaction'      => false,
                'joinparams'         => [
                    'jointype'           => 'child',
                ],
            ];

            $tab[] = [
                'id'                 => '201',
                'table'              => Problem::getTable(),
                'field'              => 'name',
                'name'               => Problem::getTypeName(1),
                'datatype'           => 'dropdown',
                'massiveaction'      => false,
                'forcegroupby'       => true,
                'joinparams'         => [
                    'beforejoin'         => [
                        'table'              => Problem_Ticket::getTable(),
                        'joinparams'         => [
                            'jointype'           => 'child',
                        ],
                    ],
                ],
            ];

            $tab[] = [
                'id'                  => '202',
                'table'               => Problem::getTable(),
                'field'               => 'status',
                'name'                => __('Status'),
                'datatype'            => 'specific',
                'searchtype'          => 'equals',
                'searchequalsonfield' => true,
                'massiveaction'       => false,
                'forcegroupby'        => true,
                'joinparams'          => [
                    'beforejoin'          => [
                        'table'               => Problem_Ticket::getTable(),
                        'joinparams'          => [
                            'jointype'            => 'child',
                        ],
                    ],
                ],
            ];

            $tab[] = [
                'id'                 => '203',
                'table'              => Problem::getTable(),
                'field'              => 'solvedate',
                'name'               => __('Resolution date'),
                'datatype'           => 'datetime',
                'massiveaction'      => false,
                'forcegroupby'       => true,
                'joinparams'         => [
                    'beforejoin'         => [
                        'table'              => Problem_Ticket::getTable(),
                        'joinparams'         => [
                            'jointype'           => 'child',
                        ],
                    ],
                ],
            ];

            $tab[] = [
                'id'                 => '204',
                'table'              => Problem::getTable(),
                'field'              => 'date',
                'name'               => __('Opening date'),
                'datatype'           => 'datetime',
                'massiveaction'      => false,
                'forcegroupby'       => true,
                'joinparams'         => [
                    'beforejoin'         => [
                        'table'              => Problem_Ticket::getTable(),
                        'joinparams'         => [
                            'jointype'           => 'child',
                        ],
                    ],
                ],
            ];
        } elseif (in_array($itemtype, $CFG_GLPI["ticket_types"])) {
            $tab[] = [
                'id'            => 140,
                'table'         => self::getTable(),
                'field'         => "id",
                'datatype'      => "count",
                'name'          => _x('quantity', 'Number of problems'),
                'forcegroupby'  => true,
                'usehaving'     => true,
                'massiveaction' => false,
                'joinparams'    => [
                    'beforejoin' => [
                        'table' => self::getItemLinkClass()::getTable(),
                        'joinparams' => [
                            'jointype' => 'itemtype_item',
                        ],
                    ],
                    'condition' => getEntitiesRestrictCriteria('NEWTABLE'),
                ],
            ];
        }

        return $tab;
    }

    public static function getAllStatusArray($withmetaforsearch = false)
    {
        $tab = [
            self::INCOMING => _x('status', 'New'),
            self::ACCEPTED => _x('status', 'Accepted'),
            self::ASSIGNED => _x('status', 'Processing (assigned)'),
            self::PLANNED  => _x('status', 'Processing (planned)'),
            self::WAITING  => __('Pending'),
            self::SOLVED   => _x('status', 'Solved'),
            self::OBSERVED => __('Under observation'),
            self::CLOSED   => _x('status', 'Closed'),
        ];

        if ($withmetaforsearch) {
            $tab['notold']    = _x('status', 'Not solved');
            $tab['notclosed'] = _x('status', 'Not closed');
            $tab['process']   = __('Processing');
            $tab['old']       = _x('status', 'Solved + Closed');
            $tab['all']       = __('All');
        }
        return $tab;
    }


    /**
     * Get the ITIL object closed status list
     *
     * @since 0.83
     *
     * @return array
     **/
    public static function getClosedStatusArray()
    {

        // To be overridden by class
        $tab = [self::CLOSED];
        return $tab;
    }


    /**
     * Get the ITIL object solved or observe status list
     *
     * @since 0.83
     *
     * @return array
     **/
    public static function getSolvedStatusArray()
    {
        // To be overridden by class
        $tab = [self::OBSERVED, self::SOLVED];
        return $tab;
    }

    /**
     * Get the ITIL object new status list
     *
     * @since 0.83.8
     *
     * @return array
     **/
    public static function getNewStatusArray()
    {
        return [self::INCOMING, self::ACCEPTED];
    }

    /**
     * Get the ITIL object assign, plan or accepted status list
     *
     * @since 0.83
     *
     * @return array
     **/
    public static function getProcessStatusArray()
    {

        // To be overridden by class
        $tab = [self::ACCEPTED, self::ASSIGNED, self::PLANNED];

        return $tab;
    }


    /**
     * @since 0.84
     *
     * @param $start
     * @param $status             (default 'proces)
     * @param $showgroupproblems  (true by default)
     **/
    public static function showCentralList($start, $status = "process", $showgroupproblems = true)
    {
        global $CFG_GLPI, $DB;

        if (!static::canView()) {
            return false;
        }

        $WHERE = [
            'is_deleted' => 0,
        ];
        $search_users_id = [
            'glpi_problems_users.users_id'   => Session::getLoginUserID(),
            'glpi_problems_users.type'       => CommonITILActor::REQUESTER,
        ];
        $search_assign = [
            'glpi_problems_users.users_id'   => Session::getLoginUserID(),
            'glpi_problems_users.type'       => CommonITILActor::ASSIGN,
        ];

        if ($showgroupproblems) {
            $search_users_id  = [0];
            $search_assign = [0];

            if (count($_SESSION['glpigroups'])) {
                $search_users_id = [
                    'glpi_groups_problems.groups_id' => $_SESSION['glpigroups'],
                    'glpi_groups_problems.type'      => CommonITILActor::REQUESTER,
                ];
                $search_assign = [
                    'glpi_groups_problems.groups_id' => $_SESSION['glpigroups'],
                    'glpi_groups_problems.type'      => CommonITILActor::ASSIGN,
                ];
            }
        }

        switch ($status) {
            case "waiting": // on affiche les problemes en attente
                $WHERE = array_merge(
                    $WHERE,
                    $search_assign,
                    ['status' => self::WAITING]
                );
                break;

            case "process": // on affiche les problemes planifi??s ou assign??s au user
                $WHERE = array_merge(
                    $WHERE,
                    $search_assign,
                    ['status' => [self::PLANNED, self::ASSIGNED]]
                );
                break;

            default:
                $WHERE = array_merge(
                    $WHERE,
                    $search_users_id,
                    [
                        'status' => [
                            self::INCOMING,
                            self::ACCEPTED,
                            self::PLANNED,
                            self::ASSIGNED,
                            self::WAITING,
                        ],
                    ]
                );
                $WHERE['NOT'] = $search_assign;
        }

        $criteria = [
            'SELECT'          => ['glpi_problems.id'],
            'DISTINCT'        => true,
            'FROM'            => 'glpi_problems',
            'LEFT JOIN'       => [
                'glpi_problems_users'   => [
                    'ON' => [
                        'glpi_problems_users'   => 'problems_id',
                        'glpi_problems'         => 'id',
                    ],
                ],
                'glpi_groups_problems'  => [
                    'ON' => [
                        'glpi_groups_problems'  => 'problems_id',
                        'glpi_problems'         => 'id',
                    ],
                ],
            ],
            'WHERE'           => $WHERE + getEntitiesRestrictCriteria('glpi_problems'),
            'ORDERBY'         => 'date_mod DESC',
        ];
        $iterator = $DB->request($criteria);

        $total_row_count = count($iterator);
        $displayed_row_count = min((int) $_SESSION['glpidisplay_count_on_home'], $total_row_count);

        if ($total_row_count > 0) {
            $options  = [
                'criteria' => [],
                'reset'    => 'reset',
            ];
            if ($showgroupproblems) {
                switch ($status) {
                    case "waiting":
                        $options['criteria'][0]['field']      = 12; // status
                        $options['criteria'][0]['searchtype'] = 'equals';
                        $options['criteria'][0]['value']      = self::WAITING;
                        $options['criteria'][0]['link']       = 'AND';

                        $options['criteria'][1]['field']      = 8; // groups_id_assign
                        $options['criteria'][1]['searchtype'] = 'equals';
                        $options['criteria'][1]['value']      = 'mygroups';
                        $options['criteria'][1]['link']       = 'AND';

                        $main_header = "<a href=\"" . htmlescape($CFG_GLPI["root_doc"] . "/front/problem.php?" . Toolbox::append_params($options)) . "\">"
                         . Html::makeTitle(__('Problems on pending status'), $displayed_row_count, $total_row_count) . "</a>";
                        break;

                    case "process":
                        $options['criteria'][0]['field']      = 12; // status
                        $options['criteria'][0]['searchtype'] = 'equals';
                        $options['criteria'][0]['value']      = 'process';
                        $options['criteria'][0]['link']       = 'AND';

                        $options['criteria'][1]['field']      = 8; // groups_id_assign
                        $options['criteria'][1]['searchtype'] = 'equals';
                        $options['criteria'][1]['value']      = 'mygroups';
                        $options['criteria'][1]['link']       = 'AND';

                        $main_header = "<a href=\"" . htmlescape($CFG_GLPI["root_doc"] . "/front/problem.php?" . Toolbox::append_params($options)) . "\">"
                         . Html::makeTitle(__('Problems to be processed'), $displayed_row_count, $total_row_count) . "</a>";
                        break;

                    default:
                        $options['criteria'][0]['field']      = 12; // status
                        $options['criteria'][0]['searchtype'] = 'equals';
                        $options['criteria'][0]['value']      = 'notold';
                        $options['criteria'][0]['link']       = 'AND';

                        $options['criteria'][1]['field']      = 71; // groups_id
                        $options['criteria'][1]['searchtype'] = 'equals';
                        $options['criteria'][1]['value']      = 'mygroups';
                        $options['criteria'][1]['link']       = 'AND';

                        $main_header = "<a href=\"" . htmlescape($CFG_GLPI["root_doc"] . "/front/problem.php?" . Toolbox::append_params($options)) . "\">"
                         . Html::makeTitle(__('Your problems in progress'), $displayed_row_count, $total_row_count) . "</a>";
                }
            } else {
                switch ($status) {
                    case "waiting":
                        $options['criteria'][0]['field']      = 12; // status
                        $options['criteria'][0]['searchtype'] = 'equals';
                        $options['criteria'][0]['value']      = self::WAITING;
                        $options['criteria'][0]['link']       = 'AND';

                        $options['criteria'][1]['field']      = 5; // users_id_assign
                        $options['criteria'][1]['searchtype'] = 'equals';
                        $options['criteria'][1]['value']      = Session::getLoginUserID();
                        $options['criteria'][1]['link']       = 'AND';

                        $main_header = "<a href=\"" . htmlescape($CFG_GLPI["root_doc"] . "/front/problem.php?" . Toolbox::append_params($options)) . "\">"
                         . Html::makeTitle(__('Problems on pending status'), $displayed_row_count, $total_row_count) . "</a>";
                        break;

                    case "process":
                        $options['criteria'][0]['field']      = 5; // users_id_assign
                        $options['criteria'][0]['searchtype'] = 'equals';
                        $options['criteria'][0]['value']      = Session::getLoginUserID();
                        $options['criteria'][0]['link']       = 'AND';

                        $options['criteria'][1]['field']      = 12; // status
                        $options['criteria'][1]['searchtype'] = 'equals';
                        $options['criteria'][1]['value']      = 'process';
                        $options['criteria'][1]['link']       = 'AND';

                        $main_header = "<a href=\"" . htmlescape($CFG_GLPI["root_doc"] . "/front/problem.php?" . Toolbox::append_params($options)) . "\">"
                         . Html::makeTitle(__('Problems to be processed'), $displayed_row_count, $total_row_count) . "</a>";
                        break;

                    default:
                        $options['criteria'][0]['field']      = 4; // users_id
                        $options['criteria'][0]['searchtype'] = 'equals';
                        $options['criteria'][0]['value']      = Session::getLoginUserID();
                        $options['criteria'][0]['link']       = 'AND';

                        $options['criteria'][1]['field']      = 12; // status
                        $options['criteria'][1]['searchtype'] = 'equals';
                        $options['criteria'][1]['value']      = 'notold';
                        $options['criteria'][1]['link']       = 'AND';

                        $main_header = "<a href=\"" . htmlescape($CFG_GLPI["root_doc"] . "/front/problem.php?" . Toolbox::append_params($options)) . "\">"
                        . Html::makeTitle(__('Your problems in progress'), $displayed_row_count, $total_row_count) . "</a>";
                }
            }

            $twig_params = [
                'class'        => 'table table-borderless table-striped table-hover card-table',
                'header_rows'  => [
                    [
                        [
                            'colspan'   => 3,
                            'content'   => $main_header,
                        ],
                    ],
                ],
                'rows'         => [],
            ];

            $i = 0;
            if ($displayed_row_count > 0) {
                $twig_params['header_rows'][] = [
                    [
                        'content'   => __('ID'),
                        'style'     => 'width: 75px',
                    ],
                    [
                        'content'   => _n('Requester', 'Requesters', 1),
                        'style'     => 'width: 20%',
                    ],
                    __('Description'),
                ];
                foreach ($iterator as $data) {
                    $problem = new self();
                    $rand = mt_rand();
                    $row = [
                        'values' => [],
                    ];

                    if ($problem->getFromDBwithData($data['id'])) {
                        $bgcolor = $_SESSION["glpipriority_" . $problem->fields["priority"]];
                        $name = sprintf(__('%1$s: %2$s'), __('ID'), $problem->fields["id"]);
                        $row['values'][] = [
                            'class' => 'badge_block',
                            'content' => "<span style='background: " . htmlescape($bgcolor) . "'></span>&nbsp;" . htmlescape($name),
                        ];

                        $requesters = [];
                        if (
                            isset($problem->users[CommonITILActor::REQUESTER])
                            && count($problem->users[CommonITILActor::REQUESTER])
                        ) {
                            foreach ($problem->users[CommonITILActor::REQUESTER] as $d) {
                                if ($d["users_id"] > 0) {
                                    $name = '<i class="fs-4 ti ti-user text-muted me-1"></i>'
                                        . htmlescape(getUserName($d["users_id"]));
                                    $requesters[] = $name;
                                } else {
                                    $requesters[] = '<i class="fs-4 ti ti-mail text-muted me-1"></i>'
                                        . htmlescape($d['alternative_email']);
                                }
                            }
                        }

                        if (
                            isset($problem->groups[CommonITILActor::REQUESTER])
                            && count($problem->groups[CommonITILActor::REQUESTER])
                        ) {
                            foreach ($problem->groups[CommonITILActor::REQUESTER] as $d) {
                                $requesters[] = '<i class="fs-4 ti ti-users text-muted me-1"></i>'
                                    . htmlescape(Dropdown::getDropdownName("glpi_groups", $d["groups_id"]));
                            }
                        }
                        $row['values'][] = implode('<br>', $requesters);

                        $link = "<a id='problem" . $problem->getID() . $rand . "' href='"
                            . htmlescape(Problem::getFormURLWithID($problem->fields["id"]));
                        $link .= "'>";
                        $link .= "<span class='b'>" . htmlescape($problem->fields["name"]) . "</span></a>";
                        $link = sprintf(
                            __s('%1$s %2$s'),
                            $link,
                            Html::showToolTip(
                                RichText::getEnhancedHtml($problem->fields['content']),
                                ['applyto' => 'problem' . $problem->fields["id"] . $rand,
                                    'display' => false,
                                ]
                            )
                        );

                        $row['values'][] = $link;
                    } else {
                        $row['class'] = 'tab_bg_2';
                        $row['values'] = [
                            [
                                'colspan' => 6,
                                'content' => "<i>" . __s('No problem in progress.') . "</i>",
                            ],
                        ];
                    }
                    $twig_params['rows'][] = $row;

                    $i++;
                    if ($i == $displayed_row_count) {
                        break;
                    }
                }
            }
            TemplateRenderer::getInstance()->display('components/table.html.twig', $twig_params);
        }
    }


    /**
     * Get problems count
     *
     * @since 0.84
     *
     * @param bool $foruser only for current login user as requester
     * @param bool $display if false, return html
     **/
    public static function showCentralCount(bool $foruser = false, bool $display = true)
    {
        global $CFG_GLPI, $DB;

        // show a tab with count of jobs in the central and give link
        if (!static::canView()) {
            return false;
        }
        if (!Session::haveRight(self::$rightname, self::READALL)) {
            $foruser = true;
        }

        $table = self::getTable();
        $criteria = [
            'SELECT' => [
                'status',
                'COUNT'  => '* AS COUNT',
            ],
            'FROM'   => $table,
            'WHERE'  => getEntitiesRestrictCriteria($table),
            'GROUP'  => 'status',
        ];

        if ($foruser) {
            $criteria['LEFT JOIN'] = [
                'glpi_problems_users' => [
                    'ON' => [
                        'glpi_problems_users'   => 'problems_id',
                        $table                  => 'id', [
                            'AND' => [
                                'glpi_problems_users.type' => CommonITILActor::REQUESTER,
                            ],
                        ],
                    ],
                ],
            ];
            $WHERE = ['glpi_problems_users.users_id' => Session::getLoginUserID()];

            if (
                isset($_SESSION["glpigroups"])
                && count($_SESSION["glpigroups"])
            ) {
                $criteria['LEFT JOIN']['glpi_groups_problems'] = [
                    'ON' => [
                        'glpi_groups_problems'  => 'problems_id',
                        $table                  => 'id', [
                            'AND' => [
                                'glpi_groups_problems.type' => CommonITILActor::REQUESTER,
                            ],
                        ],
                    ],
                ];
                $WHERE['glpi_groups_problems.groups_id'] = $_SESSION['glpigroups'];
            }
            $criteria['WHERE'][] = ['OR' => $WHERE];
        }

        $deleted_criteria = $criteria;
        $criteria['WHERE']['glpi_problems.is_deleted'] = 0;
        $deleted_criteria['WHERE']['glpi_problems.is_deleted'] = 1;
        $iterator = $DB->request($criteria);
        $deleted_iterator = $DB->request($deleted_criteria);

        $status = [];
        foreach (self::getAllStatusArray() as $key => $val) {
            $status[$key] = 0;
        }

        foreach ($iterator as $data) {
            $status[$data["status"]] = $data["COUNT"];
        }

        $number_deleted = 0;
        foreach ($deleted_iterator as $data) {
            $number_deleted += $data["COUNT"];
        }

        $options = [];
        $options['criteria'][0]['field']      = 12;
        $options['criteria'][0]['searchtype'] = 'equals';
        $options['criteria'][0]['value']      = 'process';
        $options['criteria'][0]['link']       = 'AND';
        $options['reset']                     = 'reset';

        $twig_params = [
            'title'     => [
                'link'   => $CFG_GLPI["root_doc"] . "/front/problem.php?" . Toolbox::append_params($options),
                'text'   => self::getTypeName(Session::getPluralNumber()),
                'icon'   => self::getIcon(),
            ],
            'items'     => [],
        ];

        foreach ($status as $key => $val) {
            $options['criteria'][0]['value'] = $key;
            $twig_params['items'][] = [
                'link'   => $CFG_GLPI["root_doc"] . "/front/problem.php?" . Toolbox::append_params($options),
                'text'   => self::getStatus($key),
                'icon'   => self::getStatusClass($key),
                'count'  => $val,
            ];
        }

        $options['criteria'][0]['value'] = 'all';
        $options['is_deleted']  = 1;
        $twig_params['items'][] = [
            'link'   => $CFG_GLPI["root_doc"] . "/front/problem.php?" . Toolbox::append_params($options),
            'text'   => __('Deleted'),
            'icon'   => 'ti ti-trash bg-red-lt',
            'count'  => $number_deleted,
        ];

        $output = TemplateRenderer::getInstance()->render('central/lists/itemtype_count.html.twig', $twig_params);
        if ($display) {
            echo $output;
        } else {
            return $output;
        }
    }


    /**
     * @since 0.84
     *
     * @param $ID
     * @param $forcetab  string   name of the tab to force at the display (default '')
     **/
    public static function showVeryShort($ID, $forcetab = '')
    {
        // Prints a job in short form
        // Should be called in a <table>-segment
        // Print links or not in case of user view
        // Make new job object and fill it from database, if success, print it
        $viewusers = User::canView();

        $problem   = new self();
        $rand      = mt_rand();
        if ($problem->getFromDBwithData($ID)) {
            $bgcolor = htmlescape($_SESSION["glpipriority_" . $problem->fields["priority"]]);
            $name    = htmlescape(sprintf(__('%1$s: %2$s'), __('ID'), $problem->fields["id"]));
            echo "<tr class='tab_bg_2'>";
            echo "<td>
            <div class='badge_block' style='border-color: $bgcolor'>
               <span style='background: $bgcolor'></span>&nbsp;$name
            </div>
         </td>";
            echo "<td class='center'>";

            if (
                isset($problem->users[CommonITILActor::REQUESTER])
                && count($problem->users[CommonITILActor::REQUESTER])
            ) {
                foreach ($problem->users[CommonITILActor::REQUESTER] as $d) {
                    $user = new User();
                    if ($d["users_id"] > 0 && $user->getFromDB($d["users_id"])) {
                        $name = "<span class='b'>" . htmlescape($user->getName()) . "</span>";
                        if ($viewusers) {
                            $name = sprintf(
                                __s('%1$s %2$s'),
                                $name,
                                Html::showToolTip(
                                    $user->getInfoCard(),
                                    [
                                        'link'    => $user->getLinkURL(),
                                        'display' => false,
                                    ]
                                )
                            );
                        }
                        echo $name;
                    } else {
                        echo htmlescape($d['alternative_email']) . "&nbsp;";
                    }
                    echo "<br>";
                }
            }

            if (
                isset($problem->groups[CommonITILActor::REQUESTER])
                && count($problem->groups[CommonITILActor::REQUESTER])
            ) {
                foreach ($problem->groups[CommonITILActor::REQUESTER] as $d) {
                    echo htmlescape(Dropdown::getDropdownName("glpi_groups", $d["groups_id"]));
                    echo "<br>";
                }
            }

            echo "</td>";

            echo "<td>";
            $link = "<a id='problem" . $problem->getID() . $rand . "' href='"
                  . htmlescape(Problem::getFormURLWithID($problem->getID()));
            if ($forcetab != '') {
                $link .= "&amp;forcetab=" . htmlescape($forcetab);
            }
            $link .= "'>";
            $link .= "<span class='b'>" . htmlescape($problem->fields["name"]) . "</span></a>";
            $link = printf(
                __s('%1$s %2$s'),
                $link,
                Html::showToolTip(
                    RichText::getEnhancedHtml($problem->fields['content']),
                    ['applyto' => 'problem' . $problem->fields["id"] . $rand,
                        'display' => false,
                    ]
                )
            );

            echo "</td>";

            // Finish Line
            echo "</tr>";
        } else {
            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='6' ><i>" . __s('No problem in progress.') . "</i></td></tr>";
        }
    }

    /**
     * Display problems for an item
     *
     * Will also display problems of linked items
     *
     * @param CommonDBTM $item
     * @param integer    $withtemplate
     *
     * @return void|false
     **/
    public static function showListForItem(CommonDBTM $item, $withtemplate = 0)
    {
        if (!Session::haveRightsOr(self::$rightname, [self::READALL])) {
            return false;
        }

        if ($item->isNewID($item->getID())) {
            return false;
        }

        $options = [
            'metacriteria' => [],
        ];

        switch (get_class($item)) {
            case Group::class:
                // Mini search engine
                /** @var Group $item */
                if ($item->haveChildren()) {
                    $tree = (int) Session::getSavedOption(self::class, 'tree', 0);
                    TemplateRenderer::getInstance()->display('components/form/item_itilobject_group.html.twig', [
                        'tree' => $tree,
                    ]);
                } else {
                    $tree = 0;
                }
                break;
        }
        Item_Problem::showListForItem($item, $withtemplate, $options);
    }

    public static function getListForItemRestrict(CommonDBTM $item)
    {
        $restrict = [];

        switch (true) {
            case $item instanceof User:
                $restrict['glpi_problems_users.users_id'] = $item->getID();
                $restrict['glpi_problems_users.type'] = CommonITILActor::REQUESTER;
                break;

            case $item instanceof Supplier:
                $restrict['glpi_problems_suppliers.suppliers_id'] = $item->getID();
                $restrict['glpi_problems_suppliers.type'] = CommonITILActor::ASSIGN;
                break;

            case $item instanceof Group:
                if ($item->haveChildren()) {
                    $tree = Session::getSavedOption(self::class, 'tree', 0);
                } else {
                    $tree = 0;
                }
                $restrict['glpi_groups_problems.groups_id'] = ($tree ? getSonsOf('glpi_groups', $item->getID()) : $item->getID());
                $restrict['glpi_groups_problems.type'] = CommonITILActor::REQUESTER;
                break;

            default:
                $restrict['glpi_items_problems.items_id'] = $item->getID();
                $restrict['glpi_items_problems.itemtype'] = $item->getType();
                // you can only see your tickets
                if (!Session::haveRight(self::$rightname, self::READALL)) {
                    $or = [
                        'glpi_problems.users_id_recipient'   => Session::getLoginUserID(),
                        [
                            'AND' => [
                                'glpi_problems_users.problems_id'  => 'glpi_problems.id',
                                'glpi_problems_users.users_id'    => Session::getLoginUserID(),
                            ],
                        ],
                    ];
                    if (count($_SESSION['glpigroups'])) {
                        $or['glpi_groups_problems.groups_id'] = $_SESSION['glpigroups'];
                    }
                    $restrict[] = ['OR' => $or];
                }
        }

        return $restrict;
    }

    /**
     * @since 0.85
     *
     * @see commonDBTM::getRights()
     **/
    public function getRights($interface = 'central')
    {

        $values = parent::getRights();
        unset($values[READ]);

        $values[self::READALL] = __('See all');
        $values[self::READMY]  = __('See (author)');

        return $values;
    }

    public static function getDefaultValues($entity = 0)
    {
        $default_use_notif = Entity::getUsedConfig('is_notif_enable_default', $_SESSION['glpiactive_entity'], '', 1);
        return [
            '_users_id_requester'        => Session::getLoginUserID(),
            '_users_id_requester_notif'  => [
                'use_notification'  => $default_use_notif,
                'alternative_email' => '',
            ],
            '_groups_id_requester'       => 0,
            '_users_id_assign'           => 0,
            '_users_id_assign_notif'     => [
                'use_notification'  => $default_use_notif,
                'alternative_email' => '',
            ],
            '_groups_id_assign'          => 0,
            '_users_id_observer'         => 0,
            '_users_id_observer_notif'   => [
                'use_notification'  => $default_use_notif,
                'alternative_email' => '',
            ],
            '_suppliers_id_assign_notif' => [
                'use_notification'  => $default_use_notif,
                'alternative_email' => '',
            ],
            '_groups_id_observer'        => 0,
            '_suppliers_id_assign'       => 0,
            'priority'                   => 3,
            'urgency'                    => 3,
            'impact'                     => 3,
            'content'                    => '',
            'name'                       => '',
            'entities_id'                => $_SESSION['glpiactive_entity'],
            'itilcategories_id'          => 0,
            'actiontime'                 => 0,
            'date'                       => 'NULL',
            '_add_validation'            => 0,
            '_validation_targets'        => [],
            '_tasktemplates_id'          => [],
            'items_id'                   => 0,
            '_actors'                    => [],
            'status'                     => self::INCOMING,
            'time_to_resolve'            => 'NULL',
            'itemtype'                   => '',
            'locations_id'               => 0,
            'impactcontent'              => '',
            'causecontent'               => '',
            'symptomcontent'             => '',
        ];
    }

    /**
     * get active problems for an item
     *
     * @since 9.5
     *
     * @param string $itemtype     Item type
     * @param integer $items_id    ID of the Item
     *
     * @return DBmysqlIterator
     */
    public function getActiveProblemsForItem($itemtype, $items_id)
    {
        global $DB;

        return $DB->request([
            'SELECT'    => [
                $this->getTable() . '.id',
                $this->getTable() . '.name',
                $this->getTable() . '.priority',
            ],
            'FROM'      => $this->getTable(),
            'LEFT JOIN' => [
                'glpi_items_problems' => [
                    'ON' => [
                        'glpi_items_problems' => 'problems_id',
                        $this->getTable()    => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_items_problems.itemtype'   => $itemtype,
                'glpi_items_problems.items_id'   => $items_id,
                $this->getTable() . '.is_deleted' => 0,
                'NOT'                         => [
                    $this->getTable() . '.status' => array_merge(
                        static::getSolvedStatusArray(),
                        static::getClosedStatusArray()
                    ),
                ],
            ],
        ]);
    }


    public static function getIcon()
    {
        return "ti ti-alert-triangle";
    }

    public static function getItemLinkClass(): string
    {
        return Item_Problem::class;
    }

    public static function getContentTemplatesParametersClassInstance(): CommonITILObjectParameters
    {
        return new ProblemParameters();
    }
}
