<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
use Glpi\Http\SessionManager;
use Glpi\Plugin\Hooks;
use GlpiPlugin\Tester\Form\ComputerDestination;
use GlpiPlugin\Tester\Form\ComputerProvider;
use GlpiPlugin\Tester\Form\CustomTile;
use GlpiPlugin\Tester\Form\DayOfTheWeekPolicy;
use GlpiPlugin\Tester\Form\ExternalIDField;
use GlpiPlugin\Tester\Form\QuestionTypeColor;
use GlpiPlugin\Tester\Form\QuestionTypeRange;
use GlpiPlugin\Tester\Form\TesterCategory;
use GlpiPlugin\Tester\MyPsr4Class;
use GlpiPlugin\Tester\Computer;

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
            ],
        ],
    ];
}

function plugin_tester_install(): bool
{
    global $DB;

    if (!$DB->tableExists(Computer::getTable())) {
        $default_charset    = DBConnection::getDefaultCharset();
        $default_collation  = DBConnection::getDefaultCollation();
        $default_key_sign   = DBConnection::getDefaultPrimaryKeySignOption();

        $DB->doQuery(
            "CREATE TABLE `" . Computer::getTable() . "` (
                `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
                `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                `is_recursive` tinyint NOT NULL DEFAULT '0',
                `name` varchar(255) DEFAULT NULL,
                `date_creation` timestamp NULL DEFAULT NULL,
                `date_mod` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `entities_id` (`entities_id`),
                KEY `is_recursive` (`is_recursive`),
                KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;"
        );
    }

    return true;
}


function plugin_tester_uninstall(): bool
{
    global $DB;

    $DB->dropTable(Computer::getTable());

    return true;
}

function plugin_tester_getDatabaseRelations(): array
{
    return [
        'glpi_entities' => [
            Computer::getTable() => 'entities_id',
        ],
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

function plugin_init_tester(): void
{
    global $CFG_GLPI, $PLUGIN_HOOKS;
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
    $PLUGIN_HOOKS[Hooks::ASSIGN_TO_TICKET]['tester'] = true;
    $CFG_GLPI['taggable_types'][] = Computer::class;
}

function plugin_tester_AssignToTicket(array $types): array
{
    $types[ConsumableItem::class] = ConsumableItem::getTypeName();
    return $types;
}

function plugin_tester_boot()
{
    SessionManager::registerPluginStatelessPath('tester', '#^/$#');
    SessionManager::registerPluginStatelessPath('tester', '#^/StatelessURI$#');
    SessionManager::registerPluginStatelessPath('tester', '#^/post-only$#');
}
