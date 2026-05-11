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

    public static array $undisclosedFields = ['token'];

    public static function getTypeName($nb = 0): string
    {
        return _n('Share token', 'Share tokens', $nb);
    }

    public function prepareInputForAdd($input)
    {
        // Token cannot be manually defined, it must always be a randomly generaed value.
        $input['token'] = $this->generateToken();

        if (!isset($input['users_id'])) {
            $input['users_id'] = Session::getLoginUserID() ?: 0;
        }

        return parent::prepareInputForAdd($input);
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
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
