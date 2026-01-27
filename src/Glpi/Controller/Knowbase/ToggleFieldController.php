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
use Glpi\Exception\Http\BadRequestHttpException;
use KnowbaseItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function Safe\json_decode;

final class ToggleFieldController extends AbstractController
{
    use CrudControllerTrait;

    private const ALLOWED_FIELDS = ['is_faq'];

    #[Route(
        "/Knowbase/KnowbaseItem/{id}/ToggleField",
        name: "knowbase_toggle_field",
        requirements: [
            'id' => '\d+',
        ]
    )]
    public function __invoke(int $id, Request $request): Response
    {
        // Decode submitted data
        $data = json_decode($request->getContent(), true);
        $field = $data['field'] ?? null;
        $value = $data['value'] ?? null;

        // Validate parameters
        if ($field === null || $value === null) {
            throw new BadRequestHttpException();
        }
        if (!in_array($field, self::ALLOWED_FIELDS, true)) {
            throw new BadRequestHttpException();
        }

        // Update item
        $this->update(
            KnowbaseItem::class,
            $id,
            [$field => $value ? 1 : 0],
        );

        return new Response(); // OK
    }
}
