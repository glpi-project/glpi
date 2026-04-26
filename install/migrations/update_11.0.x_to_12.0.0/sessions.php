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
 * @var Migration $migration
 * @var DBmysql $DB
 */

if (!$DB->tableExists('glpi_user_sessions')) {
    $DB->doQuery(<<<SQL
        CREATE TABLE `glpi_user_sessions` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `users_id` int unsigned NOT NULL,
            `session_token_hash` varchar(64) NOT NULL,
            `session_file` varchar(261) NOT NULL COMMENT 'Session filename. PHP allows up to 256 characters for session IDs + the "sess_" prefix used by default.',
            `ip_address` varchar(45) NOT NULL,
            `user_agent` varchar(512) NOT NULL,
            `auth_type` tinyint NOT NULL,
            `created_at` timestamp NOT NULL,
            `last_activity_at` timestamp NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `session_token_hash` (`session_token_hash`),
            KEY `users_id` (`users_id`),
            KEY `last_activity_at` (`last_activity_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
SQL);
}

if (!$DB->tableExists('glpi_user_session_history')) {
    $DB->doQuery(<<<SQL
        CREATE TABLE `glpi_user_session_history` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `users_id` int unsigned NOT NULL,
            `session_token_hash` varchar(64) NOT NULL,
            `ip_address` varchar(45) NOT NULL,
            `user_agent` varchar(512) NOT NULL,
            `auth_type` tinyint NOT NULL,
            `logged_in_at` timestamp NOT NULL,
            `logged_out_at` timestamp NULL DEFAULT NULL,
            `logout_reason` enum('user', 'admin', 'expired') DEFAULT NULL,
            `users_id_revoked_by` int unsigned DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `session_token_hash` (`session_token_hash`),
            KEY `users_id` (`users_id`, `logged_in_at` DESC),
            KEY `users_id_revoked_by` (`users_id_revoked_by`),
            KEY `logged_out_at` (`logged_out_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
SQL);
}
