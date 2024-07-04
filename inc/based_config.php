<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

// Check the resources state before trying to autoload GLPI init logic.
// It must be done here as this check must be done even when the init logic
// cannot be executed due to missing dependencies.
require_once GLPI_ROOT . '/src/Glpi/Application/ResourcesChecker.php';
(new \Glpi\Application\ResourcesChecker(GLPI_ROOT))->checkResources();

require_once GLPI_ROOT . '/vendor/autoload.php';

require_once GLPI_ROOT . '/src/Glpi/Application/ConfigurationConstants.php';
(new \Glpi\Application\ConfigurationConstants(GLPI_ROOT))->computeConstants();

// Check if web root is configured correctly
if (!isCommandLine()) {
    $included_files = array_filter(
        get_included_files(),
        function (string $included_file) {
            // prevent `tests/router.php` to be considered as initial script
            return realpath($included_file) !== realpath(sprintf('%s/tests/router.php', GLPI_ROOT));
        }
    );

    $initial_script = array_shift($included_files) ?? __FILE__;

    // If `auto_prepend_file` configuration is used, ignore first included files
    // as long as they are not located inside GLPI directory tree.
    $prepended_file = ini_get('auto_prepend_file');
    if ($prepended_file !== '' && $prepended_file !== 'none') {
        $prepended_file = stream_resolve_include_path($prepended_file);
        while (
            $initial_script !== null
            && !str_starts_with(
                realpath($initial_script) ?: '',
                realpath(GLPI_ROOT)
            )
        ) {
            $initial_script = array_shift($included_files);
        }
    }

    if (realpath($initial_script) !== realpath(sprintf('%s/public/index.php', GLPI_ROOT))) {
        echo sprintf(
            'Web server root directory configuration is incorrect, it should be "%s". See installation documentation for more details.' . PHP_EOL,
            realpath(sprintf('%s/public', GLPI_ROOT))
        );
        exit(1);
    }
}

// For plugins
/**
 * @var array $PLUGIN_HOOKS
 */
global $PLUGIN_HOOKS;
$PLUGIN_HOOKS     = [];
