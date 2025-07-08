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

use Config;
use DBConnection;
use Symfony\Component\HttpFoundation\Request;
use Update;

trait KernelListenerTrait
{
    /**
     * Indicates whether a controller has already been assigned to the request.
     */
    protected function isControllerAlreadyAssigned(Request $request): bool
    {
        return $request->attributes->get('_controller') !== null;
    }

    /**
     * Indicates whether the requested resource is made on a front-end asset endpoint.
     */
    protected function isFrontEndAssetEndpoint(Request $request): bool
    {
        $path = $request->getPathInfo();

        return \str_starts_with($path, '/js/')
            || \str_starts_with($path, '/front/css.php')
            || \str_starts_with($path, '/front/locale.php');
    }

    /**
     * Indicates whether the requested resource is made on the Symfony profiler resources.
     */
    protected function isSymfonyProfilerEndpoint(Request $request): bool
    {
        $path = $request->getPathInfo();

        return \str_starts_with($path, '/_profiler/')
            || \str_starts_with($path, '/_wdt/');
    }

    /**
     * Indicates whether the database data can be used.
     * For instance, plugins and custom objects definitions should not be loaded if a mandatory update is required.
     */
    protected function isDatabaseUsable(): bool
    {
        return DBConnection::isDbAvailable()
            && Config::isLegacyConfigurationLoaded()
            && Update::isUpdateMandatory() === false;
    }
}
