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

use Glpi\Console\Exception\GlpiMisconfiguredException;
use Glpi\Exception\NeedsGlpiUpdateException;
use Session;
use Auth;
use DBConnection;
use Config;
use Html;
use Toolbox;
use Update;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Cache\CacheManager;
use Glpi\Config\LegacyConfigProviderInterface;

final readonly class StandardIncludes implements LegacyConfigProviderInterface
{
    public function execute(): void
    {
        /**
         * @var array $CFG_GLPI
         * @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE
         */
        global $CFG_GLPI,
               $GLPI_CACHE
        ;

        if (isset($_SESSION['is_installing'])) {
            // Force `root_doc` value
            $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
            $CFG_GLPI['root_doc'] = $request->getBasePath();

            $GLPI_CACHE = (new CacheManager())->getInstallerCacheInstance();

            Session::loadLanguage(with_plugins: false);
            return;
        }

        Config::detectRootDoc();

        $skip_db_checks = false;
        $skip_maintenance_checks = false;
        if (array_key_exists('REQUEST_URI', $_SERVER)) {
            if (preg_match('#^' . $CFG_GLPI['root_doc'] . '/front/(css|locale).php#', $_SERVER['REQUEST_URI']) === 1) {
                $skip_db_checks  = true;
                $skip_maintenance_checks = true;
            }

            $no_db_checks_scripts = [
                '#^' . $CFG_GLPI['root_doc'] . '/$#',
                '#^' . $CFG_GLPI['root_doc'] . '/index.php#',
                '#^' . $CFG_GLPI['root_doc'] . '/install/install.php#',
                '#^' . $CFG_GLPI['root_doc'] . '/install/update.php#',
            ];
            foreach ($no_db_checks_scripts as $pattern) {
                if (preg_match($pattern, $_SERVER['REQUEST_URI']) === 1) {
                    $skip_db_checks = true;
                    break;
                }
            }
        }

        //init cache
        $cache_manager = new CacheManager();
        $GLPI_CACHE = $cache_manager->getCoreCacheInstance();

        // Check if the DB is configured properly
        if (!file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
            $missing_db_config = true;
        } else {
            include_once(GLPI_CONFIG_DIR . "/config_db.php");
            $missing_db_config = !class_exists('DB', false);
        }
        if (!$missing_db_config) {
            //Database connection
            if (
                !DBConnection::establishDBConnection(false, false)
                && !$skip_db_checks
            ) {
                throw new \RuntimeException(DBConnection::getLastDatabaseError());
            }

            //Options from DB, do not touch this part.
            if (
                !Config::loadLegacyConfiguration()
                && !$skip_db_checks
            ) {
                throw new \RuntimeException('Error accessing config table');
            }
        } elseif (!$skip_db_checks) {
            Session::loadLanguage('', false);

            throw new GlpiMisconfiguredException();
        }

        if (
            isCommandLine()
            && !defined('TU_USER') // In test suite context, used --debug option is the atoum one
            && isset($_SERVER['argv'])
        ) {
            $key = array_search('--debug', $_SERVER['argv']);
            if ($key) {
                $_SESSION['glpi_use_mode'] = Session::DEBUG_MODE;
                unset($_SERVER['argv'][$key]);
                $_SERVER['argv']           = array_values($_SERVER['argv']);
                $_SERVER['argc']--;
            }
        }
        Toolbox::setDebugMode();

        if (isset($_SESSION["glpiroot"]) && $CFG_GLPI["root_doc"] != $_SESSION["glpiroot"]) {
            // When `$_SESSION["glpiroot"]` differs from `$CFG_GLPI["root_doc"]`, it means that
            // either web server configuration changed,
            // either session was initialized on another GLPI instance.
            // Destroy session and redirect to login to ensure that session from another GLPI instance is not reused.
            Session::destroy();
            Auth::setRememberMeCookie('');
            Html::redirectToLogin();
        }

        if (!isset($_SESSION["glpilanguage"])) {
            $_SESSION["glpilanguage"] = Session::getPreferredLanguage();
        }

        // Override cfg_features by session value
        foreach ($CFG_GLPI['user_pref_field'] as $field) {
            if (!isset($_SESSION["glpi$field"]) && isset($CFG_GLPI[$field])) {
                $_SESSION["glpi$field"] = $CFG_GLPI[$field];
            }
        }

        // Check maintenance mode
        if (
            !$skip_maintenance_checks
            && isset($CFG_GLPI["maintenance_mode"])
            && $CFG_GLPI["maintenance_mode"]
        ) {
            if (isset($_GET['skipMaintenance']) && $_GET['skipMaintenance']) {
                $_SESSION["glpiskipMaintenance"] = 1;
            }

            if (!isset($_SESSION["glpiskipMaintenance"]) || !$_SESSION["glpiskipMaintenance"]) {
                Session::loadLanguage('', false);
                if (isCommandLine()) {
                    echo __('Service is down for maintenance. It will be back shortly.');
                    echo "\n";
                } else {
                    TemplateRenderer::getInstance()->display('maintenance.html.twig', [
                        'title'            => "MAINTENANCE MODE",
                        'maintenance_text' => $CFG_GLPI["maintenance_text"] ?? "",
                    ]);
                }
                exit();
            }
        }

        // Check version
        if (!$skip_db_checks && !defined('SKIP_UPDATES') && !Update::isDbUpToDate()) {
            Session::checkCookieSecureConfig();

            throw new NeedsGlpiUpdateException();
        }

        // First call to `Config::detectRootDoc()` cannot compute the value
        // in CLI context, as it requires DB connection to be up.
        // Now DB is up, so value can be computed.
        if (!isset($CFG_GLPI['root_doc'])) {
            Config::detectRootDoc();
        }

        // Load Language file
        Session::loadLanguage();
    }
}
