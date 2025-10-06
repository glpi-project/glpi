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

use Glpi\Form\AccessControl\FormAccessControlManager;
use Glpi\Form\Destination\FormDestinationManager;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\QuestionType\QuestionTypesManager;
use Glpi\Form\ServiceCatalog\HomeSearchManager;
use Glpi\Form\ServiceCatalog\ServiceCatalogManager;
use Glpi\Helpdesk\Tile\TilesManager;
use GlpiPlugin\Tester\Form\ComputerDestination;
use GlpiPlugin\Tester\Form\ComputerProvider;
use GlpiPlugin\Tester\Form\CustomTile;
use GlpiPlugin\Tester\Form\DayOfTheWeekPolicy;
use GlpiPlugin\Tester\Form\QuestionTypeRange;
use GlpiPlugin\Tester\Form\QuestionTypeColor;
use GlpiPlugin\Tester\Form\ExternalIDField;
use GlpiPlugin\Tester\Form\TesterCategory;
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

function plugin_tester_install(): bool
{
    return true;
}


function plugin_tester_uninstall(): bool
{
    return true;
}

function plugin_tester_getDropdown(): array
{
    return [
        PluginTesterMyLegacyClass::class => PluginTesterMyLegacyClass::getTypeName(),
        PluginTesterMyPseudoPsr4Class::class => PluginTesterMyPseudoPsr4Class::getTypeName(),
        MyPsr4Class::class => MyPsr4Class::getTypeName(),
    ];
}

function plugin_init_tester(): void
{
    global $PLUGIN_HOOKS;
    $plugin = new Plugin();
    if (!$plugin->isActivated('tester')) {
        return;
    }

    // Register form question types and categories
    $types_manager = QuestionTypesManager::getInstance();
    $types_manager->registerPluginCategory(new TesterCategory());
    $types_manager->registerPluginQuestionType(new QuestionTypeRange());
    $types_manager->registerPluginQuestionType(new QuestionTypeColor());

    // Register access control policies
    $access_manager = FormAccessControlManager::getInstance();
    $access_manager->registerPluginAccessControlPolicy(new DayOfTheWeekPolicy());

    // Register destination type
    $destination_manager = FormDestinationManager::getInstance();
    $destination_manager->registerPluginDestinationType(new ComputerDestination());

    // Register destination config field
    $destination_manager->registerPluginCommonITILConfigField(
        FormDestinationTicket::class,
        new ExternalIDField()
    );

    // Register custom tiles types
    $tiles_manager = TilesManager::getInstance();
    $tiles_manager->registerPluginTileType(new CustomTile());

    // Register custom home page search provider
    $home_manager = HomeSearchManager::getInstance();
    $home_manager->registerPluginProvider(new ComputerProvider());

    // Register custom service catalog content provider
    $service_catalog_manager = ServiceCatalogManager::getInstance();
    $service_catalog_manager->registerPluginProvider(new ComputerProvider());

    $PLUGIN_HOOKS['menu_toadd']['tester'] = ['management' => MyPsr4Class::class];
}

function plugin_tester_boot()
{
    \Glpi\Http\SessionManager::registerPluginStatelessPath('tester', '#^/$#');
    \Glpi\Http\SessionManager::registerPluginStatelessPath('tester', '#^/StatelessURI$#');
}
