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

class AgentTask_Credential extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1 = "AgentTask";
   static public $items_id_1 = 'agenttasks_id';

   static public $itemtype_2 = 'AgentCredential';
   static public $items_id_2 = 'agentcredentials_id';

   static $rightname = 'inventory';

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate
         && ($item->getType() == 'AgentTask')
         && AgentTask::canView()) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = countElementsInTable(AgentTask_Credential::getTable(),
               ['agenttasks_id' => $item->getID()]);
         }
         return self::createTabEntry(_n('Credential', 'Credentials', Session::getPluralNumber()), $nb);
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == AgentTask::class) {
         self::showForTask($item);
      } else if ($item->getType() == AgentCredential::class) {
         self::showForCredential($item);
      }
      return true;
   }

   /**
    * Show credentials for a task
    *
    * @param AgentTask $task AgentTask object
    *
    * @return void|boolean (display) Returns false if there is a rights error.
    **/
   public static function showForTask(AgentTask $task) {
      global $DB;

      $instID = $task->fields['id'];
      if (!$task->can($instID, READ)) {
         return false;
      }
      $canedit = true; //$task->can($instID, UPDATE);
      $rand    = mt_rand();

      $iterator = $DB->request([
         'SELECT' => AgentCredential::getTable() . '.id',
         'FROM' => AgentCredential::getTable(),
         'LEFT JOIN' => [
            self::getTable() => [
               'ON' => [
                  self::getTable() => 'agentcredentials_id',
                  AgentCredential::getTable() => 'id'
               ]
            ]
         ],
         'WHERE' => [
            self::getTable() . '.agenttasks_id' => $task->fields['id']
         ]
      ]);

      $number = count($iterator);

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form method='post' name='agentexistingcredential_form$rand'
         id='agentexistingcredential_form$rand'  action='" . Toolbox::getItemTypeFormURL("AgentCredential") . "'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='5'>" . __('Link to a credential') . "</th></tr>";
         echo "<tr class='tab_bg_1'><td class='center'>";
         echo __('Choose an existing credential');
         echo "</td><td class='center' class='tab_bg_1'>";
         Dropdown::show(AgentCredential::class);
         echo "</td><td class='center' class='tab_bg_1'>";
         echo "<input type='hidden' name='agenttasks_id' value='$instID'>";
         echo "<input type='submit' name='linkitem' value=\"" . _sx('button', 'Link') . "\" class='btn btn-primary'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";

         echo "<div class='firstbloc'>";
         echo "<form method='post' name='agentcredential_form$rand'
         id='agentcredential_form$rand'  action='" . Toolbox::getItemTypeFormURL("AgentCredential") . "'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='" . ($canedit ? 5 : 4) . "'>" .
              __('Add a credential') . "</th></tr>";

         echo "<tr class='tab_bg_1'><td class='center'>";
         echo __('Name');
         echo "</td><td class='center'>";
         echo Html::input('name');
         echo "</td><td class='center'>";
         echo _n('Type', 'Types', 1);
         echo "</td><td class='center'>";
         Dropdown::showFromArray('type', Glpi\Agent\Credentials\AbstractCredential::getLabelledTypes());
         echo "</td><td class='center' class='tab_bg_1'>";
         echo "<input type='hidden' name='agenttasks_id' value='$instID'>";
         echo "<input type='submit' name='additem' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
         $massiveactionparams = [];
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr>";

      if ($canedit && $number) {
         echo "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand) . "</th>";
      }

      echo "<th>" . __('Name') . "</th>";
      echo "<th>" . _n('Type', 'Types', 1) . "</th>";
      echo "</tr>";

      while ($data = $iterator->next()) {
         $credential = new AgentCredential();
         $credential->getFromDB($data['id']);

         echo "<tr>";
         echo "<td></td>";
         echo "<td>{$credential->getLink()}</td>";
         echo "<td>".Glpi\Agent\Credentials\AbstractCredential::getLabelledTypes()[$credential->fields['type']]."</td>";
         echo "</tr>";
      }
      echo "</table>";

      if ($canedit && $number) {
         $paramsma['ontop'] = false;
         Html::showMassiveActions($paramsma);
         Html::closeForm();
      }
      echo "</div>";

   }
}
