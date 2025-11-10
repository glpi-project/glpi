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
use ItemAntivirus;
use RuntimeException;

class Antivirus extends InventoryAsset
{
    public function prepare(): array
    {
        global $CFG_GLPI;

        if (!in_array($this->item->getType(), $CFG_GLPI['itemantivirus_types'])) {
            throw new RuntimeException('Antivirus are not handled for ' . $this->item->getType());
        }
        $mapping = [
            'company'      => 'manufacturers_id',
            'version'      => 'antivirus_version',
            'base_version' => 'signature_version',
            'enabled'      => 'is_active',
            'uptodate'     => 'is_uptodate',
            'expiration'   => 'date_expiration',
        ];

        foreach ($this->data as &$val) {
            foreach ($mapping as $origin => $dest) {
                if (property_exists($val, $origin)) {
                    $val->$dest = $val->$origin;
                }
            }

            if (!property_exists($val, 'antivirus_version')) {
                $val->antivirus_version = '';
            }

            if (!property_exists($val, 'is_active') || empty($val->is_active)) {
                $val->is_active = 0;
            }

            if (!property_exists($val, 'is_uptodate') || empty($val->is_uptodate)) {
                $val->is_uptodate = 0;
            } else {
                $val->is_uptodate = (int) $val->is_uptodate;
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
            'SELECT' => ['id', 'name', 'antivirus_version', 'is_dynamic'],
            'FROM'   => ItemAntivirus::getTable(),
            'WHERE'  => [
                'itemtype' => $this->item->getType(),
                'items_id' => $this->item->fields['id'],
            ],
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
        $itemAntivirus = new ItemAntivirus();

        //check for existing
        foreach ($value as $k => $val) {
            $compare = ['name' => $val->name, 'antivirus_version' => $val->antivirus_version];
            $compare = array_map('strtolower', $compare);
            foreach ($db_antivirus as $keydb => $arraydb) {
                unset($arraydb['is_dynamic']);
                if ($compare == $arraydb) {
                    $itemAntivirus->getFromDB($keydb);
                    $input = $this->handleInput($val, $itemAntivirus) + [
                        'id'           => $keydb,
                    ];
                    $itemAntivirus->update($input);
                    unset($value[$k]);
                    unset($db_antivirus[$keydb]);
                    break;
                }
            }
        }

        if (count($db_antivirus) !== 0) {
            foreach ($db_antivirus as $idtmp => $data) {
                if ($data['is_dynamic'] == 1) {
                    $itemAntivirus->delete(['id' => $idtmp], true);
                }
            }
        }

        if (count($value) != 0) {
            foreach ($value as $val) {
                $val->itemtype = $this->item->getType();
                $val->items_id = $this->item->fields['id'];
                $val->is_dynamic = 1;
                $input = $this->handleInput($val, $itemAntivirus);
                $itemAntivirus->add($input);
            }
        }
    }

    public function checkConf(Conf $conf): bool
    {
        global $CFG_GLPI;
        return $conf->import_antivirus == 1 && in_array($this->item::class, $CFG_GLPI['itemantivirus_types']);
    }

    public function getItemtype(): string
    {
        return ItemAntivirus::class;
    }
}
