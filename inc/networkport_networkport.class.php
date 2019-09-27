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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// NetworkPort_NetworkPort class
class NetworkPort_NetworkPort extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1           = 'NetworkPort';
   static public $items_id_1           = 'networkports_id_1';
   static public $itemtype_2           = 'NetworkPort';
   static public $items_id_2           = 'networkports_id_2';

   static public $log_history_1_add    = Log::HISTORY_CONNECT_DEVICE;
   static public $log_history_2_add    = Log::HISTORY_CONNECT_DEVICE;

   static public $log_history_1_delete = Log::HISTORY_DISCONNECT_DEVICE;
   static public $log_history_2_delete = Log::HISTORY_DISCONNECT_DEVICE;


   /**
    * Retrieve an item from the database
    *
    * @param integer $ID ID of the item to get
    *
    * @return boolean  true if succeed else false
   **/
   function getFromDBForNetworkPort($ID) {

      return $this->getFromDBByCrit([
         'OR'  => [
            $this->getTable() . '.networkports_id_1'  => $ID,
            $this->getTable() . '.networkports_id_2'  => $ID
         ]
      ]);
   }


   /**
    * Get port opposite port ID
    *
    * @param integer $ID networking port ID
    *
    * @return integer|false  ID of opposite port. false if not found
   **/
   function getOppositeContact($ID) {
      if ($this->getFromDBForNetworkPort($ID)) {
         if ($this->fields['networkports_id_1'] == $ID) {
            return $this->fields['networkports_id_2'];
         }
         if ($this->fields['networkports_id_2'] == $ID) {
            return $this->fields['networkports_id_1'];
         }
         return false;
      }
   }

   /**
    * Creates a new hub
    *
    * @param integer $netports_id Network port id
    * @param integer $entities_id Entity id
    *
    * @return integer
    */
   public function createHub($netports_id, $entities_id = 0) {
      $netport = new NetworkPort();

      $unmanaged = new Unmanaged();
      $hubs_id = $unmanaged->add([
         'hub'          => 1,
         'name'         => 'Hub',
         'entities_id'  => $entities_id,
         'comment'      => 'Port: ' . $netports_id,
      ]);

      $ports_id = $netport->add([
         'items_id'           => $hubs_id,
         'itemtype'           => $unmanaged->getType(),
         'name'               => 'Hub link',
         'instantiation_type' => 'NetworkPortEthernet'
      ]);
      $this->disconnectFrom($netports_id);
      $this->add([
         'networkports_id_1'  => $netports_id,
         'networkports_id_2'  => $ports_id
      ]);
      return $hubs_id;
   }

   /**
    * Connects to a hub
    *
    * @param integer $ports_id Port to link
    * @param integer $hubs_id  Hub to link
    */
   public function connectToHub($ports_id, $hubs_id) {

      global $DB;

      $netport = new NetworkPort();

      $this->disconnectFrom($ports_id);
      // Search free port
      $result = $DB->request([
         'SELECT'    => $netport->getTable() . '.id',
         'FROM'      => $netport->getTable(),
         'LEFT JOIN' => [
            self::getTable() => [
               'ON'  => [
                  $netport->getTable() => 'id',
                  self::getTable()     => 'networkports_id_2'
               ]
            ]
         ],
         'WHERE'     => [
            'itemtype'           => Unmanaged::getType(),
            'items_id'           => $hubs_id,
            'networkports_id_1'  => null
         ],
         'LIMIT'     => 1
      ])->next();

      $free_id = $result['id'] ?? 0;
      if (!$free_id) {
         //no free port, create a new one
         $free_id = $netport->add([
            'itemtype'           => Unmanaged::getType(),
            'items_id'           => $hubs_id,
            'instantiation_type' => 'NetworkPortEthernet'
         ]);
      }

      $this->add([
         'networkports_id_1'  => $ports_id,
         'networkports_id_2'  => $free_id
      ]);
      return $free_id;
   }

   /**
    * Disconnect a port
    *
    * @param integer $id Hub id
    *
    * @return boolean
    */
   public function disconnectFrom($ports_id) {
      $opposite_id = $this->getOppositeContact($ports_id);
      if ($opposite_id && $this->getFromDBForNetworkPort($opposite_id) || $this->getFromDBForNetworkPort($ports_id)) {
         if ($this->delete($this->fields)) {
            $this->cleanHubPorts();
         }
      }
   }

   /**
    * Cleans hub ports
    * If remove connection of a hub port (unknown device), we must delete this port too
    *
    * @return void
    */
   public function cleanHubPorts() {
      $netport = new \NetworkPort();
      $unmanaged = new \Unmanaged();
      $netport_vlan = new \NetworkPort_Vlan();

      $hubs_ids = [];

      foreach (['networkports_id_1', 'networkports_id_2'] as $field) {
         $port_id = $netport->getContact($this->fields[$field]);
         $netport->getFromDB($this->fields[$field]);
         if (($netport->fields['itemtype'] ?? '') == Unmanaged::getType()) {
            $unmanaged->getFromDB($netport->fields['items_id']);
            if ($unmanaged->fields['hub'] == 1) {
               $vlans = $netport_vlan->getVlansForNetworkPort($netport->fields['id']);
               foreach ($vlans as $vlan_id) {
                  $netport_vlan->unassignVlan($netport->fields['id'], $vlan_id);
               }
               $hubs_ids[$netport->fields['items_id']] = 1;
               $netport->delete($netport->fields);
            }
         }

         if ($port_id) {
            $netport->getFromDB($port_id);
            if ($netport->fields['itemtype'] == Unmanaged::getType()) {
               $unmanaged->getFromDB($netport->fields['items_id']);
               if ($unmanaged->fields['hub'] == '1') {
                  $hubs_ids[$netport->fields['items_id']] = 1;
               }
            }
         }
      }

      // If hub have no port, delete it
      foreach (array_keys($hubs_ids) as $unmanageds_id) {
         $networkports = $netport->find([
            'itemtype'  => Unmanaged::getType(),
            'items_id'  => $unmanageds_id
         ]);
         if (count($networkports) < 2) {
            $unmanaged->delete(['id' => $unmanageds_id], 1);
         } else if (count($networkports) == 2) {
            $switchs_id = 0;
            $others_id  = 0;
            foreach ($networkports as $networkport) {
               if ($networkport['name'] == 'Link') {
                  $switchs_id = $netport->getContact($networkport['id']);
               } else if ($others_id == '0') {
                  $others_id = $netport->getContact($networkport['id']);
               } else {
                  $switchs_id = $netport->getContact($networkport['id']);
               }
            }

            $this->disconnectFrom($switchs_id);
            $this->disconnectFrom($others_id);

            $this->add([
               'networkports_id_1' => $switchs_id,
               'networkports_id_2' => $others_id
            ]);
         }
      }
   }

   public function prepareInputForAdd($input) {

      if ($this->getFromDBForNetworkPort([$input['networkports_id_1'], $input['networkports_id_2']])) {
         Toolbox::logWarning('Wired non unique!');
         return false;
      }

      return $input;
   }

   public function post_addItem() {
      $this->storeConnectionLog('add');
   }

   public function pre_deleteItem() {
      $this->storeConnectionLog('remove');
      return true;
   }

   /**
    * Store connection log.
    *
    * @param string action Either add or remove
    *
    * @return void
    */
   public function storeConnectionLog($action) {
      $netports_id = null;

      $netport = new NetworkPort();
      $netport->getFromDB($this->fields['networkports_id_1']);

      if ($netport->fields['itemtype'] == 'NetworkEquipment') {
         $netports_id = $this->fields['networkports_id_1'];
      } else {
         $netport->getFromDB($this->fields['networkports_id_2']);
         if ($netport->fields['itemtype'] == 'NetworkEquipment') {
            $netports_id = $this->fields['networkports_id_2'];
         }
      }

      if ($netports_id === null) {
         return;
      }

      $log = new NetworkPortConnectionLog();

      $opposite_port = $this->getOppositeContact($netports_id);
      if (!$opposite_port) {
         return;
      }

      $input = [
         'networkports_id_source'      => $netports_id,
         'networkports_id_destination' => $opposite_port,
         'connected'                   => ($action === 'add')
      ];

      $log->add($input);
   }
}
