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

// Append-only log of the Y.js updates of the articles being edited together.
if (!$DB->tableExists('glpi_knowbaseitems_collabupdates')) {
    $DB->doQuery("CREATE TABLE `glpi_knowbaseitems_collabupdates` (
        `id`               int unsigned NOT NULL AUTO_INCREMENT,
        `knowbaseitems_id` int unsigned NOT NULL DEFAULT '0',
        `client_id`        bigint unsigned NOT NULL DEFAULT '0',
        `data`             mediumtext,
        `date_creation`    timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `knowbaseitems_id` (`knowbaseitems_id`),
        KEY `date_creation` (`date_creation`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;");
}

// Last Y.js awareness state (cursor, user) of each connected editor.
if (!$DB->tableExists('glpi_knowbaseitems_collabawareness')) {
    $DB->doQuery("CREATE TABLE `glpi_knowbaseitems_collabawareness` (
        `id`               int unsigned NOT NULL AUTO_INCREMENT,
        `knowbaseitems_id` int unsigned NOT NULL DEFAULT '0',
        `client_id`        bigint unsigned NOT NULL DEFAULT '0',
        `users_id`         int unsigned NOT NULL DEFAULT '0',
        `data`             text,
        `date_mod`         timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unicity` (`knowbaseitems_id`,`client_id`),
        KEY `users_id` (`users_id`),
        KEY `date_mod` (`date_mod`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;");
}
