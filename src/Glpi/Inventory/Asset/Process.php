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
use Item_Process;

class Process extends InventoryAsset
{
    /** @var Conf */
    private $conf;

    public function prepare(): array
    {
        $mapping = [
            'cmd'           => 'cmd',
            'cpuusage'      => 'cpuusage',
            'mem'           => 'memusage',
            'pid'           => 'pid',
            'started'       => 'started',
            'tty'           => 'tty',
            'user'          => 'user',
            'virtualmemory' => 'virtualmemory',
        ];

        foreach ($this->data as $key => &$val) {
            foreach ($mapping as $origin => $dest) {
                if (property_exists($val, $origin)) {
                    $val->$dest = $val->$origin;
                }
            }

            if (!$this->checkConf($this->conf)) {
                unset($this->data[$key]);
                continue;
            }

            $val->is_dynamic = 1;
        }

        return $this->data;
    }

    protected function getExisting(): array
    {
        global $DB;

        $db_existing = [];

        $iterator = $DB->request([
            'SELECT' => ['id', 'cmd', 'pid', 'is_dynamic'],
            'FROM'   => Item_Process::getTable(),
            'WHERE'  => [
                'items_id' => $this->item->fields['id'],
                'itemtype' => $this->item->getType(),
            ],
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
        $itemProcess = new Item_Process();
        $db_itemProcess = $this->getExisting();

        $value = $this->data;
        foreach ($value as $key => $val) {
            $db_elt = [];
            foreach (['cmd', 'pid'] as $field) {
                $db_elt[$field] = (property_exists($val, $field) ? strtolower($val->$field) : null);
            }

            foreach ($db_itemProcess as $keydb => $arraydb) {
                unset($arraydb['is_dynamic']);
                if ($db_elt == $arraydb) {
                    $input = (array) $val + [
                        'id'           => $keydb,
                    ];
                    $itemProcess->update($input);
                    unset($value[$key]);
                    unset($db_itemProcess[$keydb]);
                    break;
                }
            }
        }

        if ((!$this->main_asset || !$this->main_asset->isPartial()) && count($db_itemProcess) != 0) {
            // Delete Item_Process in DB
            foreach ($db_itemProcess as $dbid => $data) {
                if ($data['is_dynamic'] == 1) {
                    //Delete only dynamics
                    $itemProcess->delete(['id' => $dbid], true);
                }
            }
        }
        if (count($value)) {
            foreach ($value as $val) {
                $input = (array) $val + [
                    'items_id'     => $this->item->fields['id'],
                    'itemtype'     => $this->item->getType(),
                ];

                $itemProcess->add($input);
            }
        }
    }

    public function checkConf(Conf $conf): bool
    {
        global $CFG_GLPI;
        $this->conf = $conf;
        return $conf->import_process == 1 && in_array($this->item::class, $CFG_GLPI['process_types']);
    }

    public function getItemtype(): string
    {
        return Item_Process::class;
    }
}
