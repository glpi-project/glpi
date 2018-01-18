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

class DeviceFirmware extends CommonDevice {

   static protected $forward_entity_to = ['Item_DeviceFirmware', 'Infocom'];

   static function getTypeName($nb = 0) {
      return _n('Firmware', 'Firmware', $nb);
   }


   function getAdditionalFields() {

      return array_merge(
         parent::getAdditionalFields(),
         [
            [
               'name'  => 'devicefirmwaretypes_id',
               'label' => __('Type'),
               'type'  => 'dropdownValue'
            ],
            [
               'name'   => 'date',
               'label'  => __('Installation date'),
               'type'   => 'date'
            ],
            [
               'name'   => 'version',
               'label'  => __('Version'),
               'type'   => 'text'
            ],
            [
               'name'   => 'devicefirmwaremodels_id',
               'label'  => __('Model'),
               'type'   => 'dropdownValue'
            ]
         ]
      );
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'date',
         'name'               => __('Installation date'),
         'datatype'           => 'date'
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => 'glpi_devicefirmwaremodels',
         'field'              => 'name',
         'name'               => __('Model'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => 'glpi_devicefirmwaretypes',
         'field'              => 'name',
         'name'               => __('Type'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '14',
         'table'              => 'glpi_devicefirmwares',
         'field'              => 'version',
         'name'               => __('Version'),
      ];

      return $tab;
   }

   static function getHTMLTableHeader($itemtype, HTMLTableBase $base,
                                      HTMLTableSuperHeader $super = null,
                                      HTMLTableHeader $father = null, array $options = []) {
      global $CFG_GLPI;
      $column = parent::getHTMLTableHeader($itemtype, $base, $super, $father, $options);

      if ($column == $father) {
         return $father;
      }

      if (in_array($itemtype, $CFG_GLPI['itemdevicefirmware_types'])) {
         Manufacturer::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
         $base->addHeader('devicefirmware_type', __('Type'), $super, $father);
         $base->addHeader('version', __('Version'), $super, $father);
         $base->addHeader('date', __('Installation date'), $super, $father);
      }
   }

   function getHTMLTableCellForItem(HTMLTableRow $row = null, CommonDBTM $item = null,
                                    HTMLTableCell $father = null, array $options = []) {
      global $CFG_GLPI;
      $column = parent::getHTMLTableCellForItem($row, $item, $father, $options);

      if ($column == $father) {
         return $father;
      }

      if (in_array($item->getType(), $CFG_GLPI['itemdevicefirmware_types'])) {
         Manufacturer::getHTMLTableCellsForItem($row, $this, null, $options);

         if ($this->fields["devicefirmwaretypes_id"]) {
            $row->addCell(
               $row->getHeaderByName('devicefirmware_type'),
               Dropdown::getDropdownName("glpi_devicefirmwaretypes",
               $this->fields["devicefirmwaretypes_id"]),
               $father
            );
         }
         $row->addCell(
            $row->getHeaderByName('version'), $this->fields["version"],
            $father
            );

         if ($this->fields["date"]) {
            $row->addCell(
               $row->getHeaderByName('date'),
               Html::convDate($this->fields["date"]),
               $father
            );
         }
      }
   }

   function getImportCriteria() {

      return [
         'devicefirmwaretypes_id'   => 'equal',
         'manufacturers_id'         => 'equal',
         'version'                  => 'equal'
      ];
   }
}
