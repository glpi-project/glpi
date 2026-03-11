<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

use Glpi\Knowbase\CommentsThread;

/**
 * Class KnowbaseItem_Comment
 * @since 9.2.0
 */
final class KnowbaseItem_Comment extends CommonDBTM
{
    // TODO: extends CommonDBChild and uncomment these lines.
    // Note: doing so seems to break the HL API
    // public static string $itemtype = KnowbaseItem::class;
    // public static string $items_id = 'knowbaseitems_id';

    #[Override]
    public static function getTypeName($nb = 0): string
    {
        return _n('Comment', 'Comments', $nb);
    }

    #[Override]
    public static function getIcon(): string
    {
        return 'ti ti-message-circle';
    }

    #[Override]
    public static function canCreate(): bool
    {
        return Session::haveRight(KnowbaseItem::$rightname, KnowbaseItem::COMMENTS);
    }

    #[Override]
    public static function canView(): bool
    {
        return Session::haveRight(KnowbaseItem::$rightname, KnowbaseItem::COMMENTS);
    }

    #[Override]
    public static function canUpdate(): bool
    {
        return Session::haveRight(KnowbaseItem::$rightname, KnowbaseItem::COMMENTS);
    }

    #[Override]
    public static function canDelete(): bool
    {
        // Soft delete is not supported on this item
        return false;
    }

    #[Override]
    public static function canPurge(): bool
    {
        return Session::haveRight(KnowbaseItem::$rightname, KnowbaseItem::COMMENTS);
    }

    #[Override]
    public function canCreateItem(): bool
    {
        return $this->canComment();
    }

    #[Override]
    public function canViewItem(): bool
    {
        return $this->canComment();
    }

    #[Override]
    public function canUpdateItem(): bool
    {
        if (!$this->canComment()) {
            return false;
        }

        // Users can edit their own comments and admins can edit all comments
        return $this->isAuthor() || $this->isKnowbaseAdmin();
    }

    #[Override]
    public function canDeleteItem(): bool
    {
        // Soft delete is not supported on this item
        return false;
    }

    #[Override]
    public function canPurgeItem(): bool
    {
        if (!$this->canComment()) {
            return false;
        }

        // Users can delete their own comments and admins can delete all comments
        return $this->isAuthor() || $this->isKnowbaseAdmin();
    }

    #[Override]
    public function prepareInputForAdd($input): array
    {
        if (!isset($input["users_id"])) {
            $input["users_id"] = 0;
            if ($uid = Session::getLoginUserID()) {
                $input["users_id"] = $uid;
            }
        }

        return $input;
    }

    /** @return CommentsThread[] */
    public static function getCommentsThreads(KnowbaseItem $article): array
    {
        $threads = [];
        $comments = KnowbaseItem_Comment::getSeveralFromDBByCrit([
            'knowbaseitems_id'  => $article->getID(),
            'parent_comment_id' => null,
        ], ['id ASC']);

        foreach ($comments as $comment) {
            $thread = new CommentsThread();
            $thread->addComment($comment);

            $answers = KnowbaseItem_Comment::getSeveralFromDBByCrit([
                'knowbaseitems_id'  => $article->getID(),
                'parent_comment_id' => $comment->getID(),
            ], ['id ASC']);
            foreach ($answers as $answer) {
                $thread->addComment($answer);
            }

            $threads[] = $thread;
        }

        return $threads;
    }

    private function canComment(): bool
    {
        $kbitem = new KnowbaseItem();
        if (!$kbitem->getFromDB($this->fields['knowbaseitems_id'])) {
            return false;
        }
        return $kbitem->canComment();
    }

    private function isKnowbaseAdmin(): bool
    {
        return Session::haveRight(
            KnowbaseItem::$rightname,
            KnowbaseItem::KNOWBASEADMIN
        );
    }

    private function isAuthor(): bool
    {
        return Session::getLoginUserID() === $this->fields['users_id'];
    }
}
