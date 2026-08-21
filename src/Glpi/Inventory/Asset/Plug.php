<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
use Plug as GlobalPlug;

class Plug extends InventoryAsset
{
    public function prepare(): array
    {
        $plug_list = [];
        foreach ($this->data as &$val) {
            foreach ($val as $plug_values) {
                if (property_exists($plug_values, 'type')) {
                    $plug_values->plugtypes_id = $plug_values->type;
                    unset($plug_values->type);
                }
                $plug_list[] = $plug_values;
            }
        }

        $this->data = $plug_list;
        return $this->data;
    }

    public function checkConf(Conf $conf): bool
    {
        // Parent (PDU) should return false if needed (and not handle Plug)
        return true;
    }

    public function getItemtype(): string
    {
        return GlobalPlug::class;
    }


    /**
     * Resolve FK string values that handleLinks() may have skipped for locked fields.
     *
     * handleLinks() skips locked FK fields when the parent item is not new (i.e. an existing PDU).
     * For newly created Plugs we still need the integer ID, so resolve any remaining strings.
     */
    private function resolveInput(array $input): array
    {
        if (isset($input['plugtypes_id']) && !is_numeric($input['plugtypes_id'])) {
            $input['plugtypes_id'] = \Dropdown::importExternal('PlugType', (string) $input['plugtypes_id'], $this->entities_id);
        }
        return $input;
    }

    public function handle(): void
    {

        // load all plug from DB
        $plug = new GlobalPlug();
        $db_plugs = $plug->find([
            'itemtype_main' => $this->item::class,
            'items_id_main' => $this->item->fields['id'],
        ]);

        // handle each plug from inventory
        foreach ($this->data as $val) {
            $name = (string) ($val->name ?? $val->number ?? ''); // rely to number as Glpi-Agent
            $val->is_dynamic              = 1;
            $val->autoupdatesystems_id    = $this->item->fields['autoupdatesystems_id'];
            $val->entities_id             = $this->item->fields['entities_id'];
            $val->is_recursive            = $this->item->fields['is_recursive'];
            $val->itemtype_main           = $this->item::class;
            $val->items_id_main           = $this->item->fields['id'];
            $found_key = null;

            // keep key if exist from DB
            foreach ($db_plugs as $key => $db_plug) {
                if ($db_plug['name'] === $name) {
                    $found_key = $key;
                    break;
                }
            }

            // if found update it
            if ($found_key !== null) {
                $plug->getFromDB($found_key);
                $plug->update($this->resolveInput($this->handleInput($val, $plug)) + ['id' => $found_key]);
                unset($db_plugs[$found_key]);
            } else { // else add plug to current PDU
                $plug->reset();
                $plug->add($this->resolveInput($this->handleInput($val, $plug)));
            }
        }

        // clean obsolete plugs if needed
        foreach ($db_plugs as $db_plug) {
            if ($db_plug['is_dynamic']) {
                $plug->delete([
                    'id' => $db_plug['id'],
                ], true);
            }
        }
    }

}
