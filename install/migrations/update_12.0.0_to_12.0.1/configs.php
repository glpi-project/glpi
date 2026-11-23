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
 */

$auth_configured = countElementsInTable(
    'glpi_configs',
    [
        'context' => 'inventory',
        'name'    => 'auth_required',
        'NOT'     => ['value' => ''],
    ]
);

if ($auth_configured === 0) {
    $migration->addWarningMessage(
        __('No authentication method is configured for inventory: agents will be rejected until one is selected in the inventory configuration.')
    );
}

$migration->addConfig(
    [
        'auth_required'       => '',
        'basic_auth_login'    => '',
        'basic_auth_password' => '',
    ],
    'inventory'
);
