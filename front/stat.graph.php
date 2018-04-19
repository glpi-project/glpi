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

if (!$item = getItemForItemtype($_GET['itemtype'])) {
   exit;
}

if (empty($_POST["date1"]) && empty($_POST["date2"])) {
   if (isset($_GET["date1"])) {
      $_POST["date1"] = $_GET["date1"];
   }
   if (isset($_GET["date2"])) {
      $_POST["date2"] = $_GET["date2"];
   }
}

if (!empty($_POST["date1"])
    && !empty($_POST["date2"])
    && (strcmp($_POST["date2"], $_POST["date1"]) < 0)) {

   $tmp            = $_POST["date1"];
   $_POST["date1"] = $_POST["date2"];
   $_POST["date2"] = $tmp;
}

$cleantarget = preg_replace("/[&]date[12]=[0-9-]*/", "", $_SERVER['QUERY_STRING']);
$cleantarget = preg_replace("/[&]*id=([0-9]+[&]{0,1})/", "", $cleantarget);
$cleantarget = preg_replace("/&/", "&amp;", $cleantarget);

$next    = 0;
$prev    = 0;
$title   = "";
$cond    = '';
$parent  = 0;

$showuserlink = 0;
if (Session::haveRight('user', READ)) {
   $showuserlink = 1;
}

switch ($_GET["type"]) {
   case "technicien" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Technician'),
                         $item->getAssignName($_GET["id"], 'User', $showuserlink));
      break;

   case "technicien_followup" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Technician'),
                         $item->getAssignName($_GET["id"], 'User', $showuserlink));
      break;

   case "suppliers_id_assign" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Supplier'),
                         $item->getAssignName($_GET["id"], 'Supplier', $showuserlink));
      break;

   case "user" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('User'), getUserName($_GET["id"], $showuserlink));
      break;

   case "users_id_recipient" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('User'), getUserName($_GET["id"], $showuserlink));
      break;

   case "itilcategories_tree" :
      $parent = (isset($_GET['champ']) ? $_GET['champ'] : 0);
      $cond   = "(`id` = '$parent' OR `itilcategories_id` = '$parent')";
      // nobreak;

   case "itilcategories_id" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"],
                                $parent);
      $title   = sprintf(__('%1$s: %2$s'), __('Category'),
                         Dropdown::getDropdownName("glpi_itilcategories", $_GET["id"]));
      break;

   case 'locations_tree' :
      $parent = (isset($_GET['champ']) ? $_GET['champ'] : 0);
      $cond   = "(`id` = '$parent' OR `locations_id` = '$parent')";
      // nobreak;

   case 'locations_id' :
      $val1    = $_GET['id'];
      $val2    = '';
      $values  = Stat::getItems($_GET['itemtype'], $_POST['date1'], $_POST['date2'], $_GET['type'],
                                $parent);
      $title   = sprintf(__('%1$s: %2$s'), __('Location'),
                         Dropdown::getDropdownName('glpi_locations', $_GET['id']));
      break;

   case "type" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Type'), Ticket::getTicketTypeName($_GET["id"]));
      break;

   case 'group_tree' :
   case 'groups_tree_assign' :
      $parent = (isset($_GET['champ']) ? $_GET['champ'] : 0);
      $cond   = " (`id` = '$parent' OR `groups_id` = '$parent')
                   AND ".(($_GET["type"] == 'group_tree') ? '`is_requester`' : '`is_assign`');
      // nobreak;

   case "group" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"],
                                $parent);
      $title   = sprintf(__('%1$s: %2$s'), __('Group'),
                         Dropdown::getDropdownName("glpi_groups", $_GET["id"]));
      break;

   case "groups_id_assign" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Group'),
                         Dropdown::getDropdownName("glpi_groups", $_GET["id"]));
      break;

   case "priority" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Priority'), $item->getPriorityName($_GET["id"]));
      break;

   case "urgency" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Urgency'), $item->getUrgencyName($_GET["id"]));
      break;

   case "impact" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Impact'), $item->getImpactName($_GET["id"]));
      break;

   case "usertitles_id" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), _x('person', 'Title'),
                         Dropdown::getDropdownName("glpi_usertitles", $_GET["id"]));
      break;

   case "solutiontypes_id" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Solution type'),
                         Dropdown::getDropdownName("glpi_solutiontypes", $_GET["id"]));
      break;

   case "usercategories_id" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Category'),
                         Dropdown::getDropdownName("glpi_usercategories", $_GET["id"]));
      break;

   case "requesttypes_id" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Request source'),
                         Dropdown::getDropdownName("glpi_requesttypes", $_GET["id"]));
      break;

   case "device" :
      $val1 = $_GET["id"];
      $val2 = $_GET["champ"];
      if ($item = getItemForItemtype($_GET["champ"])) {
         $device_table = $item->getTable();
         $values       = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"],
                                        $_GET["champ"]);

         $iterator = $DB->request([
            'SELECT' => ['designation'],
            'FROM'   => $device_table,
            'WHERE'  => [
               'id' => $_GET['id']
            ]
         ]);
         $current = $iterator->next();

         $title  = sprintf(__('%1$s: %2$s'),
                           $item->getTypeName(), $current['designation']);
      }
      break;

   case "comp_champ" :
      $val1  = $_GET["id"];
      $val2  = $_GET["champ"];
      if ($item = getItemForItemtype($_GET["champ"])) {
         $table  = $item->getTable();
         $values = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"],
                                  $_GET["champ"]);
         $title  = sprintf(__('%1$s: %2$s'),
                           $item->getTypeName(), Dropdown::getDropdownName($table, $_GET["id"]));
      }
      break;
}

