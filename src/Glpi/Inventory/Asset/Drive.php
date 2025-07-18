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

use Glpi\Inventory\Conf;
use Item_DeviceDrive;

use function Safe\preg_match;

class Drive extends Device
{
    /** @var Conf */
    private Conf $conf;

    private $harddrives;
    private $prepared_harddrives = [];

    public function prepare(): array
    {
        $mapping = [
            'name'         => 'designation',
            'type'         => 'interfacetypes_id',
            'manufacturer' => 'manufacturers_id',
        ];

        $hdd = [];
        foreach ($this->data as $k => &$val) {
            if ($this->isDrive($val)) { // it's cd-rom / dvd
                foreach ($mapping as $origin => $dest) {
                    if (property_exists($val, $origin)) {
                        $val->$dest = $val->$origin;
                    }
                }

                if (property_exists($val, 'description')) {
                    $val->designation = $val->description;
                }

                $val->is_dynamic = 1;
            } else { // it's harddisk
                $hdd[] = $val;
                unset($this->data[$k]);
            }
        }
        if (count($hdd)) {
            $this->harddrives = new HardDrive($this->item);
            if ($this->harddrives->checkConf($this->conf)) {
                $this->harddrives->setData($hdd);
                $prep_hdds = $this->harddrives->prepare();
                if (defined('TU_USER')) {
                    $this->prepared_harddrives = $prep_hdds;
                }
            }
        }

        return $this->data;
    }

    /**
     * Is current data a drive
     *
     * @return boolean
     */
    public function isDrive($data)
    {
        $drives_regex = [
            'rom',
            'dvd',
            'blu[\s-]*ray',
            'reader',
            'sd[\s-]*card',
            'micro[\s-]*sd',
            'mmc',
        ];

        foreach ($drives_regex as $regex) {
            foreach (['type', 'model', 'name'] as $field) {
                if (
                    property_exists($data, $field)
                    && !empty($data->$field)
                    && preg_match("/" . $regex . "/i", $data->$field)
                ) {
                    return true;
                }
            }
        }

        return false;
    }
    public function handle()
    {
        parent::handle();
        if ($this->harddrives !== null) {
            $this->harddrives->handleLinks();
            $this->harddrives->handle();
        }
    }

    public function checkConf(Conf $conf): bool
    {
        $this->conf = $conf;
        return $conf->component_drive == 1 && parent::checkConf($conf);
    }

    /**
     * Get harddrives data
     *
     * @return HardDrive[]
     */
    public function getPreparedHarddrives(): array
    {
        return $this->prepared_harddrives;
    }

    public function getItemtype(): string
    {
        return Item_DeviceDrive::class;
    }
}
