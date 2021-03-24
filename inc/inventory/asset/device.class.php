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
use Item_Devices;

abstract class Device extends InventoryAsset
{
   protected $id_class;

   /**
    * Constructor
    *
    * @param CommonDBTM $item    Item instance
    * @param array      $data    Data part
    * @param string     $idclass Item device class
    */
   public function __construct(CommonDBTM $item, array $data = null, $id_class) {
      parent::__construct($item, $data);
      $this->id_class = $id_class;
   }

   public function handle() {
      global $DB;

      $devicetypes = Item_Devices::getItemAffinities($this->item->getType());

      $itemdevicetype = $this->id_class;
      if (in_array($this->id_class, $devicetypes)) {
         $value = $this->data;
         $itemdevice = new $itemdevicetype;

         $itemdevicetable = getTableForItemType($itemdevicetype);
         $devicetype      = $itemdevicetype::getDeviceType();
         $device          = new $devicetype;
         $devicetable     = getTableForItemType($devicetype);
         $fk              = getForeignKeyFieldForTable($devicetable);

         $iterator = $DB->request([
            'SELECT'    => [
               "$itemdevicetable.$fk",
            ],
            'FROM'      => $itemdevicetable,
            'WHERE'     => [
               "$itemdevicetable.items_id"     => $this->item->fields['id'],
               "$itemdevicetable.itemtype"     => $this->item->getType(),
               "$itemdevicetable.is_dynamic"   => 1
            ]
         ]);

         $existing = [];
         while ($row = $iterator->next()) {
            $existing[$row[$fk]] = $row[$fk];
         }

         foreach ($value as $val) {
            if (!isset($val->designation) || $val->designation == '') {
               //cannot be empty
               $val->designation = $itemdevice->getTypeName(1);
            }

            $device_id = $device->import(\Toolbox::addslashes_deep((array)$val));
            if ($device_id && !in_array($device_id, $existing)) {
               $itemdevice_data = [
                  $fk                  => $device_id,
                  'itemtype'           => $this->item->getType(),
                  'items_id'           => $this->item->fields['id'],
                  'is_dynamic'         => 1,
               ] + (array)$val;
               $itemdevice->add($itemdevice_data, [], $this->withHistory());

               $this->itemdeviceAdded($itemdevice, $val);
            }
         }
      }
   }

   protected function itemdeviceAdded(Item_Devices $itemdevice, $val) {
      //to be overrided
   }
}
