<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}


/**
 * ProjectTask Class
 *
 * @since 0.85
**/
class ProjectTask extends CommonDBChild {

   // From CommonDBTM
   public $dohistory = true;

   // From CommonDBChild
   static public $itemtype     = 'Project';
   static public $items_id     = 'projects_id';

   protected $team             = [];
   static $rightname           = 'projecttask';
   protected $usenotepad       = true;

   public $can_be_translated   = true;

   const READMY      = 1;
   const UPDATEMY    = 1024;



   static function getTypeName($nb = 0) {
      return _n('Project task', 'Project tasks', $nb);
   }


   static function canPurge() {
      return static::canChild('canUpdate');
   }


   static function canView() {

      return (Session::haveRightsOr('project', [Project::READALL, Project::READMY])
              || Session::haveRight(self::$rightname, ProjectTask::READMY));
   }


   /**
    * Is the current user have right to show the current task ?
    *
    * @return boolean
   **/
   function canViewItem() {

      if (!Session::haveAccessToEntity($this->getEntityID())) {
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


   static function canCreate() {
      return (Session::haveRight('project', UPDATE));
   }


   static function canUpdate() {

      return (parent::canUpdate()
              || Session::haveRight(self::$rightname, self::UPDATEMY));
   }


   /**
    * Is the current user have right to edit the current task ?
    *
    * @return boolean
   **/
   function canUpdateItem() {

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



   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            ProjectTask_Ticket::class,
            ProjectTaskTeam::class,
         ]
      );

      parent::cleanDBonPurge();
   }

   /**
    * Duplicate all tasks from a project template to his clone
    *
    * @since 9.2
    *
    * @param integer $oldid        ID of the item to clone
    * @param integer $newid        ID of the item cloned
    **/
   static function cloneProjectTask ($oldid, $newid) {
      global $DB;

      foreach ($DB->request('glpi_projecttasks',
                            ['WHERE' => "`projects_id` = '$oldid'"]) as $data) {
         $cd                  = new self();
         unset($data['id']);
         $data['projects_id'] = $newid;
         $cd->add($data);
      }
   }

   /**
    * @see commonDBTM::getRights()
    **/
   function getRights($interface = 'central') {

      $values = parent::getRights();
      unset($values[READ], $values[CREATE], $values[UPDATE], $values[DELETE], $values[PURGE]);

      $values[self::READMY]   = __('See (actor)');
      $values[self::UPDATEMY] = __('Update (actor)');

      return $values;
   }


   function defineTabs($options = []) {

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


   function post_getFromDB() {
      // Team
      $this->team    = ProjectTaskTeam::getTeamFor($this->fields['id']);
   }

   function post_getEmpty() {
      $this->fields['percent_done'] = 0;
   }


   function post_updateItem($history = 1) {
      global $CFG_GLPI;

      if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
         // Read again project to be sure that all data are up to date
         $this->getFromDB($this->fields['id']);
         NotificationEvent::raiseEvent("update", $this);
      }
   }


