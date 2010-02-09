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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

commonHeader($LANG['Menu'][13],$_SERVER['PHP_SELF'],"maintain","stat");

checkRight("statistic","1");

if (isset($_GET["date1"])) {
   $_POST["date1"] = $_GET["date1"];
}
if (isset($_GET["date2"])) {
   $_POST["date2"] = $_GET["date2"];
}
if (isset($_GET["type"])) {
   $_POST["type"] = $_GET["type"];
}
if (empty($_POST["type"])) {
   $_POST["type"] = "user";
}

if (empty($_POST["date1"]) && empty($_POST["date2"])) {
   $year = date("Y")-1;
   $_POST["date1"] = date("Y-m-d",mktime(1,0,0,date("m"),date("d"),$year));
   $_POST["date2"] = date("Y-m-d");
}

if (!empty($_POST["date1"])
    && !empty($_POST["date2"])
    && strcmp($_POST["date2"],$_POST["date1"]) < 0) {

   $tmp = $_POST["date1"];
   $_POST["date1"] = $_POST["date2"];
   $_POST["date2"] = $tmp;
}

if (!isset($_GET["start"])) {
   $_GET["start"] = 0;
}

$items =
   array($LANG['job'][4] => array('user'  => array('title' => $LANG['common'][37],
                                                  'field' => 'glpi_tickets.users_id'),
                                  'users_id_recipient'
                                          => array('title' => $LANG['job'][3],
                                                   'field' => 'glpi_tickets.users_id_recipient'),
                                  'group' => array('title' => $LANG['common'][35],
                                                   'field' => 'glpi_tickets.groups_id'),
                                  'usertitles_id'
                                          => array('title' => $LANG['users'][1],
                                                   'field' => 'glpi_usertitles.usertitles_id'),
                                  'usercategories_id'
                                          => array('title' => $LANG['users'][2],
                                                   'field' => 'glpi_users.usercategories_id')),
         $LANG['common'][32] => array('ticketcategories_id'
                                                 => array('title' => $LANG['common'][36],
                                                          'field' => 'glpi_tickets.ticketcategories_id'),
                                      'urgency'  => array('title' => $LANG['joblist'][29],
                                                          'field' => 'glpi_tickets.urgency'),
                                      'impact'   => array('title' => $LANG['joblist'][30],
                                                          'field' => 'glpi_tickets.impact'),
                                      'priority' => array('title' => $LANG['joblist'][2],
                                                          'field' => 'glpi_tickets.priority'),
                                      'requesttypes_id'
                                                 => array('title' => $LANG['job'][44],
                                                          'field' => 'glpi_tickets.requesttypes_id'),
                                      'ticketsolutiontypes_id'
                                                 => array('title' => $LANG['job'][48],
                                                          'field' => 'glpi_tickets.ticketsolutiontypes_id')),
         $LANG['job'][5] => array('technicien'
                                       => array('title' => $LANG['job'][6]." ".$LANG['stats'][48],
                                                'field' => 'glpi_tickets.users_id_assign'),
                                  'technicien_followup'
                                       => array('title' => $LANG['job'][6]." ".$LANG['stats'][49],
                                                'field' => 'glpi_followup.users_id'),
                                  'groups_id_assign'
                                       => array('title' => $LANG['common'][35],
                                                'field' => 'glpi_tickets.groups_id_assign'),
                                  'enterprise'
                                       => array('title' => $LANG['financial'][26],
                                                'field' => 'glpi_tickets.suppliers_id_assign')));
$INSELECT = "";
foreach ($items as $label => $tab) {
   $INSELECT .= "<optgroup label=\"$label\">";
   foreach ($tab as $key => $val) {
      // Current field
      if ($key == $_POST["type"]) {
         $field = $val["field"];
      }
      $INSELECT .= "<option value='$key' ".($key==$_POST["type"]?"selected":"").">".$val['title'].
                   "</option>";
   }
   $INSELECT .= "</optgroup>";
}

echo "<div class='center'><form method='post' name='form' action='stat.tracking.php'>";
echo "<table class='tab_cadre'><tr class='tab_bg_2'>";
echo "<td rowspan='2' class='center'>";
echo "<select name='type'>";
echo $INSELECT;
echo "</select></td>";
echo "<td class='right'>".$LANG['search'][8]."&nbsp;:</td><td>";
showDateFormItem("date1",$_POST["date1"]);
echo "</td><td rowspan='2' class='center'>";
echo "<input type='submit' class='button' name='submit' value='". $LANG['buttons'][7] ."'></td></tr>";
echo "<tr class='tab_bg_2'><td class='right'>".$LANG['search'][9]."&nbsp;:</td><td>";
showDateFormItem("date2",$_POST["date2"]);
echo "</td></tr>";
echo "</table></form></div>";

$val = getStatsItems($_POST["date1"],$_POST["date2"],$_POST["type"]);
$params = array('type'  => $_POST["type"],
                'field' => $field,
                'date1' => $_POST["date1"],
                'date2' => $_POST["date2"],
                'start' => $_GET["start"]);

printPager($_GET['start'],count($val),$_SERVER['PHP_SELF'],
           "date1=".$_POST["date1"]."&amp;date2=".$_POST["date2"]."&amp;type=".$_POST["type"],
           'Stat',$params);

displayStats($_POST["type"],$_POST["date1"],$_POST["date2"],$_GET['start'],$val);

commonFooter();

?>
