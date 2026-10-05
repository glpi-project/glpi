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
use KnowbaseItem;
use KnowbaseItem_KnowbaseItem;
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
        if (!is_array($data)) {
            throw new BadRequestHttpException();
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new BadRequestHttpException();
        }

        $raw_parent_id = (int) ($data['knowbaseitems_id_parent'] ?? 0);
        $parent_id = KnowbaseItem::getReadablePrefilledParentId($raw_parent_id);

        $child = new KnowbaseItem();
        $child->fields = [
            'entities_id'  => Session::getActiveEntity(),
            'is_recursive' => 0,
        ];

        // Same rules as a move in the aside: gaining a child is editing the parent.
        if ($parent_id !== null && !KnowbaseItem::isRootId($parent_id)) {
            $parent = new KnowbaseItem();
            if (!$parent->can($parent_id, UPDATE)) {
                throw new AccessDeniedHttpException();
            }
            // add() below checks that the user can create in that entity.
            if (!KnowbaseItem_KnowbaseItem::areEntitiesCoherent($child, $parent)) {
                $child->fields['entities_id'] = $parent->getEntityID();
            }
        }

        $item = $this->add(KnowbaseItem::class, [
            'name'         => $name,
            'answer'       => '',
            'entities_id'  => $child->fields['entities_id'],
            'is_recursive' => 0,
            '_parents'     => $parent_id !== null ? [$parent_id] : [],
        ]);

        return new JsonResponse([
            'id'  => (int) $item->getID(),
            'url' => KnowbaseItem::getFormURLWithID($item->getID()),
        ]);
    }
}
