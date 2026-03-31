<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\Api\HL\Controller;

use Computer;
use Glpi\Tests\HLAPITestCase;

class NotepadControllerTest extends HLAPITestCase
{
    public function testAutoSearch()
    {
        $computers_id = getItemByTypeName(Computer::class, '_test_pc03', true);
        $this->api->autoTestSearch("/Computer/$computers_id/Note", [
            [
                'itemtype' => 'Computer',
                'items_id' => $computers_id,
                'content' => 'This is a test note',
            ],
            [
                'itemtype' => 'Computer',
                'items_id' => $computers_id,
                'content' => 'This is a test note2',
            ],
            [
                'itemtype' => 'Computer',
                'items_id' => $computers_id,
                'content' => 'This is a test note3',
            ],
        ], 'content');
    }

    public function testAutoCRUD()
    {
        $computers_id = getItemByTypeName(Computer::class, '_test_pc03', true);
        $this->api->autoTestCRUD("/Computer/$computers_id/Note", [
            'content' => 'This is a test note',
        ], [
            'content' => 'This is an updated test note',
        ]);
    }
}
