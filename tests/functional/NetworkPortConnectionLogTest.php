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

/* Test for inc/networkport_networkport.class.php */

class NetworkPortConnectionLogTest extends DbTestCase
{
    private function createPort(\CommonDBTM $asset, string $mac): \NetworkPort
    {
        $port = new \NetworkPort();
        $nb_log = (int) countElementsInTable('glpi_logs');
        $ports_id = $port->add([
            'items_id'           => $asset->getID(),
            'itemtype'           => $asset->getType(),
            'entities_id'        => $asset->fields['entities_id'],
            'is_recursive'       => 0,
            'logical_number'     => 1,
            'mac'                => $mac,
            'instantiation_type' => 'NetworkPortEthernet',
            'name'               => 'eth1',
        ]);
        $this->assertGreaterThan(0, (int) $ports_id);
        $this->assertGreaterThan($nb_log, countElementsInTable('glpi_logs'));

        return $port;
    }

    public function testLogs()
    {
        $this->login();
        $neteq1 = getItemByTypeName(\NetworkEquipment::class, '_test_networkequipment_1');
        $port_1 = $this->createPort($neteq1, '00:24:81:eb:c6:d3');
        $ports_id_1 = $port_1->getID();

        $neteq2 = getItemByTypeName(\NetworkEquipment::class, '_test_networkequipment_2');
        $port_2 = $this->createPort($neteq2, '00:24:81:eb:c6:d4');
        $ports_id_2 = $port_2->getID();

        $wired = new \NetworkPort_NetworkPort();
        $this->assertGreaterThan(
            0,
            $wired->add([
                'networkports_id_1' => $ports_id_1,
                'networkports_id_2' => $ports_id_2,
            ])
        );

        $this->assertTrue($wired->getFromDBForNetworkPort($ports_id_1));
        $this->assertTrue($wired->getFromDBForNetworkPort($ports_id_2));

        $this->assertSame($ports_id_2, $wired->getOppositeContact($ports_id_1));
        $this->assertSame($ports_id_1, $wired->getOppositeContact($ports_id_2));


        //check connection log has been created
        $connection_log = new \NetworkPortConnectionLog();
        $logs = $connection_log->find();
        $this->assertCount(1, $logs);
        $log = array_pop($logs);
        $this->assertSame(1, $log['connected']);
        $this->assertSame($ports_id_1, $log['networkports_id_source']);
        $this->assertSame($ports_id_2, $log['networkports_id_destination']);

        //check for display
        ob_start();
        $connection_log->showForItem($port_1);
        $contents = ob_get_clean();
        $this->assertStringContainsString($neteq2->getLinkURL(), $contents);

        ob_start();
        $connection_log->showForItem($port_2);
        $contents = ob_get_clean();
        $this->assertStringContainsString($neteq1->getLinkURL(), $contents);
    }
}
