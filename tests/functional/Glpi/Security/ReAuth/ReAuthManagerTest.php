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

use Computer;
use Glpi\Exception\RedirectException;
use Glpi\Security\ReAuth\ReAuthManager;
use Glpi\Tests\DbTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Safe\DateTime;
use User;

#[Group('reauth')]
class ReAuthManagerTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->setCurrentTime('2026-06-22 10:00:00');
    }

    public function tearDown(): void
    {
        // Always restore the CLI flag so the reauth branch does not leak to other tests.
        unset($GLOBALS['GLPI_IS_COMMAND_LINE']);
        parent::tearDown();
    }

    public static function isReAuthenticatedProvider(): iterable
    {
        // [offset_to_now (null = unset), expected]
        yield 'validity ok' => [ReAuthManager::REAUTH_DELAY_SECONDS, true];
        yield 'validity expired' => [-ReAuthManager::REAUTH_DELAY_SECONDS, false];
        yield 'validity not set' => [null, false];
    }

    /** Returns true/false based on the session validity window. */
    #[DataProvider('isReAuthenticatedProvider')]
    public function testIsReAuthenticated(?int $offset, bool $expected): void
    {
        // --- arrange ---
        $manager = new ReAuthManager();
        if ($offset === null) {
            unset($_SESSION['glpi_reauth_until']);
        } else {
            $_SESSION['glpi_reauth_until'] = (new DateTime($_SESSION['glpi_currenttime']))->getTimestamp() + $offset;
        }

        // --- act + assert ---
        $this->assertSame($expected, $manager->isReAuthenticated());
    }

    /** authenticate() sets glpi_reauth_until to currenttime + REAUTH_DELAY_SECONDS. */
    public function testAuthenticateSetsValidityToNowPlusDelay(): void
    {
        // --- arrange ---
        $manager = new ReAuthManager();
        unset($_SESSION['glpi_reauth_until']);

        // --- act ---
        $manager->authenticate();

        // --- assert ---
        $expected = (new DateTime($_SESSION['glpi_currenttime']))->getTimestamp() + ReAuthManager::REAUTH_DELAY_SECONDS;
        $this->assertSame($expected, $_SESSION['glpi_reauth_until']);
        $this->assertTrue($manager->isReAuthenticated());
    }

    /** No exception is thrown when the session is already re-authenticated. */
    public function testCheckReAuthenticationOrRedirectDoesNothingWhenReAuthenticated(): void
    {
        // --- arrange ---
        $manager = new ReAuthManager();
        $manager->authenticate();

        // --- act ---
        $manager->checkReAuthenticationOrRedirect();

        // --- assert : no exception thrown, the session is still valid ---
        $this->assertTrue($manager->isReAuthenticated());
    }

    /** Throws RedirectException when there is no valid re-authentication token in session. */
    public function testCheckReAuthenticationOrRedirectThrowsWhenNotReAuthenticated(): void
    {
        // --- arrange ---
        $manager = new ReAuthManager();
        unset($_SESSION['glpi_reauth_until']);
        $this->fakeWebRequest();

        // --- act + assert ---
        $this->expectException(RedirectException::class);
        $manager->checkReAuthenticationOrRedirect();
    }

    /** GET request URL and data are stored in session before the RedirectException is thrown. */
    public function testRedirectToReauthThrowsAndStoresGetRequest(): void
    {
        // --- arrange ---
        $manager = new ReAuthManager();
        $this->fakeWebRequest('GET', ['foo' => 'bar']);

        // --- act ---
        // try/catch because $this->expectException() stop the test at the point exception is thrown
        // so testing $manager->xxx() would be impossible.
        try {
            $manager->redirectToReauth();
            $this->fail('A RedirectException should have been thrown.');
        } catch (RedirectException $e) {
            $this->assertSame('/ReAuth/Prompt', $e->getResponse()->getTargetUrl());
        }

        // --- assert : the GET request that triggered reauth is recorded for replay ---
        // expected data defined in fakeWebRequest()
        $this->assertSame('https://glpi.example.org/front/user.form.php', $manager->getTargetURL());
        $this->assertSame('GET', $manager->getRedirectMethod());
        $this->assertSame('https://glpi.example.org/front/user.php', $manager->getCancelURL());
        $this->assertSame('bar', $manager->getRedirectData()['foo']);
    }

    /** POST body and method are persisted in session before the RedirectException is thrown. */
    public function testRedirectToReauthStoresPostRequestData(): void
    {
        // --- arrange ---
        $manager = new ReAuthManager();
        $this->fakeWebRequest('POST', [], ['name' => 'value']);

        // --- act ---
        // See testRedirectToReauthThrowsAndStoresGetRequest() comment
        try {
            $manager->redirectToReauth();
            $this->fail('A RedirectException should have been thrown.');
        } catch (RedirectException) {
        }

        // --- assert ---
        $this->assertSame('POST', $manager->getRedirectMethod());
        $this->assertSame('value', $manager->getRedirectData()['name']);
    }

    /** All getters return safe defaults when the re-auth session keys are absent. */
    public function testGettersReturnDefaultsWhenSessionEmpty(): void
    {
        global $CFG_GLPI;

        // --- arrange ---
        $manager = new ReAuthManager();
        unset(
            $_SESSION['glpi_reauth_target_url'],
            $_SESSION['glpi_reauth_cancel_url'],
            $_SESSION['glpi_reauth_httpmethod'],
            $_SESSION['glpi_reauth_data'],
        );

        // --- assert ---
        $this->assertSame('/', $manager->getTargetURL());
        $this->assertSame('GET', $manager->getRedirectMethod());
        $this->assertSame($CFG_GLPI['root_doc'], $manager->getCancelURL());
        // getRedirectData() always injects the referer pointing to the target URL.
        $this->assertSame(['_glpi_http_referer' => '/'], $manager->getRedirectData());
    }

    /** Throws InvalidArgumentException when a non-CommonGLPI class is passed. */
    public function testAtLeastOneItemTypeRequiresReauthenticationThrowsOnInvalidType(): void
    {
        // --- arrange ---
        $manager = new ReAuthManager();

        // --- act + assert ---
        $this->expectException(InvalidArgumentException::class);
        $manager->atLeastOneitemTypesRequiresReauthentication([\stdClass::class]);
    }

    public static function atLeastOneItemTypeRequiresReauthenticationProvider(): iterable
    {
        // [itemtypes, expected]
        yield 'one of the itemtypes requires reauth (1 item)' => [[User::class], true];
        yield 'one of the itemtypes requires reauth (2 items)' => [[Computer::class, User::class], true];
        yield 'none of the itemtypes requires reauth (0 item)' => [[], false];
        yield 'none of the itemtypes requires reauth (1 item)' => [[Computer::class], false];
    }

    /** Returns true only when at least one of the given itemtypes requires re-authentication. */
    #[DataProvider('atLeastOneItemTypeRequiresReauthenticationProvider')]
    public function testAtLeastOneItemTypeRequiresReauthentication(array $itemtypes, bool $expected): void
    {
        // --- arrange : web context, not yet re-authenticated ---
        $manager = new ReAuthManager();
        $GLOBALS['GLPI_IS_COMMAND_LINE'] = false;
        unset($_SESSION['glpi_reauth_until']);

        // --- act + assert ---
        $this->assertSame($expected, $manager->atLeastOneitemTypesRequiresReauthentication($itemtypes));
    }

    /**
     * Populate the superglobals that ReAuthManager::redirectToReauth() reads to
     * record the request that triggered the re-authentication.
     *
     * Also sets GLPI_IS_COMMAND_LINE = false to simulate a web request context.
     *
     * @param array<string, string> $get
     * @param array<string, string> $post
     */
    private function fakeWebRequest(string $method = 'GET', array $get = [], array $post = []): void
    {
        // Simulate a web context: PHPUnit runs in CLI, so isCommandLine() would return true otherwise.
        $GLOBALS['GLPI_IS_COMMAND_LINE'] = false;

        $_SERVER['REQUEST_SCHEME'] = 'https';
        $_SERVER['HTTP_HOST']      = 'glpi.example.org';
        $_SERVER['REQUEST_URI']    = '/front/user.form.php?id=2';
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['HTTP_REFERER']   = 'https://glpi.example.org/front/user.php';
        $_GET  = $get;
        $_POST = $post;
    }
}
