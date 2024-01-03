<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

/**
 * @var \DBmysql $DB
 * @var \Migration $migration
 */

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

/* Update link KB_item-category from 1-1 to 1-n */
if (!$DB->tableExists('glpi_knowbaseitems_knowbaseitemcategories')) {
    $query = "CREATE TABLE `glpi_knowbaseitems_knowbaseitemcategories` (
      `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
      `knowbaseitems_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `knowbaseitemcategories_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`),
      KEY `knowbaseitems_id` (`knowbaseitems_id`),
      KEY `knowbaseitemcategories_id` (`knowbaseitemcategories_id`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->doQueryOrDie($query, "add table glpi_knowbaseitems_knowbaseitemcategories");
}

if ($DB->fieldExists('glpi_knowbaseitems', 'knowbaseitemcategories_id')) {
    $iterator = $DB->request([
        'SELECT' => ['id', 'knowbaseitemcategories_id'],
        'FROM'   => 'glpi_knowbaseitems',
        'WHERE'  => ['knowbaseitemcategories_id' => ['>', 0]]
    ]);
    if (count($iterator)) {
       //migrate existing data
        foreach ($iterator as $row) {
            $DB->insertOrDie("glpi_knowbaseitems_knowbaseitemcategories", [
                'knowbaseitemcategories_id'   => $row['knowbaseitemcategories_id'],
                'knowbaseitems_id'            => $row['id']
            ]);
        }
    }
    $migration->dropField('glpi_knowbaseitems', 'knowbaseitemcategories_id');
}
