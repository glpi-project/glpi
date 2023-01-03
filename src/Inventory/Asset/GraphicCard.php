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

class GraphicCard extends Device
{
    protected $ignored = ['controllers' => null];

    public function prepare(): array
    {
        $mapping = [
            'name'   => 'designation'
        ];

        foreach ($this->data as $k => &$val) {
            if (property_exists($val, 'name')) {
                foreach ($mapping as $origin => $dest) {
                    if (property_exists($val, $origin)) {
                        $val->$dest = $val->$origin;
                    }
                }

                $this->ignored['controllers'][$val->name] = $val->name;
                if (isset($val->chipset)) {
                    $this->ignored['controllers'][$val->chipset] = $val->chipset;
                }

                $val->is_dynamic = 1;
            } else {
                unset($this->data[$k]);
            }
        }
        return $this->data;
    }

    public function checkConf(Conf $conf): bool
    {
        return $conf->component_graphiccard == 1;
    }

    public function getItemtype(): string
    {
        return \Item_DeviceGraphicCard::class;
    }
}
