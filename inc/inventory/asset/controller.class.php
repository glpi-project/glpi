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

class Controller extends Device
{
   protected $extra_data = ['ignored' => null];

   public function __construct(CommonDBTM $item, array $data = null) {
      parent::__construct($item, $data, 'Item_DeviceControl');
   }

   public function prepare() :array {
      $mapping = [
         'name'          => 'designation',
         'manufacturer'  => 'manufacturers_id',
         'type'          => 'interfacetypes_id'
      ];
      $pcivendor = new \PCIVendor();

      foreach ($this->data as $k => &$val) {
         if (property_exists($val, 'name')) {
            foreach ($mapping as $origin => $dest) {
               if (property_exists($val, $origin)) {
                  $val->$dest = $val->$origin;
               }

               if (property_exists($val, 'pciid')) {
                  $exploded = explode(":", $val->pciids);

                  //manufacturer
                  if ($pci_manufacturer = $pcivendor->getManufacturer($exploded[0])) {
                     $val->manufacturers_id = $pci_manufacturer;
                  }

                  //product name
                  if ($pci_product = $pcivendor->getProductName($exploded[0], $exploded[1])) {
                     $val->designation = $pci_product;
                  }
               }
            }
         } else {
            unset($this->data[$k]);
         }
      }
      return $this->data;
   }

   public function handle() {
      $data = $this->data;

      foreach ($data as $k => $asset) {
         if (property_exists($asset, 'name') && isset($this->extra_data['ignored'][$asset->name])) {
            unset($data[$k]);
         }
      }

      $this->data = $data;
      parent::handle();
   }

   public function checkConf(Conf $conf): bool {
      return $conf->component_control == 1;
   }
}
