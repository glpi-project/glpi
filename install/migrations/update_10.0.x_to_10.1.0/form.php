<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
 * @var DB $DB
 * @var Migration $migration
 * @var array $ADDTODISPLAYPREF
 */

$default_charset   = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();

// Create tables
$migration->createTable(
    "glpi_forms_forms",
    "CREATE TABLE `glpi_forms_forms` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `is_recursive` tinyint NOT NULL DEFAULT '0',
        `is_active` tinyint NOT NULL DEFAULT '0',
        `is_deleted` tinyint NOT NULL DEFAULT '0',
        `name` varchar(255) NOT NULL DEFAULT '',
        `header` text,
        `date_mod` timestamp NULL DEFAULT NULL,
        `date_creation` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `name` (`name`),
        KEY `entities_id` (`entities_id`),
        KEY `is_recursive` (`is_recursive`),
        KEY `is_active` (`is_active`),
        KEY `is_deleted` (`is_deleted`),
        KEY `date_mod` (`date_mod`),
        KEY `date_creation` (`date_creation`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
);
$migration->createTable(
    "glpi_forms_sections",
    "CREATE TABLE `glpi_forms_sections` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `forms_forms_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `name` varchar(255) NOT NULL DEFAULT '',
        `description` text,
        `rank` int NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        KEY `name` (`name`),
        KEY `forms_forms_id` (`forms_forms_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
);
$migration->createTable(
    "glpi_forms_questions",
    "CREATE TABLE `glpi_forms_questions` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `forms_sections_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `name` varchar(255) NOT NULL DEFAULT '',
        `type` varchar(255) NOT NULL DEFAULT '',
        `subtype` varchar(255) NOT NULL DEFAULT '',
        `is_mandatory` tinyint NOT NULL DEFAULT '0',
        `rank` int NOT NULL DEFAULT '0',
        `description` text,
        `default_value` text COMMENT 'JSON - The default value type may not be the same for all questions type',
        `extra_data` text COMMENT 'JSON - Extra configuration field(s) depending on the questions type',
        PRIMARY KEY (`id`),
        KEY `name` (`name`),
        KEY `forms_sections_id` (`forms_sections_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
);
$migration->createTable(
    "glpi_forms_answerssets",
    "CREATE TABLE `glpi_forms_answerssets` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `forms_forms_id` int {$default_key_sign} NOT NULL DEFAULT '0',
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
        KEY `users_id` (`users_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
);

// Name (forced), Entities (forced), Child entities, Active, Last update
$ADDTODISPLAYPREF['Glpi\Form\Form'] = [1, 80, 86, 3, 4];
