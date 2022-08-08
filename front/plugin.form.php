<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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

/**
 * @since 0.84
 */

include ('../inc/includes.php');

Session::checkRight("config", UPDATE);

$plugin = new Plugin();

$id     = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : null;
$action = $id > 0 && isset($_POST['action']) ? $_POST['action'] : null;

switch ($action) {
   case 'install':
      $plugin->install($id);
      break;
   case 'activate':
      $plugin->activate($id);
      break;
   case 'unactivate':
      $plugin->unactivate($id);
      break;
   case 'uninstall':
      $plugin->uninstall($id);
      break;
   case 'clean':
      $plugin->clean($id);
      break;
   default:
      Html::displayErrorAndDie('Lost');
      break;
}

Html::back();
