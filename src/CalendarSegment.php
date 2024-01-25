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

/**
 * CalendarSegment Class
 */
class CalendarSegment extends CommonDBChild
{
   // From CommonDBTM
    public $dohistory       = true;

   // From CommonDBChild
    public static $itemtype = 'Calendar';
    public static $items_id = 'calendars_id';


    /**
     * @since 0.84
     **/
    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Time range', 'Time ranges', $nb);
    }


    public function prepareInputForAdd($input)
    {

       // Check override of segment : do not add
        if (
            count(self::getSegmentsBetween(
                $input['calendars_id'],
                $input['day'],
                $input['begin'],
                $input['day'],
                $input['end']
            )) > 0
        ) {
            Session::addMessageAfterRedirect(
                __('Can not add a range riding an existing period'),
                false,
                ERROR
            );
            return false;
        }
        return parent::prepareInputForAdd($input);
    }

    public function post_addItem()
    {

       // Update calendar cache
        $cal = new Calendar();
        $cal->updateDurationCache($this->fields['calendars_id']);

        parent::post_addItem();
    }


    public function post_deleteFromDB()
    {

       // Update calendar cache
        $cal = new Calendar();
        $cal->updateDurationCache($this->fields['calendars_id']);

        parent::post_deleteFromDB();
    }


    /**
     * Get segments of a calendar between 2 date
     *
     * @param integer $calendars_id    id of the calendar
     * @param integer $begin_day       begin day number
     * @param string  $begin_time      begin time to check
     * @param integer $end_day         end day number
     * @param string  $end_time        end time to check
     **/
    public static function getSegmentsBetween($calendars_id, $begin_day, $begin_time, $end_day, $end_time)
    {

       // Do not check hour if day before the end day of after the begin day
        return getAllDataFromTable(
            'glpi_calendarsegments',
            [
                'calendars_id' => $calendars_id,
                ['day'          => ['>=', $begin_day]],
                ['day'          => ['<=', $end_day]],
                ['OR'          => [
                    'begin'  => ['<', $end_time],
                    'day'    => ['<', $end_day]
                ]
                ],
                ['OR'          => [
                    'end'    => ['>=', $begin_time],
                    'day'    => ['>', $begin_day]
                ]
                ]
            ]
        );
    }


    /**
     * Get active time between begin and end time in a day
     *
     * @param integer $calendars_id    id of the calendar
     * @param integer $day             day number
     * @param string  $begin_time      begin time to check
     * @param string  $end_time        end time to check
     *
     * @return integer Time in seconds
     **/
    public static function getActiveTimeBetween($calendars_id, $day, $begin_time, $end_time)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $sum = 0;
       // Do not check hour if day before the end day of after the begin day
        $iterator = $DB->request([
            'SELECT' => [
                new \QueryExpression("
               TIMEDIFF(
                   LEAST(" . $DB->quoteValue($end_time) . ", " . $DB->quoteName('end') . "),
                   GREATEST(" . $DB->quoteName('begin') . ", " . $DB->quoteValue($begin_time) . ")
               ) AS " . $DB->quoteName('TDIFF'))
            ],
            'FROM'   => 'glpi_calendarsegments',
            'WHERE'  => [
                'calendars_id' => $calendars_id,
                'day'          => $day,
                'begin'        => ['<', $end_time],
                'end'          => ['>', $begin_time]
            ]
        ]);

        foreach ($iterator as $data) {
            list($hour, $minute ,$second) = explode(':', $data['TDIFF']);
            $sum += (int)$hour * HOUR_TIMESTAMP + (int)$minute * MINUTE_TIMESTAMP + (int)$second;
        }
        return $sum;
    }


    /**
     * Add a delay of a starting hour in a specific day
     *
     * @param integer $calendars_id    id of the calendar
     * @param integer $day             day number
     * @param string  $begin_time      begin time
     * @param integer $delay           timestamp delay to add
     * @param bool    $negative_delay  are we adding or removing time ?
     *
     * @return string|false Ending timestamp (HH:mm:dd) of delay or false if not applicable.
     **/
    public static function addDelayInDay(
        $calendars_id,
        $day,
        $begin_time,
        $delay,
        bool $negative_delay = false
    ) {
        // TODO: unit test this method with complex calendars using multiple
        // disconnected segments per day
        /** @var \DBmysql $DB */
        global $DB;

        // Common SELECT for both modes
        $SELECT = [
            new \QueryExpression(
                sprintf(
                    "TIMEDIFF(
                        %s,
                        GREATEST(%s, %s)
                    ) AS %s",
                    $DB->quoteName('end'),
                    $DB->quoteName('begin'),
                    $DB->quoteValue($begin_time),
                    $DB->quoteName('TDIFF')
                ),
            )
        ];

        // Common WHERE for both modes
        $WHERE = [
            'calendars_id' => $calendars_id,
            'day'          => $day,
        ];

        // Add specific SELECT and WHERE clauses
        if (!$negative_delay) {
            $SELECT[] = new \QueryExpression(
                sprintf("GREATEST(%s, %s) AS %s", ...[
                    $DB->quoteName('begin'),
                    $DB->quoteValue($begin_time),
                    $DB->quoteName('BEGIN'),
                ])
            );
            $WHERE['end'] = ['>', $begin_time];
        } else {
            // When counting back time, "00:00:00" can't be used for some comparison
            // as it is supposed to represent the end of the day
            // (e.g. finding calendar segments that start before 00:00:00 would always
            // return no results but using 23:59:59 get us the correct behavior).
            $adjusted_time_for_comparaison_in_negative_delay_mode = $begin_time == "00:00:00" ? "23:59:59" : $begin_time;

            $SELECT[] = new \QueryExpression(
                sprintf(
                    "LEAST(%s, %s) AS %s",
                    $DB->quoteName('end'),
                    $DB->quoteValue($adjusted_time_for_comparaison_in_negative_delay_mode),
                    $DB->quoteName('END'),
                )
            );
            $WHERE['begin'] = ['<', $adjusted_time_for_comparaison_in_negative_delay_mode];
        }

        $iterator = $DB->request([
            'SELECT' => $SELECT,
            'FROM'   => self::getTable(),
            'WHERE'  => $WHERE,
            'ORDER'  => !$negative_delay ? 'begin' : 'end DESC'
        ]);

        foreach ($iterator as $data) {
            list($hour, $minute, $second) = explode(':', $data['TDIFF']);
            $tstamp = (int)$hour * HOUR_TIMESTAMP + (int)$minute * MINUTE_TIMESTAMP + (int)$second;

            // Delay is completed
            if ($delay <= $tstamp) {
                if (!$negative_delay) {
                    // Add time
                    list($begin_hour, $begin_minute, $begin_second) = explode(':', $data['BEGIN']);
                    $beginstamp = (int)$begin_hour * HOUR_TIMESTAMP + (int)$begin_minute * MINUTE_TIMESTAMP + (int)$begin_second;
                    $endstamp = $beginstamp + $delay;
                } else {
                    // Substract time
                    list($begin_hour, $begin_minute, $begin_second) = explode(':', $data['END']);
                    $beginstamp = (int)$begin_hour * HOUR_TIMESTAMP + (int)$begin_minute * MINUTE_TIMESTAMP + (int)$begin_second;
                    $endstamp = $beginstamp - $delay;
                }
                $units      = Toolbox::getTimestampTimeUnits($endstamp);
                return str_pad($units['hour'], 2, '0', STR_PAD_LEFT) . ':' .
                     str_pad($units['minute'], 2, '0', STR_PAD_LEFT) . ':' .
                     str_pad($units['second'], 2, '0', STR_PAD_LEFT);
            } else {
                $delay -= $tstamp;
            }
        }
        return false;
    }


    /**
     * Get first working hour of a day
     *
     * @param integer $calendars_id    id of the calendar
     * @param integer $day             day number
     *
     * @return string Timestamp (HH:mm:dd) of first working hour
     **/
    public static function getFirstWorkingHour($calendars_id, $day)
    {
        /** @var \DBmysql $DB */
        global $DB;

       // Do not check hour if day before the end day of after the begin day
        $result = $DB->request([
            'SELECT' => ['MIN' => 'begin AS minb'],
            'FROM'   => 'glpi_calendarsegments',
            'WHERE'  => [
                'calendars_id' => $calendars_id,
                'day'          => $day
            ]
        ])->current();
        return $result['minb'];
    }


    /**
     * Get last working hour of a day
     *
     * @param integer $calendars_id    id of the calendar
     * @param integer $day             day number
     *
     * @return string Timestamp (HH:mm:dd) of last working hour
     **/
    public static function getLastWorkingHour($calendars_id, $day)
    {
        /** @var \DBmysql $DB */
        global $DB;

       // Do not check hour if day before the end day of after the begin day
        $result = $DB->request([
            'SELECT' => ['MAX' => 'end AS mend'],
            'FROM'   => 'glpi_calendarsegments',
            'WHERE'  => [
                'calendars_id' => $calendars_id,
                'day'          => $day
            ]
        ])->current();
        return $result['mend'];
    }


    /**
     * Is the hour passed is a working hour ?
     *
     * @param integer $calendars_id    id of the calendar
     * @param integer $day             day number
     * @param string  $hour            hour (Format HH:MM::SS)
     *
     * @return boolean
     **/
    public static function isAWorkingHour($calendars_id, $day, $hour)
    {
        /** @var \DBmysql $DB */
        global $DB;

       // Do not check hour if day before the end day of after the begin day
        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => 'glpi_calendarsegments',
            'WHERE'  => [
                'calendars_id' => $calendars_id,
                'day'          => $day,
                'begin'        => ['<=', $hour],
                'end'          => ['>=', $hour]
            ]
        ])->current();
        return $result['cpt'] > 0;
    }


    /**
     * Show segments of a calendar
     *
     * @param $calendar Calendar object
     **/
    public static function showForCalendar(Calendar $calendar)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $ID = $calendar->getField('id');
        if (!$calendar->can($ID, READ)) {
            return false;
        }

        $canedit = $calendar->can($ID, UPDATE);
        $rand    = mt_rand();

        $iterator = $DB->request([
            'FROM'   => 'glpi_calendarsegments',
            'WHERE'  => [
                'calendars_id' => $ID
            ],
            'ORDER'  => [
                'day',
                'begin',
                'end'
            ]
        ]);
        $numrows = count($iterator);

        if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form name='calendarsegment_form$rand' id='calendarsegment_form$rand' method='post'
                action='";
            echo Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'><th colspan='7'>" . __('Add a schedule') . "</tr>";

            echo "<tr class='tab_bg_2'><td class='center'>" . _n('Day', 'Days', 1) . "</td><td>";
            echo "<input type='hidden' name='calendars_id' value='$ID'>";
            Dropdown::showFromArray('day', Toolbox::getDaysOfWeekArray());
            echo "</td><td class='center'>" . __('Start') . '</td><td>';
            Dropdown::showHours("begin", ['value' => date('H') . ":00"]);
            echo "</td><td class='center'>" . __('End') . '</td><td>';
            Dropdown::showHours("end", ['value' => (date('H') + 1) . ":00"]);
            echo "</td><td class='center'>";
            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
            echo "</td></tr>";

            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }

        echo "<div class='spaced'>";
        if ($canedit && $numrows) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $numrows),
                'container'     => 'mass' . __CLASS__ . $rand
            ];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr>";
        if ($canedit && $numrows) {
            echo "<th width='10'>";
            echo Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            echo "</th>";
        }
        echo "<th>" . _n('Day', 'Days', 1) . "</th>";
        echo "<th>" . __('Start') . "</th>";
        echo "<th>" . __('End') . "</th>";
        echo "</tr>";

        $daysofweek = Toolbox::getDaysOfWeekArray();

        if ($numrows) {
            foreach ($iterator as $data) {
                echo "<tr class='tab_bg_1'>";

                if ($canedit) {
                    echo "<td>";
                    Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
                    echo "</td>";
                }

                echo "<td>";
                echo $daysofweek[$data['day']];
                echo "</td>";
                echo "<td>" . $data["begin"] . "</td>";
                echo "<td>" . $data["end"] . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        if ($canedit && $numrows) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>";
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            $nb = 0;
            if ($item instanceof Calendar) {
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $nb = countElementsInTable(
                        $this->getTable(),
                        ['calendars_id' => $item->getID()]
                    );
                }
                return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == 'Calendar') {
            self::showForCalendar($item);
        }
        return true;
    }
}
