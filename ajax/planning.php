<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/** @file
* @brief
*/

include ('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkCentralAccess();

if (isset($_POST["id"]) && ($_POST["id"] > 0)) {
   echo "<input type='hidden' name='plan[id]' value='".$_POST["id"]."'>";
}

if (isset($_POST["begin"]) && !empty($_POST["begin"])) {
   $begin = $_POST["begin"];

} else {
   $minute = (floor(date('i')/10)*10);

   if ($minute < 10) {
      $minute = '0'.$minute;
   }
   $begin = date("Y-m-d H").":$minute:00";
}

if (isset($_POST["end"]) && !empty($_POST["end"])) {
   $end = $_POST["end"];

} else {
   $end = date("Y-m-d H:i:s",strtotime($begin)+HOUR_TIMESTAMP);
}

echo "<table class='tab_cadre'>";

$rand_user = mt_rand();

if (isset($_POST["users_id"]) && isset($_POST["entity"])) {
   echo "<tr class='tab_bg_2'><td>".__('By')."</td>";
   echo "<td class='center'>";
   $params = array('name'   => "plan[users_id]",
                   'value'  => $_POST["users_id"],
                   'right'  => "own_ticket",
                   'rand'   => $rand_user,
                   'entity' => $_POST["entity"]);

   $params['toupdate'] = array('value_fieldname' => 'users_id',
                              'to_update'        => "user_available$rand_user",
                              'url'              => $CFG_GLPI["root_doc"]."/ajax/planningcheck.php");


   User::dropdown($params);
   echo "</td></tr>\n";
}

echo "<tr class='tab_bg_2'><td>".__('Start date')."</td><td>";
$rand_begin = Html::showDateTimeFormItem("plan[begin]", $begin, -1, false, true, '', '',
                                         $CFG_GLPI["planning_begin"], $CFG_GLPI["planning_end"]);
echo "</td></tr>\n";

echo "<tr class='tab_bg_2'><td>".__('Period')."&nbsp;";

if (isset($_POST["users_id"])) {
   echo "<span id='user_available$rand_user'>";
   include_once(GLPI_ROOT.'/ajax/planningcheck.php');
   echo "</span>";
}

echo "</td><td>";

$default_delay = floor((strtotime($end)-strtotime($begin))/15/MINUTE_TIMESTAMP)*15*MINUTE_TIMESTAMP;

$rand = Dropdown::showTimeStamp("plan[_duration]", array('min'        => 0,
                                                         'max'        => 50*HOUR_TIMESTAMP,
                                                         'value'      => $default_delay,
                                                         'emptylabel' => __('Specify an end date')));
echo "<br><div id='date_end$rand'></div>";

$params = array('duration'     => '__VALUE__',
                'end'          => $end,
                'name'         => "plan[end]",
                'global_begin' => $CFG_GLPI["planning_begin"],
                'global_end'   => $CFG_GLPI["planning_end"]);

Ajax::updateItemOnSelectEvent("dropdown_plan[_duration]$rand", "date_end$rand",
                              $CFG_GLPI["root_doc"]."/ajax/planningend.php", $params);

if ($default_delay == 0) {
   $params['duration'] = 0;
  Ajax::updateItem("date_end$rand", $CFG_GLPI["root_doc"]."/ajax/planningend.php", $params);
}

echo "</td></tr>\n";

if ((!isset($_POST["id"]) || ($_POST["id"] == 0))
    && isset($_POST['itemtype'])
    && PlanningRecall::isAvailable()) {
   echo "<tr class='tab_bg_2'><td>"._x('Planning','Reminder')."</td><td>";
   PlanningRecall::dropdown(array('itemtype' => $_POST['itemtype'],
                                  'items_id' => $_POST['items_id']));
   echo "</td></tr>";
}
echo "</table>\n";

Html::ajaxFooter();
?>