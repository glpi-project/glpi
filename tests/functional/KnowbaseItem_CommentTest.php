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

namespace tests\units;

use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_Comment;
use KnowbaseItem_User;
use Session;

/* Test for inc/knowbaseitem_comment.class.php */

/**
 * @engine isolate
 */
class KnowbaseItem_CommentTest extends DbTestCase
{
    public function testGetTypeName()
    {
        $expected = 'Comment';
        $this->assertSame($expected, KnowbaseItem_Comment::getTypeName(1));

        $expected = 'Comments';
        foreach ([0, 2, 10] as $i) {
            $this->assertSame($expected, KnowbaseItem_Comment::getTypeName($i));
        }
    }

    public function testGetCommentsForKbItem()
    {
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        // first, set data
        $this->addComments($kb1);

        $nb = countElementsInTable(
            'glpi_knowbaseitems_comments'
        );
        $this->assertSame(6, $nb);

        // second, test what we retrieve
        $comments = KnowbaseItem_Comment::getCommentsForKbItem($kb1);
        $this->assertCount(3, $comments);
        $this->assertCount(2, $comments[0]->fields['_answers']);
        $this->assertCount(1, $comments[0]->fields['_answers'][0]->fields['_answers']);
        $this->assertCount(0, $comments[0]->fields['_answers'][1]->fields['_answers']);
        $this->assertCount(0, $comments[1]->fields['_answers']);
    }

    /**
     * Add comments into database
     *
     * @param KnowbaseItem $kb   KB item instance
     *
     * @return void
     */
    private function addComments(KnowbaseItem $kb)
    {
        $this->login();
        $kbcom = new KnowbaseItem_Comment();
        $input = [
            'knowbaseitems_id' => $kb->getID(),
            'users_id'         => getItemByTypeName('User', TU_USER, true),
            'comment'          => 'Comment 1 for KB1',
        ];
        $kbcom1 = $kbcom->add($input);
        $this->assertTrue($kbcom1 > 0);

        $input['comment'] = 'Comment 2 for KB1';
        $kbcom2 = $kbcom->add($input);
        $this->assertTrue($kbcom2 > $kbcom1);

        //this one is from another user.
        $input['comment'] = 'Comment 1 - 1 for KB1';
        $input['parent_comment_id'] = $kbcom1;
        $input['users_id'] = getItemByTypeName('User', 'glpi', true);
        $kbcom11 = $kbcom->add($input);
        $this->assertTrue($kbcom11 > $kbcom2);

        $input['comment'] = 'Comment 1 - 2 for KB1';
        $input['users_id'] = getItemByTypeName('User', TU_USER, true);
        $kbcom12 = $kbcom->add($input);
        $this->assertTrue($kbcom12 > $kbcom11);

        $input['comment'] = 'Comment 1 - 1 - 1 for KB1';
        $input['parent_comment_id'] = $kbcom11;
        $kbcom111 = $kbcom->add($input);
        $this->assertTrue($kbcom111 > $kbcom12);

        // comment from non-existent user to simulate deleted user
        $this->assertGreaterThan(0, $kbcom->add([
            'knowbaseitems_id' => $kb->getID(),
            'users_id'         => 9999999,
            'comment'          => 'Comment 3 for KB1',
        ]));
    }

