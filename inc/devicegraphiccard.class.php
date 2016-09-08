<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// Class DeviceGraphicCard
class DeviceGraphicCard extends CommonDevice {

   static protected $forward_entity_to = array('Item_DeviceGraphicCard', 'Infocom');

   static function getTypeName($nb=0) {
      return _n('Graphics card', 'Graphics cards', $nb);
   }


   function getAdditionalFields() {

      return array_merge(parent::getAdditionalFields(),
                         array(array('name'  => 'chipset',
                                     'label' => __('Chipset'),
                                     'type'  => 'text'),
                               array('name'  => 'memory_default',
                                     'label' => __('Memory by default'),
                                     'type'  => 'text',
                                     'unit'  => __('Mio')),
                               array('name'  => 'interfacetypes_id',
                                     'label' => __('Interface'),
                                     'type'  => 'dropdownValue'),
                               array('name'  => 'none',
                                     'label' => RegisteredID::getTypeName(Session::getPluralNumber()).
                                        RegisteredID::showAddChildButtonForItemForm($this,
                                                                                    '_registeredID',
                                                                                    NULL, false),
                                     'type'  => 'registeredIDChooser')));
   }


   function getSearchOptions() {

      $tab                 = parent::getSearchOptions();

      $tab[12]['table']    = $this->getTable();
      $tab[12]['field']    = 'memory_default';
      $tab[12]['name']     = __('Memory by default');
      $tab[12]['datatype'] = 'string';

      $tab[14]['table']    = 'glpi_interfacetypes';
      $tab[14]['field']    = 'name';
      $tab[14]['name']     = __('Interface');
      $tab[14]['datatype'] = 'dropdown';

      return $tab;
   }


   /**
    * @since version 0.85
    * @param  $input
    *
    * @return number
   **/
   function prepareInputForAddOrUpdate($input) {

      foreach (array('memory_default') as $field) {
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
            InterfaceType::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
            $base->addHeader('devicegraphiccard_chipset', __('Chipset'), $super, $father);
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
            InterfaceType::getHTMLTableCellsForItem($row, $this, NULL, $options);

            if (!empty($this->fields["chipset"])) {
               $row->addCell($row->getHeaderByName('devicegraphiccard_chipset'),
                             $this->fields["chipset"], $father);
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

      return array('designation'       => 'equal',
                   'manufacturers_id'  => 'equal',
                   'interfacetypes_id' => 'equal');
   }

}
?>