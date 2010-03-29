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
class RuleOcs extends Rule {

   // From Rule
   public $right='rule_ocs';
   public $can_sort=true;

   function canCreate() {
      return haveRight('rule_ocs', 'w');
   }

   function canView() {
      return haveRight('rule_ocs', 'r');
   }

   function getTitle() {
      global $LANG;

      return $LANG['rulesengine'][18];
   }

   function maxActionsCount() {
      // Unlimited
      return 1;
   }


   function preProcessPreviewResults($output) {
      return $output;
   }

   function executeActions($output,$params,$criterias_result,$regex_results) {

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
                     $res = RuleAction::getRegexResultById($action->fields["value"],$regex_results);
                     if ($res != null) {
                        //Get the entity associated with the TAG
                        $target_entity = EntityData::getEntityIDByTag($res);
                        if ($target_entity != '') {
                           $output["entities_id"]=$target_entity;
                        }
                     }
                  }
                  break;
            }
         }
      }
      return $output;
   }

   function getCriterias() {
      global $LANG;
      $criterias = array ();

      $criterias['TAG']['table']     = 'accountinfo';
      $criterias['TAG']['field']     = 'TAG';
      $criterias['TAG']['name']      = $LANG['ocsconfig'][39];
      $criterias['TAG']['linkfield'] = 'HARDWARE_ID';

      $criterias['DOMAIN']['table']     = 'hardware';
      $criterias['DOMAIN']['field']     = 'WORKGROUP';
      $criterias['DOMAIN']['name']      = $LANG['setup'][89];
      $criterias['DOMAIN']['linkfield'] = '';

      $criterias['OCS_SERVER']['table']     = 'glpi_ocsservers';
      $criterias['OCS_SERVER']['field']     = 'name';
      $criterias['OCS_SERVER']['name']      = $LANG['ocsng'][29];
      $criterias['OCS_SERVER']['linkfield'] = '';
      $criterias['OCS_SERVER']['type']      = 'dropdown';
      $criterias['OCS_SERVER']['virtual']   = true;
      $criterias['OCS_SERVER']['id']        = 'ocs_server';

      $criterias['IPSUBNET']['table']     = 'networks';
      $criterias['IPSUBNET']['field']     = 'IPSUBNET';
      $criterias['IPSUBNET']['name']      = $LANG['networking'][61];
      $criterias['IPSUBNET']['linkfield'] = 'HARDWARE_ID';

      $criterias['IPADDRESS']['table']     = 'networks';
      $criterias['IPADDRESS']['field']     = 'IPADDRESS';
      $criterias['IPADDRESS']['name']      = $LANG['financial'][44]." ".
                                                                             $LANG['networking'][14];
      $criterias['IPADDRESS']['linkfield'] = 'HARDWARE_ID';

      $criterias['MACHINE_NAME']['table']     = 'hardware';
      $criterias['MACHINE_NAME']['field']     = 'NAME';
      $criterias['MACHINE_NAME']['name']      = $LANG['rulesengine'][25];
      $criterias['MACHINE_NAME']['linkfield'] = '';

      $criterias['DESCRIPTION']['table']     = 'hardware';
      $criterias['DESCRIPTION']['field']     = 'DESCRIPTION';
      $criterias['DESCRIPTION']['name']      = $LANG['joblist'][6];
      $criterias['DESCRIPTION']['linkfield'] = '';
      return $criterias;
   }

   function getActions() {
      global $LANG;
      $actions = array();
      $actions['entities_id']['name']  = $LANG['entity'][0];
      $actions['entities_id']['type']  = 'dropdown';
      $actions['entities_id']['table'] = 'glpi_entities';

      $actions['_affect_entity_by_tag']['name'] = $LANG['rulesengine'][131];
      $actions['_affect_entity_by_tag']['type'] = 'text';
      $actions['_affect_entity_by_tag']['force_actions'] = array('regex_result');
      return $actions;
   }
}

?>
