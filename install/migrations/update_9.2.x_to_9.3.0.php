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
 * Update from 9.2 to 9.3
 *
 * @return bool
 **/
function update92xto930()
{
    /**
     * @var DBmysql $DB
     * @var Migration $migration
     */
    global $DB, $migration;

    $current_config   = Config::getConfigurationValues('core');
    $updateresult     = true;
    $ADDTODISPLAYPREF = [];

    $migration->setVersion('9.3');

    //Create solutions table
    if (!$DB->tableExists('glpi_itilsolutions')) {
        $query = "CREATE TABLE `glpi_itilsolutions` (
         `id` int NOT NULL AUTO_INCREMENT,
         `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
         `items_id` int NOT NULL DEFAULT '0',
         `solutiontypes_id` int NOT NULL DEFAULT '0',
         `solutiontype_name` varchar(255) NULL DEFAULT NULL,
         `content` longtext COLLATE utf8_unicode_ci,
         `date_creation` datetime DEFAULT NULL,
         `date_mod` datetime DEFAULT NULL,
         `date_approval` datetime DEFAULT NULL,
         `users_id` int NOT NULL DEFAULT '0',
         `user_name` varchar(255) NULL DEFAULT NULL,
         `users_id_editor` int NOT NULL DEFAULT '0',
         `users_id_approval` int NOT NULL DEFAULT '0',
         `user_name_approval` varchar(255) NULL DEFAULT NULL,
         `status` int NOT NULL DEFAULT '1',
         `ticketfollowups_id` int DEFAULT NULL  COMMENT 'Followup reference on reject or approve a ticket solution',
         PRIMARY KEY (`id`),
         KEY `itemtype` (`itemtype`),
         KEY `item_id` (`items_id`),
         KEY `item` (`itemtype`,`items_id`),
         KEY `solutiontypes_id` (`solutiontypes_id`),
         KEY `users_id` (`users_id`),
         KEY `users_id_editor` (`users_id_editor`),
         KEY `users_id_approval` (`users_id_approval`),
         KEY `status` (`status`),
         KEY `ticketfollowups_id` (`ticketfollowups_id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    //add unicity key required for migration only
    $migration->addKey(
        'glpi_itilsolutions',
        ['itemtype', 'items_id', 'date_creation'],
        'migration_unicity',
        'UNIQUE'
    );
    $migration->migrationOneTable('glpi_itilsolutions');

    if ($DB->fieldExists('glpi_tickets', 'solution')) {
        //migrate solution history for tickets
        // TODO can be done when DB::insertOrUpdate() supports SELECT
        $query = "REPLACE INTO `glpi_itilsolutions` (itemtype, items_id, date_creation, users_id, user_name, solutiontypes_id, content, status, date_approval)
               SELECT
                  'Ticket' AS itemtype,
                  ticket.`id` AS items_id,
                  ticket.`solvedate` AS date_creation,
                  IF(log.user_name REGEXP '[(][0-9]+[)]$', SUBSTRING_INDEX(SUBSTRING_INDEX(log.`user_name`, '(', -1), ')', 1), 0) AS users_id,
                  IF(log.user_name REGEXP '[(][0-9]+[)]$', NULL, log.`user_name`) AS user_name,
                  ticket.`solutiontypes_id` AS solutiontypes_id,
                  ticket.`solution` AS content,
                  (CASE
                     WHEN ticket.status = 6 THEN 3   -- if CLOSED, ACCEPTED
                     WHEN ticket.status = 5 THEN 2   -- if SOLVED, WAITING
                     WHEN ticket.status <= 4 THEN 4  -- if INCOMING|ASSIGNED|PLANNED, REFUSED
                     ELSE 1                           -- else NONE
                  END) AS status,
                  ticket.`closedate` AS date_approval
               FROM glpi_tickets AS ticket
               LEFT JOIN `glpi_logs` AS log
                  ON log.`itemtype` = 'Ticket'
                  AND log.`items_id` = ticket.`id`
                  AND log.`id_search_option` = 24
               WHERE
                  LENGTH(ticket.`solution`) > 0
                  OR solutiontypes_id > 0
               GROUP BY ticket.`id`
               ORDER BY ticket.`id` ASC, log.id DESC";
        $DB->doQuery($query);
        $migration->dropField('glpi_tickets', 'solution');
        $migration->dropKey('glpi_tickets', 'solutiontypes_id');
        $migration->dropField('glpi_tickets', 'solutiontypes_id');
    }

    if ($DB->fieldExists('glpi_problems', 'solution')) {
        // Problem soution history
        // TODO can be done when DB::insertOrUpdate() supports SELECT
        $query = "REPLACE INTO `glpi_itilsolutions` (itemtype, items_id, date_creation, users_id, user_name, solutiontypes_id, content, status, date_approval)
               SELECT
                  'Problem' AS itemtype,
                  problem.`id` AS items_id,
                  problem.`solvedate` AS date_creation,
                  IF(log.user_name REGEXP '[(][0-9]+[)]$', SUBSTRING_INDEX(SUBSTRING_INDEX(log.`user_name`, '(', -1), ')', 1), 0) AS users_id,
                  IF(log.user_name REGEXP '[(][0-9]+[)]$', NULL, log.`user_name`) AS user_name,
                  problem.`solutiontypes_id` AS solutiontypes_id,
                  problem.`solution` AS content,
                  (CASE
                     WHEN problem.status = 6 THEN 3   -- if CLOSED, ACCEPTED
                     WHEN problem.status = 5 THEN 2   -- if SOLVED, WAITING
                     WHEN problem.status = 8 THEN 2   -- if OBSERVED, WAITING
                     WHEN problem.status <= 4 THEN 4  -- if INCOMING|ASSIGNED|PLANNED, REFUSED
                     ELSE 1                           -- else NONE
                  END) AS status,
                  problem.`closedate` AS date_approval
               FROM glpi_problems AS problem
               LEFT JOIN `glpi_logs` AS log
                  ON log.`itemtype` = 'Problem'
                  AND log.`items_id` = problem.`id`
                  AND log.`id_search_option` = 24
               WHERE
                  LENGTH(problem.`solution`) > 0
                  OR solutiontypes_id > 0
               GROUP BY problem.`id`
               ORDER BY problem.`id` ASC, log.id DESC";
        $DB->doQuery($query);
        $migration->dropField('glpi_problems', 'solution');
        $migration->dropKey('glpi_problems', 'solutiontypes_id');
        $migration->dropField('glpi_problems', 'solutiontypes_id');
    }

    if ($DB->fieldExists('glpi_changes', 'solution')) {
        // Change solution history
        // TODO can be done when DB::insertOrUpdate() supports SELECT
        $query = "REPLACE INTO `glpi_itilsolutions` (itemtype, items_id, date_creation, users_id, user_name, solutiontypes_id, content, status, date_approval)
               SELECT
                  'Change' AS itemtype,
                  changes.`id` AS items_id,
                  changes.`solvedate` AS date_creation,
                  IF(log.user_name REGEXP '[(][0-9]+[)]$', SUBSTRING_INDEX(SUBSTRING_INDEX(log.`user_name`, '(', -1), ')', 1), 0) AS users_id,
                  IF(log.user_name REGEXP '[(][0-9]+[)]$', NULL, log.`user_name`) AS user_name,
                  changes.`solutiontypes_id` AS solutiontypes_id,
                  changes.`solution` AS content,
                  (CASE
                     WHEN changes.status = 6 THEN 3   -- if CLOSED, ACCEPTED
                     WHEN changes.status = 5 THEN 2   -- if SOLVED, WAITING
                     WHEN changes.status = 8 THEN 2   -- if OBSERVED, WAITING
                     WHEN changes.status <= 4 THEN 4  -- if INCOMING|ASSIGNED|PLANNED, REFUSED
                     ELSE 1                           -- else NONE
                  END) AS status,
                  changes.`closedate` AS date_approval
               FROM glpi_changes AS changes
               LEFT JOIN `glpi_logs` AS log
                  ON log.`itemtype` = 'Change'
                  AND log.`items_id` = changes.`id`
                  AND log.`id_search_option` = 24
               WHERE
                  LENGTH(changes.`solution`) > 0
                  OR solutiontypes_id > 0
               GROUP BY changes.`id`
               ORDER BY changes.`id` ASC, log.id DESC";
        $DB->doQuery($query);
        $migration->dropField('glpi_changes', 'solution');
        $migration->dropKey('glpi_changes', 'solutiontypes_id');
        $migration->dropField('glpi_changes', 'solutiontypes_id');
    }

    //drop migration unicity key
    $migration->dropKey('glpi_itilsolutions', 'migration_unicity');
    $migration->migrationOneTable('glpi_itilsolutions');

    /** Datacenters */
    if (!$DB->tableExists('glpi_datacenters')) {
        $query = "CREATE TABLE `glpi_datacenters` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `entities_id` int NOT NULL DEFAULT '0',
                  `is_recursive` tinyint NOT NULL DEFAULT '0',
                  `locations_id` int NOT NULL DEFAULT '0',
                  `is_deleted` tinyint NOT NULL DEFAULT '0',
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `locations_id` (`locations_id`),
                  KEY `is_deleted` (`is_deleted`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('glpi_dcrooms')) {
        $query = "CREATE TABLE `glpi_dcrooms` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `entities_id` int NOT NULL DEFAULT '0',
                  `is_recursive` tinyint NOT NULL DEFAULT '0',
                  `locations_id` int NOT NULL DEFAULT '0',
                  `vis_cols` int DEFAULT NULL,
                  `vis_rows` int DEFAULT NULL,
                  `blueprint` text COLLATE utf8_unicode_ci,
                  `datacenters_id` int NOT NULL DEFAULT '0',
                  `is_deleted` tinyint NOT NULL DEFAULT '0',
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `locations_id` (`locations_id`),
                  KEY `datacenters_id` (`datacenters_id`),
                  KEY `is_deleted` (`is_deleted`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }
    if (!$DB->fieldExists('glpi_dcrooms', 'blueprint')) {
        $migration->addField('glpi_dcrooms', 'blueprint', 'text', ['after' => 'vis_rows']);
    }

    if (!$DB->tableExists('glpi_rackmodels')) {
        $query = "CREATE TABLE `glpi_rackmodels` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `product_number` (`product_number`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('glpi_racktypes')) {
        $query = "CREATE TABLE `glpi_racktypes` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `entities_id` int NOT NULL DEFAULT '0',
                  `is_recursive` tinyint NOT NULL DEFAULT '0',
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `date_creation` datetime DEFAULT NULL,
                  `date_mod` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `name` (`name`),
                  KEY `date_creation` (`date_creation`),
                  KEY `date_mod` (`date_mod`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('glpi_racks')) {
        $query = "CREATE TABLE `glpi_racks` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `entities_id` int NOT NULL DEFAULT '0',
                  `is_recursive` tinyint NOT NULL DEFAULT '0',
                  `locations_id` int NOT NULL DEFAULT '0',
                  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `rackmodels_id` int DEFAULT NULL,
                  `manufacturers_id` int NOT NULL DEFAULT '0',
                  `racktypes_id` int NOT NULL DEFAULT '0',
                  `states_id` int NOT NULL DEFAULT '0',
                  `users_id_tech` int NOT NULL DEFAULT '0',
                  `groups_id_tech` int NOT NULL DEFAULT '0',
                  `width` int DEFAULT NULL,
                  `height` int DEFAULT NULL,
                  `depth` int DEFAULT NULL,
                  `number_units` int DEFAULT '0',
                  `is_template` tinyint NOT NULL DEFAULT '0',
                  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `is_deleted` tinyint NOT NULL DEFAULT '0',
                  `dcrooms_id` int NOT NULL DEFAULT '0',
                  `room_orientation` int NOT NULL DEFAULT '0',
                  `position` varchar(50),
                  `bgcolor` varchar(7) DEFAULT NULL,
                  `max_power` int NOT NULL DEFAULT '0',
                  `mesured_power` int NOT NULL DEFAULT '0',
                  `max_weight` int NOT NULL DEFAULT '0',
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `locations_id` (`locations_id`),
                  KEY `rackmodels_id` (`rackmodels_id`),
                  KEY `manufacturers_id` (`manufacturers_id`),
                  KEY `racktypes_id` (`racktypes_id`),
                  KEY `states_id` (`states_id`),
                  KEY `users_id_tech` (`users_id_tech`),
                  KEY `group_id_tech` (`groups_id_tech`),
                  KEY `is_template` (`is_template`),
                  KEY `is_deleted` (`is_deleted`),
                  KEY `dcrooms_id` (`dcrooms_id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('glpi_items_racks')) {
        $query = "CREATE TABLE `glpi_items_racks` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `racks_id` int NOT NULL,
                  `itemtype` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `items_id` int NOT NULL,
                  `position` int NOT NULL,
                  `orientation` tinyint,
                  `bgcolor` varchar(7) DEFAULT NULL,
                  `hpos` tinyint NOT NULL DEFAULT '0',
                  `is_reserved` tinyint NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `item` (`itemtype`,`items_id`, `is_reserved`),
                  KEY `relation` (`racks_id`,`itemtype`,`items_id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    $migration->addRight(
        'datacenter',
        CREATE | READ | UPDATE | DELETE  | PURGE | READNOTE | UPDATENOTE | UNLOCK
    );

    //devices models enhancement for datacenters
    $models = [
        'computer',
        'monitor',
        'networkequipment',
        'peripheral',
    ];

    $models_fields = [
        [
            'name'   => 'weight',
            'type'   => "int NOT NULL DEFAULT '0'",
        ], [
            'name'   => 'required_units',
            'type'   => "int NOT NULL DEFAULT '1'",
        ], [
            'name'   => 'depth',
            'type'   => "float NOT NULL DEFAULT 1",
        ], [
            'name'   => 'power_connections',
            'type'   => "int NOT NULL DEFAULT '0'",
        ], [
            'name'   => 'power_consumption',
            'type'   => "int NOT NULL DEFAULT '0'",
        ], [
            'name'   => 'is_half_rack',
            'type'   => "tinyint NOT NULL DEFAULT '0'",
        ], [
            'name'   => 'picture_front',
            'type'   => "text",
        ], [
            'name'   => 'picture_rear',
            'type'   => "text",
        ],
    ];

    foreach ($models as $model) {
        $table = "glpi_{$model}models";
        $after = 'product_number';
        foreach ($models_fields as $field) {
            if (!$DB->fieldExists($table, $field['name'])) {
                $migration->addField(
                    $table,
                    $field['name'],
                    $field['type'],
                    ['after' => $after]
                );
            }
            $after = $field['name'];
        }
    }

    if (!$DB->tableExists('glpi_enclosuremodels')) {
        $query = "CREATE TABLE `glpi_enclosuremodels` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `weight` int NOT NULL DEFAULT '0',
                  `required_units` int NOT NULL DEFAULT '1',
                  `depth` float NOT NULL DEFAULT 1,
                  `power_connections` int NOT NULL DEFAULT '0',
                  `power_consumption` int NOT NULL DEFAULT '0',
                  `is_half_rack` tinyint NOT NULL DEFAULT '0',
                  `picture_front` text COLLATE utf8_unicode_ci,
                  `picture_rear` text COLLATE utf8_unicode_ci,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`),
                  KEY `product_number` (`product_number`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('glpi_enclosures')) {
        $query = "CREATE TABLE `glpi_enclosures` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `entities_id` int NOT NULL DEFAULT '0',
                  `is_recursive` tinyint NOT NULL DEFAULT '0',
                  `locations_id` int NOT NULL DEFAULT '0',
                  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `enclosuremodels_id` int DEFAULT NULL,
                  `users_id_tech` int NOT NULL DEFAULT '0',
                  `groups_id_tech` int NOT NULL DEFAULT '0',
                  `is_template` tinyint NOT NULL DEFAULT '0',
                  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `is_deleted` tinyint NOT NULL DEFAULT '0',
                  `orientation` tinyint,
                  `power_supplies` tinyint NOT NULL DEFAULT '0',
                  `states_id` int NOT NULL DEFAULT '0' COMMENT 'RELATION to states (id)',
                  `comment` text COLLATE utf8_unicode_ci,
                  `manufacturers_id` int NOT NULL DEFAULT '0',
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `locations_id` (`locations_id`),
                  KEY `enclosuremodels_id` (`enclosuremodels_id`),
                  KEY `users_id_tech` (`users_id_tech`),
                  KEY `group_id_tech` (`groups_id_tech`),
                  KEY `is_template` (`is_template`),
                  KEY `is_deleted` (`is_deleted`),
                  KEY `states_id` (`states_id`),
                  KEY `manufacturers_id` (`manufacturers_id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('glpi_items_enclosures')) {
        $query = "CREATE TABLE `glpi_items_enclosures` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `enclosures_id` int NOT NULL,
                  `itemtype` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `items_id` int NOT NULL,
                  `position` int NOT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `item` (`itemtype`,`items_id`),
                  KEY `relation` (`enclosures_id`,`itemtype`,`items_id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('glpi_pdumodels')) {
        $query = "CREATE TABLE `glpi_pdumodels` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `product_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `weight` int NOT NULL DEFAULT '0',
                  `required_units` int NOT NULL DEFAULT '1',
                  `depth` float NOT NULL DEFAULT 1,
                  `power_connections` int NOT NULL DEFAULT '0',
                  `max_power` int NOT NULL DEFAULT '0',
                  `is_half_rack` tinyint NOT NULL DEFAULT '0',
                  `picture_front` text COLLATE utf8_unicode_ci,
                  `picture_rear` text COLLATE utf8_unicode_ci,
                  `is_rackable` tinyint NOT NULL DEFAULT '0',
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `is_rackable` (`is_rackable`),
                  KEY `product_number` (`product_number`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->doQuery($query);
    }
    if ($DB->fieldExists('glpi_pdumodels', 'power_consumption')) {
        $migration->changeField(
            'glpi_pdumodels',
            'power_consumption',
            'max_power',
            'integer',
            ['default' => 0]
        );
    }

    if (!$DB->tableExists('glpi_pdutypes')) {
        $query = "CREATE TABLE `glpi_pdutypes` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `entities_id` int NOT NULL DEFAULT '0',
                  `is_recursive` tinyint NOT NULL DEFAULT '0',
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `date_creation` datetime DEFAULT NULL,
                  `date_mod` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `name` (`name`),
                  KEY `date_creation` (`date_creation`),
                  KEY `date_mod` (`date_mod`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('glpi_pdus')) {
        $query = "CREATE TABLE `glpi_pdus` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `entities_id` int NOT NULL DEFAULT '0',
                  `is_recursive` tinyint NOT NULL DEFAULT '0',
                  `locations_id` int NOT NULL DEFAULT '0',
                  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `pdumodels_id` int DEFAULT NULL,
                  `users_id_tech` int NOT NULL DEFAULT '0',
                  `groups_id_tech` int NOT NULL DEFAULT '0',
                  `is_template` tinyint NOT NULL DEFAULT '0',
                  `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `is_deleted` tinyint NOT NULL DEFAULT '0',
                  `states_id` int NOT NULL DEFAULT '0' COMMENT 'RELATION to states (id)',
                  `comment` text COLLATE utf8_unicode_ci,
                  `manufacturers_id` int NOT NULL DEFAULT '0',
                  `pdutypes_id` int NOT NULL DEFAULT '0',
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `locations_id` (`locations_id`),
                  KEY `pdumodels_id` (`pdumodels_id`),
                  KEY `users_id_tech` (`users_id_tech`),
                  KEY `group_id_tech` (`groups_id_tech`),
                  KEY `is_template` (`is_template`),
                  KEY `is_deleted` (`is_deleted`),
                  KEY `states_id` (`states_id`),
                  KEY `manufacturers_id` (`manufacturers_id`),
                  KEY `pdutypes_id` (`pdutypes_id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('glpi_plugs')) {
        $query = "CREATE TABLE `glpi_plugs` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('glpi_pdus_plugs')) {
        $query = "CREATE TABLE `glpi_pdus_plugs` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `plugs_id` int NOT NULL DEFAULT '0',
                  `pdus_id` int NOT NULL DEFAULT '0',
                  `number_plugs` int DEFAULT '0',
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `plugs_id` (`plugs_id`),
                  KEY `pdus_id` (`pdus_id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->doQuery($query);
    }

    if (!countElementsInTable('glpi_plugs')) {
        $plugs = ['C13', 'C15', 'C19'];
        foreach ($plugs as $plug) {
            $migration->addPostQuery(
                $DB->buildInsert('glpi_plugs', ['name' => $plug])
            );
        }
    }

    if (!$DB->tableExists('glpi_pdus_racks')) {
        $query = "CREATE TABLE `glpi_pdus_racks` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `racks_id` int NOT NULL DEFAULT '0',
                  `pdus_id` int NOT NULL DEFAULT '0',
                  `side` int DEFAULT '0',
                  `position` int NOT NULL,
                  `bgcolor` varchar(7) DEFAULT NULL,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `racks_id` (`racks_id`),
                  KEY `pdus_id` (`pdus_id`)
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->doQuery($query);
    }

    $migration->addField('glpi_states', 'is_visible_rack', 'bool', ['value' => 1,
        'after' => 'is_visible_certificate',
    ]);
    $migration->addKey('glpi_states', 'is_visible_rack');

    $ADDTODISPLAYPREF['Datacenter'] = [3];
    $ADDTODISPLAYPREF['Rack']       = [31, 23, 5, 7];
    $ADDTODISPLAYPREF['DCRoom']     = [4, 5, 6];
    $ADDTODISPLAYPREF['PDU']        = [31, 23, 5];
    $ADDTODISPLAYPREF['Enclosure']  = [31, 23, 5];

    /** /Datacenters */

    /** Add address to locations */
    if (!$DB->fieldExists('glpi_locations', 'address')) {
        $migration->addField(
            'glpi_locations',
            'address',
            'text',
            ['after' => 'sons_cache']
        );
    }

    if (!$DB->fieldExists('glpi_locations', 'postcode')) {
        $migration->addField(
            'glpi_locations',
            'postcode',
            'string',
            ['after' => 'address']
        );
    }

    if (!$DB->fieldExists('glpi_locations', 'town')) {
        $migration->addField(
            'glpi_locations',
            'town',
            'string',
            ['after' => 'postcode']
        );
    }

    if (!$DB->fieldExists('glpi_locations', 'state')) {
        $migration->addField(
            'glpi_locations',
            'state',
            'string',
            ['after' => 'town']
        );
    }

    if (!$DB->fieldExists('glpi_locations', 'country')) {
        $migration->addField(
            'glpi_locations',
            'country',
            'string',
            ['after' => 'state']
        );
    }
    /** /Add address to locations */

    /** Innodb */
    foreach (['glpi_knowbaseitemtranslations', 'glpi_knowbaseitems'] as $table) {
        foreach (['name', 'answer'] as $key) {
            $migration->addKey(
                $table,
                $key,
                $key,
                'FULLTEXT'
            );
        }
    }

    /** Migrate computerdisks to items_disks */
    if (!$DB->tableExists('glpi_items_disks') && $DB->tableExists('glpi_computerdisks')) {
        $migration->renameTable('glpi_computerdisks', 'glpi_items_disks');
    }
    if ($DB->fieldExists('glpi_items_disks', 'computers_id')) {
        $migration->dropField('glpi_items_disks', 'items_id');
        $migration->dropKey('glpi_items_disks', 'computers_id');
        $migration->changeField(
            'glpi_items_disks',
            'computers_id',
            'items_id',
            'integer'
        );
        $migration->addKey('glpi_items_disks', 'items_id');
    }
    if (!$DB->fieldExists('glpi_items_disks', 'itemtype')) {
        $migration->addField('glpi_items_disks', 'itemtype', 'string', ['after' => 'entities_id']);
    }
    $migration->addKey('glpi_items_disks', 'itemtype');
    $migration->addKey('glpi_items_disks', ['itemtype', 'items_id'], 'item');
    $migration->addPostQuery(
        $DB->buildUpdate(
            'glpi_items_disks',
            ['itemtype' => 'Computer'],
            ['itemtype' => null]
        )
    );
    /** /Migrate computerdisks to items_disks */

    /** Add Item_Device* display preferences */
    $itemDeviceTypes = Item_Devices::getDeviceTypes();
    foreach ($itemDeviceTypes as $itemDeviceType) {
        $optToAdd = [];

        // Serial number
        $itemDeviceSpecificities = $itemDeviceType::getSpecificities();
        if (array_key_exists('serial', $itemDeviceSpecificities)) {
            $optToAdd[] = $itemDeviceSpecificities['serial']['id'];
        }

        // Parent device.
        $optToAdd[] = 4;
        // Associated item.
        $optToAdd[] = 5;
        // Associated itemtype.
        $optToAdd[] = 6;

        $ADDTODISPLAYPREF[$itemDeviceType] = $optToAdd;
    }
    /** /Add Item_Device* display preferences */

    foreach ($ADDTODISPLAYPREF as $type => $tab) {
        $rank = 1;
        foreach ($tab as $newval) {
            $DB->updateOrInsert("glpi_displaypreferences", [
                'rank'      => $rank++,
            ], [
                'users_id'  => "0",
                'itemtype'  => $type,
                'num'       => $newval,
            ]);
        }
    }

    // upgrade for users multi-domains
    if (!isIndex('glpi_users', 'unicityloginauth')) {
        $migration->dropKey("glpi_users", "unicity");
        $migration->addKey(
            'glpi_users',
            ['name', 'authtype', 'auths_id'],
            'unicityloginauth',
            'UNIQUE'
        );
    }
    $migration->addField('glpi_authldaps', 'inventory_domain', 'string');
    $migration->addPostQuery(
        $DB->buildUpdate(
            "glpi_users",
            ["glpi_users.authtype" => 1],
            ["glpi_users.authtype" => 0]
        )
    );

    //Permit same license several times on same computer
    $migration->dropKey('glpi_computers_softwarelicenses', 'unicity');

    /** Logs purge */
    $purge_params = [
        'purge_computer_software_install',
        'purge_software_computer_install',
        'purge_software_version_install',
        'purge_infocom_creation',
        'purge_profile_user',
        'purge_group_user',
        'purge_adddevice',
        'purge_updatedevice',
        'purge_deletedevice',
        'purge_connectdevice',
        'purge_disconnectdevice',
        'purge_userdeletedfromldap',
        'purge_addrelation',
        'purge_deleterelation',
        'purge_createitem',
        'purge_deleteitem',
        'purge_restoreitem',
        'purge_updateitem',
        'purge_comments',
        'purge_datemod',
        'purge_all',
        'purge_user_auth_changes',
        'purge_plugins',
    ];

    $purge_plugin_values = [];
    if ($DB->tableExists('glpi_plugin_purgelogs_configs')) {
        $purge_plugin_values = iterator_to_array(
            $DB->request(['FROM' => 'glpi_plugin_purgelogs_configs'])
        )[1];
    }

    $configs_toadd = [];
    foreach ($purge_params as $purge_param) {
        if (!isset($current_config[$purge_param])) {
            $value = $purge_plugin_values[$purge_param] ?? 0;
            $configs_toadd[$purge_param] = $value;
        }
    }

    if (count($configs_toadd)) {
        $migration->addConfig($configs_toadd);
    }

    if (isset($configs_toadd['purge_plugins']) && count($purge_plugin_values)) {
        $migration->addWarningMessage(
            'There are changes on plugins logs purge between core and the old plugin. Please review your configuration.'
        );
    }

    $migration->addCrontask(
        'PurgeLogs',
        'PurgeLogs',
        7 * DAY_TIMESTAMP,
        param: 24,
    );
    /** /Logs purge */

    /** Clean item rack relation on deleted items */
    $iterator = $DB->request(['FROM' => Item_Rack::getTable()]);
    foreach ($iterator as $row) {
        $exists = $DB->request([
            'FROM'   => getTableForItemType($row['itemtype']),
            'WHERE'  => ['id' => $row['items_id']],
        ]);
        if (!count($exists)) {
            $DB->delete(
                Item_Rack::getTable(),
                [
                    'id' => $row['id'],
                ]
            );
        }
    }
    /** /Clean item rack relation on deleted items */

    // ************ Keep it at the end **************
    $migration->executeMigration();

    return $updateresult;
}
