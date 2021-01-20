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

/// Class USBVendor
class USBVendor extends CommonDropdown implements CacheableListInterface {
   public $cache_key = 'glpi_usbvendors';

   static function getTypeName($nb = 0) {
      return _n('USB vendor', 'USB vendors', $nb);
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
    * Get list of all known USBIDs
    *
    * @return array
    */
   public static function getList(): array {
      global $GLPI_CACHE;

      $vendors = new USBVendor();
      if (($usbids = $GLPI_CACHE->get($vendors->cache_key)) !== null) {
         return $usbids;
      }

      $jsonfile = new FilesToJSON();
      $file_usbids = json_decode(file_get_contents($jsonfile->getJsonFilePath('usb')), true);
      $db_usbids = $vendors->getDbList();
      $usbids = $db_usbids + $file_usbids;
      $GLPI_CACHE->set($vendors->cache_key, $usbids);

      return $usbids;
   }

   /**
    * Get USBIDs from database
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
      $usbids = $this->getList();

      if (isset($usbids[$vendorid])) {
         $usb_manufacturer = preg_replace('/&(?!\w+;)/', '&amp;', $usbids[$vendorid]);
         if (!empty($usb_manufacturer)) {
            return $usb_manufacturer;
         }
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
      $usbids = $this->getList();

      if (isset($usbids[$vendorid . '::' . $deviceid])) {
         $usb_product = preg_replace('/&(?!\w+;)/', '&amp;', $usbids[$vendorid . '::' . $deviceid]);
         if (!empty($usb_product)) {
            return $usb_product;
         }
      }

      return false;
   }
}
