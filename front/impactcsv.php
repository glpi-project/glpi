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

$itemtype = $_GET['itemtype'] ?? '';
$items_id = $_GET['items_id'] ?? '';

// Check for mandatory params
if (empty($itemtype) || empty($items_id)) {
   http_response_code(400);
   die();
}

// Check right
Session::checkRight($itemtype::$rightname, READ);

// Load item
$item = new $itemtype();
$item->getFromDB($items_id);

// Load graph and impactitem
$graph = Impact::buildGraph($item);
$impact_item = ImpactItem::findForItem($item);
if (!$impact_item) {
   throw new \InvalidArgumentException("No ImpactItem found for $itemtype $items_id");
}

// Load list data
$data = [];
$directions = [Impact::DIRECTION_FORWARD, Impact::DIRECTION_BACKWARD];
foreach ($directions as $direction) {
   $data[$direction] = Impact::buildListData($graph, $direction, $item, $impact_item);
}

// Output csv data
$filename = $item->fields['name'];
header("Content-Type: application/csv");
header("Content-Disposition: attachment; filename='$filename.csv';");
$output = fopen('php://output', 'w');
if ($output === false) {
   throw new \RuntimeException("Can't open php://output");
}

// Title of the cols in the first line
fputcsv($output, [
   __("Relation"),
   __("Itemtype"),
   __("Id"),
   __("Name"),
]);

// Flatten the hiarchical $data and insert it line by line
foreach ($data as $direction => $impact_data) {
   if ($direction == Impact::DIRECTION_FORWARD) {
      $direction_label = __("Impact");
   } else {
      $direction_label = __("Impacted by");
   }

   foreach ($impact_data as $data_type => $data_elements) {
      foreach ($data_elements as $data_element) {
         fputcsv($output, [
            $direction_label,
            $data_type,
            $data_element['stored']->fields['id'],
            $data_element['stored']->fields['name'],
         ]);
      }
   }
}
