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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// Class Calendar
class CalendarSegment extends CommonDBChild {

   // From CommonDBTM
   public $dohistory       = true;

   // From CommonDBChild
   static public $itemtype = 'Calendar';
   static public $items_id = 'calendars_id';


   /**
    * @since 0.84
   **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   static function getTypeName($nb = 0) {
      return _n('Time range', 'Time ranges', $nb);
   }


   function prepareInputForAdd($input) {

      // Check override of segment : do not add
      if (count(self::getSegmentsBetween($input['calendars_id'], $input['day'], $input['begin'],
                                         $input['day'], $input['end'])) > 0 ) {
         Session::addMessageAfterRedirect(__('Can not add a range riding an existing period'),
                                          false, ERROR);
         return false;
      }
      return parent::prepareInputForAdd($input);
   }


   /**
    * Duplicate all segments from a calendar to his clone
    *
    * @param $oldid
    * @param $newid
   **/
   static function cloneCalendar($oldid, $newid) {
      global $DB;

      $result = $DB->request(
         [
            'FROM'   => self::getTable(),
            'WHERE'  => [
               'calendars_id' => $oldid,
            ]
         ]
      );

      foreach ($result as $data) {
         $c                    = new self();
         unset($data['id']);
         $data['calendars_id'] = $newid;
         $data['_no_history']  = true;

         $c->add($data);
      }
   }


   function post_addItem() {

      // Update calendar cache
      $cal = new Calendar();
      $cal->updateDurationCache($this->fields['calendars_id']);

      parent::post_addItem();
   }


