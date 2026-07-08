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

namespace tests\units\Glpi\Security\ReAuth;

use Glpi\Security\ReAuth\TOTPReAuthStrategy;
use Glpi\Security\TOTPManager;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\Group;
use RobThree\Auth\Algorithm;
use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;
use User;

#[Group('reauth')]
class TOTPReAuthStrategyTest extends DbTestCase
{
    private const SECRET = 'G3QWAUUBIOM7GUU3EHC76WGMV5FIO3FB';

    /** Returns false when no TOTP secret is registered for the user. */
    public function testIsAvailableIsFalseWhenTwoFactorDisabled(): void
    {
        // --- arrange : test user has no TOTP secret ---
        $strategy = new TOTPReAuthStrategy();
        $users_id = getItemByTypeName(User::class, TU_USER, true);
        assert(!(new TOTPManager())->is2FAEnabled($users_id), 'Fixture: test user must not have TOTP enabled');

        // --- act + assert ---
        $this->assertFalse($strategy->isAvailable($users_id));
    }

    /** Returns true once a TOTP secret has been set for the user. */
    public function testIsAvailableIsTrueWhenTwoFactorEnabled(): void
    {
        // --- arrange : enable TOTP for the test user ---
        $strategy = new TOTPReAuthStrategy();
        $users_id = $this->enableTotpForTestUser();

        // --- act + assert ---
        $this->assertTrue($strategy->isAvailable($users_id));
    }

    /** Returns true when a freshly generated TOTP code is submitted. */
    public function testVerifyReturnsTrueWithValidCode(): void
    {
        // --- arrange ---
        $strategy = new TOTPReAuthStrategy();
        $users_id = $this->enableTotpForTestUser();

        // --- act + assert ---
        $this->assertTrue($strategy->verify($users_id, $this->generateValidCode()));
    }

    /** Returns false when the submitted TOTP code does not match the secret. */
    public function testVerifyReturnsFalseWithInvalidCode(): void
    {
        // --- arrange ---
        $strategy = new TOTPReAuthStrategy();
        $users_id = $this->enableTotpForTestUser();

        // --- act + assert ---
        $this->assertFalse($strategy->verify($users_id, '000000'));
    }

    /**
     * Defensive: verify() is normally gated by isAvailable(), but a user without
     * a TOTP secret must never be granted re-authentication.
     */
    public function testVerifyReturnsFalseWhenNoSecretConfigured(): void
    {
        // --- arrange : test user has no TOTP secret ---
        $strategy = new TOTPReAuthStrategy();
        $users_id = getItemByTypeName(User::class, TU_USER, true);
        assert(!(new TOTPManager())->is2FAEnabled($users_id), 'Fixture: test user must not have TOTP enabled');

        // --- act + assert ---
        $this->assertFalse($strategy->verify($users_id, '000000'));
    }

    /** Test $strategy->getPromptTemplate(), $strategy->getPriority() & $strategy->getLabel() */
    public function testMetadata(): void
    {
        // --- arrange ---
        $strategy = new TOTPReAuthStrategy();

        // --- act + assert ---
        $this->assertSame('pages/reauth/totp_form.html.twig', $strategy->getPromptTemplate());
        $this->assertSame(100, $strategy->getPriority());
        $this->assertNotEmpty($strategy->getLabel());
    }

    private function enableTotpForTestUser(): int
    {
        $users_id = getItemByTypeName(User::class, TU_USER, true);
        (new TOTPManager())->setSecretForUser($users_id, self::SECRET);

        return $users_id;
    }

    private function generateValidCode(): string
    {
        $tfa = new TwoFactorAuth(
            new BaconQrCodeProvider(),
            '',
            TOTPManager::CODE_LENGTH_DIGITS,
            TOTPManager::CODE_VALIDITY_SECONDS,
            Algorithm::Sha1,
        );

        return $tfa->getCode(self::SECRET);
    }
}
