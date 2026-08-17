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

/**
 * Parents an article may legally be moved under: everything the current user
 * can see, minus the article itself and its descendants, which would close a
 * cycle. Labels carry the full path so the flat dropdown still reads as a tree.
 */
final class MoveCandidates
{
    public function __construct(private readonly int $article_id) {}

    /** @return array<int, string> id => label, keyed for Dropdown::showFromArray */
    public function build(): array
    {
        $tree      = (new Builder())->buildTree();
        $forbidden = $this->forbiddenIds($this->adjacency($tree));

        $candidates = [0 => __('Root level')];

        // Level order, so a multi-parent article keeps its shortest path.
        $queue = [];
        foreach ($tree->getArticles() as $article) {
            $queue[] = [$article, ''];
        }
        for ($i = 0; $i < count($queue); $i++) {
            [$article, $prefix] = $queue[$i];
            // A forbidden article's whole subtree is forbidden too.
            if (isset($forbidden[$article->id])) {
                continue;
            }

            $label = $prefix === '' ? $article->title : $prefix . ' > ' . $article->title;
            $candidates[$article->id] ??= $label;

            foreach ($article->getChildren() as $child) {
                $queue[] = [$child, $label];
            }
        }

        return $candidates;
    }

    /**
     * Parent id => child ids, unioned over every occurrence: the same article
     * may be rendered under several parents, and a cycle closes through any of
     * them.
     *
     * @return array<int, array<int, true>>
     */
    private function adjacency(Tree $tree): array
    {
        $children_of = [];
        $stack       = $tree->getArticles();
        while ($stack !== []) {
            $article = array_pop($stack);
            foreach ($article->getChildren() as $child) {
                $children_of[$article->id][$child->id] = true;
                $stack[] = $child;
            }
        }
        return $children_of;
    }

    /**
     * @param array<int, array<int, true>> $children_of
     * @return array<int, true>
     */
    private function forbiddenIds(array $children_of): array
    {
        $forbidden = [$this->article_id => true];
        $queue     = [$this->article_id];
        for ($i = 0; $i < count($queue); $i++) {
            foreach (array_keys($children_of[$queue[$i]] ?? []) as $child_id) {
                if (isset($forbidden[$child_id])) {
                    continue;
                }
                $forbidden[$child_id] = true;
                $queue[] = $child_id;
            }
        }
        return $forbidden;
    }
}
