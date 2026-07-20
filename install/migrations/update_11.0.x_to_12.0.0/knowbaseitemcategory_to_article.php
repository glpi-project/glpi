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

$migration->displayMessage("Convert knowledge base categories into articles");

$cat_table  = 'glpi_knowbaseitemcategories';
$link_table = 'glpi_knowbaseitems_knowbaseitemcategories';

// old category id => new article id, populated below and reused by later steps
$cat_to_article = [];

// Guard: only run if the category table still exists (idempotent upgrades)
if ($DB->tableExists($cat_table)) {
    // 1) Categories -> articles, building old_cat_id => new_article_id map
    foreach ($DB->request(['FROM' => $cat_table, 'ORDER' => 'id']) as $cat) {
        $DB->insert('glpi_knowbaseitems', [
            'name'          => $cat['name'],
            'answer'        => '',
            'illustration'  => $cat['illustration'] ?? null,
            'entities_id'   => $cat['entities_id'],
            'is_recursive'  => $cat['is_recursive'],
            'is_faq'        => 0,
            'date_creation' => $cat['date_creation'],
            'date_mod'      => $cat['date_mod'],
        ]);
        $cat_to_article[(int) $cat['id']] = (int) $DB->insertId();
    }

    // NOTE: migrated categories are CLOSED by default, intentionally NO
    // visibility rows are created. They stay invisible until an admin grants
    // access, which then cascades via inherited visibility.

    // 2) Category name translations (glpi_dropdowntranslations) -> KnowbaseItemTranslation
    if ($DB->tableExists('glpi_dropdowntranslations')) {
        foreach ($DB->request([
            'FROM'  => 'glpi_dropdowntranslations',
            'WHERE' => ['itemtype' => 'KnowbaseItemCategory', 'field' => 'name'],
        ]) as $tr) {
            if (!isset($cat_to_article[(int) $tr['items_id']])) {
                continue;
            }
            $DB->insert('glpi_knowbaseitemtranslations', [
                'knowbaseitems_id' => $cat_to_article[(int) $tr['items_id']],
                'language'         => $tr['language'],
                'name'             => $tr['value'],
                'answer'           => '',
            ]);
        }
        $DB->delete('glpi_dropdowntranslations', ['itemtype' => 'KnowbaseItemCategory']);
    }
}

// 3) Build the new link table by copying the old one row by row.
// We create an empty table matching the fresh-install schema, then read the old
// link rows and insert them with the parent id already mapped in PHP.
if ($DB->tableExists($link_table)) {
    if (!$DB->tableExists('glpi_knowbaseitems_knowbaseitems')) {
        $DB->doQuery(
            "CREATE TABLE `glpi_knowbaseitems_knowbaseitems` (
                `id`                      int unsigned NOT NULL AUTO_INCREMENT,
                `knowbaseitems_id`        int unsigned NOT NULL DEFAULT '0',
                `knowbaseitems_id_parent` int unsigned NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                UNIQUE KEY `unicity` (`knowbaseitems_id`, `knowbaseitems_id_parent`),
                KEY `knowbaseitems_id_parent` (`knowbaseitems_id_parent`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC"
        );
    }

    if ($cat_to_article !== []) {
        // existing rows: parent currently holds the OLD category id -> map to new
        // article id. Rows referencing an unmapped category are dropped as stale.
        foreach ($DB->request(['FROM' => $link_table]) as $link) {
            $parent = $cat_to_article[(int) $link['knowbaseitemcategories_id']] ?? null;
            if ($parent !== null) {
                $DB->insert('glpi_knowbaseitems_knowbaseitems', [
                    'knowbaseitems_id'        => $link['knowbaseitems_id'],
                    'knowbaseitems_id_parent' => $parent,
                ]);
            }
        }
        // category-tree edges: child = category-as-article, parent = parent-category-as-article
        foreach ($DB->request(['FROM' => 'glpi_knowbaseitemcategories']) as $cat) {
            $parent_cat = (int) $cat['knowbaseitemcategories_id'];
            if ($parent_cat > 0 && isset($cat_to_article[$parent_cat])) {
                $DB->insert('glpi_knowbaseitems_knowbaseitems', [
                    'knowbaseitems_id'        => $cat_to_article[(int) $cat['id']],
                    'knowbaseitems_id_parent' => $cat_to_article[$parent_cat],
                ]);
            }
        }
    }

    $migration->dropTable($link_table);
}

// 4) ITIL/Task category FK rename + remap
foreach (['glpi_itilcategories', 'glpi_taskcategories'] as $t) {
    if ($DB->fieldExists($t, 'knowbaseitemcategories_id')) {
        $migration->changeField($t, 'knowbaseitemcategories_id', 'knowbaseitems_id', 'int unsigned NOT NULL DEFAULT 0');
        $migration->migrationOneTable($t);
        $migration->dropKey($t, 'knowbaseitemcategories_id');
        $migration->addKey($t, 'knowbaseitems_id', 'knowbaseitems_id');
        if ($cat_to_article !== []) {
            foreach ($DB->request(['FROM' => $t, 'WHERE' => ['knowbaseitems_id' => array_keys($cat_to_article)]]) as $row) {
                $DB->update(
                    $t,
                    ['knowbaseitems_id' => $cat_to_article[(int) $row['knowbaseitems_id']]],
                    ['id' => $row['id']]
                );
            }
        }
    }
}

// 5) Drop the knowbasecategory profileright
$DB->delete('glpi_profilerights', ['name' => 'knowbasecategory']);

// 6) Clean up stale rows referencing the old itemtype, then drop the table
foreach (['glpi_displaypreferences' => 'itemtype', 'glpi_savedsearches' => 'itemtype'] as $t => $col) {
    $DB->delete($t, [$col => 'KnowbaseItemCategory']);
}
$migration->dropTable('glpi_knowbaseitemcategories');
