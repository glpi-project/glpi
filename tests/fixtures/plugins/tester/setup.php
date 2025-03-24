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

use GlpiPlugin\Tester\MyPsr4Class;

function plugin_version_tester()
{
    return [
        'name'           => 'tester',
        'version'        => '1.0.0',
        'author'         => 'GLPI Test suite',
        'license'        => 'GPL v2+',
        'requirements'   => [
            'glpi' => [
                'min' => '9.5.0',
            ]
        ]
    ];
}

function plugin_tester_getDropdown(): array
{
    return [
        PluginTesterMyLegacyClass::class => PluginTesterMyLegacyClass::getTypeName(),
        PluginTesterMyPseudoPsr4Class::class => PluginTesterMyPseudoPsr4Class::getTypeName(),
        MyPsr4Class::class => MyPsr4Class::getTypeName(),
    ];
}
