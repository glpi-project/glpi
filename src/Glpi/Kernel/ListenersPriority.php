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

namespace Glpi\Kernel;

use Glpi\Kernel\Listener\PostBootListener\BootPlugins;
use Glpi\Kernel\Listener\PostBootListener\CheckPluginsStates;
use Glpi\Kernel\Listener\PostBootListener\CustomObjectsAutoloaderRegistration;
use Glpi\Kernel\Listener\PostBootListener\CustomObjectsBoot;
use Glpi\Kernel\Listener\PostBootListener\FlushBootErrors;
use Glpi\Kernel\Listener\PostBootListener\InitializeCache;
use Glpi\Kernel\Listener\PostBootListener\InitializeDbConnection;
use Glpi\Kernel\Listener\PostBootListener\InitializePlugins;
use Glpi\Kernel\Listener\PostBootListener\LoadLanguage;
use Glpi\Kernel\Listener\PostBootListener\LoadLegacyConfiguration;
use Glpi\Kernel\Listener\PostBootListener\ProfilerStart;
use Glpi\Kernel\Listener\PostBootListener\SessionStart;
use Glpi\Kernel\Listener\RequestListener\CatchInventoryAgentRequestListener;
use Glpi\Kernel\Listener\RequestListener\CheckDatabaseStatusListener;
use Glpi\Kernel\Listener\RequestListener\CheckMaintenanceListener;
use Glpi\Kernel\Listener\RequestListener\ErrorHandlerRequestListener;
use Glpi\Kernel\Listener\RequestListener\FrontEndAssetsListener;
use Glpi\Kernel\Listener\RequestListener\LegacyItemtypeRouteListener;
use Glpi\Kernel\Listener\RequestListener\LegacyRouterListener;
use Glpi\Kernel\Listener\RequestListener\PluginsRouterListener;
use Glpi\Kernel\Listener\RequestListener\RedirectLegacyRouteListener;
use Glpi\Kernel\Listener\RequestListener\SessionCheckCookieListener;
use Glpi\Kernel\Listener\RequestListener\SessionVariables;

final class ListenersPriority
{
    public const POST_BOOT_LISTENERS_PRIORITIES = [
        ProfilerStart::class =>                       200,
        InitializeDbConnection::class =>              190,
        InitializeCache::class =>                     180,
        LoadLegacyConfiguration::class =>             170,
        CustomObjectsAutoloaderRegistration::class => 160,
        CheckPluginsStates::class =>                  150,
        BootPlugins::class =>                         140,
        SessionStart::class =>                        130,

        // Need to be after `SessionStart` to prevent headers to be sent before the session start.
        FlushBootErrors::class =>                     125,

        LoadLanguage::class =>                        120,
        InitializePlugins::class =>                   110,
        CustomObjectsBoot::class =>                   100,
    ];

    public const REQUEST_LISTENERS_PRIORITIES = [
        // Registers the current request to the error handler.
        // Keep it in top priority as is required during handling of errors that may be triggered by any other listener.
        ErrorHandlerRequestListener::class => 1000,

        // Static assets must be served without executing anything else.
        // Keep the listener on top priority.
        FrontEndAssetsListener::class      => 500,

        // This listener will prevent accessing GLPI if the maintenance mode is active.
        // It must be executed right after the `FrontEndAssetsListener`, as nothing more than front-end assets
        // must be served in this case.
        CheckMaintenanceListener::class    => 490,

        // This listener will ensure that the request is made on a secure context (HTTPS) when the
        // cookies are available only on a secure context (`session.cookie_secure=on`).
        // It must be executed before trying to serve any statefull endpoint.
        SessionCheckCookieListener::class  => 475,

        // This listener will ensure that the database connection is configured and available, and that database is up-to-date.
        // It must be executed before executing any controller (except controllers related to front-end assets).
        CheckDatabaseStatusListener::class => 450,

        // This listener will forward to the inventory controller any inventory agent requests made on the index endpoint.
        CatchInventoryAgentRequestListener::class => 420,

        // Executes the legacy controller scripts (`/ajax/*.php` or `/front/*.php` scripts) whenever the
        // requested URI matches an existing file.
        LegacyRouterListener::class        => 400,

        // This listener allows matching plugins routes at runtime.
        // It must be executed prior to the `LegacyItemtypeRouteListener` to be sure that any legacy route
        // override in plugins will be taken into account before trying to forward to a generic controller.
        PluginsRouterListener::class       => 375,

        // Map legacy scripts URLS (e.g. `/front/computer.php`) to modern controllers.
        // Must be executed after the `LegacyRouterListener` to ensure to use the legacy script if it exists
        // and after the `PluginsRouterListener` to allow plugin to bypass generic controllers.
        LegacyItemtypeRouteListener::class => 350,

        // Legacy URLs redirections.
        // Must be executed before the Symfony router, to prevent `NotFoundHttpException` to be thrown.
        //
        // Symfony's Router priority is 32.
        // @see \Symfony\Component\HttpKernel\EventListener\RouterListener::getSubscribedEvents()
        RedirectLegacyRouteListener::class => 33,

        // Update session variables according to request parameters.
        // Must be called as late as possible, just before controllers execution.
        SessionVariables::class            => 0,
    ];

    private function __construct() {}
}
