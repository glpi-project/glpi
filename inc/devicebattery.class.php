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

class DeviceBattery extends CommonDevice {

   static protected $forward_entity_to = ['Item_DeviceBattery', 'Infocom'];

   static function getTypeName($nb = 0) {
      return _n('Battery', 'Batteries', $nb);
   }


   function getAdditionalFields() {
      return array_merge(
         parent::getAdditionalFields(),
         [
            [
               'name'  => 'devicebatterytypes_id',
               'label' => __('Type'),
               'type'  => 'dropdownValue'
            ],
            [
               'name'   => 'capacity',
               'label'  => __('Capacity'),
               'type'   => 'text',
               'unit'   => __('mWh')
            ],
            [
               'name'   => 'voltage',
               'label'  => __('Voltage'),
               'type'   => 'text',
               'unit'   => __('mV')
            ]
         ]
      );
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'capacity',
         'name'               => __('Capacity'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'voltage',
         'name'               => __('Voltage'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => 'glpi_devicebatterytypes',
         'field'              => 'name',
         'name'               => __('Type'),
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }

   static function getHTMLTableHeader($itemtype, HTMLTableBase $base,
                                      HTMLTableSuperHeader $super = null,
                                      HTMLTableHeader $father = null, array $options = []) {

      $column = parent::getHTMLTableHeader($itemtype, $base, $super, $father, $options);

      if ($column == $father) {
         return $father;
      }

      Manufacturer::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
      $base->addHeader('devicebattery_type', __('Type'), $super, $father);
      $base->addHeader('voltage', sprintf('%1$s (%2$s)', __('Voltage'), __('mV')), $super, $father);
      $base->addHeader('capacity', sprintf('%1$s (%2$s)', __('Capacity'), __('mWh')), $super, $father);
   }

   function getHTMLTableCellForItem(HTMLTableRow $row = null, CommonDBTM $item = null,
                                    HTMLTableCell $father = null, array $options = []) {

      $column = parent::getHTMLTableCellForItem($row, $item, $father, $options);

      if ($column == $father) {
         return $father;
      }

      Manufacturer::getHTMLTableCellsForItem($row, $this, null, $options);

      if ($this->fields["devicebatterytypes_id"]) {
         $row->addCell(
            $row->getHeaderByName('devicebattery_type'),
            Dropdown::getDropdownName("glpi_devicebatterytypes",
            $this->fields["devicebatterytypes_id"]),
            $father
         );
      }

      if ($this->fields["voltage"]) {
         $row->addCell(
            $row->getHeaderByName('voltage'),
            $this->fields['voltage'],
            $father
         );
      }

      if ($this->fields["capacity"]) {
         $row->addCell(
            $row->getHeaderByName('capacity'),
            $this->fields['capacity'],
            $father
         );
      }

   }


   function getImportCriteria() {

      return [
         'designation'           => 'equal',
         'devicebatterytypes_id' => 'equal',
         'manufacturers_id'      => 'equal',
         'capacity'              => 'delta:10',
         'voltage'               => 'delta:10'
      ];
   }
}
