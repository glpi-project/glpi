<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// CLASS Planning

class Planning {

   /**
    * Get planning state name
    *
    * @param $value status ID
   **/
   static function getState($value) {
      global $LANG;

      switch ($value) {
         case 0 :
            return $LANG['planning'][16];

         case 1 :
            return $LANG['planning'][17];

         case 2 :
            return $LANG['planning'][18];
      }
   }


   /**
    * Dropdown of planning state
    *
    * @param $name select name
    * @param $value default value
   **/
   static function dropdownState($name, $value='') {
      global $LANG;

      echo "<select name='$name' id='$name'>";
      echo "<option value='0'".($value==0?" selected ":"").">".$LANG['planning'][16]."</option>";
      echo "<option value='1'".($value==1?" selected ":"").">".$LANG['planning'][17]."</option>";
      echo "<option value='2'".($value==2?" selected ":"").">".$LANG['planning'][18]."</option>";
      echo "</select>";
   }


   /**
    * Check already planned user for a period
    *
    * @param $users_id user id
    * @param $begin begin date
    * @param $end end date
    * @param $except array of items which not be into account array('Reminder'=>array(1,2,id_of_items))
   **/
   static function checkAlreadyPlanned($users_id, $begin, $end, $except=array()) {
      global $CFG_GLPI, $LANG;

      $planned = false;
      $message = '';

      foreach ($CFG_GLPI['planning_types'] as $itemtype) {
         $data = call_user_func(array($itemtype, 'populatePlanning'),
                                array('who'       => $users_id,
                                      'who_group' => 0,
                                      'begin'     => $begin,
                                      'end'       => $end));
         if (isPluginItemType($itemtype)) {
            if (isset($data['items'])) {
               $data = $data['items'];
            } else {
               $data = array();
            }
         }
         if (count($data) && method_exists($itemtype,'getAlreadyPlannedInformation')) {
            foreach ($data as $key => $val) {
               if (!isset($except[$itemtype])
                   || (is_array($except[$itemtype]) && !in_array($val['id'],$except[$itemtype]))) {

                  $planned  = true;
                  $message .= '- '.call_user_func(array($itemtype, 'getAlreadyPlannedInformation'),
                                                  $val).'<br>';
               }
            }
         }
      }
      if ($planned) {
         Session::addMessageAfterRedirect($LANG['planning'][2].'<br>'.$message, false, ERROR);
      }
      return $planned;
   }


