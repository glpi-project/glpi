<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

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
 
/**
 * This class manages locks
 * Lock management is available for objects and link between objects. It relies on the use of
 * a is_dynamic field, to incidate if item supports lock, and is_deleted field to incidate if the
 * item or link is locked
 * By setting is_deleted to 0 again, the item is unlock
 *
 * Note : GLPI's core supports locks for objects. It's up to the external inventory tool to manage
 * locks for fields
 */
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


class Lock {

   /**
    *
    * Display form to unlock fields and links
    * @since 0.84
    * @param CommonDBTM $item the source item
    */
   static function showForItem(CommonDBTM $item) {
      global $DB;

      $ID       = $item->getID();
      $itemtype = $item->getType();
      $header   = false;
      
      //If user doesn't have write right on the item, lock form must not be displayed
      if (!$item->canCreate()) {
         return false;
      }
      
      echo "<div width='50%'>";
      echo "<form method='post' id='lock_form'
             name='lock_form' action=\"".Toolbox::getItemTypeFormURL(__CLASS__)."\">";
      echo "<input type='hidden' name='id' value='$ID'>\n";
      echo "<input type='hidden' name='itemtype' value='$itemtype'>\n";
      echo "<table class='tab_cadre_fixe'>";

      //Use a hook to allow external inventory tools to manage per field lock
      $results =  Plugin::doHookFunction('display_locked_fields', array('item' =>$item,
                                                                        'header' => $header));
      $header |= $results['header'];
      
      //Special locks for computers only
      if ($itemtype == 'Computer') {
         //Locks for items recorded in glpi_computers_items table
         $types = array('Monitor', 'Printer', 'Peripheral');
         foreach($types as $type) {
            $item   = new $type();
            $params = array('is_dynamic' => 1, 'is_deleted' => 1, 'computers_id' => $ID,
                            'itemtype' => $itemtype);
            $first  = true;
            $locale = "Locked ".strtolower($type);
            foreach ($DB->request('glpi_computers_items', $params, array('id', 'items_id')) as $line) {
               $item->getFromDB($line['items_id']);
               $header = true;
               if ($first) {
                  echo "<tr><th colspan='2'>"._n($locale, $locale.'s', 2, 'ocsinventoryng')."</th>".
                        "</tr>\n";
                  $first = false;
               }
                
               echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $item->getName() . "</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='Computer_Item[" . $line['id'] . "]'></td></tr>\n";
            }
             
         }
          
         $types = array('ComputerDisk' => 'disk', 'ComputerVirtualMachine' => 'Virtual machine');
         foreach($types as $type => $label) {
            $params = array('is_dynamic' => 1, 'is_deleted' => 1, 'computers_id' => $ID);
         
            $first  = true;
            $locale = "Locked ".$label;
            foreach ($DB->request(getTableForItemType($type), $params,
                                   array('id', 'name')) as $line) {
               $header = true;
               if ($first) {
                  echo "<tr><th colspan='2'>"._n($locale, $locale.'s', 2, 'ocsinventoryng')."</th>".
                        "</tr>\n";
                  $first = false;
               }
         
               echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $line['name'] . "</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='".$type."[" . $line['id'] . "]'></td></tr>\n";
            }
         }

         //Software versions
         $params = array('is_dynamic' => 1, 'is_deleted' => 1, 'computers_id' => $ID);
         $first  = true;
         $query = "SELECT `csv`.`id` as `id`, `sv`.`name` as `version`, `s`.`name` as `software`
                FROM `glpi_computers_softwareversions` AS csv
                   LEFT JOIN `glpi_softwareversions` AS sv
                      ON (`csv`.`softwareversions_id`=`sv`.`id`)
                   LEFT JOIN `glpi_softwares` AS s
                      ON (`sv`.`softwares_id`=`s`.`id`)
                 WHERE `csv`.`is_deleted`='1'
                   AND `csv`.`is_dynamic`='1'
                      AND `csv`.`computers_id`='$ID'";
         foreach ($DB->request($query) as $line) {
            $header = true;
            if ($first) {
               echo "<tr><th colspan='2'>"._n('Software', 'Softwares', 2, 'ocsinventoryng')."</th>".
                     "</tr>\n";
               $first = false;
            }
             
            echo "<tr class='tab_bg_1'><td class='right' width='50%'>" .
                  $line['software']." ".$line['version']. "</td>";
            echo "<td class='left' width='50%'>";
            echo "<input type='checkbox' name='Computer_SoftwareVersion[" . $line['id'] . "]'></td></tr>\n";
         }
         
         //Software licenses
         $params = array('is_dynamic' => 1, 'is_deleted' => 1, 'computers_id' => $ID);
         $first  = true;
         $query = "SELECT `csv`.`id` as `id`, `sv`.`name` as `version`, `s`.`name` as `software`
                FROM `glpi_computers_softwarelicenses` AS csv
                   LEFT JOIN `glpi_softwarelicenses` AS sv
                      ON (`csv`.`softwarelicenses_id`=`sv`.`id`)
                   LEFT JOIN `glpi_softwares` AS s
                      ON (`sv`.`softwares_id`=`s`.`id`)
                WHERE `csv`.`is_deleted`='1'
                   AND `csv`.`is_dynamic`='1'
                      AND `csv`.`computers_id`='$ID'";
         foreach ($DB->request($query) as $line) {
            $header = true;
            if ($first) {
               echo "<tr><th colspan='2'>"._n('License', 'Licenses', 2, 'ocsinventoryng')."</th>".
                     "</tr>\n";
               $first = false;
            }
         
            echo "<tr class='tab_bg_1'><td class='right' width='50%'>" .
                  $line['software']." ".$line['version']. "</td>";
            echo "<td class='left' width='50%'>";
            echo "<input type='checkbox' name='Computer_SoftwareLicense[" . $line['id'] . "]'></td></tr>\n";
         }
      }

          
      $params = array('is_dynamic' => 1, 'is_deleted' => 1, 'items_id' => $ID,
                       'itemtype' => $itemtype);
      $first  = true;
      $item = new NetworkPort();
      foreach ($DB->request('glpi_networkports', $params, array('id', 'items_id')) as $line) {
         $item->getFromDB($line['id']);
         $header = true;
         if ($first) {
            echo "<tr><th colspan='2'>"._n('Locked IP', 'Locked IP', 2, 'ocsinventoryng')."</th>".
                  "</tr>\n";
            $first = false;
         }
      
         echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $item->getName() . "</td>";
         echo "<td class='left' width='50%'>";
         echo "<input type='checkbox' name='NetworkPort[" . $line['id'] . "]'></td></tr>\n";
      }

      $types = Item_Devices::getDeviceTypes();
      $nb    = 0;
      foreach ($types as $old => $type) {
         $nb += countElementsInTable(getTableForItemType($type),
                                       "`items_id`='$ID'
                                         AND `itemtype`='$itemtype'
                                            AND `is_dynamic`='1'
                                               AND `is_deleted`='1'");
      }
      if ($nb) {
         $header = true;
         echo "<tr><th colspan='2'>"._n('Locked component', 'Locked components', 2,
               'ocsinventoryng')."</th></tr>\n";
         foreach ($types as $old => $type) {
            $associated_type  = str_replace('Item_', '', $type);
            $associated_table = getTableForItemType($associated_type);
            $fk               = getForeignKeyFieldForTable($associated_table);
            
            $query = "SELECT `i`.`id`, `t`.`designation` as `name`
                      FROM `".getTableForItemType($type)."` as i
                      LEFT JOIN `$associated_table` as t ON (`t`.`id`=`i`.`$fk`)
                      WHERE `itemtype`='$itemtype'
                         AND `items_id`='$ID'
                         AND `is_dynamic`='1'
                         AND `is_deleted`='1'";
            foreach ($DB->request($query) as $data) {
               echo "<tr class='tab_bg_1'><td class='right' width='50%'>";
               echo $associated_type::getTypeName()."&nbsp;: ".$data['name']."</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='".$itemtype."[" . $data['id'] . "]'></td></tr>\n";
            }
         }
      }
      if ($header) {
        echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
        Html::openArrowMassives('lock_form');
        Html::closeArrowMassives(array(array()));
        echo "</td></tr>";
      }
        
      if ($header) {
         echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
         echo "<input class='submit' type='submit' name='unlock' value='".
               _sx('button', 'Unlock', 'ocsinventoryng'). "'></td></tr>";
      } else {
         echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
         echo __('No locked field', 'ocsinventoryng')."</td></tr>";
      }
      echo "</table>";
      Html::closeForm();
      echo "</div>\n";
   }


   /**
    * @see inc/CommonGLPI::getTabNameForItem()
    *
    * @param $item               CommonGLPI object
    * @param$withtemplate        (default 0)
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->isDynamic() && $item->canCreate()) {
         return array('1' => _n('Lock', 'Locks', 2));
      }
      return '';
   }


   /**
    * @param $item            CommonGLPI object
    * @param $tabnum          (default 1)
    * @param $withtemplate    (default 0)
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->isDynamic()) {
         self::showForItem($item);
      }
      return true;
   }

   /**
    *
    * Unlock lockes items
    * @since 1.0
    * @param $itemtype itemtype of ids to locks
    * @param $items array of items to unlock
    */
   static function unlockItems($itemtype, $items) {
      global $DB;
      $item = new $itemtype();
      $ok = 0;
      $ko = 0;
      $condition = array();
      $table     = false;
      $field     = '';
      
      switch ($itemtype) {
         case 'Peripheral':
         case 'Monitor':
         case 'Printer':
         case 'Phone':
            $conditon = array('itemtype' => $itemtype, 'is_dynamic' => 1, 'is_deleted' => 1);
            $table    = 'glpi_computers_items';
            $field    = 'computers_id';
            break;
            
         case 'NetworkPort':
            $conditon = array('itemtype' => $itemtype, 'is_dynamic' => 1, 'is_deleted' => 1);
            $table    = 'glpi_networkports';
            $field    = 'items_id';
            break;
            
         case 'ComputerDisk':
            $conditon = array('is_dynamic' => 1, 'is_deleted' => 1);
            $table    = 'glpi_computerdisks';
            $field    = 'computers_id';
            break;
            
         case 'SoftwareVersion':
            $conditon = array('is_dynamic' => 1, 'is_deleted' => 1);
            $table    = 'glpi_compueters_softwareversions';
            $field    = 'computers_id';
            break;
      }
      
      foreach ($items as $id => $value) {
         if ($value == 1) {
            $condition[$field] = $id;
            foreach ($DB->request($table, $condition, array('id')) as $data) {
               if ($item->update(array('id' => $data['id'], 'is_deleted' => 0))) {
                  $ok++;
               } else {
                  $ko++;
               }
            }
         }
      }
      
      return array('ok' => $ok, 'ko' => $ko);
   }
   
   /**
    *
    * Get massive actions to unlock items
    * @since 0.84
    * @param unknown $itemtype source itemtype
    * @return an array of actions to be added (empty if no actions to add)
    */
   static function getUnlockMassiveActions($itemtype) {
      if (Session::haveRight('computer', 'w') && $itemtype == 'Computer') {
         return array("unlock_Monitor"      => __('Unlock monitors', 'ocsinventoryng'),
                        "unlock_Peripheral"   => __('Unlock peripherals', 'ocsinventoryng'),
                        "unlock_Printer"      => __('Unlock printers', 'ocsinventoryng'),
                        "unlock_Software"     => __('Unlock software', 'ocsinventoryng'),
                        "unlock_NetworkPort"  => __('Unlock IP', 'ocsinventoryng'),
                        "unlock_ComputerDisk" => __('Unclok volumes', 'ocsinventoryng'));
      } else {
         return array();
      }
   }
   
   /**
    *
    * Return itemtype associated with the unlock massive action
    * @since 0.84
    * @param action the selected massive action
    * @return the itemtype associated
    */
   static function getItemTypeForMassiveAction($action) {
      if (preg_match('/unlock_(.*)/', $action, $results)) {
         $itemtype = $results[1];
         if (class_exists($itemtype)) {
            return $itemtype;
         }
      }
      return false;
   }
}
?>