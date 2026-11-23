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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function Safe\json_decode;

final class UpdateServiceCatalogController extends AbstractController
{
    use CrudControllerTrait;

    #[Route(
        "/Knowbase/{id}/UpdateServiceCatalog",
        name: "knowbase_item_update_service_catalog",
        methods: ["POST"],
        requirements: [
            'id' => '\d+',
        ]
    )]
    public function __invoke(int $id, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        // When the service catalog is disabled, the fieldset is disabled in the
        // browser, so textarea/select are not submitted so only the hidden inputs
        // (id, is_pinned) and the toggle (show_in_service_catalog) are sent.
        $show_in_catalog = isset($data['show_in_service_catalog'])
            && $data['show_in_service_catalog'] != 0
        ;

        if ($show_in_catalog) {
            $this->validateInputHasExactKeys($data, [
                'description',
                'forms_categories_id',
                'id',
                'is_pinned',
                'show_in_service_catalog',
            ]);
        } else {
            $this->validateInputHasExactKeys($data, [
                'id',
                'show_in_service_catalog',
            ]);
        }

        $this->update(KnowbaseItem::class, $id, $data);
        return new Response(); // OK
    }
}
