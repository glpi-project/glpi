<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use \DbTestCase;

/* Test for inc/ticketrecurrent.class.php */

class TicketRecurrent extends DbTestCase {

   /**
    * Data provider for self::testConvertTagToImage().
    */
   protected function computeNextCreationDateProvider() {
      $calendar    = new \Calendar();
      $cal_holiday = new \Calendar_Holiday();
      $cal_segment = new \CalendarSegment();
      $holiday     = new \Holiday();

      $start_of_previous_month = date('Y-m-01 00:00:00', strtotime('-1 month'));
      $end_of_next_year        = date('Y-m-d 23:59:59', strtotime('last day of next year'));

      // Create a calendar where every day except today is a working day.
      // Used to test cases with periodicity smaller than one day.
      $calendar_id = $calendar->add(['name' => 'TicketRecurrent testing calendar']);
      $this->integer($calendar_id)->isGreaterThan(0);

      for ($day = 0; $day <= 6; $day++) {
         if ($day == date('w')) {
            continue;
         }

         $cal_segment_id = $cal_segment->add(
            [
               'calendars_id' => $calendar_id,
               'day'          => $day,
               'begin'        => '09:00:00',
               'end'          => '19:00:00'
            ]
         );
         $this->integer($cal_segment_id)->isGreaterThan(0);
      }

      $data = [
         // Empty begin date
         [
            'begin_date'     => '',
            'end_date'       => $end_of_next_year,
            'periodicity'    => HOUR_TIMESTAMP,
            'create_before'  => 0,
            'calendars_id'   => 0,
            'expected_value' => 'NULL',
         ],
         // Invalid begin date
         [
            'begin_date'     => '',
            'end_date'       => $end_of_next_year,
            'periodicity'    => HOUR_TIMESTAMP,
            'create_before'  => 0,
            'calendars_id'   => 0,
            'expected_value' => 'NULL',
         ],
         // Empty periodicity
         [
            'begin_date'     => $start_of_previous_month,
            'end_date'       => $end_of_next_year,
            'periodicity'    => '',
            'create_before'  => 0,
            'calendars_id'   => 0,
            'expected_value' => 'NULL',
         ],
         // Invalid periodicity
         [
            'begin_date'     => $start_of_previous_month,
            'end_date'       => $end_of_next_year,
            'periodicity'    => '3WEEK',
            'create_before'  => 0,
            'calendars_id'   => 0,
            'expected_value' => 'NULL',
         ],
         // Invalid anticipated creation delay compared to periodicity
         [
            'begin_date'     => $start_of_previous_month,
            'end_date'       => $end_of_next_year,
            'periodicity'    => HOUR_TIMESTAMP,
            'create_before'  => HOUR_TIMESTAMP * 2,
            'calendars_id'   => 0,
            'expected_value' => 'NULL',
            'messages'       => ['Invalid frequency. It must be greater than the preliminary creation.']
         ],
         // End date in past
         [
            'begin_date'     => '2018-03-26 15:00:00',
            'end_date'       => '2019-01-12 00:00:00',
            'periodicity'    => '1MONTH',
            'create_before'  => 0,
            'calendars_id'   => 0,
            'expected_value' => 'NULL',
         ],

         // Valid case: ticket created every hour with no anticipation and no calendar
         [
            'begin_date'     => $start_of_previous_month,
            'end_date'       => $end_of_next_year,
            'periodicity'    => HOUR_TIMESTAMP,
            'create_before'  => 0,
            'calendars_id'   => 0,
            'expected_value' => date('Y-m-d H:00:00', strtotime('+ 1 hour')),
         ],

         // Ticket created every hour with anticipation and no calendar
         [
            'begin_date'     => $start_of_previous_month,
            'end_date'       => $end_of_next_year,
            'periodicity'    => HOUR_TIMESTAMP,
            'create_before'  => HOUR_TIMESTAMP,
            'calendars_id'   => 0,
            'expected_value' => date('Y-m-d H:00:00', strtotime('+ 1 hour')),
         ],

         // Ticket created every hour with anticipation and with calendar, but no end date
         [
            'begin_date'     => $start_of_previous_month,
            'end_date'       => null,
            'periodicity'    => HOUR_TIMESTAMP,
            'create_before'  => HOUR_TIMESTAMP,
            'calendars_id'   => $calendar_id,
            'expected_value' => date('Y-m-d 08:00:00', strtotime('tomorrow')),
         ],

         // Ticket created every hour with no anticipation and with calendar and having a begin date in the future
         // As begin date is inside working hours, first occurence should be on begin date.
         [
            'begin_date'     => date('Y-m-d 09:00:00', strtotime('tomorrow')),
            'end_date'       => $end_of_next_year,
            'periodicity'    => HOUR_TIMESTAMP,
            'create_before'  => 0,
            'calendars_id'   => $calendar_id,
            'expected_value' => date('Y-m-d 09:00:00', strtotime('tomorrow')),
         ],

         // Ticket created every hour with anticipation and with calendar and having a begin date in the future
         // As begin date is outside working hours, first occurence should be on opening hour - anticipation.
         [
            'begin_date'     => date('Y-m-d 04:00:00', strtotime('tomorrow')),
            'end_date'       => $end_of_next_year,
            'periodicity'    => HOUR_TIMESTAMP,
            'create_before'  => HOUR_TIMESTAMP,
            'calendars_id'   => $calendar_id,
            'expected_value' => date('Y-m-d 08:00:00', strtotime('tomorrow')),
         ]
      ];

      // Create a calendar where every day are working days, from 9am to 7pm, but with today as a day off.
      // Used to test cases with periodicity periodicity of at least one day.
      $calendar_id = $calendar->add(['name' => 'TicketRecurrent testing calendar']);
      $this->integer($calendar_id)->isGreaterThan(0);

      $working_days = [1, 2, 3, 4, 5];
      foreach ($working_days as $day) {
         $cal_segment_id = $cal_segment->add(
            [
               'calendars_id' => $calendar_id,
               'day'          => $day,
               'begin'        => '09:00:00',
               'end'          => '19:00:00',
            ]
         );
         $this->integer($cal_segment_id)->isGreaterThan(0);
      }

      $holiday_id = $holiday->add(
         [
            'name'       => 'Today is a day off',
            'begin_date' => date('Y-m-d'),
            'end_date'   => date('Y-m-d'),
         ]
      );
      $this->integer($holiday_id)->isGreaterThan(0);

      $cal_holiday_id = $cal_holiday->add(
         [
            'calendars_id' => $calendar_id,
            'holidays_id'  => $holiday_id,
         ]
      );
      $this->integer($cal_holiday_id)->isGreaterThan(0);

      // Ticket created every day with no anticipation and no calendar, but no end date
      $data[] = [
         'begin_date'     => $start_of_previous_month,
         'end_date'       => null,
         'periodicity'    => DAY_TIMESTAMP,
         'create_before'  => 0,
         'calendars_id'   => 0,
         'expected_value' => date('Y-m-d 00:00:00', strtotime('+ 1 day')),
      ];

      // Ticket created every day with no anticipation and with calendar
      // Begin hour is outside working hours (today is a day off),
      // so creation will be done on next working day at opening hour.
      $data[] = [
         'begin_date'     => $start_of_previous_month,
         'end_date'       => $end_of_next_year,
         'periodicity'    => DAY_TIMESTAMP,
         'create_before'  => 0,
         'calendars_id'   => $calendar_id,
         'expected_value' => $this->getNextWorkingDayDate($working_days, 'tomorrow', 'Y-m-d 09:00:00'),
      ];

      // Ticket created every day with anticipation and with calendar, but no end date
      // Begin hour is outside working hours (today is a day off),
      // so creation will be done on next day at opening hour - anticipation.
      $data[] = [
         'begin_date'     => $start_of_previous_month,
         'end_date'       => null,
         'periodicity'    => DAY_TIMESTAMP,
         'create_before'  => HOUR_TIMESTAMP * 2,
         'calendars_id'   => $calendar_id,
         'expected_value' => $this->getNextWorkingDayDate($working_days, 'tomorrow', 'Y-m-d 07:00:00'),
      ];

      // Ticket created every day with no anticipation and with calendar and having a begin date in the future
      // As begin date is inside working hours, first occurence should be on begin date.
      $data[] = [
         'begin_date'     => date('Y-m-d 09:00:00', strtotime('tomorrow')),
         'end_date'       => $end_of_next_year,
         'periodicity'    => DAY_TIMESTAMP,
         'create_before'  => 0,
         'calendars_id'   => $calendar_id,
         'expected_value' => $this->getNextWorkingDayDate($working_days, 'tomorrow', 'Y-m-d 09:00:00'),
      ];

      // Ticket created every day with anticipation and with calendar and having a begin date in the future
      // As begin date is outside working hours, first occurence should be on opening hour - anticipation.
      $data[] = [
         'begin_date'     => date('Y-m-d 04:00:00', strtotime('tomorrow')),
         'end_date'       => $end_of_next_year,
         'periodicity'    => DAY_TIMESTAMP,
         'create_before'  => HOUR_TIMESTAMP * 4,
         'calendars_id'   => $calendar_id,
         'expected_value' => $this->getNextWorkingDayDate($working_days, 'tomorrow', 'Y-m-d 05:00:00'),
      ];

      // Ticket created every 7 days with no anticipation and with calendar.
      // We expect ticket to be created every monday at 12:00.
      $data[] = [
         'begin_date'     => date('Y-m-d 12:00:00', strtotime('last monday')),
         'end_date'       => $end_of_next_year,
         'periodicity'    => DAY_TIMESTAMP * 7,
         'create_before'  => 0,
         'calendars_id'   => $calendar_id,
         'expected_value' => $this->getNextWorkingDayDate(
            $working_days,
            (int)date('w') === 1 && (int)date('G') < 12
               ? 'tomorrow' // postpone to tomorrow if we are on monday prior to 12:00, as today is a day off
               : 'next monday',
            'Y-m-d 12:00:00'
         ),
      ];

      // Ticket created every 2 month with no anticipation and no calendar
      $data[] = [
         'begin_date'     => $start_of_previous_month,
         'end_date'       => $end_of_next_year,
         'periodicity'    => '2MONTH',
         'create_before'  => 0,
         'calendars_id'   => 0,
         'expected_value' => date('Y-m-01 00:00:00', strtotime($start_of_previous_month . ' + 2 month')),
      ];

      // Ticket created every 3 month with no anticipation and with calendar.
      // Next occurence day will be on a day off, so creation will be done on next working day.
      $next_occurence_date = date('Y-m-d', strtotime($start_of_previous_month . ' + 3 month'));
      $week_off_begin = $next_occurence_date;
      $week_off_end   = date('Y-m-d', strtotime($week_off_begin . ' + 7 days'));
      $holiday_id = $holiday->add(
         [
            'name'       => 'Day off from ' . $week_off_begin . ' to ' . $week_off_end,
            'begin_date' => $week_off_begin,
            'end_date'   => $week_off_end,
         ]
      );
      $this->integer($holiday_id)->isGreaterThan(0);

      $cal_holiday_id = $cal_holiday->add(
         [
            'calendars_id' => $calendar_id,
            'holidays_id'  => $holiday_id,
         ]
      );
      $this->integer($cal_holiday_id)->isGreaterThan(0);

      $data[] = [
         'begin_date'     => $start_of_previous_month,
         'end_date'       => $end_of_next_year,
         'periodicity'    => '3MONTH',
         'create_before'  => 0,
         'calendars_id'   => $calendar_id,
         'expected_value' => $this->getNextWorkingDayDate(
            $working_days,
            $week_off_end . ' + 1 day', // next occurence will be on first working day after days off
            'Y-m-d 09:00:00'
         ),
      ];

      // Ticket created every year with no anticipation and no calendar
      $data[] = [
         'begin_date'     => $start_of_previous_month,
         'end_date'       => $end_of_next_year,
         'periodicity'    => '1YEAR',
         'create_before'  => 0,
         'calendars_id'   => 0,
         'expected_value' => date('Y-m-01 00:00:00', strtotime($start_of_previous_month . ' + 1 year')),
      ];

      // Ticket created every hour with anticipation and no calendar
      // Next time is "2 hours before tomorrow 00:00:00" ...
      $next_time = strtotime(date('Y-m-d 22:00:00')); // 2 hours anticipation
      if ($next_time < time()) {
         // ... unless "2 hours before tomorrow 00:00:00" is already passed
         $next_time = strtotime('+ 1 day', $next_time);
      }
      $data[] = [
         'begin_date'     => $start_of_previous_month,
         'end_date'       => $end_of_next_year,
         'periodicity'    => DAY_TIMESTAMP,
         'create_before'  => HOUR_TIMESTAMP * 2,
         'calendars_id'   => 0,
         'expected_value' => date('Y-m-d H:i:s', $next_time),
      ];

      // Ticket created every month with anticipation and no calendar
      // Next time is "5 days before begin of next month" ...
      $next_time = strtotime('+ 2 month', strtotime($start_of_previous_month . ' - 5 days')); // 5 days anticipation
      if ($next_time < time()) {
         // ... unless "5 days before begin of next month" is already passed
         $next_time = strtotime('+ 1 month', $next_time);
      }
      $data[] = [
         'begin_date'     => $start_of_previous_month,
         'end_date'       => $end_of_next_year,
         'periodicity'    => '1MONTH',
         'create_before'  => DAY_TIMESTAMP * 5,
         'calendars_id'   => 0,
         'expected_value' => date('Y-m-d H:i:s', $next_time),
      ];

      // Ticket created every year with anticipation and no calendar
      // Next time is "4 days before 'now + 1 year'" ...
      $next_time = strtotime('+ 1 year', strtotime($start_of_previous_month . ' - 4 days')); // 4 days anticipation
      if ($next_time < time()) {
         // ... unless "4 days before 'now + 1 year'" is already passed
         $next_time = strtotime('+ 1 year', $next_time);
      }
      $data[] = [
         'begin_date'     => $start_of_previous_month,
         'end_date'       => $end_of_next_year,
         'periodicity'    => '1YEAR',
         'create_before'  => DAY_TIMESTAMP * 4,
         'calendars_id'   => 0,
         'expected_value' => date('Y-m-d H:i:s', $next_time),
      ];

      // Special case: calendar where monday to friday are full working days
      $calendar_id = $calendar->add(['name' => 'TicketRecurrent testing calendar 2']);
      $this->integer($calendar_id)->isGreaterThan(0);

      for ($day = 1; $day <= 5; $day++) {
         $cal_segment_id = $cal_segment->add(
            [
               'calendars_id' => $calendar_id,
               'day'          => $day,
               'begin'        => '00:00:00',
               'end'          => '24:00:00'
            ]
         );
         $this->integer($cal_segment_id)->isGreaterThan(0);
      }

      $next_time = strtotime('+1 hour');
      if (in_array(date('w', $next_time), ['0', '6'])) {
         $next_time = strtotime('monday midnight');
      }

      $data[] = [
         'begin_date'     => $start_of_previous_month,
         'end_date'       => $end_of_next_year,
         'periodicity'    => HOUR_TIMESTAMP,
         'create_before'  => 0,
         'calendars_id'   => $calendar_id,
         'expected_value' => date('Y-m-d H:00:00', $next_time),
      ];

      return $data;
   }

