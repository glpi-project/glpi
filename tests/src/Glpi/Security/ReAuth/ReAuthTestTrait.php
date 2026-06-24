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

namespace Glpi\Tests\Glpi\Security\ReAuth;

use Glpi\Security\ReAuth\ReAuthManager;

/**
 * Helpers shared by the re-authentication tests: fake a web request context and
 * toggle the current session's re-authentication state.
 */
trait ReAuthTestTrait
{
    /**
     * Simulate a web (non-CLI) request context so that re-authentication
     * redirects can be triggered from a test.
     */
    private function fakeWebContext(string $request_uri = '/front/central.php'): void
    {
        $GLOBALS['GLPI_IS_COMMAND_LINE'] = false;
        $_SERVER['REQUEST_SCHEME'] = 'https';
        $_SERVER['HTTP_HOST']      = 'glpi.example.org';
        $_SERVER['REQUEST_URI']    = $request_uri;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_REFERER']   = 'https://glpi.example.org/front/central.php';
        $_GET  = [];
        $_POST = [];
    }

    /**
     * Restore the globals mutated by fakeWebContext(). Must be called from tearDown().
     */
    private function restoreWebContext(): void
    {
        unset(
            $GLOBALS['GLPI_IS_COMMAND_LINE'],
            $_SERVER['REQUEST_SCHEME'],
            $_SERVER['HTTP_HOST'],
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['HTTP_REFERER'],
        );
    }

    private function setReauthenticated(bool $reauthenticated): void
    {
        $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s');
        if ($reauthenticated) {
            (new ReAuthManager())->authenticate();
        } else {
            unset($_SESSION['glpi_reauth_until']);
        }
    }
}
