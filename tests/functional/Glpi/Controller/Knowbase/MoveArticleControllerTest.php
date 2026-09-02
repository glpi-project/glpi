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

use Glpi\Controller\Knowbase\MoveArticleController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_KnowbaseItem;
use KnowbaseItem_User;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function Safe\json_encode;

class MoveArticleControllerTest extends DbTestCase
{
    private function callController(int $id, int $from_parent_id, int $to_parent_id): Response
    {
        $request = Request::create(
            '/Knowbase/Aside/Article/' . $id . '/Move',
            'POST',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'from_parent_id' => $from_parent_id,
                'to_parent_id'   => $to_parent_id,
            ]),
        );
        return (new MoveArticleController())->__invoke($id, $request);
    }

    /** @param int[] $parents */
    private function makeArticle(array $parents = []): int
    {
        return $this->createItem(KnowbaseItem::class, [
            'name'     => 'Move ' . $this->getUniqueString(),
            'answer'   => '<p>x</p>',
            '_parents' => $parents,
        ])->getID();
    }

    /** Guards the rights tests against passing on the wrong article. */
    private function assertEditable(int $id): void
    {
        $article = new KnowbaseItem();
        $this->assertTrue($article->getFromDB($id));
        $this->assertTrue($article->can($id, UPDATE));
    }

    /**
     * An article the current user may read but not edit, and drops them to the rights
     * that make the difference visible: an FAQ article authored by someone else needs
     * PUBLISHFAQ to be edited, while plain READ is enough to see it.
     *
     * Everything is created before the rights drop, so creation itself stays allowed.
     */
    private function makeUneditableArticle(): int
    {
        $id = $this->createItem(KnowbaseItem::class, [
            'name'     => 'Move ' . $this->getUniqueString(),
            'answer'   => '<p>x</p>',
            'users_id' => getItemByTypeName('User', 'normal', true),
            'is_faq'   => 1,
        ])->getID();
        // Without a grant the article would not even be readable.
        $this->createItem(KnowbaseItem_User::class, [
            'knowbaseitems_id' => $id,
            'users_id'         => Session::getLoginUserID(),
        ]);

        $this->setEntity('_test_root_entity', true);
        $_SESSION['glpiactiveprofile']['knowbase'] = READ | UPDATE;

        // Not vacuous: only editing the article is out of reach.
        $article = new KnowbaseItem();
        $this->assertTrue($article->getFromDB($id));
        $this->assertTrue($article->can($id, READ));

        return $id;
    }

    private function countLink(int $child_id, int $parent_id): int
    {
        return (int) countElementsInTable(KnowbaseItem_KnowbaseItem::getTable(), [
            'knowbaseitems_id'        => $child_id,
            'knowbaseitems_id_parent' => $parent_id,
        ]);
    }

    public function testMoveDetachesSourceAndAttachesTarget(): void
    {
        $this->login();
        $parent_a = $this->makeArticle();
        $parent_b = $this->makeArticle();
        $child    = $this->makeArticle([$parent_a]);

        $this->callController($child, $parent_a, $parent_b);

        $this->assertSame(0, $this->countLink($child, $parent_a));
        $this->assertSame(1, $this->countLink($child, $parent_b));
    }

    public function testMoveLeavesOtherParentsUntouched(): void
    {
        $this->login();
        $parent_a = $this->makeArticle();
        $parent_b = $this->makeArticle();
        $parent_c = $this->makeArticle();
        $child    = $this->makeArticle([$parent_a, $parent_b]);

        $this->callController($child, $parent_a, $parent_c);

        $this->assertSame(0, $this->countLink($child, $parent_a));
        $this->assertSame(1, $this->countLink($child, $parent_b));
        $this->assertSame(1, $this->countLink($child, $parent_c));
    }

    public function testMoveToTheRootLevelIsRejected(): void
    {
        $this->login();
        $parent_a = $this->makeArticle();
        $child    = $this->makeArticle([$parent_a]);

        try {
            $this->callController($child, $parent_a, 0);
            $this->fail('The root level is not a destination.');
        } catch (BadRequestHttpException) {
            // Expected: the root article is the base of the tree, nothing sits beside it.
        }

        $this->assertSame(1, $this->countLink($child, $parent_a));
    }

    public function testMoveFromRootOnlyCreatesTheTargetEdge(): void
    {
        $this->login();
        $parent_a = $this->makeArticle();
        $child    = $this->makeArticle();

        $this->callController($child, 0, $parent_a);

        $this->assertSame(1, $this->countLink($child, $parent_a));
    }

    public function testDroppingOnAnExistingParentMergesOccurrences(): void
    {
        $this->login();
        $parent_a = $this->makeArticle();
        $parent_b = $this->makeArticle();
        $child    = $this->makeArticle([$parent_a, $parent_b]);

        // Target edge already exists: the unicity constraint must not surface.
        $response = $this->callController($child, $parent_a, $parent_b);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(0, $this->countLink($child, $parent_a));
        $this->assertSame(1, $this->countLink($child, $parent_b));
    }

    public function testCycleIsRejectedAndSourceEdgeIsKept(): void
    {
        $this->login();
        $grand_parent = $this->makeArticle();
        $parent       = $this->makeArticle([$grand_parent]);
        $child        = $this->makeArticle([$parent]);

        try {
            $this->callController($grand_parent, 0, $child);
        } catch (BadRequestHttpException) {
            // Expected: the model refuses a link that would create a cycle.
        }

        $this->assertSame(0, $this->countLink($grand_parent, $child));
        $this->assertSame(1, $this->countLink($parent, $grand_parent));
    }

    public function testSelfParentingIsRejected(): void
    {
        $this->login();
        $article = $this->makeArticle();

        $this->expectException(BadRequestHttpException::class);
        $this->callController($article, 0, $article);
    }

    public function testMoveIsDeniedWithoutUpdateRightOnTheArticle(): void
    {
        $this->login();
        $parent_a = $this->makeArticle();
        $parent_b = $this->makeArticle();
        $child    = $this->makeArticle([$parent_a]);

        // Drop the knowbase UPDATE right, keeping READ so the article loads.
        $this->setEntity('_test_root_entity', true);
        $_SESSION['glpiactiveprofile']['knowbase'] = READ;

        $this->expectException(AccessDeniedHttpException::class);
        $this->callController($child, $parent_a, $parent_b);
    }

    public function testMoveOntoAnUnresolvableTargetIsDenied(): void
    {
        $this->login();
        $child = $this->makeArticle();

        // A target id that does not resolve must not be a valid drop destination.
        $this->expectException(NotFoundHttpException::class);
        $this->callController($child, 0, 999999999);
    }

    public function testMoveOntoATargetTheUserCannotEditIsDenied(): void
    {
        $this->login();
        $child  = $this->makeArticle();
        $target = $this->makeUneditableArticle();
        $this->assertEditable($child);

        // Gaining a child is editing the parent, as the model's own rules have it.
        $this->expectException(AccessDeniedHttpException::class);
        $this->callController($child, 0, $target);
    }

    public function testDetachingFromASourceTheUserCannotEditIsDenied(): void
    {
        $this->login();
        $source = $this->makeUneditableArticle();
        $child  = $this->makeArticle([$source]);
        $target = $this->makeArticle();
        $this->assertEditable($child);
        $this->assertEditable($target);

        // Losing a child is editing the parent too.
        $this->expectException(AccessDeniedHttpException::class);
        $this->callController($child, $source, $target);
    }

    public function testDetachingFromAnUnresolvableParentIsDenied(): void
    {
        $this->login();
        $target = $this->makeArticle();
        $child  = $this->makeArticle();

        // deleteByCriteria() reports success on zero rows, so the source needs
        // its own check or an unreadable parent would be severed silently.
        $this->expectException(NotFoundHttpException::class);
        $this->callController($child, 999999999, $target);
    }

    public function testMoveAcrossIncoherentEntitiesIsRejected(): void
    {
        $this->login();
        $entity_a = getItemByTypeName('Entity', '_test_child_1', true);
        $entity_b = getItemByTypeName('Entity', '_test_child_2', true);

        $child = $this->createItem(KnowbaseItem::class, [
            'name'        => 'Move ' . $this->getUniqueString(),
            'answer'      => '<p>x</p>',
            'entities_id' => $entity_a,
        ])->getID();
        $target = $this->createItem(KnowbaseItem::class, [
            'name'        => 'Move ' . $this->getUniqueString(),
            'answer'      => '<p>x</p>',
            'entities_id' => $entity_b,
        ])->getID();

        $this->expectException(AccessDeniedHttpException::class);
        $this->callController($child, 0, $target);
    }

    public function testStaleSourceParentIsRejected(): void
    {
        $this->login();
        $parent_a = $this->makeArticle();
        $stranger = $this->makeArticle();
        $target   = $this->makeArticle();
        $child    = $this->makeArticle([$parent_a]);

        try {
            // The aside was rendered from a state where $stranger held the article.
            $this->callController($child, $stranger, $target);
            $this->fail('A source that holds no edge must be rejected.');
        } catch (BadRequestHttpException) {
            // Expected: attaching $target anyway would add a parent instead of moving one.
        }

        $this->assertSame(0, $this->countLink($child, $target));
        $this->assertSame(1, $this->countLink($child, $parent_a));
    }

    public function testUnrelatedSessionErrorsSurviveARejectedMove(): void
    {
        $this->login();
        $parent = $this->makeArticle();
        $child  = $this->makeArticle([$parent]);

        Session::addMessageAfterRedirect('Posted by something else', false, ERROR);

        try {
            // Cycle: the model refuses the link and posts its own message next to that one.
            $this->callController($parent, 0, $child);
            $this->fail('A cycle must be rejected.');
        } catch (BadRequestHttpException) {
            // Expected.
        }

        $this->hasSessionMessages(ERROR, ['Posted by something else']);
    }

    public function testMissingPayloadKeysAreRejected(): void
    {
        $this->login();
        $article = $this->makeArticle();

        $request = Request::create(
            '/Knowbase/Aside/Article/' . $article . '/Move',
            'POST',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([]),
        );

        $this->expectException(BadRequestHttpException::class);
        (new MoveArticleController())->__invoke($article, $request);
    }
}
