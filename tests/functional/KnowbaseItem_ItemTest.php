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

namespace test\units;

use Computer;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasKnowbaseCapacity;
use Glpi\Features\Clonable;
use Glpi\Security\ShareTokenManager;
use Glpi\ShareToken;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_Item;
use Ticket;
use Toolbox;

class KnowbaseItem_ItemTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasKnowbaseCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['kb_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('KnowbaseItem_Item$1', $tabs, $itemtype);
        }
    }

    public function testRelatedItemCloneRelations()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasKnowbaseCapacity::class)]);

        foreach ($CFG_GLPI['kb_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            $item = \getItemForItemtype($itemtype);
            $this->assertContains(KnowbaseItem_Item::class, $item->getCloneRelations(), $itemtype);
        }
    }

    public function testGetTypeName()
    {
        $expected = 'Knowledge base item';
        $this->assertSame($expected, KnowbaseItem_Item::getTypeName(1));

        $expected = 'Knowledge base items';
        $this->assertSame($expected, KnowbaseItem_Item::getTypeName(0));
        $this->assertSame($expected, KnowbaseItem_Item::getTypeName(2));
        $this->assertSame($expected, KnowbaseItem_Item::getTypeName(10));
    }

    public function testGetItemsFromKB()
    {
        $this->login();
        $kb1 = getItemByTypeName('KnowbaseItem', '_knowbaseitem01');
        $items = KnowbaseItem_Item::getItems($kb1);
        $this->assertCount(3, $items);

        $expecteds = [
            0 => [
                'id'       => '_ticket01',
                'itemtype' => Ticket::getType(),
            ],
            1 => [
                'id'       => '_ticket02',
                'itemtype' => Ticket::getType(),
            ],
            2 => [
                'id'       => '_ticket03',
                'itemtype' => Ticket::getType(),
            ],
        ];

        foreach ($expecteds as $key => $expected) {
            $item = getItemByTypeName($expected['itemtype'], $expected['id']);
            $this->assertInstanceOf($expected['itemtype'], $item);
        }

        //add start & limit
        $kb1 = getItemByTypeName('KnowbaseItem', '_knowbaseitem01');
        $items = KnowbaseItem_Item::getItems($kb1, 1, 1);
        $this->assertCount(1, $items);

        $expecteds = [
            1 => [
                'id'       => '_ticket02',
                'itemtype' => Ticket::getType(),
            ],
        ];

        foreach ($expecteds as $key => $expected) {
            $item = getItemByTypeName($expected['itemtype'], $expected['id']);
            $this->assertInstanceOf($expected['itemtype'], $item);
        }

        $kb2 = getItemByTypeName('KnowbaseItem', '_knowbaseitem02');
        $items = KnowbaseItem_Item::getItems($kb2);
        $this->assertCount(2, $items);

        $expecteds = [
            0 => [
                'id'       => '_ticket03',
                'itemtype' => Ticket::getType(),
            ],
            1 => [
                'id'       => '_test_pc21',
                'itemtype' => Computer::getType(),
            ],
        ];

        foreach ($expecteds as $key => $expected) {
            $item = getItemByTypeName($expected['itemtype'], $expected['id']);
            $this->assertInstanceOf($expected['itemtype'], $item);
        }
    }

    public function testGetKbsFromItem()
    {
        $this->login();
        $ticket3 = getItemByTypeName(Ticket::getType(), '_ticket03');
        $kbs = KnowbaseItem_Item::getItems($ticket3);
        $this->assertCount(2, $kbs);

        $kb_ids = [];
        foreach ($kbs as $kb) {
            $this->assertSame($ticket3->getType(), $kb['itemtype']);
            $this->assertSame($ticket3->getID(), $kb['items_id']);
            $kb_ids[] = $kb['knowbaseitems_id'];
        }

        //test get "used"
        $kbs = KnowbaseItem_Item::getItems($ticket3, 0, 0, '', true);
        $this->assertCount(2, $kbs);

        foreach ($kbs as $key => $kb) {
            $this->assertEquals($key, $kb);
            $this->assertContains($key, $kb_ids);
        }

        $ticket1 = getItemByTypeName(Ticket::getType(), '_ticket01');
        $kbs = KnowbaseItem_Item::getItems($ticket1);
        $this->assertCount(1, $kbs);

        foreach ($kbs as $kb) {
            $this->assertSame($ticket1->getType(), $kb['itemtype']);
            $this->assertSame($ticket1->getID(), $kb['items_id']);
        }

        $computer21 = getItemByTypeName(Computer::getType(), '_test_pc21');
        $kbs = KnowbaseItem_Item::getItems($computer21);
        $this->assertCount(1, $kbs);

        foreach ($kbs as $kb) {
            $this->assertSame($computer21->getType(), $kb['itemtype']);
            $this->assertSame($computer21->getID(), $kb['items_id']);
        }

        //test with entitiesrestriction
        $_SESSION['glpishowallentities'] = 0;

        $entity = getItemByTypeName(\Entity::getType(), '_test_root_entity');
        $_SESSION['glpiactiveentities'] = [$entity->getID()];

        $ticket3 = getItemByTypeName(Ticket::getType(), '_ticket03');
        $kbs = KnowbaseItem_Item::getItems($ticket3);
        $this->assertCount(0, $kbs);

        $entity = getItemByTypeName(\Entity::getType(), '_test_child_1');
        $_SESSION['glpiactiveentities'] = [$entity->getID()];

        $ticket3 = getItemByTypeName(Ticket::getType(), '_ticket03');
        $kbs = KnowbaseItem_Item::getItems($ticket3);
        $this->assertCount(2, $kbs);

        $entity = getItemByTypeName(\Entity::getType(), '_test_child_2');
        $_SESSION['glpiactiveentities'] = [$entity->getID()];

        $ticket3 = getItemByTypeName(Ticket::getType(), '_ticket03');
        $kbs = KnowbaseItem_Item::getItems($ticket3);
        $this->assertCount(0, $kbs);

        $_SESSION['glpishowallentities'] = 1;
        unset($_SESSION['glpiactiveentities']);
    }

    public function testGetTabNameForItem()
    {
        $this->login();
        $kb_item = new KnowbaseItem_Item();
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

        // KnowbaseItem tab is no longer displayed (handled in main article view)
        $this->assertSame('', $kb_item->getTabNameForItem($kb1));

        $ticket3 = getItemByTypeName(Ticket::getType(), '_ticket03');

        $_SESSION['glpishow_count_on_tabs'] = 1;
        $name = $kb_item->getTabNameForItem($ticket3, true);
        $this->assertSame("Knowledge base 2", strip_tags($name));

        $name = $kb_item->getTabNameForItem($ticket3);
        $this->assertSame("Knowledge base 2", strip_tags($name));

        $_SESSION['glpishow_count_on_tabs'] = 0;
        $name = $kb_item->getTabNameForItem($ticket3);
        $this->assertSame("Knowledge base", strip_tags($name));
    }

    public function testDropdownAllTypesEntityRestrict()
    {
        $this->login();

        $kb = new KnowbaseItem();

        // 1. Test non-recursive KB article
        $kb->getEmpty();
        $kb->fields['entities_id'] = 123;
        $kb->fields['is_recursive'] = 0;

        ob_start();
        KnowbaseItem_Item::dropdownAllTypes($kb, 'items_id');
        $output = ob_get_clean();

        // Ensure the dropdown ajax config contains the specific entity ID (unquoted key in JS object literal)
        $this->assertStringContainsString('entity_restrict:123', $output, 'Dropdown should restrict to exact entity for non-recursive KB articles.');

        // 2. Test recursive KB article
        $kb->getEmpty();
        $kb->fields['entities_id'] = 123;
        $kb->fields['is_recursive'] = 1;

        ob_start();
        KnowbaseItem_Item::dropdownAllTypes($kb, 'items_id');
        $output = ob_get_clean();

        // For recursive KB articles, it should use getSonsOf()
        $expected_entities = getSonsOf('glpi_entities', 123);
        $expected_json = json_encode($expected_entities);

        $this->assertStringContainsString('entity_restrict:' . $expected_json, $output, 'Dropdown should restrict to child entities for recursive KB articles.');
    }

    public function testShowForItemHidesLinkedItemsWithoutReadRight(): void
    {
        global $CFG_GLPI;

        $this->login();
        $kb = $this->createItem(KnowbaseItem::class, [
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name'        => 'Shared article',
            'answer'      => 'Content',
        ]);
        $ticket = $this->createItem(Ticket::class, [
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name'        => 'Linked ticket name',
            'content'     => 'Content',
        ]);
        $computer = $this->createItem(Computer::class, [
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name'        => 'Linked computer name',
        ]);
        foreach ([$ticket, $computer] as $linked) {
            $this->createItem(KnowbaseItem_Item::class, [
                'knowbaseitems_id' => $kb->getID(),
                'itemtype'         => $linked::class,
                'items_id'         => $linked->getID(),
            ]);
        }
        $token = $this->createItem(ShareToken::class, [
            'itemtype'  => KnowbaseItem::class,
            'items_id'  => $kb->getID(),
            'name'      => $this->getUniqueString(),
            'is_active' => 1,
        ]);

        ob_start();
        KnowbaseItem_Item::showForItem($kb);
        $output = ob_get_clean();
        $this->assertStringContainsString('Linked ticket name', $output);
        $this->assertStringContainsString('Linked computer name', $output);

        // Anonymous visitor with a share token, as on the public FAQ tab
        $this->logOut();
        foreach ($CFG_GLPI['user_pref_field'] as $field) {
            $_SESSION['glpi' . $field] ??= $CFG_GLPI[$field];
        }
        $manager = new ShareTokenManager();
        $manager->grantSessionAccess($manager->decryptToken((string) $token->fields['token']));
        $this->assertTrue($kb->can($kb->getID(), READ));

        ob_start();
        KnowbaseItem_Item::showForItem($kb);
        $output = ob_get_clean();
        $this->assertStringNotContainsString('Linked ticket name', $output);
        $this->assertStringNotContainsString('Linked computer name', $output);
        $this->assertStringContainsString('No results found', $output);
    }

    public function testShowForItemPaginatesReadableLinkedItemsAcrossChunks(): void
    {
        global $DB;

        $this->login();
        $entities_id = $this->getTestRootEntity(only_id: true);
        $kb = $this->createItem(KnowbaseItem::class, [
            'entities_id' => $entities_id,
            'name'        => 'Article with many links',
            'answer'      => 'Content',
        ]);
        $computers = [];
        for ($i = 0; $i < 101; $i++) {
            $computers[$i] = $this->createItem(Computer::class, [
                'entities_id' => $entities_id,
                'name'        => "[kb-pc-$i]",
            ]);
            $this->createItem(KnowbaseItem_Item::class, [
                'knowbaseitems_id' => $kb->getID(),
                'itemtype'         => Computer::class,
                'items_id'         => $computers[$i]->getID(),
            ]);
        }
        // Link to a missing item, first in the list: it must not be counted
        $this->assertTrue($DB->insert(KnowbaseItem_Item::getTable(), [
            'knowbaseitems_id' => $kb->getID(),
            'itemtype'         => Computer::class,
            'items_id'         => $computers[100]->getID() + 1000,
        ]));

        $_SESSION['glpilist_limit'] = 10;
        $_GET['start'] = 95;
        ob_start();
        KnowbaseItem_Item::showForItem($kb);
        $output = ob_get_clean();
        unset($_GET['start']);

        // Order is items_id DESC: visible rows 95 to 100 are computers 5 to 0
        $this->assertStringContainsString('Showing 96 to 101 of 101 rows', $output);
        for ($i = 0; $i <= 5; $i++) {
            $this->assertStringContainsString("[kb-pc-$i]", $output);
        }
        $this->assertStringNotContainsString('[kb-pc-6]', $output);
        $this->assertStringNotContainsString('[kb-pc-100]', $output);
    }
}