    public function testRights(): void
    {
        $this->login();
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');
        $comment = new KnowbaseItem_Comment();
        $new_comment_input = [
            'knowbaseitems_id' => $kb1->getID(),
            'users_id'         => Session::getLoginUserID(),
            'comment'          => 'Comment for rights test',
        ];

        $all_knowbase_rights = ALLSTANDARDRIGHT | KnowbaseItem::KNOWBASEADMIN | KnowbaseItem::COMMENTS;

        $this->assertTrue($comment::canCreate());
        $this->assertTrue($comment::canUpdate());
        $this->assertTrue($comment::canView());
        $this->assertFalse($comment::canDelete());
        $this->assertTrue($comment::canPurge());
        $this->assertTrue($comment->can(-1, CREATE, $new_comment_input));
        $this->assertNotFalse($comment->add($new_comment_input));
        $this->assertTrue($comment->can($comment->getID(), READ));
        $this->assertTrue($comment->can($comment->getID(), UPDATE));
        $this->assertFalse($comment->can($comment->getID(), DELETE));
        $this->assertTrue($comment->can($comment->getID(), PURGE));
        $_SESSION['glpiactiveprofile']['knowbase'] = $all_knowbase_rights & ~KnowbaseItem::COMMENTS;
        $this->assertFalse($comment::canCreate());
        $this->assertFalse($comment::canUpdate());
        $this->assertFalse($comment::canView());
        $this->assertFalse($comment::canDelete());
        $this->assertFalse($comment::canPurge());

        $kb2 = new KnowbaseItem();
        $this->assertNotFalse($kb2->add([
            'name' => 'KB item for rights test',
            'content' => 'Content of KB item for rights test',
        ]));
        $kb2_comment1 = new KnowbaseItem_Comment();
        $kb2_comment_input = [
            'knowbaseitems_id' => $kb2->getID(),
            'users_id'         => Session::getLoginUserID(),
            'comment'          => 'Comment 1 for KB2',
        ];
        $this->assertNotFalse($kb2_comment1->add($kb2_comment_input));
        $kb2_comment2 = new KnowbaseItem_Comment();
        $kb2_comment_input['comment'] = 'Comment 2 for KB2';
        $kb2_comment_input['users_id'] = getItemByTypeName('User', 'tech', true);
        $this->assertNotFalse($kb2_comment2->add($kb2_comment_input));

        $this->login('tech', 'tech');
        $new_comment_input['knowbaseitems_id'] = $kb2->getID();
        $this->assertFalse($kb2->canViewItem());
        $this->assertFalse($comment->can(-1, CREATE, $new_comment_input));
        $this->assertFalse($kb2_comment1->can($kb2_comment1->getID(), READ));
        $this->assertFalse($kb2_comment2->can($kb2_comment2->getID(), READ));
        $this->assertFalse($kb2_comment1->can($kb2_comment1->getID(), UPDATE));
        $this->assertFalse($kb2_comment2->can($kb2_comment2->getID(), UPDATE));
        $this->assertFalse($kb2_comment2->can($kb2_comment1->getID(), DELETE));
        $this->assertFalse($kb2_comment2->can($kb2_comment2->getID(), DELETE));
        $this->assertFalse($kb2_comment1->can($kb2_comment1->getID(), PURGE));
        $this->assertFalse($kb2_comment2->can($kb2_comment2->getID(), PURGE));

        // give visibility
        $kb_user = new KnowbaseItem_User();
        $this->assertNotFalse($kb_user->add([
            'knowbaseitems_id' => $kb2->getID(),
            'users_id'         => getItemByTypeName('User', 'tech', true),
        ]));
        $this->assertTrue($kb2->update([
            'id' => $kb2->getID(),
            'is_faq' => 1,
        ]));
        $this->assertTrue($kb2->canViewItem());
        $this->assertTrue($comment->can(-1, CREATE, $new_comment_input));
        $this->assertTrue($kb2_comment1->can($kb2_comment1->getID(), READ));
        $this->assertTrue($kb2_comment2->can($kb2_comment2->getID(), READ));
        $this->assertFalse($kb2_comment1->can($kb2_comment1->getID(), UPDATE));
        $this->assertFalse($kb2_comment2->can($kb2_comment2->getID(), UPDATE));
        $this->assertFalse($kb2_comment1->can($kb2_comment1->getID(), DELETE));
        $this->assertFalse($kb2_comment2->can($kb2_comment2->getID(), DELETE));
        $this->assertFalse($kb2_comment1->can($kb2_comment1->getID(), PURGE));
        $this->assertFalse($kb2_comment2->can($kb2_comment2->getID(), PURGE));
    }
}
