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

namespace Glpi\Controller;

use Auth;
use Html;
use Session;
use Toolbox;
use Preference;
use Dropdown;
use CronTask;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Plugin\Hooks;
use Glpi\Security\Attribute\SecurityStrategy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class IndexController extends AbstractController
{
    #[Route(
        [
            "base" => "/",
            "legacy_index_file" => "/index.php",
        ],
        name: "glpi_index"
    )]
    #[SecurityStrategy('no_check')]
    public function __invoke(Request $request): Response
    {
        return new StreamedResponse($this->call(...));
    }

    private function call(): void
    {
        /**
         * @var array $CFG_GLPI
         * @var array $PLUGIN_HOOKS
         */
        global $CFG_GLPI, $PLUGIN_HOOKS;

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
        }

        //Try to detect GLPI agent calls
        $rawdata = file_get_contents("php://input");
        if (!isset($_POST['totp_code']) && !empty($rawdata) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            include_once(GLPI_ROOT . '/front/inventory.php');
            die();
        }

        Session::checkCookieSecureConfig();

        $_SESSION["glpicookietest"] = 'testcookie';

        // For compatibility reason
        if (isset($_GET["noCAS"])) {
            $_GET["noAUTO"] = $_GET["noCAS"];
        }

        if (!isset($_GET["noAUTO"])) {
            Auth::redirectIfAuthenticated();
        }

        $redirect = $_GET['redirect'] ?? '';

        Auth::checkAlternateAuthSystems(true, $redirect);

        $errors = [];
        if (isset($_GET['error']) && $redirect !== '') {
            switch ($_GET['error']) {
                case 1: // cookie error
                    $errors[] = __('You must accept cookies to reach this application');
                    break;

                case 2: // GLPI_SESSION_DIR not writable
                    $errors[] = __('Logins are not possible at this time. Please contact your administrator.');
                    break;

                case 3:
                    $errors[] = __('Your session has expired. Please log in again.');
                    break;
            }
        }

        // redirect to ticket
        if ($redirect !== '') {
            Toolbox::manageRedirect($redirect);
        }

        if (count($errors)) {
            TemplateRenderer::getInstance()->display('pages/login_error.html.twig', [
                'errors'    => $errors,
                'login_url' => $CFG_GLPI["root_doc"] . '/front/logout.php?noAUTO=1&redirect=' . str_replace("?", "&", $redirect),
            ]);
        } else {
            if (isset($_SESSION['mfa_pre_auth'], $_POST['skip_mfa'])) {
                Html::redirect($CFG_GLPI['root_doc'] . '/front/login.php?skip_mfa=1');
            }
            if (isset($_SESSION['mfa_pre_auth'])) {
                if (isset($_GET['mfa_setup'])) {
                    if (isset($_POST['secret'], $_POST['totp_code'])) {
                        $code = is_array($_POST['totp_code']) ? implode('', $_POST['totp_code']) : $_POST['totp_code'];
                        $totp = new \Glpi\Security\TOTPManager();
                        if (Session::validateIDOR($_POST) && ($algorithm = $totp->verifyCodeForSecret($code, $_POST['secret'])) !== false) {
                            $totp->setSecretForUser((int)$_SESSION['mfa_pre_auth']['user']['id'], $_POST['secret'], $algorithm);
                        } else {
                            Session::addMessageAfterRedirect(__s('Invalid code'), false, ERROR);
                        }
                        Html::redirect(Preference::getSearchURL());
                    } else {
                        // Login started. 2FA needs configured.
                        $totp = new \Glpi\Security\TOTPManager();
                        $totp->showTOTPSetupForm((int)$_SESSION['mfa_pre_auth']['user']['id']);
                    }
                } else {
                    // Login started. Need to ask for the TOTP code.
                    $totp = new \Glpi\Security\TOTPManager();
                    $totp->showTOTPPrompt((int) $_SESSION['mfa_pre_auth']['user']['id']);
                }
            } else {
                // Random number for html id/label
                $rand = mt_rand();

                // Regular login
                TemplateRenderer::getInstance()->display('pages/login.html.twig', [
                    'rand'                => $rand,
                    'card_bg_width'       => true,
                    'lang'                => $CFG_GLPI["languages"][$_SESSION['glpilanguage']][3],
                    'title'               => __('Authentication'),
                    'noAuto'              => $_GET["noAUTO"] ?? 0,
                    'redirect'            => $redirect,
                    'text_login'          => $CFG_GLPI['text_login'],
                    'namfield'            => ($_SESSION['namfield'] = \uniqid('fielda')),
                    'pwdfield'            => ($_SESSION['pwdfield'] = \uniqid('fieldb')),
                    'rmbfield'            => ($_SESSION['rmbfield'] = \uniqid('fieldc')),
                    'show_lost_password'  => $CFG_GLPI["notifications_mailing"]
                        && countElementsInTable('glpi_notifications', [
                            'itemtype' => 'User',
                            'event' => 'passwordforget',
                            'is_active' => 1
                        ]),
                    'languages_dropdown'  => Dropdown::showLanguages('language', [
                        'display'             => false,
                        'rand'                => $rand,
                        'display_emptychoice' => true,
                        'emptylabel'          => __('Default (from user profile)'),
                        'width'               => '100%'
                    ]),
                    'right_panel'         => strlen($CFG_GLPI['text_login']) > 0
                        || count($PLUGIN_HOOKS[Hooks::DISPLAY_LOGIN] ?? []) > 0
                        || $CFG_GLPI["use_public_faq"],
                    'auth_dropdown_login' => Auth::dropdownLogin(false, $rand),
                    'copyright_message'   => Html::getCopyrightMessage(false)
                ]);
            }
        }
        // call cron
        if (!GLPI_DEMO_MODE) {
            CronTask::callCronForce();
        }

        echo "</body></html>";
    }
}
