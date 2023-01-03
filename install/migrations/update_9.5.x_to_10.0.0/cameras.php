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
 */

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

if (!$DB->tableExists('glpi_devicecameras')) {
    $query = "CREATE TABLE `glpi_devicecameras` (
      `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
      `designation` varchar(255) DEFAULT NULL,
      `flashunit` tinyint NOT NULL DEFAULT '0',
      `lensfacing` varchar(255) DEFAULT NULL,
      `orientation` varchar(255) DEFAULT NULL,
      `focallength` varchar(255) DEFAULT NULL,
      `sensorsize` varchar(255) DEFAULT NULL,
      `comment` text,
      `manufacturers_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `is_recursive` tinyint NOT NULL DEFAULT '0',
      `devicecameramodels_id` int {$default_key_sign} DEFAULT NULL,
      `support` varchar(255) DEFAULT NULL,
      `date_mod` timestamp NULL DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `designation` (`designation`),
      KEY `manufacturers_id` (`manufacturers_id`),
      KEY `devicecameramodels_id` (`devicecameramodels_id`),
      KEY `entities_id` (`entities_id`),
      KEY `is_recursive` (`is_recursive`),
      KEY `date_mod` (`date_mod`),
      KEY `date_creation` (`date_creation`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_devicecameras");
} else {
    $migration->dropKey('glpi_devicecameras', 'unicity');
    $migration->addKey('glpi_devicecameras', 'manufacturers_id', 'manufacturers_id');
}

if (!$DB->tableExists('glpi_devicecameramodels')) {
    $query = "CREATE TABLE `glpi_devicecameramodels` (
      `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
      `name` varchar(255) DEFAULT NULL,
      `comment` text,
      `product_number` varchar(255) DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `name` (`name`),
      KEY `product_number` (`product_number`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_devicecameramodels");
}

if (!$DB->tableExists('glpi_imageformats')) {
    $query = "CREATE TABLE `glpi_imageformats` (
      `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
      `name` varchar(255) DEFAULT NULL,
      `date_mod` timestamp NULL DEFAULT NULL,
      `comment` text,
      `date_creation` timestamp NULL DEFAULT NULL,
      `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `is_recursive` tinyint NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`),
      UNIQUE KEY `unicity` (`name`),
      KEY `date_mod` (`date_mod`),
      KEY `entities_id` (`entities_id`),
      KEY `is_recursive` (`is_recursive`),
      KEY `date_creation` (`date_creation`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_imageformats");
}

if (!$DB->tableExists('glpi_imageresolutions')) {
    $query = "CREATE TABLE `glpi_imageresolutions` (
      `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
      `name` varchar(255) DEFAULT NULL,
      `is_video` tinyint NOT NULL DEFAULT '0',
      `date_mod` timestamp NULL DEFAULT NULL,
      `comment` text,
      `date_creation` timestamp NULL DEFAULT NULL,
      `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `is_recursive` tinyint NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`),
      UNIQUE KEY `unicity` (`name`),
      KEY `date_mod` (`date_mod`),
      KEY `is_video` (`is_video`),
      KEY `entities_id` (`entities_id`),
      KEY `is_recursive` (`is_recursive`),
      KEY `date_creation` (`date_creation`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_imageresolutions");
}

if (!$DB->tableExists('glpi_items_devicecameras')) {
    $query = "CREATE TABLE `glpi_items_devicecameras` (
      `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
      `items_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `itemtype` varchar(255) DEFAULT NULL,
      `devicecameras_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `is_deleted` tinyint NOT NULL DEFAULT '0',
      `is_dynamic` tinyint NOT NULL DEFAULT '0',
      `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `is_recursive` tinyint NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`),
      KEY `items_id` (`items_id`),
      KEY `devicecameras_id` (`devicecameras_id`),
      KEY `is_deleted` (`is_deleted`),
      KEY `is_dynamic` (`is_dynamic`),
      KEY `entities_id` (`entities_id`),
      KEY `is_recursive` (`is_recursive`),
      KEY `item` (`itemtype`,`items_id`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_items_devicecameras");
}

if (!$DB->tableExists('glpi_items_devicecameras_imageformats')) {
    $query = "CREATE TABLE `glpi_items_devicecameras_imageformats` (
      `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
      `item_devicecameras_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `imageformats_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `is_dynamic` tinyint NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`),
      KEY `item_devicecameras_id` (`item_devicecameras_id`),
      KEY `imageformats_id` (`imageformats_id`),
      KEY `is_dynamic` (`is_dynamic`)
   ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_items_devicecameras_imageformats");
}

if (!$DB->tableExists('glpi_items_devicecameras_imageresolutions')) {
    $query = "CREATE TABLE `glpi_items_devicecameras_imageresolutions` (
      `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
      `item_devicecameras_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `imageresolutions_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `is_dynamic` tinyint NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`),
      KEY `item_devicecameras_id` (`item_devicecameras_id`),
      KEY `imageresolutions_id` (`imageresolutions_id`),
      KEY `is_dynamic` (`is_dynamic`)
   ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->queryOrDie($query, "10.0 add table glpi_items_devicecameras_imageresolutions");
}