   /**
    * Show the planning selection form
    *
    *
    * @param $type planning type : can be day, week, month
    * @param $date working date
    * @param $usertype type of planning to view : can be user or group
    * @param $uID ID of the user
    * @param $gID ID of the group
    *
    * @return Display form
   **/
   static function showSelectionForm($type, $date, $usertype, $uID, $gID) {
      global $LANG, $CFG_GLPI;

      switch ($type) {
         case "month":
            $split      = explode("-",$date);
            $year_next  = $split[0];
            $month_next = $split[1]+1;
            if ($month_next>12) {
               $year_next++;
               $month_next -= 12;
            }
            $year_prev=   $split[0];
            $month_prev = $split[1]-1;
            if ($month_prev==0) {
               $year_prev--;
               $month_prev += 12;
            }
            $next = $year_next."-".sprintf("%02u",$month_next)."-".$split[2];
            $prev = $year_prev."-".sprintf("%02u",$month_prev)."-".$split[2];
            break;

         default :
            $time = strtotime($date);
            $step = 0;
            switch ($type) {
               case "week" :
                  $step = WEEK_TIMESTAMP;
                  break;

               case "day" :
                  $step = DAY_TIMESTAMP;
                  break;
            }
            $next = $time+$step+10;
            $prev = $time-$step;
            $next = strftime("%Y-%m-%d",$next);
            $prev = strftime("%Y-%m-%d",$prev);
            break;
      }

      echo "<div class='center'><form method='get' name='form' action='planning.php'>\n";
      echo "<table class='tab_cadre'><tr class='tab_bg_1'>";
      echo "<td>";
      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/planning.php?type=".$type."&amp;uID=".$uID.
                        "&amp;date=$prev&amp;usertype=$usertype&amp;gID=$gID\">";
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/left.png' alt=\"".$LANG['buttons'][12]."\"
             title=\"".$LANG['buttons'][12]."\"></a>";
      echo "</td>";
      echo "<td>";
      if (Session::haveRight("show_all_planning","1")) {
         echo "<input type='radio' id='radio_user' name='usertype' value='user' ".
                ($usertype=="user"?"checked":"").">";
         $rand_user  =User::dropdown(array( 'name'   => 'uID',
                                            'value'  => $uID,
                                            'right'  => 'interface',
                                            'all'    => 1,
                                            'entity' => $_SESSION["glpiactive_entity"]));
         echo "\n<hr>";
         echo "<input type='radio' id='radio_group' name='usertype' value='group' ".
                ($usertype=="group"?"checked":"").">";
         $rand_group = Dropdown::show('Group', array('value'     => $gID,
                                                     'name'      => 'gID',
                                                     'entity'    => $_SESSION["glpiactive_entity"],
                                                     'condition' => '`is_usergroup`'));
         echo "\n<hr>";
         echo "<input type='radio' id='radio_user_group' name='usertype' value='user_group' ".
                ($usertype=="user_group"?"checked":"").">";
         echo $LANG['joblist'][3];

         echo "\n<script type='text/javascript'>";
         echo "Ext.onReady(function() {";
         echo "   Ext.get('dropdown_uID".$rand_user."').on('change',function() {";
         echo "      window.document.getElementById('radio_user').checked=true;});";
         echo "   Ext.get('dropdown_gID".$rand_group."').on('change',function() {";
         echo "      window.document.getElementById('radio_group').checked=true;});";
         echo "});";
         echo "</script>\n";

      } else if (Session::haveRight("show_group_planning","1")) {
         echo "<select name='usertype'>";
         echo "<option value='user' ".($usertype=='user'?'selected':'').">".$LANG['joblist'][1];
         echo "</option>";
         echo "<option value='user_group' ".($usertype=='user_group'?'selected':'').">".
               $LANG['joblist'][3]."</option>";
         echo "</select>";
      }
      echo "</td>";

      echo "<td>";
      Html::showDateFormItem("date", $date, false);
      echo "</td>\n";

      echo "<td><select name='type'>";
      echo "<option value='day' ".($type=="day"?" selected ":"").">".$LANG['planning'][5].
           "</option>";
      echo "<option value='week' ".($type=="week"?" selected ":"").">".$LANG['planning'][6].
           "</option>";
      echo "<option value='month' ".($type=="month"?" selected ":"").">".
             Toolbox::ucfirst($LANG['financial'][57])."</option>";
      echo "</select></td>\n";

      echo "<td rowspan='2' class='center'>";
      echo "<input type='submit' class='button' name='submit' value=\"". $LANG['buttons'][7] ."\">";
      echo "</td>\n";

      if ($uID || $gID) {
         $port = 80;
         if (isset($_SERVER['SERVER_PORT'])) {
            $port = $_SERVER['SERVER_PORT'];
         }

         echo "<td>";
         echo "<a target='_blank'
               href=\"".$CFG_GLPI["root_doc"]."/front/planning.php?genical=1&amp;uID=".$uID.
                     "&amp;gID=".$gID."&amp;usertype=".$usertype.
                     "&amp;entities_id=".$_SESSION["glpiactive_entity"].
                     "&amp;is_recursive=".$_SESSION["glpiactive_entity_recursive"]."&amp;token=".
                     User::getPersonalToken(Session::getLoginUserID(true))."\"
                     title=\"".$LANG['planning'][12]."\">".
               "<span style='font-size:10px'>-".$LANG['planning'][10]."</span></a>";
         echo "<br>";

         
         // Todo recup l'url complete de glpi proprement, ? nouveau champs table config ?
         echo "<a target='_blank' href=\"webcal://".$_SERVER['HTTP_HOST'].':'.$port.$CFG_GLPI["root_doc"].
               "/front/planning.php?genical=1&amp;uID=".$uID."&amp;gID=".$gID.
               "&amp;usertype=".$usertype."&amp;entities_id=".$_SESSION["glpiactive_entity"].
               "&amp;is_recursive=".$_SESSION["glpiactive_entity_recursive"]."&amp;token=".
               User::getPersonalToken(Session::getLoginUserID(true))."\" title=\"".
               $LANG['planning'][13]."\">";
         echo "<span style='font-size:10px'>-".$LANG['planning'][11]."</span></a>";
         echo "</td>\n";
      }

      echo "<td>";
      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/planning.php?type=".$type."&amp;uID=".$uID.
                     "&amp;date=$next&amp;usertype=$usertype&amp;gID=$gID\">";
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/right.png' alt=\"".$LANG['buttons'][11]."\"
             title=\"".$LANG['buttons'][11]."\"></a>";
      echo "</td>";
      echo "</tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>\n";
   }


