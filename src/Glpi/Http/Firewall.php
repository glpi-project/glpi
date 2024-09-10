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

/**
 * @since 10.0.10
 */
final class Firewall implements FirewallInterface
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
     * Default strategy to apply (except for legacy scripts).
     */
    private const STRATEGY_DEFAULT = self::STRATEGY_CENTRAL_ACCESS;

    /**
     * Security strategy to apply by default on core legacy scripts.
     */
    private const STRATEGY_DEFAULT_FOR_CORE_LEGACY = self::STRATEGY_AUTHENTICATED;

    /**
     * Security strategy to apply by default on plugins legacy scripts.
     *
     * @TODO In GLPI 11.0, raise default level to `self::STRATEGY_AUTHENTICATED`.
     *       It requires to give to plugins the ability to define a specific strategy for legacy files.
     */
    private const STRATEGY_DEFAULT_FOR_PLUGINS_LEGACY = self::STRATEGY_NO_CHECK;

    /**
     * GLPI URLs path prefix.
     * @var string
     */
    private string $path_prefix;

    /**
     * GLPI root directory.
     * @var string
     */
    private string $root_dir;

    /**
     * GLPI plugins root directories.
     * @var string[]
     */
    private array $plugins_dirs;

    /**
     * @param string  $path_prefix   GLPI URLs path prefix
     * @param ?string $root_dir      GLPI root directory on filesystem
     * @param ?array  $plugins_dirs  GLPI plugins root directories on filesystem
     */
    public function __construct(string $path_prefix, ?string $root_dir = null, ?array $plugins_dirs = null)
    {
        $this->path_prefix = $path_prefix;
        $this->root_dir = $root_dir ?? \GLPI_ROOT;
        $this->plugins_dirs = $plugins_dirs ?? \PLUGINS_DIRECTORIES;
    }

    public static function createDefault(): self
    {
        /**
         * @var array $CFG_GLPI
         */
        global $CFG_GLPI;

        return new Firewall($CFG_GLPI['root_doc']);
    }

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

    public function computeFallbackStrategy(string $path): string
    {
        $unprefixed_path = preg_replace('/^' . preg_quote($this->path_prefix, '/') . '/', '', $path);

        // Check if endpoint is a plugin endpoint
        $is_plugin_endpoint = false;
        foreach ($this->plugins_dirs as $plugins_dir) {
            $relative_path = preg_replace(
                '/^' . preg_quote($this->normalizePath($this->root_dir), '/') . '/',
                '',
                $this->normalizePath($plugins_dir)
            );

            if (preg_match('/^' . preg_quote($relative_path, '/') . '\//', $unprefixed_path) === 1) {
                $is_plugin_endpoint = true;
                break;
            }
        }

        // Legacy script
        if (file_exists($this->root_dir . $unprefixed_path)) {
            return $is_plugin_endpoint
                ? self::STRATEGY_DEFAULT_FOR_PLUGINS_LEGACY
                : $this->computeStrategyForCoreLegacyScript($unprefixed_path);
        }

        // Modern controllers
        return self::STRATEGY_DEFAULT;
    }

    /**
     * Normalize a path, to make comparisons and relative paths computation easier.
     *
     * @param string $path
     * @return string
     */
    private function normalizePath(string $path): string
    {
        $realpath = realpath($path);
        if ($realpath !== false) {
            // Use realpath if possible.
            // As `realpath()` will return `false` on streams, we cannot always use it, or we will not be able to do unit tests on this method.
            $path = $realpath;
        }

        // Normalize all directory separators to `/`.
        $path = preg_replace('/\\\/', '/', $path);
        return $path;
    }

    /**
     * Compute the strategy for GLPI legacy scripts.
     */
    private function computeStrategyForCoreLegacyScript(string $path): string
    {
        if (isset($_GET["embed"], $_GET["dashboard"]) && str_starts_with($path, '/front/central.php')) {
            // Allow anonymous access for embed dashboards.
            return 'no_check';
        }

        if (isset($_GET["token"]) && str_starts_with($path, '/front/planning.php')) {
            // Token based access for ical/webcal access can be made anonymously.
            return 'no_check';
        }

        $paths = [
            '/ajax/knowbase.php' => self::STRATEGY_FAQ_ACCESS,
            '/front/helpdesk.faq.php' => self::STRATEGY_FAQ_ACCESS,

            '/ajax/common.tabs.php' => self::STRATEGY_NO_CHECK, // specific checks done later to allow anonymous access to public FAQ tabs
            '/ajax/dashboard.php' => self::STRATEGY_NO_CHECK, // specific checks done later to allow anonymous access to embed dashboards
            '/ajax/telemetry.php' => self::STRATEGY_NO_CHECK, // Must be available during installation. This script already checks for permissions when the flag usually set by the installer is missing.
            '/front/cron.php' => self::STRATEGY_NO_CHECK, // in GLPI mode, cronjob can also be triggered from public pages
            '/front/css.php' => self::STRATEGY_NO_CHECK, // CSS must be accessible also on public pages
            '/front/document.send.php' => self::STRATEGY_NO_CHECK, // may allow unauthenticated access, for public FAQ images
            '/front/helpdesk.php' => self::STRATEGY_NO_CHECK, // Anonymous access may be allowed by configuration.
            '/front/inventory.php' => self::STRATEGY_NO_CHECK, // allow anonymous requests from inventory agent
            '/front/locale.php' => self::STRATEGY_NO_CHECK, // locales must be accessible also on public pages
            '/front/login.php' => self::STRATEGY_NO_CHECK,
            '/front/logout.php' => self::STRATEGY_NO_CHECK,
            '/front/lostpassword.php' => self::STRATEGY_NO_CHECK,
            '/front/tracking.injector.php' => self::STRATEGY_NO_CHECK, // Anonymous access may be allowed by configuration.
            '/front/updatepassword.php' => self::STRATEGY_NO_CHECK,
        ];

        foreach ($paths as $checkPath => $strategy) {
            if (\str_starts_with($path, $checkPath)) {
                return $strategy;
            }
        }

        return self::STRATEGY_DEFAULT_FOR_CORE_LEGACY;
    }
}
