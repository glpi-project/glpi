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

use NetworkEquipmentModel;
use NetworkEquipmentType;
use NetworkName;

class NetworkEquipment extends MainAsset
{
   private $management_ports = [];

   protected $extra_data = [
      'network_device'                          => null,
      'network_components'                      => null,
      '\Glpi\Inventory\Asset\NetworkPort'       => null
   ];

   protected function getModelsFieldName(): string {
      return NetworkEquipmentModel::getForeignKeyField();
   }

   protected function getTypesFieldName(): string {
      return NetworkEquipmentType::getForeignKeyField();
   }

   public function prepare() :array {
      parent::prepare();

      $val = $this->data[0];
      $model_field = $this->getModelsFieldName();
      $types_field = $this->getTypesFieldName();

      if (isset($this->extra_data['network_device'])) {
         $device = (object)$this->extra_data['network_device'];

         $dev_mapping = [
            'description'  => 'name',
            'location'     => 'locations_id',
            'model'        => $model_field,
            'type'         => $types_field,
            'manufacturer' => 'manufacturers_id'
         ];

         foreach ($dev_mapping as $origin => $dest) {
            if (property_exists($device, $origin)) {
               $device->$dest = $device->$origin;
            }
         }
         $this->hardware = $device;

         foreach ($device as $key => $property) {
            $val->$key = $property;
         }

         if (property_exists($device, 'ips')) {
            $portkey = 'management';
            $port = new \stdClass();
            if (property_exists($device, 'mac')) {
               $port->mac = $device->mac;
            }
            $port->name = __('Management');
            $port->netname = __('internal');
            $port->instantiation_type = 'NetworkPortAggregate';
            $port->is_internal = true;
            $port->ipaddress = [];

            //add internal port(s)
            foreach ($device->ips as $ip) {
               if ($ip != '127.0.0.1' && $ip != '::1' && !in_array($ip, $port->ipaddress)) {
                  $port->ipaddress[] = $ip;
               }
            }

            $this->management_ports[$portkey] = $port;
         }
      }

      if ($this->isStackedSwitch()) {
         //keep only stack parts, not main equipment
         $this->data = [];
         $switches = $this->getStackedSwitches();
         foreach ($switches as $switch) {
            $stack = clone $val;
            $stack->firmware = $switch->version;
            $stack->serial = $switch->serial;
            $stack->model = $switch->model;
            $stack->$model_field = $switch->model;
            $stack->description = $stack->name . ' - ' . $switch->name;
            $stack->name = $stack->name . ' - ' . $switch->name;
            $this->data[] = $stack;
         }
      } else {
         //keep an entry for main equipment
         $this->data = [$val];
         if ($this->isWirelessController()) {
            $aps = $this->getAccessPoints();
            $i = 1;
            foreach ($aps as $ap) {
               $wcontrol = clone $val;
               $wcontrol->is_ap = true;
               $wcontrol->mac = $ap->mac;
               $wcontrol->name = $ap->name . ' ' . $ap->description;
               $wcontrol->serial = $ap->serial;
               $wcontrol->networkequipmentmodels_id = $ap->model ?? '';
               $wcontrol->ram = null;
               $wcontrol->memory = null;
               $wcontrol->ips = [$ap->ip];

               //add internal port
               $port = new \stdClass();
               $port->mac = $ap->mac;
               $port->name = __('Management');
               $port->is_internal = true;
               $port->netname = __('internal');
               $port->instantiation_type = 'NetworkPortAggregate';
               $port->ipaddress = [$ap->ip];
               $wcontrol->ap_port = $port;

               $firmware = new \stdClass();
               $firmware->description = $ap->comment ?? '';
               $firmware->name = $ap->model ?? '';
               $firmware->devicefirmwaretypes_id = 'device';
               $firmware->version = $ap->version ?? '';
               $wcontrol->firmware = $firmware;

               $this->data[] = $wcontrol;

               ++$i;
            }
         }
      }

      return $this->data;
   }

   /**
    * After rule engine passed, update task (log) and create item if required
    *
    * @param integer $items_id id of the item (0 if new)
    * @param string  $itemtype Item type
    * @param integer $rules_id Matched rule id, if any
    * @param integer $ports_id Matched port id, if any
    */
   public function rulepassed($items_id, $itemtype, $rules_id, $ports_id = 0) {
      if (property_exists($this->data[$this->current_key], 'is_ap')) {
         $bkp_assets = $this->assets;
         $np = new NetworkPort($this->item, [$this->data[$this->current_key]]);

         if ($np->checkConf($this->conf)) {
            $np->setAgent($this->getAgent());
            $np->setEntityID($this->getEntityID());
            $np->prepare();
            $np->handleLinks();
            $this->assets = ['Glpi\Inventory\Asset\NetworkPort' => [$np]];
         }
      }

      parent::rulepassed($items_id, $itemtype, $rules_id, $ports_id);

      if (isset($bkp_assets)) {
         $this->assets = $bkp_assets;
      }
   }

