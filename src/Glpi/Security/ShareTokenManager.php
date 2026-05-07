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
    private const SESSION_KEY = 'glpi_shared_access';

    /**
     * Grant read access to the item shared by the given token.
     */
    public function grantSessionAccess(string $token): (CommonDBTM&ShareableInterface)|null
    {
        global $DB;

        $result = $DB->request([
            'FROM'  => ShareToken::getTable(),
            'WHERE' => [
                'token'     => $token,
                'is_active' => 1,
                'OR' => [
                    ['date_expiration' => null],
                    ['date_expiration' => ['>', QueryFunction::now()]],
                ],
            ],
            'LIMIT' => 1,
        ]);

        $row = $result->current();
        if ($row === null) {
            return null;
        }

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
            'token'      => $token,
            'expires_at' => \date('Y-m-d H:i:s', strtotime('+5 minutes', strtotime($_SESSION['glpi_currenttime']))),
        ];

        return $item;
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
            || !\array_key_exists('token', $session_data)
            || !\is_string($session_data['token'])
            || !\array_key_exists('expires_at', $session_data)
            || !\is_string($session_data['expires_at'])
        ) {
            return false;
        }

        if (strtotime($session_data['expires_at']) > strtotime($_SESSION['glpi_currenttime'])) {
            return true;
        }

        $access_granted_again = $this->grantSessionAccess($session_data['token']) !== null;

        if (!$access_granted_again) {
            // Revoke access
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
            foreach ($ids as $items_id => $token) {
                if ($this->hasSessionAccess($itemtype, (int) $items_id)) {
                    if (!\array_key_exists($itemtype, $validated)) {
                        $validated[$itemtype] = [];
                    }
                    $validated[$itemtype][] = $items_id;
                }
            }
        }

        return $validated;
    }
}
