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

    public function testUnresolvableParentIsRejected(): void
    {
        $this->login();

        // Same answer as for a hidden parent, not to disclose which ids exist.
        $this->expectException(AccessDeniedHttpException::class);
        $request = new Request(content: json_encode([
            'name' => 'Unresolvable parent test',
            'knowbaseitems_id_parent' => 999999,
        ]));
        (new CreateArticleController())($request);
    }

    public function testParentTheUserCannotEditIsRejected(): void
    {
        $this->login();
        // Readable but not editable: an FAQ article of someone else needs PUBLISHFAQ.
        $parent = $this->createItem(KnowbaseItem::class, [
            'name'        => 'Uneditable parent',
            'answer'      => '',
            'entities_id' => Session::getActiveEntity(),
            'users_id'    => getItemByTypeName('User', 'normal', true),
            'is_faq'      => 1,
        ]);
        $this->createItem(KnowbaseItem_User::class, [
            'knowbaseitems_id' => $parent->getID(),
            'users_id'         => Session::getLoginUserID(),
        ]);
        $this->setEntity('_test_root_entity', true);
        $_SESSION['glpiactiveprofile']['knowbase'] = READ | CREATE | UPDATE;
        // Reloaded: the grant above is not in the loaded visibility rules.
        $this->assertTrue($parent->getFromDB($parent->getID()));
        $this->assertTrue($parent->can($parent->getID(), READ));
        $this->assertFalse($parent->can($parent->getID(), UPDATE));

        $this->expectException(AccessDeniedHttpException::class);
        $request = new Request(content: json_encode([
            'name' => 'Uneditable parent test',
            'knowbaseitems_id_parent' => $parent->getID(),
        ]));
        (new CreateArticleController())($request);
    }

    public function testParentInAnIncoherentEntityIsRejected(): void
    {
        $this->login();
        $parent = $this->createItem(KnowbaseItem::class, [
            'name'        => 'Parent in a child entity',
            'answer'      => '',
            'entities_id' => getItemByTypeName('Entity', '_test_child_1', true),
        ]);
        // Editable, but the new article lands in the non-recursive parent entity.
        $this->setEntity('_test_root_entity', true);
        $this->assertTrue($parent->can($parent->getID(), UPDATE));

        $this->expectException(AccessDeniedHttpException::class);
        $request = new Request(content: json_encode([
            'name' => 'Incoherent entity test',
            'knowbaseitems_id_parent' => $parent->getID(),
        ]));
        (new CreateArticleController())($request);
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
