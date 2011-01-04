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

   const PATTERN_IS_EMPTY = 30;
   const RULE_ACTION_LINK_OR_IMPORT = 0;
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


   function preProcessPreviewResults($output) {
      return $output;
   }

   function getCriterias() {
      global $LANG;

      $criterias = array ();
      $criterias['entities_id']['table']     = 'glpi_entities';
      $criterias['entities_id']['field']     = 'entities_id';
      $criterias['entities_id']['name']      = $LANG['rulesengine'][152].' : '.$LANG['ocsng'][62];
      $criterias['entities_id']['linkfield'] = 'entities_id';
      $criterias['entities_id']['type']      = 'dropdown';

      $criterias['state']['table']     = 'glpi_states';
      $criterias['state']['field']     = 'name';
      $criterias['state']['name']      = $LANG['ocsconfig'][55];
      $criterias['state']['linkfield'] = 'state';
      $criterias['state']['type']      = 'dropdown';
      $criterias['state']['complex']   = true;
      $criterias['state']['allow_condition'] = array(Rule::PATTERN_IS, Rule::PATTERN_IS_NOT);

      $criterias['OCS_SERVER']['table']     = 'glpi_ocsservers';
      $criterias['OCS_SERVER']['field']     = 'name';
      $criterias['OCS_SERVER']['name']      = $LANG['ocsng'][29];
      $criterias['OCS_SERVER']['linkfield'] = '';
      $criterias['OCS_SERVER']['type']      = 'dropdown';
      $criterias['OCS_SERVER']['virtual']   = true;
      $criterias['OCS_SERVER']['id']        = 'ocs_server';

      $criterias['TAG']['table']     = 'accountinfo';
      $criterias['TAG']['field']     = 'TAG';
      $criterias['TAG']['name']      = $LANG['rulesengine'][152].' : '.$LANG['ocsconfig'][39];
      $criterias['TAG']['linkfield'] = 'HARDWARE_ID';

      $criterias['DOMAIN']['table']     = 'hardware';
      $criterias['DOMAIN']['field']     = 'WORKGROUP';
      $criterias['DOMAIN']['name']      = $LANG['rulesengine'][152].' : '.$LANG['setup'][89];
      $criterias['DOMAIN']['linkfield'] = '';

      $criterias['IPSUBNET']['table']     = 'networks';
      $criterias['IPSUBNET']['field']     = 'IPSUBNET';
      $criterias['IPSUBNET']['name']      = $LANG['rulesengine'][152].' : '.$LANG['networking'][61];
      $criterias['IPSUBNET']['linkfield'] = 'HARDWARE_ID';

      $criterias['MACADDRESS']['table']     = 'networks';
      $criterias['MACADDRESS']['field']     = 'MACADDR';
      $criterias['MACADDRESS']['name']      = $LANG['rulesengine'][152].' : '.
                                                $LANG['device_iface'][2];
      $criterias['MACADDRESS']['linkfield'] = 'HARDWARE_ID';

      $criterias['IPADDRESS']['table']     = 'networks';
      $criterias['IPADDRESS']['field']     = 'IPADDRESS';
      $criterias['IPADDRESS']['name']      = $LANG['rulesengine'][152].' : '.
                                                $LANG['financial'][44]." ". $LANG['networking'][14];
      $criterias['IPADDRESS']['linkfield'] = 'HARDWARE_ID';

      $criterias['MACHINE_NAME']['table']     = 'hardware';
      $criterias['MACHINE_NAME']['field']     = 'NAME';
      $criterias['MACHINE_NAME']['name']      = $LANG['rulesengine'][152].' : '.
                                                   $LANG['rulesengine'][25];
      $criterias['MACHINE_NAME']['linkfield'] = '';
      $criterias['MACHINE_NAME']['allow_condition'] = array(Rule::PATTERN_IS, Rule::PATTERN_IS_NOT,
                                                            RuleImportComputer::PATTERN_IS_EMPTY,
                                                            Rule::PATTERN_FIND);

      $criterias['DESCRIPTION']['table']     = 'hardware';
      $criterias['DESCRIPTION']['field']     = 'DESCRIPTION';
      $criterias['DESCRIPTION']['name']      = $LANG['rulesengine'][152].' : '.$LANG['joblist'][6];
      $criterias['DESCRIPTION']['linkfield'] = '';

      $criterias['SSN']['table']     = 'bios';
      $criterias['SSN']['field']     = 'SSN';
      $criterias['SSN']['name']      = $LANG['rulesengine'][152].' : '.$LANG['common'][19];
      $criterias['SSN']['linkfield'] = 'HARDWARE_ID';

      return $criterias;
   }


   function getActions() {
      global $LANG;

      $actions = array();
      $actions['_fusion']['name'] = $LANG['ocsng'][58];
      $actions['_fusion']['type'] = 'text';
      $actions['_fusion']['type'] = 'fusion_type';

      $actions['_ignore_import']['name'] = $LANG['rulesengine'][132];
      $actions['_ignore_import']['type'] = 'yesonly';

      return $actions;
   }

   static function getRuleActionValues() {
      global $LANG;
      return array(self::RULE_ACTION_LINK_OR_IMPORT       => $LANG['ocsng'][78],
                      self::RULE_ACTION_LINK_OR_NO_IMPORT => $LANG['ocsng'][79]);
   }
   /**
    * Add more action values specific to this type of rule
    * @param value the value for this action
    * @return the label's value or ''
    */
   function displayAdditionRuleActionValue($value) {
      global $LANG;
      $values = self::getRuleActionValues();
      if (isset($values[$value])) {
         return $values[$value];
      } else {
         return '';
      }
   }

   function manageSpecificCriteriaValues($criteria, $name, $value) {
      global $LANG;

      switch ($criteria['type']) {
         case "state":
            $link_array = array("0" => $LANG['choice'][0],
                                "1" => $LANG['choice'][1]." : ".$LANG['ocsconfig'][57],
                                "2" => $LANG['choice'][1]." : ".$LANG['ocsconfig'][56]);

            Dropdown::showFromArray($name, $link_array, array('value' => $value));
      }
      return false;
   }

   /**
    * Add more criteria specific to this type of rule
    */
   static function addMoreCriteria() {
      global $LANG;
      return array(Rule::PATTERN_FIND      => $LANG['rulesengine'][151],
                   RuleImportComputer::PATTERN_IS_EMPTY => $LANG['rulesengine'][154]);
   }

   function displayAdditionalRuleCondition($condition, $criteria, $name, $value) {
      if ($condition == Rule::PATTERN_FIND 
            || $condition == RuleImportComputer::PATTERN_IS_EMPTY) {
         Dropdown::showYesNo($name, 0, 0);
         return true;
      }
      return false;
   }
   
   function displayAdditionalRuleAction($action,$params = array()) {
      global $LANG;
      switch ($action['type']) {
         case 'fusion_type':
            Dropdown::showFromArray('value',self::getRuleActionValues());
            break;
         default:
            break;
      }
      return true;
   }

   function checkComplexCriteria($input) {
      global $DB;

      $complex_criterias = array();
      $sql_where = '';
      $sql_from = '';
      $continue = true;
      
      foreach ($this->criterias as $criteria) {
         //If the field used by the criteria is not present in the source data, don't try
         //to go further ! 
         if (!isset($input[$criteria->fields['criteria']])
               || (in_array($criteria->fields['criteria'],array('IPSUBNET','MACADDRESS','IPADDRESS'))
                   && (!is_array($input[$criteria->fields['criteria']])))) {
            $continue = false;
            break;
         }
         //It's a complex criteria or it's the 'state' criteria
         if ($criteria->fields['condition'] == Rule::PATTERN_FIND
               || $criteria->fields['condition'] == 'state') {
            $complex_criterias[] = $criteria;
         }
      }
      

      //If no complex criteria or a value is missing, then there's a problem !
      if (!$continue || empty($complex_criterias)) {
         return false;
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
            case 'IPADDRESS':
               $sql_from .= " LEFT JOIN `glpi_networkports`
                                 ON (`glpi_computers`.`id`=`glpi_networkports`.`items_id`
                                    AND `glpi_networkports`.`itemtype` = 'Computer') ";
               $sql_where .= " AND `glpi_networkports`.`ip` IN ";
               for ($i=0 ; $i<count($input["IPADDRESS"]) ; $i++) {
                  $sql_where .= ($i>0 ? ',"' : '("').$input["IPADDRESS"][$i].'"';
               }
               $sql_where .= ")";
               break;
            case 'MACADDRESS':
               $sql_where .= " AND `glpi_networkports`.`mac` IN (";
               $sql_where.= implode(',',$input['MACADDRESS']);
               $sql_where .= ")";
               break;
            case 'MACHINE_NAME':
               if ($criteria->fields['condition'] == self::PATTERN_IS_EMPTY) {
                  $sql_where .= " AND (`glpi_computers`.`name`='' OR `glpi_computers`.`name` IS NULL) ";
               } else {
                  $sql_where .= " AND (`glpi_computers`.`name`='".$input['name']."') ";
               }
               break;
            case 'SSN':
               $sql_where .= " AND `glpi_computers`.`serial`='".$input["serial"]."'";
               break;
            case 'state':
               if ($criteria['condition'] == Rule::PATTERN_IS) {
                  $condition = " IN ";
               } else {
                  $conditin = " NOT IN ";
               }
               $sql_where .= " AND `glpi_computers`.`states_id` $condition ('".$criteria['']."')";
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
      } else {
         return false;
      }
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
                     $output['action'] = OcsServer::LINK_RESULT_LINK;
                  } else {
                     $output['action'] = OcsServer::LINK_RESULT_IMPORT;
                  }
               } elseif ($action->fields["value"] == self::RULE_ACTION_LINK_OR_NO_IMPORT) {
                  if (isset($this->criterias_results['found_computers'])) {
                     $output['found_computers'] = $this->criterias_results['found_computers'];
                     $output['action'] = OcsServer::LINK_RESULT_LINK;
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

}

?>
