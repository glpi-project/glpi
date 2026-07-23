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

declare(strict_types=1);

namespace Glpi\Tests\Controller\Security;

use Glpi\Controller\AbstractController;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Glpi\Security\ReAuth\ReAuthManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Test-only endpoints to control the reauth ("sudo") session state.
 *
 * Since the "no reauth granted on login" change, a freshly logged-in user is
 * no longer reauthenticated, so any page requiring reauth redirects to the
 * prompt. E2E tests (Cypress/Playwright) log in through a direct HTTP POST and
 * cannot go through the interactive prompt, so they use these endpoints to
 * grant/revoke reauth on the current session.
 *
 * These routes are only exposed in test environments (see routes/testing.php
 * and routes/e2e_testing.php).
 */
final class ReAuthTestController extends AbstractController
{
    public function __construct(
        private readonly ReAuthManager $reAuthManager,
    ) {}

    #[Route(
        path: "/test/reauth/grant",
        name: "test_reauth_grant",
        methods: ['POST']
    )]
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    public function grant(): Response
    {
        $this->reAuthManager->authenticate();

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[Route(
        path: "/test/reauth/revoke",
        name: "test_reauth_revoke",
        methods: ['POST']
    )]
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    public function revoke(): Response
    {
        $this->reAuthManager->revoke();

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
