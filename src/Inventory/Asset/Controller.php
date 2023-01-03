<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace Glpi\Inventory\Asset;

use CommonDBTM;
use Glpi\Inventory\Conf;

class Controller extends Device
{
    protected $extra_data = ['ignored' => null];

    public function prepare(): array
    {
        $mapping = [
            'name'          => 'designation',
            'manufacturer'  => 'manufacturers_id',
            'type'          => 'interfacetypes_id',
            'model'         => 'devicecontrolmodels_id'
        ];
        $pcivendor = new \PCIVendor();

        foreach ($this->data as $k => &$val) {
            if (property_exists($val, 'name')) {
                foreach ($mapping as $origin => $dest) {
                    if (property_exists($val, $origin)) {
                        $val->$dest = $val->$origin;
                    }
                }
                if (property_exists($val, 'vendorid')) {
                   //manufacturer
                    if ($pci_manufacturer = $pcivendor->getManufacturer($val->vendorid)) {
                        $val->manufacturers_id = $pci_manufacturer;
                        if (property_exists($val, 'productid')) {
                          //product name
                            if ($pci_product = $pcivendor->getProductName($val->vendorid, $val->productid)) {
                                $val->designation = $pci_product;
                            }
                        }
                    }
                }
                $val->is_dynamic = 1;
            } else {
                unset($this->data[$k]);
            }
        }
        return $this->data;
    }

    public function handle()
    {
        $data = $this->data;

        foreach ($data as $k => $asset) {
            if (property_exists($asset, 'name') && isset($this->extra_data['ignored'][$asset->name])) {
                unset($data[$k]);
            }
        }

        $this->data = $data;
        parent::handle();
    }

    public function checkConf(Conf $conf): bool
    {
        return $conf->component_control == 1;
    }

    public function getItemtype(): string
    {
        return \Item_DeviceControl::class;
    }
}