   function post_deleteFromDB() {

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
   static function getSegmentsBetween($calendars_id, $begin_day, $begin_time, $end_day, $end_time) {

      // Do not check hour if day before the end day of after the begin day
      return getAllDatasFromTable(
         'glpi_calendarsegments', [
            'calendars_id' => $calendars_id,
            ['day'          => ['>=', $begin_day]],
            ['day'          => ['<=', $end_day]],
            ['OR'          => [
               'begin'  => ['<', $end_time],
               'day'    => ['<', $end_day]
            ]],
            ['OR'          => [
               'end'    => ['>=', $begin_time],
               'day'    => ['>', $begin_day]
            ]]
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
   static function getActiveTimeBetween($calendars_id, $day, $begin_time, $end_time) {
      global $DB;

      $sum = 0;
      // Do not check hour if day before the end day of after the begin day
      $iterator = $DB->request([
         'SELECT' => [
            new \QueryExpression("TIMEDIFF(LEAST(" . $DB->quoteValue($end_time) .", " . $DB->quoteName('end') . ")"),
            new \QueryExpression("GREATEST(" . $DB->quoteName('begin') . ", " . $DB->quoteValue($begin_time) . ")) AS " . $DB->quoteName('TDIFF'))
         ],
         'FROM'   => 'glpi_calendarsegments',
         'WHERE'  => [
            'calendars_id' => $calendars_id,
            'day'          => $day,
            'begin'        => ['<', $end_time],
            'end'          => ['>', $begin_time]
         ]
      ]);

      while ($data = $iterator->next()) {
         list($hour, $minute ,$second) = explode(':', $data['TDIFF']);
         $sum += $hour*HOUR_TIMESTAMP+$minute*MINUTE_TIMESTAMP+$second;
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
    *
    * @return string|false Ending timestamp (HH:mm:dd) of delay or false if not applicable.
   **/
   static function addDelayInDay($calendars_id, $day, $begin_time, $delay) {
      global $DB;

      // Do not check hour if day before the end day of after the begin day
      $iterator = $DB->request([
         'SELECT' => [
            new \QueryExpression(
               "GREATEST(" . $DB->quoteName('begin') . ", " . $DB->quoteValue($begin_time)  . ") AS " . $DB->quoteName('BEGIN')
            ),
            new \QueryExpression(
               "TIMEDIFF(" . $DB->quoteName('end') . ", GREATEST(" . $DB->quoteName('begin') . ", " . $DB->quoteValue($begin_time) . ")) AS " . $DB->quoteName('TDIFF')
            )
         ],
         'FROM'   => 'glpi_calendarsegments',
         'WHERE'  => [
            'calendars_id' => $calendars_id,
            'day'          => $day,
            'end'          => ['>', $begin_time]
         ],
         'ORDER'  => 'begin'
      ]);

      while ($data = $iterator->next()) {
         list($hour, $minute, $second) = explode(':', $data['TDIFF']);
         $tstamp = $hour*HOUR_TIMESTAMP+$minute*MINUTE_TIMESTAMP+$second;

         // Delay is completed
         if ($delay <= $tstamp) {
            list($begin_hour, $begin_minute, $begin_second) = explode(':', $data['BEGIN']);
            $beginstamp = $begin_hour*HOUR_TIMESTAMP+$begin_minute*MINUTE_TIMESTAMP+$begin_second;
            $endstamp   = $beginstamp+$delay;
            $units      = Toolbox::getTimestampTimeUnits($endstamp);
            return str_pad($units['hour'], 2, '0', STR_PAD_LEFT).':'.
                     str_pad($units['minute'], 2, '0', STR_PAD_LEFT).':'.
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
   static function getFirstWorkingHour($calendars_id, $day) {
      global $DB;

      // Do not check hour if day before the end day of after the begin day
      $result = $DB->request([
         'SELECT' => ['MIN' => 'begin AS minb'],
         'FROM'   => 'glpi_calendarsegments',
         'WHERE'  => [
            'calendars_id' => $calendars_id,
            'day'          => $day
         ]
      ])->next();
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
   static function getLastWorkingHour($calendars_id, $day) {
      global $DB;

      // Do not check hour if day before the end day of after the begin day
      $result = $DB->request([
         'SELECT' => ['MAX' => 'end AS mend'],
         'FROM'   => 'glpi_calendarsegments',
         'WHERE'  => [
            'calendars_id' => $calendars_id,
            'day'          => $day
         ]
      ])->next();
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
   static function isAWorkingHour($calendars_id, $day, $hour) {
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
      ])->next();
      return $result['cpt'] > 0;
   }


   /**
    * Show segments of a calendar
    *
    * @param $calendar Calendar object
   **/
   static function showForCalendar(Calendar $calendar) {
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
         echo Toolbox::getItemTypeFormURL(__CLASS__)."'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='7'>".__('Add a schedule')."</tr>";

         echo "<tr class='tab_bg_2'><td class='center'>".__('Day')."</td><td>";
         echo "<input type='hidden' name='calendars_id' value='$ID'>";
         Dropdown::showFromArray('day', Toolbox::getDaysOfWeekArray());
         echo "</td><td class='center'>".__('Start').'</td><td>';
         Dropdown::showHours("begin", ['value' => date('H').":00"]);
         echo "</td><td class='center'>".__('End').'</td><td>';
         Dropdown::showHours("end", ['value' => (date('H')+1).":00"]);
         echo "</td><td class='center'>";
            echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td></tr>";

         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $numrows) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $numrows),
                           'container'     => 'mass'.__CLASS__.$rand];
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr>";
      if ($canedit && $numrows) {
         echo "<th width='10'>";
         echo Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         echo "</th>";
      }
      echo "<th>".__('Day')."</th>";
      echo "<th>".__('Start')."</th>";
      echo "<th>".__('End')."</th>";
      echo "</tr>";

      $daysofweek = Toolbox::getDaysOfWeekArray();

      if ($numrows) {
         while ($data = $iterator->next()) {
            echo "<tr class='tab_bg_1'>";

            if ($canedit) {
               echo "<td>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
               echo "</td>";
            }

            echo "<td>";
            echo $daysofweek[$data['day']];
            echo "</td>";
            echo "<td>".$data["begin"]."</td>";
            echo "<td>".$data["end"]."</td>";
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


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Calendar' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable($this->getTable(),
                                             ['calendars_id' => $item->getID()]);
               }
               return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType()=='Calendar') {
         self::showForCalendar($item);
      }
      return true;
   }
}
