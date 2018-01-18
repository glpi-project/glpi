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

/// Class DeviceSimcard
class DeviceSimcard extends CommonDevice {
   static protected $forward_entity_to = ['Item_DeviceSimcard', 'Infocom'];

   static function getTypeName($nb = 0) {
      return _n('Simcard', 'Simcards', $nb);
   }

   function getAdditionalFields() {

      return array_merge(
         parent::getAdditionalFields(),
         [
            [
               'name'  => 'devicesimcardtypes_id',
               'label' => __('Type'),
               'type'  => 'dropdownValue'
            ],
            [
               'name'  => 'voltage',
               'label' => __('Voltage'),
               'type'  => 'text',
               'unit'  => 'mV'
            ],
            [
                  'name'  => 'allow_voip',
                  'label' => __('Allow VOIP'),
                  'type'  => 'bool'
            ],
         ]
      );
   }

   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
            'id'                 => '12',
            'table'              => 'glpi_devicesimcardvoltages',
            'field'              => 'name',
            'name'               => __('Voltage'),
            'datatype'           => 'string'
      ];

      $tab[] = [
            'id'                 => '13',
            'table'              => 'glpi_devicesimcardtypes',
            'field'              => 'name',
            'name'               => __('Type'),
            'datatype'           => 'dropdown'
      ];

      $tab[] = [
            'id'                 => '14',
            'table'              => $this->getTable(),
            'field'              => 'allow_voip',
            'name'               => __('Allow VOIP'),
            'datatype'           => 'bool'
      ];

      return $tab;
   }

   /**
    * Criteria used for import function
    *
    * @see CommonDevice::getImportCriteria()
    *
    * @since 9.2
    **/
   function getImportCriteria() {

      return [
            'designation'             => 'equal',
            'manufacturers_id'        => 'equal',
            'devicesensortypes_id'    => 'equal',
      ];
   }

}
