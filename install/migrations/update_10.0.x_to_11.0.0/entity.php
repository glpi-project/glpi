<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
if (!$DB->fieldExists("glpi_entities", "inquest_max_rate", false)) {
    $migration->addField('glpi_entities', 'inquest_max_rate', "int NOT NULL DEFAULT '5'", ['after' => 'inquest_URL']);
}

if (!$DB->fieldExists("glpi_entities", "inquest_default_rate", false)) {
    $migration->addField('glpi_entities', 'inquest_default_rate', "int NOT NULL DEFAULT '3'", ['after' => 'inquest_max_rate']);
}

if (!$DB->fieldExists("glpi_entities", "inquest_mandatory_comment", false)) {
    $migration->addField('glpi_entities', 'inquest_mandatory_comment', "int NOT NULL DEFAULT '0'", ['after' => 'inquest_default_rate']);
}

$fields = [
    'is_contact_autoupdate',
    'is_user_autoupdate',
    'is_group_autoupdate',
    'is_location_autoupdate',
    'is_contact_autoclean',
    'is_user_autoclean',
    'is_group_autoclean',
    'is_location_autoclean',
];
$config = Config::getConfigurationValues('core');
foreach ($fields as $field) {
    if (!$DB->fieldExists("glpi_entities", $field, false)) {
        $migration->addField(
            'glpi_entities',
            $field,
            "tinyint NOT NULL DEFAULT '-2'"
        );
        $migration->addPostQuery(
            $DB->buildUpdate(
                'glpi_entities',
                [$field => $config[$field]],
                ['id' => 0]
            )
        );
    }
}
$migration->removeConfig($fields);

$fields = [
    'state_autoupdate_mode',
    'state_autoclean_mode',
];
$config = Config::getConfigurationValues('core');
foreach ($fields as $field) {
    if (!$DB->fieldExists("glpi_entities", $field, false)) {
        $migration->addField(
            'glpi_entities',
            $field,
            "int NOT NULL DEFAULT '-2'"
        );
        $migration->addPostQuery(
            $DB->buildUpdate(
                'glpi_entities',
                [$field => $config[$field]],
                ['id' => 0]
            )
        );
    }
}
$migration->removeConfig($fields);

/** Add base url for entities to be used in notification */
if (!$DB->fieldExists("glpi_entities", "url_base", false)) {
    $migration->addField('glpi_entities', 'url_base', "TEXT", ['after' => 'mailing_signature']);
}
