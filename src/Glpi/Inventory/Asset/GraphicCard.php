<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Glpi\Inventory\Conf;
use Item_DeviceGraphicCard;
use PCIVendor;

class GraphicCard extends Device
{
    protected array $ignored = ['controllers' => null];
    protected array $extra_data = ['controllers' => null];

    public function prepare(): array
    {
        $mapping = [
            'name'   => ['designation', 'devicegraphiccardmodels_id'],
        ];
        $pcivendor = new PCIVendor();

        foreach ($this->data as $k => &$val) {
            if (property_exists($val, 'name')) {
                foreach ($mapping as $origin => $dests) {
                    if (property_exists($val, $origin)) {
                        foreach ((array) $dests as $dest) {
                            $val->$dest = $val->$origin;
                        }
                    }
                }

                $this->ignored['controllers'][$val->name] = $val->name;
                if (isset($val->chipset)) {
                    $this->ignored['controllers'][$val->chipset] = $val->chipset;
                }

                $val->is_dynamic = 1;

                if (isset($this->extra_data['controllers'])) {
                    $found_controller = false;
                    foreach ((array) $this->extra_data['controllers'] as $controller) {
                        if (
                            property_exists($controller, 'name')
                            && (
                                $controller->name === $val->name
                                || (isset($val->chipset) && $controller->name === $val->chipset)
                            )
                        ) {
                            $found_controller = $controller;
                            break;
                        }
                    }

                    if ($found_controller) {
                        if (property_exists($found_controller, 'pciid')) {
                            $exploded = explode(":", $found_controller->pciid);

                            //manufacturer
                            if ($pci_manufacturer = $pcivendor->getManufacturer($exploded[0])) {
                                $val->manufacturers_id = $pci_manufacturer;
                            }

                            //product name
                            if ($pci_product = $pcivendor->getProductName($exploded[0], $exploded[1])) {
                                $val->designation = $pci_product;
                                $val->devicegraphiccardmodels_id = $pci_product;
                            }
                        } elseif (property_exists($found_controller, 'vendorid')) {
                            //manufacturer
                            if ($pci_manufacturer = $pcivendor->getManufacturer($found_controller->vendorid)) {
                                $val->manufacturers_id = $pci_manufacturer;
                            }

                            if (property_exists($found_controller, 'productid')) {
                                //product name
                                if ($pci_product = $pcivendor->getProductName($found_controller->vendorid, $found_controller->productid)) {
                                    $val->designation = $pci_product;
                                    $val->devicegraphiccardmodels_id = $pci_product;
                                }
                            }
                        }
                    }
                }

                // Use caption for designation if available (after PCI lookup, as fallback)
                if (property_exists($val, 'caption') && !empty($val->caption)) {
                    $val->designation = $val->caption;
                }
            } else {
                unset($this->data[$k]);
            }
        }
        return $this->data;
    }

    public function checkConf(Conf $conf): bool
    {
        return $conf->component_graphiccard == 1 && parent::checkConf($conf);
    }

    public function getItemtype(): string
    {
        return Item_DeviceGraphicCard::class;
    }
}
