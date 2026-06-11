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
use KnowbaseItemCategory;
use Safe\Exceptions\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function Safe\json_decode;

final class ReparentNodeController extends AbstractController
{
    #[Route(
        "/Knowbase/Aside/Reparent",
        name: "knowbase_aside_reparent",
        methods: 'POST',
    )]
    public function __invoke(Request $request): Response
    {
        try {
            $payload = json_decode($request->getContent(), true);
        } catch (JsonException) {
            throw new BadRequestHttpException();
        }
        if (!is_array($payload)) {
            throw new BadRequestHttpException();
        }
        $this->validateInputHasExactKeys($payload, [
            'itemtype',
            'items_id',
            'from_parent_id',
            'to_parent_id',
        ]);

        $itemtype       = (string) $payload['itemtype'];
        $items_id       = (int) $payload['items_id'];
        $from_parent_id = (int) $payload['from_parent_id'];
        $to_parent_id   = (int) $payload['to_parent_id'];

        if ($items_id <= 0 || $from_parent_id < 0 || $to_parent_id < 0) {
            throw new BadRequestHttpException();
        }

        // The target category, if not the synthetic root, must exist and be
        // visible to the caller's active entity scope. canViewItem() enforces
        // the entity restriction that getFromDB() alone would skip — without
        // it, a user could drop items into a category from a foreign entity.
        if ($to_parent_id > 0) {
            $target_category = new KnowbaseItemCategory();
            if (!$target_category->getFromDB($to_parent_id)) {
                throw new NotFoundHttpException();
            }
            if (!$target_category->canViewItem()) {
                throw new AccessDeniedHttpException();
            }
        }

        return match ($itemtype) {
            KnowbaseItem::class         => $this->moveArticle($items_id, $from_parent_id, $to_parent_id),
            KnowbaseItemCategory::class => $this->moveCategory($items_id, $to_parent_id),
            default                     => throw new BadRequestHttpException(),
        };
    }

    /**
     * Move a KB article between categories by routing through
     * `KnowbaseItem::update(['_categories' => …])` — the same path the form
     * uses. Going through update() ensures that `post_updateItem` runs (which
     * in turn invokes `update1NTableData` on the junction, raises the
     * `update` notification event, fires `pre_item_update` plugin hook, and
     * emits the `update` webhook).
     *
     * The same article can belong to multiple categories, so we compute the
     * new set as `(current - from_parent_id) ∪ to_parent_id`. A parent_id of
     * 0 represents the synthetic "Uncategorized" bucket — the absence of any
     * junction row for the article.
     */
    private function moveArticle(int $article_id, int $from_parent_id, int $to_parent_id): Response
    {
        $article = new KnowbaseItem();
        if (!$article->getFromDB($article_id)) {
            throw new NotFoundHttpException();
        }
        if (!$article->can($article_id, UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        if ($from_parent_id === $to_parent_id) {
            // No-op (drop on same category).
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        // `post_getFromDB()` has already populated $article->fields['_categories']
        // via `load1NTableData()` (see KnowbaseItem::post_getFromDB).
        $current_ids = array_map('intval', $article->fields['_categories'] ?? []);

        $new_ids = array_values(array_diff($current_ids, [$from_parent_id]));
        if ($to_parent_id > 0 && !in_array($to_parent_id, $new_ids, true)) {
            $new_ids[] = $to_parent_id;
        }

        // `__categories_defined = 1` is required so that `update1NTableData`
        // treats an empty value as "clear all" rather than "untouched" (see
        // CommonDBTM::update1NTableData and the dropdownField macro convention).
        $ok = $article->update([
            'id'                   => $article_id,
            '_categories'          => $new_ids === [] ? '' : $new_ids,
            '__categories_defined' => 1,
        ]);
        if (!$ok) {
            return new Response('', Response::HTTP_CONFLICT);
        }

        // `update1NTableData` surfaces junction-mutation failures via
        // `trigger_error(E_USER_WARNING)` only — `$article->update()` still
        // returns true. Re-fetch and verify the *intended delta* applied:
        // the removed category must no longer link, the added one must
        // link. We don't compare the full set because external category
        // additions (concurrent edits, side-effects) are not this drag's
        // concern — we only verify what the drag itself attempted to do.
        $article->getFromDB($article_id);
        $current_after = array_map('intval', $article->fields['_categories'] ?? []);

        $removal_pending = $from_parent_id > 0 && in_array($from_parent_id, $current_after, true);
        $addition_pending = $to_parent_id > 0 && !in_array($to_parent_id, $current_after, true);

        if ($removal_pending || $addition_pending) {
            trigger_error(
                sprintf(
                    'ReparentNodeController: article %d reparent delta failed (from=%d, to=%d, observed=[%s]).',
                    $article_id,
                    $from_parent_id,
                    $to_parent_id,
                    implode(',', $current_after),
                ),
                E_USER_WARNING,
            );
            return new Response('', Response::HTTP_CONFLICT);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * Move a KB category to a new parent category. Cycle prevention is handled
     * by CommonTreeDropdown::prepareInputForUpdate which returns false (and
     * therefore makes update() return false) when the target is a descendant
     * of the moved category.
     */
    private function moveCategory(int $category_id, int $to_parent_id): Response
    {
        if ($category_id === $to_parent_id) {
            throw new BadRequestHttpException();
        }

        $category = new KnowbaseItemCategory();
        if (!$category->getFromDB($category_id)) {
            throw new NotFoundHttpException();
        }

        $input = [
            'id'                        => $category_id,
            'knowbaseitemcategories_id' => $to_parent_id,
        ];
        if (!$category->can($category_id, UPDATE, $input)) {
            throw new AccessDeniedHttpException();
        }

        if (!$category->update($input)) {
            return new Response('', Response::HTTP_CONFLICT);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
