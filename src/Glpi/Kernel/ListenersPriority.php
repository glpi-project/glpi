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

use Glpi\Kernel\Listener\PostBootListener as PostBootListener;
use Glpi\Kernel\Listener\RequestListener as RequestListener;

final class ListenersPriority
{
    public const POST_BOOT_LISTENERS_PRIORITIES = [
        PostBootListener\SessionStart::class =>                        200,
        PostBootListener\ProfilerStart::class =>                       190,
        PostBootListener\InitializeDbConnection::class =>              180,
        PostBootListener\InitializeCache::class =>                     170,
        PostBootListener\LoadLegacyConfiguration::class =>             160,
        PostBootListener\LoadLanguage::class =>                        150,
        PostBootListener\CustomObjectsAutoloaderRegistration::class => 140,
        PostBootListener\InitializePlugins::class =>                   130,
        PostBootListener\CustomObjectsBootstrap::class =>              120,
    ];

    public const REQUEST_LISTENERS_PRIORITIES = [
        // Registers the current request to the error handler.
        // Keep it in top priority as is required during handling of errors that may be triggered by any other listener.
        RequestListener\ErrorHandlerRequestListener::class => 1000,

        // Static assets must be served without executing anything else.
        // Keep the listener on top priority.
        RequestListener\FrontEndAssetsListener::class      => 500,

        // This listener will prevent accessing GLPI if the maintenance mode is active.
        // It must be executed right after the `FrontEndAssetsListener`, as nothing more than front-end assets
        // must be served in this case.
        RequestListener\CheckMaintenanceListener::class    => 490,

        // This listener will ensure that the request is made on a secure context (HTTPS) when the
        // cookies are available only on a secure context (`session.cookie_secure=on`).
        // It must be executed before trying to serve any statefull endpoint.
        RequestListener\SessionCheckCookieListener::class  => 475,

        // This listener will ensure that the database connection is configured and available, and that database is up-to-date.
        // It must be executed before executing any controller (except controllers related to front-end assets).
        RequestListener\CheckDatabaseStatusListener::class => 450,

        // This listener will forward to the inventory controller any inventory agent requests made on the index endpoint.
        RequestListener\CatchInventoryAgentRequestListener::class => 420,

        // Executes the legacy controller scripts (`/ajax/*.php` or `/front/*.php` scripts) whenever the
        // requested URI matches an existing file.
        RequestListener\LegacyRouterListener::class        => 400,

        // This listener allows matching plugins routes at runtime.
        // It must be executed prior to the `LegacyItemtypeRouteListener` to be sure that any legacy route
        // override in plugins will be taken into account before trying to forward to a generic controller.
        RequestListener\PluginsRouterListener::class       => 375,

        // Map legacy scripts URLS (e.g. `/front/computer.php`) to modern controllers.
        // Must be executed after the `LegacyRouterListener` to ensure to use the legacy script if it exists
        // and after the `PluginsRouterListener` to allow plugin to bypass generic controllers.
        RequestListener\LegacyItemtypeRouteListener::class => 350,

        // Legacy URLs redirections.
        // Must be executed before the Symfony router, to prevent `NotFoundHttpException` to be thrown.
        //
        // Symfony's Router priority is 32.
        // @see \Symfony\Component\HttpKernel\EventListener\RouterListener::getSubscribedEvents()
        RequestListener\RedirectLegacyRouteListener::class => 33,

        // Update session variables according to request parameters.
        // Must be called as late as possible, just before controllers execution.
        RequestListener\SessionVariables::class            => 0,
    ];

    private function __construct()
    {
    }
}
