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

use Glpi\Application\View\TemplateRenderer;
use Glpi\CalDAV\Contracts\CalDAVCompatibleItemInterface;
use Glpi\CalDAV\Traits\VobjectConverterTrait;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\DBAL\QuerySubQuery;
use Glpi\RichText\RichText;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Property\FlatText;
use Sabre\VObject\Property\IntegerValue;

/**
 * ProjectTask Class
 *
 * @since 0.85
 **/
class ProjectTask extends CommonDBChild implements CalDAVCompatibleItemInterface
{
    use Glpi\Features\PlanningEvent;
    use VobjectConverterTrait;
    use Glpi\Features\Teamwork;

   // From CommonDBTM
    public $dohistory = true;

   // From CommonDBChild
    public static $itemtype     = 'Project';
    public static $items_id     = 'projects_id';

    protected $team             = [];
    public static $rightname    = 'projecttask';
    protected $usenotepad       = true;

    public $can_be_translated   = true;

    const READMY      = 1;
    const UPDATEMY    = 1024;


    public static function getTypeName($nb = 0)
    {
        return _n('Project task', 'Project tasks', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['tools', Project::class, self::class];
    }

    public static function getIcon()
    {
        return 'ti ti-list-check';
    }

    public static function canView(): bool
    {
        return (Session::haveRightsOr('project', [Project::READALL, Project::READMY])
              || Session::haveRight(self::$rightname, self::READMY));
    }

    /**
     * Is the current user have right to show the current task ?
     *
     * @return boolean
     **/
    public function canViewItem(): bool
    {
        if (!Session::haveAccessToEntity($this->getEntityID(), $this->isRecursive())) {
            return false;
        }
        $project = new Project();
        if ($project->getFromDB($this->fields['projects_id'])) {
            return (Session::haveRight('project', Project::READALL)
                 || (Session::haveRight('project', Project::READMY)
                     && (($project->fields["users_id"] === Session::getLoginUserID())
                         || $project->isInTheManagerGroup()
                         || $project->isInTheTeam()))
                 || (Session::haveRight(self::$rightname, self::READMY)
                     && (($this->fields["users_id"] === Session::getLoginUserID())
                         || $this->isInTheTeam())));
        }
        return false;
    }

    public static function canCreate(): bool
    {
        return (Session::haveRight('project', UPDATE));
    }

    public static function canUpdate(): bool
    {
        return (parent::canUpdate()
              || Session::haveRight(self::$rightname, self::UPDATEMY));
    }

    /**
     * Is the current user have right to edit the current task ?
     *
     * @return boolean
     **/
    public function canUpdateItem(): bool
    {
        if (!Session::haveAccessToEntity($this->getEntityID())) {
            return false;
        }
        $project = new Project();
        if ($project->getFromDB($this->fields['projects_id'])) {
            return (Session::haveRight('project', UPDATE)
                 || (Session::haveRight(self::$rightname, self::UPDATEMY)
                     && (($this->fields["users_id"] === Session::getLoginUserID())
                         || $this->isInTheTeam())));
        }
        return false;
    }

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                ProjectTask_Ticket::class,
                ProjectTaskTeam::class,
                ProjectTaskLink::class,
            ]
        );

        parent::cleanDBonPurge();
    }

    public function getRights($interface = 'central')
    {
        $values = parent::getRights();
        unset($values[READ], $values[CREATE], $values[UPDATE]);

        $values[self::READMY]   = __('See (actor)');
        $values[self::UPDATEMY] = __('Update (actor)');

        return $values;
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(__CLASS__, $ong, $options);
        $this->addStandardTab('ProjectTaskTeam', $ong, $options);
        $this->addStandardTab('Document_Item', $ong, $options);
        $this->addStandardTab('ProjectTask_Ticket', $ong, $options);
        $this->addStandardTab('Notepad', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }

    public function post_getFromDB()
    {
        // Team
        $this->team = ProjectTaskTeam::getTeamFor($this->fields['id']);
    }

    public function post_getEmpty()
    {
        $this->fields['percent_done'] = 0;
    }

    public function post_updateItem($history = true)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        // Handle rich-text images
        $this->input = $this->addFiles(
            $this->input,
            [
                'force_update'  => true,
                'name'          => 'content',
                'content_field' => 'content',
            ]
        );

        if (in_array('plan_start_date', $this->updates, true) || in_array('plan_end_date', $this->updates, true)) {
            // dates have changed, check for planning conflicts on attached team
            $team = ProjectTaskTeam::getTeamFor($this->fields['id']);
            $users = [];
            foreach ($team as $type => $actors) {
                switch ($type) {
                    case User::getType():
                        foreach ($actors as $actor) {
                            $users[$actor['items_id']] = $actor['items_id'];
                        }
                        break;
                    case Group::getType():
                        foreach ($actors as $actor) {
                             $group_iterator = $DB->request([
                                 'SELECT' => 'users_id',
                                 'FROM'   => Group_User::getTable(),
                                 'WHERE'  => ['groups_id' => $actor['items_id']]
                             ]);
                            foreach ($group_iterator as $row) {
                                $users[$row['users_id']] = $row['users_id'];
                            }
                        }
                        break;
                    case Supplier::getType():
                    case Contact::getType():
                        //only Users can be checked for planning conflicts
                        break;
                    default:
                        if (count($actors)) {
                            throw new \RuntimeException($type . " is not (yet?) handled.");
                        }
                }
            }

            foreach ($users as $user) {
                Planning::checkAlreadyPlanned(
                    $user,
                    $this->fields['plan_start_date'],
                    $this->fields['plan_end_date']
                );
            }
        }
        if (in_array('auto_percent_done', $this->updates, true) && (int) $this->input['auto_percent_done'] === 1) {
            // Auto-calculate was toggled. Force recalculation of this and parents
            self::recalculatePercentDone($this->getID());
        } else {
            // Update parent percent_done
            if ($this->fields['projecttasks_id'] > 0) {
                self::recalculatePercentDone($this->fields['projecttasks_id']);
            }
            if ($this->fields['projects_id'] > 0) {
                Project::recalculatePercentDone($this->fields['projects_id']);
            }
        }

        if (isset($this->input['_old_projects_id'])) {
            // Recalculate previous parent project percent done
            Project::recalculatePercentDone($this->input['_old_projects_id']);
        }
        if (isset($this->input['_old_projecttasks_id'])) {
            // Recalculate previous parent task percent done
            self::recalculatePercentDone($this->input['_old_projecttasks_id']);
        }

        if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
            // Read again project to be sure that all data are up to date
            $this->getFromDB($this->fields['id']);
            NotificationEvent::raiseEvent("update", $this);
        }

        // If task has changed of project, update all sub-tasks
        if (in_array('projects_id', $this->updates, true)) {
            foreach (self::getAllForProjectTask($this->getID()) as $task) {
                $task['projects_id'] = $this->fields['projects_id'];
                self::getById($task['id'])->update($task);
            }
        }
    }

    public function post_addItem()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        // Add team members
        if (!empty($this->input['teammember_list'])) {
            $taskteam = new ProjectTaskTeam();
            $members_types = self::getTeamMembersItemtypes();
            foreach ($members_types as $type) {
                $ids = ProjectTaskTeamDropdown::getPostedIds(
                    $this->input['teammember_list'],
                    $type
                );
                foreach ($ids as $id) {
                    $taskteam->add([
                        'projecttasks_id' => $this->fields['id'],
                        'itemtype'        => $type,
                        'items_id'        => $id
                    ]);
                }
            }
        }

        // Handle rich-text images
        $this->input = $this->addFiles(
            $this->input,
            [
                'force_update'  => true,
                'name'          => 'content',
                'content_field' => 'content',
            ]
        );

        // ADD Documents
        $document_items = Document_Item::getItemsAssociatedTo(static::class, $this->fields['id']);
        $override_input['items_id'] = $this->getID();
        foreach ($document_items as $document_item) {
            $document_item->clone($override_input);
        }

        // Update parent percent_done
        if ($this->fields['projecttasks_id'] > 0) {
            self::recalculatePercentDone($this->fields['projecttasks_id']);
        }
        if ($this->fields['projects_id'] > 0) {
            Project::recalculatePercentDone($this->fields['projects_id']);
        }

        if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
            // Clean reload of the project
            $this->getFromDB($this->fields['id']);

            NotificationEvent::raiseEvent('new', $this);
        }
    }

    public function post_deleteItem()
    {
        // Delete all sub-tasks
        foreach (self::getAllForProjectTask($this->getID()) as $task) {
            self::getById($task['id'])->delete($task);
        }
    }

    public function post_restoreItem()
    {
        // If task has a parent, restore it
        if ($this->fields['projecttasks_id'] > 0) {
            $parent = self::getById($this->fields['projecttasks_id']);
            if ($parent->isDeleted()) {
                $parent->restore($parent->fields);
            }
        }

         // Restore all sub-tasks
        foreach (self::getAllForProjectTask($this->getID()) as $task) {
            self::getById($task['id'])->restore($task);
        }
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
     * Get team member count
     *
     * @return integer
     */
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

    public function pre_deleteItem()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!isset($this->input['_disablenotif']) && $CFG_GLPI['use_notifications']) {
            NotificationEvent::raiseEvent('delete', $this);
        }
        return true;
    }

    public function post_purgeItem()
    {
        parent::post_purgeItem();

        // Update parent percent_done
        if ($this->fields['projecttasks_id'] > 0) {
            self::recalculatePercentDone($this->fields['projecttasks_id']);
        }
        if ($this->fields['projects_id'] > 0) {
            Project::recalculatePercentDone($this->fields['projects_id']);
        }
    }

    public function prepareInputForUpdate($input)
    {
        if (isset($input['auto_percent_done']) && $input['auto_percent_done']) {
            unset($input['percent_done']);
        }
        $projectstate_id = $this->recalculateStatus($input);
        if ($projectstate_id !== false) {
            $input['projectstates_id'] = $projectstate_id;
        }

        if (isset($input["plan"])) {
            $input["plan_start_date"] = $input['plan']["begin"];
            $input["plan_end_date"]   = $input['plan']["end"];
        }

        if (isset($input['is_milestone']) && $input['is_milestone']) {
           // Milestone are a precise moment, start date and end dates should have same values.
            if (array_key_exists('plan_start_date', $input)) {
                $input['plan_end_date'] = $input['plan_start_date'];
            }
            if (array_key_exists('real_start_date', $input)) {
                $input['real_end_date'] = $input['real_start_date'];
            }
        }

        if (isset($input['projects_id']) && $input['projects_id'] <= 0) {
            Session::addMessageAfterRedirect(
                __s('A linked project is mandatory'),
                false,
                ERROR
            );
            unset($input['projects_id']);
        }

        if (isset($input['projecttasks_id']) && $input['projecttasks_id'] > 0) {
            if (self::checkCircularRelation($input['id'], $input['projecttasks_id'])) {
                Session::addMessageAfterRedirect(
                    __s('Circular relation found. Parent not updated.'),
                    false,
                    ERROR
                );
                unset($input['projecttasks_id']);
            }
        }
        if (
            $this->fields['projects_id'] > 0 && isset($input['projects_id'])
            && ((int) $input['projects_id'] !== (int) $this->fields['projects_id'])
        ) {
            $input['_old_projects_id'] = $this->fields['projects_id'];
        }
        if (
            $this->fields['projecttasks_id'] > 0 && isset($input['projecttasks_id'])
            && ((int) $input['projecttasks_id'] !== (int) $this->fields['projecttasks_id'])
        ) {
            $input['_old_projecttasks_id'] = $this->fields['projecttasks_id'];
        }
        $input = $this->autoSetDate($input);

        return Project::checkPlanAndRealDates($input);
    }

    public function prepareInputForAdd($input)
    {
        if (!isset($input['projects_id']) || (int) $input['projects_id'] === 0) {
            Session::addMessageAfterRedirect(
                __s('A linked project is mandatory'),
                false,
                ERROR
            );
            return false;
        }

        if (!isset($input['uuid'])) {
            $input['uuid'] = \Ramsey\Uuid\Uuid::uuid4();
        }
        if (!isset($input['users_id'])) {
            $input['users_id'] = Session::getLoginUserID();
        }

        if (isset($input["plan"])) {
            $input["plan_start_date"] = $input['plan']["begin"];
            $input["plan_end_date"]   = $input['plan']["end"];
        }

        if (isset($input['is_milestone']) && $input['is_milestone']) {
           // Milestone are a precise moment, start date and end dates should have same values.
            if (array_key_exists('plan_start_date', $input)) {
                $input['plan_end_date'] = $input['plan_start_date'];
            }
            if (array_key_exists('real_start_date', $input)) {
                $input['real_end_date'] = $input['real_start_date'];
            }
        }

        if ((!isset($input['percent_done']))) {
            $input['percent_done'] = 0;
        }
        $input = $this->autoSetDate($input);

        $projectstate_id = $this->recalculateStatus($input);
        if ($projectstate_id !== false) {
            $input['projectstates_id'] = $projectstate_id;
        }

        return Project::checkPlanAndRealDates($input);
    }

    public function post_clone($source, $history)
    {
        // Clone all sub-tasks of the source and link them to the cloned task
        foreach (self::getAllForProjectTask($source->getID()) as $task) {
            self::getById($task['id'])->clone([
                'projecttasks_id' => $this->getID()
            ]);
        }
    }

    /**
     * Set automatically the real start and end dates if not set
     * @param array $input the input of the form
     *
     * @return array the input with the real start and end dates set if needed
     */
    public function autoSetDate(array $input): array
    {
        $percent_done = (int) ($input['percent_done'] ?? $this->fields['percent_done'] ?? 0);
        $real_start_date = $input['real_start_date'] ?? $this->fields['real_start_date'] ?? null;
        $real_end_date = $input['real_end_date'] ?? $this->fields['real_end_date'] ?? null;

        if ($percent_done < 100 && $real_end_date) {
            $input['real_end_date'] = null;
        } elseif (
            isset($this->fields['percent_done'])
            && (int) $this->fields['percent_done'] === 100 && $percent_done < 100
        ) {
            $input['real_end_date'] = null;
        } elseif (($real_start_date && $real_end_date) || $percent_done === 0) {
            // If both real start and end dates are set, or if the task is not started,
            return $input;
        } else {
            // Set automatically the real start date if not set
            if (empty($real_start_date) && $percent_done > 0) {
                $input['real_start_date'] = Session::getCurrentTime();
            }
            // Set automatically the real end date if not set
            if (empty($real_end_date) && $percent_done === 100) {
                $input['real_end_date'] = Session::getCurrentTime();
            }
            // Set automatically the effective duration if not set
            if (!empty($input['real_start_date']) && !empty($input['real_end_date'])) {
                $input['effective_duration'] = $this->autoSetEffectiveDuration(
                    $input['real_start_date'],
                    $input['real_end_date']
                );
            }
        }

        return $input;
    }

    /**
     * Set automatically the effective duration if not set
     * @param string $startdate the start date
     * @param string $enddate the end date
     *
     * @return int the effective duration
     * @throws Exception if the start or end date is not valid dates
     */
    public function autoSetEffectiveDuration($startdate, $enddate): int
    {
        if (empty($startdate) || empty($enddate)) {
            return 0;
        }
        $start = new DateTime($startdate);
        $end   = new DateTime($enddate);
        return $end->getTimestamp() - $start->getTimestamp();
    }

    /**
     * List of all possible team members itemtypes.
     * @return class-string<CommonDBTM>[]
     */
    public static function getTeamMembersItemtypes(): array
    {
        return [
            User::class,
            Group::class,
            Contact::class,
            Supplier::class,
        ];
    }

    /**
     * Get all tasks for a project
     *
     * @param integer $ID ID of the project
     *
     * @return array of tasks ordered by dates
     **/
    public static function getAllForProject($ID)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $tasks = [];
        $iterator = $DB->request([
            'FROM'   => 'glpi_projecttasks',
            'WHERE'  => [
                'projects_id'  => $ID
            ],
            'ORDERBY'   => ['plan_start_date', 'real_start_date']
        ]);

        foreach ($iterator as $data) {
            $tasks[] = $data;
        }
        return $tasks;
    }

    /**
     * Get all sub-tasks for a project task
     * @since 9.5.0
     * @param integer $ID ID of the project task
     *
     * @return array of tasks ordered by dates
     **/
    public static function getAllForProjectTask($ID)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $tasks = [];
        $iterator = $DB->request([
            'FROM'   => 'glpi_projecttasks',
            'WHERE'  => [
                'projecttasks_id'  => $ID
            ],
            'ORDERBY'   => ['plan_start_date', 'real_start_date']
        ]);

        foreach ($iterator as $data) {
            $tasks[] = $data;
        }
        return $tasks;
    }

    /**
     * Get all linked tickets for a project
     *
     * @param integer $ID ID of the project
     *
     * @return array of tickets
     **/
    public static function getAllTicketsForProject($ID)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'FROM'         => 'glpi_projecttasks_tickets',
            'INNER JOIN'   => [
                'glpi_projecttasks'  => [
                    'ON' => [
                        'glpi_projecttasks_tickets'   => 'projecttasks_id',
                        'glpi_projecttasks'           => 'id'
                    ]
                ]
            ],
            'FIELDS' =>  'tickets_id',
            'WHERE'        => [
                'glpi_projecttasks.projects_id'   => $ID
            ]
        ]);

        $tasks = [];
        foreach ($iterator as $data) {
            $tasks[] = $data['tickets_id'];
        }
        return $tasks;
    }

    /**
     * Print the Project task form
     *
     * @param integer $ID Id of the project task
     * @param array $options of possible options:
     *     - target form target
     *     - projects_id ID of the software for add process
     *
     * @return bool True if displayed, false if item not found or not right to display
     **/
    public function showForm($ID, array $options = [])
    {
        if ($ID > 0) {
            $this->check($ID, READ);
            $duration        = ProjectTask_Ticket::getTicketsTotalActionTime($this->getID());
            $projects_id     = $this->fields['projects_id'];
            $projecttasks_id = $this->fields['projecttasks_id'];
            $recursive       = null; // Wont be used in the case, value is irrelevant
        } else {
            $this->check(-1, CREATE, $options);
            $duration        = null;
            $projects_id     = $options['projects_id'];
            $projecttasks_id = $options['projecttasks_id'];
            $recursive       = $this->fields['is_recursive'];
        }

        $duration_dropdown_to_add = [];
        for ($i = 9; $i <= 100; $i++) {
            $duration_dropdown_to_add[] = $i * HOUR_TIMESTAMP;
        }

        $this->initForm($ID, $options);

        TemplateRenderer::getInstance()->display('pages/tools/project_task.html.twig', [
            'id'                       => $ID,
            'item'                     => $this,
            'params'                   => $options,
            'parent'                   => Project::getById($projects_id),
            'projects_id'              => $projects_id,
            'projecttasks_id'          => $projecttasks_id,
            'recursive'                => $recursive,
            'duration_dropdown_to_add' => $duration_dropdown_to_add,
            'duration'                 => $duration,
            'rand'                     => mt_rand(),
        ]);
        return true;
    }

    /**
     * Get total effective duration of a project task (sum of effective duration + sum of action time of tickets)
     *
     * @param integer $projecttasks_id $projecttasks_id ID of the project task
     *
     * @return integer total effective duration
     **/
    public static function getTotalEffectiveDuration($projecttasks_id)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $item = new static();
        $time = 0;

        if ($item->getFromDB($projecttasks_id)) {
            $time += $item->fields['effective_duration'];
        }

        $iterator = $DB->request([
            'SELECT'    => [
                QueryFunction::sum(
                    expression: 'glpi_tickets.actiontime',
                    alias: 'duration'
                )
            ],
            'FROM'      => self::getTable(),
            'LEFT JOIN' => [
                'glpi_projecttasks_tickets'   => [
                    'FKEY'   => [
                        'glpi_projecttasks_tickets'   => 'projecttasks_id',
                        self::getTable()              => 'id'
                    ]
                ],
                'glpi_tickets'                => [
                    'FKEY'   => [
                        'glpi_projecttasks_tickets'   => 'tickets_id',
                        'glpi_tickets'                => 'id'
                    ]
                ]
            ],
            'WHERE'     => [self::getTable() . '.id' => $projecttasks_id]
        ]);

        if ($row = $iterator->current()) {
            $time += $row['duration'];
        }
        return $time;
    }

    /**
     * Get total effective duration of a project (sum of effective duration + sum of action time of tickets)
     *
     * @param integer $projects_id $project_id ID of the project
     *
     * @return integer total effective duration
     **/
    public static function getTotalEffectiveDurationForProject($projects_id)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => ['projects_id' => $projects_id]
        ]);
        $time = 0;
        foreach ($iterator as $data) {
            $time += static::getTotalEffectiveDuration($data['id']);
        }
        return $time;
    }

    /**
     * Get total planned duration of a project
     *
     * @param integer $projects_id $project_id ID of the project
     *
     * @return integer total effective duration
     **/
    public static function getTotalPlannedDurationForProject($projects_id)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'SELECT'    => [
                QueryFunction::sum(
                    expression: 'planned_duration',
                    alias: 'duration'
                )
            ],
            'FROM'   => self::getTable(),
            'WHERE'  => ['projects_id' => $projects_id]
        ]);

        return count($iterator) ? $iterator->current()['duration'] : 0;
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => 'glpi_projects',
            'field'              => 'name',
            'name'               => Project::getTypeName(1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Father'),
            'datatype'           => 'dropdown',
            'massiveaction'      => true,
         // Add virtual condition to relink table
            'joinparams'         => [
                'condition'          => 'AND 1=1'
            ]
        ];

        $tab[] = [
            'id'                 => '21',
            'table'              => static::getTable(),
            'field'              => 'content',
            'name'               => __('Description'),
            'datatype'           => 'text',
            'htmltext'           => true,
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => 'glpi_projectstates',
            'field'              => 'name',
            'name'               => _x('item', 'State'),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '14',
            'table'              => 'glpi_projecttasktypes',
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => static::getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => static::getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
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
            'step'               => 5
        ];

        $tab[] = [
            'id'                 => '24',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id',
            'name'               => __('Creator'),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => static::getTable(),
            'field'              => 'plan_start_date',
            'name'               => __('Planned start date'),
            'datatype'           => 'datetime'
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => static::getTable(),
            'field'              => 'plan_end_date',
            'name'               => __('Planned end date'),
            'datatype'           => 'datetime'
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => static::getTable(),
            'field'              => 'real_start_date',
            'name'               => __('Real start date'),
            'datatype'           => 'datetime'
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => static::getTable(),
            'field'              => 'real_end_date',
            'name'               => __('Real end date'),
            'datatype'           => 'datetime'
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => static::getTable(),
            'field'              => 'planned_duration',
            'name'               => __('Planned duration'),
            'datatype'           => 'timestamp',
            'min'                => 0,
            'max'                => 100 * HOUR_TIMESTAMP,
            'step'               => HOUR_TIMESTAMP,
            'addfirstminutes'    => true,
            'inhours'            => true
        ];

        $tab[] = [
            'id'                 => '17',
            'table'              => static::getTable(),
            'field'              => 'effective_duration',
            'name'               => __('Effective duration'),
            'datatype'           => 'timestamp',
            'min'                => 0,
            'max'                => 100 * HOUR_TIMESTAMP,
            'step'               => HOUR_TIMESTAMP,
            'addfirstminutes'    => true,
            'inhours'            => true
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => static::getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '18',
            'table'              => static::getTable(),
            'field'              => 'is_milestone',
            'name'               => __('Milestone'),
            'datatype'           => 'bool'
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
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '86',
            'table'              => static::getTable(),
            'field'              => 'is_recursive',
            'name'               => __('Child entities'),
            'datatype'           => 'bool'
        ];

        $tab[] = [
            'id'                 => 'projecttask_team',
            'name'               => ProjectTaskTeam::getTypeName(),
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
                    'table'      => ProjectTaskTeam::getTable(),
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
                    'table'      => ProjectTaskTeam::getTable(),
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
                    'table'      => ProjectTaskTeam::getTable(),
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
                    'table'      => ProjectTaskTeam::getTable(),
                    'joinparams' => [
                        'jointype' => 'child',
                    ]
                ]
            ]
        ];

        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        return $tab;
    }

    /**
     * Show tasks of a project or a task
     *
     * @param Project|ProjectTask $item
     *
     * @return void|false
     **/
    public static function showFor($item)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $ID = $item->getField('id');

        if (!$item->canViewItem()) {
            return false;
        }

        $columns = [
            'name'             => self::getTypeName(Session::getPluralNumber()),
            'tname'            => _n('Type', 'Types', 1),
            'sname'            => __('Status'),
            'percent_done'     => __('Percent done'),
            'plan_start_date'  => __('Planned start date'),
            'plan_end_date'    => __('Planned end date'),
            'planned_duration' => __('Planned duration'),
            '_effect_duration' => __('Effective duration'),
            'fname'            => __('Father'),
            '_task_team'       => ProjectTaskTeam::getTypeName(),
        ];

        $criteria = [
            'SELECT' => [
                'glpi_projecttasks.*',
                'glpi_projecttasktypes.name AS tname',
                'glpi_projectstates.name AS sname',
                'glpi_projectstates.color',
                'father.name AS fname',
                'father.id AS fID'
            ],
            'FROM'   => 'glpi_projecttasks',
            'LEFT JOIN' => [
                'glpi_projecttasktypes'        => [
                    'ON'  => [
                        'glpi_projecttasktypes' => 'id',
                        'glpi_projecttasks'     => 'projecttasktypes_id'
                    ]
                ],
                'glpi_projectstates'          => [
                    'ON'  => [
                        'glpi_projectstates' => 'id',
                        'glpi_projecttasks'  => 'projectstates_id'
                    ]
                ],
                'glpi_projecttasks AS father' => [
                    'ON'  => [
                        'father' => 'id',
                        'glpi_projecttasks'  => 'projecttasks_id'
                    ]
                ]
            ],
            'WHERE'  => [], //$where
            'ORDERBY'   => [] // $sort $order";
        ];

        if (isset($_GET["order"]) && ($_GET["order"] == "DESC")) {
            $order = "DESC";
        } else {
            $order = "ASC";
        }

        if (!isset($_GET["sort"]) || empty($_GET["sort"])) {
            $_GET["sort"] = "plan_start_date";
        }

        if (isset($_GET["sort"]) && !empty($_GET["sort"]) && isset($columns[$_GET["sort"]])) {
            $sort = [$_GET["sort"] . " $order"];
            $ui_sort = $_GET['sort'];
        } else {
            $sort = ["plan_start_date $order", "name"];
            $ui_sort = 'plan_start_date';
        }
        $criteria['ORDERBY'] = $sort;

        $canedit = false;
        if ($item->getType() == 'Project') {
            $canedit = $item->canEdit($ID);
        }

        switch ($item->getType()) {
            case 'Project':
                $criteria['WHERE']['glpi_projecttasks.projects_id'] = $ID;
                break;

            case 'ProjectTask':
                $criteria['WHERE']['glpi_projecttasks.projecttasks_id'] = $ID;
                break;

            default: // Not available type
                return;
        }

        echo "<div class='spaced'>";

        if ($canedit) {
            echo "<div class='center firstbloc'>";
            echo "<a class='btn btn-primary' href='" . ProjectTask::getFormURL() . "?projects_id=$ID'>" .
                _sx('button', 'Add a task') . "</a>";
            echo "</div>";
        }

        $rand = mt_rand();
        if (
            ($item->getType() == 'ProjectTask')
            && $item->can($ID, UPDATE)
        ) {
            echo "<div class='firstbloc'>";
            echo "<form name='projecttask_form$rand' id='projecttask_form$rand' method='post'
                action='" . Toolbox::getItemTypeFormURL('ProjectTask') . "'>";
            $projet = $item->fields['projects_id'];
            echo "<a href='" . Toolbox::getItemTypeFormURL('ProjectTask') . "?projecttasks_id=$ID&amp;projects_id=$projet'>";
            echo __s('Create a sub task from this task of project');
            echo "</a>";
            Html::closeForm();
            echo "</div>";
        }

        if (Session::haveTranslations('ProjectTaskType', 'name')) {
            $criteria['SELECT'][] = 'namet2.value AS transname2';
            $criteria['LEFT JOIN']['glpi_dropdowntranslations AS namet2'] = [
                'ON'  => [
                    'namet2'             => 'items_id',
                    'glpi_projecttasks'  => 'projecttasktypes_id', [
                        'AND' => [
                            'namet2.itemtype' => 'ProjectTaskType',
                            'namet2.language' => $_SESSION['glpilanguage'],
                            'namet2.field'    => 'name'
                        ]
                    ]
                ]
            ];
        }

        if (Session::haveTranslations('ProjectState', 'name')) {
            $criteria['SELECT'][] = 'namet3.value AS transname3';
            $criteria['LEFT JOIN']['glpi_dropdowntranslations AS namet3'] = [
                'ON'  => [
                    'namet3'             => 'items_id',
                    'glpi_projectstates' => 'id', [
                        'AND' => [
                            'namet3.itemtype' => 'ProjectState',
                            'namet3.language' => $_SESSION['glpilanguage'],
                            'namet3.field'    => 'name'
                        ]
                    ]
                ]
            ];
        }

        Session::initNavigateListItems(
            'ProjectTask',
            //TRANS : %1$s is the itemtype name,
            //       %2$s is the name of the item (used for headings of a list)
                                     sprintf(
                                         __('%1$s = %2$s'),
                                         $item::getTypeName(1),
                                         $item->getName()
                                     )
        );

        $iterator = $DB->request($criteria);
        if (count($criteria)) {
            $massive_action_form_id = 'mass' . str_replace('\\', '', static::class) . $rand;
            if ($canedit) {
                Html::openMassiveActionsForm($massive_action_form_id);
                $massiveactionparams = [
                    'num_displayed' => min($_SESSION['glpilist_limit'], count($criteria)),
                    'specific_actions' => [
                        'update' => _x('button', 'Update'),
                        'clone' => _x('button', 'Clone'),
                        'delete' => _x('button', 'Put in trashbin'),
                        'restore' => _x('button', 'Restore'),
                        'purge' => _x('button', 'Delete permanently')
                    ]
                ];
                Html::showMassiveActions($massiveactionparams);
            }

            echo "<table class='tab_cadre_fixehov'>";

            $header = '<tr>';
            if ($canedit) {
                $header  .= "<th width='10'>";
                $header    .= Html::getCheckAllAsCheckbox($massive_action_form_id);
                $header    .= "</th>";
            }
            foreach ($columns as $key => $val) {
                $val = htmlescape($val);

                // Non order column
                if ($key[0] == '_') {
                    $header .= "<th>$val</th>";
                } else {
                    $header .= "<th" . ($ui_sort == $key ? " class='order_$order'" : '') . ">" .
                     "<a href='javascript:reloadTab(\"sort=$key&amp;order=" .
                        (($order == "ASC") ? "DESC" : "ASC") . "&amp;start=0\");'>$val</a></th>";
                }
            }
            $header .= "</tr>";
            echo $header;

            foreach ($iterator as $data) {
                Session::addToNavigateListItems('ProjectTask', $data['id']);
                $rand = mt_rand();
                echo "<tr class='" . ($data['is_deleted'] ? "tab_bg_1_2" : "tab_bg_2") . "'>";

                if ($canedit) {
                    echo "<td width='10'>";
                    Html::showMassiveActionCheckBox(__CLASS__, $data['id']);
                    echo "</td>";
                } else {
                    echo "<td></td>";
                }

                echo "<td>";
                $link = "<a id='ProjectTask" . (int)$data["id"] . $rand . "' href='" .
                        htmlescape(ProjectTask::getFormURLWithID($data['id'])) . "'>" . htmlescape($data['name']) .
                        (empty($data['name']) ? "(" . (int)$data['id'] . ")" : "") . "</a>";
                echo sprintf(
                    __s('%1$s %2$s'),
                    $link,
                    Html::showToolTip(
                        RichText::getEnhancedHtml($data['content']),
                        ['display' => false,
                            'applyto' => "ProjectTask" . (int)$data["id"] . $rand
                        ]
                    )
                );
                echo "</td>";
                $name = !empty($data['transname2']) ? $data['transname2'] : $data['tname'];
                echo "<td>" . htmlescape($name) . "</td>";
                echo "<td";
                $statename = !empty($data['transname3']) ? $data['transname3'] : $data['sname'];
                echo " style=\"background-color:" . htmlescape($data['color']) . "\"";
                echo ">" . htmlescape($statename) . "</td>";
                echo "<td>";
                echo Dropdown::getValueWithUnit($data["percent_done"], "%");
                echo "</td>";
                echo "<td>" . Html::convDateTime($data['plan_start_date']) . "</td>";
                echo "<td>" . Html::convDateTime($data['plan_end_date']) . "</td>";
                echo "<td>" . Html::timestampToString($data['planned_duration'], false) . "</td>";
                echo "<td>" . Html::timestampToString(
                    self::getTotalEffectiveDuration($data['id']),
                    false
                ) . "</td>";
                echo "<td>";
                if ($data['projecttasks_id'] > 0) {
                      $father = Dropdown::getDropdownName('glpi_projecttasks', $data['projecttasks_id']);
                      echo "<a id='ProjectTask" . (int)$data["projecttasks_id"] . $rand . "' href='" .
                        htmlescape(ProjectTask::getFormURLWithID($data['projecttasks_id'])) . "'>" . htmlescape($father) .
                        (empty($father) ? "(" . (int)$data['projecttasks_id'] . ")" : "") . "</a>";
                }
                echo '</td><td>';
                $projecttask = new ProjectTask();
                $projecttask->getFromDB($data['id']);
                foreach ($projecttask->getTeam() as $projecttaskteam) {
                    $item = getItemForItemtype($projecttaskteam['itemtype']);
                    echo "<a href='" . htmlescape($item->getFormURLWithID($projecttaskteam['items_id'])) . "'>" .
                        htmlescape($projecttaskteam['display_name']) . '</a><br>';
                }
                echo "</td></tr>";
            }
            echo $header;
            echo "</table>";

            if ($canedit) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
                Html::closeForm();
            }
        } else {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . __s('No item found') . "</th></tr>";
            echo "</table>";
        }

        echo "</div>";
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $nb = 0;
        switch ($item::class) {
            case Project::class:
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $nb = countElementsInTable(
                        static::getTable(),
                        ['projects_id' => $item->getID()]
                    );
                }
                return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::class);

            case self::class:
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $nb = countElementsInTable(
                        static::getTable(),
                        ['projecttasks_id' => $item->getID()]
                    );
                }
                return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::class);
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item::class) {
            case Project::class:
            case self::class:
                self::showFor($item);
                break;
        }
        return true;
    }

    /**
     * Show team for a project task
     *
     * @param ProjectTask $task object
     *
     * @return boolean
     **/
    public function showTeam(ProjectTask $task)
    {
        // TODO : permit to simple add member of project team ?

        $ID      = $task->fields['id'];
        $canedit = $task->canEdit($ID);

        $rand = mt_rand();
        $nb   = $task->getTeamCount();

        if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form name='projecttaskteam_form$rand' id='projecttaskteam_form$rand' ";
            echo " method='post' action='" . Toolbox::getItemTypeFormURL('ProjectTaskTeam') . "'>";
            echo "<input type='hidden' name='projecttasks_id' value='$ID'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'><th colspan='2'>" . __s('Add a team member') . "</tr>";
            echo "<tr class='tab_bg_2'><td>";

            $params = ['itemtypes'       => ProjectTeam::$available_types,
                'entity_restrict' => ($task->fields['is_recursive']
                                               ? getSonsOf(
                                                   'glpi_entities',
                                                   $task->fields['entities_id']
                                               )
                                               : $task->fields['entities_id']),
                'checkright'      => true
            ];
            $addrand = Dropdown::showSelectItemFromItemtypes($params);

            echo "</td>";
            echo "<td width='20%'>";
            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
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
            $header_top    .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_top    .= "</th>";
            $header_bottom .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_bottom .= "</th>";
        }
        $header_end .= "<th>" . _sn('Type', 'Types', 1) . "</th>";
        $header_end .= "<th>" . _sn('Member', 'Members', Session::getPluralNumber()) . "</th>";
        $header_end .= "</tr>";
        echo $header_begin . $header_top . $header_end;

        foreach (ProjectTaskTeam::$available_types as $type) {
            if (isset($task->team[$type]) && count($task->team[$type])) {
                if ($item = getItemForItemtype($type)) {
                    foreach ($task->team[$type] as $data) {
                        $item->getFromDB($data['items_id']);
                        echo "<tr class='tab_bg_2'>";
                        if ($canedit) {
                             echo "<td>";
                             Html::showMassiveActionCheckBox('ProjectTaskTeam', $data["id"]);
                             echo "</td>";
                        }
                        echo "<td>" . htmlescape($item->getTypeName(1)) . "</td>";
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

    /**
     * Get the list of active project tasks for a list of groups.
     *
     * @param array $groups_id The group IDs.
     * @return array The list of projecttask IDs.
     */
    public static function getActiveProjectTaskIDsForGroup(array $groups_id): array
    {
        /** @var \DBmysql $DB */
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
                        self::getTable() => 'projectstates_id'
                    ]
                ]
            ],
            'WHERE' => [
                self::getTable() . '.id' => new QuerySubQuery([
                    'SELECT' => [
                        'projecttasks_id'
                    ],
                    'FROM' => ProjectTaskTeam::getTable(),
                    'WHERE' => [
                        ['itemtype' => 'Group', 'items_id' => $groups_id],
                        'OR' => [
                            [ProjectState::getTable() . '.is_finished' => 0],
                            [ProjectState::getTable() . '.is_finished' => null]
                        ]
                    ]
                ])
            ]
        ];

        return iterator_to_array($DB->request($req), false);
    }

    /**
     * Get the list of active project tasks for a list of users
     *
     * @param array $users_id The user IDs.
     * @param bool $search_in_groups Whether to search in groups.
     * @return array The list of projecttask IDs.
     */
    public static function getActiveProjectTaskIDsForUser(array $users_id, bool $search_in_groups = true): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (count($users_id) === 0) {
            return [];
        }

        $groups_sub_query = new QuerySubQuery([
            'SELECT' => [
                'groups_id'
            ],
            'FROM' => Group_User::getTable(),
            'WHERE' => [
                'users_id' => $users_id
            ]
        ]);

        $crit = [
            ['itemtype' => 'User', 'items_id' => $users_id]
        ];

        if ($search_in_groups) {
            $crit[] = ['itemtype' => 'Group', 'items_id' => $groups_sub_query];
        }

        $req = [
            'SELECT' => self::getTable() . '.id',
            'FROM' => self::getTable(),
            'LEFT JOIN' => [
                ProjectState::getTable() => [
                    'FKEY' => [
                        ProjectState::getTable() => 'id',
                        self::getTable() => 'projectstates_id'
                    ]
                ]
            ],
            'WHERE' => [
                self::getTable() . '.id' => new QuerySubQuery([
                    'SELECT' => [
                        'projecttasks_id'
                    ],
                    'FROM' => ProjectTaskTeam::getTable(),
                    'WHERE' => [
                        'OR' => $crit
                    ]
                ]),
                'OR' => [
                    [ProjectState::getTable() . '.is_finished' => 0],
                    [ProjectState::getTable() . '.is_finished' => null]
                ]
            ]
        ];

        return iterator_to_array($DB->request($req), false);
    }

    /**
     *  Show the list of projecttasks for a user in the personal view or for a group in the group view
     *
     * @param string $itemtype The itemtype (User or Group)
     * @return void
     * @used-by Central
     */
    public static function showListForCentral(string $itemtype): void
    {
        $projecttasks_id = [];
        switch ($itemtype) {
            case 'User':
                $projecttasks_id = self::getActiveProjectTaskIDsForUser([Session::getLoginUserID()], false);
                break;
            case 'Group':
                $projecttasks_id = self::getActiveProjectTaskIDsForGroup($_SESSION['glpigroups']);
                break;
        }

        // If no project tasks are found, do not display anything
        if (empty($projecttasks_id)) {
            return;
        }

        $options = [
            'criteria' => [
                [
                    'link' => 'AND',
                    'field' => ($itemtype === 'User') ? 87 : 88, // 87 = ProjectTask teams - Users, 88 = ProjectTask teams - Groups
                    'searchtype' => 'equals',
                    'value' => ($itemtype === 'User') ? 'myself' : 'mygroups' // 'myself' or 'mygroups'
                ]
            ]
        ];

        // Retrieve finished project states to exclude them from the search
        $project_states = (new ProjectState())->find([
            'is_finished' => 1
        ]);

        foreach ($project_states as $state) {
            $options['criteria'][] = [
                'link' => 'AND',
                'field' => 12,
                'searchtype' => 'notequals',
                'value' => $state['id']
            ];
        }

        $displayed_row_count = min(count($projecttasks_id), (int)$_SESSION['glpidisplay_count_on_home']);

        $twig_params = [
            'class'       => 'table table-borderless table-striped table-hover card-table',
            'header_rows' => [
                [
                    [
                        'colspan' => 4,
                        'content' => sprintf(
                            '<a href="%s">%s</a>',
                            htmlescape(self::getSearchURL() . '?' . Toolbox::append_params($options)),
                            Html::makeTitle(__('Ongoing projects tasks'), $displayed_row_count, count($projecttasks_id))
                        ),
                    ]
                ],
                [
                    [
                        'content' => __s('Name'),
                        'style'   => 'width: 30%'
                    ],
                    [
                        'content' => _sn('State', 'States', 1),
                        'style'   => 'width: 30%'
                    ],
                    [
                        'content' => _sn('Project', 'Projects', 1),
                        'style'   => 'width: 30%'
                    ],
                    [
                        'content' => __s('Percent done'),
                        'style'   => 'width: 10%'
                    ]
                ]
            ],
            'rows' => []
        ];

        foreach ($projecttasks_id as $key => $raw_projecttask) {
            if ($key >= $displayed_row_count) {
                break;
            }

            $projecttask = self::getById($raw_projecttask['id']);
            $project = Project::getById($projecttask->fields['projects_id']);
            $state = ProjectState::getById($projecttask->fields['projectstates_id']);

            $twig_params['rows'][] = [
                'values' => [
                    [
                        'content' => $projecttask->getLink(),
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
                        'content' => $project->getLink(),
                    ],
                    [
                        'content' => $projecttask->fields['percent_done'] . '%'
                    ]
                ]
            ];
        }

        TemplateRenderer::getInstance()->display('components/table.html.twig', $twig_params);
    }

    /**
     * Populate the planning with planned project tasks
     *
     * @since 9.1
     *
     * @param array $options of possible options:
     *    - who         ID of the user (0 = undefined)
     *    - whogroup    ID of the group of users (0 = undefined)
     *    - begin       Date
     *    - end         Date
     *    - color
     *    - event_type_color
     *
     * @return array of planning item
     **/
    public static function populatePlanning($options = []): array
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $interv = [];
        $ttask  = new self();

        if (
            !isset($options['begin'], $options['end'])
            || ($options['begin'] === 'NULL') || ($options['end'] === 'NULL')
        ) {
            return $interv;
        }

        $default_options = [
            'genical'             => false,
            'color'               => '',
            'event_type_color'    => '',
        ];
        $options = array_merge($default_options, $options);

        $who       = $options['who'];
        $whogroup  = $options['whogroup'];
        $begin     = $options['begin'];
        $end       = $options['end'];

       // Get items to print
        $ADDWHERE = [];

        if ($whogroup === "mine") {
            if (isset($_SESSION['glpigroups'])) {
                $whogroup = $_SESSION['glpigroups'];
            } else if ($who > 0) {
                $whogroup = array_column(Group_User::getUserGroups($who), 'id');
            }
        }

        if ($who > 0) {
            $ADDWHERE['glpi_projecttaskteams.itemtype'] = 'User';
            $ADDWHERE['glpi_projecttaskteams.items_id'] = $who;
        }

        if ($whogroup > 0) {
            $ADDWHERE['glpi_projecttaskteams.itemtype'] = 'Group';
            $ADDWHERE['glpi_projecttaskteams.items_id'] = $whogroup;
        }

        if (!count($ADDWHERE)) {
            $ADDWHERE = [
                'glpi_projecttaskteams.itemtype' => 'User',
                'glpi_projecttaskteams.items_id' => new QuerySubQuery([
                    'SELECT'          => 'glpi_profiles_users.users_id',
                    'DISTINCT'        => true,
                    'FROM'            => 'glpi_profiles',
                    'LEFT JOIN'       => [
                        'glpi_profiles_users'   => [
                            'ON' => [
                                'glpi_profiles_users'   => 'profiles_id',
                                'glpi_profiles'         => 'id'
                            ]
                        ]
                    ],
                    'WHERE'           => [
                        'glpi_profiles.interface'  => 'central'
                    ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $_SESSION['glpiactive_entity'], 1)
                ])
            ];
        }

        if (!isset($options['display_done_events']) || !$options['display_done_events']) {
            $ADDWHERE['glpi_projecttasks.percent_done'] = ['<', 100];
            $ADDWHERE[] = ['OR' => [
                ['glpi_projectstates.is_finished'  => 0],
                ['glpi_projectstates.is_finished'  => null]
            ]
            ];
        }

        $ttask_table = $ttask::getTable();
        $SELECT = [$ttask_table . '.*'];
        $WHERE = $ADDWHERE;
        if (isset($options['not_planned'])) {
            //not planned case
            $bdate = QueryFunction::dateSub(
                date: $ttask_table . '.date_creation',
                interval: new QueryExpression($DB::quoteName($ttask_table . '.planned_duration')),
                interval_unit: 'SECOND',
            );
            $edate = QueryFunction::dateAdd(
                date: $ttask_table . '.date_creation',
                interval: new QueryExpression($DB::quoteName($ttask_table . '.planned_duration')),
                interval_unit: 'SECOND',
            );
            $SELECT[] = new QueryExpression($bdate, 'notp_date');
            $SELECT[] = new QueryExpression($edate, 'notp_edate');

            $WHERE = array_merge($WHERE, [
                $ttask_table . '.plan_start_date'   => null,
                $ttask_table . '.plan_end_date'     => null,
                $ttask_table . '.planned_duration'  => ['>', 0],
                //begin is replaced with creation tim minus duration
                new QueryExpression($edate . " >= '" . $begin . "'"),
                new QueryExpression($bdate . " <= '" . $end . "'")
            ]);
        } else {
           //std case: get tasks for current view dates
            $WHERE[$ttask_table . '.plan_end_date'] = ['>=', $begin];
            $WHERE[$ttask_table . '.plan_start_date'] = ['<=', $end];
        }

        $iterator = $DB->request([
            'SELECT'       => $SELECT,
            'FROM'         => 'glpi_projecttaskteams',
            'INNER JOIN'   => [
                $ttask_table => [
                    'ON' => [
                        'glpi_projecttaskteams' => 'projecttasks_id',
                        $ttask_table      => 'id'
                    ]
                ]
            ],
            'LEFT JOIN'    => [
                'glpi_projectstates' => [
                    'ON' => [
                        $ttask_table   => 'projectstates_id',
                        'glpi_projectstates' => 'id'
                    ]
                ]
            ],
            'WHERE'        => $WHERE,
            'ORDERBY'      => $ttask_table . '.plan_start_date'
        ]);

        $interv = [];
        $task   = new self();

        if (count($iterator)) {
            foreach ($iterator as $data) {
                if ($task->getFromDB($data["id"])) {
                    if (isset($data['notp_date'])) {
                        $data['plan_start_date'] = $data['notp_date'];
                        $data['plan_end_date'] = $data['notp_edate'];
                    }
                    $key = $data["plan_start_date"] .
                      "$$$" . "ProjectTask" .
                      "$$$" . $data["id"] .
                      "$$$" . $who . "$$$" . $whogroup;
                    $interv[$key]['color']            = $options['color'];
                    $interv[$key]['event_type_color'] = $options['event_type_color'];
                    $interv[$key]['itemtype']         = 'ProjectTask';
                    if (!$options['genical']) {
                        $interv[$key]["url"] = Project::getFormURLWithID($task->fields['projects_id']);
                    } else {
                        $interv[$key]["url"] = $CFG_GLPI["url_base"] .
                                        Project::getFormURLWithID($task->fields['projects_id'], false);
                    }
                    $interv[$key]["ajaxurl"] = $CFG_GLPI["root_doc"] . "/ajax/planning.php" .
                                          "?action=edit_event_form" .
                                          "&itemtype=ProjectTask" .
                                          "&id=" . $data['id'] .
                                          "&url=" . $interv[$key]["url"];

                    $interv[$key][$task::getForeignKeyField()] = $data["id"];
                    $interv[$key]["id"]                        = $data["id"];
                    $interv[$key]["users_id"]                  = $data["users_id"];

                    if (strcmp($begin, $data["plan_start_date"]) > 0) {
                        $interv[$key]["begin"] = $begin;
                    } else {
                        $interv[$key]["begin"] = $data["plan_start_date"];
                    }

                    if (strcmp($end, $data["plan_end_date"]) < 0) {
                           $interv[$key]["end"]   = $end;
                    } else {
                        $interv[$key]["end"]   = $data["plan_end_date"];
                    }

                    $interv[$key]["name"]     = $task->fields["name"];
                    $interv[$key]["content"]  = $task->fields["content"] !== null
                    ? RichText::getSafeHtml($task->fields["content"])
                    : '';
                    $interv[$key]["status"]   = $task->fields["percent_done"];

                    $ttask->getFromDB($data["id"]);
                    $interv[$key]["editable"] = $ttask->canUpdateItem();
                }
            }
        }

        return $interv;
    }

    /**
     * Populate the planning with not planned project tasks
     *
     * @since 9.1
     *
     * @param array $options of possible options:
     *    - who         ID of the user (0 = undefined)
     *    - whogroup    ID of the group of users (0 = undefined)
     *    - begin       Date
     *    - end         Date
     *    - color
     *    - event_type_color
     *
     * @return array of planning item
     * @used-by Planning
     **/
    public static function populateNotPlanned($options = []): array
    {
        $options['not_planned'] = true;
        return self::populatePlanning($options);
    }

    /**
     * Display a Planning Item
     *
     * @since 9.1
     *
     * @param array $val Array of the items to display
     * @param integer $who ID of the user (0 if all)
     * @param string $type Position of the item in the time block (in, through, begin or end)
     *                         (default '')
     * @param integer $complete (Not used)
     *
     * @return string
     **/
    public static function displayPlanningItem(array $val, $who, $type = "", $complete = 0)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $html = "";
        $rand     = mt_rand();
        $users_id = "";  // show users_id project task
        $img      = "rdv_private.png"; // default icon for project task

        if ((int) $val["users_id"] !== Session::getLoginUserID()) {
            $users_id = "<br>" . sprintf(__('%1$s: %2$s'), __('By'), getUserName($val["users_id"]));
            $img      = "rdv_public.png";
        }

        $html .= "<img src='" . $CFG_GLPI["root_doc"] . "/pics/" . $img . "' alt='' title=\"" .
             self::getTypeName(1) . "\">&nbsp;";
        $html .= "<a id='project_task_" . $val["id"] . $rand . "' href='" .
             self::getFormURLWithID($val["id"]) . "'>";

        switch ($type) {
            case "in":
               //TRANS: %1$s is the start time of a planned item, %2$s is the end
                $beginend = sprintf(
                    __('From %1$s to %2$s'),
                    date("H:i", strtotime($val["begin"])),
                    date("H:i", strtotime($val["end"]))
                );
                $html .= sprintf(__('%1$s: %2$s'), $beginend, Html::resume_text($val["name"], 80));
                break;

            case "through":
                $html .= Html::resume_text($val["name"], 80);
                break;

            case "begin":
                $start = sprintf(__('Start at %s'), date("H:i", strtotime($val["begin"])));
                $html .= sprintf(__('%1$s: %2$s'), $start, Html::resume_text($val["name"], 80));
                break;

            case "end":
                $end = sprintf(__('End at %s'), date("H:i", strtotime($val["end"])));
                $html .= sprintf(__('%1$s: %2$s'), $end, Html::resume_text($val["name"], 80));
                break;
        }

        $html .= $users_id;
        $html .= "</a>";

        $html .= "<div class='b'>";
        $html .= sprintf(__('%1$s: %2$s'), __('Percent done'), $val["status"] . "%");
        $html .= "</div>";

        // $val['content'] has already been sanitized and decoded by self::populatePlanning()
        $content = $val['content'];
        $html .= "<div class='event-description rich_text_container'>" . $content . "</div>";

        $parent = getItemForItemtype($val['itemtype']);
        $parent->getFromDB($val[$parent::getForeignKeyField()]);
        $html .= $parent->getLink(['icon' => true, 'forceid' => true]) . "<br>";
        $html .= "<span>" . Entity::badgeCompletenameById($parent->getEntityID()) . "</span><br>";
        return $html;
    }

    /**
     * Update the specified project task's percent_done based on the percent_done of sub-tasks.
     * This function indirectly updates the percent done for all parent tasks if they are set to automatically update.
     * The parent project's percent_done is not updated here to avoid duplicate updates.
     * @since 9.5.0
     * @return boolean False if the specified project task is not set to automatically update the percent done.
     */
    public static function recalculatePercentDone($ID)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $projecttask = new self();
        $projecttask->getFromDB($ID);
        if (!$projecttask->fields['auto_percent_done']) {
            return false;
        }

        $iterator = $DB->request([
            'SELECT' => [
                QueryFunction::cast(
                    expression: QueryFunction::avg('percent_done'),
                    type: 'UNSIGNED',
                    alias: 'percent_done'
                )
            ],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'projecttasks_id' => $ID
            ]
        ]);
        if ($iterator->count()) {
            $percent_done = $iterator->current()['percent_done'];
        } else {
            $percent_done = 0;
        }
        $projecttask->update([
            'id'                 => $ID,
            'percent_done'       => $percent_done,
        ]);
        return true;
    }

    /**
     * Recalculate the status of a project task based on the percent_done.
     * @since 11.0.0
     * @param array $input
     * @return integer|false
     */
    public function recalculateStatus(array $input): int|false
    {
        /** @var \DBmysql $DB */
        global $DB;

        $auto_projectstates = $input['auto_projectstates'] ?? $this->fields['auto_projectstates'] ?? false;
        $percent_done = $input['percent_done'] ?? $this->fields['percent_done'] ?? null;

        if (!$auto_projectstates || $percent_done === null) {
            return false;
        }
        $config = Config::getConfigurationValues('core');
        if ((int) $percent_done === 0 || (int) $percent_done < 0) {
            $state_id = $config['projecttask_unstarted_states_id'] ?? 0;
        } elseif ((int) $percent_done === 100) {
            $state_id = $config['projecttask_completed_states_id'] ?? 0;
        } else {
            $state_id = $config['projecttask_inprogress_states_id'] ?? 0;
        }
        $state = ProjectState::getById($state_id);
        if (!$state) {
            return false;
        }
        return $state->getID();
    }

    public static function getGroupItemsAsVCalendars($groups_id)
    {
        return self::getItemsAsVCalendars(
            [
                ProjectTaskTeam::getTableField('itemtype') => Group::class,
                ProjectTaskTeam::getTableField('items_id') => $groups_id,
            ]
        );
    }

    public static function getUserItemsAsVCalendars($users_id)
    {
        return self::getItemsAsVCalendars(
            [
                ProjectTaskTeam::getTableField('itemtype') => User::class,
                ProjectTaskTeam::getTableField('items_id') => $users_id,
            ]
        );
    }

    /**
     * Returns items as VCalendar objects.
     *
     * @param array $criteria
     *
     * @return \Sabre\VObject\Component\VCalendar[]
     */
    private static function getItemsAsVCalendars(array $criteria)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $query = [
            'FROM'       => self::getTable(),
            'INNER JOIN' => [
                ProjectTaskTeam::getTable() => [
                    'ON' => [
                        ProjectTaskTeam::getTable() => 'projecttasks_id',
                        self::getTable()            => 'id',
                    ],
                ],
            ],
            'WHERE'      => $criteria,
        ];

        $tasks_iterator = $DB->request($query);

        $vcalendars = [];
        foreach ($tasks_iterator as $task) {
            $item = new self();
            $item->getFromResultSet($task);
            $vcalendar = $item->getAsVCalendar();
            if (null !== $vcalendar) {
                $vcalendars[] = $vcalendar;
            }
        }

        return $vcalendars;
    }

    /**
     * {@inheritdoc}
     * @return VCalendar|null
     * @throws Exception If one or more of the datetimes are invalid
     */
    public function getAsVCalendar(): ?VCalendar
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!$this->canViewItem()) {
            return null;
        }

        $is_planned = !empty($this->fields['plan_start_date']) && !empty($this->fields['plan_end_date']);
        $target_component = $this->getTargetCaldavComponent($is_planned, true);
        if (null === $target_component) {
            return null;
        }

        $vcalendar = $this->getVCalendarForItem($this, $target_component);

        $fields = $this->fields;
        $utc_tz = new \DateTimeZone('UTC');

        $vcomp = $vcalendar->getBaseComponent();
        if ($vcomp === null) {
            return null;
        }

        if ('VTODO' === $target_component) {
            if ($is_planned) {
                $vcomp->DTSTART = (new \DateTime($fields['plan_start_date']))->setTimeZone($utc_tz);
                $vcomp->DUE = (new \DateTime($fields['plan_end_date']))->setTimeZone($utc_tz);
            }
            $vcomp->STATUS = 100 === (int) $fields['percent_done'] ? 'COMPLETED' : 'NEEDS-ACTION';
            $vcomp->{'PERCENT-COMPLETE'} = $fields['percent_done'];
        } else if ('VEVENT' === $target_component) {
            if ($is_planned) {
                $vcomp->DTSTART = (new \DateTime($fields['plan_start_date']))->setTimeZone($utc_tz);
                $vcomp->DTEND   = (new \DateTime($fields['plan_end_date']))->setTimeZone($utc_tz);
            }
        }

        $vcomp->URL = $CFG_GLPI['url_base'] . Project::getFormURLWithID($fields['projects_id'], false);

        return $vcalendar;
    }

    public function getInputFromVCalendar(VCalendar $vcalendar)
    {
        $vtodo = $vcalendar->getBaseComponent();

        if (null !== $vtodo->RRULE) {
            throw new \UnexpectedValueException('RRULE not yet implemented for Project tasks');
        }

        $input = $this->getCommonInputFromVcomponent($vtodo, $this->isNewItem());

        if ($vtodo->DESCRIPTION instanceof FlatText) {
           // Description is not in HTML format
            $input['content'] = $vtodo->DESCRIPTION->getValue();
        }

        if ($vtodo->{'PERCENT-COMPLETE'} instanceof IntegerValue) {
            $input['percent_done'] = $vtodo->{'PERCENT-COMPLETE'}->getValue();
        } else if (array_key_exists('state', $input) && $input['state'] == \Planning::DONE) {
           // Consider task as done if status is DONE
            $input['percent_done'] = 100;
        }

        return $input;
    }

    public function prepareInputForClone($input)
    {
        $input['uuid'] = \Ramsey\Uuid\Uuid::uuid4();
        return $input;
    }

    public static function getTeamRoles(): array
    {
        return Project::getTeamRoles();
    }

    public static function getTeamRoleName(int $role, int $nb = 1): string
    {
        return Project::getTeamRoleName($role, $nb);
    }

    public static function getTeamItemtypes(): array
    {
        return ProjectTaskTeam::$available_types;
    }

    public function addTeamMember(string $itemtype, int $items_id, array $params = []): bool
    {
        $projecttask_team = new ProjectTaskTeam();
        $result = $projecttask_team->add([
            'projecttasks_id' => $this->getID(),
            'itemtype'        => $itemtype,
            'items_id'        => $items_id
        ]);
        return (bool) $result;
    }

    public function deleteTeamMember(string $itemtype, int $items_id, array $params = []): bool
    {
        $projecttask_team = new ProjectTaskTeam();
        $result = $projecttask_team->deleteByCriteria([
            'projecttasks_id' => $this->getID(),
            'itemtype'        => $itemtype,
            'items_id'        => $items_id
        ]);
        return (bool) $result;
    }

    public function getTeam(): array
    {
        $team = ProjectTaskTeam::getTeamFor($this->getID(), true);
        // Flatten the array
        $result = [];
        foreach ($team as $itemtype_members) {
            foreach ($itemtype_members as $member) {
                $result[] = $member;
            }
        }
        return $result;
    }
}
