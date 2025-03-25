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
 * @var \Migration $migration
 */

$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

$migration->dropKey('glpi_items_devicecameras_imageformats', 'item_devicecameras_id');
$migration->changeField(
    'glpi_items_devicecameras_imageformats',
    'item_devicecameras_id',
    'items_devicecameras_id',
    "int {$default_key_sign} NOT NULL DEFAULT '0'"
);
$migration->addKey('glpi_items_devicecameras_imageformats', 'items_devicecameras_id');

$migration->dropKey('glpi_items_devicecameras_imageresolutions', 'item_devicecameras_id');
$migration->changeField(
    'glpi_items_devicecameras_imageresolutions',
    'item_devicecameras_id',
    'items_devicecameras_id',
    "int {$default_key_sign} NOT NULL DEFAULT '0'"
);
$migration->addKey('glpi_items_devicecameras_imageresolutions', 'items_devicecameras_id');
