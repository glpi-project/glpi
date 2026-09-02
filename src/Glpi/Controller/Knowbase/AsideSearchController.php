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
use Glpi\Knowbase\Aside\SearchResultsBuilder;
use KnowbaseItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Render one page of the aside search results, which replace the article tree
 * for as long as the reader is searching.
 */
final class AsideSearchController extends AbstractController
{
    #[Route(
        "/Knowbase/Aside/Search",
        name: "knowbase_aside_search",
        methods: 'GET',
    )]
    public function __invoke(Request $request): Response
    {
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

        // Validate offset
        $offset = $request->query->getInt('offset');
        if ($offset < 0 || $offset % SearchResultsBuilder::PAGE_SIZE !== 0) {
            throw new BadRequestHttpException();
        }

        $builder = new SearchResultsBuilder($request->query->getInt('current_id'));

        return $this->render('pages/tools/kb/aside_search_results.html.twig', [
            'results'  => $builder->build($contains, $offset),
            'contains' => $contains,
        ]);
    }
}
