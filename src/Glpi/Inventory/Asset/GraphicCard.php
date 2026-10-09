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

use function Safe\preg_match;

class GraphicCard extends Device
{
    protected array $ignored = ['controllers' => null];
    protected array $extra_data = ['controllers' => null];

    public function prepare(): array
    {
        $mapping = [
            'name'   => 'designation',
        ];

        foreach ($this->data as $k => &$val) {
            /** @var \stdClass $val */
            if (property_exists($val, 'name')) {
                foreach ($mapping as $origin => $dest) {
                    if (property_exists($val, $origin)) {
                        $val->$dest = $val->$origin;
                    }
                }

                $val->is_dynamic = 1;

                if (isset($this->extra_data['controllers'])) {
                    $found_controller = false;
                    $controllers = is_array($this->extra_data['controllers']) ? $this->extra_data['controllers'] : [$this->extra_data['controllers']];
                    foreach ($controllers as $controller) {
                        /** @var \stdClass $controller */
                        if (
                            (property_exists($controller, 'pcislot') && property_exists($val, 'pcislot') && $controller->pcislot === $val->pcislot)
                            || (property_exists($controller, 'type') && $controller->type === $val->name)
                            || (property_exists($controller, 'name') && isset($val->chipset) && $controller->name === $val->chipset)
                        ) {
                            $found_controller = $controller;
                            break;
                        }
                    }

                    if ($found_controller) {
                        if (property_exists($found_controller, 'name')) {
                            $this->ignored['controllers'][$found_controller->name] = $found_controller->name;
                            $val->name = $found_controller->name;
                        } elseif (property_exists($found_controller, 'caption')) {
                            $val->name = $found_controller->caption;
                        }

                        if ($this->applyPciInfoFromController($val, $found_controller)) {
                            $val->devicegraphiccardmodels_id = $val->designation;
                            $val->name = $val->designation;
                        }

                        if (preg_match('/^(.*)\s+\[(.*)\]$/', $val->name, $matches)) {
                            $val->name = trim($matches[1]);
                            $val->chipset = trim($matches[2]);
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
