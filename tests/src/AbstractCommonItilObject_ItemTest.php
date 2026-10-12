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
use DeviceSimcard;
use Glpi\Form\Form;
use Item_DeviceSimcard;
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
            '<span class="d-flex align-items-center"><i class="ti ti-package me-2" aria-hidden="true"></i>Items <span class="badge glpi-badge" data-testid="tab-count-badge">1</span></span>',
            $link->getTabNameForItem($itil_item),
        );

        $this->createItem($this->getTestedClass(), [
            $itil_itemtype::getForeignKeyField() => $itil_item->getID(),
            'itemtype' => Computer::class,
            'items_id' => getItemByTypeName(Computer::class, '_test_pc02', true),
        ]);
        $this->assertEquals(
            '<span class="d-flex align-items-center"><i class="ti ti-package me-2" aria-hidden="true"></i>Items <span class="badge glpi-badge" data-testid="tab-count-badge">2</span></span>',
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

    public function testShowForObjectWithItemtypeWithoutNameField(): void
    {
        $itil_itemtype = $this->getTestedClass()::$itemtype_1;
        if (!is_subclass_of($itil_itemtype, CommonITILObject::class)) {
            $this->markTestSkipped('This test is only for ITIL items');
        }

        $this->login();
        $entity = $this->getTestRootEntity(true);

        // Item_DeviceSimcard is a linkable itemtype whose table has no "name" column.
        $computer = $this->createItem(Computer::class, [
            'name'        => __FUNCTION__,
            'entities_id' => $entity,
        ]);
        $simcard_model = $this->createItem(DeviceSimcard::class, [
            'designation' => __FUNCTION__,
            'entities_id' => $entity,
        ]);
        $simcard = $this->createItem(Item_DeviceSimcard::class, [
            'devicesimcards_id' => $simcard_model->getID(),
            'itemtype'          => Computer::class,
            'items_id'          => $computer->getID(),
            'entities_id'       => $entity,
        ]);

        $itil_item = $this->createItem($itil_itemtype, [
            'name'        => __FUNCTION__,
            'content'     => 'test',
            'entities_id' => $entity,
        ], ['content']);
        $this->createItem($this->getTestedClass(), [
            $itil_itemtype::getForeignKeyField() => $itil_item->getID(),
            'itemtype'                           => Item_DeviceSimcard::class,
            'items_id'                           => $simcard->getID(),
        ]);

        $_SESSION['glpiactiveprofile']['helpdesk_item_type'][] = Item_DeviceSimcard::class;

        // Rendering the linked items must not raise "Undefined array key name".
        $errors = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$errors): bool {
            $errors[] = $errstr;
            return true;
        });
        try {
            $method = new \ReflectionMethod($this->getTestedClass(), 'showForObject');
            $method->setAccessible(true);
            ob_start();
            $method->invoke(null, $itil_item);
            ob_get_clean();
        } finally {
            restore_error_handler();
        }

        $this->assertEmpty(
            array_filter($errors, static fn(string $e): bool => str_contains($e, 'Undefined array key "name"')),
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
}
