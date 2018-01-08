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

Html::header(__('Statistics'), '', "helpdesk", "stat");

Session::checkRight("statistic", READ);

if (!$item = getItemForItemtype($_GET['itemtype'])) {
   exit;
}

if (empty($_GET["type"])) {
   $_GET["type"] = "user";
}

if (empty($_GET["showgraph"])) {
   $_GET["showgraph"] = 0;
}

if (empty($_GET["value2"])) {
   $_GET["value2"] = 0;
}

if (empty($_GET["date1"]) && empty($_GET["date2"])) {
   $year              = date("Y")-1;
   $_GET["date1"] = date("Y-m-d", mktime(1, 0, 0, date("m"), date("d"), $year));
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

$stat = new Stat();
Stat::title();

$requester = ['user'               => ['title' => __('Requester')],
                   'users_id_recipient' => ['title' => __('Writer')],
                   'group'              => ['title' => __('Group')],
                   'group_tree'         => ['title' => __('Group tree')],
                   'usertitles_id'      => ['title' => _x('person', 'Title')],
                   'usercategories_id'  => ['title' => __('Category')]];

$caract    = ['itilcategories_id'   => ['title' => __('Category')],
                   'itilcategories_tree' => ['title' => __('Category tree')],
                   'urgency'             => ['title' => __('Urgency')],
                   'impact'              => ['title' => __('Impact')],
                   'priority'            => ['title' => __('Priority')],
                   'solutiontypes_id'    => ['title' => __('Solution type')]];

if ($_GET['itemtype'] == 'Ticket') {
   $caract['type']            = ['title' => __('Type')];
   $caract['requesttypes_id'] = ['title' => __('Request source')];
   $caract['locations_id']    = ['title' => __('Location')];
   $caract['locations_tree']  = ['title' => __('Location tree')];
}


$items = [__('Requester')       => $requester,
               __('Characteristics') => $caract,
               __('Assigned to')     => ['technicien'
                                                   => ['title' => __('Technician as assigned')],
                                              'technicien_followup'
                                                   => ['title' => __('Technician in tasks')],
                                              'groups_id_assign'
                                                   => ['title' => __('Group')],
                                              'groups_tree_assign'
                                                   => ['title' => __('Group tree')],
                                              'suppliers_id_assign'
                                                   => ['title' => __('Supplier')]]];

$values = [];
foreach ($items as $label => $tab) {
   foreach ($tab as $key => $val) {
      $values[$label][$key] = $val['title'];
   }
}

echo "<div class='center'><form method='get' name='form' action='stat.tracking.php'>";
// Keep it first param
echo "<input type='hidden' name='itemtype' value=\"". $_GET["itemtype"] ."\">";

echo "<table class='tab_cadre_fixe'>";
echo "<tr class='tab_bg_2'><td rowspan='2' class='center' width='30%'>";
Dropdown::showFromArray('type', $values, ['value' => $_GET['type']]);
echo "</td>";
echo "<td class='right'>".__('Start date')."</td><td>";
Html::showDateField("date1", ['value' => $_GET["date1"]]);
echo "</td>";
echo "<td class='right'>".__('Show graphics')."</td>";
echo "<td rowspan='2' class='center'>";
echo "<input type='submit' class='submit' name='submit' value=\"".__s('Display report')."\"></td>".
     "</tr>";

echo "<tr class='tab_bg_2'><td class='right'>".__('End date')."</td><td>";
Html::showDateField("date2", ['value' => $_GET["date2"]]);
echo "</td><td class='center'>";
echo "<input type='hidden' name='value2' value='".$_GET["value2"]."'>";
Dropdown::showYesNo('showgraph', $_GET['showgraph']);
echo "</td></tr>";
echo "</table>";
// form using GET method : CRSF not needed
echo "</form>";
echo "</div>";

$val    = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"],
                         $_GET["value2"]);
$params = ['type'   => $_GET["type"],
                'date1'  => $_GET["date1"],
                'date2'  => $_GET["date2"],
                'value2' => $_GET["value2"],
                'start'  => $_GET["start"]];

