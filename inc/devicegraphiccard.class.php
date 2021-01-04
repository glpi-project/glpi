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

/// Class DeviceGraphicCard
class DeviceGraphicCard extends CommonDevice {

   static protected $forward_entity_to = ['Item_DeviceGraphicCard', 'Infocom'];

   static function getTypeName($nb = 0) {
      return _n('Graphics card', 'Graphics cards', $nb);
   }


   function getAdditionalFields() {

      return array_merge(parent::getAdditionalFields(),
                         [['name'  => 'chipset',
                                     'label' => __('Chipset'),
                                     'type'  => 'text'],
                               ['name'  => 'memory_default',
                                     'label' => __('Memory by default'),
                                     'type'  => 'text',
                                     'unit'  => __('Mio')],
                               ['name'  => 'interfacetypes_id',
                                     'label' => __('Interface'),
                                     'type'  => 'dropdownValue'],
                               ['name'  => 'none',
                                     'label' => RegisteredID::getTypeName(Session::getPluralNumber()).
                                        RegisteredID::showAddChildButtonForItemForm($this,
                                                                                    '_registeredID',
                                                                                    null, false),
                                     'type'  => 'registeredIDChooser'],
                               ['name'  => 'devicegraphiccardmodels_id',
                                     'label' => _n('Model', 'Models', 1),
                                     'type'  => 'dropdownValue']]);
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'chipset',
         'name'               => __('Chipset'),
         'datatype'           => 'string',
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'memory_default',
         'name'               => __('Memory by default'),
         'datatype'           => 'string',
         'autocomplete'       => true,
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
         'table'              => 'glpi_devicegraphiccardmodels',
         'field'              => 'name',
         'name'               => _n('Model', 'Models', 1),
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }


   /**
    * @since 0.85
    * @param  $input
    *
    * @return number
   **/
   function prepareInputForAddOrUpdate($input) {

      foreach (['memory_default'] as $field) {
         if (isset($input[$field]) && !is_numeric($input[$field])) {
            $input[$field] = 0;
         }
      }
      return $input;
   }


   function prepareInputForAdd($input) {
      return $this->prepareInputForAddOrUpdate($input);
   }


   function prepareInputForUpdate($input) {
      return $this->prepareInputForAddOrUpdate($input);
   }


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
            InterfaceType::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
            $base->addHeader('devicegraphiccard_chipset', __('Chipset'), $super, $father);
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
            InterfaceType::getHTMLTableCellsForItem($row, $this, null, $options);

            if (!empty($this->fields["chipset"])) {
               $row->addCell($row->getHeaderByName('devicegraphiccard_chipset'),
                             $this->fields["chipset"], $father);
            }
            break;
      }
   }


   function getImportCriteria() {

      return ['designation'       => 'equal',
                   'manufacturers_id'  => 'equal',
                   'interfacetypes_id' => 'equal'];
   }

   public static function rawSearchOptionsToAdd($itemtype, $main_joinparams) {
      $tab = [];

      $tab[] = [
         'id'                 => '13',
         'table'              => 'glpi_devicegraphiccards',
         'field'              => 'designation',
         'name'               => static::getTypeName(1),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_devicegraphiccards',
               'joinparams'         => $main_joinparams
            ]
         ]
      ];

      return $tab;
   }
}
