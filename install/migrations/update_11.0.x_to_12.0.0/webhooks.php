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

use Glpi\Api\HL\Router;

/**
 * @var Migration $migration
 */

// Existing webhooks were built against the v2 payloads. Without this, they would silently
// switch to the next major version as soon as it becomes the router default.
$migration->addField('glpi_webhooks', 'pinned_version', 'string', [
    'update' => "'2'",
]);

// array_map: PHP turns numeric array keys into integers, and the column holds a string.
$deprecated_majors = array_map(
    'strval',
    array_keys(
        array_filter(
            Router::getAPIMajorVersions(),
            static fn(array $info): bool => $info['deprecated']
        )
    )
);

if ($deprecated_majors !== []) {
    $deprecated_count = countElementsInTable('glpi_webhooks', ['pinned_version' => $deprecated_majors]);
    if ($deprecated_count > 0) {
        $migration->addWarningMessage(
            sprintf(
                __('%d webhooks are pinned to a deprecated API version. Update them to a supported version.'),
                $deprecated_count
            )
        );
    }
}
