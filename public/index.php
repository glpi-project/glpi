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

/**
 * GLPI web router.
 *
 * This router is used to be able to expose only the `/public` directory on the webserver.
 */

$glpi_root  = realpath(dirname(__FILE__, 2));

if (preg_match('/^\/public/', $_SERVER['REQUEST_URI']) !== 1 && $_SERVER['SCRIPT_NAME'] === '/public/index.php') {
    // When requested URI does not start with '/public' but `$_SERVER['SCRIPT_NAME']` is '/public/index.php',
    // it means that document root is the GLPI root directory, but a rewrite rule redirects the request to the PHP router.
    // This case happen when redirection to PHP router is made by an `.htaccess` file placed in the GLPI root directory,
    // and has to be handled to support shared hosting where it is not possible to change the web server root directory.
    $uri_prefix = '';
} else {
    // `$_SERVER['SCRIPT_NAME']` corresponds to the script path relative to server document root.
    // -> if server document root is `/public`, then `$_SERVER['SCRIPT_NAME']` will be equal to `/index.php`
    // -> if script is located into a `/glpi-alias` alias directory, then `$_SERVER['SCRIPT_NAME']` will be equal to `/glpi-alias/index.php`
    $uri_prefix = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
}

// Get URI path relative to GLPI (i.e. without alias directory prefix).
$path       = preg_replace(
    '/^' . preg_quote($uri_prefix, '/') . '/',
    '',
    parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)
);

require $glpi_root . '/src/Http/ProxyRouter.php';

$proxy = new \Glpi\Http\ProxyRouter($glpi_root, $path);

if ($proxy->isTargetAPhpScript() && $proxy->isPathAllowed() && ($target_file = $proxy->getTargetFile()) !== null) {
    // Ensure `getcwd()` and inclusion path is based on requested file FS location.
    chdir(dirname($target_file));

    // Redefine some $_SERVER variables to have same values whenever scripts are called directly
    // or through current router.
    $target_path     = $uri_prefix . $proxy->getTargetPath();
    $target_pathinfo = $proxy->getTargetPathInfo();
    $_SERVER['PATH_INFO']       = $target_pathinfo;
    $_SERVER['PHP_SELF']        = $target_path;
    $_SERVER['SCRIPT_FILENAME'] = $target_file;
    $_SERVER['SCRIPT_NAME']     = $target_path;

    // Execute target script.
    require($target_file);
    exit();
}

$proxy->proxify();