   /**
    * Show the availability of a user
    *
    * @since version 0.83
    *
    * @param $who ID of the user
    * @param $begin begin date to check
    * @param $end end date to check
    *
    * @return Nothing (display function)
   **/
   static function checkAvailability($who, $begin='', $end='') {
      global $LANG, $CFG_GLPI, $DB;
      if (empty($who)) {
         return false;
      }
      if (empty($begin)) {
         $begin = date("Y-m-d");
      }
      if (empty($end)) {
         $end = date("Y-m-d");
      }
      if ($end < $begin) {
         $end = $begin;
      }
      $realbegin = $begin." ".$CFG_GLPI["planning_begin"];
      $realend   = $end." ".$CFG_GLPI["planning_end"];

      echo "<div class='center'><form method='get' name='form' action='planning.php'>\n";
      echo "<table class='tab_cadre'>";
      echo "<tr class='tab_bg_1'><th colspan='2'>".$LANG['common'][75]."&nbsp:</th>";
      echo "<th colspan='3'>".getUserName($who)."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['buttons'][33]." : </td>\n";
      echo "<td>";
      Html::showDateFormItem("begin", $begin, false);
      echo "</td>\n";
      echo "<td>".$LANG['buttons'][32]." : </td>\n";
      echo "<td>";
      Html::showDateFormItem("end", $end, false);
      echo "</td>\n";

      echo "<td rowspan='2' class='center'>";
      echo "<input type='hidden' name='users_id' value=\"$who\">";
      echo "<input type='submit' class='button' name='checkavailability' value=\"".
             $LANG['buttons'][7] ."\">";
      echo "</td>\n";

      echo "</tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>\n";


            // ---------------Tracking
      $interv = TicketTask::populatePlanning(array('who'       => $who,
                                                   'who_group' => 0,
                                                   'begin'     => $realbegin,
                                                   'end'       => $realend));
      // ---------------Problem
      $interv2 = ProblemTask::populatePlanning(array('who'       => $who,
                                                     'who_group' => 0,
                                                     'begin'     => $realbegin,
                                                     'end'       => $realend));

      // ---------------reminder
      $datareminders = Reminder::populatePlanning(array('who'       => $who,
                                                        'who_group' => 0,
                                                        'begin'     => $realbegin,
                                                        'end'       => $realend));

      $interv = array_merge($interv, $interv2, $datareminders);

      // Print Headers
      echo "<br><div class='center'><table class='tab_cadre_fixe'>";
      $colnumber  = 1;
      $plan_begin = explode(":",$CFG_GLPI["planning_begin"]);
      $plan_end   = explode(":",$CFG_GLPI["planning_end"]);
      $begin_hour = intval($plan_begin[0]);
      $end_hour   = intval($plan_end[0]);
      if ($plan_end[1]!=0) {
         $end_hour++;
      }
      $colsize = floor((100-15)/($end_hour-$begin_hour));

      // Print Headers
      echo "<tr class='tab_bg_1'><th width='15%'>&nbsp;</th>";

      for ($i=$begin_hour ; $i<$end_hour ; $i++) {
         echo "<th width='$colsize%' colspan='4'>".($i<10?'0':'').$i.
                " -> ". (($i+1)<10?'0':'').($i+1)."</th>";
         $colnumber += 4;
      }
      echo "</tr>";

      $day_begin = strtotime($realbegin);
      $day_end   = strtotime($realend);


      for ($time=$day_begin ; $time<$day_end ; $time+=DAY_TIMESTAMP) {
         $current_day = date('Y-m-d', $time);
         echo "<tr><th>".Html::convDate($current_day)."</th>";
         $begin_quarter = $begin_hour*4;
         $end_quarter   = $end_hour*4;
         for ($i=$begin_quarter ; $i<$end_quarter ; $i++) {

            $begin_time = date("Y-m-d H:i:s", strtotime($current_day)+($i)*HOUR_TIMESTAMP/4);
            $end_time   = date("Y-m-d H:i:s", strtotime($current_day)+($i+1)*HOUR_TIMESTAMP/4);
            // Init activity interval
            $begin_act = $end_time;
            $end_act   = $begin_time;


            /// TODO : review system if 2 independent task from :10 -> :20 + :40 -> :50
            /// Will view not available from :10 to :50
            /// So do not display text for the moment
            reset($interv);
            while ($data=current($interv)) {
               if ($data["begin"]>=$begin_time && $data["end"]<=$end_time) {
                  // In
                  if ($begin_act > $data["begin"]) {
                     $begin_act = $data["begin"];
                  }
                  if ($end_act < $data["end"]) {
                     $end_act   = $data["end"];
                  }

                  unset($interv[key($interv)]);
               } else if ($data["begin"]<$begin_time && $data["end"]>$end_time) {
                  // Through
                  $begin_act = $begin_time;
                  $end_act   = $end_time;

                  next($interv);
               } else if ($data["begin"]>=$begin_time && $data["begin"]<$end_time) {
                  // Begin
                  if ($begin_act > $data["begin"]) {
                     $begin_act = $data["begin"];
                  }
                  $end_act   = $end_time;

                  next($interv);
               } else if ($data["end"]>$begin_time && $data["end"]<=$end_time) {
                  //End
                  $begin_act = $begin_time;
                  if ($end_act < $data["end"]) {
                     $end_act = $data["end"];
                  }
                  unset($interv[key($interv)]);
               } else { // Defautl case
                  next($interv);
               }
            }
            if ($begin_act < $end_act) {
               // Activity in hour
               echo "<td class='notavailable'>&nbsp;</td>";
            } else {
               // No activity
               echo "<td class='available' >&nbsp;</td>";
            }
         }
         echo "</tr>";
      }
      echo "<tr class='tab_bg_1'><td colspan='$colnumber'>&nbsp;</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".$LANG['profiles'][34]."</th>";
      echo "<td class='available' colspan=8>".$LANG['reservation'][4]."</td>";
      echo "<td class='notavailable' colspan=8>".$LANG['reservation'][11]."</td>";
      echo "<td colspan='".($colnumber-17)."'>&nbsp;</td></tr>";
      echo "</table></div>";

   }


