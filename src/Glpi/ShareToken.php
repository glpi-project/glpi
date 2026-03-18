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

    public static string $rightname = '';
    public static array $undisclosedFields = ['token'];

    public static function getTypeName($nb = 0): string
    {
        return _n('Share token', 'Share tokens', $nb);
    }

    public function prepareInputForAdd($input)
    {
        if (empty($input['token'])) {
            $input['token'] = self::generateToken();
        }

        if (!isset($input['users_id'])) {
            $input['users_id'] = Session::getLoginUserID() ?: 0;
        }

        return parent::prepareInputForAdd($input);
    }

    /**
     * Create a new sharing token for an item.
     *
     * Caller is responsible for verifying that the current user has
     * permission to manage sharing on the target item (e.g. UPDATE right).
     *
     * @param class-string<\CommonDBTM> $itemtype The item class name
     * @param int $items_id The item ID
     * @param string|null $name Optional label for the token
     *
     * @return self|false The created ShareToken or false on failure
     */
    public static function createToken(string $itemtype, int $items_id, ?string $name = null): self|false
    {
        $token = new self();
        $id = $token->add([
            'itemtype'  => $itemtype,
            'items_id'  => $items_id,
            'name'      => $name,
            'is_active' => 1,
        ]);

        if ($id === false) {
            return false;
        }

        return $token;
    }

    /**
     * Toggle the active state of a token.
     *
     * Caller is responsible for verifying permissions on the parent item.
     *
     * @param int $token_id The ShareToken ID
     *
     * @return bool
     */
    public static function toggleActive(int $token_id): bool
    {
        $token = new self();
        if (!$token->getFromDB($token_id)) {
            return false;
        }

        return $token->update([
            'id'        => $token_id,
            'is_active' => $token->fields['is_active'] ? 0 : 1,
        ]);
    }

    /**
     * Regenerate the token string for an existing ShareToken.
     *
     * Caller is responsible for verifying permissions on the parent item.
     *
     * @param int $token_id The ShareToken ID
     *
     * @return self|false The updated ShareToken or false on failure
     */
    public static function regenerateToken(int $token_id): self|false
    {
        $token = new self();
        if (!$token->getFromDB($token_id)) {
            return false;
        }

        $success = $token->update([
            'id'    => $token_id,
            'token' => self::generateToken(),
        ]);

        if (!$success) {
            return false;
        }

        return $token;
    }

    /**
     * Get all tokens for a given item.
     *
     * @param class-string<\CommonDBTM> $itemtype The item class name
     * @param int $items_id The item ID
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getTokensForItem(string $itemtype, int $items_id): array
    {
        global $DB;

        $results = [];
        $iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => [
                'itemtype' => $itemtype,
                'items_id' => $items_id,
            ],
            'ORDER' => 'date_creation DESC',
        ]);

        foreach ($iterator as $row) {
            $results[] = $row;
        }

        return $results;
    }

    /**
     * Generate a cryptographically secure random token.
     *
     * @return string A 64-character hex string
     */
    private static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
