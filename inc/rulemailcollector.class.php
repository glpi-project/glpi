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

      $criterias['from']['name']  = $LANG['rulesengine'][136];
      $criterias['from']['table'] = '';
      $criterias['from']['type'] = 'text';

      $criterias['fromName']['name']  = 'fromName';
      $criterias['fromName']['table'] = '';
      $criterias['fromName']['type'] = 'text';

      $criterias['to']['name']  = $LANG['rulesengine'][137];
      $criterias['to']['table'] = '';
      $criterias['to']['type'] = 'text';

      $criterias['toName']['name']  = 'toName';
      $criterias['toName']['table'] = '';
      $criterias['toName']['type'] = 'text';

      $criterias['subject']['name']  = $LANG['common'][90];
      $criterias['subject']['table'] = '';
      $criterias['subject']['type'] = 'text';

      $criterias['content']['name']  = $LANG['mailing'][115];
      $criterias['content']['table'] = '';
      $criterias['content']['type'] = 'text';

      $criterias['GROUPS']['table']     = 'glpi_groups';
      $criterias['GROUPS']['field']     = 'name';
      $criterias['GROUPS']['name']      = $LANG['rulesengine'][143];
      $criterias['GROUPS']['linkfield'] = '';
      $criterias['GROUPS']['type']      = 'dropdown';
      $criterias['GROUPS']['virtual']   = true;
      $criterias['GROUPS']['id']        = 'groups';

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

      $actions['_affect_entity_by_tag']['name']  = $LANG['rulesengine'][131];
      $actions['_affect_entity_by_tag']['type']  = 'text';
      $actions['_affect_entity_by_tag']['force_actions'] = array('regex_result');

      $actions['_refuse_email_no_response']['name']   = $LANG['rulesengine'][134];
      $actions['_refuse_email_no_response']['type']   = 'yesno';
      $actions['_refuse_email_no_response']['table']   = '';

      $actions['_refuse_email_with_response']['name']   = $LANG['rulesengine'][135];
      $actions['_refuse_email_with_response']['type']   = 'yesno';
      $actions['_refuse_email_with_response']['table']   = '';
      return $actions;
   }

   function executeActions($output,$params,$regex_results) {

      if (count($this->actions)) {
         foreach ($this->actions as $action) {
            switch ($action->fields["action_type"]) {
               case "assign" :
                  $output[$action->fields["field"]] = $action->fields["value"];
                  break;

               case "regex_result" :
                  switch ($action->fields["field"]) {
                     case "_affect_entity_by_domain" :
                     case "_affect_entity_by_tag" :
                        $match_entity = false;
                        foreach ($regex_results as $regex_result) {
                           $res = RuleAction::getRegexResultById($action->fields["value"],array($regex_result));
                           if ($res != null) {
                              if ($action->fields["field"] == "_affect_entity_by_domain") {
                                 $entity_found = EntityData::getEntityIDByDomain($res);
                              }
                              else {
                                 $entity_found = EntityData::getEntityIDByTag($res);
                              }
                              //If an entity was found
                              if ($entity_found > -1) {
                                 $output['entities_id'] = $entity_found;
                                 $match_entity = true;
                                 break;
                              }
                           }
                        }
                        break;
                  } // switch (field)
            }
         }
      }

      return $output;
   }
}
?>
