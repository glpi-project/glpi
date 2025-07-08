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

/* Test for inc/networkporttype.class.php */

class NetworkPortTypeTest extends DbTestCase
{
    public function testDefaults()
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'   => \NetworkPortType::getTable(),
        ]);
        $this->assertGreaterThanOrEqual(
            300,
            count($iterator)
        );

        $iterator = $DB->request([
            'FROM'   => \NetworkPortType::getTable(),
            'WHERE'  => ['is_importable' => true],
        ]);

        $expecteds = [
            [
                'value_decimal' => 6,
                'name' => 'ethernet-csmacd',
                'comment' => '[RFC1213]',
                'is_importable' => 1,
                'instantiation_type' => 'NetworkPortEthernet',
            ], [
                'value_decimal' => 7,
                'name' => 'IEEE802.3',
                'comment' => 'DEPRECATED [RFC3635]',
                'is_importable' => 1,
                'instantiation_type' => 'NetworkPortEthernet',
            ], [
                'value_decimal' => 56,
                'name' => 'fibre-channel',
                'comment' => 'Fibre Channel',
                'is_importable' => 1,
                'instantiation_type' => 'NetworkPortFiberchannel',
            ], [
                'value_decimal' => 62,
                'name' => 'fastEther',
                'comment' => 'DEPRECATED [RFC3635]',
                'is_importable' => 1,
                'instantiation_type' => 'NetworkPortEthernet',
            ], [
                'value_decimal' => 71,
                'name' => 'IEEE802.11',
                'comment' => 'radio spread spectrum [Dawkoon_Paul_Lee]',
                'is_importable' => 1,
                'instantiation_type' => 'NetworkPortWifi',
            ], [
                'value_decimal' => 117,
                'name' => 'gigabitEthernet',
                'comment' => 'DEPRECATED [RFC3635]',
                'is_importable' => 1,
                'instantiation_type' => 'NetworkPortEthernet',
            ], [
                'value_decimal' => 169,
                'name' => 'shdsl',
                'comment' => 'Multirate HDSL2 [Bob_Ray]',
                'is_importable' => 1,
                'instantiation_type' => 'NetworkPortEthernet',
            ], [
                'value_decimal' => 188,
                'name' => 'radioMAC',
                'comment' => 'MAC layer over radio links [Daniele_Behar]',
                'is_importable' => 1,
                'instantiation_type' => 'NetworkPortWifi',
            ],
        ];

        $this->assertCount(count($expecteds), $iterator);

        foreach ($iterator as $row) {
            $expected = array_shift($expecteds);
            $expected += [
                'id' => $row['id'],
                'entities_id' => 0,
                'is_recursive' => 0,
                'date_creation' => $row['date_creation'],
                'date_mod' => $row['date_mod'],
            ];
            $this->assertEquals($expected, $row);
        }
    }
}
