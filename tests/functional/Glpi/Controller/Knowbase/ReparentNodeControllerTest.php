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

use Glpi\Controller\Knowbase\ReparentNodeController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Plugin\Hooks;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_KnowbaseItemCategory;
use KnowbaseItemCategory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ReparentNodeControllerTest extends DbTestCase
{
    private function callController(array $payload): Response
    {
        $controller = new ReparentNodeController();
        $request = new Request(content: json_encode($payload));
        return $controller->__invoke($request);
    }

    private function makeCategory(string $name, int $parent_id = 0): KnowbaseItemCategory
    {
        return $this->createItem(KnowbaseItemCategory::class, [
            'name'                      => $name,
            'knowbaseitemcategories_id' => $parent_id,
            'entities_id'               => $this->getTestRootEntity(only_id: true),
            'is_recursive'              => 1,
        ]);
    }

    /**
     * @param int[] $category_ids
     */
    private function makeArticle(string $name, array $category_ids = []): KnowbaseItem
    {
        return $this->createItem(KnowbaseItem::class, [
            'name'        => $name,
            'answer'      => '<p>Content</p>',
            '_categories' => $category_ids,
        ]);
    }

    private function hasJunction(int $article_id, int $category_id): bool
    {
        $junction = new KnowbaseItem_KnowbaseItemCategory();
        return $junction->getFromDBByCrit([
            'knowbaseitems_id'          => $article_id,
            'knowbaseitemcategories_id' => $category_id,
        ]);
    }

    public function testMoveArticleBetweenCategories(): void
    {
        $this->login();
        $cat_from = $this->makeCategory('From');
        $cat_to   = $this->makeCategory('To');
        $article  = $this->makeArticle('A', [$cat_from->getID()]);

        $response = $this->callController([
            'itemtype'       => KnowbaseItem::class,
            'items_id'       => $article->getID(),
            'from_parent_id' => $cat_from->getID(),
            'to_parent_id'   => $cat_to->getID(),
        ]);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertFalse($this->hasJunction($article->getID(), $cat_from->getID()));
        $this->assertTrue($this->hasJunction($article->getID(), $cat_to->getID()));
    }

    public function testMoveArticleFromUncategorizedToCategory(): void
    {
        $this->login();
        $cat_to  = $this->makeCategory('To');
        $article = $this->makeArticle('A');

        $this->assertFalse($this->hasJunction($article->getID(), $cat_to->getID()));

        $response = $this->callController([
            'itemtype'       => KnowbaseItem::class,
            'items_id'       => $article->getID(),
            'from_parent_id' => 0,
            'to_parent_id'   => $cat_to->getID(),
        ]);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertTrue($this->hasJunction($article->getID(), $cat_to->getID()));
    }

    public function testMoveArticleToUncategorizedRemovesLink(): void
    {
        $this->login();
        $cat_from = $this->makeCategory('From');
        $article  = $this->makeArticle('A', [$cat_from->getID()]);

        $response = $this->callController([
            'itemtype'       => KnowbaseItem::class,
            'items_id'       => $article->getID(),
            'from_parent_id' => $cat_from->getID(),
            'to_parent_id'   => 0,
        ]);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertFalse($this->hasJunction($article->getID(), $cat_from->getID()));
    }

    public function testMoveArticleOnlyAffectsOriginCategoryWhenInMultipleCategories(): void
    {
        $this->login();
        $cat_a = $this->makeCategory('A');
        $cat_b = $this->makeCategory('B');
        $cat_c = $this->makeCategory('C');
        $article = $this->makeArticle('Multi', [$cat_a->getID(), $cat_b->getID()]);

        // Drag the (article, A) occurrence into C.
        // The link to B must remain untouched.
        $response = $this->callController([
            'itemtype'       => KnowbaseItem::class,
            'items_id'       => $article->getID(),
            'from_parent_id' => $cat_a->getID(),
            'to_parent_id'   => $cat_c->getID(),
        ]);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertFalse($this->hasJunction($article->getID(), $cat_a->getID()));
        $this->assertTrue($this->hasJunction($article->getID(), $cat_b->getID()));
        $this->assertTrue($this->hasJunction($article->getID(), $cat_c->getID()));
    }

    public function testMoveArticleToCategoryItIsAlreadyInOnlyRemovesOrigin(): void
    {
        $this->login();
        $cat_a = $this->makeCategory('A');
        $cat_b = $this->makeCategory('B');
        $article = $this->makeArticle('Multi', [$cat_a->getID(), $cat_b->getID()]);

        // Drop the (article, A) occurrence on B (where the article already lives).
        // Result: A link removed, B link still single (no duplicate row).
        $response = $this->callController([
            'itemtype'       => KnowbaseItem::class,
            'items_id'       => $article->getID(),
            'from_parent_id' => $cat_a->getID(),
            'to_parent_id'   => $cat_b->getID(),
        ]);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertFalse($this->hasJunction($article->getID(), $cat_a->getID()));
        $this->assertTrue($this->hasJunction($article->getID(), $cat_b->getID()));

        global $DB;
        $count = count($DB->request([
            'FROM'  => KnowbaseItem_KnowbaseItemCategory::getTable(),
            'WHERE' => [
                'knowbaseitems_id'          => $article->getID(),
                'knowbaseitemcategories_id' => $cat_b->getID(),
            ],
        ]));
        $this->assertSame(1, $count);
    }

    public function testMoveCategoryToNewParent(): void
    {
        $this->login();
        $parent = $this->makeCategory('Parent');
        $child  = $this->makeCategory('Child');

        $response = $this->callController([
            'itemtype'       => KnowbaseItemCategory::class,
            'items_id'       => $child->getID(),
            'from_parent_id' => 0,
            'to_parent_id'   => $parent->getID(),
        ]);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $reloaded = new KnowbaseItemCategory();
        $reloaded->getFromDB($child->getID());
        $this->assertSame($parent->getID(), (int) $reloaded->fields['knowbaseitemcategories_id']);
    }

    public function testMoveCategoryToRootSetsParentToZero(): void
    {
        $this->login();
        $parent = $this->makeCategory('Parent');
        $child  = $this->makeCategory('Child', $parent->getID());

        $response = $this->callController([
            'itemtype'       => KnowbaseItemCategory::class,
            'items_id'       => $child->getID(),
            'from_parent_id' => $parent->getID(),
            'to_parent_id'   => 0,
        ]);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $reloaded = new KnowbaseItemCategory();
        $reloaded->getFromDB($child->getID());
        $this->assertSame(0, (int) $reloaded->fields['knowbaseitemcategories_id']);
    }

    public function testMoveCategoryIntoOwnDescendantReturnsConflict(): void
    {
        $this->login();
        $parent = $this->makeCategory('Parent');
        $child  = $this->makeCategory('Child', $parent->getID());

        // Attempt to move Parent under Child — would create a cycle.
        $response = $this->callController([
            'itemtype'       => KnowbaseItemCategory::class,
            'items_id'       => $parent->getID(),
            'from_parent_id' => 0,
            'to_parent_id'   => $child->getID(),
        ]);

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());

        // Verify the parent was not moved.
        $reloaded = new KnowbaseItemCategory();
        $reloaded->getFromDB($parent->getID());
        $this->assertSame(0, (int) $reloaded->fields['knowbaseitemcategories_id']);
    }

    public function testMoveCategoryIntoItselfIsRejected(): void
    {
        $this->login();
        $category = $this->makeCategory('Self');

        $this->expectException(BadRequestHttpException::class);
        $this->callController([
            'itemtype'       => KnowbaseItemCategory::class,
            'items_id'       => $category->getID(),
            'from_parent_id' => 0,
            'to_parent_id'   => $category->getID(),
        ]);
    }

    public function testInvalidItemtypeIsRejected(): void
    {
        $this->login();

        $this->expectException(BadRequestHttpException::class);
        $this->callController([
            'itemtype'       => 'NotARealClass',
            'items_id'       => 1,
            'from_parent_id' => 0,
            'to_parent_id'   => 0,
        ]);
    }

    public function testMissingKeysAreRejected(): void
    {
        $this->login();

        $this->expectException(BadRequestHttpException::class);
        $this->callController([
            'itemtype' => KnowbaseItem::class,
            'items_id' => 1,
        ]);
    }

    public function testInvalidJsonIsRejected(): void
    {
        $this->login();

        $controller = new ReparentNodeController();
        $request = new Request(content: 'not json');

        $this->expectException(BadRequestHttpException::class);
        $controller->__invoke($request);
    }

    public function testUnknownTargetCategoryReturnsNotFound(): void
    {
        $this->login();
        $article = $this->makeArticle('A');

        $this->expectException(NotFoundHttpException::class);
        $this->callController([
            'itemtype'       => KnowbaseItem::class,
            'items_id'       => $article->getID(),
            'from_parent_id' => 0,
            'to_parent_id'   => 99999,
        ]);
    }

    public function testUnauthorizedUserCannotMoveArticle(): void
    {
        // Set up data as a privileged user.
        $this->login();
        $cat_from = $this->makeCategory('From');
        $cat_to   = $this->makeCategory('To');
        $article  = $this->makeArticle('A', [$cat_from->getID()]);

        // Re-authenticate as a low-privilege user.
        $this->login('normal', 'normal');

        $this->expectException(AccessDeniedHttpException::class);
        $this->callController([
            'itemtype'       => KnowbaseItem::class,
            'items_id'       => $article->getID(),
            'from_parent_id' => $cat_from->getID(),
            'to_parent_id'   => $cat_to->getID(),
        ]);
    }

    public function testUnauthorizedUserCannotMoveCategory(): void
    {
        $this->login();
        $parent = $this->makeCategory('Parent');
        $child  = $this->makeCategory('Child');

        $this->login('normal', 'normal');

        $this->expectException(AccessDeniedHttpException::class);
        $this->callController([
            'itemtype'       => KnowbaseItemCategory::class,
            'items_id'       => $child->getID(),
            'from_parent_id' => 0,
            'to_parent_id'   => $parent->getID(),
        ]);
    }

    public function testNoOpDropOnSameCategoryReturnsSuccess(): void
    {
        $this->login();
        $cat = $this->makeCategory('Same');
        $article = $this->makeArticle('A', [$cat->getID()]);

        $response = $this->callController([
            'itemtype'       => KnowbaseItem::class,
            'items_id'       => $article->getID(),
            'from_parent_id' => $cat->getID(),
            'to_parent_id'   => $cat->getID(),
        ]);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertTrue($this->hasJunction($article->getID(), $cat->getID()));
    }

    public function testMoveArticleFromMultiCategoryToUncategorizedKeepsOtherCategories(): void
    {
        $this->login();
        $cat_a = $this->makeCategory('Multi A');
        $cat_b = $this->makeCategory('Multi B');
        $article = $this->makeArticle('Multi', [$cat_a->getID(), $cat_b->getID()]);

        // Drag the (article, A) occurrence to the synthetic "Uncategorized" bucket.
        // The link to A must be removed; the link to B must remain.
        $response = $this->callController([
            'itemtype'       => KnowbaseItem::class,
            'items_id'       => $article->getID(),
            'from_parent_id' => $cat_a->getID(),
            'to_parent_id'   => 0,
        ]);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertFalse($this->hasJunction($article->getID(), $cat_a->getID()));
        $this->assertTrue($this->hasJunction($article->getID(), $cat_b->getID()));
    }

    public function testMoveArticleFiresKnowbaseItemUpdateHook(): void
    {
        global $PLUGIN_HOOKS;

        $this->login();
        $cat_from = $this->makeCategory('Hook From');
        $cat_to   = $this->makeCategory('Hook To');
        $article  = $this->makeArticle('Hook A', [$cat_from->getID()]);

        // Register a spy on the unconditional CommonDBTM::update() hook to assert
        // that the article reparent goes through $article->update() (rather than
        // direct junction manipulation, which would bypass this hook).
        // Uses the always-active "tester" plugin slot (see GLPITestCase::126-128).
        $captured_ids = [];
        $previous = $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['tester'][KnowbaseItem::class] ?? null;
        $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['tester'][KnowbaseItem::class] = static function ($item) use (&$captured_ids) {
            if ($item instanceof KnowbaseItem) {
                $captured_ids[] = $item->getID();
            }
        };

        try {
            $response = $this->callController([
                'itemtype'       => KnowbaseItem::class,
                'items_id'       => $article->getID(),
                'from_parent_id' => $cat_from->getID(),
                'to_parent_id'   => $cat_to->getID(),
            ]);
        } finally {
            if ($previous === null) {
                unset($PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['tester'][KnowbaseItem::class]);
            } else {
                $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['tester'][KnowbaseItem::class] = $previous;
            }
        }

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertContains($article->getID(), $captured_ids);
    }

    public function testMoveArticleReturnsConflictWhenJunctionMutationFails(): void
    {
        global $PLUGIN_HOOKS;

        $this->login();
        $cat_from = $this->makeCategory('Fail From');
        $cat_to   = $this->makeCategory('Fail To');
        $article  = $this->makeArticle('Fail A', [$cat_from->getID()]);

        // Veto every junction add() so update1NTableData warns but continues,
        // simulating a constraint violation / plugin veto on add. Without the
        // post-update verification, $article->update() returns true and the
        // controller would silently report 204 to the client.
        $previous = $PLUGIN_HOOKS[Hooks::PRE_ITEM_ADD]['tester'][KnowbaseItem_KnowbaseItemCategory::class] ?? null;
        $PLUGIN_HOOKS[Hooks::PRE_ITEM_ADD]['tester'][KnowbaseItem_KnowbaseItemCategory::class] = static function ($item) {
            $item->input = false;
        };

        try {
            // E_USER_WARNING raised by update1NTableData on the vetoed add() must
            // not bubble up as a PHPUnit failure for this scenario.
            set_error_handler(static fn() => true, E_USER_WARNING);

            try {
                $response = $this->callController([
                    'itemtype'       => KnowbaseItem::class,
                    'items_id'       => $article->getID(),
                    'from_parent_id' => $cat_from->getID(),
                    'to_parent_id'   => $cat_to->getID(),
                ]);

                $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
            } finally {
                restore_error_handler();
            }
        } finally {
            if ($previous === null) {
                unset($PLUGIN_HOOKS[Hooks::PRE_ITEM_ADD]['tester'][KnowbaseItem_KnowbaseItemCategory::class]);
            } else {
                $PLUGIN_HOOKS[Hooks::PRE_ITEM_ADD]['tester'][KnowbaseItem_KnowbaseItemCategory::class] = $previous;
            }
        }
    }
}
