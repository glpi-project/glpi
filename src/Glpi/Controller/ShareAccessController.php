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

namespace Glpi\Controller;

use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Http\Firewall;
use Glpi\Http\RedirectResponse;
use Glpi\Security\Attribute\SecurityStrategy;
use Glpi\Security\ShareTokenManager;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShareAccessController extends AbstractController
{
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    #[Route(
        "/Share/{token}",
        name: "glpi_share_access",
        methods: "GET",
        requirements: ['token' => '[0-9a-f]{64}'],
    )]
    public function __invoke(Request $request, string $token): Response
    {
        $token_manager = new ShareTokenManager();

        $shared_item = $token_manager->grantSessionAccess($token);
        if ($shared_item === null) {
            throw new NotFoundHttpException();
        }

        // Authenticated user: redirect to normal item URL
        if (Session::isAuthenticated()) {
            $response = new RedirectResponse($shared_item->getItemUrl());
            $response->headers->set('Referrer-Policy', 'no-referrer');
            return $response;
        }

        // Anonymous user: render the shared item into a sessionless state.
        $response = $this->render(
            $shared_item->getShareableViewTemplate(),
            $shared_item->getShareableViewParams(),
        );
        $response->headers->set('Referrer-Policy', 'no-referrer');
        return $response;
    }
}
