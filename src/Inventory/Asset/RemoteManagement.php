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
use Glpi\Toolbox\Sanitizer;
use Item_RemoteManagement;

class RemoteManagement extends InventoryAsset
{
    public function prepare(): array
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!in_array($this->item->getType(), $CFG_GLPI['remote_management_types'])) {
            throw new \RuntimeException(
                'Remote Management are handled for following types only: ' .
                implode(', ', $CFG_GLPI['remote_management_types'])
            );
        }

        $mapping = [
            'id'      => 'remoteid',
        ];

        foreach ($this->data as &$val) {
            foreach ($mapping as $origin => $dest) {
                if (property_exists($val, $origin)) {
                    $val->$dest = $val->$origin;
                }
            }

            unset($val->id);
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
        /** @var \DBmysql $DB */
        global $DB;

        $db_existing = [];

        $iterator = $DB->request([
            'SELECT' => ['id', 'remoteid', 'type', 'is_dynamic'],
            'FROM'   => Item_RemoteManagement::getTable(),
            'WHERE'  => [
                'itemtype' => $this->item->getType(),
                'items_id' => $this->item->fields['id']
            ]
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
        $db_mgmt = $this->getExisting();
        $value = $this->data;
        $mgmt = new Item_RemoteManagement();

        foreach ($value as $k => $val) {
            $compare = ['remoteid' => $val->remoteid, 'type' => $val->type];
            $compare = array_map('strtolower', $compare);
            foreach ($db_mgmt as $keydb => $arraydb) {
                unset($arraydb['is_dynamic']);
                if ($compare == $arraydb) {
                    $input = (array)$val + [
                        'id'           => $keydb
                    ];
                    $mgmt->update(Sanitizer::sanitize($input));
                    unset($value[$k]);
                    unset($db_mgmt[$keydb]);
                    break;
                }
            }
        }

        if (!$this->main_asset || !$this->main_asset->isPartial()) {
            foreach ($db_mgmt as $idtmp => $data) {
                if ($data['is_dynamic']) {
                    $mgmt->delete(['id' => $idtmp], true);
                }
            }
        }

        foreach ($value as $val) {
            $val->itemtype = $this->item->getType();
            $val->items_id = $this->item->fields['id'];
            $val->is_dynamic = 1;
            $mgmt->add(Sanitizer::sanitize((array)$val));
        }
    }

    public function checkConf(Conf $conf): bool
    {
        return true;
    }

    public function getItemtype(): string
    {
        return \Item_RemoteManagement::class;
    }
}
