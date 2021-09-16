<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

global $PLUGINS_EXCLUDED;

// follow download progress of a plugin with a minimal loading of files
// So we get a ajax answer in 5ms instead 100ms
if (($_GET["action"] ?? null) == "get_dl_progress") {
   if (!defined('GLPI_ROOT')) {
      define('GLPI_ROOT', dirname(__DIR__));
   }

   include_once GLPI_ROOT . '/inc/based_config.php';
   Session::setPath();
   Session::start();

   echo $_SESSION['marketplace_dl_progress'][$_GET['key']] ?? 0;
   exit;
}

if (in_array($_POST["action"] ?? null, ['download_plugin', 'update_plugin'])) {
   // Do not load plugin that will be updated, to be able to load its new informations
   // by redefining its plugin_version_ function after files replacement.
   $PLUGINS_EXCLUDED = [$_POST['key']];
}


// get common marketplace action, load GLPI framework
include ("../inc/includes.php");

Session::checkRight("config", UPDATE);

use Glpi\Marketplace\Controller as MarketplaceController;
use Glpi\Marketplace\View as MarketplaceView;

if (isset($_POST['key']) && isset($_POST["action"])) {
   $marketplace_ctrl = new MarketplaceController($_POST['key']);
   if ($_POST["action"] == "download_plugin"
      || $_POST["action"] == "update_plugin") {
      $marketplace_ctrl->downloadPlugin();
   }
   if ($_POST["action"] == "clean_plugin") {
      if ($marketplace_ctrl->cleanPlugin()) {
         echo "cleaned";
      }
   }
   if ($_POST["action"] == "install_plugin") {
      $marketplace_ctrl->installPlugin();
   }
   if ($_POST["action"] == "uninstall_plugin") {
      $marketplace_ctrl->uninstallPlugin();
   }
   if ($_POST["action"] == "enable_plugin") {
      $marketplace_ctrl->enablePlugin();
   }
   if ($_POST["action"] == "disable_plugin") {
      $marketplace_ctrl->disablePlugin();
   }

   echo MarketplaceView::getButtons($_POST['key']);
} else if (($_GET["action"] ?? null) == "refresh_plugin_list") {
   switch ($_GET['tab']) {
      default:
      case 'discover':
         echo MarketplaceView::discover(
            $_GET['force'] ?? false,
            true,
            $_GET['tag'] ?? "",
            $_GET['filter'] ?? "",
            $_GET['page'] ?? 1,
            $_GET['sort'] ?? "sort-alpha-asc"
         );
         break;
      case 'installed':
         echo MarketplaceView::installed(true, true, $_GET['filter'] ?? "");
         break;
   }
} else if (($_GET["action"] ?? null) == "getPagination") {
   echo MarketplaceView::getPaginationHtml(
      $_GET['page'] ?? 1,
      $_GET['total'] ?? 1,
      true
   );
}
