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

/* Test for inc/NetworkPortMetrics.class.php */

class NetworkPortMetrics extends DbTestCase {

   public function testNetworkPortithMetrics() {

      $neteq = new \NetworkEquipment();
      $neteq_id = $neteq->add([
         'name'   => 'My network equipment',
         'entities_id'  => 0
      ]);
      $this->integer($neteq_id)->isGreaterThan(0);

      $port = [
         'name'         => 'Gigabit0/1/2',
         'ifinerrors'   => 10,
         'ifouterrors'  => 50,
         'ifinbytes'    => 1076823325,
         'ifoutbytes'   => 2179528910,
         'ifspeed'      => 4294967295,
         'instanciation_type' => 'NetworkPortEthernet',
         'entities_id'  => 0,
         'items_id'     => $neteq_id,
         'itemtype'     => $neteq->getType(),
         'is_dynamic'   => 1,
         'ifmtu'        => 1000
      ];

      $netport = new \NetworkPort();
      $netports_id = $netport->add($port);
      $this->integer($netports_id)->isGreaterThan(0);

      //create port, check if metrics has been addded
      $metrics = new \NetworkPortMetrics();
      $values = $metrics->getMetrics($netport);
      $this->array($values)->hasSize(1);

      $value = array_pop($values);
      $expected = [
            'networkports_id' => $netports_id,
            'ifinerrors'   => 10,
            'ifouterrors'  => 50,
            'ifinbytes'    => 1076823325,
            'ifoutbytes'   => 2179528910,
            'date' => $value['date'],
            'id' => $value['id']
      ];
      $this->array($value)->isEqualTo($expected);

      //update port, check metrics
      $port['ifmtu'] = 1500;
      $port['ifinbytes'] = 1056823325;
      $port['ifoutbytes'] = 2159528910;
      $port['ifinerrors'] = 0;
      $port['ifouterrors'] = 0;

      $this->boolean($netport->update($port + ['id' => $netports_id]))->isTrue();
      $values = $metrics->getMetrics($netport);
      $this->array($values)->hasSize(2);

      $value2 = array_pop($values);
      $value1 = array_pop($values);
      $this->array($value1)->isIdenticalTo($value);

      $expected = [
            'networkports_id' => $netports_id,
            'ifinerrors'   => 0,
            'ifouterrors'  => 0,
            'ifinbytes'    => 1056823325,
            'ifoutbytes'   => 2159528910,
            'date' => $value['date'],
            'id' => $value['id']
      ];
      $this->array($value1)->isIdenticalTo($value);

      //check logs => no bytes nor errors
      global $DB;
      $iterator = $DB->request([
         'FROM'   => \Log::getTable(),
         'WHERE'  => [
            'itemtype'  => 'NetworkPort'
         ]
      ]);

      $this->integer(count($iterator))->isIdenticalTo(2);
      while ($row = $iterator->next()) {
         $this->integer($row['id_search_option'])
            ->isNotEqualTo(34) //ifinbytes SO from NetworkPort
            ->isNotEqualTo(35); //ifinerrors SO from NetworkPort
      }
   }
}
