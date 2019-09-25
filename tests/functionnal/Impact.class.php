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

class Impact extends \DbTestCase {

   public function testGetTabNameForItem_notCommonDBTM() {
      $impact = new \Impact();

      $this->exception(function() use ($impact) {
         $notCommonDBTM = new \Impact();
         $impact->getTabNameForItem($notCommonDBTM);
      })->isInstanceOf(\InvalidArgumentException::class);
   }

   public function testGetTabNameForItem_notEnabledOrITIL() {
      $impact = new \Impact();

      $this->exception(function() use ($impact) {
         $notEnabledOrITIL = new \ImpactCompound();
         $impact->getTabNameForItem($notEnabledOrITIL);
      })->isInstanceOf(\InvalidArgumentException::class);
   }

   public function testGetTabNameForItem_tabCountDisabled() {
      $oldSession = $_SESSION['glpishow_count_on_tabs'];
      $_SESSION['glpishow_count_on_tabs'] = false;

      $impact = new \Impact();
      $computer = new \Computer();
      $tab_name = $impact->getTabNameForItem($computer);
      $_SESSION['glpishow_count_on_tabs'] = $oldSession;

      $this->string($tab_name)->isEqualTo("Impact analysis");
   }

   public function testGetTabNameForItem_enabledAsset() {
      $oldSession = $_SESSION['glpishow_count_on_tabs'];
      $_SESSION['glpishow_count_on_tabs'] = true;

      $impact = new \Impact();
      $impactRelationManager = new \ImpactRelation();

      // Get computers
      $computer1 = getItemByTypeName('Computer', '_test_pc01');
      $computer2 = getItemByTypeName('Computer', '_test_pc02');
      $computer3 = getItemByTypeName('Computer', '_test_pc03');

      // Create an impact graph
      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => $computer1->fields['id'],
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => $computer2->fields['id'],
      ]);
      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => $computer2->fields['id'],
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => $computer3->fields['id'],
      ]);
      $tab_name = $impact->getTabNameForItem($computer2);
      $_SESSION['glpishow_count_on_tabs'] = $oldSession;

      $this->string($tab_name)->isEqualTo("Impact analysis <sup class='tab_nb'>2</sup>");
   }

   public function testGetTabNameForItem_ITILObject() {
      $oldSession = $_SESSION['glpishow_count_on_tabs'];
      $_SESSION['glpishow_count_on_tabs'] = true;

      $impact = new \Impact();
      $impactRelationManager = new \ImpactRelation();
      $ticketManager = new \Ticket();
      $itemTicketManger = new \Item_Ticket();

      // Get computers
      $computer1 = getItemByTypeName('Computer', '_test_pc01');
      $computer2 = getItemByTypeName('Computer', '_test_pc02');
      $computer3 = getItemByTypeName('Computer', '_test_pc03');

      // Create an impact graph
      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => $computer1->fields['id'],
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => $computer2->fields['id'],
      ]);
      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => $computer2->fields['id'],
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => $computer3->fields['id'],
      ]);

      // Create a ticket and link it to the computer
      $ticketId = $ticketManager->add(['name' => "test", 'content' => "test"]);
      $itemTicketManger->add([
         'itemtype'   => "Computer",
         'items_id'   => $computer2->fields['id'],
         'tickets_id' => $ticketId,
      ]);

      // Get the actual ticket
      $ticket = new \Ticket;
      $ticket->getFromDB($ticketId);

      $tab_name = $impact->getTabNameForItem($ticket);
      $_SESSION['glpishow_count_on_tabs'] = $oldSession;

      $this->string($tab_name)->isEqualTo("Impact analysis");
   }

   public function testBuildGraph_empty() {
      $computer = getItemByTypeName('Computer', '_test_pc01');
      $graph = \Impact::buildGraph($computer);

      $this->array($graph)->hasKeys(["nodes", "edges"]);

      // Nodes should contain only _test_pc01
      $id = $computer->fields['id'];
      $this->array($graph["nodes"])->hasSize(1);
      $this->string($graph["nodes"]["Computer::$id"]['label'])->isEqualTo("_test_pc01");

      // Edges should be empty
      $this->array($graph["edges"])->hasSize(0);
   }

   public function testBuildGraph_complex() {
      $impactRelationManager = new \ImpactRelation();
      $impactItemManager = new \ImpactItem();
      $impactCompoundManager = new \ImpactCompound();

      $computer1 = getItemByTypeName('Computer', '_test_pc01');
      $computer2 = getItemByTypeName('Computer', '_test_pc02');
      $computer3 = getItemByTypeName('Computer', '_test_pc03');
      $computer4 = getItemByTypeName('Computer', '_test_pc11');
      $computer5 = getItemByTypeName('Computer', '_test_pc12');
      $computer6 = getItemByTypeName('Computer', '_test_pc13');

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
         'items_id'  => $computer1->fields['id'],
         'parent_id' => 0,
      ]);
      $impactItemManager->add([
         'itemtype'  => "Computer",
         'items_id'  => $computer2->fields['id'],
         'parent_id' => $compound01Id,
      ]);
      $impactItemManager->add([
         'itemtype'  => "Computer",
         'items_id'  => $computer3->fields['id'],
         'parent_id' => $compound01Id,
      ]);
      $impactItemManager->add([
         'itemtype'  => "Computer",
         'items_id'  => $computer4->fields['id'],
         'parent_id' => $compound02Id,
      ]);
      $impactItemManager->add([
         'itemtype'  => "Computer",
         'items_id'  => $computer5->fields['id'],
         'parent_id' => $compound02Id,
      ]);
      $impactItemManager->add([
         'itemtype'  => "Computer",
         'items_id'  => $computer6->fields['id'],
         'parent_id' => $compound02Id,
      ]);

      // Set relations
      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => $computer1->fields['id'],
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => $computer2->fields['id'],
      ]);
      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => $computer2->fields['id'],
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => $computer3->fields['id'],
         ]);
      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => $computer3->fields['id'],
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => $computer4->fields['id'],
      ]);
      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => $computer4->fields['id'],
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => $computer5->fields['id'],
      ]);
      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => $computer2->fields['id'],
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => $computer6->fields['id'],
      ]);
      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => $computer6->fields['id'],
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => $computer2->fields['id'],
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