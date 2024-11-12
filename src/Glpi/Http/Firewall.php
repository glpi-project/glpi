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

namespace Glpi\Http;

use Session;
use Symfony\Component\HttpFoundation\Request;

/**
 * @since 10.0.10
 */
final class Firewall
{
    /**
     * Nothing to check. Entrypoint accepts anonymous access.
     */
    public const STRATEGY_NO_CHECK = 'no_check';

    /**
     * Check that user is authenticated.
     */
    public const STRATEGY_AUTHENTICATED = 'authenticated';

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
     * GLPI root directory.
     */
    private string $root_dir;

    /**
     * GLPI plugins root directories.
     * @var string[]
     */
    private array $plugins_dirs;

    /**
     * Registered plugins strategies for legacy scripts.
     *
     * @phpstan-var array<string, array<string, self::STRATEGY_*>>
     */
    private static array $plugins_legacy_scripts_strategies = [];

    /**
     * @param ?string $root_dir             GLPI root directory on filesystem
     * @param ?array  $plugins_dirs         GLPI plugins root directories on filesystem
     */
    public function __construct(?string $root_dir = null, ?array $plugins_dirs = null)
    {
        $this->root_dir = $root_dir ?? \GLPI_ROOT;
        $this->plugins_dirs = $plugins_dirs ?? \PLUGINS_DIRECTORIES;
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
                throw new \LogicException(sprintf('Invalid firewall strategy `%s`.', $strategy));
        }
    }

    /**
     * Compute the fallback strategy for given path.
     */
    public function computeFallbackStrategy(Request $request): string
    {
        $unprefixed_path = preg_replace(
            '/^' . preg_quote($request->getBasePath(), '/') . '/',
            '',
            $request->getPathInfo()
        );

        $path_matches = [];
        $plugin_path_pattern = '#^/(plugins|marketplace)/(?<plugin_key>[^/]+)(?<plugin_resource>/.+)$#';
        if (preg_match($plugin_path_pattern, $unprefixed_path, $path_matches) === 1) {
            return $this->computeFallbackStrategyForPlugin(
                $path_matches['plugin_key'],
                $path_matches['plugin_resource']
            );
        }

        return $this->computeFallbackStrategyForCore($unprefixed_path);
    }

    /**
     * Compute the fallback strategy for GLPI resources.
     */
    private function computeFallbackStrategyForCore(string $path): string
    {
        if (!file_exists($this->root_dir . $path)) {
            // Modern controllers
            return self::FALLBACK_STRATEGY;
        }

        if (isset($_GET["embed"], $_GET["dashboard"]) && str_starts_with($path, '/front/central.php')) {
            // Allow anonymous access for embed dashboards.
            return 'no_check';
        }

        if (isset($_GET["token"]) && str_starts_with($path, '/front/planning.php')) {
            // Token based access for ical/webcal access can be made anonymously.
            return 'no_check';
        }

        $paths = [
            '/front/helpdesk.faq.php' => self::STRATEGY_FAQ_ACCESS,

            '/ajax/common.tabs.php' => self::STRATEGY_NO_CHECK, // specific checks done later to allow anonymous access to public FAQ tabs
            '/ajax/dashboard.php' => self::STRATEGY_NO_CHECK, // specific checks done later to allow anonymous access to embed dashboards
            '/ajax/telemetry.php' => self::STRATEGY_NO_CHECK, // Must be available during installation. This script already checks for permissions when the flag usually set by the installer is missing.
            '/front/cron.php' => self::STRATEGY_NO_CHECK, // in GLPI mode, cronjob can also be triggered from public pages
            '/front/css.php' => self::STRATEGY_NO_CHECK, // CSS must be accessible also on public pages
            '/front/document.send.php' => self::STRATEGY_NO_CHECK, // may allow unauthenticated access, for public FAQ images
            '/front/inventory.php' => self::STRATEGY_NO_CHECK, // allow anonymous requests from inventory agent
            '/front/locale.php' => self::STRATEGY_NO_CHECK, // locales must be accessible also on public pages
            '/front/login.php' => self::STRATEGY_NO_CHECK,
            '/front/logout.php' => self::STRATEGY_NO_CHECK,
            '/front/lostpassword.php' => self::STRATEGY_NO_CHECK,
            '/front/updatepassword.php' => self::STRATEGY_NO_CHECK,
            '/install/' => self::STRATEGY_NO_CHECK, // No check during install/update
        ];

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
        // Check if the file exists to apply the strategies related to legacyy scripts
        foreach ($this->plugins_dirs as $plugin_dir) {
            $expected_filenames = [
                $plugin_dir . '/' . $plugin_key . $plugin_resource,
            ];
            $resource_matches = [];
            if (\preg_match('#^(?<filename>.+\.php)(/.*)$#', $plugin_resource, $resource_matches)) {
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
