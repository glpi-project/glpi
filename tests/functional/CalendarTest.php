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

use CalendarSegment;
use DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/* Test for inc/calendar.class.php */

class CalendarTest extends DbTestCase
{
    /**
     * Data provider for the testComputeEndDate function
     *
     * @return iterable
     */
    protected function testComputeEndDateProvider(): iterable
    {
        // Default calendar
        $default_calendar = getItemByTypeName('Calendar', 'Default');

        // 5x24h (monday to friday)
        $five_by_twentyfour = $this->createItem(\Calendar::class, [
            'name' => "5x24",
        ]);
        $parent = $five_by_twentyfour->getID();
        $this->createItems(CalendarSegment::class, [
            ['calendars_id' => $parent, 'day' => 1, 'begin' => '00:00:00', 'end' => '24:00:00'],
            ['calendars_id' => $parent, 'day' => 2, 'begin' => '00:00:00', 'end' => '24:00:00'],
            ['calendars_id' => $parent, 'day' => 3, 'begin' => '00:00:00', 'end' => '24:00:00'],
            ['calendars_id' => $parent, 'day' => 4, 'begin' => '00:00:00', 'end' => '24:00:00'],
            ['calendars_id' => $parent, 'day' => 5, 'begin' => '00:00:00', 'end' => '24:00:00'],
        ]);

        // 1st case: future date
        $parameters = ["2018-11-19 10:00:00", 7 * DAY_TIMESTAMP, 0, true];
        yield [$default_calendar, $parameters, "2018-11-28 10:00:00"];
        yield [$five_by_twentyfour, $parameters, "2018-11-28 10:00:00"];

        // 2nd case: future date + end of day parameter
        $parameters = ["2018-11-19 10:00:00", 7 * DAY_TIMESTAMP, 0, true, true];
        yield [$default_calendar, $parameters, "2018-11-28 20:00:00"];
        yield [$five_by_twentyfour, $parameters, "2018-11-29 00:00:00"];

        // 3rd case: past date
        $parameters = ["2018-11-19 10:00:00", -7 * DAY_TIMESTAMP, 0, true];
        yield [$default_calendar, $parameters, "2018-11-08 10:00:00"];
        yield [$five_by_twentyfour, $parameters, "2018-11-08 10:00:00"];

        // 4th case: past date + end of day parameter
        $parameters = ["2018-11-19 10:00:00", -7 * DAY_TIMESTAMP, 0, true, true];
        yield [$default_calendar, $parameters, "2018-11-08 20:00:00"];
        yield [$five_by_twentyfour, $parameters, "2018-11-09 00:00:00"];
    }

    /**
     * Test cases for the computeEndDate function
     *
     * @return void
     */
    public function testComputeEndDate(): void
    {
        $provider = $this->testComputeEndDateProvider();
        foreach ($provider as $row) {
            /** @var \Calendar $calendar */
            $calendar = $row[0];
            /** @var array $compute_end_date_parameters */
            $compute_end_date_parameters = $row[1];
            /** @var string $expected_date */
            $expected_date = $row[2];

            $this->assertEquals(
                $expected_date,
                $calendar->computeEndDate(...$compute_end_date_parameters)
            );
        }
    }

    public static function activeProvider()
    {
        return [
            [
                'start'  => '2019-01-01 07:00:00',
                'end'    => '2019-01-01 09:00:00',
                'value'  => HOUR_TIMESTAMP,
            ], [
                'start'  => '2019-01-01 06:00:00',
                'end'    => '2019-01-01 07:00:00',
                'value'  => 0,
            ], [
                'start'  => '2019-01-01 00:00:00',
                'end'    => '2019-01-08 00:00:00',
                'value'  => 12 * HOUR_TIMESTAMP * 5,
            ], [
                'start'  => '2019-01-08 00:00:00',
                'end'    => '2019-01-01 00:00:00',
                'value'  => 0,
            ], [
                'start'  => '2019-01-01 07:00:00',
                'end'    => '2019-01-01 09:00:00',
                'value'  => HOUR_TIMESTAMP * 2,
                'days'   => true,
            ], [
                'start'  => '2019-01-01 00:00:00',
                'end'    => '2019-01-08 00:00:00',
                'value'  => WEEK_TIMESTAMP,
                'days'   => true,
            ],
        ];
    }

    #[DataProvider('activeProvider')]
    public function testGetActiveTimeBetween($start, $end, $value, $days = false)
    {
        $calendar = new \Calendar();
        $this->assertTrue($calendar->getFromDB(1)); //get default calendar

        $this->assertEquals(
            $value,
            $calendar->getActiveTimeBetween(
                $start,
                $end,
                $days
            )
        );
    }

    public static function workingdayProvider()
    {
        return [
            ['2019-01-01 00:00:00', true],
            ['2019-01-02 00:00:00', true],
            ['2019-01-03 00:00:00', true],
            ['2019-01-04 00:00:00', true],
            ['2019-01-05 00:00:00', false],
            ['2019-01-06 00:00:00', false],
        ];
    }

