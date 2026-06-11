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

namespace tests\unit\Glpi\Controller\Knowbase;

use Entity;
use Glpi\Controller\Knowbase\CreateCategoryFromAsideController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Tests\DbTestCase;
use KnowbaseItemCategory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use function Safe\json_decode;

final class CreateCategoryFromAsideControllerTest extends DbTestCase
{
    public function testPostCreatesCategoryUnderParent(): void
    {
        $this->login();
        $parent = $this->createItem(KnowbaseItemCategory::class, [
            'name'                      => 'Hardware',
            'knowbaseitemcategories_id' => 0,
            'entities_id'               => $this->getTestRootEntity(only_id: true),
            'is_recursive'              => 1,
        ]);

        $response = $this->postCreate([
            'name'                      => 'Laptops',
            'knowbaseitemcategories_id' => (string) $parent->getID(),
        ]);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        $this->assertSame('Laptops', $body['name']);
        $this->assertSame($parent->getID(), $body['parent_id']);
        $this->assertGreaterThan(0, $body['id']);

        $created = new KnowbaseItemCategory();
        $this->assertTrue($created->getFromDB($body['id']));
        $this->assertSame('Laptops', $created->fields['name']);
        $this->assertSame($parent->getID(), (int) $created->fields['knowbaseitemcategories_id']);
    }

