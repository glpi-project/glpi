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

use Glpi\Controller\Knowbase\CreateArticleController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_User;
use Session;
use Symfony\Component\HttpFoundation\Request;

use function Safe\json_decode;
use function Safe\json_encode;

final class CreateArticleControllerTest extends DbTestCase
{
    public function testCreatesArticleLinkedToParent(): void
    {
        $this->login();
        $parent = $this->createItem(KnowbaseItem::class, [
            'name'        => 'Parent article',
            'answer'      => '',
            'entities_id' => Session::getActiveEntity(),
        ]);

        $request = new Request(content: json_encode([
            'name' => 'New from inline input',
            'knowbaseitems_id_parent' => $parent->getID(),
        ]));
        $response = (new CreateArticleController())($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('url', $data);

        $item = new KnowbaseItem();
        $this->assertTrue($item->getFromDB($data['id']));
        $this->assertSame('New from inline input', $item->fields['name']);
        $this->assertSame('', $item->fields['answer']);

        $links = (new \KnowbaseItem_KnowbaseItem())->find([
            'knowbaseitems_id' => $data['id'],
        ]);
        $this->assertCount(1, $links);
        $link = array_pop($links);
        $this->assertSame($parent->getID(), (int) $link['knowbaseitems_id_parent']);
    }

    public function testCreatedArticleIsScopedToActiveEntity(): void
    {
        $this->login();
        $child_entity_id = getItemByTypeName('Entity', '_test_child_1', true);
        $this->setEntity($child_entity_id, false);

        $request = new Request(content: json_encode(['name' => 'Entity scoped test']));
        $response = (new CreateArticleController())($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $item = new KnowbaseItem();
        $this->assertTrue($item->getFromDB($data['id']));
        $this->assertSame($child_entity_id, (int) $item->fields['entities_id']);
        $this->assertSame(0, (int) $item->fields['is_recursive']);
    }

    public function testCreatesArticleUnderRootWhenNoParentGiven(): void
    {
        $this->login();

        $request = new Request(content: json_encode(['name' => 'No parent']));
        $response = (new CreateArticleController())($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame(
            [KnowbaseItem::getRootId()],
            $this->getParentIds((int) $data['id']),
        );
    }

    public function testEmptyNameReturns400(): void
    {
        $this->login();

        $this->expectException(BadRequestHttpException::class);
        $request = new Request(content: json_encode(['name' => '   ']));
        (new CreateArticleController())($request);
    }

    public function testNonArrayJsonBodyIsRejected(): void
    {
        $this->login();
        $this->expectException(BadRequestHttpException::class);
        $request = new Request(content: json_encode(42));
        (new CreateArticleController())($request);
    }

    public function testUnreadableParentIsSilentlyDropped(): void
    {
        $this->login();

        $request = new Request(content: json_encode([
            'name' => 'Unreadable parent test',
            'knowbaseitems_id_parent' => 999999,
        ]));
        $response = (new CreateArticleController())($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        // The unreadable parent is dropped, so the article falls back to the
        // root article like any other parentless creation.
        $this->assertSame(
            [KnowbaseItem::getRootId()],
            $this->getParentIds((int) $data['id']),
        );
    }

    public function testUneditableParentIsDenied(): void
    {
        $this->login();
        $parent = $this->createItem(KnowbaseItem::class, [
            'name'        => 'Readable, not editable',
            'answer'      => '',
            'entities_id' => Session::getActiveEntity(),
        ]);
        $_SESSION['glpiactiveprofile']['knowbase'] = READ | CREATE;
        $count_before = countElementsInTable(KnowbaseItem::getTable());

        try {
            (new CreateArticleController())(new Request(content: json_encode([
                'name' => 'Should be denied',
                'knowbaseitems_id_parent' => $parent->getID(),
            ])));
            $this->fail('An uneditable parent must be denied.');
        } catch (AccessDeniedHttpException) {
            $this->assertSame($count_before, countElementsInTable(KnowbaseItem::getTable()));
        }
    }

    public function testFaqParentWithoutPublishFaqIsDenied(): void
    {
        $this->login();
        // An FAQ article by someone else needs PUBLISHFAQ to be edited, UPDATE is not enough.
        $parent = $this->createItem(KnowbaseItem::class, [
            'name'        => 'FAQ parent',
            'answer'      => '',
            'entities_id' => Session::getActiveEntity(),
            'users_id'    => getItemByTypeName('User', 'normal', true),
            'is_faq'      => 1,
        ]);
        $this->createItem(KnowbaseItem_User::class, [
            'knowbaseitems_id' => $parent->getID(),
            'users_id'         => Session::getLoginUserID(),
        ]);
        $_SESSION['glpiactiveprofile']['knowbase'] = READ | UPDATE | CREATE;
        // Fresh instance: visibility targets are loaded with the article.
        $this->assertTrue((new KnowbaseItem())->can($parent->getID(), READ));
        $count_before = countElementsInTable(KnowbaseItem::getTable());

        try {
            (new CreateArticleController())(new Request(content: json_encode([
                'name' => 'Should be denied',
                'knowbaseitems_id_parent' => $parent->getID(),
            ])));
            $this->fail('An FAQ parent the user cannot publish must be denied.');
        } catch (AccessDeniedHttpException) {
            $this->assertSame($count_before, countElementsInTable(KnowbaseItem::getTable()));
        }
    }

    public function testRootParentIsAllowedWithoutUpdateRight(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile']['knowbase'] = READ | CREATE;

        $response = (new CreateArticleController())(new Request(content: json_encode([
            'name' => 'Under the root',
            'knowbaseitems_id_parent' => KnowbaseItem::getRootId(),
        ])));

        $data = json_decode($response->getContent(), true);
        $this->assertSame(
            [KnowbaseItem::getRootId()],
            $this->getParentIds((int) $data['id']),
        );
    }

    public function testChildInheritsParentEntity(): void
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);
        $child_entity_id = getItemByTypeName('Entity', '_test_child_1', true);
        $parent = $this->createItem(KnowbaseItem::class, [
            'name'         => 'Parent in a sub-entity',
            'answer'       => '',
            'entities_id'  => $child_entity_id,
            'is_recursive' => 0,
        ]);

        $response = (new CreateArticleController())(new Request(content: json_encode([
            'name' => 'Child of a sub-entity article',
            'knowbaseitems_id_parent' => $parent->getID(),
        ])));

        $data = json_decode($response->getContent(), true);
        $item = new KnowbaseItem();
        $this->assertTrue($item->getFromDB($data['id']));
        $this->assertSame($child_entity_id, (int) $item->fields['entities_id']);
        $this->assertSame([$parent->getID()], $this->getParentIds((int) $data['id']));
    }

    public function testChildUnderRecursiveParentKeepsActiveEntity(): void
    {
        $this->login();
        $child_entity_id = getItemByTypeName('Entity', '_test_child_1', true);
        $parent = $this->createItem(KnowbaseItem::class, [
            'name'         => 'Recursive parent',
            'answer'       => '',
            'entities_id'  => getItemByTypeName('Entity', '_test_root_entity', true),
            'is_recursive' => 1,
        ]);
        $this->setEntity($child_entity_id, false);

        $response = (new CreateArticleController())(new Request(content: json_encode([
            'name' => 'Child of a recursive article',
            'knowbaseitems_id_parent' => $parent->getID(),
        ])));

        $data = json_decode($response->getContent(), true);
        $item = new KnowbaseItem();
        $this->assertTrue($item->getFromDB($data['id']));
        $this->assertSame($child_entity_id, (int) $item->fields['entities_id']);
        $this->assertSame([$parent->getID()], $this->getParentIds((int) $data['id']));
    }

    public function testParentInInaccessibleEntityIsDenied(): void
    {
        $this->login();
        $parent = $this->createItem(KnowbaseItem::class, [
            'name'         => 'Parent in another entity',
            'answer'       => '',
            'entities_id'  => getItemByTypeName('Entity', '_test_child_2', true),
            'is_recursive' => 0,
        ]);
        $this->setEntity('_test_child_1', false);

        $this->expectException(AccessDeniedHttpException::class);
        (new CreateArticleController())(new Request(content: json_encode([
            'name' => 'Should be denied',
            'knowbaseitems_id_parent' => $parent->getID(),
        ])));
    }

    /**
     * @return int[]
     */
    private function getParentIds(int $article_id): array
    {
        $links = (new \KnowbaseItem_KnowbaseItem())->find([
            'knowbaseitems_id' => $article_id,
        ]);

        return array_map('intval', array_column($links, 'knowbaseitems_id_parent'));
    }

    public function testUserWithoutCreateRightIsDenied(): void
    {
        $this->login('normal', 'normal');

        $this->expectException(AccessDeniedHttpException::class);
        $request = new Request(content: json_encode(['name' => 'Should be denied']));
        (new CreateArticleController())($request);
    }
}
