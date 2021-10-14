<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

class DeviceCamera extends CommonDevice {

   static protected $forward_entity_to = ['Item_DeviceCamera', 'Infocom'];

   static function getTypeName($nb = 0) {
      return _n('Camera', 'Cameras', $nb);
   }

   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong)
         ->addImpactTab($ong, $options)
         ->addStandardTab('Item_DeviceCamera_ImageResolution', $ong, $options)
         ->addStandardTab('Item_DeviceCamera_ImageFormat', $ong, $options)
         ->addStandardTab('Infocom', $ong, $options)
         ->addStandardTab('Contract_Item', $ong, $options)
         ->addStandardTab('Log', $ong, $options);
      return $ong;
   }

   function getAdditionalFields() {
      return array_merge(
         parent::getAdditionalFields(),
         [
            [
               'name'  => 'devicecameramodels_id',
               'label' => _n('Model', 'Models', 1),
               'type'  => 'dropdownValue'
            ],
            [
               'name'   => 'flashunit',
               'label'  => __('Flashunit'),
               'type'   => 'bool',
            ],
            [
               'name'   => 'lensfacing',
               'label'  => __('Lensfacing'),
               'type'   => 'text',
            ],
            [
               'name'   => 'orientation',
               'label'  => __('Orientation'),
               'type'   => 'text',
            ],
            [
               'name'   => 'focallength',
               'label'  => __('Focal length'),
               'type'   => 'text',
            ],
            [
               'name'   => 'sensorsize',
               'label'  => __('Sensor size'),
               'type'   => 'text',
            ],
            [
               'name'   => 'support',
               'label'  => __('Support'),
               'type'   => 'text',
            ]
         ]
      );
   }

   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '10',
         'table'              => 'glpi_devicecameramodels',
         'field'              => 'name',
         'name'               => _n('Model', 'Models', 1),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'flashunit',
         'name'               => __('Flashunit'),
         'datatype'           => 'boolean',
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'lensfacing',
         'name'               => __('Lensfacing'),
         'datatype'           => 'string',
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => $this->getTable(),
         'field'              => 'orientation',
         'name'               => __('orientation'),
         'datatype'           => 'string',
      ];

      $tab[] = [
         'id'                 => '14',
         'table'              => $this->getTable(),
         'field'              => 'focallength',
         'name'               => __('Focal length'),
         'datatype'           => 'string',
      ];

      $tab[] = [
         'id'                 => '15',
         'table'              => $this->getTable(),
         'field'              => 'sensorsize',
         'name'               => __('Sensor size'),
         'datatype'           => 'string',
      ];

      $tab[] = [
         'id'                 => '17',
         'table'              => $this->getTable(),
         'field'              => 'support',
         'name'               => __('Support'),
         'datatype'           => 'string',
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
      $base->addHeader('devicecamera_model', _n('Model', 'Models', 1), $super, $father);
      $base->addHeader('flashunit', __('Flashunit'), $super, $father);
      $base->addHeader('lensfacing', __('lensfacing'), $super, $father);
      $base->addHeader('orientation', __('orientation'), $super, $father);
      $base->addHeader('focallength', __('focal length'), $super, $father);
      $base->addHeader('sensorsize', __('sensorsize'), $super, $father);
      $base->addHeader('support', __('support'), $super, $father);

   }

   function getHTMLTableCellForItem(HTMLTableRow $row = null, CommonDBTM $item = null,
                                    HTMLTableCell $father = null, array $options = []) {

      $column = parent::getHTMLTableCellForItem($row, $item, $father, $options);

      if ($column == $father) {
         return $father;
      }

      Manufacturer::getHTMLTableCellsForItem($row, $this, null, $options);

      if ($this->fields["devicecameramodels_id"]) {
         $row->addCell(
            $row->getHeaderByName('devicecamera_model'),
            Dropdown::getDropdownName("glpi_devicecameramodels", $this->fields["devicecameramodels_id"]),
            $father
         );
      }

      if ($this->fields["lensfacing"]) {
         $row->addCell(
            $row->getHeaderByName('lensfacing'),
            $this->fields["lensfacing"],
            $father
         );
      }

      if ($this->fields["flashunit"]) {
         $row->addCell(
            $row->getHeaderByName('flashunit'),
            $this->fields["flashunit"],
            $father
         );
      }

   }

   function getImportCriteria() {
      return [
         'designation'           => 'equal',
         'devicebatterymodels_id' => 'equal',
         'manufacturers_id'      => 'equal'
      ];
   }

   static function getIcon() {
      return "fas fa-camera";
   }
}
