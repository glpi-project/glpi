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

namespace Glpi\Knowbase\Aside;

use KnowbaseItem;
use KnowbaseItem_KnowbaseItem;

/**
 * Builds the aside article tree from the set of articles the current user
 * may see.
 *
 * The hierarchy is NOT a naive `knowbaseitems_id_parent` walk: an article
 * visible to the user may have all of its parents invisible to them (e.g. a
 * closed-by-default migrated category). Such an article must still surface
 * somewhere in the tree, so it is "promoted" to the root level whenever none
 * of its parents are themselves visible. An article with several visible
 * parents appears under each of them.
 */
final class Builder
{
    public const array LIST_COLUMNS = [
        'glpi_knowbaseitems.id',
        'glpi_knowbaseitems.name',
        'glpi_knowbaseitems.illustration',
    ];

    /** @var array<int, true> */
    private array $folded_ids_lookup_map = [];

    /**
     * Articles to render, when the tree is restricted to a subset (search).
     * Null renders the whole tree.
     *
     * @var array<int, true>|null
     */
    private ?array $rendered_ids = null;

    public function __construct(private readonly int $current_id = 0) {}

    public function buildTree(): Tree
    {
        return $this->build(null);
    }

    /**
     * Tree restricted to the given articles and to the branches leading to
     * them, as used by the aside search.
     *
     * Ancestors are included so a match is never orphaned, and nothing is
     * collapsed: a match has to be visible without the user unfolding its
     * ancestors first. Descendants of a match are left out unless they match
     * too, which is what the search filter it replaces did.
     *
     * @param int[] $matching_ids
     */
    public function buildSearchTree(array $matching_ids): Tree
    {
        return $this->build(array_fill_keys(array_map('intval', $matching_ids), true));
    }

    /**
     * @param array<int, true>|null $matching_ids Null builds the whole tree.
     */
    private function build(?array $matching_ids): Tree
    {
        global $DB;

        // Articles the current user has collapsed, restored on each render.
        $this->folded_ids_lookup_map = array_fill_keys(KnowbaseItem::getFoldedIdsForCurrentUser(), true);

        // 1) All articles the current user may see (visibility applied).
        $criteria = KnowbaseItem::getListRequest([], 'browse');
        $criteria['SELECT'] = self::LIST_COLUMNS;
        $rows = $DB->request($criteria);
        $data = [];              // id => row
        foreach ($rows as $row) {
            $data[(int) $row['id']] = $row;
        }
        if ($data === []) {
            return new Tree();
        }
        $visible_ids = array_keys($data);

        // 2) Visible parent -> [visible children] adjacency, and child -> has a visible parent?
        $children_of         = [];     // parent_id => int[] child ids
        $parents_of          = [];     // child_id => int[] parent ids
        $has_visible_parent  = [];     // child_id => true
        foreach ($DB->request(['FROM' => KnowbaseItem_KnowbaseItem::getTable()]) as $link) {
            $child  = (int) $link['knowbaseitems_id'];
            $parent = (int) $link['knowbaseitems_id_parent'];
            if (!isset($data[$child], $data[$parent])) {
                continue; // one of the ends is not visible to the current user
            }
            $children_of[$parent][] = $child;
            $parents_of[$child][] = $parent;
            $has_visible_parent[$child] = true;
        }

        $this->rendered_ids = $matching_ids === null
            ? null
            : $this->withAncestors(array_intersect_key($matching_ids, $data), $parents_of);

        // 3) Roots = visible articles with no visible parent (promote-to-root).
        $tree = new Tree();
        foreach ($visible_ids as $id) {
            if (isset($has_visible_parent[$id]) || !$this->isRendered($id)) {
                continue;
            }
            $tree->addArticle($this->buildArticle($id, $data, $children_of, []));
        }
        return $tree;
    }

    /**
     * @param array<int, array<string, mixed>> $data
     * @param array<int,int[]> $children_of
     * @param array<int,bool>  $ancestors  visited guard (DAG, but defensive)
     */
    private function buildArticle(int $id, array $data, array $children_of, array $ancestors): Article
    {
        $row = $data[$id];
        $article = new Article(
            id: $id,
            title: $row['name'] ?? '',
            illustration: $row['illustration'] ?? '',
            link: KnowbaseItem::getFormURLWithID($id),
            is_current: $this->current_id > 0 && $id === $this->current_id,
            collapsed: $this->rendered_ids === null
                && isset($this->folded_ids_lookup_map[$id]),
        );
        $ancestors[$id] = true;
        foreach ($children_of[$id] ?? [] as $child_id) {
            if (isset($ancestors[$child_id]) || !$this->isRendered($child_id)) {
                continue; // cycles are forbidden by writes; guard defensively
            }
            $article->addChild($this->buildArticle($child_id, $data, $children_of, $ancestors));
        }
        return $article;
    }

    private function isRendered(int $id): bool
    {
        return $this->rendered_ids === null || isset($this->rendered_ids[$id]);
    }

    /**
     * The given articles plus every ancestor leading to them, so a restricted
     * tree stays attached to its roots.
     *
     * @param array<int, true> $ids
     * @param array<int, int[]> $parents_of
     *
     * @return array<int, true>
     */
    private function withAncestors(array $ids, array $parents_of): array
    {
        $kept = [];
        $to_walk = array_keys($ids);
        while ($to_walk !== []) {
            $id = array_pop($to_walk);
            if (isset($kept[$id])) {
                continue;
            }
            $kept[$id] = true;
            foreach ($parents_of[$id] ?? [] as $parent) {
                $to_walk[] = $parent;
            }
        }

        return $kept;
    }
}