   /**
    * @param string         $begin_date
    * @param string         $end_date
    * @param string|integer $periodicity
    * @param integer        $create_before
    * @param integer        $calendars_id
    * @param string         $expected_value
    * @param array          $messages
    *
    * @dataProvider computeNextCreationDateProvider
    */
   public function testComputeNextCreationDate(
      $begin_date,
      $end_date,
      $periodicity,
      $create_before,
      $calendars_id,
      $expected_value,
      $messages = null
   ) {

      $ticketRecurrent = new \TicketRecurrent();
      $value = $ticketRecurrent->computeNextCreationDate(
         $begin_date,
         $end_date,
         $periodicity,
         $create_before,
         $calendars_id
      );

      $this->string($value)->isIdenticalTo($expected_value);
      if ($messages === null) {
         $this->hasNoSessionMessage(ERROR);
      } else {
         $this->hasSessionMessage(ERROR, $messages);
      }
   }

   /**
    * Get next working day for reference date.
    *
    * @param array  $working_days    List of working days (0 for sunday, 6 for saturday).
    * @param string $reference_date  Reference date.
    * @param string $format          Date return format.
    *
    * @return string
    */
   private function getNextWorkingDayDate(array $working_days, $reference_date, $format) {
      $reference_date = date('Y-m-d H:i:s', strtotime($reference_date)); // normalize reference date
      $i = 0;
      do {
         $time = strtotime($reference_date . ' + ' . $i . ' days');
         $day = date('w', $time);
         $i++;
      } while (!in_array($day, $working_days));

      return date($format, $time);
   }
}
