<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

include ('../inc/includes.php');

Html::header(__('Statistics'), $_SERVER['PHP_SELF'], "helpdesk", "stat");

Session::checkRight("statistic", READ);

if (empty($_GET["date1"]) && empty($_GET["date2"])) {
   $year          = date("Y")-1;
   $_GET["date1"] = date("Y-m-d", mktime(1, 0, 0, (int)date("m"), (int)date("d"), $year));
   $_GET["date2"] = date("Y-m-d");
}

if (!empty($_GET["date1"])
    && !empty($_GET["date2"])
    && (strcmp($_GET["date2"], $_GET["date1"]) < 0)) {

   $tmp           = $_GET["date1"];
   $_GET["date1"] = $_GET["date2"];
   $_GET["date2"] = $tmp;
}

Stat::title();

if (!$item = getItemForItemtype($_GET['itemtype'])) {
   exit;
}

$stat = new Stat();

$stat->displaySearchForm(
   $_GET['itemtype'],
   $_GET['date1'],
   $_GET['date2']
);

///////// Stats nombre intervention
$values = [];
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

$stat->displayLineGraph(
   _x('Quantity', 'Number') . " - " . $item->getTypeName(Session::getPluralNumber()),
   array_keys($values['total']), [
      [
         'name' => _nx('ticket', 'Opened', 'Opened', 2),
         'data' => $values['total']
      ], [
         'name' => _nx('ticket', 'Solved', 'Solved', 2),
         'data' => $values['solved']
      ], [
         'name' => __('Late'),
         'data' => $values['late']
      ], [
         'name' => __('Closed'),
         'data' => $values['closed']
      ]
   ]
);

$values = [];
//Temps moyen de resolution d'intervention
$values['avgsolved'] = Stat::constructEntryValues($_GET['itemtype'], "inter_avgsolvedtime",
                                                   $_GET["date1"], $_GET["date2"]);
// Pass to hour values
foreach ($values['avgsolved'] as &$val) {
   $val = round($val / HOUR_TIMESTAMP, 2);
}

//Temps moyen de cloture d'intervention
$values['avgclosed'] = Stat::constructEntryValues($_GET['itemtype'], "inter_avgclosedtime",
                                                   $_GET["date1"], $_GET["date2"]);
// Pass to hour values
foreach ($values['avgclosed'] as &$val) {
   $val = round($val / HOUR_TIMESTAMP, 2);
}
//Temps moyen d'intervention reel
$values['avgactiontime'] = Stat::constructEntryValues($_GET['itemtype'], "inter_avgactiontime",
                                                       $_GET["date1"], $_GET["date2"]);

// Pass to hour values
foreach ($values['avgactiontime'] as &$val) {
   $val =  round($val / HOUR_TIMESTAMP, 2);
}

$stat->displayLineGraph(
   __('Average time') . " - " .  _n('Hour', 'Hours', 2),
   array_keys($values['avgsolved']), [
      [
         'name' => __('Closure'),
         'data' => $values['avgsolved']
      ], [
         'name' => __('Resolution'),
         'data' => $values['avgclosed']
      ], [
         'name' => __('Real duration'),
         'data' => $values['avgactiontime']
      ]
   ]
);

if ($_GET['itemtype'] == 'Ticket') {

   ///////// Satisfaction
   $values = [];
   $values['opensatisfaction']   = Stat::constructEntryValues($_GET['itemtype'],
                                                              "inter_opensatisfaction",
                                                              $_GET["date1"], $_GET["date2"]);

   $values['answersatisfaction'] = Stat::constructEntryValues($_GET['itemtype'],
                                                              "inter_answersatisfaction",
                                                              $_GET["date1"], $_GET["date2"]);

   $stat->displayLineGraph(
      __('Satisfaction survey') . " - " .  __('Tickets'),
      array_keys($values['opensatisfaction']), [
         [
            'name' => _nx('survey', 'Opened', 'Opened', 2),
            'data' => $values['opensatisfaction']
         ], [
            'name' => _nx('survey', 'Answered', 'Answered', 2),
            'data' => $values['answersatisfaction']
         ]
      ]
   );

   $values = [];
   $values['avgsatisfaction'] = Stat::constructEntryValues($_GET['itemtype'],
                                                           "inter_avgsatisfaction",
                                                           $_GET["date1"], $_GET["date2"]);

   $stat->displayLineGraph(
      __('Satisfaction'),
      array_keys($values['avgsatisfaction']), [
         [
            'name' => __('Satisfaction'),
            'data' => $values['avgsatisfaction']
         ]
      ]
   );
}


Html::footer();
