<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// OCS Rules class
class RuleImportComputer extends Rule {

   const PATTERN_IS_EMPTY              = 30;
   const RULE_ACTION_LINK_OR_IMPORT    = 0;
   const RULE_ACTION_LINK_OR_NO_IMPORT = 1;

   var $restrict_matching = Rule::AND_MATCHING;


   // From Rule
   public $right    = 'rule_ocs';
   public $can_sort = true;


   function canCreate() {
      return haveRight('rule_ocs', 'w');
   }


   function canView() {
      return haveRight('rule_ocs', 'r');
   }


   function getTitle() {
      global $LANG;

      return $LANG['rulesengine'][57];
   }


   function maxActionsCount() {
      // Unlimited
      return 1;
   }


   function getCriterias() {
      global $LANG;

      $criterias = array();
      $criterias['entities_id']['table']     = 'glpi_entities';
      $criterias['entities_id']['field']     = 'entities_id';
      $criterias['entities_id']['name']      = $LANG['rulesengine'][152].' : '.$LANG['ocsng'][62];
      $criterias['entities_id']['linkfield'] = 'entities_id';
      $criterias['entities_id']['type']      = 'dropdown';

      $criterias['states_id']['table']           = 'glpi_states';
      $criterias['states_id']['field']           = 'name';
      $criterias['states_id']['name']            = $LANG['ocsconfig'][55];
      $criterias['states_id']['linkfield']       = 'state';
      $criterias['states_id']['type']            = 'dropdown';
      //Means that this criterion can only be used in a global search query
      $criterias['states_id']['is_global']       = true;
      $criterias['states_id']['allow_condition'] = array(Rule::PATTERN_IS, Rule::PATTERN_IS_NOT);

      $criterias['ocsservers_id']['table']     = 'glpi_ocsservers';
      $criterias['ocsservers_id']['field']     = 'name';
      $criterias['ocsservers_id']['name']      = $LANG['ocsng'][29];
      $criterias['ocsservers_id']['linkfield'] = '';
      $criterias['ocsservers_id']['type']      = 'dropdown';

      $criterias['TAG']['name']        = $LANG['rulesengine'][152].' : '.$LANG['ocsconfig'][39];

      $criterias['DOMAIN']['name']     = $LANG['rulesengine'][152].' : '.$LANG['setup'][89];

      $criterias['IPSUBNET']['name']   = $LANG['rulesengine'][152].' : '.$LANG['networking'][61];

      $criterias['MACADDRESS']['name'] = $LANG['rulesengine'][152].' : '.$LANG['device_iface'][2];

      $criterias['IPADDRESS']['name']  = $LANG['rulesengine'][152].' : '.
                                         $LANG['financial'][44]." ". $LANG['networking'][14];

      $criterias['name']['name']            = $LANG['rulesengine'][152].' : '.
                                                        $LANG['rulesengine'][25];
      $criterias['name']['allow_condition'] = array(Rule::PATTERN_IS, Rule::PATTERN_IS_NOT,
                                                    self::PATTERN_IS_EMPTY, Rule::PATTERN_FIND);

      $criterias['DESCRIPTION']['name']   = $LANG['rulesengine'][152].' : '.$LANG['joblist'][6];

      $criterias['serial']['name']        = $LANG['rulesengine'][152].' : '.$LANG['common'][19];

      return $criterias;
   }


   function getActions() {
      global $LANG;

      $actions = array();
      $actions['_fusion']['name']        = $LANG['ocsng'][58];
      $actions['_fusion']['type']        = 'fusion_type';

      $actions['_ignore_import']['name'] = $LANG['rulesengine'][132];
      $actions['_ignore_import']['type'] = 'yesonly';

      return $actions;
   }


   static function getRuleActionValues() {
      global $LANG;

      return array(self::RULE_ACTION_LINK_OR_IMPORT    => $LANG['ocsng'][79],
                   self::RULE_ACTION_LINK_OR_NO_IMPORT => $LANG['ocsng'][78]);
   }


