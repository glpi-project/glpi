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

class RuleDictionnaryDropdownCollection extends RuleCachedCollection {

   // From RuleCollection
//   public $rule_class_name = 'RuleDictionnaryDropdown';
   public $right     = 'rule_dictionnary_dropdown';
   public $menu_type = 'dictionnary';

   // Specific ones
   /// dropdown table
   var $item_table = "";

   function replayRulesOnExistingDB($offset=0, $maxtime=0, $items=array(), $params=array()) {
      global $DB,$LANG;

      // Model check : need to check using manufacturer extra data so specific function
      if (strpos($this->item_table,'models')) {
         return $this->replayRulesOnExistingDBForModel($offset, $maxtime);
      }

      if (isCommandLine()) {
         echo "replayRulesOnExistingDB started : " . date("r") . "\n";
      }

      // Get All items
      $Sql = "SELECT *
              FROM `".$this->item_table."`";
      if ($offset) {
         $Sql .= " LIMIT ".intval($offset).",999999999";
      }
      $result = $DB->query($Sql);

      $nb = $DB->numrows($result)+$offset;
      $i  = $offset;
      if ($result && $nb>$offset) {
         // Step to refresh progressbar
         $step = ($nb>20 ? floor($nb/20) : 1);
         $send = array ();
         $send["tablename"] = $this->item_table;

         while ($data = $DB->fetch_array($result)) {
            if (!($i % $step)) {

               if (isCommandLine()) {
                  echo "replayRulesOnExistingDB : $i/$nb\r";
               } else {
                  changeProgressBarPosition($i,$nb,"$i / $nb");
               }
            }

            //Replay Type dictionnary
            $ID=Dropdown::importExternal(getItemTypeForTable($this->item_table),
                                         addslashes($data["name"]), -1, array(),
                                         addslashes($data["comment"]));
            if ($data['id'] != $ID) {
               $tomove[$data['id']] = $ID;
               $type = GetItemTypeForTable($this->item_table);

               if (class_exists($type)) {
                  $dropdown = new $type();
                  $dropdown->delete(array('id'          => $data['id'],
                                          '_replace_by' => $ID));
               }
            }
            $i++;

            if ($maxtime) {
               $crt = explode(" ", microtime());
               if ($crt[0]+$crt[1] > $maxtime) {
                  break;
               }
            }
         } // end while
      }

      if (isCommandLine()) {
         echo "replayRulesOnExistingDB ended : " . date("r") . "\n";
      } else {
         changeProgressBarPosition($i,$nb,"$i / $nb");
      }
      return ($i==$nb ? -1 : $i);
   }


