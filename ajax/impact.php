<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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
      $action = $_GET["action"]  ?? "";

      switch ($action) {
         case "search":
            $itemtype = $_GET["itemtype"] ?? "";
            $used     = $_GET["used"]     ?? "[]";
            $filter   = $_GET["filter"]   ?? "";
            $page     = $_GET["page"]     ?? 0;

            // Check required params
            if (empty($itemtype)) {
               Toolbox::throwError(400, "Missing itemtype");
            }

            // Execute search
            $assets = Impact::searchAsset($itemtype, json_decode($used), $filter, $page);
            header('Content-Type: application/json');
            echo json_encode($assets);
            break;

         case 'load':
            $itemtype = $_GET["itemtype"]  ?? "";
            $items_id = $_GET["items_id"]  ?? "";
            $view     = $_GET["view"]      ?? "graph";

            // Check required params
            if (empty($itemtype) || empty($items_id)) {
               Toolbox::throwError(400, "Missing itemtype or items_id");
            }

            // Check that the the target asset exist
            if (!Impact::assetExist($itemtype, $items_id)) {
               Toolbox::throwError(400, "Object[class=$itemtype, id=$items_id] doesn't exist");
            }

            // Prepare graph
            $item = new $itemtype;
            $item->getFromDB($items_id);
            $graph = Impact::buildGraph($item);
            $params = Impact::prepareParams($item);
            $readonly = $item->can($items_id, UPDATE);

            if ($view == "graph") {
               // Output graph as json
               header('Content-Type: application/json');
               echo json_encode([
                  'graph'  => Impact::makeDataForCytoscape($graph),
                  'params' => $params,
                  'readonly' => $readonly,
               ]);
            } else if ($view == "list") {
               // Output list as HTML
               header('Content-Type: text/html');
               Impact::displayListView($item, $graph);
            }
            break;

         default:
            Toolbox::throwError(400, "Missing or invalid 'action' parameter");
            break;
      }
      break;

   // Post request: update the store impact dependencies, compounds or items
   case 'POST':
      // Check required params
      if (!isset($_POST['impacts'])) {
         Toolbox::throwError(400, "Missing 'impacts' payload");
      }

      // Decode data (should be json)
      $data = Toolbox::jsonDecode($_UPOST['impacts'], true);
      if (!is_array($data)) {
         Toolbox::throwError(400, "Payload should be an array");
      }
      $data = Toolbox::addslashes_deep($data);

      $readonly = true;

      // Handle context for the starting node
      $context_em = new \ImpactContext();
      $context_data = $data['context'];

      // Get id and type from node_id (e.g. Computer::4 -> [Computer, 4])
      $start_node_details = explode(Impact::NODE_ID_DELIMITER, $context_data['node_id']);

      // Get impact_item for this node
      $item = new $start_node_details[0];
      $item->getFromDB($start_node_details[1]);
      $impact_item = \ImpactItem::findForItem($item);
      $start_node_impact_item_id = $impact_item->fields['id'];
      $readonly = !$item->can($item->fields['id'], UPDATE);

      // Stop here if readonly graph
      if ($readonly) {
         Toolbox::throwError(403, "Missing rights");
      }

      $context_id = 0;
      if ($impact_item->fields["impactcontexts_id"] == 0
         || $impact_item->fields["is_slave"] == 1) {
         // There is no context OR we are slave to another context -> let's
         // create a new one
         $context_id = $context_em->add($context_data);

         // Set the context_id to be updated
         $data['items'][$start_node_impact_item_id]['impactcontexts_id'] = $context_id;
         $data['items'][$start_node_impact_item_id]['is_slave'] = 0;
      } else {
         // Update existing context
         $context_id = $impact_item->fields["impactcontexts_id"];
         $context_em->getFromDB($context_id);
         $context_data['id'] = $context_id;
         $context_em->update($context_data);
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

               // If this is not the starting node, check for context update
               if ($id !== $start_node_impact_item_id) {
                  $em->getFromDB($id);

                  // If this node has no context -> make it a slave
                  if ($em->fields['impactcontexts_id'] == 0) {
                     $impactItem['impactcontexts_id'] = $context_id;
                     $impactItem['is_slave'] = 1;
                  }
               }

               $em->update($impactItem);
               break;
         }
      }

      header('Content-Type: application/javascript');
      http_response_code(200);
      break;
}