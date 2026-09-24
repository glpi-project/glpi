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
use KnowbaseItem;
use Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

use function Safe\json_decode;

final class CreateArticleController extends AbstractController
{
    #[Route(
        "/Knowbase/KnowbaseItem/Create",
        name: "knowbase_article_create",
        methods: ["POST"],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new BadRequestHttpException();
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new BadRequestHttpException();
        }

        $parent_id = (int) ($data['knowbaseitems_id_parent'] ?? 0);
        $input = [
            'name'         => $name,
            'answer'       => '',
            'entities_id'  => Session::getActiveEntity(),
            'is_recursive' => 0,
            '_parents'     => $parent_id > 0 ? [$parent_id] : [],
        ];

        $item = new KnowbaseItem();
        if (!$item->can(-1, CREATE, $input)) {
            throw new AccessDeniedHttpException();
        }
        // The model only refuses the parent, see `KnowbaseItem_KnowbaseItem::canAttach()`,
        // with a redirect flash message: this endpoint never redirects, so it is dropped.
        $errors_before = $_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR] ?? null;
        if (!$item->add($input)) {
            if ($errors_before === null) {
                unset($_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR]);
            } else {
                $_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR] = $errors_before;
            }
            throw new AccessDeniedHttpException();
        }

        return new JsonResponse([
            'id'  => (int) $item->getID(),
            'url' => KnowbaseItem::getFormURLWithID($item->getID()),
        ]);
    }
}
