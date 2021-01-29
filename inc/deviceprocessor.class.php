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

/// Class DeviceProcessor
class DeviceProcessor extends CommonDevice {

   static protected $forward_entity_to = ['Item_DeviceProcessor', 'Infocom'];

   static function getTypeName($nb = 0) {
      return _n('Processor', 'Processors', $nb);
   }


   function getAdditionalFields() {

      return array_merge(parent::getAdditionalFields(),
                         [['name'  => 'frequency_default',
                                     'label' => __('Frequency by default'),
                                     'type'  => 'text',
                                     'unit'  => __('MHz')],
                               ['name'  => 'frequence',
                                     'label' => __('Frequency'),
                                     'type'  => 'text',
                                     'unit'  => __('MHz')],
                               ['name'  => 'nbcores_default',
                                     'label' => __('Number of cores'),
                                     'type'  => 'integer'],
                               ['name'  => 'nbthreads_default',
                                     'label' => __('Number of threads'),
                                     'type'  => 'integer'],
                               ['name'  => 'deviceprocessormodels_id',
                                     'label' => _n('Model', 'Models', 1),
                                     'type'  => 'dropdownValue']
                           ]);
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'frequency_default',
         'name'               => __('Frequency by default'),
         'datatype'           => 'string',
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'frequence',
         'name'               => __('Frequency'),
         'datatype'           => 'string',
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => $this->getTable(),
         'field'              => 'nbcores_default',
         'name'               => __('Number of cores'),
         'datatype'           => 'integer'
      ];

      $tab[] = [
         'id'                 => '14',
         'table'              => $this->getTable(),
         'field'              => 'nbthreads_default',
         'name'               => __('Number of threads'),
         'datatype'           => 'integer'
      ];

      $tab[] = [
         'id'                 => '15',
         'table'              => 'glpi_deviceprocessormodels',
         'field'              => 'name',
         'name'               => _n('Model', 'Models', 1),
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }


   /**
    * @since 0.85
    * @param $input
    *
    * @return number
   **/
   function prepareInputForAddOrUpdate($input) {

      foreach (['frequence', 'frequency_default', 'nbcores_default',
                     'nbthreads_default'] as $field) {
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
            break;
      }
   }


   function getImportCriteria() {

      return ['designation'          => 'equal',
                   'manufacturers_id'     => 'equal',
                   'frequence'            => 'delta:10'];
   }

   public static function rawSearchOptionsToAdd($itemtype, $main_joinparams) {
      global $DB;

      $tab = [];

      $tab[] = [
         'id'                 => '17',
         'table'              => 'glpi_deviceprocessors',
         'field'              => 'designation',
         'name'               => self::getTypeName(1),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_deviceprocessors',
               'joinparams'         => $main_joinparams
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '18',
         'table'              => 'glpi_items_deviceprocessors',
         'field'              => 'nbcores',
         'name'               => __('processor: number of cores'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'number',
         'massiveaction'      => false,
         'joinparams'         => $main_joinparams,
         'computation'        =>
            'SUM(' . $DB->quoteName('TABLE.nbcores') . ') * COUNT(DISTINCT ' .
            $DB->quoteName('TABLE.id') . ') / COUNT(*)',
         'nometa'             => true, // cannot GROUP_CONCAT a SUM
      ];

      $tab[] = [
         'id'                 => '34',
         'table'              => 'glpi_items_deviceprocessors',
         'field'              => 'nbthreads',
         'name'               => __('processor: number of threads'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'number',
         'massiveaction'      => false,
         'joinparams'         => $main_joinparams,
         'computation'        =>
            'SUM(' . $DB->quoteName('TABLE.nbthreads') . ') * COUNT(DISTINCT ' .
            $DB->quoteName('TABLE.id') . ') / COUNT(*)',
         'nometa'             => true, // cannot GROUP_CONCAT a SUM
      ];

      $tab[] = [
         'id'                 => '36',
         'table'              => 'glpi_items_deviceprocessors',
         'field'              => 'frequency',
         'name'               => __('Processor frequency'),
         'unit'               => 'MHz',
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'number',
         'width'              => 100,
         'massiveaction'      => false,
         'joinparams'         => $main_joinparams,
         'computation'        =>
            'SUM(' . $DB->quoteName('TABLE.frequency') . ') / COUNT(' .
            $DB->quoteName('TABLE.id') . ')',
         'nometa'             => true, // cannot GROUP_CONCAT a SUM
      ];

      return $tab;
   }


   static function getIcon() {
      return "fas fa-microchip";
   }
}
