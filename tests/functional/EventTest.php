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

use Glpi\Event;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class EventTest extends DbTestCase
{
    #[DataProvider('eventLogLevelThresholdDataProvider')]
    public function testEventLogThreshold(int $level, int $threshold, bool $should_be_logged_in_file, bool $should_be_logged_in_db): void
    {
        // --- arrange
        global $CFG_GLPI;

        $CFG_GLPI["use_log_in_files"] = 1; // ensure file logging is enabled
        $CFG_GLPI["event_loglevel"] = $threshold;
        $log_file = GLPI_LOG_DIR . "/event.log"; // event.log is hardcoded \Glpi\Event::log()
        $log_service = $this->getUniqueString();
        file_put_contents($log_file, ""); // empty log file for easier debugging

        // --- act
        Event::log(0, self::class, $level, $log_service, 'event');

        // --- assert
        // logged in DB
        $expected_count = $should_be_logged_in_db ? 1 : 0;
        $this->assertEquals(
            $expected_count,
            countElementsInTable(
                Event::getTable(),
                ['service' => $log_service, 'level' => $level]
            )
        );

        // logged in file
        $this->assertEquals(
            $should_be_logged_in_file,
            str_contains(file_get_contents($log_file), "[$log_service]")
        );
    }

    public static function eventLogLevelThresholdDataProvider(): iterable
    {
        foreach (range(1, 5) as $threshold) {
            foreach (range(1, 5) as $level) {
                yield "level=$level, threshold=$threshold" => [
                    'level' => $level,
                    'threshold' => $threshold,
                    'should_be_logged_in_file'
                        => ($level <= $threshold) // should be logged if level is equal or more critical than threshold (notice glpi level are inverted compared to PSR)
                        && $level <= 3, // no logging for level higher than 3
                    'should_be_logged_in_db' => $level <= $threshold,
                ];
            }
        }
    }
}
