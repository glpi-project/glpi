<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use DbTestCase;

/* Test for inc/NetworkPortMetrics.class.php */

class NetworkPortMetricsTest extends DbTestCase
{
    public function testNetworkPortithMetrics()
    {

        $neteq = new \NetworkEquipment();
        $neteq_id = $neteq->add([
            'name'   => 'My network equipment',
            'entities_id'  => 0,
        ]);
        $this->assertGreaterThan(0, $neteq_id);

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
            'ifmtu'        => 1000,
        ];

        $netport = new \NetworkPort();
        $netports_id = $netport->add($port);
        $this->assertGreaterThan(0, $netports_id);

        //create port, check if metrics has been addded
        $metrics = new \NetworkPortMetrics();
        $values = $metrics->getMetrics($netport);
        $this->assertCount(1, $values);

        $value = array_pop($values);
        $expected = [
            'networkports_id' => $netports_id,
            'ifinerrors'   => 10,
            'ifouterrors'  => 50,
            'ifinbytes'    => 1076823325,
            'ifoutbytes'   => 2179528910,
            'date' => $value['date'],
            'id' => $value['id'],
            'date_creation' => $_SESSION['glpi_currenttime'],
            'date_mod' => $_SESSION['glpi_currenttime'],
        ];
        $this->assertEquals($expected, $value);

        //update port, check metrics
        $port['ifmtu'] = 1500;
        $port['ifinbytes'] = 1056823325;
        $port['ifoutbytes'] = 2159528910;
        $port['ifinerrors'] = 0;
        $port['ifouterrors'] = 0;

        $this->assertTrue($netport->update($port + ['id' => $netports_id]));
        $values = $metrics->getMetrics($netport);
        $this->assertCount(1, $values); // Still 1 row, as there should be only one row per date

        $updated_value = array_pop($values);

        $expected = [
            'networkports_id' => $netports_id,
            'ifinerrors'   => 0,
            'ifouterrors'  => 0,
            'ifinbytes'    => 1056823325,
            'ifoutbytes'   => 2159528910,
            'date' => $value['date'],
            'id' => $value['id'],
            'date_creation' => $_SESSION['glpi_currenttime'],
            'date_mod' => $_SESSION['glpi_currenttime'],
        ];
        $this->assertEquals($expected, $updated_value);

        //check logs => no bytes nor errors
        global $DB;
        $iterator = $DB->request([
            'FROM'   => \Log::getTable(),
            'WHERE'  => [
                'itemtype'  => 'NetworkPort',
            ],
        ]);

        $this->assertSame(2, count($iterator));
        foreach ($iterator as $row) {
            $this->assertNotEquals(34, $row['id_search_option']);
            $this->assertNotEquals(35, $row['id_search_option']);
        }
    }
}
