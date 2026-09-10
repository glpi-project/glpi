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

namespace Glpi\Tests;

use CommonITILObject;
use CommonItilObject_Item;
use Computer;
use Entity;
use Glpi\Form\Form;
use User;

abstract class AbstractCommonItilObject_ItemTest extends DbTestCase
{
    /**
     * Return the name of the Rule class this test class tests
     * @return class-string<CommonItilObject_Item>
     */
    protected function getTestedClass(): string
    {
        $test_class = static::class;
        return preg_replace('/Test$/', '', substr(strrchr($test_class, '\\'), 1));
    }

    public function testGetTabNameForItemITIL(): void
    {
        $this->login();
        $itil_itemtype = $this->getTestedClass()::$itemtype_1;
        $link = new ($this->getTestedClass())();

        $itil_item = $this->createItem($itil_itemtype, [
            'name' => __FUNCTION__,
            'content' => 'test',
            'entities_id' => $this->getTestRootEntity(true),
        ], ['content']);

        $this->createItem($this->getTestedClass(), [
            $itil_itemtype::getForeignKeyField() => $itil_item->getID(),
            'itemtype' => Computer::class,
            'items_id' => getItemByTypeName(Computer::class, '_test_pc01', true),
        ]);
        $this->assertEquals(
            '<span class="d-flex align-items-center"><i class="ti ti-package me-2"></i>Items <span class="badge glpi-badge" data-testid="tab-count-badge">1</span></span>',
            $link->getTabNameForItem($itil_item),
        );

        $this->createItem($this->getTestedClass(), [
            $itil_itemtype::getForeignKeyField() => $itil_item->getID(),
            'itemtype' => Computer::class,
            'items_id' => getItemByTypeName(Computer::class, '_test_pc02', true),
        ]);
        $this->assertEquals(
            '<span class="d-flex align-items-center"><i class="ti ti-package me-2"></i>Items <span class="badge glpi-badge" data-testid="tab-count-badge">2</span></span>',
            $link->getTabNameForItem($itil_item),
        );

        // Adding a form should not increase the counter as it should only keep
        // track assets
        $this->createItem($this->getTestedClass(), [
            $itil_itemtype::getForeignKeyField() => $itil_item->getID(),
            'itemtype' => Form::class,
            'items_id' => getItemByTypeName(Form::class, 'Request a service', true),
        ]);
        $this->assertEquals(
            'Items 2', // 2 is the value from the last test, no change
            strip_tags($link->getTabNameForItem($itil_item)),
        );

        $_SESSION['glpiactiveprofile']['helpdesk_item_type'] = [];
        $this->assertEquals(
            'Items',
            strip_tags($link->getTabNameForItem($itil_item)),
        );
    }

    public function testGetTabNameForItemUser(): void
    {
        if (!is_subclass_of($this->getTestedClass()::$itemtype_1, CommonITILObject::class)) {
            $this->markTestSkipped('This test is only for ITIL items');
        }
        $this->login();
        $itil_itemtype = $this->getTestedClass()::$itemtype_1;
        $link = new ($this->getTestedClass())();

        $user = getItemByTypeName(User::class, TU_USER);
        $tab_label = $link->getTabNameForItem($user);
        $this->assertStringContainsString(
            $itil_itemtype::getTypeName(\Session::getPluralNumber()),
            $tab_label,
        );
        // Extract count from the inside the .badge element in the label
        $original_tab_count = (int) preg_replace(
            '/.*<span class="badge glpi-badge">(\d+)<\/span>.*/',
            '$1',
            $tab_label,
        );

        $this->createItem($itil_itemtype, [
            'name' => __FUNCTION__,
            'content' => 'test',
            'entities_id' => $this->getTestRootEntity(true),
            '_users_id_assign' => $user->getID(),
        ], ['content']);
        $this->assertEquals(
            $original_tab_count + 1,
            (int) preg_replace(
                '/.*<span class="badge glpi-badge" data-testid="tab-count-badge">(\d+)<\/span>.*/',
                '$1',
                $link->getTabNameForItem($user),
            ),
        );

        $this->createItem($itil_itemtype, [
            'name' => __FUNCTION__,
            'content' => 'test',
            'entities_id' => $this->getTestRootEntity(true),
            '_users_id_assign' => 0,
            '_users_id_requester' => $user->getID(),
        ], ['content']);
        $this->assertEquals(
            $original_tab_count + 2,
            (int) preg_replace(
                '/.*<span class="badge glpi-badge" data-testid="tab-count-badge">(\d+)<\/span>.*/',
                '$1',
                $link->getTabNameForItem($user),
            ),
        );
    }

