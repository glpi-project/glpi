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

use Config;
use Glpi\Exception\Http\AccessDeniedHttpException;
use LogicException;
use Plugin;
use Session;
use Symfony\Component\HttpFoundation\Request;

use function Safe\preg_match;

/**
 * @since 10.0.10
 */
final class Firewall
{
    use RequestRouterTrait;

    /**
     * Nothing to check. Entrypoint accepts anonymous access.
     */
    public const STRATEGY_NO_CHECK = 'no_check';

    /**
     * Check that user is authenticated.
     */
    public const STRATEGY_AUTHENTICATED = 'authenticated';

    /**
     * Check that user is authenticated and has administration rights.
     */
    public const STRATEGY_ADMIN_ACCESS = 'admin_access';

    /**
     * Check that user is authenticated and is using a profile based on central interface.
     */
    public const STRATEGY_CENTRAL_ACCESS = 'central_access';

    /**
     * Check that user is authenticated and is using a profile based on simplified interface.
     */
    public const STRATEGY_HELPDESK_ACCESS = 'helpdesk_access';

    /**
     * Check that FAQ access is allowed (unauthenticated if public FAQ is enabled, or by checking rights).
     */
    public const STRATEGY_FAQ_ACCESS = 'faq_access';

    /**
     * Fallback strategy to apply (except for legacy scripts).
     */
    private const FALLBACK_STRATEGY = self::STRATEGY_CENTRAL_ACCESS;

    /**
     * Fallback strategy to apply to legacy scripts.
     */
    private const FALLBACK_STRATEGY_FOR_LEGACY_SCRIPTS = self::STRATEGY_AUTHENTICATED;

    /**
     * Registered plugins strategies for legacy scripts.
     *
     * @phpstan-var array<string, array<string, self::STRATEGY_*>>
     */
    private static array $plugins_legacy_scripts_strategies = [];

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
     * Add a security strategy for specific plugin legacy scripts.
     *
     * @param string $plugin_key    The plugin key.
     * @param string $pattern       The resource pattern, relative to the plugin root URI (e.g. `#^/front/api.php/#`).
     * @param string $strategy      The strategy to apply.
     *
     * @phpstan-param self::STRATEGY_* $strategy
     */
    public static function addPluginStrategyForLegacyScripts(
        string $plugin_key,
        string $pattern,
        string $strategy
    ): void {
        self::$plugins_legacy_scripts_strategies[$plugin_key][$pattern] = $strategy;
    }

    /**
     * Reset strategies for all plugins.
     */
    public static function resetPluginsStrategies(): void
    {
        self::$plugins_legacy_scripts_strategies = [];
    }

    /**
     * Apply the firewall strategy.
     *
     * @phpstan-param self::STRATEGY_* $strategy
     */
    public function applyStrategy(string $strategy): void
    {
        switch ($strategy) {
            case self::STRATEGY_AUTHENTICATED:
                Session::checkLoginUser();
                break;
            case self::STRATEGY_ADMIN_ACCESS:
                Session::checkLoginUser();
                if (!Session::haveRight(Config::$rightname, UPDATE)) {
                    throw new AccessDeniedHttpException('Missing administration rights.');
                }
                break;
            case self::STRATEGY_CENTRAL_ACCESS:
                Session::checkCentralAccess();
                break;
            case self::STRATEGY_HELPDESK_ACCESS:
                Session::checkHelpdeskAccess();
                break;
            case self::STRATEGY_FAQ_ACCESS:
                Session::checkFaqAccess();
                break;
            case self::STRATEGY_NO_CHECK:
                // nothing to do
                break;
            default:
                throw new LogicException(sprintf('Invalid firewall strategy `%s`.', $strategy));
        }
    }

    /**
     * Compute the fallback strategy for given path.
     */
    public function computeFallbackStrategy(Request $request): string
    {
        $path = $this->normalizePath($request);

        $path_matches = [];

        if (preg_match(Plugin::PLUGIN_RESOURCE_PATTERN, $path, $path_matches) === 1) {
            return $this->computeFallbackStrategyForPlugin(
                $path_matches['plugin_key'],
                $path_matches['plugin_resource']
            );
        }

        return $this->computeFallbackStrategyForCore($path);
    }

