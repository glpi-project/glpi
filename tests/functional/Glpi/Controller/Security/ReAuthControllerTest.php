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

use Auth;
use Glpi\Controller\Security\ReAuthController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Security\ReAuth\CasReAuthStrategy;
use Glpi\Security\ReAuth\ReAuthManager;
use Glpi\Security\TOTPManager;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\Glpi\Security\ReAuth\ReAuthTrait;
use PHPUnit\Framework\Attributes\Group;
use Safe\DateTime;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

#[Group('reauth')]
final class ReAuthControllerTest extends DbTestCase
{
    use ReAuthTrait;

    public function tearDown(): void
    {
        // The manager caches the resolved strategy: do not leak the CAS one to other tests.
        $this->resetReAuthManager();

        parent::tearDown();
    }

    /** Returns a 200 response containing the verify action URL. */
    public function testPromptRendersTheReAuthForm(): void
    {
        // --- arrange ---
        $this->login();
        $controller = new ReAuthController($this->getReAuthManager());

        // --- act ---
        $response = $controller->prompt();

        // --- assert : the strategy's verify URL appears as the form action ---
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('/ReAuth/Verify', $response->getContent());
    }

    /** Sets glpi_reauth_until in session when the correct password is submitted. */
    public function testVerifyReAuthenticatesUser(): void
    {
        // --- arrange : logged-in user, not yet re-authenticated ---
        $this->login();
        unset($_SESSION['glpi_reauth_until']);
        $expected_reauth_until = (new DateTime($_SESSION['glpi_currenttime']))->getTimestamp() + ReAuthManager::REAUTH_DELAY_SECONDS;
        $controller = new ReAuthController($this->getReAuthManager());

        // --- act : submit the correct password ---
        $response = $controller->verify(Request::create('/ReAuth/Verify', 'POST', ['user_input' => TU_PASS]));

        // --- assert : a fresh reauth window is opened ---
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expected_reauth_until, $_SESSION['glpi_reauth_until']);
        // The replayed request must carry the parameter asking for the origin page to be restored,
        // which also pins the data the controller hands over to the replay template.
        $this->assertStringContainsString(
            ReAuthManager::RESTORE_REFERER_PARAM,
            (string) $response->getContent()
        );
    }

    /** Re-renders the prompt without setting glpi_reauth_until when a wrong password is submitted. */
    public function testVerifyDoesNotReAuthenticate(): void
    {
        // --- arrange : logged-in user, not yet re-authenticated ---
        $this->login();
        unset($_SESSION['glpi_reauth_until']);
        $controller = new ReAuthController($this->getReAuthManager());

        // --- act : submit a wrong password ---
        $response = $controller->verify(Request::create('/ReAuth/Verify', 'POST', ['user_input' => 'wrong-password']));

        // --- assert : the prompt is re-rendered and no reauth window is opened ---
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('/ReAuth/Verify', $response->getContent());
        $this->assertArrayNotHasKey('glpi_reauth_until', $_SESSION);
    }

    /**
     * Logged-in user whose session was opened through CAS, with a CAS server answering every
     * ticket validation with the given identity.
     */
    private function makeCasController(string $validated_cas_user): ReAuthController
    {
        global $CFG_GLPI;

        $this->login();
        unset($_SESSION['glpi_reauth_until']);
        $this->resetReAuthManager();

        $CFG_GLPI['cas_host']    = 'cas.test';
        $CFG_GLPI['cas_port']    = '443';
        $CFG_GLPI['cas_uri']     = 'cas';
        $CFG_GLPI['cas_version'] = 'CAS_VERSION_3_0';
        $_SESSION['glpiauthtype'] = Auth::CAS;

        $cas_response = "<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>"
            . "<cas:authenticationSuccess><cas:user>$validated_cas_user</cas:user></cas:authenticationSuccess>"
            . '</cas:serviceResponse>';

        return new ReAuthController(
            $this->getReAuthManager(),
            new CasReAuthStrategy(new MockHttpClient(new MockResponse($cas_response))),
        );
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

    /** A submission to the core verify endpoint while CAS is selected re-renders the prompt. */
    public function testVerifyPostDoesNotReAuthenticateWhenCasIsSelected(): void
    {
        // --- arrange ---
        $controller = $this->makeCasController(TU_USER);

        // --- act ---
        $response = $controller->verify(Request::create('/ReAuth/Verify', 'POST', ['user_input' => TU_PASS]));

        // --- assert ---
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('/ReAuth/CAS', (string) $response->getContent());
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

    /** Coming back from CAS as someone else re-renders the prompt without re-authenticating. */
    public function testCasCallbackDoesNotReAuthenticateAnotherIdentity(): void
    {
        // --- arrange ---
        $controller = $this->makeCasController('glpi');
        $controller->casStart();

        // --- act ---
        $response = $controller->casCallback(Request::create('/ReAuth/CAS/Callback', 'GET', ['ticket' => 'ST-1-abc']));

        // --- assert ---
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('/ReAuth/CAS', (string) $response->getContent());
        $this->assertArrayNotHasKey('glpi_reauth_until', $_SESSION);
    }
}
