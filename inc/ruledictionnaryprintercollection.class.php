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
      $this->initCache("glpi_rulecacheprinters", 
                       array("name"               => "old_value",
                             "manufacturer"       => "manufacturer"),
                       array("name"               => "new_value",
                             "manufacturer"       => "new_manufacturer",
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
                     `glpi_printers`.`manufacturers_id` AS manufacturers_id,
                     `glpi_printers`.`comment` AS comment
              FROM `glpi_printers`
              LEFT JOIN `glpi_manufacturers`
                  ON (`glpi_manufacturers`.`id` = `glpi_printers`.`manufacturers_id`) ";

      // Do not replay on trash and templates
      $sql .= "WHERE `glpi_printers`.`is_deleted` = '0'
                     AND `glpi_printers`.`is_template` = '0' ";

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

         //Replay printer dictionnary rules
         $input    = addslashes_deep($input);
         $res_rule = $this->processAllRules($input, array(), array());
         $res_rule = addslashes_deep($res_rule);

         foreach (array('manufacturer', 'is_global', 'name') as $attr) {
            if (isset($res_rule[$attr]) && $res_rule[$attr] == '') {
               unset($res_rule[$attr]);
            }
         }
         
         //If the software's name or version has changed
         if (self::somethingHasChanged($res_rule, $input)) {

            $IDs = array();
            //Find all the printers in the database with the same name and manufacturer
            $sql = "SELECT `id`
                    FROM `glpi_printers`
                    WHERE `name` = '" . $input["name"] . "'
                          AND `manufacturers_id` = '" . $input["manufacturers_id"] . "'";
            $res_printer = $DB->query($sql);

            if ($DB->numrows($res_printer) > 0) {
               //Store all the software's IDs in an array
               while ($result = $DB->fetch_array($res_printer)) {
                  $IDs[] = $result["id"];
               }
               //Replay dictionnary on all the softwares
               $this->replayDirectionnaryOnPrintersByID($IDs, $res_rule);
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


   static function somethingHasChanged($res_rule, $input) {
      if ((isset ($res_rule["name"]) && $res_rule["name"] != $input["name"])
          || (isset($res_rule["manufacturer"]))
            && $res_rule["manufacturer"] != ''
               || isset($res_rule['is_global'])
                  && $res_rule['is_global'] != '') {
            return true;
      }
      return false;
   }

   /**
    * Replay dictionnary on several printers
    *
    * @param $IDs array of printers IDs to replay
    * @param $res_rule array of rule results
    *
    * @return Query result handler
   **/
   function replayDirectionnaryOnPrintersByID($IDs, $res_rule = array()) {
      global $DB;

      $new_printers  = array();
      $delete_ids = array();

      foreach ($IDs as $ID) {
         $sql = "SELECT `gp`.`id`,
                        `gp`.`name` AS name,
                        `gp`.`entities_id` AS entities_id,
                        `gp`.`is_global` AS is_global,
                        `gm`.`name` AS manufacturer
                 FROM `glpi_printers` AS gp
                 LEFT JOIN `glpi_manufacturers` AS gm
                    ON (`gp`.`manufacturers_id` = `gm`.`id`)
                 WHERE `gp`.`is_template` = '0'
                    AND `gp`.`id` = '$ID'";

         $res_printer = $DB->query($sql);
         if ($DB->numrows($res_printer)) {
            $printer = $DB->fetch_array($res_printer);
            //For each printer
            $this->replayDictionnaryOnOnePrinter($new_printers, $res_rule, $printer, $delete_ids);
         }
      }
      //Delete printer if needed
      $this->putOldPrintersInTrash($delete_ids);
   }
   
   function putOldPrintersInTrash($IDS = array()) {
      $printer = new Printer;
      foreach ($IDS as $id) {
         $printer->delete(array('id' => $id));
      }
   }
   
   /**
    * Replay dictionnary on one printer
    *
    * @param $new_softs array containing new printers already computed
    * @param $res_rule array of rule results
    * @param $ID ID of the software
    * @param $entity working entity ID
    * @param $name printer name
    * @param $manufacturer manufacturer ID
    * @param $printers_ids array containing replay printer need to be trashed
   **/
   function replayDictionnaryOnOnePrinter(& $new_printers, $res_rule, $params = array(), 
                                          & $printers_ids) {
      global $DB;

      $p['id']           = 0;
      $p['name']         = '';
      $p['manufacturer'] = '';
      $p['is_global']    = '';
      $p['entity']       = 0;
      foreach ($params as $key => $value) {
         $p[$key] = $value;
      }
      
      $input["name"]         = $p['name'];
      $input["manufacturer"] = $p['manufacturer'];
      $input                 = addslashes_deep($input);

      if (empty ($res_rule)) {
         $res_rule = $this->processAllRules($input, array(), array());
         $res_rule = addslashes_deep($res_rule);
      }

      $printer = new Printer();

      //Printer's name has changed
      if (isset ($res_rule["name"])
            && $res_rule["name"] != $p['name']) {

         $manufacturer = "";
         if (isset ($res_rule["manufacturer"])) {
            $manufacturer = Dropdown::getDropdownName("glpi_manufacturers",
                                                      $res_rule["manufacturer"]);
                                                      
         }

         //New printer not already present in this entity
         if (!isset ($new_printers[$p['entity']][$res_rule["name"]])) {
            // create new printer or restore it from trash
            $new_printer_id = $printer->addOrRestoreFromTrash($res_rule["name"], $manufacturer,
                                                              $p['entity']);
            $new_printers[$p['entity']][$res_rule["name"]] = $new_printer_id;
         } else {
            $new_printer_id = $new_printers[$p['entity']][$res_rule["name"]];
         }
         
         // Move direct connections
         $this->moveDirectConnections($p['id'], $new_printer_id);

      } else {
         $new_printer_id  = $p['id'];
         $res_rule["id"]  = $p['id'];

         if (isset($res_rule["manufacturer"])) {
            if ($res_rule["manufacturer"] != '') {
               $res_rule["manufacturers_id"] = $res_rule["manufacturer"];
            }
            unset($res_rule["manufacturer"]);
         }
         $printer->update($res_rule);
      }

      // Add to printer to deleted list
      if ($new_printer_id != $p['id']) {
         $printers_ids[] = $p['id'];
      }

   }

   /**
    * Move direct connections from old printer to the new one
    * @params $ID the old printer's id
    * @params $new_printers_id the new printer's id
    * 
    * @return nothing
    */
   function moveDirectConnections($ID, $new_printers_id) {
      global $DB;
      $computeritem = new Computer_Item();
      //For each direct connection of this printer
      foreach (getAllDatasFromTable("glpi_computers_items", 
                                    "`itemtype` = 'Printer' AND `items_id` = '$ID'") as $connection) {
         
         //Direct connection exists in the target printer ?
         if (!countElementsInTable("glpi_computers_items", 
                                   "`itemtype` = 'Printer' 
                                      AND `items_id` = '$new_printers_id' 
                                         AND `computers_id`='".$connection["computers_id"]."'")) {
            //Direct connection doesn't exists in the target printer : move it
            $computeritem->update(array ('id'       => $connection['id'], 
                                         'items_id' => $new_printers_id));
         } else {
            //Direct connection already exists in the target printer : delete it
            $computeritem->delete($connection);
         }
      }
   }
}

?>