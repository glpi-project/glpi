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
use Glpi\Knowbase\SidePanel\HistoryRenderer;
use KnowbaseItem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Render one page of the article history, to be appended to the history panel
 * as the user scrolls through it.
 *
 * The first page is rendered by the panel itself, see
 * `Glpi\Knowbase\SidePanel\HistoryRenderer`.
 */
final class HistoryPageController extends AbstractController
{
    #[Route(
        "/Knowbase/{id}/HistoryPage/{offset}",
        name: "knowbase_article_history_page",
        methods: ["GET"],
        requirements: [
            'id' => '\d+',
            'offset' => '\d+',
        ]
    )]
    public function __invoke(int $id, int $offset): Response
    {
        $kb = KnowbaseItem::getById($id);
        if (!$kb) {
            throw new BadRequestHttpException();
        }
        if (!$kb->can($id, READ)) {
            throw new AccessDeniedHttpException();
        }

        $renderer = new HistoryRenderer();
        if (!$renderer->canView($kb)) {
            throw new AccessDeniedHttpException();
        }

        return $this->render(
            $renderer->getPageTemplate(),
            $renderer->getPageParams($kb, $offset),
        );
    }
}
