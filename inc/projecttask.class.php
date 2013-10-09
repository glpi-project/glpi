<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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

/// ProjectTask class
class ProjectTask extends CommonDBChild {

   // From CommonDBTM
   public $dohistory = true;

   // From CommonDBChild
   static public $itemtype  = 'Project';
   static public $items_id  = 'projects_id';

   protected $team   = array();

   static function getTypeName($nb=0) {
      return _n('Task', 'Tasks', $nb);
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('ProjectTaskTeam',$ong, $options);
      $this->addStandardTab('ProjectTask_Ticket',$ong, $options);
      $this->addStandardTab('Note',$ong, $options);
      $this->addStandardTab('Log',$ong, $options);

      return $ong;
   }

   function post_getFromDB() {
      // Team
      $this->team    = ProjectTaskTeam::getTeamFor($this->fields['id']);
   }

   /// Get team member count
   function getTeamCount() {
      $nb = 0;
      if (is_array($this->team) && count($this->team)) {
         foreach ($this->team as $val) {
            $nb +=  count($val);
         }
      }
      return $nb;
   }
   
   function prepareInputForUpdate($input) {

      return Project::checkPlanAndRealDates($input);
   }

   function prepareInputForAdd($input) {

      if (!isset($input['users_id'])) {
         $input['users_id'] = Session::getLoginUserID();
      }
      if (!isset($input['date'])) {
         $input['date'] = $_SESSION['glpi_currenttime'];
      }
      
      return Project::checkPlanAndRealDates($input);
   }
   
