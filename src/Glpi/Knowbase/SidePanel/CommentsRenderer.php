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

use Glpi\Knowbase\CommentsThread;
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
        $threads = KnowbaseItem_Comment::getCommentsThreads($item);

        // Load users
        $users = User::getSeveralFromDBByCrit([
            'id' => $this->extractRequiredUsersIds($threads),
        ]);
        $users = iterator_to_array($users);

        // Transform the user list into an id -> user hash map
        $users = array_combine(
            keys: array_map(fn(User $user) => $user->getID(), $users),
            values: $users,
        );

        return [
            'id'       => $item->getID(),
            'threads'  => $threads,
            'users'    => $users,
        ];
    }

    /**
     * @param CommentsThread[] $threads
     * @return int[]
     */
    private function extractRequiredUsersIds(array $threads): array
    {
        $ids = [];
        foreach ($threads as $thread) {
            foreach ($thread->getComments() as $comment) {
                $ids[] = $comment->fields['users_id'];
            }
        }

        return array_unique($ids);
    }
}
