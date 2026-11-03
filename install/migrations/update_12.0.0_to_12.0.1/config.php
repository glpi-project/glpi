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

// Delete unsafe SSO variables.
$sso_variables_data = getAllDataFromTable('glpi_ssovariables');

$used_sso_variable_id = (int) ($DB->request([
    'FROM' => 'glpi_configs',
    'WHERE' => [
        'name'    => 'ssovariables_id',
        'context' => 'core',
    ],
])->current()['value'] ?? 0);

foreach ($sso_variables_data as $sso_variable_data) {
    $sso_variable_name = mb_strtoupper($sso_variable_data['name'] ?? '');
    if (
        str_starts_with($sso_variable_name, 'HTTP_') === false
        && $sso_variable_name !== 'PHP_AUTH_USER'
    ) {
        // Only headers prefixed by `HTTP_` and `PHP_AUTH_USER` are unsafe.
        continue;
    }

    if ($sso_variable_data['id'] === $used_sso_variable_id) {
        // Do not delete the variable if it is used.
        $migration->addWarningMessage(
            sprintf(
                __('The %s variable is unsafe. It should not be used for authentication.'),
                $sso_variable_name,
            )
        );
        continue;
    }

    $migration->addPostQuery($DB->buildDelete('glpi_ssovariables', ['id' => $sso_variable_data['id']]));
}
