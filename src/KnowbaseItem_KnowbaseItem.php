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

/**
 * A link between two KB articles (parent/child).
 */
final class KnowbaseItem_KnowbaseItem extends CommonDBRelation
{
    // From CommonDBRelation
    public static ?string $itemtype_1 = KnowbaseItem::class;
    public static ?string $items_id_1 = 'knowbaseitems_id';
    public static ?string $itemtype_2 = KnowbaseItem::class;
    public static ?string $items_id_2 = 'knowbaseitems_id_parent';
    public static bool $checkAlwaysBothItems = true;

    // From CommonDBTM
    public bool $dohistory = true;
    public static string $rightname = 'knowbase';

    /** Set while an article's purge removes its links, see `cleanDBonItemDelete()`. */
    private bool $is_cleaning_item_deletion = false;

    public function prepareInputForAdd($input)
    {
        if (!$this->prepareInput($input, is_update: false)) {
            return false;
        }
        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        if (!$this->prepareInput($input, is_update: true)) {
            return false;
        }
        return parent::prepareInputForUpdate($input);
    }

    public function pre_deleteItem()
    {
        if (!$this->is_cleaning_item_deletion && Session::getLoginUserID() !== false) {
            $parent = KnowbaseItem::getById((int) $this->fields['knowbaseitems_id_parent']);
            if ($parent !== false && !self::canDetach($parent)) {
                self::addRefusalMessage();
                return false;
            }
        }
        return parent::pre_deleteItem();
    }

    public function cleanDBonItemDelete($itemtype, $items_id)
    {
        // Purging an article unlinks it, whatever the rights on the other side.
        $this->is_cleaning_item_deletion = true;
        try {
            parent::cleanDBonItemDelete($itemtype, $items_id);
        } finally {
            $this->is_cleaning_item_deletion = false;
        }
    }

    /**
     * @param array<string, mixed> $input
     * @param bool $is_update Whether the loaded link is updated, its fields are
     *                        stale otherwise (the object may be reused).
     */
    private function prepareInput(array $input, bool $is_update): bool
    {
        // Target articles should be specified
        $child = $this->getAndValidateArticle($input, 'knowbaseitems_id', $is_update);
        $parent = $this->getAndValidateArticle($input, 'knowbaseitems_id_parent', $is_update);
        if ($child === null || $parent === null) {
            return false;
        }
        $child_id = $child->getID();
        $parent_id = $parent->getID();

        // Parent and child must be different
        if ($child_id === $parent_id) {
            Session::addMessageAfterRedirect(
                __s('An article cannot be its own parent.'),
                false,
                ERROR,
            );
            return false;
        }

        // The root article is the base of the knowledge base tree, it cannot be
        // moved under another article.
        if ($child->isRoot()) {
            Session::addMessageAfterRedirect(
                __s('The root article of the knowledge base cannot have a parent.'),
                false,
                ERROR,
            );
            return false;
        }

        // Reject if $child is an ancestor of $parent
        if (self::isAncestor($child_id, $parent_id)) {
            Session::addMessageAfterRedirect(
                __s('This link would create a cycle in the knowledge base tree.'),
                false,
                ERROR,
            );
            return false;
        }

        // Internal processes (CLI, cron, migrations) act on behalf of no user.
        if (Session::getLoginUserID() === false) {
            return true;
        }
        $previous_parent = $is_update ? KnowbaseItem::getById((int) $this->fields['knowbaseitems_id_parent']) : false;
        if (
            !self::canAttach($child, $parent)
            || ($previous_parent !== false && !self::canDetach($previous_parent))
        ) {
            self::addRefusalMessage();
            return false;
        }

        return true;
    }

    /** @param array<string, mixed> $input */
    private function getAndValidateArticle(array $input, string $key, bool $is_update): ?KnowbaseItem
    {
        $article_id  = (int) ($input[$key] ?? ($is_update ? $this->fields[$key] : 0));
        if ($article_id == 0) {
            Session::addMessageAfterRedirect(
                msg: htmlescape(sprintf(__("Missing '%s' value."), $key)),
                message_type: ERROR,
            );
            return null;
        }
        $article = KnowbaseItem::getById($article_id);
        if ($article === false) {
            Session::addMessageAfterRedirect(
                msg: htmlescape(sprintf(__("Invalid '%s' value: '%s'."), $key, $article_id)),
                message_type: ERROR,
            );
            return null;
        }

        return $article;
    }

