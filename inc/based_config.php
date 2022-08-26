<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__FILE__, 2));
}

// Notice problem  for date function :
$tz = ini_get('date.timezone');
if (!empty($tz)) {
    date_default_timezone_set($tz);
} else {
    date_default_timezone_set(@date_default_timezone_get());
}

include_once(GLPI_ROOT . "/inc/autoload.function.php");

(function () {
   // Define GLPI_* constants that can be customized by admin.
   //
   // Use a self-invoking anonymous function to:
   // - prevent any global variables/functions definition from `local_define.php` and `downstream.php` files;
   // - prevent any global variables definition from current function logic.

    $constants = [
      // Constants related to system paths
        'GLPI_CONFIG_DIR'      => GLPI_ROOT . '/config', // Path for configuration files (db, security key, ...)
        'GLPI_VAR_DIR'         => GLPI_ROOT . '/files',  // Path for all files
        'GLPI_MARKETPLACE_DIR' => GLPI_ROOT . '/marketplace', // Path for marketplace plugins
        'GLPI_DOC_DIR'         => '{GLPI_VAR_DIR}', // Path for documents storage
        'GLPI_CACHE_DIR'       => '{GLPI_VAR_DIR}/_cache', // Path for cache
        'GLPI_CRON_DIR'        => '{GLPI_VAR_DIR}/_cron', // Path for cron storage
        'GLPI_DUMP_DIR'        => '{GLPI_VAR_DIR}/_dumps', // Path for backup dump
        'GLPI_GRAPH_DIR'       => '{GLPI_VAR_DIR}/_graphs', // Path for graph storage
        'GLPI_LOCAL_I18N_DIR'  => '{GLPI_VAR_DIR}/_locales', // Path for local i18n files
        'GLPI_LOCK_DIR'        => '{GLPI_VAR_DIR}/_lock', // Path for lock files storage (used by cron)
        'GLPI_LOG_DIR'         => '{GLPI_VAR_DIR}/_log', // Path for log storage
        'GLPI_PICTURE_DIR'     => '{GLPI_VAR_DIR}/_pictures', // Path for picture storage
        'GLPI_PLUGIN_DOC_DIR'  => '{GLPI_VAR_DIR}/_plugins', // Path for plugins documents storage
        'GLPI_RSS_DIR'         => '{GLPI_VAR_DIR}/_rss', // Path for rss storage
        'GLPI_SESSION_DIR'     => '{GLPI_VAR_DIR}/_sessions', // Path for sessions storage
        'GLPI_TMP_DIR'         => '{GLPI_VAR_DIR}/_tmp', // Path for temp storage
        'GLPI_UPLOAD_DIR'      => '{GLPI_VAR_DIR}/_uploads', // Path for upload storage
        "GLPI_INVENTORY_DIR"   => '{GLPI_VAR_DIR}/_inventories', //Path for inventories

      // Security constants
        'GLPI_USE_CSRF_CHECK'            => '1',
        'GLPI_CSRF_EXPIRES'              => '7200',
        'GLPI_CSRF_MAX_TOKENS'           => '100',
        'GLPI_USE_IDOR_CHECK'            => '1',
        'GLPI_IDOR_EXPIRES'              => '7200',
        'GLPI_ALLOW_IFRAME_IN_RICH_TEXT' => false,
        'GLPI_SERVERSIDE_URL_ALLOWLIST'  => [
            // allowlist (regex format) of URL that can be fetched from server side (used for RSS feeds and external calendars, among others)
            // URL will be considered as safe as long as it matches at least one entry of the allowlist
            '/^(https?|feed):\/\/[^@:]+(\/.*)?$/', // only accept http/https/feed protocols, and reject presence of @ (username) and : (protocol) in host part of URL
        ],

      // Constants related to GLPI Project / GLPI Network external services
        'GLPI_TELEMETRY_URI'                => 'https://telemetry.glpi-project.org', // Telemetry project URL
        'GLPI_INSTALL_MODE'                 => is_dir(GLPI_ROOT . '/.git') ? 'GIT' : 'TARBALL', // Install mode for telemetry
        'GLPI_NETWORK_MAIL'                 => 'glpi@teclib.com',
        'GLPI_NETWORK_SERVICES'             => 'https://services.glpi-network.com', // GLPI Network services project URL
        'GLPI_NETWORK_REGISTRATION_API_URL' => '{GLPI_NETWORK_SERVICES}/api/registration/',
        'GLPI_MARKETPLACE_PLUGINS_API_URI'  => '{GLPI_NETWORK_SERVICES}/api/glpi-plugins/',
        'GLPI_MARKETPLACE_ALLOW_OVERRIDE'   => true, // allow marketplace to override a plugin found outside GLPI_MARKETPLACE_DIR
        'GLPI_MARKETPLACE_MANUAL_DOWNLOADS' => true, // propose manual download link of plugins which cannot be installed/updated by marketplace
        'GLPI_USER_AGENT_EXTRA_COMMENTS'    => '', // Extra comment to add to GLPI User-Agent

      // SQL compatibility
        'GLPI_DISABLE_ONLY_FULL_GROUP_BY_SQL_MODE' => '1', // '1' to disable ONLY_FULL_GROUP_BY 'sql_mode'

      // Other constants
        'GLPI_AJAX_DASHBOARD'         => '1',
        'GLPI_CALDAV_IMPORT_STATE'    => 0, // external events created from a caldav client will take this state by default (0 = Planning::INFO)
        'GLPI_DEMO_MODE'              => '0',
        'GLPI_CENTRAL_WARNINGS'       => '1', // display (1), or not (0), warnings on GLPI Central page
    ];

   // Define constants values based on server env variables (i.e. defined using apache SetEnv directive)
    foreach (array_keys($constants) as $name) {
        if (!defined($name) && ($value = getenv($name)) !== false) {
            define($name, $value);
        }
    }

   // Define constants values from local configuration file
    if (file_exists(GLPI_ROOT . '/config/local_define.php') && !defined('TU_USER')) {
        require_once GLPI_ROOT . '/config/local_define.php';
    }

   // Define constants values from downstream distribution file
    if (file_exists(GLPI_ROOT . '/inc/downstream.php')) {
        include_once(GLPI_ROOT . '/inc/downstream.php');
    }

   // Define constants values from defaults
   // 1. First, define constants that does not inherit from another one.
   // 2. Second, define constants that inherits from another one.
   // This logic is quiet simple and is not made to handle chain inheritance.
    $inherit_pattern = '/\{(?<name>GLPI_[\w]+)\}/';
    foreach ($constants as $key => $value) {
        if (!defined($key) && (!is_string($value) || !preg_match($inherit_pattern, $value))) {
            define($key, $value);
        }
    }
    foreach ($constants as $key => $value) {
        if (!defined($key)) {
           // Replace {GLPI_*} by value of corresponding constant
            $value = preg_replace_callback(
                '/\{(?<name>GLPI_[\w]+)\}/',
                function ($matches) {
                    return defined($matches['name']) ? constant($matches['name']) : '';
                },
                $value
            );

            define($key, $value);
        }
    }

   // Where to load plugins.
   // Order in this array is important (priority to first found).
    if (!defined('PLUGINS_DIRECTORIES')) {
        define('PLUGINS_DIRECTORIES', [
            GLPI_MARKETPLACE_DIR,
            GLPI_ROOT . '/plugins',
        ]);
    } else if (!is_array(PLUGINS_DIRECTORIES)) {
        throw new \Exception('PLUGINS_DIRECTORIES constant value must be an array');
    }
})();

define('GLPI_I18N_DIR', GLPI_ROOT . "/locales");

include_once(GLPI_ROOT . "/inc/define.php");
