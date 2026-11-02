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

use Glpi\Controller\Knowbase\KnowbaseItemController;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_Comment;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class KnowbaseItemControllerTest extends DbTestCase
{
    private function makeArticle(): int
    {
        return $this->createItem(KnowbaseItem::class, [
            'name'   => 'Answer update ' . $this->getUniqueString(),
            'answer' => '<p>The quoted passage lives here</p>',
        ])->getID();
    }

    private function makeAnchoredComment(int $knowbaseitems_id): int
    {
        $comment = new KnowbaseItem_Comment();
        return (int) $comment->add([
            'knowbaseitems_id'  => $knowbaseitems_id,
            'comment'           => 'Anchored comment',
            'anchor_prefix'     => 'The ',
            'anchor_exact'      => 'quoted passage',
            'anchor_suffix'     => ' lives',
            'anchor_occurrence' => 0,
        ]);
    }

    private function updateAnswer(int $id, array $body): JsonResponse
    {
        $request = Request::create(
            '/Knowbase/KnowbaseItem/' . $id . '/Answer',
            'POST',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body),
        );
        $request->attributes->set('knowbaseitems_id', $id);

        return (new KnowbaseItemController())->updateAnswer($request);
    }

    public function testOrphanedAnchorsAreDroppedOnSave(): void
    {
        $this->login();
        $id         = $this->makeArticle();
        $comment_id = $this->makeAnchoredComment($id);

        $this->updateAnswer($id, [
            'answer'               => '<p>Something completely different</p>',
            'orphaned_comment_ids' => [$comment_id],
        ]);

        $comment = new KnowbaseItem_Comment();
        $comment->getFromDB($comment_id);
        $this->assertFalse($comment->hasAnchor());
    }

    public function testEditedAnchorsAreRefreshedOnSave(): void
    {
        $this->login();
        $id         = $this->makeArticle();
        $comment_id = $this->makeAnchoredComment($id);

        $this->updateAnswer($id, [
            'answer'          => '<p>The edited passage lives here</p>',
            'comment_anchors' => [[
                'id'         => $comment_id,
                'prefix'     => 'The ',
                'exact'      => 'edited passage',
                'suffix'     => ' lives',
                'occurrence' => 0,
            ]],
        ]);

        $comment = new KnowbaseItem_Comment();
        $comment->getFromDB($comment_id);
        $this->assertSame('edited passage', $comment->fields['anchor_exact']);
    }

    public function testRefreshedAnchorOfAnotherArticleIsNotTouched(): void
    {
        $this->login();
        $id       = $this->makeArticle();
        $other_id = $this->makeArticle();
        // Belongs to $other_id, but reported as refreshed while saving $id.
        $comment_id = $this->makeAnchoredComment($other_id);

        $this->updateAnswer($id, [
            'answer'          => '<p>The edited passage lives here</p>',
            'comment_anchors' => [[
                'id'    => $comment_id,
                'exact' => 'hijacked',
            ]],
        ]);

        $comment = new KnowbaseItem_Comment();
        $comment->getFromDB($comment_id);
        $this->assertSame('quoted passage', $comment->fields['anchor_exact']);
    }

    public function testAnchorsAreKeptWhenNoneAreReportedOrphaned(): void
    {
        $this->login();
        $id         = $this->makeArticle();
        $comment_id = $this->makeAnchoredComment($id);

        $this->updateAnswer($id, ['answer' => '<p>The quoted passage lives here still</p>']);

        $comment = new KnowbaseItem_Comment();
        $comment->getFromDB($comment_id);
        $this->assertTrue($comment->hasAnchor());
    }

    public function testAnchorOfAnotherArticleIsNotDropped(): void
    {
        $this->login();
        $id       = $this->makeArticle();
        $other_id = $this->makeArticle();
        // Belongs to $other_id, but reported as orphaned while saving $id.
        $comment_id = $this->makeAnchoredComment($other_id);

        $this->updateAnswer($id, [
            'answer'               => '<p>Something completely different</p>',
            'orphaned_comment_ids' => [$comment_id],
        ]);

        $comment = new KnowbaseItem_Comment();
        $comment->getFromDB($comment_id);
        $this->assertTrue($comment->hasAnchor());
    }
}
