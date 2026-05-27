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
use KnowbaseItem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

use function Safe\json_decode;

final class UpdateCategoriesController extends AbstractController
{
    use CrudControllerTrait;

    #[Route(
        "/Knowbase/{id}/UpdateCategories",
        name: "knowbase_update_categories",
        methods: ["POST"],
        requirements: ['id' => '\d+'],
    )]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !array_key_exists('categories_ids', $data) || !is_array($data['categories_ids'])) {
            return new JsonResponse(['error' => 'Invalid payload'], 400);
        }

        $ids = array_values(array_unique(
            array_filter(
                array_map('intval', $data['categories_ids']),
                fn(int $v) => $v > 0
            )
        ));

        $this->update(KnowbaseItem::class, $id, [
            '__categories_defined' => 1,
            '_categories'          => $ids,
        ]);

        return new JsonResponse(['success' => true]);
    }
}
