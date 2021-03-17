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

use Glpi\Inventory\Conf;
use Item_OperatingSystem;
use RuleDictionnaryOperatingSystemArchitectureCollection;

class OperatingSystem extends InventoryAsset
{
   protected $extra_data = ['hardware' => null];
   private $operatingsystems_id;

   public function prepare() :array {
      $mapping = [
         'name'           => 'operatingsystems_id',
         'version'        => 'operatingsystemversions_id',
         'service_pack'   => 'operatingsystemservicepacks_id',
         'arch'           => 'operatingsystemarchitectures_id',
         'kernel_name'    => 'operatingsystemkernels_id',
         'kernel_version' => 'operatingsystemkernelversions_id'
      ];

      $val = (object)$this->data;
      foreach ($mapping as $origin => $dest) {
         if (property_exists($val, $origin)) {
            $val->$dest = $val->$origin;
         }
      }

      if (isset($this->extra_data['hardware'])) {
         if (property_exists($this->extra_data['hardware'], 'winprodid')) {
            $val->licenseid = $this->extra_data['hardware']->winprodid;
         }

         if (property_exists($this->extra_data['hardware'], 'winprodkey')) {
            $val->license_number = $this->extra_data['hardware']->winprodkey;
         }

         if (property_exists($this->extra_data['hardware'], 'osname')) {
            $val->full_name = $this->extra_data['hardware']->osname;
         }

         if (property_exists($this->extra_data['hardware'], 'osversion')) {
            $val->version = $this->extra_data['hardware']->osversion;
         }

         if (property_exists($this->extra_data['hardware'], 'oscomments')
                  && $this->extra_data['hardware']->oscomments != ''
                  && !strstr($this->extra_data['hardware']->oscomments, 'UTC')) {
            $val->service_pack = $this->extra_data['hardware']->oscomments;
         }
      }

      if (property_exists($val, 'full_name')) {
         $val->operatingsystems_id = $val->full_name;
      }

      if (property_exists($val, 'operatingsystemarchitectures_id')
         && $val->operatingsystemarchitectures_id != ''
      ) {
         $rulecollection = new RuleDictionnaryOperatingSystemArchitectureCollection();
         $res_rule = $rulecollection->processAllRules(['name' => $val->operatingsystemarchitectures_id]);
         if (isset($res_rule['name'])) {
            $val->operatingsystemarchitectures_id = $res_rule['name'];
         }
         if ($val->operatingsystemarchitectures_id == '0') {
            $val->operatingsystemarchitectures_id = '';
         }
      }
      if (property_exists($val, 'operatingsystemservicepacks_id') && $val->operatingsystemservicepacks_id == '0') {
         $val->operatingsystemservicepacks_id = '';
      }

      $this->data = [$val];
      return $this->data;
   }

   public function handle() {
      global $DB;

      $ios = new Item_OperatingSystem();

      $val = $this->data[0];

      $ios->getFromDBByCrit([
         'itemtype'  => $this->item->getType(),
         'items_id'  => $this->item->fields['id']
      ]);

      $input_os = [
         'itemtype'                          => $this->item->getType(),
         'items_id'                          => $this->item->fields['id'],
         'operatingsystemarchitectures_id'   => $val->operatingsystemarchitectures_id ?? 0,
         'operatingsystemkernelversions_id'  => $val->operatingsystemkernelversions_id ?? 0,
         'operatingsystems_id'               => $val->operatingsystems_id,
         'operatingsystemversions_id'        => $val->operatingsystemversions_id ?? 0,
         'operatingsystemservicepacks_id'    => $val->operatingsystemservicepacks_id ?? 0,
         'licenseid'                         => $val->licenseid ?? '',
         'license_number'                    => $val->license_number ?? '',
         'is_dynamic'                        => 1,
         'entities_id'                       => $this->item->fields['entities_id']
      ];

      $this->withHistory(true);//always store history for OS
      if (!$ios->isNewItem()) {
         //OS exists, check for updates
         $same = true;
         foreach ($input_os as $key => $value) {
            if ($ios->fields[$key] != $value) {
               $same = false;
               break;
            }
         }
         if ($same === false) {
            $ios->update(['id' => $ios->getID()] + $input_os, $this->withHistory());
         }
      } else {
         $ios->add($input_os, [], $this->withHistory());
      }

      $val->operatingsystems_id = $ios->fields['id'];;
      $this->operatingsystems_id = $val->operatingsystems_id;

      //cleanup
      if (!$this->item->isPartial()) {
         $iterator = $DB->request([
            'FROM' => $ios->getTable(),
            'WHERE' => [
               'itemtype'  => $this->item->getType(),
               'items_id'  => $this->item->fields['id'],
               'NOT'       => ['id' => $ios->fields['id']]
            ]
         ]);

         while ($row = $iterator->next()) {
            $ios->delete($row['id'], true);
         }
      }

   }

   public function checkConf(Conf $conf): bool {
      return true;
   }

   /**
    * Get current OS id
    *
    * @return integer
    */
   public function getId() {
      return $this->operatingsystems_id;
   }
}
