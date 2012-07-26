<?php
/*
 * @version $Id:
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

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

// ----------------------------------------------------------------------
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// OCS Rules class
class RuleImportEntity extends Rule {

   // From Rule
   public $right    = 'rule_ocs';
   public $can_sort = true;


   function getTitle() {
      return __('Rules for assigning a computer to an entity');
   }


   /**
    * @see inc/Rule::maxActionsCount()
   **/
   function maxActionsCount() {
      // Unlimited
      return 2;
   }


   /**
    * @see inc/Rule::preProcessPreviewResults()
   **/
   function preProcessPreviewResults($output) {
      return $output;
   }


   /**
    * @see inc/Rule::executeActions()
   **/
   function executeActions($output, $params) {

      if (count($this->actions)) {
         foreach ($this->actions as $action) {
            switch ($action->fields["action_type"]) {
               case "assign" :
                  $output[$action->fields["field"]] = $action->fields["value"];
                  break;

               case "regex_result" :
                  //Assign entity using the regex's result
                  if ($action->fields["field"] == "_affect_entity_by_tag") {
                     //Get the TAG from the regex's results
                     if (isset($this->regex_results[0])) {
                        $res = RuleAction::getRegexResultById($action->fields["value"],
                                                              $this->regex_results[0]);
                     } else {
                        $res = $action->fields["value"];
                     }
                     if ($res != null) {
                        //Get the entity associated with the TAG
                        $target_entity = Entity::getEntityIDByTag($res);
                        if ($target_entity != '') {
                           $output["entities_id"] = $target_entity;
                        }
                     }
                  }
                  break;
            }
         }
      }
      return $output;
   }


   /**
    * @see inc/Rule::getCriterias()
   **/
   function getCriterias() {

      $criterias                                = array();
/*
      $criterias['TAG']['table']                = 'accountinfo';
      $criterias['TAG']['field']                = 'TAG';
      $criterias['TAG']['name']                 = __('OCSNG TAG');
      $criterias['TAG']['linkfield']            = 'HARDWARE_ID';

      $criterias['DOMAIN']['table']             = 'hardware';
      $criterias['DOMAIN']['field']             = 'WORKGROUP';
      $criterias['DOMAIN']['name']              = __('Domain');
      $criterias['DOMAIN']['linkfield']         = '';

      $criterias['OCS_SERVER']['table']         = 'glpi_ocsservers';
      $criterias['OCS_SERVER']['field']         = 'name';
      $criterias['OCS_SERVER']['name']          = _n('OCSNG server', 'OCSNG servers', 1);
      $criterias['OCS_SERVER']['linkfield']     = '';
      $criterias['OCS_SERVER']['type']          = 'dropdown';
      $criterias['OCS_SERVER']['virtual']       = true;
      $criterias['OCS_SERVER']['id']            = 'ocs_server';

      $criterias['IPSUBNET']['table']           = 'networks';
      $criterias['IPSUBNET']['field']           = 'IPSUBNET';
      $criterias['IPSUBNET']['name']            = __('Subnet');
      $criterias['IPSUBNET']['linkfield']       = 'HARDWARE_ID';

      $criterias['IPADDRESS']['table']          = 'networks';
      $criterias['IPADDRESS']['field']          = 'IPADDRESS';
      $criterias['IPADDRESS']['name']           = __('IP address');
      $criterias['IPADDRESS']['linkfield']      = 'HARDWARE_ID';

      $criterias['MACHINE_NAME']['table']       = 'hardware';
      $criterias['MACHINE_NAME']['field']       = 'NAME';
      $criterias['MACHINE_NAME']['name']        = __("Computer's name");
      $criterias['MACHINE_NAME']['linkfield']   = '';

      $criterias['DESCRIPTION']['table']        = 'hardware';
      $criterias['DESCRIPTION']['field']        = 'DESCRIPTION';
      $criterias['DESCRIPTION']['name']         = __('Description');
      $criterias['DESCRIPTION']['linkfield']    = '';

      $criterias['SSN']['table']                = 'bios';
      $criterias['SSN']['field']                = 'SSN';
      $criterias['SSN']['name']                 = __('Serial number');
      $criterias['SSN']['linkfield']            = 'HARDWARE_ID';
*/
      $criterias['_source']['table']            = '';
      $criterias['_source']['field']            = '_source';
      $criterias['_source']['name']             = __('Source');
      $criterias['_source']['allow_condition']  = array(Rule::PATTERN_IS, Rule::PATTERN_IS_NOT);

      return $criterias;
   }


   /**
    * @since version 0.84
    *
    * @see inc/Rule::displayAdditionalRuleCondition()
   **/
   function displayAdditionalRuleCondition($condition, $criteria, $name, $value, $test=false) {
      global $PLUGIN_HOOKS;
      if ($criteria['field'] == '_source') {
         $tab = array();
         foreach($PLUGIN_HOOKS['import_item'] as $plug => $types) {
            $tab[$plug] = Plugin::getInfo($plug, 'name');
         }
         Dropdown::showFromArray($name, $tab);
         return true;
      }
      return false;
   }

   /**
    * @since version 0.84
    *
    * @see inc/Rule::getAdditionalCriteriaDisplayPattern()
   **/
   function getAdditionalCriteriaDisplayPattern($ID, $condition, $pattern) {
      $crit = $this->getCriteria($ID);
      if ($crit['field'] == '_source') {
         $name = Plugin::getInfo($pattern, 'name');
         if (empty($name)) {
            return false;
         } else {
            return $name;
         }
      }
   }

   /**
    * @see inc/Rule::getActions()
   **/
   function getActions() {

      $actions                                           = array();

      $actions['entities_id']['name']                    = __('Entity');
      $actions['entities_id']['type']                    = 'dropdown';
      $actions['entities_id']['table']                   = 'glpi_entities';

      $actions['locations_id']['name']                   = __('Location');
      $actions['locations_id']['type']                   = 'dropdown';
      $actions['locations_id']['table']                  = 'glpi_locations';

      $actions['_affect_entity_by_tag']['name']          = __('Entity from TAG');
      $actions['_affect_entity_by_tag']['type']          = 'text';
      $actions['_affect_entity_by_tag']['force_actions'] = array('regex_result');

      $actions['_ignore_ocs_import']['name']             = __('To be unaware of import');
      $actions['_ignore_ocs_import']['type']             = 'yesonly';

      return $actions;
   }

}
?>
