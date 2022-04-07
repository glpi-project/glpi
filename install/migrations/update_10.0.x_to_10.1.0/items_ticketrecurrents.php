<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

/**
 * @var DB $DB
 * @var Migration $migration
 */

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

// Change Ticket recurent items
// Add glpi_items_ticketrecurrents table for associated elements
if (!$DB->tableExists('glpi_items_ticketrecurrents')) {
    $query = "CREATE TABLE `glpi_items_ticketrecurrents` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `itemtype` varchar(255) DEFAULT NULL,
        `items_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `ticketrecurrents_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        UNIQUE KEY `unicity` (`itemtype`,`items_id`,`ticketrecurrents_id`),
        KEY `items_id` (`items_id`),
        KEY `ticketrecurrents_id` (`ticketrecurrents_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
    $DB->queryOrDie($query, "10.1.0 add table glpi_items_ticket");
}

// Add glpi_items_ticketrecurrents table for associated elements
if (!$DB->fieldExists('glpi_ticketrecurrents', 'ticket_per_item')) {
    $migration->addField('glpi_ticketrecurrents', 'ticket_per_item', 'bool');
}
