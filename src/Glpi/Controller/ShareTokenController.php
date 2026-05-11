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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\ShareableInterface;
use Glpi\ShareToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShareTokenController extends AbstractController
{
    #[Route(
        "/Share/Token/{itemtype}/{items_id}",
        name: "glpi_share_token_list",
        methods: ["GET"],
        requirements: ['items_id' => '\d+'],
    )]
    public function list(string $itemtype, int $items_id): JsonResponse
    {
        $this->checkRightToShareItem($itemtype, $items_id);
        /** @var class-string<\CommonDBTM> $itemtype */
        $tokens = ShareToken::getTokensForItem($itemtype, $items_id);

        return new JsonResponse(['tokens' => $tokens]);
    }

    #[Route(
        "/Share/Token/{itemtype}/{items_id}",
        name: "glpi_share_token_create",
        methods: ["POST"],
        requirements: ['items_id' => '\d+'],
    )]
    public function create(Request $request, string $itemtype, int $items_id): JsonResponse
    {
        $this->checkRightToShareItem($itemtype, $items_id);
        /** @var class-string<\CommonDBTM> $itemtype */
        $name = $request->getPayload()->getString('name') ?: null;

        $input = [
            'itemtype'  => $itemtype,
            'items_id'  => $items_id,
            'name'      => $name,
            'is_active' => 1,
        ];

        $token = new ShareToken();

        if ($token->add($input) === false) {
            return new JsonResponse(
                ['success' => false, 'message' => __('Failed to create share link')],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        return new JsonResponse([
            'success' => true,
            'token'   => $token->fields,
        ]);
    }

    #[Route(
        "/Share/Token/{token_id}/Toggle",
        name: "glpi_share_token_toggle",
        methods: ["POST"],
        requirements: ['token_id' => '\d+'],
    )]
    public function toggle(int $token_id): JsonResponse
    {
        $token = new ShareToken();
        if (!$token->getFromDB($token_id)) {
            throw new NotFoundHttpException();
        }

        $this->checkRightToUpdateToken($token);

        $success = $token->update([
            'id'        => $token_id,
            'is_active' => $token->fields['is_active'] ? 0 : 1,
        ]);

        return new JsonResponse(['success' => $success]);
    }

    #[Route(
        "/Share/Token/{token_id}/Delete",
        name: "glpi_share_token_delete",
        methods: ["POST"],
        requirements: ['token_id' => '\d+'],
    )]
    public function delete(int $token_id): JsonResponse
    {
        $token = new ShareToken();
        if (!$token->getFromDB($token_id)) {
            throw new NotFoundHttpException();
        }

        $this->checkRightToUpdateToken($token);

        $share_token = new ShareToken();
        $success = $share_token->delete(['id' => $token_id], true);

        return new JsonResponse(['success' => $success]);
    }

    /**
     * Validate that the itemtype implements ShareableInterface and the user can manage sharing.
     */
    private function checkRightToShareItem(string $itemtype, int $items_id): void
    {
        $item = getItemForItemtype($itemtype);
        if (!($item instanceof \CommonDBTM) || !($item instanceof ShareableInterface)) {
            throw new BadRequestHttpException();
        }

        if (!$item->getFromDB($items_id)) {
            throw new NotFoundHttpException();
        }

        if (!$item->canManageSharing()) {
            throw new AccessDeniedHttpException();
        }
    }

    /**
     * Validate the user can manage sharing on the item associated to the token.
     */
    private function checkRightToUpdateToken(ShareToken $token): void
    {
        $itemtype = $token->fields['itemtype'];
        $items_id = (int) $token->fields['items_id'];

        $this->checkRightToShareItem($itemtype, $items_id);
    }
}