    public function testPostInheritsEntityFromParent(): void
    {
        $this->login();
        /** @var int $root_id */
        $root_id = $this->getTestRootEntity(only_id: true);
        $child_entity_id = $this->createItem(Entity::class, [
            'name'        => 'Child for category test',
            'entities_id' => $root_id,
        ])->getID();
        $parent = $this->createItem(KnowbaseItemCategory::class, [
            'name'                      => 'Scoped',
            'knowbaseitemcategories_id' => 0,
            'entities_id'               => $child_entity_id,
            'is_recursive'              => 0,
        ]);

        $this->setEntity($root_id, true);

        $response = $this->postCreate([
            'name'                      => 'Scoped child',
            'knowbaseitemcategories_id' => (string) $parent->getID(),
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        $created = new KnowbaseItemCategory();
        $this->assertTrue($created->getFromDB($body['id']));
        $this->assertSame($child_entity_id, (int) $created->fields['entities_id']);
    }

    public function testPostWithRootParentUsesActiveEntity(): void
    {
        $this->login();
        /** @var int $root_id */
        $root_id = $this->getTestRootEntity(only_id: true);
        $this->setEntity($root_id, false);

        $response = $this->postCreate([
            'name'                      => 'Top level',
            'knowbaseitemcategories_id' => '0',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        $created = new KnowbaseItemCategory();
        $this->assertTrue($created->getFromDB($body['id']));
        $this->assertSame($root_id, (int) $created->fields['entities_id']);
    }

    public function testPostRootCategoryFollowsRecursiveSessionContext(): void
    {
        $this->login();
        /** @var int $root_id */
        $root_id = $this->getTestRootEntity(only_id: true);
        $this->setEntity($root_id, true);

        $response = $this->postCreate([
            'name'                      => 'Recursive root',
            'knowbaseitemcategories_id' => '0',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        $created = new KnowbaseItemCategory();
        $this->assertTrue($created->getFromDB($body['id']));
        $this->assertSame(1, (int) $created->fields['is_recursive']);
    }

    public function testPostRootCategoryStaysLocalInNonRecursiveContext(): void
    {
        $this->login();
        /** @var int $root_id */
        $root_id = $this->getTestRootEntity(only_id: true);
        $this->setEntity($root_id, false);

        $response = $this->postCreate([
            'name'                      => 'Local root',
            'knowbaseitemcategories_id' => '0',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        $created = new KnowbaseItemCategory();
        $this->assertTrue($created->getFromDB($body['id']));
        $this->assertSame(0, (int) $created->fields['is_recursive']);
    }

    public function testPostSubCategoryInheritsParentRecursivity(): void
    {
        $this->login();
        /** @var int $root_id */
        $root_id = $this->getTestRootEntity(only_id: true);
        $parent = $this->createItem(KnowbaseItemCategory::class, [
            'name'                      => 'Recursive parent',
            'knowbaseitemcategories_id' => 0,
            'entities_id'               => $root_id,
            'is_recursive'              => 1,
        ]);
        $this->setEntity($root_id, false);

        $response = $this->postCreate([
            'name'                      => 'Inheriting child',
            'knowbaseitemcategories_id' => (string) $parent->getID(),
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        $created = new KnowbaseItemCategory();
        $this->assertTrue($created->getFromDB($body['id']));
        $this->assertSame(1, (int) $created->fields['is_recursive']);
    }

    public function testPostEmptyNameReturns422(): void
    {
        $this->login();

        $response = $this->postCreate([
            'name'                      => '   ',
            'knowbaseitemcategories_id' => '0',
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        $this->assertArrayHasKey('name', $body['errors']);
    }

    public function testPostUnknownParentRaisesBadRequest(): void
    {
        $this->login();

        $this->expectException(BadRequestHttpException::class);
        $this->postCreate([
            'name'                      => 'Orphan',
            'knowbaseitemcategories_id' => '999999',
        ]);
    }

    public function testPostWithoutCreateRightRaisesAccessDenied(): void
    {
        $this->removeRightFromProfile('super-admin', 'knowbasecategory', CREATE);
        $this->login();

        $this->expectException(AccessDeniedHttpException::class);
        $this->postCreate([
            'name'                      => 'Denied',
            'knowbaseitemcategories_id' => '0',
        ]);
    }

    public function testGetEditFormRendersCommentAndIllustration(): void
    {
        $this->login();
        $category = $this->createItem(KnowbaseItemCategory::class, [
            'name'                      => 'Hardware',
            'knowbaseitemcategories_id' => 0,
            'entities_id'               => $this->getTestRootEntity(only_id: true),
            'is_recursive'              => 1,
            'comment'                   => 'Existing description',
        ]);

        $response = (new CreateCategoryFromAsideController())->editForm($category->getID());

        $this->assertSame(200, $response->getStatusCode());
        $html = (string) $response->getContent();
        $this->assertStringContainsString('Existing description', $html);
        $this->assertStringContainsString('data-glpi-kb-category-edit-save', $html);
        $this->assertStringContainsString('data-glpi-illustration-picker', $html);
    }

    public function testGetEditFormDefaultsEmptyIllustrationToFallbackIcon(): void
    {
        $this->login();
        $category = $this->createItem(KnowbaseItemCategory::class, [
            'name'                      => 'No illustration',
            'knowbaseitemcategories_id' => 0,
            'entities_id'               => $this->getTestRootEntity(only_id: true),
            'is_recursive'              => 1,
            'illustration'              => '',
        ]);

        $response = (new CreateCategoryFromAsideController())->editForm($category->getID());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('value="kb-faq"', (string) $response->getContent());
    }

    public function testGetEditFormUnknownCategoryRaisesBadRequest(): void
    {
        $this->login();

        $this->expectException(BadRequestHttpException::class);
        (new CreateCategoryFromAsideController())->editForm(999999);
    }

    public function testGetEditFormWithoutUpdateRightRaisesAccessDenied(): void
    {
        $this->removeRightFromProfile('super-admin', 'knowbasecategory', UPDATE);
        $this->login();
        $category = $this->createItem(KnowbaseItemCategory::class, [
            'name'                      => 'Locked',
            'knowbaseitemcategories_id' => 0,
            'entities_id'               => $this->getTestRootEntity(only_id: true),
            'is_recursive'              => 1,
        ]);

        $this->expectException(AccessDeniedHttpException::class);
        (new CreateCategoryFromAsideController())->editForm($category->getID());
    }

    public function testPostUpdateChangesIllustrationAndComment(): void
    {
        $this->login();
        $category = $this->createItem(KnowbaseItemCategory::class, [
            'name'                      => 'Hardware',
            'knowbaseitemcategories_id' => 0,
            'entities_id'               => $this->getTestRootEntity(only_id: true),
            'is_recursive'              => 1,
            'illustration'              => 'kb-faq',
        ]);

        $response = $this->postUpdate($category->getID(), [
            'illustration' => 'browse-kb',
            'comment'      => 'Updated description',
        ]);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        $this->assertSame('browse-kb', $body['illustration']);
        $this->assertSame('Updated description', $body['comment']);

        $updated = new KnowbaseItemCategory();
        $this->assertTrue($updated->getFromDB($category->getID()));
        $this->assertSame('browse-kb', $updated->fields['illustration']);
        $this->assertSame('Updated description', $updated->fields['comment']);
    }

    public function testPostUpdateUnknownCategoryRaisesBadRequest(): void
    {
        $this->login();

        $this->expectException(BadRequestHttpException::class);
        $this->postUpdate(999999, ['comment' => 'x']);
    }

    public function testPostUpdateWithoutUpdateRightRaisesAccessDenied(): void
    {
        $this->removeRightFromProfile('super-admin', 'knowbasecategory', UPDATE);
        $this->login();
        $category = $this->createItem(KnowbaseItemCategory::class, [
            'name'                      => 'Locked',
            'knowbaseitemcategories_id' => 0,
            'entities_id'               => $this->getTestRootEntity(only_id: true),
            'is_recursive'              => 1,
        ]);

        $this->expectException(AccessDeniedHttpException::class);
        $this->postUpdate($category->getID(), ['comment' => 'x']);
    }

    /**
     * @param array<string, string> $form_fields
     */
    private function postCreate(array $form_fields): JsonResponse
    {
        $request = new Request(request: $form_fields);
        $request->setMethod('POST');
        return (new CreateCategoryFromAsideController())->create($request);
    }

    /**
     * @param array<string, string> $form_fields
     */
    private function postUpdate(int $id, array $form_fields): JsonResponse
    {
        $request = new Request(request: $form_fields);
        $request->setMethod('POST');
        return (new CreateCategoryFromAsideController())->update($request, $id);
    }
}
