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
 * left alone.
 *
 * `to_parent_id` is always a real article: the root article is the base of the
 * tree, nothing may sit beside it. `from_parent_id` may be `0`, for an article
 * the aside promoted to the root level because none of its parents are visible
 * to the current user: there is then no edge to remove.
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

        if ($from_parent_id < 0 || $to_parent_id <= 0 || $from_parent_id === $to_parent_id) {
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

        // Same reason on the source: deleteByCriteria() checks no rights either, and
        // reports success on zero rows, so an unreadable parent would sever silently.
        if ($from_parent_id > 0) {
            $source = new KnowbaseItem();
            if (!$source->getFromDB($from_parent_id)) {
                throw new NotFoundHttpException();
            }
            if (!$source->can($from_parent_id, READ)) {
                throw new AccessDeniedHttpException();
            }
        }

        $link = new KnowbaseItem_KnowbaseItem();

        $DB->beginTransaction();

        // The page may have been rendered before someone else moved the article, and
        // deleteByCriteria() reports success on zero rows: without this the move would
        // add a parent instead of moving one.
        if ($from_parent_id > 0 && !KnowbaseItem_KnowbaseItem::isParentOf($from_parent_id, $id)) {
            $DB->rollBack();
            throw new BadRequestHttpException();
        }

        $already_linked = $link->find([
            'knowbaseitems_id'        => $id,
            'knowbaseitems_id_parent' => $to_parent_id,
        ]) !== [];

        // Target edge created first: the model's cycle check needs it in place.
        if (!$already_linked) {
            $errors_before = $_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR] ?? null;

            if (
                $link->add([
                    'knowbaseitems_id'        => $id,
                    'knowbaseitems_id_parent' => $to_parent_id,
                ]) === false
            ) {
                $DB->rollBack();
                // The model reports the reason via a redirect flash message, but this
                // endpoint never redirects: put the bucket back as it was, so only the
                // model's own message is dropped.
                if ($errors_before === null) {
                    unset($_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR]);
                } else {
                    $_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR] = $errors_before;
                }
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
