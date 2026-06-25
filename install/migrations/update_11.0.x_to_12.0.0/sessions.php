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

if (!$DB->tableExists('glpi_usertokens')) {
    $DB->doQuery(<<<SQL
        CREATE TABLE `glpi_usertokens` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `users_id` int unsigned NOT NULL,
            `type` varchar(64) NOT NULL,
            `token_uid` char(16) NOT NULL,
            `token_hash` varchar(255) NOT NULL,
            `date_expiration` timestamp NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `token_uid` (`token_uid`),
            KEY `users_id` (`users_id`),
            KEY `type` (`type`),
            KEY `date_expiration` (`date_expiration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
SQL);
}

$migration->dropField('glpi_users', 'cookie_token');
$migration->dropField('glpi_users', 'cookie_token_date');
