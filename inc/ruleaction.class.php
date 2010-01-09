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

class RuleAction extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_ruleactions';

   /**
    * Get all actions for a given rule
    * @param $ID the rule_description ID
    * @return an array of RuleAction objects
   **/
   function getRuleActions($ID) {
      global $DB;

      $sql = "SELECT *
              FROM `glpi_ruleactions`
              WHERE `rules_id` = '$ID'";
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

}

?>
