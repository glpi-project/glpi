<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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
 * @var array $ADDTODISPLAYPREF
 */

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();

if (!$DB->fieldExists('glpi_authldaps', 'tls_certfile')) {
    $migration->addField(
        'glpi_authldaps',
        'tls_certfile',
        'text',
        [
            'after'  => 'inventory_domain'
        ]
    );
}

if (!$DB->fieldExists('glpi_authldaps', 'tls_keyfile')) {
    $migration->addField(
        'glpi_authldaps',
        'tls_keyfile',
        'text',
        [
            'after'  => 'tls_certfile'
        ]
    );
}

if (!$DB->fieldExists('glpi_authldaps', 'use_bind')) {
    $migration->addField(
        'glpi_authldaps',
        'use_bind',
        'bool',
        [
            'after'  => 'tls_keyfile',
            'value' => 1
        ]
    );
}

if (!$DB->fieldExists('glpi_authldaps', 'timeout')) {
    $migration->addField(
        'glpi_authldaps',
        'timeout',
        'int',
        [
            'after'  => 'use_bind',
            'value' => 10
        ]
    );
}

if (!$DB->fieldExists('glpi_authldapreplicates', 'timeout')) {
    $migration->addField(
        'glpi_authldapreplicates',
        'timeout',
        'int',
        [
            'after'  => 'name',
            'value' => 10
        ]
    );
}
