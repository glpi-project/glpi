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

use Glpi\Asset\AssetDefinitionManager;
use Glpi\Http\Firewall;
use Glpi\Toolbox\URL;

/**
 * @var array $CFG_GLPI
 * @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE
 * @var bool|null $AJAX_INCLUDE
 * @var bool $FOOTER_LOADED
 * @var bool $HEADER_LOADED
 * @var bool|null $PLUGINS_EXCLUDED
 * @var bool|null $PLUGINS_INCLUDED
 * @var string|null $SECURITY_STRATEGY
 * @var string $CURRENTCSRFTOKEN
 */
global $CFG_GLPI,
    $GLPI_CACHE,
    $AJAX_INCLUDE,
    $FOOTER_LOADED, $HEADER_LOADED,
    $PLUGINS_EXCLUDED, $PLUGINS_INCLUDED,
    $SECURITY_STRATEGY,
    $CURRENTCSRFTOKEN
;

if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__));
}

include_once GLPI_ROOT . '/vendor/autoload.php';
include_once GLPI_ROOT . '/inc/based_config.php';

\Glpi\Debug\Profiler::getInstance()->start('php_request');


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
    \Glpi\Debug\Profile::getCurrent();
}

// Mark if Header is loaded or not :
$HEADER_LOADED = false;
$FOOTER_LOADED = false;
if (isset($AJAX_INCLUDE)) {
    $HEADER_LOADED = true;
}

// Assets classes autoload
AssetDefinitionManager::getInstance()->registerAssetsAutoload();

/* On startup, register all plugins configured for use. */
if (!isset($PLUGINS_INCLUDED)) {
   // PLugin already included
    $PLUGINS_INCLUDED = 1;
    $PLUGINS_EXCLUDED = isset($PLUGINS_EXCLUDED) ? $PLUGINS_EXCLUDED : [];
    $plugin = new Plugin();
    $plugin->init(true, $PLUGINS_EXCLUDED);
}

// Assets classes bootstraping.
// Must be done after plugins initialization, to allow plugin to register new capacities.
AssetDefinitionManager::getInstance()->boostrapAssets();

if (!isset($_SESSION["MESSAGE_AFTER_REDIRECT"])) {
    $_SESSION["MESSAGE_AFTER_REDIRECT"] = [];
}

// Manage force tab
if (isset($_REQUEST['forcetab'])) {
    $itemtype = URL::extractItemtypeFromUrlPath($_SERVER['PHP_SELF']);
    if ($itemtype !== null) {
        Session::setActiveTab($itemtype, $_REQUEST['forcetab']);
    }
}

// Manage tabs
if (isset($_REQUEST['glpi_tab']) && isset($_REQUEST['itemtype'])) {
    Session::setActiveTab($_REQUEST['itemtype'], $_REQUEST['glpi_tab']);
}
// Override list-limit if choosen
if (isset($_REQUEST['glpilist_limit'])) {
    $_SESSION['glpilist_limit'] = $_REQUEST['glpilist_limit'];
}

// Security : check CSRF token
if (!isAPI() && count($_POST) > 0) {
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

// The user's current groups are stored in his session
// If there was any change regarding groups membership and/or configuration, we
// need to reset the data stored in his session
$last_group_change = $GLPI_CACHE->get('last_group_change');
if (
    isset($_SESSION['glpigroups'])
    && ($_SESSION['glpigroups_cache_date'] ?? "") < $last_group_change
) {
    Session::loadGroups();
}