    public function getGetTabNameForItemAsset(): void
    {
        $this->login();
        $itil_itemtype = $this->getTestedClass()::$itemtype_1;
        $link = new ($this->getTestedClass())();


        $computer = $this->createItem(Computer::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // Link new computer with a new ITIL
        $this->createItem($itil_itemtype, [
            'name' => __FUNCTION__,
            'content' => 'test',
            'entities_id' => $this->getTestRootEntity(true),
            'items_id' => [Computer::class => [$computer->getID()]],
        ], ['content', 'items_id']);

        $this->assertEquals(
            1,
            (int) preg_replace(
                '/.*<span class="badge glpi-badge">(\d+)<\/span>.*/',
                '$1',
                $link->getTabNameForItem($computer),
            ),
        );
    }

        /**
     * A recursive ITIL object can already be linked to items located in its
     * sub-entities (see CommonDBRelation::can()'s entity coherency check,
     * which allows the link when either extremity is recursive over an
     * ancestor of the other). The item search dropdowns must offer those
     * items instead of restricting the candidate pool to the object's own
     * entity only.
     */
    public function testItemAddFormEntityRestrictOnRecursiveItil(): void
    {
        if (!is_subclass_of($this->getTestedClass()::$itemtype_1, CommonITILObject::class)) {
            $this->markTestSkipped('This test is only for ITIL items');
        }
        $this->login();
        $itil_itemtype = $this->getTestedClass()::$itemtype_1;

        // Ensure the item search dropdowns are actually rendered regardless
        // of the test session's default profile configuration.
        $_SESSION['glpiactiveprofile']['helpdesk_hardware'] = 2 ** CommonITILObject::HELPDESK_ALL_HARDWARE;

        $root_entity = $this->getTestRootEntity(true);
        $child_entity = $this->createItem(Entity::class, [
            'name'        => __FUNCTION__ . '_child',
            'entities_id' => $root_entity,
        ])->getID();

        $itil_item = $this->createItem($itil_itemtype, [
            'name'         => __FUNCTION__,
            'content'      => 'test',
            'entities_id'  => $root_entity,
            'is_recursive' => 1,
        ], ['content']);

        ob_start();
        $this->getTestedClass()::itemAddForm($itil_item, [
            'id'         => $itil_item->getID(),
            '_canupdate' => true,
        ]);
        $html = ob_get_clean();

        $entity_restrict = $this->extractEntityRestrictFromItemAddForm($html);
        $this->assertIsArray($entity_restrict);
        $this->assertContains($root_entity, $entity_restrict);
        $this->assertContains($child_entity, $entity_restrict);
    }

    /**
     * A non-recursive ITIL object must keep the item search restricted to
     * its own entity: nothing in the rights model allows linking it to
     * items located in sibling or child entities.
     */
    public function testItemAddFormEntityRestrictOnNonRecursiveItil(): void
    {
        if (!is_subclass_of($this->getTestedClass()::$itemtype_1, CommonITILObject::class)) {
            $this->markTestSkipped('This test is only for ITIL items');
        }
        $this->login();
        $itil_itemtype = $this->getTestedClass()::$itemtype_1;

        $_SESSION['glpiactiveprofile']['helpdesk_hardware'] = 2 ** CommonITILObject::HELPDESK_ALL_HARDWARE;

        $root_entity = $this->getTestRootEntity(true);

        $itil_item = $this->createItem($itil_itemtype, [
            'name'         => __FUNCTION__,
            'content'      => 'test',
            'entities_id'  => $root_entity,
            'is_recursive' => 0,
        ], ['content']);

        ob_start();
        $this->getTestedClass()::itemAddForm($itil_item, [
            'id'         => $itil_item->getID(),
            '_canupdate' => true,
        ]);
        $html = ob_get_clean();

        $entity_restrict = $this->extractEntityRestrictFromItemAddForm($html);
        $this->assertIsNotArray($entity_restrict, 'entity_restrict must stay a single entity for a non-recursive ITIL object');
        $this->assertEquals($root_entity, $entity_restrict);
    }

    /**
     * Extract the `entity_restrict` value embedded by Ajax::updateItemOnSelectEvent()
     * in the JS emitted for the "global search" item dropdown.
     *
     * @return int|array<int,int>
     */
    private function extractEntityRestrictFromItemAddForm(string $html): int|array
    {
        $this->assertMatchesRegularExpression(
            '/entity_restrict:(\{[^}]*\}|\[[^\]]*\]|\d+)/',
            $html,
            'entity_restrict parameter not found in the rendered item search dropdown',
        );
        preg_match('/entity_restrict:(\{[^}]*\}|\[[^\]]*\]|\d+)/', $html, $matches);
        $decoded = json_decode($matches[1], true);

        return is_array($decoded) ? array_values($decoded) : (int) $decoded;
    }
}
