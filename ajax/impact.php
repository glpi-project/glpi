<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2019 Teclib' and contributors.
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

const DELTA_ACTION_ADD    = 1;
const DELTA_ACTION_UPDATE = 2;
const DELTA_ACTION_DELETE = 3;

$AJAX_INCLUDE = 1;
include ('../inc/includes.php');

// Send UTF8 Headers
header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

switch ($_SERVER['REQUEST_METHOD']) {
   // GET request: build the impact graph for a given asset
   case 'GET':
      $itemtype =  $_GET["itemtype"]  ?? "";
      $items_id   =  $_GET["items_id"]    ?? "";

      // Check required params
      if (empty($itemtype) || empty($items_id)) {
         Toolbox::throwBadRequest("Missing itemtype or items_id");
      }

      // Check that the the target asset exist
      if (!Impact::assetExist($itemtype, $items_id)) {
         Toolbox::throwBadRequest("Object[class=$itemtype, id=$items_id] doesn't exist");
      }

      // Prepare graph
      $item = new $itemtype;
      $item->getFromDB($items_id);
      $graph = Impact::makeDataForCytoscape(Impact::buildGraph($item));
      $params = Impact::prepareParams($item);

      // Output graph
      header('Content-Type: application/json');
      echo json_encode([
         'graph'  => $graph,
         'params' => $params
      ]);

      break;

   // Post request: update the store impact dependencies, compounds or items
   case 'POST':
      // Check required params
      if (!isset($_POST['impacts'])) {
         Toolbox::throwBadRequest("Missing 'impacts' payload");
      }

      // Decode data (should be json)
      $data = Toolbox::jsonDecode($_POST['impacts'], true);
      if (!is_array($data)) {
         Toolbox::throwBadRequest("Payload should be an array");
      }

      // Save impact relation delta
      $em = new ImpactRelation();
      foreach ($data['edges'] as $impact) {
         // Extract action
         $action = $impact['action'];
         unset($impact['action']);

         switch ($action) {
            case DELTA_ACTION_ADD:
               $em->add($impact);
               break;

            case DELTA_ACTION_DELETE:
               $impact['id'] = ImpactRelation::getIDFromInput($impact);
               $em->delete($impact);
               break;

            default:
               break;
         }
      }

      // Save impact compound delta
      $em = new ImpactCompound();
      foreach ($data['compounds'] as $id => $compound) {
         // Extract action
         $action = $compound['action'];
         unset($compound['action']);

         switch ($action) {
            case DELTA_ACTION_ADD:
               $newCompoundID = $em->add($compound);

               // Update id reference in impactitem
               // This is needed because some nodes might have this compound
               // temporary id as their parent id
               foreach ($data['items'] as $nodeID => $node) {
                  if ($node['parent_id'] === $id) {
                     $data['items'][$nodeID]['parent_id'] = $newCompoundID;
                  }
               }
               break;

            case DELTA_ACTION_UPDATE:
               $compound['id'] = $id;
               $em->update($compound);
               break;

            case DELTA_ACTION_DELETE:
               $em->delete(['id' => $id]);
               break;

            default:
               break;
         }
      }

      // Save impact item delta
      $em = new ImpactItem();
      foreach ($data['items'] as $id => $impactItem) {
         // Extract action
         $action = $impactItem['action'];
         unset($impactItem['action']);

         switch ($action) {
            case DELTA_ACTION_UPDATE:
               $impactItem['id'] = $id;
               $em->update($impactItem);
               break;
         }
      }

      header('Content-Type: application/javascript');
      http_response_code(200);
      break;
}