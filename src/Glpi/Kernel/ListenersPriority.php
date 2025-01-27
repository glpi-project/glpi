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

use Glpi\Kernel\Listener as KernelListener;
use Glpi\Http\Listener as HttpListener;

final class ListenersPriority
{
    public const POST_BOOT_LISTENERS_PRIORITIES = [
        KernelListener\SessionStart::class =>                        200,
        KernelListener\ProfilerStart::class =>                       190,
        KernelListener\InitializeDbConnection::class =>              180,
        KernelListener\InitializeCache::class =>                     170,
        KernelListener\LoadLegacyConfiguration::class =>             160,
        KernelListener\LoadLanguage::class =>                        150,
        KernelListener\CustomObjectsAutoloaderRegistration::class => 140,
        KernelListener\InitializePlugins::class =>                   130,
        KernelListener\CustomObjectsBootstrap::class =>              120,
    ];

    public const REQUEST_LISTENERS_PRIORITIES = [
        // Registers the current request to the error handler.
        // Keep it in top priority as is required during handling of errors that may be triggered by any other listener.
        HttpListener\ErrorHandlerRequestListener::class => 1000,

        // Static assets must be served without executing anything else.
        // Keep the listener on top priority.
        HttpListener\LegacyAssetsListener::class        => 500,

        // This listener will ensure that the request is made on a secure context (HTTPS) when the
        // cookies are available only on a secure context (`session.cookie_secure=on`).
        // It must be executed before trying to serve any statefull endpoint.
        HttpListener\SessionCheckCookieListener::class  => 475,

        // This listener will ensure that the database connection is configured and available.
        // It must be executed before executing any controller (except controllers related to front-end assets).
        HttpListener\CheckDatabaseStatusListener::class => 450,

        // This listener will ensure that GLPI is not being updated, or does not need a database update.
        // Must also be executed before other controllers, since it defines its own controller.
        HttpListener\CheckIfUpdateNeededListener::class => 440,

        HttpListener\CheckMaintenanceListener::class    => 430,

        // This listener will forward to the inventory controller any inventory agent requests made on the index endpoint.
        HttpListener\CatchInventoryAgentRequestListener::class => 420,

        // Executes the legacy controller scripts (`/ajax/*.php` or `/front/*.php` scripts) whenever the
        // requested URI matches an existing file.
        HttpListener\LegacyRouterListener::class        => 400,

        // Map legacy scripts URLS (e.g. `/front/computer.php`) to modern controllers.
        // Must be executed after the `LegacyRouterListener` to ensure to use the legacy script if it exists.
        HttpListener\LegacyItemtypeRouteListener::class => 375,

        // Legacy URLs redirections.
        // Must be executed before the Symfony router, to prevent `NotFoundHttpException` to be thrown.
        //
        // Symfony's Router priority is 32.
        // @see \Symfony\Component\HttpKernel\EventListener\RouterListener::getSubscribedEvents()
        HttpListener\RedirectLegacyRouteListener::class => 33,

        // This listener allows matching plugins routes at runtime,
        //   that's why it's executed right after Symfony's Router,
        //   and also after GLPI's config is set.
        //
        // Symfony's Router priority is 32.
        // @see \Symfony\Component\HttpKernel\EventListener\RouterListener::getSubscribedEvents()
        HttpListener\PluginsRouterListener::class       => 31,

        // Redefine the `$_SERVER['PHP_SELF']` variables that it still used to retrieve the "current path".
        // Must be called as late as possible, just before controllers execution.
        //
        // FIXME: `$_SERVER['PHP_SELF']` should not be altered, `$request()->getBasePath() . $request->getPathInfo()`
        // should be used instead.
        HttpListener\OverridePHPSelfParam::class        => 0,

        // Update session variables according to request parameters.
        // Must be called as late as possible, just before controllers execution.
        HttpListener\SessionVariables::class            => 0,
    ];

    private function __construct()
    {
    }
}
