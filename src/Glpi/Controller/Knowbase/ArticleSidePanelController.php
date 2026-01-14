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
use Glpi\Knowbase\SidePanel\CommentsRenderer;
use Glpi\Knowbase\SidePanel\RendererInterface;
use KnowbaseItem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ArticleSidePanelController extends AbstractController
{
    #[Route(
        "/Knowbase/{id}/SidePanel/{key}",
        name: "knowbase_article_side_panel",
        requirements: [
            'id' => '\d+',
        ]
    )]
    public function __invoke(int $id, string $key): Response
    {
        // Parse id
        $kb = KnowbaseItem::getById($id);
        if (!$kb) {
            throw new BadRequestHttpException();
        }
        if (!$kb::canView() || !$kb->canViewItem()) {
            throw new AccessDeniedHttpException();
        }

        // Get renderer
        $renderer = $this->getRendererForKey($key);
        if (!$renderer->canView($kb)) {
            throw new AccessDeniedHttpException();
        }

        // Render content
        return $this->render(
            $renderer->getTemplate(),
            $renderer->getParams($kb),
        );
    }

    private function getRendererForKey(string $key): RendererInterface
    {
        return match ($key) {
            'comments' => new CommentsRenderer(),
            default    => throw new BadRequestHttpException(),
        };
    }
}
