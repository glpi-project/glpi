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

use function Safe\json_decode;

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
        $this->validateShareableItem($itemtype, $items_id);
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
        $this->validateShareableItem($itemtype, $items_id);
        /** @var class-string<\CommonDBTM> $itemtype */
        $data = json_decode($request->getContent(), true);
        $name = $data['name'] ?? null;

        $token = ShareToken::createToken($itemtype, $items_id, $name);
        if ($token === false) {
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
        $this->validateTokenOwnership($token_id);

        $success = ShareToken::toggleActive($token_id);

        return new JsonResponse(['success' => $success]);
    }

    #[Route(
        "/Share/Token/{token_id}/Regenerate",
        name: "glpi_share_token_regenerate",
        methods: ["POST"],
        requirements: ['token_id' => '\d+'],
    )]
    public function regenerate(int $token_id): JsonResponse
    {
        $this->validateTokenOwnership($token_id);

        $token = ShareToken::regenerateToken($token_id);
        if ($token === false) {
            return new JsonResponse(
                ['success' => false, 'message' => __('Failed to regenerate token')],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        return new JsonResponse([
            'success' => true,
            'token'   => $token->fields,
        ]);
    }

    #[Route(
        "/Share/Token/{token_id}/Delete",
        name: "glpi_share_token_delete",
        methods: ["POST"],
        requirements: ['token_id' => '\d+'],
    )]
    public function delete(int $token_id): JsonResponse
    {
        $this->validateTokenOwnership($token_id);

        $share_token = new ShareToken();
        $success = $share_token->delete(['id' => $token_id], true);

        return new JsonResponse(['success' => $success]);
    }

    /**
     * Validate that the itemtype implements ShareableInterface and the user can manage sharing.
     */
    private function validateShareableItem(string $itemtype, int $items_id): void
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
     * Validate that the token exists and the user can manage sharing on the associated item.
     */
    private function validateTokenOwnership(int $token_id): void
    {
        $share_token = new ShareToken();
        if (!$share_token->getFromDB($token_id)) {
            throw new NotFoundHttpException();
        }

        $itemtype = $share_token->fields['itemtype'];
        $items_id = (int) $share_token->fields['items_id'];

        $this->validateShareableItem($itemtype, $items_id);
    }
}
