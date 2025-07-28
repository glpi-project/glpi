<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Glpi\Features\Clonable;

use function Safe\strtotime;

/**
 * Calendar Class
 **/
class Calendar extends CommonDropdown
{
    use Clonable;

    // From CommonDBTM
    public $dohistory                   = true;
    public $can_be_translated           = false;

    protected static $forward_entity_to = ['CalendarSegment'];

    public static $rightname = 'calendar';


    public function getCloneRelations(): array
    {
        return [
            Calendar_Holiday::class,
            CalendarSegment::class,
        ];
    }


    /**
     * @since 0.84
     **/
    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'CommonDropdown' . MassiveAction::CLASS_ACTION_SEPARATOR . 'merge';
        return $forbidden;
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Calendar', 'Calendars', $nb);
    }


    public function defineTabs($options = [])
    {

        $ong = parent::defineTabs($options);
        $this->addStandardTab(CalendarSegment::class, $ong, $options);
        $this->addStandardTab(Calendar_Holiday::class, $ong, $options);

        return $ong;
    }


    public function getSpecificMassiveActions($checkitem = null)
    {

        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($isadmin) {
            $actions[self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'duplicate'] = _sx('button', 'Duplicate');
            $actions[self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'addholiday'] = __s('Add a close time');
        }
        return $actions;
    }


    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {

        switch ($ma->getAction()) {
            case 'duplicate':
                Entity::dropdown();
                echo "<br><br>";
                echo Html::submit(_x('button', 'Duplicate'), ['name' => 'massiveaction']) . "</span>";
                return true;

            case 'addholiday':
                Holiday::dropdown();
                echo "<br><br>";
                echo Html::submit(_x('button', 'Add'), ['name' => 'massiveaction']) . "</span>";
                return true;
        }

        return parent::showMassiveActionsSubForm($ma);
    }


    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {

        switch ($ma->getAction()) {
            case 'duplicate': // For calendar duplicate in another entity
                if (Toolbox::hasTrait($item, Clonable::class)) {
                    $input = $ma->getInput();
                    $options = [];
                    if ($item->isEntityAssign()) {
                        $options = ['entities_id' => $input['entities_id']];
                    }
                    foreach ($ids as $id) {
                        if ($item->getFromDB($id)) {
                            if (
                                !$item->isEntityAssign()
                                 || ($input['entities_id'] != $item->getEntityID())
                            ) {
                                if ($item->can(-1, CREATE, $options)) {
                                    if (method_exists($item, 'clone') && $item->clone($options)) {
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

            case 'addholiday': // add an holiday with massive action
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
                                'holidays_id'  => $input['holidays_id'],
                            ];
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
     * @see Clonable::post_clone
     */
    public function post_clone($source, $history)
    {
        $this->updateDurationCache($this->getID());
    }


    public function cleanDBonPurge()
    {

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
    public function isHoliday($date)
    {
        $calendar_holiday = new Calendar_Holiday();
        $holidays = $calendar_holiday->getHolidaysForCalendar($this->fields['id']);

        foreach ($holidays as $holiday) {
            if ($holiday['is_perpetual']) {
                // Compare only month and day for holidays that occurs every year.
                $date_to_compare = date('m-d', strtotime($date));
                $begin_date      = date('m-d', strtotime($holiday['begin_date']));
                $end_date        = date('m-d', strtotime($holiday['end_date']));
            } else {
                // Normalize dates to Y-m-d
                $date_to_compare = date('Y-m-d', strtotime($date));
                $begin_date      = date('Y-m-d', strtotime($holiday['begin_date']));
                $end_date        = date('Y-m-d', strtotime($holiday['end_date']));
            }

            if ($begin_date <= $date_to_compare && $date_to_compare <= $end_date) {
                return true;
            }
        }

        return false;
    }


    /**
     * Seconds elapsed between two dates
     *
     * Taking opening hours into account unless param $include_inactive_time is true
     *
     * @param string $start                 begin datetime
     * @param string $end                   end datetime
     * @param bool   $include_inactive_time true to just get the time passed between start time and end time
     *
     * @return int seconds elapsed between the two dates, taking opening hours into account.
     *
     * @FIXME Remove `$include_inactive_time` parameter in GLPI 11.0. It does not seems to be used and makes no sense.
     */
    public function getActiveTimeBetween($start, $end, $include_inactive_time = false)
    {

        if (!isset($this->fields['id'])) {
            return 0;
        }

        if ($end < $start) {
            return 0;
        }

        $timestart  = strtotime($start);
        $timeend    = strtotime($end);
        $datestart  = date('Y-m-d', $timestart);
        $dateend    = date('Y-m-d', $timeend);
        // Need to finish at the closing day : set hour to midnight (23:59:59 for PHP)
        $timerealend = strtotime($dateend . ' 23:59:59');

        $activetime = 0;

        if ($include_inactive_time) {
            $activetime = $timeend - $timestart;
        } else {
            $cache_duration = $this->getDurationsCache();

            for ($actualtime = $timestart; $actualtime <= $timerealend; $actualtime += DAY_TIMESTAMP) {
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

                    if (
                        (($actualdate == $datestart) || ($actualdate == $dateend))
                        && ($cache_duration[$dayofweek] > 0)
                    ) {
                        $timeoftheday = CalendarSegment::getActiveTimeBetween(
                            $this->fields['id'],
                            $dayofweek,
                            $beginhour,
                            $endhour
                        );
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
    public function isAWorkingDay($time)
    {

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
    public function hasAWorkingDay()
    {

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
    public function isAWorkingHour($time)
    {

        if ($this->isAWorkingDay($time)) {
            $dayofweek = self::getDayNumberInWeek($time);
            return CalendarSegment::isAWorkingHour(
                $this->fields['id'],
                $dayofweek,
                date('H:i:s', $time)
            );
        }
        return false;
    }


    /**
     * Add a delay to a date using the active calendar
     *
     * if delay >= DAY_TIMESTAMP : work in days
     * else work in minutes
     *
     * @param string   $start               begin
     * @param integer  $delay               delay to add (in seconds)
     * @param integer  $additional_delay    delay to add (default 0)
     * @param boolean  $work_in_days        force working in days (false by default)
     * @param boolean  $end_of_working_day  end of working day (false by default)
     *
     * @return boolean|string end date
     **/
    public function computeEndDate($start, $delay, $additional_delay = 0, $work_in_days = false, $end_of_working_day = false)
    {
        // TODO 11.0: parameter $work_in_day make calculation for duration exprimed
        // in days (e.g "+ 5 days") but we don't have anything for month.
        // +1 month will push the date 30 working day when it should get the next
        // valid calendar date at least one month away from the starting date.

        if (!isset($this->fields['id'])) {
            return false;
        }

        if (!$this->hasAWorkingDay()) {
            // Invalid calendar (no working day = unable to find any date inside calendar hours)
            return false;
        }
        $cache_duration = $this->getDurationsCache();

        $actualtime = strtotime($start);
        $timestart  = strtotime($start);
        $datestart  = date('Y-m-d', $timestart);

        // manage dates in past
        $negative_delay = false;
        if ($delay < 0) {
            $delay = -$delay;
            $negative_delay = true;
        }

        // Compute initial target date
        if ($end_of_working_day) {
            // Computation that will result in a target date that corresponds to the end of a working day.

            $numberofdays = $delay / DAY_TIMESTAMP;
            $dayofweek  = self::getDayNumberInWeek($actualtime);
            $actualdate = date('Y-m-d', $actualtime);

            // Begin next day working
            if (
                $this->isHoliday($actualdate)
                || ($cache_duration[$dayofweek] == 0)
            ) {
                while (
                    $this->isHoliday($actualdate)
                    || ($cache_duration[$dayofweek] == 0)
                ) {
                    $actualtime = self::getActualTime($actualtime, DAY_TIMESTAMP, $negative_delay);
                    $actualdate  = date('Y-m-d', $actualtime);
                    $dayofweek   = self::getDayNumberInWeek($actualtime);
                }
            }

            while ($numberofdays > 0) {
                if (
                    !$this->isHoliday($actualdate)
                    && ($cache_duration[$dayofweek] > 0)
                ) {
                    $numberofdays--;
                }
                $actualtime = self::getActualTime($actualtime, DAY_TIMESTAMP, $negative_delay);
                $actualdate  = date('Y-m-d', $actualtime);
                $dayofweek   = self::getDayNumberInWeek($actualtime);
            }

            // Get next working day
            if (
                $this->isHoliday($actualdate)
                || ($cache_duration[$dayofweek] == 0)
            ) {
                while (
                    $this->isHoliday($actualdate)
                    || ($cache_duration[$dayofweek] == 0)
                ) {
                    $actualtime = self::getActualTime($actualtime, DAY_TIMESTAMP, $negative_delay);
                    $actualdate  = date('Y-m-d', $actualtime);
                    $dayofweek   = self::getDayNumberInWeek($actualtime);
                }
            }

            $lastworkinghour = CalendarSegment::getLastWorkingHour($this->fields['id'], $dayofweek);
            $actualtime      = strtotime(date('Y-m-d', $actualtime) . ' ' . $lastworkinghour);
        } elseif ($work_in_days) {
            // Computation that is based on a delay expressed in full days.

            // Compute Real starting time
            // If day is an holiday must start on the begin of next working day
            $actualdate = date('Y-m-d', $actualtime);
            $dayofweek  = self::getDayNumberInWeek($actualtime);
            if (
                $this->isHoliday($actualdate)
                || ($cache_duration[$dayofweek] == 0)
            ) {
                while (
                    $this->isHoliday($actualdate)
                    || ($cache_duration[$dayofweek] == 0)
                ) {
                    $actualtime = self::getActualTime($actualtime, DAY_TIMESTAMP, $negative_delay);
                    $actualdate = date('Y-m-d', $actualtime);
                    $dayofweek  = self::getDayNumberInWeek($actualtime);
                }
                $firstworkhour = CalendarSegment::getFirstWorkingHour(
                    $this->fields['id'],
                    $dayofweek
                );
                $actualtime    = strtotime($actualdate . ' ' . $firstworkhour);
            }

            while ($delay > 0) {
                // Begin next day : do not take into account first day : must finish to a working day
                $actualtime = self::getActualTime($actualtime, DAY_TIMESTAMP, $negative_delay);
                $actualdate = date('Y-m-d', $actualtime);
                $dayofweek  = self::getDayNumberInWeek($actualtime);

                if (
                    !$this->isHoliday($actualdate)
                    && ($cache_duration[$dayofweek] > 0)
                ) {
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
                $actualtime   = strtotime(date('Y-m-d', $actualtime) . ' ' . $lastworkinghour);
            }
        } else {
            // Computation based on a delay expressed in working hours.

            while ($delay >= 0) {
                $actualdate = date('Y-m-d', $actualtime);
                $dayofweek  = self::getDayNumberInWeek($actualtime);
                if (!$this->isHoliday($actualdate) && $cache_duration[$dayofweek] > 0) {
                    $dayofweek = self::getDayNumberInWeek($actualtime);
                    $beginhour = '00:00:00';

                    if ($actualdate == $datestart) { // First day cannot use cache
                        $beginhour = date('H:i:s', $timestart);
                        if (!$negative_delay) {
                            // Count to end of day
                            $timeoftheday = CalendarSegment::getActiveTimeBetween(
                                $this->fields['id'],
                                $dayofweek,
                                $beginhour,
                                '24:00:00'
                            );
                        } else {
                            // Count to start of day
                            $timeoftheday = CalendarSegment::getActiveTimeBetween(
                                $this->fields['id'],
                                $dayofweek,
                                "00:00:00",
                                $beginhour
                            );
                        }
                    } else {
                        $timeoftheday = $cache_duration[$dayofweek];
                    }

                    if ($delay === 0 && $timeoftheday === 0 && $this->isAWorkingHour($timestart)) {
                        // Special case:
                        // - current day is a working day;
                        // - there is no delay to add;
                        // - current time it the exact last second of working hours of the current day.
                        // -> we do not have to add any delay to current time
                        break;
                    }

                    if ($delay >= $timeoftheday) {
                        // Delay is greater or equal than remaining time in day
                        // -> pass to next day
                        $actualtime = self::getActualTime($actualtime, DAY_TIMESTAMP, $negative_delay);
                        $delay      -= $timeoftheday;
                    } else {
                        // End of the delay in the day : get hours with this delay
                        $endhour = CalendarSegment::addDelayInDay(
                            $this->fields['id'],
                            $dayofweek,
                            $beginhour,
                            $delay,
                            $negative_delay
                        );
                        $actualtime = strtotime($actualdate . ' ' . $endhour);
                        break;
                    }
                } else {
                    // Holiday or non working day : pass to next day
                    $actualtime = self::getActualTime($actualtime, DAY_TIMESTAMP, $negative_delay);
                }
            }
        }

        $actualdate = date('Y-m-d H:i:s', $actualtime);

        if ($additional_delay) {
            // Add additional delay in `$work_in_days = false` mode.
            // For OLA/SLA, additional delay only include "waiting times" inside calendar working hours
            // and should therefore be added inside working hours only, in addition to the initial target date.
            $actualdate = $this->computeEndDate(
                $actualdate,
                $additional_delay
            );
        }

        return $actualdate;
    }

    public static function getActualTime($current_time, $number = 0, $negative = false)
    {
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
    public function getDurationsCache()
    {

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
    public function getDaysDurations()
    {

        if (!isset($this->fields['id'])) {
            return false;
        }

        $results = [];
        for ($i = 0; $i < 7; $i++) {
            $results[$i] = CalendarSegment::getActiveTimeBetween(
                $this->fields['id'],
                $i,
                '00:00:00',
                '24:00:00'
            );
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
    public function updateDurationCache($calendars_id)
    {

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
    public static function getDayNumberInWeek($date)
    {
        return (int) date('w', $date);
    }

    public static function getIcon()
    {
        return "ti ti-calendar";
    }
}
