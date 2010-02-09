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
   public $sub_type = RULE_AFFECT_RIGHTS;
   public $right='rule_ldap';
   public $orderby="name";

   /**
    * Constructor
   **/
   function __construct() {
      //Dynamically add all the ldap criterias to the current list of rule's criterias
      $this->addLdapCriteriasToArray();

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

   /**
    * Display form to add rules
    * @param $ID entity ID
    */
   function showAndAddRuleForm($ID) {
      global $LANG, $CFG_GLPI;

      $canedit = haveRight($this->right, "w");

      if ($canedit) {
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
         echo "<input type=hidden name='sub_type' value=\"" . $this->sub_type . "\">";
         echo "<input type=hidden name='entities_id' value='-1'>";
         echo "<input type=hidden name='affectentity' value='$ID'>";
         echo "<input type=hidden name='_method' value='addLdapRule'>";
         echo "<input type='submit' name='execute' value=\"" . $LANG['buttons'][8] .
                "\" class='submit'>";
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td class='center'>".$LANG['profiles'][22] . "&nbsp;:&nbsp;";
         Dropdown::show('Profile');
         echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$LANG['profiles'][28] . "&nbsp;:&nbsp;";
         Dropdown::showYesNo("is_recursive",0);
         echo "</td></tr>\n";

         echo "</table></form><br>";
      }
      //Get all rules and actions
      $rules = $this->getRulesForEntity( $ID, 0, 1);

      if (empty ($rules)) {
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr><th>" . $LANG['entity'][6] . " - " . $LANG['search'][15] . "</th></tr>\n";
         echo "</table><br>\n";
      } else {
         if ($canedit) {
            echo "<form name='ldapaffectation_form' id='ldapaffectation_form' method='post' ".
                   "action='".GLPI_ROOT."/front/ruleright.php'>";
         }
         echo "<div class='center'><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='3'>" . $LANG['entity'][6] . "</th></tr>";
         initNavigateListItems('RuleRight',$LANG['entity'][0]."=".Dropdown::getDropdownName("glpi_entities",$ID));

         foreach ($rules as $rule) {
            addToNavigateListItems('RuleRight',$rule->fields["id"]);
            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
               echo "<td width='10'>";
               echo "<input type='checkbox' name='item[" . $rule->fields["id"] . "]' value='1'>";
               echo "</td>";
            }
            if ($canedit) {
               echo "<td><a href=\"" . $CFG_GLPI["root_doc"] . "/front/ruleright.form.php?id=" .
                          $rule->fields["id"] . "&amp;onglet=1\">" . $rule->fields["name"] . "</a>";
               echo "</td>";
            } else {
               echo "<td>" . $rule->fields["name"] . "</td>";
            }
            echo "<td>" . $rule->fields["description"] . "</td>";
            echo "</tr>";
         }
         echo "</table><br>";

         if ($canedit) {
            openArrowMassive("ldapaffectation_form", true);
            echo "<input type='hidden' name='action' value='delete'>";
            closeArrowMassive('massiveaction', $LANG['buttons'][6]);
            echo "</form>";
         }
      }
   }

   /**
    * Get all ldap rules criterias from the DB and add them into the RULES_CRITERIAS
    */
   function addLdapCriteriasToArray() {
      global $DB,$RULES_CRITERIAS;

      $sql = "SELECT `name`, `value`
              FROM `glpi_ruleldapparameters`";
      $result = $DB->query($sql);
      while ($datas = $DB->fetch_array($result)) {
         $RULES_CRITERIAS[$this->sub_type][$datas["value"]]['name']=$datas["name"];
         $RULES_CRITERIAS[$this->sub_type][$datas["value"]]['field']=$datas["value"];
         $RULES_CRITERIAS[$this->sub_type][$datas["value"]]['linkfield']='';
         $RULES_CRITERIAS[$this->sub_type][$datas["value"]]['table']='';
      }
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
                           $res = RuleAction::getRegexResultById($action->fields["value"],array($regex_result));
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

   /**
    * Return all rules from database
    * @param $ID of entity
    * @param $withcriterias import rules criterias too
    * @param $withactions import rules actions too
    */
   function getRulesForEntity($ID, $withcriterias, $withactions) {
      global $DB;

      $ldap_affect_user_rules = array ();

      //Get all the rules whose sub_type is $sub_type and entity is $ID
      $sql = "SELECT `glpi_rules`.`id`
              FROM `glpi_ruleactions`, `glpi_rules`
              WHERE `glpi_ruleactions`.`rules_id` = `glpi_rules`.`id`
                    AND `glpi_ruleactions`.`field` = 'entities_id'
                    AND `glpi_rules`.`sub_type` = '".$this->sub_type."'
                    AND `glpi_ruleactions`.`value` = '$ID'";
      $result = $DB->query($sql);
      while ($rule = $DB->fetch_array($result)) {
         $affect_rule = new Rule;
         $affect_rule->getRuleWithCriteriasAndActions($rule["id"], 0, 1);
         $ldap_affect_user_rules[] = $affect_rule;
      }
      return $ldap_affect_user_rules;
   }

   function getTitleRule($target) {
   }

   function getTitle() {
      global $LANG;

      return $LANG['entity'][6];
   }

}


?>
