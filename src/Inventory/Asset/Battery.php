<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @copyright 2010-2022 by the FusionInventory Development Team.
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

class Battery extends Device
{
    public function prepare(): array
    {
        $mapping = [
            'name'         => 'designation',
            'manufacturer' => 'manufacturers_id',
            'serial'       => 'serial',
            'date'         => 'manufacturing_date',
            'capacity'     => 'capacity',
            'chemistry'    => 'devicebatterytypes_id',
            'voltage'      => 'voltage'
        ];

        foreach ($this->data as &$val) {
            foreach ($mapping as $origin => $dest) {
                if (property_exists($val, $origin)) {
                    $val->$dest = $val->$origin;
                }
            }

            if (!isset($val->voltage) || $val->voltage == '') {
               //a numeric value is expected here
                $val->voltage = 0;
            }

            if (!isset($val->capacity) || $val->capacity == '') {
                $val->capacity = 0;
            }

            $val->is_dynamic = 1;
        }
        return $this->data;
    }

    public function checkConf(Conf $conf): bool
    {
        return $conf->component_battery == 1;
    }

    public function getItemtype(): string
    {
        return \Item_DeviceBattery::class;
    }
}
