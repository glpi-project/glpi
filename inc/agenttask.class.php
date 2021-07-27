<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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
 * @since 10.0.0
 **/
class AgentTask extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Agent';
   static public $items_id = 'agents_id';

   /** @var boolean */
   public $dohistory = true;

   /** @var string */
   static $rightname = 'inventory';

   static function canCreate() {
      return true;
   }

   function canCreateItem() {
      return true;
   }

   static function getTypeName($nb = 0) {
      return _n('Agent task', 'Agent tasks', $nb);
   }


   /**
    * Define tabs to display on form page
    *
    * @param array $options
    * @return array containing the tabs name
    */
   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(AgentTask_Credential::class, $ong, $options);

      $ttype_class = $this->fields['task_type'];
      if (!$this->isNewItem() && $ttype_class) {
         $this->addSTandardTab($ttype_class, $ong, $options);
      }

      return $ong;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate
         && ($item->getType() == 'Agent')
         && Agent::canView()) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = countElementsInTable(self::getTable(),
               ['agents_id' => $item->getID()]);
         }
         return self::createTabEntry(self::getTypeName(), $nb);
      }

      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case Agent::class:
            self::showTasks($item);
            break;
         case CommonTask::class:
            $item->showTaskTab();
            break;
      }
      return true;
   }

   public static function showTasks(Agent $agent) {
      $ID = $agent->fields['id'];

      if (!$agent->getFromDB($ID) || !$agent->can($ID, READ)) {
         return;
      }

      $rand = mt_rand();
      $canedit = $agent->canEdit($ID);

      if ($canedit) {
         echo "<div class='center firstbloc'>".
            "<a class='vsubmit' href='".self::getFormURL()."?agents_id=$ID'>";
         echo __('Add a task');
         echo "</a></div>\n";
      }

      $tasks = getAllDataFromTable(
         self::getTable(), [
            'agents_id' => $ID
         ]
      );

      echo "<table class='tab_cadre_fixehov'>";
      $header = "<tr><th>".__('Name')."</th>";
      $header .= "<th>".__('Start')."</th>";
      $header .= "<th>".__('End')."</th>";
      $header .= "<th>".__('Peridicity')."</th>";
      $header .= "</tr>";
      echo $header;

      if (!count($tasks)) {
         echo "<tr><td colspan='2'>".__('Not task found')."</td></tr>";
      }
      foreach ($tasks as $task) {
         $atask = new AgentTask();
         $atask->getFromDB($task['id']);
         echo "<tr class='tab_bg_2'>";
         echo "<td>";
         /*echo "<a href='".$agent->getFormURLWithID($agent->fields['id'])."'>";
         echo $agent->fields['name']."</a>";*/
         echo $atask->getLink();
         echo "</td>";
         echo "<td>".Html::convDate($task['start_date'])."</td>";
         echo "<td>".Html::convDate($task['end_date'])."</td>";
         echo "<td>".$task['periodicity']."</td>";
         echo "</tr>";

      }

      echo $header;
      echo "</table>";
   }

   /**
    * Display form
    *
    * @param integer $id      ID of the agent
    * @param array   $options Options
    *
    * @return boolean
    */
   function showForm($id, $options = []) {
      global $CFG_GLPI;

      if (!empty($id)) {
         $this->getFromDB($id);
      } else {
         $this->getEmpty();
      }
      $this->initForm($id, $options);
      $this->showFormHeader($options);
      $rand = mt_rand();

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='name'>".__('Name')."</label></td>";
      echo "<td align='center'>";
      Html::autocompletionTextField($this, 'name', ['size' => 40]);
      echo "</td>";
      echo "<td>".Agent::getTypeName(1)."</td>";
      echo "<td align='center'>";
      Agent::dropdown([
         'value' => $_GET['agents_id'] ?? $this->fields['agents_id'] ?? null
      ]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='deviceid'>".__('Start date')."</label></td>";
      echo "<td align='center'>";
      Html::showDateField("start_date", ['value' => $this->fields['start_date']]);
      echo "</td>";
      echo "<td>".__('End date')."</td>";
      echo "<td align='center'>";
      Html::showDateField("end_date", ['value' => $this->fields['end_date']]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='periodicity'>".__('Periodicity')."</label></td>";
      echo "<td align='center'>";
      Dropdown::showNumber('periodicity');
      echo "</td>";
      echo "<td><label for='task_type'>".__('Task type')."</label></td>";
      echo "<td align='center'>";
      Dropdown::showFromArray('task_type', $CFG_GLPI['agenttasks_types']);
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);

      return true;
   }

   public function prepareInputForAdd($input) {
      if (empty($input['end_date'])) {
         unset($input['end_date']);
      }

      return $input;
   }
}