   /**
    * Print the Software / version form
    *
    * @param $ID        integer  Id of the version or the template to print
    * @param $options   array    of possible options:
    *     - target form target
    *     - projects_id ID of the software for add process
    *
    * @return true if displayed  false if item not found or not right to display
    *
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      if ($ID > 0) {
         $this->check($ID, READ);
         $projects_id = $this->fields['projects_id'];
      } else {
         $projects_id = $options['projects_id'];
         $this->check(-1, CREATE, $options);
      }

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td>"._n('Project', 'Projects', 2)."</td>";
      echo "<td>";
      if ($this->isNewID($ID)) {
         echo "<input type='hidden' name='projects_id' value='$projects_id'>";
      }
      echo "<a href='project.form.php?id=".$projects_id."'>".
             Dropdown::getDropdownName("glpi_projects", $projects_id)."</a>";
      echo "</td>";
      echo "<td>".__('As child of')."</td>";
      echo "<td>";
      $this->dropdown(array('comments' => 0,
                            'entity'   => $this->fields['entities_id'],
                            'value'    => $this->fields['projecttasks_id'],
                            'used'     => array($this->fields['id'])));
      echo "</td>";
      echo "</tr>";

      if ($ID) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Creation date')."</td>";
         echo "<td>";
         echo Html::convDateTime($this->fields["date"]);
         echo sprintf(__('%1$s by %2$s'), Html::convDateTime($this->fields["date"]),
                                       getUserName($this->fields["users_id"], 1));
         echo "</td>";
         echo "<td>".__('Last update')."</td>";
         echo "<td>";
         echo Html::convDateTime($this->fields["date_mod"]);
         echo "</td>";
         echo "</tr>";
      }

//       echo "<tr>";
//       echo "<td rowspan='4' class='middle'>".__('Description')."</td>";
//       echo "<td class='center middle' rowspan='4'>";
//       echo "<textarea cols='45' rows='3' name='content' >".$this->fields["content"];
//       echo "</textarea></td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".__('Name')."</td>";
      echo "<td colspan='3'>";
      Html::autocompletionTextField($this,"name", array('size' => 80));
      echo "</td></tr>\n";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('State')."</td>";
      echo "<td>";
      ProjectState::dropdown(array('value' => $this->fields["projectstates_id"]));
      echo "</td>";
      echo "<td>".__('Type')."</td>";
      echo "<td>";
      ProjectTaskType::dropdown(array('value' => $this->fields["projecttasktypes_id"]));
      echo "</td>";
      echo "</tr>";

      echo "<tr><td colspan='4' class='subheader'>".__('Planning')."</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Planned start date')."</td>";
      echo "<td>";
      Html::showDateTimeField("plan_start_date", array('value' => $this->fields['plan_start_date']));
      echo "</td>";
      echo "<td>".__('Real start date')."</td>";
      echo "<td>";
      Html::showDateTimeField("real_start_date", array('value' => $this->fields['real_start_date']));
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
      $toadd = array();
      for ($i=9 ; $i<=100 ; $i++) {
         $toadd[] = $i*HOUR_TIMESTAMP;
      }

      Dropdown::showTimeStamp("planned_duration", array('min'             => 0,
                                                  'max'             => 8*HOUR_TIMESTAMP,
                                                  'value'           => $this->fields["planned_duration"],
                                                  'addfirstminutes' => true,
                                                  'inhours'         => true,
                                                  'toadd'           => $toadd));
      echo "</td>";
      echo "<td>".__('Effective duration')."</td>";
      echo "<td>";
      echo 'TODO : Display ticket duration<br>+';
      Dropdown::showTimeStamp("effective_duration", array('min'     => 0,
                                                  'max'             => 8*HOUR_TIMESTAMP,
                                                  'value'           => $this->fields["effective_duration"],
                                                  'addfirstminutes' => true,
                                                  'inhours'         => true,
                                                  'toadd'           => $toadd));
      echo "<br>TODO : Total = XXX";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Description')."</td>";
      echo "<td colspan='3'>";
      echo "<textarea id='content' name='content' cols='90' rows='6'>".
               $this->fields["content"]."</textarea>";
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Comments')."</td>";
      echo "<td colspan='3'>";
      echo "<textarea id='comment' name='comment' cols='90' rows='6'>".
               $this->fields["comment"]."</textarea>";
      echo "</td>";
      echo "</tr>\n";
      
      $this->showFormButtons($options);

      return true;
   }


   function getSearchOptions() {

      $tab                 = array();
      $tab['common']       = __('Characteristics');

      $tab[2]['table']     = $this->getTable();
      $tab[2]['field']     = 'name';
      $tab[2]['name']      = __('Name');
      $tab[2]['datatype']  = 'string';

      $tab[13]['table']             = $this->getTable();
      $tab[13]['field']             = 'name';
      $tab[13]['name']              = __('Father');
      $tab[13]['datatype']          = 'dropdown';
      $tab[13]['massiveaction']     = false;
      // Add virtual condition to relink table
      $tab[13]['joinparams']        = array('condition' => "AND 1=1");

      $tab[21]['table']         = $this->getTable();
      $tab[21]['field']         = 'content';
      $tab[21]['name']          = __('Description');
      $tab[21]['massiveaction'] = false;
      $tab[21]['datatype']      = 'text';

      $tab[12]['table']          = 'glpi_projectstates';
      $tab[12]['field']          = 'name';
      $tab[12]['name']           = __('State');
      $tab[12]['datatype']      = 'dropdown';

      $tab[14]['table']          = 'glpi_projecttasktypes';
      $tab[14]['field']          = 'name';
      $tab[14]['name']           = __('Type');
      $tab[14]['datatype']      = 'dropdown';

      $tab[15]['table']         = $this->getTable();
      $tab[15]['field']         = 'date';
      $tab[15]['name']          = __('Opening date');
      $tab[15]['datatype']      = 'datetime';
      $tab[15]['massiveaction'] = false;

      $tab[19]['table']          = $this->getTable();
      $tab[19]['field']          = 'date_mod';
      $tab[19]['name']           = __('Last update');
      $tab[19]['datatype']       = 'datetime';
      $tab[19]['massiveaction']  = false;
      
      $tab[5]['table']         = $this->getTable();
      $tab[5]['field']         = 'percent_done';
      $tab[5]['name']          = __('Percent done');
      $tab[5]['datatype']      = 'number';
      $tab[5]['unit']          = '%';
      $tab[5]['min']           = 0;
      $tab[5]['max']           = 100;
      $tab[5]['step']          = 5;

      $tab[24]['table']          = 'glpi_users';
      $tab[24]['field']          = 'name';
      $tab[24]['linkfield']      = 'users_id';
      $tab[24]['name']           = __('Creator');
      $tab[24]['datatype']       = 'dropdown';


      $tab[7]['table']         = $this->getTable();
      $tab[7]['field']         = 'plan_start_date';
      $tab[7]['name']          = __('Planned begin date');
      $tab[7]['datatype']      = 'datetime';

      $tab[8]['table']         = $this->getTable();
      $tab[8]['field']         = 'plan_end_date';
      $tab[8]['name']          = __('Planned end date');
      $tab[8]['datatype']      = 'datetime';

      $tab[9]['table']         = $this->getTable();
      $tab[9]['field']         = 'real_start_date';
      $tab[9]['name']          = __('Real begin date');
      $tab[9]['datatype']      = 'datetime';

      $tab[10]['table']         = $this->getTable();
      $tab[10]['field']         = 'real_end_date';
      $tab[10]['name']          = __('Real end date');
      $tab[10]['datatype']      = 'datetime';
      
      $tab[16]['table']    = $this->getTable();
      $tab[16]['field']    = 'comment';
      $tab[16]['name']     = __('Comments');
      $tab[16]['datatype'] = 'text';

      $tab[90]['table']          = $this->getTable();
      $tab[90]['field']          = 'notepad';
      $tab[90]['name']           = __('Notes');
      $tab[90]['massiveaction']  = false;
      $tab[90]['datatype']       = 'text';

      $tab[80]['table']          = 'glpi_entities';
      $tab[80]['field']          = 'completename';
      $tab[80]['name']           = __('Entity');
      $tab[80]['datatype']       = 'dropdown';

      $tab[86]['table']          = $this->getTable();
      $tab[86]['field']          = 'is_recursive';
      $tab[86]['name']           = __('Child entities');
      $tab[86]['datatype']       = 'bool';
      
      return $tab;
   }

   /**
    * Show tasks of a project
    *
    * @param $project Software object
    *
    * @return nothing
   **/
   static function showForProject(Project $project) {
      global $DB, $CFG_GLPI;

      $ID = $project->getField('id');

      if (!$project->canViewItem()) {
         return false;
      }

      $canedit = $project->canEdit($ID);

      echo "<div class='spaced'>";
      
      $query = "SELECT `glpi_projecttasks`.*,
                       `glpi_projecttasktypes`.`name` AS tname,
                       `glpi_projectstates`.`name` AS sname
                FROM `glpi_projecttasks`
                LEFT JOIN `glpi_projecttasktypes` ON (`glpi_projecttasktypes`.`id` = `glpi_projecttasks`.`projecttasktypes_id`)
                LEFT JOIN `glpi_projectstates` ON (`glpi_projectstates`.`id` = `glpi_projecttasks`.`projectstates_id`)
                WHERE `projects_id` = '$ID'
                ORDER BY `plan_start_date`, `real_start_date`, `name`";

      Session::initNavigateListItems('ProjectTask',
            //TRANS : %1$s is the itemtype name,
            //       %2$s is the name of the item (used for headings of a list)
                                     sprintf(__('%1$s = %2$s'), Project::getTypeName(1),
                                             $project->getName()));

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            echo "<table class='tab_cadre_fixe'><tr>";
            echo "<th>".self::getTypeName(2)."</th>";
            echo "<th>".__('Type')."</th>";
            echo "<th>".__('Status')."</th>";
            echo "<th>".__('Planned start date')."</th>";
            echo "<th>".__('Planned end date')."</th>";
            echo "<th>".__('Planned duration')."</th>";
            echo "<th>".__('Effective duration')."</th>";
            echo "<th>".__('Father')."</th>";
            echo "</tr>\n";
            for ($tot=$nb=0 ; $data=$DB->fetch_assoc($result) ; $tot+=$nb) {
               Session::addToNavigateListItems('ProjectTask',$data['id']);
               $rand = mt_rand();
               echo "<tr class='tab_bg_2'>";
               echo "<td>";
               $link=  "<a id='ProjectTask".$data["id"].$rand."' href='projecttask.form.php?id=".$data['id']."'>".
                        $data['name'].(empty($data['name'])?"(".$data['id'].")":"")."</a>";
               echo sprintf(__('%1$s %2$s'), $link,
                                    Html::showToolTip($data['content'],
                                                      array('display' => false,
                                                            'applyto' => "ProjectTask".$data["id"].$rand)));
               
               echo "</td>";
               echo "<td>".$data['tname']."</td>";
               echo "<td>".$data['sname']."</td>";
               echo "<td>".Html::convDateTime($data['plan_start_date'])."</td>";
               echo "<td>".Html::convDateTime($data['plan_end_date'])."</td>";
               echo "<td>".Html::timestampToString($data['planned_duration'], false)."</td>";
               echo "<td>".Html::timestampToString($data['effective_duration'], false)." + add ticket duration</td>";
               echo "<td>".Dropdown::getDropdownName('glpi_projecttasks', $data['projecttasks_id'])."</td>";
            }

            echo "<tr class='tab_bg_1'><td class='right b' colspan='1'>".__('Total')."</td>";
            echo "<td class='numeric b'>$tot</td><td>";
            if ($canedit) {
               echo "<a class='vsubmit' href='projecttask.form.php?projects_id=$ID'>".
                      _x('button', 'Add a task')."</a>";
            }
            echo "</td></tr>";
            echo "</table>\n";

         } else {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>".__('No item found')."</th></tr>";
            if ($canedit) {
               echo "<tr class='tab_bg_2'><td class='center'>";
               echo "<a href='projecttask.form.php?projects_id=$ID'>".
                      _x('button', 'Add a task')."</a></td></tr>";
            }
            echo "</table>\n";
         }

      }
      echo "</div>";
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case 'Project' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(self::getTypeName(2),
                                              countElementsInTable($this->getTable(),
                                                                   "projects_id
                                                                        = '".$item->getID()."'"));
               }
               return self::getTypeName(2);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == 'Project') {
         self::showForProject($item);
      }
      return true;
   }

   /**
    * Show team for a project task
   **/
   function showTeam(ProjectTask $task) {
      global $DB, $CFG_GLPI;


      /// TODO : permit to simple add member of project team ?
      
      $ID      = $task->fields['id'];
      $canedit = $task->canEdit($ID);

      echo "<div class='center'>";

      $rand = mt_rand();
      $nb = 0;

      $nb = $task->getTeamCount();

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='projecttaskteam_form$rand' id='projecttaskteam_form$rand' ";
         echo " method='post' action='".Toolbox::getItemTypeFormURL('ProjectTaskTeam')."'>";
         echo "<input type='hidden' name='projecttasks_id' value='$ID'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='2'>".__('Add a team member')."</tr>";
         echo "<tr class='tab_bg_2'><td>";

         $params = array('itemtypes' => ProjectTeam::$available_types,
                         'entity_restrict' => ($task->fields['is_recursive']
                                               ? getSonsOf('glpi_entities', $task->fields['entities_id'])
                                               : $task->fields['entities_id']),
                         );
         $addrand = Dropdown::showSelectItemFromItemtypes($params);

         echo "</td>";
         echo "<td width='20%'>";
         echo "<input type='submit' name='add' value=\""._sx('button','Add')."\"
               class='submit'>";
         echo "</td>";
         echo "</tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }
      echo "<div class='spaced'>";
      if ($canedit && $nb) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams
            = array('num_displayed'
                        => $nb,
                    'container'
                        => 'mass'.__CLASS__.$rand);
//                     'specific_actions'
//                         => array('MassiveAction'.MassiveAction::CLASS_ACTION_SEPARATOR.'delete'
//                                     => _x('button', 'Delete permanently')) );
//
//          if ($this->fields['users_id'] != Session::getLoginUserID()) {
//             $massiveactionparams['confirm']
//                = __('Caution! You are not the author of this element. Delete targets can result in loss of access to that element.');
//          }
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr>";
      if ($canedit && $nb) {
         echo "<th width='10'>";
         echo Html::checkAllAsCheckbox('mass'.__CLASS__.$rand);
         echo "</th>";
      }
      echo "<th>".__('Type')."</th>";
      echo "<th>"._n('Member', 'Members', 2)."</th>";
      echo "</tr>";

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
   
}
?>
