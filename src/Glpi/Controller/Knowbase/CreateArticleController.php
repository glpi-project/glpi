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
use Glpi\Controller\CrudControllerTrait;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Knowbase\Aside\Article;
use KnowbaseItem;
use KnowbaseItem_Favorite;
use Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

use function Safe\json_decode;

final class CreateArticleController extends AbstractController
{
    use CrudControllerTrait;

    #[Route(
        "/Knowbase/KnowbaseItem/Create",
        name: "knowbase_article_create",
        methods: ["POST"],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new BadRequestHttpException();
        }

        $raw_category_id = (int) ($data['knowbaseitemcategories_id'] ?? 0);
        $category_id = KnowbaseItem::getReadablePrefilledCategoryId($raw_category_id);

        $item = $this->add(KnowbaseItem::class, [
            'name'         => $name,
            'answer'       => '',
            'entities_id'  => Session::getActiveEntity(),
            'is_recursive' => 0,
            '_categories'  => $category_id !== null ? [$category_id] : [],
        ]);

        $article = new Article(
            id: (int) $item->getID(),
            title: $item->fields['name'],
            illustration: $item->fields['illustration'] ?? '',
            link: KnowbaseItem::getFormURLWithID($item->getID()),
            is_current: true,
        );

        $show_actions = KnowbaseItem_Favorite::canCreate()
            || KnowbaseItem::canUpdate()
            || KnowbaseItem::canPurge();

        return new JsonResponse([
            'id'            => $article->id,
            'url'           => $article->link,
            'is_recursive'  => (bool) $item->fields['is_recursive'],
            'html'          => TemplateRenderer::getInstance()->render(
                'pages/tools/kb/aside_article_row.html.twig',
                ['article' => $article, 'show_actions' => $show_actions],
            ),
        ]);
    }
}
