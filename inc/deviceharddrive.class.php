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

/// Class DeviceHardDrive
class DeviceHardDrive extends CommonDevice {

   static protected $forward_entity_to = ['Item_DeviceHardDrive', 'Infocom'];

   static function getTypeName($nb = 0) {
      return _n('Hard drive', 'Hard drives', $nb);
   }


   function getAdditionalFields() {

      return array_merge(parent::getAdditionalFields(),
                         [['name'  => 'capacity_default',
                                     'label' => __('Capacity by default'),
                                     'type'  => 'text',
                                     'unit'  => __('Mio')],
                               ['name'  => 'rpm',
                                     'label' => __('Rpm'),
                                     'type'  => 'text'],
                               ['name'  => 'cache',
                                     'label' => __('Cache'),
                                     'type'  => 'text',
                                     'unit'  => __('Mio')],
                               ['name'  => 'deviceharddrivemodels_id',
                                     'label' => __('Model'),
                                     'type'  => 'dropdownValue'],
                               ['name'  => 'interfacetypes_id',
                                     'label' => __('Interface'),
                                     'type'  => 'dropdownValue']]);
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'capacity_default',
         'name'               => __('Capacity by default'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'rpm',
         'name'               => __('Rpm'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => $this->getTable(),
         'field'              => 'cache',
         'name'               => __('Cache'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '14',
         'table'              => 'glpi_interfacetypes',
         'field'              => 'name',
         'name'               => __('Interface'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '15',
         'table'              => 'glpi_deviceharddrivemodels',
         'field'              => 'name',
         'name'               => __('Model'),
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }


   /**
    * @since 0.85
    * @param $input
    *
    * @return number
   **/
   function prepareInputForAddOrUpdate($input) {

      foreach (['capacity_default'] as $field) {
         if (isset($input[$field]) && !is_numeric($input[$field])) {
            $input[$field] = 0;
         }
      }
      return $input;
   }


   /**
    * @since 0.85
    * @see CommonDropdown::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {
      return self::prepareInputForAddOrUpdate($input);
   }


   /**
    * @since 0.85
    * @see CommonDropdown::prepareInputForUpdate()
   **/
   function prepareInputForUpdate($input) {
      return self::prepareInputForAddOrUpdate($input);
   }


   /**
    * @since 0.84
    *
    * @see CommonDevice::getHTMLTableHeader()
   **/
   static function getHTMLTableHeader($itemtype, HTMLTableBase $base,
                                      HTMLTableSuperHeader $super = null,
                                      HTMLTableHeader $father = null, array $options = []) {

      $column = parent::getHTMLTableHeader($itemtype, $base, $super, $father, $options);

      if ($column == $father) {
         return $father;
      }

      switch ($itemtype) {
         case 'Computer' :
            Manufacturer::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
            $base->addHeader('deviceharddriver_rpm', __('Rpm'), $super, $father);
            $base->addHeader('deviceharddriver_cache', __('Cache'), $super, $father);
            InterfaceType::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
            break;
      }

   }


   function getHTMLTableCellForItem(HTMLTableRow $row = null, CommonDBTM $item = null,
                                    HTMLTableCell $father = null, array $options = []) {

      $column = parent::getHTMLTableCellForItem($row, $item, $father, $options);

      if ($column == $father) {
         return $father;
      }

      switch ($item->getType()) {
         case 'Computer' :
            Manufacturer::getHTMLTableCellsForItem($row, $this, null, $options);
            if ($this->fields["rpm"]) {
               $row->addCell($row->getHeaderByName('deviceharddriver_rpm'), $this->fields["rpm"]);
            }

            if ($this->fields["cache"]) {
               $row->addCell($row->getHeaderByName('deviceharddriver_cache'),
                             $this->fields["cache"]);
            }

            InterfaceType::getHTMLTableCellsForItem($row, $this, null, $options);
            break;
      }
   }


   /**
    * Criteria used for import function
    *
    * @see CommonDevice::getImportCriteria()
    *
    * @since 0.84
   **/
   function getImportCriteria() {

      return ['designation'       => 'equal',
                   'manufacturers_id'  => 'equal',
                   'interfacetypes_id' => 'equal'];
   }

   public static function rawSearchOptionsToAdd($itemtype, $main_joinparams) {
      $tab = [];

      $tab[] = [
         'id'                 => '114',
         'table'              => 'glpi_deviceharddrives',
         'field'              => 'designation',
         'name'               => __('Hard drive type'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_deviceharddrives',
               'joinparams'         => $main_joinparams
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '115',
         'table'              => 'glpi_items_deviceharddrives',
         'field'              => 'capacity',
         'name'               => __('Hard drive size'),
         'unit'               => 'Mio',
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'number',
         'width'              => 1000,
         'massiveaction'      => false,
         'joinparams'         => $main_joinparams,
         'computation'        => '(SUM(TABLE.`capacity`) / COUNT(TABLE.`id`))
                                       * COUNT(DISTINCT TABLE.`id`)'
      ];

      return $tab;
   }
}
