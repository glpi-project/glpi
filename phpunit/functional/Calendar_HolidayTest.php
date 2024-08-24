<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

use Calendar;
use DbTestCase;
use Holiday;

/* Test for inc/calendar_holiday.class.php */

class Calendar_HolidayTest extends DbTestCase
{
    public function testGetHolidaysForCalendar()
    {

        $calendar_holiday = new \Calendar_Holiday();

        $default_calendar_id = getItemByTypeName('Calendar', 'Default', true);

       // No default holidays
        $holidays = $calendar_holiday->getHolidaysForCalendar($default_calendar_id);
        $this->assertEquals([], $holidays);

       // Add holidays
        $this->addHolidaysToCalendar($default_calendar_id);

        $holidays = $calendar_holiday->getHolidaysForCalendar($default_calendar_id);
        $this->assertEquals(
            [
                ['begin_date' => '2000-01-01', 'end_date' => '2000-01-01', 'is_perpetual' => 1],
                ['begin_date' => '2020-04-06', 'end_date' => '2020-04-17', 'is_perpetual' => 0],
                ['begin_date' => '2020-08-03', 'end_date' => '2020-08-21', 'is_perpetual' => 0],
                ['begin_date' => '2020-12-21', 'end_date' => '2020-12-25', 'is_perpetual' => 0],
            ],
            $holidays
        );

       // Check that calendar filtering is not buggy
        $calendar = new Calendar();
        $calendar_id = $calendar->add(['name' => 'Test']);
        $this->assertGreaterThan(0, $calendar_id);

        $holidays = $calendar_holiday->getHolidaysForCalendar($calendar_id);
        $this->assertEquals([], $holidays);
    }

    /**
     * @tags cache
     */
    public function testHolidaysCache()
    {
        global $GLPI_CACHE;

        $holiday = new Holiday();
        $calendar_holiday = new \Calendar_Holiday();

        $default_calendar_id = getItemByTypeName('Calendar', 'Default', true);
        $cache_key = sprintf('calendar-%s-holidays', $default_calendar_id);

        // No default cache (cache is set on reading operations)
        $this->assertFalse($GLPI_CACHE->has($cache_key));

        // Validate that cache is set on reading
        $this->validateHolidayCacheMatchesMethodResult($default_calendar_id);

        // Validate that there is no DB operation when cache is set
        global $DB;
        $db_back = $DB;
        $DB = null; // Setting $DB to null will result in an error if something tries to request DB
        $holidays = $calendar_holiday->getHolidaysForCalendar($default_calendar_id);
        $DB = $db_back;
        $this->assertEquals($holidays, $GLPI_CACHE->get($cache_key));

        // Validate that cache is invalidated when holidays are added
        $this->assertTrue($GLPI_CACHE->has($cache_key));
        $this->addHolidaysToCalendar($default_calendar_id);
        $this->assertFalse($GLPI_CACHE->has($cache_key));
        $this->validateHolidayCacheMatchesMethodResult($default_calendar_id);

        // Validate that cache is invalidated when holidays are updated
        $holiday_id = getItemByTypeName('Holiday', 'Spring holidays', true);
        $this->assertTrue($GLPI_CACHE->has($cache_key));
        $holiday->update(['id' => $holiday_id, 'begin_date' => '2020-03-01']);
        $this->assertFalse($GLPI_CACHE->has($cache_key));
        $this->validateHolidayCacheMatchesMethodResult($default_calendar_id);

        // Validate that cache is invalidated when calendar_holiday is deleted
        $holiday_id = getItemByTypeName('Holiday', 'Spring holidays', true);
        $this->assertTrue($calendar_holiday->getFromDBByCrit(['holidays_id' => $holiday_id]));
        $this->assertTrue($GLPI_CACHE->has($cache_key));
        $this->assertTrue($calendar_holiday->delete(['id' => $calendar_holiday->fields['id']]));
        $this->assertFalse($GLPI_CACHE->has($cache_key));
        $this->validateHolidayCacheMatchesMethodResult($default_calendar_id);

        // Validate that cache is invalidated when holiday is deleted
        $holiday_id = getItemByTypeName('Holiday', 'Summer holidays', true);
        $this->assertTrue($GLPI_CACHE->has($cache_key));
        $this->assertTrue($holiday->delete(['id' => $holiday_id, true]));
        $this->assertFalse($GLPI_CACHE->has($cache_key));
        $this->validateHolidayCacheMatchesMethodResult($default_calendar_id);

        // Validate that cache is invalidated when calendar_holiday is updated
        // Cannot be tested as `CommonDBRelation::prepareInputForUpdate()` refuses the update.
        // I choosed to keep invalidation code in `Calendar_Holiday::post_updateItem()` to be sure to handle this case
        // if update becomes possible in the future.
        /*
        $calendar = new Calendar();
        $calendar_id = $calendar->add(['name' => 'Test']);
        $this->assertGreaterThan(0, $calendar_id);

        $this->validateHolidayCacheMatchesMethodResult($calendar_id);

        $holiday_id = getItemByTypeName('Holiday', 'Winter holidays', true);
        $this->assertTrue($calendar_holiday->getFromDBByCrit(['holidays_id' => $holiday_id]));
        $this->boolean(
          $calendar_holiday->update(['id' => $calendar_holiday->fields['id'], 'calendars_id' => $calendar_id])
        )->isTrue();
        $this->assertFalse($GLPI_CACHE->has($cache_key)); // Previously associated calendar cache is invalidated
        $this->assertFalse($GLPI_CACHE->has(sprintf('calendar-%s-holidays', $calendar_id)));
        */
    }

