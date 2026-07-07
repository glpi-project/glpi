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
use Glpi\Tests\Glpi\Security\ReAuth\ReAuthTestTrait;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Safe\DateTime;
use User;

#[Group('reauth')]
class ReAuthManagerTest extends DbTestCase
{
    use ReAuthTestTrait;

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
        yield 'validity exactly now' => [0, false];
        yield 'validity not set' => [null, false];
    }

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
        $this->fakeWebContext(request_uri: '/front/user.form.php?id=2');

        // --- act + assert ---
        $this->expectException(RedirectException::class);
        $manager->checkReAuthenticationOrRedirect();
    }

    /** POST body and method are persisted in session before the RedirectException is thrown. */
    public function testRedirectToReauthStoresPostRequestData(): void
    {
        // --- arrange ---
        $manager = new ReAuthManager();
        $this->fakeWebContext(
            request_uri: '/front/user.form.php?id=2',
            method: 'POST',
            post: ['name' => 'value'],
            referer: 'https://glpi.example.org/front/user.php',
        );

        // --- act ---
        // try/catch because $this->expectException() stop the test at the point exception is thrown
        // so testing $manager->xxx() would be impossible.
        try {
            $manager->redirectToReauth();
            $this->fail('A RedirectException should have been thrown.');
        } catch (RedirectException) {
        }

        // --- assert ---
        $this->assertSame('POST', $manager->getRequestedMethod());
        $this->assertSame('value', $manager->getRequestedPostData()['name']);
        // The GET query string of a POST request is preserved: browsers keep the action
        // URL's query string untouched on replay since the form data goes in the body.
        $this->assertSame('https://glpi.example.org/front/user.form.php?id=2', $manager->getRequestedURL());
        // _glpi_http_referer is only meaningful for POST replays: Html::getRefererUrl()
        // reads it from $_POST, so it must be present here.
        $this->assertArrayHasKey('_glpi_http_referer', $manager->getRequestedPostData());
    }

    /** The requested URL drops its GET query string: browsers rebuild it from the form fields on replay. */
    public function testRedirectToReauthStoresUrlWithoutGetParams(): void
    {
        // --- arrange ---
        $manager = new ReAuthManager();
        $requested_url = '/front/user.form.php?id=2&another_param=value';
        $this->fakeWebContext(request_uri: $requested_url);

        // --- act ---
        try {
            $manager->redirectToReauth();
            $this->fail('A RedirectException should have been thrown.');
        } catch (RedirectException) {
        }

        // --- assert ---
        $this->assertSame('https://glpi.example.org/front/user.form.php', $manager->getRequestedURL());
        // _glpi_http_referer would only ever land in $_GET on replay, which
        // Html::getRefererUrl() never reads, so it must not be injected here.
        $this->assertArrayNotHasKey('_glpi_http_referer', $manager->getRequestedPostData());
    }

    /** All getters return safe defaults when the re-auth session keys are absent. */
    public function testGettersReturnDefaultsWhenSessionEmpty(): void
    {
        global $CFG_GLPI;

        // --- arrange ---
        $manager = new ReAuthManager();
        unset(
            $_SESSION['glpi_reauth_requested_url'],
            $_SESSION['glpi_reauth_origin_url'],
            $_SESSION['glpi_reauth_requested_httpmethod'],
            $_SESSION['glpi_reauth_requested_post_data'],
        );

        // --- assert ---
        $this->assertSame('/', $manager->getRequestedURL());
        $this->assertSame('GET', $manager->getRequestedMethod());
        $this->assertSame($CFG_GLPI['root_doc'], $manager->getOriginURL());
        // Default method is GET: no _glpi_http_referer is injected (see testRedirectToReauthStoresPostRequestData).
        $this->assertSame([], $manager->getRequestedPostData());
    }

    /** Throws InvalidArgumentException when a non-CommonGLPI class is passed. */
    public function testAtLeastOneItemTypeRequiresReauthenticationThrowsOnInvalidType(): void
    {
        // --- arrange ---
        $manager = new ReAuthManager();

        // --- act + assert ---
        $this->expectException(InvalidArgumentException::class);
        $manager->atLeastOneItemTypesRequiresReauthentication([\stdClass::class]);
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
        $this->assertSame($expected, $manager->atLeastOneItemTypesRequiresReauthentication($itemtypes));
    }
}