   /**
    * Add more action values specific to this type of rule
    *
    * @param value the value for this action
    *
    * @return the label's value or ''
   **/
   function displayAdditionRuleActionValue($value) {
      global $LANG;

      $values = self::getRuleActionValues();
      if (isset($values[$value])) {
         return $values[$value];
      }
      return '';
   }


   function manageSpecificCriteriaValues($criteria, $name, $value) {
      global $LANG;

      switch ($criteria['type']) {
         case "state" :
            $link_array = array("0" => $LANG['choice'][0],
                                "1" => $LANG['choice'][1]." : ".$LANG['ocsconfig'][57],
                                "2" => $LANG['choice'][1]." : ".$LANG['ocsconfig'][56]);

            Dropdown::showFromArray($name, $link_array, array('value' => $value));
      }
      return false;
   }


   /**
    * Add more criteria specific to this type of rule
   **/
   static function addMoreCriteria($criterion='') {
      global $LANG;

      return array(Rule::PATTERN_FIND     => $LANG['rulesengine'][151],
                   self::PATTERN_IS_EMPTY => $LANG['rulesengine'][154]);
   }


   function getAdditionalCriteriaDisplayPattern($ID, $condition, $pattern) {
      global $LANG;

      if ($condition == self::PATTERN_IS_EMPTY) {
          return $LANG['choice'][1];
      }
      return false;
   }


   function displayAdditionalRuleCondition($condition, $criteria, $name, $value, $test=false) {

      if ($test) {
         return false;
      }

      switch ($condition) {
         case Rule::PATTERN_FIND :
         case self::PATTERN_IS_EMPTY :
            Dropdown::showYesNo($name, 0, 0);
            return true;
      }

      return false;
   }


   function displayAdditionalRuleAction($action, $params=array()) {
      global $LANG;

      switch ($action['type']) {
         case 'fusion_type' :
            Dropdown::showFromArray('value', self::getRuleActionValues());
            break;

         default :
            break;
      }
      return true;
   }


   function getCriteriaByID($ID) {

      $criteria = array();
      foreach ($this->criterias as $criterion) {
         if ($ID == $criterion->fields['criteria']) {
            $criteria[] = $criterion;
         }
      }
      return $criteria;
   }


   function findWithGlobalCriteria($input) {
      global $DB;

      $complex_criterias = array();
      $sql_where         = '';
      $sql_from          = '';
      $continue          = true;
      $global_criteria   = array('IPADDRESS', 'IPSUBNET', 'MACADDRESS', 'name', 'serial');

      foreach ($global_criteria as $criterion) {
         $criteria = $this->getCriteriaByID($criterion);
         if (!empty($criteria)) {
            foreach ($criteria as $crit) {
               if (!isset($input[$criterion]) || $input[$criterion] == '') {
                  $continue = false;
               } else if ($crit->fields["condition"] == Rule::PATTERN_FIND) {
                  $complex_criterias[] = $crit;
               }
            }
         }
      }

      foreach ($this->getCriteriaByID('states_id') as $crit) {
         $complex_criterias[] = $crit;
      }

      //If a value is missing, then there's a problem !
      if (!$continue) {
         return false;
      }

      //No complex criteria
      if (empty($complex_criterias)) {
         return true;
      }

      //Build the request to check if the machine exists in GLPI
      if (is_array($input['entities_id'])) {
         $where_entity = implode($input['entities_id'],',');
      } else {
         $where_entity = $input['entities_id'];
      }

      $sql_where = " `glpi_computers`.`entities_id` IN ($where_entity)
                    AND `glpi_computers`.`is_template` = '0' ";
      $sql_from = "`glpi_computers`";

      foreach ($complex_criterias as $criteria) {
         switch ($criteria->fields['criteria']) {
            case 'IPADDRESS' :
               $sql_from .= " LEFT JOIN `glpi_networkports`
                                 ON (`glpi_computers`.`id` = `glpi_networkports`.`items_id`
                                     AND `glpi_networkports`.`itemtype` = 'Computer') ";
               $sql_where .= " AND `glpi_networkports`.`ip` IN ";
               for ($i=0 ; $i<count($input["IPADDRESS"]) ; $i++) {
                  $sql_where .= ($i>0 ? ',"' : '("').$input["IPADDRESS"][$i].'"';
               }
               $sql_where .= ")";
               break;

            case 'MACADDRESS' :
               $sql_where .= " AND `glpi_networkports`.`mac` IN (";
               $sql_where .= implode(',',$input['MACADDRESS']);
               $sql_where .= ")";
               break;

            case 'name' :
               if ($criteria->fields['condition'] == self::PATTERN_IS_EMPTY) {
                  $sql_where .= " AND (`glpi_computers`.`name`=''
                                       OR `glpi_computers`.`name` IS NULL) ";
               } else {
                  $sql_where .= " AND (`glpi_computers`.`name`='".$input['name']."') ";
               }
               break;

            case 'serial' :
               $sql_where .= " AND `glpi_computers`.`serial`='".$input["serial"]."'";
               break;

            case 'states_id' :
               if ($criteria->fields['condition'] == Rule::PATTERN_IS) {
                  $condition = " IN ";
               } else {
                  $conditin = " NOT IN ";
               }
               $sql_where .= " AND `glpi_computers`.`states_id`
                                 $condition ('".$criteria->fields['pattern']."')";
               break;
         }
      }

