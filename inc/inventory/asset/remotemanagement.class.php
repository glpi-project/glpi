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
use Item_RemoteManagement;

class RemoteManagement extends InventoryAsset
{
   public function prepare() :array {
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
      }

      return $this->data;
   }

   public function handle() {
      global $DB;

      $db_mgmt = [];
      $value = $this->data;
      $mgmt = new Item_RemoteManagement();

      $iterator = $DB->request([
         'FROM'   => $mgmt->getTable(),
         'WHERE'  => [
            'itemtype' => $this->item->getType(),
            'items_id' => $this->item->fields['id']
         ]
      ]);
      while ($data = $iterator->next()) {
         $idtmp = $data['id'];
         unset($data['id']);
         $data = array_map('strtolower', $data);
         $db_mgmt[$idtmp] = $data;
      }

      foreach ($value as $k => $val) {
         $compare = ['remoteid' => $val->remoteid, 'type' => $val->type];
         $compare = array_map('strtolower', $compare);
         foreach ($db_mgmt as $keydb => $arraydb) {
            if ($compare == $arraydb) {
               $input = (array)$val + [
                  'id'           => $keydb,
                  'is_dynamic'   => 1
               ];
               $mgmt->update($input, $this->withHistory());
               unset($data[$k]);
               unset($db_mgmt[$keydb]);
               break;
            }
         }
      }

      foreach ($db_mgmt as $idtmp => $data) {
         $mgmt->delete(['id' => $idtmp], 1);
      }

      foreach ($value as $val) {
         $val->itemtype = $this->item->getType();
         $val->items_id = $this->item->fields['id'];
         $val->is_dynamic = 1;
         $mgmt->add((array)$val, [], $this->withHistory());
      }
   }

   public function checkConf(Conf $conf): bool {
      return true;
   }
}