// Found next and prev items
$foundkey = -1;
foreach ($values as $key => $val) {
   if ($val['id'] == $_GET["id"]) {
      $foundkey = $key;
   }
}

if ($foundkey >= 0) {
   if (isset($values[$foundkey+1])) {
      $next = $values[$foundkey+1]['id'];
   }
   if (isset($values[$foundkey-1])) {
      $prev = $values[$foundkey-1]['id'];
   }
}

$stat = new Stat();

echo "<div class='center'>";
echo "<table class='tab_cadre'>";
echo "<tr><td>";
if ($prev > 0) {
   echo "<a href=\"".$_SERVER['PHP_SELF']."?$cleantarget&amp;date1=".$_POST["date1"]."&amp;date2=".
          $_POST["date2"]."&amp;id=$prev\">
          <img src='".$CFG_GLPI["root_doc"]."/pics/left.png' alt=\"".__s('Previous')."\"
           title=\"".__s('Previous')."\"></a>";
}
echo "</td>";

echo "<td width='400' class='center b'>$title</td>";
echo "<td>";
if ($next > 0) {
   echo "<a href=\"".$_SERVER['PHP_SELF']."?$cleantarget&amp;date1=".$_POST["date1"]."&amp;date2=".
          $_POST["date2"]."&amp;id=$next\">
          <img src='".$CFG_GLPI["root_doc"]."/pics/right.png' alt=\"".__s('Next')."\"
           title=\"".__s('Next')."\"></a>";
}
echo "</td>";
echo "</tr>";
echo "</table></div><br>";

$target = preg_replace("/&/", "&amp;", $_SERVER["REQUEST_URI"]);

echo "<form method='post' name='form' action='$target'><div class='center'>";
echo "<table class='tab_cadre'>";
echo "<tr class='tab_bg_2'><td class='right'>".__('Start date')."</td><td>";
Html::showDateField("date1", ['value' => $_POST["date1"]]);
echo "</td><td rowspan='2' class='center'>";
echo "<input type='hidden' name='itemtype' value=\"".$_GET['itemtype']."\">";
echo "<input type='submit' class='submit' value=\"".__s('Display report')."\"></td></tr>";

