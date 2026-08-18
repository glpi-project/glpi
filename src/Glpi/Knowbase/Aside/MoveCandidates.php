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
 * Parents an article may legally be moved under: everything the current user
 * can see, minus the article itself and its descendants (which would close a
 * cycle), minus parents the move would refuse on entity coherence. Labels
 * carry the full path so the flat dropdown still reads as a tree.
 */
final class MoveCandidates
{
    public function __construct(private readonly int $article_id) {}

    /** @return array<int, string> id => label, keyed for Dropdown::showFromArray */
    public function build(): array
    {
        $tree      = (new Builder())->buildTree();
        $forbidden = $this->forbiddenIds();

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

        return $this->dropIncoherentEntities($candidates);
    }

    /**
     * Real ancestry, walked breadth-first over the unfiltered relation table: the
     * visible tree promotes an article to root when its parent is invisible, which
     * would otherwise hide a genuine descendant from this forbidden set.
     *
     * @return array<int, true>
     */
    private function forbiddenIds(): array
    {
        global $DB;

        $forbidden = [$this->article_id => true];
        $frontier  = [$this->article_id];
        while ($frontier !== []) {
            $next = [];
            foreach ($DB->request([
                'SELECT' => 'knowbaseitems_id',
                'FROM'   => KnowbaseItem_KnowbaseItem::getTable(),
                'WHERE'  => ['knowbaseitems_id_parent' => $frontier],
            ]) as $row) {
                $child_id = (int) $row['knowbaseitems_id'];
                if (isset($forbidden[$child_id])) {
                    continue;
                }
                $forbidden[$child_id] = true;
                $next[] = $child_id;
            }
            $frontier = $next;
        }
        return $forbidden;
    }

    /**
     * @param array<int, string> $candidates
     * @return array<int, string>
     */
    private function dropIncoherentEntities(array $candidates): array
    {
        global $DB;

        $article = new KnowbaseItem();
        if (!$article->getFromDB($this->article_id)) {
            return $candidates;
        }

        // KB sharing (glpi_entities_knowbaseitems) is independent of entities_id/is_recursive coherence.
        $ids = array_filter(array_keys($candidates), static fn (int $id): bool => $id !== 0);
        if ($ids === []) {
            return $candidates;
        }

        $rows = $DB->request([
            'SELECT' => ['id', 'entities_id', 'is_recursive'],
            'FROM'   => KnowbaseItem::getTable(),
            'WHERE'  => ['id' => $ids],
        ]);
        foreach ($rows as $row) {
            $parent = new KnowbaseItem();
            $parent->getFromResultSet($row);
            if (!KnowbaseItem_KnowbaseItem::areEntitiesCoherent($article, $parent)) {
                unset($candidates[(int) $row['id']]);
            }
        }

        return $candidates;
    }
}
