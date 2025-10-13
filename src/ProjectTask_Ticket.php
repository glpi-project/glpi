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
use Glpi\DBAL\QueryFunction;

/**
 * ProjectTask_Ticket Class
 *
 * Relation between ProjectTasks and Tickets
 *
 * @since 0.85
 **/
class ProjectTask_Ticket extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1   = 'ProjectTask';
    public static $items_id_1   = 'projecttasks_id';

    public static $itemtype_2   = 'Ticket';
    public static $items_id_2   = 'tickets_id';

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Link Ticket/Project task', 'Links Ticket/Project task', $nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (static::canView()) {
            $nb = 0;
            switch ($item::class) {
                case ProjectTask::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForItem($item);
                    }
                    return self::createTabEntry(Ticket::getTypeName(Session::getPluralNumber()), $nb, $item::class);

                case Ticket::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForItem($item);
                    }
                    return self::createTabEntry(ProjectTask::getTypeName(Session::getPluralNumber()), $nb, $item::class);
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item::class) {
            case ProjectTask::class:
                self::showForProjectTask($item);
                break;
            case Ticket::class:
                self::showForTicket($item);
                break;
        }
        return true;
    }

    /**
     * Get total duration of tickets linked to a project task
     *
     * @param integer $projecttasks_id ID of the project task
     *
     * @return integer total actiontime
     **/
    public static function getTicketsTotalActionTime($projecttasks_id)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT'    => [
                QueryFunction::sum(
                    expression: 'glpi_tickets.actiontime',
                    alias: 'duration'
                ),
            ],
            'FROM'         => self::getTable(),
            'INNER JOIN'   => [
                'glpi_tickets' => [
                    'FKEY'   => [
                        self::getTable()  => 'tickets_id',
                        'glpi_tickets'    => 'id',
                    ],
                ],
            ],
            'WHERE'        => ['projecttasks_id' => $projecttasks_id],
        ]);

        return count($iterator) ? $iterator->current()['duration'] : 0;
    }

    /**
     * Show tickets for a projecttask
     *
     * @param ProjectTask $projecttask object
     **/
    public static function showForProjectTask(ProjectTask $projecttask)
    {
        $ID = $projecttask->getField('id');
        if (!$projecttask->can($ID, READ)) {
            return false;
        }

        $canedit = $projecttask->canEdit($ID);
        $rand    = mt_rand();

        $iterator = self::getListForItem($projecttask);

        $tickets = [];
        $used    = [];
        foreach ($iterator as $data) {
            $tickets[$data['id']] = $data;
            $used[$data['id']]    = $data['id'];
        }

        if ($canedit) {
            $condition = [
                'NOT' => [
                    'glpi_tickets.status'    => array_merge(
                        Ticket::getSolvedStatusArray(),
                        Ticket::getClosedStatusArray()
                    ),
                ],
            ];
            echo TemplateRenderer::getInstance()->render('components/form/link_existing_or_new.html.twig', [
                'rand' => $rand,
                'link_itemtype' => self::class,
                'source_itemtype' => ProjectTask::class,
                'source_items_id' => $ID,
                'target_itemtype' => Ticket::class,
                'dropdown_options' => [
                    'entity'      => $projecttask->getEntityID(),
                    'entity_sons' => $projecttask->isRecursive(),
                    'used'        => $used,
                    'displaywith' => ['id'],
                    'condition'   => $condition,
                ],
                'create_link' => Session::haveRight(Ticket::$rightname, CREATE),
                'form_label' => __('Add a ticket'),
                'button_label' => __('Create a ticket from this project task'),
            ]);
        }

        [$columns, $formatters] = array_values(Ticket::getCommonDatatableColumns());
        $entries = Ticket::getDatatableEntries(array_map(static function ($t) {
            $t['itemtype'] = Ticket::class;
            $t['item_id'] = $t['id'];
            return $t;
        }, $tickets));

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => $columns,
            'formatters' => $formatters,
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
            ],
        ]);
    }

    /**
     * Show projecttasks for a ticket
     *
     * @param Ticket $ticket object
     **/
    public static function showForTicket(Ticket $ticket)
    {
        global $CFG_GLPI, $DB;

        $ID = $ticket->getField('id');
        if (!$ticket->can($ID, READ)) {
            return false;
        }

        $canedit = $ticket->canEdit($ID);
        $rand = mt_rand();

        $iterator = self::getListForItem($ticket);

        $used    = [];
        foreach ($iterator as $data) {
            $used[$data['id']]    = $data['id'];
        }

        if (
            $canedit
            && !in_array((int) $ticket->fields['status'], array_merge(
                $ticket->getClosedStatusArray(),
                $ticket->getSolvedStatusArray()
            ), true)
        ) {
            $finished_states_it = $DB->request(
                [
                    'SELECT' => ['id'],
                    'FROM'   => ProjectState::getTable(),
                    'WHERE'  => [
                        'is_finished' => 1,
                    ],
                ]
            );
            $finished_states_ids = [];
            foreach ($finished_states_it as $finished_state) {
                $finished_states_ids[] = $finished_state['id'];
            }

            $p = ['projects_id'     => '__VALUE__',
                'entity_restrict' => $ticket->getEntityID(),
                'used'            => $used,
                'rand'            => $rand,
                'myname'          => "projects",
            ];

            if (count($finished_states_ids)) {
                $where = [
                    'OR'  => [
                        'projectstates_id'   => $finished_states_ids,
                        'is_template'        => 1,
                    ],
                ];
            } else {
                $where = ['is_template' => 1];
            }

            $excluded_projects_it = $DB->request(
                [
                    'SELECT' => ['id'],
                    'FROM'   => Project::getTable(),
                    'WHERE'  => $where,
                ]
            );
            $excluded_projects_ids = [];
            foreach ($excluded_projects_it as $excluded_project) {
                $excluded_projects_ids[] = $excluded_project['id'];
            }

            $dd_params = [
                'used'        => $used,
                'entity'      => $ticket->getEntityID(),
                'entity_sons' => $ticket->isRecursive(),
                'displaywith' => ['id'],
            ];

            $condition = [];
            if (count($finished_states_ids)) {
                $condition['glpi_projecttasks.projectstates_id'] = $finished_states_ids;
            }
            if (count($excluded_projects_ids)) {
                $condition['glpi_projecttasks.projects_id'] = $excluded_projects_ids;
            }

            if (count($condition)) {
                $dd_params['condition'] = ['NOT' => $condition];
            }

            echo TemplateRenderer::getInstance()->render('components/form/link_existing_or_new.html.twig', [
                'rand' => $rand,
                'link_itemtype' => self::class,
                'source_itemtype' => Ticket::class,
                'source_items_id' => $ID,
                'target_itemtype' => ProjectTask::class,
                'dropdown_options' => [
                    "itemtype" => Project::class,
                    'entity'      => $ticket->getEntityID(),
                    'entity_sons' => $ticket->isRecursive(),
                    'condition'   => ['NOT' => ['glpi_projects.projectstates_id' => $finished_states_ids]],
                ],
                'ajax_dropdown' => [
                    'toobserve' => "dropdown_projects_id$rand",
                    'toupdate' => [
                        "id" => "results_projects$rand",
                        "itemtype" => ProjectTask::class,
                        'params' => $dd_params,
                    ],
                    'url' => $CFG_GLPI["root_doc"] . "/ajax/dropdownProjectTaskTicket.php",
                    'params' => $p,
                ],
                'create_link' => false,
                'form_label' => __('Add a project task'),
                'button_label' => __('Create a project task from this ticket'),
            ]);
        }

        $columns = [
            'projectname'      => Project::getTypeName(Session::getPluralNumber()),
            'name'             => ProjectTask::getTypeName(Session::getPluralNumber()),
            'tname'            => _n('Type', 'Types', 1),
            'sname'            => __('Status'),
            'percent_done'     => __('Percent done'),
            'plan_start_date'  => __('Planned start date'),
            'plan_end_date'    => __('Planned end date'),
            'planned_duration' => __('Planned duration'),
            '_effect_duration' => __('Effective duration'),
            'fname'            => __('Father'),
        ];

        if (isset($_GET["order"]) && ($_GET["order"] === "DESC")) {
            $order = "DESC";
        } else {
            $order = "ASC";
        }

        if (empty($_GET["sort"])) {
            $_GET["sort"] = "plan_start_date";
        }

        if (!empty($_GET["sort"]) && isset($columns[$_GET["sort"]])) {
            $sort = $_GET["sort"];
        } else {
            $sort = ["plan_start_date $order", 'name'];
        }
        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_projecttasks.*',
                'glpi_projecttasktypes.name AS tname',
                'glpi_projectstates.name AS sname',
                'glpi_projectstates.color',
                'father.name AS fname',
                'father.id AS fID',
                'glpi_projects.name AS projectname',
                'glpi_projects.content AS projectcontent',
                'glpi_projecttasks_tickets.id AS linkid',
            ],
            'FROM'      => 'glpi_projecttasks',
            'LEFT JOIN' => [
                'glpi_projecttasktypes' => [
                    'ON' => [
                        'glpi_projecttasktypes' => 'id',
                        'glpi_projecttasks'     => 'projecttasktypes_id',
                    ],
                ],
                'glpi_projectstates'    => [
                    'ON' => [
                        'glpi_projectstates' => 'id',
                        'glpi_projecttasks'  => 'projectstates_id',
                    ],
                ],
                'glpi_projecttasks AS father' => [
                    'ON' => [
                        'father'             => 'id',
                        'glpi_projecttasks'  => 'projecttasks_id',
                    ],
                ],
                'glpi_projecttasks_tickets'   => [
                    'ON' => [
                        'glpi_projecttasks_tickets'   => 'projecttasks_id',
                        'glpi_projecttasks'           => 'id',
                    ],
                ],
                'glpi_projects'               => [
                    'ON' => [
                        'glpi_projecttasks'  => 'projects_id',
                        'glpi_projects'      => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_projecttasks_tickets.tickets_id' => $ID,
            ],
            'ORDERBY'   => [
                "$sort $order",
            ],
        ]);

        $entries = [];
        foreach ($iterator as $data) {
            $project_name = htmlescape($data['projectname'] . (empty($data['projectname']) ? "({$data['projects_id']})" : ''));
            $projectlink = "<a href='" . htmlescape(Project::getFormURLWithID($data['projects_id'])) . "'>$project_name</a>";
            $task_name = htmlescape($data['name'] . (empty($data['name']) ? "({$data['id']})" : ''));
            $tasklink = "<a href='" . htmlescape(ProjectTask::getFormURLWithID($data['id'])) . "'>$task_name</a>";

            $father = '';
            if ($data['projecttasks_id'] > 0) {
                $father_name = Dropdown::getDropdownName('glpi_projecttasks', $data['projecttasks_id']);
                $father = sprintf(
                    '<a href="%s">%s</a>',
                    htmlescape(ProjectTask::getFormURLWithID($data['projecttasks_id'])),
                    htmlescape($father_name ?: "(" . $data['projecttasks_id'] . ")")
                );
            }

            $status = $data['sname'];

            if (!empty($status)) {
                $fg_color = Toolbox::getFgColor($data['color']);
                $status_badge_style = "background-color:{$data['color']}; color:{$fg_color};";
                $status = '<span class="badge" style="' . htmlescape($status_badge_style) . '">' . htmlescape($data['sname']) . '</span>';
            }

            $entries[] = [
                'itemtype' => self::class,
                'id'       => $data['linkid'],
                'projectname' => $projectlink,
                'name' => $tasklink,
                'tname' => $data['tname'],
                'sname' => $status,
                'percent_done' => Dropdown::getValueWithUnit($data["percent_done"], "%"),
                'plan_start_date' => $data['plan_start_date'],
                'plan_end_date' => $data['plan_end_date'],
                'planned_duration' => $data['planned_duration'],
                '_effect_duration' => ProjectTask::getTotalEffectiveDuration($data['id']),
                'fname' => $father,
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => $columns,
            'formatters' => [
                'projectname' => 'raw_html',
                'name' => 'raw_html',
                'sname' => 'raw_html',
                'plan_start_date' => 'datetime',
                'plan_end_date' => 'datetime',
                'planned_duration' => 'duration',
                '_effect_duration' => 'duration',
                'fname' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
            ],
        ]);
    }
}