   /**
    * Show the planning
    *
    * @param $who ID of the user (0 = undefined)
    * @param $who_group ID of the group of users (0 = undefined)
    * @param $when Date of the planning to display
    * @param $type type of planning to display (day, week, month)
    *
    * @return Nothing (display function)
   **/
   static function show($who, $who_group, $when, $type) {
      global $LANG, $CFG_GLPI, $DB;

      if (!Session::haveRight("show_planning","1")
          && !Session::haveRight("show_all_planning","1")) {
         return false;
      }

      // Define some constants
      $date = explode("-",$when);
      $time = mktime(0, 0, 0, $date[1], $date[2], $date[0]);

      // Check bisextile years
      list($current_year, $current_month, $current_day) = explode("-", $when);
      if (($current_year%4)==0) {
         $feb = 29;
      } else {
         $feb = 28;
      }
      $nb_days = array(31, $feb, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

      // Begin of the month
      $begin_month_day = strftime("%w", mktime(0, 0, 0, $current_month, 1, $current_year));
      if ($begin_month_day==0) {
         $begin_month_day = 7;
      }
      $end_month_day = strftime("%w", mktime(0, 0, 0, $current_month, $nb_days[$current_month-1],
                                             $current_year));

      // Day of the week
      $dayofweek = date("w",$time);
      // Cas du dimanche
      if ($dayofweek==0) {
         $dayofweek = 7;
      }

      // Get begin and duration
      $begin = 0;
      $end   = 0;
      switch ($type) {
         case "month" :
            $begin = strtotime($current_year."-".$current_month."-01 00:00:00");
            $end   = $begin+DAY_TIMESTAMP*$nb_days[$current_month-1];
            break;

         case "week" :
            $tbegin = $begin=$time+mktime(0,0,0,0,1,0)-mktime(0,0,0,0,$dayofweek,0);
            $end    = $begin+WEEK_TIMESTAMP;
            break;

         case "day" :
            $add   = "";
            $begin = $time;
            $end   = $begin+DAY_TIMESTAMP;
            break;
      }
      $begin = date("Y-m-d H:i:s", $begin);
      $end   = date("Y-m-d H:i:s", $end);

      // Print Headers
      echo "<div class='center'><table class='tab_cadre_fixe'>";
      // Print Headers
      echo "<tr class='tab_bg_1'>";
      switch ($type) {
         case "month" :
            for ($i=1 ; $i<=7 ; $i++) {
               echo "<th width='12%'>".$LANG['calendarDay'][$i%7]."</th>";
            }
            break;

         case "week" :
            for ($i=1 ; $i<=7 ; $i++, $tbegin+=DAY_TIMESTAMP) {
               echo "<th width='12%'>".$LANG['calendarDay'][$i%7]." ".date('d', $tbegin)."</th>";
            }
            break;

         case "day" :
            echo "<th width='12%'>".$LANG['calendarDay'][$dayofweek%7]."</th>";
            break;
      }
      echo "</tr>\n";


      // ---------------Tracking
      $interv = TicketTask::populatePlanning(array('who'       => $who,
                                                   'who_group' => $who_group,
                                                   'begin'     => $begin,
                                                   'end'       => $end));
      // ---------------Problem
      $interv2 = ProblemTask::populatePlanning(array('who'       => $who,
                                                     'who_group' => $who_group,
                                                     'begin'     => $begin,
                                                     'end'       => $end));

      // ---------------reminder
      $datareminders = Reminder::populatePlanning(array('who'       => $who,
                                                        'who_group' => $who_group,
                                                        'begin'     => $begin,
                                                        'end'       => $end));

      $interv = array_merge($interv, $interv2, $datareminders);

      // --------------- Plugins
      $data = Plugin::doHookFunction("planning_populate", array("begin"     => $begin,
                                                                "end"       => $end,
                                                                "who"       => $who,
                                                                "who_group" => $who_group));

      if (isset($data["items"])&&count($data["items"])) {
         $interv = array_merge($data["items"], $interv);
      }

      // Display Items
      $tmp        = explode(":", $CFG_GLPI["planning_begin"]);
      $hour_begin = $tmp[0];
      $tmp        = explode(":", $CFG_GLPI["planning_end"]);
      $hour_end   = $tmp[0];
      if ($tmp[1]>0) {
         $hour_end++;
      }

      switch ($type) {
         case "week" :
            for ($hour=$hour_begin ; $hour<=$hour_end ; $hour++) {
               echo "<tr>";
               for ($i=1 ; $i<=7 ; $i++) {
                  echo "<td class='tab_bg_3 top' width='12%'>";
                  echo "<span class='b'>".self::displayUsingTwoDigits($hour).":00</span><br>";

                  // From midnight
                  if ($hour==$hour_begin) {
                     $begin_time = date("Y-m-d H:i:s",
                                        strtotime($when)+($i-$dayofweek)*DAY_TIMESTAMP);
                  } else {
                     $begin_time = date("Y-m-d H:i:s",
                                        strtotime($when)+($i-$dayofweek)*DAY_TIMESTAMP+$hour*HOUR_TIMESTAMP);
                  }
                  // To midnight
                  if ($hour==$hour_end) {
                     $end_time = date("Y-m-d H:i:s",
                                      strtotime($when)+($i-$dayofweek)*DAY_TIMESTAMP+24*HOUR_TIMESTAMP);
                  } else {
                     $end_time = date("Y-m-d H:i:s",
                                      strtotime($when)+($i-$dayofweek)*DAY_TIMESTAMP+($hour+1)*HOUR_TIMESTAMP);
                  }

                  reset($interv);
                  while ($data=current($interv)) {
                     $type = "";
                     if ($data["begin"]>=$begin_time && $data["end"]<=$end_time) {
                        $type = "in";
                     } else if ($data["begin"]<$begin_time && $data["end"]>$end_time) {
                        $type = "through";
                     } else if ($data["begin"]>=$begin_time && $data["begin"]<$end_time) {
                        $type = "begin";
                     } else if ($data["end"]>$begin_time && $data["end"]<=$end_time) {
                        $type = "end";
                     }

                     if (empty($type)) {
                        next($interv);
                     } else {
                        self::displayPlanningItem($data,$who,$type);
                        if ($type=="in") {
                           unset($interv[key($interv)]);
                        } else {
                           next($interv);
                        }
                     }
                  }
                  echo "</td>";
               }
               echo "</tr>\n";
            }
            break;

         case "day" :
            for ($hour=$hour_begin ; $hour<=$hour_end ; $hour++) {
               echo "<tr>";
               $begin_time = date("Y-m-d H:i:s", strtotime($when)+($hour)*HOUR_TIMESTAMP);
               $end_time   = date("Y-m-d H:i:s", strtotime($when)+($hour+1)*HOUR_TIMESTAMP);
               echo "<td class='tab_bg_3 top' width='12%'>";
               echo "<span class='b'>".self::displayUsingTwoDigits($hour).":00</span><br>";
               reset($interv);
               while ($data=current($interv)) {
                  $type = "";
                  if ($data["begin"]>=$begin_time && $data["end"]<=$end_time) {
                     $type = "in";
                  } else if ($data["begin"]<$begin_time && $data["end"]>$end_time) {
                     $type = "through";
                  } else if ($data["begin"]>=$begin_time && $data["begin"]<$end_time) {
                     $type = "begin";
                  } else if ($data["end"]>$begin_time && $data["end"]<=$end_time) {
                     $type = "end";
                  }

                  if (empty($type)) {
                     next($interv);
                  } else {
                     self::displayPlanningItem($data,$who,$type,1);
                     if ($type=="in") {
                        unset($interv[key($interv)]);
                     } else {
                        next($interv);
                     }
                  }
               }
               echo "</td></tr>";
            }
            break;

         case "month" :
            echo "<tr class='tab_bg_3'>";
            // Display first day out of the month
            for ($i=1 ; $i<$begin_month_day ; $i++) {
               echo "<td style='background-color:#ffffff'>&nbsp;</td>";
            }
            // Print real days
            if ($current_month<10 && strlen($current_month)==1) {
               $current_month = "0".$current_month;
            }
            $begin_time = strtotime($begin);
            $end_time   = strtotime($end);
            for ($time=$begin_time ; $time<$end_time ; $time+=DAY_TIMESTAMP) {
               // Add 6 hours for midnight problem
               $day = date("d", $time+6*HOUR_TIMESTAMP);

               echo "<td height='100' class='tab_bg_3 top'>";
               echo "<table class='center'><tr><td class='center'>";
               echo "<span style='font-family: arial,helvetica,sans-serif; font-size: 14px; color: black'>".$day."</span></td></tr>";

               echo "<tr class='tab_bg_3'>";
               echo "<td class='tab_bg_3 top' width='12%'>";
               $begin_day = date("Y-m-d H:i:s", $time);
               $end_day   = date("Y-m-d H:i:s", $time+DAY_TIMESTAMP);
               reset($interv);
               while ($data=current($interv)) {
                  $type = "";
                  if ($data["begin"]>=$begin_day && $data["end"]<=$end_day) {
                     $type = "in";
                  } else if ($data["begin"]<$begin_day && $data["end"]>$end_day) {
                     $type = "through";
                  } else if ($data["begin"]>=$begin_day && $data["begin"]<$end_day) {
                     $type = "begin";
                  } else if ($data["end"]>$begin_day && $data["end"]<=$end_day) {
                     $type = "end";
                  }

                  if (empty($type)) {
                     next($interv);
                  } else {
                     self::displayPlanningItem($data,$who,$type);
                     if ($type=="in") {
                        unset($interv[key($interv)]);
                     } else {
                        next($interv);
                     }
                  }
               }
               echo "</td></tr></table>";
               echo "</td>";

               // Add break line
               if (($day+$begin_month_day)%7==1) {
                  echo "</tr>\n";
                  if ($day!=$nb_days[$current_month-1]) {
                     echo "<tr>";
                  }
               }
            }
            if ($end_month_day!=0) {
               for ($i=0 ; $i<7-$end_month_day ; $i++) {
                  echo "<td style='background-color:#ffffff'>&nbsp;</td>";
               }
            }
            echo "</tr>";
            break;
      }
      echo "</table></div>";
   }


   /**
    * Display a Planning Item
    *
    * @param $val Array of the item to display
    * @param $who ID of the user (0 if all)
    * @param $type position of the item in the time block (in, through, begin or end)
    * @param $complete complete display (more details)
    *
    * @return Nothing (display function)
   **/
   static function displayPlanningItem($val, $who, $type="", $complete=0) {
      global $CFG_GLPI, $LANG, $PLUGIN_HOOKS;

      $color = "#e4e4e4";
      if (isset($val["state"])) {
         switch ($val["state"]) {
            case 0 :
               $color = "#efefe7"; // Information
               break;

            case 1 :
               $color = "#fbfbfb"; // To be done
               break;

            case 2 :
               $color = "#e7e7e2"; // Done
               break;
         }
      }
      echo "<div style=' margin:auto; text-align:left; border:1px dashed #cccccc;
             background-color: $color; font-size:9px; width:98%;'>";

      // Plugins case
      if (isset($val["plugin"]) && isset($PLUGIN_HOOKS['display_planning'][$val["plugin"]])) {
         $function = $PLUGIN_HOOKS['display_planning'][$val["plugin"]];
         if (is_callable($function)) {
            $val["type"] = $type;
            call_user_func($function, $val);
         }

      } else if (isset($val["tickets_id"])) {  // show tracking
         TicketTask::displayPlanningItem($val, $who, $type, $complete);

      } else if (isset($val["problems_id"])) {  // show tracking
         ProblemTask::displayPlanningItem($val, $who, $type, $complete);

      } /*else if (isset($val["changes_id"])) {  // show tracking
         ChangeTask::displayPlanningItem($val, $who, $type, $complete);

      }*/ else {  // show Reminder
         Reminder::displayPlanningItem($val, $who, $type, $complete);
      }
      echo "</div><br>";
   }


   /**
    * Display an integer using 2 digits
    *
    * @param $time value to display
    *
    * @return string return the 2 digits item
   **/
   static private function displayUsingTwoDigits($time) {

      $time = round($time);
      if ($time<10 && strlen($time)>0) {
         return "0".$time;
      }
      return $time;
   }


   /**
    * Show the planning for the central page of a user
    *
    * @param $who ID of the user
    *
    * @return Nothing (display function)
   **/
   static function showCentral($who) {
      global $CFG_GLPI, $LANG;

      if (!Session::haveRight("show_planning","1") || $who<=0) {
         return false;
      }

      $when  = strftime("%Y-%m-%d");
      $debut = $when;

      // Get begin and duration
      $date  = explode("-",$when);
      $time  = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
      $begin = $time;
      $end   = $begin+DAY_TIMESTAMP;
      $begin = date("Y-m-d H:i:s", $begin);
      $end   = date("Y-m-d H:i:s", $end);

      // ---------------Tracking
      $interv = TicketTask::populatePlanning(array('who'       => $who,
                                                   'who_group' => 0,
                                                   'begin'     => $begin,
                                                   'end'       => $end));

      // ---------------Problem
      $interv2 = ProblemTask::populatePlanning(array('who'       => $who,
                                                     'who_group' => 0,
                                                     'begin'     => $begin,
                                                     'end'       => $end));

      // ---------------Reminder
      $data = Reminder::populatePlanning(array('who'       => $who,
                                               'who_group' => 0,
                                               'begin'     => $begin,
                                               'end'       => $end));

      $interv = array_merge($interv, $interv2, $data);

      // ---------------Plugin
      $data = Plugin::doHookFunction("planning_populate", array("begin"     => $begin,
                                                                "end"       => $end,
                                                                "who"       => $who,
                                                                "who_group" => -1));

      if (isset($data["items"]) && count($data["items"])) {
         $interv = array_merge($data["items"], $interv);
      }
      ksort($interv);

      echo "<table class='tab_cadrehov'><tr><th>";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/planning.php?uID=$who'>".$LANG['planning'][15]."</a>";
      echo "</th></tr>";
      $type = '';
      if (count($interv)>0) {
         foreach ($interv as $key => $val) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            if ($val["begin"]<$begin) {
               $val["begin"] = $begin;
            }
            if ($val["end"]>$end) {
               $val["end"] = $end;
            }
            self::displayPlanningItem($val, $who, 'in');
            echo "</td></tr>\n";
         }
      }
      echo "</table>";
   }



