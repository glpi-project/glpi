<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Calendar Class
**/
class Calendar extends CommonDropdown {
   use Glpi\Features\Clonable;

   // From CommonDBTM
   public $dohistory                   = true;
   public $can_be_translated           = false;

   static protected $forward_entity_to = ['CalendarSegment'];

   static $rightname = 'calendar';


   public function getCloneRelations() :array {
      return [
         Calendar_Holiday::class,
         CalendarSegment::class
      ];
   }


   /**
    * @since 0.84
   **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'CommonDropdown'.MassiveAction::CLASS_ACTION_SEPARATOR.'merge';
      return $forbidden;
   }


   static function getTypeName($nb = 0) {
      return _n('Calendar', 'Calendars', $nb);
   }


   function defineTabs($options = []) {

      $ong = parent::defineTabs($options);
      $this->addStandardTab('CalendarSegment', $ong, $options);
      $this->addStandardTab('Calendar_Holiday', $ong, $options);

      return $ong;
   }


   function getSpecificMassiveActions($checkitem = null) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($isadmin) {
         $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'duplicate'] = _x('button', 'Duplicate');
         $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'addholiday'] = __('Add a close time');
      }
      return $actions;
   }


   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'duplicate' :
            Entity::dropdown();
            echo "<br><br>";
            echo Html::submit(_x('button', 'Duplicate'), ['name' => 'massiveaction'])."</span>";
            return true;

         case 'addholiday' :
            Holiday::dropdown();
            echo "<br><br>";
            echo Html::submit(_x('button', 'Add'), ['name' => 'massiveaction'])."</span>";
            return true;
      }

      return parent::showMassiveActionsSubForm($ma);
   }


   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      switch ($ma->getAction()) {
         case 'duplicate' : // For calendar duplicate in another entity
            if (method_exists($item, 'duplicate')) {
               $input = $ma->getInput();
               $options = [];
               if ($item->isEntityAssign()) {
                  $options = ['entities_id' => $input['entities_id']];
               }
               foreach ($ids as $id) {
                  if ($item->getFromDB($id)) {
                     if (!$item->isEntityAssign()
                         || ($input['entities_id'] != $item->getEntityID())) {
                        if ($item->can(-1, CREATE, $options)) {
                           if ($item->duplicate($options)) {
                              $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                           } else {
                              $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                              $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                           }
                        } else {
                           $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                           $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                        }
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_COMPAT));
                     }
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                  }
               }
            } else {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
            }
            return;

         case 'addholiday' : // add an holiday with massive action
            $input = $ma->getInput();
            if ($input['holidays_id'] > 0) {
               $holiday          = new Holiday();
               $calendar_holiday = new Calendar_Holiday();

               $holiday->getFromDB($input['holidays_id']);
               $entities = [$holiday->getEntityID() => $holiday->getEntityID()];
               if ($holiday->isRecursive()) {
                  $entities = getSonsOf("glpi_entities", $holiday->getEntityID());
               }

               foreach ($ids as $id) {
                  $entities_id = CommonDBTM::getItemEntity('Calendar', $id);
                  if (isset($entities[$entities_id])) {
                     $input = ['calendars_id' => $id,
                                    'holidays_id'  => $input['holidays_id']];
                     if ($calendar_holiday->add($input)) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                     }
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                  }
               }
            } else {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }


   /**
    * Clone a calendar to another entity : name is updated
    *
    * @param $options array of new values to set
    * @return boolean True on success
    */
   function duplicate($options = []) {

      $input = Toolbox::addslashes_deep($this->fields);
      unset($input['id']);

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            if (isset($this->fields[$key])) {
               $input[$key] = $val;
            }
         }
      }

      if ($newID = $this->clone($input)) {
         $this->updateDurationCache($newID);
         return true;
      }

      return false;
   }


   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            Calendar_Holiday::class,
            CalendarSegment::class,
         ]
      );
   }

   /**
    * Check if the given date is a holiday
    *
    * @param string $date Date of the day to check
    *
    * @return boolean
   **/
   function isHoliday($date) {
      global $DB;

      // Use a static cache to improve performances when multiple elements requires a computation
      // on same calendars/dates.
      static $result_cache = [];
      $cache_key = $this->fields['id'] . '-' . date('Y-m-d', strtotime($date));
      if (array_key_exists($cache_key, $result_cache)) {
         return $result_cache[$cache_key];
      }

      $result = $DB->request([
         'COUNT'        => 'cpt',
         'FROM'         => 'glpi_calendars_holidays',
         'INNER JOIN'   => [
            'glpi_holidays'   => [
               'ON' => [
                  'glpi_calendars_holidays'  => 'holidays_id',
                  'glpi_holidays'            => 'id'
               ]
            ]
         ],
         'WHERE'        => [
            'glpi_calendars_holidays.calendars_id' => $this->fields['id'],
            'OR'                                   => [
               [
                  'AND' => [
                     'glpi_holidays.end_date'            => ['>=', $date],
                     'glpi_holidays.begin_date'          => ['<=', $date]
                  ]
               ],
               [
                  'AND' => [
                     'glpi_holidays.is_perpetual'  => 1,
                     new \QueryExpression("MONTH(".$DB->quoteName('end_date').")*100 + DAY(".$DB->quoteName('end_date').") >= ".date('nd', strtotime($date))),
                     new \QueryExpression("MONTH(".$DB->quoteName('begin_date').")*100 + DAY(".$DB->quoteName('begin_date').") <= ".date('nd', strtotime($date)))
                  ]
               ]
            ]
         ]
      ])->next();

      $is_holiday = (int)$result['cpt'] > 0;

      $result_cache[$cache_key] = $is_holiday;

      return $is_holiday;
   }


   /**
    * Get active time between to date time for the active calendar
    *
    * @param $start           datetime begin
    * @param $end             datetime end
    * @param $work_in_days    boolean  force working in days (false by default)
    *
    * @return integer timestamp of delay
    */
   function getActiveTimeBetween($start, $end, $work_in_days = false) {

      if (!isset($this->fields['id'])) {
         return false;
      }

      if ($end < $start) {
         return 0;
      }

      $timestart  = strtotime($start);
      $timeend    = strtotime($end);
      $datestart  = date('Y-m-d', $timestart);
      $dateend    = date('Y-m-d', $timeend);
      // Need to finish at the closing day : set hour to midnight (23:59:59 for PHP)
      $timerealend = strtotime($dateend.' 23:59:59');

      $activetime = 0;

      if ($work_in_days) {
         $activetime = $timeend-$timestart;

      } else {
         $cache_duration = $this->getDurationsCache();

         for ($actualtime=$timestart; $actualtime<=$timerealend; $actualtime+=DAY_TIMESTAMP) {
            $actualdate = date('Y-m-d', $actualtime);

            if (!$this->isHoliday($actualdate)) {
               $beginhour    = '00:00:00';
               // Calendar segment work with '24:00:00' format for midnight
               $endhour      = '24:00:00';
               $dayofweek    = self::getDayNumberInWeek($actualtime);
               $timeoftheday = 0;

               if ($actualdate == $datestart) { // First day : cannot use cache
                  $beginhour = date('H:i:s', $timestart);
               }

               if ($actualdate == $dateend) { // Last day : cannot use cache
                  $endhour = date('H:i:s', $timeend);
               }

               if ((($actualdate == $datestart) || ($actualdate == $dateend))
                   && ($cache_duration[$dayofweek] > 0)) {
                  $timeoftheday = CalendarSegment::getActiveTimeBetween($this->fields['id'],
                                                                        $dayofweek, $beginhour,
                                                                        $endhour);
               } else {
                  $timeoftheday = $cache_duration[$dayofweek];
               }
               $activetime += $timeoftheday;
            }
         }
      }
      return $activetime;
   }


   /**
    * Check if the given time is on a working day (does not check working hours)
    *
    * @since 0.84
    *
    * @param integer $time Time to check
    *
    * @return boolean
    */
   function isAWorkingDay($time) {

      $cache_duration   = $this->getDurationsCache();
      $dayofweek        = self::getDayNumberInWeek($time);
      $date             = date('Y-m-d', $time);
      return (($cache_duration[$dayofweek] > 0) && !$this->isHoliday($date));
   }


   /**
    * Determines if calendar has, at least, one working day.
    *
    * @since 9.4.3
    *
    * @return boolean
    */
   public function hasAWorkingDay() {

      $durations = $this->getDurationsCache();
      return false !== $durations && array_sum($durations) > 0;
   }


   /**
    *
    * Check if the given time is in a working hour
    *
    * @since 0.85
    *
    * @param integer $time Time to check
    *
    * @return boolean
    */
   function isAWorkingHour($time) {

      if ($this->isAWorkingDay($time)) {
         $dayofweek = self::getDayNumberInWeek($time);
         return CalendarSegment::isAWorkingHour($this->fields['id'], $dayofweek,
                                                date('H:i:s', $time));
      }
      return false;
   }


   /**
    * Add a delay to a date using the active calendar
    *
    * if delay >= DAY_TIMESTAMP : work in days
    * else work in minutes
    *
    * @param datetime $start               begin
    * @param integer  $delay               delay to add (in seconds)
    * @param integer  $additional_delay    delay to add (default 0)
    * @param boolean  $work_in_days        force working in days (false by default)
    * @param boolean  $end_of_working_day  end of working day (false by default)
    *
    * @return boolean|string end date
   **/
   function computeEndDate($start, $delay, $additional_delay = 0, $work_in_days = false, $end_of_working_day = false) {

      if (!isset($this->fields['id'])) {
         return false;
      }

      if (!$this->hasAWorkingDay()) {
         // Invalid calendar (no working day = unable to find any date inside calendar hours)
         return false;
      }

      $actualtime = strtotime($start);
      $timestart  = strtotime($start);
      $datestart  = date('Y-m-d', $timestart);

      // manage dates in past
      $negative_delay = false;
      if ($delay < 0) {
         $delay = -$delay;
         $negative_delay = true;
      }

      // End of working day
      if ($end_of_working_day) {
         $numberofdays = $delay / DAY_TIMESTAMP;
         // Add $additional_delay to start time.
         // If start + delay is next day : +1 day
         $actualtime += $additional_delay;
         $cache_duration = $this->getDurationsCache();
         $dayofweek      = self::getDayNumberInWeek($actualtime);
         $actualdate     = date('Y-m-d', $actualtime);

         // Begin next day working
         if ($this->isHoliday($actualdate)
             || ($cache_duration[$dayofweek] == 0)) {

            while ($this->isHoliday($actualdate)
                   || ($cache_duration[$dayofweek] == 0)) {
               $actualtime = self::getActualTime($actualtime, DAY_TIMESTAMP, $negative_delay);
               $actualdate  = date('Y-m-d', $actualtime);
               $dayofweek   = self::getDayNumberInWeek($actualtime);
            }
         }

         while ($numberofdays > 0) {
            if (!$this->isHoliday($actualdate)
                && ($cache_duration[$dayofweek] > 0)) {
               $numberofdays --;
            }
            $actualtime = self::getActualTime($actualtime, DAY_TIMESTAMP, $negative_delay);
            $actualdate  = date('Y-m-d', $actualtime);
            $dayofweek   = self::getDayNumberInWeek($actualtime);
         }

         // Get next working day
         if ($this->isHoliday($actualdate)
             || ($cache_duration[$dayofweek] == 0)) {

            while ($this->isHoliday($actualdate)
                   || ($cache_duration[$dayofweek] == 0)) {
               $actualtime = self::getActualTime($actualtime, DAY_TIMESTAMP, $negative_delay);
               $actualdate  = date('Y-m-d', $actualtime);
               $dayofweek   = self::getDayNumberInWeek($actualtime);
            }
         }

         $lastworkinghour = CalendarSegment::getLastWorkingHour($this->fields['id'], $dayofweek);
         $actualtime      = strtotime(date('Y-m-d', $actualtime).' '.$lastworkinghour);
         return date('Y-m-d H:i:s', $actualtime);
      }

      // Add additional delay to initial delay
      $delay += $additional_delay;

      if ($work_in_days) { // only based on days
         $cache_duration = $this->getDurationsCache();

         // Compute Real starting time
         // If day is an holiday must start on the begin of next working day
         $actualdate = date('Y-m-d', $actualtime);
         $dayofweek  = self::getDayNumberInWeek($actualtime);
         if ($this->isHoliday($actualdate)
             || ($cache_duration[$dayofweek] == 0)) {

            while ($this->isHoliday($actualdate)
                   || ($cache_duration[$dayofweek] == 0)) {
               $actualtime = self::getActualTime($actualtime, DAY_TIMESTAMP, $negative_delay);
               $actualdate = date('Y-m-d', $actualtime);
               $dayofweek  = self::getDayNumberInWeek($actualtime);
            }
            $firstworkhour = CalendarSegment::getFirstWorkingHour($this->fields['id'],
                                                                  $dayofweek);
            $actualtime    = strtotime($actualdate.' '.$firstworkhour);
         }

         while ($delay > 0) {
            // Begin next day : do not take into account first day : must finish to a working day
            $actualtime = self::getActualTime($actualtime, DAY_TIMESTAMP, $negative_delay);
            $actualdate = date('Y-m-d', $actualtime);
            $dayofweek  = self::getDayNumberInWeek($actualtime);

            if (!$this->isHoliday($actualdate)
                && ($cache_duration[$dayofweek] > 0)) {
               $delay -= DAY_TIMESTAMP;
            }
            if ($delay < 0) { // delay done : if < 0 delete hours
               $actualtime = self::getActualTime($actualtime, $delay, $negative_delay);
            }
         }

         // If > last working hour set last working hour
         $dayofweek       = self::getDayNumberInWeek($actualtime);
         $lastworkinghour = CalendarSegment::getLastWorkingHour($this->fields['id'], $dayofweek);
         if ($lastworkinghour < date('H:i:s', $actualtime)) {
            $actualtime   = strtotime(date('Y-m-d', $actualtime).' '.$lastworkinghour);
         }

         return date('Y-m-d H:i:s', $actualtime);
      }

      // else  // based on working hours
      $cache_duration = $this->getDurationsCache();

      // Only if segments exists
      if (countElementsInTable('glpi_calendarsegments',
                               ['calendars_id' => $this->fields['id']])) {
         while ($delay >= 0) {
            $actualdate = date('Y-m-d', $actualtime);
            if (!$this->isHoliday($actualdate)) {
               $dayofweek = self::getDayNumberInWeek($actualtime);
               $beginhour = '00:00:00';

               if ($actualdate == $datestart) { // First day cannot use cache
                  $beginhour    = date('H:i:s', $timestart);
                  $timeoftheday = CalendarSegment::getActiveTimeBetween($this->fields['id'],
                                                                        $dayofweek, $beginhour,
                                                                        '24:00:00');
               } else {
                  $timeoftheday = $cache_duration[$dayofweek];
               }

               if ($timeoftheday <= $delay && !$negative_delay
                  || $timeoftheday >= $delay && $negative_delay) {
                  // Delay is greater or equal than remaining time in day
                  // -> pass to next day
                  $actualtime = self::getActualTime($actualtime, DAY_TIMESTAMP, $negative_delay);
                  $delay      -= $timeoftheday;
               } else {
                  // End of the delay in the day : get hours with this delay
                  $endhour = CalendarSegment::addDelayInDay($this->fields['id'], $dayofweek,
                                                            $beginhour, $delay);
                  return $actualdate.' '.$endhour;
               }

            } else { // Holiday : pass to next day
               $actualtime = self::getActualTime($actualtime, DAY_TIMESTAMP, $negative_delay);
            }
         }
      }
      return false;
   }

   static function getActualTime($current_time, $number = 0, $negative = false) {
      if ($negative) {
         return $current_time - $number;
      } else {
         return $current_time + $number;
      }
   }


   /**
    * Get days durations including all segments of the current calendar
    *
    * @return boolean|array
   **/
   function getDurationsCache() {

      if (!isset($this->fields['id'])) {
         return false;
      }
      $cache_duration = importArrayFromDB($this->fields['cache_duration']);

      // Invalid cache duration : recompute it
      if (!isset($cache_duration[0])) {
         $this->updateDurationCache($this->fields['id']);
         $cache_duration = importArrayFromDB($this->fields['cache_duration']);
      }

      return $cache_duration;
   }


   /**
    * Get days durations including all segments of the current calendar
    *
    * @return boolean|array
   **/
   function getDaysDurations() {

      if (!isset($this->fields['id'])) {
         return false;
      }

      $results = [];
      for ($i=0; $i<7; $i++) {
         $results[$i] = CalendarSegment::getActiveTimeBetween($this->fields['id'], $i, '00:00:00',
                                                              '24:00:00');
      }
      return $results;
   }


   /**
    * Update the calendar cache
    *
    * @param integer $calendars_id ID of the calendar
    *
    * @return bool True if successful in updating the cache, otherwise returns false.
    */
   function updateDurationCache($calendars_id) {

      if ($this->getFromDB($calendars_id)) {
         $input = [
            'id'             => $calendars_id,
            'cache_duration' => exportArrayToDB($this->getDaysDurations()),
         ];
         return $this->update($input);
      }
      return false;
   }


   /**
    * Get day number (in week) for a date.
    *
    * @param integer $date Date as a UNIX timestamp
    *
    * @return integer
    */
   static function getDayNumberInWeek($date) {
      return (int)date('w', $date);
   }
}
