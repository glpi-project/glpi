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

namespace Glpi;

use CommonDBChild;
use Glpi\Security\ShareTokenManager;
use GLPIKey;
use Log;
use Session;

/**
 * ShareToken - Generic sharing token system.
 *
 * Stores multiple tokens per item (itemtype + items_id) to allow
 * sharing items via public links. Access is granted through the PHP
 * session so that existing CommonDBTM::can() checks work transparently.
 */
class ShareToken extends CommonDBChild
{
    public static string $itemtype = 'itemtype';
    public static string $items_id = 'items_id';
    public static int $checkParentRights = self::HAVE_SAME_RIGHT_ON_ITEM;

    public static array $undisclosedFields = ['token'];

    private ?bool $was_active_before_purge = null;

    public static function getTypeName($nb = 0): string
    {
        return _n('Share token', 'Share tokens', $nb);
    }

    public function prepareInputForAdd($input)
    {
        // Token cannot be manually defined, it must always be a randomly generated value.
        $manager = new ShareTokenManager();
        $plain = $manager->generateToken();
        $input['token']      = (new GLPIKey())->encrypt($plain);
        $input['token_hint'] = $manager->computeTokenHint($plain);

        if (!isset($input['users_id'])) {
            $input['users_id'] = Session::getLoginUserID() ?: 0;
        }

        return parent::prepareInputForAdd($input);
    }

    public function post_addItem()
    {
        parent::post_addItem();

        if (!empty($this->input['_no_history'])) {
            return;
        }

        if (!$this->isActive()) {
            return;
        }

        if ($this->countOtherActiveTokens() > 0) {
            return;
        }

        $this->logSharingTransition(enabled: true);
    }

    public function post_updateItem($history = true)
    {
        parent::post_updateItem($history);

        if (!empty($this->input['_no_history'])) {
            return;
        }

        if (!in_array('is_active', $this->updates, true)) {
            return;
        }

        $new_active = $this->isActive();
        $other_active = $this->countOtherActiveTokens() > 0;

        if ($new_active && !$other_active) {
            $this->logSharingTransition(enabled: true);
        } elseif (!$new_active && !$other_active) {
            $this->logSharingTransition(enabled: false);
        }
    }

    public function pre_deleteItem()
    {
        $this->was_active_before_purge = $this->isActive();

        return parent::pre_deleteItem();
    }

    public function post_purgeItem()
    {
        parent::post_purgeItem();

        if (!empty($this->input['_no_history'])) {
            return;
        }

        if (!$this->was_active_before_purge) {
            return;
        }

        if ($this->countOtherActiveTokens() > 0) {
            return;
        }

        $this->logSharingTransition(enabled: false);
    }

    /**
     * Count active tokens for the same parent item, excluding the current one.
     */
    private function countOtherActiveTokens(): int
    {
        return (int) countElementsInTable(self::getTable(), [
            'itemtype'  => $this->fields['itemtype'],
            'items_id'  => $this->fields['items_id'],
            'is_active' => 1,
            ['NOT' => ['id' => $this->getID()]],
        ]);
    }

    /**
     * Write a sharing transition entry against the parent item.
     *
     * The transition is encoded with HISTORY_ADD_RELATION (enabled),
     * HISTORY_DEL_RELATION (disabled) or HISTORY_UPDATE_RELATION (regenerated),
     * combined with itemtype_link set to ShareToken::class so HistoryBuilder
     * can pick it up unambiguously.
     *
     * Best-effort transition logging: a race window exists when two concurrent
     * operations both observe countOtherActiveTokens() === 0 before either has
     * committed. Result: duplicate "Sharing enabled" entries on simultaneous
     * first-token creations (or symmetric on last-token deletions). Acceptable
     * for V1; tighten with a transactional SELECT ... FOR UPDATE if needed.
     */
    private function logSharingTransition(bool $enabled): void
    {
        $this->writeSharingLog($enabled ? Log::HISTORY_ADD_RELATION : Log::HISTORY_DEL_RELATION);
    }

    /**
     * Write a single "link regenerated" entry against the parent item.
     *
     * Called explicitly by ShareTokenController::regenerate() once the new
     * token exists, instead of letting the purge+add pair emit the usual
     * disabled/enabled transitions (both suppressed via '_no_history').
     */
    public function logRegeneration(): void
    {
        $this->writeSharingLog(Log::HISTORY_UPDATE_RELATION);
    }

    /**
     * Write a sharing history entry against the parent item for the given linked action.
     */
    private function writeSharingLog(int $linked_action): void
    {
        $parent = $this->getItem(getFromDB: true, getEmpty: false);
        if (!$parent) {
            return;
        }

        Log::history(
            $parent->getID(),
            $parent::class,
            [0, '', $linked_action === Log::HISTORY_DEL_RELATION ? '0' : '1'],
            self::class,
            $linked_action,
        );
    }
}
