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
use Glpi\ShareableInterface;
use Session;
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
    public function __invoke(string $token): Response
    {
        $token_data = ShareTokenManager::validateToken($token);
        if ($token_data === null) {
            throw new NotFoundHttpException();
        }

        $itemtype = $token_data['itemtype'];
        $items_id = $token_data['items_id'];

        $item = getItemForItemtype($itemtype);
        if (!($item instanceof \CommonDBTM) || !($item instanceof ShareableInterface)) {
            throw new NotFoundHttpException();
        }

        if (!$item->getFromDB($items_id)) {
            throw new NotFoundHttpException();
        }

        // Respect soft-delete
        if ($item->maybeDeleted() && $item->isDeleted()) {
            throw new NotFoundHttpException();
        }

        // Grant session-based read access
        ShareTokenManager::grantSessionAccess($itemtype, $items_id, $token);

        // Authenticated user: redirect to normal item URL
        if (Session::isAuthenticated()) {
            $response = new RedirectResponse($item->getItemUrl());
            $response->headers->set('Referrer-Policy', 'no-referrer');
            return $response;
        }

        // Anonymous user: redirect to dedicated sessionless view controller (route "glpi_share_view").
        // The token is passed as a query parameter so the viewer can validate access without
        // relying on session cookies (works for cookie-less browsers, scrapers, link previewers).
        global $CFG_GLPI;
        $response = new RedirectResponse($CFG_GLPI['root_doc'] . '/Share/View/' . rawurlencode($itemtype) . '/' . $items_id . '?t=' . rawurlencode($token));
        $response->headers->set('Referrer-Policy', 'no-referrer');
        return $response;
    }
}
