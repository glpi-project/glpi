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
class RuleRight extends Rule {

   // From Rule
   public $right='rule_ldap';
   public $orderby="name";
   public $specific_parameters = true;

   /**
    * Constructor
   **/
   function __construct() {
      // Temproray hack for this class
      $this->forceTable('glpi_rules');
   }

   function canCreate() {
      return haveRight('rule_ldap', 'w');
   }

   function canView() {
      return haveRight('rule_ldap', 'r');
   }

   function preProcessPreviewResults($output) {
      return $output;
   }

   function maxActionsCount() {
      // Unlimited
      return 4;
   }

   function showNewRuleForm($ID) {
      global $LANG;

      echo "<form method='post' action='".getItemTypeFormURL('Entity')."'>";
      echo "<table  class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>" .$LANG['rulesengine'][19] . "</th></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16] . "&nbsp;:&nbsp;";
      autocompletionTextField($this, "name", array('value'=>'', 'size'=>33));
      echo "&nbsp;&nbsp;&nbsp;".$LANG['joblist'][6] . "&nbsp;:&nbsp;";
      autocompletionTextField($this, "description", array('value'=>'', 'size'=>33));
      echo "&nbsp;&nbsp;&nbsp;".$LANG['rulesengine'][9] . "&nbsp;:&nbsp;";
      $this->dropdownRulesMatch("match", "AND");
      echo "</td><td rowspan='2' class='tab_bg_2 center middle'>";
      echo "<input type=hidden name='sub_type' value=\"" . get_class($this) . "\">";
      echo "<input type=hidden name='entities_id' value='-1'>";
      echo "<input type=hidden name='affectentity' value='$ID'>";
      echo "<input type=hidden name='_method' value='addRule'>";
      echo "<input type='submit' name='execute' value=\"" . $LANG['buttons'][8] .
             "\" class='submit'>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='center'>".$LANG['profiles'][22] . "&nbsp;:&nbsp;";
      Dropdown::show('Profile');
      echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$LANG['profiles'][28] . "&nbsp;:&nbsp;";
      Dropdown::showYesNo("is_recursive",0);
      echo "</td></tr>\n";

      echo "</table></form>";
   }

   /**
    * Filter actions if needed
   *  @param $actions the actions array
    * @return the filtered actions array
    */
   function filterActions($actions) {

      $RuleAction = new RuleAction;
      $this->actions = $RuleAction->getRuleActions($this->fields["id"]);
      foreach($this->actions as $action) {
         switch ($action->fields["field"]) {
            case "_affect_entity_by_dn" :
               unset($actions["_affect_entity_by_tag"]);
               unset($actions["entities_id"]);
               break;

            case "_affect_entity_by_tag" :
               unset($actions["_affect_entity_by_dn"]);
               unset($actions["entities_id"]);
               break;

            case "entities_id" :
               unset($actions["_affect_entity_by_tag"]);
               unset($actions["_affect_entity_by_dn"]);
               break;
         }
      }
      return $actions;
   }

   /**
   * Execute the actions as defined in the rule
   * @param $output the result of the actions
   * @param $params the parameters
   * @param $regex_results array results of the regex match if used in criteria
   * @return the fields modified
   */
   function executeActions($output,$params,$criterias_result,$regex_results) {
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

                     case "profiles_id" :
                        $right = $action->fields["value"];
                        break;

                     case "is_recursive" :
                        $is_recursive = $action->fields["value"];
                        break;

                     case "is_active" :
                        $output["is_active"] = $action->fields["value"];
                        break;

                     case "_ignore_user_import" :
                        $continue = false;
                        $output_src["_stop_import"] = true;
                        break;
                  } // switch (field)
                  break;

