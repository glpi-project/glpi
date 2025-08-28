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

// This file contains stubs for GLPI constants.
// Please try to keep them alphabetically ordered.
// Keep in sync with the dynamicConstantNames config option in the PHPStan config file

// Wrap in a function to be sure to never declare any variable in the global scope.
(static function () {
    $random_val = static fn(array $values) => $values[array_rand($values)];

    // Directories constants
    define('GLPI_CACHE_DIR', dirname(__FILE__, 2) . '/files/_cache');
    define('GLPI_CONFIG_DIR', dirname(__FILE__, 2) . '/config');
    define('GLPI_CRON_DIR', dirname(__FILE__, 2) . '/files/_cron');
    define('GLPI_DOC_DIR', dirname(__FILE__, 2) . '/files');
    define('GLPI_GRAPH_DIR', dirname(__FILE__, 2) . '/files/_graphs');
    define('GLPI_INVENTORY_DIR', dirname(__FILE__, 2) . '/files/_inventories');
    define('GLPI_LOCAL_I18N_DIR', dirname(__FILE__, 2) . '/files/_locales');
    define('GLPI_LOCK_DIR', dirname(__FILE__, 2) . '/files/_lock');
    define('GLPI_LOG_DIR', dirname(__FILE__, 2) . '/files/_log');
    define('GLPI_MARKETPLACE_DIR', dirname(__FILE__, 2) . '/marketplace');
    define('GLPI_PICTURE_DIR', dirname(__FILE__, 2) . '/files/_pictures');
    define('GLPI_PLUGIN_DOC_DIR', dirname(__FILE__, 2) . '/files/_plugins');
    define('GLPI_RSS_DIR', dirname(__FILE__, 2) . '/files/_rss');
    define('GLPI_SESSION_DIR', dirname(__FILE__, 2) . '/files/_sessions');
    define('GLPI_THEMES_DIR', dirname(__FILE__, 2) . '/files/_themes');
    define('GLPI_TMP_DIR', dirname(__FILE__, 2) . '/files/_tmp');
    define('GLPI_UPLOAD_DIR', dirname(__FILE__, 2) . '/files/_uploads');
    define('GLPI_VAR_DIR', dirname(__FILE__, 2) . '/files');

    // Optionnal constants
    if ($random_val([false, true]) === true) {
        define('GLPI_FORCE_MAIL', 'example@glpi-project.org');
    }

    // Other constants
    define('GLPI_AJAX_DASHBOARD', $random_val([false, true]));
    define('GLPI_ALLOW_IFRAME_IN_RICH_TEXT', $random_val([false, true]));
    define('GLPI_CALDAV_IMPORT_STATE', $random_val([0, 1, 2]));
    define('GLPI_CENTRAL_WARNINGS', $random_val([false, true]));
    define('GLPI_DOCUMENTATION_ROOT_URL', 'https://links.glpi-project.org');
    define('GLPI_DISABLE_ONLY_FULL_GROUP_BY_SQL_MODE', $random_val([false, true]));
    define('GLPI_DISALLOWED_UPLOADS_PATTERN', $random_val(['', '/\.(php\d*|phar)$/i']));
    define('GLPI_ENVIRONMENT_TYPE', $random_val(['development', 'testing', 'staging', 'production']));
    define('GLPI_INSTALL_MODE', $random_val(['GIT', 'TARBALL']));
    define('GLPI_LOG_LVL', $random_val(['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug']));
    define('GLPI_MARKETPLACE_ALLOW_OVERRIDE', $random_val([false, true]));
    define('GLPI_MARKETPLACE_ENABLE', $random_val([0, 1, 2, 3]));
    define('GLPI_MARKETPLACE_MANUAL_DOWNLOADS', $random_val([false, true]));
    define('GLPI_MARKETPLACE_PLUGINS_API_URI', 'https://services.glpi-network.com/api/marketplace/');
    define('GLPI_MARKETPLACE_PRERELEASES', $random_val([false, true]));
    define('GLPI_NETWORK_REGISTRATION_API_URL', 'https://services.glpi-network.com/api/registration/');
    define('GLPI_NETWORK_MAIL', 'glpi@teclib.com');
    define('GLPI_NETWORK_SERVICES', 'https://services.glpi-network.com');
    define('GLPI_PLUGINS_DIRECTORIES', [dirname(__FILE__, 2) . '/plugins', dirname(__FILE__, 2) . '/marketplace']);
    define('GLPI_SERVERSIDE_URL_ALLOWLIST', $random_val([[], ['/^.*$/']]));
    define('GLPI_SKIP_UPDATES', $random_val([false, true]));
    define('GLPI_STRICT_ENV', $random_val([false, true]));
    define('GLPI_SYSTEM_CRON', $random_val([false, true]));
    define('GLPI_TELEMETRY_URI', 'https://telemetry.glpi-project.org');
    define('GLPI_TEXT_MAXSIZE', $random_val([1000, 2000, 3000, 4000]));
    define('GLPI_USER_AGENT_EXTRA_COMMENTS', $random_val(['', 'app-version:5']));
    define('GLPI_WEBHOOK_ALLOW_RESPONSE_SAVING', $random_val([false, true]));
    define('GLPI_WEBHOOK_CRA_MANDATORY', $random_val([false, true]));
})();
