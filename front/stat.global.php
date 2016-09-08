<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

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

Html::header(__('Statistics'), $_SERVER['PHP_SELF'], "helpdesk", "stat");

Session::checkRight("statistic", READ);


if (empty($_GET["date1"]) && empty($_GET["date2"])) {
   $year          = date("Y")-1;
   $_GET["date1"] = date("Y-m-d", mktime(1,0,0,date("m"), date("d"), $year));
   $_GET["date2"] = date("Y-m-d");
}

if (!empty($_GET["date1"])
    && !empty($_GET["date2"])
    && (strcmp($_GET["date2"],$_GET["date1"]) < 0)) {

   $tmp           = $_GET["date1"];
   $_GET["date1"] = $_GET["date2"];
   $_GET["date2"] = $tmp;
}

Stat::title();

if (!$item = getItemForItemtype($_GET['itemtype'])) {
   exit;
}

echo "<form method='get' name='form' action='stat.global.php'><div class='center'>";
// Keep it at first parameter
echo "<input type='hidden' name='itemtype' value=\"".$_GET['itemtype']."\">";

echo "<table class='tab_cadre'>";
echo "<tr class='tab_bg_2'><td class='right'>".__('Start date')."</td><td>";
Html::showDateField("date1", array('value' => $_GET["date1"]));
echo "</td><td rowspan='2' class='center'>";

echo "<input type='submit' class='submit' value=\"".__s('Display report')."\"></td></tr>";

echo "<tr class='tab_bg_2'><td class='right'>".__('End date')."</td><td>";
Html::showDateField("date2", array('value' => $_GET["date2"]));
echo "</td></tr>";
echo "</table></div>";

///////// Stats nombre intervention
// Total des interventions
$values['total']   = Stat::constructEntryValues($_GET['itemtype'], "inter_total", $_GET["date1"],
                                                $_GET["date2"]);
// Total des interventions résolues
$values['solved']  = Stat::constructEntryValues($_GET['itemtype'], "inter_solved", $_GET["date1"],
                                                $_GET["date2"]);
// Total des interventions closes
$values['closed']  = Stat::constructEntryValues($_GET['itemtype'], "inter_closed", $_GET["date1"],
                                                $_GET["date2"]);
// Total des interventions closes
$values['late']    = Stat::constructEntryValues($_GET['itemtype'], "inter_solved_late",
                                                $_GET["date1"], $_GET["date2"]);

$available = array('total'  => _nx('ticket', 'Opened', 'Opened', 2),
                   'solved' => _nx('ticket', 'Solved', 'Solved', 2),
                   'late'   => __('Late'),
                   'closed' => __('Closed'),);
echo "<div class='center'>";

$show_all = false;
if (!isset($_GET['graph']) || (count($_GET['graph']) == 0)) {
   $show_all = true;
}

foreach ($available as $key => $name) {
   echo "<input type='checkbox' onchange='submit()' name='graph[$key]' ".
          ($show_all || isset($_GET['graph'][$key])?"checked":"")."> ".$name."&nbsp;";
}
echo "</div>";

$toprint = array();
foreach ($available as $key => $name) {
   if ($show_all || isset($_GET['graph'][$key])) {
      $toprint[$name] = $values[$key];
   }
}

Stat::showGraph($toprint, array('title'     => _x('Quantity', 'Number'),
                                'showtotal' => 1,
                                'unit'      => $item->getTypeName(Session::getPluralNumber())));

//Temps moyen de resolution d'intervention
$values2['avgsolved'] = Stat::constructEntryValues($_GET['itemtype'] ,"inter_avgsolvedtime",
                                                   $_GET["date1"], $_GET["date2"]);
// Pass to hour values
foreach ($values2['avgsolved'] as $key => $val) {
   $values2['avgsolved'][$key] /= HOUR_TIMESTAMP;
}

//Temps moyen de cloture d'intervention
$values2['avgclosed'] = Stat::constructEntryValues($_GET['itemtype'], "inter_avgclosedtime",
                                                   $_GET["date1"], $_GET["date2"]);
