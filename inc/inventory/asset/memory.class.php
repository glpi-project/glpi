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

use CommonDBTM;
use Glpi\Inventory\Conf;

class Memory extends Device
{
   public function __construct(CommonDBTM $item, array $data = null) {
      parent::__construct($item, $data, 'Item_DeviceMemory');
   }

   public function prepare() :array {
      $mapping = [
         'capacity'     => 'size',
         'speed'        => 'frequence',
         'type'         => 'devicememorytypes_id',
         'serialnumber' => 'serial',
         'numslots'     => 'busID'
      ];

      foreach ($this->data as $k => &$val) {
         if (property_exists($val, 'capacity') && $val->capacity > 0) {
            foreach ($mapping as $origin => $dest) {
               if (property_exists($val, $origin)) {
                  $val->$dest = $val->$origin;
               }
            }
         } else {
            unset($this->data[$k]);
            continue;
         }

         // Hack to remove Memories with Flash types see ticket
         // http://forge.fusioninventory.org/issues/1337
         if (property_exists($val, 'type')
               && preg_match('/Flash/', $val->type)) {
            unset($this->data[$k]);
            continue;
         }

         $designation = '';
         if (property_exists($val, 'type')
            && $val->type != 'Empty Slot'
            && $val->type != 'Unknown'
         ) {
            $designation = $val->type;
         }
         if (property_exists($val, 'description')) {
            if ($designation != '') {
               $designation .= ' - ';
            }
            $designation .= $val->description;
         }

         if ($designation != '') {
            $val->designation = $designation;
         }

         if (property_exists($val, 'frequence')) {
            $val->frequence = str_replace([' MHz', ' MT/s'], '', $val->frequence);
         }
      }
      return $this->data;
   }

   public function checkConf(Conf $conf): bool {
      return $conf->component_memory == 1;
   }
}
