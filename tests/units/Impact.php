<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

use DbTestCase;

class Impact extends DbTestCase {

   private $nodes = [
      ['id' => "A"],
      ['id' => "B"],
      ['id' => "C"],
      ['id' => "D"],
      ['id' => "E"],
      ['id' => "F"],
      ['id' => "G"],
   ];

   private $forward = [
      ['A', 'B'],
      ['B', 'E'],
      ['E', 'G'],
   ];

   private $backward = [
      ['F', 'C'],
   ];

   private $both = [
      ['A', 'D'],
      ['D', 'C'],
      ['C', 'A'],
   ];

   private $graph = [
      'nodes' => [],
      'edges' => []
   ];

   public function beforeTestMethod($method) {
      foreach ($this->nodes as $node) {
         $id = $node['id'];
         $this->graph['nodes'][$id] = $node;
      }

      foreach ($this->forward as $edge) {
         $this->graph['edges'][] = [
            'source' => $edge[0],
            'target' => $edge[1],
            'flag'   => \Impact::DIRECTION_FORWARD,
         ];
      }

      foreach ($this->backward as $edge) {
         $this->graph['edges'][] = [
            'source' => $edge[0],
            'target' => $edge[1],
            'flag'   => \Impact::DIRECTION_BACKWARD,
         ];
      }

      foreach ($this->both as $edge) {
         $this->graph['edges'][] = [
            'source' => $edge[0],
            'target' => $edge[1],
            'flag'   => \Impact::DIRECTION_FORWARD | \Impact::DIRECTION_BACKWARD,
         ];
      }

   }

   public function bfsProvider() {
      return [
         [
            'a'         => ['id' => "A"],
            'b'         => ['id' => "B"],
            'direction' => \Impact::DIRECTION_FORWARD,
            'result'    => [
               $this->graph['nodes']['A'],
               $this->graph['nodes']['B'],
            ],
         ],
         [
            'a'         => ['id' => "A"],
            'b'         => ['id' => "E"],
            'direction' => \Impact::DIRECTION_FORWARD,
            'result'    => [
               $this->graph['nodes']['A'],
               $this->graph['nodes']['B'],
               $this->graph['nodes']['E'],
            ],
         ],
         [
            'a'         => ['id' => "A"],
            'b'         => ['id' => "G"],
            'direction' => \Impact::DIRECTION_FORWARD,
            'result'    => [
               $this->graph['nodes']['A'],
               $this->graph['nodes']['B'],
               $this->graph['nodes']['E'],
               $this->graph['nodes']['G'],
            ],
         ],
         [
            'a'         => ['id' => "A"],
            'b'         => ['id' => "D"],
            'direction' => \Impact::DIRECTION_FORWARD,
            'result'    => [
               $this->graph['nodes']['A'],
               $this->graph['nodes']['D'],
            ],
         ],
         [
            'a'         => ['id' => "A"],
            'b'         => ['id' => "C"],
            'direction' => \Impact::DIRECTION_FORWARD,
            'result'    => [
               $this->graph['nodes']['A'],
               $this->graph['nodes']['D'],
               $this->graph['nodes']['C'],
            ],
         ],
         [
            'a'         => ['id' => "A"],
            'b'         => ['id' => "D"],
            'direction' => \Impact::DIRECTION_BACKWARD,
            'result'    => [
               $this->graph['nodes']['D'],
               $this->graph['nodes']['C'],
               $this->graph['nodes']['A'],
            ],
         ],
         [
            'a'         => ['id' => "A"],
            'b'         => ['id' => "C"],
            'direction' => \Impact::DIRECTION_BACKWARD,
            'result'    => [
               $this->graph['nodes']['C'],
               $this->graph['nodes']['A'],
            ],
         ],
         [
            'a'         => ['id' => "A"],
            'b'         => ['id' => "F"],
            'direction' => \Impact::DIRECTION_BACKWARD,
            'result'    => [
               $this->graph['nodes']['F'],
               $this->graph['nodes']['C'],
               $this->graph['nodes']['A'],
            ],
         ],
      ];
   }

   /**
    * @dataProvider bfsProvider
    */
   public function testBfs($a, $b, $direction, $result) {
      $path = \Impact::bfs($this->graph, $a, $b, $direction);
      $this->array($path);

      for ($i = 0; $i < count($path); $i++) {
         $this->string($path[$i]['id'])->isEqualTo($result[$i]['id']);
      }
   }

   public function testFilterGraph() {
      $forward = \Impact::filterGraph($this->graph, \Impact::DIRECTION_FORWARD);
      $this->array($forward)->hasKey('nodes')->hasKey('edges');

      foreach ($forward['edges'] as $edge) {
         $this->integer(\Impact::DIRECTION_FORWARD & $edge['flag'])->isEqualTo(\Impact::DIRECTION_FORWARD);
      }

      $backward = \Impact::filterGraph($this->graph, \Impact::DIRECTION_BACKWARD);
      $this->array($forward)->hasKey('nodes')->hasKey('edges');

      foreach ($backward['edges'] as $edge) {
         $this->integer(\Impact::DIRECTION_BACKWARD & $edge['flag'])->isEqualTo(\Impact::DIRECTION_BACKWARD);
      }
   }
}