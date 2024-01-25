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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * @var \DBmysql $DB
 * @var \Migration $migration
 */

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

if (!$DB->tableExists('glpi_tickets_contracts')) {
    $query = "CREATE TABLE `glpi_tickets_contracts` (
      `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
      `tickets_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `contracts_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`),
      UNIQUE KEY `unicity` (`tickets_id`,`contracts_id`),
      KEY `contracts_id` (`contracts_id`)
   ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->doQueryOrDie($query, "add table glpi_tickets_contracts");
}

if (!$DB->fieldExists("glpi_entities", "contracts_id_default")) {
    $migration->addField(
        "glpi_entities",
        "contracts_id_default",
        "int {$default_key_sign} NOT NULL DEFAULT 0",
        [
            'after'     => "anonymize_support_agents",
            'value'     => -2,               // Inherit as default value
            'update'    => '0',              // Not enabled for root entity
            'condition' => 'WHERE `id` = 0'
        ]
    );

    $migration->addKey("glpi_entities", "contracts_id_default");
}
