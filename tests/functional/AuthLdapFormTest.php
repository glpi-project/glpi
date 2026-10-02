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

use AuthLDAP;
use Glpi\Exception\RedirectException;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\Glpi\Security\ReAuth\ReAuthTrait;
use PHPUnit\Framework\Attributes\Group;
use Throwable;

#[Group('reauth')]
class AuthLdapFormTest extends DbTestCase
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
    private function submitForm(array $post): ?Throwable
    {
        $this->fakeWebContext(request_uri: '/front/authldap.form.php', method: 'POST', post: $post);
        $_POST = $post;

        $exception = null;
        try {
            include GLPI_ROOT . '/front/authldap.form.php';
        } catch (Throwable $e) {
            $exception = $e;
        }

        return $exception;
    }

    private function isReAuthRedirect(?Throwable $exception): bool
    {
        return $exception instanceof RedirectException
            && str_contains($exception->getResponse()->getTargetUrl(), '/ReAuth/Prompt');
    }

    /**
     * @return array<string, mixed>
     */
    private function reloadLdap(int $id): array
    {
        $ldap = new AuthLDAP();
        $this->assertTrue($ldap->getFromDB($id));

        return $ldap->fields;
    }

    public function testUpdatesRequireReAuthentication(): void
    {
        $this->login();

        $ldap = $this->createItem(AuthLDAP::class, [
            'name'      => 'LDAP form test',
            'host'      => 'ldap.example.org',
            'basedn'    => 'ou=people,dc=example,dc=org',
            'is_active' => 1,
        ]);

        // Without a recent re-authentication, the directory cannot be updated nor removed
        $this->setReauthenticated(false);
        $this->assertTrue($this->isReAuthRedirect($this->submitForm([
            'update' => 1,
            'id'     => $ldap->getID(),
            'host'   => 'ldap.attacker.example.org',
        ])));
        $this->assertSame('ldap.example.org', $this->reloadLdap($ldap->getID())['host']);

        $this->setReauthenticated(false);
        $this->assertTrue($this->isReAuthRedirect($this->submitForm(['purge' => 1, 'id' => $ldap->getID()])));
        $this->assertTrue((new AuthLDAP())->getFromDB($ldap->getID()));

        // After a re-authentication, they can
        $this->setReauthenticated(true);
        $this->assertFalse($this->isReAuthRedirect($this->submitForm([
            'update' => 1,
            'id'     => $ldap->getID(),
            'host'   => 'ldap2.example.org',
        ])));
        $this->assertSame('ldap2.example.org', $this->reloadLdap($ldap->getID())['host']);
    }
}
