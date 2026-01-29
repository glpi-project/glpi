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
use KnowbaseItem;
use KnowbaseItem_Comment;
use RuntimeException;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AddCommentController extends AbstractController
{
    #[Route(
        "/Knowbase/{id}/AddComment",
        name: "knowbase_article_add_comment",
        requirements: [
            'id' => '\d+',
        ]
    )]
    public function __invoke(int $id, Request $request): Response
    {
        // Parse kb id
        $kb = KnowbaseItem::getById($id);
        if (!$kb) {
            throw new BadRequestHttpException();
        }

        // Parse content
        $content = $request->request->getString('content');
        if (empty($content)) {
            throw new BadRequestHttpException();
        }

        $comment = new KnowbaseItem_Comment();
        $input = [
            'knowbaseitems_id' => $id,
            'comment' => $content,
        ];
        if (!$comment->can(-1, CREATE, $input)) {
            throw new AccessDeniedHttpException();
        }

        // Try to add comment
        if (!$comment->add($input)) {
            throw new RuntimeException("Failed to create comment");
        }

        // Render new comment
        return $this->render('pages/tools/kb/sidepanel/comment.html.twig', [
            'comment_id'    => $comment->getID(),
            'user'          => Session::getCurrentUser(),
            'date_creation' => $comment->fields['date_creation'],
            'comment'       => $comment->fields['comment'],
            'can_edit'      => true,
            'can_delete'    => true,
        ]);
    }
}
