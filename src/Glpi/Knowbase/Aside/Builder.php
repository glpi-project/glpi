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

    /** @var array<int, array<string, mixed>> Visible articles, id => row */
    private array $data = [];

    /** @var array<int, int[]> parent_id => visible child ids */
    private array $children_of = [];

    /** @var array<int, int[]> child_id => visible parent ids */
    private array $parents_of = [];

    /** @var array<int, true> Visible articles with no visible parent */
    private array $roots = [];

    /**
     * Articles that render folded, as a lookup map.
     *
     * The knowledge base is folded by default.
     * An article is unfolded when it is a root (the entry point of the tree),
     * when the user unfolded it, or when it leads to the article being read.
     *
     * @var array<int, true>
     */
    private array $folded_ids_lookup_map = [];

    /**
     * Articles to render, when the tree is restricted to a subset (search).
     * Null renders the whole tree.
     *
     * @var array<int, true>|null
     */
    private ?array $rendered_ids = null;

    private bool $hierarchy_loaded = false;

    public function __construct(private readonly int $current_id = 0) {}

    public function buildTree(): Tree
    {
        $this->loadHierarchy();
        $this->rendered_ids = null;
        $this->folded_ids_lookup_map = $this->computeFoldedIds();

        $tree = new Tree();
        foreach (array_keys($this->roots) as $id) {
            $tree->addArticle($this->buildArticle($id, []));
        }

        return $tree;
    }

    /**
     * Tree restricted to the given articles and to the branches leading to
     * them, as used by the aside search.
     *
     * Ancestors are included so a match is never orphaned, and nothing is
     * folded: a match has to be visible without the reader unfolding its
     * ancestors first. Descendants of a match are left out unless they match
     * too, which is what the search filter it replaces did.
     *
     * @param int[] $matching_ids
     */
    public function buildSearchTree(array $matching_ids): Tree
    {
        $this->loadHierarchy();
        $this->rendered_ids = $this->withAncestors(array_intersect_key(
            array_fill_keys(array_map('intval', $matching_ids), true),
            $this->data
        ));
        $this->folded_ids_lookup_map = [];

        $tree = new Tree();
        foreach (array_keys($this->roots) as $id) {
            if ($this->isRendered($id)) {
                $tree->addArticle($this->buildArticle($id, []));
            }
        }

        return $tree;
    }

    /**
     * Children of a single article, as the aside fetches them when the reader
     * unfolds it. Empty when the article is not visible to the current user.
     *
     * @return Article[]
     */
    public function buildChildren(int $parent_id): array
    {
        $this->loadHierarchy();
        if (!isset($this->data[$parent_id])) {
            return [];
        }
        $this->rendered_ids = null;
        $this->folded_ids_lookup_map = $this->computeFoldedIds();

        $children = [];
        foreach ($this->children_of[$parent_id] ?? [] as $child_id) {
            $children[] = $this->buildArticle($child_id, [$parent_id => true]);
        }

        return $children;
    }

    /**
     * Load the visible articles and the hierarchy between them, once.
     */
    private function loadHierarchy(): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        if ($this->hierarchy_loaded) {
            return;
        }
        $this->hierarchy_loaded = true;

        // 1) All articles the current user may see (visibility applied).
        $criteria = KnowbaseItem::getListRequest([], 'browse');
        $criteria['SELECT'] = self::LIST_COLUMNS;
        foreach ($DB->request($criteria) as $row) {
            $this->data[(int) $row['id']] = $row;
        }
        if ($this->data === []) {
            return;
        }

        // 2) Visible parent-> [visible children] adjacency, and the reverse.
        $has_visible_parent = [];
        foreach ($DB->request(['FROM' => KnowbaseItem_KnowbaseItem::getTable()]) as $link) {
            $child  = (int) $link['knowbaseitems_id'];
            $parent = (int) $link['knowbaseitems_id_parent'];
            if (!isset($this->data[$child], $this->data[$parent])) {
                continue; // one of the ends is not visible to the current user
            }
            $this->children_of[$parent][] = $child;
            $this->parents_of[$child][] = $parent;
            $has_visible_parent[$child] = true;
        }

        // 3) Roots = visible articles with no visible parent (promote-to-root).
        foreach (array_keys($this->data) as $id) {
            if (!isset($has_visible_parent[$id])) {
                $this->roots[$id] = true;
            }
        }
    }

    /**
     * @param array<int, true> $ancestors Visited guard (DAG, but defensive)
     */
    private function buildArticle(int $id, array $ancestors): Article
    {
        $row = $this->data[$id];
        $folded = $this->rendered_ids === null && isset($this->folded_ids_lookup_map[$id]);

        $ancestors[$id] = true;
        $children = [];
        foreach ($this->children_of[$id] ?? [] as $child_id) {
            if (isset($ancestors[$child_id]) || !$this->isRendered($child_id)) {
                continue; // cycles are forbidden by writes; guard defensively
            }
            $children[] = $child_id;
        }

        $article = new Article(
            id: $id,
            title: $row['name'] ?? '',
            illustration: $row['illustration'] ?? '',
            link: KnowbaseItem::getFormURLWithID($id),
            is_current: $this->current_id > 0 && $id === $this->current_id,
            collapsed: $folded,
            has_children: $children !== [],
            children_loaded: !$folded,
        );

        if (!$folded) {
            foreach ($children as $child_id) {
                $article->addChild($this->buildArticle($child_id, $ancestors));
            }
        }

        return $article;
    }

    /**
     * Resolve the fold state of every visible article, see
     * `$folded_ids_lookup_map`.
     *
     * @return array<int, true>
     */
    private function computeFoldedIds(): array
    {
        $unfolded = array_fill_keys(KnowbaseItem::getUnfoldedIdsForCurrentUser(), true);

        // The branch leading to the article being read is always unfolded, so
        // the reader can see where they are. It is not persisted: reading an
        // article is not the same as opening a branch for good.
        $on_current_branch = $this->current_id > 0
            ? $this->withAncestors([$this->current_id => true])
            : [];

        $folded = [];
        foreach (array_keys($this->data) as $id) {
            if (isset($this->roots[$id]) || isset($unfolded[$id]) || isset($on_current_branch[$id])) {
                continue;
            }
            $folded[$id] = true;
        }

        return $folded;
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
     *
     * @return array<int, true>
     */
    private function withAncestors(array $ids): array
    {
        $kept = [];
        $to_walk = array_keys($ids);
        while ($to_walk !== []) {
            $id = array_pop($to_walk);
            if (isset($kept[$id])) {
                continue;
            }
            $kept[$id] = true;
            foreach ($this->parents_of[$id] ?? [] as $parent) {
                $to_walk[] = $parent;
            }
        }

        return $kept;
    }
}
