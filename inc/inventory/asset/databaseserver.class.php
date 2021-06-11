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

use DatabaseServer as GDatabaseServer;
use DatabaseServerInstance;
use Glpi\Inventory\Conf;
use RuleImportAssetCollection;
use RuleMatchedLog;
use Toolbox;

class DatabaseServer extends InventoryAsset
{
   public function prepare() :array {
      $mapping = [
         'type' => 'databaseservertypes_id',
         'manufacturer' => 'manufacturers_id',
         'port' => 'instance_port',
         'size' => 'instance_size',
         'is_onbackup' => 'instance_is_onbackup'
      ];

      foreach ($this->data as &$val) {
         foreach ($mapping as $origin => $dest) {
            if (property_exists($val, $origin)) {
               $val->$dest = $val->$origin;
            }
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
         'FROM'   => GDatabaseServer::getTable(),
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
      $database = new GDatabaseServer();
      $dbitem = new \DatabaseServer_Item();
      $dbinstance = new DatabaseServerInstance();

      $db_servers = $this->getExisting();

      $servers = [];

      foreach ($value as $key => $val) {
         $input = [
            'itemtype'     => 'DatabaseServer',
            'name'         => $val->name ?? '',
            'entities_id'  => $this->item->fields['entities_id']
         ];
         $data = $rule->processAllRules($input, [], ['class' => $this, 'return' => true]);

         if (isset($data['found_inventories'])) {
            $instances = [];
            $existing_instances = [];

            $items_id = null;
            $itemtype = 'DatabaseServer';
            if ($data['found_inventories'][0] == 0) {
               // add server
               $val->is_dynamic = 1;
               $val->entities_id = $this->entities_id;

               $instances = $val->instances ?? [];
               $input = (array)$val;

               $default_inst = null;
               if (count($instances)) {
                  //GLPI will create a default instance if nothing provided adding server
                  $default_inst = array_pop($instances);
                  $input += [
                     '_instance_name' => $default_inst->name,
                     '_instance_port' => $default_inst->port,
                     '_instance_size' => $default_inst->size ?? null,
                     '_instance_is_onbackup' => ($default_inst->is_onbackup ?? false ? 1 : 0),
                     '_instance_is_active' => ($default_inst->is_active ?? false ? 1 : 0),
                     '_instance_date_lastboot' => $default_inst->date_lastboot ?? null,
                     '_instance_date_lastbackup' => $default_inst->date_lastbackup ?? null
                  ];
               }
               $items_id = $database->add(Toolbox::addslashes_deep($input), [], $this->withHistory());

            } else {
               $items_id = $data['found_inventories'][0];
               $instances = $val->instances ?? [];

               $database->getFromDB($items_id);
               $input = (array)$val + ['id' => $database->fields['id']];
               $database->update($input, $this->withHistory());

               $existing_instances = $database->getInstances();
               //update, relying on instance name
               foreach ($existing_instances as $dbkey => $existing_instance) {
                  foreach ($instances as $key => $instance) {
                     if ($existing_instance['name'] == $instance->name) {
                        $instinput = (array)$instance;
                        $instinput += ['id' => $dbkey];
                        $dbinstance->update(Toolbox::addslashes_deep($instinput), [], $this->withHistory());
                        unset(
                           $existing_instances[$dbkey],
                           $instances[$key]
                        );
                        break;
                     }
                  }
               }

               if (count($existing_instances)) {
                  foreach ($existing_instances as $existing_instance) {
                     $dbinstance->delete(['id' => $existing_instance['id']], true, $this->withHistory());
                  }
               }
            }

            //create instances
            foreach ($instances as $instance) {
               $instinput = (array)$instance;
               $instinput += [
                  'is_dynamic' => 1,
                  'databaseservers_id' => $database->fields['id']
               ];
               $dbinstance->add(Toolbox::addslashes_deep($instinput), [], $this->withHistory());
            }

            $servers[$items_id] = $items_id;
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

      if (count($db_servers) && count($servers)) {
         foreach ($db_servers as $keydb) {
            foreach ($servers as $key) {
               if ($key == $keydb) {
                  unset($servers[$key]);
                  unset($db_servers[$keydb]);
                  break;
               }
            }
         }
      }

      if (count($db_servers) != 0) {
         //remove no longer existing databases
         foreach ($db_servers as $idtmp => $data) {
            $database->delete(['id' => $idtmp], 1);
         }
      }

      if (count($servers) && $this->item) {
         foreach ($servers as $servers_id) {
            //link with main item
            $dbitem->add(
               [
                  'databaseservers_id' => $servers_id,
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
