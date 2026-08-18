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
use Glpi\Exception\Http\NotFoundHttpException;
use KnowbaseItem;
use KnowbaseItem_KnowbaseItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Moves an article to another parent in the knowledge base aside tree.
 *
 * An article may have several parents and is rendered under each of them, so
 * this endpoint moves a single edge: the link to `from_parent_id` is removed
 * and a link to `to_parent_id` is created. The article's other parents are
 * left alone. `0` means the root level, which carries no link at all.
 */
final class MoveArticleController extends AbstractController
{
    #[Route(
        "/Knowbase/Aside/Article/{id}/Move",
        name: "knowbase_aside_article_move",
        requirements: [
            'id' => '\d+',
        ],
        methods: 'POST',
    )]
    public function __invoke(int $id, Request $request): Response
    {
        global $DB;

        $payload        = $request->getPayload();
        $from_parent_id = $payload->getInt('from_parent_id', -1);
        $to_parent_id   = $payload->getInt('to_parent_id', -1);

        if ($from_parent_id < 0 || $to_parent_id < 0 || $from_parent_id === $to_parent_id) {
            throw new BadRequestHttpException();
        }

        $article = new KnowbaseItem();
        if (!$article->getFromDB($id)) {
            throw new NotFoundHttpException();
        }
        if (!$article->can($id, UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        // KnowbaseItem_KnowbaseItem::$checkAlwaysBothItems only applies when the caller
        // goes through can()/canCreateItem(); add() below never does, so this is the
        // only rights check on the target and must stay.
        if ($to_parent_id > 0) {
            $target = new KnowbaseItem();
            if (!$target->getFromDB($to_parent_id)) {
                throw new NotFoundHttpException();
            }
            if (!$target->can($to_parent_id, READ)) {
                throw new AccessDeniedHttpException();
            }
            if (!KnowbaseItem_KnowbaseItem::areEntitiesCoherent($article, $target)) {
                throw new AccessDeniedHttpException();
            }
        }

        $link = new KnowbaseItem_KnowbaseItem();

        $DB->beginTransaction();

        // Target edge created first: the model's cycle check needs it in place.
        if ($to_parent_id > 0) {
            $already_linked = $link->find([
                'knowbaseitems_id'        => $id,
                'knowbaseitems_id_parent' => $to_parent_id,
            ]) !== [];

            if (
                !$already_linked
                && $link->add([
                    'knowbaseitems_id'        => $id,
                    'knowbaseitems_id_parent' => $to_parent_id,
                ]) === false
            ) {
                $DB->rollBack();
                // The model reports the reason via a redirect flash message, but this
                // endpoint never redirects: drop it so it doesn't leak into the next page.
                unset($_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR]);
                throw new BadRequestHttpException();
            }
        }

        if (
            $from_parent_id > 0
            && !$link->deleteByCriteria([
                'knowbaseitems_id'        => $id,
                'knowbaseitems_id_parent' => $from_parent_id,
            ])
        ) {
            $DB->rollBack();
            throw new BadRequestHttpException();
        }

        $DB->commit();

        return new Response(); // OK
    }
}
