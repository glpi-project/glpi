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

// Check PHP version not to have trouble
// Need to be the very fist step before any include
if (version_compare(PHP_VERSION, '7.3.0') < 0) {
   die('PHP >= 7.3.0 required');
}

use Glpi\Application\View\TemplateRenderer;
use Glpi\Toolbox\RichText;

//Load GLPI constants
define('GLPI_ROOT', __DIR__);
include (GLPI_ROOT . "/inc/based_config.php");

define('DO_NOT_CHECK_HTTP_REFERER', 1);

// If config_db doesn't exist -> start installation
if (!file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
   Html::redirect("install/install.php");
   die();

} else {
   include (GLPI_ROOT . "/inc/includes.php");
   $_SESSION["glpicookietest"] = 'testcookie';

   // For compatibility reason
   if (isset($_GET["noCAS"])) {
      $_GET["noAUTO"] = $_GET["noCAS"];
   }

   if (!isset($_GET["noAUTO"])) {
      Auth::redirectIfAuthenticated();
   }
   Auth::checkAlternateAuthSystems(true, isset($_GET["redirect"])?$_GET["redirect"]:"");

   $theme = $_SESSION['glpipalette'] ?? 'auror';

   $errors = "";
   if (isset($_GET['error']) && isset($_GET['redirect'])) {
      switch ($_GET['error']) {
         case 1 : // cookie error
            $errors.= __('You must accept cookies to reach this application');
            break;

         case 2 : // GLPI_SESSION_DIR not writable
            $errors.= __('Checking write permissions for session files');
            break;

         case 3 :
            $errors.= __('Invalid use of session ID');
            break;
      }
   }

   TemplateRenderer::getInstance()->display('login.html.twig', [
      'card_bg_width'       => true,
      'lang'                => $CFG_GLPI["languages"][$_SESSION['glpilanguage']][3],
      'title'               => __('Authentication'),
      'noAuto'              => $_GET["noAUTO"] ?? 0,
      'redirect'            => Html::entities_deep($_GET['redirect'] ?? ""),
      'text_login'          => RichText::getSafeHtml($CFG_GLPI['text_login'], true),
      'namfield'            => ($_SESSION['namfield'] = uniqid('fielda')),
      'pwdfield'            => ($_SESSION['pwdfield'] = uniqid('fieldb')),
      'rmbfield'            => ($_SESSION['rmbfield'] = uniqid('fieldc')),
      'show_lost_password'  => $CFG_GLPI["notifications_mailing"]
                              && countElementsInTable('glpi_notifications', [
                                 'itemtype'  => 'User',
                                 'event'     => 'passwordforget',
                                 'is_active' => 1
                              ]),
      'languages_dropdown'  => Dropdown::showLanguages('language', [
         'class'               => 'form-select',
         'display'             => false,
         'display_emptychoice' => true,
         'emptylabel'          => __('Default (from user profile)'),
         'width'               => '100%'
      ]),
      'right_panel'         => strlen($CFG_GLPI['text_login']) > 0
                               || count($PLUGIN_HOOKS['display_login'] ?? []) > 0
                               || $CFG_GLPI["use_public_faq"],
      'auth_dropdown_login' => Auth::dropdownLogin(false),
      'copyright_message'   => Html::getCopyrightMessage(false),
      'errors'              => $errors
   ]);
}
// call cron
if (!GLPI_DEMO_MODE) {
   CronTask::callCronForce();
}

echo "</body></html>";
