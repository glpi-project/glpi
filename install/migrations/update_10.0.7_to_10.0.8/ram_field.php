<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
 * @var DB $DB
 * @var Migration $migration
 */

foreach (['glpi_computervirtualmachines', 'glpi_networkequipments'] as $table) {
    // field has to be nullable to be able to set empty values to null
    $migration->changeField(
        $table,
        'ram',
        'ram',
        'varchar(255) DEFAULT NULL',
    );
    $migration->migrationOneTable($table);

    $DB->update(
        $table,
        ['ram' => null],
        ['ram' => '']
    );
    $DB->update(
        $table,
        ['ram' => new QueryExpression(sprintf('REGEXP_SUBSTR(%s, %s)', $DB->quoteName('ram'), $DB->quoteValue('[0-9]+')))],
        ['ram' => ['REGEXP', '[^0-9]+']]
    );
    $migration->changeField(
        $table,
        'ram',
        'ram',
        'int unsigned DEFAULT NULL',
    );
}
