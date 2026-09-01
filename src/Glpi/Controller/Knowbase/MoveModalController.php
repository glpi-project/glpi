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
        // The root article is the base of the tree, it cannot be moved.
        if ($item->isRoot()) {
            throw new AccessDeniedHttpException();
        }

        $hint       = $request->query->getInt('from_parent_id');
        $candidates = (new MoveCandidates($id))->build();

        // Verified, not trusted: a real edge. Candidacy qualifies the destination, never
        // the edge to remove, or a refused parent would turn the move into a copy.
        $from_parent_id = KnowbaseItem_KnowbaseItem::isParentOf($hint, $id) ? $hint : 0;

        return $this->render('pages/tools/kb/modal/move.html.twig', [
            'id'             => $id,
            'from_parent_id' => $from_parent_id,
            // The root level is no longer a destination, so the dropdown has to open on a
            // real article: it falls back to the root one when the occurrence is unknown.
            'selected_id'    => $from_parent_id > 0 ? $from_parent_id : $this->fallbackSelection($candidates),
            'candidates'     => $candidates,
        ]);
    }

    /**
     * @param array<int, string> $candidates
     */
    private function fallbackSelection(array $candidates): int
    {
        $root_id = KnowbaseItem::hasRoot() ? KnowbaseItem::getRootId() : 0;
        if (array_key_exists($root_id, $candidates)) {
            return $root_id;
        }

        // Only a corrupted installation, or an article the root cannot host, gets here.
        return (int) (array_key_first($candidates) ?? 0);
    }
}
