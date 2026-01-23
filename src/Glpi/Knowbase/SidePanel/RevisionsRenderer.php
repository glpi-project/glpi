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

namespace Glpi\Knowbase\SidePanel;

use DBmysql;
use KnowbaseItem;
use Override;
use User;

final class RevisionsRenderer implements RendererInterface
{
    #[Override]
    public function canView(KnowbaseItem $item): bool
    {
        return $item->canUpdateItem();
    }

    #[Override]
    public function getTemplate(): string
    {
        return "pages/tools/kb/sidepanel/revisions.html.twig";
    }

    #[Override]
    public function getParams(KnowbaseItem $item): array
    {
        /** @var DBmysql $DB */
        global $DB;

        $kb_id = $item->getID();

        // Cache for User objects
        $user_cache = [];

        // Build revisions list
        $revisions = [];

        // First, add the current version from KnowbaseItem fields
        $current_user_id = (int) $item->fields['users_id'];
        $user_cache[$current_user_id] = User::getById($current_user_id) ?: null;

        $revisions[] = [
            'id' => null,
            'revision' => null,
            'user' => $user_cache[$current_user_id],
            'date' => $item->fields['date_mod'],
            'is_current' => true,
            'is_first' => false,
        ];

        // Fetch revisions from database (for base article, language = '')
        $result = $DB->request([
            'SELECT' => [
                'id',
                'revision',
                'users_id',
                'date',
            ],
            'FROM' => 'glpi_knowbaseitems_revisions',
            'WHERE' => [
                'knowbaseitems_id' => $kb_id,
                'language' => '',
            ],
            'ORDER' => ['revision DESC'],
        ]);

        $total_revisions = count($result);
        $index = 0;

        foreach ($result as $row) {
            $user_id = $row['users_id'];
            if (!isset($user_cache[$user_id])) {
                $user_cache[$user_id] = User::getById($user_id) ?: null;
            }

            $index++;
            $revisions[] = [
                'id' => $row['id'],
                'revision' => $row['revision'],
                'user' => $user_cache[$user_id],
                'date' => $row['date'],
                'is_current' => false,
                'is_first' => ($index === $total_revisions),
            ];
        }

        return [
            'id' => $kb_id,
            'revisions' => $revisions,
            'can_revert' => $item->canUpdateItem(),
        ];
    }
}
