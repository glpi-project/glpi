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
use Config;
use Glpi\Tests\DbTestCase;
use Log;
use PurgeLogs;
use RefusedEquipment;

use function Safe\strtotime;

class PurgeLogsTest extends DbTestCase
{
    /**
     * Insert a log entry and return its ID.
     */
    private function addLog(
        string $itemtype,
        int $items_id,
        string $date_mod,
        int $linked_action = Log::HISTORY_LOG_SIMPLE_MESSAGE,
        int $id_search_option = 0
    ): int {
        global $DB;

        $DB->insert(
            'glpi_logs',
            [
                'itemtype'         => $itemtype,
                'items_id'         => $items_id,
                'linked_action'    => $linked_action,
                'id_search_option' => $id_search_option,
                'date_mod'         => $date_mod,
            ]
        );
        return $DB->insertId();
    }

    public function testPurgeRefusedLogs(): void
    {
        global $CFG_GLPI;

        $refused = $this->createItem(RefusedEquipment::class, [
            'name'     => __FUNCTION__,
            'itemtype' => Computer::class,
        ]);
        $computer = getItemByTypeName(Computer::class, '_test_pc01', true);

        $old_date    = date('Y-m-d H:i:s', strtotime('-2 months'));
        $recent_date = date('Y-m-d H:i:s');

        $old_refused_log    = $this->addLog(RefusedEquipment::class, $refused->getID(), $old_date);
        $recent_refused_log = $this->addLog(RefusedEquipment::class, $refused->getID(), $recent_date);
        $old_computer_log   = $this->addLog(Computer::class, $computer, $old_date);

        // Keep all: nothing is purged
        $CFG_GLPI['purge_refusedequipment'] = Config::KEEP_ALL;
        PurgeLogs::purgeRefusedLogs();
        $this->assertTrue((new Log())->getFromDB($old_refused_log));
        $this->assertTrue((new Log())->getFromDB($recent_refused_log));
        $this->assertTrue((new Log())->getFromDB($old_computer_log));

        // Older than 1 month: only old refused equipment logs are purged
        $CFG_GLPI['purge_refusedequipment'] = 1;
        PurgeLogs::purgeRefusedLogs();
        $this->assertFalse((new Log())->getFromDB($old_refused_log));
        $this->assertTrue((new Log())->getFromDB($recent_refused_log));
        $this->assertTrue((new Log())->getFromDB($old_computer_log));

        // Refused equipment itself must not be purged
        $this->assertTrue((new RefusedEquipment())->getFromDB($refused->getID()));
    }

    public function testPurgeDevices(): void
    {
        global $CFG_GLPI;

        $computer    = getItemByTypeName(Computer::class, '_test_pc01', true);
        $old_date    = date('Y-m-d H:i:s', strtotime('-2 months'));
        $recent_date = date('Y-m-d H:i:s');

        $logs = [];
        foreach (
            [
                Log::HISTORY_ADD_DEVICE,
                Log::HISTORY_UPDATE_DEVICE,
                Log::HISTORY_DELETE_DEVICE,
                Log::HISTORY_CONNECT_DEVICE,
                Log::HISTORY_DISCONNECT_DEVICE,
            ] as $action
        ) {
            $logs[$action] = [
                'old'    => $this->addLog(Computer::class, $computer, $old_date, $action),
                'recent' => $this->addLog(Computer::class, $computer, $recent_date, $action),
            ];
        }

        $CFG_GLPI['purge_adddevice']        = 1;
        $CFG_GLPI['purge_updatedevice']     = 1;
        $CFG_GLPI['purge_deletedevice']     = Config::KEEP_ALL;
        $CFG_GLPI['purge_connectdevice']    = Config::DELETE_ALL;
        $CFG_GLPI['purge_disconnectdevice'] = 3;

        PurgeLogs::purgeDevices();

        $expected = [
            Log::HISTORY_ADD_DEVICE        => ['old' => false, 'recent' => true],
            Log::HISTORY_UPDATE_DEVICE     => ['old' => false, 'recent' => true],
            Log::HISTORY_DELETE_DEVICE     => ['old' => true,  'recent' => true],
            Log::HISTORY_CONNECT_DEVICE    => ['old' => false, 'recent' => false],
            Log::HISTORY_DISCONNECT_DEVICE => ['old' => true,  'recent' => true],
        ];
        foreach ($expected as $action => $exists) {
            foreach ($exists as $age => $should_exist) {
                $this->assertSame(
                    $should_exist,
                    (new Log())->getFromDB($logs[$action][$age]),
                    sprintf('Unexpected state for %s log of action %d', $age, $action)
                );
            }
        }
    }

    public function testPurgeOthers(): void
    {
        global $CFG_GLPI;

        $computer = getItemByTypeName(Computer::class, '_test_pc01', true);
        $old_date = date('Y-m-d H:i:s', strtotime('-2 months'));

        $comments_log = $this->addLog(Computer::class, $computer, $old_date, 0, 16);
        $datemod_log  = $this->addLog(Computer::class, $computer, $old_date, 0, 19);
        $other_log    = $this->addLog(Computer::class, $computer, $old_date, 0, 1);

        $CFG_GLPI['purge_comments'] = 1;
        $CFG_GLPI['purge_datemod']  = 1;

        PurgeLogs::purgeOthers();

        $this->assertFalse((new Log())->getFromDB($comments_log));
        $this->assertFalse((new Log())->getFromDB($datemod_log));
        $this->assertTrue((new Log())->getFromDB($other_log));
    }
}
