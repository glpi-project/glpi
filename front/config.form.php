<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

require_once(__DIR__ . '/_check_webserver_config.php');

use Glpi\Cache\CacheManager;

Session::checkRight("config", READ);

if (isset($_GET['check_version'])) {
    Session::addMessageAfterRedirect(
        htmlescape(Toolbox::checkNewVersionAvailable())
    );
    Html::back();
}

$config = new Config();
$_POST['id'] = Config::getConfigIDForContext('core');
if (!empty($_POST["update_auth"])) {
    $config->update($_POST);
    Html::back();
}
if (!empty($_POST["update"])) {
    $config->update($_POST);
    Html::redirect(Toolbox::getItemTypeFormURL('Config'));
}
if (!empty($_POST['reset_opcache'])) {
    $config->checkGlobal(UPDATE);
    if (opcache_reset()) {
        Session::addMessageAfterRedirect(__s('PHP OPcache reset successful'));
    }
    Html::redirect(Toolbox::getItemTypeFormURL('Config'));
}
if (!empty($_POST['reset_core_cache'])) {
    $config->checkGlobal(UPDATE);
    $cache_manager = new CacheManager();
    if ($cache_manager->getCoreCacheInstance()->clear()) {
        Session::addMessageAfterRedirect(__s('GLPI cache reset successful'));
    }
    Html::redirect(Toolbox::getItemTypeFormURL('Config'));
}
if (!empty($_POST['reset_translation_cache'])) {
    $config->checkGlobal(UPDATE);
    $cache_manager = new CacheManager();
    if ($cache_manager->getTranslationsCacheInstance()->clear()) {
        Session::addMessageAfterRedirect(__s('Translation cache reset successful'));
    }
    Html::redirect(Toolbox::getItemTypeFormURL('Config'));
}

Config::displayFullPageForItem($_POST['id'], ["config", "config"], [
    'formoptions'  => "data-track-changes=true",
]);
