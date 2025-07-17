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
 * @var array $ADDTODISPLAYPREF
 * @var DBmysql $DB
 * @var Migration $migration
 */

use function Safe\json_decode;
use function Safe\json_encode;

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

if (!$DB->tableExists('glpi_assets_assetdefinitions')) {
    $query = <<<SQL
        CREATE TABLE `glpi_assets_assetdefinitions` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `system_name` varchar(255) DEFAULT NULL,
            `label` varchar(255) NOT NULL,
            `icon` varchar(255) DEFAULT NULL,
            `picture` text,
            `comment` text,
            `is_active` tinyint NOT NULL DEFAULT '0',
            `capacities` JSON NOT NULL,
            `profiles` JSON NOT NULL,
            `translations` JSON NOT NULL,
            `fields_display` JSON NOT NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE `system_name` (`system_name`),
            KEY `label` (`label`),
            KEY `is_active` (`is_active`),
            KEY `date_creation` (`date_creation`),
            KEY `date_mod` (`date_mod`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
    $DB->doQuery($query);
} else {
    foreach (['profiles', 'translations', 'fields_display'] as $field) {
        $migration->addField('glpi_assets_assetdefinitions', $field, 'JSON NOT NULL', ['update' => "'[]'"]);
    }
    $migration->addField('glpi_assets_assetdefinitions', 'label', 'string', [
        'after' => 'system_name',
        'update' => $DB::quoteName('system_name'),
    ]);
    $migration->addKey('glpi_assets_assetdefinitions', 'label');
    $migration->addField('glpi_assets_assetdefinitions', 'picture', 'text');

    // Convert capacities for a classname list to an object list.
    $definitions_iterator = $DB->request(['FROM' => 'glpi_assets_assetdefinitions']);
    foreach ($definitions_iterator as $definition_data) {
        $capacities_current = json_decode($definition_data['capacities']);
        if (!is_array($capacities_current)) {
            continue; // Unexpected value
        }

        $capacities_normalized = array_map(
            fn($capacity) => is_string($capacity) ? ['name' => $capacity, 'config' => []] : $capacity,
            $capacities_current
        );
        if ($capacities_normalized !== $capacities_current) {
            $migration->addPostQuery(
                $DB->buildUpdate(
                    'glpi_assets_assetdefinitions',
                    ['capacities' => json_encode($capacities_normalized)],
                    ['id' => $definition_data['id']]
                )
            );
        }
    }

    // Add `Asset` suffix to custom asset classes.
    foreach ($definitions_iterator as $definition_data) {
        $migration->renameItemtype(
            'Glpi\\CustomAsset\\' . $definition_data['system_name'],
            'Glpi\\CustomAsset\\' . $definition_data['system_name'] . 'Asset',
            false
        );
    }
}

$ADDTODISPLAYPREF['Glpi\\Asset\\AssetDefinition'] = [2, 3, 4, 5, 6];

if (!$DB->tableExists('glpi_assets_assets')) {
    $query = <<<SQL
        CREATE TABLE `glpi_assets_assets` (
            `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
            `assets_assetdefinitions_id` int {$default_key_sign} NOT NULL,
            `assets_assetmodels_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `assets_assettypes_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `name` varchar(255) DEFAULT NULL,
            `uuid` varchar(255) DEFAULT NULL,
            `comment` text,
            `serial` varchar(255) DEFAULT NULL,
            `otherserial` varchar(255) DEFAULT NULL,
            `contact` varchar(255) DEFAULT NULL,
            `contact_num` varchar(255) DEFAULT NULL,
            `users_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `users_id_tech` int {$default_key_sign} NOT NULL DEFAULT '0',
            `locations_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `manufacturers_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `states_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
            `is_recursive` tinyint NOT NULL DEFAULT '0',
            `is_deleted` tinyint NOT NULL DEFAULT '0',
            `is_template` tinyint NOT NULL DEFAULT '0',
            `is_dynamic` tinyint NOT NULL DEFAULT '0',
            `template_name` varchar(255) DEFAULT NULL,
            `autoupdatesystems_id` int unsigned NOT NULL DEFAULT '0',
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            `last_inventory_update` timestamp NULL DEFAULT NULL,
            `custom_fields` json,
            PRIMARY KEY (`id`),
            KEY `assets_assetdefinitions_id` (`assets_assetdefinitions_id`),
            KEY `assets_assetmodels_id` (`assets_assetmodels_id`),
            KEY `assets_assettypes_id` (`assets_assettypes_id`),
            KEY `name` (`name`),
            KEY `uuid` (`uuid`),
            KEY `users_id` (`users_id`),
            KEY `users_id_tech` (`users_id_tech`),
            KEY `locations_id` (`locations_id`),
            KEY `manufacturers_id` (`manufacturers_id`),
            KEY `states_id` (`states_id`),
            KEY `entities_id` (`entities_id`),
            KEY `is_recursive` (`is_recursive`),
            KEY `is_deleted` (`is_deleted`),
            KEY `is_template` (`is_template`),
            KEY `is_dynamic` (`is_dynamic`),
            KEY `autoupdatesystems_id` (`autoupdatesystems_id`),
            KEY `date_creation` (`date_creation`),
            KEY `date_mod` (`date_mod`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
    $DB->doQuery($query);
} else {
    $migration->addField('glpi_assets_assets', 'assets_assetmodels_id', 'fkey');
    $migration->addKey('glpi_assets_assets', 'assets_assetmodels_id');
    $migration->addField('glpi_assets_assets', 'assets_assettypes_id', 'fkey');
    $migration->addKey('glpi_assets_assets', 'assets_assettypes_id');
    $migration->addField('glpi_assets_assets', 'is_template', 'bool');
    $migration->addKey('glpi_assets_assets', 'is_template');
    $migration->addField('glpi_assets_assets', 'is_dynamic', 'bool');
    $migration->addKey('glpi_assets_assets', 'is_dynamic');
    $migration->addField('glpi_assets_assets', 'template_name', 'string');
    $migration->dropKey('glpi_assets_assets', 'groups_id');
    $migration->dropField('glpi_assets_assets', 'groups_id');
    $migration->dropKey('glpi_assets_assets', 'groups_id_tech');
    $migration->dropField('glpi_assets_assets', 'groups_id_tech');
    $migration->addField('glpi_assets_assets', 'uuid', 'string');
    $migration->addKey('glpi_assets_assets', 'uuid');
    $migration->addField('glpi_assets_assets', 'autoupdatesystems_id', 'fkey');
    $migration->addKey('glpi_assets_assets', 'autoupdatesystems_id');
    $migration->addField('glpi_assets_assets', 'last_inventory_update', 'timestamp');
    $migration->addField('glpi_assets_assets', 'custom_fields', 'json', ['update' => $DB::quoteValue('{}')]);
}

if (!$DB->tableExists('glpi_assets_assetmodels')) {
    $query = <<<SQL
        CREATE TABLE `glpi_assets_assetmodels` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `assets_assetdefinitions_id` int {$default_key_sign} NOT NULL,
          `name` varchar(255) DEFAULT NULL,
          `comment` text,
          `product_number` varchar(255) DEFAULT NULL,
          `weight` int NOT NULL DEFAULT '0',
          `required_units` int NOT NULL DEFAULT '1',
          `depth` float NOT NULL DEFAULT '1',
          `power_connections` int NOT NULL DEFAULT '0',
          `power_consumption` int NOT NULL DEFAULT '0',
          `is_half_rack` tinyint NOT NULL DEFAULT '0',
          `picture_front` text,
          `picture_rear` text,
          `pictures` text,
          `date_mod` timestamp NULL DEFAULT NULL,
          `date_creation` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `assets_assetdefinitions_id` (`assets_assetdefinitions_id`),
          KEY `name` (`name`),
          KEY `date_mod` (`date_mod`),
          KEY `date_creation` (`date_creation`),
          KEY `product_number` (`product_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
    $DB->doQuery($query);
}

if (!$DB->tableExists('glpi_assets_assettypes')) {
    $query = <<<SQL
        CREATE TABLE `glpi_assets_assettypes` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `assets_assetdefinitions_id` int {$default_key_sign} NOT NULL,
          `name` varchar(255) DEFAULT NULL,
          `comment` text,
          `date_mod` timestamp NULL DEFAULT NULL,
          `date_creation` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `assets_assetdefinitions_id` (`assets_assetdefinitions_id`),
          KEY `name` (`name`),
          KEY `date_mod` (`date_mod`),
          KEY `date_creation` (`date_creation`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
    $DB->doQuery($query);
}

if (!$DB->tableExists('glpi_assets_customfielddefinitions')) {
    $query = <<<SQL
        CREATE TABLE `glpi_assets_customfielddefinitions` (
          `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
          `assets_assetdefinitions_id` int {$default_key_sign} NOT NULL,
          `system_name` varchar(255) NOT NULL,
          `label` varchar(255) NOT NULL,
          `type` varchar(255) NOT NULL,
          `field_options` json,
          `itemtype` VARCHAR(255) NULL DEFAULT NULL,
          `default_value` text,
          `translations` JSON NOT NULL,
          `date_creation` timestamp NULL DEFAULT NULL,
          `date_mod` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `unicity` (`assets_assetdefinitions_id`, `system_name`),
          KEY `system_name` (`system_name`),
          KEY `label` (`label`),
          KEY `date_creation` (`date_creation`),
          KEY `date_mod` (`date_mod`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
    $DB->doQuery($query);
} else {
    $migration->addKey('glpi_assets_customfielddefinitions', 'label');
    $migration->addField('glpi_assets_customfielddefinitions', 'translations', 'JSON NOT NULL', ['update' => "'[]'"]);
    $migration->changeField('glpi_assets_customfielddefinitions', 'name', 'system_name', 'string');
    $migration->addField('glpi_assets_customfielddefinitions', 'date_creation', 'timestamp');
    $migration->addKey('glpi_assets_customfielddefinitions', 'date_creation');
    $migration->addField('glpi_assets_customfielddefinitions', 'date_mod', 'timestamp');
    $migration->addKey('glpi_assets_customfielddefinitions', 'date_mod');
}

// New config values
$migration->addConfig(['glpi_11_assets_migration' => 0]);
