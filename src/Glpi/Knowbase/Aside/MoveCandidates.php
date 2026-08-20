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
use Session;

/**
 * Parents an article may legally be moved under: everything the current user
 * can see, minus the article itself and its descendants (which would close a
 * cycle), minus parents the move would refuse on entity coherence or on rights.
 * Labels carry the full path so the flat dropdown still reads as a tree.
 *
 * The root level is not a candidate: the root article is the base of the tree
 * and is offered under its own title, like any other parent.
 */
final class MoveCandidates
{
    public function __construct(private readonly int $article_id) {}

    /**
     * Loads the whole visible tree, then queries the relation and article
     * tables: call once per request, never per candidate or in a loop.
     *
     * @return array<int, string> id => label, keyed for Dropdown::showFromArray
     */
    public function build(): array
    {
        $tree      = (new Builder())->buildTree();
        $forbidden = KnowbaseItem_KnowbaseItem::getDescendantIds($this->article_id);

        $candidates = [];

        // Level order, so a multi-parent article keeps its shortest path.
        $queue = [];
        foreach ($tree->getArticles() as $article) {
            $queue[] = [$article, ''];
        }
        // Bound stays dynamic: the loop body enqueues the children it discovers.
        for ($i = 0; isset($queue[$i]); $i++) {
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

        return $this->dropRefusedParents($candidates);
    }

    /**
     * Drops what the endpoint would refuse: an incoherent entity, or rights that rule
     * the parent out on their own. The move requires UPDATE on the new parent, and
     * publishing under an FAQ article needs PUBLISHFAQ.
     *
     * Only the part of `canUpdateItem()` that costs nothing here: the visibility half
     * would mean four queries per candidate, see `KnowbaseItem::post_getFromDB()`. The
     * endpoint runs the full check anyway.
     *
     * @param array<int, string> $candidates
     * @return array<int, string>
     */
    private function dropRefusedParents(array $candidates): array
    {
        global $DB;

        $article = new KnowbaseItem();
        if (!$article->getFromDB($this->article_id)) {
            return $candidates;
        }

        // KB sharing (glpi_entities_knowbaseitems) is independent of entities_id/is_recursive coherence.
        $ids = array_keys($candidates);
        if ($ids === []) {
            return $candidates;
        }

        $rows = $DB->request([
            'SELECT' => ['id', 'entities_id', 'is_recursive', 'is_faq', 'users_id'],
            'FROM'   => KnowbaseItem::getTable(),
            'WHERE'  => ['id' => $ids],
        ]);
        foreach ($rows as $row) {
            $parent = new KnowbaseItem();
            $parent->getFromResultSet($row);
            if (
                !KnowbaseItem_KnowbaseItem::areEntitiesCoherent($article, $parent)
                || $this->isRefusedOnRights($parent)
            ) {
                unset($candidates[(int) $row['id']]);
            }
        }

        return $candidates;
    }

    private function isRefusedOnRights(KnowbaseItem $parent): bool
    {
        if (Session::haveRight(KnowbaseItem::$rightname, KnowbaseItem::KNOWBASEADMIN)) {
            return false;
        }
        // Its author edits it whatever the article is, see `KnowbaseItem::canUpdateItem()`.
        if ((int) $parent->fields['users_id'] === Session::getLoginUserID()) {
            return false;
        }

        return (bool) $parent->fields['is_faq']
            && !Session::haveRight(KnowbaseItem::$rightname, KnowbaseItem::PUBLISHFAQ);
    }
}
