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

use DatabaseInstance as GDatabaseInstance;
use Glpi\Inventory\Conf;
use RuleImportAssetCollection;
use RuleMatchedLog;
use Toolbox;

class DatabaseInstance extends InventoryAsset
{
    public function prepare(): array
    {
        $mapping = [
            'type' => 'databaseinstancetypes_id',
            'manufacturer' => 'manufacturers_id'
        ];

        foreach ($this->data as &$val) {
            foreach ($mapping as $origin => $dest) {
                if (property_exists($val, $origin)) {
                    $val->$dest = $val->$origin;
                }
            }

            if (property_exists($val, 'is_onbackup')) {
                $val->is_onbackup = $val->is_onbackup ? 1 : 0;
            }

            if (property_exists($val, 'is_active')) {
                $val->is_active = $val->is_active ? 1 : 0;
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
            'SELECT' => [
                'id',
                'name',
                'is_dynamic'
            ],
            'FROM'   => GDatabaseInstance::getTable(),
            'WHERE'  => [
                'itemtype'     => $this->item->getType(),
                'items_id'     => $this->item->fields['id']
            ]
        ]);

        foreach ($iterator as $row) {
            $db_existing[$row['id']] = $row;
        }

        return $db_existing;
    }

    public function handle()
    {
        global $DB;

        $rule = new RuleImportAssetCollection();
        $value = $this->data;
        $instance = new GDatabaseInstance();
        $odatabase = new \Database();

        $db_instances = $this->getExisting();

        $instances = [];

        foreach ($value as $key => $val) {
            $input = [
                'itemtype'     => 'DatabaseInstance',
                'name'         => $val->name ?? '',
                'entities_id'  => $this->item->fields['entities_id'],
                'linked_item' => [
                    'itemtype' => $this->item->getType(),
                    'items_id' => $this->item->fields['id']
                ]
            ];
            $data = $rule->processAllRules($input, [], ['class' => $this, 'return' => true]);

            if (isset($data['found_inventories'])) {
                $databases = $val->databases ?? [];
                $input = (array)$val;

                $items_id = null;
                $itemtype = 'DatabaseInstance';
                if ($data['found_inventories'][0] == 0) {
                    // add instance
                    $input += [
                        'entities_id'  => $this->entities_id,
                        'itemtype'     => $this->item->getType(),
                        'items_id'     => $this->item->fields['id']
                    ];
                    $items_id = $instance->add(Toolbox::addslashes_deep($input));
                } else {
                    $items_id = $data['found_inventories'][0];
                    $databases = $val->databases ?? [];

                    $instance->getFromDB($items_id);
                    $input += ['id' => $instance->fields['id']];
                    $instance->update(Toolbox::addslashes_deep($input));

                    $existing_databases = $instance->getDatabases();
                   //update databases, relying on name
                    foreach ($existing_databases as $dbkey => $existing_database) {
                        foreach ($databases as $key => $database) {
                            if ($existing_database['name'] == $database->name) {
                                 $dbinput = (array)$database;
                                 $dbinput += ['id' => $dbkey, 'is_deleted' => 0, 'is_dynamic' => 1];
                                 $odatabase->update(Toolbox::addslashes_deep($dbinput));
                                 unset(
                                     $existing_databases[$dbkey],
                                     $databases[$key]
                                 );
                                 break;
                            }
                        }
                    }

                   //cleanup associated databases
                    if (count($existing_databases)) {
                        foreach ($existing_databases as $dbkey => $existing_database) {
                            $odatabase->delete(['id' => $dbkey]);
                        }
                    }
                }

               //create new databases
                foreach ($databases as $database) {
                    $dbinput = (array)$database;
                    $dbinput += [
                        'databaseinstances_id' => $instance->fields['id'],
                        'is_dynamic' => 1
                    ];
                    $odatabase->add(Toolbox::addslashes_deep($dbinput));
                }

                $instances[$items_id] = $items_id;
                $rulesmatched = new RuleMatchedLog();
                $agents_id = $this->agent->fields['id'];
                if (empty($agents_id)) {
                    $agents_id = 0;
                }
                $inputrulelog = [
                    'date'      => date('Y-m-d H:i:s'),
                    'rules_id'  => $data['rules_id'],
                    'items_id'  => $items_id,
                    'itemtype'  => $itemtype,
                    'agents_id' => $agents_id,
                    'method'    => 'inventory'
                ];
                $rulesmatched->add($inputrulelog, [], false);
                $rulesmatched->cleanOlddata($items_id, $itemtype);
            }
        }

        if (count($db_instances) && count($instances)) {
            foreach (array_keys($db_instances) as $keydb) {
                foreach ($instances as $key) {
                    if ($key == $keydb) {
                        unset($instances[$key]);
                        unset($db_instances[$keydb]);
                        break;
                    }
                }
            }
        }

        if (count($db_instances) != 0) {
           //remove no longer existing databases
            foreach ($db_instances as $idtmp => $data) {
                if ($data['is_dynamic'] == 1) {
                    $instance->delete(['id' => $idtmp]);
                }
            }
        }
    }

    public function checkConf(Conf $conf): bool
    {
        return true;
    }
}
