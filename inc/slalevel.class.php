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
 * SlaLevel class
**/
class SlaLevel extends RuleTicket {

   protected $rules_id_field    = 'slalevels_id';
   protected $ruleactionclass   = 'SlaLevelAction';
   // No criteria
   protected $rulecriteriaclass = 'SlaLevelCriteria';

   static $rightname            = 'sla';


   /**
    * Constructor
   **/
   function __construct() {
      // Override in order not to use glpi_rules table.
   }

   // Temporary hack for this class in 0.84
   static function getTable() {
      return 'glpi_slalevels';
   }


   /**
    * @since version 0.85
   **/
   static function getConditionsArray() {
      // Override ruleticket one
      return array();
   }


   /**
    * @since version 0.84
   **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   static function getTypeName($nb=0) {
      return _n('Escalation level', 'Escalation levels', $nb);
   }


   function cleanDBonPurge() {
      global $DB;

      parent::cleanDBOnPurge();

      $sql = "DELETE
              FROM `glpi_slalevels_tickets`
              WHERE `".$this->rules_id_field."` = '".$this->fields['id']."'";
      $DB->query($sql);
   }


   /**
    * @param $sla SLA object
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

         $resolution_time = $sla->getResolutionTime();
         self::dropdownExecutionTime('execution_time',
                                     array('max_time'
                                             => $resolution_time,
                                           'used'
                                             => self::getAlreadyUsedExecutionTime($sla->fields['id'])));

         echo "</td><td class='center'>".__('Active')."</td><td>";
         Dropdown::showYesNo("is_active", 1);
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td></tr>";

         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      $query = "SELECT *
                FROM `glpi_slalevels`
                WHERE `slas_id` = '$ID'
                ORDER BY `execution_time`";
      $result  = $DB->query($query);
      $numrows = $DB->numrows($result);

      echo "<div class='spaced'>";
      if ($canedit && $numrows) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array('num_displayed'  => $numrows,
                                      'container'      => 'mass'.__CLASS__.$rand);
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

      while ($data = $DB->fetch_assoc($result)) {
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
         echo "<td>".(($data["execution_time"] <> 0)
                        ?Html::timestampToString($data["execution_time"], false)
                        :__('Due date'))."</td>";
         echo "<td>".Dropdown::getYesNo($data["is_active"])."</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'><td colspan='2'>";
         $this->getRuleWithCriteriasAndActions($data['id'],1,1);
         $this->showCriteriasList($data["id"], array('readonly' => true));
         echo "</td><td colspan='2'>";
         $this->showActionsList($data["id"], array('readonly' => true));
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

      $actions                            = parent::getActions();

      unset($actions['slas_id']);
      $actions['recall']['name']          = __('Automatic reminders of SLA');
      $actions['recall']['type']          = 'yesonly';
      $actions['recall']['force_actions'] = array('send');

      // Only append actors
      $actions['_users_id_requester']['force_actions']  = array('append');
      $actions['_groups_id_requester']['force_actions'] = array('append');
      $actions['_users_id_assign']['force_actions']     = array('append');
      $actions['_groups_id_assign']['force_actions']    = array('append');
      $actions['_suppliers_id_assign']['force_actions'] = array('append');
      $actions['_users_id_observer']['force_actions']   = array('append');
      $actions['_groups_id_observer']['force_actions']  = array('append');

      return $actions;
   }


   /**
    * @since version 0.84
    *
    * @see RuleTicket::getCriterias()
   **/
   function getCriterias() {

      $actions                      = parent::getActions();

      unset($actions['slas_id']);
      // Could not be used as criteria
      unset($actions['users_id_validate_requester_supervisor']);
      unset($actions['users_id_validate_assign_supervisor']);
      unset($actions['affectobject']);
      unset($actions['groups_id_validate']);
      unset($actions['users_id_validate']);
      unset($actions['validation_percent']);
      $actions['status']['name']    = __('Status');
      $actions['status']['type']    = 'dropdown_status';
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
   function showForm($ID, $options=array()) {

      $canedit = $this->can('sla',UPDATE);

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

      $resolution_time = $sla->getResolutionTime();

      self::dropdownExecutionTime('execution_time',
                                  array('max_time'
                                             => $resolution_time,
                                        'used'
                                             => self::getAlreadyUsedExecutionTime($sla->fields['id']),
                                        'value'
                                             => $this->fields['execution_time']));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Logical operator')."</td>";
      echo "<td>";
      $this->dropdownRulesMatch(array('value' => $this->fields["match"]));
      echo "</td>";
      echo "<td colspan='2'>&nbsp;</td></tr>";

      $this->showFormButtons($options);
   }


   /**
    * Dropdown execution time for SLA
    *
    * @param $name      string   name of the select
    * @param $options   array    of possible options:
    *       - value : default value
    *       - max_time : max time to use
    *       - used : already used values
    *
    * @return nothing
   **/
   static function dropdownExecutionTime($name, $options=array()) {

      $p['value']    = '';
      $p['max_time'] = 4*DAY_TIMESTAMP;
      $p['used']     = array();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }
      // Display default value;
      if (($key = array_search($p['value'], $p['used'])) !== false) {
         unset($p['used'][$key]);
      }

      $possible_values = array();
      for ($i=10 ; $i<60 ; $i+=10) {
         if (!in_array($i*MINUTE_TIMESTAMP,$p['used'])) {
            $possible_values[$i*MINUTE_TIMESTAMP] = sprintf(_n('+ %d minute','+ %d minutes',$i), $i);
         }
         if (!in_array(-$i*MINUTE_TIMESTAMP,$p['used'])) {
            if ($p['max_time'] >= $i*MINUTE_TIMESTAMP) {
               $possible_values[-$i*MINUTE_TIMESTAMP] = sprintf(_n('- %d minute','- %d minutes',$i), $i);
            }
         }
      }

      for ($i=1 ; $i<24 ; $i++) {
         if (!in_array($i*HOUR_TIMESTAMP,$p['used'])) {
            $possible_values[$i*HOUR_TIMESTAMP] = sprintf(_n('+ %d hour','+ %d hours',$i), $i);
         }
         if (!in_array(-$i*HOUR_TIMESTAMP,$p['used'])) {
            if ($p['max_time'] >= $i*HOUR_TIMESTAMP) {
               $possible_values[-$i*HOUR_TIMESTAMP] = sprintf(_n('- %d hour','- %d hours',$i),
                                                              $i);
            }
         }
      }

      for ($i=1 ; $i<30 ; $i++) {
         if (!in_array($i*DAY_TIMESTAMP,$p['used'])) {
            $possible_values[$i*DAY_TIMESTAMP] = sprintf(_n('+ %d day','+ %d days',$i), $i);
         }
         if (!in_array(-$i*DAY_TIMESTAMP,$p['used'])) {
            if ($p['max_time'] >= $i*DAY_TIMESTAMP) {
               $possible_values[-$i*DAY_TIMESTAMP] = sprintf(_n('- %d day','- %d days',$i), $i);
            }
         }
      }
      if (!in_array(0,$p['used'])) {
         $possible_values[0] = __('Due date');
      }
      ksort($possible_values);

      Dropdown::showFromArray($name, $possible_values, array('value' => $p['value']));
   }


