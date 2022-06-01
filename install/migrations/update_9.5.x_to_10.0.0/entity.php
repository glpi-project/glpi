<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * @var DB $DB
 * @var Migration $migration
 */

$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

/** Create registration_number field */
if (!$DB->fieldExists("glpi_entities", "registration_number")) {
    $migration->addField(
        "glpi_entities",
        "registration_number",
        "string",
        [
            'after'     => "ancestors_cache",
        ]
    );
}
/** /Create registration_number field */

/** Replace -1 value for entities_id field */
// Replace -1 value for root entity to be able to change type to unsigned.
// Use max int signed value of mysql to be fairly certain not to be blocked because of the uniqueness key.
$DB->updateOrDie('glpi_entities', ['entities_id' => pow(2, 31) - 1], ['id' => '0']);

$migration->changeField('glpi_entities', 'entities_id', 'entities_id', "int {$default_key_sign} DEFAULT '0'");
$migration->migrationOneTable('glpi_entities'); // Ensure 'entities_id' is nullable.
$DB->updateOrDie('glpi_entities', ['entities_id' => 'NULL'], ['id' => '0']);
/** /Replace -1 value for entities_id field */

/** Replace negative values for config foreign keys */
$fkey_config_fields = [
    'calendars_id',
    'changetemplates_id',
    'contracts_id_default',
    'entities_id_software',
    'problemtemplates_id',
    'tickettemplates_id',
    'transfers_id',
];
$migration->migrationOneTable('glpi_entities');
foreach ($fkey_config_fields as $fkey_config_field) {
    $strategy_field = str_replace('_id', '_strategy', $fkey_config_field);
    if (!$DB->fieldExists('glpi_entities', $strategy_field)) {
        $migration->addField(
            'glpi_entities',
            str_replace('_id', '_strategy', $fkey_config_field),
            'tinyint NOT NULL DEFAULT -2',
            [
                // 0 value for root entity
                'update'    => '0',
                'condition' => 'WHERE `id` = 0'
            ]
        );
        $migration->migrationOneTable('glpi_entities'); // Ensure strategy field is created to be able to fill it

        if ($DB->fieldExists('glpi_entities', $fkey_config_field)) {
            // 'contracts_id_default' and 'transfers_id' fields will only exist if a previous dev install exists
            $DB->updateOrDie(
                'glpi_entities',
                [
                    // Put negative values (-10[never]/ -2[inherit]/ -1[auto]) in strategy field
                    // or 0 if an id was selected to indicate that value is not inherited.
                    str_replace('_id', '_strategy', $fkey_config_field) => new QueryExpression(
                        sprintf('LEAST(%s, 0)', $DB->quoteName($fkey_config_field))
                    ),
                    // Keep only positive (or 0) values in id field
                    $fkey_config_field => new QueryExpression(
                        sprintf('GREATEST(%s, 0)', $DB->quoteName($fkey_config_field))
                    ),
                ],
                ['1'] // Update all entities
            );
        }
    }

    if ($DB->fieldExists('glpi_entities', $fkey_config_field)) {
        // 'contracts_id_default' and 'transfers_id' fields will only exist if a previous dev install exists
        $migration->changeField('glpi_entities', $fkey_config_field, $fkey_config_field, "int {$default_key_sign} NOT NULL DEFAULT 0");
    }

    // Add display_users_initials to entity
    if (!$DB->fieldExists("glpi_entities", "display_users_initials")) {
        $migration->addField(
            "glpi_entities",
            "display_users_initials",
            "integer",
            [
                'after'     => "anonymize_support_agents",
                'value'     => -2,               // Inherit as default value
                'update'    => '1',              // Enabled for root entity
                'condition' => 'WHERE `id` = 0'
            ]
        );
    }
}
/** /Replace negative values for config foreign keys */

/** Email configuration at entity level */
$migration->changeField('glpi_entities', 'admin_reply', 'replyto_email', 'string');
$migration->changeField('glpi_entities', 'admin_reply_name', 'replyto_email_name', 'string');
$migration->addField('glpi_entities', 'from_email', 'string', ['update' => '', 'condition' => 'WHERE `id` = 0']);
$migration->addField('glpi_entities', 'from_email_name', 'string', ['update' => '', 'condition' => 'WHERE `id` = 0']);
$migration->addField('glpi_entities', 'noreply_email', 'string', ['update' => '', 'condition' => 'WHERE `id` = 0']);
$migration->addField('glpi_entities', 'noreply_email_name', 'string', ['update' => '', 'condition' => 'WHERE `id` = 0']);
/** /Email configuration at entity level */

// Add certificates_alert_repeat_interval to entity
if (!$DB->fieldExists("glpi_entities", "certificates_alert_repeat_interval")) {
    $migration->addField(
        "glpi_entities",
        "certificates_alert_repeat_interval",
        "integer",
        [
            'after'     => "send_certificates_alert_before_delay",
            'value'     => -2,               // Inherit as default value
            'update'    => '0',              // Disabled for root entity
            'condition' => 'WHERE `id` = 0'
        ]
    );
}
