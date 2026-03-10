<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
 * @var DBmysql $DB
 * @var Migration $migration
 */

$migration->displayMessage('Remove entities_id and is_recursive from glpi_documents_items');

// Drop old index that includes entities_id and is_recursive
$migration->dropKey('glpi_documents_items', 'item');
// Drop entities_id index
$migration->dropKey('glpi_documents_items', 'entities_id');

// Drop the fields
$migration->dropField('glpi_documents_items', 'entities_id');
$migration->dropField('glpi_documents_items', 'is_recursive');

// Recreate the item index without entities_id and is_recursive
$migration->addKey('glpi_documents_items', ['itemtype', 'items_id'], 'item');
