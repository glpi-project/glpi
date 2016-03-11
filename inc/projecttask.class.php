<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


/**
 * ProjectTask Class
 *
 * @since version 0.85
**/
class ProjectTask extends CommonDBChild {

   // From CommonDBTM
   public $dohistory = true;

   // From CommonDBChild
   static public $itemtype     = 'Project';
   static public $items_id     = 'projects_id';

   protected $team             = array();
   static $rightname           = 'project';
   protected $usenotepad       = true;

   var $can_be_translated      = true;

   const READMY      = 1;
   const UPDATEMY    = 1024;



   static function getTypeName($nb=0) {
      return _n('Project task', 'Project tasks', $nb);
   }


   static function canView() {

      return (Session::haveRightsOr(self::$rightname, array(Project::READALL, Project::READMY))
              || Session::haveRight('projecttask', ProjectTask::READMY));
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
         return (Session::haveRight(self::$rightname, Project::READALL)
                 || (Session::haveRight(self::$rightname, Project::READMY)
                     && (($project->fields["users_id"] === Session::getLoginUserID())
                         || $project->isInTheManagerGroup()
                         || $project->isInTheTeam()))
                 || (Session::haveRight('projecttask', ProjectTask::READMY)
                     && (($this->fields["users_id"] === Session::getLoginUserID())
                         || $this->isInTheTeam())));
      }
      return false;
   }


   static function canCreate() {
      return (Session::haveRight(self::$rightname, UPDATE));
   }


   static function canUpdate() {

      return (parent::canUpdate()
              || Session::haveRight('projecttask', self::UPDATEMY));
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
         return (Session::haveRight(self::$rightname, UPDATE)
                 || (Session::haveRight('projecttask', ProjectTask::UPDATEMY)
                     && (($this->fields["users_id"] === Session::getLoginUserID())
                         || $this->isInTheTeam())));
      }
      return false;
   }



   function cleanDBonPurge() {
      global $DB;

      $pt = new ProjectTaskTeam();
      $pt->cleanDBonItemDelete(__CLASS__, $this->fields['id']);

      $pt = new ProjectTask_Ticket();
      $pt->cleanDBonItemDelete(__CLASS__, $this->fields['id']);

      parent::cleanDBonPurge();
   }


   /**
    * @see commonDBTM::getRights()
    **/
   function getRights($interface='central') {

      $values = array();

      $values[self::READMY]   = __('See (actor)');
      $values[self::UPDATEMY] = __('Update (actor)');

      return $values;
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__,$ong, $options);
      $this->addStandardTab('ProjectTaskTeam',$ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('ProjectTask_Ticket',$ong, $options);
      $this->addStandardTab('Notepad',$ong, $options);
      $this->addStandardTab('Log',$ong, $options);

      return $ong;
   }


   function post_getFromDB() {
      // Team
      $this->team    = ProjectTaskTeam::getTeamFor($this->fields['id']);
   }

   function post_getEmpty() {
      $this->fields['percent_done'] = 0;
   }


   function post_updateItem($history=1) {
      global $CFG_GLPI;

      if ($CFG_GLPI["use_mailing"]) {
         // Read again project to be sure that all data are up to date
         $this->getFromDB($this->fields['id']);
         NotificationEvent::raiseEvent("update", $this);
      }
   }


   function post_addItem() {
      global $DB, $CFG_GLPI;

      if ($CFG_GLPI["use_mailing"]) {
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

      NotificationEvent::raiseEvent('delete',$this);
      return true;
   }


   function prepareInputForUpdate($input) {

      if (isset($input['is_milestone'])
            && $input['is_milestone']){
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
            && $input['is_milestone']){
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

      $tasks = array();
      foreach ($DB->request('glpi_projecttasks',
                            array("projects_id" => $ID,
                                  'ORDER'       => array('plan_start_date',
                                                         'real_start_date'))) as $data) {
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

      $tasks = array();
      foreach ($DB->request(array('glpi_projecttasks_tickets', 'glpi_projecttasks'),
                            array("`glpi_projecttasks`.`projects_id`"
                                          => $ID,
                                  "`glpi_projecttasks_tickets`.`projecttasks_id`"
                                          => "`glpi_projecttasks`.`id`",
                                  'FIELDS' =>  "tickets_id" ))
                        as $data) {
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
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      if ($ID > 0) {
         $this->check($ID, READ);
         $projects_id     = $this->fields['projects_id'];
         $projecttasks_id = $this->fields['projecttasks_id'];
      } else {
         $projects_id     = $options['projects_id'];
         $projecttasks_id = $options['projecttasks_id'];
         $recursive       = $this->fields['is_recursive'];
         $this->check(-1, CREATE, $options);
      }

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td>"._n('Project', 'Projects', Session::getPluralNumber())."</td>";
      echo "<td>";
      if ($this->isNewID($ID)) {
         echo "<input type='hidden' name='projects_id' value='$projects_id'>";
         echo "<input type='hidden' name='is_recursive' value='$recursive'>";
      }
      echo "<a href='project.form.php?id=".$projects_id."'>".
             Dropdown::getDropdownName("glpi_projects", $projects_id)."</a>";
      echo "</td>";
      echo "<td>".__('As child of')."</td>";
      echo "<td>";
      $this->dropdown(array('entity'    => $this->fields['entities_id'],
                            'value'     => $projecttasks_id,
                            'condition' => "`glpi_projecttasks`.`projects_id`='".
                                             $this->fields['projects_id']."'",
                            'used'      => array($this->fields['id'])));
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
      Html::autocompletionTextField($this,"name", array('size' => 80));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._x('item', 'State')."</td>";
      echo "<td>";
      ProjectState::dropdown(array('value' => $this->fields["projectstates_id"]));
      echo "</td>";
      echo "<td>".__('Type')."</td>";
      echo "<td>";
      ProjectTaskType::dropdown(array('value' => $this->fields["projecttasktypes_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Percent done')."</td>";
      echo "<td>";
      Dropdown::showNumber("percent_done", array('value' => $this->fields['percent_done'],
                                                 'min'   => 0,
                                                 'max'   => 100,
                                                 'step'  => 5,
                                                 'unit'  => '%'));

      echo "</td>";
      echo "<td>";
      _e('Milestone');
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo("is_milestone", $this->fields["is_milestone"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr><td colspan='4' class='subheader'>".__('Planning')."</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Planned start date')."</td>";
      echo "<td>";
      Html::showDateTimeField("plan_start_date",
                              array('value' => $this->fields['plan_start_date']));
      echo "</td>";
      echo "<td>".__('Real start date')."</td>";
      echo "<td>";
      Html::showDateTimeField("real_start_date",
                              array('value' => $this->fields['real_start_date']));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Planned end date')."</td>";
      echo "<td>";
      Html::showDateTimeField("plan_end_date", array('value' => $this->fields['plan_end_date']));
      echo "</td>";
      echo "<td>".__('Real end date')."</td>";
      echo "<td>";
      Html::showDateTimeField("real_end_date", array('value' => $this->fields['real_end_date']));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Planned duration')."</td>";
      echo "<td>";

      Dropdown::showTimeStamp("planned_duration",
                              array('min'             => 0,
                                    'max'             => 100*HOUR_TIMESTAMP,
                                    'step'            => HOUR_TIMESTAMP,
                                    'value'           => $this->fields["planned_duration"],
                                    'addfirstminutes' => true,
                                    'inhours'         => true));
      echo "</td>";
      echo "<td>".__('Effective duration')."</td>";
      echo "<td>";
      Dropdown::showTimeStamp("effective_duration",
                              array('min'             => 0,
                                    'max'             => 100*HOUR_TIMESTAMP,
                                    'step'            => HOUR_TIMESTAMP,
                                    'value'           => $this->fields["effective_duration"],
                                    'addfirstminutes' => true,
                                    'inhours'         => true));
      if ($ID) {
         $ticket_duration = ProjectTask_Ticket::getTicketsTotalActionTime($this->getID());
         echo "<br>";
         printf(__('%1$s: %2$s'),__('Tickets duration'),
                Html::timestampToString($ticket_duration, false));
         echo '<br>';
         printf(__('%1$s: %2$s'),__('Total duration'),
                Html::timestampToString($ticket_duration+$this->fields["effective_duration"],
                                        false));
      }
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Description')."</td>";
      echo "<td colspan='3'>";
      echo "<textarea id='content' name='content' cols='90' rows='6'>".$this->fields["content"].
           "</textarea>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Comments')."</td>";
      echo "<td colspan='3'>";
      echo "<textarea id='comment' name='comment' cols='90' rows='6'>".$this->fields["comment"].
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
      $query = "SELECT SUM(`glpi_tickets`.`actiontime`)
                FROM `glpi_projecttasks`
                LEFT JOIN `glpi_projecttasks_tickets`
                   ON (`glpi_projecttasks`.`id` = `glpi_projecttasks_tickets`.`projecttasks_id`)
                LEFT JOIN `glpi_tickets`
                   ON (`glpi_projecttasks_tickets`.`tickets_id` = `glpi_tickets`.`id`)
                WHERE `glpi_projecttasks`.`id` = '$projecttasks_id';";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            $time += $DB->result($result, 0, 0);
         }
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

      $query = "SELECT `id`
                FROM `glpi_projecttasks`
                WHERE `glpi_projecttasks`.`projects_id` = '$projects_id';";
      $time = 0;
      foreach ($DB->request($query) as $data) {
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

      $query = "SELECT SUM(`planned_duration`) as SUM
                FROM `glpi_projecttasks`
                WHERE `glpi_projecttasks`.`projects_id` = '$projects_id';";
      foreach ($DB->request($query) as $data) {
         return $data['SUM'];
      }
      return 0;
   }


   function getSearchOptions() {

      $tab                          = array();
      $tab['common']                = __('Characteristics');

      $tab[1]['table']              = $this->getTable();
      $tab[1]['field']              = 'name';
      $tab[1]['name']               = __('Name');
      $tab[1]['datatype']           = 'itemlink';
      $tab[1]['massiveaction']      = false; // implicit key==1

      $tab[2]['table']              = $this->getTable();
      $tab[2]['field']              = 'id';
      $tab[2]['name']               = __('ID');
      $tab[2]['massiveaction']      = false; // implicit field is id
      $tab[2]['datatype']           = 'number';

      $tab[2]['table']              = 'glpi_projects';
      $tab[2]['field']              = 'name';
      $tab[2]['name']               = __('Project');
      $tab[2]['massiveaction']      = false;
      $tab[2]['datatype']           = 'dropdown';

      $tab[13]['table']             = $this->getTable();
      $tab[13]['field']             = 'name';
      $tab[13]['name']              = __('Father');
      $tab[13]['datatype']          = 'dropdown';
      $tab[13]['massiveaction']     = false;
      // Add virtual condition to relink table
      $tab[13]['joinparams']        = array('condition' => "AND 1=1");

      $tab[21]['table']             = $this->getTable();
      $tab[21]['field']             = 'content';
      $tab[21]['name']              = __('Description');
      $tab[21]['massiveaction']     = false;
      $tab[21]['datatype']          = 'text';

      $tab[12]['table']             = 'glpi_projectstates';
      $tab[12]['field']             = 'name';
      $tab[12]['name']              = _x('item', 'State');
      $tab[12]['datatype']          = 'dropdown';

      $tab[14]['table']             = 'glpi_projecttasktypes';
      $tab[14]['field']             = 'name';
      $tab[14]['name']              = __('Type');
      $tab[14]['datatype']          = 'dropdown';

      $tab[15]['table']             = $this->getTable();
      $tab[15]['field']             = 'date';
      $tab[15]['name']              = __('Opening date');
      $tab[15]['datatype']          = 'datetime';
      $tab[15]['massiveaction']     = false;

      $tab[19]['table']             = $this->getTable();
      $tab[19]['field']             = 'date_mod';
      $tab[19]['name']              = __('Last update');
      $tab[19]['datatype']          = 'datetime';
      $tab[19]['massiveaction']     = false;

      $tab[5]['table']              = $this->getTable();
      $tab[5]['field']              = 'percent_done';
      $tab[5]['name']               = __('Percent done');
      $tab[5]['datatype']           = 'number';
      $tab[5]['unit']               = '%';
      $tab[5]['min']                = 0;
      $tab[5]['max']                = 100;
      $tab[5]['step']               = 5;

      $tab[24]['table']             = 'glpi_users';
      $tab[24]['field']             = 'name';
      $tab[24]['linkfield']         = 'users_id';
      $tab[24]['name']              = __('Creator');
      $tab[24]['datatype']          = 'dropdown';


      $tab[7]['table']              = $this->getTable();
      $tab[7]['field']              = 'plan_start_date';
      $tab[7]['name']               = __('Planned start date');
      $tab[7]['datatype']           = 'datetime';

      $tab[8]['table']              = $this->getTable();
      $tab[8]['field']              = 'plan_end_date';
      $tab[8]['name']               = __('Planned end date');
      $tab[8]['datatype']           = 'datetime';

      $tab[9]['table']              = $this->getTable();
      $tab[9]['field']              = 'real_start_date';
      $tab[9]['name']               = __('Real start date');
      $tab[9]['datatype']           = 'datetime';

      $tab[10]['table']             = $this->getTable();
      $tab[10]['field']             = 'real_end_date';
      $tab[10]['name']              = __('Real end date');
      $tab[10]['datatype']          = 'datetime';

      $tab[11]['table']             = $this->getTable();
      $tab[11]['field']             = 'planned_duration';
      $tab[11]['name']              = __('Planned duration');
      $tab[11]['datatype']          = 'timestamp';
      $tab[11]['min']               = 0;
      $tab[11]['max']               = 100*HOUR_TIMESTAMP;
      $tab[11]['step']              = HOUR_TIMESTAMP;
      $tab[11]['addfirstminutes']   = true;
      $tab[11]['inhours']           = true;

      $tab[17]['table']             = $this->getTable();
      $tab[17]['field']             = 'effective_duration';
      $tab[17]['name']              = __('Effective duration');
      $tab[17]['datatype']          = 'timestamp';
      $tab[17]['min']               = 0;
      $tab[17]['max']               = 100*HOUR_TIMESTAMP;
      $tab[17]['step']              = HOUR_TIMESTAMP;
      $tab[17]['addfirstminutes']   = true;
      $tab[17]['inhours']           = true;

      $tab[16]['table']             = $this->getTable();
      $tab[16]['field']             = 'comment';
      $tab[16]['name']              = __('Comments');
      $tab[16]['datatype']          = 'text';

      $tab[18]['table']             = $this->getTable();
      $tab[18]['field']             = 'is_milestone';
      $tab[18]['name']              = __('Milestone');
      $tab[18]['datatype']          = 'bool';

      $tab[80]['table']             = 'glpi_entities';
      $tab[80]['field']             = 'completename';
      $tab[80]['name']              = __('Entity');
      $tab[80]['datatype']          = 'dropdown';

      $tab[86]['table']             = $this->getTable();
      $tab[86]['field']             = 'is_recursive';
      $tab[86]['name']              = __('Child entities');
      $tab[86]['datatype']          = 'bool';

      $tab += Notepad::getSearchOptionsToAdd();

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

      $columns = array('name'             => self::getTypeName(Session::getPluralNumber()),
                       'tname'            => __('Type'),
                       'sname'            => __('Status'),
                       'percent_done'     => __('Percent done'),
                       'plan_start_date'  => __('Planned start date'),
                       'plan_end_date'    => __('Planned end date'),
                       'planned_duration' => __('Planned duration'),
                       '_effect_duration' => __('Effective duration'),
                       'fname'            => __('Father'),);

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
         echo "<a class='vsubmit' href='projecttask.form.php?projects_id=$ID'>".
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
         _e('Create a sub task from this task of project');
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

            $sort_img = "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/" .
                          (($order == "DESC") ? "puce-down.png" : "puce-up.png") ."\" alt='' title=''>";

            $header = '<tr>';
            foreach ($columns as $key => $val) {
               // Non order column
               if ($key[0] == '_') {
                  $header .= "<th>$val</th>";
               } else {
                  $header .= "<th>".(($sort == "`$key`") ?$sort_img:"").
                        "<a href='javascript:reloadTab(\"sort=$key&amp;order=".
                           (($order == "ASC") ?"DESC":"ASC")."&amp;start=0\");'>$val</a></th>";
               }
            }
            $header .= "</tr>\n";
            echo $header;

            while ($data=$DB->fetch_assoc($result)) {
               Session::addToNavigateListItems('ProjectTask',$data['id']);
               $rand = mt_rand();
               echo "<tr class='tab_bg_2'>";
               echo "<td>";
               $link = "<a id='ProjectTask".$data["id"].$rand."' href='projecttask.form.php?id=".
                         $data['id']."'>".$data['name'].
                         (empty($data['name'])?"(".$data['id'].")":"")."</a>";
               echo sprintf(__('%1$s %2$s'), $link,
                             Html::showToolTip($data['content'],
                                               array('display' => false,
                                                     'applyto' => "ProjectTask".$data["id"].$rand)));
               echo "</td>";
               $name = !empty($data['transname2'])?$data['transname2']:$data['tname'];
               echo "<td>".$name."</td>";
               echo "<td";
               $statename = !empty($data['transname3'])?$data['transname3']:$data['sname'];
               echo " style=\"background-color:".$data['color']."\"";
               echo ">".$statename."</td>";
               echo "<td>";
               echo Dropdown::getValueWithUnit($data["percent_done"],"%");
               echo "</td>";
               echo "<td>".Html::convDateTime($data['plan_start_date'])."</td>";
               echo "<td>".Html::convDateTime($data['plan_end_date'])."</td>";
               echo "<td>".Html::timestampToString($data['planned_duration'], false)."</td>";
               echo "<td>".Html::timestampToString(self::getTotalEffectiveDuration($data['id']),
                                                   false)."</td>";
               echo "<td>";
               if ($data['projecttasks_id']>0) {
                  $father = Dropdown::getDropdownName('glpi_projecttasks', $data['projecttasks_id']);
                  echo "<a id='ProjectTask".$data["projecttasks_id"].$rand."' href='projecttask.form.php?id=".
                           $data['projecttasks_id']."'>".$father.
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


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Project' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable($this->getTable(),
                                             "projects_id = '".$item->getID()."'");
               }
               return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);

            case __CLASS__ :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable($this->getTable(),
                                             "projecttasks_id = '".$item->getID()."'");
               }
               return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

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

         $params = array('itemtypes'       => ProjectTeam::$available_types,
                         'entity_restrict' => ($task->fields['is_recursive']
                                               ? getSonsOf('glpi_entities',
                                                           $task->fields['entities_id'])
                                               : $task->fields['entities_id']),
                         );
         $addrand = Dropdown::showSelectItemFromItemtypes($params);

         echo "</td>";
         echo "<td width='20%'>";
         echo "<input type='submit' name='add' value=\""._sx('button','Add')."\" class='submit'>";
         echo "</td>";
         echo "</tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }
      echo "<div class='spaced'>";
      if ($canedit && $nb) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array('num_displayed' => $nb,
                                      'container'     => 'mass'.__CLASS__.$rand);
//                     'specific_actions'
//                         => array('delete' => _x('button', 'Delete permanently')) );
//
//          if ($this->fields['users_id'] != Session::getLoginUserID()) {
//             $massiveactionparams['confirm']
//                = __('Caution! You are not the author of this element. Delete targets can result in loss of access to that element.');
//          }
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
                     Html::showMassiveActionCheckBox('ProjectTaskTeam',$data["id"]);
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

      $todisplay = array();

      $task = new self();
//       echo $ID.'<br>';
      if ($task->getFromDB($ID)) {
         $subtasks = array();
         foreach ($DB->request('glpi_projecttasks',
                               array('projecttasks_id' => $ID,
                                     'ORDER'           => array('plan_start_date',
                                                                'real_start_date'))) as $data) {
            $subtasks += static::getDataToDisplayOnGantt($data['id']);
         }

         $real_begin = NULL;
         $real_end   = NULL;
         // Use real if set
         if (!is_null($task->fields['real_start_date'])) {
            $real_begin = $task->fields['real_start_date'];
         }

         // Determine begin / end date of current task if not set (min/max sub projects / tasks)
         if (is_null($real_begin)) {
            if (!is_null($task->fields['plan_start_date'])) {
               $real_begin = $task->fields['plan_start_date'];
            } else {
               foreach($subtasks as $subtask) {
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
               foreach($subtasks as $subtask) {
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

         if ($task->fields['is_milestone']){
            $percent = "";
         }else{
            $percent = isset($task->fields['percent_done'])?$task->fields['percent_done']:0;
         }

         // Add current task
         $todisplay[$real_begin.'#'.$real_end.'#task'.$task->getID()]
                        = array('id'    => $task->getID(),
                              'name'    => $task->fields['name'],
                              'desc'    => $task->fields['content'],
                              'link'    => $task->getlink(),
                              'type'    => 'task',
                              'percent' => $percent,
                              'from'    => $real_begin,
                              'parents' => $parents,
                              'to'      => $real_end,
                              'is_milestone' => $task->fields['is_milestone']);

         // Add ordered subtasks
         foreach($subtasks as $key => $val) {
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

      $todisplay = array();

      $task      = new self();
      // Get all tasks without father
      foreach ($DB->request('glpi_projecttasks',
                            array('projects_id'     => $ID,
                                  'projecttasks_id' => 0,
                                  'ORDER'           => array('plan_start_date',
                                                             'real_start_date'))) as $data) {
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

}
?>