    /**
     * The article and every article below it, walked breadth-first over the raw
     * edges: a visibility-filtered tree promotes an article to root when its
     * parent is invisible, which would hide a genuine descendant.
     *
     * Not visibility-filtered on purpose: subtract these ids, never display them.
     *
     * @return array<int, true> keyed by id, for O(1) membership tests
     */
    public static function getDescendantIds(int $article_id): array
    {
        global $DB;

        $descendants = [$article_id => true];
        $frontier    = [$article_id];
        while ($frontier !== []) {
            $next = [];
            foreach ($DB->request([
                'SELECT' => 'knowbaseitems_id',
                'FROM'   => self::getTable(),
                'WHERE'  => ['knowbaseitems_id_parent' => $frontier],
            ]) as $row) {
                $child_id = (int) $row['knowbaseitems_id'];
                if (isset($descendants[$child_id])) {
                    continue;
                }
                $descendants[$child_id] = true;
                $next[] = $child_id;
            }
            $frontier = $next;
        }
        return $descendants;
    }

    /** True if $parent_id is a direct parent of $child_id. */
    public static function isParentOf(int $parent_id, int $child_id): bool
    {
        if ($parent_id <= 0 || $child_id <= 0) {
            return false;
        }

        return countElementsInTable(self::getTable(), [
            'knowbaseitems_id'        => $child_id,
            'knowbaseitems_id_parent' => $parent_id,
        ]) > 0;
    }

    /** True if $ancestor_id is an ancestor of $node_id. */
    private static function isAncestor(int $ancestor_id, int $node_id): bool
    {
        $seen  = [];
        $queue = [$node_id];
        while ($queue !== []) {
            $current = array_pop($queue);
            if (isset($seen[$current])) {
                continue;
            }
            $seen[$current] = true;
            foreach ((new self())->find(['knowbaseitems_id' => $current]) as $row) {
                $p = (int) $row['knowbaseitems_id_parent'];
                if ($p === $ancestor_id) {
                    return true;
                }
                $queue[] = $p;
            }
        }
        return false;
    }

    /**
     * Gaining a child is editing the parent. The root article is everyone's
     * default parent, see `KnowbaseItem::setRootAsDefaultParent()`.
     *
     * Not `can(UPDATE)`: it also requires the UPDATE right, that authors with
     * CREATE or PUBLISHFAQ only lack.
     */
    public static function canAttach(KnowbaseItem $child, KnowbaseItem $parent): bool
    {
        return $parent->isRoot()
            || ($parent->canUpdateItem() && self::areEntitiesCoherent($child, $parent));
    }

    /** Losing a child is editing the parent too, see `canAttach()`. */
    public static function canDetach(KnowbaseItem $parent): bool
    {
        return $parent->isRoot() || $parent->canUpdateItem();
    }

    public static function addRefusalMessage(): void
    {
        Session::addMessageAfterRedirect(
            __s('You are not allowed to change the parents of this article.'),
            false,
            ERROR,
        );
    }

    /**
     * Same rule as CommonDBRelation::canRelationItem(): entities must match, or the
     * more specific side must be recursive over the other one's entity.
     */
    public static function areEntitiesCoherent(KnowbaseItem $child, KnowbaseItem $parent): bool
    {
        if (!$child->isEntityAssign() || !$parent->isEntityAssign()) {
            return true;
        }

        $child_entity  = $child->getEntityID();
        $parent_entity = $parent->getEntityID();

        if ($child_entity == $parent_entity) {
            return true;
        }
        if ($child->isRecursive() && in_array($child_entity, getAncestorsOf('glpi_entities', $parent_entity))) {
            return true;
        }
        if ($parent->isRecursive() && in_array($parent_entity, getAncestorsOf('glpi_entities', $child_entity))) {
            return true;
        }

        return false;
    }
}
