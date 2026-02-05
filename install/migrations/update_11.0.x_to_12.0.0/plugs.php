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
 * @var Migration $migration
 * @var DBmysql $DB
 */

$migration->addField(
    'glpi_plugs',
    'custom_name',
    'varchar(255) DEFAULT NULL',
    ['after' => 'name']
);

$migration->addField(
    'glpi_plugs',
    'itemtype_main',
    'varchar(255) DEFAULT NULL',
    ['after' => 'custom_name']
);

$migration->addField(
    'glpi_plugs',
    'items_id_main',
    'fkey',
    ['after' => 'itemtype_main']
);

$migration->addField(
    'glpi_plugs',
    'itemtype_asset',
    'VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'\'',
    ['after' => 'items_id_main']
);

$migration->addField(
    'glpi_plugs',
    'items_id_asset',
    'fkey',
    ['after' => 'itemtype_asset']
);

$migration->addField(
    'glpi_plugs',
    'entities_id',
    'fkey',
    ['after' => 'items_id_asset']
);

$migration->addField(
    'glpi_plugs',
    'is_recursive',
    'bool',
    ['after' => 'entities_id']
);


$migration->addKey('glpi_plugs', ['itemtype_asset', 'items_id_asset'], 'item');
$migration->addKey('glpi_plugs', ['itemtype_main', 'items_id_main'], 'mainitem');
$migration->addKey('glpi_plugs', 'items_id_main');
$migration->addKey('glpi_plugs', 'entities_id');
$migration->addKey('glpi_plugs', 'is_recursive');
$migration->migrationOneTable('glpi_plugs');

$migration->dropKey('glpi_plugs', 'pdus_id');
// migrate existing plugs linked by glpi_items_plugs to new structure
if ($DB->tableExists('glpi_items_plugs')) {

    $criteria = [
        'SELECT'    => ["*"],
        'FROM'      => 'glpi_items_plugs',
    ];
    $iterator = $DB->request($criteria);

    foreach ($iterator as $p) {
        // try to load plug from DB
        $plug = $DB->request([
            'SELECT' => ["id"],
            'FROM'   => 'glpi_plugs',
            'WHERE'  => ['id' => $p['plugs_id']],
        ])->current();

        // handle plug migration if exist
        if ($plug && $DB->numrows($result)) {
            for ($i = 1; $i <= $p['number_plugs']; $i++) {
                // create dedicated plug
                $migration->addPostQuery(
                    $DB->buildInsert('glpi_plugs', [
                        'name'              => $plug['name'] . ' - ' . $i,
                        'itemtype_asset'    => $p['itemtype'],
                        'items_id_asset'    => $p['items_id'],
                        'entities_id'       => 0,
                        'is_recursive'      => 1,
                        'comment'           => $plug['comment'],
                    ])
                );
            }

            // remove old plug
            $migration->addPostQuery(
                $DB->buildDelete("glpi_plugs", ['id' => $p['plugs_id']])
            );
        }
    }
    $migration->dropTable('glpi_items_plugs');
}