   function post_addItem() {
      global $DB, $CFG_GLPI;

      // ADD Documents
      Document_Item::cloneItem('ProjectTaskTemplate',
                               $this->input["projecttasktemplates_id"],
                               $this->fields['id'],
                               $this->getType());

      if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
         // Clean reload of the project
         $this->getFromDB($this->fields['id']);

         NotificationEvent::raiseEvent('new', $this);
      }
   }


   /**
    * Is the current user in the team?
    *
    * @return boolean
   **/
   function isInTheTeam() {

      if (isset($this->team['User']) && count($this->team['User'])) {
         foreach ($this->team['User'] as $data) {
            if ($data['items_id'] == Session::getLoginUserID()) {
               return true;
            }
         }
      }

      if (isset($_SESSION['glpigroups']) && count($_SESSION['glpigroups'])
          && isset($this->team['Group']) && count($this->team['Group'])) {
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
    * Get team member count
    *
    * @return number
    */
   function getTeamCount() {

      $nb = 0;
      if (is_array($this->team) && count($this->team)) {
         foreach ($this->team as $val) {
            $nb +=  count($val);
         }
      }
      return $nb;
   }


   function pre_deleteItem() {
      global $CFG_GLPI;

      if (!isset($this->input['_disablenotif']) && $CFG_GLPI['use_notifications']) {
         NotificationEvent::raiseEvent('delete', $this);
      }
      return true;
   }


   function prepareInputForUpdate($input) {

      if (isset($input["plan"])) {
         $input["plan_start_date"] = $input['plan']["begin"];
         $input["plan_end_date"]   = $input['plan']["end"];
      }

      if (isset($input['is_milestone'])
            && $input['is_milestone']) {
         $input['plan_end_date'] = $input['plan_start_date'];
         $input['real_end_date'] = $input['real_start_date'];
      }
      return Project::checkPlanAndRealDates($input);
   }


   function prepareInputForAdd($input) {

      if (!isset($input['users_id'])) {
         $input['users_id'] = Session::getLoginUserID();
      }
      if (!isset($input['date'])) {
         $input['date'] = $_SESSION['glpi_currenttime'];
      }

      if (isset($input['is_milestone'])
            && $input['is_milestone']) {
         $input['plan_end_date'] = $input['plan_start_date'];
         $input['real_end_date'] = $input['real_start_date'];
      }

      return Project::checkPlanAndRealDates($input);
   }


    /**
    * Get all tasks for a project
    *
    * @param $ID        integer  Id of the project
    *
    * @return array of tasks ordered by dates
   **/
   static function getAllForProject($ID) {
      global $DB;

      $tasks = [];
      foreach ($DB->request('glpi_projecttasks',
                            ["projects_id" => $ID,
                                  'ORDER'       => ['plan_start_date',
                                                         'real_start_date']]) as $data) {
         $tasks[] = $data;
      }
      return $tasks;
   }


    /**
    * Get all linked tickets for a project
    *
    * @param $ID        integer  Id of the project
    *
    * @return array of tickets
   **/
   static function getAllTicketsForProject($ID) {
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
      while ($data = $iterator->next()) {
         $tasks[] = $data['tickets_id'];
      }
      return $tasks;
   }


   /**
    * Print the Project task form
    *
    * @param $ID        integer  Id of the project task
    * @param $options   array    of possible options:
    *     - target form target
    *     - projects_id ID of the software for add process
    *
    * @return true if displayed  false if item not found or not right to display
   **/
   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      $rand_template           = mt_rand();
      $rand_name               = mt_rand();
      $rand_description        = mt_rand();
      $rand_comment            = mt_rand();
      $rand_project            = mt_rand();
      $rand_state              = mt_rand();
      $rand_type               = mt_rand();
      $rand_percent            = mt_rand();
      $rand_milestone          = mt_rand();
      $rand_plan_start_date    = mt_rand();
      $rand_plan_end_date      = mt_rand();
      $rand_real_start_date    = mt_rand();
      $rand_real_end_date      = mt_rand();
      $rand_effective_duration = mt_rand();
      $rand_planned_duration   = mt_rand();

      if ($ID > 0) {
         $this->check($ID, READ);
         $projects_id     = $this->fields['projects_id'];
         $projecttasks_id = $this->fields['projecttasks_id'];
      } else {
         $this->check(-1, CREATE, $options);
         $projects_id     = $options['projects_id'];
         $projecttasks_id = $options['projecttasks_id'];
         $recursive       = $this->fields['is_recursive'];
      }

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td style='width:100px'>"._n('Project task template', 'Project task templates', 1)."</td><td>";
      ProjectTaskTemplate::dropdown(['value'     => $this->fields['projecttasktemplates_id'],
                                     'entity'    => $this->getEntityID(),
                                     'rand'      => $rand_template,
                                     'on_change' => 'projecttasktemplate_update(this.value)']);
      echo "</td>";
      echo "<td colspan='2'></td>";
      echo "</tr>";
      echo Html::scriptBlock('
         function projecttasktemplate_update(value) {
            $.ajax({
               url: "' . $CFG_GLPI["root_doc"] . '/ajax/projecttask.php",
               type: "POST",
               data: {
                  projecttasktemplates_id: value
               }
            }).done(function(data) {

               // set input name
               $("#textfield_name'.$rand_name.'").val(data.name);

               // set textarea description
               $("#description'.$rand_description.'").val(data.description);
                // set textarea comment
               $("#comment'.$rand_comment.'").val(data.comments);

               // set project task
               $("#dropdown_projecttasks_id'.$rand_project.'").trigger("setValue", data.projecttasks_id);
               // set state
               $("#dropdown_projectstates_id'.$rand_state.'").trigger("setValue", data.projectstates_id);
               // set type
               $("#dropdown_projecttasktypes_id'.$rand_type.'").trigger("setValue", data.projecttasktypes_id);
               // set percent done
               $("#dropdown_percent_done'.$rand_percent.'").trigger("setValue", data.percent_done);
               // set milestone
               $("#dropdown_is_milestone'.$rand_milestone.'").trigger("setValue", data.is_milestone);

               // set plan_start_date
               $("#showdate'.$rand_plan_start_date.'").val(data.plan_start_date);
               // set plan_end_date
               $("#showdate'.$rand_plan_end_date.'").val(data.plan_end_date);
               // set real_start_date
               $("#showdate'.$rand_real_start_date.'").val(data.real_start_date);
               // set real_end_date
               $("#showdate'.$rand_real_end_date.'").val(data.real_end_date);

               // set effective_duration
               $("#dropdown_effective_duration'.$rand_effective_duration.'").trigger("setValue", data.effective_duration);
               // set planned_duration
               $("#dropdown_planned_duration'.$rand_planned_duration.'").trigger("setValue", data.planned_duration);

            });
         }
      ');

      echo "<tr class='tab_bg_1'><td>"._n('Project', 'Projects', Session::getPluralNumber())."</td>";
      echo "<td>";
      if ($this->isNewID($ID)) {
         echo "<input type='hidden' name='projects_id' value='$projects_id'>";
         echo "<input type='hidden' name='is_recursive' value='$recursive'>";
      }
      echo "<a href='".Project::getFormURLWithID($projects_id)."'>".
             Dropdown::getDropdownName("glpi_projects", $projects_id)."</a>";
      echo "</td>";
      echo "<td>".__('As child of')."</td>";
      echo "<td>";
      $this->dropdown([
         'entity'    => $this->fields['entities_id'],
         'value'     => $projecttasks_id,
         'rand'      => $rand_project,
         'condition' => ['glpi_projecttasks.projects_id' => $this->fields['projects_id']],
         'used'      => [$this->fields['id']]
      ]);
      echo "</td></tr>";

      $showuserlink = 0;
      if (Session::haveRight('user', READ)) {
         $showuserlink = 1;
      }

      if ($ID) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Creation date')."</td>";
         echo "<td>";
         echo sprintf(__('%1$s by %2$s'), Html::convDateTime($this->fields["date"]),
                                       getUserName($this->fields["users_id"], $showuserlink));
         echo "</td>";
         echo "<td>".__('Last update')."</td>";
         echo "<td>";
         echo Html::convDateTime($this->fields["date_mod"]);
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_1'><td>".__('Name')."</td>";
      echo "<td colspan='3'>";
      Html::autocompletionTextField($this, "name", ['size' => 80,
                                                    'rand' => $rand_name]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._x('item', 'State')."</td>";
      echo "<td>";
      ProjectState::dropdown(['value' => $this->fields["projectstates_id"],
                              'rand'  => $rand_state]);
      echo "</td>";
      echo "<td>".__('Type')."</td>";
      echo "<td>";
      ProjectTaskType::dropdown(['value' => $this->fields["projecttasktypes_id"],
                                 'rand'  => $rand_type]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Percent done')."</td>";
      echo "<td>";
      Dropdown::showNumber("percent_done", ['value' => $this->fields['percent_done'],
                                            'rand'  => $rand_percent,
                                            'min'   => 0,
                                            'max'   => 100,
                                            'step'  => 5,
                                            'unit'  => '%']);

      echo "</td>";
      echo "<td>";
      echo __('Milestone');
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo("is_milestone", $this->fields["is_milestone"], -1, ['rand' => $rand_milestone]);
      $js = "$('#dropdown_is_milestone$rand_milestone').on('change', function(e) {
         $('tr.is_milestone').toggleClass('starthidden');
      })";
      echo Html::scriptBlock($js);
      echo "</td>";
      echo "</tr>";

      echo "<tr><td colspan='4' class='subheader'>".__('Planning')."</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Planned start date')."</td>";
      echo "<td>";
      Html::showDateTimeField("plan_start_date",
                              ['value' => $this->fields['plan_start_date'],
                               'rand'  => $rand_plan_start_date]);
      echo "</td>";
      echo "<td>".__('Real start date')."</td>";
      echo "<td>";
      Html::showDateTimeField("real_start_date",
                              ['value' => $this->fields['real_start_date'],
                               'rand'  => $rand_real_start_date]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Planned end date')."</td>";
      echo "<td>";
      Html::showDateTimeField("plan_end_date", ['value' => $this->fields['plan_end_date'],
                                                'rand'  => $rand_plan_end_date]);
      echo "</td>";
      echo "<td>".__('Real end date')."</td>";
      echo "<td>";
      Html::showDateTimeField("real_end_date", ['value' => $this->fields['real_end_date'],
                                                'rand'  => $rand_real_end_date]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1 is_milestone" . ($this->fields['is_milestone'] ? ' starthidden' : '')  . "'>";
      echo "<td>".__('Planned duration')."</td>";
      echo "<td>";

      $toadd = [];
      for ($i = 9; $i <= 100; $i++) {
         $toadd[] = $i*HOUR_TIMESTAMP;
      }

      Dropdown::showTimeStamp("planned_duration",
                              ['min'             => 0,
                               'max'             => 8*HOUR_TIMESTAMP,
                               'rand'            => $rand_planned_duration,
                               'value'           => $this->fields["planned_duration"],
                               'addfirstminutes' => true,
                               'inhours'         => true,
                               'toadd'           => $toadd]);
      echo "</td>";
      echo "<td>".__('Effective duration')."</td>";
      echo "<td>";
      Dropdown::showTimeStamp("effective_duration",
                              ['min'             => 0,
                               'max'             => 8*HOUR_TIMESTAMP,
                               'rand'            => $rand_effective_duration,
                               'value'           => $this->fields["effective_duration"],
                               'addfirstminutes' => true,
                               'inhours'         => true,
                               'toadd'           => $toadd]);
      if ($ID) {
         $ticket_duration = ProjectTask_Ticket::getTicketsTotalActionTime($this->getID());
         echo "<br>";
         printf(__('%1$s: %2$s'), __('Tickets duration'),
                Html::timestampToString($ticket_duration, false));
         echo '<br>';
         printf(__('%1$s: %2$s'), __('Total duration'),
                Html::timestampToString($ticket_duration+$this->fields["effective_duration"],
                                        false));
      }
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Description')."</td>";
      echo "<td colspan='3'>";
      echo "<textarea id='description$rand_description' name='content' cols='90' rows='6'>".$this->fields["content"].
           "</textarea>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Comments')."</td>";
      echo "<td colspan='3'>";
      echo "<textarea id='comment$rand_comment' name='comment' cols='90' rows='6'>".$this->fields["comment"].
           "</textarea>";
      echo "</td></tr>\n";

      $this->showFormButtons($options);

      return true;
   }


   /**
    * Get total effective duration of a project task (sum of effective duration + sum of action time of tickets)
    *
    * @param $projecttasks_id    integer    $projecttasks_id ID of the project task
    *
    * @return integer total effective duration
   **/
   static function getTotalEffectiveDuration($projecttasks_id) {
      global $DB;

      $item = new static();
      $time = 0;

      if ($item->getFromDB($projecttasks_id)) {
         $time += $item->fields['effective_duration'];
      }

      $iterator = $DB->request([
         'SELECT'    => new QueryExpression('SUM(glpi_tickets.actiontime) AS duration'),
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

      if ($row = $iterator->next()) {
         $time += $row['duration'];
      }
      return $time;
   }


   /**
    * Get total effective duration of a project (sum of effective duration + sum of action time of tickets)
    *
    * @param $projects_id    integer    $project_id ID of the project
    *
    * @return integer total effective duration
   **/
   static function getTotalEffectiveDurationForProject($projects_id) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => 'id',
         'FROM'   => self::getTable(),
         'WHERE'  => ['projects_id' => $projects_id]
      ]);
      $time = 0;
      while ($data = $iterator->next()) {
         $time += static::getTotalEffectiveDuration($data['id']);
      }
      return $time;
   }


   /**
    * Get total planned duration of a project
    *
    * @param $projects_id    integer    $project_id ID of the project
    *
    * @return integer total effective duration
   **/
   static function getTotalPlannedDurationForProject($projects_id) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => new QueryExpression('SUM(planned_duration) AS duration'),
         'FROM'   => self::getTable(),
         'WHERE'  => ['projects_id' => $projects_id]
      ]);

      while ($data = $iterator->next()) {
         return $data['duration'];
      }
      return 0;
   }


   function rawSearchOptions() {
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
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => 'glpi_projects',
         'field'              => 'name',
         'name'               => __('Project'),
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Father'),
         'datatype'           => 'dropdown',
         'massiveaction'      => false,
         // Add virtual condition to relink table
         'joinparams'         => [
            'condition'          => 'AND 1=1'
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
         'name'               => __('Type'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '15',
         'table'              => $this->getTable(),
         'field'              => 'date',
         'name'               => __('Opening date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
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
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'planned_duration',
         'name'               => __('Planned duration'),
         'datatype'           => 'timestamp',
         'min'                => 0,
         'max'                => 100*HOUR_TIMESTAMP,
         'step'               => HOUR_TIMESTAMP,
         'addfirstminutes'    => true,
         'inhours'            => true
      ];

      $tab[] = [
         'id'                 => '17',
         'table'              => $this->getTable(),
         'field'              => 'effective_duration',
         'name'               => __('Effective duration'),
         'datatype'           => 'timestamp',
         'min'                => 0,
         'max'                => 100*HOUR_TIMESTAMP,
         'step'               => HOUR_TIMESTAMP,
         'addfirstminutes'    => true,
         'inhours'            => true
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '18',
         'table'              => $this->getTable(),
         'field'              => 'is_milestone',
         'name'               => __('Milestone'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '86',
         'table'              => $this->getTable(),
         'field'              => 'is_recursive',
         'name'               => __('Child entities'),
         'datatype'           => 'bool'
      ];

      $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

      return $tab;
   }


   /**
    * Show tasks of a project
    *
    * @param $item Project or ProjectTask object
    *
    * @return nothing
   **/
   static function showFor($item) {
      global $DB, $CFG_GLPI;

      $ID = $item->getField('id');

      if (!$item->canViewItem()) {
         return false;
      }

      $columns = [
         'name'             => self::getTypeName(Session::getPluralNumber()),
         'tname'            => __('Type'),
         'sname'            => __('Status'),
         'percent_done'     => __('Percent done'),
         'plan_start_date'  => __('Planned start date'),
         'plan_end_date'    => __('Planned end date'),
         'planned_duration' => __('Planned duration'),
         '_effect_duration' => __('Effective duration'),
         'fname'            => __('Father')
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
         $sort = "`".$_GET["sort"]."`";
      } else {
         $sort = "`plan_start_date` $order, `name`";
      }

      $canedit = false;
      if ($item->getType() =='Project') {
         $canedit = $item->canEdit($ID);
      }

      switch ($item->getType()) {
         case 'Project' :
            $where = "WHERE `glpi_projecttasks`.`projects_id` = '$ID'";
            break;

         case 'ProjectTask' :
            $where = "WHERE `glpi_projecttasks`.`projecttasks_id` = '$ID'";
            break;

         default : // Not available type
            return;
      }

      echo "<div class='spaced'>";

      if ($canedit) {
         echo "<div class='center firstbloc'>";
         echo "<a class='vsubmit' href='".ProjectTask::getFormURL()."?projects_id=$ID'>".
                _x('button', 'Add a task')."</a>";
         echo "</div>";
      }

      if (($item->getType() == 'ProjectTask')
          && $item->can($ID, UPDATE)) {
         $rand = mt_rand();
         echo "<div class='firstbloc'>";
         echo "<form name='projecttask_form$rand' id='projecttask_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL('ProjectTask')."'>";
         $projet = $item->fields['projects_id'];
         echo "<a href='".Toolbox::getItemTypeFormURL('ProjectTask')."?projecttasks_id=$ID&amp;projects_id=$projet'>";
         echo __('Create a sub task from this task of project');
         echo "</a>";
         Html::closeForm();
         echo "</div>";
      }

      $addselect = '';
      $addjoin = '';
      if (Session::haveTranslations('ProjectTaskType', 'name')) {
         $addselect .= ", `namet2`.`value` AS transname2";
         $addjoin   .= " LEFT JOIN `glpi_dropdowntranslations` AS namet2
                           ON (`namet2`.`itemtype` = 'ProjectTaskType'
                               AND `namet2`.`items_id` = `glpi_projecttasks`.`projecttasktypes_id`
                               AND `namet2`.`language` = '".$_SESSION['glpilanguage']."'
                               AND `namet2`.`field` = 'name')";
      }

      if (Session::haveTranslations('ProjectState', 'name')) {
         $addselect .= ", `namet3`.`value` AS transname3";
         $addjoin   .= "LEFT JOIN `glpi_dropdowntranslations` AS namet3
                           ON (`namet3`.`itemtype` = 'ProjectState'
                               AND `namet3`.`language` = '".$_SESSION['glpilanguage']."'
                               AND `namet3`.`field` = 'name')";
         $where     .= " AND `namet3`.`items_id` = `glpi_projectstates`.`id` ";
      }

      $query = "SELECT `glpi_projecttasks`.*,
                       `glpi_projecttasktypes`.`name` AS tname,
                       `glpi_projectstates`.`name` AS sname,
                       `glpi_projectstates`.`color`,
                       `father`.`name` AS fname,
                       `father`.`id` AS fID
                       $addselect
                FROM `glpi_projecttasks`
                $addjoin
                LEFT JOIN `glpi_projecttasktypes`
                   ON (`glpi_projecttasktypes`.`id` = `glpi_projecttasks`.`projecttasktypes_id`)
                LEFT JOIN `glpi_projectstates`
                   ON (`glpi_projectstates`.`id` = `glpi_projecttasks`.`projectstates_id`)
                LEFT JOIN `glpi_projecttasks` as father
                   ON (`father`.`id` = `glpi_projecttasks`.`projecttasks_id`)
                $where
                ORDER BY $sort $order";

      Session::initNavigateListItems('ProjectTask',
            //TRANS : %1$s is the itemtype name,
            //       %2$s is the name of the item (used for headings of a list)
                                     sprintf(__('%1$s = %2$s'), $item::getTypeName(1),
                                             $item->getName()));

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            echo "<table class='tab_cadre_fixehov'>";

            $header = '<tr>';
            foreach ($columns as $key => $val) {
               // Non order column
               if ($key[0] == '_') {
                  $header .= "<th>$val</th>";
               } else {
                  $header .= "<th".($sort == "`$key`" ? " class='order_$order'" : '').">".
                        "<a href='javascript:reloadTab(\"sort=$key&amp;order=".
                           (($order == "ASC") ?"DESC":"ASC")."&amp;start=0\");'>$val</a></th>";
               }
            }
            $header .= "</tr>\n";
            echo $header;

            while ($data = $DB->fetchAssoc($result)) {
               Session::addToNavigateListItems('ProjectTask', $data['id']);
               $rand = mt_rand();
               echo "<tr class='tab_bg_2'>";
               echo "<td>";
               $link = "<a id='ProjectTask".$data["id"].$rand."' href='".
                         ProjectTask::getFormURLWithID($data['id'])."'>".$data['name'].
                         (empty($data['name'])?"(".$data['id'].")":"")."</a>";
               echo sprintf(__('%1$s %2$s'), $link,
                             Html::showToolTip($data['content'],
                                               ['display' => false,
                                                     'applyto' => "ProjectTask".$data["id"].$rand]));
               echo "</td>";
               $name = !empty($data['transname2'])?$data['transname2']:$data['tname'];
               echo "<td>".$name."</td>";
               echo "<td";
               $statename = !empty($data['transname3'])?$data['transname3']:$data['sname'];
               echo " style=\"background-color:".$data['color']."\"";
               echo ">".$statename."</td>";
               echo "<td>";
               echo Dropdown::getValueWithUnit($data["percent_done"], "%");
               echo "</td>";
               echo "<td>".Html::convDateTime($data['plan_start_date'])."</td>";
               echo "<td>".Html::convDateTime($data['plan_end_date'])."</td>";
               echo "<td>".Html::timestampToString($data['planned_duration'], false)."</td>";
               echo "<td>".Html::timestampToString(self::getTotalEffectiveDuration($data['id']),
                                                   false)."</td>";
               echo "<td>";
               if ($data['projecttasks_id']>0) {
                  $father = Dropdown::getDropdownName('glpi_projecttasks', $data['projecttasks_id']);
                  echo "<a id='ProjectTask".$data["projecttasks_id"].$rand."' href='".
                           ProjectTask::getFormURLWithID($data['projecttasks_id'])."'>".$father.
                           (empty($father)?"(".$data['projecttasks_id'].")":"")."</a>";
               }
               echo "</td></tr>";
            }
            echo $header;
            echo "</table>\n";

         } else {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>".__('No item found')."</th></tr>";
            echo "</table>\n";
         }

      }
      echo "</div>";
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      $nb = 0;
      switch ($item->getType()) {
         case 'Project' :
            if ($_SESSION['glpishow_count_on_tabs']) {
               $nb = countElementsInTable($this->getTable(),
                                          ['projects_id' => $item->getID()]);
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);

         case __CLASS__ :
            if ($_SESSION['glpishow_count_on_tabs']) {
               $nb = countElementsInTable($this->getTable(),
                                          ['projecttasks_id' => $item->getID()]);
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'Project' :
            self::showFor($item);
            break;

         case __CLASS__ :
            self::showFor($item);
            break;
      }
      return true;
   }


   /**
    * Show team for a project task
    *
    * @param $task   ProjectTask object
    *
    * @return boolean
   **/
   function showTeam(ProjectTask $task) {
      global $DB, $CFG_GLPI;

      /// TODO : permit to simple add member of project team ?

      $ID      = $task->fields['id'];
      $canedit = $task->canEdit($ID);

      $rand = mt_rand();
      $nb   = 0;
      $nb   = $task->getTeamCount();

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='projecttaskteam_form$rand' id='projecttaskteam_form$rand' ";
         echo " method='post' action='".Toolbox::getItemTypeFormURL('ProjectTaskTeam')."'>";
         echo "<input type='hidden' name='projecttasks_id' value='$ID'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='2'>".__('Add a team member')."</tr>";
         echo "<tr class='tab_bg_2'><td>";

         $params = ['itemtypes'       => ProjectTeam::$available_types,
                         'entity_restrict' => ($task->fields['is_recursive']
                                               ? getSonsOf('glpi_entities',
                                                           $task->fields['entities_id'])
                                               : $task->fields['entities_id']),
                         'checkright'      => true];
         $addrand = Dropdown::showSelectItemFromItemtypes($params);

         echo "</td>";
         echo "<td width='20%'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td>";
         echo "</tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }
      echo "<div class='spaced'>";
      if ($canedit && $nb) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $nb),
                                      'container'     => 'mass'.__CLASS__.$rand];
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';
      if ($canedit && $nb) {
         $header_top    .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_top    .= "</th>";
         $header_bottom .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_bottom .= "</th>";
      }
      $header_end .= "<th>".__('Type')."</th>";
      $header_end .= "<th>"._n('Member', 'Members', Session::getPluralNumber())."</th>";
      $header_end .= "</tr>";
      echo $header_begin.$header_top.$header_end;

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
                  echo "<td>".$item->getTypeName(1)."</td>";
                  echo "<td>".$item->getLink()."</td>";
                  echo "</tr>";
               }
            }
         }
      }
      if ($nb) {
         echo $header_begin.$header_bottom.$header_end;
      }
      echo "</table>";
      if ($canedit && $nb) {
         $massiveactionparams['ontop'] =false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }

      echo "</div>";
      // Add items

      return true;
   }


   /** Get data to display on GANTT for a project task
    *
   * @param $ID ID of the project task
   */
   static function getDataToDisplayOnGantt($ID) {
      global $DB;

      $todisplay = [];

      $task = new self();
      // echo $ID.'<br>';
      if ($task->getFromDB($ID)) {
         $subtasks = [];
         foreach ($DB->request('glpi_projecttasks',
                               ['projecttasks_id' => $ID,
                                     'ORDER'           => ['plan_start_date',
                                                                'real_start_date']]) as $data) {
            $subtasks += static::getDataToDisplayOnGantt($data['id']);
         }

         $real_begin = null;
         $real_end   = null;
         // Use real if set
         if (!is_null($task->fields['real_start_date'])) {
            $real_begin = $task->fields['real_start_date'];
         }

         // Determine begin / end date of current task if not set (min/max sub projects / tasks)
         if (is_null($real_begin)) {
            if (!is_null($task->fields['plan_start_date'])) {
               $real_begin = $task->fields['plan_start_date'];
            } else {
               foreach ($subtasks as $subtask) {
                  if (is_null($real_begin)
                      || (!is_null($subtask['from'])
                          && ($real_begin > $subtask['from']))) {
                     $real_begin = $subtask['from'];
                  }
               }
            }
         }

         // Use real if set
         if (!is_null($task->fields['real_end_date'])) {
            $real_end = $task->fields['real_end_date'];
         }
         if (is_null($real_end)) {
            if (!is_null($task->fields['plan_end_date'])) {
               $real_end = $task->fields['plan_end_date'];
            } else {
               foreach ($subtasks as $subtask) {
                  if (is_null($real_end)
                      || (!is_null($subtask['to'])
                          && ($real_end < $subtask['to']))) {
                     $real_end = $subtask['to'];
                  }
               }
            }
         }

         $parents = 0;
         if ($task->fields['projecttasks_id'] > 0) {
            $parents = count(getAncestorsOf("glpi_projecttasks", $ID));
         }

         if ($task->fields['is_milestone']) {
            $percent = "";
         } else {
            $percent = isset($task->fields['percent_done'])?$task->fields['percent_done']:0;
         }

         // Add current task
         $todisplay[$real_begin.'#'.$real_end.'#task'.$task->getID()]
                        = ['id'    => $task->getID(),
                              'name'    => $task->fields['name'],
                              'desc'    => $task->fields['content'],
                              'link'    => $task->getlink(),
                              'type'    => 'task',
                              'percent' => $percent,
                              'from'    => $real_begin,
                              'parents' => $parents,
                              'to'      => $real_end,
                              'is_milestone' => $task->fields['is_milestone']];

         // Add ordered subtasks
         foreach ($subtasks as $key => $val) {
            $todisplay[$key] = $val;
         }
      }
      return $todisplay;
   }


   /** Get data to display on GANTT for a project
    *
   * @param $ID ID of the project
   */
   static function getDataToDisplayOnGanttForProject($ID) {
      global $DB;

      $todisplay = [];

      $task      = new self();
      // Get all tasks without father
      foreach ($DB->request('glpi_projecttasks',
                            ['projects_id'     => $ID,
                                  'projecttasks_id' => 0,
                                  'ORDER'           => ['plan_start_date',
                                                             'real_start_date']]) as $data) {
         if ($task->getFromDB($data['id'])) {
            $todisplay += static::getDataToDisplayOnGantt($data['id']);
         }
      }

      return $todisplay;
   }


   /**
    * Display debug information for current object
   **/
   function showDebug() {
      NotificationEvent::debugEvent($this);
   }

   /**
    * Populate the planning with planned project tasks
    *
    * @since 0.85
    *
    * @param $options   array of possible options:
    *    - who ID of the user (0 = undefined)
    *    - who_group ID of the group of users (0 = undefined)
    *    - begin Date
    *    - end Date
    *    - color
    *    - event_type_color
    *
    * @return array of planning item
   **/
   static function populatePlanning($options = []) {

      global $DB, $CFG_GLPI;

      $interv = [];
      $ttask  = new self;

      if (!isset($options['begin']) || ($options['begin'] == 'NULL')
          || !isset($options['end']) || ($options['end'] == 'NULL')) {
         return $interv;
      }

      $default_options = [
         'genical'             => false,
         'color'               => '',
         'event_type_color'    => '',
      ];
      $options = array_merge($default_options, $options);

      $who       = $options['who'];
      $who_group = $options['who_group'];
      $begin     = $options['begin'];
      $end       = $options['end'];

      // Get items to print
      $WHERE = [];
      $ADDWHERE = [];

      if ($who_group === "mine") {
         if (!$options['genical']
             && count($_SESSION["glpigroups"])) {
            $ADDWHERE['glpi_projecttaskteams.itemtype'] = ['Group'];
            $ADDWHERE['glpi_projecttaskteams.items_id'] = new \QuerySubQuery([
               'SELECT'          => 'groups_id',
               'DISTINCT'        => true,
               'FROM'            => 'glpi_groups',
               'WHERE'           => [
                  'groups_id' => $_SESSION['glpigroups'],
                  'is_assign' => 1
               ]
            ]);
         } else { // Only personal ones
            $ADDWHERE['glpi_projecttaskteams.itemtype'] = 'User';
            $ADDWHERE['glpi_projecttaskteams.items_id'] = $who;
         }

      } else {
         if ($who > 0) {
            $ADDWHERE['glpi_projecttaskteams.itemtype'] = 'User';
            $ADDWHERE['glpi_projecttaskteams.items_id'] = $who;
         }

         if ($who_group > 0) {
            $ADDWHERE['glpi_projecttaskteams.itemtype'] = 'Group';
            $ADDWHERE['glpi_projecttaskteams.items_id'] = $who_group;
         }
      }

      if (!count($ADDWHERE)) {
         $ADDWHERE = [
            'glpi_projecttaskteams.itemtype' => 'User',
            'glpi_projecttaskteams.items_id' => new \QuerySubQuery([
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
            ['glpi_projecttasks.is_finished'  => 0],
            ['glpi_projecttasks.is_finished'  => null]
         ]];
      }

      $WHERE[$ttask->getTable() . '.plan_end_date'] = ['>=', $begin];
      $WHERE[$ttask->getTable() . '.plan_start_date'] = ['<=', $end];

      $iterator = $DB->request([
         'SELECT'       => $ttask->getTable() . '.*',
         'FROM'         => 'glpi_projecttaskteams',
         'INNER JOIN'   => [
            $ttask->getTable() => [
               'ON' => [
                  'glpi_projecttaskteams' => 'projecttasks_id',
                  $ttask->getTable()      => 'id'
               ]
            ]
         ],
         'LEFT JOIN'    => [
            'glpi_projectstates' => [
               'ON' => [
                  $ttask->getTable()   => 'projectstates_id',
                  'glpi_projectstates' => 'id'
               ]
            ]
         ],
         'WHERE'        => $WHERE,
         'ORDERBY'      => $ttask->getTable() . '.plan_start_date'
      ]);

      $interv = [];
      $task   = new self();

      if (count($iterator)) {
         while ($data = $iterator->next()) {
            if ($task->getFromDB($data["id"])) {
               $key = $data["plan_start_date"]."$$$"."ProjectTask"."$$$".$data["id"];
               $interv[$key]['color']            = $options['color'];
               $interv[$key]['event_type_color'] = $options['event_type_color'];
               $interv[$key]['itemtype']         = 'ProjectTask';
               if (!$options['genical']) {
                  $interv[$key]["url"] = Project::getFormURLWithID($task->fields['projects_id']);
               } else {
                  $interv[$key]["url"] = $CFG_GLPI["url_base"].
                                         Project::getFormURLWithID($task->fields['projects_id'], false);
               }
               $interv[$key]["ajaxurl"] = $CFG_GLPI["root_doc"]."/ajax/planning.php".
                                          "?action=edit_event_form".
                                          "&itemtype=ProjectTask".
                                          "&id=".$data['id'].
                                          "&url=".$interv[$key]["url"];

               $interv[$key][$task->getForeignKeyField()] = $data["id"];
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
               $interv[$key]["content"]  = Html::resume_text($task->fields["content"],
                                                             $CFG_GLPI["cut"]);
               $interv[$key]["status"]   = $task->fields["percent_done"];

               $ttask->getFromDB($data["id"]);
               $interv[$key]["editable"] = $ttask->canUpdateItem();
            }
         }
      }

      return $interv;
   }

   /**
    * Display a Planning Item
    *
    * @since 9.1
    *
    * @param $val       array of the item to display
    * @param $who             ID of the user (0 if all)
    * @param $type            position of the item in the time block (in, through, begin or end)
    *                         (default '')
    * @param $complete        complete display (more details) (default 0)
    *
    * @return Nothing (display function)
    **/
   static function displayPlanningItem(array $val, $who, $type = "", $complete = 0) {
      global $CFG_GLPI;

      $html = "";
      $rand     = mt_rand();
      $users_id = "";  // show users_id project task
      $img      = "rdv_private.png"; // default icon for project task

      if ($val["users_id"] != Session::getLoginUserID()) {
         $users_id = "<br>".sprintf(__('%1$s: %2$s'), __('By'), getUserName($val["users_id"]));
         $img      = "rdv_public.png";
      }

      $html.= "<img src='".$CFG_GLPI["root_doc"]."/pics/".$img."' alt='' title=\"".
             self::getTypeName(1)."\">&nbsp;";
      $html.= "<a id='project_task_".$val["id"].$rand."' href='".
             ProjectTask::getFormURLWithID($val["id"])."'>";

      switch ($type) {
         case "in" :
            //TRANS: %1$s is the start time of a planned item, %2$s is the end
            $beginend = sprintf(__('From %1$s to %2$s'), date("H:i", strtotime($val["begin"])),
                                date("H:i", strtotime($val["end"])));
            $html.= sprintf(__('%1$s: %2$s'), $beginend, Html::resume_text($val["name"], 80));
            break;

         case "through" :
            $html.= Html::resume_text($val["name"], 80);
            break;

         case "begin" :
            $start = sprintf(__('Start at %s'), date("H:i", strtotime($val["begin"])));
            $html.= sprintf(__('%1$s: %2$s'), $start, Html::resume_text($val["name"], 80));
            break;

         case "end" :
            $end = sprintf(__('End at %s'), date("H:i", strtotime($val["end"])));
            $html.= sprintf(__('%1$s: %2$s'), $end, Html::resume_text($val["name"], 80));
            break;
      }

      $html.= $users_id;
      $html.= "</a>";

      $html.= "<div class='b'>";
      $html.= sprintf(__('%1$s: %2$s'), __('Percent done'), $val["status"]."%");
      $html.= "</div>";
      $html.= "<div class='event-description rich_text_container'>".html_entity_decode($val["content"])."</div>";
      return $html;
   }
}
