<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

// Check PHP version not to have trouble
// Need to be the very fist step before any include
if (
    version_compare(PHP_VERSION, '7.4.0', '<') ||
    version_compare(PHP_VERSION, '8.2.0', '>=')
) {
    die('PHP 7.4.0 - 8.2.0 (exclusive) required');
}

use Glpi\Application\View\TemplateRenderer;
use Glpi\Plugin\Hooks;
use Glpi\Toolbox\Sanitizer;

//Load GLPI constants
define('GLPI_ROOT', __DIR__);
include(GLPI_ROOT . "/inc/based_config.php");

define('DO_NOT_CHECK_HTTP_REFERER', 1);

// If config_db doesn't exist -> start installation
if (!file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
    if (file_exists(GLPI_ROOT . '/install/install.php')) {
        Html::redirect("install/install.php");
    } else {
        // Init session (required by header display logic)
        Session::setPath();
        Session::start();
        Session::loadLanguage('', false);
        // Prevent inclusion of debug informations in footer, as they are based on vars that are not initialized here.
        $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;

        // no translation
        $title_text        = 'GLPI seems to not be configured properly.';
        $missing_conf_text = sprintf('Database configuration file "%s" is missing.', GLPI_CONFIG_DIR . '/config_db.php');
        $hint_text         = 'You have to either restart the install process, either restore this file.';

        Html::nullHeader('Missing configuration');
        echo '<div class="container-fluid mb-4">';
        echo '<div class="row justify-content-center">';
        echo '<div class="col-xl-6 col-lg-7 col-md-9 col-sm-12">';
        echo '<h2>' . $title_text . '</h2>';
        echo '<p class="mt-2 mb-n2 alert alert-warning">';
        echo $missing_conf_text;
        echo ' ';
        echo $hint_text;
        echo '</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        Html::nullFooter();
    }
    die();
} else {
    include(GLPI_ROOT . "/inc/includes.php");
    $_SESSION["glpicookietest"] = 'testcookie';

    //Try to detect GLPI agent calls
    $rawdata = file_get_contents("php://input");
    if (!empty($rawdata) && $_SERVER['REQUEST_METHOD'] == 'POST') {
        include_once(GLPI_ROOT . '/front/inventory.php');
        die();
    }

    // For compatibility reason
    if (isset($_GET["noCAS"])) {
        $_GET["noAUTO"] = $_GET["noCAS"];
    }

    if (!isset($_GET["noAUTO"])) {
        Auth::redirectIfAuthenticated();
    }

    $redirect = array_key_exists('redirect', $_GET) ? Sanitizer::unsanitize($_GET['redirect']) : '';

    Auth::checkAlternateAuthSystems(true, $redirect);

    $theme = $_SESSION['glpipalette'] ?? 'auror';

    $errors = "";
    if (isset($_GET['error']) && $redirect !== '') {
        switch ($_GET['error']) {
            case 1: // cookie error
                $errors .= __('You must accept cookies to reach this application');
                break;

            case 2: // GLPI_SESSION_DIR not writable
                $errors .= __('Checking write permissions for session files');
                break;

            case 3:
                $errors .= __('Invalid use of session ID');
                break;
        }
    }

    // redirect to ticket
    if ($redirect !== '') {
        Toolbox::manageRedirect($redirect);
    }

    TemplateRenderer::getInstance()->display('pages/login.html.twig', [
        'card_bg_width'       => true,
        'lang'                => $CFG_GLPI["languages"][$_SESSION['glpilanguage']][3],
        'title'               => __('Authentication'),
        'noAuto'              => $_GET["noAUTO"] ?? 0,
        'redirect'            => $redirect,
        'text_login'          => $CFG_GLPI['text_login'],
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
            'display'             => false,
            'display_emptychoice' => true,
            'emptylabel'          => __('Default (from user profile)'),
            'width'               => '100%'
        ]),
        'right_panel'         => strlen($CFG_GLPI['text_login']) > 0
                               || count($PLUGIN_HOOKS[Hooks::DISPLAY_LOGIN] ?? []) > 0
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
