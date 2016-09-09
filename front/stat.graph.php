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
    && (strcmp($_POST["date2"],$_POST["date1"]) < 0)) {

   $tmp            = $_POST["date1"];
   $_POST["date1"] = $_POST["date2"];
   $_POST["date2"] = $tmp;
}

$cleantarget = preg_replace("/[&]date[12]=[0-9-]*/","",$_SERVER['QUERY_STRING']);
$cleantarget = preg_replace("/[&]*id=([0-9]+[&]{0,1})/","",$cleantarget);
$cleantarget = preg_replace("/&/","&amp;",$cleantarget);

$next    = 0;
$prev    = 0;
$title   = "";
$cond    = '';
$parent  = 0;

$showuserlink = 0;
if (Session::haveRight('user', READ)) {
   $showuserlink = 1;
}

switch($_GET["type"]) {
   case "technicien" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Technician'),
                         $item->getAssignName($_GET["id"], 'User', $showuserlink));
      break;

   case "technicien_followup" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Technician'),
                         $item->getAssignName($_GET["id"], 'User', $showuserlink));
      break;

   case "suppliers_id_assign" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Supplier'),
                         $item->getAssignName($_GET["id"], 'Supplier', $showuserlink));
      break;

   case "user" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('User'), getUserName($_GET["id"], $showuserlink));
      break;

   case "users_id_recipient" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('User'), getUserName($_GET["id"], $showuserlink));
      break;

   case "itilcategories_tree" :
      $parent = (isset($_GET['champ']) ? $_GET['champ'] : 0);
      $cond   = "(`id` = '$parent' OR `itilcategories_id` = '$parent')";
      // nobreak;

   case "itilcategories_id" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"],
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
      $values  = Stat::getItems($_GET['itemtype'], $_GET['date1'], $_GET['date2'], $_GET['type'],
                                $parent);
      $title   = sprintf(__('%1$s: %2$s'), __('Location'),
                         Dropdown::getDropdownName('glpi_locations', $_GET['id']));
      break;

   case "type" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
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
      $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"],
                                $parent);
      $title   = sprintf(__('%1$s: %2$s'), __('Group'),
                         Dropdown::getDropdownName("glpi_groups", $_GET["id"]));
      break;

   case "groups_id_assign" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Group'),
                         Dropdown::getDropdownName("glpi_groups", $_GET["id"]));
      break;

   case "priority" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Priority'), $item->getPriorityName($_GET["id"]));
      break;

   case "urgency" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Urgency'), $item->getUrgencyName($_GET["id"]));
      break;

   case "impact" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Impact'), $item->getImpactName($_GET["id"]));
      break;

   case "usertitles_id" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), _x('person','Title'),
                         Dropdown::getDropdownName("glpi_usertitles", $_GET["id"]));
      break;

   case "solutiontypes_id" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Solution type'),
                         Dropdown::getDropdownName("glpi_solutiontypes", $_GET["id"]));
      break;

   case "usercategories_id" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Category'),
                         Dropdown::getDropdownName("glpi_usercategories", $_GET["id"]));
      break;

   case "requesttypes_id" :
      $val1    = $_GET["id"];
      $val2    = "";
      $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
      $title   = sprintf(__('%1$s: %2$s'), __('Request source'),
                         Dropdown::getDropdownName("glpi_requesttypes", $_GET["id"]));
      break;

   case "device" :
      $val1 = $_GET["id"];
      $val2 = $_GET["champ"];
      if ($item = getItemForItemtype($_GET["champ"])) {
         $device_table = $item->getTable();
         $values       = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"],
                                        $_GET["champ"]);

         $query  = "SELECT `designation`
                    FROM `".$device_table."`
                    WHERE `id` = '".$_GET['id']."'";
         $result = $DB->query($query);

         $title  = sprintf(__('%1$s: %2$s'),
                           $item->getTypeName(), $DB->result($result, 0, "designation"));
      }
      break;

   case "comp_champ" :
      $val1  = $_GET["id"];
      $val2  = $_GET["champ"];
      if ($item = getItemForItemtype($_GET["champ"])) {
         $table  = $item->getTable();
         $values = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"],
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

$target = preg_replace("/&/","&amp;",$_SERVER["REQUEST_URI"]);

echo "<form method='post' name='form' action='$target'><div class='center'>";
echo "<table class='tab_cadre'>";
echo "<tr class='tab_bg_2'><td class='right'>".__('Start date')."</td><td>";
Html::showDateField("date1", array('value' => $_POST["date1"]));
echo "</td><td rowspan='2' class='center'>";
echo "<input type='hidden' name='itemtype' value=\"".$_GET['itemtype']."\">";
echo "<input type='submit' class='submit' value=\"".__s('Display report')."\"></td></tr>";

echo "<tr class='tab_bg_2'><td class='right'>".__('End date')."</td><td>";
Html::showDateField("date2", array('value' => $_POST["date2"]));
echo "</td></tr>";
echo "</table></div>";



$show_all = false;
if (!isset($_POST['graph']) || (count($_POST['graph']) == 0)) {
   $show_all = true;
}


///////// Stats nombre intervention
// Total des interventions
$values['total']  = Stat::constructEntryValues($_GET['itemtype'], "inter_total", $_GET["date1"],
                                               $_GET["date2"], $_GET["type"], $val1, $val2);
// Total des interventions résolues
$values['solved'] = Stat::constructEntryValues($_GET['itemtype'], "inter_solved", $_GET["date1"],
                                               $_GET["date2"], $_GET["type"], $val1, $val2);
// Total des interventions closes
$values['closed'] = Stat::constructEntryValues($_GET['itemtype'], "inter_closed", $_GET["date1"],
                                               $_GET["date2"], $_GET["type"], $val1, $val2);
// Total des interventions closes
$values['late']   = Stat::constructEntryValues($_GET['itemtype'], "inter_solved_late",
                                               $_GET["date1"], $_GET["date2"], $_GET["type"],
                                               $val1, $val2);

$available = array('total'  => _nx('ticket','Opened','Opened',2),
                   'solved' => _nx('ticket','Solved', 'Solved', 2),
                   'late'   => __('Late'),
                   'closed' => __('Closed'),);
echo "<div class='center'>";

foreach ($available as $key => $name) {
   echo "<input type='checkbox' onchange='submit()' name='graph[$key]' ".
          ($show_all||isset($_POST['graph'][$key])?"checked":"")."> ".$name."&nbsp;";
}
echo "</div>";

$toprint = array();
foreach ($available as $key => $name) {
   if ($show_all || isset($_POST['graph'][$key])) {
      $toprint[$name] = $values[$key];
   }
}

Stat::showGraph($toprint, array('title'     => _x('quantity', 'Number of tickets'),
                                'showtotal' => 1,
                                'unit'      => __('Tickets')));

//Temps moyen de resolution d'intervention
$values2['avgsolved'] = Stat::constructEntryValues($_GET['itemtype'], "inter_avgsolvedtime",
                                                   $_GET["date1"], $_GET["date2"],
                                                   $_GET["type"], $val1, $val2);
// Pass to hour values
foreach ($values2['avgsolved'] as $key => $val) {
   $values2['avgsolved'][$key] /= HOUR_TIMESTAMP;
}
//Temps moyen de cloture d'intervention
$values2['avgclosed'] = Stat::constructEntryValues($_GET['itemtype'], "inter_avgclosedtime",
                                                   $_GET["date1"], $_GET["date2"],
                                                   $_GET["type"], $val1, $val2);
// Pass to hour values
foreach ($values2['avgclosed'] as $key => $val) {
   $values2['avgclosed'][$key] /= HOUR_TIMESTAMP;
}
//Temps moyen d'intervention reel
$values2['avgactiontime'] = Stat::constructEntryValues($_GET['itemtype'], "inter_avgactiontime",
                                                       $_GET["date1"], $_GET["date2"],
                                                       $_GET["type"], $val1, $val2);
// Pass to hour values
foreach ($values2['avgactiontime'] as $key => $val) {
   $values2['avgactiontime'][$key] /= HOUR_TIMESTAMP;
}


$available = array('avgclosed'     => __('Closure'),
                   'avgsolved'     => __('Resolution'),
                   'avgactiontime' => __('Real duration'));


if ($_GET['itemtype'] == 'Ticket') {
   $available['avgtaketime'] = __('Take into account');
   //Temps moyen de prise en compte de l'intervention
   $values2['avgtaketime'] = Stat::constructEntryValues($_GET['itemtype'], "inter_avgtakeaccount",
                                                        $_GET["date1"], $_GET["date2"],
                                                        $_GET["type"], $val1, $val2);
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
                                                              $_GET["date1"], $_GET["date2"],
                                                              $_GET["type"], $val1, $val2);

   $values['answersatisfaction'] = Stat::constructEntryValues($_GET['itemtype'],
                                                              "inter_answersatisfaction",
                                                              $_GET["date1"], $_GET["date2"],
                                                              $_GET["type"], $val1, $val2);


   $available = array('opensatisfaction'   => _nx('survey','Opened','Opened', 2),
                     'answersatisfaction'  => _nx('survey','Answered','Answered',2));
   echo "<div class='center'>";

   foreach ($available as $key => $name) {
      echo "<input type='checkbox' onchange='submit()' name='graph[$key]' ".
            ($show_all||isset($_POST['graph'][$key])?"checked":"")."> ".$name."&nbsp;";
   }
   echo "</div>";

   $toprint = array();
   foreach ($available as $key => $name) {
      if ($show_all || isset($_POST['graph'][$key])) {
         $toprint[$name] = $values[$key];
      }
   }

   Stat::showGraph($toprint, array('title'   => __('Satisfaction survey'),
                                 'showtotal' => 1,
                                 'unit'      => __('Tickets')));

   $values['avgsatisfaction'] = Stat::constructEntryValues($_GET['itemtype'],
                                                           "inter_avgsatisfaction",
                                                           $_GET["date1"], $_GET["date2"],
                                                           $_GET["type"], $val1, $val2);

   $available = array('avgsatisfaction' => __('Satisfaction'));
   echo "<div class='center'>";

   foreach ($available as $key => $name) {
      echo "<input type='checkbox' onchange='submit()' name='graph[$key]' ".
            ($show_all||isset($_POST['graph'][$key])?"checked":"")."> ".$name."&nbsp;";
   }
   echo "</div>";

   $toprint = array();
   foreach ($available as $key => $name) {
      if ($show_all || isset($_POST['graph'][$key])) {
         $toprint[$name] = $values[$key];
      }
   }

   Stat::showGraph($toprint, array('title' => __('Satisfaction')));

}
// form using GET method : CRSF not needed
Html::closeForm();
Html::footer();
?>