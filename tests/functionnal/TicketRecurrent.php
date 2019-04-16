<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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
      $start_of_current_month = date('Y-m-01 00:00:00');
      $end_of_next_year       = date('Y-m-d 23:59:59', strtotime('last day of next year'));

      // Create a calendar where evey day except today is a working day
      $calendar = new \Calendar();
      $segment  = new \CalendarSegment();
      $calendar_id = $calendar->add(['name' => 'TicketRecurrent testing calendar']);
      $this->integer($calendar_id)->isGreaterThan(0);

      for ($day = 0; $day <= 6; $day++) {
         if ($day == date('w')) {
            continue;
         }

         $segment_id = $segment->add(
            [
               'calendars_id' => $calendar_id,
               'day'          => $day,
               'begin'        => '09:00:00',
               'end'          => '19:00:00'
            ]
         );
         $this->integer($segment_id)->isGreaterThan(0);
      }

      return [
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
            'begin_date'     => $start_of_current_month,
            'end_date'       => $end_of_next_year,
            'periodicity'    => '',
            'create_before'  => 0,
            'calendars_id'   => 0,
            'expected_value' => 'NULL',
         ],
         // Invalid periodicity
         [
            'begin_date'     => $start_of_current_month,
            'end_date'       => $end_of_next_year,
            'periodicity'    => '3WEEK',
            'create_before'  => 0,
            'calendars_id'   => 0,
            'expected_value' => 'NULL',
         ],
         // Invalid anticipated creation delay compared to periodicity
         [
            'begin_date'     => $start_of_current_month,
            'end_date'       => $end_of_next_year,
            'periodicity'    => HOUR_TIMESTAMP,
            'create_before'  => HOUR_TIMESTAMP * 2,
            'calendars_id'   => 0,
            'expected_value' => 'NULL',
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

         // Valid case 1: ticket created every hour with no anticipation and no calendar
         [
            'begin_date'     => $start_of_current_month,
            'end_date'       => $end_of_next_year,
            'periodicity'    => HOUR_TIMESTAMP,
            'create_before'  => 0,
            'calendars_id'   => 0,
            'expected_value' => date('Y-m-d H:00:00', strtotime('+ 1 hour')),
         ],

         // Valid case 2: ticket created every hour with anticipation and no calendar
         [
            'begin_date'     => $start_of_current_month,
            'end_date'       => $end_of_next_year,
            'periodicity'    => HOUR_TIMESTAMP,
            'create_before'  => HOUR_TIMESTAMP,
            'calendars_id'   => 0,
            'expected_value' => date('Y-m-d H:00:00', strtotime('+ 1 hour')),
         ],

         // Valid case 3: ticket created every hour with no anticipation and with calendar
         [
            'begin_date'     => $start_of_current_month,
            'end_date'       => $end_of_next_year,
            'periodicity'    => HOUR_TIMESTAMP,
            'create_before'  => HOUR_TIMESTAMP,
            'calendars_id'   => $calendar_id,
            'expected_value' => date('Y-m-d 08:00:00', strtotime('tomorrow')), // 1 hour anticipation
         ],

         // Valid case 4: ticket created every day with no anticipation and no calendar
         [
            'begin_date'     => $start_of_current_month,
            'end_date'       => $end_of_next_year,
            'periodicity'    => DAY_TIMESTAMP,
            'create_before'  => 0,
            'calendars_id'   => 0,
            'expected_value' => date('Y-m-d 00:00:00', strtotime('+ 1 day')),
         ],

         // Valid case 5: ticket created every hour with anticipation and no calendar
         [
            'begin_date'     => $start_of_current_month,
            'end_date'       => $end_of_next_year,
            'periodicity'    => DAY_TIMESTAMP,
            'create_before'  => HOUR_TIMESTAMP * 2,
            'calendars_id'   => 0,
            'expected_value' => date('Y-m-d 22:00:00'), // 2 hours anticipation
         ],

         // Valid case 6: ticket created every hour with no anticipation and with calendar
         [
            'begin_date'     => date('Y-m-01 09:00:00'), // first day of month at 9am
            'end_date'       => $end_of_next_year,
            'periodicity'    => DAY_TIMESTAMP,
            'create_before'  => HOUR_TIMESTAMP * 2,
            'calendars_id'   => $calendar_id,
            'expected_value' => date('Y-m-d 07:00:00', strtotime('tomorrow')), // 2 hours anticipation
         ],

         // Valid case 7: ticket created every 2 month with no anticipation and no calendar
         [
            'begin_date'     => $start_of_current_month,
            'end_date'       => $end_of_next_year,
            'periodicity'    => '2MONTH',
            'create_before'  => 0,
            'calendars_id'   => 0,
            'expected_value' => date('Y-m-01 00:00:00', strtotime('+ 2 month')),
         ],

         // Valid case 8: ticket created every month with anticipation and no calendar
         [
            'begin_date'     => $start_of_current_month,
            'end_date'       => $end_of_next_year,
            'periodicity'    => '1MONTH',
            'create_before'  => DAY_TIMESTAMP * 5,
            'calendars_id'   => 0,
            'expected_value' => date('Y-m-d 00:00:00', strtotime('+ 1 month', strtotime($start_of_current_month . ' - 5 days'))), // 5 days anticipation
         ],

         // Valid case 9: ticket created every year with no anticipation and no calendar
         [
            'begin_date'     => $start_of_current_month,
            'end_date'       => $end_of_next_year,
            'periodicity'    => '1YEAR',
            'create_before'  => 0,
            'calendars_id'   => 0,
            'expected_value' => date('Y-m-01 00:00:00', strtotime('+ 1 year')),
         ],

         // Valid case 10: ticket created every year with anticipation and no calendar
         [
            'begin_date'     => $start_of_current_month,
            'end_date'       => $end_of_next_year,
            'periodicity'    => '1YEAR',
            'create_before'  => DAY_TIMESTAMP * 4,
            'calendars_id'   => 0,
            'expected_value' => date('Y-m-d 00:00:00', strtotime('+ 1 year', strtotime($start_of_current_month . ' - 4 days'))), // 4 day anticipation
         ],
      ];
   }

   /**
    * @param string         $begin_date
    * @param string         $end_date
    * @param string|integer $periodicity
    * @param integer        $create_before
    * @param integer        $calendars_id
    * @param string         $expected_value
    *
    * @dataProvider computeNextCreationDateProvider
    */
   public function testComputeNextCreationDate(
      $begin_date,
      $end_date,
      $periodicity,
      $create_before,
      $calendars_id,
      $expected_value) {

      $ticketRecurrent = new \TicketRecurrent();
      $value = $ticketRecurrent->computeNextCreationDate(
         $begin_date,
         $end_date,
         $periodicity,
         $create_before,
         $calendars_id
      );

      $this->string($value)->isIdenticalTo($expected_value);
   }
}
