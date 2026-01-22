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

use Glpi\DBAL\QueryExpression;

/**
 * @var DBmysql $DB
 * @var Migration $migration
 */

// Fix null date_creation in glpi_documents_items
// This can happen when migrating from pre-9.5.0 where date_mod was NULL
// see #22134
$migration->addPostQuery(
    $DB->buildUpdate(
        'glpi_documents_items',
        [
            'date_creation' => new QueryExpression(
                'COALESCE('
                . $DB->quoteName('glpi_documents_items.date_mod') . ', '
                . '(SELECT ' . $DB->quoteName('d.date_creation')
                . ' FROM ' . $DB->quoteName('glpi_documents') . ' AS d'
                . ' WHERE ' . $DB->quoteName('d.id') . ' = '
                . $DB->quoteName('glpi_documents_items.documents_id') . ')'
                . ')'
            ),
        ],
        ['date_creation' => null]
    )
);
