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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Plugin\Hooks;
use Glpi\RichText\RichText;
use Glpi\Team\Team;
use Glpi\Toolbox\Sanitizer;

/**
 * Project Class
 *
 * @since 0.85
 **/
class Project extends CommonDBTM implements ExtraVisibilityCriteria
{
    use Glpi\Features\Kanban;
    use Glpi\Features\Clonable;
    use Glpi\Features\Teamwork;

   // From CommonDBTM
    public $dohistory                   = true;
    protected static $forward_entity_to = ['ProjectCost', 'ProjectTask'];
    public static $rightname                   = 'project';
    protected $usenotepad               = true;

    const READMY                        = 1;
    const READALL                       = 1024;

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
            KnowbaseItem_Item::class
        ];
    }

    /**
     * Name of the type
     *
     * @param $nb : number of item in the type (default 0)
     **/
    public static function getTypeName($nb = 0)
    {
        return _n('Project', 'Projects', $nb);
    }


    public static function canView()
    {
        return Session::haveRightsOr(self::$rightname, [self::READALL, self::READMY]);
    }


    /**
     * Is the current user have right to show the current project ?
     *
     * @return boolean
     **/
    public function canViewItem()
    {

        if (!parent::canViewItem()) {
            return false;
        }
        return (Session::haveRight(self::$rightname, self::READALL)
              || (Session::haveRight(self::$rightname, self::READMY)
                  && (($this->fields["users_id"] === Session::getLoginUserID())
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
    public function canCreateItem()
    {

        if (!Session::haveAccessToEntity($this->getEntityID())) {
            return false;
        }
        return Session::haveRight(self::$rightname, CREATE);
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
        $values[self::READMY]  = __('See (actor)');

        return $values;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (static::canView() && !$withtemplate) {
            $nb = 0;
            switch ($item->getType()) {
                case __CLASS__:
                    $ong    = [];
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            $this->getTable(),
                            [
                                $this->getForeignKeyField() => $item->getID(),
                                'is_deleted'                => 0
                            ]
                        );
                    }
                    $ong[1] = self::createTabEntry($this->getTypeName(Session::getPluralNumber()), $nb);
                    $ong[3] = __('Kanban');
                    return $ong;
            }
        }

        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case __CLASS__:
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
        $this->addStandardTab('ProjectTask', $ong, $options);
        $this->addStandardTab('ProjectTeam', $ong, $options);
        $this->addStandardTab(__CLASS__, $ong, $options);
        $this->addStandardTab('ProjectCost', $ong, $options);
        $this->addStandardTab('Itil_Project', $ong, $options);
        $this->addStandardTab('Item_Project', $ong, $options);
        $this->addStandardTab('Document_Item', $ong, $options);
        $this->addStandardTab('Contract_Item', $ong, $options);
        $this->addStandardTab('Notepad', $ong, $options);
        $this->addStandardTab('KnowbaseItem_Item', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }


    public static function getAdditionalMenuContent()
    {

       // No view to project by right on tasks add it
        if (
            !static::canView()
            && Session::haveRight('projecttask', ProjectTask::READMY)
        ) {
            $menu['project']['title'] = Project::getTypeName(Session::getPluralNumber());
            $menu['project']['page']  = ProjectTask::getSearchURL(false);

            return $menu;
        }
        return false;
    }


    public static function getAdditionalMenuOptions()
    {
        return [
            'task' => [
                'title' => __('My tasks'),
                'page'  => ProjectTask::getSearchURL(false),
                'links' => [
                    'search' => ProjectTask::getSearchURL(false),
                ]
            ]
        ];
        return false;
    }


    /**
     * @see CommonGLPI::getAdditionalMenuLinks()
     **/
    public static function getAdditionalMenuLinks()
    {
        global $CFG_GLPI;

        $links = [];
        if (
            static::canView()
            || Session::haveRight('projecttask', ProjectTask::READMY)
        ) {
            $pic_validate = '
            <i class="ti ti-eye-check" title="' . __('My tasks') . '"></i>
            <span class="d-none d-xxl-block">
               ' . __('My tasks') . '
            </span>
         ';

            $links[$pic_validate] = ProjectTask::getSearchURL(false);

            $links['summary_kanban'] = Project::getFormURL(false) . '?showglobalkanban=1';
        }
        if (count($links)) {
            return $links;
        }
        return false;
    }


    public function post_updateItem($history = 1)
    {
        global $CFG_GLPI;

        if (in_array('auto_percent_done', $this->updates) && $this->input['auto_percent_done'] == 1) {
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
        $this->team    = ProjectTeam::getTeamFor($this->fields['id']);
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
                'glpi_projects'      => 'id'
            ]
        ];

        $teamtable = 'glpi_projectteams';
        $ors = [
            'glpi_projects.users_id'   => Session::getLoginUserID(),
            [
                "$teamtable.itemtype"   => 'User',
                "$teamtable.items_id"   => Session::getLoginUserID()
            ]
        ];
        if (count($_SESSION['glpigroups'])) {
            $ors['glpi_projects.groups_id'] = $_SESSION['glpigroups'];
            $ors[] = [
                "$teamtable.itemtype"   => 'Group',
                "$teamtable.items_id"   => $_SESSION['glpigroups']
            ];
        }

        $where[] = [
            'OR' => $ors,
        ];

        $criteria = [
            'LEFT JOIN' => $join,
            'WHERE'     => $where
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
                if ($data['items_id'] == Session::getLoginUserID()) {
                    return true;
                }
            }
        }

        if (
            isset($_SESSION['glpigroups']) && count($_SESSION['glpigroups'])
            && isset($this->team['Group']) && count($this->team['Group'])
        ) {
            foreach ($_SESSION['glpigroups'] as $groups_id) {
                foreach ($this->team['Group'] as $data) {
                    if ($data['items_id'] == $groups_id) {
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
                if ($this->fields['groups_id'] == $groups_id) {
                    return true;
                }
            }
        }
        return false;
    }


    /**
     * Get team member count
     *
     * @return number
     **/
    public function getTeamCount()
    {

        $nb = 0;
        if (is_array($this->team) && count($this->team)) {
            foreach ($this->team as $val) {
                $nb +=  count($val);
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
            'name'               => __('Characteristics')
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
            'forcegroupby'       => true,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'code',
            'name'               => __('Code'),
            'massiveaction'      => false,
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Father'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
            'joinparams'         => [
                'condition'       => [new QueryExpression('1=1')]
            ]
        ];

        $tab[] = [
            'id'                 => '21',
            'table'              => $this->getTable(),
            'field'              => 'content',
            'name'               => __('Description'),
            'massiveaction'      => false,
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'priority',
            'name'               => __('Priority'),
            'searchtype'         => 'equals',
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '14',
            'table'              => 'glpi_projecttypes',
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown'
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
            'table'              => $this->getTable(),
            'field'              => 'date',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'percent_done',
            'name'               => __('Percent done'),
            'datatype'           => 'number',
            'unit'               => '%',
            'min'                => 0,
            'max'                => 100,
            'step'               => 5
        ];

        $plugin = new Plugin();
        if ($plugin->isActivated('gantt')) {
            $tab[] = [
                'id'                 => '6',
                'table'              => $this->getTable(),
                'field'              => 'show_on_global_gantt',
                'name'               => __('Show on global Gantt'),
                'datatype'           => 'bool'
            ];
        }

        $tab[] = [
            'id'                 => '24',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id',
            'name'               => _n('Manager', 'Managers', 1),
            'datatype'           => 'dropdown',
            'right'              => 'see_project'
        ];

        $tab[] = [
            'id'                 => '49',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'linkfield'          => 'groups_id',
            'name'               => __('Manager group'),
            'condition'          => ['is_manager' => 1],
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'plan_start_date',
            'name'               => __('Planned start date'),
            'datatype'           => 'datetime'
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'plan_end_date',
            'name'               => __('Planned end date'),
            'datatype'           => 'datetime'
        ];

        $tab[] = [
            'id'                 => '17',
            'table'              => $this->getTable(),
            'field'              => '_virtual_planned_duration',
            'name'               => __('Planned duration'),
            'datatype'           => 'specific',
            'nosearch'           => true,
            'massiveaction'      => false,
            'nosort'             => true
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => $this->getTable(),
            'field'              => 'real_start_date',
            'name'               => __('Real start date'),
            'datatype'           => 'datetime'
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => $this->getTable(),
            'field'              => 'real_end_date',
            'name'               => __('Real end date'),
            'datatype'           => 'datetime'
        ];

        $tab[] = [
            'id'                 => '18',
            'table'              => $this->getTable(),
            'field'              => '_virtual_effective_duration',
            'name'               => __('Effective duration'),
            'datatype'           => 'specific',
            'nosearch'           => true,
            'massiveaction'      => false,
            'nosort'             => true
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '50',
            'table'              => $this->getTable(),
            'field'              => 'template_name',
            'name'               => __('Template name'),
            'datatype'           => 'text',
            'massiveaction'      => false,
            'nosearch'           => true,
            'nodisplay'          => true,
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '86',
            'table'              => $this->getTable(),
            'field'              => 'is_recursive',
            'name'               => __('Child entities'),
            'datatype'           => 'bool'
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
                'condition'          => ['NEWTABLE.projects_id' => new QueryExpression($DB->quoteName('REFTABLE.id'))],
                'beforejoin'         => [
                    'table'        => $this->getTable(),
                    'joinparams'   => [
                        'jointype'  => 'child'
                    ],
                ],
            ],
            'computation'        => '(SUM(' . $DB->quoteName('TABLE.cost') . '))',
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
                    'condition'          => "AND NEWTABLE.`itemtype` = '$itil_type'"
                ]
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
                    ]
                ]
            ]
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
                    ]
                ]
            ]
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
                    ]
                ]
            ]
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
                    ]
                ]
            ]
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
                'jointype'  => 'child'
            ]
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
                'jointype'  => 'child'
            ]
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
            'joinparams'         => [
                'jointype'          => 'item_revert',
                'specific_itemtype' => 'ProjectState',
                'beforejoin'        => [
                    'table'      => ProjectTask::getTable(),
                    'joinparams' => [
                        'jointype' => 'child',
                    ]
                ]
            ]
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
                    ]
                ]
            ]
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
                'jointype'  => 'child'
            ]
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
                'jointype'  => 'child'
            ]
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
                'jointype'  => 'child'
            ]
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
                'jointype'  => 'child'
            ]
        ];

        $tab[] = [
            'id'                 => '119',
            'table'              => ProjectTask::getTable(),
            'field'              => 'plan_end_date',
            'name'               => __('Planned end date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'splititems'         => true,
            'joinparams'         => [
                'jointype'  => 'child'
            ]
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
                'jointype'  => 'child'
            ]
        ];

        $tab[] = [
            'id'                 => '122',
            'table'              => ProjectTask::getTable(),
            'field'              => 'real_end_date',
            'name'               => __('Real end date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'  => 'child'
            ]
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
                'jointype'  => 'child'
            ]
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
                'jointype'  => 'child'
            ]
        ];

        $tab[] = [
            'id'                 => '125',
            'table'              => ProjectTask::getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text',
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'splititems'         => true,
            'joinparams'         => [
                'jointype'  => 'child'
            ]
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
                'jointype'  => 'child'
            ]
        ];

       // add objectlock search options
        $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        return $tab;
    }


    /**
     * @param $output_type     (default 'Search::HTML_OUTPUT')
     * @param $mass_id         id of the form to check all (default '')
     */
    public static function commonListHeader($output_type = Search::HTML_OUTPUT, $mass_id = '')
    {

       // New Line for Header Items Line
        echo Search::showNewLine($output_type);
       // $show_sort if
        $header_num                      = 1;

        $items                           = [];
        $items[(empty($mass_id) ? '&nbsp' : Html::getCheckAllAsCheckbox($mass_id))] = '';
        $items[__('ID')]                 = "id";
        $items[__('Status')]             = "glpi_projectstates.name";
        $items[_n('Date', 'Dates', 1)]               = "date";
        $items[__('Last update')]        = "date_mod";

        if (count($_SESSION["glpiactiveentities"]) > 1) {
            $items[Entity::getTypeName(Session::getPluralNumber())] = "glpi_entities.completename";
        }

        $items[__('Priority')]         = "priority";
        $items[_n('Manager', 'Managers', 1)] = "users_id";
        $items[__('Manager group')]    = "groups_id";
        $items[__('Name')]             = "name";

        foreach ($items as $key => $val) {
            $link   = "";
            echo Search::showHeaderItem($output_type, $key, $header_num, $link);
        }

       // End Line for column headers
        echo Search::showEndLine($output_type);
    }


    /**
     * Display a line for an object
     *
     * @since 0.85 (befor in each object with differents parameters)
     *
     * @param $id                 Integer  ID of the object
     * @param $options            array    of options
     *      output_type            : Default output type (see Search class / default Search::HTML_OUTPUT)
     *      row_num                : row num used for display
     *      type_for_massiveaction : itemtype for massive action
     *      id_for_massaction      : default 0 means no massive action
     *      followups              : only for Tickets : show followup columns
     */
    public static function showShort($id, $options = [])
    {
        global $DB;

        $p['output_type']            = Search::HTML_OUTPUT;
        $p['row_num']                = 0;
        $p['type_for_massiveaction'] = 0;
        $p['id_for_massiveaction']   = 0;

        if (count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $rand = mt_rand();

       // Prints a job in short form
       // Should be called in a <table>-segment
       // Print links or not in case of user view
       // Make new job object and fill it from database, if success, print it
        $item        = new static();

        $candelete   = static::canDelete();
        $canupdate   = Session::haveRight(static::$rightname, UPDATE);
        $align       = "class='center";
        $align_desc  = "class='left";

        $align      .= "'";
        $align_desc .= "'";

        if ($item->getFromDB($id)) {
            $item_num = 1;
            $bgcolor  = $_SESSION["glpipriority_" . $item->fields["priority"]];

            echo Search::showNewLine($p['output_type'], $p['row_num'] % 2);

            $check_col = '';
            if (
                ($candelete || $canupdate)
                && ($p['output_type'] == Search::HTML_OUTPUT)
                && $p['id_for_massiveaction']
            ) {
                $check_col = Html::getMassiveActionCheckBox(
                    $p['type_for_massiveaction'],
                    $p['id_for_massiveaction']
                );
            }
            echo Search::showItem($p['output_type'], $check_col, $item_num, $p['row_num'], $align);

            $id_col = $item->fields["id"];
            echo Search::showItem($p['output_type'], $id_col, $item_num, $p['row_num'], $align);
           // First column
            $first_col = '';
            $color     = '';
            if ($item->fields["projectstates_id"]) {
                $iterator = $DB->request([
                    'SELECT' => 'color',
                    'FROM'   => 'glpi_projectstates',
                    'WHERE'  => ['id' => $item->fields['projectstates_id']]
                ]);
                foreach ($iterator as $colorrow) {
                     $color = $colorrow['color'];
                }
                $first_col = Dropdown::getDropdownName('glpi_projectstates', $item->fields["projectstates_id"]);
            }
            echo Search::showItem(
                $p['output_type'],
                $first_col,
                $item_num,
                $p['row_num'],
                "$align bgcolor='$color'"
            );

           // Second column
            $second_col = sprintf(
                __('Opened on %s'),
                ($p['output_type'] == Search::HTML_OUTPUT ? '<br>' : '') .
                Html::convDateTime($item->fields['date'])
            );

            echo Search::showItem(
                $p['output_type'],
                $second_col,
                $item_num,
                $p['row_num'],
                $align . " width=130"
            );

           // Second BIS column
            $second_col = Html::convDateTime($item->fields["date_mod"]);
            echo Search::showItem(
                $p['output_type'],
                $second_col,
                $item_num,
                $p['row_num'],
                $align . " width=90"
            );

           // Second TER column
            if (count($_SESSION["glpiactiveentities"]) > 1) {
                $second_col = Dropdown::getDropdownName('glpi_entities', $item->fields['entities_id']);
                echo Search::showItem(
                    $p['output_type'],
                    $second_col,
                    $item_num,
                    $p['row_num'],
                    $align . " width=100"
                );
            }

           // Third Column
            echo Search::showItem(
                $p['output_type'],
                "<span class='b'>" .
                                 CommonITILObject::getPriorityName($item->fields["priority"]) .
                                 "</span>",
                $item_num,
                $p['row_num'],
                "$align bgcolor='$bgcolor'"
            );

           // Fourth Column
            $fourth_col = "";

            if ($item->fields["users_id"]) {
                $userdata    = getUserName($item->fields["users_id"], 2);
                $fourth_col .= sprintf(
                    __('%1$s %2$s'),
                    "<span class='b'>" . $userdata['name'] . "</span>",
                    Html::showToolTip(
                        $userdata["comment"],
                        ['link'    => $userdata["link"],
                            'display' => false
                        ]
                    )
                );
            }

            echo Search::showItem($p['output_type'], $fourth_col, $item_num, $p['row_num'], $align);

           // Fifth column
            $fifth_col = "";

            if ($item->fields["groups_id"]) {
                $fifth_col .= Dropdown::getDropdownName("glpi_groups", $item->fields["groups_id"]);
                $fifth_col .= "<br>";
            }

            echo Search::showItem($p['output_type'], $fifth_col, $item_num, $p['row_num'], $align);

           // Eigth column
            $eigth_column = "<span class='b'>" . $item->fields["name"] . "</span>&nbsp;";

           // Add link
            if ($item->canViewItem()) {
                $eigth_column = "<a id='" . $item->getType() . $item->fields["id"] . "$rand' href=\"" .
                              $item->getLinkURL() . "&amp;forcetab=Project$\">$eigth_column</a>";
            }

            if ($p['output_type'] == Search::HTML_OUTPUT) {
                $eigth_column = sprintf(
                    __('%1$s %2$s'),
                    $eigth_column,
                    Html::showToolTip(
                        $item->fields['content'],
                        ['display' => false,
                            'applyto' => $item->getType() .
                                                                           $item->fields["id"] .
                        $rand
                        ]
                    )
                );
            }

            echo Search::showItem(
                $p['output_type'],
                $eigth_column,
                $item_num,
                $p['row_num'],
                $align_desc . "width='200'"
            );

           // Finish Line
            echo Search::showEndLine($p['output_type']);
        } else {
            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='6' ><i>" . __('No item in progress.') . "</i></td></tr>";
        }
    }

    public function prepareInputForAdd($input)
    {

        if (isset($input["id"]) && ($input["id"] > 0)) {
            $input["_oldID"] = $input["id"];
        }
        if (isset($input['withtemplate']) && (int) $input['withtemplate'] == 2) {
            // Remove dates for template from input. Keep date_creation because it can be overridden
            unset($input['date'], $input['date_mod']);
        }
        unset($input['id']);
        unset($input['withtemplate']);

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
                    __('Circular relation found. Parent not updated.'),
                    false,
                    ERROR
                );
                unset($input['projects_id']);
            }
        }
        if (
            $this->fields['projects_id'] > 0 && isset($input['projects_id'])
            && ($input['projects_id'] != $this->fields['projects_id'])
        ) {
            $input['_old_projects_id'] = $this->fields['projects_id'];
        }
        return self::checkPlanAndRealDates($input);
    }


    public static function checkPlanAndRealDates($input)
    {

        if (
            isset($input['plan_start_date']) && !empty($input['plan_start_date'])
            && isset($input['plan_end_date']) && !empty($input['plan_end_date'])
            && (($input['plan_end_date'] < $input['plan_start_date'])
              || empty($input['plan_start_date']))
        ) {
            Session::addMessageAfterRedirect(
                __('Invalid planned dates. Dates not updated.'),
                false,
                ERROR
            );
            unset($input['plan_start_date']);
            unset($input['plan_end_date']);
        }
        if (
            isset($input['real_start_date']) && !empty($input['real_start_date'])
            && isset($input['real_end_date']) && !empty($input['real_end_date'])
            && (($input['real_end_date'] < $input['real_start_date'])
              || empty($input['real_start_date']))
        ) {
            Session::addMessageAfterRedirect(
                __('Invalid real dates. Dates not updated.'),
                false,
                ERROR
            );
            unset($input['real_start_date']);
            unset($input['real_end_date']);
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
        $rand = mt_rand();

        $iterator = $DB->request([
            'FROM'   => $this->getTable(),
            'WHERE'  => [
                $this->getForeignKeyField()   => $ID,
                'is_deleted'                  => 0
            ]
        ]);
        $numrows = count($iterator);

        if ($this->can($ID, UPDATE)) {
            echo "<div class='firstbloc'>";
            echo "<form name='project_form$rand' id='project_form$rand' method='post'
         action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";

            echo "<a href='" . Toolbox::getItemTypeFormURL('Project') . "?projects_id=$ID'>";
            echo __('Create a sub project from this project');
            echo "</a>";
            Html::closeForm();
            echo "</div>";
        }

        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='noHover'><th colspan='12'>" . Project::getTypeName($numrows) . "</th></tr>";
        if ($numrows) {
            Project::commonListHeader();
            Session::initNavigateListItems(
                'Project',
                //TRANS : %1$s is the itemtype name,
                                 //        %2$s is the name of the item (used for headings of a list)
                                         sprintf(
                                             __('%1$s = %2$s'),
                                             Project::getTypeName(1),
                                             $this->fields["name"]
                                         )
            );

            $i = 0;
            foreach ($iterator as $data) {
                 Session::addToNavigateListItems('Project', $data["id"]);
                 Project::showShort($data['id'], ['row_num' => $i]);
                 $i++;
            }
            Project::commonListHeader();
        }
        echo "</table>";
        echo "</div>\n";
    }


    /**
     * Print the computer form
     *
     * @param $ID        integer ID of the item
     * @param $options   array
     *     - target for the Form
     *     - withtemplate template or basic computer
     *
     *@return void
     **/
    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        $is_template = isset($options['withtemplate']) && (int) $options['withtemplate'] === 1;
        $from_template = isset($options['withtemplate']) && (int) $options['withtemplate'] === 2;

        if (!$is_template) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Creation date') . "</td>";
            echo "<td>";

            $date = $this->fields["date"];
            if (!$ID || $from_template) {
                $date = $_SESSION['glpi_currenttime'];
            }
            Html::showDateTimeField("date", ['value' => $date,
                'maybeempty' => false
            ]);
            echo "</td>";
            if ($ID && !$from_template) {
                echo "<td>" . __('Last update') . "</td>";
                echo "<td >" . Html::convDateTime($this->fields["date_mod"]) . "</td>";
            } else {
                echo "<td colspan='2'>&nbsp;</td>";
            }
            echo "</tr>";
        }

        echo "<tr class='tab_bg_1'>";
        $tplmark = $this->getAutofillMark('name', $options);
        echo "<td>" . __('Name') . $tplmark . "</td>";
        echo "<td>";
        echo Html::input(
            'name',
            [
                'value' => autoName(
                    Sanitizer::decodeHtmlSpecialChars($this->fields['name']),
                    'name',
                    $from_template,
                    $this->getType(),
                    $this->fields['entities_id']
                )
            ]
        );
        echo "</td>";
        echo "<td>" . __('Code') . "</td>";
        echo "<td>";
        echo Html::input('code', ['value' => $this->fields['code']]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Priority') . "</td>";
        echo "<td>";
        CommonITILObject::dropdownPriority(['value' => $this->fields['priority'],
            'withmajor' => 1
        ]);
        echo "</td>";
        echo "<td>" . __('As child of') . "</td>";
        echo "<td>";
        $this->dropdown(['entity'   => $this->fields['entities_id'],
            'value'    => $this->fields['projects_id'],
            'used'     => [$this->fields['id']]
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . _x('item', 'State') . "</td>";
        echo "<td>";
        ProjectState::dropdown(['value' => $this->fields["projectstates_id"]]);
        echo "</td>";
        echo "<td>" . __('Percent done') . "</td>";
        echo "<td>";
        $percent_done_params = [
            'value' => $this->fields['percent_done'],
            'min'   => 0,
            'max'   => 100,
            'step'  => 5,
            'unit'  => '%'
        ];
        if ($this->fields['auto_percent_done']) {
            $percent_done_params['specific_tags'] = ['disabled' => 'disabled'];
        }
        Dropdown::showNumber("percent_done", $percent_done_params);
        $auto_percent_done_params = [
            'type'      => 'checkbox',
            'name'      => 'auto_percent_done',
            'title'     => __('Automatically calculate'),
            'onclick'   => "$(\"select[name='percent_done']\").prop('disabled', !$(\"input[name='auto_percent_done']\").prop('checked'));"
        ];
        if ($this->fields['auto_percent_done']) {
            $auto_percent_done_params['checked'] = 'checked';
        }
        Html::showCheckbox($auto_percent_done_params);
        echo "<span class='ms-3'>";
        Html::showToolTip(__('When automatic computation is active, percentage is computed based on the average of all child project and task percent done.'));
        echo "</span></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . _n('Type', 'Types', 1) . "</td>";
        echo "<td>";
        ProjectType::dropdown(['value' => $this->fields["projecttypes_id"]]);
        echo "</td>";
        $plugin = new Plugin();
        if ($plugin->isActivated('gantt')) {
            echo "<td>" . __('Show on global Gantt') . "</td>";
            echo "<td>";
            Dropdown::showYesNo("show_on_global_gantt", $this->fields["show_on_global_gantt"]);
            echo "</td>";
        }
        echo "</tr>";

        echo "<tr><td colspan='4' class='subheader'>" . _n('Manager', 'Managers', 1) . "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . User::getTypeName(1) . "</td>";
        echo "<td>";
        User::dropdown(['name'   => 'users_id',
            'value'  => $ID ? $this->fields["users_id"] : Session::getLoginUserID(),
            'right'  => 'see_project',
            'entity' => $this->fields["entities_id"]
        ]);
        echo "</td>";
        echo "<td>" . Group::getTypeName(1) . "</td>";
        echo "<td>";
        Group::dropdown([
            'name'      => 'groups_id',
            'value'     => $this->fields['groups_id'],
            'entity'    => $this->fields['entities_id'],
            'condition' => ['is_manager' => 1]
        ]);
        echo "</td></tr>\n";

        echo "<tr><td colspan='4' class='subheader'>" . __('Planning') . "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Planned start date') . "</td>";
        echo "<td>";
        Html::showDateTimeField("plan_start_date", ['value' => $this->fields['plan_start_date']]);
        echo "</td>";
        echo "<td>" . __('Real start date') . "</td>";
        echo "<td>";
        Html::showDateTimeField("real_start_date", ['value' => $this->fields['real_start_date']]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Planned end date') . "</td>";
        echo "<td>";
        Html::showDateTimeField("plan_end_date", ['value' => $this->fields['plan_end_date']]);
        echo "</td>";
        echo "<td>" . __('Real end date') . "</td>";
        echo "<td>";
        Html::showDateTimeField("real_end_date", ['value' => $this->fields['real_end_date']]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Planned duration');
        echo Html::showTooltip(__('Sum of planned durations of tasks'));
        echo "</td>";
        echo "<td>";
        echo Html::timestampToString(
            ProjectTask::getTotalPlannedDurationForProject($this->fields['id']),
            false
        );
        echo "</td>";
        echo "<td>" . __('Effective duration');
        echo Html::showTooltip(__('Sum of total effective durations of tasks'));
        echo "</td>";
        echo "<td>";
        echo Html::timestampToString(
            ProjectTask::getTotalEffectiveDurationForProject($this->fields['id']),
            false
        );
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Description') . "</td>";
        echo "<td colspan='3'>";
        echo "<textarea id='content' name='content' cols='90' rows='6'>" . $this->fields["content"] .
           "</textarea>";
        echo "</td>";
        echo "</tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Comments') . "</td>";
        echo "<td colspan='3'>";
        echo "<textarea id='comment' name='comment' cols='90' rows='6'>" . $this->fields["comment"] .
           "</textarea>";
        echo "</td>";
        echo "</tr>\n";

        $this->showFormButtons($options);

        return true;
    }


    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'priority':
                return CommonITILObject::getPriorityName($values[$field]);
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    /**
     * @since 0.85
     *
     * @param $field
     * @param $name            (default '')
     * @param $values          (default '')
     * @param $options   array
     **/
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

        echo "<div class='center'>";

        $rand = mt_rand();
        $nb   = 0;
        $nb   = $project->getTeamCount();

        if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form name='projectteam_form$rand' id='projectteam_form$rand' ";
            echo " method='post' action='" . Toolbox::getItemTypeFormURL('ProjectTeam') . "'>";
            echo "<input type='hidden' name='projects_id' value='$ID'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'><th colspan='2'>" . __('Add a team member') . "</tr>";
            echo "<tr class='tab_bg_2'><td>";

            $params = ['itemtypes'       => ProjectTeam::$available_types,
                'entity_restrict' => ($project->fields['is_recursive']
                                               ? getSonsOf(
                                                   'glpi_entities',
                                                   $project->fields['entities_id']
                                               )
                                               : $project->fields['entities_id']),
            ];
            Dropdown::showSelectItemFromItemtypes($params);

            echo "</td>";
            echo "<td width='20%'>";
            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\"
               class='btn btn-primary'>";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }
        echo "<div class='spaced'>";
        if ($canedit && $nb) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $nb),
                'container'     => 'mass' . __CLASS__ . $rand
            ];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixehov'>";
        $header_begin  = "<tr>";
        $header_top    = '';
        $header_bottom = '';
        $header_end    = '';
        if ($canedit && $nb) {
            $header_begin    .= "<th width='10'>";
            $header_top    .= Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_bottom .= Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_end    .= "</th>";
        }
        $header_end .= "<th>" . _n('Type', 'Types', 1) . "</th>";
        $header_end .= "<th>" . _n('Member', 'Members', Session::getPluralNumber()) . "</th>";
        $header_end .= "</tr>";
        echo $header_begin . $header_top . $header_end;

        foreach (ProjectTeam::$available_types as $type) {
            if (isset($project->team[$type]) && count($project->team[$type])) {
                if ($item = getItemForItemtype($type)) {
                    foreach ($project->team[$type] as $data) {
                        $item->getFromDB($data['items_id']);
                        echo "<tr class='tab_bg_2'>";
                        if ($canedit) {
                             echo "<td>";
                             Html::showMassiveActionCheckBox('ProjectTeam', $data["id"]);
                             echo "</td>";
                        }
                        echo "<td>" . $item->getTypeName(1) . "</td>";
                        echo "<td>" . $item->getLink() . "</td>";
                        echo "</tr>";
                    }
                }
            }
        }
        if ($nb) {
            echo $header_begin . $header_bottom . $header_end;
        }

        echo "</table>";
        if ($canedit && $nb) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }

        echo "</div>";
       // Add items

        return true;
    }

    public static function getAllForKanban($active = true, $current_id = -1)
    {
        global $DB;

        $items = [
            -1 => __('Global')
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
                    ]
                ]
            ];
            $joins = [
                'glpi_projectstates' => [
                    'FKEY' => [
                        'glpi_projectstates' => 'id',
                        'glpi_projects'      => 'projectstates_id'
                    ]
                ]
            ];
        }
        $criteria += getEntitiesRestrictCriteria(self::getTable(), '', '', 'auto');
        $iterator = $DB->request(array_merge_recursive([
            'SELECT'   => [
                'glpi_projects.id',
                'glpi_projects.name',
                'glpi_projects.is_deleted',
                'glpi_projectstates.is_finished'
            ],
            'DISTINCT' => true,
            'FROM'     => 'glpi_projects',
            'LEFT JOIN' => $joins,
            'WHERE'     => $criteria
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
                'WHERE'     => ['id' => $current_id]
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
        if ($column_field === null || $column_field == 'projectstates_id') {
            $columns = ['projectstates_id' => []];
            $projectstate = new ProjectState();
            $restrict = [];
            if (!empty($column_ids) && !$get_default) {
                $restrict = ['id' => $column_ids];
            }
            $allstates = $projectstate->find($restrict, ['is_finished ASC', 'id']);
            foreach ($allstates as $state) {
                $columns['projectstates_id'][$state['id']] = [
                    'name'            => $state['name'],
                    'header_color'    => $state['color'],
                    'header_fg_color' => Toolbox::getFgColor($state['color'], 50),
                ];
            }
            return $columns['projectstates_id'];
        } else {
            return [];
        }
    }

    public static function getDataToDisplayOnKanban($ID, $criteria = [])
    {
        global $DB, $CFG_GLPI;

        $items      = [];

       // Get sub-projects
        $projectteam = new ProjectTeam();
        $project = new Project();
        $project_visibility = self::getVisibilityCriteria();
        $project_visibility['WHERE'] += getEntitiesRestrictCriteria(self::getTable(), '', '', 'auto');

        $required_project_fields = [
            'id', 'name', 'content', 'plan_start_date', 'plan_end_date', 'real_start_date',
            'real_end_date', 'percent_done', 'projects_id', 'projectstates_id',
        ];
        $request = [
            'SELECT' => [
                'glpi_projectstates.is_finished'
            ],
            'FROM'   => 'glpi_projects',
            'LEFT JOIN' => [
                'glpi_projectstates' => [
                    'FKEY' => [
                        'glpi_projects'   => 'projectstates_id',
                        'glpi_projectstates' => 'id'
                    ]
                ]
            ] + $project_visibility['LEFT JOIN'],
            'WHERE'     => $project_visibility['WHERE'] + [
                'is_template' => 0
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
        $project_ids = array_map(static function ($e) {
            return $e['id'];
        }, array_filter($projects, static function ($e) use ($ID) {
          // Filter tasks of closed projects in Global view
            return ($ID > 0 || !$e['is_finished']);
        }));
        $projectteams = count($project_ids) ? $projectteam->find(['projects_id' => $project_ids]) : [];

       // Get sub-tasks
        $projecttask = new ProjectTask();
        $projecttaskteam = new ProjectTaskTeam();
        $project_task_criteria = [
            'is_template' => 0,
            'projects_id' => ($ID <= 0 && count($project_ids)) ? $project_ids : $ID,
        ];
        $projecttasks = $projecttask->find($project_task_criteria + $criteria);
        $projecttask_ids = array_map(static function ($e) {
            return $e['id'];
        }, $projecttasks);
        $projecttaskteams = count($projecttask_ids) ? $projecttaskteam->find(['projecttasks_id' => $projecttask_ids]) : [];

       // Build team member data
        $supported_teamtypes = [
            'User' => ['id', 'firstname', 'realname'],
            'Group' => ['id', 'name'],
            'Supplier' => ['id', 'name'],
            'Contact' => ['id', 'name', 'firstname']
        ];
        $all_members = [];
        foreach ($supported_teamtypes as $itemtype => $fields) {
            $all_ids = array_map(static function ($e) {
                return $e['items_id'];
            }, array_filter(array_merge($projectteams, $projecttaskteams), static function ($e) use ($itemtype) {
                return ($e['itemtype'] === $itemtype);
            }));
            if (count($all_ids)) {
                $itemtable = $itemtype::getTable();
                $all_items = $DB->request([
                    'SELECT'    => $fields,
                    'FROM'      => $itemtable,
                    'WHERE'     => [
                        "{$itemtable}.id"   => $all_ids
                    ]
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
                '_steps'    => ProjectTask::getAllForProject($subproject['id'])
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

            $subproject_teams = array_filter($projectteams, static function ($e) use ($subproject) {
                return $e['projects_id'] == $subproject['id'];
            });
            foreach ($subproject_teams as $teammember) {
                switch ($teammember['itemtype']) {
                    case 'Group':
                    case 'Supplier':
                        $matches = array_filter($all_members[$teammember['itemtype']], static function ($e) use ($teammember) {
                            return ($e['id'] == $teammember['items_id']);
                        });
                        if (count($matches)) {
                             $item['_team'][] = array_merge($teammember, reset($matches));
                        }
                        break;
                    case 'User':
                    case 'Contact':
                        $contact_matches = array_filter($all_members[$teammember['itemtype']], static function ($e) use ($teammember) {
                            return ($e['id'] == $teammember['items_id']);
                        });
                        if (count($contact_matches)) {
                              $match = reset($contact_matches);
                              // contact -> name, user -> realname
                              $realname = $match['name'] ?? $match['realname'] ?? "";
                              $match['name'] = formatUserName($match['id'], '', $realname, $match['firstname']);
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
                'type' => $subtask['projecttasktypes_id']
            ]);
            if ($ID <= 0) {
                $item['_parents_id'] = $projects[$subtask['projects_id']]['id'];
                $item['_parent_itemtype'] = 'Project';
                $item['_parent_name'] = $projects[$subtask['projects_id']]['name'];
            }

            $projecttask->fields = $subtask;
            $item['_readonly'] = !ProjectTask::canUpdate() || !$projecttask->canUpdateItem();

            $subtask_teams = array_filter($projecttaskteams, static function ($e) use ($subtask) {
                return $e['projecttasks_id'] == $subtask['id'];
            });
            foreach ($subtask_teams as $teammember) {
                switch ($teammember['itemtype']) {
                    case 'Group':
                    case 'Supplier':
                        $matches = array_filter($all_members[$teammember['itemtype']], static function ($e) use ($teammember) {
                            return ($e['id'] == $teammember['items_id']);
                        });
                        if (count($matches)) {
                             $item['_team'][] = array_merge($teammember, reset($matches));
                        }
                        break;
                    case 'User':
                    case 'Contact':
                        $contact_matches = array_filter($all_members[$teammember['itemtype']], static function ($e) use ($teammember) {
                            return ($e['id'] == $teammember['items_id']);
                        });
                        if (count($contact_matches)) {
                              $match = reset($contact_matches);
                            if ($teammember['itemtype'] === 'User') {
                                $match['name'] = formatUserName($match['id'], '', $match['realname'], $match['firstname']);
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

        if ($column_field !== 'projectstates_id') {
            return [];
        }

        $columns = [];
        if (empty($column_ids) || $get_default || in_array(0, $column_ids)) {
            $columns[0] = [
                'name'         => __('No status'),
                '_protected'   => true
            ];
        }
        $criteria = [];
        if (!empty($column_ids)) {
            $criteria = [
                'projectstates_id'   => $column_ids
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
            if (!in_array($item['projectstates_id'], array_keys($columns))) {
                continue;
            }
            $itemtype = $item['_itemtype'];
            $card = [
                'id'              => "{$itemtype}-{$item['id']}",
                'title'           => '<span class="pointer">' . $item['name'] . '</span>',
                'title_tooltip'   => Html::resume_text(RichText::getTextFromHtml($item['content'] ?? "", false, true), 100),
                'is_deleted'      => $item['is_deleted'] ?? false,
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
                $content .= Html::link(sprintf(__('%s of %s'), $childref, $parentname), Project::getFormURLWithID($item['_parents_id']));
                $content .= "</div>";
            }
            $content .= "<div class='flex-break'></div>";
            if ($itemtype === 'ProjectTask' && $item['projecttasktypes_id'] !== 0) {
                $typematches = array_filter($alltypes, function ($t) use ($item) {
                    return $t['id'] === $item['projecttasktypes_id'];
                });
                $content .= reset($typematches)['name'] . '&nbsp;';
            }
            if (array_key_exists('is_milestone', $item) && $item['is_milestone']) {
                $content .= "&nbsp;<i class='fas fa-map-signs' title='" . __('Milestone') . "'></i>&nbsp;";
            }
            if (isset($item['_steps']) && count($item['_steps'])) {
                $done = count(array_filter($item['_steps'], function ($step) {
                    return $step['percent_done'] == 100;
                }));
                $total = count($item['_steps']);
                $content .= "<div class='flex-break'></div>";
                $content .= sprintf(__('%s / %s tasks complete'), $done, $total);
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
            $metadata_values = ['name', 'content', 'is_milestone', 'plan_start_date', 'plan_end_date', 'real_start_date', 'real_end_date',
                'planned_duration', 'effective_duration', 'percent_done'
            ];
            foreach ($metadata_values as $metadata_value) {
                if (isset($item[$metadata_value])) {
                    $card['_metadata'][$metadata_value] = $item[$metadata_value];
                }
            }
            if (isset($card['_metadata']['content']) && is_string($card['_metadata']['content'])) {
                $card['_metadata']['content'] = Glpi\RichText\RichText::getTextFromHtml($card['_metadata']['content'], false, true);
            } else {
                $card['_metadata']['content'] = '';
            }
            $card['_metadata'] = Plugin::doHookFunction(Hooks::KANBAN_ITEM_METADATA, [
                'itemtype' => $itemtype,
                'items_id' => $item['id'],
                'metadata' => $card['_metadata']
            ])['metadata'];
            $columns[$item['projectstates_id']]['items'][] = $card;
        }

       // If no specific columns were asked for, drop empty columns.
       // If specific columns were asked for, such as when loading a user's Kanban view, we must preserve them.
       // We always preserve the 'No Status' column.
        foreach ($columns as $column_id => $column) {
            if (
                $column_id !== 0 && !in_array($column_id, $column_ids) &&
                (!isset($column['items']) || !count($column['items']))
            ) {
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
     * @param int $ID ID of the parent Project or -1 for a global view.
     * @return bool|void False if the Kanban cannot be shown.
     */
    public static function showKanban($ID)
    {
        $project = new Project();
        if (
            ($ID <= 0 && !Project::canView()) ||
            ($ID > 0 && (!$project->getFromDB($ID) || !$project->canView()))
        ) {
            return false;
        }

        $supported_itemtypes = [];
        if (Project::canCreate()) {
            $team_role_ids = static::getTeamRoles();
            $team_roles = [];

            foreach ($team_role_ids as $role_id) {
                $team_roles[$role_id] = static::getTeamRoleName($role_id);
            }
           // Owner cannot be set from the Kanban view yet because it is a special case (One owner user and one owner group)
            unset($team_roles[Team::ROLE_OWNER]);

            $supported_itemtypes['Project'] = [
                'name'   => Project::getTypeName(1),
                'icon'   => Project::getIcon(),
                'fields' => [
                    'projects_id'  => [
                        'type'   => 'hidden',
                        'value'  => $ID
                    ],
                    'name'   => [
                        'placeholder'  => __('Name')
                    ],
                    'content'   => [
                        'placeholder'  => __('Content'),
                        'type'         => 'textarea'
                    ],
                    'users_id'  => [
                        'type'         => 'hidden',
                        'value'        => $_SESSION['glpiID']
                    ],
                    'entities_id' => [
                        'type'   => 'hidden',
                        'value'  => $ID > 0 ? $project->fields["entities_id"] : $_SESSION['glpiactive_entity'],
                    ],
                    'is_recursive' => [
                        'type'   => 'hidden',
                        'value'  => 0
                    ]
                ],
                'team_itemtypes'  => Project::getTeamItemtypes(),
                'team_roles'      => $team_roles,
            ];
        }

        if (ProjectTask::canCreate()) {
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
                        'value'  => $ID
                    ],
                    'name'   => [
                        'placeholder'  => __('Name')
                    ],
                    'content'   => [
                        'placeholder'  => __('Content'),
                        'type'         => 'textarea'
                    ],
                    'projecttasktemplates_id' => [
                        'type'   => 'hidden',
                        'value'  => 0
                    ],
                    'projecttasks_id' => [
                        'type'   => 'hidden',
                        'value'  => 0
                    ],
                    'entities_id' => [
                        'type'   => 'hidden',
                        'value'  => $ID > 0 ? $project->fields["entities_id"] : $_SESSION['glpiactive_entity'],
                    ],
                    'is_recursive' => [
                        'type'   => 'hidden',
                        'value'  => 0
                    ]
                ],
                'team_itemtypes'  => ProjectTask::getTeamItemtypes(),
                'team_roles'      => $team_roles,
            ];
            if ($ID <= 0) {
                $supported_itemtypes['ProjectTask']['fields']['projects_id'] = [
                    'type'   => 'raw',
                    'value'  => Project::dropdown(['display' => false, 'width' => '90%'])
                ];
            }
        }
        $column_field = [
            'id' => 'projectstates_id',
            'extra_fields' => [
                'color'  => [
                    'type'   => 'color'
                ]
            ]
        ];

        $canmodify_view = ($ID == 0 || $project->canModifyGlobalState());
        $rights = [
            'create_item'                    => self::canCreate() || ProjectTask::canCreate(),
            'delete_item'                    => self::canDelete() || ProjectTask::canDelete(),
            'create_column'                  => (bool)ProjectState::canCreate(),
            'modify_view'                    => $ID == 0 || $project->canModifyGlobalState(),
            'order_card'                     => (bool)$project->canOrderKanbanCard($ID),
            'create_card_limited_columns'    => $canmodify_view ? [] : [0]
        ];

        TemplateRenderer::getInstance()->display('components/kanban/kanban.html.twig', [
            'kanban_id'                   => 'kanban',
            'rights'                      => $rights,
            'supported_itemtypes'         => $supported_itemtypes,
            'max_team_images'             => 3,
            'column_field'                => $column_field,
            'item'                        => [
                'itemtype'  => 'Project',
                'items_id'  => $ID
            ],
            'supported_filters'           => [
                'title' => [
                    'description' => _x('filters', 'The title of the item'),
                    'supported_prefixes' => ['!', '#'] // Support exclusions and regex
                ],
                'type' => [
                    'description' => _x('filters', 'The type of the item'),
                    'supported_prefixes' => ['!'] // Support exclusions only
                ],
                'milestone' => [
                    'description' => _x('filters', 'If the item represents a milestone or not'),
                    'supported_prefixes' => ['!']
                ],
                'content' => [
                    'description' => _x('filters', 'The content of the item'),
                    'supported_prefixes' => ['!', '#']
                ],
                'team' => [
                    'description' => _x('filters', 'A team member for the item'),
                    'supported_prefixes' => ['!']
                ],
                'user' => [
                    'description' => _x('filters', 'A user in the team of the item'),
                    'supported_prefixes' => ['!']
                ],
                'group' => [
                    'description' => _x('filters', 'A group in the team of the item'),
                    'supported_prefixes' => ['!']
                ],
                'supplier' => [
                    'description' => _x('filters', 'A supplier in the team of the item'),
                    'supported_prefixes' => ['!']
                ],
                'contact' => [
                    'description' => _x('filters', 'A contact in the team of the item'),
                    'supported_prefixes' => ['!']
                ],
            ] + self::getKanbanPluginFilters(static::getType())
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
            Team::ROLE_MEMBER
        ];
    }

    public static function getTeamRoleName(int $role, int $nb = 1): string
    {
        switch ($role) {
            case Team::ROLE_OWNER:
                return _n('Manager', 'Managers', $nb);
            case Team::ROLE_MEMBER:
                return _n('Member', 'Members', $nb);
        }
        return '';
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
            'items_id'     => $items_id
        ]);
        return (bool) $result;
    }

    public function deleteTeamMember(string $itemtype, int $items_id, array $params = []): bool
    {
        $project_team = new ProjectTeam();
        $result = $project_team->deleteByCriteria([
            'projects_id'  => $this->getID(),
            'itemtype'     => $itemtype,
            'items_id'     => $items_id
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
     * Display debug information for current object
     **/
    public function showDebug()
    {
        NotificationEvent::debugEvent($this);
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

        $query1 = new \QuerySubQuery([
            'SELECT' => [
                'percent_done'
            ],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'projects_id'  => $ID,
                'is_deleted'   => 0
            ]
        ]);
        $query2 = new \QuerySubQuery([
            'SELECT' => [
                'percent_done'
            ],
            'FROM'   => ProjectTask::getTable(),
            'WHERE'  => [
                'projects_id' => $ID
            ]
        ]);
        $union = new QueryUnion([$query1, $query2], false, 'all_items');
        $iterator = $DB->request([
            'SELECT' => [
                new QueryExpression('CAST(AVG(' . $DB->quoteName('percent_done') . ') AS UNSIGNED) AS percent_done')
            ],
            'FROM'   => $union
        ]);

        if ($iterator->count()) {
            $avg = $iterator->current()['percent_done'];
            $percent_done = is_null($avg) ? 0 : $avg;
        } else {
            $percent_done = 0;
        }

        $project->update([
            'id'           => $ID,
            'percent_done' => $percent_done
        ]);
        return true;
    }


    public static function getIcon()
    {
        return "ti ti-layout-kanban";
    }
}
