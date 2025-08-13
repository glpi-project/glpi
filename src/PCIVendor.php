<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Psr\SimpleCache\InvalidArgumentException;

use function Safe\file_get_contents;
use function Safe\json_decode;

/**
 * PCIVendor class
 */
class PCIVendor extends CommonDropdown implements CacheableListInterface
{
    public string $cache_key = 'glpi_pcivendors';

    public static function getTypeName($nb = 0)
    {
        return _n('PCI vendor', 'PCI vendors', $nb);
    }

    public function getAdditionalFields()
    {
        return [
            [
                'name'   => 'vendorid',
                'label'  => __('Vendor ID'),
                'type'   => 'text',
            ], [
                'name'  => 'deviceid',
                'label' => __('Device ID'),
                'type'  => 'text',
            ],
        ];
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '10',
            'table'              => static::getTable(),
            'field'              => 'vendorid',
            'name'               => __('Vendor ID'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => static::getTable(),
            'field'              => 'deviceid',
            'name'               => __('Device ID'),
            'datatype'           => 'string',
        ];

        return $tab;
    }

    /**
     * Get list of all known PCIIDs
     *
     * @return array
     */
    public static function getList(): array
    {
        global $GLPI_CACHE;

        $vendors = new PCIVendor();
        if (($pciids = $GLPI_CACHE->get($vendors->cache_key)) !== null) {
            return $pciids;
        }

        $jsonfile = new FilesToJSON();
        $file_pciids = json_decode(file_get_contents($jsonfile->getJsonFilePath('pciid')), true) ?? [];
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
    private function getDbList(): array
    {
        global $DB;

        $list = [];
        $iterator = $DB->request(['FROM' => static::getTable()]);
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
     * @throws InvalidArgumentException
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
    public function getManufacturer($vendorid): false|string
    {
        $pciids = self::getList();

        return $pciids[$vendorid] ?? false;
    }

    /**
     * Get product name from  vendoreid and deviceid
     *
     * @param string $vendorid Vendor ID to look for
     * @param string $deviceid Device ID to look for
     *
     * @return string|false
     */
    public function getProductName($vendorid, $deviceid): false|string
    {
        $pciids = self::getList();

        return $pciids[$vendorid . '::' . $deviceid] ?? false;
    }

    public static function getIcon()
    {
        return "fas fa-memory";
    }
}
