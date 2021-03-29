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

use Computer_Item;
use Glpi\Inventory\Conf;
use Peripheral as GPeripheral;
use RuleImportAssetCollection;
use RuleMatchedLog;

class Peripheral extends InventoryAsset
{
   protected $extra_data = ['inputs' => null];

   public function prepare() :array {
      $mapping = [
         'manufacturer' => 'manufacturers_id',
      ];

      $existing = [];
      $usbvendor = new \USBVendor();

      foreach ($this->data as $k => &$val) {
         if (property_exists($val, 'name')) {
            foreach ($mapping as $origin => $dest) {
               if (property_exists($val, $origin)) {
                  $val->$dest = $val->$origin;
               }
            }

            if (property_exists($val, 'vendorid')
               && property_exists($val, 'productid')
               && $val->vendorid != ''
            ) {
               //manufacturer
               if (empty($val->manufacturers_id)
                  && $usb_manufacturer = $usbvendor->getManufacturer($val->vendorid)
               ) {
                  $val->manufacturers_id = $usb_manufacturer;
               }

               //product name
               if (empty($val->productname)
                  && $usb_product = $usbvendor->getProductName($val->vendorid, $val->productid)
               ) {
                  $val->productname = $usb_product;
               }
            }

            if (property_exists($val, 'productname') && $val->productname != '') {
               $val->name = $val->productname;
            }
            unset($val->productname);

            $existing[$val->name] = $k;

         } else {
            unset($this->data[$k]);
         }
      }

      if ($this->extra_data['inputs'] !== null) {
         //hanlde inputs
         $point_types = [
            3 => 'Mouse',
            4 => 'Trackball',
            5 => 'Track Point',
            6 => 'Glide Point',
            7 => 'Touch Pad',
            8 => 'Touch Screen',
            9 => 'Mouse - Optical Sensor'
         ];

         foreach ($this->extra_data['inputs'] as $k => &$val) {
            foreach ($mapping as $origin => $dest) {
               if (property_exists($val, $origin)) {
                  $val->$dest = $val->$origin;
               }
            }

            $val->serial = '';
            $val->peripheraltypes_id = '';

            if (property_exists($val, 'layout')) {
               $val->peripheraltypes_id = 'keyboard';
            } else if (property_exists($val, 'pointingtype') && isset($point_types[$val->pointingtype])) {
               $val->peripheraltypes_id = $point_types[$val->pointingtype];
            }

            if (property_exists($val, 'name') && isset($existing[$val->name])) {
               $this->data[$existing[$val->name]]->peripheraltypes_id = $val->peripheraltypes_id;
            } else {
               $this->data[] = $val;
            }
         }
      }

      return $this->data;
   }

   public function handle() {
      global $DB;

      $rule = new RuleImportAssetCollection();
      $peripheral = new GPeripheral();
      $computer_Item = new Computer_Item();

      $peripherals = [];
      $value = $this->data;

      foreach ($value as $key => $val) {
         $input = [
            'itemtype'     => 'Peripheral',
            'name'         => $val->name ?? '',
            'serial'       => $val->serial ?? '',
            'entities_id'  => $this->item->fields['entities_id']
         ];
         $data = $rule->processAllRules($input, [], ['class' => $this, 'return' => true]);

         if (isset($data['found_inventories'])) {
            $items_id = null;
            $itemtype = 'Peripheral';
            if ($data['found_inventories'][0] == 0) {
               // add peripheral
               $val->is_dynamic = 1;
               $val->entities_id = $this->entities_id;
               $items_id = $peripheral->add((array)$val, [], $this->withHistory());
            } else {
               $items_id = $data['found_inventories'][0];
            }

            $peripherals[] = $items_id;
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

      $db_peripherals = [];
      $iterator = $DB->request([
         'SELECT'    => [
            'glpi_peripherals.id',
            'glpi_computers_items.id AS link_id'
         ],
         'FROM'      => 'glpi_computers_items',
         'LEFT JOIN' => [
            'glpi_peripherals' => [
               'FKEY' => [
                  'glpi_peripherals'      => 'id',
                  'glpi_computers_items'  => 'items_id'
               ]
            ]
         ],
         'WHERE'     => [
            'itemtype'                          => 'Peripheral',
            'computers_id'                      => $this->item->fields['id'],
            'entities_id'                       => $this->entities_id,
            'glpi_computers_items.is_dynamic'   => 1,
            'glpi_peripherals.is_global'           => 0
         ]
      ]);

      while ($data = $iterator->next()) {
         $idtmp = $data['link_id'];
         unset($data['link_id']);
         $db_peripherals[$idtmp] = $data['id'];
      }

      if (count($db_peripherals) == 0) {
         foreach ($peripherals as $peripherals_id) {
            $input = [
               'computers_id'    => $this->item->fields['id'],
               'itemtype'        => 'Peripheral',
               'items_id'        => $peripherals_id,
               'is_dynamic'      => 1,
            ];
            $computer_Item->add($input, [], $this->withHistory());
         }
      } else {
         // Check all fields from source:
         foreach ($peripherals as $key => $peripherals_id) {
            foreach ($db_peripherals as $keydb => $periphs_id) {
               if ($peripherals_id == $periphs_id) {
                  unset($peripherals[$key]);
                  unset($db_peripherals[$keydb]);
                  break;
               }
            }
         }

         if (count($peripherals) || count($db_peripherals)) {
            if ((!$this->main_asset || !$this->main_asset->isPartial()) && count($db_peripherals) != 0) {
               // Delete peripherals links in DB
               foreach ($db_peripherals as $idtmp => $data) {
                  $computer_Item->delete(['id'=>$idtmp], 1);
               }
            }
            if (count($peripherals) != 0) {
               foreach ($peripherals as $peripherals_id) {
                  $input = [
                     'computers_id'    => $this->item->fields['id'],
                     'itemtype'        => 'Peripheral',
                     'items_id'        => $peripherals_id,
                     'is_dynamic'      => 1,
                  ];
                  $computer_Item->add($input, [], $this->withHistory());
               }
            }
         }
      }
   }

   public function checkConf(Conf $conf): bool {
      return true;
   }
}
