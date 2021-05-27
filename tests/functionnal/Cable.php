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

namespace tests\units;

use DbTestCase;

/* Test for inc/networkport.class.php */

class Cable extends DbTestCase {

   public function testAddNetworkPortThenSocket() {
      $this->login();

      //First step add networkport
      $computer1 = getItemByTypeName('Computer', '_test_pc01');
      $networkport = new \NetworkPort();

      // Be sure added
      $nb_log = (int)countElementsInTable('glpi_logs');
      $new_id = $networkport->add([
         'items_id'           => $computer1->getID(),
         'itemtype'           => 'Computer',
         'entities_id'        => $computer1->fields['entities_id'],
         'is_recursive'       => 0,
         'logical_number'     => 1,
         'mac'                => '00:24:81:eb:c6:d0',
         'instantiation_type' => 'NetworkPortEthernet',
         'name'               => 'eth1',
      ]);
      $this->integer((int)$new_id)->isGreaterThan(0);
      $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

      //Second step add socket
      //add socket model
      $socketModel = new \SocketModel();
      $nb_log = (int)countElementsInTable('glpi_logs');
      $socketModel_id = $socketModel->add([
         'name' => 'socketModel1'
      ]);
      $this->integer((int)$socketModel_id)->isGreaterThan(0);
      $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

      $socket = new \Socket();
      $socket_id = $socket->add([
         'name'               => 'socket1',
         'networkports_id'    => $new_id,
         'wiring_side'        => \Socket::FRONT, //default is REAR
         'items_id'           => $computer1->getID(),
         'itemtype'           => 'Computer',
         'entities_id'        => $computer1->fields['entities_id'],
         'is_recursive'       => 0,
         'socketmodels_id'    => $socketModel_id,
         'locations_id'       => 0,
         'comment'            => 'comment',
      ]);
      $this->integer((int)$socket_id)->isGreaterThan(0);

      // check data in db
      $all_sockets = getAllDataFromTable('glpi_sockets', ['ORDER' => 'id']);
      $current_socket = end($all_sockets);
      unset($current_socket['id']);
      unset($current_socket['date_mod']);
      unset($current_socket['date_creation']);
      $expected = [
         'entities_id'        => $computer1->fields['entities_id'],
         'is_recursive'       => 0,
         'locations_id'       => 0,
         'name'               => 'socket1',
         'socketmodels_id'    => $socketModel_id,
         'wiring_side'        => \Socket::FRONT, //default is REAR
         'itemtype'           => 'Computer',
         'items_id'           => $computer1->getID(),
         'networkports_id'    => $new_id,
         'comment'            => 'comment',
      ];

      $this->array($current_socket)->isIdenticalTo($expected);
   }


   public function testBackwardCompatibility() {

      //test when sockets_id is defined from NetworkPort instanciation (NetworkPortEthernet, NetworkPortBNC, NetworkPortFiberChannel)
      //before it was the NetworkPort instantiation that had the socket reference
      //now it's the socket that have the networkport reference

      $this->login();

      //Second step add socket
      //add socket model
      $socketModel = new \SocketModel();
      $nb_log = (int)countElementsInTable('glpi_logs');
      $socketModel_id = $socketModel->add([
         'name' => 'socketModel1'
      ]);
      $this->integer((int)$socketModel_id)->isGreaterThan(0);
      $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

      $socket = new \Socket();
      $nb_log = (int)countElementsInTable('glpi_logs');
      $socket_id = $socket->add([
         'name'               => 'socket1',
         'wiring_side'        => \Socket::FRONT, //default is REAR
         'itemtype'           => '',
         'is_recursive'       => 0,
         'socketmodels_id'    => $socketModel_id,
         'locations_id'       => 0,
         'comment'            => 'comment',
      ]);
      $this->integer((int)$socket_id)->isGreaterThan(0);
      $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

      //Second step add networkport
      // Do some installations
      $computer1 = getItemByTypeName('Computer', '_test_pc01');
      $networkport = new \NetworkPort();

      // Be sure added
      $nb_log = (int)countElementsInTable('glpi_logs');
      $new_id = $networkport->add([
         'items_id'                    => $computer1->getID(),
         'itemtype'                    => 'Computer',
         'entities_id'                 => $computer1->fields['entities_id'],
         'is_recursive'                => 0,
         'logical_number'              => 3,
         'mac'                         => '00:24:81:eb:c6:d2',
         'instantiation_type'          => 'NetworkPortEthernet',
         'name'                        => 'em3',
         'comment'                     => 'Comment me!',
         'items_devicenetworkcards_id' => 0,
         'type'                        => 'T',
         'speed'                       => 1000,
         'speed_other_value'           => '',
         'NetworkName_name'            => 'test1',
         'NetworkName_comment'         => 'test1 comment',
         'NetworkName_fqdns_id'        => 0,
         'NetworkName__ipaddresses'    => ['-1' => '192.168.20.1'],
         '_create_children'            => true // automatically add instancation, networkname and ipadresses
      ]);
      $this->integer($new_id)->isGreaterThan(0);
      $this->integer((int)countElementsInTable('glpi_logs'))->isGreaterThan($nb_log);

      // retrieve NEtworkPortEthernet automatically created
      $all_netportethernets = getAllDataFromTable('glpi_networkportethernets', ['ORDER' => 'id']);
      $networkportethernet = end($all_netportethernets);
      $networkPortethernet_id = $networkportethernet['id'];
      unset($networkportethernet['date_mod']);
      unset($networkportethernet['date_creation']);

      //specify sockets_id and update it
      $data = $networkportethernet;
      $data['id'] = $networkPortethernet_id;
      $data['sockets_id'] = $socket_id;
      $networkPort_ethernet = new \NetworkPortEthernet();
      $this->boolean($networkPort_ethernet->update($data))->isTrue();

      //reload socket to check if link to networkports_id is ok (with itemtype and items_id)
      $this->boolean($socket->getFromDB($socket_id))->isTrue();
      $this->string($socket->fields['itemtype'])->isIdenticalTo('Computer');
      $this->integer($socket->fields['items_id'])->isIdenticalTo($computer1->getID());
      $this->integer($socket->fields['networkports_id'])->isIdenticalTo($new_id);

   }

}