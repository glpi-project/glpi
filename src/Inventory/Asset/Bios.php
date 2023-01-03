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
use DeviceFirmwareType;
use Glpi\Inventory\Conf;

class Bios extends Device
{
    public function prepare(): array
    {
        $mapping = [
            'bdate'           => 'date',
            'bversion'        => 'version',
            'bmanufacturer'   => 'manufacturers_id',
            'biosserial'      => 'serial'
        ];

        $val = (object)$this->data;
        foreach ($mapping as $origin => $dest) {
            if (property_exists($val, $origin)) {
                $val->$dest = $val->$origin;
            }
        }

        $val->designation = sprintf(
            __('%1$s BIOS'),
            property_exists($val, 'bmanufacturer') ? $val->bmanufacturer : ''
        );
        $val->devicefirmwaretypes_id = 'BIOS';

        $this->data = [$val];
        return $this->data;
    }

    public function handle()
    {
        if (isset($this->main_item) && $this->main_item->isPartial()) {
            return;
        }

        parent::handle();
    }

    public function checkConf(Conf $conf): bool
    {
        return true;
    }

    public function getItemtype(): string
    {
        return \Item_DeviceFirmware::class;
    }
}
