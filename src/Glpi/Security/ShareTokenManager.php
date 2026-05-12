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

namespace Glpi\Security;

use CommonDBTM;
use Glpi\DBAL\QueryFunction;
use Glpi\ShareableInterface;
use Glpi\ShareToken;

use function Safe\strtotime;

/**
 * Service responsible for share token validation and session-based access grants.
 *
 * Only this class should be aware of the session variable structure used to
 * store shared access grants.
 */
final class ShareTokenManager
{
    /**
     * Session key used to store shared access grants.
     */
    public const SESSION_KEY = 'glpi_shared_access';

    /**
     * Grant read access to the item shared by the given token.
     */
    public function grantSessionAccess(string $token): (CommonDBTM&ShareableInterface)|null
    {
        $row = $this->findValidTokenRowByPlaintext($token);
        if ($row === null) {
            return null;
        }

        return $this->openSessionAccessFromRow($row);
    }

    /**
     * Check whether the current session has shared access to an item.
     *
     * @param class-string<CommonDBTM> $itemtype The item class name
     * @param int $items_id The item ID
     *
     * @return bool
     */
    public function hasSessionAccess(string $itemtype, int $items_id): bool
    {
        $session_data = $_SESSION[self::SESSION_KEY][$itemtype][$items_id] ?? null;
        if (
            !\is_array($session_data)
            || !\array_key_exists('sharetoken_id', $session_data)
            || !\is_int($session_data['sharetoken_id'])
            || !\array_key_exists('expires_at', $session_data)
            || !\is_string($session_data['expires_at'])
        ) {
            return false;
        }

        if (strtotime($session_data['expires_at']) > strtotime($_SESSION['glpi_currenttime'])) {
            return true;
        }

        // TTL expired: revalidate by indexed id lookup. No decrypt needed.
        $row = $this->findValidTokenRowById($session_data['sharetoken_id']);
        if ($row === null || $this->openSessionAccessFromRow($row) === null) {
            unset($_SESSION[self::SESSION_KEY][$itemtype][$items_id]);
            return false;
        }

        return true;
    }

    /**
     * Return all items accessible via the current session's shared access grants.
     *
     * The returned array is indexed by itemtype.
     *
     * @return array<class-string<CommonDBTM>, list<int>>
     */
    public function getAccessibleItems(): array
    {
        $items = $_SESSION[self::SESSION_KEY] ?? [];
        $validated = [];

        foreach ($items as $itemtype => $ids) {
            foreach (\array_keys($ids) as $items_id) {
                $items_id = (int) $items_id;
                if ($this->hasSessionAccess($itemtype, $items_id)) {
                    if (!\array_key_exists($itemtype, $validated)) {
                        $validated[$itemtype] = [];
                    }
                    $validated[$itemtype][] = $items_id;
                }
            }
        }

        return $validated;
    }

    /**
     * Find an active, non-expired token row matching the given plaintext.
     *
     * Lookup is filtered by `token_hint` (deterministic SHA-256 truncation of
     * the plaintext) so the DB returns at most a handful of candidates — usually
     * one. Each candidate is decrypted and compared in constant time.
     *
     * @return array<string, mixed>|null
     */
    private function findValidTokenRowByPlaintext(string $plain): ?array
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['id', 'token', 'itemtype', 'items_id'],
            'FROM'   => ShareToken::getTable(),
            'WHERE'  => [
                'token_hint' => ShareToken::computeTokenHint($plain),
                'is_active'  => 1,
                'OR' => [
                    ['date_expiration' => null],
                    ['date_expiration' => ['>', QueryFunction::now()]],
                ],
            ],
        ]);

        foreach ($iterator as $candidate) {
            if (\hash_equals(ShareToken::decryptToken((string) $candidate['token']), $plain)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Find an active, non-expired token row by its primary key.
     *
     * Used on session refresh to avoid keeping the plaintext token in $_SESSION.
     *
     * @return array<string, mixed>|null
     */
    private function findValidTokenRowById(int $id): ?array
    {
        global $DB;

        return $DB->request([
            'SELECT' => ['id', 'itemtype', 'items_id'],
            'FROM'   => ShareToken::getTable(),
            'WHERE'  => [
                'id'        => $id,
                'is_active' => 1,
                'OR' => [
                    ['date_expiration' => null],
                    ['date_expiration' => ['>', QueryFunction::now()]],
                ],
            ],
            'LIMIT' => 1,
        ])->current();
    }

    /**
     * Validate the underlying item and record the access in $_SESSION.
     *
     * @param array<string, mixed> $row Token row containing at least `id`, `itemtype`, `items_id`.
     */
    private function openSessionAccessFromRow(array $row): (CommonDBTM&ShareableInterface)|null
    {
        $item = \getItemForItemtype($row['itemtype']);

        if (
            !($item instanceof ShareableInterface)
            || !($item instanceof CommonDBTM)
            || !$item->getFromDB((int) $row['items_id'])
            || (
                $item->maybeDeleted()
                && (!$item->useDeletedToLockIfDynamic() || !$item->isDynamic())
                && $item->isDeleted()
            )
        ) {
            return null;
        }

        // allow access for the next 5 minutes
        $_SESSION[self::SESSION_KEY][$item::class][$item->getID()] = [
            'sharetoken_id' => (int) $row['id'],
            'expires_at'    => \date('Y-m-d H:i:s', strtotime('+5 minutes', strtotime($_SESSION['glpi_currenttime']))),
        ];

        return $item;
    }
}
