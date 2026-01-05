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
 * @var \DBmysql $DB
 * @var \Migration $migration
 */

$glpi_key_manager = new GLPIKey();
$use_legacy_key   = $glpi_key_manager->keyExists() === false;

if ($use_legacy_key) {
    $glpi_key    = $glpi_key_manager->getLegacyKey();
    $encrypt_fct = function (string $value) use ($glpi_key): string {
        // Code corresponding to encryption used prior to GLPI 9.5 (copied from `Toolbox::encrypt()`).
        // /!\ It is mandatory to encrypt data using the legacy key to handle migrations from a GLPI version < 9.5.0.
        // Data will be re-encrypted at the update process when a new key will be generated.
        $result = '';
        $strlen = strlen($value);
        for ($i = 0; $i < $strlen; $i++) {
            $char    = substr($value, $i, 1);
            $keychar = substr($glpi_key, ($i % strlen($glpi_key)) - 1, 1);
            $char    = chr(ord($char) + ord($keychar));
            $result .= $char;
        }
        return base64_encode($result);
    };
} else {
    $glpi_key    = $glpi_key_manager->get();
    $encrypt_fct = fn(string $value) => $glpi_key_manager->encrypt($value);
}

if ($glpi_key === null) {
    // If `$glpi_key` is `null`, it means tha the key file exists but an error occurs while reading it.
    // It is preferable to fail here rather than ruin all tokens values in database.
    throw new RuntimeException('Unable to get the GLPI encryption key value.');
}

// Encrypt API clients tokens
$are_apiclients_tokens_encrypted = $DB->request([
    'FROM' => 'glpi_configs',
    'WHERE' => [
        'name'    => 'are_apiclients_tokens_encrypted',
        'context' => 'core',
    ],
])->current()['value'] ?? false;

if ((bool) $are_apiclients_tokens_encrypted === false) {
    $api_clients_iterator = $DB->request([
        'FROM' => 'glpi_apiclients',
    ]);

    foreach ($api_clients_iterator as $api_client_data) {
        if (empty($api_client_data['app_token'])) {
            continue;
        }

        $migration->addPostQuery(
            $DB->buildUpdate(
                'glpi_apiclients',
                [
                    'app_token' => $encrypt_fct($api_client_data['app_token']),
                ],
                [
                    'id' => $api_client_data['id'],
                ]
            )
        );
    }

    $migration->addConfig(['are_apiclients_tokens_encrypted' => 1]);
}

// Encrypt users tokens
$are_users_tokens_encrypted = $DB->request([
    'FROM' => 'glpi_configs',
    'WHERE' => [
        'name'    => 'are_users_tokens_encrypted',
        'context' => 'core',
    ],
])->current()['value'] ?? false;

if ((bool) $are_users_tokens_encrypted === false) {
    $migration->changeField('glpi_users', 'password_forget_token', 'password_forget_token', 'string'); // change length from 40 to 255

    $users_iterator = $DB->request([
        'FROM' => 'glpi_users',
    ]);

    foreach ($users_iterator as $user_data) {
        $update_input = [];

        foreach (['password_forget_token', 'personal_token', 'api_token', 'cookie_token'] as $token_field) {
            if (!empty($user_data[$token_field])) {
                $update_input[$token_field] = $encrypt_fct($user_data[$token_field]);
            }
        }

        if ($update_input === []) {
            continue;
        }

        $migration->addPostQuery(
            $DB->buildUpdate(
                'glpi_users',
                $update_input,
                [
                    'id' => $user_data['id'],
                ]
            )
        );
    }

    $migration->addConfig(['are_users_tokens_encrypted' => 1]);
}
