<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

use AutoUpdateSystem;
use CommonDBTM;
use ComputerAntivirus;
use Glpi\Inventory\Conf;
use Glpi\Toolbox\Sanitizer;
use Unmanaged as GlobalUnmanaged;

class Unmanaged extends MainAsset
{
    public function prepare(): array
    {

        $raw_data = $this->raw_data;
        if (!is_array($raw_data)) {
            $raw_data = [$raw_data];
        }

        $this->data = [];

        foreach ($raw_data as $entry) {

            $val = new \stdClass();

            //set update system
            $val->autoupdatesystems_id = $entry->content->autoupdatesystems_id ?? AutoUpdateSystem::NATIVE_INVENTORY;
            $val->last_inventory_update = $_SESSION["glpi_currenttime"];

            if (isset($this->extra_data['hardware'])) {
                $this->prepareForHardware($val);
            }

            if (isset($this->extra_data['network_device'])) {
                $this->prepareForNetworkDevice($val);
            }

            $this->data[] = $val;
        }

        return $this->data;
    }

    /**
     * Prepare network device information
     *
     * @param stdClass $val
     *
     * @return void
     */
    protected function prepareForNetworkDevice($val)
    {
        $network_device = (object)$this->extra_data['network_device'];
        $nd_mapping = [
            'type'      => 'type',
            'mac'       => 'mac',
            'name'      => 'name',
            'workgroup' => 'domains_id',
            'ips'       => 'ips'
        ];

        foreach ($nd_mapping as $origin => $dest) {
            if (property_exists($network_device, $origin)) {
                $network_device->$dest = $network_device->$origin;
            }
        }

        foreach ($network_device as $key => $property) {
            $val->$key = $property;
        }
    }

    /**
     * Prepare hardware information
     *
     * @param stdClass $val
     *
     * @return void
     */
    protected function prepareForHardware($val)
    {
        $hardware = (object)$this->extra_data['hardware'];
        $hw_mapping = [
            'name'           => 'name',
            'winprodid'      => 'licenseid',
            'winprodkey'     => 'license_number',
            'workgroup'      => 'domains_id',
            'lastloggeduser' => 'users_id',
        ];

        foreach ($hw_mapping as $origin => $dest) {
            if (property_exists($hardware, $origin)) {
                $hardware->$dest = $hardware->$origin;
            }
        }
        $this->hardware = $hardware;

        foreach ($hardware as $key => $property) {
            $val->$key = $property;
        }
    }

    /**
     * Get existing entries from database
     *
     * @return array
     */
    protected function getExisting(): array
    {
        global $DB;

        $db_existing = [];

        $iterator = $DB->request([
            'SELECT' => ['id', 'name', 'antivirus_version', 'is_dynamic'],
            'FROM'   => GlobalUnmanaged::getTable(),
            'WHERE'  => ['name' => $this->item->fields['id']]
        ]);

        foreach ($iterator as $data) {
            $idtmp = $data['id'];
            unset($data['id']);
            $data = array_map('strtolower', $data);
            $db_existing[$idtmp] = $data;
        }

        return $db_existing;
    }

    public function handle()
    {
        $db_antivirus = $this->getExisting();
        $value = $this->data;
        $computerAntivirus = new ComputerAntivirus();

       //check for existing
        foreach ($value as $k => $val) {
            $compare = ['name' => $val->name, 'antivirus_version' => $val->antivirus_version];
            $compare = array_map('strtolower', $compare);
            foreach ($db_antivirus as $keydb => $arraydb) {
                unset($arraydb['is_dynamic']);
                if ($compare == $arraydb) {
                    $computerAntivirus->getFromDB($keydb);
                    $input = $this->handleInput($val, $computerAntivirus) + [
                        'id'           => $keydb
                    ];
                    $computerAntivirus->update(Sanitizer::sanitize($input));
                    unset($value[$k]);
                    unset($db_antivirus[$keydb]);
                    break;
                }
            }
        }

        if ((!$this->main_asset || !$this->main_asset->isPartial()) && count($db_antivirus) !== 0) {
            foreach ($db_antivirus as $idtmp => $data) {
                if ($data['is_dynamic'] == 1) {
                    $computerAntivirus->delete(['id' => $idtmp], true);
                }
            }
        }

        if (count($value) != 0) {
            foreach ($value as $val) {
                $val->computers_id = $this->item->fields['id'];
                $val->is_dynamic = 1;
                $input = $this->handleInput($val, $computerAntivirus);
                $computerAntivirus->add(Sanitizer::sanitize($input));
            }
        }
    }

    public function checkConf(Conf $conf): bool
    {
        return 1;
    }

    protected function getModelsFieldName(): string
    {
        return "";
    }

    protected function getTypesFieldName(): string
    {
        return "";
    }

    public function getItemtype(): string
    {
        return \Unmanaged::class;
    }
}
