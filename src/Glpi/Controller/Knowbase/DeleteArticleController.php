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
use Glpi\Controller\CrudControllerTrait;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use KnowbaseItem;
use Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteArticleController extends AbstractController
{
    use CrudControllerTrait;

    #[Route(
        "/Knowbase/KnowbaseItem/{id}/Delete",
        name: "knowbase_article_delete",
        methods: ["POST"],
        requirements: [
            'id' => '\d+',
        ]
    )]
    public function __invoke(int $id): Response
    {
        $article = new KnowbaseItem();
        if (!$article->getFromDB($id)) {
            throw new NotFoundHttpException();
        }

        // Note: duplicated by the $this->purge call later but necessary to
        // avoid disclosing information about child articles to someone that
        // doesn't have the required rights.
        if (!$article->can($id, PURGE)) {
            throw new AccessDeniedHttpException();
        }

        if ($article->hasChildrenWithoutOtherParent()) {
            return new JsonResponse([
                'success' => false,
                // Rendered as HTML by `glpi_alert()`.
                'message' => \htmlescape(KnowbaseItem::getChildrenDeletionRefusalMessage()),
            ], Response::HTTP_CONFLICT);
        }

        $this->purge(KnowbaseItem::class, $id);
        Session::addMessageAfterRedirect(__s('Item successfully deleted.'));

        return new JsonResponse([
            'redirect' => KnowbaseItem::getSearchURL(),
        ]);
    }
}
