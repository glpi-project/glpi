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

use KnowbaseItem;
use KnowbaseItem_Comment;
use Override;
use User;

final class CommentsRenderer implements RendererInterface
{
    #[Override]
    public function canView(KnowbaseItem $item): bool
    {
        return $item->canComment();
    }

    #[Override]
    public function getTemplate(): string
    {
        return "pages/tools/kb/sidepanel/comments.html.twig";
    }

    #[Override]
    public function getParams(KnowbaseItem $item): array
    {
        $comments = KnowbaseItem_Comment::getCommentsForKbItem($item);

        // Load users
        $users = User::getSeveralFromDBByCrit([
            'id' => $this->extractRequiredUsersIds($comments),
        ]);
        $users = iterator_to_array($users);
        $users = array_combine(
            keys: array_map(fn(User $user) => $user->getID(), $users),
            values: $users,
        );

        return [
            'id'       => $item->getID(),
            'comments' => $comments,
            'users'    => $users,
        ];
    }

    /**
     * @param KnowbaseItem_Comment[] $comments
     * @return int[]
     */
    private function extractRequiredUsersIds(array $comments): array
    {
        $ids = $this->doExtractRequiredUsersIds($comments);
        return array_unique($ids);
    }

    /**
     * @param KnowbaseItem_Comment[] $comments
     * @return int[]
     */
    private function doExtractRequiredUsersIds(array $comments): array
    {
        $ids = [];

        foreach ($comments as $comment) {
            $ids[] = $comment->fields['users_id'];

            $children = $comment->fields['_answers'];
            array_push($ids, ...$this->doExtractRequiredUsersIds($children));
        }

        return $ids;
    }
}
