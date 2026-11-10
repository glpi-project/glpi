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

namespace tests\units\Glpi\System;

use Computer;
use CronTask;
use Glpi\System\HousekeepingCron;
use Glpi\Tests\DbTestCase;
use Item_Environment;
use Item_Process;
use Log;
use PHPUnit\Framework\Attributes\DataProvider;

class HousekeepingCronTest extends DbTestCase
{
    public static function itemtypeProvider(): iterable
    {
        yield [Item_Process::class];
        yield [Item_Environment::class];
    }

    #[DataProvider('itemtypeProvider')]
    public function testCleanLogs(string $itemtype): void
    {
        $computer = getItemByTypeName(Computer::class, '_test_pc01');
        $common_input = [
            'items_id'      => $computer->getID(),
            'user_name'     => 'test',
            'date_mod'      => $_SESSION['glpi_currenttime'],
        ];

        // 3 entries logged on parent, 2 on item itself
        for ($i = 0; $i < 3; $i++) {
            $this->createItem(Log::class, $common_input + [
                'itemtype'      => Computer::class,
                'itemtype_link' => $itemtype,
                'linked_action' => Log::HISTORY_ADD_SUBITEM,
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            $this->createItem(Log::class, $common_input + [
                'itemtype'      => $itemtype,
                'itemtype_link' => '',
                'linked_action' => Log::HISTORY_LOCK_SUBITEM,
            ]);
        }
        // unrelated entry, must be kept
        $kept = $this->createItem(Log::class, $common_input + [
            'itemtype'      => Computer::class,
            'itemtype_link' => '',
            'linked_action' => Log::HISTORY_CREATE_ITEM,
        ]);

        $this->assertTrue(HousekeepingCron::hasPendingWork());

        $task = new CronTask();
        $this->assertTrue($task->getFromDBbyName(HousekeepingCron::class, HousekeepingCron::TASK_NAME));
        $task->fields['param'] = 4;

        // limit reached, some work remains
        $this->assertSame(-1, HousekeepingCron::cronHousekeeping($task));
        $this->assertSame(
            1,
            countElementsInTable(Log::getTable(), ['itemtype_link' => $itemtype])
            + countElementsInTable(Log::getTable(), ['itemtype' => $itemtype])
        );
        $this->assertTrue(HousekeepingCron::hasPendingWork());

        // remaining entry
        $this->assertSame(1, HousekeepingCron::cronHousekeeping($task));
        $this->assertFalse(HousekeepingCron::hasPendingWork());

        // nothing left to do
        $this->assertSame(0, HousekeepingCron::cronHousekeeping($task));

        $this->assertSame(0, countElementsInTable(Log::getTable(), ['itemtype_link' => $itemtype]));
        $this->assertSame(0, countElementsInTable(Log::getTable(), ['itemtype' => $itemtype]));
        $this->assertTrue((new Log())->getFromDB($kept->getID()));
    }

    public function testExactSizeLastBatch(): void
    {
        $computer = getItemByTypeName(Computer::class, '_test_pc01');
        for ($i = 0; $i < 4; $i++) {
            $this->createItem(Log::class, [
                'itemtype'      => Computer::class,
                'items_id'      => $computer->getID(),
                'itemtype_link' => Item_Process::class,
                'linked_action' => Log::HISTORY_ADD_SUBITEM,
                'user_name'     => 'test',
                'date_mod'      => $_SESSION['glpi_currenttime'],
            ]);
        }

        $task = new CronTask();
        $this->assertTrue($task->getFromDBbyName(HousekeepingCron::class, HousekeepingCron::TASK_NAME));
        $task->fields['param'] = 4;

        // limit reached, but nothing remains: fully processed
        $this->assertSame(1, HousekeepingCron::cronHousekeeping($task));
        $this->assertFalse(HousekeepingCron::hasPendingWork());
    }
}
