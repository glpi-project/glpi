<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

use Glpi\Features\CacheableListInterface;
use Glpi\Inventory\FilesToJSON;

/// Class USBVendor
class USBVendor extends CommonDropdown implements CacheableListInterface
{
    public $cache_key = 'glpi_usbvendors';

    public static function getTypeName($nb = 0)
    {
        return _n('USB vendor', 'USB vendors', $nb);
    }

    public function getAdditionalFields()
    {
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

    public function rawSearchOptions()
    {
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
    public static function getList(): array
    {
        global $GLPI_CACHE;

        $vendors = new USBVendor();
        if (($usbids = $GLPI_CACHE->get($vendors->cache_key)) !== null) {
            return $usbids;
        }

        $jsonfile = new FilesToJSON();
        $file_usbids = json_decode(file_get_contents($jsonfile->getJsonFilePath('usbid')), true);
        $db_usbids = $vendors->getDbList();
        $usbids = $db_usbids + $file_usbids;
        $usbids = array_change_key_case($usbids, CASE_LOWER);
        $GLPI_CACHE->set($vendors->cache_key, $usbids);

        return $usbids;
    }

    /**
     * Get USBIDs from database
     *
     * @return array
     */
    private function getDbList(): array
    {
        global $DB;

        $list = [];
        $iterator = $DB->request(['FROM' => $this->getTable()]);
        foreach ($iterator as $row) {
            $row_key = $row['vendorid'];
            if (!empty($row['deviceid'])) {
                $row_key .= '::' . $row['deviceid'];
            }
            $list[$row_key] = $row['name'];
        }

        return $list;
    }

    public function getListCacheKey(): string
    {
        return $this->cache_key;
    }

    /**
     * Clean cache
     *
     * @return void
     */
    public function invalidateListCache(): void
    {
        global $GLPI_CACHE;

        $GLPI_CACHE->delete($this->cache_key);
    }

    /**
     * Get manufacturer from vendorid
     *
     * @param string $vendorid Vendor ID to look for
     *
     * @return string|false
     */
    public function getManufacturer($vendorid)
    {
        $usbids = $this->getList();

        $vendorid = strtolower($vendorid);

        if (isset($usbids[$vendorid])) {
            $usb_manufacturer = preg_replace('/&(?!\w+;)/', '&amp;', $usbids[$vendorid]);
            if (!empty($usb_manufacturer)) {
                return $usb_manufacturer;
            }
        }

        return false;
    }

    /**
     * Get product name from  vendorid and deviceid
     *
     * @param string $vendorid Vendor ID to look for
     * @param string $deviceid Device ID to look for
     *
     * @return string|false
     */
    public function getProductName($vendorid, $deviceid)
    {
        $usbids = $this->getList();

        $vendorid = strtolower($vendorid);
        $deviceid = strtolower($deviceid);

        if (isset($usbids[$vendorid . '::' . $deviceid])) {
            $usb_product = preg_replace('/&(?!\w+;)/', '&amp;', $usbids[$vendorid . '::' . $deviceid]);
            if (!empty($usb_product)) {
                return $usb_product;
            }
        }

        return false;
    }

    public static function getIcon()
    {
        return "ti ti-usb";
    }
}
