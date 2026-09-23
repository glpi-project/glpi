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

/** /Add handling of body encryption in notification */
$migration->addField('glpi_queuednotifications', 'is_body_encrypted', 'boolean');
$migration->migrationOneTable('glpi_queuednotifications'); // must be migrated before performing the encryption of existing notifications

// Encrypt sensitive notifications
$glpi_key_manager = new GLPIKey();
$use_legacy_key   = $glpi_key_manager->keyExists() === false;

if ($use_legacy_key) {
    $glpi_key    = $glpi_key_manager->getLegacyKey();

    if ($glpi_key === null) {
        // If `$glpi_key` is `null`, it means the key file exists but an error occurs while reading it.
        // It is preferable to fail here rather than ruin all tokens values in database.
        throw new RuntimeException('Unable to get the GLPI encryption key value.');
    }

    $encrypt_fct = function (string $value) use ($glpi_key): string {
        // Code corresponding to encryption used prior to GLPI 9.5 (copied from `Toolbox::encrypt()`).
        // /!\ It is mandatory to encrypt data using the legacy key to handle migrations from a GLPI version < 9.5.0.
        // Data will be re-encrypted at the end of the update process when a new key will be generated.
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

    if ($glpi_key === null) {
        // If `$glpi_key` is `null`, it means the key file exists but an error occurs while reading it.
        // It is preferable to fail here rather than ruin all tokens values in database.
        throw new RuntimeException('Unable to get the GLPI encryption key value.');
    }

    $encrypt_fct = fn(string $value) => $glpi_key_manager->encrypt($value);
}

$queuednotifications_iterator = $DB->request([
    'FROM'  => 'glpi_queuednotifications',
    'WHERE' => [
        'itemtype'          => 'User',
        'event'             => ['passwordforget', 'passwordinit'],
        'is_body_encrypted' => false,
    ],
]);

foreach ($queuednotifications_iterator as $queuednotification_data) {
    $update = [];
    foreach (['body_text', 'body_html'] as $field) {
        if (!empty($queuednotification_data[$field])) {
            $update[$field] = $encrypt_fct($queuednotification_data[$field]);
        }
    }

    if ($update === []) {
        continue;
    }

    $migration->addPostQuery(
        $DB->buildUpdate(
            'glpi_queuednotifications',
            $update + ['is_body_encrypted' => true],
            [
                'id' => $queuednotification_data['id'],
            ]
        )
    );
}
