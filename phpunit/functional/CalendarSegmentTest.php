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

use CalendarSegment;
use DbTestCase;

class CalendarSegmentTest extends DbTestCase
{
    /**
     * Data provider for testAddDelayInDay function
     * Tests various scenarios with positive and negative delays, single and multiple segments
     *
     * @return iterable
     */
    public function addDelayInDayProvider(): iterable
    {
        // POSITIVE DELAY TESTS

        // Test 1: Simple positive delay within a single segment
        yield [
            'day' => 1,
            'begin_time' => '10:00:00',
            'delay' => 2 * HOUR_TIMESTAMP,
            'negative_delay' => false,
            'expected' => '12:00:00',
        ];

        // Test 2: Positive delay from start to end of segment
        yield [
            'day' => 1,
            'begin_time' => '09:00:00',
            'delay' => 9 * HOUR_TIMESTAMP,
            'negative_delay' => false,
            'expected' => '18:00:00',
        ];

        // Test 3: Positive delay spanning two segments on same day
        yield [
            'day' => 2,
            'begin_time' => '10:00:00',
            'delay' => 5 * HOUR_TIMESTAMP,
            'negative_delay' => false,
            'expected' => '17:00:00',
        ];

        // Test 4: Positive delay starting before segment
        yield [
            'day' => 2,
            'begin_time' => '07:00:00',
            'delay' => 3 * HOUR_TIMESTAMP,
            'negative_delay' => false,
            'expected' => '11:00:00',
        ];

        // Test 5: Positive delay starting in gap between segments
        yield [
            'day' => 2,
            'begin_time' => '13:00:00',
            'delay' => 2 * HOUR_TIMESTAMP,
            'negative_delay' => false,
            'expected' => '16:00:00',
        ];

        // Test 6: Positive delay exceeding available time
        yield [
            'day' => 1,
            'begin_time' => '17:00:00',
            'delay' => 3 * HOUR_TIMESTAMP,
            'negative_delay' => false,
            'expected' => false,
        ];

        // NEGATIVE DELAY TESTS

        // Test 7: Simple negative delay within a single segment
        yield [
            'day' => 1,
            'begin_time' => '15:00:00',
            'delay' => 2 * HOUR_TIMESTAMP,
            'negative_delay' => true,
            'expected' => '13:00:00',
        ];

        // Test 8: Negative delay from end to start of segment
        yield [
            'day' => 1,
            'begin_time' => '18:00:00',
            'delay' => 9 * HOUR_TIMESTAMP,
            'negative_delay' => true,
            'expected' => '09:00:00',
        ];

        // Test 9: Negative delay spanning two segments
        yield [
            'day' => 2,
            'begin_time' => '17:00:00',
            'delay' => 5 * HOUR_TIMESTAMP,
            'negative_delay' => true,
            'expected' => '10:00:00',
        ];

        // Test 10: Negative delay starting after segment
        yield [
            'day' => 1,
            'begin_time' => '19:00:00',
            'delay' => 3 * HOUR_TIMESTAMP,
            'negative_delay' => true,
            'expected' => '15:00:00',
        ];

        // Test 11: Negative delay starting in gap between segments
        yield [
            'day' => 2,
            'begin_time' => '13:00:00',
            'delay' => 2 * HOUR_TIMESTAMP,
            'negative_delay' => true,
            'expected' => '10:00:00',
        ];

        // Test 12: Negative delay exceeding available time
        yield [
            'day' => 2,
            'begin_time' => '10:00:00',
            'delay' => 3 * HOUR_TIMESTAMP,
            'negative_delay' => true,
            'expected' => false,
        ];

        // Test 13: Zero delay
        yield [
            'day' => 1,
            'begin_time' => '12:00:00',
            'delay' => 0,
            'negative_delay' => false,
            'expected' => '12:00:00',
        ];
    }

    /**
     * Test the addDelayInDay method
     *
     * @dataProvider addDelayInDayProvider
     *
     * @param integer      $day              Day number (1|2)
     * @param string       $begin_time       Starting time (HH:MM:SS)
     * @param integer      $delay            Delay in seconds
     * @param boolean      $negative_delay   Whether to subtract time instead of adding
     * @param string|false $expected         Expected result time (HH:MM:SS) or false
     *
     * @return void
     */
    public function testAddDelayInDay(
        int $day,
        string $begin_time,
        int $delay,
        bool $negative_delay,
        $expected
    ): void {
        // Create a calendar with segments on different days
        // Monday (day 1): 09:00-18:00 (9h continuous)
        // Tuesday (day 2): 08:00-12:00 and 14:00-18:00 (8h split)
        $calendar = $this->createItem(\Calendar::class, [
            'name' => 'Test Calendar',
        ]);
        $this->createItems(CalendarSegment::class, [
            ['calendars_id' => $calendar->getID(), 'day' => 1, 'begin' => '09:00:00', 'end' => '18:00:00'],
            ['calendars_id' => $calendar->getID(), 'day' => 2, 'begin' => '08:00:00', 'end' => '12:00:00'],
            ['calendars_id' => $calendar->getID(), 'day' => 2, 'begin' => '14:00:00', 'end' => '18:00:00'],
        ]);

        $result = CalendarSegment::addDelayInDay(
            $calendar->getID(),
            $day,
            $begin_time,
            $delay,
            $negative_delay
        );

        $this->assertEquals(
            $expected,
            $result
        );
    }
}
