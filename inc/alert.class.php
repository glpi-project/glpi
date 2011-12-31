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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Alert class
 */
class Alert extends CommonDBTM {

   // ALERTS TYPE
   const THRESHOLD = 1;
   const END       = 2;
   const NOTICE    = 3;
   const NOTCLOSED = 4;

   function prepareInputForAdd($input) {

      if (!isset($input['date']) || empty($input['date'])) {
         $input['date'] = date("Y-m-d H:i:s");
      }
      return $input;
   }


   /**
    * Clear all alerts of an alert type for an item
    *
    *@param $itemtype ID of the type to clear
    *@param $ID ID of the item to clear
    *@param $alert_type ID of the alert type to clear
    *
    *@return nothing
    *
   **/
   function clear($itemtype, $ID, $alert_type) {
      global $DB;

      $query = "DELETE
                FROM `".$this->getTable()."`
                WHERE `itemtype` = '$itemtype'
                      AND `items_id` = '$ID'
                      AND `type` = '$alert_type'";
      $DB->query($query);
   }


   static function dropdown($options=array()) {
      global $LANG;

      if (!isset($options['value'])) {
         $value = 0;
      } else {
         $value = $options['value'];
      }

      if (isset($options['inherit_parent']) && $options['inherit_parent']) {
         $times[EntityData::CONFIG_PARENT] = __('Inheritance of the parent entity');
      }

      $times[EntityData::CONFIG_NEVER] = __('Never');
      $times[DAY_TIMESTAMP]            = __('Each day');
      $times[WEEK_TIMESTAMP]           = __('Each week');
      $times[MONTH_TIMESTAMP]          = __('Each month');

      Dropdown::showFromArray($options['name'], $times, array('value' => $value));
   }


   static function dropdownYesNo($options = array()) {
      global $LANG;

      if (!isset($options['value'])) {
         $value = 0;
      } else {
         $value = $options['value'];
      }

      if (isset($options['inherit_parent']) && $options['inherit_parent']) {
         $times[EntityData::CONFIG_PARENT] = __('Inheritance of the parent entity');
      }

      $times[0] = __('No');
      $times[1] = __('Yes');

      Dropdown::showFromArray($options['name'], $times, array('value' => $value));
   }


   static function dropdownIntegerNever($name, $value, $options=array()) {
      global $LANG;

      $p['max']   = 100;
      $p['step']  = 1;
      $p['toadd'] = array();
      if (isset($options['never_value']) && $options['never_value']) {
         $p['toadd'][$options['never_value']] = __('Never');
      } else {
         $p['toadd'][0] = __('Never');
      }

      if (isset($options['inherit_parent']) && $options['inherit_parent']) {
         $p['toadd'][-2] = __('Inheritance of the parent entity');
      }

      foreach ($options as $key=>$val) {
         $p[$key] = $val;
      }
      Dropdown::showInteger($name, $value, 1, $p['max'], $p['step'], $p['toadd']);
   }


   static function alertExists($itemtype='', $items_id='', $type='') {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_alerts`
                WHERE `itemtype` = '$itemtype'
                      AND `type` = '$type'
                      AND `items_id` = '$items_id'";
      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         return $DB->result($result,0,'id');
      }
      return false;
   }


   /**
    * Get the possible value for infocom alert
    *
    * @since version 0.83
    *
    * @param $val if not set, ask for all values, else for 1 value
    *
    * @return array or string
    */
   static function getAlertName($val=NULL) {
      global $LANG;

      $tmp[0] = Dropdown::EMPTY_VALUE;
      $tmp[pow(2, self::END)] = $LANG['financial'][80];

      if (is_null($val)) {
         return $tmp;
      }
      if (isset($tmp[$val])) {
         return $tmp[$val];
      }
      return NOT_AVAILABLE;
   }


   static function dropdownInfocomAlert($value) {

      Dropdown::showFromArray("default_infocom_alert", self::getAlertName(),
                              array('value' => $value));
   }


   static function displayLastAlert($itemtype, $items_id) {
      global $DB, $LANG;

      if ($items_id) {
         $query = "SELECT `date`
                   FROM `glpi_alerts`
                   WHERE `itemtype` = '$itemtype'
                         AND `items_id` = '$items_id'
                   ORDER BY `date` DESC
                   LIMIT 1";
         $result = $DB->query($query);
         if ($DB->numrows($result) > 0) {
            echo "&nbsp;".$LANG['mailing'][52].' '.Html::convDateTime($DB->result($result, 0,
                                                                                  'date'));
         }
      }
   }

}
?>