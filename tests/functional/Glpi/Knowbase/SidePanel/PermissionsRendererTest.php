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

namespace tests\units\Glpi\Knowbase\SidePanel;

use Entity;
use Entity_KnowbaseItem;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Knowbase\SidePanel\PermissionsRenderer;
use Glpi\Tests\DbTestCase;
use Group;
use Group_KnowbaseItem;
use KnowbaseItem;
use KnowbaseItem_Profile;
use KnowbaseItem_User;
use Profile;
use Symfony\Component\DomCrawler\Crawler;
use User;

final class PermissionsRendererTest extends DbTestCase
{
    public function testEmptyState(): void
    {
        // Arrange: create a KB article without permissions
        $kb = $this->createArticle();

        // Act: render permission content
        $permissions = $this->renderPermissions($kb);

        // Assert: empty state label should be printed
        $this->assertCount(1, $this->getEmptyPermissionNode($permissions));
    }

    public function testWithUser(): void
    {
        // Arrange: create a KB article visible for a user
        $kb = $this->createArticle();
        $this->addUserVisiblity($kb, getItemByTypeName(User::class, "glpi"));

        // Act: render permission content
        $permissions = $this->renderPermissions($kb);

        // Assert: an entry should be visible with the user name
        $this->assertCount(0, $this->getEmptyPermissionNode($permissions));

        $entries = $this->getEntriesNodes($permissions);
        $this->assertCount(1, $entries);

        $entry = $entries->eq(0);
        $this->assertEquals("glpi", $this->getEntryLabel($entry)->text());
        $this->assertCount(0, $this->getEntryContext($entry));
    }

    public function testWithGroup(): void
    {
        // Arrange: create a KB article visible for a group
        $kb = $this->createArticle();
        $this->addGroupVisiblity($kb, getItemByTypeName(
            Group::class,
            "_test_group_1",
        ));

        // Act: render permission content
        $permissions = $this->renderPermissions($kb);

        // Assert: an entry should be visible with the group name
        $this->assertCount(0, $this->getEmptyPermissionNode($permissions));

        $entries = $this->getEntriesNodes($permissions);
        $this->assertCount(1, $entries);

        $entry = $entries->eq(0);
        $this->assertEquals("_test_group_1", $this->getEntryLabel($entry)->text());
        $this->assertCount(0, $this->getEntryContext($entry));
    }

    public function testWithGroupAndContext1(): void
    {
        // Arrange: create a KB article visible for a group on a target entity
        $this->login();
        $kb = $this->createArticle();
        $this->addGroupVisiblity(
            article: $kb,
            group: getItemByTypeName(Group::class, "_test_group_1"),
            entity: getItemByTypeName(Entity::class, "_test_root_entity"),
        );

        // Act: render permission content
        $permissions = $this->renderPermissions($kb);

        // Assert: an entry should be visible with the group name
        $this->assertCount(0, $this->getEmptyPermissionNode($permissions));

        $entries = $this->getEntriesNodes($permissions);
        $this->assertCount(1, $entries);

        $entry = $entries->eq(0);
        $this->assertEquals("_test_group_1", $this->getEntryLabel($entry)->text());
        $this->assertEquals(
            "Root entity > _test_root_entity (recursive)",
            $this->getEntryContext($entry)->text()
        );
    }

    public function testWithGroupAndContext2(): void
    {
        // Arrange: create a KB article visible for a group on a target entity,
        // with recursion disabled.
        $this->login();
        $kb = $this->createArticle();
        $this->addGroupVisiblity(
            article: $kb,
            group: getItemByTypeName(Group::class, "_test_group_1"),
            entity: getItemByTypeName(Entity::class, "_test_root_entity"),
            is_recursive: false,
        );

        // Act: render permission content
        $permissions = $this->renderPermissions($kb);

        // Assert: an entry should be visible with the group name
        $this->assertCount(0, $this->getEmptyPermissionNode($permissions));

        $entries = $this->getEntriesNodes($permissions);
        $this->assertCount(1, $entries);

        $entry = $entries->eq(0);
        $this->assertEquals("_test_group_1", $this->getEntryLabel($entry)->text());
        $this->assertEquals(
            "Root entity > _test_root_entity",
            $this->getEntryContext($entry)->text()
        );
    }

    public function testWithEntity(): void
    {
        // Arrange: create a KB article visible for a group on a target entity,
        // with recursion disabled.
        $this->login();
        $kb = $this->createArticle();
        $this->addEntityVisiblity(
            article: $kb,
            entity: getItemByTypeName(Entity::class, "_test_root_entity"),
            is_recursive: false,
        );

        // Act: render permission content
        $permissions = $this->renderPermissions($kb);

        // Assert: an entry should be visible with the entity name
        $this->assertCount(0, $this->getEmptyPermissionNode($permissions));

        $entries = $this->getEntriesNodes($permissions);
        $this->assertCount(1, $entries);

        $entry = $entries->eq(0);
        $this->assertEquals(
            "Root entity > _test_root_entity",
            $this->getEntryLabel($entry)->text()
        );
    }

    public function testWithEntityRecursive(): void
    {
        // Arrange: create a KB article visible for a group on a target entity,
        // with recursion disabled.
        $this->login();
        $kb = $this->createArticle();
        $this->addEntityVisiblity(
            article: $kb,
            entity: getItemByTypeName(Entity::class, "_test_root_entity"),
            is_recursive: true,
        );

        // Act: render permission content
        $permissions = $this->renderPermissions($kb);

        // Assert: an entry should be visible with the entity name
        $this->assertCount(0, $this->getEmptyPermissionNode($permissions));

        $entries = $this->getEntriesNodes($permissions);
        $this->assertCount(1, $entries);

        $entry = $entries->eq(0);
        $this->assertEquals(
            "Root entity > _test_root_entity (recursive)",
            $this->getEntryLabel($entry)->text()
        );
    }