               case "regex_result" :
                  switch ($action->fields["field"]) {
                     case "_affect_entity_by_dn" :
                     case "_affect_entity_by_tag" :
                        $match_entity = false;
                        $entity = array();
                        foreach ($regex_results as $regex_result) {
                           $res = RuleAction::getRegexResultById($action->fields["value"],$regex_result);
                           if ($res != null) {
                              if ($action->fields["field"] == "_affect_entity_by_dn") {
                                 $entity_found = EntityData::getEntityIDByDN($res);
                              } else {
                                $entity_found = EntityData::getEntityIDByTag($res);
                              }
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

      if ($continue) {
         //Nothing to be returned by the function :
         //Store in session the entity and/or right
         if ($entity != '' && $right != '') {
            $output["_ldap_rules"]["rules_entities_rights"][] = array($entity,
                                                                      $right,
                                                                      $is_recursive);
         } else if ($entity != '') {
            if (!is_array($entity)) {
              $entities_array=array($entity,$is_recursive);
              $output["_ldap_rules"]["rules_entities"][]=array($entities_array);
            //If it comes from a regex with multiple results
            } else {
               $output["_ldap_rules"]["rules_entities"][] = $entity;
            }
         } else if ($right != '') {
            $output["_ldap_rules"]["rules_rights"][]=$right;
         }

         return $output;
      } else {
         return $output_src;
      }
   }

   function getTitleRule($target) {
   }

   function getTitle() {
      global $LANG;

      return $LANG['entity'][6];
   }

   function getCriterias() {
      global $LANG;
      $criterias = array();
      $criterias['LDAP_SERVER']['table']     = 'glpi_authldaps';
      $criterias['LDAP_SERVER']['field']     = 'name';
      $criterias['LDAP_SERVER']['name']      = $LANG['login'][2];
      $criterias['LDAP_SERVER']['linkfield'] = '';
      $criterias['LDAP_SERVER']['type']      = 'dropdown';
      $criterias['LDAP_SERVER']['virtual']   = true;
      $criterias['LDAP_SERVER']['id']        = 'ldap_server';

      $criterias['MAIL_SERVER']['table']     = 'glpi_authmails';
      $criterias['MAIL_SERVER']['field']     = 'name';
      $criterias['MAIL_SERVER']['name']      = $LANG['login'][3];
      $criterias['MAIL_SERVER']['linkfield'] = '';
      $criterias['MAIL_SERVER']['type']      = 'dropdown';
      $criterias['MAIL_SERVER']['virtual']   = true;
      $criterias['MAIL_SERVER']['id']        = 'mail_server';

      $criterias['MAIL_EMAIL']['table']     = '';
      $criterias['MAIL_EMAIL']['field']     = '';
      $criterias['MAIL_EMAIL']['name']      = $LANG['login'][6]." ".$LANG['login'][3];
      $criterias['MAIL_EMAIL']['linkfield'] = '';
      $criterias['MAIL_EMAIL']['virtual']   = true;
      $criterias['MAIL_EMAIL']['id']        = 'mail_email';

      $criterias['GROUPS']['table']     = 'glpi_groups';
      $criterias['GROUPS']['field']     = 'name';
      $criterias['GROUPS']['name']      = $LANG['Menu'][36]." ".$LANG['login'][2];
      $criterias['GROUPS']['linkfield'] = '';
      $criterias['GROUPS']['type']      = 'dropdown';
      $criterias['GROUPS']['virtual']   = true;
      $criterias['GROUPS']['id']        = 'groups';

      //Dynamically add all the ldap criterias to the current list of rule's criterias
      $this->addSpecificCriteriasToArray($criterias);
      return $criterias;
   }

   function getActions() {
      global $LANG;
      $actions = array();
      $actions['entities_id']['name']   = $LANG['entity'][0];
      $actions['entities_id']['type']   = 'dropdown';
      $actions['entities_id']['table']  = 'glpi_entities';

      $actions['_affect_entity_by_dn']['name']   = $LANG['rulesengine'][130];
      $actions['_affect_entity_by_dn']['type']   = 'text';
      $actions['_affect_entity_by_dn']['force_actions'] = array('regex_result');

      $actions['_affect_entity_by_tag']['name']  = $LANG['rulesengine'][131];
      $actions['_affect_entity_by_tag']['type']  = 'text';
      $actions['_affect_entity_by_tag']['force_actions'] = array('regex_result');

      $actions['profiles_id']['name']  = $LANG['Menu'][35];
      $actions['profiles_id']['type']  = 'dropdown';
      $actions['profiles_id']['table'] = 'glpi_profiles';

      $actions['is_recursive']['name']  = $LANG['profiles'][28];
      $actions['is_recursive']['type']  = 'yesno';
      $actions['is_recursive']['table'] = '';

      $actions['is_active']['name']  = $LANG['common'][60];
      $actions['is_active']['type']  = 'yesno';
      $actions['is_active']['table'] = '';

      $actions['_ignore_user_import']['name']  = $LANG['rulesengine'][132];
      $actions['_ignore_user_import']['type']  = 'yesno';
      $actions['_ignore_user_import']['table'] = '';
      return $actions;
   }

   /**
    * Get all ldap rules criterias from the DB and add them into the RULES_CRITERIAS
    */
   function addSpecificCriteriasToArray(&$criterias) {

      foreach (getAllDatasFromTable('glpi_rulerightparameters') as $datas ) {
         $criterias[$datas["value"]]['name']=$datas["name"];
         $criterias[$datas["value"]]['field']=$datas["value"];
         $criterias[$datas["value"]]['linkfield']='';
         $criterias[$datas["value"]]['table']='';
      }
   }
}


?>
