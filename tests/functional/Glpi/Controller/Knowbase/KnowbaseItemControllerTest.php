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
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_Comment;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

    private function sanitizeHtmlBlock(?int $id, array $body): JsonResponse
    {
        $request = Request::create(
            $id === null
                ? '/Knowbase/KnowbaseItem/SanitizeHtmlBlock'
                : '/Knowbase/KnowbaseItem/' . $id . '/SanitizeHtmlBlock',
            'POST',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($body),
        );
        if ($id !== null) {
            $request->attributes->set('knowbaseitems_id', $id);
        }

        return (new KnowbaseItemController())->sanitizeHtmlBlock($request);
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

    public function testSanitizeHtmlBlockStripsDisallowedContent(): void
    {
        $this->login();
        $id = $this->makeArticle();

        $response = $this->sanitizeHtmlBlock($id, [
            'html' => '<p onclick="alert(1)">Hi</p><script>alert(1)</script>',
        ]);

        $payload = json_decode($response->getContent(), true);
        $this->assertTrue($payload['success']);
        $this->assertStringContainsString('<p>Hi</p>', $payload['html']);
        $this->assertStringNotContainsString('<script', $payload['html']);
        $this->assertStringNotContainsString('onclick', $payload['html']);
    }

    public function testSanitizeHtmlBlockDeniedWithoutUpdateRight(): void
    {
        $this->login();
        $id = $this->makeArticle();

        // Drop the knowbase UPDATE right, keeping READ so the article loads.
        $this->setEntity('_test_root_entity', true);
        $_SESSION['glpiactiveprofile']['knowbase'] = READ;

        $this->expectException(AccessDeniedHttpException::class);
        $this->sanitizeHtmlBlock($id, ['html' => '<p>Hi</p>']);
    }

    /**
     * The dialog field holds HTML by definition. Without `is_html`, isRichTextHtmlContent()
     * only knows a few common tags and would escape the rest into a `<p>` block.
     */
    public function testSanitizeHtmlBlockKeepsTagsIsRichTextHtmlContentDoesNotKnow(): void
    {
        $this->login();
        $id = $this->makeArticle();

        $response = $this->sanitizeHtmlBlock($id, ['html' => '<figure><figcaption>Caption</figcaption></figure>']);
        $html = json_decode($response->getContent(), true)['html'];

        $this->assertStringContainsString('<figure>', $html);
        $this->assertStringNotContainsString('&lt;figure&gt;', $html);
    }

    public static function invalidHtmlBodyProvider(): iterable
    {
        yield 'missing' => [[]];
        yield 'null' => [['html' => null]];
        yield 'array' => [['html' => ['<p>Hi</p>']]];
        yield 'int' => [['html' => 5]];
    }

    #[DataProvider('invalidHtmlBodyProvider')]
    public function testSanitizeHtmlBlockRejectsInvalidHtml(array $body): void
    {
        $this->login();
        $id = $this->makeArticle();

        $response = $this->sanitizeHtmlBlock($id, $body);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertFalse(json_decode($response->getContent(), true)['success']);
    }

    public function testSanitizeHtmlBlockRejectsUnknownArticle(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        $this->sanitizeHtmlBlock(999999, ['html' => '<p>Hi</p>']);
    }

    /**
     * The article has no id yet while it is being created. The id only drives the
     * rights check, the sanitizing itself never needs it.
     */
    public function testSanitizeHtmlBlockWorksWhileArticleIsBeingCreated(): void
    {
        $this->login();

        $response = $this->sanitizeHtmlBlock(null, [
            'html' => '<p onclick="alert(1)">Hi</p><script>alert(1)</script>',
        ]);

        $payload = json_decode($response->getContent(), true);
        $this->assertTrue($payload['success']);
        $this->assertStringContainsString('<p>Hi</p>', $payload['html']);
        $this->assertStringNotContainsString('<script', $payload['html']);
        $this->assertStringNotContainsString('onclick', $payload['html']);
    }

    public function testSanitizeHtmlBlockDeniedWithoutCreateRightWhileArticleIsBeingCreated(): void
    {
        $this->login();

        $this->setEntity('_test_root_entity', true);
        $_SESSION['glpiactiveprofile']['knowbase'] = READ;

        $this->expectException(AccessDeniedHttpException::class);
        $this->sanitizeHtmlBlock(null, ['html' => '<p>Hi</p>']);
    }
}
