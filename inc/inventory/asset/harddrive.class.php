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

class HardDrive extends Device
{
   public function __construct(CommonDBTM $item, array $data = null) {
      parent::__construct($item, $data, 'Item_DeviceHardDrive');
   }

   public function prepare() :array {
      $mapping = [
         'disksize'      => 'capacity',
         'interface'     => 'interfacetypes_id',
         'manufacturer'  => 'manufacturers_id',
         'model'         => 'designation',
         'serialnumber'  => 'serial'
      ];

      foreach ($this->data as &$val) {
         foreach ($mapping as $origin => $dest) {
            if (property_exists($val, $origin)) {
               $val->$dest = $val->$origin;
            }
         }

         if ((!property_exists($val, 'model') || $val->model == '') && property_exists($val, 'name')) {
            $val->designation = $val->name;
         }
      }

      return $this->data;
   }

   public function checkConf(Conf $conf): bool {
      return $conf->component_harddrive == 1;
   }
}
