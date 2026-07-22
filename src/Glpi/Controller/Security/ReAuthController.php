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
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Glpi\Security\ReAuth\ReAuthManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ReAuthController extends AbstractController
{
    public function __construct(
        private readonly UrlGeneratorInterface $router,
        private readonly ReAuthManager $reAuthManager,
    ) {}

    #[Route(
        path: "/ReAuth/Prompt",
        name: "reauth_prompt",
        methods: ['GET']
    )]
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    public function prompt(bool $failed = false): Response
    {
        return new Response(
            TemplateRenderer::getInstance()->render(
                'pages/reauth/prompt.html.twig', // includes the template defined in $this->buildTemplateContext() ('template' key).
                [
                    ...$this->buildTemplateContext(),
                    'failed' => $failed,
                ]
            )
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
        // Redirect-based strategies (e.g. OAuth) are verified out-of-band by their
        // own endpoint, not through this synchronous form post.
        if ($this->reAuthManager->isRedirectStrategy()) {
            throw new BadRequestHttpException();
        }

        $user_input = $request->request->get('user_input');

        if ($this->reAuthManager->verify((string) $user_input)) {
            $this->reAuthManager->authenticate();

            return new Response(
                TemplateRenderer::getInstance()->render('pages/redirect_post.html.twig', [
                    'http_method' => $this->reAuthManager->getRequestedMethod(),
                    'url'         => $this->reAuthManager->getRequestedURL(),
                    'post_data'   => $this->reAuthManager->getRequestedPostData(),
                ])
            );
        }

        return $this->prompt(true);
    }

    /**
     * @return array{cancel_url: string, action: string, label: string, template: string, is_redirect: bool, reauth_url: ?string}
     */
    private function buildTemplateContext(): array
    {
        $is_redirect = $this->reAuthManager->isRedirectStrategy();

        return [
            'cancel_url'    => $this->reAuthManager->getOriginURL(),
            'action'        => $this->router->generate('reauth_verify'),
            'label'         => $this->reAuthManager->getLabel(),
            'template'      => $this->reAuthManager->getPromptTemplate(),
            // Redirect-based strategies (e.g. OAuth) are verified out-of-band:
            // the prompt shows a link to `reauth_url` instead of an input form.
            'is_redirect'   => $is_redirect,
            'reauth_url'    => $is_redirect ? $this->reAuthManager->getReauthUrl() : null,
        ];
    }
}
