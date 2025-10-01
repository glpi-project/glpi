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

use Plugin;
use Symfony\Component\HttpFoundation\Request;

use function Safe\preg_match;

/**
 * @final
 */
class SessionManager
{
    use RequestRouterTrait;

    /**
     * Registered plugins stateless paths patterns.
     *
     * @var array<string, string[]>
     */
    private static array $plugins_statelass_paths = [];

    /**
     * @param ?string $glpi_root             GLPI root directory on filesystem
     * @param ?array  $plugin_directories         GLPI plugins root directories on filesystem
     */
    public function __construct(?string $glpi_root = null, ?array $plugin_directories = null)
    {
        $this->glpi_root = $glpi_root ?? GLPI_ROOT;
        $this->plugin_directories = $plugin_directories ?? GLPI_PLUGINS_DIRECTORIES;
    }

    /**
     * Add a pattern of stateless path for the given plugin.
     *
     * @param string $plugin_key    The plugin key.
     * @param string $pattern       The resource pattern, relative to the plugin root URI (e.g. `#^/front/api.php/#`).
     */
    public static function registerPluginStatelessPath(string $plugin_key, string $pattern): void
    {
        self::$plugins_statelass_paths[$plugin_key] ??= [];
        self::$plugins_statelass_paths[$plugin_key][] = $pattern;
    }

    /**
     * Compute the fallback strategy for given path.
     */
    public function isResourceStateless(Request $request): bool
    {
        $path = $this->normalizePath($request);

        $path_matches = [];
        if (preg_match(Plugin::PLUGIN_RESOURCE_PATTERN, $path, $path_matches) === 1) {
            $plugin_key      = $path_matches['plugin_key'];
            $plugin_resource = $path_matches['plugin_resource'];

            foreach (self::$plugins_statelass_paths[$plugin_key] ?? [] as $pattern) {
                if (preg_match($pattern, $plugin_resource) === 1) {
                    return true;
                }
            }

            return false;
        }

        if (\str_starts_with($path, '/_wdt/') || \str_starts_with($path, '/_profiler/')) {
            // Symfony profiler and web developer toolbar are not using sessions.
            return true;
        }

        if (\str_starts_with($path, '/api.php') || \str_starts_with($path, '/apirest.php')) {
            // API clients must not use cookies, as the session token is expected to be passed in headers.
            return true;
        }

        if ($this->getTargetFile($path) !== null && !$this->isTargetAPhpScript($path)) {
            // Static files loaded by the FrontEndAssetsListener must not start
            // a session or it'll prevent them from being cached.
            return true;
        }

        if (\str_starts_with($path, '/caldav.php')) {
            // CalDAV clients must not use cookies, as the authentication is expected to be passed in headers.
            return true;
        }

        if (in_array($path, ['/Inventory', '/front/inventory.php'], true)) {
            // Inventory endpoints are machine to machine endpoints, they are not supposed to use sessions.
            return true;
        }

        if (
            $request->query->has('embed')
            && (
                (
                    \str_starts_with($path, '/front/central.php')
                    && $request->query->has('dashboard')
                )
                || (
                    \str_starts_with($path, '/ajax/dashboard.php')
                    && \in_array($request->get('action'), ['get_dashboard_items', 'get_card', 'get_cards'], true)
                )
            )
        ) {
            // Embed dashboards will need to act in an isolated session context.
            return true;
        }

        if (\str_starts_with($path, '/front/cron.php')) {
            // The cron endpoint is not expected to use the authenticated user session.
            return true;
        }

        if (\str_starts_with($path, '/front/smtp_oauth2_callback.php') && !$request->query->has('cookie_refresh')) {
            // The SMTP Oauth2 callback endpoint should try to reload/init the session before the `cookie_refresh` hack
            // has been used.
            return true;
        }

        if (\str_starts_with($path, '/front/planning.php') && $request->query->has('genical')) {
            // The `genical` endpoint must not use cookies, as the authentication is expected to be passed in the query parameters.
            return true;
        }

        return false;
    }
}
