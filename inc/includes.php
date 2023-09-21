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

use Glpi\Http\Firewall;
use Glpi\Toolbox\Sanitizer;

if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__));
}

include_once GLPI_ROOT . '/inc/based_config.php';

// Init Timer to compute time of display
$TIMER_DEBUG = new Timer();
$TIMER_DEBUG->start();
\Glpi\Debug\Profiler::getInstance()->start('php_request');


/// TODO try to remove them if possible
include_once(GLPI_ROOT . "/inc/db.function.php");

// Standard includes
include_once(GLPI_ROOT . "/inc/config.php");

// Security of PHP_SELF
$_SERVER['PHP_SELF'] = Html::cleanParametersURL($_SERVER['PHP_SELF']);

if (!isCommandLine()) {
    $firewall = new Firewall($CFG_GLPI['root_doc']);
    $firewall->applyStrategy($_SERVER['PHP_SELF'], $SECURITY_STRATEGY ?? null);
}

// Load Language file
Session::loadLanguage();

if (
    isset($_SESSION['glpi_use_mode'])
    && ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
) {
    // Start the debug profile
    $profile = \Glpi\Debug\Profile::getCurrent();
    $SQL_TOTAL_REQUEST    = 0;
    $DEBUG_SQL = [
        'queries' => [],
        'errors'  => [],
        'times'   => [],
    ];
}

// Mark if Header is loaded or not :
$HEADER_LOADED = false;
$FOOTER_LOADED = false;
if (isset($AJAX_INCLUDE)) {
    $HEADER_LOADED = true;
}

/* On startup, register all plugins configured for use. */
if (!isset($PLUGINS_INCLUDED)) {
   // PLugin already included
    $PLUGINS_INCLUDED = 1;
    $PLUGINS_EXCLUDED = isset($PLUGINS_EXCLUDED) ? $PLUGINS_EXCLUDED : [];
    $plugin = new Plugin();
    $plugin->init(true, $PLUGINS_EXCLUDED);
}

// Security system
if (isset($_POST)) {
    $_UPOST = $_POST; //keep raw, as a workaround
    if (isset($_POST['_glpi_simple_form'])) {
        $_POST = array_map('urldecode', $_POST);
    }
    $_POST = Sanitizer::sanitize($_POST);
}
if (isset($_GET)) {
    $_UGET = $_GET; //keep raw, as a workaround
    $_GET  = Sanitizer::sanitize($_GET);
}
if (isset($_REQUEST)) {
    $_UREQUEST = $_REQUEST; //keep raw, as a workaround
    $_REQUEST  = Sanitizer::sanitize($_REQUEST);
}
if (isset($_FILES)) {
    $_UFILES = $_FILES; //keep raw, as a workaround
    foreach ($_FILES as &$file) {
        $file['name'] = Sanitizer::sanitize($file['name']);
    }
}
unset($file);

if (!isset($_SESSION["MESSAGE_AFTER_REDIRECT"])) {
    $_SESSION["MESSAGE_AFTER_REDIRECT"] = [];
}

// Manage force tab
if (isset($_REQUEST['forcetab'])) {
    if (preg_match('/\/plugins\/([a-zA-Z]+)\/front\/([a-zA-Z]+).form.php/', $_SERVER['PHP_SELF'], $matches)) {
        $itemtype = 'plugin' . $matches[1] . $matches[2];
        Session::setActiveTab($itemtype, $_REQUEST['forcetab']);
    } else if (preg_match('/([a-zA-Z]+).form.php/', $_SERVER['PHP_SELF'], $matches)) {
        $itemtype = $matches[1];
        Session::setActiveTab($itemtype, $_REQUEST['forcetab']);
    } else if (preg_match('/\/plugins\/([a-zA-Z]+)\/front\/([a-zA-Z]+).php/', $_SERVER['PHP_SELF'], $matches)) {
        $itemtype = 'plugin' . $matches[1] . $matches[2];
        Session::setActiveTab($itemtype, $_REQUEST['forcetab']);
    } else if (preg_match('/([a-zA-Z]+).php/', $_SERVER['PHP_SELF'], $matches)) {
        $itemtype = $matches[1];
        Session::setActiveTab($itemtype, $_REQUEST['forcetab']);
    }
}
// Manage tabs
if (isset($_REQUEST['glpi_tab']) && isset($_REQUEST['itemtype'])) {
    Session::setActiveTab($_REQUEST['itemtype'], Sanitizer::unsanitize($_REQUEST['glpi_tab']));
}
// Override list-limit if choosen
if (isset($_REQUEST['glpilist_limit'])) {
    $_SESSION['glpilist_limit'] = $_REQUEST['glpilist_limit'];
}

// Security : check CSRF token
if (
    GLPI_USE_CSRF_CHECK
    && !isAPI()
    && isset($_POST) && is_array($_POST) && count($_POST)
) {
    if (preg_match(':' . $CFG_GLPI['root_doc'] . '(/(plugins|marketplace)/[^/]*|)/ajax/:', $_SERVER['REQUEST_URI']) === 1) {
       // Keep CSRF token as many AJAX requests may be made at the same time.
       // This is due to the fact that read operations are often made using POST method (see #277).
        define('GLPI_KEEP_CSRF_TOKEN', true);

       // For AJAX requests, check CSRF token located into "X-Glpi-Csrf-Token" header.
        Session::checkCSRF(['_glpi_csrf_token' => $_SERVER['HTTP_X_GLPI_CSRF_TOKEN'] ?? '']);
    } else {
        Session::checkCSRF($_POST);
    }
}
// SET new global Token
$CURRENTCSRFTOKEN = '';

// Manage profile change
if (isset($_REQUEST["force_profile"]) && ($_SESSION['glpiactiveprofile']['id'] ?? -1) != $_REQUEST["force_profile"]) {
    if (isset($_SESSION['glpiprofiles'][$_REQUEST["force_profile"]])) {
        Session::changeProfile($_REQUEST["force_profile"]);
    }
}

// Manage entity change
if (isset($_REQUEST["force_entity"]) && ($_SESSION["glpiactive_entity"] ?? -1) != $_REQUEST["force_entity"]) {
    Session::changeActiveEntities($_REQUEST["force_entity"], true);
}
