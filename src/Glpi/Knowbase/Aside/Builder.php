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
 * An article attaches to its nearest visible ancestor, or becomes a root.
 * Ties each get a copy.
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

    /** @var array<int, int[]> ancestor_id => visible article ids attached to it */
    private array $children_of = [];

    /** @var array<int, int[]> child_id => nearest visible ancestor ids */
    private array $parents_of = [];

    /** @var array<int, true> Visible articles with no visible ancestor at all */
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

    private bool $hierarchy_loaded = false;

    public function __construct(private readonly int $current_id = 0) {}

    public function buildTree(): Tree
    {
        $this->loadHierarchy();

        return $this->buildTreeWith($this->computeFoldedIds());
    }

    /**
     * The whole visible tree, folding ignored: a consumer that walks the
     * hierarchy instead of rendering it needs every descendant loaded.
     */
    public function buildUnfoldedTree(): Tree
    {
        $this->loadHierarchy();

        return $this->buildTreeWith([]);
    }

    /**
     * @param array<int, true> $folded_ids_lookup_map
     */
    private function buildTreeWith(array $folded_ids_lookup_map): Tree
    {
        $this->folded_ids_lookup_map = $folded_ids_lookup_map;

        $tree = new Tree();
        foreach (array_keys($this->roots) as $id) {
            $tree->addArticle($this->buildArticle($id, []));
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

        // 2) The full parent graph: the walk needs the invisible links too.
        $raw_parents_of = [];
        $link_criteria = [
            'SELECT' => ['knowbaseitems_id', 'knowbaseitems_id_parent'],
            'FROM'   => KnowbaseItem_KnowbaseItem::getTable(),
        ];
        foreach ($DB->request($link_criteria) as $link) {
            $child  = (int) $link['knowbaseitems_id'];
            $parent = (int) $link['knowbaseitems_id_parent'];
            $raw_parents_of[$child][] = $parent;
        }

        // 3) Attach each article to its nearest visible ancestor, or make it a root.
        foreach (array_keys($this->data) as $id) {
            $ancestors = $this->findNearestVisibleAncestors($id, $raw_parents_of);
            if ($ancestors === []) {
                $this->roots[$id] = true;
                continue;
            }
            foreach ($ancestors as $ancestor_id) {
                $this->children_of[$ancestor_id][] = $id;
                $this->parents_of[$id][] = $ancestor_id;
            }
        }

        // 4) A cycle reaches no root: promote what the walk left out.
        $reached = [];
        self::walk(array_keys($this->roots), $this->children_of, $reached);
        foreach (array_keys($this->data) as $id) {
            if (isset($reached[$id])) {
                continue;
            }
            $this->roots[$id] = true;
            self::walk([$id], $this->children_of, $reached);
        }
    }

    /**
     * The visible ancestors of `$id` with the fewest hops up, ties included.
     *
     * @param array<int, int[]> $raw_parents_of child_id => every parent id, visible or not
     *
     * @return int[]
     */
    private function findNearestVisibleAncestors(int $id, array $raw_parents_of): array
    {
        $visited  = [$id => true]; // a cycle must not make it its own ancestor
        $frontier = [$id];

        while ($frontier !== []) {
            $next_frontier = [];
            $visible       = [];
            foreach ($frontier as $current) {
                foreach ($raw_parents_of[$current] ?? [] as $parent_id) {
                    if (isset($visited[$parent_id])) {
                        continue;
                    }
                    $visited[$parent_id] = true;
                    if (isset($this->data[$parent_id])) {
                        $visible[] = $parent_id;
                    } else {
                        $next_frontier[] = $parent_id;
                    }
                }
            }
            if ($visible !== []) {
                return $visible;
            }
            $frontier = $next_frontier;
        }

        return [];
    }

    /**
     * Every id reachable from `$starts` through `$adjacency`, `$starts` included.
     *
     * @param int[] $starts
     * @param array<int, int[]> $adjacency
     * @param array<int, true> $reached
     */
    private static function walk(array $starts, array $adjacency, array &$reached): void
    {
        $stack = $starts;
        while ($stack !== []) {
            $id = array_pop($stack);
            if (isset($reached[$id])) {
                continue;
            }
            $reached[$id] = true;
            foreach ($adjacency[$id] ?? [] as $next_id) {
                $stack[] = $next_id;
            }
        }
    }

    /**
     * @param array<int, true> $ancestors Visited guard (DAG, but defensive)
     */
    private function buildArticle(int $id, array $ancestors): Article
    {
        $row = $this->data[$id];
        $folded = isset($this->folded_ids_lookup_map[$id]);

        $ancestors[$id] = true;
        $children = [];
        foreach ($this->children_of[$id] ?? [] as $child_id) {
            if (isset($ancestors[$child_id])) {
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

    /**
     * The given articles plus every ancestor leading to them.
     *
     * @param array<int, true> $ids
     *
     * @return array<int, true>
     */
    private function withAncestors(array $ids): array
    {
        $kept = [];
        self::walk(array_keys($ids), $this->parents_of, $kept);

        return $kept;
    }
}
