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

namespace Glpi\Inventory\Asset;

use Glpi\Inventory\Conf;
use Item_DeviceProcessor;

class Processor extends Device
{
    public function prepare(): array
    {
        $mapping = [
            'speed'        => 'frequency',
            'manufacturer' => 'manufacturers_id',
            'serial'       => 'serial',
            'name'         => 'designation',
            'core'         => 'nbcores',
            'thread'       => 'nbthreads',
            'id'           => 'internalid',
        ];
        foreach ($this->data as &$val) {
            foreach ($mapping as $origin => $dest) {
                if (property_exists($val, $origin)) {
                    $val->$dest = $val->$origin;
                }
            }
            if (property_exists($val, 'frequency')) {
                $val->frequency_default = $val->frequency;
                $val->frequence = $val->frequency;
            } else {
                $val->frequency_default = 0;
                $val->frequency = 0;
                $val->frequence = 0;
            }
            if (property_exists($val, 'type')) {
                $val->designation = $val->type;
            }
            unset($val->id);
            $val->is_dynamic = 1;
        }
        return $this->data;
    }

    public function checkConf(Conf $conf): bool
    {
        return $conf->component_processor == 1 && parent::checkConf($conf);
    }

    public function getItemtype(): string
    {
        return Item_DeviceProcessor::class;
    }
}
