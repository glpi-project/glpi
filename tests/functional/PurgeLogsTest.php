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
    private function addLog(string $itemtype, int $items_id, string $date_mod): int
    {
        global $DB;

        $DB->insert(
            'glpi_logs',
            [
                'itemtype'      => $itemtype,
                'items_id'      => $items_id,
                'linked_action' => Log::HISTORY_LOG_SIMPLE_MESSAGE,
                'date_mod'      => $date_mod,
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
}
