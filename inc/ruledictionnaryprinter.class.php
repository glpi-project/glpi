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

/**
* Rule class store all informations about a GLPI rule :
*   - description
*   - criterias
*   - actions
**/
class RuleDictionnaryPrinter extends RuleCached {

   // From Rule
   public $right    = 'rule_dictionnary_printer';
   public $can_sort = true;


   function getTitle() {
      global $LANG;

      return __('Dictionnary of printers');
   }


   function maxActionsCount() {
      return 4;
   }


   function showCacheRuleHeader() {
      global $LANG;

      echo "<tr><th colspan='3'>" . __('Cache informations') . "</th><th colspan='3'>" . $this->fields["name"];
      echo "</th></tr>";

      echo "<tr><td class='tab_bg_1 b'>" . __('Original value') . "</td>";
      echo "<td class='tab_bg_1 b'>" . __('Original manufacturer')."</td>";
      echo "<td class='tab_bg_1 b'>" . __('Modified value') . "</td>";
      echo "<td class='tab_bg_1 b'>" . __('Management type') . "</td>";
      echo "<td class='tab_bg_1 b'>" . __('New manufacturer')."</td>";
      echo "<td class='tab_bg_1 b'>" . __('To be unaware of import') . "</td></tr>";
   }


   function showCacheRuleDetail($fields) {
      global $LANG;

      echo "<td class='tab_bg_2'>" . $fields["old_value"] . "</td>";
      echo "<td class='tab_bg_2'>" . $fields["manufacturer"] . "</td>";
      echo "<td class='tab_bg_2'>". ($fields["new_value"] != '' ? $fields["new_value"]
                                                                : __('Unchanged'))."</td>";
      echo "<td class='tab_bg_2'>".
             ($fields["is_global"] != '' ? Dropdown::getGlobalSwitch($fields["is_global"])
                                         : __('Unchanged')) . "</td>";
      echo "<td class='tab_bg_2'>" .
            ((isset($fields["new_manufacturer"]) && $fields["new_manufacturer"] != '')
             ? Dropdown::getDropdownName("glpi_manufacturers", $fields["new_manufacturer"])
             : __('Unchanged')) . "</td>";
      echo "<td class='tab_bg_2'>";

      if ($fields["ignore_ocs_import"] == '') {
         echo "&nbsp;";
      } else {
         echo Dropdown::getYesNo($fields["ignore_ocs_import"]);
      }
      echo "</td>";
   }


   function getCriterias() {
      global $LANG;

      $criterias = array();
      $criterias['name']['field'] = 'name';
      $criterias['name']['name']  = __('Name');
      $criterias['name']['table'] = 'glpi_printers';

      $criterias['manufacturer']['field'] = 'name';
      $criterias['manufacturer']['name']  = __('Manufacturer');
      $criterias['manufacturer']['table'] = '';

      $criterias['comment']['field'] = 'comment';
      $criterias['comment']['name']  = __('Comments');
      $criterias['comment']['table'] = '';

      return $criterias;
   }


   function getActions() {
      global $LANG;

      $actions = array();
      $actions['name']['name']          = __('Name');
      $actions['name']['force_actions'] = array('assign', 'regex_result');

      $actions['_ignore_ocs_import']['name'] = __('To be unaware of import');
      $actions['_ignore_ocs_import']['type'] = 'yesonly';

      $actions['manufacturer']['name']  = __('Manufacturer');
      $actions['manufacturer']['table'] = 'glpi_manufacturers';
      $actions['manufacturer']['type']  = 'dropdown';

      $actions['is_global']['name']  = __('Management type');
      $actions['is_global']['type']  = 'dropdown_management';

      return $actions;
   }

}

?>