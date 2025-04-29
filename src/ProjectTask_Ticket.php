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
use Glpi\RichText\RichText;

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
            switch ($item->getType()) {
                case 'ProjectTask':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForItem($item);
                    }
                    return self::createTabEntry(Ticket::getTypeName(Session::getPluralNumber()), $nb);

                case 'Ticket':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForItem($item);
                    }
                    return self::createTabEntry(ProjectTask::getTypeName(Session::getPluralNumber()), $nb);
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case 'ProjectTask':
                self::showForProjectTask($item);
                break;

            case 'Ticket':
                self::showForTicket($item);
                break;
        }
        return true;
    }


    /**
     * Get total duration of tickets linked to a project task
     *
     * @param $projecttasks_id    integer    $projecttasks_id ID of the project task
     *
     * @return integer total actiontime
     **/
    public static function getTicketsTotalActionTime($projecttasks_id)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'SELECT'       => new QueryExpression('SUM(glpi_tickets.actiontime) AS duration'),
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

        if ($row = $iterator->current()) {
            return $row['duration'];
        }
        return 0;
    }


    /**
     * Show tickets for a projecttask
     *
     * @param $projecttask ProjectTask object
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
        $numrows = count($iterator);

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
                'link_itemtype' => __CLASS__,
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
            ]);
        }

        echo "<div class='spaced'>";
        if ($canedit && $numrows) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['num_displayed'    => min($_SESSION['glpilist_limit'], $numrows),
                'container'        => 'mass' . __CLASS__ . $rand,
            ];
            Html::showMassiveActions($massiveactionparams);
        }

        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr><th colspan='12'>" . Ticket::getTypeName($numrows) . "</th>";
        echo "</tr>";
        if ($numrows) {
            Ticket::commonListHeader(Search::HTML_OUTPUT, 'mass' . __CLASS__ . $rand);
            Session::initNavigateListItems(
                'Ticket',
                //TRANS : %1$s is the itemtype name,
                //        %2$s is the name of the item (used for headings of a list)
                sprintf(
                    __('%1$s = %2$s'),
                    ProjectTask::getTypeName(1),
                    $projecttask->fields["name"]
                )
            );

            $i = 0;
            foreach ($tickets as $data) {
                Session::addToNavigateListItems('Ticket', $data["id"]);
                Ticket::showShort(
                    $data['id'],
                    [
                        'row_num'                => $i,
                        'type_for_massiveaction' => __CLASS__,
                        'id_for_massiveaction'   => $data['linkid'],
                    ]
                );
                $i++;
            }
        }
        echo "</table>";
        if ($canedit && $numrows) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>";
    }


    /**
     * Show projecttasks for a ticket
     *
     * @param $ticket Ticket object
     **/
    public static function showForTicket(Ticket $ticket)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $ID = $ticket->getField('id');
        if (!$ticket->can($ID, READ)) {
            return false;
        }

        $canedit = $ticket->canEdit($ID);
        $rand    = mt_rand();

        $iterator = self::getListForItem($ticket);
        $numrows = count($iterator);

        $pjtasks = [];
        $used    = [];
        foreach ($iterator as $data) {
            $pjtasks[$data['id']] = $data;
            $used[$data['id']]    = $data['id'];
        }

        if (
            $canedit
            && !in_array($ticket->fields['status'], array_merge(
                $ticket->getClosedStatusArray(),
                $ticket->getSolvedStatusArray()
            ))
        ) {
            $rand = mt_rand();

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
                'link_itemtype' => __CLASS__,
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
            ]);
        }

        echo "<div class='spaced'>";

        if ($numrows) {
            $columns = ['projectname'      => Project::getTypeName(Session::getPluralNumber()),
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

            if (isset($_GET["order"]) && ($_GET["order"] == "DESC")) {
                $order = "DESC";
            } else {
                $order = "ASC";
            }

            if (!isset($_GET["sort"]) || empty($_GET["sort"])) {
                $_GET["sort"] = "plan_start_date";
            }

            if (isset($columns[$_GET["sort"]])) {
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

            Session::initNavigateListItems(
                'ProjectTask',
                //TRANS : %1$s is the itemtype name,
                //       %2$s is the name of the item (used for headings of a list)
                sprintf(
                    __('%1$s = %2$s'),
                    $ticket::getTypeName(1),
                    $ticket->getName()
                )
            );

            if (count($iterator)) {
                echo "<table class='tab_cadre_fixehov'>";
                echo "<tr><th colspan='10'>" . ProjectTask::getTypeName($numrows) . "</th>";
                echo "</tr>";

                $header = '<tr>';
                foreach ($columns as $key => $val) {
                    // Non order column
                    if ($key[0] == '_') {
                        $header .= "<th>$val</th>";
                    } else {
                        $header .= "<th" . ($sort == "$key" ? " class='order_$order'" : '') . ">" .
                              "<a href='javascript:reloadTab(\"sort=$key&amp;order=" .
                                 (($order == "ASC") ? "DESC" : "ASC") . "&amp;start=0\");'>$val</a></th>";
                    }
                }
                $header .= "</tr>\n";
                echo $header;

                foreach ($iterator as $data) {
                    Session::addToNavigateListItems('ProjectTask', $data['id']);
                    $rand = mt_rand();
                    echo "<tr class='tab_bg_2'>";
                    echo "<td>";
                    $link = "<a id='Project" . $data["projects_id"] . $rand . "' href='" .
                          Project::getFormURLWithID($data['projects_id']) . "'>" . $data['projectname'] .
                          (empty($data['projectname']) ? "(" . $data['projects_id'] . ")" : "") . "</a>";
                    echo sprintf(
                        __('%1$s %2$s'),
                        $link,
                        Html::showToolTip(
                            $data['projectcontent'],
                            ['display' => false,
                                'applyto' => "Project" . $data["projects_id"] . $rand,
                            ]
                        )
                    );
                    echo "</td>";
                    echo "<td>";
                    $link = "<a id='ProjectTask" . $data["id"] . $rand . "' href='" .
                          ProjectTask::getFormURLWithID($data['id']) . "'>" . $data['name'] .
                          (empty($data['name']) ? "(" . $data['id'] . ")" : "") . "</a>";
                    echo sprintf(
                        __('%1$s %2$s'),
                        $link,
                        Html::showToolTip(
                            RichText::getEnhancedHtml($data['content']),
                            ['display' => false,
                                'applyto' => "ProjectTask" . $data["id"] . $rand,
                            ]
                        )
                    );
                    echo "</td>";
                    echo "<td>" . $data['tname'] . "</td>";
                    echo "<td";
                    echo " style=\"background-color:" . $data['color'] . "\"";
                    echo ">" . $data['sname'] . "</td>";
                    echo "<td>";
                    echo Dropdown::getValueWithUnit($data["percent_done"], "%");
                    echo "</td>";
                    echo "<td>" . Html::convDateTime($data['plan_start_date']) . "</td>";
                    echo "<td>" . Html::convDateTime($data['plan_end_date']) . "</td>";
                    echo "<td>" . Html::timestampToString($data['planned_duration'], false) . "</td>";
                    echo "<td>" . Html::timestampToString(
                        ProjectTask::getTotalEffectiveDuration($data['id']),
                        false
                    ) . "</td>";
                    echo "<td>";
                    if ($data['projecttasks_id'] > 0) {
                        $father = Dropdown::getDropdownName('glpi_projecttasks', $data['projecttasks_id']);
                        echo "<a id='ProjectTask" . $data["projecttasks_id"] . $rand . "' href='" .
                        ProjectTask::getFormURLWithID($data['projecttasks_id']) . "'>" . $father .
                        (empty($father) ? "(" . $data['projecttasks_id'] . ")" : "") . "</a>";
                    }
                    echo "</td></tr>";
                }
                echo $header;
                echo "</table>\n";
            } else {
                echo "<table class='tab_cadre_fixe'>";
                echo "<tr><th>" . __('No item found') . "</th></tr>";
                echo "</table>\n";
            }
            echo "</div>";
        }
    }
}
