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

/**
 * Specific rule collection for dictionnary : got a function initialize rule's caching system
**/
class RuleCachedCollection extends RuleCollection {

   // Specific ones
   /// Cache table used
   var $cache_table;
   /// Cache parameters
   var $cache_params;


   /**
    * Init a cache rule collection
    *
    * @param $cache_table           cache table used
    * @param $input_params    array input parameters to store (default "name" => "old_value")
    * @param $output_params   array output parameters to store (default "name" => "new_value")
    *
    * @return nothing
   **/
   function initCache($cache_table, $input_params=array("name" => "old_value"),
                      $output_params=array("name" => "new_value")) {

      $this->can_replay_rules             = true;
      $this->stop_on_first_match          = true;
      $this->cache_table                  = $cache_table;
      $this->cache_params["input_value"]  = $input_params;
      $this->cache_params["output_value"] = $output_params;
   }


   /**
    * Show the list of rules
    *
    * @param $target  where to go
    *
    * @return nothing
   **/
   function showAdditionalInformationsInForm($target) {
      global $CFG_GLPI;

      echo "<div class='spaced center'>";
      echo "<a class='vsubmit' href='#' onClick=\"var w = window.open('".$CFG_GLPI["root_doc"].
            "/front/popup.php?popup=show_cache&amp;sub_type=".$this->getRuleClassName()."' , ".
            "'glpipopup', 'height=400, width=1000, top=100, left=100, scrollbars=yes' );w.focus();\">".
            __('Cache information')."</a></div>";
   }


   /**
    * Process all the rules collection
    *
    * @param input            array the input data used to check criterias
    * @param output           array the initial ouput array used to be manipulate by actions
    * @param params           array parameters for all internal functions
    * @param force_no_cache         don't write rule's result into cache (for preview mode mainly)
    *                               (false by default)
    *
    * @return the output array updated by actions
   **/
   function processAllRules($input=array(), $output=array(), $params=array(),
                            $force_no_cache=false) {

      //If cache enabled : try to get value from the cache
      $new_values = $this->checkDataInCache($input);

      if ($new_values != Rule::RULE_NOT_IN_CACHE) {
         $output["_rule_process"] = true;
         return Toolbox::addslashes_deep(array_merge($output, $new_values));
      }
      $output = parent::processAllRules($input, $output, $params, $force_no_cache);

      if (!$force_no_cache
          && isset($output["_ruleid"])) {
         $this->insertDataInCache(Toolbox::addslashes_deep($input), $output);
         unset($output["_ruleid"]);
      }

      return $output;
   }


   /**
    * Show cache status by rules
   **/
   function showCacheStatusForRuleType() {
      global $DB, $CFG_GLPI;

      echo "<div class='center'>";
      echo "<table  class='tab_cadre_fixe'>";

      $query = "SELECT `name`,
                       `rules_id`,
                        COUNT(`rules_id`) AS cpt
                FROM `".$this->cache_table."`,
                     `glpi_rules`
                WHERE `".$this->cache_table."`.`rules_id` = `glpi_rules`.`id`
                GROUP BY `rules_id`
                ORDER BY `name`";
      $res_count = $DB->query($query);

      echo "<tr><th>".__('Cache information')."</th><th>".$this->getTitle()."</th></tr>\n";
      echo "<tr><td class='tab_bg_1'>".__('Rule name')."</td>";
      echo "<td class='tab_bg_1'>".__('Objects in cache')."</td></tr>\n";

      $total = 0;
      while ($datas = $DB->fetch_assoc($res_count)) {
         echo "<tr><td class='tab_bg_2'>";
         echo "<a href='".$CFG_GLPI['root_doc']."/front/popup.php?popup=show_cache&amp;sub_type=".
                $this->getRuleClassName()."&amp;rules_id=".$datas["rules_id"]."'>".$datas["name"].
              "</a></td>";
         echo "<td class='tab_bg_2 numeric'>".$datas["cpt"]."</td></tr>\n";
         $total += $datas["cpt"];
      }

      echo "<tr>\n";
      echo "<td class='tab_bg_2 b'>".sprintf(__('%1$s (%2$s)'), __('Total'),
                                             $DB->numrows($res_count))."</td>";
      echo "<td class='tab_bg_2 b numeric'>".$total."</td>";
      echo "</tr></table></div>\n";
   }


   /**
    * Check if a data is in cache
    *
    * @param input data array to search
    *
    * @return boolean : is in cache ?
   **/
   function checkDataInCache($input) {
      global $DB;

      $where = "";
      $first = true;
      $input = Toolbox::addslashes_deep($input);
      
      foreach ($this->cache_params["input_value"] as $param => $value) {
         if (isset($input[$param])) {
            $where .= (!$first?" AND ":"")." `".$value."` = '".$input[$param]."'";
            $first  = false;
         }
      }
      $sql = "SELECT *
              FROM `".$this->cache_table."`
              WHERE ".$where;

      if ($res_check = $DB->query($sql)) {
         $output_values = array();

         if ($DB->numrows($res_check) > 0) {
            $data = $DB->fetch_assoc($res_check);

            foreach ($this->cache_params["output_value"] as $param => $param_value) {
               if (isset($data[$param_value])) {
                  $output_values[$param] = $data[$param_value];
               }
            }
            return $output_values;
         }
      }

      return Rule::RULE_NOT_IN_CACHE;
   }


   /**
    * Insert data in cache
    *
    * @param input   input data array
    * @param $output output data array
   **/
   function insertDataInCache($input, $output) {
      global $DB;

      $old_values = "";
      $into_old   = "";

      foreach ($this->cache_params["input_value"] as $param => $value) {
         $into_old   .= "`".$value."`, ";
         // Input are slashes protected...
         $old_values .= "'".$input[$param]."', ";
      }

      $into_new   = "";
      $new_values = "";

      foreach ($this->cache_params["output_value"] as $param => $value) {
         if (!isset($output[$param])) {
            $output[$param] = "";
         }
         $into_new   .= ", `".$value."`";
         // Output are not slashes protected...
         $new_values .= " ,'".$output[$param]."'";
      }

      $sql = "INSERT INTO `".$this->cache_table."`
                     (".$into_old."`rules_id`".$into_new.")
              VALUES (".$old_values.$output["_ruleid"].$new_values.")";
      $DB->query($sql);
   }


}
?>
