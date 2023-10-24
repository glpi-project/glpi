<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

$SECURITY_STRATEGY = 'no_check'; // CSS must be accessible also on public pages

if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__));
}

use Glpi\Application\ErrorHandler;
use Glpi\UI\ThemeManager;

$_GET["donotcheckversion"]   = true;
$dont_check_maintenance_mode = true;
$skip_db_check               = true;

//std cache, with DB connection
include_once GLPI_ROOT . "/inc/db.function.php";
include_once GLPI_ROOT . '/inc/config.php';

// Main CSS compilation requires about 140MB of memory on PHP 7.4 (110MB on PHP 8.2).
// Ensure to have enough memory to not reach memory limit.
$max_memory = 192;
if (Toolbox::getMemoryLimit() < ($max_memory * 1024 * 1024)) {
    ini_set('memory_limit', sprintf('%dM', $max_memory));
}

// Ensure warnings will not break CSS output.
ErrorHandler::getInstance()->disableOutput();

// If a custom theme is requested, we need to get the real path of the theme
if (isset($_GET['file']) && isset($_GET['is_custom_theme']) && $_GET['is_custom_theme']) {
    $theme = ThemeManager::getInstance()->getTheme($_GET['file']);

    if (!$theme) {
        trigger_error(sprintf('Unable to find theme `%s`.', $_GET['file']), E_USER_WARNING);
        $theme = ThemeManager::getInstance()->getTheme(ThemeManager::DEFAULT_THEME);
    }

    $_GET['file'] = $theme->getPath();
}

$css = Html::compileScss($_GET);

header('Content-Type: text/css');

$is_cacheable = !isset($_GET['nocache'])
    && GLPI_ENVIRONMENT_TYPE !== GLPI::ENV_DEVELOPMENT // do not use browser cache on development env
;
if ($is_cacheable) {
   // Makes CSS cacheable by browsers and proxies
    $max_age = WEEK_TIMESTAMP;
    header_remove('Pragma');
    header('Cache-Control: public');
    header('Cache-Control: max-age=' . $max_age);
    header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + $max_age));
}

echo $css;
