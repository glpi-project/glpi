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
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use KnowbaseItem_Favorite;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function Safe\json_decode;

final class ToggleFavoriteController extends AbstractController
{
    use CrudControllerTrait;

    #[Route(
        "/Knowbase/{id}/ToggleFavorite",
        name: "knowbase_toggle_favorite",
        requirements: [
            'id' => '\d+',
        ],
        methods: 'POST',
    )]
    public function __invoke(int $id, Request $request): Response
    {
        // Decode submitted data
        $data = json_decode($request->getContent(), true);
        $value = $data['value'] ?? null;

        if ($value === null) {
            throw new BadRequestHttpException();
        }

        $user_id = Session::getLoginUserID();
        if ($user_id === false) {
            throw new AccessDeniedHttpException();
        }

        $criteria = [
            'knowbaseitems_id' => $id,
            'users_id'         => $user_id,
        ];

        if ($value) {
            $this->add(KnowbaseItem_Favorite::class, $criteria);
        } else {
            $favorite = new KnowbaseItem_Favorite();
            if ($favorite->getFromDBByCrit($criteria)) {
                $this->purge(KnowbaseItem_Favorite::class, $favorite->getID());
            }
        }

        return new Response(); // OK
    }
}