echo "<tr class='tab_bg_2'><td class='right'>".__('End date')."</td><td>";
Html::showDateField("date2", ['value' => $_POST["date2"]]);
echo "</td></tr>";
echo "</table></div>";

// form using GET method : CRSF not needed
Html::closeForm();


$show_all = false;
if (!isset($_POST['graph']) || (count($_POST['graph']) == 0)) {
   $show_all = true;
}


///////// Stats nombre intervention
// Total des interventions
$values['total']  = Stat::constructEntryValues($_GET['itemtype'], "inter_total", $_POST["date1"],
                                               $_POST["date2"], $_GET["type"], $val1, $val2);
// Total des interventions r??solues
$values['solved'] = Stat::constructEntryValues($_GET['itemtype'], "inter_solved", $_POST["date1"],
                                               $_POST["date2"], $_GET["type"], $val1, $val2);
// Total des interventions closes
$values['closed'] = Stat::constructEntryValues($_GET['itemtype'], "inter_closed", $_POST["date1"],
                                               $_POST["date2"], $_GET["type"], $val1, $val2);
// Total des interventions closes
$values['late']   = Stat::constructEntryValues($_GET['itemtype'], "inter_solved_late",
                                               $_POST["date1"], $_POST["date2"], $_GET["type"],
                                               $val1, $val2);


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
                                                   $_POST["date1"], $_POST["date2"],
                                                   $_GET["type"], $val1, $val2);
// Pass to hour values
foreach ($values['avgsolved'] as $key => &$val) {
   $val = round($val / HOUR_TIMESTAMP, 2);
}
//Temps moyen de cloture d'intervention
$values['avgclosed'] = Stat::constructEntryValues($_GET['itemtype'], "inter_avgclosedtime",
                                                   $_POST["date1"], $_POST["date2"],
                                                   $_GET["type"], $val1, $val2);
// Pass to hour values
foreach ($values['avgclosed'] as $key => &$val) {
   $val = round($val / HOUR_TIMESTAMP, 2);
}
//Temps moyen d'intervention reel
$values['avgactiontime'] = Stat::constructEntryValues($_GET['itemtype'], "inter_avgactiontime",
                                                       $_POST["date1"], $_POST["date2"],
                                                       $_GET["type"], $val1, $val2);
// Pass to hour values
foreach ($values['avgactiontime'] as $key => &$val) {
   $val = round($val / HOUR_TIMESTAMP, 2);
}

$series = [
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
];

if ($_GET['itemtype'] == 'Ticket') {
   //Temps moyen de prise en compte de l'intervention
   $values['avgtaketime'] = Stat::constructEntryValues($_GET['itemtype'], "inter_avgtakeaccount",
                                                        $_POST["date1"], $_POST["date2"],
                                                        $_GET["type"], $val1, $val2);
   // Pass to hour values
   foreach ($values['avgtaketime'] as $key => &$val) {
      $val = round($val / HOUR_TIMESTAMP, 2);
   }

   $series[] = [
      'name' => __('Take into account'),
      'data' => $values['avgtaketime']
   ];
}

$stat->displayLineGraph(
   __('Average time') . " - " .  _n('Hour', 'Hours', 2),
   array_keys($values['avgsolved']),
   $series
);

if ($_GET['itemtype'] == 'Ticket') {
   $values = [];
   ///////// Satisfaction
   $values['opensatisfaction']   = Stat::constructEntryValues($_GET['itemtype'],
                                                              "inter_opensatisfaction",
                                                              $_POST["date1"], $_POST["date2"],
                                                              $_GET["type"], $val1, $val2);

   $values['answersatisfaction'] = Stat::constructEntryValues($_GET['itemtype'],
                                                              "inter_answersatisfaction",
                                                              $_POST["date1"], $_POST["date2"],
                                                              $_GET["type"], $val1, $val2);

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
                                                           $_POST["date1"], $_POST["date2"],
                                                           $_GET["type"], $val1, $val2);

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
