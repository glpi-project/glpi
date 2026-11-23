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

namespace tests\units\Glpi\Kernel\Listener\RequestListener;

use Glpi\Kernel\Listener\RequestListener\ReAuthReplayListener;
use Glpi\Security\ReAuth\ReAuthManager;
use Glpi\Tests\Glpi\Security\ReAuth\ReAuthTrait;
use Html;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * The referer of a request replayed after a re-authentication is the verification page, which is
 * not a page the user can be sent back to. The listener restores the origin page recorded in
 * session, so that Html::back() and everything else relying on the referer keep working.
 */
#[Group('reauth')]
final class ReAuthReplayListenerTest extends TestCase
{
    use ReAuthTrait;

    private const ORIGIN_URL = 'https://glpi.example.org/front/central.php';
    private const VERIFY_URL = 'https://glpi.example.org/ReAuth/Verify';

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetReAuthManager();
    }

    protected function tearDown(): void
    {
        $this->restoreWebContext();
        unset($_SESSION['glpi_reauth_origin_url']);
        $this->resetReAuthManager();
        parent::tearDown();
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testRefererIsRestoredOnAReplayedRequest(): void
    {
        // --- arrange ---
        $this->getReAuthManager()->setOriginURL(self::ORIGIN_URL);
        $event = $this->makeRequestEvent([ReAuthManager::RESTORE_REFERER_PARAM => '1']);

        // --- act ---
        $this->makeListener()->onKernelRequest($event);

        // --- assert : both the header bag, read through the Symfony request, and the superglobal,
        // read by the legacy scripts ---
        $this->assertSame(self::ORIGIN_URL, $event->getRequest()->headers->get('referer'));
        $this->assertSame(self::ORIGIN_URL, $_SERVER['HTTP_REFERER']);
        $this->assertSame(self::ORIGIN_URL, Html::getRefererUrl());
    }

    /**
     * A replayed POST request carries the parameter in its body, not in the query string.
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testRefererIsRestoredOnAReplayedPostRequest(): void
    {
        // --- arrange ---
        $this->getReAuthManager()->setOriginURL(self::ORIGIN_URL);
        $event = $this->makeRequestEvent(
            [ReAuthManager::RESTORE_REFERER_PARAM => '1'],
            method: 'POST',
        );

        // --- act ---
        $this->makeListener()->onKernelRequest($event);

        // --- assert ---
        $this->assertSame(self::ORIGIN_URL, $event->getRequest()->headers->get('referer'));
        $this->assertSame(self::ORIGIN_URL, $_SERVER['HTTP_REFERER']);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testRefererIsLeftUntouchedWithoutTheParam(): void
    {
        // --- arrange ---
        $this->getReAuthManager()->setOriginURL(self::ORIGIN_URL);
        $event = $this->makeRequestEvent([]);

        // --- act ---
        $this->makeListener()->onKernelRequest($event);

        // --- assert : an ordinary request keeps the referer sent by the browser ---
        $this->assertSame(self::VERIFY_URL, $event->getRequest()->headers->get('referer'));
        $this->assertSame(self::VERIFY_URL, $_SERVER['HTTP_REFERER']);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testSubRequestsAreIgnored(): void
    {
        // --- arrange ---
        $this->getReAuthManager()->setOriginURL(self::ORIGIN_URL);
        $event = $this->makeRequestEvent(
            [ReAuthManager::RESTORE_REFERER_PARAM => '1'],
            HttpKernelInterface::SUB_REQUEST
        );

        // --- act ---
        $this->makeListener()->onKernelRequest($event);

        // --- assert : only the main request is the replayed one ---
        $this->assertSame(self::VERIFY_URL, $event->getRequest()->headers->get('referer'));
    }

    /**
     * The forged parameter carries no value: it can only restore the user's own origin page.
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testForgedParamOnlyRestoresTheSessionOriginPage(): void
    {
        // --- arrange ---
        $this->getReAuthManager()->setOriginURL(self::ORIGIN_URL);
        $event = $this->makeRequestEvent([
            ReAuthManager::RESTORE_REFERER_PARAM => '1',
            '_glpi_http_referer'                 => 'https://evil.tld/phishing',
        ]);

        // --- act ---
        $this->makeListener()->onKernelRequest($event);

        // --- assert ---
        $this->assertSame(self::ORIGIN_URL, $event->getRequest()->headers->get('referer'));
    }

    private function makeListener(): ReAuthReplayListener
    {
        return new ReAuthReplayListener($this->getReAuthManager());
    }

    /**
     * @param array<string, string> $parameters Sent as the query string on GET, as the body on POST
     */
    private function makeRequestEvent(
        array $parameters,
        int $request_type = HttpKernelInterface::MAIN_REQUEST,
        string $method = 'GET',
    ): RequestEvent {
        $is_post = $method === 'POST';
        $query = $is_post ? [] : $parameters;

        $this->fakeWebContext(
            request_uri: '/ajax/switchdebug.php' . ($query === [] ? '' : '?' . http_build_query($query)),
            method: $method,
            get: $query,
            post: $is_post ? $parameters : [],
            referer: self::VERIFY_URL,
        );

        $request = new Request($query, $is_post ? $parameters : []);
        $request->headers->set('referer', self::VERIFY_URL);

        return new RequestEvent(
            $this->createMock(KernelInterface::class),
            $request,
            $request_type
        );
    }
}
