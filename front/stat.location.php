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
if (isset($_GET["dropdown"])) {
   $_POST["dropdown"] = $_GET["dropdown"];
}
if (empty($_POST["dropdown"])) {
   $_POST["dropdown"] = "ComputerType";
}

echo "<form method='post' name='form' action='stat.location.php'>";

echo "<table class='tab_cadre'><tr class='tab_bg_2'><td rowspan='2'>";
echo "<select name='dropdown'>";
echo "<optgroup label='".$LANG['setup'][0]."'>";
echo "<option value='ComputerType' ".($_POST["dropdown"]=="ComputerType"?"selected":"").
      ">".$LANG['common'][17]."</option>";
echo "<option value='ComputerModel' ".($_POST["dropdown"]=="ComputerModel"?"selected":"").
      ">".$LANG['common'][22]."</option>";
echo "<option value='OperatingSystem' ".
      ($_POST["dropdown"]=="OperatingSystem"?"selected":"").">".$LANG['computers'][9]."</option>";
echo "<option value='Location' ".($_POST["dropdown"]=="Location"?"selected":"").">".
      $LANG['common'][15]."</option>";
echo "</optgroup>";

$devices = Dropdown::getDeviceItemTypes();
foreach($devices as $label => $dp) {
   echo "<optgroup label='$label'>";
   foreach ($dp as $i => $name) {
      echo "<option value='$i' ".($_POST["dropdown"]==$i?"selected":"").">$name</option>";
   }
   echo "</optgroup>";
}
echo "</select></td>";

echo "<td class='right'>".$LANG['search'][8]."&nbsp;:</td><td>";
showDateFormItem("date1",$_POST["date1"]);
echo "</td><td rowspan='2' class='center'>";
echo "<input type='submit' class='button' name='submit' value='". $LANG['buttons'][7] ."'></td></tr>";
echo "<tr class='tab_bg_2'><td class='right'>".$LANG['search'][9]."&nbsp;:</td><td>";
showDateFormItem("date2",$_POST["date2"]);
echo "</td></tr>";
echo "</table></form>";

if (empty($_POST["dropdown"]) || !class_exists($_POST["dropdown"])) {
   // Do nothing
   commonFooter();
   exit();
}
$item = new $_POST["dropdown"];
if (!($item instanceof CommonDevice)) {
  // echo "Dropdown";
   $type = "comp_champ";

   $val = Stat::getItems($_POST["date1"],$_POST["date2"],$_POST["dropdown"]);
   $params = array('type'     => $type,
                   'dropdown' => $_POST["dropdown"],
                   'date1'    => $_POST["date1"],
                   'date2'    => $_POST["date2"],
                   'start'    => $_GET["start"]);

   printPager($_GET['start'],count($val),$_SERVER['PHP_SELF'],
              "date1=".$_POST["date1"]."&amp;date2=".$_POST["date2"]."&amp;dropdown=".$_POST["dropdown"],
              'Stat',$params);

   $data=Stat::show($type,$_POST["date1"],$_POST["date2"],$_GET['start'],$val,$_POST["dropdown"]);

} else {
//   echo "Device";
   $type = "device";
   $field = $_POST["dropdown"];

   $val = Stat::getItems($_POST["date1"],$_POST["date2"],$_POST["dropdown"]);
   $params = array('type'     => $type,
                   'dropdown' => $_POST["dropdown"],
                   'date1'    => $_POST["date1"],
                   'date2'    => $_POST["date2"],
                   'start'    => $_GET["start"]);

   printPager($_GET['start'],count($val),$_SERVER['PHP_SELF'],
              "date1=".$_POST["date1"]."&amp;date2=".$_POST["date2"]."&amp;dropdown=".$_POST["dropdown"],
              'Stat',$params);

   $data=Stat::show($type,$_POST["date1"],$_POST["date2"],$_GET['start'],$val,$_POST["dropdown"]);
}

echo '<br>';
if (is_array($data['opened'])) {
   Stat::showGraph(array($LANG['stats'][5]=>$data['opened'])
                  ,array('title'=>$LANG['stats'][5],
                        'showtotal' => 1,
                        'unit'      => $LANG['stats'][35],
                        'type'      => 'pie'));
}
if (is_array($data['solved'])) {
   Stat::showGraph(array($LANG['stats'][11]=>$data['solved'])
                  ,array('title'    => $LANG['stats'][11],
                        'showtotal' => 1,
                        'unit'      => $LANG['stats'][35],
                        'type'      => 'pie'));
}
commonFooter();

?>
