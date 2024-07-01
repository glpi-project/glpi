<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Config\LegacyConfigurators;

use Glpi\Config\ConfigProviderHasRequestTrait;
use Session;
use Glpi\Config\ConfigProviderWithRequestInterface;
use Glpi\Config\LegacyConfigProviderInterface;

final class SessionStart implements LegacyConfigProviderInterface, ConfigProviderWithRequestInterface
{
    use ConfigProviderHasRequestTrait;

    /**
     * An array of regular expressions of the paths that disable the Session.
     */
    private const NO_COOKIE_PATHS = [
        '/api(rest)?\.php.*',
        '/caldav\.php.*',
    ];

    private const NO_SESSION_PATHS = [
        '/api(rest)?\.php.*',
    ];

    public function execute(): void
    {
        $path = $this->getRequest()->getRequestUri();
        $path = '/' . ltrim($path, '/');

        $noCookiePaths = '~^' . implode('|', \array_map(static fn ($regex) => '(?:' . $regex . ')', self::NO_COOKIE_PATHS)) . '$~sUu';

        Session::setPath();

        if (
            \preg_match($noCookiePaths, $path)
            || (\preg_match('~^/front/planning\.php~Uu', $path) && $this->getRequest()->query->has('genical'))
        ) {
            // Disable session cookie for these paths
            ini_set('session.use_cookies', 0);
        }

        $noSessionPaths = '~^' . implode('|', \array_map(static fn ($regex) => '(?:' . $regex . ')', self::NO_SESSION_PATHS)) . '$~sUu';
        if (
            !\preg_match($noSessionPaths, $path)
        ) {
            // Disable session cookie for these paths
            Session::start();
        }

        // Default Use mode
        if (!isset($_SESSION['glpi_use_mode'])) {
            $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;
        }
    }
}
