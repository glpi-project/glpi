<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2017 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class DeviceFirmware extends CommonDevice {

   static protected $forward_entity_to = array('Item_DeviceFirmware', 'Infocom');

   static function getTypeName($nb=0) {
      return __('Firmware');
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
               'label'  => __('Date'),
               'type'   => 'text'
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


   function getSearchOptionsNew() {
      $tab = parent::getSearchOptionsNew();

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'date',
         'name'               => __('Date'),
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

      return $tab;
   }

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
            $base->addHeader('devicefirmware_type', __('Type'), $super, $father);
            break;
      }
   }

   function getHTMLTableCellForItem(HTMLTableRow $row=NULL, CommonDBTM $item=NULL,
                                    HTMLTableCell $father=NULL, array $options=array()) {

      $column = parent::getHTMLTableCellForItem($row, $item, $father, $options);

      if ($column == $father) {
         return $father;
      }

      switch ($item->getType()) {
         case 'Computer' :
            Manufacturer::getHTMLTableCellsForItem($row, $this, NULL, $options);

            if ($this->fields["devicefirmwaretypes_id"]) {
               $row->addCell(
                  $row->getHeaderByName('devicefirmware_type'),
                  Dropdown::getDropdownName("glpi_devicefirmwaretypes",
                  $this->fields["devicefirmwaretypes_id"]),
                  $father
               );
            }

            break;
      }
   }

   function getImportCriteria() {

      return [
         'designation'              => 'equal',
         'devicefirmwaretypes_id'   => 'equal',
         'manufacturers_id'         => 'equal'
      ];
   }
}
