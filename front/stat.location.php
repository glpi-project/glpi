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

include ('../inc/includes.php');

Html::header(__('Statistics'), '', "helpdesk", "stat");

Session::checkRight("statistic", READ);


if (empty($_GET["showgraph"])) {
   $_GET["showgraph"] = 0;
}

if (empty($_GET["date1"]) && empty($_GET["date2"])) {
   $year          = date("Y")-1;
   $_GET["date1"] = date("Y-m-d",mktime(1,0,0,date("m"),date("d"),$year));
   $_GET["date2"] = date("Y-m-d");
}

if (!empty($_GET["date1"])
    && !empty($_GET["date2"])
    && (strcmp($_GET["date2"], $_GET["date1"]) < 0)) {

   $tmp           = $_GET["date1"];
   $_GET["date1"] = $_GET["date2"];
   $_GET["date2"] = $tmp;
}

if (!isset($_GET["start"])) {
   $_GET["start"] = 0;
}
// Why this test ?? For me it's doing nothing
if (isset($_GET["dropdown"])) {
   $_GET["dropdown"] = $_GET["dropdown"];
}

if (empty($_GET["dropdown"])) {
   $_GET["dropdown"] = "ComputerType";
}

if (!isset($_GET['itemtype'])) {
   $_GET['itemtype'] = 'Ticket';
}

Stat::title();

echo "<form method='get' name='form' action='stat.location.php'>";
// keep it first param
echo "<input type='hidden' name='itemtype' value='". $_GET['itemtype'] ."'>";

echo "<table class='tab_cadre_fixe' ><tr class='tab_bg_2'><td rowspan='2' width='30%'>";
$values = array(_n('Dropdown','Dropdowns',2) => array('ComputerType'    => __('Type'),
                                                       'ComputerModel'   => __('Model'),
                                                       'OperatingSystem' => __('Operating system'),
                                                       'Location'        => __('Location')),
               );
$devices = Dropdown::getDeviceItemTypes();
foreach ($devices as $label => $dp) {
   foreach ($dp as $i => $name) {
      $values[$label][$i] = $name;
   }
}

Dropdown::showFromArray('dropdown', $values, array('value' => $_GET["dropdown"]));

echo "</td>";

echo "<td class='right'>".__('Start date')."</td><td>";
Html::showDateField("date1", array('value' => $_GET["date1"]));
echo "</td>";
echo "<td class='right'>".__('Show graphics')."</td>";
echo "<td rowspan='2' class='center'>";
echo "<input type='submit' class='submit' name='submit' value='".__s('Display report')."'></td></tr>";

echo "<tr class='tab_bg_2'><td class='right'>".__('End date')."</td><td>";
Html::showDateField("date2", array('value' => $_GET["date2"]));
echo "</td><td class='center'>";
Dropdown::showYesNo('showgraph', $_GET['showgraph']);
echo "</td>";
echo "</tr>";
echo "</table>";
// form using GET method : CRSF not needed
echo "</form>";

if (empty($_GET["dropdown"])
    || !($item = getItemForItemtype($_GET["dropdown"]))) {
   // Do nothing
   Html::footer();
   exit();
}


if (!($item instanceof CommonDevice)) {
  // echo "Dropdown";
   $type = "comp_champ";

   $val = Stat::getItems($_GET['itemtype'], $_GET["date1"], $_GET["date2"], $_GET["dropdown"]);
   $params = array('type'     => $type,
                   'dropdown' => $_GET["dropdown"],
                   'date1'    => $_GET["date1"],
                   'date2'    => $_GET["date2"],
                   'start'    => $_GET["start"]);

} else {
//   echo "Device";
   $type  = "device";
   $field = $_GET["dropdown"];

   $val = Stat::getItems($_GET['itemtype'], $_GET["date1"], $_GET["date2"], $_GET["dropdown"]);
   $params = array('type'     => $type,
                   'dropdown' => $_GET["dropdown"],
                   'date1'    => $_GET["date1"],
                   'date2'    => $_GET["date2"],
                   'start'    => $_GET["start"]);
}

Html::printPager($_GET['start'], count($val), $CFG_GLPI['root_doc'].'/front/stat.location.php',
                 "date1=".$_GET["date1"]."&amp;date2=".$_GET["date2"].
                     "&amp;itemtype=".$_GET['itemtype']."&amp;dropdown=".$_GET["dropdown"],
                 'Stat', $params);

if (!$_GET['showgraph']) {
   Stat::showTable($_GET['itemtype'], $type, $_GET["date1"], $_GET["date2"], $_GET['start'], $val,
                   $_GET["dropdown"]);
} else {
   $data = Stat::getDatas($_GET['itemtype'], $type, $_GET["date1"], $_GET["date2"], $_GET['start'],
                          $val, $_GET["dropdown"]);

   if (isset($data['opened']) && is_array($data['opened'])) {
      foreach ($data['opened'] as $key => $val) {
         $cleandata[Html::clean($key)] = $val;
      }
      Stat::showGraph(array(__('Number opened') => $cleandata),
                      array('title'     => __('Number opened'),
                            'showtotal' => 1,
                            'unit'      => __('Tickets'),
                            'type'      => 'pie'));
   }

   if (isset($data['solved']) && is_array($data['solved'])) {
      foreach ($data['solved'] as $key => $val) {
         $cleandata[Html::clean($key)] = $val;
      }

      Stat::showGraph(array(__('Number solved') => $cleandata),
                      array('title'     => __('Number solved'),
                            'showtotal' => 1,
                            'unit'      => __('Tickets'),
                            'type'      => 'pie'));
   }

   if (isset($data['late']) && is_array($data['late'])) {
      foreach ($data['late'] as $key => $val) {
         $cleandata[Html::clean($key)] = $val;
      }

      Stat::showGraph(array(__('Number solved late') => $cleandata),
                      array('title'     => __('Number solved late'),
                            'showtotal' => 1,
                            'unit'      => __('Tickets'),
                            'type'      => 'pie'));
   }

   if (isset($data['closed']) && is_array($data['closed'])) {
      foreach ($data['closed'] as $key => $val) {
         $newkey = Toolbox::unclean_cross_side_scripting_deep(Html::clean($key));
         $cleandata[$newkey]=$val;
      }
      Stat::showGraph(array(__('Number closed') => $cleandata),
                      array('title'     => __('Number closed'),
                            'showtotal' => 1,
                            'unit'      => __('Tickets'),
                            'type'      => 'pie'));
   }

   if (isset($data['opensatisfaction']) && is_array($data['opensatisfaction'])) {
      foreach ($data['opensatisfaction'] as $key => $val) {
         $newkey             = Toolbox::unclean_cross_side_scripting_deep(Html::clean($key));
         $cleandata[$newkey] = $val;
      }
      Stat::showGraph(array(__('Satisfaction survey') => $cleandata),
                      array('title'     => __('Satisfaction survey'),
                            'showtotal' => 1,
                            'unit'      => __('Tickets'),
                            'type'      => 'pie'));
   }

}

Html::footer();
?>
