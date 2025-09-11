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
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\DBAL\QuerySubQuery;
use Glpi\DBAL\QueryUnion;
use Glpi\Features\Clonable;
use Glpi\Features\Kanban;
use Glpi\Features\KanbanInterface;
use Glpi\Features\Teamwork;
use Glpi\Features\TeamworkInterface;
use Glpi\Plugin\Hooks;
use Glpi\RichText\RichText;
use Glpi\Team\Team;

/**
 * Project Class
 *
 * @since 0.85
 **/
class Project extends CommonDBTM implements ExtraVisibilityCriteria, KanbanInterface, TeamworkInterface
{
    use Kanban;
    use Clonable;
    use Teamwork;

    // From CommonDBTM
    public $dohistory                   = true;
    protected static $forward_entity_to = ['ProjectCost', 'ProjectTask'];
    public static $rightname                   = 'project';
    protected $usenotepad               = true;

    public const READMY                        = 1;
    public const READALL                       = 1024;

    protected $team                     = [];

    public function getCloneRelations(): array
    {
        return [
            ProjectCost::class,
            ProjectTask::class,
            Document_Item::class,
            ProjectTeam::class,
            Itil_Project::class,
            Contract_Item::class,
            Notepad::class,
            KnowbaseItem_Item::class,
        ];
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Project', 'Projects', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['tools', self::class];
    }

    public static function canView(): bool
    {
        return Session::haveRightsOr(self::$rightname, [self::READALL, self::READMY]);
    }

    /**
     * Is the current user have right to show the current project ?
     *
     * @return boolean
     **/
    public function canViewItem(): bool
    {
        if (!parent::canViewItem()) {
            return false;
        }
        return (Session::haveRight(self::$rightname, self::READALL)
              || (Session::haveRight(self::$rightname, self::READMY)
                  && (
                      ($this->fields["users_id"] === Session::getLoginUserID())
                      || $this->isInTheManagerGroup()
                      || $this->isInTheTeam()
                  ))
        );
    }

    /**
     * Is the current user have right to create the current change ?
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

    public function getRights($interface = 'central')
    {
        $values = parent::getRights();
        unset($values[READ]);

        $values[self::READALL] = __('See all');
        $values[self::READMY]  = __('See (actor)');

        return $values;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (static::canView() && !$withtemplate) {
            $nb = 0;
            switch ($item::class) {
                case self::class:
                    $ong    = [];
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            static::getTable(),
                            [
                                static::getForeignKeyField() => $item->getID(),
                                'is_deleted'                => 0,
                            ]
                        );
                    }
                    $ong[1] = self::createTabEntry(static::getTypeName(Session::getPluralNumber()), $nb, $item::class);
                    $ong[3] = self::createTabEntry(__('Kanban'));
                    return $ong;
            }
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item::class) {
            case self::class:
                switch ($tabnum) {
                    case 1:
                        $item->showChildren();
                        break;

                    case 3:
                        $item->showKanban($item->getID());
                        break;
                }
                break;
        }
        return true;
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addImpactTab($ong, $options);
        $this->addStandardTab(ProjectTask::class, $ong, $options);
        $this->addStandardTab(ProjectTeam::class, $ong, $options);
        $this->addStandardTab(self::class, $ong, $options);
        $this->addStandardTab(ProjectCost::class, $ong, $options);
        $this->addStandardTab(Itil_Project::class, $ong, $options);
        $this->addStandardTab(Item_Project::class, $ong, $options);
        $this->addStandardTab(Document_Item::class, $ong, $options);
        $this->addStandardTab(Contract_Item::class, $ong, $options);
        $this->addStandardTab(Notepad::class, $ong, $options);
        $this->addStandardTab(KnowbaseItem_Item::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public static function getAdditionalMenuContent()
    {
        // No view to project by right on tasks add it
        if (
            !static::canView()
            && Session::haveRight('projecttask', ProjectTask::READMY)
        ) {
            $menu['project']['title'] = self::getTypeName(Session::getPluralNumber());
            $menu['project']['page']  = ProjectTask::getMyTasksURL(false);

            return $menu;
        }
        return false;
    }

    public static function getAdditionalMenuOptions()
    {
        return [
            ProjectTask::class => [
                'title' => __('My tasks'),
                'page'  => ProjectTask::getMyTasksURL(false),
                'links' => [
                    'search' => ProjectTask::getMyTasksURL(false),
                ],
            ],
        ];
    }

    public static function getAdditionalMenuLinks()
    {
        $links = [];
        if (
            static::canView()
            || Session::haveRight('projecttask', ProjectTask::READMY)
        ) {
            $pic_validate = '
            <i class="ti ti-eye-check" title="' . __s('My tasks') . '"></i>
            <span class="d-none d-xxl-block">
               ' . __s('My tasks') . '
            </span>
         ';

            $links[$pic_validate] = ProjectTask::getMyTasksURL(false);

            $links['summary_kanban'] = self::getFormURL(false) . '?showglobalkanban=1';
        }
        if (count($links)) {
            return $links;
        }
        return false;
    }

    public function post_updateItem($history = true)
    {
        global $CFG_GLPI;

        $this->input = $this->addFiles($this->input, [
            'force_update'  => true,
            'name'          => 'content',
        ]);

        if (in_array('auto_percent_done', $this->updates, true) && (int) $this->input['auto_percent_done'] === 1) {
            // Auto-calculate was toggled. Force recalculation of this and parents
            self::recalculatePercentDone($this->getID());
        } else {
            if ($this->fields['projects_id'] > 0) {
                // Update parent percent_done
                self::recalculatePercentDone($this->fields['projects_id']);
            }
        }

        if (isset($this->input['_old_projects_id'])) {
            // Recalculate previous parent percent done
            self::recalculatePercentDone($this->input['_old_projects_id']);
        }

        if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
            // Read again project to be sure that all data are up to date
            $this->getFromDB($this->fields['id']);
            NotificationEvent::raiseEvent("update", $this);
        }
    }

    public function post_addItem()
    {
        global $CFG_GLPI;

        $this->input = $this->addFiles($this->input, [
            'force_update'  => true,
            'name'          => 'content',
        ]);

        // Update parent percent_done
        if (isset($this->fields['projects_id']) && $this->fields['projects_id'] > 0) {
            self::recalculatePercentDone($this->fields['projects_id']);
        }

        if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
            // Clean reload of the project
            $this->getFromDB($this->fields['id']);

            NotificationEvent::raiseEvent('new', $this);
        }
    }

    public function post_deleteItem()
    {
        // Update parent percent_done
        if ($this->fields['projects_id'] > 0) {
            self::recalculatePercentDone($this->fields['projects_id']);
        }
    }

    public function post_restoreItem()
    {
        // Update parent percent_done
        if ($this->fields['projects_id'] > 0) {
            self::recalculatePercentDone($this->fields['projects_id']);
        }
    }

    public function post_getEmpty()
    {
        $this->fields['priority']     = 3;
        $this->fields['percent_done'] = 0;

        // Set as manager to be able to see it after creation
        if (!Session::haveRight(self::$rightname, self::READALL)) {
            $this->fields['users_id'] = Session::getLoginUserID();
        }
    }

    public function post_getFromDB()
    {
        // Team
        $this->team = ProjectTeam::getTeamFor($this->fields['id']);
    }

    public function pre_deleteItem()
    {
        global $CFG_GLPI;

        if (!isset($this->input['_disablenotif']) && $CFG_GLPI['use_notifications']) {
            NotificationEvent::raiseEvent('delete', $this);
        }
        return true;
    }

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                Item_Project::class,
                Itil_Project::class,
                ProjectCost::class,
                ProjectTask::class,
                ProjectTeam::class,
            ]
        );

        parent::cleanDBonPurge();
    }

    /**
     * Return visibility joins to add to DBIterator parameters
     *
     * @since 9.4
     *
     * @param boolean $forceall force all joins (false by default)
     *
     * @return array
     */
    public static function getVisibilityCriteria(bool $forceall = false): array
    {
        if (Session::haveRight('project', self::READALL)) {
            return [
                'LEFT JOIN' => [],
                'WHERE' => [],
            ];
        }

        $join = [];
        $where = [];

        $join['glpi_projectteams'] = [
            'ON' => [
                'glpi_projectteams'  => 'projects_id',
                'glpi_projects'      => 'id',
            ],
        ];

        $teamtable = 'glpi_projectteams';
        $ors = [
            'glpi_projects.users_id'   => Session::getLoginUserID(),
            [
                "$teamtable.itemtype"   => 'User',
                "$teamtable.items_id"   => Session::getLoginUserID(),
            ],
        ];
        if (count($_SESSION['glpigroups'])) {
            $ors['glpi_projects.groups_id'] = $_SESSION['glpigroups'];
            $ors[] = [
                "$teamtable.itemtype"   => 'Group',
                "$teamtable.items_id"   => $_SESSION['glpigroups'],
            ];
        }

        $where[] = [
            'OR' => $ors,
        ];

        $criteria = [
            'LEFT JOIN' => $join,
            'WHERE'     => $where,
        ];

        return $criteria;
    }

