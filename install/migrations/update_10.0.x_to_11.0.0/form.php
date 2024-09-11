<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
 * @var DBmysql $DB
 * @var Migration $migration
 * @var array $ADDTODISPLAYPREF
 */

$default_charset   = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();

// Create tables
if (!$DB->tableExists('glpi_forms_forms')) {
    $DB->doQueryOrDie(
        "CREATE TABLE `glpi_forms_forms` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `is_recursive` tinyint NOT NULL DEFAULT '0',
            `is_active` tinyint NOT NULL DEFAULT '0',
            `is_deleted` tinyint NOT NULL DEFAULT '0',
            `is_draft` tinyint NOT NULL DEFAULT '0',
            `name` varchar(255) NOT NULL DEFAULT '',
            `header` longtext,
            `icon` varchar(255) NOT NULL DEFAULT '',
            `description` longtext,
            `date_mod` timestamp NULL DEFAULT NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `name` (`name`),
            KEY `entities_id` (`entities_id`),
            KEY `is_recursive` (`is_recursive`),
            KEY `is_active` (`is_active`),
            KEY `is_deleted` (`is_deleted`),
            KEY `is_draft` (`is_draft`),
            KEY `date_mod` (`date_mod`),
            KEY `date_creation` (`date_creation`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
    );
}
if (!$DB->tableExists('glpi_forms_sections')) {
    $DB->doQueryOrDie(
        "CREATE TABLE `glpi_forms_sections` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `forms_forms_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `name` varchar(255) NOT NULL DEFAULT '',
            `description` longtext,
            `rank` int NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            KEY `name` (`name`),
            KEY `forms_forms_id` (`forms_forms_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
    );
}
if (!$DB->tableExists('glpi_forms_questions')) {
    $DB->doQueryOrDie(
        "CREATE TABLE `glpi_forms_questions` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `forms_sections_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `name` varchar(255) NOT NULL DEFAULT '',
            `type` varchar(255) NOT NULL DEFAULT '',
            `is_mandatory` tinyint NOT NULL DEFAULT '0',
            `rank` int NOT NULL DEFAULT '0',
            `description` longtext,
            `default_value` text COMMENT 'JSON - The default value type may not be the same for all questions type',
            `extra_data` text COMMENT 'JSON - Extra configuration field(s) depending on the questions type',
            PRIMARY KEY (`id`),
            KEY `name` (`name`),
            KEY `forms_sections_id` (`forms_sections_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
    );
}
if (!$DB->tableExists('glpi_forms_comments')) {
    $DB->doQueryOrDie(
        "CREATE TABLE `glpi_forms_comments` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `forms_sections_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `name` varchar(255) NOT NULL DEFAULT '',
            `description` longtext,
            `rank` int NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            KEY `name` (`name`),
            KEY `forms_sections_id` (`forms_sections_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
    );
}
if (!$DB->tableExists('glpi_forms_answerssets')) {
    $DB->doQueryOrDie(
        "CREATE TABLE `glpi_forms_answerssets` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `forms_forms_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `users_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `name` varchar(255) NOT NULL DEFAULT '',
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            `index` int NOT NULL DEFAULT '0',
            `answers` text COMMENT 'JSON - Answers for each questions of the parent form',
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
    $DB->doQueryOrDie(
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
    $DB->doQueryOrDie(
        "CREATE TABLE `glpi_forms_destinations_formdestinations` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `forms_forms_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `itemtype` varchar(255) NOT NULL,
            `name` varchar(255) NOT NULL,
            `config` JSON NOT NULL COMMENT 'Extra configuration field(s) depending on the destination type',
            PRIMARY KEY (`id`),
            KEY `name` (`name`),
            KEY `itemtype` (`itemtype`),
            KEY `forms_forms_id` (`forms_forms_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
    );
}
if (!$DB->tableExists('glpi_forms_accesscontrols_formaccesscontrols')) {
    $DB->doQueryOrDie(
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

// Add rights for the forms object
$migration->addRight("form", ALLSTANDARDRIGHT, ['config' => UPDATE]);

// Name (forced), Entities (forced), Child entities, Active, Last update
$ADDTODISPLAYPREF['Glpi\Form\Form'] = [1, 80, 86, 3, 4];
$ADDTODISPLAYPREF['Glpi\Form\AnswersSet'] = [1, 3, 4];

// Temporary migration code to cover dev migrations
// TODO: Should be removed from the final release
if (GLPI_VERSION == "11.0.0-dev") {
    $migration->addField("glpi_forms_forms", "is_draft", "bool");
    $migration->addKey("glpi_forms_forms", "is_draft");
    $migration->changeField("glpi_forms_forms", "header", "header", "longtext");
    $migration->changeField("glpi_forms_sections", "description", "description", "longtext");
    $migration->changeField("glpi_forms_questions", "description", "description", "longtext");

    // Deletion of subtype, use final type in `type` field instead
    if ($DB->fieldExists("glpi_forms_questions", "subtype")) {
        $migration->dropField("glpi_forms_questions", "subtype");

        // Set a concrete type instead the "parent type" that was used before this migration
        $questions = $DB->request([
            'SELECT' => ['id', 'type'],
            'FROM' => 'glpi_forms_questions',
        ]);
        foreach ($questions as $question) {
            if ($question['type'] == "Glpi\Form\QuestionType\AbstractQuestionTypeShortAnswer") {
                // Default subtype for short answers
                $new_type = "Glpi\Form\QuestionType\QuestionTypeShortText";
            } elseif ($question['type'] == "Glpi\Form\QuestionType\QuestionTypeLongText") {
                // Long answers have no sub types, use parent
                $new_type = $question['type'];
            } else {
                // Unknown type
                continue;
            }

            $migration->addPostQuery($DB->buildUpdate(
                'glpi_forms_questions',
                ['type' => $new_type],
                ['id' => $question['id']]
            ));
        }
    }

    $migration->addField("glpi_forms_answerssets", "entities_id", "fkey");
    $migration->addKey("glpi_forms_answerssets", "entities_id");
    $migration->addField("glpi_forms_destinations_formdestinations", "config", "JSON NOT NULL COMMENT 'Extra configuration field(s) depending on the destination type'");

    $migration->addField("glpi_forms_forms", "icon", "string");
    $migration->addField("glpi_forms_forms", "description", "text");
}

CronTask::register('Glpi\Form\Form', 'purgedraftforms', DAY_TIMESTAMP, [
    'state'         => CronTask::STATE_WAITING,
    'mode'          => CronTask::MODE_INTERNAL,
    'hourmin'       => 0,
    'hourmax'       => 24,
    'logs_lifetime' => 30,
    'param'         => 7
]);