      $sql_glpi = "SELECT `glpi_computers`.`id`
                   FROM $sql_from
                   WHERE $sql_where
                   ORDER BY `glpi_computers`.`is_deleted` ASC";
      $result_glpi = $DB->query($sql_glpi);

      if ($DB->numrows($result_glpi) > 0) {
         while ($data=$DB->fetch_array($result_glpi)) {
            $this->criterias_results['found_computers'][] = $data['id'];
         }
         return true;
      }

      if (count($this->actions)) {
         foreach ($this->actions as $action) {
            if ($action->fields['field'] == '_fusion') {
               if ($action->fields["value"] == self::RULE_ACTION_LINK_OR_NO_IMPORT) {
                  return true;
               }
            }
         }
      }
      return false;

   }


   /**
    * Execute the actions as defined in the rule
    *
    * @param $output the fields to manipulate
    * @param $params parameters
    *
    * @return the $output array modified
   **/
   function executeActions($output, $params) {

      if (count($this->actions)) {
         foreach ($this->actions as $action) {
            if ($action->fields['field'] == '_fusion') {
               if ($action->fields["value"] == self::RULE_ACTION_LINK_OR_IMPORT) {
                  if (isset($this->criterias_results['found_computers'])) {
                     $output['found_computers'] = $this->criterias_results['found_computers'];
                     $output['action']          = OcsServer::LINK_RESULT_LINK;
                  } else {
                     $output['action'] = OcsServer::LINK_RESULT_IMPORT;
                  }

               } else if ($action->fields["value"] == self::RULE_ACTION_LINK_OR_NO_IMPORT) {
                  if (isset($this->criterias_results['found_computers'])) {
                     $output['found_computers'] = $this->criterias_results['found_computers'];
                     $output['action']          = OcsServer::LINK_RESULT_LINK;
                  } else {
                     $output['action'] = OcsServer::LINK_RESULT_NO_IMPORT;
                  }
               }

            } else {
               $output['action'] = OcsServer::LINK_RESULT_NO_IMPORT;
            }
         }
      }
      return $output;
   }


   /**
    * Function used to display type specific criterias during rule's preview
    *
    * @param $fields fields values
   **/
   function showSpecificCriteriasForPreview($fields) {

      $entity_as_criteria = false;
      foreach ($this->criterias as $criteria) {
         if ($criteria->fields['criteria'] == 'entities_id') {
            $entity_as_criteria = true;
            break;
         }
      }
      if (!$entity_as_criteria) {
         echo "<input type='hidden' name='entities_id' value='".$_SESSION["glpiactive_entity"]."'>";
      }
   }


   function preProcessPreviewResults($output) {
      return OcsServer::previewRuleImportProcess($output);
   }

}

?>
