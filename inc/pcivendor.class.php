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

use Glpi\Features\CacheableListInterface;
use Glpi\Inventory\FilesToJSON;

/// Class PCIVendor
class PCIVendor extends CommonDropdown implements CacheableListInterface {
   public $cache_key = 'glpi_pcivendors';

   static function getTypeName($nb = 0) {
      return _n('PCI vendor', 'PCI vendors', $nb);
   }

   function getAdditionalFields() {
      return [
         [
            'name'   => 'vendorid',
            'label'  => __('Vendor ID'),
            'type'   => 'text'
         ], [
            'name'  => 'deviceid',
            'label' => __('Device ID'),
            'type'  => 'text'
         ]
      ];
   }

   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '10',
         'table'              => $this->getTable(),
         'field'              => 'vendorid',
         'name'               => __('Vendor ID'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'deviceid',
         'name'               => __('Device ID'),
         'datatype'           => 'string'
      ];

      return $tab;
   }

   /**
    * Get list of all known PCIIDs
    *
    * @return array
    */
   public static function getList(): array {
      global $GLPI_CACHE;

      $vendors = new PCIVendor();
      if (($pciids = $GLPI_CACHE->get($vendors->cache_key)) !== null) {
         return $pciids;
      }

      $jsonfile = new FilesToJSON();
      $file_pciids = json_decode(file_get_contents($jsonfile->getPathFor('pci')), true);
      $db_pciids = $vendors->getDbList();
      $pciids = $db_pciids + $file_pciids;
      $GLPI_CACHE->set($vendors->cache_key, $pciids);

      return $pciids;
   }

   /**
    * Get PCIIDs from database
    *
    * @return array
    */
   private function getDbList(): array {
      global $DB;

      $list = [];
      $iterator = $DB->request(['FROM' => $this->getTable()]);
      while ($row = $iterator->next()) {
         $row_key = $row['vendorid'];
         if (!empty($row['deviceid'])) {
            $row_key .= '::' . $row['deviceid'];
         }
         $list[$row_key] = $row['name'];
      }

      return $list;
   }

   public function getListCacheKey(): string {
      return $this->cache_key;
   }

   /**
    * Clean cache
    *
    * @return void
    */
   public function invalidateListCache(): void {
      global $GLPI_CACHE;

      if ($GLPI_CACHE->has($this->cache_key)) {
         $GLPI_CACHE->delete($this->cache_key);
      }
   }

   /**
    * Get manufacturer from vendorid
    *
    * @param string $vendorid Vendor ID to look for
    *
    * @return string|false
    */
   public function getManufacturer($vendorid) {
      $pciids = $this->getList();

      if (isset($pciids[$vendorid])) {
         return $pciids[$vendorid];
      }

      return false;
   }

   /**
    * Get product name from  vendoreid and deviceid
    *
    * @param string $vendorid Vendor ID to look for
    * @param string $deviceid Device ID to look for
    *
    * @return string|false
    */
   public function getProductName($vendorid, $deviceid) {
      $pciids = $this->getList();

      if (isset($pciids[$vendorid . '::' . $deviceid])) {
         return $pciids[$vendorid . '::' . $deviceid];
      }

      return false;
   }
}
