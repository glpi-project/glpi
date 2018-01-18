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
   die("Sorry. You can't access directly to this file");
}

/*
 * @since 9.2
 */
class DeviceSensor extends CommonDevice {

   static protected $forward_entity_to = ['Item_DeviceSensor', 'Infocom'];

   static function getTypeName($nb = 0) {
      return _n('Sensor', 'Sensors', $nb);
   }


   function getAdditionalFields() {

      return array_merge(parent::getAdditionalFields(),
                         [['name'  => 'devicesensortypes_id',
                           'label' => __('Type'),
                           'type'  => 'dropdownValue']]);
   }


   function rawSearchOptions() {
      $tab                 = parent::rawSearchOptions();

      $tab[] = ['id'       => '12',
                'table'    => 'glpi_devicesensortypes',
                'field'    => 'name',
                'name'     => __('Type'),
                'datatype' => 'dropdown'];

      return $tab;
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
         case 'Peripheral' :
            Manufacturer::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
            $base->addHeader('devicesensor_type', __('Type'), $super, $father);
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
         case 'Peripheral' :
            Manufacturer::getHTMLTableCellsForItem($row, $this, null, $options);
            break;
      }
   }


   /**
    * Criteria used for import function
    */
   function getImportCriteria() {

      return ['designation'          => 'equal',
              'manufacturers_id'     => 'equal',
              'devicesensortypes_id' => 'equal',
              'locations_id'         => 'equal'];
   }

}
