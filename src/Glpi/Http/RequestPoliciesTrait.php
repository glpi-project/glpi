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

namespace Glpi\Http;

use Symfony\Component\HttpFoundation\Request;

trait RequestPoliciesTrait
{
    /**
     * Indicates the requested resource is made on a front-end asset endpoint.
     */
    protected function isFrontEndAssetEndpoint(Request $request): bool
    {
        $path = $request->getPathInfo();

        return \str_starts_with($path, '/js/')
            || \str_starts_with($path, '/front/css.php')
            || \str_starts_with($path, '/front/locale.php');
    }

    /**
     * Indicates the requested resource is made on the Symfony profiler resources.
     */
    protected function isSymfonyProfilerEndpoint(Request $request): bool
    {
        $path = $request->getPathInfo();

        return \str_starts_with($path, '/_profiler/')
            || \str_starts_with($path, '/_wdt/');
    }

    /**
     * Indicates whether the DB status should be checked for the given request.
     */
    protected function shouldCheckDbStatus(Request $request): bool
    {
        $path = $request->getPathInfo();

        if ($this->isFrontEndAssetEndpoint($request) || $this->isSymfonyProfilerEndpoint($request)) {
            // These resources should always be available.
            return false;
        }

        if (
            \str_starts_with($path, '/install/')
            || ($_SESSION['is_installing'] ?? false)
        ) {
            // DB status should never be checked when the requested endpoint is part of the install process.
            return false;
        }

        return true;
    }
}
