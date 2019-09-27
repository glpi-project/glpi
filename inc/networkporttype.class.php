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

use Glpi\Inventory\FilesToJSON;

/// Class NetworkPortType
class NetworkPortType extends CommonDropdown {

   public const DEFAULT_TYPE = 'NetworkPortEthernet';

   static function getTypeName($nb = 0) {
      return _n('Network port type', 'Network port types', $nb);
   }

   function getAdditionalFields() {

      return [
         [
            'name'   => 'value_decimal',
            'label'  => __('Decimal'),
            'type'   => 'integer',
            'max'    => 1000
         ], [
            'name'  => 'is_importable',
            'label' => __('Import'),
            'type'  => 'bool'
         ], [
            'name'  => 'instantiation_type',
            'label' => __('Instanciation type'),
            'type'           => 'itemtypename',
            'itemtype_list'      => 'networkport_instantiations',
         ]
      ];
   }

   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '10',
         'table'              => $this->getTable(),
         'field'              => 'value_decimal',
         'name'               => __('Decimal'),
         'datatype'           => 'integer'
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'is_importable',
         'name'               => __('Import'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'instantiation_type',
         'name'               => __('Instanciation type'),
         'datatype'           => 'itemtypename',
         'itemtype_list'      => 'networkport_instantiations',
      ];

      return $tab;
   }

   public static function getDefaults(): array {
      $jsonfile = new FilesToJSON();
      $iftypes = json_decode(file_get_contents($jsonfile->getPathFor('iftype')), true);

      $default_instanciations = [
         'Ethernet'     => [6, 7, 62, 117, 169],
         'Wifi'         => [71],
         'Fiberchannel' => [56]
      ];

      $template = [
         'entities_id'        => 0,
         'is_recursive'       => 0,
         'value_decimal'      => 0,
         'name'               => null,
         'comment'            => null,
         'is_importable'      => 0,
         'instantiation_type' => null,
         'date_creation'      => $_SESSION['glpi_currenttime'],
         'date_mod'           => $_SESSION['glpi_currenttime']
      ];

      $defaults = [];
      foreach ($iftypes as $iftype) {
         $importable = 0;
         $instanciation = null;

         foreach ($default_instanciations as $inst_type => $importables) {
            if (in_array($iftype['decimal'], $importables)) {
               $importable = 1;
               $instanciation = 'NetworkPort' . $inst_type;
            }
         }

         $row = array_merge($template, [
            'value_decimal'      => (int)$iftype['decimal'],
            'name'               => $iftype['name'],
            'comment'            => trim($iftype['description'] . ' ' . $iftype['references']),
            'is_importable'      => $importable,
            'instantiation_type' => $instanciation
         ]);
         $defaults[] = $row;

      }
      return $defaults;
   }

   /**
    * Get instantiation type for a port type
    *
    * @param mixed $type Requested type
    *
    * @return false|string
    */
   public static function getInstantiationType($type) {
      global $DB, $GLPI_CACHE;

      if (null === $type || empty($type)) {
         return self::DEFAULT_TYPE;
      }

      if (($import_types = $GLPI_CACHE->get('glpi_inventory_ports_types')) === null) {
         $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
               'is_importable'   => true
            ]
         ]);

         $import_types = [];
         while ($row = $iterator->next()) {
            $import_types[$row['value_decimal']] = $row;
         }
         $GLPI_CACHE->set('glpi_inventory_ports_types', $import_types);
      }

      foreach ($import_types as $num => $entry) {
         $name = $entry['name'];
         $othername = "$name ($num)";

         if ($type === $num || $type == $name || $type == $othername) {
            return $row['instantiation_type'] ?? self::DEFAULT_TYPE;
         }
      }

      return false;
   }
}
