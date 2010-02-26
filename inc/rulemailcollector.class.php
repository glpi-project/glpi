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

/// Rule class for Rights management
class RuleMailCollector extends Rule {

   // From Rule
   public $right='rule_mailcollector';
   public $orderby="name";

   /**
    * Constructor
   **/
   function __construct() {

      // Temproray hack for this class
      $this->forceTable('glpi_rules');
   }

   function canCreate() {
      return haveRight('rule_mailcollector', 'w');
   }

   function canView() {
      return haveRight('rule_mailcollector', 'r');
   }

   function preProcessPreviewResults($output) {
      return $output;
   }

   function maxActionsCount() {
      return 1;
   }


   /**
   * Execute the actions as defined in the rule
   * @param $output the result of the actions
   * @param $params the parameters
   * @param $regex_results array results of the regex match if used in criteria
   * @return the fields modified
   */
   /*
   function executeActions($output,$params,$regex_results) {
      global $CFG_GLPI;

      $entity='';
      $right='';
      $is_recursive = 0;
      $continue = true;
      $output_src = $output;

      if (count($this->actions)) {
         foreach ($this->actions as $action) {
            switch ($action->fields["action_type"]) {
               case "assign" :
                  switch ($action->fields["field"]) {
                     case "entities_id" :
                        $entity = $action->fields["value"];
                        break;
               case "regex_result" :
                  switch ($action->fields["field"]) {
                     case "_affect_entity_by_domain" :
                        $match_entity = false;
                        $entity = array();
                        foreach ($regex_results as $regex_result) {
                           $res = RuleAction::getRegexResultById($action->fields["value"],array($regex_result));
                           if ($res != null) {
                                $entity_found = EntityData::getEntityIDByDomain($res);
                              //If an entity was found
                              if ($entity > -1) {
                                 array_push($entity, array($entity_found,
                                                           $is_recursive));
                                 $match_entity=true;
                              }
                           }
                        }
                        if (!$match_entity) {
                           //Not entity assigned : action processing must be stopped for this rule
                           $continue = false;
                        }
                        break;
                  } // switch (field)
                  break;
               } // switch (action_type)
            } // foreach (action)
         } // count (actions)
      }
   }*/

   function getTitleRule($target) {
   }

   function getTitle() {
      global $LANG;

      return $LANG['rulesengine'][70];
   }

   function getCriterias() {
      global $LANG;
      $criterias = array();
      $criterias['mailcollector']['field'] = 'name';
      $criterias['mailcollector']['name']  = $LANG['mailgate'][0];
      $criterias['mailcollector']['table'] = 'glpi_mailcollectors';
      $criterias['mailcollector']['type'] = 'dropdown';

      $criterias['from_email']['name']  = $LANG['rulesengine'][136];
      $criterias['from_email']['table'] = '';
      $criterias['from_email']['type'] = 'text';

      $criterias['to_email']['name']  = $LANG['rulesengine'][137];
      $criterias['to_email']['table'] = '';
      $criterias['to_email']['type'] = 'text';

      $criterias['title']['name']  = $LANG['common'][90];
      $criterias['title']['table'] = '';
      $criterias['title']['type'] = 'text';

      $criterias['content']['name']  = $LANG['mailing'][115];
      $criterias['content']['table'] = '';
      $criterias['content']['type'] = 'text';

      return $criterias;
   }

   function getActions() {
      global $LANG;
      $actions = array();
      $actions['entities_id']['name']   = $LANG['entity'][0];
      $actions['entities_id']['type']   = 'dropdown';
      $actions['entities_id']['table']  = 'glpi_entities';

      $actions['_affect_entity_by_domain']['name']   = $LANG['rulesengine'][133];
      $actions['_affect_entity_by_domain']['type']   = 'text';
      $actions['_affect_entity_by_domain']['force_actions'] = array('regex_result');

      $actions['_refuse_email_no_response']['name']   = $LANG['rulesengine'][134];
      $actions['_refuse_email_no_response']['type']   = 'yesno';
      $actions['_refuse_email_no_response']['table']   = '';

      $actions['_refuse_email_with_response']['name']   = $LANG['rulesengine'][135];
      $actions['_refuse_email_with_response']['type']   = 'yesno';
      $actions['_refuse_email_with_response']['table']   = '';
      return $actions;
   }
}
?>
