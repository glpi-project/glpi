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

use Glpi\Cache\CacheManager;

include('../inc/includes.php');
Session::checkRight("config", READ);

if (isset($_GET['check_version'])) {
    Session::addMessageAfterRedirect(
        Toolbox::checkNewVersionAvailable()
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
    $context = array_key_exists('config_context', $_POST) ? $_POST['config_context'] : 'core';

    $glpikey = new GLPIKey();
    foreach (array_keys($_POST) as $field) {
        if ($glpikey->isConfigSecured($context, $field)) {
           // Field must not be altered, it will be encrypted and never displayed, so sanitize is not necessary.
            $_POST[$field] = $_UPOST[$field];
        }
    }

    $config->update($_POST);
    Html::redirect(Toolbox::getItemTypeFormURL('Config'));
}
if (!empty($_POST['reset_opcache'])) {
    $config->checkGlobal(UPDATE);
    if (opcache_reset()) {
        Session::addMessageAfterRedirect(__('PHP OPcache reset successful'));
    }
    Html::redirect(Toolbox::getItemTypeFormURL('Config'));
}
if (!empty($_POST['reset_core_cache'])) {
    $config->checkGlobal(UPDATE);
    $cache_manager = new CacheManager();
    if ($cache_manager->getCoreCacheInstance()->clear()) {
        Session::addMessageAfterRedirect(__('GLPI cache reset successful'));
    }
    Html::redirect(Toolbox::getItemTypeFormURL('Config'));
}
if (!empty($_POST['reset_translation_cache'])) {
    $config->checkGlobal(UPDATE);
    $cache_manager = new CacheManager();
    if ($cache_manager->getTranslationsCacheInstance()->clear()) {
        Session::addMessageAfterRedirect(__('Translation cache reset successful'));
    }
    Html::redirect(Toolbox::getItemTypeFormURL('Config'));
}

Config::displayFullPageForItem($_POST['id'], ["config", "config"], [
    'formoptions'  => "data-track-changes=true"
]);
