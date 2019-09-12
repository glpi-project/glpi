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

namespace tests\units;

class Impact extends \GLPITestCase {

   private function clearImpactItems() {
      global $DB;

      $DB->delete(\ImpactItem::getTable(), [true]);
      $DB->delete(\ImpactRelation::getTable(), [true]);
      $DB->delete(\ImpactCompound::getTable(), [true]);
   }

   public function testGetTabNameForItem_notCommonDBTM() {
      $this->clearImpactItems();
      $impact = new \Impact();

      $this->exception(function() use ($impact) {
         $notCommonDBTM = new \Impact();
         $impact->getTabNameForItem($notCommonDBTM);
      })->isInstanceOf(\InvalidArgumentException::class);
   }

   public function testGetTabNameForItem_notEnabledOrITIL() {
      $this->clearImpactItems();
      $impact = new \Impact();

      $this->exception(function() use ($impact) {
         $notEnabledOrITIL = new \ImpactCompound();
         $impact->getTabNameForItem($notEnabledOrITIL);
      })->isInstanceOf(\InvalidArgumentException::class);
   }

   public function testGetTabNameForItem_tabCountDisabled() {
      $this->clearImpactItems();
      $oldSession = $_SESSION['glpishow_count_on_tabs'];
      $_SESSION['glpishow_count_on_tabs'] = false;

      $impact = new \Impact();
      $computer = new \Computer();

      $this->string($impact->getTabNameForItem($computer))
         ->isEqualTo("Impacts ");

      $_SESSION['glpishow_count_on_tabs'] = $oldSession;
   }

   public function testGetTabNameForItem_enabledAsset() {
      $this->clearImpactItems();
      $oldSession = $_SESSION['glpishow_count_on_tabs'];
      $_SESSION['glpishow_count_on_tabs'] = true;

      $impact = new \Impact();
      $impactRelationManager = new \ImpactRelation();

      // Get computer
      $computer = getItemByTypeName('Computer', '_test_pc02');

      // Create an impact graph
      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => "1",
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => "2",
      ]);
      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => "2",
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => "3",
      ]);

      $this->string($impact->getTabNameForItem($computer))
         ->isEqualTo("Impacts  <sup class='tab_nb'>2</sup>");

      // Clean up
      $_SESSION['glpishow_count_on_tabs'] = $oldSession;
   }

   public function testGetTabNameForItem_ITILObject() {
      $this->clearImpactItems();
      $oldSession = $_SESSION['glpishow_count_on_tabs'];
      $_SESSION['glpishow_count_on_tabs'] = true;

      $impact = new \Impact();
      $impactRelationManager = new \ImpactRelation();
      $ticketManager = new \Ticket();
      $itemTicketManger = new \Item_Ticket();

      // Create an impact graph
      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => "1",
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => "2",
      ]);
      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => "2",
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => "3",
      ]);

      // Create a ticket and link it to the computer
      $ticketId = $ticketManager->add(['name' => "test", 'content' => "test"]);
      $itemTicketManger->add([
         'itemtype'   => "Computer",
         'items_id'   => 2,
         'tickets_id' => $ticketId,
      ]);

      // Get the actual ticket
      $ticket = new \Ticket;
      $ticket->getFromDB($ticketId);

      $this->string($impact->getTabNameForItem($ticket))
         ->isEqualTo("Impacts ");

      // Clean up
      $_SESSION['glpishow_count_on_tabs'] = $oldSession;
   }

   public function testBuildGraph_empty() {
      $this->clearImpactItems();
      $computer = getItemByTypeName('Computer', '_test_pc01');
      $graph = \Impact::buildGraph($computer);

      $this->array($graph)->hasKeys(["nodes", "edges"]);

      // Nodes should contain only _test_pc01
      $this->array($graph["nodes"])->hasSize(1);
      $this->string($graph["nodes"]["Computer::1"]['label'])->isEqualTo("_test_pc01");

      // Edges should be empty
      $this->array($graph["edges"])->hasSize(0);
   }

   public function testBuildGraph_complex() {
      $this->clearImpactItems();
      $impactRelationManager = new \ImpactRelation();
      $impactItemManager = new \ImpactItem();
      $impactCompoundManager = new \ImpactCompound();

      // Set compounds
      $compound01Id = $impactCompoundManager->add([
         'name'  => "_test_compound01",
         'color' => "#000011",
      ]);
      $compound02Id = $impactCompoundManager->add([
         'name'  => "_test_compound02",
         'color' => "#110000",
      ]);

      // Set impact items
      $impactItemManager->add([
         'itemtype'  => "Computer",
         'items_id'  => 1,
         'parent_id' => 0,
      ]);
      $impactItemManager->add([
         'itemtype'  => "Computer",
         'items_id'  => 2,
         'parent_id' => $compound01Id,
      ]);
      $impactItemManager->add([
         'itemtype'  => "Computer",
         'items_id'  => 3,
         'parent_id' => $compound01Id,
      ]);
      $impactItemManager->add([
         'itemtype'  => "Computer",
         'items_id'  => 4,
         'parent_id' => $compound02Id,
      ]);
      $impactItemManager->add([
         'itemtype'  => "Computer",
         'items_id'  => 5,
         'parent_id' => $compound02Id,
      ]);
      $impactItemManager->add([
         'itemtype'  => "Computer",
         'items_id'  => 6,
         'parent_id' => $compound02Id,
      ]);

      // Set relations
      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => "1",
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => "2",
      ]);
      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => "2",
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => "3",
      ]);
      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => "3",
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => "4",
      ]);
      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => "4",
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => "5",
      ]);
      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => "2",
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => "6",
      ]);
      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => "6",
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => "2",
      ]);

      // Build graph from pc02
      $computer = getItemByTypeName('Computer', '_test_pc02');
      $graph = \Impact::buildGraph($computer);
      // var_dump($graph);
      $this->array($graph)->hasKeys(["nodes", "edges"]);

      // Nodes should contain 8 elements (6 nodes + 2 compounds)
      $this->array($graph["nodes"])->hasSize(8);
      $nodes = array_filter($graph["nodes"], function($elem) {
         return !isset($elem['color']);
      });
      $this->array($nodes)->hasSize(6);
      $compounds = array_filter($graph["nodes"], function($elem) {
         return isset($elem['color']);
      });
      $this->array($compounds)->hasSize(2);

      // Edges should contain 6 elements (3 forward, 1 backward, 2 both)
      $this->array($graph["edges"])->hasSize(6);
      $backward = array_filter($graph["edges"], function($elem) {
         return $elem["flag"] == \Impact::DIRECTION_BACKWARD;
      });
      $this->array($backward)->hasSize(1);
      $forward = array_filter($graph["edges"], function($elem) {
         return $elem["flag"] == \Impact::DIRECTION_FORWARD;
      });
      $this->array($forward)->hasSize(3);
      $both = array_filter($graph["edges"], function($elem) {
         return $elem["flag"] == (\Impact::DIRECTION_FORWARD | \Impact::DIRECTION_BACKWARD);
      });
      $this->array($both)->hasSize(2);
   }
}