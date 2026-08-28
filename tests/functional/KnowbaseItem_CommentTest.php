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

    public function testGetCommentsThreads()
    {
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        // first, set data
        $this->addComments($kb1);

        $nb = countElementsInTable(
            'glpi_knowbaseitems_comments'
        );
        $this->assertSame(5, $nb);

        // second, test what we retrieve
        $threads = KnowbaseItem_Comment::getCommentsThreads($kb1);

        $thread1 = $threads[0];
        $this->assertCount(3, $thread1->getComments());
        $this->assertEquals([
            'Comment 1 for KB1',
            'Comment 1 - 1 for KB1',
            'Comment 1 - 2 for KB1',
        ], array_map(fn($c) => $c->fields['comment'], $thread1->getComments()));

        $thread2 = $threads[1];
        $this->assertCount(1, $thread2->getComments());
        $this->assertEquals([
            'Comment 2 for KB1',
        ], array_map(fn($c) => $c->fields['comment'], $thread2->getComments()));

        $thread3 = $threads[2];
        $this->assertCount(1, $thread3->getComments());
        $this->assertEquals([
            'Comment 3 for KB1',
        ], array_map(fn($c) => $c->fields['comment'], $thread3->getComments()));
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

    public function testAnchoredCommentPersistsAnchorFields(): void
    {
        $this->login();
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        $comment = new KnowbaseItem_Comment();
        $id = $comment->add([
            'knowbaseitems_id'  => $kb1->getID(),
            'comment'           => 'Anchored comment',
            'anchor_prefix'     => 'before ',
            'anchor_exact'      => 'the quoted text',
            'anchor_suffix'     => ' after',
            'anchor_occurrence' => 0,
        ]);
        $this->assertGreaterThan(0, $id);

        $comment->getFromDB($id);
        $this->assertTrue($comment->hasAnchor());
        $this->assertSame('before ', $comment->fields['anchor_prefix']);
        $this->assertSame('the quoted text', $comment->fields['anchor_exact']);
        $this->assertSame(' after', $comment->fields['anchor_suffix']);
        $this->assertSame(0, (int) $comment->fields['anchor_occurrence']);
    }

    public function testAnchorAtTheLengthLimitIsAccepted(): void
    {
        $this->login();
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        // Multi-byte on purpose: the limit counts characters, not bytes.
        $exact = str_repeat('é', KnowbaseItem_Comment::MAX_ANCHOR_LENGTH);

        $comment = new KnowbaseItem_Comment();
        $id = $comment->add([
            'knowbaseitems_id' => $kb1->getID(),
            'comment'          => 'Anchor at the limit',
            'anchor_exact'     => $exact,
        ]);
        $this->assertGreaterThan(0, $id);

        $comment->getFromDB($id);
        $this->assertSame($exact, $comment->fields['anchor_exact']);
    }

    public function testAnchorLongerThanTheLengthLimitIsRejected(): void
    {
        $this->login();
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        $comment = new KnowbaseItem_Comment();
        $this->assertFalse($comment->add([
            'knowbaseitems_id' => $kb1->getID(),
            'comment'          => 'Oversized anchor',
            'anchor_exact'     => str_repeat('a', KnowbaseItem_Comment::MAX_ANCHOR_LENGTH + 1),
        ]));
    }

    public function testAnchorContextLongerThanTheLengthLimitIsRejected(): void
    {
        $this->login();
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        $comment = new KnowbaseItem_Comment();
        $this->assertFalse($comment->add([
            'knowbaseitems_id' => $kb1->getID(),
            'comment'          => 'Oversized anchor context',
            'anchor_prefix'    => str_repeat('a', KnowbaseItem_Comment::MAX_ANCHOR_CONTEXT_LENGTH + 1),
            'anchor_exact'     => 'quoted',
        ]));
    }

    public function testClearAnchorsForItemDropsEveryAnchorField(): void
    {
        $this->login();
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        $comment = new KnowbaseItem_Comment();
        $id = $comment->add([
            'knowbaseitems_id'  => $kb1->getID(),
            'comment'           => 'Anchored on a passage about to be edited away',
            'anchor_prefix'     => 'before ',
            'anchor_exact'      => 'the quoted text',
            'anchor_suffix'     => ' after',
            'anchor_occurrence' => 2,
        ]);

        (new KnowbaseItem_Comment())->clearAnchorsForItem($kb1, [$id]);

        $comment->getFromDB($id);
        $this->assertFalse($comment->hasAnchor());
        $this->assertNull($comment->fields['anchor_prefix']);
        $this->assertNull($comment->fields['anchor_exact']);
        $this->assertNull($comment->fields['anchor_suffix']);
        $this->assertNull($comment->fields['anchor_occurrence']);
        // The comment itself survives; only its anchoring is dropped.
        $this->assertSame('Anchored on a passage about to be edited away', $comment->fields['comment']);
    }

    public function testClearAnchorsForItemIgnoresCommentsOfAnotherArticle(): void
    {
        $this->login();
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        $other = new KnowbaseItem();
        $this->assertNotFalse($other->add([
            'name'    => 'KB item owning the anchor',
            'content' => 'Content',
        ]));

        $comment = new KnowbaseItem_Comment();
        $id = $comment->add([
            'knowbaseitems_id' => $other->getID(),
            'comment'          => 'Anchored elsewhere',
            'anchor_exact'     => 'untouchable',
        ]);

        (new KnowbaseItem_Comment())->clearAnchorsForItem($kb1, [$id]);

        $comment->getFromDB($id);
        $this->assertTrue($comment->hasAnchor());
        $this->assertSame('untouchable', $comment->fields['anchor_exact']);
    }

    public function testClearAnchorsForItemSkipsWhenUserCannotUpdateArticle(): void
    {
        $this->login();
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        $comment = new KnowbaseItem_Comment();
        $id = $comment->add([
            'knowbaseitems_id' => $kb1->getID(),
            'comment'          => 'Someone else comment',
            'anchor_exact'     => 'untouchable',
        ]);

        // 'tech' is not the article's author; drop KB-admin/update/publish-FAQ bits too.
        $this->login('tech', 'tech');
        $_SESSION['glpiactiveprofile']['knowbase'] &= ~(UPDATE | KnowbaseItem::KNOWBASEADMIN | KnowbaseItem::PUBLISHFAQ);
        (new KnowbaseItem_Comment())->clearAnchorsForItem($kb1, [$id]);

        $this->login();
        $comment->getFromDB($id);
        $this->assertTrue($comment->hasAnchor());
        $this->assertSame('untouchable', $comment->fields['anchor_exact']);
    }

    public function testClearAnchorsForItemSucceedsForNonAuthorWhoCanUpdateArticle(): void
    {
        $this->login();
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        $comment = new KnowbaseItem_Comment();
        $id = $comment->add([
            'knowbaseitems_id' => $kb1->getID(),
            'comment'          => 'Someone else comment',
            'anchor_exact'     => 'to be cleared',
        ]);

        // 'tech' can update the article and comment, but is neither its
        // author, the comment's author, nor a KB admin.
        $this->login('tech', 'tech');
        $_SESSION['glpiactiveprofile']['knowbase'] |= READ | UPDATE | KnowbaseItem::COMMENTS;
        $_SESSION['glpiactiveprofile']['knowbase'] &= ~KnowbaseItem::KNOWBASEADMIN;

        (new KnowbaseItem_Comment())->clearAnchorsForItem($kb1, [$id]);

        $this->login();
        $comment->getFromDB($id);
        $this->assertFalse($comment->hasAnchor());
    }

    public function testClearAnchorsForItemSkipsWhenUserCanUpdateArticleButNotComment(): void
    {
        $this->login();
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        $comment = new KnowbaseItem_Comment();
        $id = $comment->add([
            'knowbaseitems_id' => $kb1->getID(),
            'comment'          => 'Someone else comment',
            'anchor_exact'     => 'untouchable',
        ]);

        // 'tech' can update the article, but the comment feature itself is disabled for them.
        $this->login('tech', 'tech');
        $_SESSION['glpiactiveprofile']['knowbase'] |= READ | UPDATE;
        $_SESSION['glpiactiveprofile']['knowbase'] &= ~(KnowbaseItem::KNOWBASEADMIN | KnowbaseItem::COMMENTS);

        (new KnowbaseItem_Comment())->clearAnchorsForItem($kb1, [$id]);

        $this->login();
        $comment->getFromDB($id);
        $this->assertTrue($comment->hasAnchor());
        $this->assertSame('untouchable', $comment->fields['anchor_exact']);
    }

    public function testUpdateWithAnOversizedAnchorIsRejected(): void
    {
        $this->login();
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        $comment = new KnowbaseItem_Comment();
        $id = $comment->add([
            'knowbaseitems_id' => $kb1->getID(),
            'comment'          => 'Anchored comment',
            'anchor_exact'     => 'quoted',
        ]);

        $this->assertFalse($comment->update([
            'id'           => $id,
            'anchor_exact' => str_repeat('a', KnowbaseItem_Comment::MAX_ANCHOR_LENGTH + 1),
        ]));
    }

    public function testUpdateWithoutAnchorFieldsIsUnaffected(): void
    {
        $this->login();
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        $comment = new KnowbaseItem_Comment();
        $id = $comment->add([
            'knowbaseitems_id' => $kb1->getID(),
            'comment'          => 'Before edit',
            'anchor_exact'     => 'quoted',
        ]);

        $this->assertNotFalse($comment->update([
            'id'      => $id,
            'comment' => 'After edit',
        ]));

        $comment->getFromDB($id);
        $this->assertSame('After edit', $comment->fields['comment']);
        $this->assertTrue($comment->hasAnchor());
    }

    public function testRefreshAnchorsForItemStoresTheEditedQuote(): void
    {
        $this->login();
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        $comment = new KnowbaseItem_Comment();
        $id = $comment->add([
            'knowbaseitems_id'  => $kb1->getID(),
            'comment'           => 'Anchored on a passage the author then edited',
            'anchor_prefix'     => 'before ',
            'anchor_exact'      => 'the quoted text',
            'anchor_suffix'     => ' after',
            'anchor_occurrence' => 0,
        ]);

        (new KnowbaseItem_Comment())->refreshAnchorsForItem($kb1, [[
            'id'         => $id,
            'prefix'     => 'right before ',
            'exact'      => 'the edited quoted text',
            'suffix'     => ' right after',
            'occurrence' => 2,
        ]]);

        $comment->getFromDB($id);
        $this->assertSame('right before ', $comment->fields['anchor_prefix']);
        $this->assertSame('the edited quoted text', $comment->fields['anchor_exact']);
        $this->assertSame(' right after', $comment->fields['anchor_suffix']);
        $this->assertSame(2, (int) $comment->fields['anchor_occurrence']);
    }

    public function testRefreshAnchorsForItemIgnoresCommentsOfAnotherArticle(): void
    {
        $this->login();
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        $other = new KnowbaseItem();
        $this->assertNotFalse($other->add([
            'name'    => 'KB item owning the anchor',
            'content' => 'Content',
        ]));

        $comment = new KnowbaseItem_Comment();
        $id = $comment->add([
            'knowbaseitems_id' => $other->getID(),
            'comment'          => 'Anchored elsewhere',
            'anchor_exact'     => 'untouchable',
        ]);

        (new KnowbaseItem_Comment())->refreshAnchorsForItem($kb1, [[
            'id'    => $id,
            'exact' => 'hijacked',
        ]]);

        $comment->getFromDB($id);
        $this->assertSame('untouchable', $comment->fields['anchor_exact']);
    }

    public function testRefreshAnchorsForItemDoesNotAnchorACommentThatNeverWas(): void
    {
        $this->login();
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        $comment = new KnowbaseItem_Comment();
        $id = $comment->add([
            'knowbaseitems_id' => $kb1->getID(),
            'comment'          => 'Plain comment, never anchored',
        ]);

        (new KnowbaseItem_Comment())->refreshAnchorsForItem($kb1, [[
            'id'    => $id,
            'exact' => 'smuggled anchor',
        ]]);

        $comment->getFromDB($id);
        $this->assertFalse($comment->hasAnchor());
    }

    public function testRefreshAnchorsForItemRejectsAnOverlongQuote(): void
    {
        $this->login();
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        $comment = new KnowbaseItem_Comment();
        $id = $comment->add([
            'knowbaseitems_id' => $kb1->getID(),
            'comment'          => 'Anchored within the allowed length',
            'anchor_exact'     => 'the quoted text',
        ]);

        (new KnowbaseItem_Comment())->refreshAnchorsForItem($kb1, [[
            'id'    => $id,
            'exact' => str_repeat('a', KnowbaseItem_Comment::MAX_ANCHOR_LENGTH + 1),
        ]]);

        $comment->getFromDB($id);
        $this->assertSame('the quoted text', $comment->fields['anchor_exact']);
    }

    public function testCommentWithoutAnchorHasNoAnchor(): void
    {
        $this->login();
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        $comment = new KnowbaseItem_Comment();
        $id = $comment->add([
            'knowbaseitems_id' => $kb1->getID(),
            'comment'          => 'Plain comment',
        ]);
        $comment->getFromDB($id);

        $this->assertFalse($comment->hasAnchor());
    }

    public function testReplyDoesNotPersistAnchorFields(): void
    {
        $this->login();
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        $root = new KnowbaseItem_Comment();
        $root_id = $root->add([
            'knowbaseitems_id'  => $kb1->getID(),
            'comment'           => 'Root comment',
            'anchor_prefix'     => 'before ',
            'anchor_exact'      => 'quoted',
            'anchor_suffix'     => ' after',
            'anchor_occurrence' => 0,
        ]);

        $reply = new KnowbaseItem_Comment();
        $reply_id = $reply->add([
            'knowbaseitems_id'  => $kb1->getID(),
            'comment'           => 'Reply comment',
            'parent_comment_id' => $root_id,
            // A client bug (or a malicious request) might still send anchor
            // fields on a reply; they must never be persisted.
            'anchor_prefix'     => 'should ',
            'anchor_exact'      => 'not be stored',
            'anchor_suffix'     => ' at all',
            'anchor_occurrence' => 0,
        ]);
        $reply->getFromDB($reply_id);

        $this->assertFalse($reply->hasAnchor());
        $this->assertNull($reply->fields['anchor_exact']);
    }

    public function testGetAnchorsForItem(): void
    {
        $this->login();
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        $anchored = new KnowbaseItem_Comment();
        $anchored_id = $anchored->add([
            'knowbaseitems_id'  => $kb1->getID(),
            'comment'           => 'Anchored',
            'anchor_prefix'     => 'a',
            'anchor_exact'      => 'b',
            'anchor_suffix'     => 'c',
            'anchor_occurrence' => 2,
        ]);

        $plain = new KnowbaseItem_Comment();
        $plain->add([
            'knowbaseitems_id' => $kb1->getID(),
            'comment'          => 'Plain, no anchor',
        ]);

        $anchors = KnowbaseItem_Comment::getAnchorsForItem($kb1);
        $matching = array_values(array_filter(
            $anchors,
            fn($anchor) => $anchor['id'] === $anchored_id,
        ));

        $this->assertCount(1, $matching);
        $this->assertEquals([
            'id'         => $anchored_id,
            'prefix'     => 'a',
            'exact'      => 'b',
            'suffix'     => 'c',
            'occurrence' => 2,
        ], $matching[0]);
    }
}
