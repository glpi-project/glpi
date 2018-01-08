<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

include ('../inc/includes.php');
Session::checkRight("config", READ);

$config = new Config();
$_POST['id'] = 1;
if (!empty($_POST["update_auth"])) {
   $config->update($_POST);
   Html::back();
}
if (!empty($_POST["update"])) {
   $config->update($_POST);
   Html::redirect(Toolbox::getItemTypeFormURL('Config'));
}
if (!empty($_GET['reset_opcache'])) {
   $config->checkGlobal(UPDATE);
   if (opcache_reset()) {
      Session::addMessageAfterRedirect(__('Cache reset successful'));
   }
   Html::redirect(Toolbox::getItemTypeFormURL('Config'));
}
if (!empty($_GET['reset_cache'])) {
   $config->checkGlobal(UPDATE);
   if ($GLPI_CACHE->flush()) {
      Session::addMessageAfterRedirect(__('Cache reset successful'));
   }
   Html::redirect(Toolbox::getItemTypeFormURL('Config'));
}

Html::header(Config::getTypeName(1), $_SERVER['PHP_SELF'], "config", "config");
$config->display(['id' => 1]);
Html::footer();
