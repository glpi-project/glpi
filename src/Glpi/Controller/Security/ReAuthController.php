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

namespace Glpi\Controller\Security;

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

class ReAuthController extends AbstractController
{
    public function __construct(
        private readonly ReAuthManager $reAuthManager,
        private readonly ?CasReAuthStrategy $casStrategy = null,
    ) {}

    #[Route(
        path: "/ReAuth/Prompt",
        name: "reauth_prompt",
        methods: ['GET']
    )]
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    public function prompt(bool $failed = false): Response
    {
        return $this->render(
            'pages/reauth/prompt.html.twig',
            [
                ...$this->buildTemplateContext(),
                'failed' => $failed,
            ]
        );
    }

    #[Route(
        path: "/ReAuth/Verify",
        name: "reauth_verify",
        methods: ['POST']
    )]
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    public function verify(Request $request): Response
    {
        if ($this->reAuthManager->verify($request)) {
            return $this->replayRequested();
        }

        return $this->prompt(true);
    }

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
        if ($this->getCasStrategy()->complete($_SESSION['glpiID'], $request)) {
            return $this->replayRequested();
        }

        return $this->prompt(true);
    }

    private function replayRequested(): Response
    {
        $this->reAuthManager->authenticate();

        return $this->render('pages/redirect_post.html.twig', [
            'http_method' => $this->reAuthManager->getRequestedMethod(),
            'url'         => $this->reAuthManager->getRequestedURL(),
            'replay_data' => $this->reAuthManager->getReplayData(),
        ]);
    }

    /**
     * The CAS round-trip is only open to users for whom the CAS strategy is the selected one.
     */
    private function getCasStrategy(): CasReAuthStrategy
    {
        if (!$this->reAuthManager->isSelectedStrategy(CasReAuthStrategy::class)) {
            throw new AccessDeniedHttpException();
        }

        return $this->casStrategy ?? new CasReAuthStrategy();
    }

    /**
     * @return array{cancel_url: string, label: string, template: string, verify_url: string, verify_http_method: string}
     */
    private function buildTemplateContext(): array
    {
        return [
            'cancel_url'         => $this->reAuthManager->getOriginURL(),
            'label'              => $this->reAuthManager->getLabel(),
            'template'           => $this->reAuthManager->getPromptTemplate(),
            'verify_url'         => $this->reAuthManager->getVerifyUrl(),
            'verify_http_method' => $this->reAuthManager->getVerifyHttpMethod(),
        ];
    }
}
