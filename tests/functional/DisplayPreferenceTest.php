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

use DisplayPreference;
use Glpi\Tests\DbTestCase;

class DisplayPreferenceTest extends DbTestCase
{
    public function testCanAddOneDisplayPreference()
    {
        $instance = new DisplayPreference();
        $instance->updateOrder('ComputerType', 0, [
            0 => 16,
        ]);
        $instance->getFromDBByCrit([
            'itemtype'  => 'ComputerType',
            'users_id'  => 0,
            'interface' => 'central',
            'num'       => 16,
            'rank'      => 0,
        ]);
        $this->assertFalse($instance->isNewItem());

        // Add a second display preference
        $instance->updateOrder('ComputerType', 0, [
            0 => '16',
            1 => '121',
        ]);
        // Check the previous preference is still there
        $instance->getFromDBByCrit([
            'itemtype'  => 'ComputerType',
            'users_id'  => 0,
            'interface' => 'central',
            'num'       => 16,
            'rank'      => 0,
        ]);
        $this->assertFalse($instance->isNewItem());
        // Check the new preference is actually added
        $instance->getFromDBByCrit([
            'itemtype'  => 'ComputerType',
            'users_id'  => 0,
            'interface' => 'central',
            'num'       => 121,
            'rank'      => 1,
        ]);
        $this->assertFalse($instance->isNewItem());
    }

    public function testCanRemoveAllDisplayPreferences()
    {
        $instance = new DisplayPreference();
        // Add one display preferences
        $instance->updateOrder('ComputerType', 0, [
            0 => '16',
        ]);
        // Remove it
        $instance->updateOrder('ComputerType', 0, []);
        $rows = $instance->find([
            'itemtype' => 'ComputerType',
            'users_id' => 0,
            'interface' => 'central',
        ]);
        $this->assertSame(0, count($rows));

        // Add several display preferences
        $instance->updateOrder('ComputerType', 0, [
            0 => '16',
            1 => '121',
        ]);
        // Remove them
        $instance->updateOrder('ComputerType', 0, []);
        $rows = $instance->find([
            'itemtype' => 'ComputerType',
            'users_id' => 0,
            'interface' => 'central',
        ]);
        $this->assertSame(0, count($rows));
    }
}
