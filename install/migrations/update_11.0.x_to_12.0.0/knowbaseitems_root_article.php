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

use Glpi\DBAL\QuerySubQuery;

/**
 * @var DBmysql $DB
 * @var Migration $migration
 */

// The root article is the base of the knowledge base tree: it can never be
// deleted and its id is kept in the `root_knowbaseitems_id` configuration.
// Fresh installations get theirs from `install/empty_data.php`.
//
// This file must run *after* `knowbaseitemcategory_to_article.php` (it does,
// files are included in alphabetical order) so that the articles created from
// the former categories are attached to the root article too.
$kb_links_table = 'glpi_knowbaseitems_knowbaseitems';

// Drop the links that reference an article that no longer exists as a safety.
$kb_existing_articles = new QuerySubQuery([
    'SELECT' => 'id',
    'FROM'   => 'glpi_knowbaseitems',
]);
$DB->delete($kb_links_table, [
    'OR' => [
        'knowbaseitems_id'        => ['NOT IN', $kb_existing_articles],
        'knowbaseitems_id_parent' => ['NOT IN', $kb_existing_articles],
    ],
]);

$root_config = $DB->request([
    'SELECT' => 'value',
    'FROM'   => 'glpi_configs',
    'WHERE'  => [
        'context' => 'core',
        'name'    => 'root_knowbaseitems_id',
    ],
])->current();

$root_id = (int) ($root_config['value'] ?? 0);

// Also recreate the article when the configuration points to an article that no
// longer exists (interrupted migration, manual database surgery, ...), as the
// whole feature relies on this id being valid.
if ($root_id === 0 || countElementsInTable('glpi_knowbaseitems', ['id' => $root_id]) === 0) {
    $migration->displayMessage("Create the knowledge base root article");

    $now = date('Y-m-d H:i:s');
    $DB->insert('glpi_knowbaseitems', [
        'entities_id'   => 0,
        'is_recursive'  => 1,
        'name'          => __('Home'),
        'answer'        => '',
        'is_faq'        => 0,
        'users_id'      => 0,
        'date_creation' => $now,
        'date_mod'      => $now,
    ]);
    $root_id = (int) $DB->insertId();

    // Not `$migration->addConfig()`: that one only inserts missing keys, while
    // we may also have to fix a key pointing to a missing article.
    $DB->updateOrInsert(
        'glpi_configs',
        [
            'value' => $root_id,
        ],
        [
            'context' => 'core',
            'name'    => 'root_knowbaseitems_id',
        ]
    );
}

// Attach every other top-level article, i.e. every article that has no parent,
// to the root article so the knowledge base is a single tree.
$migration->displayMessage("Attach top-level knowledge base articles to the root article");

foreach ($DB->request([
    'SELECT' => 'id',
    'FROM'   => 'glpi_knowbaseitems',
    'WHERE'  => [
        ['NOT' => ['id' => $root_id]],
        'id' => ['NOT IN', new QuerySubQuery([
            'SELECT' => 'knowbaseitems_id',
            'FROM'   => $kb_links_table,
        ])],
    ],
]) as $article) {
    $DB->insert($kb_links_table, [
        'knowbaseitems_id'        => (int) $article['id'],
        'knowbaseitems_id_parent' => $root_id,
    ]);
}
