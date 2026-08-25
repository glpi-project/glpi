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
use Glpi\Knowbase\Aside\Builder;
use KnowbaseItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Renders the children of a single knowledge base article, for the aside tree.
 *
 * The tree is folded by default and renders a folded article without its
 * children: rendering the whole knowledge base up front is what makes a large
 * one expensive. This fills a branch in when the reader unfolds it.
 */
final class AsideArticleChildrenController extends AbstractController
{
    #[Route(
        "/Knowbase/Aside/Article/{id}/Children",
        name: "knowbase_aside_article_children",
        requirements: [
            'id' => '\d+',
        ],
        methods: 'GET',
    )]
    public function __invoke(int $id, Request $request): Response
    {
        if (!KnowbaseItem::canView()) {
            throw new AccessDeniedHttpException();
        }

        // Visibility is applied by the builder, which returns nothing for an
        // article the current user may not see.
        $children = (new Builder($request->query->getInt('current_id')))->buildChildren($id);

        return $this->render('pages/tools/kb/aside_children.html.twig', [
            'children'     => $children,
            'can_create'   => KnowbaseItem::canCreate(),
            'show_actions' => KnowbaseItem::canShowAsideActions(),
        ]);
    }
}
