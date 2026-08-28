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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Knowbase\Aside\Builder;
use KnowbaseItem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class AsideSearchController extends AbstractController
{
    #[Route(
        "/Knowbase/Aside/Search",
        name: "knowbase_aside_search",
        methods: 'GET',
    )]
    public function __invoke(Request $request): JsonResponse
    {
        global $DB;

        // If we can't see the knowbase, it make no sense to search inside it
        if (!KnowbaseItem::canView()) {
            throw new AccessDeniedHttpException();
        }

        // Get requester filter
        $contains = trim($request->query->getString('contains'));
        if ($contains === '') {
            // An empty filter make no sense
            throw new BadRequestHttpException();
        }

        // Get article IDs that match this filter
        $criteria = KnowbaseItem::getListRequest(['contains' => $contains], 'search');

        // Only the matching ids are needed here
        $criteria['SELECT'] = [KnowbaseItem::getTableField('id')];
        unset($criteria['ORDERBY']);

        $ids = [];
        foreach ($DB->request($criteria) as $data) {
            $ids[] = (int) $data['id'];
        }

        // Render the filtered tree
        $tree = (new Builder($request->query->getInt('current_id')))->buildSearchTree($ids);

        return new JsonResponse([
            // Consumed by the favorites section, which filters the rows it
            // already holds.
            'ids'  => $ids,
            'html' => TemplateRenderer::getInstance()->render(
                'pages/tools/kb/aside_tree.html.twig',
                [
                    'tree'         => $tree,
                    'can_create'   => KnowbaseItem::canCreate(),
                    'show_actions' => KnowbaseItem::canShowAsideActions(),
                ]
            ),
        ]);
    }
}
