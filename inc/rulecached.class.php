<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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

/** @file
* @brief 
*/
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Rule cached class
class RuleCached extends Rule {

   /**
    * get the cache table name for this rule type
    *
    * @return string table name
   **/
   function getCacheTable() {

      $rulecollection = RuleCollection::getClassByType(get_class($this));
      return $rulecollection->cache_table;
   }


   /**
   * Delete cache for a rule
   *
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


   /**
    * @see CommonDBTM::post_updateItem()
   **/
   function post_updateItem($history=1) {

      // Clean cache in all case (match, active, ranking, add/delete criteria/action)
      $this->deleteCacheByRuleId($this->input["id"]);
   }


   /**
    * Show cache statis for a current rule
    *
    * @param $target where to go
   **/
   function showCacheStatusByRule($target) {
      global $DB;

      echo "<div class='center'>";
      echo "<table  class='tab_cadre_fixe'>";
      $rulecollection = RuleCollection::getClassByType($this->getType());

      $query = "SELECT *
                FROM `".$rulecollection->cache_table."`
                WHERE `".$rulecollection->cache_table."`.`rules_id` = '".$this->fields["id"]."'";

      $res_count = $DB->query($query);
      $this->showCacheRuleHeader();

      while ($datas = $DB->fetch_assoc($res_count)) {
         echo "<tr>";
         $this->showCacheRuleDetail($datas);
         echo "</tr>\n";
      }

      echo "</table><br><br>\n";
      echo "<a href='$target'>".__('Cache information')."</a></div>";
   }


   /**
    * Display Header for cache display
   **/
   function showCacheRuleHeader() {

      echo "<tr><th>".__('Cache information')."</th><th>".$this->fields["name"];
      echo "</th></tr>\n";
      echo "<tr><td class='tab_bg_1'>".__('Original value')."</td>";
      echo "<td class='tab_bg_1'>".__('Modified value')."</td></tr>";
   }


   /**
    * Display a cache item
    *
    * @param $fields data array
   **/
   function showCacheRuleDetail($fields) {

      echo "<td class='tab_bg_2'>".$fields["old_value"]."</td>";
      echo "<td class='tab_bg_2'>".
            (($fields["new_value"] != '') ?$fields["new_value"]:__('Unchanged'))."</td>";
   }

}
?>
