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

namespace Glpi\Controller\OAuth;

use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\OAuth\AccessTokenRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OAuthController extends AbstractController
{
    #[Route(
        "/OAuth/AccessToken/{access_token}/Revoke",
        name: "oauth_revoke_access_token",
        requirements: [
            'access_token' => '\w+',
        ],
        methods: ['POST']
    )]
    public function revokeAccessToken(Request $request): Response
    {
        if (!\OAuthClient::canUpdate()) {
            throw new AccessDeniedHttpException();
        }
        $repo = new AccessTokenRepository();
        $repo->revokeAccessToken($request->attributes->getString('access_token'));
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
