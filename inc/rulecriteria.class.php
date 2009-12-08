<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

/// Criteria Rule class
class RuleCriteria extends CommonDBTM {
   // From CommonDBTM
   public $table = 'glpi_rulecriterias';

   /**
    * Get all criterias for a given rule
    * @param $ID the rule_description ID
    * @return an array of RuleCriteria objects
   **/
   function getRuleCriterias($ID) {
      global $DB;

      $sql = "SELECT *
              FROM `glpi_rulecriterias`
              WHERE `rules_id` = '$ID'";
      $result = $DB->query($sql);

      $rules_list = array ();
      while ($rule = $DB->fetch_assoc($result)) {
         $tmp = new RuleCriteria;
         $tmp->fields = $rule;
         $rules_list[] = $tmp;
      }
      return $rules_list;
   }

   /**
    * Process a criteria of a rule
    * @param $input the input data used to check criterias
    * @param $regex_result
   **/
   function process(&$input,&$regex_result) {

      // Undefine criteria field : set to blank
      if (!isset($input[$this->fields["criteria"]])) {
         $input[$this->fields["criteria"]]='';
      }

      //If the value is not an array
      if (!is_array($input[$this->fields["criteria"]])) {
         $value=$this->getValueToMatch($this->fields["condition"],$input[$this->fields["criteria"]]);
         $res = matchRules($value,$this->fields["condition"],$this->fields["pattern"],$regex_result);
      } else {
         //If the value if, in fact, an array of values
         // Negative condition : Need to match all condition (never be)
         if (in_array($this->fields["condition"],array(PATTERN_IS_NOT,
                                                       PATTERN_NOT_CONTAIN,
                                                       REGEX_NOT_MATCH))) {
            $res = true;
            foreach($input[$this->fields["criteria"]] as $tmp) {
               $value=$this->getValueToMatch($this->fields["condition"],$tmp);
               $res &= matchRules($value,$this->fields["condition"],$this->fields["pattern"],
                                  $regex_result);
            }

         // Positive condition : Need to match one
         } else {
            $res = false;
            foreach($input[$this->fields["criteria"]] as $tmp) {
               $value=$this->getValueToMatch($this->fields["condition"],$tmp);
               $res |= matchRules($value,$this->fields["condition"],$this->fields["pattern"],
                                  $regex_result);
               if ($res) {
                  break;
               }
            }
         }
         return $value;
      }
      return $res;
   }

   /**
    * Return a value associated with a pattern associated to a criteria to compare it
    * @param $condition condition used
    * @param $initValue the pattern
   **/
   function getValueToMatch($condition,&$initValue) {

      if (!empty($this->type)
          && ($condition!=PATTERN_IS && $condition!=PATTERN_IS_NOT)) {
         switch ($this->type) {
            case "dropdown" :
               return CommonDropdown::getDropdownName($this->table,$initValue);

            case "dropdown_users" :
               return getUserName($initValue);

            case "dropdown_tracking_itemtype" :
               $ci =new CommonItem();
               $ci->setType($initValue);
               return $ci->getType($initValue);

            case "dropdown_urgency" :
               return Ticket::getUrgencyName($initValue);

            case "dropdown_impact" :
               return Ticket::getImpactName($initValue);

            case "dropdown_priority" :
               return Ticket::getPriorityName($initValue);
         }
      }
      return $initValue;
   }

}

?>
