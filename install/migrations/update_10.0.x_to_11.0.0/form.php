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

use function Safe\json_decode;
use function Safe\json_encode;

/**
 * @var DBmysql $DB
 * @var Migration $migration
 * @var array $ADDTODISPLAYPREF
 */

$default_charset   = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();

if (!$DB->tableExists('glpi_forms_categories')) {
    $DB->doQuery(
        "CREATE TABLE `glpi_forms_categories` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL DEFAULT '',
            `description` longtext,
            `illustration` varchar(255) NOT NULL DEFAULT '',
            `forms_categories_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `completename` text,
            `level` int NOT NULL DEFAULT '0',
            `ancestors_cache` longtext,
            `sons_cache` longtext,
            `comment` text,
            PRIMARY KEY (`id`),
            KEY `name` (`name`),
            KEY `level` (`level`),
            KEY `forms_categories_id` (`forms_categories_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
    );
}

// Create tables
if (!$DB->tableExists('glpi_forms_forms')) {
    $DB->doQuery(
        "CREATE TABLE `glpi_forms_forms` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `uuid` varchar(255) NOT NULL DEFAULT '',
            `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `is_recursive` tinyint NOT NULL DEFAULT '0',
            `is_active` tinyint NOT NULL DEFAULT '0',
            `is_deleted` tinyint NOT NULL DEFAULT '0',
            `is_draft` tinyint NOT NULL DEFAULT '0',
            `is_pinned` tinyint NOT NULL DEFAULT '0',
            `name` varchar(255) NOT NULL DEFAULT '',
            `header` longtext,
            `illustration` varchar(255) NOT NULL DEFAULT '',
            `description` longtext,
            `forms_categories_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `usage_count` int unsigned NOT NULL DEFAULT '0',
            `submit_button_visibility_strategy` varchar(30) NOT NULL DEFAULT '',
            `submit_button_conditions` JSON NOT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uuid` (`uuid`),
            KEY `name` (`name`),
            KEY `entities_id` (`entities_id`),
            KEY `is_recursive` (`is_recursive`),
            KEY `is_active` (`is_active`),
            KEY `is_deleted` (`is_deleted`),
            KEY `is_draft` (`is_draft`),
            KEY `date_mod` (`date_mod`),
            KEY `date_creation` (`date_creation`),
            KEY `forms_categories_id` (`forms_categories_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
    );
}
if (!$DB->tableExists('glpi_forms_sections')) {
    $DB->doQuery(
        "CREATE TABLE `glpi_forms_sections` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `uuid` varchar(255) NOT NULL DEFAULT '',
            `forms_forms_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `name` varchar(255) NOT NULL DEFAULT '',
            `description` longtext,
            `rank` int NOT NULL DEFAULT '0',
            `visibility_strategy` varchar(30) NOT NULL DEFAULT '',
            `conditions` JSON NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uuid` (`uuid`),
            KEY `name` (`name`),
            KEY `forms_forms_id` (`forms_forms_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
    );
}
if (!$DB->tableExists('glpi_forms_questions')) {
    $DB->doQuery(
        "CREATE TABLE `glpi_forms_questions` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `uuid` varchar(255) NOT NULL DEFAULT '',
            `forms_sections_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `forms_sections_uuid` varchar(255) NOT NULL DEFAULT '',
            `name` varchar(255) NOT NULL DEFAULT '',
            `type` varchar(255) NOT NULL DEFAULT '',
            `is_mandatory` tinyint NOT NULL DEFAULT '0',
            `vertical_rank` int NOT NULL DEFAULT '0',
            `horizontal_rank` int DEFAULT NULL,
            `description` longtext,
            `default_value` text COMMENT 'JSON - The default value type may not be the same for all questions type',
            `extra_data` text COMMENT 'JSON - Extra configuration field(s) depending on the questions type',
            `visibility_strategy` varchar(30) NOT NULL DEFAULT '',
            `conditions` JSON NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uuid` (`uuid`),
            KEY `name` (`name`),
            KEY `forms_sections_id` (`forms_sections_id`),
            KEY `forms_sections_uuid` (`forms_sections_uuid`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
    );
}
if (!$DB->tableExists('glpi_forms_comments')) {
    $DB->doQuery(
        "CREATE TABLE `glpi_forms_comments` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `uuid` varchar(255) NOT NULL DEFAULT '',
            `forms_sections_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `forms_sections_uuid` varchar(255) NOT NULL DEFAULT '',
            `name` varchar(255) NOT NULL DEFAULT '',
            `description` longtext,
            `vertical_rank` int NOT NULL DEFAULT '0',
            `horizontal_rank` int DEFAULT NULL,
            `visibility_strategy` varchar(30) NOT NULL DEFAULT '',
            `conditions` JSON NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uuid` (`uuid`),
            KEY `name` (`name`),
            KEY `forms_sections_id` (`forms_sections_id`),
            KEY `forms_sections_uuid` (`forms_sections_uuid`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
    );
}
if (!$DB->tableExists('glpi_forms_answerssets')) {
    $DB->doQuery(
        "CREATE TABLE `glpi_forms_answerssets` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `forms_forms_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `users_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `name` varchar(255) NOT NULL DEFAULT '',
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            `index` int NOT NULL DEFAULT '0',
            `answers` json COMMENT 'JSON - Answers for each questions of the parent form',
            PRIMARY KEY (`id`),
            KEY `name` (`name`),
            KEY `date_creation` (`date_creation`),
            KEY `date_mod` (`date_mod`),
            KEY `forms_forms_id` (`forms_forms_id`),
            KEY `users_id` (`users_id`),
            KEY `entities_id` (`entities_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
    );
}
if (!$DB->tableExists('glpi_forms_destinations_answerssets_formdestinationitems')) {
    $DB->doQuery(
        "CREATE TABLE `glpi_forms_destinations_answerssets_formdestinationitems` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `forms_answerssets_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `itemtype` varchar(255) NOT NULL,
            `items_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            UNIQUE KEY `unicity` (`forms_answerssets_id`,`itemtype`,`items_id`),
            KEY `item` (`itemtype`, `items_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
    );
}
if (!$DB->tableExists('glpi_forms_destinations_formdestinations')) {
    $DB->doQuery(
        "CREATE TABLE `glpi_forms_destinations_formdestinations` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `forms_forms_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `itemtype` varchar(255) NOT NULL,
            `name` varchar(255) NOT NULL,
            `config` JSON NOT NULL COMMENT 'Extra configuration field(s) depending on the destination type',
            `creation_strategy` varchar(30) NOT NULL DEFAULT '',
            `conditions` JSON NOT NULL,
            PRIMARY KEY (`id`),
            KEY `name` (`name`),
            KEY `itemtype` (`itemtype`),
            KEY `forms_forms_id` (`forms_forms_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
    );
}
if (!$DB->tableExists('glpi_forms_accesscontrols_formaccesscontrols')) {
    $DB->doQuery(
        "CREATE TABLE `glpi_forms_accesscontrols_formaccesscontrols` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `forms_forms_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `strategy` varchar(255) NOT NULL,
            `config` JSON NOT NULL,
            `is_active` tinyint NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            UNIQUE KEY `unicity` (`forms_forms_id`, `strategy`),
            KEY `is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
    );
}
if (!$DB->tableExists('glpi_helpdesks_tiles_items_tiles')) {
    $DB->doQuery(
        "CREATE TABLE `glpi_helpdesks_tiles_items_tiles` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `itemtype_item` varchar(255) DEFAULT NULL,
            `items_id_item` int {$default_key_sign} NOT NULL DEFAULT '0',
            `itemtype_tile` varchar(255) DEFAULT NULL,
            `items_id_tile` int {$default_key_sign} NOT NULL DEFAULT '0',
            `rank` int NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unicity` (`itemtype_item`, `items_id_item`, `rank`),
            UNIQUE KEY `item` (`itemtype_tile`,`items_id_tile`),
            KEY `rank` (`rank`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
    );
}
if (!$DB->tableExists('glpi_helpdesks_tiles_formtiles')) {
    $DB->doQuery(
        "CREATE TABLE `glpi_helpdesks_tiles_formtiles` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `forms_forms_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            KEY `forms_forms_id` (`forms_forms_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
    );
}
if (!$DB->tableExists('glpi_helpdesks_tiles_glpipagetiles')) {
    $DB->doQuery(
        "CREATE TABLE `glpi_helpdesks_tiles_glpipagetiles` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `title` varchar(255) DEFAULT NULL,
            `description` text DEFAULT null,
            `illustration` varchar(255) DEFAULT NULL,
            `page` varchar(255) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
    );
} else {
    // Fix a bad type, description was created as varchar(255) when it should have been a text field.
    $migration->changeField(
        'glpi_helpdesks_tiles_glpipagetiles',
        'description',
        'description',
        'text',
    );
}
if (!$DB->tableExists('glpi_helpdesks_tiles_externalpagetiles')) {
    $DB->doQuery(
        "CREATE TABLE `glpi_helpdesks_tiles_externalpagetiles` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `title` varchar(255) DEFAULT NULL,
            `description` text DEFAULT null,
            `illustration` varchar(255) DEFAULT NULL,
            `url` text DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
    );
} else {
    // Fix a bad type, description was created as varchar(255) when it should have been a text field.
    $migration->changeField(
        'glpi_helpdesks_tiles_externalpagetiles',
        'description',
        'description',
        'text',
    );
}

$field = 'show_tickets_properties_on_helpdesk';
if (!$DB->fieldExists("glpi_entities", $field)) {
    $migration->addField(
        'glpi_entities',
        $field,
        "int NOT NULL DEFAULT '-2'"
    );
    $migration->addPostQuery(
        $DB->buildUpdate(
            'glpi_entities',
            [$field => 1], // Ticket details shown by default for upgraded installations to maintain previous behavior
            ['id' => 0]
        )
    );
}

$fields = ['custom_helpdesk_home_scene_left', 'custom_helpdesk_home_scene_right', 'custom_helpdesk_home_title'];
foreach ($fields as $field) {
    if (!$DB->fieldExists("glpi_entities", $field)) {
        $migration->addField(
            'glpi_entities',
            $field,
            "varchar(255) NOT NULL DEFAULT '-2'"
        );
        $migration->addPostQuery(
            $DB->buildUpdate(
                'glpi_entities',
                [$field => ''],
                ['id' => 0]
            )
        );
    }
}

$fields = [
    'enable_helpdesk_home_search_bar' => 1,
    'enable_helpdesk_service_catalog' => 1,
    'expand_service_catalog'          => 0,
];
foreach ($fields as $field => $default_value) {
    if (!$DB->fieldExists("glpi_entities", $field)) {
        $migration->addField(
            'glpi_entities',
            $field,
            "tinyint NOT NULL DEFAULT -2"
        );
        $migration->addPostQuery(
            $DB->buildUpdate(
                'glpi_entities',
                [$field => $default_value],
                ['id' => 0]
            )
        );
    }
}

if (!$DB->fieldExists('glpi_forms_forms', 'uuid')) {
    $migration->addField(
        'glpi_forms_forms',
        'uuid',
        "varchar(255) NOT NULL DEFAULT ''",
        [
            'after'  => 'id',
            'update' => '(select md5(UUID()))',
        ]
    );
    $migration->addKey(
        'glpi_forms_forms',
        'uuid',
        'uuid',
        'UNIQUE KEY'
    );
}

if (!$DB->fieldExists('glpi_forms_forms', 'submit_button_visibility_strategy')) {
    $migration->addField(
        'glpi_forms_forms',
        'submit_button_visibility_strategy',
        "varchar(30) NOT NULL DEFAULT ''",
        [
            'after' => 'usage_count',
        ]
    );
}

if (!$DB->fieldExists('glpi_forms_forms', 'submit_button_conditions')) {
    $migration->addField(
        'glpi_forms_forms',
        'submit_button_conditions',
        "JSON NOT NULL",
        [
            'after' => 'submit_button_visibility_strategy',
        ]
    );
}

if (!$DB->fieldExists('glpi_forms_questions', 'validation_strategy')) {
    $migration->addField(
        'glpi_forms_questions',
        'validation_strategy',
        "varchar(30) NOT NULL DEFAULT ''",
        [
            'after' => 'conditions',
        ]
    );
}

if (!$DB->fieldExists('glpi_forms_questions', 'validation_conditions')) {
    $migration->addField(
        'glpi_forms_questions',
        'validation_conditions',
        "JSON NOT NULL",
        [
            'after' => 'validation_strategy',
        ]
    );
}

if (!$DB->fieldExists('glpi_forms_forms', 'render_layout')) {
    $migration->addField(
        'glpi_forms_forms',
        'render_layout',
        "varchar(30) NOT NULL DEFAULT ''",
        [
            'after' => 'is_pinned',
        ]
    );
}

// Change the type of the 'answers' field in glpi_forms_answerssets
$migration->changeField(
    'glpi_forms_answerssets',
    'answers',
    'answers',
    "json COMMENT 'JSON - Answers for each questions of the parent form'",
);

// Add rights for the forms object
$migration->addRight("form", ALLSTANDARDRIGHT, ['config' => UPDATE]);

// New config values
$migration->addConfig(['glpi_11_form_migration' => 0]);

// Name (forced), Entities (forced), Child entities, Active, Last update
$ADDTODISPLAYPREF['Glpi\Form\Form'] = [1, 80, 86, 3, 4];
$ADDTODISPLAYPREF['Glpi\Form\AnswersSet'] = [1, 3, 4];

$migration->addCrontask(
    'Glpi\Form\Form',
    'purgedraftforms',
    DAY_TIMESTAMP,
    param: 7
);

// Data format for ValidationField has changed
if ($DB->tableExists('glpi_forms_destinations_formdestinations')) {
    $iterator = $DB->request(['FROM' => 'glpi_forms_destinations_formdestinations']);
    foreach ($iterator as $data) {
        $config = json_decode($data['config'] ?? '[]', true) ?? [];
        $validation_field_key = 'glpi-form-destination-commonitilfield-validationfield';

        // If 'strategies' key is not set, skip this entry
        // We probably already migrated it
        if (!isset($config[$validation_field_key]['strategies'])) {
            continue;
        }

        $strategies                       = $config[$validation_field_key]['strategies'] ?? [];
        $specific_actors                  = $config[$validation_field_key]['specific_actors'] ?? [];
        $specific_question_ids            = $config[$validation_field_key]['specific_question_ids'] ?? [];
        $specific_validationtemplates_ids = $config[$validation_field_key]['specific_validationtemplates_ids'] ?? [];

        $strategy_configs = [];
        foreach ($strategies as $strategy) {
            // Convert strategy to enum value safely
            $strategy_value = match ($strategy) {
                'no_validation'    => 'no_validation',
                'specific_values'  => 'specific_values',
                'specific_actors'  => 'specific_actors',
                'specific_answers' => 'specific_answers',
                default            => 'no_validation' // Default to no validation for unknown strategies
            };

            $strategy_configs[] = [
                'strategy'                        => $strategy_value,
                'specific_validationtemplate_ids' => $specific_validationtemplates_ids,
                'specific_question_ids'           => $specific_question_ids,
                'specific_actors'                 => $specific_actors,
                'specific_validationstep_id'      => 0,
            ];
        }

        // Build the hardcoded structure that would be produced by ValidationFieldConfig::jsonSerialize()
        $config[$validation_field_key] = [
            'strategy_configs' => $strategy_configs,
        ];

        // Update the config field
        $DB->update(
            'glpi_forms_destinations_formdestinations',
            ['config' => json_encode($config)],
            ['id' => $data['id']]
        );
    }
}
