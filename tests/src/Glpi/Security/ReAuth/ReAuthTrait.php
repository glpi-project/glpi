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
trait ReAuthTrait
{
    /**
     * Simulate a web (non-CLI) request context so that re-authentication
     * redirects can be triggered from a test.
     *
     * @param array<string, string> $get
     * @param array<string, string> $post
     */
    private function fakeWebContext(
        string $request_uri = '/front/central.php',
        string $method = 'GET',
        array $get = [],
        array $post = [],
        string $referer = 'https://glpi.example.org/front/central.php',
    ): void {
        // getMainRequest() falls back to Request::createFromGlobals() in the test context, so the
        // super globals must carry the exact keys Symfony reads: HTTPS (not REQUEST_SCHEME) drives
        // isSecure(), and QUERY_STRING (not the query part of REQUEST_URI) drives getUri().
        $query_string = parse_url($request_uri, PHP_URL_QUERY) ?? '';
        parse_str($query_string, $get_from_uri);

        $GLOBALS['GLPI_IS_COMMAND_LINE'] = false;
        $_SERVER['HTTPS']          = 'on';
        $_SERVER['HTTP_HOST']      = 'glpi.example.org';
        $_SERVER['REQUEST_URI']    = $request_uri;
        $_SERVER['QUERY_STRING']   = $query_string;
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['HTTP_REFERER']   = $referer;
        // A real web request always carries a client IP; without it SessionTracker::recordNewSession()
        // would try to insert a NULL ip_address when login() is called under the faked web context.
        $_SERVER['REMOTE_ADDR']    = '127.0.0.1';
        $_GET  = $get ?: $get_from_uri;
        $_POST = $post;
    }

    /**
     * Restore the globals mutated by fakeWebContext(). Must be called from tearDown().
     */
    private function restoreWebContext(): void
    {
        unset(
            $GLOBALS['GLPI_IS_COMMAND_LINE'],
            $_SERVER['HTTPS'],
            $_SERVER['HTTP_HOST'],
            $_SERVER['REQUEST_URI'],
            $_SERVER['QUERY_STRING'],
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['HTTP_REFERER'],
            $_SERVER['REMOTE_ADDR'],
        );
    }

    private function setReauthenticated(bool $reauthenticated): void
    {
        $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s');
        if ($reauthenticated) {
            ($this->getReAuthManager())->authenticate();
        } else {
            unset($_SESSION['glpi_reauth_until']);
        }
    }

    private function getReAuthManager(): ReAuthManager
    {
        return ReAuthManager::getInstance();
    }
}