// Pass to hour values
foreach ($values2['avgclosed'] as $key => $val) {
   $values2['avgclosed'][$key] /= HOUR_TIMESTAMP;
}
//Temps moyen d'intervention reel
$values2['avgactiontime'] = Stat::constructEntryValues($_GET['itemtype'], "inter_avgactiontime",
                                                       $_GET["date1"], $_GET["date2"]);

// Pass to hour values
foreach ($values2['avgactiontime'] as $key => $val) {
   $values2['avgactiontime'][$key] /= HOUR_TIMESTAMP;
}


$available = array('avgclosed'      => __('Closure'),
                   'avgsolved'      => __('Resolution'),
                   'avgactiontime'  => __('Real duration'));


if ($_GET['itemtype'] == 'Ticket') {
   $available['avgtaketime'] = __('Take into account');

   //Temps moyen de prise en compte de l'intervention
   $values2['avgtaketime'] = Stat::constructEntryValues($_GET['itemtype'], "inter_avgtakeaccount",
                                                        $_GET["date1"], $_GET["date2"]);

   // Pass to hour values
   foreach ($values2['avgtaketime'] as $key => $val) {
      $values2['avgtaketime'][$key] /= HOUR_TIMESTAMP;
   }
}


echo "<div class='center'>";

$show_all2 = false;
if (!isset($_GET['graph2']) || (count($_GET['graph2']) == 0)) {
   $show_all2 = true;
}


foreach ($available as $key => $name) {
   echo "<input type='checkbox' onchange='submit()' name='graph2[$key]' ".
          ($show_all2||isset($_GET['graph2'][$key])?"checked":"")."> ".$name."&nbsp;";
}
echo "</div>";

$toprint = array();
foreach ($available as $key => $name) {
   if ($show_all2 || isset($_GET['graph2'][$key])) {
      $toprint[$name] = $values2[$key];
   }
}

Stat::showGraph($toprint, array('title'     => __('Average time'),
                                'unit'      => _n('Hour', 'Hours', 2),
                                'showtotal' => 1,
                                'datatype'  => 'average'));


if ($_GET['itemtype'] == 'Ticket') {

   ///////// Satisfaction
   $values['opensatisfaction']   = Stat::constructEntryValues($_GET['itemtype'],
                                                              "inter_opensatisfaction",
                                                              $_GET["date1"], $_GET["date2"]);

   $values['answersatisfaction'] = Stat::constructEntryValues($_GET['itemtype'],
                                                              "inter_answersatisfaction",
                                                              $_GET["date1"], $_GET["date2"]);


   $available = array('opensatisfaction'   => _nx('survey','Opened','Opened',2),
                      'answersatisfaction' => _nx('survey','Answered','Answered',2));
   echo "<div class='center'>";

   foreach ($available as $key => $name) {
      echo "<input type='checkbox' onchange='submit()' name='graph[$key]' ".
            ($show_all || isset($_GET['graph'][$key])?"checked":"")."> ".$name."&nbsp;";
   }
   echo "</div>";

   $toprint = array();
   foreach ($available as $key => $name) {
      if ($show_all || isset($_GET['graph'][$key])) {
         $toprint[$name] = $values[$key];
      }
   }

   Stat::showGraph($toprint, array('title'     => __('Satisfaction survey'),
                                   'showtotal' => 1,
                                   'unit'      => __('Tickets')));


   $values['avgsatisfaction'] = Stat::constructEntryValues($_GET['itemtype'],
                                                           "inter_avgsatisfaction",
                                                           $_GET["date1"], $_GET["date2"]);

   $available = array('avgsatisfaction' => __('Satisfaction'));
   echo "<div class='center'>";

   foreach ($available as $key => $name) {
      echo "<input type='checkbox' onchange='submit()' name='graph[$key]' ".
            ($show_all||isset($_GET['graph'][$key])?"checked":"")."> ".$name."&nbsp;";
   }
   echo "</div>";

   $toprint = array();
   foreach ($available as $key => $name) {
      if ($show_all
          || isset($_GET['graph'][$key])) {
         $toprint[$name] = $values[$key];
      }
   }

   Stat::showGraph($toprint, array('title' => __('Satisfaction')));
}

// form using GET method : CRSF not needed
echo "</form>";
Html::footer();
?>