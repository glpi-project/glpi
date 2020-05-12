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

namespace Glpi\Dashboard;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class Item extends \CommonDBChild {
   static public $itemtype = "Glpi\\Dashboard\\Dashboard";
   static public $items_id = 'dashboards_dashboards_id';

   // prevent bad getFromDB when bootstraping tests suite
   static public $mustBeAttached = false;

   /**
    * Return items for the provided dashboard
    *
    * @param int $dashboards_id
    *
    * @return array the items
    */
   static function getForDashboard(int $dashboards_id = 0): array {
      global $DB;

      $di_iterator = $DB->request([
         'FROM'  => self::getTable(),
         'WHERE' => [
            'dashboards_dashboards_id' => $dashboards_id
         ]
      ]);

      $items = [];
      foreach ($di_iterator as $item) {
         unset($item['id']);
         $item['card_options'] = importArrayFromDB($item['card_options']);
         $items[] = $item;
      }

      return $items;
   }


   /**
    * Save items in DB for the provided dashboard
    *
    * @param int $dashboards_id id (not key) of the dashboard
    * @param array $items cards of the dashboard, contains:
    *    - gridstack_id: unique id of the card in the grid, usually build like card_id.uuidv4
    *    - card_id: key of array return by getAllDasboardCards
    *    - x: position in grid
    *    - y: position in grid
    *    - width: size in grid
    *    - height: size in grid
    *    - card_options, sub array, depends on the card, contains at least a key color
    *
    * @return void
    */
   static function addForDashboard(int $dashboards_id = 0, array $items = []) {
      global $DB, $_UREQUEST;

      $query_items = $DB->buildInsert(
         self::getTable(),
         [
            'dashboards_dashboards_id' => new \QueryParam(),
            'gridstack_id' => new \QueryParam(),
            'card_id'      => new \QueryParam(),
            'x'            => new \QueryParam(),
            'y'            => new \QueryParam(),
            'width'        => new \QueryParam(),
            'height'       => new \QueryParam(),
            'card_options' => new \QueryParam(),
         ]
      );
      $stmt = $DB->prepare($query_items);
      foreach ($items as $item_key => $item) {
         // card_options should be unescaped as they will be json_encoded after
         $card_options = $_UREQUEST['items'][$item_key]['card_options'] ?? $item['card_options'] ?? [];

         // clean
         unset(
            $card_options['force'],
            $card_options['card_id'],
            $card_options['gridstack_id']
         );

         // encode for DB
         $card_options = exportArrayToDB($card_options);
         $gridstack_id = $item['gridstack_id'] ?? $item['gs_id'];

         $stmt->bind_param(
            'issiiiis',
            $dashboards_id,
            $gridstack_id,
            $item['card_id'],
            $item['x'],
            $item['y'],
            $item['width'],
            $item['height'],
            $card_options
         );
         $stmt->execute();
      }
   }
}