    #[DataProvider('workingdayProvider')]
    public function testIsAWorkingDay($date, $expected)
    {
        $calendar = new \Calendar();
        $this->assertTrue($calendar->getFromDB(1)); //get default calendar

        $this->assertSame($expected, $calendar->isAWorkingDay(strtotime($date)));
    }

    public function testHasAWorkingDay()
    {
        $calendar = new \Calendar();
        $this->assertTrue($calendar->getFromDB(1)); //get default calendar
        $this->assertTrue($calendar->hasAWorkingDay());

        $cid = $calendar->add([
            'name'   => 'Test',
        ]);
        $this->assertGreaterThan(0, $cid);
        $this->assertTrue($calendar->getFromDB($cid));
        $this->assertFalse($calendar->hasAWorkingDay());
    }

    public static function workinghourProvider()
    {
        return [
            ['2019-01-01 00:00:00', false],
            ['2019-01-02 08:30:00', true],
            ['2019-01-03 18:10:00', true],
            ['2019-01-04 21:00:00', false],
            ['2019-01-05 08:30:00', false],
            ['2019-01-06 00:00:00', false],
        ];
    }

    #[DataProvider('workinghourProvider')]
    public function testIsAWorkingHour($date, $expected)
    {
        $calendar = new \Calendar();
        $this->assertTrue($calendar->getFromDB(1)); //get default calendar

        $this->assertSame($expected, $calendar->isAWorkingHour(strtotime($date)));
    }

    private function addXmas(\Calendar $calendar)
    {
        $calendar_holiday = new \Calendar_Holiday();
        $this->assertGreaterThan(
            0,
            (int) $calendar_holiday->add([
                'calendars_id' => $calendar->fields['id'],
                'holidays_id'  => getItemByTypeName('Holiday', 'X-Mas', true),
            ])
        );

        $this->checkXmas($calendar);
    }

    private function checkXmas(\Calendar $calendar)
    {
        $this->assertFalse(
            $calendar->isHoliday('2018-01-01')
        );

        $this->assertTrue(
            $calendar->isHoliday('2019-01-01')
        );
    }

    public function testIsHoliday()
    {
        $calendar = new \Calendar();
        // get Default calendar
        $this->assertTrue($calendar->getFromDB(getItemByTypeName('Calendar', 'Default', true)));

        $this->addXmas($calendar);

        $dates = [
            '2019-05-01'   => true,
            '2019-05-02'   => false,
            '2019-07-01'   => false,
            '2019-07-12'   => true,
        ];

        //no holiday by default
        foreach (array_keys($dates) as $date) {
            $this->assertFalse($calendar->isHoliday($date));
        }

        //Add holidays
        $calendar_holiday = new \Calendar_Holiday();
        $holiday = new \Holiday();
        $hid = (int) $holiday->add([
            'name'         => '1st of may',
            'entities_id'  => 0,
            'is_recursive' => 1,
            'begin_date'   => '2019-05-01',
            'end_date'     => '2019-05-01',
            'is_perpetual' => 1,
        ]);
        $this->assertGreaterThan(0, $hid);
        $this->assertGreaterThan(
            0,
            (int) $calendar_holiday->add([
                'holidays_id'  => $hid,
                'calendars_id' => $calendar->fields['id'],
            ])
        );

        $hid = (int) $holiday->add([
            'name'   => 'Summer vacations',
            'entities_id'  => 0,
            'is_recursive' => 1,
            'begin_date'   => '2019-07-08',
            'end_date'     => '2019-09-01',
            'is_perpetual' => 0,
        ]);
        $this->assertGreaterThan(0, $hid);
        $this->assertGreaterThan(
            0,
            (int) $calendar_holiday->add([
                'holidays_id'  => $hid,
                'calendars_id' => $calendar->fields['id'],
            ])
        );

        foreach ($dates as $date => $expected) {
            $this->assertSame($expected, $calendar->isHoliday($date));
        }
    }

    public function testClone()
    {
        $calendar = new \Calendar();
        $default_id = getItemByTypeName('Calendar', 'Default', true);
        // get Default calendar
        $this->assertTrue($calendar->getFromDB($default_id));
        $this->addXmas($calendar);

        $id = $calendar->clone();
        $this->assertGreaterThan(0, $id);
        $this->assertTrue($calendar->getFromDB($id));
        //should have been duplicated too.
        $this->checkXmas($calendar);

        //change name, and clone again
        $this->updateItem('Calendar', $id, ['name' => "Je s'apelle Groot"]);

        $calendar = new \Calendar();
        $this->assertTrue($calendar->getFromDB($id));

        $id = $calendar->clone();
        $this->assertGreaterThan($default_id, $id);
        $this->assertTrue($calendar->getFromDB($id));
        $this->assertEquals(
            "Je s'apelle Groot (copy)",
            $calendar->fields['name']
        );
        //should have been duplicated too.
        $this->checkXmas($calendar);
    }
}
