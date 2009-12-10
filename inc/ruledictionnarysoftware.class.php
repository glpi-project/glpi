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

/**
* Rule class store all informations about a GLPI rule :
*   - description
*   - criterias
*   - actions
*
**/
class RuleDictionnarySoftware extends RuleCached {

   // From Rule
   public $sub_type=RULE_DICTIONNARY_SOFTWARE;
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
         echo getYesNo($fields["ignore_ocs_import"]);
      }
      echo "</td>";
      echo "<td class='tab_bg_2'>" .
            ((isset ($fields["is_helpdesk_visible"]) && $fields["is_helpdesk_visible"] != '').".
             " ? getYesNo($fields["is_helpdesk_visible"]) : getYesNo(0)) . "</td>";
   }
}

?>