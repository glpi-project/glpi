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

// Optional text-quote anchor (W3C TextQuoteSelector-style) attaching a comment
// to a selected passage instead of the whole article. Root comments only.
$table = 'glpi_knowbaseitems_comments';

$migration->addField($table, 'anchor_prefix', 'string', [
    'after' => 'parent_comment_id',
    'value' => null,
]);
$migration->addField($table, 'anchor_exact', 'text', [
    'after' => 'anchor_prefix',
]);
$migration->addField($table, 'anchor_suffix', 'string', [
    'after' => 'anchor_exact',
    'value' => null,
]);
$migration->addField($table, 'anchor_occurrence', 'integer', [
    'after'     => 'anchor_suffix',
    'nodefault' => true,
    'null'      => true,
]);
