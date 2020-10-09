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

/**
 * @var DB $DB
 * @var Migration $migration
 */

$migration->displayMessage("Adding unicity key to reservationitem");
$table = ReservationItem::getTable();

// Drop is_deleted
$migration->dropKey($table, 'is_deleted');
$migration->dropField($table, "is_deleted");

// Find duplicates by itemtype/items_id couple
$duplicates_by_itemtype = $DB->request([
   'SELECT'  => ['itemtype', 'items_id'],
   'COUNT'   => 'cpt',
   'FROM'    => $table,
   'WHERE'   => [],
   'GROUPBY' => ['itemtype', 'items_id'],
   'HAVING'  => ['cpt' => ['>' , 1]]
]);

foreach ($duplicates_by_itemtype as $duplicates) {
   // Get ids of all duplicate for this itemtype/items_id couple expect the first
   // LIMIT $duplicates['cpt'] is there because we need a LIMIT clause to use OFFSET
   $to_delete = $DB->request([
      'SELECT' =>  'id',
      'FROM' => $table,
      'WHERE' => [
         'itemtype' => $duplicates['itemtype'],
         'items_id' => $duplicates['items_id'],
      ],
      'LIMIT' => $duplicates['cpt'],
      'START' => 1
   ]);

   // Reduce to an array of ids
   $to_delete = array_map(function ($value) {
      return $value['id'];
   }, iterator_to_array($to_delete));

   $DB->delete($table, ['id' => $to_delete]);
}

// Add unicity key
$migration->addKey(
   $table,
   ['itemtype', 'items_id'],
   'unicity',
   'UNIQUE'
);
