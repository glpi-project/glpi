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

namespace tests\units;

use Computer;
use Glpi\Tests\DbTestCase;
use ObjectLock;

class ObjectLockTest extends DbTestCase
{
    public function testGetFormURLWithID(): void
    {
        $computer = new Computer();
        $computer->add([
            'name' => 'Test Computer',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        $objectLock = new ObjectLock();
        $objectLock->add([
            'itemtype' => Computer::class,
            'items_id' => $computer->getID(),
            'users_id' => 1,
            'date' => date('Y-m-d H:i:s'),
        ]);

        $this->assertEquals(
            "/front/computer.form.php?id={$computer->getID()}",
            ObjectLock::getFormURLWithID($objectLock->getID(), false)
        );
    }
}