   /**
    * Get already used execution time for a SLA
    *
    * @param $slas_id   integer  id of the SLA
    *
    * @return array of already used execution times
   **/
   static function getAlreadyUsedExecutionTime($slas_id) {
      global $DB;

      $result = array();
      $query  = "SELECT DISTINCT `execution_time`
                 FROM `glpi_slalevels`
                 WHERE `slas_id` = '$slas_id';";

      foreach ($DB->request($query) as $data) {
         $result[$data['execution_time']] = $data['execution_time'];
      }
      return $result;
   }


   /**
    * Get first level for a SLA
    *
    * @param $slas_id   integer  id of the SLA
    *
    * @return id of the sla level : 0 if not exists
   **/
   static function getFirstSlaLevel($slas_id) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_slalevels`
                WHERE `slas_id` = '$slas_id'
                     AND `is_active` = 1
                ORDER BY `execution_time` ASC LIMIT 1;";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            return $DB->result($result,0,0);
         }
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

      $query = "SELECT `execution_time`
                FROM `glpi_slalevels`
                WHERE `id` = '$slalevels_id';";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            $execution_time = $DB->result($result,0,0);

            $query = "SELECT `id`
                      FROM `glpi_slalevels`
                       WHERE `slas_id` = '$slas_id'
                             AND `id` <> '$slalevels_id'
                             AND `execution_time` > '$execution_time'
                             AND `is_active` = 1
                      ORDER BY `execution_time` ASC LIMIT 1 ;";

            if ($result = $DB->query($query)) {
               if ($DB->numrows($result)) {
                  return $DB->result($result,0,0);
               }
            }
         }
      }
      return 0;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'SLA' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb =  countElementsInTable($this->getTable(), "`slas_id` = '".$item->getID()."'");
               }
               return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == 'SLA') {
         $slalevel = new self();
         $slalevel->showForSLA($item);
      }
      return true;
   }

}
?>
