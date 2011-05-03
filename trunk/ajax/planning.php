<?php
/*
 * @version $Id$
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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT','..');
include (GLPI_ROOT."/inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
header_nocache();

checkCentralAccess();

if (isset($_POST["id"]) && $_POST["id"]>0) {
   echo "<input type='hidden' name='plan[id]' value='".$_POST["id"]."'>";
}

if (isset($_POST["begin"]) && !empty($_POST["begin"])) {
   $begin = $_POST["begin"];

} else {
   $minute = (floor(date('i')/10)*10);

   if ($minute<10) {
      $minute = '0'.$minute;
   }
   $begin = date("Y-m-d H").":$minute:00";
}

if (isset($_POST["end"]) && !empty($_POST["end"])) {
   $end = $_POST["end"];

} else {
   $end = date("Y-m-d H:i:s",strtotime($begin)+HOUR_TIMESTAMP);
}

$state = 0;
if (isset($_POST["state"])) {
   $state = $_POST["state"];
}

echo "<table class='tab_cadre'>";
if (isset($_POST["users_id"]) && isset($_POST["entity"])) {
   echo "<tr class='tab_bg_2'><td>".$LANG['common'][95]."&nbsp;:&nbsp;</td>";
   echo "<td class='center'>";
   User::dropdown(array('name'   => "plan[users_id]",
                        'value'  => $_POST["users_id"],
                        'right'  => "own_ticket",
                        'entity' => $_POST["entity"]));
   echo "</td></tr>\n";
}

echo "<tr class='tab_bg_2'><td>".$LANG['state'][0]."&nbsp;:&nbsp;</td>";
echo "<td class='center'>";
Planning::dropdownState("plan[state]", $state);
echo "</td></tr>";

echo "<tr class='tab_bg_2'><td>".$LANG['search'][8]."&nbsp;:&nbsp;</td><td>";
$rand_begin = showDateTimeFormItem("plan[begin]", $begin, -1, false, true, '', '',
                                   $CFG_GLPI["planning_begin"], $CFG_GLPI["planning_end"]);
echo "</td></tr>\n";

echo "<tr class='tab_bg_2'><td>".$LANG['planning'][3]."&nbsp;:&nbsp;</td><td>";

$values = array(0                    => $LANG['search'][12],
                15*MINUTE_TIMESTAMP  => '0'.$LANG['gmt'][2].'15',
                30*MINUTE_TIMESTAMP  => '0'.$LANG['gmt'][2].'30',
                45*MINUTE_TIMESTAMP  => '0'.$LANG['gmt'][2].'45',
                60*MINUTE_TIMESTAMP  => '1'.$LANG['gmt'][2].'00',
                90*MINUTE_TIMESTAMP  => '1'.$LANG['gmt'][2].'30',
                120*MINUTE_TIMESTAMP => '2'.$LANG['gmt'][2].'00',
                150*MINUTE_TIMESTAMP => '2'.$LANG['gmt'][2].'30',
                180*MINUTE_TIMESTAMP => '3'.$LANG['gmt'][2].'00',
                210*MINUTE_TIMESTAMP => '3'.$LANG['gmt'][2].'30',
                4*HOUR_TIMESTAMP     => '4'.$LANG['gmt'][2].'00',
                5*HOUR_TIMESTAMP     => '5'.$LANG['gmt'][2].'00',
                6*HOUR_TIMESTAMP     => '6'.$LANG['gmt'][2].'00',
                7*HOUR_TIMESTAMP     => '7'.$LANG['gmt'][2].'00',
                8*HOUR_TIMESTAMP     => '8'.$LANG['gmt'][2].'00');

$default_delay   = 0;
$begin_timestamp = strtotime($begin);
$end_timestamp   = strtotime($end);

// Floor with MINUTE_TIMESTAMP for rounded purpose
$computed_delay = floor(($end_timestamp-$begin_timestamp)/15/MINUTE_TIMESTAMP)*15*MINUTE_TIMESTAMP;

if (isset($values[$computed_delay])) {
   $default_delay = $computed_delay;
}
$rand = Dropdown::showFromArray("plan[_duration]", $values, array('value' => $default_delay));

echo "<br><div id='date_end$rand'></div>";

$params = array('duration'     => '__VALUE__',
                'end'          => $end,
                'global_begin' => $CFG_GLPI["planning_begin"],
                'global_end'   => $CFG_GLPI["planning_end"]);

ajaxUpdateItemOnSelectEvent("dropdown_plan[_duration]$rand", "date_end$rand",
                            $CFG_GLPI["root_doc"]."/ajax/planningend.php", $params, false);

if ($default_delay==0) {
   $params['duration'] = 0;
   ajaxUpdateItem("date_end$rand", $CFG_GLPI["root_doc"]."/ajax/planningend.php", $params);
}

echo "</td></tr>\n";
echo "</table>\n";

ajaxFooter();

?>
