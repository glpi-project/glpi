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

namespace Glpi\Controller\Security;

use Glpi\Controller\AbstractController;
use Glpi\Exception\AuthenticationFailedException;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Glpi\Security\TOTPManager;
use Html;
use Session;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class MFAController extends AbstractController
{
    #[Route(
        path: "/MFA/Setup",
        name: "mfa_setup",
    )]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function setup(Request $request): Response
    {
        if (!isset($_SESSION['mfa_pre_auth'])) {
            return new RedirectResponse($request->getBasePath() . '/front/login.php');
        }
        return new StreamedResponse(static function () {
            $totp = new TOTPManager();
            $totp->showTOTPSetupForm((int) $_SESSION['mfa_pre_auth']['user_id']);
        });
    }

    #[Route(
        path: "/MFA/Prompt",
        name: "mfa_prompt",
        methods: ['GET']
    )]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function prompt(Request $request): Response
    {
        $totp = new TOTPManager();
        return new StreamedResponse(static function () use ($totp) {
            $totp->showTOTPPrompt();
        });
    }

    #[Route(
        path: "/MFA/Verify",
        name: "mfa_verify_totp",
        methods: ['POST']
    )]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function verify(Request $request): Response
    {
        $pre_auth_data = $_SESSION['mfa_pre_auth'] ?? null;

        $from_login = $pre_auth_data !== null;
        $users_id = $from_login ? (int) $pre_auth_data['user_id'] : (int) Session::getLoginUserID();
        if (!$users_id) {
            return new RedirectResponse($request->getBasePath() . '/MFA/Prompt');
        }
        $totp = new TOTPManager();
        $backup_code = $request->request->get('backup_code');
        $totp_code = $request->get('totp_code');
        if (is_array($totp_code)) {
            $totp_code = implode('', $totp_code);
        }
        $secret = $request->request->get('secret');
        $algorithm = null;

        $in_grace_period = $totp->get2FAEnforcement($users_id) === TOTPManager::ENFORCEMENT_MANDATORY_GRACE_PERIOD && !$totp->is2FAEnabled($users_id);
        $query_params = isset($pre_auth_data['redirect']) ? sprintf('redirect=%s', $pre_auth_data['redirect']) : '';

        if (!($in_grace_period && $request->request->has('skip_mfa'))) {
            if (
                !(
                    (isset($backup_code) && $totp->verifyBackupCodeForUser($backup_code, $users_id))
                    || (isset($totp_code, $secret) && ($algorithm = $totp->verifyCodeForSecret($totp_code, $secret)))
                    || (isset($totp_code) && !isset($secret) && $totp->verifyCodeForUser($totp_code, $users_id))
                )
            ) {
                // Verification failure
                if (isset($backup_code)) {
                    throw new AuthenticationFailedException(authentication_errors: [__('Invalid backup code')]);
                } else {
                    throw new AuthenticationFailedException(authentication_errors: [__('Invalid TOTP code')]);
                }
            }

            if (
                isset($secret)
                && !(Session::validateIDOR($request->request->all())
                    && $totp->setSecretForUser($users_id, $request->request->getString('secret'), $algorithm))
            ) {
                Session::addMessageAfterRedirect(__s('Invalid code'), false, ERROR);
                return new RedirectResponse($from_login ? ($request->getBasePath() . '/MFA/Prompt') : Html::getBackUrl());
            }
            $_SESSION['mfa_success'] = true;
            if ($from_login) {
                // If backup codes already generated, continue the login. Otherwise show/generate them.
                $next_page = $totp->isBackupCodesAvailable($users_id) ? ('/front/login.php?' . $query_params) : '/MFA/ShowBackupCodes';
                $next_page = $request->getBasePath() . $next_page;
            } else {
                $next_page = Html::getBackUrl();
            }
        } else {
            // 2FA is not set up yet, the user is in a grace period, and the user chose to skip it
            $_SESSION['mfa_exploit_grace_period'] = true;
            $next_page = '/front/login.php?' . $query_params;
        }

        return new RedirectResponse($next_page);
    }

    #[Route(
        path: "/MFA/ShowBackupCodes",
        name: "mfa_show_status",
        methods: ['GET']
    )]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function showBackupCodes(Request $request): Response
    {
        $pre_auth_data = $_SESSION['mfa_pre_auth'] ?? null;
        if (!isset($_SESSION['mfa_success'], $pre_auth_data)) {
            $query_params = isset($pre_auth_data['redirect']) ? http_build_query($pre_auth_data['redirect']) : '';
            return new RedirectResponse($request->getBasePath() . '/front/login.php?' . $query_params);
        }
        $totp = new TOTPManager();
        return new StreamedResponse(static function () use ($pre_auth_data, $totp) {
            $totp->showBackupCodes((int) $pre_auth_data['user_id']);
        });
    }
}
