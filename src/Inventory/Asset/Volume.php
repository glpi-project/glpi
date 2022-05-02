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
use Item_Disk;
use Toolbox;

class Volume extends InventoryAsset
{
    /** @var @var Conf */
    private $conf;

    public function prepare(): array
    {
        $mapping = [
            'volumn'         => 'device',
            'filesystem'     => 'filesystems_id',
            'total'          => 'totalsize',
            'free'           => 'freesize',
            'encrypt_name'   => 'encryption_tool',
            'encrypt_algo'   => 'encryption_algorithm',
            'encrypt_status' => 'encryption_status',
            'encrypt_type'   => 'encryption_type'
        ];

        foreach ($this->data as $key => &$val) {
            foreach ($mapping as $origin => $dest) {
                if (property_exists($val, $origin)) {
                    $val->$dest = $val->$origin;
                }
            }

           //check if type should be imported
            if (
                $this->isNetworkDrive($val) && $this->conf->component_networkdrive != 1
                || $this->isRemovableDrive($val) && $this->conf->component_removablemedia != 1
                || $this->conf->import_volume != 1
            ) {
                unset($this->data[$key]);
                continue;
            }

            if (property_exists($val, 'label') && !empty($val->label)) {
                $val->name = $val->label;
            } else if (
                (!property_exists($val, 'volumn') || empty($val->volumn))
                  && property_exists($val, 'letter')
            ) {
                $val->name = $val->letter;
            } else if (property_exists($val, 'type')) {
                $val->name = $val->type;
            } else if (property_exists($val, 'volumn')) {
                $val->name = $val->volumn;
            }

            if (!property_exists($val, 'mountpoint')) {
                if (property_exists($val, 'letter')) {
                    $val->mountpoint = $val->letter;
                } else if (property_exists($val, 'type')) {
                    $val->mountpoint = $val->type;
                }
            }

            if (property_exists($val, 'encryption_status')) {
               //Encryption status
                if ($val->encryption_status == "Yes") {
                    $val->encryption_status = Item_Disk::ENCRYPTION_STATUS_YES;
                } else if ($val->encryption_status == "Partially") {
                    $val->encryption_status = Item_Disk::ENCRYPTION_STATUS_PARTIALLY;
                } else {
                    $val->encryption_status = Item_Disk::ENCRYPTION_STATUS_NO;
                }
            }

            $val->is_dynamic = 1;
        }

        return $this->data;
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
            'SELECT' => ['id', 'name', 'device', 'mountpoint', 'is_dynamic'],
            'FROM'   => Item_Disk::getTable(),
            'WHERE'  => [
                'items_id' => $this->item->fields['id'],
                'itemtype' => $this->item->getType()
            ]
        ]);
        foreach ($iterator as $data) {
            $dbid = $data['id'];
            unset($data['id']);
            $db_existing[$dbid] = [];
            foreach ($data as $key => $value) {
                $db_existing[$dbid][$key] = $value !== null ? strtolower($value) : null;
            }
        }

        return $db_existing;
    }

    public function handle()
    {
        $itemDisk = new Item_Disk();
        $db_itemdisk = $this->getExisting();

        $value = $this->data;
        foreach ($value as $key => $val) {
            $db_elt = [];
            foreach (['name', 'device', 'mountpoint'] as $field) {
                $db_elt[$field] = (property_exists($val, $field) ? strtolower($val->$field) : null);
            }

            foreach ($db_itemdisk as $keydb => $arraydb) {
                unset($arraydb['is_dynamic']);
                if ($db_elt == $arraydb) {
                    $input = (array)$val + [
                        'id'           => $keydb,
                    ];
                    $itemDisk->update(Toolbox::addslashes_deep($input));
                    unset($value[$key]);
                    unset($db_itemdisk[$keydb]);
                    break;
                }
            }
        }

        if ((!$this->main_asset || !$this->main_asset->isPartial()) && count($db_itemdisk) != 0) {
           // Delete Item_Disk in DB
            foreach ($db_itemdisk as $dbid => $data) {
                if ($data['is_dynamic'] == 1) {
                    //Delete only dynamics
                    $itemDisk->delete(['id' => $dbid], 1);
                }
            }
        }
        if (count($value)) {
            foreach ($value as $val) {
                $input = (array)$val + [
                    'items_id'     => $this->item->fields['id'],
                    'itemtype'     => $this->item->getType()
                ];

                $itemDisk->add(Toolbox::addslashes_deep($input));
            }
        }
    }

    /**
     * Check if asset is a network drive, based on its filesystem
     *
     * @param \stdClass $raw_data Raw data from inventory
     *
     * @return bool
     */
    public function isNetworkDrive(\stdClass $raw_data): bool
    {
        return strtolower($raw_data->type ?? '') == 'network drive'
         || in_array(strtolower($raw_data->filesystem ?? ''), ['nfs', 'smbfs', 'afpfs']);
    }

    public function isRemovableDrive(\stdClass $raw_data): bool
    {
        return in_array(strtolower($raw_data->type ?? ''), ['removable disk', 'compact disk']);
    }

    public function checkConf(Conf $conf): bool
    {
        $this->conf = $conf;
        return $conf->import_volume == 1;
    }
}
