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

// Drop the ancestors/sons cache that may have been corrupted by bugs that have now been resolved.
$tree_dropdown_tables = [
    'glpi_businesscriticities',
    'glpi_documentcategories',
    'glpi_entities',
    'glpi_groups',
    'glpi_ipnetworks',
    'glpi_itilcategories',
    'glpi_knowbaseitemcategories',
    'glpi_locations',
    'glpi_softwarecategories',
    'glpi_softwarelicenses',
    'glpi_softwarelicensetypes',
    'glpi_states',
    'glpi_taskcategories',
];
foreach ($tree_dropdown_tables as $table) {
    $migration->addPostQuery(
        $DB->buildUpdate(
            $table,
            [
                'ancestors_cache' => null,
                'sons_cache' => null,
            ],
            [
                new QueryExpression(true),
            ]
        )
    );
}
