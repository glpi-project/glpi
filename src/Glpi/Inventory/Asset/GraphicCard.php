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

class GraphicCard extends Device
{
    protected $ignored = ['controllers' => null];
    protected $extra_data = ['controllers' => null];

    public function prepare(): array
    {
        $mapping = [
            'name'   => 'designation',
        ];

        foreach ($this->data as $k => &$val) {
            if (property_exists($val, 'name')) {
                foreach ($mapping as $origin => $dest) {
                    if (property_exists($val, $origin)) {
                        $val->$dest = $val->$origin;
                    }
                }

                $this->ignored['controllers'][$val->name] = $val->name;
                $combined_name = null;
                if (isset($val->chipset)) {
                    $this->ignored['controllers'][$val->chipset] = $val->chipset;
                    $combined_name = sprintf('%s [%s]', $val->name, $val->chipset);
                    $this->ignored['controllers'][$combined_name] = $combined_name;
                }

                $val->is_dynamic = 1;

                if (isset($this->extra_data['controllers'])) {
                    $found_controller = false;
                    foreach ((array)$this->extra_data['controllers'] as $controller) {
                        if (
                            property_exists($controller, 'name')
                            && ($controller->name === $val->name
                                || (isset($val->chipset) && $controller->name === $val->chipset)
                                || ($combined_name && $controller->name === $combined_name)
                            )
                        ) {
                            $found_controller = $controller;
                            break;
                        }
                    }

                    if ($found_controller && property_exists($found_controller, 'vendorid')) {
                        $pcivendor = new \PCIVendor();
                        if ($pci_manufacturer = $pcivendor->getManufacturer($found_controller->vendorid)) {
                            $val->manufacturers_id = $pci_manufacturer;
                        }
                    }
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
