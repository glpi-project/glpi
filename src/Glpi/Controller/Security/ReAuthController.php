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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Controller\AbstractController;
use Glpi\Exception\RedirectException;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Glpi\Security\ReAuthManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ReAuthController extends AbstractController
{
    private ReAuthManager $reAuthManager;

    public function __construct(
        private readonly ?UrlGeneratorInterface $router = null
    ) {
        $this->reAuthManager = new ReAuthManager();
    }


    #[Route(
        path: "/ReAuth/Prompt",
        name: "reauth_prompt",
        methods: ['GET']
    )]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function prompt(Request $request): Response
    {
        return new Response(TemplateRenderer::getInstance()->render('pages/2fa/2fa_request.html.twig', [
            'redirect' => $this->reAuthManager->getRedirectSuccessURL(),
            'action' => $this->router->generate('reauth_verify'),
        ]));
    }

    #[Route(
        path: "/ReAuth/Verify",
        name: "reauth_verify",
        methods: ['POST']
    )]
    public function verify(Request $request): void
    {
        $totp_code = $request->get('totp_code');
        if (is_array($totp_code)) {
            $totp_code = implode('', $totp_code);
        }
        if ($this->reAuthManager->verify((string) $totp_code)) { // @todo refacto gestion de l'array dans verify ?
            $this->reAuthManager->authenticate();
            throw new RedirectException($this->reAuthManager->getRedirectSuccessURL());
        }

        throw new \Exception('Vérif ratée : Rediriger vers ? petit bloc avec message d\'erreur et bouton pour revenir à la page précédente ?');
    }
}