    /**
     * Add some holidays on given calendar.
     *
     * @param int $calendar_id
     *
     * @return void
     */
    private function addHolidaysToCalendar(int $calendar_id): void
    {
        $holiday = new Holiday();
        $calendar_holiday = new \Calendar_Holiday();

        $holiday_id = (int)$holiday->add(
            [
                'name'         => 'New Year՛s Day',
                'entities_id'  => 0,
                'is_recursive' => 1,
                'begin_date'   => '2000-01-01',
                'end_date'     => '2000-01-01',
                'is_perpetual' => 1,
            ]
        );
        $this->assertGreaterThan(0, $holiday_id);
        $calendar_holiday_id = (int)$calendar_holiday->add(
            [
                'holidays_id'  => $holiday_id,
                'calendars_id' => $calendar_id,
            ]
        );
        $this->assertGreaterThan(0, $calendar_holiday_id);

        $holiday_id = (int)$holiday->add(
            [
                'name'         => 'Spring holidays',
                'entities_id'  => 0,
                'is_recursive' => 1,
                'begin_date'   => '2020-04-06',
                'end_date'     => '2020-04-17',
                'is_perpetual' => 0,
            ]
        );
        $this->assertGreaterThan(0, $holiday_id);
        $calendar_holiday_id = (int)$calendar_holiday->add(
            [
                'holidays_id'  => $holiday_id,
                'calendars_id' => $calendar_id,
            ]
        );
        $this->assertGreaterThan(0, $calendar_holiday_id);

        $holiday_id = (int)$holiday->add(
            [
                'name'         => 'Summer holidays',
                'entities_id'  => 0,
                'is_recursive' => 1,
                'begin_date'   => '2020-08-03',
                'end_date'     => '2020-08-21',
                'is_perpetual' => 0,
            ]
        );
        $this->assertGreaterThan(0, $holiday_id);
        $calendar_holiday_id = (int)$calendar_holiday->add(
            [
                'holidays_id'  => $holiday_id,
                'calendars_id' => $calendar_id,
            ]
        );
        $this->assertGreaterThan(0, $calendar_holiday_id);

        $holiday_id = (int)$holiday->add(
            [
                'name'         => 'Winter holidays',
                'entities_id'  => 0,
                'is_recursive' => 1,
                'begin_date'   => '2020-12-21',
                'end_date'     => '2020-12-25',
                'is_perpetual' => 0,
            ]
        );
        $this->assertGreaterThan(0, $holiday_id);
        $calendar_holiday_id = (int)$calendar_holiday->add(
            [
                'holidays_id'  => $holiday_id,
                'calendars_id' => $calendar_id,
            ]
        );
        $this->assertGreaterThan(0, $calendar_holiday_id);
    }

    /**
     * Validate that holidays cache for given calendar matches "getHolidaysForCalendar" method result.
     *
     * @param int $calendar_id
     *
     * @return void
     */
    private function validateHolidayCacheMatchesMethodResult(int $calendar_id): void
    {
        global $GLPI_CACHE;

        $calendar_holiday = new \Calendar_Holiday();
        $cache_key = sprintf('calendar-%s-holidays', $calendar_id);

        $holidays = $calendar_holiday->getHolidaysForCalendar($calendar_id);
        $this->assertTrue($GLPI_CACHE->has($cache_key));
        $this->assertEquals($holidays, $GLPI_CACHE->get($cache_key));
    }
}
