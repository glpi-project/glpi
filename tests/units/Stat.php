<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

use \DbTestCase;

/* Test for inc/ticket.class.php */

class Stat extends DbTestCase {

   public function testBarGraph() {
      $stat = new \Stat();
      $labels = ['label 1', 'label 2'];
      $series = [
         [
            'name'   => 'label 1',
            'data'   => [1, 3, 4, 6]
         ],
         [
            'name'   => 'label 2',
            'data'   => [10, 12, 16, 19]
         ],
      ];
      $html = $stat->displayBarGraph('title of the graph', $labels, $series, null, false);

      $expectedLabels = "labels: ['" . implode("', '", $labels) . "']";
      $expectedSeries = [];
      foreach ($series as $serie) {
         $name = $serie['name'];
         $data = implode(', ', $serie['data']);
         $expectedSeries[] = "{'name': '$name', 'data': [$data]}";
      }

      // Verify the labels are in the HTML
      $this->string($html)
           ->contains($expectedLabels)
           ->contains('title of the graph');

      // Verify the series are in  the HTML
      foreach ($expectedSeries as $expectedserie) {
         $this->string($html)
              ->contains($expectedserie);
      }
   }

   /**
    * @engine inline
    */
   public function testStackedBarGraph() {
      $stat = new \Stat();
      $labels = ['label 1', 'label 2'];
      $series = [
            [
                  'name'   => 'label 1',
                  'data'   => [1, 3, 4, 6]
            ],
            [
                  'name'   => 'label 2',
                  'data'   => [10, 12, 16, 19]
            ],
      ];
      $html = $stat->displayBarGraph('title of the graph', $labels, $series, null, false);

      $expectedLabels = "labels: ['" . implode("', '", $labels) . "']";
      $expectedSeries = [];
      foreach ($series as $serie) {
         $name = $serie['name'];
         $data = implode(', ', $serie['data']);
         $expectedSeries[] = "{'name': '$name', 'data': [$data]}";
      }

      // Verify the labels are in the HTML
      $this->string($html)
           ->contains($expectedLabels)
           ->contains('title of the graph');

      // Verify the series are in  the HTML
      foreach ($expectedSeries as $expectedserie) {
         $this->string($html)
              ->contains($expectedserie);
      }
   }
}