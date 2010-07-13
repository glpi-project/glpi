<?php
/*
 * @version $Id: calendarsegment.class.php 11838 2010-06-29 15:40:45Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/// Class Calendar
class CalendarSegment extends CommonDBChild {

   // From CommonDBTM
   var $dohistory=true;

   // From CommonDBChild
   public $itemtype = 'Calendar';
   public $items_id = 'calendars_id';

   static function getTypeName() {
      global $LANG;

      return $LANG['calendar'][5];
   }

   function canCreate() {
      return haveRight('calendar', 'w');
   }

   function canView() {
      return haveRight('calendar', 'r');
   }


   function prepareInputForAdd($input) {
      global $LANG;
      // Check override of segment : do not add
      if (count(self::getSegmentsBetween($input['calendars_id'],$input['day'],$input['begin']
                                                      ,$input['day'],$input['end'])) > 0 ) {
         addMessageAfterRedirect($LANG['calendar'][8],false,ERROR);
         return false;
      }
      return $input;
   }

   /**
    * Duplicate all segments from a calendar to his clone
    */
   function cloneCalendar ($oldid, $newid) {
      global $DB;

      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `calendars_id`='$oldid'";

      foreach ($DB->request($query) as $data) {
         unset($data['id']);
         $data['calendars_id'] = $newid;
         $data['_no_history'] = true;

         $this->add($data);
      }
   }


   function post_addItem() {
      // Update calendar cache
      $cal=new Calendar();
      $cal->updateDurationCache($this->fields['calendars_id']);
   }

   function post_deleteFromDB() {
      // Update calendar cache
      $cal=new Calendar();
      $cal->updateDurationCache($this->fields['calendars_id']);
   }

   /**
    * Get segments of a calendar between 2 date
    *
    * @param $calendars_id id of the calendar
    * @param $begin_day begin day number
    * @param $begin_time begin time to check
    * @param $end_day end day number
    * @param $end_time end time to check
    **/
   static function getSegmentsBetween($calendars_id,$begin_day,$begin_time,$end_day,$end_time) {
      global $DB;
      // Do not check hour if day before the end day of after the begin day
      return getAllDatasFromTable('glpi_calendarsegments',
                  "`calendars_id` = '$calendars_id'
                  AND `day` >= '$begin_day'
                  AND `day` <= '$end_day'
                  AND (`begin` < '$end_time' OR `day` < '$end_day')
                  AND ('$begin_time' < `end` OR `day` > '$begin_day')");
   }

   /**
    * Get segments of a calendar between 2 date
    *
    * @param $calendars_id id of the calendar
    * @param $day day number
    * @param $begin_time begin time to check
    * @param $end_time end time to check
    * @return timestamp value
    **/
   static function getActiveTimeBetween($calendars_id,$day,$begin_time,$end_time) {
      global $DB;
      $sum=0;
      // Do not check hour if day before the end day of after the begin day
      $query= "SELECT TIMEDIFF(LEAST('$end_time',`end`),GREATEST(`begin`,'$begin_time')) AS TDIFF
               FROM `glpi_calendarsegments`
               WHERE `calendars_id` = '$calendars_id'
                  AND `day` = '$day'
                  AND (`begin` < '$end_time')
                  AND ('$begin_time' < `end`)";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data=$DB->fetch_assoc($result)) {
//                echo "TDIFF =".$data['TDIFF'].'<br>';
               list($hour,$minute,$second)=explode(':',$data['TDIFF']);
               $sum+=$hour*HOUR_TIMESTAMP+$minute*MINUTE_TIMESTAMP+$second;
            }
         }
      }
      return $sum;
   }

   /**
    * Add a delay of a starting hour in a specific day
    *
    * @param $calendars_id id of the calendar
    * @param $day day number
    * @param $begin_time begin time
    * @param delay timestamp delay to add
    * @return timestamp value
    **/
   static function addDelayInDay($calendars_id,$day,$begin_time,$delay) {
      global $DB;
      $sum=0;
      // Do not check hour if day before the end day of after the begin day
      $query= "SELECT GREATEST(`begin`,'$begin_time') AS BEGIN, TIMEDIFF(`end`,GREATEST(`begin`,'$begin_time')) AS TDIFF
               FROM `glpi_calendarsegments`
               WHERE `calendars_id` = '$calendars_id'
                  AND `day` = '$day'
                  AND ('$begin_time' < `end`)
               ORDER BY `begin`";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data=$DB->fetch_assoc($result)) {
//                echo "TDIFF =".$data['TDIFF'].'<br>';
               list($hour,$minute,$second)=explode(':',$data['TDIFF']);
               $tstamp=$hour*HOUR_TIMESTAMP+$minute*MINUTE_TIMESTAMP+$second;
               // Delay is completed
               if ($delay <= $tstamp) {
                  list($begin_hour,$begin_minute,$begin_second)=explode(':',$data['BEGIN']);
                  $beginstamp=$begin_hour*HOUR_TIMESTAMP+$begin_minute*MINUTE_TIMESTAMP+$begin_second;
                  $endstamp=$beginstamp+$delay;
                  $units=getTimestampTimeUnits($endstamp);
                  return str_pad($units['hour'],2,'0',STR_PAD_LEFT).':'.
                        str_pad($units['minute'],2,'0',STR_PAD_LEFT).':'.
                        str_pad($units['second'],2,'0',STR_PAD_LEFT);
               } else {
                  $delay-=$tstamp;
               }
            }
         }
      }
      return false;
   }

   /**
    * Show segments of a calendar
    *
    * @param $calendar Calendar object
    **/
   static function showForCalendar(Calendar $calendar) {
      global $DB,$CFG_GLPI, $LANG;

      $ID = $calendar->getField('id');
      if (!$calendar->can($ID,'r')) {
         return false;
      }

      $canedit = $calendar->can($ID,'w');

      $rand=mt_rand();
      echo "<form name='calendarsegment_form$rand' id='calendarsegment_form$rand' method='post' action='";
      echo getItemTypeFormURL(__CLASS__)."'>";

      if ($canedit) {
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='4'>".$LANG['calendar'][6]."</tr>";

         echo "<tr class='tab_bg_2'><td class='center'>".$LANG['calendar'][7]."&nbsp;: ";
         echo "<input type='hidden' name='calendars_id' value='$ID'>";
         Dropdown::showFromArray('day', $LANG['calendarDay']);
         echo "</td><td class='center'>".$LANG['buttons'][33]."&nbsp;: ";
         Dropdown::showHours("begin",date('H').":00");
         echo "</td><td class='center'>".$LANG['buttons'][32]."&nbsp;: ";
         Dropdown::showHours("end",(date('H')+1).":00");
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value='".$LANG['buttons'][8]."' class='submit'>";
         echo "</td></tr>";

         echo "</table></div><br>";
      }

      echo "<div class='center'><table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='2'>".$LANG['calendar'][7]."</th>";
      echo "<th>".$LANG['buttons'][33]."</th>";
      echo "<th>".$LANG['buttons'][32]."</th>";
      echo "</tr>";

      $query = "SELECT *
                FROM `glpi_calendarsegments`
                WHERE `calendars_id` = '$ID'
                ORDER BY `day`, `begin`, `end`";
      $result=$DB->query($query);

      if ($DB->numrows($result) >0) {
         while ($data = $DB->fetch_array($result)) {
            echo "<tr class='tab_bg_1'>";
            echo "<td width='10'>";
            if ($canedit) {
               echo "<input type='checkbox' name='item[".$data["id"]."]' value='1'>";
            } else {
               echo "&nbsp;";
            }
            echo "</td>";

            echo "<td>";
            echo $LANG['calendarDay'][$data['day']];
            echo "</td>";
            echo "<td>".$data["begin"]."</td>";
            echo "<td>".$data["end"]."</td>";
         }
         echo "</tr>";
      }
      echo "</table></div>";

      if ($canedit) {
         openArrowMassive("calendarsegment_form$rand",true);
         closeArrowMassive('delete', $LANG['buttons'][6]);
      }
      echo "</form>";
   }

}

?>
