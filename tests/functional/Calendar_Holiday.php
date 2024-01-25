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

class Calendar_Holiday extends DbTestCase
{
    public function testGetHolidaysForCalendar()
    {

        $calendar_holiday = new \Calendar_Holiday();

        $default_calendar_id = getItemByTypeName('Calendar', 'Default', true);

       // No default holidays
        $holidays = $calendar_holiday->getHolidaysForCalendar($default_calendar_id);
        $this->array($holidays)->isEqualTo([]);

       // Add holidays
        $this->addHolidaysToCalendar($default_calendar_id);

        $holidays = $calendar_holiday->getHolidaysForCalendar($default_calendar_id);
        $this->array($holidays)->isEqualTo(
            [
                ['begin_date' => '2000-01-01', 'end_date' => '2000-01-01', 'is_perpetual' => 1],
                ['begin_date' => '2020-04-06', 'end_date' => '2020-04-17', 'is_perpetual' => 0],
                ['begin_date' => '2020-08-03', 'end_date' => '2020-08-21', 'is_perpetual' => 0],
                ['begin_date' => '2020-12-21', 'end_date' => '2020-12-25', 'is_perpetual' => 0],
            ]
        );

       // Check that calendar filtering is not buggy
        $calendar = new Calendar();
        $calendar_id = $calendar->add(['name' => 'Test']);
        $this->integer($calendar_id)->isGreaterThan(0);

        $holidays = $calendar_holiday->getHolidaysForCalendar($calendar_id);
        $this->array($holidays)->isEqualTo([]);
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
        $this->boolean($GLPI_CACHE->has($cache_key))->isFalse();

       // Validate that cache is set on reading
        $this->validateHolidayCacheMatchesMethodResult($default_calendar_id);

       // Validate that there is no DB operation when cache is set
        global $DB;
        $db_back = $DB;
        $DB = null; // Setting $DB to null will result in an error if something tries to request DB
        $holidays = $calendar_holiday->getHolidaysForCalendar($default_calendar_id);
        $DB = $db_back;
        $this->array($GLPI_CACHE->get($cache_key))->isEqualTo($holidays);

       // Validate that cache is invalidated when holidays are added
        $this->boolean($GLPI_CACHE->has($cache_key))->isTrue();
        $this->addHolidaysToCalendar($default_calendar_id);
        $this->boolean($GLPI_CACHE->has($cache_key))->isFalse();
        $this->validateHolidayCacheMatchesMethodResult($default_calendar_id);

       // Validate that cache is invalidated when holidays are updated
        $holiday_id = getItemByTypeName('Holiday', 'Spring holidays', true);
        $this->boolean($GLPI_CACHE->has($cache_key))->isTrue();
        $holiday->update(['id' => $holiday_id, 'begin_date' => '2020-03-01']);
        $this->boolean($GLPI_CACHE->has($cache_key))->isFalse();
        $this->validateHolidayCacheMatchesMethodResult($default_calendar_id);

       // Validate that cache is invalidated when calendar_holiday is deleted
        $holiday_id = getItemByTypeName('Holiday', 'Spring holidays', true);
        $this->boolean($calendar_holiday->getFromDBByCrit(['holidays_id' => $holiday_id]))->isTrue();
        $this->boolean($GLPI_CACHE->has($cache_key))->isTrue();
        $this->boolean($calendar_holiday->delete(['id' => $calendar_holiday->fields['id']]))->isTrue();
        $this->boolean($GLPI_CACHE->has($cache_key))->isFalse();
        $this->validateHolidayCacheMatchesMethodResult($default_calendar_id);

       // Validate that cache is invalidated when holiday is deleted
        $holiday_id = getItemByTypeName('Holiday', 'Summer holidays', true);
        $this->boolean($GLPI_CACHE->has($cache_key))->isTrue();
        $this->boolean($holiday->delete(['id' => $holiday_id, true]))->isTrue();
        $this->boolean($GLPI_CACHE->has($cache_key))->isFalse();
        $this->validateHolidayCacheMatchesMethodResult($default_calendar_id);

       // Validate that cache is invalidated when calendar_holiday is updated
       // Cannot be tested as `CommonDBRelation::prepareInputForUpdate()` refuses the update.
       // I choosed to keep invalidation code in `Calendar_Holiday::post_updateItem()` to be sure to handle this case
       // if update becomes possible in the future.
       /*
       $calendar = new Calendar();
       $calendar_id = $calendar->add(['name' => 'Test']);
       $this->integer($calendar_id)->isGreaterThan(0);

       $this->validateHolidayCacheMatchesMethodResult($calendar_id);

       $holiday_id = getItemByTypeName('Holiday', 'Winter holidays', true);
       $this->boolean($calendar_holiday->getFromDBByCrit(['holidays_id' => $holiday_id]))->isTrue();
       $this->boolean(
         $calendar_holiday->update(['id' => $calendar_holiday->fields['id'], 'calendars_id' => $calendar_id])
       )->isTrue();
       $this->boolean($GLPI_CACHE->has($cache_key))->isFalse(); // Previously associated calendar cache is invalidated
       $this->boolean($GLPI_CACHE->has(sprintf('calendar-%s-holidays', $calendar_id)))->isFalse();
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
        $this->integer($holiday_id)->isGreaterThan(0);
        $calendar_holiday_id = (int)$calendar_holiday->add(
            [
                'holidays_id'  => $holiday_id,
                'calendars_id' => $calendar_id,
            ]
        );
        $this->integer($calendar_holiday_id)->isGreaterThan(0);

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
        $this->integer($holiday_id)->isGreaterThan(0);
        $calendar_holiday_id = (int)$calendar_holiday->add(
            [
                'holidays_id'  => $holiday_id,
                'calendars_id' => $calendar_id,
            ]
        );
        $this->integer($calendar_holiday_id)->isGreaterThan(0);

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
        $this->integer($holiday_id)->isGreaterThan(0);
        $calendar_holiday_id = (int)$calendar_holiday->add(
            [
                'holidays_id'  => $holiday_id,
                'calendars_id' => $calendar_id,
            ]
        );
        $this->integer($calendar_holiday_id)->isGreaterThan(0);

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
        $this->integer($holiday_id)->isGreaterThan(0);
        $calendar_holiday_id = (int)$calendar_holiday->add(
            [
                'holidays_id'  => $holiday_id,
                'calendars_id' => $calendar_id,
            ]
        );
        $this->integer($calendar_holiday_id)->isGreaterThan(0);
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
        $this->boolean($GLPI_CACHE->has($cache_key))->isTrue();
        $this->array($GLPI_CACHE->get($cache_key))->isEqualTo($holidays);
    }
}
