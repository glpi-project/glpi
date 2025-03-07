<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Glpi\Http\Firewall;
use Html;
use Session;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Toolbox;
use Preference;
use Dropdown;
use CronTask;
use Glpi\Http\HeaderlessStreamedResponse;
use Glpi\Plugin\Hooks;
use Glpi\Security\Attribute\SecurityStrategy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function __invoke(Request $request): Response
    {
        $response = $this->call();

        if ($response instanceof RedirectResponse) {
            // Redirections don't call cron anyway.
            return $response;
        }

        // call cron
        $out = CronTask::callCronForce(true);

        $response->setContent($response->getContent() . $out . '</body></html>');

        return $response;
    }

    private function call(): Response
    {
        /**
         * @var array $CFG_GLPI
         * @var array $PLUGIN_HOOKS
         */
        global $CFG_GLPI, $PLUGIN_HOOKS;

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

        if (count($errors) > 0) {
            return $this->render('pages/login_error.html.twig', [
                'errors'    => $errors,
                'login_url' => $CFG_GLPI["root_doc"] . '/front/logout.php?noAUTO=1&redirect=' . \rawurlencode($redirect),
            ]);
        }

        if ($redirect !== '') {
            Toolbox::manageRedirect($redirect);
        }

        if (isset($_SESSION['mfa_pre_auth'], $_POST['skip_mfa'])) {
            return new RedirectResponse($CFG_GLPI['root_doc'] . '/front/login.php?skip_mfa=1');
        }
        if (isset($_SESSION['mfa_pre_auth'])) {
            return new HeaderlessStreamedResponse(static function () {
                if (isset($_GET['mfa_setup'])) {
                    if (isset($_POST['secret'], $_POST['totp_code'])) {
                        $code = is_array($_POST['totp_code']) ? implode('', $_POST['totp_code']) : $_POST['totp_code'];
                        $totp = new \Glpi\Security\TOTPManager();
                        if (Session::validateIDOR($_POST) && ($algorithm = $totp->verifyCodeForSecret($code, $_POST['secret'])) !== false) {
                            $totp->setSecretForUser((int)$_SESSION['mfa_pre_auth']['user']['id'], $_POST['secret'], $algorithm);
                        } else {
                            Session::addMessageAfterRedirect(__s('Invalid code'), false, ERROR);
                        }
                        return new RedirectResponse(Preference::getSearchURL());
                    }

                    // Login started. 2FA needs configured.
                    $totp = new \Glpi\Security\TOTPManager();
                    $totp->showTOTPSetupForm((int)$_SESSION['mfa_pre_auth']['user']['id']);
                } else {
                    // Login started. Need to ask for the TOTP code.
                    $totp = new \Glpi\Security\TOTPManager();
                    $totp->showTOTPPrompt((int) $_SESSION['mfa_pre_auth']['user']['id']);
                }
            });
        }

        // Random number for html id/label
        $rand = mt_rand();

        // Regular login
        return $this->render('pages/login.html.twig', [
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
