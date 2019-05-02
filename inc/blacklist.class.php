<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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
 * Blacklist Class
 *
 * @since 0.84
**/
class Blacklist extends CommonDropdown {

   // From CommonDBTM
   public $dohistory = true;

   static $rightname = 'config';

   public $can_be_translated = false;

   const IP     = 1;
   const MAC    = 2;
   const SERIAL = 3;
   const UUID   = 4;
   const EMAIL  = 5;


   static function canCreate() {
      return static::canUpdate();
   }


   /**
    * @since 0.85
   **/
   static function canPurge() {
      return static::canUpdate();
   }


   function getAdditionalFields() {

      return [['name'  => 'value',
                         'label' => __('Value'),
                         'type'  => 'text',
                         'list'  => true],
                   ['name'  => 'type',
                         'label' => _n('Type', 'Types', 1),
                         'type'  => '',
                         'list'  => true]];
   }


   static function getTypeName($nb = 0) {
      return _n('Blacklist', 'Blacklists', $nb);
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'value',
         'name'               => __('Value'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'type',
         'name'               => _n('Type', 'Types', 1),
         'searchtype'         => ['equals', 'notequals'],
         'datatype'           => 'specific'
      ];

      return $tab;
   }


   /**
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      if ((!isset($input['name']) || empty($input['name']))
          && isset($input['value'])) {
         $input['name'] = $input['value'];
      }
      return $input;
   }


   /**
    * @see CommonDropdown::displaySpecificTypeField()
   **/
   function displaySpecificTypeField($ID, $field = []) {

      if ($field['name'] == 'type') {
         self::dropdownType($field['name'], ['value' => $this->fields['type']]);
      }
   }


   /**
    * @param $field
    * @param $values
    * @param $options   array
    */
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'type' :
            $types = self::getTypes();
            return $types[$values[$field]];
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @since 0.84
    *
    * @param $field
    * @param $name               (default '')
    * @param $values             (default '')
    * @param $options      array
    **/
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;
      switch ($field) {
         case 'type' :
            $options['value']  = $values[$field];
            return self::dropdownType($name, $options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * Dropdown of blacklist types
    *
    * @param string $name   select name
    * @param array $options possible options:
    *    - value       : integer / preselected value (default 0)
    *    - toadd       : array / array of specific values to add at the begining
    *    - on_change   : string / value to transmit to "onChange"
    *    - display
    *
    * @return string id of the select
   **/
   static function dropdownType($name, $options = []) {

      $params = [
         'value'     => 0,
         'toadd'     => [],
         'on_change' => '',
         'display'   => true,
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $items = [];
      if (count($params['toadd'])>0) {
         $items = $params['toadd'];
      }

      $items += self::getTypes();

      return Dropdown::showFromArray($name, $items, $params);
   }


   /**
    * Get blacklist types
    *
    * @return array of types
   **/
   static function getTypes() {

      $options = [
         self::IP     => __('IP'),
         self::MAC    => __('MAC'),
         self::SERIAL => __('Serial number'),
         self::UUID   => __('UUID'),
         self::EMAIL  => _n('Email', 'Emails', 1),
      ];

      return $options;
   }


   /**
    * Get blacklisted items for a specific type
    *
    * @param string $type type to get (see constants)
    *
    * @return array of blacklisted items
   **/
   static function getBlacklistedItems($type) {

      $data = getAllDataFromTable('glpi_blacklists', ['type' => $type]);
      $items = [];
      if (count($data)) {
         foreach ($data as $val) {
            $items[] = $val['value'];
         }
      }
      return $items;
   }


   /**
    * Get blacklisted IP
    *
    * @return array of blacklisted IP
   **/
   static function getIPs() {
      return self::getBlacklistedItems(self::IP);
   }


   /**
    * Get blacklisted MAC
    *
    * @return array of blacklisted MAC
   **/
   static function getMACs() {
      return self::getBlacklistedItems(self::MAC);
   }


   /**
    * Get blacklisted Serial number
    *
    * @return array of blacklisted Serial number
   **/
   static function getSerialNumbers() {
      return self::getBlacklistedItems(self::SERIAL);
   }


   /**
    * Get blacklisted UUID
    *
    * @return array of blacklisted UUID
   **/
   static function getUUIDs() {
      return self::getBlacklistedItems(self::UUID);
   }


   /**
    * Get blacklisted Emails
    *
    * @return array of blacklisted Emails
   **/
   static function getEmails() {
      return self::getBlacklistedItems(self::EMAIL);
   }

}
