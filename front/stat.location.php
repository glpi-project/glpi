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

commonHeader($LANG['Menu'][13],'',"maintain","stat");

checkRight("statistic","1");


if (empty($_REQUEST["showgraph"])) {
   $_REQUEST["showgraph"] = 0;
}

if (empty($_REQUEST["date1"]) && empty($_REQUEST["date2"])) {
   $year = date("Y")-1;
   $_REQUEST["date1"] = date("Y-m-d",mktime(1,0,0,date("m"),date("d"),$year));
   $_REQUEST["date2"] = date("Y-m-d");
}

if (!empty($_REQUEST["date1"])
    && !empty($_REQUEST["date2"])
    && strcmp($_REQUEST["date2"], $_REQUEST["date1"]) < 0) {

   $tmp = $_REQUEST["date1"];
   $_REQUEST["date1"] = $_REQUEST["date2"];
   $_REQUEST["date2"] = $tmp;
}

if (!isset($_REQUEST["start"])) {
   $_REQUEST["start"] = 0;
}
if (isset($_REQUEST["dropdown"])) {
   $_REQUEST["dropdown"] = $_REQUEST["dropdown"];
}
if (empty($_REQUEST["dropdown"])) {
   $_REQUEST["dropdown"] = "ComputerType";
}

echo "<form method='get' name='form' action='stat.location.php'>";

echo "<table class='tab_cadre'><tr class='tab_bg_2'><td rowspan='2'>";
echo "<select name='dropdown'>";
echo "<optgroup label='".$LANG['setup'][0]."'>";
echo "<option value='ComputerType' ".($_REQUEST["dropdown"]=="ComputerType"?"selected":"").">".
       $LANG['common'][17]."</option>";
echo "<option value='ComputerModel' ".($_REQUEST["dropdown"]=="ComputerModel"?"selected":"").">".
       $LANG['common'][22]."</option>";
echo "<option value='OperatingSystem' ".
      ($_REQUEST["dropdown"]=="OperatingSystem"?"selected":"").">".$LANG['computers'][9]."</option>";
echo "<option value='Location' ".($_REQUEST["dropdown"]=="Location"?"selected":"").">".
      $LANG['common'][15]."</option>";
echo "</optgroup>";

$devices = Dropdown::getDeviceItemTypes();
foreach ($devices as $label => $dp) {
   echo "<optgroup label='$label'>";
   foreach ($dp as $i => $name) {
      echo "<option value='$i' ".($_REQUEST["dropdown"]==$i?"selected":"").">$name</option>";
   }
   echo "</optgroup>";
}
echo "</select></td>";

echo "<td class='right'>".$LANG['search'][8]."&nbsp;:</td><td>";
showDateFormItem("date1",$_REQUEST["date1"]);
echo "</td>";
echo "<td class='right'>".$LANG['stats'][7]."&nbsp;:</td>";
echo "<td rowspan='2' class='center'>";
echo "<input type='submit' class='button' name='submit' value='". $LANG['buttons'][7] ."'></td></tr>";
echo "<tr class='tab_bg_2'><td class='right'>".$LANG['search'][9]."&nbsp;:</td><td>";
showDateFormItem("date2",$_REQUEST["date2"]);
echo "</td><td class='center'>";
Dropdown::showYesNo('showgraph',$_REQUEST['showgraph']);
echo "</td>";
echo "</tr>";
echo "</table></form>";

if (empty($_REQUEST["dropdown"]) || !class_exists($_REQUEST["dropdown"])) {
   // Do nothing
   commonFooter();
   exit();
}
$item = new $_REQUEST["dropdown"];
if (!($item instanceof CommonDevice)) {
  // echo "Dropdown";
   $type = "comp_champ";

   $val = Stat::getItems($_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST["dropdown"]);
   $params = array('type'     => $type,
                   'dropdown' => $_REQUEST["dropdown"],
                   'date1'    => $_REQUEST["date1"],
                   'date2'    => $_REQUEST["date2"],
                   'start'    => $_REQUEST["start"]);

} else {
//   echo "Device";
   $type = "device";
   $field = $_REQUEST["dropdown"];

   $val = Stat::getItems($_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST["dropdown"]);
   $params = array('type'     => $type,
                   'dropdown' => $_REQUEST["dropdown"],
                   'date1'    => $_REQUEST["date1"],
                   'date2'    => $_REQUEST["date2"],
                   'start'    => $_REQUEST["start"]);
}

printPager($_REQUEST['start'], count($val), $CFG_GLPI['root_doc'].'/front/stat.location.php',
           "date1=".$_REQUEST["date1"]."&amp;date2=".$_REQUEST["date2"]."&amp;dropdown=".
               $_REQUEST["dropdown"],
           'Stat', $params);

if (!$_REQUEST['showgraph']) {
   Stat::show($type, $_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST['start'], $val,
              $_REQUEST["dropdown"]);
} else {
   $data = Stat::getDatas($type, $_REQUEST["date1"], $_REQUEST["date2"], $_REQUEST['start'], $val,
                          $_REQUEST["dropdown"]);

   if (isset($data['opened']) && is_array($data['opened'])) {
      foreach ($data['opened'] as $key => $val) {
         $cleandata[html_clean($key)] = $val;
      }
      Stat::showGraph(array($LANG['stats'][5] => $cleandata),
                      array('title'     => $LANG['stats'][5],
                            'showtotal' => 1,
                            'unit'      => $LANG['stats'][35],
                            'type'      => 'pie'));
   }

   if (isset($data['solved']) && is_array($data['solved'])) {
      foreach ($data['solved'] as $key => $val) {
         $cleandata[html_clean($key)] = $val;
      }

      Stat::showGraph(array($LANG['stats'][11]=>$cleandata),
                      array('title'     => $LANG['stats'][11],
                            'showtotal' => 1,
                            'unit'      => $LANG['stats'][35],
                            'type'      => 'pie'));
   }

   if (isset($data['late']) && is_array($data['late'])) {
      foreach ($data['late'] as $key => $val) {
         $cleandata[html_clean($key)] = $val;
      }

      Stat::showGraph(array($LANG['stats'][19]=>$cleandata),
                      array('title'     => $LANG['stats'][19],
                            'showtotal' => 1,
                            'unit'      => $LANG['stats'][35],
                            'type'      => 'pie'));
   }

   if (isset($data['closed']) && is_array($data['closed'])) {
      foreach ($data['closed'] as $key => $val) {
         $newkey=html_clean($key);
         $cleandata[$newkey]=$val;
      }
      Stat::showGraph(array($LANG['stats'][11]=>$cleandata),
                      array('title'     => $LANG['stats'][17],
                            'showtotal' => 1,
                            'unit'      => $LANG['stats'][35],
                            'type'      => 'pie'));
   }

}

commonFooter();

?>
