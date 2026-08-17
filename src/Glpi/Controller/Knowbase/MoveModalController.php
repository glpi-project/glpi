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
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Knowbase\Aside\MoveCandidates;
use KnowbaseItem;
use KnowbaseItem_KnowbaseItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The "Move article" modal: a dropdown of legal parents, posting to the same
 * endpoint the aside drag uses.
 */
final class MoveModalController extends AbstractController
{
    #[Route(
        "/Knowbase/{id}/MoveModal",
        name: "knowbase_move_modal",
        requirements: [
            'id' => '\d+',
        ],
        methods: 'GET',
    )]
    public function __invoke(int $id, Request $request): Response
    {
        $item = new KnowbaseItem();
        if (!$item->getFromDB($id)) {
            throw new NotFoundHttpException();
        }
        // READ is not enough: this modal exists only to move.
        if (!$item->can($id, UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        $hint       = $request->query->getInt('from_parent_id');
        $candidates = (new MoveCandidates($id))->build();

        return $this->render('pages/tools/kb/modal/move.html.twig', [
            'id'             => $id,
            // Verified, not trusted: kept only if it is a real edge AND an offered candidate.
            'from_parent_id' => KnowbaseItem_KnowbaseItem::isParentOf($hint, $id) && array_key_exists($hint, $candidates)
                ? $hint
                : 0,
            'candidates'     => $candidates,
        ]);
    }
}
