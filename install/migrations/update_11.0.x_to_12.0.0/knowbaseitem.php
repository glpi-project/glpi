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

$migration->addField(
    'glpi_knowbaseitems',
    'is_draft',
    'bool',
    ['after' => 'is_pinned']
);
$migration->addKey('glpi_knowbaseitems', 'is_draft');

// Registered disabled: enabling auto-purge on upgrade would silently delete
// drafts a week later. Operators enable it explicitly from the cron page.
$migration->addCrontask(
    KnowbaseItem::class,
    'purgedraftitems',
    DAY_TIMESTAMP,
    param: 7,
    options: ['state' => 0],
);
$migration->displayMessage(
    'Knowledge base draft auto-purge cron is registered as disabled. Enable it from the cron page if desired.'
);