   //*******************************************************************************************************************************
   // *********************************** Implementation ICAL ***************************************************************
   //*******************************************************************************************************************************

   /**
    *  Generate ical file content
    *
    * @param $who user ID
    * @param $who_group group ID
    *
    * @return icalendar string
   **/
   static function generateIcal($who,$who_group) {
      global $CFG_GLPI, $LANG;

      if ($who===0 && $who_group===0) {
         return false;
      }

      include_once (GLPI_ROOT . "/lib/icalcreator/iCalcreator.class.php");
      $v = new vcalendar();

      $v->setProperty( "version", "2.0" );


      $dotz = true;
      if( substr( phpversion(), 0, 3 ) < '5.2' ) {
         $dotz = false;
      }

      if ($dotz) {
         $tz = date_default_timezone_get();
         $v->setConfig( 'TZID', $tz );
         $v->setProperty( "X-WR-TIMEZONE", $tz );
         $xprops = array( "X-LIC-LOCATION" => $tz );
         iCalUtilityFunctions::createTimezone( $v, $tz, $xprops );
      }

      if (!empty( $CFG_GLPI["version"])) {
         $v->setConfig( 'unique_id', "GLPI-Planning-".trim($CFG_GLPI["version"]) );
      } else {
         $v->setConfig( 'unique_id', "GLPI-Planning-UnknownVersion" );
      }
      

      $v->setProperty( "method", "PUBLISH" );
      $v->setProperty( "x-wr-calname", "GLPI-".$who."-".$who_group );
      $v->setProperty( "calscale", "GREGORIAN" );
      $interv = array();

      $begin = time()-MONTH_TIMESTAMP*12;
      $end   = time()+MONTH_TIMESTAMP*12;
      $begin = date("Y-m-d H:i:s", $begin);
      $end   = date("Y-m-d H:i:s", $end);

      // ---------------Tracking
      $interv = TicketTask::populatePlanning(array('who'       => $who,
                                                   'who_group' => $who_group,
                                                   'begin'     => $begin,
                                                   'end'       => $end));

      // ---------------Problem
      $interv2 = ProblemTask::populatePlanning(array('who'       => $who,
                                                     'who_group' => $who_group,
                                                     'begin'     => $begin,
                                                     'end'       => $end));

      // ---------------Reminder
      $data = Reminder::populatePlanning(array('who'       => $who,
                                               'who_group' => $who_group,
                                               'begin'     => $begin,
                                               'end'       => $end));

      $interv = array_merge($interv, $interv2, $data);

      // ---------------Plugin
      $data = Plugin::doHookFunction("planning_populate", array("begin"     => $begin,
                                                                "end"       => $end,
                                                                "who"       => $who,
                                                                "who_group" => $who_group));

      if (isset($data["items"]) && count($data["items"])) {
         $interv = array_merge($data["items"], $interv);
      }
      
      if (count($interv)>0) {
         foreach ($interv as $key => $val) {
            $vevent = new vevent(); //initiate EVENT
            if (isset($val["tickettasks_id"])) {
               $vevent->setProperty("uid", "Job#".$val["tickettasks_id"]);
            } else if (isset($val["problemtasks_id"])) {
               $vevent->setProperty("uid", "Problem".$val["problemtasks_id"]);
            } else if (isset($val["reminders_id"])) {
               $vevent->setProperty("uid", "Event#".$val["reminders_id"]);
            } else if (isset($val['planningID'])) { // Specify the ID (for plugins)
               $vevent->setProperty("uid", "Plugin#".$val['planningID']);
            } else {
               $vevent->setProperty("uid", "Plugin#".$key);
            }
            $vevent->setProperty( "dstamp", $val["begin"].(!$dotz?'Z':'') );
            $vevent->setProperty( "dtstart", $val["begin"].(!$dotz?'Z':'') );
            $vevent->setProperty( "dtend", $val["end"].(!$dotz?'Z':'') );
            
            if (isset($val["tickets_id"])) {
               $vevent->setProperty("summary",
                                    $LANG['planning'][8]." #".$val["tickets_id"]." ".
                                    $val["name"]);
            } else if (isset($val["name"])) {
               $vevent->setProperty( "summary", $val["name"] );
            }

            if (isset($val["content"])) {
               $text = $val["content"];
               // be sure to replace nl by \r\n
               $text = preg_replace("/<br( [^>]*)?".">/i", "\r\n", $text);
               $text = Html::clean($text);
               $vevent->setProperty( "description", $text );
            } else if (isset($val["name"])) {
               $text = $val["name"];
               // be sure to replace nl by \r\n
               $text = preg_replace("/<br( [^>]*)?".">/i", "\r\n", $text);
               $text = Html::clean($text);
               $vevent->setProperty( "description", $text );
            }

            if (isset($val["tickets_id"])) {
               $vevent->setProperty("url",
                                    $CFG_GLPI["url_base"]."/index.php?redirect=tracking_".
                                       $val["tickets_id"]);
            }

            $v->setComponent( $vevent );
         }
      }
      $v->sort();
//       $v->parse();
      return $v->returnCalendar();
   }

}

?>
