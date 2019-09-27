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

class Drive extends Device
{
   private $harddrives;
   private $prepared_harddrives = [];

   public function __construct(CommonDBTM $item, array $data = null) {
      parent::__construct($item, $data, 'Item_DeviceDrive');
   }

   public function prepare() :array {
      $mapping = [
         'serialnumber' => 'serial',
         'name'         => 'designation',
         'type'         => 'interfacetypes_id',
         'manufacturer' => 'manufacturers_id',
      ];

      $hdd = [];
      foreach ($this->data as $k => &$val) {
         if ($this->isDrive($val)) { // it's cd-rom / dvd
            foreach ($mapping as $origin => $dest) {
               if (property_exists($val, $origin)) {
                  $val->$dest = $val->$origin;
               }
            }

            if (property_exists($val, 'description')) {
               $val->designation = $val->description;
            }
         } else { // it's harddisk
            $hdd[] = $val;
            unset($this->data[$k]);
         }
      }
      if (count($hdd)) {
         $this->harddrives = new HardDrive($this->item, $hdd);
         $prep_hdds = $this->harddrives->prepare();
         if (defined('TU_USER')) {
            $this->prepared_harddrives = $prep_hdds;
         }
      }

      return $this->data;
   }

   /**
    * Is current data a drive
    *
    * @return boolean
    */
   public function isDrive($data) {
      $drives_regex = [
         'rom',
         'dvd',
         'blu[\s-]*ray',
         'reader',
         'sd[\s-]*card',
         'micro[\s-]*sd',
         'mmc'
      ];

      foreach ($drives_regex as $regex) {
         foreach (['type', 'model', 'name'] as $field) {
            if (property_exists($data, $field)
               && !empty($data->$field)
               && preg_match("/".$regex."/i", $data->$field)
            ) {
               return true;
            }
         }
      }

      return false;
   }
   public function handle() {
      parent::handle();
      if ($this->harddrives !== null) {
         $this->harddrives->handleLinks();
         $this->harddrives->handle();
      }
   }

   public function checkConf(Conf $conf): bool {
      return $conf->component_drive == 1;
   }

   /**
    * Get harddrives data
    *
    * @return HardDrive
    */
   public function getPreparedHarddrives() :array {
      return $this->prepared_harddrives;
   }
}
