<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
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
   public function prepare() :array {
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
      }

      return $this->data;
   }

   /**
    * Get existing entries from database
    *
    * @return array
    */
   protected function getExisting(): array {
      global $DB;

      $db_existing = [];

      $iterator = $DB->request([
         'SELECT' => [
            'id'
         ],
         'FROM'   => GDatabaseInstance::getTable(),
         'WHERE'  => [
            'is_dynamic'   => 1
         ]
      ]);

      while ($row = $iterator->next()) {
         $db_existing[$row['id']] = $row['id'];
      }

      return $db_existing;
   }

   public function handle() {
      global $DB;

      $rule = new RuleImportAssetCollection();
      $value = $this->data;
      $instance = new GDatabaseInstance();
      $dbitem = new \DatabaseInstance_Item();
      $odatabase = new \Database();

      $db_instances = $this->getExisting();

      $instances = [];

      foreach ($value as $key => $val) {
         $input = [
            'itemtype'     => 'DatabaseInstance',
            'name'         => $val->name ?? '',
            'entities_id'  => $this->item->fields['entities_id']
         ];
         $data = $rule->processAllRules($input, [], ['class' => $this, 'return' => true]);

         if (isset($data['found_inventories'])) {
            $databases = $val->databases ?? [];
            $input = (array)$val;
            $existing_databases = [];

            $items_id = null;
            $itemtype = 'DatabaseInstance';
            if ($data['found_inventories'][0] == 0) {
               // add instance
               $input['is_dynamic'] = 1;
               $input['entities_id'] = $this->entities_id;
               $items_id = $instance->add(Toolbox::addslashes_deep($input), [], $this->withHistory());
            } else {
               $items_id = $data['found_inventories'][0];
               $databases = $val->databases ?? [];

               $instance->getFromDB($items_id);
               $input += ['id' => $instance->fields['id']];
               $instance->update($input, $this->withHistory());

               $existing_databases = $instance->getDatabases();
               //update databases, relying on name
               foreach ($existing_databases as $dbkey => $existing_database) {
                  foreach ($databases as $key => $database) {
                     if ($existing_database['name'] == $database->name) {
                        $dbinput = (array)$database;
                        $dbinput += ['id' => $dbkey, 'is_deleted' => 0];
                        $odatabase->update(Toolbox::addslashes_deep($dbinput), [], $this->withHistory());
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
                  foreach ($existing_databases as $existing_database) {
                     $odatabase->delete(['id' => $existing_database['id']], false, $this->withHistory());
                  }
               }
            }

            //create new databases
            foreach ($databases as $database) {
               $dbinput = (array)$database;
               $dbinput += [
                  'is_dynamic' => 1,
                  'databaseinstances_id' => $instance->fields['id']
               ];
               $odatabase->add(Toolbox::addslashes_deep($dbinput), [], $this->withHistory());
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
         foreach ($db_instances as $keydb) {
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
            $instance->delete(['id' => $idtmp]);
         }
      }

      if (count($instances) && $this->item) {
         foreach ($instances as $instances_id) {
            //link with main item
            $dbitem->add(
               [
                  'databaseinstances_id' => $instances_id,
                  'itemtype' => $this->item->getType(),
                  'items_id' => $this->item->fields['id']
               ],
               $this->withHistory()
            );
         }
      }
   }

   public function checkConf(Conf $conf): bool {
      return true;
   }
}
