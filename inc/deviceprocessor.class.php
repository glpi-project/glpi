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

/// Class DeviceProcessor
class DeviceProcessor extends CommonDevice {

   static protected $forward_entity_to = array('Item_DeviceProcessor', 'Infocom');

   static function getTypeName($nb=0) {
      return _n('Processor', 'Processors', $nb);
   }


   function getAdditionalFields() {

      return array_merge(parent::getAdditionalFields(),
                         array(array('name'  => 'frequency_default',
                                     'label' => __('Frequency by default'),
                                     'type'  => 'text',
                                     'unit'  => __('MHz')),
                               array('name'  => 'frequence',
                                     'label' => __('Frequency'),
                                     'type'  => 'text',
                                     'unit'  => __('MHz')),
                               array('name'  => 'nbcores_default',
                                     'label' => __('Number of cores'),
                                     'type'  => 'integer'),
                               array('name'  => 'nbthreads_default',
                                     'label' => __('Number of threads'),
                                     'type'  => 'integer')
                           ));
   }


   function getSearchOptions() {

      $tab                 = parent::getSearchOptions();

      $tab[11]['table']    = $this->getTable();
      $tab[11]['field']    = 'frequency_default';
      $tab[11]['name']     = __('Frequency by default');
      $tab[11]['datatype'] = 'string';

      $tab[12]['table']    = $this->getTable();
      $tab[12]['field']    = 'frequence';
      $tab[12]['name']     = __('Frequency');
      $tab[12]['datatype'] = 'string';



      $tab[13]['table']    = $this->getTable();
      $tab[13]['field']    = 'nbcores_default';
      $tab[13]['name']     = __('Number of cores');
      $tab[13]['datatype'] = 'integer';

      $tab[14]['table']    = $this->getTable();
      $tab[14]['field']    = 'nbthreads_default';
      $tab[14]['name']     = __('Number of threads');
      $tab[14]['datatype'] = 'integer';

      return $tab;
   }


   /**
    * @since version 0.85
    * @param $input
    *
    * @return number
   **/
   function prepareInputForAddOrUpdate($input) {

      foreach (array('frequence', 'frequency_default', 'nbcores_default',
                     'nbthreads_default') as $field) {
         if (isset($input[$field]) && !is_numeric($input[$field])) {
            $input[$field] = 0;
         }
      }
      return $input;
   }


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

      return array('designation'          => 'equal',
                   'manufacturers_id'     => 'equal',
                   'frequence'            => 'delta:10');
   }

}
?>