    /**
     * Is the current user in the team?
     *
     * @return boolean
     **/
    public function isInTheTeam()
    {
        if (isset($this->team['User']) && count($this->team['User'])) {
            foreach ($this->team['User'] as $data) {
                if ((int) $data['items_id'] === Session::getLoginUserID()) {
                    return true;
                }
            }
        }

        if (
            isset($_SESSION['glpigroups'], $this->team['Group'])
            && count($_SESSION['glpigroups']) && count($this->team['Group'])
        ) {
            foreach ($_SESSION['glpigroups'] as $groups_id) {
                foreach ($this->team['Group'] as $data) {
                    if ((int) $data['items_id'] === (int) $groups_id) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Is the current user in manager group?
     *
     * @return boolean
     **/
    public function isInTheManagerGroup()
    {
        if (
            isset($_SESSION['glpigroups']) && count($_SESSION['glpigroups'])
            && $this->fields['groups_id']
        ) {
            foreach ($_SESSION['glpigroups'] as $groups_id) {
                if ((int) $this->fields['groups_id'] === (int) $groups_id) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get team member count
     *
     * @return integer
     **/
    public function getTeamCount()
    {
        $nb = 0;
        if (is_array($this->team) && count($this->team)) {
            foreach ($this->team as $val) {
                $nb += count($val);
            }
        }
        return $nb;
    }

    public function rawSearchOptions()
    {
        global $DB;

        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
            'forcegroupby'       => true,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => static::getTable(),
            'field'              => 'code',
            'name'               => __('Code'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Father'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
            'joinparams'         => [
                'condition'       => [new QueryExpression('true')], // Add virtual condition to relink table
            ],
        ];

        $tab[] = [
            'id'                 => '21',
            'table'              => static::getTable(),
            'field'              => 'content',
            'name'               => __('Description'),
            'massiveaction'      => false,
            'datatype'           => 'text',
            'htmltext'           => true,
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => static::getTable(),
            'field'              => 'priority',
            'name'               => __('Priority'),
            'searchtype'         => 'equals',
            'datatype'           => 'specific',
        ];

        $tab[] = [
            'id'                 => '14',
            'table'              => 'glpi_projecttypes',
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => 'glpi_projectstates',
            'field'              => 'name',
            'name'               => _n('State', 'States', 1),
            'datatype'           => 'dropdown',
            'additionalfields'   => ['color'],
        ];

        $tab[] = [
            'id'                 => '15',
            'table'              => static::getTable(),
            'field'              => 'date',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => static::getTable(),
            'field'              => 'percent_done',
            'name'               => __('Percent done'),
            'datatype'           => 'number',
            'unit'               => '%',
            'min'                => 0,
            'max'                => 100,
            'step'               => 5,
        ];

        $plugin = new Plugin();
        if ($plugin->isActivated('gantt')) {
            $tab[] = [
                'id'                 => '6',
                'table'              => static::getTable(),
                'field'              => 'show_on_global_gantt',
                'name'               => __('Show on global Gantt'),
                'datatype'           => 'bool',
            ];
        }

        $tab[] = [
            'id'                 => '24',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id',
            'name'               => _n('Manager', 'Managers', 1),
            'datatype'           => 'dropdown',
            'right'              => 'see_project',
        ];

        $tab[] = [
            'id'                 => '49',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'linkfield'          => 'groups_id',
            'name'               => __('Manager group'),
            'condition'          => ['is_manager' => 1],
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => static::getTable(),
            'field'              => 'plan_start_date',
            'name'               => __('Planned start date'),
            'datatype'           => 'datetime',
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => static::getTable(),
            'field'              => 'plan_end_date',
            'name'               => __('Planned end date'),
            'datatype'           => 'datetime',
        ];

        $tab[] = [
            'id'                 => '17',
            'table'              => static::getTable(),
            'field'              => '_virtual_planned_duration',
            'name'               => __('Planned duration'),
            'datatype'           => 'specific',
            'nosearch'           => true,
            'massiveaction'      => false,
            'nosort'             => true,
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => static::getTable(),
            'field'              => 'real_start_date',
            'name'               => __('Real start date'),
            'datatype'           => 'datetime',
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => static::getTable(),
            'field'              => 'real_end_date',
            'name'               => __('Real end date'),
            'datatype'           => 'datetime',
        ];

        $tab[] = [
            'id'                 => '18',
            'table'              => static::getTable(),
            'field'              => '_virtual_effective_duration',
            'name'               => __('Effective duration'),
            'datatype'           => 'specific',
            'nosearch'           => true,
            'massiveaction'      => false,
            'nosort'             => true,
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => static::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => static::getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '50',
            'table'              => static::getTable(),
            'field'              => 'template_name',
            'name'               => __('Template name'),
            'datatype'           => 'text',
            'massiveaction'      => false,
            'nosearch'           => true,
            'nodisplay'          => true,
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => static::getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '86',
            'table'              => static::getTable(),
            'field'              => 'is_recursive',
            'name'               => __('Child entities'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '91',
            'table'              => ProjectCost::getTable(),
            'field'              => 'totalcost',
            'name'               => __('Total cost'),
            'datatype'           => 'decimal',
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
                'specific_itemtype'  => 'ProjectCost',
                'condition'          => ['NEWTABLE.projects_id' => new QueryExpression($DB::quoteName('REFTABLE.id'))],
                'beforejoin'         => [
                    'table'        => static::getTable(),
                    'joinparams'   => [
                        'jointype'  => 'child',
                    ],
                ],
            ],
            'computation'        => QueryFunction::sum('TABLE.cost'),
            'nometa'             => true, // cannot GROUP_CONCAT a SUM
        ];

        $itil_count_types = [
            'Change'  => _x('quantity', 'Number of changes'),
            'Problem' => _x('quantity', 'Number of problems'),
            'Ticket'  => _x('quantity', 'Number of tickets'),
        ];
        $index = 92;
        foreach ($itil_count_types as $itil_type => $label) {
            $tab[] = [
                'id'                 => $index,
                'table'              => Itil_Project::getTable(),
                'field'              => 'id',
                'name'               => $label,
                'datatype'           => 'count',
                'forcegroupby'       => true,
                'usehaving'          => true,
                'massiveaction'      => false,
                'joinparams'         => [
                    'jointype'           => 'child',
                    'condition'          => ['NEWTABLE.itemtype' => $itil_type],
                ],
            ];
            $index++;
        }

        $tab[] = [
            'id'                 => 'project_team',
            'name'               => ProjectTeam::getTypeName(),
        ];

        $tab[] = [
            'id'                 => '87',
            'table'              => User::getTable(),
            'field'              => 'name',
            'name'               => User::getTypeName(2),
            'forcegroupby'       => true,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'jointype'          => 'itemtype_item_revert',
                'specific_itemtype' => 'User',
                'beforejoin'        => [
                    'table'      => ProjectTeam::getTable(),
                    'joinparams' => [
                        'jointype' => 'child',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '88',
            'table'              => Group::getTable(),
            'field'              => 'completename',
            'name'               => Group::getTypeName(2),
            'forcegroupby'       => true,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'jointype'          => 'itemtype_item_revert',
                'specific_itemtype' => 'Group',
                'beforejoin'        => [
                    'table'      => ProjectTeam::getTable(),
                    'joinparams' => [
                        'jointype' => 'child',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '89',
            'table'              => Supplier::getTable(),
            'field'              => 'name',
            'name'               => Supplier::getTypeName(2),
            'forcegroupby'       => true,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'jointype'          => 'itemtype_item_revert',
                'specific_itemtype' => 'Supplier',
                'beforejoin'        => [
                    'table'      => ProjectTeam::getTable(),
                    'joinparams' => [
                        'jointype' => 'child',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '90',
            'table'              => Contact::getTable(),
            'field'              => 'name',
            'name'               => Contact::getTypeName(2),
            'forcegroupby'       => true,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'jointype'          => 'itemtype_item_revert',
                'specific_itemtype' => 'Contact',
                'beforejoin'        => [
                    'table'      => ProjectTeam::getTable(),
                    'joinparams' => [
                        'jointype' => 'child',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => 'project_task',
            'name'               => ProjectTask::getTypeName(),
        ];

        $tab[] = [
            'id'                 => '111',
            'table'              => ProjectTask::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'string',
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'splititems'         => true,
            'joinparams'         => [
                'jointype'  => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => '112',
            'table'              => ProjectTask::getTable(),
            'field'              => 'content',
            'name'               => __('Description'),
            'datatype'           => 'text',
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'splititems'         => true,
            'joinparams'         => [
                'jointype'  => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => '113',
            'table'              => ProjectState::getTable(),
            'field'              => 'name',
            'name'               => _x('item', 'State'),
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'splititems'         => true,
            'additionalfields'   => ['color'],
            'joinparams'         => [
                'jointype'          => 'item_revert',
                'specific_itemtype' => 'ProjectState',
                'beforejoin'        => [
                    'table'      => ProjectTask::getTable(),
                    'joinparams' => [
                        'jointype' => 'child',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '114',
            'table'              => ProjectTaskType::getTable(),
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'splititems'         => true,
            'joinparams'         => [
                'jointype'          => 'item_revert',
                'specific_itemtype' => 'ProjectTaskType',
                'beforejoin'        => [
                    'table'      => ProjectTask::getTable(),
                    'joinparams' => [
                        'jointype' => 'child',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '115',
            'table'              => ProjectTask::getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'splititems'         => true,
            'joinparams'         => [
                'jointype'  => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => '116',
            'table'              => ProjectTask::getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'splititems'         => true,
            'joinparams'         => [
                'jointype'  => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => '117',
            'table'              => ProjectTask::getTable(),
            'field'              => 'percent_done',
            'name'               => __('Percent done'),
            'datatype'           => 'number',
            'unit'               => '%',
            'min'                => 0,
            'max'                => 100,
            'step'               => 5,
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'splititems'         => true,
            'joinparams'         => [
                'jointype'  => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => '118',
            'table'              => ProjectTask::getTable(),
            'field'              => 'plan_start_date',
            'name'               => __('Planned start date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'splititems'         => true,
            'joinparams'         => [
                'jointype'  => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => '1400',
            'table'              => ProjectTask::getTable(),
            'field'              => 'plan_end_date',
            'name'               => __('Planned end date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'splititems'         => true,
            'joinparams'         => [
                'jointype'  => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => '120',
            'table'              => ProjectTask::getTable(),
            'field'              => 'real_start_date',
            'name'               => __('Real start date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'splititems'         => true,
            'joinparams'         => [
                'jointype'  => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => '122',
            'table'              => ProjectTask::getTable(),
            'field'              => 'real_end_date',
            'name'               => __('Real end date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'splititems'         => true,
            'joinparams'         => [
                'jointype'  => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => '123',
            'table'              => ProjectTask::getTable(),
            'field'              => 'planned_duration',
            'name'               => __('Planned Duration'),
            'datatype'           => 'timestamp',
            'min'                => 0,
            'max'                => 100 * HOUR_TIMESTAMP,
            'step'               => HOUR_TIMESTAMP,
            'addfirstminutes'    => true,
            'inhours'            => true,
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'splititems'         => true,
            'joinparams'         => [
                'jointype'  => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => '124',
            'table'              => ProjectTask::getTable(),
            'field'              => 'effective_duration',
            'name'               => __('Effective duration'),
            'datatype'           => 'timestamp',
            'min'                => 0,
            'max'                => 100 * HOUR_TIMESTAMP,
            'step'               => HOUR_TIMESTAMP,
            'addfirstminutes'    => true,
            'inhours'            => true,
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'splititems'         => true,
            'joinparams'         => [
                'jointype'  => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => '125',
            'table'              => ProjectTask::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'splititems'         => true,
            'joinparams'         => [
                'jointype'  => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => '126',
            'table'              => ProjectTask::getTable(),
            'field'              => 'is_milestone',
            'name'               => __('Milestone'),
            'datatype'           => 'bool',
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'splititems'         => true,
            'joinparams'         => [
                'jointype'  => 'child',
            ],
        ];

        // add objectlock search options
        $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        return $tab;
    }

    /**
     * @return array{columns: array, formatters: array} Array of columns and formatters to be used in datatables (templates/components/datatable.html.twig)
     * @see Project::getDatatableEntries()
     * @note If the columns are changed, you must also update the `getDatatableEntries` method to match the new columns.
     */
    final public static function getCommonDatatableColumns(): array
    {
        $columns = [
            'name' => __('Name'),
            'status' => __('Status'),
            'date' => _n('Date', 'Dates', 1),
            'date_mod' => __('Last update'),
        ];
        if (Session::isMultiEntitiesMode()) {
            $columns['entity'] = Entity::getTypeName(1);
        }
        $columns['priority'] = __('Priority');
        $columns['users_id'] = _n('Manager', 'Managers', 1);
        $columns['groups_id'] = __('Manager group');

        return [
            'columns' => $columns,
            'formatters' => [
                'name' => 'raw_html',
                'status' => 'badge',
                'date' => 'datetime',
                'date_mod' => 'datetime',
                'priority' => 'badge',
                'users_id' => 'raw_html',
                'groups_id' => 'raw_html',
            ],
        ];
    }

    /**
     * @param array{item_id: int, id: int}[] $data
     *        - item_id: The ID of the Project
     *        - id: The ID of the entry in the datatable (probably the ID of the link between the Project and another item)
     *        - itemtype: The type of the entry in the datatable (Project or a link itemtype between the Project and another item)
     * @return array The data with the other required fields added
     * @see Project::getCommonDatatableColumns()
     */
    public static function getDatatableEntries(array $data): array
    {
        global $DB;

        $item = new static();
        $state_iterator = $DB->request([
            'SELECT' => ['id', 'color'],
            'FROM'   => 'glpi_projectstates',
        ]);
        $state_colors = [];
        foreach ($state_iterator as $state) {
            $state_colors[$state['id']] = $state['color'];
        }

        $entities = [];
        $users = [];
        $groups = [];
        $user = new User();

        foreach ($data as &$entry) {
            $item->getFromDB($entry['item_id']);
            $entry['name'] = $item->getLink();
            $entry['status'] = [
                'content' => Dropdown::getDropdownName('glpi_projectstates', $item->fields['projectstates_id']),
                'color' => $state_colors[$item->fields['projectstates_id']] ?? '',
            ];
            $entry['date'] = Html::convDateTime($item->fields['date']);
            $entry['date_mod'] = Html::convDateTime($item->fields['date_mod']);
            if (Session::isMultiEntitiesMode()) {
                if (!isset($entities[$item->fields['entities_id']])) {
                    $entities[$item->fields['entities_id']] = Dropdown::getDropdownName('glpi_entities', $item->fields['entities_id']);
                }
                $entry['entity'] = $entities[$item->fields['entities_id']];
            }
            $entry['priority'] = [
                'content' => CommonITILObject::getPriorityName($item->fields["priority"]),
                'color' => $_SESSION["glpipriority_" . $item->fields["priority"]],
            ];
            if ($item->fields['users_id']) {
                if (!isset($users[$item->fields['users_id']])) {
                    $user->getFromDB($item->fields['users_id']);
                    $users[$item->fields['users_id']] = sprintf(
                        __s('%1$s %2$s'),
                        htmlescape($user->getName()),
                        Html::showToolTip(
                            $user->getInfoCard(),
                            [
                                'link'    => $user->getLinkURL(),
                                'display' => false,
                            ]
                        )
                    );
                }
                $entry['users_id'] = $users[$item->fields['users_id']];
            }
            if ($item->fields['groups_id']) {
                if (!isset($groups[$item->fields['groups_id']])) {
                    $groups[$item->fields['groups_id']] = sprintf(
                        __s('%1$s %2$s'),
                        htmlescape(Dropdown::getDropdownName('glpi_groups', $item->fields["groups_id"])),
                        Html::showToolTip(
                            Dropdown::getDropdownComments('glpi_groups', $item->fields["groups_id"]),
                            ['display' => false]
                        )
                    );
                }
                $entry['groups_id'] = $groups[$item->fields['groups_id']];
            }
        }

        return $data;
    }

    public function prepareInputForAdd($input)
    {
        if (isset($input["id"]) && ($input["id"] > 0)) {
            $input["_oldID"] = $input["id"];
        }
        if (isset($input['withtemplate']) && (int) $input['withtemplate'] === 2) {
            // Remove dates for template from input. Keep date_creation because it can be overridden
            unset($input['date'], $input['date_mod']);
        }
        unset($input['id'], $input['withtemplate']);

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        if (isset($input['auto_percent_done']) && $input['auto_percent_done']) {
            unset($input['percent_done']);
        }
        if (isset($input['projects_id']) && $input['projects_id'] > 0) {
            if (self::checkCircularRelation($input['id'], $input['projects_id'])) {
                Session::addMessageAfterRedirect(
                    __s('Circular relation found. Parent not updated.'),
                    false,
                    ERROR
                );
                unset($input['projects_id']);
            }
        }
        if (
            $this->fields['projects_id'] > 0 && isset($input['projects_id'])
            && ((int) $input['projects_id'] !== (int) $this->fields['projects_id'])
        ) {
            $input['_old_projects_id'] = $this->fields['projects_id'];
        }
        return self::checkPlanAndRealDates($input);
    }

    public static function checkPlanAndRealDates($input)
    {
        if (
            !empty($input['plan_start_date']) && !empty($input['plan_end_date'])
            && (($input['plan_end_date'] < $input['plan_start_date']))
        ) {
            Session::addMessageAfterRedirect(
                __s('Invalid planned dates. Dates not updated.'),
                false,
                ERROR
            );
            unset($input['plan_start_date'], $input['plan_end_date']);
        }
        if (
            !empty($input['real_start_date']) && !empty($input['real_end_date'])
            && (($input['real_end_date'] < $input['real_start_date']))
        ) {
            Session::addMessageAfterRedirect(
                __s('Invalid real dates. Dates not updated.'),
                false,
                ERROR
            );
            unset($input['real_start_date'], $input['real_end_date']);
        }
        return $input;
    }

    /**
     * Print the HTML array children of a TreeDropdown
     *
     * @return void
     **/
    public function showChildren()
    {
        global $DB;

        $ID   = $this->getID();
        $this->check($ID, READ);

        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => static::getTable(),
            'WHERE'  => [
                static::getForeignKeyField()   => $ID,
                'is_deleted'                  => 0,
            ],
        ]);
        $canedit = $this->can($ID, UPDATE);
        $entries_to_fetch = [];

        if ($canedit) {
            // langauge=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <div class="mb-3">
                    <a class="btn btn-primary" href="{{ 'Project'|itemtype_form_path() }}?projects_id={{ projects_id }}">{{ label }}</a>
                </div>
TWIG, ['projects_id' => $ID, 'label' => __('Create a sub project from this project')]);
        }

        foreach ($iterator as $data) {
            $entries_to_fetch[] = [
                'item_id' => $ID,
                'id' => $data['id'],
                'itemtype' => static::class,
            ];
        }

        $header = self::getCommonDatatableColumns();
        $entries = self::getDatatableEntries($entries_to_fetch);
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => $header['columns'],
            'formatters' => $header['formatters'],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . mt_rand(),
            ],
        ]);
    }

    /**
     * Print the Project form
     *
     * @param integer $ID ID of the item
     * @param array $options
     *     - target for the Form
     *     - withtemplate : 1 for newtemplate, 2 for newobject from template
     *
     * @return bool true if displayed  false if item not found or not right to display
     **/
    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        $plugin = new Plugin();

        TemplateRenderer::getInstance()->display('pages/tools/project.html.twig', [
            'item' => $this,
            'params' => $options + ['formfooter' => false],
            'gantt_plugin_enabled' => $plugin->isActivated('gantt'),
            'planned_duration' => ProjectTask::getTotalPlannedDurationForProject($this->fields['id']),
            'effective_duration' => ProjectTask::getTotalEffectiveDurationForProject($this->fields['id']),
        ]);

        return true;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        return match ($field) {
            'priority' => htmlescape(CommonITILObject::getPriorityName($values[$field])),
            default => parent::getSpecificValueToDisplay($field, $values, $options),
        };
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;

        switch ($field) {
            case 'priority':
                $options['name']      = $name;
                $options['value']     = $values[$field];
                $options['withmajor'] = 1;
                return CommonITILObject::dropdownPriority($options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * Show team for a project
     **/
    public function showTeam(Project $project)
    {
        $ID      = $project->fields['id'];
        $canedit = $project->can($ID, UPDATE);

        if ($canedit) {
            $twig_params = [
                'id' => $ID,
                'label' => __('Add a team member'),
                'btn_label' => _x('button', 'Add'),
                'dropdown_params' => [
                    'itemtypes'       => ProjectTeam::$available_types,
                    'entity_restrict' => ($project->fields['is_recursive']
                        ? getSonsOf(
                            'glpi_entities',
                            $project->fields['entities_id']
                        )
                        : $project->fields['entities_id']),
                    'checkright'      => true,
                ],
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                <div class="mb-3">
                    <form method="post" action="{{ 'ProjectTeam'|itemtype_form_path }}">
                        <div class="d-flex">
                            <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="projects_id" value="{{ id }}">
                            {{ fields.dropdownItemsFromItemtypes('items_id', label, dropdown_params) }}
                        </div>
                        <div class="d-flex flex-row-reverse">
                            <button type="submit" name="add" class="btn btn-primary">{{ btn_label }}</button>
                        </div>
                    </form>
                </div>
TWIG, $twig_params);
        }

        $entries = [];
        foreach (ProjectTeam::$available_types as $type) {
            if (isset($project->team[$type]) && count($project->team[$type])) {
                if ($item = getItemForItemtype($type)) {
                    foreach ($project->team[$type] as $data) {
                        $item->getFromDB($data['items_id']);
                        $entries[] = [
                            'itemtype' => 'ProjectTeam',
                            'id' => $data['id'],
                            'type' => $item::getTypeName(1),
                            'member' => $item->getLink(),
                        ];
                    }
                }
            }
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'type' => _n('Type', 'Types', 1),
                'member' => _n('Member', 'Members', 1),
            ],
            'formatters' => [
                'member' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . mt_rand(),
            ],
        ]);

        return true;
    }

    public static function getAllForKanban($active = true, $current_id = -1)
    {
        global $DB;

        $items = [
            -1 => __('Global'),
        ];
        $criteria = [
            'is_template' => 0,
        ];
        $joins = [];
        if ($active) {
            $criteria += [
                'is_deleted'   => 0,
                [
                    'OR' => [
                        ['is_finished' => 0],
                        ['is_finished' => 'null'],
                    ],
                ],
            ];
            $joins = [
                'glpi_projectstates' => [
                    'FKEY' => [
                        'glpi_projectstates' => 'id',
                        'glpi_projects'      => 'projectstates_id',
                    ],
                ],
            ];
        }
        $criteria += getEntitiesRestrictCriteria(self::getTable(), '', '', 'auto');
        $iterator = $DB->request(array_merge_recursive([
            'SELECT'   => [
                'glpi_projects.id',
                'glpi_projects.name',
                'glpi_projects.is_deleted',
                'glpi_projectstates.is_finished',
            ],
            'DISTINCT' => true,
            'FROM'     => 'glpi_projects',
            'LEFT JOIN' => $joins,
            'WHERE'     => $criteria,
        ], self::getVisibilityCriteria()));
        foreach ($iterator as $data) {
            $items[$data['id']] = $data['name'];
        }

        if ($current_id > -1 && !isset($items[$current_id])) {
            // Current Kanban is not in the list yet
            $iterator = $DB->request([
                'SELECT'   => [
                    'glpi_projects.id',
                    'glpi_projects.name',
                ],
                'FROM'     => 'glpi_projects',
                'WHERE'     => ['id' => $current_id],
            ]);
            if ($iterator->count()) {
                $data = $iterator->current();
                $items[$data['id']] = $data['name'];
            }
        }
        return $items;
    }

    public static function getAllKanbanColumns($column_field = null, $column_ids = [], $get_default = false)
    {
        $result = [];

        if ($column_field === null || $column_field === 'projectstates_id') {
            global $DB;

            $restrict = [];
            if (!empty($column_ids) && !$get_default) {
                $restrict = ['id' => $column_ids];
            }

            $addselect = [];
            $ljoin = [];
            if (Session::haveTranslations(ProjectState::getType(), 'name')) {
                $addselect[] = "namet2.value AS transname";
                $ljoin['glpi_dropdowntranslations AS namet2'] = [
                    'ON' => [
                        'namet2' => 'items_id',
                        ProjectState::getTable()   => 'id', [
                            'AND' => [
                                'namet2.itemtype' => ProjectState::getType(),
                                'namet2.language' => $_SESSION['glpilanguage'],
                                'namet2.field'    => 'name',
                            ],
                        ],
                    ],
                ];
            }

            $criteria = [
                'SELECT'   => array_merge([ProjectState::getTable() . ".*"], $addselect),
                'DISTINCT' => true,
                'FROM'     => ProjectState::getTable(),
                'WHERE'    => $restrict,
            ];
            if (count($ljoin)) {
                $criteria['LEFT JOIN'] = $ljoin;
            }
            $iterator = $DB->request($criteria);

            if (count($iterator)) {
                foreach ($iterator as $projectstate) {
                    $result[$projectstate['id']] = [
                        'name'            => $projectstate['transname'] ?? $projectstate['name'],
                        'id'              => $projectstate['id'],
                        'header_color'    => $projectstate['color'],
                        'header_fg_color' => Toolbox::getFgColor($projectstate['color'], 50),
                    ];
                }
            }

            // sort by name ASC
            uasort($result, static fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
        }

        return $result;
    }

    public static function getDataToDisplayOnKanban($ID, $criteria = [])
    {
        global $DB;

        $items      = [];

        // Get sub-projects
        $projectteam = new ProjectTeam();
        $project = new Project();
        $project_visibility = self::getVisibilityCriteria();
        $project_visibility['WHERE'] += getEntitiesRestrictCriteria(self::getTable(), '', '', 'auto');

        $required_project_fields = [
            'id', 'name', 'content', 'plan_start_date', 'plan_end_date', 'real_start_date',
            'real_end_date', 'percent_done', 'projects_id', 'projectstates_id', 'is_deleted',
            'date_creation',
        ];
        $request = [
            'SELECT' => [
                'glpi_projectstates.is_finished',
            ],
            'FROM'   => 'glpi_projects',
            'LEFT JOIN' => [
                'glpi_projectstates' => [
                    'FKEY' => [
                        'glpi_projects'   => 'projectstates_id',
                        'glpi_projectstates' => 'id',
                    ],
                ],
            ] + $project_visibility['LEFT JOIN'],
            'WHERE'     => $project_visibility['WHERE'] + [
                'is_template' => 0,
            ],
        ];
        foreach ($required_project_fields as $field) {
            $request['SELECT'][] = 'glpi_projects.' . $field;
        }
        if ($ID > 0) {
            $request['WHERE']['glpi_projects.projects_id'] = $ID;
            $request['WHERE'] += $criteria;
        }

        $iterator = $DB->request($request);
        $projects = [];
        foreach ($iterator as $data) {
            $projects[$data['id']] = $data;
        }
        $project_ids = array_map(
            static fn($e) => $e['id'],
            array_filter($projects, static fn($e)
                // Filter tasks of closed projects in Global view
                => $ID > 0 || !$e['is_finished'])
        );
        $projectteams = count($project_ids) ? $projectteam->find(['projects_id' => $project_ids]) : [];

        // Get sub-tasks
        $projecttask = new ProjectTask();
        $projecttaskteam = new ProjectTaskTeam();
        $project_task_criteria = [
            'is_template' => 0,
            'projects_id' => ($ID <= 0 && count($project_ids)) ? $project_ids : $ID,
        ];
        $projecttasks = $projecttask->find($project_task_criteria + $criteria);
        $projecttask_ids = array_map(static fn($e) => $e['id'], $projecttasks);
        $projecttaskteams = count($projecttask_ids) ? $projecttaskteam->find(['projecttasks_id' => $projecttask_ids]) : [];

        // Build team member data
        /** @var array<class-string<CommonDBTM>, string[]> $supported_teamtypes */
        $supported_teamtypes = [
            'User' => ['id', 'name', 'firstname', 'realname'],
            'Group' => ['id', 'name'],
            'Supplier' => ['id', 'name'],
            'Contact' => ['id', 'name', 'firstname'],
        ];
        $all_members = [];
        foreach ($supported_teamtypes as $itemtype => $fields) {
            $all_ids = array_map(
                static fn($e) => $e['items_id'],
                array_filter(array_merge($projectteams, $projecttaskteams), static fn($e) => $e['itemtype'] === $itemtype)
            );
            if (count($all_ids)) {
                $itemtable = $itemtype::getTable();
                $all_items = $DB->request([
                    'SELECT'    => $fields,
                    'FROM'      => $itemtable,
                    'WHERE'     => [
                        "{$itemtable}.id"   => $all_ids,
                    ],
                ]);
                $all_members[$itemtype] = [];
                foreach ($all_items as $data) {
                    $all_members[$itemtype][] = $data;
                }
            } else {
                $all_members[$itemtype] = [];
            }
        }

        foreach ($projects as $subproject) {
            $item = array_merge($subproject, [
                '_itemtype' => 'Project',
                '_team'     => [],
                '_steps'    => ProjectTask::getAllForProject($subproject['id']),
            ]);
            if ($ID <= 0 && $subproject['projects_id'] > 0) {
                if (isset($projects[$subproject['projects_id']])) {
                    $item['_parents_id'] = $projects[$subproject['projects_id']]['id'];
                    $item['_parent_itemtype'] = 'Project';
                    $item['_parent_name'] = $projects[$subproject['projects_id']]['name'];
                }
            }

            $project->fields = $subproject;
            $item['_readonly'] = !Project::canUpdate() || !$project->canUpdateItem();

            $subproject_teams = array_filter($projectteams, static fn($e) => $e['projects_id'] === $subproject['id']);
            foreach ($subproject_teams as $teammember) {
                switch ($teammember['itemtype']) {
                    case 'Group':
                    case 'Supplier':
                        $matches = array_filter($all_members[$teammember['itemtype']], static fn($e) => $e['id'] === $teammember['items_id']);
                        if (count($matches)) {
                            $item['_team'][] = array_merge($teammember, reset($matches));
                        }
                        break;
                    case 'User':
                    case 'Contact':
                        $contact_matches = array_filter($all_members[$teammember['itemtype']], static fn($e) => $e['id'] === $teammember['items_id']);
                        if (count($contact_matches)) {
                            $match = reset($contact_matches);
                            // contact -> name, user -> realname
                            $realname = $teammember['itemtype'] === 'User' ? $match['realname'] : $match['name'];
                            $name = $teammember['itemtype'] === 'User' ? $match['name'] : '';
                            $match['name'] = formatUserName($match['id'], $name, $realname, $match['firstname']);
                            $item['_team'][] = array_merge($teammember, $match);
                        }
                        break;
                }
            }
            $items[] = $item;
        }

        foreach ($projecttasks as $subtask) {
            $item = array_merge($subtask, [
                '_itemtype' => 'ProjectTask',
                '_team' => [],
                '_steps' => ProjectTask::getAllForProjectTask($subtask['id']),
                'type' => $subtask['projecttasktypes_id'],
            ]);
            if ($ID <= 0) {
                $item['_parents_id'] = $projects[$subtask['projects_id']]['id'];
                $item['_parent_itemtype'] = 'Project';
                $item['_parent_name'] = $projects[$subtask['projects_id']]['name'];
            }

            $projecttask->fields = $subtask;
            $item['_readonly'] = !ProjectTask::canUpdate() || !$projecttask->canUpdateItem();

            $subtask_teams = array_filter($projecttaskteams, static fn($e) => $e['projecttasks_id'] == $subtask['id']);
            foreach ($subtask_teams as $teammember) {
                switch ($teammember['itemtype']) {
                    case 'Group':
                    case 'Supplier':
                        $matches = array_filter($all_members[$teammember['itemtype']], static fn($e) => $e['id'] === $teammember['items_id']);
                        if (count($matches)) {
                            $item['_team'][] = array_merge($teammember, reset($matches));
                        }
                        break;
                    case 'User':
                    case 'Contact':
                        $contact_matches = array_filter($all_members[$teammember['itemtype']], static fn($e) => $e['id'] === $teammember['items_id']);
                        if (count($contact_matches)) {
                            $match = reset($contact_matches);
                            if ($teammember['itemtype'] === 'User') {
                                $match['name'] = formatUserName($match['id'], $match['name'], $match['realname'], $match['firstname']);
                            } else {
                                $match['name'] = formatUserName($match['id'], '', $match['name'], $match['firstname']);
                            }
                            $item['_team'][] = array_merge($teammember, $match);
                        }
                        break;
                }
            }
            $items[] = $item;
        }

        return $items;
    }

    public static function getKanbanColumns($ID, $column_field = null, $column_ids = [], $get_default = false)
    {
        // TODO Make this function only return the card data and leave rendering to Vue components. This will deduplicate the data between display and filters.
        if ($column_field !== 'projectstates_id') {
            return [];
        }

        $columns = [];
        if (empty($column_ids) || $get_default || in_array(0, $column_ids, false)) {
            $columns[0] = [
                'name'         => __('No status'),
                '_protected'   => true,
            ];
        }
        $criteria = [];
        if (!empty($column_ids)) {
            $criteria = [
                'projectstates_id'   => $column_ids,
            ];
        }
        $items      = self::getDataToDisplayOnKanban($ID, $criteria);

        $projecttasktype = new ProjectTaskType();
        $alltypes = $projecttasktype->find();

        $extracolumns = self::getAllKanbanColumns('projectstates_id', $column_ids, $get_default);
        foreach ($extracolumns as $column_id => $column) {
            $columns[$column_id] = $column;
        }

        foreach ($items as $item) {
            if (!array_key_exists($item['projectstates_id'], $columns)) {
                continue;
            }
            $itemtype = $item['_itemtype'];
            $card = [
                'id'              => "{$itemtype}-{$item['id']}",
                'title'           => $item['name'],
            ];

            $content = "<div class='kanban-plugin-content'>";
            $plugin_content_pre = Plugin::doHookFunction(Hooks::PRE_KANBAN_CONTENT, [
                'itemtype' => $itemtype,
                'items_id' => $item['id'],
            ]);
            if (!empty($plugin_content_pre['content'])) {
                $content .= $plugin_content_pre['content'];
            }
            $content .= "</div>";
            // Core content
            $content .= "<div class='kanban-core-content'>";
            if (isset($item['_parents_id'])) {
                $childref = $itemtype === 'Project' ? __('Subproject') : __('Subtask');
                $parentname = $item['_parent_name'] ?? $item['_parents_id'];

                $content .= "<div>";
                $content .= sprintf(
                    '<a href="%1$s">%2$s</a>',
                    htmlescape(Project::getFormURLWithID($item['_parents_id'])),
                    htmlescape(sprintf(__('%s of %s'), $childref, $parentname))
                );
                $content .= "</div>";
            }
            $content .= "<div class='flex-break'></div>";
            if ($itemtype === 'ProjectTask' && $item['projecttasktypes_id'] !== 0) {
                $typematches = array_filter($alltypes, static fn($t) => $t['id'] === $item['projecttasktypes_id']);
                $content .= htmlescape(reset($typematches)['name']) . '&nbsp;';
            }
            if (array_key_exists('is_milestone', $item) && $item['is_milestone']) {
                $content .= "&nbsp;<i class='ti ti-directions-filled' title='" . __s('Milestone') . "'></i>&nbsp;";
            }
            if (isset($item['_steps']) && count($item['_steps'])) {
                $done = count(array_filter($item['_steps'], static fn($step) => (int) $step['percent_done'] === 100));
                $total = count($item['_steps']);
                $content .= "<div class='flex-break'></div>";
                $content .= sprintf(__s('%s / %s tasks complete'), $done, $total);
            }
            // Percent Done
            $content .= "<div class='flex-break'></div>";
            $content .= Html::progress(100, $item['percent_done']);

            $content .= "</div>";
            $content .= "<div class='kanban-plugin-content'>";
            $plugin_content_post = Plugin::doHookFunction(Hooks::POST_KANBAN_CONTENT, [
                'itemtype' => $itemtype,
                'items_id' => $item['id'],
            ]);
            if (!empty($plugin_content_post['content'])) {
                $content .= $plugin_content_post['content'];
            }
            $content .= "</div>";

            $card['content'] = $content;
            $card['_team'] = $item['_team'];
            $card['_readonly'] = $item['_readonly'];
            $card['_form_link'] = $itemtype::getFormUrlWithID($item['id']);
            $card['_metadata'] = [];
            $card['due_date'] = $item['plan_end_date'] ? Html::convDateTime($item['plan_end_date']) : '';
            $metadata_values = ['name', 'content', 'is_milestone', 'plan_start_date', 'plan_end_date', 'real_start_date', 'real_end_date',
                'planned_duration', 'effective_duration', 'percent_done', 'is_deleted', 'date_creation',
            ];
            foreach ($metadata_values as $metadata_value) {
                if (isset($item[$metadata_value])) {
                    $card['_metadata'][$metadata_value] = $item[$metadata_value];
                }
            }
            if (isset($card['_metadata']['content']) && is_string($card['_metadata']['content'])) {
                $card['_metadata']['content'] = RichText::getTextFromHtml(content: $card['_metadata']['content'], preserve_line_breaks: true);
            } else {
                $card['_metadata']['content'] = '';
            }
            $card['_metadata'] = Plugin::doHookFunction(Hooks::KANBAN_ITEM_METADATA, [
                'itemtype' => $itemtype,
                'items_id' => $item['id'],
                'metadata' => $card['_metadata'],
            ])['metadata'];
            $columns[$item['projectstates_id']]['items'][] = $card;
        }

        foreach (array_keys($columns) as $column_id) {
            if ($column_id !== 0 && !in_array($column_id, $column_ids)) {
                unset($columns[$column_id]);
            }
        }
        return $columns;
    }

    public function canModifyGlobalState()
    {
        // Only project manager (or managing group) may change the Kanban's state
        return $this->fields["users_id"] === Session::getLoginUserID() || $this->isInTheManagerGroup();
    }

    public function forceGlobalState()
    {
        // All users must be using the global state unless viewing the global Kanban
        return $this->getID() > 0;
    }

    /**
     * Show Kanban view.
     * @param int $ID ID of the parent Project or 0 for a global view.
     * @return bool|void False if the Kanban cannot be shown.
     */
    public static function showKanban($ID)
    {
        $project = new Project();
        if (
            ($ID <= 0 && !self::canView())
            || ($ID > 0 && (!$project->getFromDB($ID) || !$project->canViewItem()))
        ) {
            return false;
        }

        $supported_itemtypes = [];
        $team_role_ids = static::getTeamRoles();
        $team_roles = [];

        foreach ($team_role_ids as $role_id) {
            $team_roles[$role_id] = static::getTeamRoleName($role_id);
        }
        // Owner cannot be set from the Kanban view yet because it is a special case (One owner user and one owner group)
        unset($team_roles[Team::ROLE_OWNER]);

        $supported_itemtypes['Project'] = [
            'name'   => self::getTypeName(1),
            'icon'   => self::getIcon(),
            'fields' => [
                'projects_id'  => [
                    'type'   => 'hidden',
                    'value'  => $ID,
                ],
                'name'   => [
                    'placeholder'  => __('Name'),
                ],
                'content'   => [
                    'placeholder'  => __('Content'),
                    'type'         => 'textarea',
                ],
                'users_id'  => [
                    'type'         => 'hidden',
                    'value'        => $_SESSION['glpiID'],
                ],
                'entities_id' => [
                    'type'   => 'hidden',
                    'value'  => $ID > 0 ? $project->fields["entities_id"] : $_SESSION['glpiactive_entity'],
                ],
                'is_recursive' => [
                    'type'   => 'hidden',
                    'value'  => $ID > 0 ? $project->fields["is_recursive"] : 0,
                ],
            ],
            'team_itemtypes'  => self::getTeamItemtypes(),
            'team_roles'      => $team_roles,
            'allow_create'    => self::canCreate(),
        ];

        $team_role_ids = static::getTeamRoles();
        $team_roles = [];

        foreach ($team_role_ids as $role_id) {
            $team_roles[$role_id] = static::getTeamRoleName($role_id);
        }
        // Owner cannot be set from the Kanban view yet because it is a special case (One owner user and one owner group)
        unset($team_roles[Team::ROLE_OWNER]);

        $supported_itemtypes['ProjectTask'] = [
            'name'   => ProjectTask::getTypeName(1),
            'icon'   => ProjectTask::getIcon(),
            'fields' => [
                'projects_id'  => [
                    'type'   => 'hidden',
                    'value'  => $ID,
                ],
                'name'   => [
                    'placeholder'  => __('Name'),
                ],
                'content'   => [
                    'placeholder'  => __('Content'),
                    'type'         => 'textarea',
                ],
                'projecttasktemplates_id' => [
                    'type'   => 'hidden',
                    'value'  => 0,
                ],
                'projecttasks_id' => [
                    'type'   => 'hidden',
                    'value'  => 0,
                ],
                'entities_id' => [
                    'type'   => 'hidden',
                    'value'  => $ID > 0 ? $project->fields["entities_id"] : $_SESSION['glpiactive_entity'],
                ],
                'is_recursive' => [
                    'type'   => 'hidden',
                    'value'  => $ID > 0 ? $project->fields["is_recursive"] : 0,
                ],
            ],
            'team_itemtypes'  => ProjectTask::getTeamItemtypes(),
            'team_roles'      => $team_roles,
            'allow_create'    => ProjectTask::canCreate(),
            'allow_bulk_add'  => $ID > 0,
        ];
        if ($ID <= 0) {
            $supported_itemtypes['ProjectTask']['fields']['projects_id'] = [
                'type'   => 'raw',
                'value'  => self::dropdown([
                    'display' => false,
                    'width' => '90%',
                    'condition' => [
                        'LEFT JOIN' => [
                            ProjectState::getTable() => [
                                'ON' => [
                                    ProjectState::getTable() => 'id',
                                    self::getTable() => 'projectstates_id',
                                ],
                            ],
                        ],
                        'WHERE' => [
                            'is_finished'   => false,
                        ],
                    ],
                ]),
            ];
        }
        $column_field = [
            'id' => 'projectstates_id',
            'extra_fields' => [
                'color'  => [
                    'type'   => 'color',
                ],
            ],
        ];

        $canmodify_view = ($ID === 0 || $project->canModifyGlobalState());
        $rights = [
            'create_item'                    => self::canCreate() || ProjectTask::canCreate(),
            'delete_item'                    => self::canDelete() || ProjectTask::canDelete(),
            'create_column'                  => (bool) ProjectState::canCreate(),
            'modify_view'                    => $ID === 0 || $project->canModifyGlobalState(),
            'order_card'                     => (bool) $project->canOrderKanbanCard($ID),
            'create_card_limited_columns'    => $canmodify_view ? [] : [0],
        ];

        TemplateRenderer::getInstance()->display('components/kanban/kanban.html.twig', [
            'kanban_id'                   => 'kanban',
            'rights'                      => $rights,
            'supported_itemtypes'         => $supported_itemtypes,
            'max_team_images'             => 3,
            'column_field'                => $column_field,
            'item'                        => [
                'itemtype'  => 'Project',
                'items_id'  => $ID,
            ],
            'supported_filters'           => [
                'title' => [
                    'description' => _x('filters', 'The title of the item'),
                    'supported_prefixes' => ['!', '#'], // Support exclusions and regex
                ],
                'type' => [
                    'description' => _x('filters', 'The type of the item'),
                    'supported_prefixes' => ['!'], // Support exclusions only
                ],
                'milestone' => [
                    'description' => _x('filters', 'If the item represents a milestone or not'),
                    'supported_prefixes' => ['!'],
                ],
                'content' => [
                    'description' => _x('filters', 'The content of the item'),
                    'supported_prefixes' => ['!', '#'],
                ],
                'deleted' => [
                    'description' => _x('filters', 'If the item is deleted or not'),
                    'supported_prefixes' => ['!'],
                ],
                'team' => [
                    'description' => _x('filters', 'A team member for the item'),
                    'supported_prefixes' => ['!'],
                ],
                'user' => [
                    'description' => _x('filters', 'A user in the team of the item'),
                    'supported_prefixes' => ['!'],
                ],
                'group' => [
                    'description' => _x('filters', 'A group in the team of the item'),
                    'supported_prefixes' => ['!'],
                ],
                'supplier' => [
                    'description' => _x('filters', 'A supplier in the team of the item'),
                    'supported_prefixes' => ['!'],
                ],
                'contact' => [
                    'description' => _x('filters', 'A contact in the team of the item'),
                    'supported_prefixes' => ['!'],
                ],
            ] + self::getKanbanPluginFilters(static::getType()),
        ]);
    }

    public function canOrderKanbanCard($ID)
    {
        if ($ID > 0) {
            $this->getFromDB($ID);
        }
        return ($ID <= 0 || $this->canModifyGlobalState());
    }

    public static function getTeamRoles(): array
    {
        return [
            Team::ROLE_OWNER,
            Team::ROLE_MEMBER,
        ];
    }

    public static function getTeamRoleName(int $role, int $nb = 1): string
    {
        return match ($role) {
            Team::ROLE_OWNER => _n('Manager', 'Managers', $nb),
            Team::ROLE_MEMBER => _n('Member', 'Members', $nb),
            default => '',
        };
    }

    public static function getTeamItemtypes(): array
    {
        return ProjectTeam::$available_types;
    }

    public function addTeamMember(string $itemtype, int $items_id, array $params = []): bool
    {
        $project_team = new ProjectTeam();
        $result = $project_team->add([
            'projects_id'  => $this->getID(),
            'itemtype'     => $itemtype,
            'items_id'     => $items_id,
        ]);
        return (bool) $result;
    }

    public function deleteTeamMember(string $itemtype, int $items_id, array $params = []): bool
    {
        $project_team = new ProjectTeam();
        $result = $project_team->deleteByCriteria([
            'projects_id'  => $this->getID(),
            'itemtype'     => $itemtype,
            'items_id'     => $items_id,
        ]);
        return (bool) $result;
    }

    public function getTeam(): array
    {
        $team = ProjectTeam::getTeamFor($this->getID(), true);
        // Flatten the array
        $result = [];
        foreach ($team as $itemtype_members) {
            foreach ($itemtype_members as $member) {
                $result[] = $member;
            }
        }
        return $result;
    }

    /**
     * Get the list of active projects for a list of groups.
     *
     * @param array $groups_id The group IDs.
     * @param bool $search_in_team Whether to search in the team.
     * @return array The list of project IDs.
     */
    public static function getActiveProjectIDsForGroup(
        array $groups_id,
        bool $search_in_team = true
    ): array {
        global $DB;

        if (count($groups_id) === 0) {
            return [];
        }

        $req = [
            'SELECT' => self::getTable() . '.id',
            'FROM' => self::getTable(),
            'LEFT JOIN' => [
                ProjectState::getTable() => [
                    'FKEY' => [
                        ProjectState::getTable() => 'id',
                        self::getTable() => 'projectstates_id',
                    ],
                ],
            ],
            'WHERE' => [
                ['OR' => ['groups_id' => $groups_id]],
                [
                    'OR' => [
                        [ProjectState::getTable() . '.is_finished' => 0],
                        [ProjectState::getTable() . '.is_finished' => null],
                    ],
                ],
                ['NOT' => ['is_template' => 1]],
            ],
        ];

        if ($search_in_team) {
            $team_sub_query = new QuerySubQuery([
                'SELECT' => [
                    'projects_id',
                ],
                'FROM' => ProjectTeam::getTable(),
                'WHERE' => [
                    'OR' => [
                        ['itemtype' => 'Group', 'items_id' => $groups_id],
                    ],
                ],
            ]);

            $req['WHERE'][0]['OR'][self::getTable() . '.id'] = $team_sub_query;
        }

        return iterator_to_array($DB->request($req), false);
    }

    /**
     * Get the list of active projects for a list of users.
     *
     * @param array $users_id The user IDs.
     * @param bool $search_in_groups Whether to search in groups.
     * @param bool $search_in_team Whether to search in the team.
     * @return array The list of project IDs.
     */
    public static function getActiveProjectIDsForUser(
        array $users_id,
        bool $search_in_groups = true,
        bool $search_in_team = true
    ): array {
        global $DB;

        if (count($users_id) === 0) {
            return [];
        }

        $req = [
            'SELECT' => self::getTable() . '.id',
            'FROM' => self::getTable(),
            'LEFT JOIN' => [
                ProjectState::getTable() => [
                    'FKEY' => [
                        ProjectState::getTable() => 'id',
                        self::getTable() => 'projectstates_id',
                    ],
                ],
            ],
            'WHERE' => [
                ['OR' => ['users_id' => $users_id]],
                [
                    'OR' => [
                        [ProjectState::getTable() . '.is_finished' => 0],
                        [ProjectState::getTable() . '.is_finished' => null],
                    ],
                ],
                ['NOT' => ['is_template' => 1]],
            ],
        ];

        $groups_sub_query = new QuerySubQuery([
            'SELECT' => [
                'groups_id',
            ],
            'FROM' => Group_User::getTable(),
            'WHERE' => [
                'users_id' => $users_id,
            ],
        ]);

        if ($search_in_groups) {
            $req['WHERE'][0]['OR']['groups_id'] = $groups_sub_query;
        }

        if ($search_in_team) {
            $crit = [
                ['itemtype' => 'User', 'items_id' => $users_id],
            ];

            if ($search_in_groups) {
                $crit[] = ['itemtype' => 'Group', 'items_id' => $groups_sub_query];
            }

            $team_sub_query = new QuerySubQuery([
                'SELECT' => [
                    'projects_id',
                ],
                'FROM' => ProjectTeam::getTable(),
                'WHERE' => [
                    'OR' => $crit,
                ],
            ]);

            $req['WHERE'][0]['OR'][self::getTable() . '.id'] = $team_sub_query;
        }

        return iterator_to_array($DB->request($req), false);
    }

    /**
     *  Show the list of projects for a user in the personal view or for a group in the group view
     *
     * @param string $itemtype The itemtype (User or Group)
     * @return void
     * @used-by Central
     */
    public static function showListForCentral(string $itemtype): void
    {
        $projects_id = [];
        switch ($itemtype) {
            case 'User':
                $projects_id = self::getActiveProjectIDsForUser([Session::getLoginUserID()], false, true);
                break;
            case 'Group':
                $projects_id = self::getActiveProjectIDsForGroup($_SESSION['glpigroups']);
                break;
        }

        // If no project are found, do not display anything
        if ($projects_id === []) {
            return;
        }

        $options = [
            'criteria' => [
                [
                    'link' => 'AND',
                    'criteria' => [
                        [
                            'link' => 'AND',
                            'field' => ($itemtype === 'User') ? 87 : 88, // 87 = Project teams - Users, 88 = Project teams - Groups
                            'searchtype' => 'equals',
                            'value' => ($itemtype === 'User') ? 'myself' : 'mygroups',
                        ],
                        [
                            'link' => 'OR',
                            'field' => ($itemtype === 'User') ? 24 : 49, // 24 = Project Manager, 49 = Project Manager group
                            'searchtype' => 'equals',
                            'value' => ($itemtype === 'User') ? 'myself' : 'mygroups',
                        ],
                    ],
                ],
            ],
        ];

        // Retrieve finished project states to exclude them from the search
        $project_states = (new ProjectState())->find([
            'is_finished' => 1,
        ]);

        foreach ($project_states as $state) {
            $options['criteria'][] = [
                'link' => 'AND',
                'field' => 12,
                'searchtype' => 'notequals',
                'value' => $state['id'],
            ];
        }

        $displayed_row_count = min(count($projects_id), (int) $_SESSION['glpidisplay_count_on_home']);

        $twig_params = [
            'class'       => 'table table-borderless table-striped table-hover card-table',
            'header_rows' => [
                [
                    [
                        'colspan' => 4,
                        'content' => sprintf(
                            '<a href="%s">%s</a>',
                            htmlescape(self::getSearchURL() . '?' . Toolbox::append_params($options)),
                            Html::makeTitle(__('Ongoing projects'), $displayed_row_count, count($projects_id))
                        ),
                    ],
                ],
                [
                    [
                        'content' => __s('Name'),
                        'style'   => 'width: 30%',
                    ],
                    [
                        'content' => _sn('State', 'States', 1),
                        'style'   => 'width: 30%',
                    ],
                    [
                        'content' => __s('Priority'),
                        'style'   => 'width: 30%',
                    ],
                    [
                        'content' => __s('Percent done'),
                        'style'   => 'width: 10%',
                    ],
                ],
            ],
            'rows' => [],
        ];

        foreach ($projects_id as $key => $raw_project) {
            if ($key >= $displayed_row_count) {
                break;
            }

            $project = self::getById($raw_project['id']);
            $priority = CommonITILObject::getPriorityName($project->fields['priority']);
            $state = ProjectState::getById($project->fields['projectstates_id']);

            $twig_params['rows'][] = [
                'values' => [
                    [
                        'content' => $project->getLink(),
                    ],
                    [
                        'content' => $state !== false
                            ? sprintf(
                                '<div class="badge_block" style="border-color:%s"><span class="me-1" style="background:%s"></span>%s',
                                htmlescape($state->fields['color']),
                                htmlescape($state->fields['color']),
                                htmlescape($state->fields['name']),
                            )
                            : '',
                    ],
                    [
                        'content' => sprintf(
                            '<div class="badge_block" style="border-color: #ffcece"><span class="me-1" style="background: #ffcece"></span>%s',
                            htmlescape($priority)
                        ),
                    ],
                    [
                        'content' => Html::getProgressBar((float) $project->fields['percent_done']),
                    ],
                ],
            ];
        }

        TemplateRenderer::getInstance()->display('components/table.html.twig', $twig_params);
    }

    /**
     * Update the specified project's percent_done based on the percent_done of subprojects and tasks.
     * This function indirectly updates the percent done for all parents if they are set to automatically update.
     * @since 9.5.0
     * @return boolean False if the specified project is not set to automatically update the percent done.
     */
    public static function recalculatePercentDone($ID)
    {
        global $DB;

        $project = new self();
        $project->getFromDB($ID);
        if (!$project->fields['auto_percent_done']) {
            return false;
        }

        $query1 = new QuerySubQuery([
            'SELECT' => [
                'percent_done',
            ],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'projects_id'  => $ID,
                'is_deleted'   => 0,
            ],
        ]);
        $query2 = new QuerySubQuery([
            'SELECT' => [
                'percent_done',
            ],
            'FROM'   => ProjectTask::getTable(),
            'WHERE'  => [
                'projects_id' => $ID,
            ],
        ]);
        $union = new QueryUnion([$query1, $query2], false, 'all_items');
        $iterator = $DB->request([
            'SELECT' => [
                QueryFunction::cast(
                    expression: QueryFunction::avg('percent_done'),
                    type: 'UNSIGNED',
                    alias: 'percent_done'
                ),
            ],
            'FROM'   => $union,
        ]);

        if ($iterator->count()) {
            $avg = $iterator->current()['percent_done'];
            $percent_done = is_null($avg) ? 0 : $avg;
        } else {
            $percent_done = 0;
        }

        $project->update([
            'id'           => $ID,
            'percent_done' => $percent_done,
        ]);
        return true;
    }

    public static function rawSearchOptionsToAdd($itemtype = null)
    {
        $tab = [];

        if (is_a($itemtype, CommonITILObject::class, true)) {
            $link_table = Itil_Project::getTable();
        } else {
            $link_table = Item_Project::getTable();
        }

        $tab[] = [
            'id'                 => '450',
            'table'              => self::getTable(),
            'field'              => 'name',
            'name'               => self::getTypeName(1),
            'massiveaction'      => false,
            'searchtype'         => ['equals', 'notequals'],
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'jointype'           => 'items_id',
                'beforejoin'         => [
                    'table'              => $link_table,
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                    ],
                ],
            ],
        ];

        return $tab;
    }

    public static function getIcon()
    {
        return "ti ti-layout-kanban";
    }
}
