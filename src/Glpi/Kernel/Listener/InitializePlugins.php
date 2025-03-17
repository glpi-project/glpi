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

namespace Glpi\Kernel\Listener;

use DBConnection;
use GLPI;
use Glpi\Debug\Profiler;
use Glpi\DependencyInjection\PluginContainer;
use Glpi\Kernel\ListenersPriority;
use Glpi\Kernel\PostBootEvent;
use Plugin;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Update;

final readonly class InitializePlugins implements EventSubscriberInterface
{
    public function __construct(private PluginContainer $pluginContainer)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostBootEvent::class => ['onPostBoot', ListenersPriority::POST_BOOT_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onPostBoot(): void
    {
        if (!DBConnection::isDbAvailable() || (!defined('SKIP_UPDATES') && !Update::isDbUpToDate())) {
            // Requires the database to be available.
            return;
        }

        if (GLPI_ENVIRONMENT_TYPE === GLPI::ENV_TESTING) {
            $this->setupTesterPlugin();
        }

        Profiler::getInstance()->start('InitializePlugins::execute', Profiler::CATEGORY_BOOT);

        $plugin = new Plugin();
        $plugin->init(true);

        $this->pluginContainer->initializeContainer();

        Profiler::getInstance()->stop('InitializePlugins::execute');
    }

    private function setupTesterPlugin(): void
    {
        /** @var \DBmysql $DB */
        global $DB;
        $DB->updateOrInsert(table: Plugin::getTable(), params: [
            'directory' => 'tester',
            'name'      => 'tester',
            'state'     => 1,
            'version'   => '1.0.0',
        ], where: ['directory' => 'tester']);
    }
}
