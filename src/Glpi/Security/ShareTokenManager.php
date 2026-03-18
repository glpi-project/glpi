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

use Glpi\DBAL\QueryFunction;
use Glpi\ShareToken;

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
     * Per-request cache: token string => validation result.
     *
     * Avoids repeated SQL queries when hasSessionAccess() is called
     * multiple times for the same token within a single HTTP request.
     *
     * @var array<string, bool>
     */
    private static array $validation_cache = [];

    /**
     * Validate a token string and return the associated item info.
     *
     * @param string $token The token string
     *
     * @return array{itemtype: class-string<\CommonDBTM>, items_id: int}|null
     */
    public static function validateToken(string $token): ?array
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

        return [
            'itemtype' => $row['itemtype'],
            'items_id' => (int) $row['items_id'],
        ];
    }

    /**
     * Grant read access to an item in the current session.
     *
     * @param class-string<\CommonDBTM> $itemtype The item class name
     * @param int $items_id The item ID
     * @param string $token The token string used to gain access
     */
    public static function grantSessionAccess(string $itemtype, int $items_id, string $token): void
    {
        $_SESSION[self::SESSION_KEY][$itemtype][$items_id] = $token;
    }

    /**
     * Check whether the current session has shared access to an item.
     *
     * Re-validates the stored token against the database on each request
     * (cached per-request via static property) to ensure revoked tokens
     * are denied immediately.
     *
     * @param class-string<\CommonDBTM> $itemtype The item class name
     * @param int $items_id The item ID
     *
     * @return bool
     */
    public static function hasSessionAccess(string $itemtype, int $items_id): bool
    {
        $token = $_SESSION[self::SESSION_KEY][$itemtype][$items_id] ?? null;
        if (!is_string($token) || $token === '') {
            return false;
        }

        if (array_key_exists($token, self::$validation_cache)) {
            return self::$validation_cache[$token];
        }

        $valid = self::validateToken($token) !== null;
        self::$validation_cache[$token] = $valid;

        if (!$valid) {
            unset($_SESSION[self::SESSION_KEY][$itemtype][$items_id]);
        }

        return $valid;
    }

    /**
     * Reset the per-request validation cache.
     *
     * @internal Only for use in test suites where multiple token lifecycle
     *           events happen within the same PHP process.
     */
    public static function resetValidationCache(): void
    {
        self::$validation_cache = [];
    }

    /**
     * Return all items accessible via the current session's shared access grants.
     *
     * Only items whose stored token is still valid (active, not expired) are returned.
     * The returned array is indexed by itemtype, then by items_id.
     *
     * @return array<string, array<int, string>>
     */
    public static function getAccessibleItems(): array
    {
        $items = $_SESSION[self::SESSION_KEY] ?? [];
        $validated = [];

        foreach ($items as $itemtype => $ids) {
            foreach ($ids as $items_id => $token) {
                if (self::hasSessionAccess($itemtype, (int) $items_id)) {
                    $validated[$itemtype][$items_id] = $token;
                }
            }
        }

        return $validated;
    }
}
