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

namespace tests\units\Glpi\Controller\Knowbase;

use Glpi\Controller\Knowbase\AddCommentController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_Comment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AddCommentControllerTest extends DbTestCase
{
    private function makeArticle(): int
    {
        return $this->createItem(KnowbaseItem::class, [
            'name'   => 'Add comment anchor ' . $this->getUniqueString(),
            'answer' => '<p>Some content to comment on</p>',
        ])->getID();
    }

    private function callController(int $id, array $body): Response
    {
        $request = Request::create(
            '/Knowbase/' . $id . '/AddComment',
            'POST',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body),
        );
        return (new AddCommentController())->__invoke($id, $request);
    }

    public function testAnchoredCommentIsPersisted(): void
    {
        $this->login();
        $id = $this->makeArticle();

        $this->callController($id, [
            'content'           => 'My comment',
            'anchor_prefix'     => 'Some ',
            'anchor_exact'      => 'content',
            'anchor_suffix'     => ' to comment',
            'anchor_occurrence' => 0,
        ]);

        $comments = iterator_to_array(KnowbaseItem_Comment::getSeveralFromDBByCrit([
            'knowbaseitems_id' => $id,
        ]));
        $comment = reset($comments);
        $this->assertNotFalse($comment);
        $this->assertTrue($comment->hasAnchor());
        $this->assertSame('content', $comment->fields['anchor_exact']);
    }

    public function testReplyIgnoresAnchorFields(): void
    {
        $this->login();
        $id = $this->makeArticle();

        $this->callController($id, ['content' => 'Root comment']);
        $roots = iterator_to_array(KnowbaseItem_Comment::getSeveralFromDBByCrit([
            'knowbaseitems_id' => $id,
        ]));
        $root = reset($roots);

        $this->callController($id, [
            'content'           => 'Reply comment',
            'parent_comment_id' => $root->getID(),
            'anchor_exact'      => 'should be ignored',
        ]);

        $replies = iterator_to_array(KnowbaseItem_Comment::getSeveralFromDBByCrit([
            'knowbaseitems_id'  => $id,
            'parent_comment_id' => $root->getID(),
        ]));
        $reply = reset($replies);
        $this->assertNotFalse($reply);
        $this->assertFalse($reply->hasAnchor());
    }

    public function testPlainCommentHasNoAnchor(): void
    {
        $this->login();
        $id = $this->makeArticle();

        $this->callController($id, ['content' => 'Plain comment']);

        $comments = iterator_to_array(KnowbaseItem_Comment::getSeveralFromDBByCrit([
            'knowbaseitems_id' => $id,
        ]));
        $comment = reset($comments);
        $this->assertNotFalse($comment);
        $this->assertFalse($comment->hasAnchor());
    }

    public function testOversizedAnchorIsRejected(): void
    {
        $this->login();
        $id = $this->makeArticle();

        $this->expectException(BadRequestHttpException::class);
        $this->callController($id, [
            'content'      => 'My comment',
            'anchor_exact' => str_repeat('a', KnowbaseItem_Comment::MAX_ANCHOR_LENGTH + 1),
        ]);
    }

    public function testUserWithoutCommentRightIsDenied(): void
    {
        $this->login();
        $id = $this->makeArticle();

        $_SESSION['glpiactiveprofile']['knowbase'] &= ~KnowbaseItem::COMMENTS;

        $this->expectException(AccessDeniedHttpException::class);
        $this->callController($id, ['content' => 'Should be denied']);
    }
}
