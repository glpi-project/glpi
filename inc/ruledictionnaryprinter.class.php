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

      return $LANG['rulesengine'][39];
   }


   function maxActionsCount() {
      return 4;
   }


   function showCacheRuleHeader() {
      global $LANG;

      echo "<tr><th colspan='6'>" . $LANG['rulesengine'][100] . "&nbsp;: " . $this->fields["name"];
      echo "</th></tr>";

      echo "<tr><td class='tab_bg_1 b'>" . $LANG['rulesengine'][104] . "</td>";
      echo "<td class='tab_bg_1 b'>" . $LANG['common'][5] . " " . $LANG['rulesengine'][108]."</td>";
      echo "<td class='tab_bg_1 b'>" . $LANG['rulesengine'][105] . "</td>";
      echo "<td class='tab_bg_1 b'>" . $LANG['peripherals'][33] . "</td>";
      echo "<td class='tab_bg_1 b'>" . $LANG['common'][5] . " " . $LANG['rulesengine'][105]."</td>";
      echo "<td class='tab_bg_1 b'>" . $LANG['rulesengine'][132] . "</td></tr>";
   }


   function showCacheRuleDetail($fields) {
      global $LANG;

      echo "<td class='tab_bg_2'>" . $fields["old_value"] . "</td>";
      echo "<td class='tab_bg_2'>" . $fields["manufacturer"] . "</td>";
      echo "<td class='tab_bg_2'>". ($fields["new_value"] != '' ? $fields["new_value"]
                                                                : $LANG['rulesengine'][106])."</td>";
      echo "<td class='tab_bg_2'>".
             ($fields["is_global"] != '' ? Dropdown::getGlobalSwitch($fields["is_global"])
                                         : $LANG['rulesengine'][106]) . "</td>";
      echo "<td class='tab_bg_2'>" .
            ((isset ($fields["new_manufacturer"]) && $fields["new_manufacturer"] != '')
             ? Dropdown::getDropdownName("glpi_manufacturers", $fields["new_manufacturer"])
             : $LANG['rulesengine'][106]) . "</td>";
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
      $criterias['name']['name']  = $LANG['common'][16];
      $criterias['name']['table'] = 'glpi_printers';

      $criterias['manufacturer']['field'] = 'name';
      $criterias['manufacturer']['name']  = $LANG['common'][5];
      $criterias['manufacturer']['table'] = '';

      $criterias['PORT']['field'] = 'PORT';
      $criterias['PORT']['name']  = $LANG['setup'][175];
      $criterias['PORT']['table'] = '';

      $criterias['DRIVER']['field'] = 'DRIVER';
      $criterias['DRIVER']['name']  = $LANG['printers'][32];
      $criterias['DRIVER']['table'] = '';

      $criterias['model']['field'] = 'model';
      $criterias['model']['name']  = $LANG['common'][22];
      $criterias['model']['table'] = '';

      $criterias['type']['field'] = 'type';
      $criterias['type']['name']  = $LANG['common'][17];
      $criterias['type']['table'] = '';

      $criterias['comment']['field'] = 'comment';
      $criterias['comment']['name']  = $LANG['common'][25];
      $criterias['comment']['table'] = '';

      return $criterias;
   }


   function getActions() {
      global $LANG;

      $actions = array();
      $actions['name']['name']          = $LANG['common'][16];
      $actions['name']['force_actions'] = array('assign', 'regex_result');

      $actions['_ignore_ocs_import']['name'] = $LANG['rulesengine'][132];
      $actions['_ignore_ocs_import']['type'] = 'yesonly';

      $actions['manufacturers_id']['name']  = $LANG['common'][5];
      $actions['manufacturers_id']['table'] = 'glpi_manufacturers';
      $actions['manufacturers_id']['type']  = 'dropdown';

      $actions['is_global']['name']  = $LANG['peripherals'][33];
      $actions['is_global']['type']  = 'dropdown_management';

      return $actions;
   }

}

?>