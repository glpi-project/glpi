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
            'label'  => __('Weight'),
            'max'    => 1000
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
            'unit'   => __('watts'),
            'html'   => true
         ];
      }

      if ($DB->fieldExists($this->getTable(), 'max_power')) {
         $fields[] = [
            'name'   => 'max_power',
            'type'   => 'integer',
            'label'  => __('Max. power (in watts)'),
            'unit'   => __('watts'),
            'html'   => true
         ];
      }

      if ($DB->fieldExists($this->getTable(), 'is_half_rack')) {
         $fields[] = [
            'name'   => 'is_half_rack',
            'type'   => 'bool',
            'label'  => __('Is half rack')
         ];
      }

      if ($DB->fieldExists($this->getTable(), 'picture_front')) {
         $fields[] = [
            'name'   => 'picture_front',
            'type'   => 'picture',
            'label'  => __('Front picture')
         ];
      }

      if ($DB->fieldExists($this->getTable(), 'picture_rear')) {
         $fields[] = [
            'name'   => 'picture_rear',
            'type'   => 'picture',
            'label'  => __('Rear picture')
         ];
      }

      return $fields;
   }

   function rawSearchOptions() {
      global $DB;
      $options = parent::rawSearchOptions();
      $table   = $this->getTable();

      if ($DB->fieldExists($table, 'product_number')) {
         $options[] = [
            'id'    => '130',
            'table' => $table,
            'field' => 'product_number',
            'name'  => __('Product Number'),
         ];
      }

      if ($DB->fieldExists($table, 'weight')) {
         $options[] = [
            'id'       => '131',
            'table'    => $table,
            'field'    => 'weight',
            'name'     => __('Weight'),
            'datatype' => 'decimal'
         ];
      }

      if ($DB->fieldExists($table, 'required_units')) {
         $options[] = [
            'id'       => '132',
            'table'    => $table,
            'field'    => 'required_units',
            'name'     => __('Required units'),
            'datatype' => 'number'
         ];
      }

      if ($DB->fieldExists($table, 'depth')) {
         $options[] = [
            'id'       => '133',
            'table'    => $table,
            'field'    => 'depth',
            'name'     => __('Depth'),
         ];
      }

      if ($DB->fieldExists($table, 'power_connections')) {
         $options[] = [
            'id'       => '134',
            'table'    => $table,
            'field'    => 'power_connections',
            'name'     => __('Power connections'),
            'datatype' => 'number'
         ];
      }

      if ($DB->fieldExists($table, 'power_consumption')) {
         $options[] = [
            'id'       => '135',
            'table'    => $table,
            'field'    => 'power_consumption',
            'name'     => __('Power consumption'),
            'datatype' => 'decimal'
         ];
      }

      if ($DB->fieldExists($table, 'is_half_rack')) {
         $options[] = [
            'id'       => '136',
            'table'    => $table,
            'field'    => 'is_half_rack',
            'name'     => __('Is half rack'),
            'datatype' => 'bool'
         ];
      }

      if ($DB->fieldExists($table, 'picture_front')) {
         $options[] = [
            'id'            => '137',
            'table'         => $table,
            'field'         => 'picture_front',
            'name'          => __('Front picture'),
            'datatype'      => 'specific',
            'nosearch'      => true,
            'massiveaction' => true,
            'nosort'        => true,
         ];
      }

      if ($DB->fieldExists($table, 'picture_rear')) {
         $options[] = [
            'id'            => '138',
            'table'         => $table,
            'field'         => 'picture_rear',
            'name'          => __('Rear picture'),
            'datatype'      => 'specific',
            'nosearch'      => true,
            'massiveaction' => true,
            'nosort'        => true,
         ];
      }

      return $options;
   }

   static function getSpecificValueToDisplay($field, $values, array $options = []) {
      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'picture_front':
         case 'picture_rear':
            if (isset($options['html']) && $options['html']) {
               return Html::image($values[$field], [
                  'alt'   => $options['searchopt']['name'],
                  'style' => 'height: 30px;',
               ]);
            }
      }

      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   function prepareInputForAdd($input) {
      return $this->managePictures($input);
   }

   function prepareInputForUpdate($input) {
      return $this->managePictures($input);
   }

   /**
    * Add/remove front and rear pictures for models
    * @param  array $input the form input
    * @return array        the altered input
    */
   function managePictures($input) {
      global $CFG_GLPI;

      foreach (['picture_front', 'picture_rear'] as $name) {
         if (isset($input["_blank_$name"])
             && $input["_blank_$name"]) {
            $input[$name] = '';
         }

         if (isset($input["_$name"])) {
            $filename = array_shift($input["_$name"]);
            $src      = GLPI_TMP_DIR."/".$filename;
            $prefix   = '';
            if (isset($input["_prefix_$name"])) {
               $prefix = array_shift($input["_prefix_$name"]);
            }
            $filename = str_replace($prefix, '', $filename);
            $dest     = GLPI_PICTURE_DIR."/".$filename;
            $moved    = false;

            if (is_file($dest)) {
               $moved = @unlink($dest);
            }
            $moved = rename($src, $dest);

            if ($moved) {
               $input[$name] = $CFG_GLPI["root_doc"].
                               "/front/document.send.php?file=_pictures/$filename";
            }
         }
      }

      return $input;
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
