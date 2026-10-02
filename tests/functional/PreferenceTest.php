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

namespace tests\units;

use Glpi\Exception\RedirectException;
use Glpi\Security\TOTPManager;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\Glpi\Security\ReAuth\ReAuthTrait;
use PHPUnit\Framework\Attributes\Group;
use Throwable;
use User;

#[Group('reauth')]
class PreferenceTest extends DbTestCase
{
    use ReAuthTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->resetReAuthManager();
    }

    public function tearDown(): void
    {
        $this->restoreWebContext();
        $this->resetReAuthManager();
        $_POST = [];
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $post
     */
    private function submitPreferences(array $post): ?Throwable
    {
        $this->fakeWebContext(request_uri: '/front/preference.php', method: 'POST', post: $post);
        $_POST = $post;

        $exception = null;
        try {
            include GLPI_ROOT . '/front/preference.php';
        } catch (Throwable $e) {
            $exception = $e;
        }

        return $exception;
    }

    private function getPasswordHash(int $users_id): string
    {
        $user = new User();
        $this->assertTrue($user->getFromDB($users_id));

        return $user->fields['password'];
    }

    private function isReAuthRedirect(?Throwable $exception): bool
    {
        return $exception instanceof RedirectException
            && str_contains($exception->getResponse()->getTargetUrl(), '/ReAuth/Prompt');
    }

    public function testSensitiveChangesRequireReAuthentication(): void
    {
        $this->login();
        $users_id = (int) \Session::getLoginUserID();

        $totp = new TOTPManager();
        $totp->setSecretForUser($users_id, $totp->createSecret());
        $this->assertTrue($totp->is2FAEnabled($users_id));

        $old_hash = $this->getPasswordHash($users_id);

        // Without a recent re-authentication, the 2FA cannot be disabled and the password cannot be changed
        $this->setReauthenticated(false);
        $this->assertTrue($this->isReAuthRedirect($this->submitPreferences(['disable_2fa' => 1])));
        $this->assertTrue($totp->is2FAEnabled($users_id));

        $this->setReauthenticated(false);
        $this->assertTrue($this->isReAuthRedirect($this->submitPreferences([
            'update'    => 1,
            'id'        => $users_id,
            'password'  => 'NewPassword-1234!',
            'password2' => 'NewPassword-1234!',
        ])));
        $this->assertSame($old_hash, $this->getPasswordHash($users_id));

        // After a re-authentication, they can
        $this->setReauthenticated(true);
        $this->assertFalse($this->isReAuthRedirect($this->submitPreferences(['disable_2fa' => 1])));
        $this->assertFalse($totp->is2FAEnabled($users_id));
    }
}
