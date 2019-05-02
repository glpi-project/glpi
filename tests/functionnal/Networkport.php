<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

use \DbTestCase;

/* Test for inc/networkport.class.php */

class Networkport extends DbTestCase {

   public function testAddSimpleNetworkPort() {
      $this->login();

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

      // check data in db
      $all_netports = getAllDataFromTable('glpi_networkports', ['ORDER' => 'id']);
      $current_networkport = end($all_netports);
      unset($current_networkport['id']);
      unset($current_networkport['date_mod']);
      unset($current_networkport['date_creation']);
      $expected = [
          'items_id'           => $computer1->getID(),
          'itemtype'           => 'Computer',
          'entities_id'        => $computer1->fields['entities_id'],
          'is_recursive'       => '0',
          'logical_number'     => '1',
          'name'               => 'eth1',
          'instantiation_type' => 'NetworkPortEthernet',
          'mac'                => '00:24:81:eb:c6:d0',
          'comment'            => null,
          'is_deleted'         => '0',
          'is_dynamic'         => '0',
      ];
      $this->array($current_networkport)->isIdenticalTo($expected);

      $all_netportethernets = getAllDataFromTable('glpi_networkportethernets', ['ORDER' => 'id']);
      $networkportethernet = end($all_netportethernets);
      $this->boolean($networkportethernet)->isFalse();

      // be sure added and have no logs
      $nb_log = (int)countElementsInTable('glpi_logs');
      $new_id = $networkport->add([
         'items_id'           => $computer1->getID(),
         'itemtype'           => 'Computer',
         'entities_id'        => $computer1->fields['entities_id'],
         'logical_number'     => 2,
         'mac'                => '00:24:81:eb:c6:d1',
         'instantiation_type' => 'NetworkPortEthernet',
      ], [], false);
      $this->integer((int)$new_id)->isGreaterThan(0);
      $this->integer((int)countElementsInTable('glpi_logs'))->isIdenticalTo($nb_log);
   }

   public function testAddCompleteNetworkPort() {
      $this->login();

      $computer1 = getItemByTypeName('Computer', '_test_pc01');

      // Do some installations
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
         'netpoints_id'                => 0,
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

      // check data in db
      // 1 -> NetworkPortEthernet
      $all_netportethernets = getAllDataFromTable('glpi_networkportethernets', ['ORDER' => 'id']);
      $networkportethernet = end($all_netportethernets);
      unset($networkportethernet['id']);
      unset($networkportethernet['date_mod']);
      unset($networkportethernet['date_creation']);
      $expected = [
          'networkports_id'             => "$new_id",
          'items_devicenetworkcards_id' => '0',
          'netpoints_id'                => '0',
          'type'                        => 'T',
          'speed'                       => '1000',
      ];
      $this->array($networkportethernet)->isIdenticalTo($expected);

      // 2 -> NetworkName
      $all_networknames = getAllDataFromTable('glpi_networknames', ['ORDER' => 'id']);
      $networkname = end($all_networknames);
      $networknames_id = $networkname['id'];
      unset($networkname['id']);
      unset($networkname['date_mod']);
      unset($networkname['date_creation']);
      $expected = [
          'entities_id' => $computer1->fields['entities_id'],
          'items_id'    => "$new_id",
          'itemtype'    => 'NetworkPort',
          'name'        => 'test1',
          'comment'     => 'test1 comment',
          'fqdns_id'    => '0',
          'is_deleted'  => '0',
          'is_dynamic'  => '0',
      ];
      $this->array($networkname)->isIdenticalTo($expected);

      // 3 -> IPAddress
      $all_ipadresses = getAllDataFromTable('glpi_ipaddresses', ['ORDER' => 'id']);
      $ipadress = end($all_ipadresses);
      unset($ipadress['id']);
      unset($ipadress['date_mod']);
      unset($ipadress['date_creation']);
      $expected = [
          'entities_id'  => $computer1->fields['entities_id'],
          'items_id'     => $networknames_id,
          'itemtype'     => 'NetworkName',
          'version'      => '4',
          'name'         => '192.168.20.1',
          'binary_0'     => '0',
          'binary_1'     => '0',
          'binary_2'     => '65535',
          'binary_3'     => '3232240641',
          'is_deleted'   => '0',
          'is_dynamic'   => '0',
          'mainitems_id' => $computer1->getID(),
          'mainitemtype' => 'Computer',
      ];
      $this->array($ipadress)->isIdenticalTo($expected);

      // be sure added and have no logs
      $nb_log = (int)countElementsInTable('glpi_logs');
      $new_id = $networkport->add([
         'items_id'                    => $computer1->getID(),
         'itemtype'                    => 'Computer',
         'entities_id'                 => $computer1->fields['entities_id'],
         'is_recursive'                => 0,
         'logical_number'              => 4,
         'mac'                         => '00:24:81:eb:c6:d4',
         'instantiation_type'          => 'NetworkPortEthernet',
         'name'                        => 'em4',
         'comment'                     => 'Comment me!',
         'netpoints_id'                => 0,
         'items_devicenetworkcards_id' => 0,
         'type'                        => 'T',
         'speed'                       => 1000,
         'speed_other_value'           => '',
         'NetworkName_name'            => 'test2',
         'NetworkName_fqdns_id'        => 0,
         'NetworkName__ipaddresses'    => ['-1' => '192.168.20.2']
      ], [], false);
      $this->integer((int)$new_id)->isGreaterThan(0);
      $this->integer((int)countElementsInTable('glpi_logs'))->isIdenticalTo($nb_log);
   }
}
