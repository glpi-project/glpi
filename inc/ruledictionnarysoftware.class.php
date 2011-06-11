<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
* Rule class store all informations about a GLPI rule :
*   - description
*   - criterias
*   - actions
*
**/
class RuleDictionnarySoftware extends RuleCached {

   // From Rule
   public $right='rule_dictionnary_software';
   public $can_sort=true;

   function getTitle() {
      global $LANG;

      return $LANG['rulesengine'][35];
   }

   function maxActionsCount() {
      return 4;
   }

   function showCacheRuleHeader() {
      global $LANG;

      echo "<tr><th colspan='7'>" . $LANG['rulesengine'][100] . "&nbsp;: " . $this->fields["name"];
      echo "</th></tr>";
      echo "<tr><td class='tab_bg_1 b'>" . $LANG['rulesengine'][104] . "</td>";
      echo "<td class='tab_bg_1 b'>" . $LANG['common'][5] . " " . $LANG['rulesengine'][108] . "</td>";
      echo "<td class='tab_bg_1 b'>" . $LANG['rulesengine'][105] . "</td>";
      echo "<td class='tab_bg_1 b'>" . $LANG['rulesengine'][78] . "</td>";
      echo "<td class='tab_bg_1 b'>" . $LANG['common'][5] . "</td>";
      echo "<td class='tab_bg_1 b'>" . $LANG['ocsconfig'][6] . "</td>";
      echo "<td class='tab_bg_1 b'>" . $LANG['software'][46] . "</td></tr>\n";
   }

   function showCacheRuleDetail($fields) {
      global $LANG;

      echo "<td class='tab_bg_2'>" . $fields["old_value"] . "</td>";
      echo "<td class='tab_bg_2'>" . $fields["manufacturer"] . "</td>";
      echo "<td class='tab_bg_2'>" .
            ($fields["new_value"] != '' ? $fields["new_value"] : $LANG['rulesengine'][106]) . "</td>";
      echo "<td class='tab_bg_2'>" .
            ($fields["version"] != '' ? $fields["version"] : $LANG['rulesengine'][106]) . "</td>";
      echo "<td class='tab_bg_2'>" .
            ((isset ($fields["new_manufacturer"]) && $fields["new_manufacturer"] != '') ?
             Dropdown::getDropdownName("glpi_manufacturers", $fields["new_manufacturer"]) :
             $LANG['rulesengine'][106]) . "</td>";
      echo "<td class='tab_bg_2'>";
      if ($fields["ignore_ocs_import"] == '') {
         echo "&nbsp;";
      } else {
         echo Dropdown::getYesNo($fields["ignore_ocs_import"]);
      }
      echo "</td>";
      echo "<td class='tab_bg_2'>" .
            ((isset ($fields["is_helpdesk_visible"]) && $fields["is_helpdesk_visible"] != '').".
             " ? Dropdown::getYesNo($fields["is_helpdesk_visible"]) : Dropdown::getYesNo(0)) . "</td>";
   }

   function getCriterias() {
      global $LANG;
      $criterias = array();
      $criterias['name']['field'] = 'name';
      $criterias['name']['name']  = $LANG['help'][31];
      $criterias['name']['table'] = 'glpi_softwares';

      $criterias['manufacturer']['field'] = 'name';
      $criterias['manufacturer']['name']  = $LANG['common'][5];
      $criterias['manufacturer']['table'] = 'glpi_manufacturers';
      return $criterias;
   }

   function getActions() {
      global $LANG;
      $actions = array();
      $actions['name']['name']          = $LANG['help'][31];
      $actions['name']['force_actions'] = array('assign','regex_result');

      $actions['_ignore_ocs_import']['name'] = $LANG['ocsconfig'][6];
      $actions['_ignore_ocs_import']['type'] = 'yesonly';

      $actions['version']['name']          = $LANG['rulesengine'][78];
      $actions['version']['force_actions'] = array('assign','regex_result','append_regex_result');

      $actions['manufacturer']['name']  = $LANG['common'][5];
      $actions['manufacturer']['table'] = 'glpi_manufacturers';
      $actions['manufacturer']['type']  = 'dropdown';

      $actions['is_helpdesk_visible']['name']  = $LANG['software'][46];
      $actions['is_helpdesk_visible']['table'] = 'glpi_softwares';
      $actions['is_helpdesk_visible']['type']  = 'yesno';
      return $actions;
   }

   function addSpecificParamsForPreview($input,$params) {
      if (isset($input["version"])) {
         $params["version"] = $input["version"];
      }
      return $params;
   }


   /**
    * Function used to display type specific criterias during rule's preview
    * @param $fields fields values
    */
   function showSpecificCriteriasForPreview($fields) {
      $this->getRuleWithCriteriasAndActions($this->fields['id'],0,1);
      //if there's a least one action with type == append_regex_result, then need to display
      //this field as a criteria
      foreach ($this->actions as $action) {
         if ($action->fields["action_type"] == "append_regex_result") {
            $value = (isset($fields[$action->fields['field']])?$fields[$action->fields['field']]:'');
            //Get actions for this type of rule
            $actions = $this->getActions();
            
            //display the additionnal field
            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo $this->fields['match'];
            echo "</td>";
            echo "<td>".$actions[$action->fields['field']]['name']."</td><td>";
            echo "<input type='text' name='version' value='$value'></td></tr>";
         }
      }
   }
}

?>