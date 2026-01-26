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

namespace Glpi\Controller\Knowbase;

use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use KnowbaseItem_Comment;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UpdateCommentController extends AbstractController
{
    #[Route(
        "/Knowbase/Comment/{id}/Update",
        name: "knowbase_comment_update",
        methods: ["POST"],
        requirements: [
            'id' => '\d+',
        ]
    )]
    public function __invoke(int $id, Request $request): Response
    {
        $comment = new KnowbaseItem_Comment();
        if (!$comment->getFromDB($id)) {
            throw new NotFoundHttpException();
        }

        $content = $request->request->getString('content');
        if (empty($content)) {
            throw new BadRequestHttpException();
        }

        $input = [
            'id'      => $id,
            'comment' => $content,
        ];
        if (!$comment->can($id, UPDATE, $input)) {
            throw new AccessDeniedHttpException();
        }

        $success = $comment->update($input);

        if (!$success) {
            throw new RuntimeException("Failed to update comment");
        }

        return new JsonResponse([
            'success' => true,
            'comment' => $comment->fields['comment'],
        ]);
    }
}
