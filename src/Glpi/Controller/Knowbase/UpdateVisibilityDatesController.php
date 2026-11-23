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

final class UpdateVisibilityDatesController extends AbstractController
{
    use CrudControllerTrait;

    #[Route(
        "/Knowbase/{id}/UpdateVisibilityDates",
        name: "knowbase_update_visibility_dates",
        methods: ["POST"],
        requirements: [
            'id' => '\d+',
        ]
    )]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        // Decode data
        $data = json_decode($request->getContent(), true);

        // Build update input
        $update_input = [];
        if (array_key_exists('begin_date', $data)) {
            $update_input['begin_date'] = $data['begin_date'] ?: null;
        }
        if (array_key_exists('end_date', $data)) {
            $update_input['end_date'] = $data['end_date'] ?: null;
        }
        if ($update_input === []) {
            return new JsonResponse(['error' => 'No fields provided'], 400);
        }

        // Execute update
        $this->update(
            KnowbaseItem::class,
            $id,
            $update_input,
        );
        return new JsonResponse(['success' => true]);
    }
}
