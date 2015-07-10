<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Planning Class
**/
class Planning extends CommonGLPI {

   static $rightname = 'planning';

   const READMY    =    1;
   const READGROUP = 1024;
   const READALL   = 2048;

   const INFO = 0;
   const TODO = 1;
   const DONE = 2;

   /**
    * @since version 0.85
    *
    * @param $nb
   **/
   static function getTypeName($nb=0) {
      return __('Planning');
   }


   /**
    * @see CommonGLPI::getMenuShorcut()
    *
    * @since version 0.85
   **/
   static function getMenuShorcut() {
      return 'p';
   }


   /**
    * @since version 0.85
   **/
   static function canView() {

      return Session::haveRightsOr(self::$rightname, array(self::READMY, self::READGROUP,
                                                           self::READALL));
   }


   function defineTabs($options=array()) {

      $ong               = array();
      $ong['no_all_tab'] = true ;

      $this->addStandardTab(__CLASS__, $ong, $options);

      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->getType() == __CLASS__) {
         $tabs[1] = __('Personal View');
         if (Session::haveRight(self::$rightname, self::READGROUP)) {
            $tabs[2] = __('Group View');
         }
         if (Session::haveRight(self::$rightname, self::READALL)) {
            $tabs[3] = _n('User', 'Users', Session::getPluralNumber());
            $tabs[4] = _n('Group', 'Groups', Session::getPluralNumber());
         }

         return $tabs;
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1 : // all
               Planning::showSelectionForm($_GET['type'], $_GET['date'], 'my', 0,
                                           $_GET["limititemtype"]);
               Planning::showPlanning($_SESSION['glpiID'], $_GET["gID"], $_GET["date"],
                                      $_GET["type"], $_GET["limititemtype"]);
               break;

            case 2 :
               Planning::showSelectionForm($_GET['type'], $_GET['date'], 'mygroups', 0,
                                           $_GET["limititemtype"]);
               Planning::showPlanning($_SESSION['glpiID'], 'mine', $_GET["date"],
                                      $_GET["type"], $_GET["limititemtype"]);
               break;

            case 3 :
               Planning::showSelectionForm($_GET['type'], $_GET['date'], 'users',
                                           $_GET["uID"], $_GET["limititemtype"]);
               Planning::showPlanning($_GET['uID'], 0, $_GET["date"], $_GET["type"],
                                      $_GET["limititemtype"]);
               break;

            case 4 :
               Planning::showSelectionForm($_GET['type'], $_GET['date'], 'groups',
                                           $_GET["gID"], $_GET["limititemtype"]);
               Planning::showPlanning(0, $_GET['gID'], $_GET["date"], $_GET["type"],
                                      $_GET["limititemtype"]);
               break;
         }
      }
      return true;
   }


   /**
    * Get planning state name
    *
    * @param $value status ID
   **/
   static function getState($value) {

      switch ($value) {
         case static::INFO :
            return _n('Information', 'Information', 1);

         case static::TODO :
            return __('To do');

         case static::DONE :
            return __('Done');
      }
   }


   /**
    * Dropdown of planning state
    *
    * @param $name   select name
    * @param $value  default value (default '')
    * @param $display  display of send string ? (true by default)
   **/
   static function dropdownState($name, $value='', $display=true) {

      $values = array(static::INFO => _n('Information', 'Information', 1),
                      static::TODO => __('To do'),
                      static::DONE => __('Done'));

      return Dropdown::showFromArray($name, $values, array('value'   => $value,
                                                           'display' => $display));
   }


   /**
    * Check already planned user for a period
    *
    * @param $users_id        user id
    * @param $begin           begin date
    * @param $end             end date
    * @param $except    array of items which not be into account array
    *                         ('Reminder'=>array(1,2,id_of_items))
   **/
   static function checkAlreadyPlanned($users_id, $begin, $end, $except=array()) {
      global $CFG_GLPI;

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

         if (count($data)
             && method_exists($itemtype,'getAlreadyPlannedInformation')) {
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
         Session::addMessageAfterRedirect(__('The user is busy at the selected timeframe.').
                                          '<br>'.$message, false, ERROR);
      }
      return $planned;
   }


   /**
    * Show the planning selection form
    *
    * @param $type            planning type : can be day, week, month
    * @param $date            working date
    * @param $usertype        type of planning to view : can be user or group
    * @param $value           ID of the item
    * @param $limititemtype   itemtype only display this itemtype (default '')
    *
    * @return Display form
   **/
   static function showSelectionForm($type, $date, $usertype, $value, $limititemtype='') {
      global $CFG_GLPI;

      switch ($type) {
         case "month" :
            $split      = explode("-",$date);
            $year_next  = $split[0];
            $month_next = $split[1]+1;
            if ($month_next > 12) {
               $year_next++;
               $month_next -= 12;
            }
            $year_prev  = $split[0];
            $month_prev = $split[1]-1;
            if ($month_prev == 0) {
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

      $uID = 0;
      $gID = 0;

      switch ($usertype) {
         case 'my' :
            $uID = $_SESSION['glpiID'];
            break;

         case 'mygroups' :
            if (!Session::haveRight(self::$rightname, self::READGROUP)) {
               exit();
            }
            $gID = 'mine';
            break;

         case 'users' :
            if (!Session::haveRight(self::$rightname, self::READALL)) {
               exit();
            }
            $uID = $value;
            break;

         case 'groups' :
            if (!Session::haveRight(self::$rightname, self::READALL)) {
               exit();
            }
            $gID = $value;
            break;
      }

      echo "<div class='center'><form method='get' name='form' action='planning.php'>\n";
      echo "<table class='tab_cadre_fixe'><tr class='tab_bg_1'>";
      echo "<td>";
      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/planning.php?type=".$type."&amp;uID=".$uID.
             "&amp;date=$prev&amp;usertype=$usertype&amp;gID=$gID&amp;limititemtype=$limititemtype\">";
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/left.png' alt=\"".__s('Previous')."\"
             title=\"".__s('Previous')."\"></a>";
      echo "</td>";

      switch ($usertype) {
         case 'users' :
            echo "<td>";
            $rand_user = User::dropdown(array('name'   => 'uID',
                                              'value'  => $value,
                                              'right'  => 'interface',
                                              'all'    => 1,
                                              'entity' => $_SESSION["glpiactive_entity"]));
            echo "</td>";
            break;

         case 'groups' :
            echo "<td>";
            $rand_group = Group::dropdown(array('value'     => $value,
                                                'name'      => 'gID',
                                                'entity'    => $_SESSION["glpiactive_entity"],
                                                'condition' => '`is_usergroup`'));
            echo "</td>";
            break;
      }


      echo "<td>";
      Dropdown::showItemTypes('limititemtype', $CFG_GLPI['planning_types'],
                              array('value' => $limititemtype));
      echo "</td>";

      echo "<td>";
      Html::showDateField("date", array('value'      => $date,
                                        'maybeempty' => false));
      echo '</td>';
      echo "<td>";

      $values = array('day'   => __('Day'),
                      'week'  => __('Week'),
                      'month' => __('Month'));
      Dropdown::showFromArray('type', $values, array('value' => $type));
      echo "</td>\n";

      echo "<td rowspan='2' class='center'>";
      echo "<input type='submit' class='submit' name='submit' value=\""._sx('button', 'Show')."\">";
      echo "</td>\n";

      if ($uID || $gID) {
         echo "<td>";
         echo "<a target='_blank'
                href=\"".$CFG_GLPI["root_doc"]."/front/planning.php?genical=1&amp;uID=".$uID.
                 "&amp;gID=".$gID."&amp;usertype=".$usertype."&amp;limititemtype=$limititemtype".
                 "&amp;entities_id=".$_SESSION["glpiactive_entity"].
                 "&amp;is_recursive=".$_SESSION["glpiactive_entity_recursive"].
                 "&amp;token=".User::getPersonalToken(Session::getLoginUserID(true))."\"
                 title=\"".__s('Download the planning in Ical format')."\">".
               "<span style='font-size:10px'>".__('Ical')."</span></a>";
         echo "<br>";

         $url = parse_url($CFG_GLPI["url_base"]);
         $port = 80;
         if (isset($url['port'])) {
            $port = $url['port'];
         } else if (isset($url['scheme']) && ($url["scheme"] == 'https')) {
            $port = 443;
         }

         echo "<a target='_blank' href=\"webcal://".$url['host'].':'.$port.
               (isset($url['path'])?$url['path']:'').
               "/front/planning.php?genical=1&amp;uID=".$uID."&amp;gID=".$gID.
               "&amp;usertype=".$usertype."&amp;limititemtype=$limititemtype".
               "&amp;entities_id=".$_SESSION["glpiactive_entity"].
               "&amp;is_recursive=".$_SESSION["glpiactive_entity_recursive"]."&amp;token=".
               User::getPersonalToken(Session::getLoginUserID(true))."\" title=\"".
               __s('webcal:// synchronization')."\">";
         echo "<span style='font-size:10px'>".__('Webcal')."</span></a>";
         echo "</td>\n";
      }
      echo "<td>";
      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/planning.php?type=".$type."&amp;uID=".$uID.
            "&amp;date=$next&amp;usertype=$usertype&amp;gID=$gID&amp;limititemtype=$limititemtype\">";
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/right.png' alt=\"".__s('Next')."\"
             title=\"".__s('Next')."\"></a>";
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
    * @param $params   array of params
    *    must contain :
    *          - begin: begin date to check (default '')
    *          - end: end date to check (default '')
    *          - itemtype : User or Object type (Ticket...)
    *          - foreign key field of the itemtype to define which item to used
    *    optional :
    *          - limitto : limit display to a specific user
    *
    * @return Nothing (display function)
   **/
   static function checkAvailability($params=array()) {
      global $CFG_GLPI, $DB;


      if (!isset($params['itemtype'])) {
         return false;
      }
      if (!($item = getItemForItemtype($params['itemtype']))) {
         return false;
      }
      if (!isset($params[$item->getForeignKeyField()])
          || !$item->getFromDB($params[$item->getForeignKeyField()])) {
         return false;
      }
      // No limit by default
      if (!isset($params['limitto'])) {
         $params['limitto'] = 0;
      }
      if (isset($params['begin']) && !empty($params['begin'])) {
         $begin = $params['begin'];
      } else {
         $begin = date("Y-m-d");
      }
      if (isset($params['end']) && !empty($params['end'])) {
         $end = $params['end'];
      } else {
         $end = date("Y-m-d");
      }

      if ($end < $begin) {
         $end = $begin;
      }
      $realbegin = $begin." ".$CFG_GLPI["planning_begin"];
      $realend   = $end." ".$CFG_GLPI["planning_end"];

      $users = array();

      switch ($item->getType()) {
         case 'User' :
            $users[$item->getID()] = $item->getName();
            break;

         default :
            if (Toolbox::is_a($item, 'CommonITILObject')) {
               foreach ($item->getUsers(CommonITILActor::ASSIGN) as $data) {
                  $users[$data['users_id']] = getUserName($data['users_id']);
               }
               foreach ($item->getGroups(CommonITILActor::ASSIGN) as $data) {
                  foreach (Group_User::getGroupUsers($data['groups_id']) as $data2) {
                  $users[$data2['id']] = formatUserName($data2["id"], $data2["name"],
                                                        $data2["realname"], $data2["firstname"]);
                  }
               }
            }

            break;
      }
      asort($users);
      // Use get method to check availability
      echo "<div class='center'><form method='GET' name='form' action='planning.php'>\n";
      echo "<table class='tab_cadre_fixe'>";
      $colspan = 5;
      if (count($users) > 1) {
         $colspan++;
      }
      echo "<tr class='tab_bg_1'><th colspan='$colspan'>".__('Availability')."</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Start')."</td>\n";
      echo "<td>";
      Html::showDateField("begin", array('value'      => $begin,
                                         'maybeempty' => false));
      echo "</td>\n";
      echo "<td>".__('End')."</td>\n";
      echo "<td>";
      Html::showDateField("end", array('value'      => $end,
                                       'maybeempty' => false));
      echo "</td>\n";
      if (count($users) > 1) {
         echo "<td width='40%'>";
         $data = array(0 => __('All'));
         $data += $users;
         Dropdown::showFromArray('limitto', $data, array('width' => '100%',
                                                         'value' => $params['limitto']));
         echo "</td>";
      }

      echo "<td class='center'>";
      echo "<input type='hidden' name='".$item->getForeignKeyField()."' value=\"".$item->getID()."\">";
      echo "<input type='hidden' name='itemtype' value=\"".$item->getType()."\">";
      echo "<input type='submit' class='submit' name='checkavailability' value=\"".
             _sx('button', 'Search') ."\">";
      echo "</td>\n";

      echo "</tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>\n";

      if (($params['limitto'] > 0) && isset($users[$params['limitto']])) {
         $displayuser[$params['limitto']] = $users[$params['limitto']];
      } else {
         $displayuser = $users;
      }

      if (count($displayuser)) {
         foreach ($displayuser as $who => $whoname) {
            $params = array('who'       => $who,
                            'who_group' => 0,
                            'begin'     => $realbegin,
                            'end'       => $realend);

            $interv = array();
            foreach ($CFG_GLPI['planning_types'] as $itemtype) {
               $interv = array_merge($interv, $itemtype::populatePlanning($params));
            }

            // Print Headers
            echo "<br><div class='center'><table class='tab_cadre_fixe'>";
            $colnumber  = 1;
            $plan_begin = explode(":",$CFG_GLPI["planning_begin"]);
            $plan_end   = explode(":",$CFG_GLPI["planning_end"]);
            $begin_hour = intval($plan_begin[0]);
            $end_hour   = intval($plan_end[0]);
            if ($plan_end[1] != 0) {
               $end_hour++;
            }
            $colsize    = floor((100-15)/($end_hour-$begin_hour));
            $timeheader = '';
            for ($i=$begin_hour ; $i<$end_hour ; $i++) {
               $from       = ($i<10?'0':'').$i;
               $timeheader.= "<th width='$colsize%' colspan='4'>".$from.":00</th>";
               $colnumber += 4;
            }

            // Print Headers
            echo "<tr class='tab_bg_1'><th colspan='$colnumber'>";
            echo $whoname;
            echo "</th></tr>";
            echo "<tr class='tab_bg_1'><th width='15%'>&nbsp;</th>";
            echo $timeheader;
            echo "</tr>";


            $day_begin = strtotime($realbegin);
            $day_end   = strtotime($realend);


            for ($time=$day_begin ; $time<$day_end ; $time+=DAY_TIMESTAMP) {
               $current_day   = date('Y-m-d', $time);
               echo "<tr><th>".Html::convDate($current_day)."</th>";
               $begin_quarter = $begin_hour*4;
               $end_quarter   = $end_hour*4;
               for ($i=$begin_quarter ; $i<$end_quarter ; $i++) {
                  $begin_time = date("Y-m-d H:i:s", strtotime($current_day)+($i)*HOUR_TIMESTAMP/4);
                  $end_time   = date("Y-m-d H:i:s", strtotime($current_day)+($i+1)*HOUR_TIMESTAMP/4);
                  // Init activity interval
                  $begin_act  = $end_time;
                  $end_act    = $begin_time;

                  reset($interv);
                  while ($data = current($interv)) {
                     if (($data["begin"] >= $begin_time)
                         && ($data["end"] <= $end_time)) {
                        // In
                        if ($begin_act > $data["begin"]) {
                           $begin_act = $data["begin"];
                        }
                        if ($end_act < $data["end"]) {
                           $end_act = $data["end"];
                        }
                        unset($interv[key($interv)]);

                     } else if (($data["begin"] < $begin_time)
                                && ($data["end"] > $end_time)) {
                        // Through
                        $begin_act = $begin_time;
                        $end_act   = $end_time;
                        next($interv);

                     } else if (($data["begin"] >= $begin_time)
                                && ($data["begin"] < $end_time)) {
                        // Begin
                        if ($begin_act > $data["begin"]) {
                           $begin_act = $data["begin"];
                        }
                        $end_act = $end_time;
                        next($interv);

                     } else if (($data["end"] > $begin_time)
                                && ($data["end"] <= $end_time)) {
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
                     if (($begin_act <= $begin_time)
                         && ($end_act >= $end_time)) {
                        // Activity in quarter
                        echo "<td class='notavailable'>&nbsp;</td>";
                     } else {
                        // Not all the quarter
                        if ($begin_act <= $begin_time) {
                           echo "<td class='partialavailableend'>&nbsp;</td>";
                        } else {
                           echo "<td class='partialavailablebegin'>&nbsp;</td>";
                        }
                     }
                  } else {
                     // No activity
                     echo "<td class='available'>&nbsp;</td>";
                  }
               }
               echo "</tr>";
            }
            echo "<tr class='tab_bg_1'><td colspan='$colnumber'>&nbsp;</td></tr>";
            echo "</table></div>";
         }
      }
      echo "<div><table class='tab_cadre'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th>".__('Caption')."</th>";
      echo "<td class='available' colspan=8>".__('Available')."</td>";
      echo "<td class='notavailable' colspan=8>".__('Unavailable')."</td>";
      echo "</tr>";
      echo "</table></div>";

   }


   /**
    * Show the planning
    *
    * Function name change since version 0.84 show() => showPlanning
    *
    * @param $who             ID of the user (0 = undefined)
    * @param $who_group       ID of the group of users (0 = undefined)
    * @param $when            Date of the planning to display
    * @param $type            type of planning to display (day, week, month)
    * @param $limititemtype   itemtype limit display to this itemtype (default '')
    *
    * @return Nothing (display function)
   **/
   static function showPlanning($who, $who_group, $when, $type, $limititemtype='') {
      global $CFG_GLPI, $DB;

      if (!static::canView()) {
         return false;
      }

      // Define some constants
      $date       = explode("-",$when);
      $time       = mktime(0, 0, 0, $date[1], $date[2], $date[0]);

      $daysinweek = Toolbox::getDaysOfWeekArray();

      // Check bisextile years
      list($current_year, $current_month, $current_day) = explode("-", $when);
      if (($current_year%4) == 0) {
         $feb = 29;
      } else {
         $feb = 28;
      }
      $nb_days = array(31, $feb, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

      // Begin of the month
      $begin_month_day = strftime("%w", mktime(0, 0, 0, $current_month, 1, $current_year));
      if ($begin_month_day == 0) {
         $begin_month_day = 7;
      }
      $end_month_day = strftime("%w", mktime(0, 0, 0, $current_month, $nb_days[$current_month-1],
                                             $current_year));

      // Day of the week
      $dayofweek     = date("w",$time);
      // Cas du dimanche
      if ($dayofweek == 0) {
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
               echo "<th width='12%'>".$daysinweek[$i%7]."</th>";
            }
            break;

         case "week" :
            for ($i=1 ; $i<=7 ; $i++, $tbegin+=DAY_TIMESTAMP) {
               echo "<th width='12%'>".$daysinweek[$i%7]." ".date('d', $tbegin)."</th>";
            }
            break;

         case "day" :
            echo "<th width='12%'>".$daysinweek[$dayofweek%7]."</th>";
            break;
      }
      echo "</tr>\n";

      $params = array('who'       => $who,
                      'who_group' => $who_group,
                      'begin'     => $begin,
                      'end'       => $end);

      $interv = array();
      if (empty($limititemtype)) {
         foreach ($CFG_GLPI['planning_types'] as $itemtype) {
            $interv = array_merge($interv, $itemtype::populatePlanning($params));
         }
      } else {
         $interv = $limititemtype::populatePlanning($params);
      }

      // Display Items
      $tmp        = explode(":", $CFG_GLPI["planning_begin"]);
      $hour_begin = $tmp[0];
      $tmp        = explode(":", $CFG_GLPI["planning_end"]);
      $hour_end   = $tmp[0];
      if ($tmp[1] > 0) {
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
                  if ($hour == $hour_begin) {
                     $begin_time = date("Y-m-d H:i:s",
                                        strtotime($when)+($i-$dayofweek)*DAY_TIMESTAMP);
                  } else {
                     $begin_time = date("Y-m-d H:i:s",
                                        strtotime($when)+($i-$dayofweek)*DAY_TIMESTAMP+$hour*HOUR_TIMESTAMP);
                  }
                  // To midnight
                  if ($hour == $hour_end) {
                     $end_time = date("Y-m-d H:i:s",
                                      strtotime($when)+($i-$dayofweek)*DAY_TIMESTAMP+24*HOUR_TIMESTAMP);
                  } else {
                     $end_time = date("Y-m-d H:i:s",
                                      strtotime($when)+($i-$dayofweek)*DAY_TIMESTAMP+($hour+1)*HOUR_TIMESTAMP);
                  }

                  reset($interv);
                  while ($data=current($interv)) {
                     $type = "";
                     if (( $data["begin"]>= $begin_time)
                         && ($data["end"] <= $end_time)) {
                        $type = "in";
                     } else if (($data["begin"] < $begin_time)
                                && ($data["end"] > $end_time)) {
                        $type = "through";
                     } else if (($data["begin"] >= $begin_time)
                                && ($data["begin"] < $end_time)) {
                        $type = "begin";
                     } else if (($data["end"] > $begin_time)
                                && ($data["end"] <= $end_time)) {
                        $type = "end";
                     }

                     if (empty($type)) {
                        next($interv);
                     } else {
                        self::displayPlanningItem($data,$who,$type);
                        if ($type == "in") {
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
                  if (($data["begin"] >= $begin_time)
                      && ($data["end"] <= $end_time)) {
                     $type = "in";
                  } else if (($data["begin"] < $begin_time)
                             && ($data["end"] > $end_time)) {
                     $type = "through";
                  } else if (($data["begin"] >= $begin_time)
                             && ($data["begin"] < $end_time)) {
                     $type = "begin";
                  } else if (($data["end"] > $begin_time)
                             && ($data["end"] <= $end_time)) {
                     $type = "end";
                  }

                  if (empty($type)) {
                     next($interv);
                  } else {
                     self::displayPlanningItem($data,$who,$type,1);
                     if ($type == "in") {
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
            if (($current_month < 10) && (strlen($current_month) == 1)) {
               $current_month = "0".$current_month;
            }
            $begin_time = strtotime($begin);
            $end_time   = strtotime($end);
            for ($time=$begin_time ; $time<$end_time ; $time+=DAY_TIMESTAMP) {
               // Add 6 hours for midnight problem
               $day = date("d", $time+6*HOUR_TIMESTAMP);

               echo "<td height='100' class='tab_bg_3 top'>";
               echo "<table class='center'><tr><td class='center'>";
               echo "<span style='font-family: arial,helvetica,sans-serif; font-size: 14px; color: black'>".
                      $day."</span></td></tr>";

               echo "<tr class='tab_bg_3'>";
               echo "<td class='tab_bg_3 top' width='12%'>";
               $begin_day = date("Y-m-d H:i:s", $time);
               $end_day   = date("Y-m-d H:i:s", $time+DAY_TIMESTAMP);
               reset($interv);
               while ($data = current($interv)) {
                  $type = "";
                  if (($data["begin"] >= $begin_day)
                      && ($data["end"] <= $end_day)) {
                     $type = "in";
                  } else if (($data["begin"] < $begin_day)
                             && ($data["end"] > $end_day)) {
                     $type = "through";
                  } else if (($data["begin"] >= $begin_day)
                             && ($data["begin"] < $end_day)) {
                     $type = "begin";
                  } else if (($data["end"] > $begin_day)
                             && ($data["end"] <= $end_day)) {
                     $type = "end";
                  }

                  if (empty($type)) {
                     next($interv);
                  } else {
                     self::displayPlanningItem($data,  $who,$type);
                     if ($type == "in") {
                        unset($interv[key($interv)]);
                     } else {
                        next($interv);
                     }
                  }
               }
               echo "</td></tr></table>";
               echo "</td>";

               // Add break line
               if ((($day+$begin_month_day)%7) == 1) {
                  echo "</tr>\n";
                  if ($day != $nb_days[$current_month-1]) {
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
    * @param $val       Array of the item to display
    * @param $who             ID of the user (0 if all)
    * @param $type            position of the item in the time block (in, through, begin or end)
    *                         (default '')
    * @param $complete        complete display (more details) (default 0)
    *
    * @return Nothing (display function)
   **/
   static function displayPlanningItem(array $val, $who, $type="", $complete=0) {
      global $CFG_GLPI, $PLUGIN_HOOKS;

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
      if (isset($val['itemtype']) && !empty($val['itemtype'])) {
         $val['itemtype']::displayPlanningItem($val, $who, $type, $complete);

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
      if (($time < 10) && (strlen($time) > 0)) {
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
      global $CFG_GLPI;

      if (!Session::haveRight(self::$rightname, self::READMY)
          || ($who <= 0)) {
         return false;
      }

      $when   = strftime("%Y-%m-%d");
      $debut  = $when;

      // Get begin and duration
      $date   = explode("-",$when);
      $time   = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
      $begin  = $time;
      $end    = $begin+DAY_TIMESTAMP;
      $begin  = date("Y-m-d H:i:s", $begin);
      $end    = date("Y-m-d H:i:s", $end);

      $params = array('who'       => $who,
                      'who_group' => 0,
                      'begin'     => $begin,
                      'end'       => $end);
      $interv = array();
      foreach ($CFG_GLPI['planning_types'] as $itemtype) {
         $interv = array_merge($interv,$itemtype::populatePlanning($params));
      }

      ksort($interv);

      echo "<table class='tab_cadrehov'><tr class='noHover'><th>";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/planning.php?uID=$who'>".__('Your planning').
           "</a>";
      echo "</th></tr>";
      $type = '';
      if (count($interv) > 0) {
         foreach ($interv as $key => $val) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            if ($val["begin"] < $begin) {
               $val["begin"] = $begin;
            }
            if ($val["end"] > $end) {
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
    * @param $who             user ID
    * @param $who_group       group ID
    * @param $limititemtype   itemtype only display this itemtype (default '')
    *
    * @return icalendar string
   **/
   static function generateIcal($who, $who_group, $limititemtype='') {
      global $CFG_GLPI;

      if (($who === 0)
          && ($who_group === 0)) {
         return false;
      }
      include_once (GLPI_ROOT . "/lib/icalcreator/iCalcreator.class.php");
      $v = new vcalendar();

      if (!empty( $CFG_GLPI["version"])) {
         $v->setConfig( 'unique_id', "GLPI-Planning-".trim($CFG_GLPI["version"]) );
      } else {
         $v->setConfig( 'unique_id', "GLPI-Planning-UnknownVersion" );
      }

      $tz     = date_default_timezone_get();
      $v->setConfig( 'TZID', $tz );

      $v->setProperty( "method", "PUBLISH" );
      $v->setProperty( "version", "2.0" );

      $v->setProperty( "X-WR-TIMEZONE", $tz );
      $xprops = array( "X-LIC-LOCATION" => $tz );
      iCalUtilityFunctions::createTimezone( $v, $tz, $xprops );

      $v->setProperty( "x-wr-calname", "GLPI-".$who."-".$who_group );
      $v->setProperty( "calscale", "GREGORIAN" );
      $interv = array();

      $begin  = time()-MONTH_TIMESTAMP*12;
      $end    = time()+MONTH_TIMESTAMP*12;
      $begin  = date("Y-m-d H:i:s", $begin);
      $end    = date("Y-m-d H:i:s", $end);

      $params = array('who'       => $who,
                      'who_group' => $who_group,
                      'begin'     => $begin,
                      'end'       => $end);

      $interv = array();
      if (empty($limititemtype)) {
         foreach ($CFG_GLPI['planning_types'] as $itemtype) {
            $interv = array_merge($interv, $itemtype::populatePlanning($params));
         }
      } else {
         $interv = $limititemtype::populatePlanning($params);
      }

      if (count($interv) > 0) {
         foreach ($interv as $key => $val) {
            $vevent = new vevent(); //initiate EVENT
            if (isset($val['itemtype'])) {
               if (isset($val[getForeignKeyFieldForItemType($val['itemtype'])])) {
                  $vevent->setProperty("uid",
                                       $val['itemtype']."#".
                                          $val[getForeignKeyFieldForItemType($val['itemtype'])]);
               } else {
                  $vevent->setProperty("uid", "Other#".$key);
               }
            } else {
               $vevent->setProperty("uid", "Other#".$key);
            }

            $vevent->setProperty( "dstamp", $val["begin"] );
            $vevent->setProperty( "dtstart", $val["begin"] );
            $vevent->setProperty( "dtend", $val["end"] );

            if (isset($val["tickets_id"])) {
               $vevent->setProperty("summary",
                  // TRANS: %1$s is the ticket, %2$s is the title
                                    sprintf(__('Ticket #%1$s %2$s'),
                                           $val["tickets_id"], $val["name"]));
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

            if (isset($val["url"])) {
               $vevent->setProperty("url",$val["url"]);
            }

            $v->setComponent( $vevent );
         }
      }
      $v->sort();
//       $v->parse();
      return $v->returnCalendar();
   }

   /**
    * @since version 0.85
   **/
   function getRights($interface='central') {

      $values[self::READMY]    = __('See personnal planning');
      $values[self::READGROUP] = __('See schedule of people in my groups');
      $values[self::READALL]   = __('See all plannings');

      return $values;
   }
}
?>