   /**
    * Replay collection rules on an existing DB for model dropdowns
    *
    * @param $offset offset used to begin
    * @param $maxtime maximum time of process (reload at the end)
    *
    * @return -1 on completion else current offset
   **/
   function replayRulesOnExistingDBForModel($offset=0, $maxtime=0) {
      global $DB, $LANG;

      if (isCommandLine()) {
         echo "replayRulesOnExistingDB started : " . date("r") . "\n";
      }

      // Model check : need to check using manufacturer extra data
      if (strpos($this->item_table,'models')===false) {
         echo "Error replaying rules";
         return false;
      }

      $model_table = getPlural(str_replace('models', '', $this->item_table));
      $model_field = getForeignKeyFieldForTable($this->item_table);

      // Need to give manufacturer from item table
      $Sql = "SELECT DISTINCT `glpi_manufacturers`.`id` AS idmanu,
                     `glpi_manufacturers`.`name` AS manufacturer,
                     `".$this->item_table."`.`id`,
                     `".$this->item_table."`.`name` AS name,
                     `".$this->item_table."`.`comment`
              FROM `".$this->item_table."`,
                   `$model_table`
              LEFT JOIN `glpi_manufacturers`
                  ON (`$model_table`.`manufacturers_id` = `glpi_manufacturers`.`id`)
              WHERE `$model_table`.`$model_field` = `".$this->item_table."`.`id`";

      if ($offset) {
         $Sql .= " LIMIT ".intval($offset).",999999999";
      }
      $result = $DB->query($Sql);

      $nb = $DB->numrows($result)+$offset;
      $i  = $offset;

      if ($result && $nb>$offset) {
         // Step to refresh progressbar
         $step    = ($nb>20 ? floor($nb/20) : 1);
         $tocheck = array();

         while ($data = $DB->fetch_array($result)) {
            if (!($i % $step)) {

               if (isCommandLine()) {
                  echo "replayRulesOnExistingDB : $i/$nb\r";
               } else {
                  changeProgressBarPosition($i, $nb, "$i / $nb");
               }
            }

            // Model case
            if (isset($data["manufacturer"])) {
               $data["manufacturer"] = Manufacturer::processName($data["manufacturer"]);
            }

            //Replay Type dictionnary
            $ID = Dropdown::importExternal(getItemTypeForTable($this->item_table),
                                           addslashes($data["name"]), -1, $data,
                                           addslashes($data["comment"]));

            if ($data['id'] != $ID) {
               $tocheck[$data["id"]][] = $ID;
               $sql = "UPDATE `$model_table`
                       SET `$model_field` = '$ID'
                       WHERE `$model_field` = '".$data['id']."'";

               if (empty($data['idmanu'])) {
                  $sql .= " AND (`manufacturers_id` IS NULL
                                 OR `manufacturers_id` = '0')";
               } else {
                  $sql .= " AND `manufacturers_id` = '".$data['idmanu']."'";
               }
               $DB->query($sql);
            }

            $i++;
            if ($maxtime) {
               $crt = explode(" ",microtime());
               if ($crt[0]+$crt[1] > $maxtime) {
                  break;
               }
            }
         }

         foreach ($tocheck AS $ID => $tab) {
            $sql = "SELECT COUNT(*)
                    FROM `$model_table`
                    WHERE `$model_field` = '$ID'";
            $result = $DB->query($sql);
            $deletecartmodel = false;

            // No item left : delete old item
            if ($result && $DB->result($result,0,0)==0) {
               $Sql = "DELETE
                       FROM `".$this->item_table."`
                       WHERE `id` = '$ID'";
               $resdel = $DB->query($Sql);
               $deletecartmodel = true;
            }

            // Manage cartridge assoc Update items
            if ($this->getRuleClassName()=='RuleDictionnaryPrinterModel') {
               $sql = "SELECT *
                       FROM `glpi_cartridgeitems_printermodels`
                       WHERE `printermodels_id` = '$ID'";

               if ($result=$DB->query($sql)) {
                  if ($DB->numrows($result)) {
                     // Get compatible cartridge type
                     $carttype = array();
                     while ($data=$DB->fetch_array($result)) {
                        $carttype[] = $data['cartridgeitems_id'];
                     }
                     // Delete cartrodges_assoc
                     if ($deletecartmodel) {
                        $sql = "DELETE
                                FROM `glpi_cartridgeitems_printermodels`
                                WHERE `printermodels_id` = 'id'";
                        $DB->query($sql);
                     }
                     // Add new assoc
                     if (!class_exists('CartridgeItem')) {
                        include_once (GLPI_ROOT . "/inc/cartridgeitem.function.php");
                     }
                     $ct = new CartridgeItem();
                     foreach ($carttype as $cartID) {
                        foreach ($tab as $model) {
                           $ct->addCompatibleType($cartID, $model);
                        }
                     }
                  }
               }
            }
         } // each tocheck
      }

      if (isCommandLine()) {
         echo "replayRulesOnExistingDB ended : " . date("r") . "\n";
      } else {
         changeProgressBarPosition($i, $nb, "$i / $nb");
      }
      return ($i==$nb ? -1 : $i);
   }

}

?>
