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

namespace Glpi\Controller\Security\Reauth;

use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Glpi\Security\ReAuth\CasReAuthStrategy;
use Glpi\Security\ReAuth\ReAuthManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CAS re-authentication round-trip, verified out of band by the CAS server.
 */
final class CASController extends AbstractController
{
    public function __construct(
        private readonly ReAuthManager $reauth_manager,
        private readonly ?CasReAuthStrategy $cas_strategy = null,
    ) {}

    /**
     * Send the user to the CAS server, which asks for the credentials again.
     */
    #[Route(
        path: "/ReAuth/CAS",
        name: "reauth_cas_start",
        methods: ['GET']
    )]
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    public function casStart(): Response
    {
        return new RedirectResponse($this->getCasStrategy()->start($_SESSION['glpiID']));
    }

    /**
     * Way back from the CAS server, with the service ticket to validate.
     */
    #[Route(
        path: "/ReAuth/CAS/Callback",
        name: "reauth_cas_callback",
        methods: ['GET']
    )]
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    public function casCallback(Request $request): Response
    {
        global $CFG_GLPI;

        if ($this->getCasStrategy()->complete($_SESSION['glpiID'], $request)) {
            $this->reauth_manager->authenticate();

            return $this->render('pages/redirect_post.html.twig', [
                'http_method' => $this->reauth_manager->getRequestedMethod(),
                'url'         => $this->reauth_manager->getRequestedURL(),
                'replay_data' => $this->reauth_manager->getReplayData(),
            ]);
        }

        return new RedirectResponse($CFG_GLPI['root_doc'] . '/ReAuth/Prompt?failed=1');
    }

    /**
     * The CAS round-trip is only open to users for whom the CAS strategy is the selected one.
     */
    private function getCasStrategy(): CasReAuthStrategy
    {
        if (!$this->reauth_manager->isSelectedStrategy(CasReAuthStrategy::class)) {
            throw new AccessDeniedHttpException();
        }

        return $this->cas_strategy ?? new CasReAuthStrategy();
    }
}
