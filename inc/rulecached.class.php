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

/// Rule cached class
class RuleCached extends Rule {

   /**
    * get the cache table name for this rule type
    *
    * @return string table name
    */
   function getCacheTable() {
      $rulecollection = RuleCollection::getClassByType(get_class($this));
      return $rulecollection->cache_table;
   }


   /**
   * Delete cache for a rule
   * @param $ID rule ID
   **/
   function deleteCacheByRuleId($ID) {
      global $DB;

      $DB->query("DELETE
                  FROM `".$this->getCacheTable()."`
                  WHERE `rules_id` = '$ID'");
   }

   function cleanDBonPurge() {
      parent::cleanDBonPurge();

      $this->deleteCacheByRuleId($this->fields['id']);
   }

   function post_updateItem($history=1) {

      // Clean cache in all case (match, active, ranking, add/delete criteria/action)
      $this->deleteCacheByRuleId($this->input["id"]);
   }

   /**
   * Show cache statis for a current rule
   * @param $target where to go
   **/
   function showCacheStatusByRule($target) {
      global $DB,$LANG;

      echo "<div class='center'>";
      echo "<table  class='tab_cadre_fixe'>";
      $rulecollection = RuleCollection::getClassByType($this->getType());

      $query = "SELECT *
                FROM `".$rulecollection->cache_table."`, `glpi_rules`
                WHERE `".$rulecollection->cache_table."`.`rules_id` = `glpi_rules`.`id`
                      AND `".$rulecollection->cache_table."`.`rules_id` = '".$this->fields["id"]."'
                ORDER BY `name`";

      $res_count=$DB->query($query);
      $this->showCacheRuleHeader();

      while ($datas = $DB->fetch_array($res_count)) {
         echo "<tr>";
         $this->showCacheRuleDetail($datas);
         echo "</tr>\n";
      }

      echo "</table><br><br>\n";
      echo "<a href=\"$target\">".$LANG['buttons'][13]." (".$LANG['rulesengine'][100].")</a></div>";
   }

   /// Display Header for cache display
   function showCacheRuleHeader() {
      global $LANG;

      echo "<tr><th colspan='2'>".$LANG['rulesengine'][100]."&nbsp;: ".$this->fields["name"];
      echo "</th></tr>\n";
      echo "<tr><td class='tab_bg_1'>".$LANG['rulesengine'][104]."</td>";
      echo "<td class='tab_bg_1'>".$LANG['rulesengine'][105]."</td></tr>";
   }

   /**
    * Display a cache item
    * @param $fields data array
   **/
   function showCacheRuleDetail($fields) {
      global $LANG;

      echo "<td class='tab_bg_2'>".$fields["old_value"]."</td>";
      echo "<td class='tab_bg_2'>".($fields["new_value"]!=''
             ?$fields["new_value"]:$LANG['rulesengine'][106])."</td>";
   }

}

?>
