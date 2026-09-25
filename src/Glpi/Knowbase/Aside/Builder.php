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
 * An article whose direct parent is invisible attaches to its nearest visible
 * ancestor, or becomes a root if it has none. Ties each get a copy.
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

        // 2) The full child graph: the walk needs the invisible links too.
        $raw_children_of = [];
        foreach ($DB->request(['FROM' => KnowbaseItem_KnowbaseItem::getTable()]) as $link) {
            $child  = (int) $link['knowbaseitems_id'];
            $parent = (int) $link['knowbaseitems_id_parent'];
            $raw_children_of[$parent][] = $child;
        }

        // 3) Attach each article to its nearest visible ancestor, or make it a root.
        $attached = $this->findNearestVisibleAncestors($raw_children_of);
        foreach (array_keys($this->data) as $id) {
            $ancestors = $attached[$id] ?? [];
            unset($ancestors[$id]); // a cycle can make an article its own ancestor
            if ($ancestors === []) {
                $this->roots[$id] = true;
                continue;
            }
            foreach (array_keys($ancestors) as $ancestor_id) {
                $this->children_of[$ancestor_id][] = $id;
                $this->parents_of[$id][] = $ancestor_id;
            }
        }

        // 4) A cycle reaches no root: promote what the walk left out.
        $reached = [];
        foreach (array_keys($this->roots) as $id) {
            $this->markReachable($id, $reached);
        }
        foreach (array_keys($this->data) as $id) {
            if (isset($reached[$id])) {
                continue;
            }
            $this->roots[$id] = true;
            $this->markReachable($id, $reached);
        }
    }

    /**
     * @param array<int, true> $reached
     */
    private function markReachable(int $id, array &$reached): void
    {
        $stack = [$id];
        while ($stack !== []) {
            $current = array_pop($stack);
            if (isset($reached[$current])) {
                continue;
            }
            $reached[$current] = true;
            foreach ($this->children_of[$current] ?? [] as $child_id) {
                $stack[] = $child_id;
            }
        }
    }

    /**
     * For every visible article, the visible ancestors that reach it with the
     * fewest hops down, ties included. Breadth-first from every visible article
     * at once; a visible article stops the walk, a cycle is an article seen twice.
     *
     * @param array<int, int[]> $raw_children_of parent_id => every child id, visible or not
     *
     * @return array<int, array<int, true>> visible article id => ancestor ids
     */
    private function findNearestVisibleAncestors(array $raw_children_of): array
    {
        $frontier = [];
        foreach (array_keys($this->data) as $id) {
            $frontier[$id] = [$id => true]; // seeds itself, to carry its id down
        }
        $walked = array_fill_keys(array_keys($frontier), true); // expand once: the first hop wins

        $attached = [];
        $attached_at = [];
        $hops = 0;
        while ($frontier !== []) {
            $hops++;
            $next = [];
            foreach ($frontier as $id => $ancestors) {
                foreach ($raw_children_of[$id] ?? [] as $child_id) {
                    if (isset($this->data[$child_id])) {
                        $attached_at[$child_id] ??= $hops;
                        if ($attached_at[$child_id] === $hops) {
                            $attached[$child_id] = ($attached[$child_id] ?? []) + $ancestors; // tie
                        }
                        continue;
                    }
                    if (isset($walked[$child_id])) {
                        if (isset($next[$child_id])) {
                            $next[$child_id] += $ancestors; // tie
                        }
                        continue;
                    }
                    $walked[$child_id] = true;
                    $next[$child_id] = $ancestors;
                }
            }
            $frontier = $next;
        }

        return $attached;
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
            is_root: KnowbaseItem::isRootId($id),
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
