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
 * DevicePci Class
**/
class DevicePci extends CommonDevice {

   static protected $forward_entity_to = ['Item_DevicePci', 'Infocom'];

   static function getTypeName($nb = 0) {
      return _n('Other component', 'Other components', $nb);
   }


   /**
    * @see CommonDevice::getAdditionalFields()
    * @since 0.85
    */
   function getAdditionalFields() {

      return array_merge(parent::getAdditionalFields(),
                         [['name'  => 'none',
                                     'label' => RegisteredID::getTypeName(Session::getPluralNumber()).
                                        RegisteredID::showAddChildButtonForItemForm($this,
                                                                                    '_registeredID',
                                                                                    null, false),
                                     'type'  => 'registeredIDChooser'],
                         ['name'  => 'devicepcimodels_id',
                                     'label' => __('Model'),
                                     'type'  => 'dropdownValue']]);
   }

   function rawSearchOptions() {

      $tab                 = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '17',
         'table'              => 'glpi_devicepcimodels',
         'field'              => 'name',
         'name'               => __('Model'),
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }

   public static function rawSearchOptionsToAdd($itemtype, $main_joinparams) {
      $tab = [];

      $tab[] = [
         'id'                 => '95',
         'table'              => 'glpi_devicepcis',
         'field'              => 'designation',
         'name'               => __('Other component'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_devicepcis',
               'joinparams'         => $main_joinparams
            ]
         ]
      ];

      return $tab;
   }
}
