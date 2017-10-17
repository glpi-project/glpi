<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

/// CommonDCModelDropdown class - dropdown for datacenter items models
abstract class CommonDCModelDropdown extends CommonDropdown {

   public $additional_fields_for_dictionnary = ['manufacturer'];

   /**
    * Return Additional Fields for this type
    *
    * @return array
   **/
   function getAdditionalFields() {
      global $DB;

      $fields = [];
      if ($DB->fieldExists($this->getTable(), 'product_number')) {
         $fields[] = [
            'name'   => 'product_number',
            'type'   => 'text',
            'label'  => __('Product Number')
         ];
      }

      if ($DB->fieldExists($this->getTable(), 'weight')) {
         $fields[] = [
            'name'   => 'weight',
            'type'   => 'integer',
            'label'  => __('Weight')
         ];
      }

      if ($DB->fieldExists($this->getTable(), 'required_units')) {
         $fields[] = [
            'name'   => 'required_units',
            'type'   => 'integer',
            'min'    => 1,
            'label'  => __('Required units')
         ];
      }

      if ($DB->fieldExists($this->getTable(), 'depth')) {
         $fields[] = [
            'name'   => 'depth',
            'type'   => 'depth',
            'label'  => __('Depth')
         ];
      }

      if ($DB->fieldExists($this->getTable(), 'power_connections')) {
         $fields[] = [
            'name'   => 'power_connections',
            'type'   => 'integer',
            'label'  => __('Power connections')
         ];
      }

      if ($DB->fieldExists($this->getTable(), 'power_consumption')) {
         $fields[] = [
            'name'   => 'power_consumption',
            'type'   => 'integer',
            'label'  => __('Power consumption'),
            'unit'   => __('watts')
         ];
      }

      if ($DB->fieldExists($this->getTable(), 'is_half_rack')) {
         $fields[] = [
            'name'   => 'is_half_rack',
            'type'   => 'bool',
            'label'  => __('Is half rack')
         ];
      }

      return $fields;
   }

   function displaySpecificTypeField($ID, $field = []) {
      switch ($field['type']) {
         case 'depth':
            Dropdown::showFromArray(
               $field['name'],
               [
                  '1'      => __('1'),
                  '0.5'    => __('1/2'),
                  '0.33'   => __('1/3'),
                  '0.25'   => __('1/4')
               ], [
                  'value'                 => $this->fields[$field['name']]
               ]
            );
            break;
         default:
            throw new \RuntimeException("Unknown {$field['type']}");
      }
   }
}
