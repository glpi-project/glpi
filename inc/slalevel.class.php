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
 * SlaLevel class
**/
class SlaLevel extends LevelAgreementLevel {

   protected $rules_id_field     = 'slalevels_id';
   protected $ruleactionclass    = 'SlaLevelAction';
   static protected $parentclass = 'SLA';
   static protected $fkparent    = 'slas_id';
   // No criteria
   protected $rulecriteriaclass = 'SlaLevelCriteria';


   static function getTable($classname = null) {
      return CommonDBTM::getTable(__CLASS__);
   }


   function cleanDBonPurge() {
      global $DB;

      parent::cleanDBonPurge();

      // SlaLevel_Ticket does not extends CommonDBConnexity
      $slt = new SlaLevel_Ticket();
      $slt->deleteByCriteria([$this->rules_id_field => $this->fields['id']]);
   }


   function showForParent(SLA $sla) {
      return $this->showForSLA($sla);
   }


   /**
    * @param $sla SLA object
    *
    * @since 9.1 (before showForSLA)
   **/
   function showForSLA(SLA $sla) {
      global $DB;

      $ID = $sla->getField('id');
      if (!$sla->can($ID, READ)) {
         return false;
      }

      $canedit = $sla->can($ID, UPDATE);

      $rand    = mt_rand();

      if ($canedit) {
         echo "<div class='center first-bloc'>";
         echo "<form name='slalevel_form$rand' id='slalevel_form$rand' method='post' action='";
         echo Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='7'>".__('Add an escalation level')."</tr>";

         echo "<tr class='tab_bg_2'><td class='center'>".__('Name')."";
         echo "<input type='hidden' name='slas_id' value='$ID'>";
         echo "<input type='hidden' name='entities_id' value='".$sla->getEntityID()."'>";
         echo "<input type='hidden' name='is_recursive' value='".$sla->isRecursive()."'>";
         echo "<input type='hidden' name='match' value='AND'>";
         echo "</td><td><input  name='name' value=''>";
         echo "</td><td class='center'>".__('Execution')."</td><td>";

         $delay = $sla->getTime();
         self::dropdownExecutionTime('execution_time',
                                     ['max_time' => $delay,
                                      'used'     => self::getAlreadyUsedExecutionTime($sla->fields['id']),
                                      'type'     => $sla->fields['type']]);

         echo "</td><td class='center'>".__('Active')."</td><td>";
         Dropdown::showYesNo("is_active", 1);
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td></tr>";

         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'slas_id'   => $ID
         ],
         'ORDER'  => 'execution_time'
      ]);
      $numrows = count($iterator);

      echo "<div class='spaced'>";
      if ($canedit && $numrows) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = ['num_displayed'  => min($_SESSION['glpilist_limit'], $numrows),
                                      'container'      => 'mass'.__CLASS__.$rand];
         Html::showMassiveActions($massiveactionparams);
      }

      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr>";
      if ($canedit && $numrows) {
         echo "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand)."</th>";
      }
      echo "<th>".__('Name')."</th>";
      echo "<th>".__('Execution')."</th>";
      echo "<th>".__('Active')."</th>";
      echo "</tr>";
      Session::initNavigateListItems('SlaLevel',
      //TRANS: %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                                     sprintf(__('%1$s = %2$s'), SLA::getTypeName(1),
                                             $sla->getName()));

      while ($data = $iterator->next()) {
         Session::addToNavigateListItems('SlaLevel', $data["id"]);

         echo "<tr class='tab_bg_2'>";
         if ($canedit) {
            echo "<td>".Html::getMassiveActionCheckBox(__CLASS__, $data["id"])."</td>";
         }

         echo "<td>";
         if ($canedit) {
            echo "<a href='".Toolbox::getItemTypeFormURL('SlaLevel')."?id=".$data["id"]."'>";
         }
         echo $data["name"];
         if (empty($data["name"])) {
            echo "(".$data['id'].")";
         }
         if ($canedit) {
            echo "</a>";
         }
         echo "</td>";
         echo "<td>".($data["execution_time"] != 0
                        ? Html::timestampToString($data["execution_time"], false)
                        : ($sla->fields['type'] == 1
                              ? __('Time to own')
                              : __('Time to resolve'))).
              "</td>";
         echo "<td>".Dropdown::getYesNo($data["is_active"])."</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'><td colspan='2'>";
         $this->getRuleWithCriteriasAndActions($data['id'], 1, 1);
         $this->showCriteriasList($data["id"], ['readonly' => true]);
         echo "</td><td colspan='2'>";
         $this->showActionsList($data["id"], ['readonly' => true]);
         echo "</td></tr>";
      }

      echo "</table>";
      if ($canedit && $numrows) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }


   function getActions() {

      $actions = parent::getActions();

      unset($actions['slas_id']);
      $actions['recall']['name']          = __('Automatic reminders of SLA');
      $actions['recall']['type']          = 'yesonly';
      $actions['recall']['force_actions'] = ['send'];

      return $actions;
   }


   /**
    * Show the rule
    *
    * @param $ID              ID of the rule
    * @param $options   array of possible options
    *
    * @return nothing
   **/
   function showForm($ID, $options = []) {

      $canedit = $this->can('sla', UPDATE);

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>".__('Active')."</td>";
      echo "<td>";
      Dropdown::showYesNo("is_active", $this->fields["is_active"]);
      echo"</td></tr>\n";

      $sla = new SLA();
      $sla->getFromDB($this->fields['slas_id']);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".SLA::getTypeName(1)."</td>";
      echo "<td>".$sla->getLink()."</td>";
      echo "<td>".__('Execution')."</td>";
      echo "<td>";

      $delay = $sla->getTime();

      self::dropdownExecutionTime('execution_time',
                                  ['max_time'
                                             => $delay,
                                        'used'
                                             => self::getAlreadyUsedExecutionTime($sla->fields['id']),
                                        'value'
                                             => $this->fields['execution_time'],
                                        'type'
                                             => $sla->fields['type']]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Logical operator')."</td>";
      echo "<td>";
      $this->dropdownRulesMatch(['value' => $this->fields["match"]]);
      echo "</td>";
      echo "<td colspan='2'>&nbsp;</td></tr>";

      $this->showFormButtons($options);
   }

   /**
    * Get first level for a SLA
    *
    * @param $slas_id   integer  id of the SLA
    *
    * @since 9.1 (before getFirst SlaLevel)
    *
    * @return id of the sla level : 0 if not exists
   **/
   static function getFirstSlaLevel($slas_id) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => 'id',
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'slas_id'   => $slas_id,
            'is_active' => 1
         ],
         'ORDER'  => 'execution_time ASC',
         'LIMIT'  => 1
      ]);

      if ($result = $iterator->next()) {
         return $result['id'];
      }
      return 0;
   }


   /**
    * Get next level for a SLA
    *
    * @param $slas_id         integer id of the SLA
    * @param $slalevels_id    integer id of the current SLA level
    *
    * @return id of the sla level : 0 if not exists
   **/
   static function getNextSlaLevel($slas_id, $slalevels_id) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => 'execution_time',
         'FROM'   => self::getTable(),
         'WHERE'  => ['id' => $slalevels_id]
      ]);

      if ($result = $iterator->next()) {
         $execution_time = $result['execution_time'];

         $lvl_iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => self::getTable(),
            'WHERE'  => [
               'slas_id'         => $slas_id,
               'is_active'       => 1,
               'id'              => ['<>', $slalevels_id],
               'execution_time'  => ['>', $execution_time]
            ],
            'ORDER'  => 'execution_time ASC',
            'LIMIT'  => 1
         ]);

         if ($result = $lvl_iterator->next()) {
            return $result['id'];
         }
      }
      return 0;
   }

}
