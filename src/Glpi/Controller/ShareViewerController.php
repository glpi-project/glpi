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
use Glpi\Security\Attribute\SecurityStrategy;
use Glpi\Security\ShareTokenManager;
use Glpi\ShareableInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShareViewerController extends AbstractController
{
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    #[Route(
        "/Share/View/{itemtype}/{items_id}",
        name: "glpi_share_view",
        methods: "GET",
        requirements: ['itemtype' => '[A-Za-z\\\\]+', 'items_id' => '\d+'],
    )]
    public function __invoke(Request $request, string $itemtype, int $items_id): Response
    {
        if (!is_a($itemtype, \CommonDBTM::class, true) || !is_a($itemtype, ShareableInterface::class, true)) {
            throw new NotFoundHttpException();
        }

        // Primary access check: token query parameter (works without session/cookies).
        // Fallback: session grant set by ShareAccessController on the initial redirect.
        $token = $request->query->getString('t');
        if ($token !== '') {
            $token_data = ShareTokenManager::validateToken($token);
            if (
                $token_data === null
                || $token_data['itemtype'] !== $itemtype
                || $token_data['items_id'] !== $items_id
            ) {
                throw new NotFoundHttpException();
            }
            // Ensure embedded document requests (images, attachments) also work.
            ShareTokenManager::grantSessionAccess($itemtype, $items_id, $token);
        } elseif (!ShareTokenManager::hasSessionAccess($itemtype, $items_id)) {
            throw new NotFoundHttpException();
        }

        $item = getItemForItemtype($itemtype);
        if (!($item instanceof \CommonDBTM) || !($item instanceof ShareableInterface)) {
            throw new NotFoundHttpException();
        }

        if (!$item->getFromDB($items_id)) {
            throw new NotFoundHttpException();
        }

        if ($item->maybeDeleted() && $item->isDeleted()) {
            throw new NotFoundHttpException();
        }

        $response = $this->render(
            $item->getShareableViewTemplate(),
            $item->getShareableViewParams(),
        );
        $response->headers->set('Referrer-Policy', 'no-referrer');
        return $response;
    }
}
