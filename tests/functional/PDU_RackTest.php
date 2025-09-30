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

class PDU_RackTest extends DbTestCase
{
    public function testAdd()
    {
        $rack = $this->createItem(
            \Rack::class,
            [
                'name' => 'Test rack',
                'number_units' => 10,
                'dcrooms_id' => 0,
                'position' => 0,
                'entities_id' => 0,
            ]
        );

        $pdu = $this->createItem(
            \PDU::class,
            [
                'name' => 'Test PDU',
                'entities_id' => 0,
            ]
        );

        $pdur = new \PDU_Rack();

        $good_input = [
            'racks_id' => $rack->getID(),
            'pdus_id' => $pdu->getID(),
            'side' => 1,
            'position' => 1,
            'bgcolor' => '#ff9d1f',
        ];

        $input = $good_input;
        unset($input['racks_id']);
        unset($input['pdus_id']);
        unset($input['position']);
        unset($input['side']);
        $this->assertFalse(
            $pdur->add($input)
        );
        $this->hasSessionMessages(
            ERROR,
            [
                'A pdu is required',
                'A rack is required',
                'A position is required',
                'A side is required',
            ]
        );

        $input = $good_input;
        $input['racks_id'] = 0;
        $input['pdus_id'] = 0;
        $this->assertFalse(
            $pdur->add($input)
        );
        $this->hasSessionMessages(ERROR, ['A pdu is required', 'A rack is required']);

        $this->assertGreaterThan(0, $pdur->add($good_input));
    }
}
