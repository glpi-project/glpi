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
 * Holiday Class
**/
class Holiday extends CommonDropdown {

   static $rightname = 'calendar';

   public $can_be_translated = false;


   static function getTypeName($nb = 0) {
      return _n('Close time', 'Close times', $nb);
   }


   function getAdditionalFields() {

      return [['name'  => 'begin_date',
                         'label' => __('Start'),
                         'type'  => 'date'],
                   ['name'  => 'end_date',
                         'label' => __('End'),
                         'type'  => 'date'],
                   ['name'  => 'is_perpetual',
                         'label' => __('Recurrent'),
                         'type'  => 'bool']];
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'begin_date',
         'name'               => __('Start'),
         'datatype'           => 'date'
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'end_date',
         'name'               => __('End'),
         'datatype'           => 'date'
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => $this->getTable(),
         'field'              => 'is_perpetual',
         'name'               => __('Recurrent'),
         'datatype'           => 'bool'
      ];

      return $tab;
   }


   /**
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      $input = parent::prepareInputForAdd($input);

      if (empty($input['end_date'])
          || ($input['end_date'] == 'NULL')
          || ($input['end_date'] < $input['begin_date'])) {

         $input['end_date'] = $input['begin_date'];
      }
      return $input;
   }


   /**
    * @see CommonDBTM::prepareInputForUpdate()
   **/
   function prepareInputForUpdate($input) {

      $input = parent::prepareInputForUpdate($input);

      if (empty($input['end_date'])
          || ($input['end_date'] == 'NULL')
          || ($input['end_date'] < $input['begin_date'])) {

         $input['end_date'] = $input['begin_date'];
      }

      return $input;
   }

}