   public function handleLinks(array $data = null) {
      if (property_exists($this, 'current_key')) {
         $data = [$this->data[$this->current_key]];
      } else {
         $data = $this->data;
      }
      parent::handleLinks($data);
   }

   protected function portCreated(\stdClass $port, int $netports_id) {
      if (property_exists($port, 'is_internal') && $port->is_internal) {
         return;
      }

      // Get networkname
      $netname = new NetworkName();
      if ($netname->getFromDBByCrit(['itemtype' => 'NetworkPort', 'items_id' => $netports_id])) {
         if ($netname->fields['name'] != $port->name) {
            $netname->update([
               'id'     => $netname->getID(),
               'name'   => ($port->netname ?? $port->name)
            ], $this->withHistory());
         }
      } else {
         $netname->add([
            'itemtype'  => 'NetworkPort',
            'items_id'  => $netports_id,
            'name'      => $port->name
         ], [], $this->withHistory());
      }
   }

   public function getManagementPorts() {
      return $this->management_ports;
   }

   public function setManagementPorts(array $ports): NetworkEquipment {
      $this->management_ports = $ports;
      return $this;
   }

   /**
    * Is device a stacked switch
    * Relies on level/dependencies of network_components
    *
    * @param integer $parent_index Parent index for recursive calls
    *
    * @return boolean
    */
   public function isStackedSwitch($parent_index = 0): bool {
      $components = $this->extra_data['network_components'] ?? [];
      if (!count($components)) {
         return false;
      }

      $elt_count = 0;
      foreach ($components as $component) {
         if (!property_exists($component, 'type')) {
            continue;
         }
         switch ($component->type) {
            case 'stack':
               if ($parent_index == 0) {
                  $elt_count += $this->isStackedSwitch($component->index);
               }
               break;
            case 'chassis':
               if (property_exists($component, 'serial')) {
                  ++$elt_count;
               }
               break;
         }
      }

      return $elt_count >= 2;
   }

   /**
    * Get detected switches (osrted by their index)
    *
    * @return array
    */
   public function getStackedSwitches($parent_index = 0): array {
      $components = $this->extra_data['network_components'] ?? [];
      if (!count($components)) {
         return [];
      }

      $switches = [];

      foreach ($components as $component) {
         switch ($component->type) {
            case 'stack':
               if ($parent_index == 0 && (!property_exists($component, 'parent_index') || !empty($component->parent_index))) {
                  $switches += $this->getStackedSwitches($component->index);
               }
               break;
            case 'chassis':
               if (property_exists($component, 'serial')) {
                  $switches[$component->index] = $component;
               }
               break;
         }
      }

      ksort($switches);
      return $switches;
   }

   /**
    * Is device a wireless controller
    * Relies on level/dependencies of network_components
    *
    * @param integer $parent_index Parent index for recursive calls
    *
    * @return boolean
    */
   public function isWirelessController($parent_index = 0): bool {
      $components = $this->extra_data['network_components'] ?? [];
      if (!count($components)) {
         return false;
      }

      foreach ($components as $component) {
         if (property_exists($component, 'ip') && property_exists($component, 'mac')
            && !empty($component->ip) && !empty($component->mac)) {
            return true;
         }
      }

      return false;
   }

   /**
    * Get wireless controller access points
    *
    * @return array
    */
   public function getAccessPoints(): array {
      $components = $this->extra_data['network_components'] ?? [];
      if (!count($components)) {
         return [];
      }

      $aps = [];
      foreach ($components as $component) {
         if (property_exists($component, 'ip') && property_exists($component, 'mac')
            && !empty($component->ip) && !empty($component->mac)) {
            $aps[$component->index] = $component;
         }
      }

      return $aps;
   }

   public function getStackId() {
      if (count($this->data) !=1) {
         throw new \RuntimeException('Exactly one entry in data is expected.');
      } else {
         $data = current($this->data);
         return preg_replace('/.+ - (\d)/', '$1', $data->name);
      }
   }
}
