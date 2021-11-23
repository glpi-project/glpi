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

 /**
 * @var Migration $migration
 */

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();

/* Update link KB_item-category from 1-1 to 1-n */
if (!$DB->tableExists('glpi_knowbaseitems_categories')) {
   $query = "CREATE TABLE `glpi_knowbaseitems_categories` (
      `id` int NOT NULL AUTO_INCREMENT,
      `knowbaseitems_id` int NOT NULL DEFAULT '0',
      `knowbaseitemcategories_id` int NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`),
      KEY `knowbaseitems_id` (`knowbaseitems_id`),
      KEY `knowbaseitemcategories_id` (`knowbaseitemcategories_id`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
   $DB->queryOrDie($query, "add table glpi_knowbaseitems_categories");

   if ($DB->fieldExists('glpi_knowbaseitems', 'knowbaseitemcategories_id')) {
      $iterator = $DB->request([
         'SELECT' => ['id', 'knowbaseitemcategories_id'],
         'FROM'   => 'glpi_knowbaseitems',
         'WHERE'  => ['knowbaseitemcategories_id' => ['>', 0]]
      ]);
      if (count($iterator)) {
         //migrate existing data
         $migration->migrationOneTable('glpi_knowbaseitems_categories');
         foreach ($iterator as $row) {
            $DB->insert("glpi_knowbaseitems_categories", [
               'knowbaseitemcategories_id'   => $row['knowbaseitemcategories_id'],
               'knowbaseitems_id'            => $row['id']
            ]);
         }
      }
      $migration->dropField('glpi_knowbaseitems', 'knowbaseitemcategories_id');
   }
}