    /**
     * Compute the fallback strategy for GLPI resources.
     */
    private function computeFallbackStrategyForCore(string $path): string
    {
        if (!file_exists($this->glpi_root . $path)) {
            // Modern controllers
            return self::FALLBACK_STRATEGY;
        }

        $paths = [
            '/front/helpdesk.faq.php' => self::STRATEGY_FAQ_ACCESS,

            '/ajax/common.tabs.php' => self::STRATEGY_NO_CHECK, // specific checks done later to allow anonymous access to public FAQ tabs
            '/ajax/telemetry.php' => self::STRATEGY_NO_CHECK, // Must be available during installation. This script already checks for permissions when the flag usually set by the installer is missing.
            '/front/css.php' => self::STRATEGY_NO_CHECK, // CSS must be accessible also on public pages
            '/front/document.send.php' => self::STRATEGY_NO_CHECK, // may allow unauthenticated access, for public FAQ images
            '/front/locale.php' => self::STRATEGY_NO_CHECK, // locales must be accessible also on public pages
            '/front/login.php' => self::STRATEGY_NO_CHECK,
            '/front/logout.php' => self::STRATEGY_NO_CHECK,
            '/front/initpassword.php' => self::STRATEGY_NO_CHECK,
            '/front/lostpassword.php' => self::STRATEGY_NO_CHECK,
            '/front/updatepassword.php' => self::STRATEGY_NO_CHECK,
            '/install/' => self::STRATEGY_NO_CHECK, // No check during install/update
        ];

        if (Config::allowUnauthenticatedUploads()) {
            $paths['/ajax/fileupload.php'] = self::STRATEGY_NO_CHECK;
            $paths['/ajax/getFileTag.php'] = self::STRATEGY_NO_CHECK;
        }

        foreach ($paths as $checkPath => $strategy) {
            if (\str_starts_with($path, $checkPath)) {
                return $strategy;
            }
        }

        return self::FALLBACK_STRATEGY_FOR_LEGACY_SCRIPTS;
    }

    /**
     * Compute the fallback strategy for plugins resources.
     */
    private function computeFallbackStrategyForPlugin(string $plugin_key, string $plugin_resource): string
    {
        // Check if the file exists to apply the strategies related to legacy scripts
        foreach ($this->plugin_directories as $plugin_dir) {
            $expected_filenames = [
                // A PHP script located in the `/public` directory of a plugin will not have the `/public` prefix in its URL
                $plugin_dir . '/' . $plugin_key . '/public' . $plugin_resource,
            ];
            if (preg_match('#^/(ajax|front|report)/.+#', $plugin_resource) === 1) {
                // Only `/ajax`, `/front` and `/report` can be publicly accessed outside the `/public` dir
                $expected_filenames[] = $plugin_dir . '/' . $plugin_key . $plugin_resource;
            }

            $resource_matches = [];
            if (preg_match('#^(?<filename>.+\.php)(/.*)$#', $plugin_resource, $resource_matches)) {
                // /front/api.php/path/to/endpoint -> /front/api.php
                $expected_filenames[] = $plugin_dir . '/' . $plugin_key . $resource_matches['filename'];
            }

            foreach ($expected_filenames as $expected_filename) {
                if (\file_exists($expected_filename)) {
                    // Try to match a registered strategy
                    $strategies = self::$plugins_legacy_scripts_strategies[$plugin_key] ?? [];
                    foreach ($strategies as $pattern => $strategy) {
                        if (preg_match($pattern, $plugin_resource) === 1) {
                            return $strategy;
                        }
                    }

                    return self::FALLBACK_STRATEGY_FOR_LEGACY_SCRIPTS;
                }
            }
        }

        // Modern controllers
        return self::FALLBACK_STRATEGY;
    }
}
