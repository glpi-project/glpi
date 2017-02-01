<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// Class DeviceMemory
class DeviceMemory extends CommonDevice {

   static protected $forward_entity_to = array('Item_DeviceMemory', 'Infocom');

   static function getTypeName($nb=0) {
      return _n('Memory', 'Memories', $nb);
   }


   function getAdditionalFields() {

      return array_merge(parent::getAdditionalFields(),
                         array(array('name'  => 'size_default',
                                     'label' => __('Size by default'),
                                     'type'  => 'text',
                                     'unit'  => __('Mio')),
                               array('name'  => 'frequence',
                                     'label' => __('Frequency'),
                                     'type'  => 'text',
                                     'unit'  => __('MHz')),
                               array('name'  => 'devicememorytypes_id',
                                     'label' => __('Type'),
                                     'type'  => 'dropdownValue'),
                               array('name'  => 'devicememorymodels_id',
                                     'label' => __('Model'),
                                     'type'  => 'dropdownValue')));
   }


   function getSearchOptionsNew() {
      $tab = parent::getSearchOptionsNew();

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'size_default',
         'name'               => __('Size by default'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'frequence',
         'name'               => __('Frequency'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => 'glpi_devicememorytypes',
         'field'              => 'name',
         'name'               => __('Type'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '14',
         'table'              => 'glpi_devicememorymodels',
         'field'              => 'name',
         'name'               => __('Model'),
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }


   /**
    * @since version 0.85
    * @param $input
    *
    * @return number
   **/
   function prepareInputForAddOrUpdate($input) {

      foreach (array('size_default') as $field) {
         if (isset($input[$field]) && !is_numeric($input[$field])) {
            $input[$field] = 0;
         }
      }
      return $input;
   }


   /**
    * @since version 0.85
    * @see CommonDropdown::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {
      return self::prepareInputForAddOrUpdate($input);
   }


   /**
    * @since version 0.85
    * @see CommonDropdown::prepareInputForUpdate()
   **/
   function prepareInputForUpdate($input) {
      return self::prepareInputForAddOrUpdate($input);
   }


   /**
    * @since version 0.84
    *
    * @see CommonDevice::getHTMLTableHeader()
   **/
   static function getHTMLTableHeader($itemtype, HTMLTableBase $base,
                                      HTMLTableSuperHeader $super=NULL,
                                      HTMLTableHeader $father=NULL, array $options=array()) {

      $column = parent::getHTMLTableHeader($itemtype, $base, $super, $father, $options);

      if ($column == $father) {
         return $father;
      }

      switch ($itemtype) {
         case 'Computer' :
            Manufacturer::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
            $base->addHeader('devicememory_type', __('Type'), $super, $father);
            $base->addHeader('devicememory_frequency', __('Frequency'), $super, $father);
            break;
      }

   }


   /**
    * @since version 0.84
    *
    * @see CommonDevice::getHTMLTableCellForItem()
   **/
   function getHTMLTableCellForItem(HTMLTableRow $row=NULL, CommonDBTM $item=NULL,
                                    HTMLTableCell $father=NULL, array $options=array()) {

      $column = parent::getHTMLTableCellForItem($row, $item, $father, $options);

      if ($column == $father) {
         return $father;
      }

      switch ($item->getType()) {
         case 'Computer' :
            Manufacturer::getHTMLTableCellsForItem($row, $this, NULL, $options);
            if ($this->fields["devicememorytypes_id"]) {
               $row->addCell($row->getHeaderByName('devicememory_type'),
                             Dropdown::getDropdownName("glpi_devicememorytypes",
                                                       $this->fields["devicememorytypes_id"]),
                             $father);
            }

            if (!empty($this->fields["frequence"])) {
               $row->addCell($row->getHeaderByName('devicememory_frequency'),
                             $this->fields["frequence"], $father);
            }
            break;
      }
   }


   /**
    * Criteria used for import function
    *
    * @see CommonDevice::getImportCriteria()
    *
    * @since version 0.84
   **/
   function getImportCriteria() {

      return array('designation'          => 'equal',
                   'devicememorytypes_id' => 'equal',
                   'manufacturers_id'     => 'equal',
                   'frequence'            => 'delta:10');
   }

}
