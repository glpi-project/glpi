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

use Glpi\Toolbox\Sanitizer;

/**
 * @var DB $DB
 * @var Migration $migration
 * @var array $ADDTODISPLAYPREF
 */

$migration->addConfig(\Glpi\Inventory\Conf::getDefaults(), 'inventory');

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

if (!$DB->tableExists('glpi_agenttypes')) {
    $query = "CREATE TABLE `glpi_agenttypes` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `name` varchar(255) DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `name` (`name`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_agenttypes");
    $migration->addPostQuery(
        $DB->buildInsert(
            "glpi_agenttypes",
            [
                'id'           => 1,
                'name'         => 'Core',
            ]
        )
    );
}
if (!$DB->tableExists('glpi_agents')) {
    $query = "CREATE TABLE `glpi_agents` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `deviceid` VARCHAR(255) NOT NULL,
         `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `is_recursive` tinyint NOT NULL DEFAULT '0',
         `name` varchar(255) DEFAULT NULL,
         `agenttypes_id` int {$default_key_sign} NOT NULL,
         `last_contact` timestamp NULL DEFAULT NULL,
         `version` varchar(255) DEFAULT NULL,
         `locked` tinyint NOT NULL DEFAULT '0',
         `itemtype` varchar(100) NOT NULL,
         `items_id` int {$default_key_sign} NOT NULL,
         `useragent` varchar(255) DEFAULT NULL,
         `tag` varchar(255) DEFAULT NULL,
         `port` varchar(6) DEFAULT NULL,
         `threads_networkdiscovery` int NOT NULL DEFAULT '1' COMMENT 'Number of threads for Network Discovery',
         `threads_networkinventory` int NOT NULL DEFAULT '1' COMMENT 'Number of threads for Network Inventory',
         `timeout_networkdiscovery` int NOT NULL DEFAULT '0' COMMENT 'Network Discovery task timeout (disabled by default)',
         `timeout_networkinventory` int NOT NULL DEFAULT '0' COMMENT 'Network Inventory task timeout (disabled by default)',
         PRIMARY KEY (`id`),
         KEY `name` (`name`),
         KEY `entities_id` (`entities_id`),
         KEY `is_recursive` (`is_recursive`),
         KEY `item` (`itemtype`,`items_id`),
         UNIQUE KEY `deviceid` (`deviceid`),
         KEY `agenttypes_id` (`agenttypes_id`)
   ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_agents");
} else {
    $migration->dropKey('glpi_agents', 'items_id');
    $migration->dropKey('glpi_agents', 'itemtype');
    $migration->dropForeignKeyContraint('glpi_agents', 'agenttypes_id');
    $migration->migrationOneTable('glpi_agents');
    $migration->addKey('glpi_agents', 'agenttypes_id');
    $migration->addKey('glpi_agents', 'entities_id');
    $migration->addKey('glpi_agents', 'is_recursive');
    $migration->addKey('glpi_agents', ['itemtype', 'items_id'], 'item');
    $migration->addField(
        'glpi_agents',
        'threads_networkdiscovery',
        'int NOT NULL DEFAULT 1',
        [
            'comment' => 'Number of threads for Network Discovery'
        ]
    );
    $migration->addField(
        'glpi_agents',
        'threads_networkinventory',
        "int NOT NULL DEFAULT '1'",
        [
            'comment' => 'Number of threads for Network Inventory'
        ]
    );
    $migration->addField(
        'glpi_agents',
        'timeout_networkdiscovery',
        "int NOT NULL DEFAULT '0'",
        [
            'comment' => 'Network Discovery task timeout (disabled by default)'
        ]
    );
    $migration->addField(
        'glpi_agents',
        'timeout_networkinventory',
        "int NOT NULL DEFAULT '0'",
        [
            'comment' => 'Network Inventory task timeout (disabled by default)'
        ]
    );
}
$ADDTODISPLAYPREF['Agent'] = [2, 4, 10, 8, 11, 6];

if (!$DB->tableExists('glpi_rulematchedlogs')) {
    $query = "CREATE TABLE `glpi_rulematchedlogs` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `date` timestamp NULL DEFAULT NULL,
         `items_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `itemtype` varchar(100) DEFAULT NULL,
         `rules_id` int {$default_key_sign} NULL DEFAULT NULL,
         `agents_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `method` varchar(255) DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `item` (`itemtype`,`items_id`),
         KEY `agents_id` (`agents_id`),
         KEY `rules_id` (`rules_id`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_rulematchedlogs");
} else {
    $migration->addKey('glpi_rulematchedlogs', 'agents_id');
    $migration->addKey('glpi_rulematchedlogs', 'rules_id');
}

//locked fields
if (!$DB->tableExists('glpi_lockedfields')) {
    $query = "CREATE TABLE `glpi_lockedfields` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `itemtype` varchar(100) DEFAULT NULL,
         `items_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `field` varchar(50) NOT NULL,
         `value` varchar(255) DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         UNIQUE KEY `unicity` (`itemtype`, `items_id`, `field`),
         KEY `date_creation` (`date_creation`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_lockedfields");
} else {
    $migration->dropKey('glpi_lockedfields', 'item');
    $migration->migrationOneTable('glpi_lockedfields');
    $migration->addField('glpi_lockedfields', 'value', 'string');
    $migration->addKey('glpi_lockedfields', 'date_creation');
}
$ADDTODISPLAYPREF['Lockedfield'] = [3, 13, 5];


//transfer configuration per entity
if (!$DB->fieldExists('glpi_entities', 'transfers_id')) {
    $migration->addField(
        'glpi_entities',
        'transfers_id',
        "int {$default_key_sign} NOT NULL DEFAULT 0",
        [
            'value'     => -2,
            // 0 value for root entity
            'update'    => '0',
            'condition' => 'WHERE `id` = 0'
        ]
    );
    $migration->addKey('glpi_entities', 'transfers_id');
}

//agent URL configuration per entity
if (!$DB->fieldExists('glpi_entities', 'agent_base_url')) {
    $migration->addField(
        'glpi_entities',
        'agent_base_url',
        'string'
    );
}

//missing fields in network related tables
if (!$DB->fieldExists('glpi_networkequipments', 'autoupdatesystems_id')) {
    $migration->addField(
        'glpi_networkequipments',
        'autoupdatesystems_id',
        "int {$default_key_sign} NOT NULL DEFAULT '0'",
        [
            'after' => 'date_creation',
        ]
    );
    $migration->addKey('glpi_networkequipments', 'autoupdatesystems_id');
}

if (!$DB->fieldExists('glpi_networkequipments', 'sysdescr')) {
    $migration->addField(
        'glpi_networkequipments',
        'sysdescr',
        'text',
        [
            'after' => 'autoupdatesystems_id',
        ]
    );
}

if (!$DB->fieldExists('glpi_networkequipments', 'cpu')) {
    $migration->addField(
        'glpi_networkequipments',
        'cpu',
        'integer',
        [
            'after' => 'sysdescr',
        ]
    );
}

if (!$DB->fieldExists('glpi_networkequipments', 'uptime')) {
    $migration->addField(
        'glpi_networkequipments',
        'uptime',
        'string',
        [
            'after' => 'cpu',
            'value' => '0'

        ]
    );
}

if (!$DB->fieldExists('glpi_networkequipments', 'last_inventory_update')) {
    $migration->addField(
        'glpi_networkequipments',
        'last_inventory_update',
        'timestamp',
        [
            'after' => 'uptime',
        ]
    );
}

if (!$DB->fieldExists('glpi_printers', 'sysdescr')) {
    $migration->addField(
        'glpi_printers',
        'sysdescr',
        'text',
        [
            'after' => 'date_creation',
        ]
    );
}

if (!$DB->fieldExists('glpi_printers', 'last_inventory_update')) {
    $migration->addField(
        'glpi_printers',
        'last_inventory_update',
        'timestamp',
        [
            'after' => 'sysdescr',
        ]
    );
}

if (!$DB->fieldExists('glpi_computers', 'last_inventory_update')) {
    $migration->addField(
        'glpi_computers',
        'last_inventory_update',
        'timestamp',
        [
            'after' => 'is_recursive',
        ]
    );
}

if (!$DB->fieldExists('glpi_phones', 'last_inventory_update')) {
    $migration->addField(
        'glpi_phones',
        'last_inventory_update',
        'timestamp',
        [
            'after' => 'is_recursive',
        ]
    );
}

//new fields in networkports table
$netport_fields = [
    'ifmtu' => "int NOT NULL DEFAULT '0'",
    'ifspeed'            => "bigint NOT NULL DEFAULT '0'",
    'ifinternalstatus'   => "varchar(255) DEFAULT NULL",
    'ifconnectionstatus' => "int NOT NULL DEFAULT '0'",
    'iflastchange'       => "varchar(255) DEFAULT NULL",
    'ifinbytes'         => "bigint NOT NULL DEFAULT '0'",
    'ifinerrors'         => "bigint NOT NULL DEFAULT '0'",
    'ifoutbytes'        => "bigint NOT NULL DEFAULT '0'",
    'ifouterrors'        => "bigint NOT NULL DEFAULT '0'",
    'ifstatus'           => "varchar(255) DEFAULT NULL",
    'ifdescr'            => "varchar(255) DEFAULT NULL",
    'ifalias'            => "varchar(255) DEFAULT NULL",
    'portduplex'         => "varchar(255) DEFAULT NULL",
    'trunk'              => "tinyint NOT NULL DEFAULT '0'",
    'lastup'             => "timestamp NULL DEFAULT NULL"
];
foreach ($netport_fields as $netport_field => $definition) {
    if (!$DB->fieldExists('glpi_networkports', $netport_field)) {
        $migration->addField('glpi_networkports', $netport_field, $definition);
    }
}

if (!$DB->tableExists('glpi_unmanageds')) {
    $query = "CREATE TABLE `glpi_unmanageds` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `is_recursive` tinyint NOT NULL DEFAULT '0',
         `name` varchar(255) DEFAULT NULL,
         `serial` varchar(255) DEFAULT NULL,
         `otherserial` varchar(255) DEFAULT NULL,
         `contact` varchar(255) DEFAULT NULL,
         `contact_num` varchar(255) DEFAULT NULL,
         `date_mod` timestamp NULL DEFAULT NULL,
         `comment` text,
         `locations_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `networks_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `manufacturers_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `is_deleted` tinyint NOT NULL DEFAULT '0',
         `users_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `groups_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `states_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `is_dynamic` tinyint NOT NULL DEFAULT '0',
         `date_creation` timestamp NULL DEFAULT NULL,
         `autoupdatesystems_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `sysdescr` text,
         `domains_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `agents_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `itemtype` varchar(100) NULL DEFAULT NULL,
         `accepted` tinyint NOT NULL DEFAULT '0',
         `hub` tinyint NOT NULL DEFAULT '0',
         `ip` varchar(255) DEFAULT NULL,
         `snmpcredentials_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         PRIMARY KEY (`id`),
         KEY `name` (`name`),
         KEY `entities_id` (`entities_id`),
         KEY `is_recursive` (`is_recursive`),
         KEY `manufacturers_id` (`manufacturers_id`),
         KEY `groups_id` (`groups_id`),
         KEY `users_id` (`users_id`),
         KEY `locations_id` (`locations_id`),
         KEY `networks_id` (`networks_id`),
         KEY `states_id` (`states_id`),
         KEY `is_deleted` (`is_deleted`),
         KEY `date_mod` (`date_mod`),
         KEY `is_dynamic` (`is_dynamic`),
         KEY `serial` (`serial`),
         KEY `otherserial` (`otherserial`),
         KEY `date_creation` (`date_creation`),
         KEY `autoupdatesystems_id` (`autoupdatesystems_id`),
         KEY `domains_id` (`domains_id`),
         KEY `agents_id` (`agents_id`),
         KEY `snmpcredentials_id` (`snmpcredentials_id`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_unmanageds");
} else {
    $migration->addKey('glpi_unmanageds', 'is_recursive');
}
$ADDTODISPLAYPREF['Unmanaged'] = [2, 4, 3, 5, 7, 10, 18, 14, 15, 9];

if (!$DB->tableExists('glpi_networkporttypes')) {
    $query = "CREATE TABLE `glpi_networkporttypes` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `is_recursive` tinyint NOT NULL DEFAULT '0',
         `value_decimal` int NOT NULL,
         `name` varchar(255) DEFAULT NULL,
         `comment` text,
         `is_importable` tinyint NOT NULL DEFAULT '0',
         `instantiation_type` varchar(255) DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         `date_mod` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `value_decimal` (`value_decimal`),
         KEY `name` (`name`),
         KEY `entities_id` (`entities_id`),
         KEY `is_recursive` (`is_recursive`),
         KEY `is_importable` (`is_importable`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_networkporttypes");
} else {
    $migration->addKey('glpi_networkporttypes', 'is_recursive');
}

$ADDTODISPLAYPREF['NetworkPortType'] = [10, 11, 12];

if (!$DB->tableExists('glpi_networkporttypes') || countElementsInTable(NetworkPortType::getTable()) === 0) {
    if (!$DB->tableExists('glpi_networkporttypes')) {
        $migration->migrationOneTable(NetworkPortType::getTable());
    }
    $default_types = Sanitizer::encodeHtmlSpecialCharsRecursive(NetworkPortType::getDefaults());
    $reference = array_replace(
        $default_types[0],
        array_fill_keys(
            array_keys($default_types[0]),
            new QueryParam()
        )
    );
    $stmt = $DB->prepare($DB->buildInsert(NetworkPortType::getTable(), $reference));
    if (false === $stmt) {
        $msg = "Error preparing statement in table " . NetworkPortType::getTable();
        throw new \RuntimeException($msg);
    }

    $types = str_repeat('s', count($default_types[0]));
    foreach ($default_types as $row) {
        $res = $stmt->bind_param($types, ...array_values($row));
        if (false === $res) {
            $msg = "Error binding params in table " . NetworkPortType::getTable() . "\n";
            $msg .= print_r($row, true);
            throw new \RuntimeException($msg);
        }
        $res = $stmt->execute();
        if (false === $res) {
            $msg = $stmt->error;
            $msg .= "\nError execution statement in table " . NetworkPortType::getTable() . "\n";
            $msg .= print_r($row, true);
            throw new \RuntimeException($msg);
        }
    }
}

$ADDTODISPLAYPREF['NetworkPort'] = [3, 30, 31, 32, 33, 34, 35, 36, 38, 39, 40];

if (!$DB->tableExists('glpi_printers_cartridgeinfos')) {
    $query = "CREATE TABLE `glpi_printers_cartridgeinfos` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `printers_id` int {$default_key_sign} NOT NULL,
         `property` varchar(255)  NOT NULL,
         `value` varchar(255)  NOT NULL,
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `printers_id` (`printers_id`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_printers_cartridgeinfos");
}

if (!$DB->tableExists('glpi_printerlogs')) {
    $query = "CREATE TABLE `glpi_printerlogs` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `printers_id` int {$default_key_sign} NOT NULL,
         `total_pages` int NOT NULL DEFAULT '0',
         `bw_pages` int NOT NULL DEFAULT '0',
         `color_pages` int NOT NULL DEFAULT '0',
         `rv_pages` int NOT NULL DEFAULT '0',
         `prints` int NOT NULL DEFAULT '0',
         `bw_prints` int NOT NULL DEFAULT '0',
         `color_prints` int NOT NULL DEFAULT '0',
         `copies` int NOT NULL DEFAULT '0',
         `bw_copies` int NOT NULL DEFAULT '0',
         `color_copies` int NOT NULL DEFAULT '0',
         `scanned` int NOT NULL DEFAULT '0',
         `faxed` int NOT NULL DEFAULT '0',
         `date` date DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         `date_mod` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         UNIQUE KEY `unicity` (`printers_id`,`date`),
         KEY `date` (`date`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_printerlogs");
} else {
    foreach (['date_creation', 'date_mod'] as $date_field) {
        if (!$DB->fieldExists('glpi_printerlogs', $date_field)) {
            $migration->addField(
                'glpi_printerlogs',
                $date_field,
                'timestamp',
                [
                    'update' => $DB->quoteName('date'),
                ]
            );
            $migration->addKey('glpi_printerlogs', $date_field);
        }
    }
    // In GLPI 10.0.0-rc2 or earlier, `date` had timestamp datatype.
    $migration->changeField('glpi_printerlogs', 'date', 'date', 'date');

    $migration->dropKey('glpi_printerlogs', 'printers_id');

    if (!isIndex('glpi_printerlogs', 'unicity')) {
        // Preserve only last insert for a given date.
        $to_preserve_sql = new \QueryExpression(
            sprintf(
                'SELECT MAX(%s) as %s FROM %s GROUP BY %s, DATE(%s)',
                $DB->quoteName('id'),
                $DB->quoteName('id'),
                $DB->quoteName('glpi_printerlogs'),
                $DB->quoteName('printers_id'),
                $DB->quoteName('date')
            )
        );
        $to_preserve_result = $DB->query($to_preserve_sql->getValue())->fetch_all(MYSQLI_ASSOC);
        if (!empty($to_preserve_result)) { // If there is no entries to preserve, it means that table is empty, and nothing has to be deleted
            $DB->deleteOrDie(
                'glpi_printerlogs',
                [
                    'NOT' => ['id' => array_column($to_preserve_result, 'id')]
                ]
            );
        }
        $migration->addKey('glpi_printerlogs', ['printers_id', 'date'], 'unicity', 'UNIQUE');
    }
}

if (!$DB->tableExists('glpi_networkportconnectionlogs')) {
    $query = "CREATE TABLE `glpi_networkportconnectionlogs` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `date` timestamp NULL DEFAULT NULL,
         `connected` tinyint NOT NULL DEFAULT '0',
         `networkports_id_source` int {$default_key_sign} NOT NULL DEFAULT '0',
         `networkports_id_destination` int {$default_key_sign} NOT NULL DEFAULT '0',
         PRIMARY KEY (`id`),
         KEY `date` (`date`),
         KEY `networkports_id_destination` (`networkports_id_destination`),
         KEY `networkports_id_source` (`networkports_id_source`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_networkportconnectionlogs");
} else {
    $migration->addKey('glpi_networkportconnectionlogs', 'networkports_id_destination');
    $migration->addKey('glpi_networkportconnectionlogs', 'networkports_id_source');
}

if (!$DB->tableExists('glpi_networkportmetrics')) {
    $query = "CREATE TABLE `glpi_networkportmetrics` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `date` date DEFAULT NULL,
         `ifinbytes` bigint NOT NULL DEFAULT '0',
         `ifinerrors` bigint NOT NULL DEFAULT '0',
         `ifoutbytes` bigint NOT NULL DEFAULT '0',
         `ifouterrors` bigint NOT NULL DEFAULT '0',
         `networkports_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `date_creation` timestamp NULL DEFAULT NULL,
         `date_mod` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         UNIQUE KEY `unicity` (`networkports_id`,`date`),
         KEY `date` (`date`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_networkportmetrics");
} else {
    foreach (['date_creation', 'date_mod'] as $date_field) {
        if (!$DB->fieldExists('glpi_networkportmetrics', $date_field)) {
            $migration->addField(
                'glpi_networkportmetrics',
                $date_field,
                'timestamp',
                [
                    'update' => $DB->quoteName('date'),
                ]
            );
            $migration->addKey('glpi_networkportmetrics', $date_field);
        }
    }
    // In GLPI 10.0.0-rc2 or earlier, `date` had timestamp datatype.
    $migration->changeField('glpi_networkportmetrics', 'date', 'date', 'date');

    $migration->dropKey('glpi_networkportmetrics', 'networkports_id');

    if (!isIndex('glpi_networkportmetrics', 'unicity')) {
        // Preserve only last insert for a given date.
        $to_preserve_sql = new \QueryExpression(
            sprintf(
                'SELECT MAX(%s) as %s FROM %s GROUP BY %s, DATE(%s)',
                $DB->quoteName('id'),
                $DB->quoteName('id'),
                $DB->quoteName('glpi_networkportmetrics'),
                $DB->quoteName('networkports_id'),
                $DB->quoteName('date')
            )
        );
        $to_preserve_result = $DB->query($to_preserve_sql->getValue())->fetch_all(MYSQLI_ASSOC);
        if (!empty($to_preserve_result)) { // If there is no entries to preserve, it means that table is empty, and nothing has to be deleted
            $DB->deleteOrDie(
                'glpi_networkportmetrics',
                [
                    'NOT' => ['id' => array_column($to_preserve_result, 'id')]
                ]
            );
        }
        $migration->addKey('glpi_networkportmetrics', ['networkports_id', 'date'], 'unicity', 'UNIQUE');
    }
}

if (!$DB->tableExists('glpi_refusedequipments')) {
    $query = "CREATE TABLE `glpi_refusedequipments` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `name` varchar(255) DEFAULT NULL,
         `itemtype` varchar(100) DEFAULT NULL,
         `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `ip` varchar(255) DEFAULT NULL,
         `mac` varchar(255) DEFAULT NULL,
         `rules_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `method` varchar(255) DEFAULT NULL,
         `serial` varchar(255) DEFAULT NULL,
         `uuid` varchar(255) DEFAULT NULL,
         `agents_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `autoupdatesystems_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `date_creation` timestamp NULL DEFAULT NULL,
         `date_mod` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `entities_id` (`entities_id`),
         KEY `agents_id` (`agents_id`),
         KEY `autoupdatesystems_id` (`autoupdatesystems_id`),
         KEY `rules_id` (`rules_id`),
         KEY `date_creation` (`date_creation`),
         KEY `date_mod` (`date_mod`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_refusedequipments");
} else {
    $migration->addKey('glpi_refusedequipments', 'entities_id');
    $migration->addKey('glpi_refusedequipments', 'agents_id');
    $migration->addKey('glpi_refusedequipments', 'rules_id');
    $migration->addKey('glpi_refusedequipments', 'date_creation');
    $migration->addKey('glpi_refusedequipments', 'date_mod');
    if (!$DB->fieldExists('glpi_refusedequipments', 'autoupdatesystems_id')) {
        $migration->addField(
            'glpi_networkequipments',
            'autoupdatesystems_id',
            "int {$default_key_sign} NOT NULL DEFAULT '0'",
            [
                'after' => 'agents_id'
            ]
        );
        $migration->addKey('glpi_refusedequipments', 'autoupdatesystems_id');
    }
}

$migration->addConfig(['purge_refusedequipment' => 0]);

CronTask::Register(
    'Glpi\Inventory\Inventory',
    'cleantemp',
    1 * DAY_TIMESTAMP,
    [
        'mode'  => CronTask::MODE_EXTERNAL,
        'state' => CronTask::STATE_DISABLE
    ]
);

CronTask::Register(
    'Glpi\Inventory\Inventory',
    'cleanorphans',
    7 * DAY_TIMESTAMP,
    [
        'mode'  => CronTask::MODE_EXTERNAL,
        'state' => CronTask::STATE_WAITING
    ]
);

if (!$DB->tableExists('glpi_usbvendors')) {
    $query = "CREATE TABLE `glpi_usbvendors` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `is_recursive` tinyint NOT NULL DEFAULT '0',
         `vendorid` varchar(4) NOT NULL,
         `deviceid` varchar(4) DEFAULT NULL,
         `name` varchar(255) DEFAULT NULL,
         `comment` text,
         `date_creation` timestamp NULL DEFAULT NULL,
         `date_mod` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `deviceid` (`deviceid`),
         UNIQUE KEY `unicity` (`vendorid`, `deviceid`),
         KEY `name` (`name`),
         KEY `entities_id` (`entities_id`),
         KEY `is_recursive` (`is_recursive`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_usbvendors");
} else {
    $migration->dropKey('glpi_usbvendors', 'vendorid');
    $migration->migrationOneTable('glpi_usbvendors');
    $migration->addKey('glpi_usbvendors', 'is_recursive');
}
$ADDTODISPLAYPREF['USBVendor'] = [10, 11];

if (!$DB->tableExists('glpi_pcivendors')) {
    $query = "CREATE TABLE `glpi_pcivendors` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `is_recursive` tinyint NOT NULL DEFAULT '0',
         `vendorid` varchar(4) NOT NULL,
         `deviceid` varchar(4) DEFAULT NULL,
         `name` varchar(255) DEFAULT NULL,
         `comment` text,
         `date_creation` timestamp NULL DEFAULT NULL,
         `date_mod` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `deviceid` (`deviceid`),
         UNIQUE KEY `unicity` (`vendorid`, `deviceid`),
         KEY `name` (`name`),
         KEY `entities_id` (`entities_id`),
         KEY `is_recursive` (`is_recursive`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_pcivendors");
} else {
    $migration->dropKey('glpi_pcivendors', 'vendorid');
    $migration->migrationOneTable('glpi_pcivendors');
    $migration->addKey('glpi_pcivendors', 'is_recursive');
}
$ADDTODISPLAYPREF['PCIVendor'] = [10, 11];

if (!$DB->tableExists('glpi_snmpcredentials')) {
    $query = "CREATE TABLE `glpi_snmpcredentials` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `name` varchar(64) DEFAULT NULL,
         `snmpversion` varchar(8) NOT NULL DEFAULT '1',
         `community` varchar(255) DEFAULT NULL,
         `username` varchar(255) DEFAULT NULL,
         `authentication` varchar(255) DEFAULT NULL,
         `auth_passphrase` varchar(255) DEFAULT NULL,
         `encryption` varchar(255) DEFAULT NULL,
         `priv_passphrase` varchar(255) DEFAULT NULL,
         `is_deleted` tinyint NOT NULL DEFAULT '0',
         PRIMARY KEY (`id`),
         KEY `name` (`name`),
         KEY `snmpversion` (`snmpversion`),
         KEY `is_deleted` (`is_deleted`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_snmpcredentials");
}
if (countElementsInTable('glpi_snmpcredentials') === 0) {
    $migration->addPostQuery(
        $DB->buildInsert(
            'glpi_snmpcredentials',
            [
                'name'          => 'Public community v1',
                'snmpversion'   => 1,
                'community'     => 'public'
            ]
        )
    );
    $migration->addPostQuery(
        $DB->buildInsert(
            'glpi_snmpcredentials',
            [
                'name'          => 'Public community v2c',
                'snmpversion'   => 2,
                'community'     => 'public'
            ]
        )
    );
}

$cred_tables = ['glpi_printers', 'glpi_networkequipments', 'glpi_unmanageds'];
foreach ($cred_tables as $cred_table) {
    if (!$DB->fieldExists($cred_table, 'snmpcredentials_id')) {
        $migration->addField(
            $cred_table,
            'snmpcredentials_id',
            "int {$default_key_sign} NOT NULL DEFAULT '0'"
        );
        $migration->addKey($cred_table, 'snmpcredentials_id');
    }
}

if (!$DB->fieldExists('glpi_printers', 'autoupdatesystems_id')) {
    $migration->addField(
        'glpi_printers',
        'autoupdatesystems_id',
        "int {$default_key_sign} NOT NULL DEFAULT '0'",
        [
            'after' => 'snmpcredentials_id',
        ]
    );
    $migration->addKey('glpi_printers', 'autoupdatesystems_id');
}

// Other autoupdatesystems_id additions
$autoupdatesystems_tables = ['glpi_databaseinstances', 'glpi_monitors', 'glpi_peripherals', 'glpi_phones'];
foreach ($autoupdatesystems_tables as $autoupdatesystems_table) {
    if (!$DB->fieldExists($autoupdatesystems_table, 'autoupdatesystems_id')) {
        $migration->addField(
            $autoupdatesystems_table,
            'autoupdatesystems_id',
            "int {$default_key_sign} NOT NULL DEFAULT '0'",
            [
                'after' => 'is_dynamic',
            ]
        );
        $migration->addKey($autoupdatesystems_table, 'autoupdatesystems_id');
    }
}

if (countElementsInTable(Blacklist::getTable()) === 4) {
    $stmt = $DB->prepare(
        $DB->buildInsert(
            Blacklist::getTable(),
            [
                'type' => new QueryParam(),
                'name' => new QueryParam(),
                'value' => new QueryParam()
            ]
        )
    );
    if (false === $stmt) {
        $msg = "Error preparing statement in table " . Blacklist::getTable();
        throw new \RuntimeException($msg);
    }

    $types = 'sss';
    foreach (Blacklist::getDefaults() as $type => $values) {
        foreach ($values as $props) {
            $value = $props['value'];
            $name  = $props['name'];

           //defaults already present in database
            if (
                $type == Blacklist::IP && in_array($value, ['0.0.0.0', '127.0.0.1', ''])
                || $type == Blacklist::MAC && $value == ''
            ) {
                continue;
            }
            $res = $stmt->bind_param($types, $type, $name, $value);
            if (false === $res) {
                $msg = "Error binding params in table " . Blacklist::getTable() . "\n";
                $msg .= "type: $type, value: $value";
                throw new \RuntimeException($msg);
            }
            $res = $stmt->execute();
            if (false === $res) {
                $msg = $stmt->error;
                $msg .= "\nError execution statement in table " . Blacklist::getTable() . "\n";
                $msg .= "type: $type, value: $value";
                throw new \RuntimeException($msg);
            }
        }
    }
}

$migration->addRight('inventory', READ);