    public function testWithProfile(): void
    {
        // Arrange: create a KB article visible for a profile
        $kb = $this->createArticle();
        $this->addProfileVisiblity($kb, getItemByTypeName(
            Profile::class,
            "Admin",
        ));

        // Act: render permission content
        $permissions = $this->renderPermissions($kb);

        // Assert: an entry should be visible with the profile name
        $this->assertCount(0, $this->getEmptyPermissionNode($permissions));

        $entries = $this->getEntriesNodes($permissions);
        $this->assertCount(1, $entries);

        $entry = $entries->eq(0);
        $this->assertEquals("Admin", $this->getEntryLabel($entry)->text());
        $this->assertCount(0, $this->getEntryContext($entry));
    }

    public function testWithProfileAndContext1(): void
    {
        // Arrange: create a KB article visible for a profile on a target entity
        $this->login();
        $kb = $this->createArticle();
        $this->addProfileVisiblity(
            article: $kb,
            profile: getItemByTypeName(Profile::class, "Technician"),
            entity: getItemByTypeName(Entity::class, "_test_root_entity"),
        );

        // Act: render permission content
        $permissions = $this->renderPermissions($kb);

        // Assert: an entry should be visible with the profile name
        $this->assertCount(0, $this->getEmptyPermissionNode($permissions));

        $entries = $this->getEntriesNodes($permissions);
        $this->assertCount(1, $entries);

        $entry = $entries->eq(0);
        $this->assertEquals("Technician", $this->getEntryLabel($entry)->text());
        $this->assertEquals(
            "Root entity > _test_root_entity (recursive)",
            $this->getEntryContext($entry)->text()
        );
    }

    public function testWithProfileAndContext2(): void
    {
        // Arrange: create a KB article visible for a profile on a target entity,
        // with recursion disabled.
        $this->login();
        $kb = $this->createArticle();
        $this->addProfileVisiblity(
            article: $kb,
            profile: getItemByTypeName(Profile::class, "Observer"),
            entity: getItemByTypeName(Entity::class, "_test_root_entity"),
            is_recursive: false,
        );

        // Act: render permission content
        $permissions = $this->renderPermissions($kb);

        // Assert: an entry should be visible with the profile name
        $this->assertCount(0, $this->getEmptyPermissionNode($permissions));

        $entries = $this->getEntriesNodes($permissions);
        $this->assertCount(1, $entries);

        $entry = $entries->eq(0);
        $this->assertEquals("Observer", $this->getEntryLabel($entry)->text());
        $this->assertEquals(
            "Root entity > _test_root_entity",
            $this->getEntryContext($entry)->text()
        );
    }

    private function renderPermissions(KnowbaseItem $kb): Crawler
    {
        $permissions = new PermissionsRenderer();
        $html = TemplateRenderer::getInstance()->render(
            $permissions->getTemplate(),
            $permissions->getParams($kb),
        );

        return new Crawler($html);
    }

    private function getEmptyPermissionNode(Crawler $parent): Crawler
    {
        return $parent->filter('[data-testid="empty-permissions"]');
    }

    private function getEntriesNodes(Crawler $parent): Crawler
    {
        return $parent->filter('[data-testid="permission-entry"]');
    }

    private function getEntryLabel(Crawler $parent): Crawler
    {
        return $parent->filter('[data-testid="entry-label"]');
    }

    private function getEntryContext(Crawler $parent): Crawler
    {
        return $parent->filter('[data-testid="entry-context"]');
    }

    private function createArticle(
        string $name = 'My article',
        string $content = 'My content',
    ): KnowbaseItem {
        return $this->createItem(KnowbaseItem::class, [
            'name' => $name,
            'answer' => $content,
        ]);
    }

    private function addUserVisiblity(
        KnowbaseItem $article,
        User $user,
    ): void {
        $this->createItem(KnowbaseItem_User::class, [
            'knowbaseitems_id' => $article->getID(),
            'users_id' => $user->getID(),
        ]);
    }

    private function addGroupVisiblity(
        KnowbaseItem $article,
        Group $group,
        ?Entity $entity = null,
        bool $is_recursive = true,
    ): void {
        if ($entity === null) {
            $entity = getItemByTypeName(Entity::class, "Root entity");
        }

        $this->createItem(Group_KnowbaseItem::class, [
            'knowbaseitems_id' => $article->getID(),
            'groups_id' => $group->getID(),
            'entities_id' => $entity->getID(),
            'is_recursive' => $is_recursive,
        ]);
    }

    private function addEntityVisiblity(
        KnowbaseItem $article,
        Entity $entity,
        bool $is_recursive,
    ): void {
        $this->createItem(Entity_KnowbaseItem::class, [
            'knowbaseitems_id' => $article->getID(),
            'entities_id' => $entity->getID(),
            'is_recursive' => $is_recursive,
        ]);
    }

    private function addProfileVisiblity(
        KnowbaseItem $article,
        Profile $profile,
        ?Entity $entity = null,
        bool $is_recursive = true,
    ): void {
        if ($entity === null) {
            $entity = getItemByTypeName(Entity::class, "Root entity");
        }

        $this->createItem(KnowbaseItem_Profile::class, [
            'knowbaseitems_id' => $article->getID(),
            'profiles_id' => $profile->getID(),
            'entities_id' => $entity->getID(),
            'is_recursive' => $is_recursive,
        ]);
    }
}
