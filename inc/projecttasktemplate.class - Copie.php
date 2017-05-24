<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Template for task
 * @since version 9.1
**/
class ProjectTaskTemplate extends CommonDropdown {

   // From CommonDBTM
   public $dohistory          = true;
   public $can_be_translated  = true;

   static $rightname          = 'projecttasktemplate';



   static function getTypeName($nb=0) {
      return _n('Project task template', 'Project task templates', $nb);
   }


   function getAdditionalFields() {

      return array(array('name'  => 'projectstates_id',
                         'label' => _x('item', 'State'),
                         'type'  => 'dropdownValue',
                         'list'  => true),
                   array('name'  => 'projecttasktypes_id',
                         'label' => __('Type'),
                         'type'  => 'dropdownValue'),
                   array('name'  => 'projects_id',
                         'label' => __('As child of'),
                         'type'  => 'dropdownValue'),
                   array('name'  => 'percent_done',
                         'label' => __('Percent done'),
                         'type'  => 'percent'),
                   array('name'  => 'is_milestone',
                         'label' => __('Milestone'),
                         'type'  => 'bool'),
                   array('name'  => 'plan_start_date',
                         'label' => __('Planned start date'),
                         'type'  => 'datetime'),
                   array('name'  => 'real_start_date',
                         'label' => __('Real start date'),
                         'type'  => 'datetime'),
                   array('name'  => 'plan_end_date',
                         'label' => __('Planned end date'),
                         'type'  => 'datetime'),
                   array('name'  => 'real_end_date',
                         'label' => __('Real end date'),
                         'type'  => 'datetime'),
                   array('name'  => 'planned_duration',
                         'label' => __('Planned duration'),
                         'type'  => 'actiontime'),
                   array('name'  => 'effective_duration',
                         'label' => __('Effective duration'),
                         'type'  => 'actiontime'),
                   array('name'  => 'description',
                         'label' => __('Description'),
                         'type'  => 'textarea'),
                   array('name'  => 'comments',
                         'label' => __('Comments'),
                         'type'  => 'textarea'),
                  );
   }


   function getSearchOptionsNew() {
      $tab = parent::getSearchOptionsNew();

//      $tab[] = [
//         'id'                 => '4',
//         'name'               => __('Content'),
//         'field'              => 'content',
//         'table'              => $this->getTable(),
//         'datatype'           => 'text',
//         'htmltext'           => true
//      ];

      return $tab;
   }

   /**
    * @see CommonDropdown::displaySpecificTypeField()
   **/
   function displaySpecificTypeField($ID, $field=array()) {

      switch ($field['type']) {
         case 'percent' :
            Dropdown::showNumber("percent_done", array('value' => $this->fields['percent_done'],
                                                       'min'   => 0,
                                                       'max'   => 100,
                                                       'step'  => 5,
                                                       'unit'  => '%'));
            break;
         case 'actiontime' :
            Dropdown::showTimeStamp($field["name"],
                                    array('min'             => 0,
                                          'max'             => 100*HOUR_TIMESTAMP,
                                          'step'            => HOUR_TIMESTAMP,
                                          'value'           => $this->fields[$field["name"]],
                                          'addfirstminutes' => true,
                                          'inhours'         => true));
            break;
      }
   }

   function defineTabs($options=array()) {

      $ong = parent::defineTabs($options);
//      $this->addStandardTab('ProjectTaskTeam', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab(__CLASS__, $ong, $options);

      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

         $nb = 0;
         switch ($item->getType()) {
            case 'ProjectTaskTemplate' :
               $projecttaskteam = new ProjectTaskTeam();
               $projecttask = new ProjectTask();
//               $projecttaskteam->getFromDB($item->fields['id']);
               $projecttaskteam->teams = ProjectTaskTeam::getTeamFor($item->fields['id']);
//               Toolbox::logDebug(ProjectTaskTeam::getTeamFor($item->fields['id']));
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = $projecttask->getTeamCount();
               }
               return self::createTabEntry(ProjectTaskTeam::getTypeName(1), $nb);

         }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'ProjectTaskTemplate' :
            $projecttask = new ProjectTask();
            $projecttask->teams = ProjectTaskTeam::getTeamFor($item->fields['id']);
            $projecttask->fields = $item->fields;

            $projecttask->showTeam($projecttask);
            return true;
      }
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
      Toolbox::logDebug($task);
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
         $massiveactionparams = array('num_displayed' => min($_SESSION['glpilist_limit'], $nb),
                                      'container'     => 'mass'.__CLASS__.$rand);
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
}
