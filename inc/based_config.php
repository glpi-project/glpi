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

// Check if dependencies are up to date
$needrun  = false;

// composer dependencies
$autoload = GLPI_ROOT . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    $needrun = true;
} else if (file_exists(GLPI_ROOT . '/composer.lock')) {
    if (!file_exists(GLPI_ROOT . '/.composer.hash')) {
       /* First time */
        $needrun = true;
    } else if (sha1_file(GLPI_ROOT . '/composer.lock') != file_get_contents(GLPI_ROOT . '/.composer.hash')) {
       /* update */
        $needrun = true;
    }
}

// node dependencies
if (!file_exists(GLPI_ROOT . '/public/lib')) {
    $needrun = true;
} else if (file_exists(GLPI_ROOT . '/package-lock.json')) {
    if (!file_exists(GLPI_ROOT . '/.package.hash')) {
       /* First time */
        $needrun = true;
    } else if (sha1_file(GLPI_ROOT . '/package-lock.json') != file_get_contents(GLPI_ROOT . '/.package.hash')) {
       /* update */
        $needrun = true;
    }
}

if ($needrun) {
    $deps_install_msg = 'Application dependencies are not up to date.' . PHP_EOL
      . 'Run "php bin/console dependencies install" in the glpi tree to fix this.' . PHP_EOL;
    if (isCommandLine()) {
        echo $deps_install_msg;
    } else {
        echo nl2br($deps_install_msg);
    }
    die(1);
}

// Check if locales are compiled.
$need_mo_compile = false;
$locales_files = scandir(GLPI_ROOT . '/locales');
$po_files = preg_grep('/\.po$/', $locales_files);
$mo_files = preg_grep('/\.mo$/', $locales_files);
if (count($mo_files) < count($po_files)) {
    $need_mo_compile = true;
} else if (file_exists(GLPI_ROOT . '/locales/glpi.pot')) {
   // Assume that `locales/glpi.pot` file only exists when installation mode is GIT
    foreach ($po_files as $po_file) {
        $po_file = GLPI_ROOT . '/locales/' . $po_file;
        $mo_file = preg_replace('/\.po$/', '.mo', $po_file);
        if (!file_exists($mo_file) || filemtime($mo_file) < filemtime($po_file)) {
            $need_mo_compile = true;
            break; // No need to scan the whole dir
        }
    }
}
if ($need_mo_compile) {
    $mo_compile_msg = 'Application locales have to be compiled.' . PHP_EOL
      . 'Run "php bin/console locales:compile" in the glpi tree to fix this.' . PHP_EOL;
    if (isCommandLine()) {
        echo $mo_compile_msg;
    } else {
        echo nl2br($mo_compile_msg);
    }
    die(1);
}

require_once $autoload;

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
