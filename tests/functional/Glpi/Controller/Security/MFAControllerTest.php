<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\Controller\Security;

use Glpi\Controller\Security\MFAController;
use Glpi\Exception\AuthenticationFailedException;
use Glpi\Security\TOTPManager;
use Glpi\Tests\DbTestCase;
use Symfony\Component\HttpFoundation\Request;

class MFAControllerTest extends DbTestCase
{
    public function testVerifyEnforcesRateLimitAfterMaxAttempts(): void
    {
        $users_id = getItemByTypeName('User', TU_USER, true);

        $totp = new TOTPManager();
        $totp->setSecretForUser($users_id, $totp->createSecret());
        $totp->clearMFAFailures($users_id);

        $controller = new MFAController();

        // First MFA_MAX_ATTEMPTS failures must report "Invalid TOTP code"
        for ($i = 0; $i < TOTPManager::MFA_MAX_ATTEMPTS; $i++) {
            $_SESSION['mfa_pre_auth'] = ['user_id' => $users_id];
            $request = Request::create('/MFA/Verify', 'POST', ['totp_code' => str_split('invalid')]);
            try {
                $controller->verify($request);
                $this->fail('Expected AuthenticationFailedException was not thrown');
            } catch (AuthenticationFailedException $e) {
                $this->assertContains(__('Invalid TOTP code'), $e->getAuthenticationErrors());
            }
        }

        // The next attempt must be blocked by the rate limit, not report a code error
        $_SESSION['mfa_pre_auth'] = ['user_id' => $users_id];
        $request = Request::create('/MFA/Verify', 'POST', ['totp_code' => str_split('invalid')]);
        try {
            $controller->verify($request);
            $this->fail('Expected rate-limit exception was not thrown');
        } catch (AuthenticationFailedException $e) {
            $this->assertContains(
                __('Too many failed MFA attempts. Please try again later.'),
                $e->getAuthenticationErrors()
            );
        }

        // Clean up
        $totp->clearMFAFailures($users_id);
        $totp->disable2FAForUser($users_id);
    }
}
