<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Alert class
**/
class Alert extends CommonDBTM {

   // ALERTS TYPE
   const THRESHOLD   = 1;
   const END         = 2;
   const NOTICE      = 3;
   const NOTCLOSED   = 4;
   const ACTION      = 5;
   const PERIODICITY = 6;

   function prepareInputForAdd($input) {

      if (!isset($input['date']) || empty($input['date'])) {
         $input['date'] = date("Y-m-d H:i:s");
      }
      return $input;
   }


   /**
    * Clear all alerts of an alert type for an item
    *
    * @param string  $itemtype   ID of the type to clear
    * @param string  $ID         ID of the item to clear
    * @param integer $alert_type ID of the alert type to clear
    *
    * @return boolean
    */
   function clear($itemtype, $ID, $alert_type) {

      return $this->deleteByCriteria(['itemtype' => $itemtype, 'items_id' => $ID, 'type' => $alert_type], 1);
   }


   /**
    * Clear all alerts  for an item
    *
    * @since 0.84
    *
    * @param string  $itemtype ID of the type to clear
    * @param integer $ID       ID of the item to clear
    *
    * @return boolean
    */
   function cleanDBonItemDelete($itemtype, $ID) {

      return $this->deleteByCriteria(['itemtype' => $itemtype, 'items_id' => $ID], 1);
   }

   static function dropdown($options = []) {

      $p = [
         'name'           => 'alert',
         'value'          => 0,
         'display'        => true,
         'inherit_parent' => false,
      ];

      if (count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $times = [];

      if ($p['inherit_parent']) {
         $times[Entity::CONFIG_PARENT] = __('Inheritance of the parent entity');
      }

      $times[Entity::CONFIG_NEVER]  = __('Never');
      $times[DAY_TIMESTAMP]         = __('Each day');
      $times[WEEK_TIMESTAMP]        = __('Each week');
      $times[MONTH_TIMESTAMP]       = __('Each month');

      return Dropdown::showFromArray($p['name'], $times, $p);
   }


   /**
    * Builds a Yes/No dropdown
    *
    * @param array $options Display options
    *
    * @return void|string (see $options['display'])
    */
   static function dropdownYesNo($options = []) {

      $p = [
         'name'           => 'alert',
         'value'          => 0,
         'display'        => true,
         'inherit_parent' => false,
      ];

      if (count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $times = [];

      if ($p['inherit_parent']) {
         $times[Entity::CONFIG_PARENT] = __('Inheritance of the parent entity');
      }

      $times[0] = __('No');
      $times[1] = __('Yes');

      return Dropdown::showFromArray($p['name'], $times, $p);
   }


   /**
    * ?
    *
    * @param string $name    Dropdown name
    * @param string $value   Dropdown selected value
    * @param array  $options Display options
    *
    * @return void|string (see $options['display'])
    */
   static function dropdownIntegerNever($name, $value, $options = []) {

      $p = [
         'min'     => 1,
         'max'     => 100,
         'step'    => 1,
         'toadd'   => [],
         'display' => true,
      ];

      if (isset($options['inherit_parent']) && $options['inherit_parent']) {
         $p['toadd'][-2] = __('Inheritance of the parent entity');
      }

      $never_string = __('Never');
      if (isset($options['never_string']) && $options['never_string']) {
         $never_string = $options['never_string'];
      }
      if (isset($options['never_value']) && $options['never_value']) {
         $p['toadd'][$options['never_value']] = $never_string;
      } else {
         $p['toadd'][0] = $never_string;
      }
      $p['value'] = $value;

      foreach ($options as $key=>$val) {
         $p[$key] = $val;
      }

      return Dropdown::showNumber($name, $p);
   }


   /**
    * Does alert exists
    *
    * @since 9.5.0 Made all params required. Dropped invalid defaults.
    * @param string  $itemtype The item type
    * @param integer $items_id The item's ID
    * @param integer $type     The type of alert (see constants in {@link \Alert} class)
    *
    * @return integer|boolean
    */
   static function alertExists($itemtype, $items_id, $type) {
      global $DB;

      if ($items_id <= 0 || $type <= 0) {
         return false;
      }
      $iter = $DB->request(self::getTable(), ['itemtype' => $itemtype, 'items_id' => $items_id, 'type' => $type]);
      if ($row = $iter->next()) {
         return $row['id'];
      }
      return false;
   }


   /**
    * Get date of alert
    *
    * @since 0.84
    * @since 9.5.0 Made all params required. Dropped invalid defaults.
    *
    * @param string  $itemtype The item type
    * @param integer $items_id The item's ID
    * @param integer $type     The type of alert (see constants in {@link \Alert} class)
    *
    * @return mixed|boolean
    */
   static function getAlertDate($itemtype, $items_id, $type) {
      global $DB;

      if ($items_id <= 0 || $type <= 0) {
         return false;
      }
      $iter = $DB->request(self::getTable(), ['itemtype' => $itemtype, 'items_id' => $items_id, 'type' => $type]);
      if ($row = $iter->next()) {
         return $row['date'];
      }
      return false;
   }


   /**
    * Display last alert
    *
    * @param string  $itemtype The item type
    * @param integer $items_id The item's ID
    *
    * @return void
    */
   static function displayLastAlert($itemtype, $items_id) {
      global $DB;

      if ($items_id) {
         $iter = $DB->request(self::getTable(), ['FIELDS'   => 'date',
                                                 'ORDER'    => 'date DESC',
                                                 'LIMIT'    => 1,
                                                 'itemtype' => $itemtype,
                                                 'items_id' => $items_id]);
         if ($row = $iter->next()) {
            //TRANS: %s is the date
            echo sprintf(__('Alert sent on %s'), Html::convDateTime($row['date']));
         }
      }
   }

}
