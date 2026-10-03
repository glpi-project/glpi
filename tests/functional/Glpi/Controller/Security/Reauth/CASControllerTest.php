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

namespace tests\units\Glpi\Controller\Security\Reauth;

use Auth;
use Glpi\Controller\Security\Reauth\CASController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Security\ReAuth\CasReAuthStrategy;
use Glpi\Security\ReAuth\ReAuthManager;
use Glpi\Security\TOTPManager;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\Glpi\Security\ReAuth\ReAuthTrait;
use Glpi\Toolbox\HttpClient;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

#[Group('reauth')]
final class CASControllerTest extends DbTestCase
{
    use ReAuthTrait;

    public function tearDown(): void
    {
        // The manager caches the resolved strategy: do not leak the CAS one to other tests.
        $this->resetReAuthManager();

        parent::tearDown();
    }

    /**
     * Logged-in user whose session was opened through CAS, with a CAS server answering every
     * ticket validation with the given identity.
     */
    private function makeCasController(string $validated_cas_user): CASController
    {
        $this->loginWithCasSession();

        $cas_response = "<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>"
            . "<cas:authenticationSuccess><cas:user>$validated_cas_user</cas:user></cas:authenticationSuccess>"
            . '</cas:serviceResponse>';

        return new CASController(
            $this->getReAuthManager(),
            new CasReAuthStrategy(
                $this->makeHttpClient(new MockHttpClient(new MockResponse($cas_response)))
            ),
        );
    }

    /** GLPI HTTP client whose requests are answered by the given mock. */
    private function makeHttpClient(MockHttpClient $mock): HttpClient
    {
        $http_client = new HttpClient(context: Auth::class);
        // No interface to mock: replace the inner Symfony client.
        $this->setPrivateProperty($http_client, 'client', $mock);

        return $http_client;
    }

    /** The CAS prompt sends the user to the CAS server, asking for the credentials again. */
    public function testCasStartRedirectsToCasServer(): void
    {
        // --- arrange ---
        $controller = $this->makeCasController(TU_USER);

        // --- act ---
        $response = $controller->casStart();

        // --- assert ---
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringStartsWith('https://cas.test/cas/login?service=', $response->getTargetUrl());
        $this->assertStringEndsWith('&renew=true', $response->getTargetUrl());
    }

    /** The CAS round-trip is refused to a session that was not opened through CAS. */
    public function testCasStartIsDeniedForNonCasSession(): void
    {
        // --- arrange ---
        $controller = $this->makeCasController(TU_USER);
        $_SESSION['glpiauthtype'] = Auth::DB_GLPI;

        // --- assert ---
        $this->expectException(AccessDeniedHttpException::class);

        // --- act ---
        $controller->casStart();
    }

    /**
     * The CAS round-trip is refused when another strategy is selected: a CAS session with 2FA
     * enabled must not re-authenticate with the CAS password only.
     */
    public function testCasRoundTripIsDeniedWhenTotpIsSelected(): void
    {
        // --- arrange ---
        $controller = $this->makeCasController(TU_USER);
        (new TOTPManager())->setSecretForUser($_SESSION['glpiID'], 'G3QWAUUBIOM7GUU3EHC76WGMV5FIO3FB');

        // --- act + assert : start ---
        try {
            $controller->casStart();
            $this->fail('The CAS round-trip must not start when TOTP is selected.');
        } catch (AccessDeniedHttpException) {
        }

        // --- act + assert : callback ---
        try {
            $controller->casCallback(Request::create('/ReAuth/CAS/Callback', 'GET', ['ticket' => 'ST-1-abc']));
            $this->fail('The CAS callback must be refused when TOTP is selected.');
        } catch (AccessDeniedHttpException) {
        }
        $this->assertArrayNotHasKey('glpi_reauth_until', $_SESSION);
    }

    /** Coming back from CAS with a valid ticket re-authenticates the user and replays the request. */
    public function testCasCallbackReAuthenticatesUser(): void
    {
        // --- arrange ---
        $controller = $this->makeCasController(TU_USER);
        $controller->casStart();

        // --- act ---
        $response = $controller->casCallback(Request::create('/ReAuth/CAS/Callback', 'GET', ['ticket' => 'ST-1-abc']));

        // --- assert ---
        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('glpi_reauth_until', $_SESSION);
        $this->assertStringContainsString(ReAuthManager::RESTORE_REFERER_PARAM, (string) $response->getContent());
    }

    /** Coming back from CAS as someone else goes back to the prompt, showing the failure. */
    public function testCasCallbackDoesNotReAuthenticateAnotherIdentity(): void
    {
        global $CFG_GLPI;

        // --- arrange ---
        $controller = $this->makeCasController('glpi');
        $controller->casStart();

        // --- act ---
        $response = $controller->casCallback(Request::create('/ReAuth/CAS/Callback', 'GET', ['ticket' => 'ST-1-abc']));

        // --- assert ---
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($CFG_GLPI['root_doc'] . '/ReAuth/Prompt?failed=1', $response->getTargetUrl());
        $this->assertArrayNotHasKey('glpi_reauth_until', $_SESSION);
    }
}
