<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Application\View\TemplateRenderer;

/**
 * Class KnowbaseItem_Comment
 * @since 9.2.0
 * @todo Extend CommonDBChild
 */
class KnowbaseItem_Comment extends CommonDBTM
{
    public static function getTypeName($nb = 0)
    {
        return _n('Comment', 'Comments', $nb);
    }

    public static function getIcon()
    {
        return 'ti ti-message-circle';
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!($item instanceof KnowbaseItem) || !$item->canComment()) {
            return '';
        }

        $nb = 0;
        if ($_SESSION['glpishow_count_on_tabs']) {
            if ($item::class === KnowbaseItem::class) {
                $where = [
                    'knowbaseitems_id' => $item->getID(),
                    'language'         => null,
                ];
            } else {
                $where = [
                    'knowbaseitems_id' => $item->fields['knowbaseitems_id'],
                    'language'         => $item->fields['language'],
                ];
            }

            $nb = countElementsInTable(
                'glpi_knowbaseitems_comments',
                $where
            );
        }
        return self::createTabEntry(self::getTypeName($nb), $nb, $item::getType());
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }
        self::showForItem($item, $withtemplate);
        return true;
    }

    /**
     * Show linked items of a knowbase item
     *
     * @param CommonDBTM $item
     * @param integer $withtemplate withtemplate param (default 0)
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        $kbitem_id = $item::class === KnowbaseItem::class ? $item->getID() : $item->fields['knowbaseitems_id'];
        $lang = $item::class === KnowbaseItem::class ? null : $item->fields['language'];
        $kbitem = new KnowbaseItem();
        $kbitem->getFromDB($kbitem_id);
        $comments = self::getCommentsForKbItem($kbitem_id, $lang);

        TemplateRenderer::getInstance()->display('pages/tools/kb/comments.html.twig', [
            'kbitem_id' => $kbitem_id,
            'lang' => $lang,
            'comments' => $comments,
            'can_comment' => $kbitem->canComment(),
        ]);
    }

    /**
     * Gat all comments for specified KB entry
     *
     * @param integer $kbitem_id KB entry ID
     * @param string  $lang      Requested language
     * @param integer $parent    Parent ID (defaults to 0)
     * @param array   $user_data_cache
     *
     * @return array
     */
    public static function getCommentsForKbItem($kbitem_id, $lang, $parent = null, &$user_data_cache = [])
    {
        global $DB;

        $where = [
            'knowbaseitems_id'  => $kbitem_id,
            'language'          => $lang,
            'parent_comment_id' => $parent,
        ];

        $db_comments = $DB->request([
            'FROM' => 'glpi_knowbaseitems_comments',
            'WHERE' => $where,
            'ORDER' => 'id ASC',
        ]);

        $comments = [];
        foreach ($db_comments as $db_comment) {
            if (!isset($user_data_cache[$db_comment['users_id']])) {
                $user = new User();
                $user->getFromDB($db_comment['users_id']);
                $user_data_cache[$db_comment['users_id']] = [
                    'avatar' => User::getThumbnailURLForPicture($user->fields['picture']),
                    'link'   => $user->getLinkURL(),
                    'initials' => $user->getUserInitials(),
                    'initials_bg_color' => $user->getUserInitialsBgColor(),
                ];
            }
            $db_comment['answers'] = self::getCommentsForKbItem($kbitem_id, $lang, $db_comment['id'], $user_data_cache);
            $db_comment['user_info'] = $user_data_cache[$db_comment['users_id']];
            $comments[] = $db_comment;
        }

        return $comments;
    }

    /**
     * Get comment form
     *
     * @param integer       $kbitem_id Knowbase item ID
     * @param string        $lang      Related item language
     * @param false|integer $edit      Comment id to edit, or false
     * @param false|integer $answer    Comment id to answer to, or false
     * @return string
     */
    public static function getCommentForm($kbitem_id, $lang = null, $edit = false, $answer = false)
    {
        $content = '';
        if ($edit !== false) {
            $comment = new KnowbaseItem_Comment();
            $comment->getFromDB($edit);
            $content = $comment->fields['comment'];
        }
        return TemplateRenderer::getInstance()->render('pages/tools/kb/comment_form.html.twig', [
            'kbitem_id' => $kbitem_id,
            'language' => $lang,
            'comment_id' => $edit,
            'comment' => $content,
            'parent_comment_id' => $answer,
            'edit' => $edit,
        ]);
    }

    public function prepareInputForAdd($input)
    {
        if (!isset($input["users_id"])) {
            $input["users_id"] = 0;
            if ($uid = Session::getLoginUserID()) {
                $input["users_id"] = $uid;
            }
        }

        return $input;
    }
}
