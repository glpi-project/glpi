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

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Blacklist class
class Blacklist extends CommonDropdown {

   // From CommonDBTM
   public $dohistory = true;

   // IP
   const IP     = 1;
   // MAC
   const MAC    = 2;
   // SERIAL
   const SERIAL = 3;
   // UUID
   const UUID   = 4;   

   function canCreate() {
      return Session::haveRight('config', 'w');
   }


   function canView() {
      return Session::haveRight('config', 'r');
   }


   function getAdditionalFields() {

      return array(array('name'  => 'value',
                         'label' => __('Value'),
                         'type'  => 'text',
                         'list'  => true),
                   array('name'  => 'type',
                         'label' => _n('Type','Types',1),
                         'type'  => '',
                         'list'  => true));
   }


   static function getTypeName($nb=0) {
      return _n('Blacklist','Blacklists',$nb);
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {

      $tab = parent::getSearchOptions();

      $tab[11]['table']    = $this->getTable();
      $tab[11]['field']    = 'value';
      $tab[11]['name']     = __('Value');
      $tab[11]['datatype'] = 'text';

      $tab[12]['table']    = $this->getTable();
      $tab[12]['field']    = 'type';
      $tab[12]['name']     = _n('Type','Types',1);

      return $tab;
   }

   function prepareInputForAdd($input) {
      if ((!isset($input['name']) || empty($input['name'])) && isset($input['value'])) {
         $input['name'] = $input['value'];
      }
      return $input;
   }

   function displaySpecificTypeField($ID, $field=array()) {
      print_r($field);
      if ($field['name'] == 'type') {
         echo "i";
         self::dropdownType($field['name'], array('value' => $this->fields['type']));
      
      }
   }

   /**
    * Dropdown of blacklist types
    *
    * @param $name select name
    * @param $options array of options
    *
    * Parameters which could be used in options array :
    *    - value : integer / preselected value (default 0)
    *    - toadd : array / array of specific values to add at the begining
    *    - on_change : string / value to transmit to "onChange"
    *
    * @return string id of the select
   **/
   static function dropdownType($name, $options=array()) {

      $params['value']       = 0;
      $params['toadd']       = array();
      $params['on_change']   = '';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $items = array();
      if (count($params['toadd'])>0) {
         $items = $params['toadd'];
      }

      $items += self::getTypes();

      return Dropdown::showFromArray($name, $items, $params);
   }
   
   /**
    * Get ticket types
    *
    * @return array of types
   **/
   static function getTypes() {

      $options[self::IP]     = __('IP');
      $options[self::MAC]    = __('MAC');
      $options[self::SERIAL] = __('Serial number');
      $options[self::UUID]   = __('UUID');

      return $options;
   }
}
?>