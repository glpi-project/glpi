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

use ComputerAntivirus;
use Glpi\Inventory\Conf;

class Antivirus extends InventoryAsset
{
   public function prepare() :array {
      if ($this->item->getType() != 'Computer') {
         throw new \RuntimeException('Antivirus are handled for computers only.');
      }
      $mapping = [
         'company'      => 'manufacturers_id',
         'version'      => 'antivirus_version',
         'base_version' => 'signature_version',
         'enabled'      => 'is_active',
         'uptodate'     => 'is_uptodate',
         'expiration'   => 'date_expiration'
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
         'SELECT' => ['id', 'name', 'antivirus_version'],
         'FROM'   => ComputerAntivirus::getTable(),
         'WHERE'  => ['computers_id' => $this->item->fields['id']]
      ]);

      while ($data = $iterator->next()) {
         $idtmp = $data['id'];
         unset($data['id']);
         $data = array_map('strtolower', $data);
         $db_existing[$idtmp] = $data;
      }

      return $db_existing;
   }

   public function handle() {
      global $DB;

      $db_antivirus = $this->getExisting();
      $value = $this->data;
      $computerAntivirus = new ComputerAntivirus();

      //check for existing
      foreach ($value as $k => $val) {
         $compare = ['name' => $val->name, 'antivirus_version' => $val->antivirus_version];
         $compare = array_map('strtolower', $compare);
         foreach ($db_antivirus as $keydb => $arraydb) {
            if ($compare == $arraydb) {
               $input = (array)$val + [
                  'id'           => $keydb,
                  'is_dynamic'   => 1
               ];
               $computerAntivirus->update($input, $this->withHistory());
               unset($data[$k]);
               unset($db_antivirus[$keydb]);
               break;
            }
         }
      }

      if (!$this->item->isPartial() && count($db_antivirus) != 0) {
         foreach ($db_antivirus as $idtmp => $data) {
            $computerAntivirus->delete(['id' => $idtmp], 1);
         }
      }

      if (count($value) != 0) {
         foreach ($value as $val) {
            $val->computers_id = $this->item->fields['id'];
            $val->is_dynamic = 1;
            $computerAntivirus->add((array)$val, [], $this->withHistory());
         }
      }
   }

   public function checkConf(Conf $conf): bool {
      return $conf->import_antivirus == 1;
   }
}
