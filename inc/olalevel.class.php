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

/**
 * @since 9.2
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * OlaLevel class
**/
class OlaLevel extends LevelAgreementLevel {

   protected $rules_id_field     = 'olalevels_id';
   protected $ruleactionclass    = 'OlaLevelAction';
   static protected $parentclass = 'OLA';
   static protected $fkparent    = 'olas_id';
   // No criteria
   protected $rulecriteriaclass = 'OlaLevelCriteria';


   static function getTable($classname = null) {
      return CommonDBTM::getTable(__CLASS__);
   }


   function cleanDBonPurge() {
      global $DB;

      parent::cleanDBOnPurge();

      $sql = "DELETE
              FROM `glpi_olalevels_tickets`
              WHERE `".$this->rules_id_field."` = '".$this->fields['id']."'";
      $DB->query($sql);
   }


   function showForParent(OLA $ola) {
      return $this->showForOLA($ola);
   }


   /**
    * @param $ola OLA object
    *
    * @since 9.1 (before showForOLA)
   **/
   function showForOLA(OLA $ola) {
      global $DB;

      $ID = $ola->getField('id');
      if (!$ola->can($ID, READ)) {
         return false;
      }

      $canedit = $ola->can($ID, UPDATE);

      $rand    = mt_rand();

      if ($canedit) {
         echo "<div class='center first-bloc'>";
         echo "<form name='olalevel_form$rand' id='olalevel_form$rand' method='post' action='";
         echo Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='7'>".__('Add an escalation level')."</tr>";

         echo "<tr class='tab_bg_2'><td class='center'>".__('Name')."";
         echo "<input type='hidden' name='olas_id' value='$ID'>";
         echo "<input type='hidden' name='entities_id' value='".$ola->getEntityID()."'>";
         echo "<input type='hidden' name='is_recursive' value='".$ola->isRecursive()."'>";
         echo "<input type='hidden' name='match' value='AND'>";
         echo "</td><td><input  name='name' value=''>";
         echo "</td><td class='center'>".__('Execution')."</td><td>";

         $delay = $ola->getTime();
         self::dropdownExecutionTime('execution_time',
                                     ['max_time' => $delay,
                                      'used'     => self::getAlreadyUsedExecutionTime($ola->fields['id'])]);

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
                FROM `glpi_olalevels`
                WHERE `olas_id` = '$ID'
                ORDER BY `execution_time`";
      $result  = $DB->query($query);
      $numrows = $DB->numrows($result);

      echo "<div class='spaced'>";
      if ($canedit && $numrows) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = ['num_displayed'  => $numrows,
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
      Session::initNavigateListItems('OlaLevel',
      //TRANS: %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                                     sprintf(__('%1$s = %2$s'), OLA::getTypeName(1),
                                             $ola->getName()));

      while ($data = $DB->fetch_assoc($result)) {
         Session::addToNavigateListItems('OlaLevel', $data["id"]);

         echo "<tr class='tab_bg_2'>";
         if ($canedit) {
            echo "<td>".Html::getMassiveActionCheckBox(__CLASS__, $data["id"])."</td>";
         }

         echo "<td>";
         if ($canedit) {
            echo "<a href='".Toolbox::getItemTypeFormURL('OlaLevel')."?id=".$data["id"]."'>";
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
                        : ($ola->fields['type'] == 1
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

      unset($actions['olas_id']);
      $actions['recall_ola']['name']          = __('Automatic reminders of OLA');
      $actions['recall_ola']['type']          = 'yesonly';
      $actions['recall_ola']['force_actions'] = ['send'];

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

      $canedit = $this->can('ola', UPDATE);

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

      $ola = new OLA();
      $ola->getFromDB($this->fields['olas_id']);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".OLA::getTypeName(1)."</td>";
      echo "<td>".$ola->getLink()."</td>";
      echo "<td>".__('Execution')."</td>";
      echo "<td>";

      $delay = $ola->getTime();

      self::dropdownExecutionTime('execution_time',
                                  ['max_time'  => $delay,
                                   'used'      => self::getAlreadyUsedExecutionTime($ola->fields['id']),
                                   'value'     => $this->fields['execution_time']]);
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
    * Get first level for a OLA
    *
    * @param $olas_id   integer  id of the OLA
    *
    * @since 9.1 (before getFirst OlaLevel)
    *
    * @return id of the ola level : 0 if not exists
   **/
   static function getFirstOlaLevel($olas_id) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_olalevels`
                WHERE `olas_id` = '$olas_id'
                     AND `is_active` = 1
                ORDER BY `execution_time` ASC LIMIT 1;";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            return $DB->result($result, 0, 0);
         }
      }
      return 0;
   }


   /**
    * Get next level for a OLA
    *
    * @param $olas_id         integer id of the OLA
    * @param $olalevels_id    integer id of the current OLA level
    *
    * @return id of the ola level : 0 if not exists
   **/
   static function getNextOlaLevel($olas_id, $olalevels_id) {
      global $DB;

      $query = "SELECT `execution_time`
                FROM `glpi_olalevels`
                WHERE `id` = '$olalevels_id';";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            $execution_time = $DB->result($result, 0, 0);

            $query = "SELECT `id`
                      FROM `glpi_olalevels`
                       WHERE `olas_id` = '$olas_id'
                             AND `id` <> '$olalevels_id'
                             AND `execution_time` > '$execution_time'
                             AND `is_active` = 1
                      ORDER BY `execution_time` ASC LIMIT 1;";

            if ($result = $DB->query($query)) {
               if ($DB->numrows($result)) {
                  return $DB->result($result, 0, 0);
               }
            }
         }
      }
      return 0;
   }

}
