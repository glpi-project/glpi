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
 * @var \DBmysql $DB
 * @var \Migration $migration
 */

// Add pendingreasons_id field
$migration->addField("glpi_itilfollowuptemplates", "pendingreasons_id", "fkey");
$migration->addKey("glpi_itilfollowuptemplates", "pendingreasons_id");

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

if (!$DB->tableExists('glpi_entities_itilfollowuptemplates')) {
    $query = "CREATE TABLE `glpi_entities_itilfollowuptemplates` (
        `id` int unsigned NOT NULL AUTO_INCREMENT,
        `itilfollowuptemplates_id` int unsigned  NOT NULL DEFAULT '0',
        `entities_id` int unsigned  NOT NULL DEFAULT '0',
        `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        KEY `itilfollowuptemplates_id` (`itilfollowuptemplates_id`),
        KEY `entities_id` (`entities_id`),
        KEY `is_recursive` (`is_recursive`)
    ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->doQuery($query);
}

$DB->doQuery("INSERT INTO glpi_entities_itilfollowuptemplates (itilfollowuptemplates_id, entities_id, is_recursive)
SELECT id, entities_id, is_recursive
FROM glpi_itilfollowuptemplates;");

if (!$DB->tableExists('glpi_itilfollowuptemplates_users')) {
    $query = "CREATE TABLE `glpi_itilfollowuptemplates_users` (
        `id` int unsigned NOT NULL AUTO_INCREMENT,
        `itilfollowuptemplates_id` int unsigned  NOT NULL DEFAULT '0',
        `users_id` int unsigned  NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        KEY `itilfollowuptemplates_id` (`itilfollowuptemplates_id`),
        KEY `users_id` (`users_id`)
    ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->doQuery($query);
}

if (!$DB->tableExists('glpi_groups_itilfollowuptemplates')) {
    $query = "CREATE TABLE `glpi_groups_itilfollowuptemplates` (
        `id` int unsigned NOT NULL AUTO_INCREMENT,
        `itilfollowuptemplates_id` int unsigned  NOT NULL DEFAULT '0',
        `groups_id` int unsigned  NOT NULL DEFAULT '0',
        `entities_id` int unsigned  NULL,
        `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
        `no_entity_restriction` tinyint NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        KEY `itilfollowuptemplates_id` (`itilfollowuptemplates_id`),
        KEY `groups_id` (`groups_id`),
        KEY `entities_id` (`entities_id`),
        KEY `is_recursive` (`is_recursive`)
    ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->doQuery($query);
}

if (!$DB->tableExists('glpi_profiles_itilfollowuptemplates')) {
    $query = "CREATE TABLE `glpi_profiles_itilfollowuptemplates` (
        `id` int unsigned NOT NULL AUTO_INCREMENT,
        `itilfollowuptemplates_id` int unsigned  NOT NULL DEFAULT '0',
        `profiles_id` int unsigned  NOT NULL DEFAULT '0',
        `entities_id` int unsigned  NULL,
        `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
        `no_entity_restriction` tinyint NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        KEY `itilfollowuptemplates_id` (`itilfollowuptemplates_id`),
        KEY `profiles_id` (`profiles_id`),
        KEY `entities_id` (`entities_id`),
        KEY `is_recursive` (`is_recursive`)
    ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->doQuery($query);
}