Html::printPager($_GET['start'], count($val), $CFG_GLPI['root_doc'].'/front/stat.tracking.php',
                 "date1=".$_GET["date1"]."&amp;date2=".$_GET["date2"]."&amp;type=".$_GET["type"].
                    "&amp;showgraph=".$_GET["showgraph"]."&amp;itemtype=".$_GET["itemtype"].
                    "&amp;value2=".$_GET['value2'],
                 'Stat', $params);

if (!$_GET['showgraph']) {
   Stat::showTable($_GET["itemtype"], $_GET["type"], $_GET["date1"], $_GET["date2"], $_GET['start'],
                   $val, $_GET['value2']);

} else {
   $data = Stat::getData($_GET["itemtype"], $_GET["type"], $_GET["date1"], $_GET["date2"],
                          $_GET['start'], $val, $_GET['value2']);

   if (isset($data['opened']) && is_array($data['opened'])) {
      $count = 0;
      $labels = [];
      $series = [];
      foreach ($data['opened'] as $key => $val) {
         $newkey             = Toolbox::unclean_cross_side_scripting_deep(Html::clean($key));
         if ($val > 0) {
            $labels[] = $newkey;
            $series[] = ['name' => $newkey, 'data' => $val];
            $count += $val;
         }
      }

      if (count($series)) {
         $stat->displayPieGraph(
            sprintf(
               __('Opened %1$s (%2$s)'),
               $item->getTypeName(Session::getPluralNumber()),
               $count
            ),
            $labels,
            $series
         );
      }
   }

   if (isset($data['solved']) && is_array($data['solved'])) {
      $count = 0;
      $labels = [];
      $series = [];
      foreach ($data['solved'] as $key => $val) {
         $newkey             = Toolbox::unclean_cross_side_scripting_deep(Html::clean($key));
         if ($val > 0) {
            $labels[] = $newkey;
            $series[] = ['name' => $newkey, 'data' => $val];
            $count += $val;
         }
      }

      if (count($series)) {
         $stat->displayPieGraph(
            sprintf(
               __('Solved %1$s (%2$s)'),
               $item->getTypeName(Session::getPluralNumber()),
               $count
            ),
            $labels,
            $series
         );
      }
   }

   if (isset($data['late']) && is_array($data['late'])) {
      $count = 0;
      $labels = [];
      $series = [];
      foreach ($data['late'] as $key => $val) {
         $newkey             = Toolbox::unclean_cross_side_scripting_deep(Html::clean($key));
         if ($val > 0) {
            $labels[] = $newkey;
            $series[] = ['name' => $newkey, 'data' => $val];
            $count += $val;
         }
      }

      if (count($series)) {
         $stat->displayPieGraph(
            sprintf(
               __('Solved late %1$s (%2$s)'),
               $item->getTypeName(Session::getPluralNumber()),
               $count
            ),
            $labels,
               $series
         );
      }
   }


   if (isset($data['closed']) && is_array($data['closed'])) {
      $count = 0;
      $labels = [];
      $series = [];
      foreach ($data['closed'] as $key => $val) {
         $newkey             = Toolbox::unclean_cross_side_scripting_deep(Html::clean($key));
         if ($val > 0) {
            $labels[] = $newkey;
            $series[] = ['name' => $newkey, 'data' => $val];
            $count += $val;
         }
      }

      if (count($series)) {
         $stat->displayPieGraph(
            sprintf(
               __('Closed %1$s (%2$s)'),
               $item->getTypeName(Session::getPluralNumber()),
               $count
            ),
            $labels,
               $series
         );
      }
   }

   if ($_GET['itemtype'] == 'Ticket') {
      $count = 0;
      $labels = [];
      $series = [];
      if (isset($data['opensatisfaction']) && is_array($data['opensatisfaction'])) {
         foreach ($data['opensatisfaction'] as $key => $val) {
            $newkey             = Toolbox::unclean_cross_side_scripting_deep(Html::clean($key));
            if ($val > 0) {
               $labels[] = $newkey;
               $series[] = ['name' => $newkey, 'data' => $val];
               $count += $val;
            }
         }

         if (count($series)) {
            $stat->displayPieGraph(
               sprintf(
                  __('%1$s satisfaction survey (%2$s)'),
                  $item->getTypeName(Session::getPluralNumber()),
                  $count
               ),
               $labels,
               $series
            );
         }
      }
   }

}

Html::footer();
