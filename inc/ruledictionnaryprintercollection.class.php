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

class RuleDictionnaryPrinterCollection extends RuleCachedCollection {
   // From RuleCollection

   public $stop_on_first_match = true;
   public $right               = 'rule_dictionnary_printer';
   public $menu_type           = 'dictionnary';
   public $menu_option         = 'printer';


   /**
    * Constructor
   **/
   function __construct() {

      //Init cache system values
      $this->initCache("glpi_rulecacheprinters", array("name"         => "old_value",
                                                       "manufacturer" => "manufacturer"),
                       array("name"               => "new_value",
                             "manufacturers_id"   => "new_manufacturer",
                             "_ignore_ocs_import" => "ignore_ocs_import",
                             "is_global"          => "is_global"));
   }


   function getTitle() {
      global $LANG;

      return $LANG['rulesengine'][39];
   }


   function cleanTestOutputCriterias($output) {

      //If output array contains keys begining with _ : drop it
      foreach ($output as $criteria => $value) {
         if ($criteria[0] == '_' && $criteria != '_ignore_ocs_import') {
            unset ($output[$criteria]);
         }
      }
      return $output;
   }


   function replayRulesOnExistingDB($offset=0, $maxtime=0, $items=array(),
                                    $params=array()) {
      global $DB;

      if (isCommandLine()) {
         echo "replayRulesOnExistingDB started : " . date("r") . "\n";
      }
      $nb = 0;
      $i  = $offset;

      //Select all the differents software
      $sql = "SELECT DISTINCT `glpi_printers`.`name`,
                     `glpi_manufacturers`.`name` AS manufacturer,
                     `glpi_printers`.`manufacturers_id` AS manufacturers_id
              FROM `glpi_printers`
              LEFT JOIN `glpi_manufacturers`
                  ON (`glpi_manufacturers`.`id` = `glpi_printers`.`manufacturers_id`)";

      // Do not replay on trash and templates
      $sql .= "WHERE `glpi_printers`.`is_deleted` = '0'
                     AND `glpi_printers`.`is_template` = '0' ";

      if (isset ($params['manufacturer']) && $params['manufacturer'] > 0) {
         $sql .= " AND `manufacturers_id` = '" . $params['manufacturer'] . "'";
      }

      if ($offset) {
         $sql .= " LIMIT " . intval($offset) . ",999999999";
      }

      $res  = $DB->query($sql);
      $nb   = $DB->numrows($res) + $offset;
      $step = ($nb > 1000 ? 50 : ($nb > 20 ? floor($DB->numrows($res) / 20) : 1));

      while ($input = $DB->fetch_array($res)) {
         if (!($i % $step)) {
            if (isCommandLine()) {
               echo date("H:i:s") . " replayRulesOnExistingDB : $i/$nb (" .
                    round(memory_get_usage() / (1024 * 1024), 2) . " Mo)\n";
            } else {
               changeProgressBarPosition($i, $nb, "$i / $nb");
            }
         }

         //If manufacturer is set, then first run the manufacturer's dictionnary
         if (isset ($input["manufacturer"])) {
            $input["manufacturer"] = Manufacturer::processName($input["manufacturer"]);
         }

         //Replay software dictionnary rules
         $input    = addslashes_deep($input);
         $res_rule = $this->processAllRules($input, array(), array());
         $res_rule = addslashes_deep($res_rule);

         //If the software's name or version has changed
         if (self::somethingHasChanged($res_rule,$input)) {
            $IDs = array();
            //Find all the softwares in the database with the same name and manufacturer
            $sql = "SELECT `id`
                    FROM `glpi_printers`
                    WHERE `name` = '" . $input["name"] . "'
                          AND `manufacturers_id` = '" . $input["manufacturers_id"] . "'";
            $res_soft = $DB->query($sql);

            if ($DB->numrows($res_soft) > 0) {
               //Store all the software's IDs in an array
               while ($result = $DB->fetch_array($res_soft)) {
                  $IDs[] = $result["id"];
               }
               //Replay dictionnary on all the softwares
               $this->replayDictionnaryOnSoftwaresByID($IDs, $res_rule);
            }
         }
         $i++;

         if ($maxtime) {
            $crt = explode(" ", microtime());
            if ($crt[0] + $crt[1] > $maxtime) {
               break;
            }
         }
      }

      if (isCommandLine()) {
         echo "replayRulesOnExistingDB : $i/$nb               \n";
      } else {
         changeProgressBarPosition($i, $nb, "$i / $nb");
      }

      if (isCommandLine()) {
         echo "replayRulesOnExistingDB ended : " . date("r") . "\n";
      }

      return ($i == $nb ? -1 : $i);
   }


   static function somethingHasChanged($res_rule,$input) {

      if ((isset ($res_rule["name"]) && $res_rule["name"] != $input["name"])
          || (isset($res_rule["manufacturer"]))
          && $res_rule["manufacturer"] != ''
          || isset($res_rule['is_global'])
          && $res_rule['is_global'] != '') {
            return true;
      }
      return false;
   }

}

?>