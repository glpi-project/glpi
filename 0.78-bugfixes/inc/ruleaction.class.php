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

class RuleAction extends CommonDBChild {

   // From CommonDBChild
   public $items_id  = 'rules_id';
   public $dohistory = true;

   // From CommonDBTM
   public $table = 'glpi_ruleactions';

   function __construct($rule_type='Rule') {
      $this->itemtype = $rule_type;
   }

   /**
   * Get title used in rule
   * @return Title of the rule
   **/
   static function getTypeName() {
      global $LANG;

      return $LANG['rulesengine'][7];
   }

   function getNameID($with_comment=0) {
      $rule = new $this->itemtype ();
      return html_clean($rule->getMinimalActionText($this->fields));
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'action_type';
      $tab[1]['linkfield']     = '';
      $tab[1]['name']          = $LANG['rulesengine'][7];
      $tab[1]['datatype']      = 'text';

      $tab[2]['table']     = $this->getTable();
      $tab[2]['field']     = 'field';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['rulesengine'][12];
      $tab[2]['datatype']  = 'text';

      $tab[3]['table']     = $this->getTable();
      $tab[3]['field']     = 'value';
      $tab[3]['linkfield'] = '';
      $tab[3]['name']      = $LANG['rulesengine'][13];
      $tab[3]['datatype']  = 'text';


      return $tab;
   }
   /**
    * Get all actions for a given rule
    * @param $ID the rule_description ID
    * @return an array of RuleAction objects
   **/
   function getRuleActions($ID) {
      global $DB;

      $sql = "SELECT *
              FROM `glpi_ruleactions`
              WHERE `rules_id` = '$ID'
              ORDER BY `id`";
      $result = $DB->query($sql);

      $rules_actions = array ();
      while ($rule = $DB->fetch_assoc($result)) {
         $tmp = new RuleAction;
         $tmp->fields = $rule;
         $rules_actions[] = $tmp;
      }
      return $rules_actions;
   }

   /**
    * Add an action
    * @param $action action type
    * @param $ruleid rule ID
    * @param $field field name
    * @param $value value
   **/
   function addActionByAttributes($action,$ruleid,$field,$value) {

      $ruleAction = new RuleAction;
      $input["action_type"]=$action;
      $input["field"]=$field;
      $input["value"]=$value;
      $input["rules_id"]=$ruleid;
      $ruleAction->add($input);
   }

   /**
   * Display a dropdown with all the possible actions
   **/
   static function dropdownActions($sub_type,$name,$value='') {
      global $LANG,$CFG_GLPI;

      $rule = new $sub_type();
      $actions_options = $rule->getActions();

      $actions=array("assign");
      if (isset($actions_options[$value]['force_actions'])) {
         $actions=$actions_options[$value]['force_actions'];
      }

      $elements=array();
      foreach ($actions as $action) {
         switch ($action) {
            case "assign" :
               $elements["assign"] = $LANG['rulesengine'][22];
               break;

            case "regex_result" :
               $elements["regex_result"] = $LANG['rulesengine'][45];
               break;

            case "append_regex_result" :
               $elements["append_regex_result"] = $LANG['rulesengine'][79];
               break;

            case "affectbyip" :
               $elements["affectbyip"] = $LANG['rulesengine'][46];
               break;

            case "affectbyfqdn" :
               $elements["affectbyfqdn"] = $LANG['rulesengine'][47];
               break;

            case "affectbymac" :
               $elements["affectbymac"] = $LANG['rulesengine'][49];
               break;

            case 'compute';
               $elements['compute'] = $LANG['rulesengine'][38];
               break;
         }
      }
      return Dropdown::showFromArray($name,$elements,array('value' => $value));
   }

   static function getActionByID($ID) {
      global $LANG;

      switch ($ID) {
         case "assign" :
            return $LANG['rulesengine'][22];

         case "regex_result" :
            return $LANG['rulesengine'][45];

         case "append_regex_result" :
            return $LANG['rulesengine'][79];

         case "affectbyip" :
            return $LANG['rulesengine'][46];

         case "affectbyfqdn" :
            return $LANG['rulesengine'][47];

         case "affectbymac" :
            return $LANG['rulesengine'][49];

         case 'compute' :
            return $LANG['rulesengine'][38];
      }
   }

   static function getRegexResultById($action,$regex_result) {
      $results = array();

      if (count($regex_result)>0) {
         if (preg_match_all("/#([0-9])/",$action,$results)>0) {
            foreach($results[1] as $result) {
               $action=str_replace("#$result",
                                 (isset($regex_result[$result])?
                                    $regex_result[$result]:''),$action);
            }
         }
      }
      return $action;
   }

   static function getAlreadyUsedForRuleID($rules_id,$sub_type) {
      global $DB;

      $rule = new $sub_type();
      $actions_options = $rule->getActions();

      $actions = array();
      $res = $DB->query("SELECT `field` FROM `glpi_ruleactions` WHERE `rules_id`='".$rules_id."'");
      while ($action = $DB->fetch_array($res)) {
         if (isset($actions_options[$action["field"]])) {
            $actions[$action["field"]] = $action["field"];
         }
      }
      return $actions;
   }

   function displayActionSelectPattern($options = array()) {
      $display=false;
      switch ($_POST["action_type"]) {
         //If a regex value is used, then always display an autocompletiontextfield
         case "regex_result" :
         case "append_regex_result" :
            autocompletionTextField($this,"value");
            break;
         default :
            $actions = Rule::getActionsByType($options["sub_type"]);
            if (isset($actions[$options["field"]]['type'])) {
               switch($actions[$options["field"]]['type']) {
                  case "dropdown" :
                     $table=$actions[$options["field"]]['table'];
                     Dropdown::show(getItemTypeForTable($table), array('name' => "value"));
                     $display=true;
                     break;
                  case "dropdown_assign" :
                     User::dropdown(array('name' => 'value','right' => 'own_ticket'));
                     $display=true;
                     break;
                  case "dropdown_users" :
                     User::dropdown(array('name'   => 'value',
                                          'right'  => 'all'));
                     $display=true;
                     break;

                  case "dropdown_urgency" :
                     Ticket::dropdownUrgency("value");
                     $display=true;
                     break;

                  case "dropdown_impact" :
                     Ticket::dropdownImpact("value");
                     $display=true;
                     break;

                  case "dropdown_priority" :
                     if ($_POST["action_type"]!='compute') {
                        Ticket::dropdownPriority("value");
                     }
                     $display=true;
                     break;

                  case "dropdown_status" :
                     Ticket::dropdownStatus("value");
                     $display=true;
                     break;

                  case "yesonly" :
                     Dropdown::showYesNo("value",0,0);
                     $display=true;
                     break;

                  case "yesno" :
                     Dropdown::showYesNo("value");
                     $display=true;
                     break;
               }
            }
            if (!$display) {
               autocompletionTextField($this, "value");
            }
      }
   }
}

?>
