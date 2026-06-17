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

use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasKnowbaseCapacity;
use Glpi\Features\Clonable;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_Item;
use PHPUnit\Framework\Attributes\DataProvider;
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
                'itemtype' => \Ticket::getType(),
            ],
            1 => [
                'id'       => '_ticket02',
                'itemtype' => \Ticket::getType(),
            ],
            2 => [
                'id'       => '_ticket03',
                'itemtype' => \Ticket::getType(),
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
                'itemtype' => \Ticket::getType(),
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
                'itemtype' => \Ticket::getType(),
            ],
            1 => [
                'id'       => '_test_pc21',
                'itemtype' => \Computer::getType(),
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
        $ticket3 = getItemByTypeName(\Ticket::getType(), '_ticket03');
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

        $ticket1 = getItemByTypeName(\Ticket::getType(), '_ticket01');
        $kbs = KnowbaseItem_Item::getItems($ticket1);
        $this->assertCount(1, $kbs);

        foreach ($kbs as $kb) {
            $this->assertSame($ticket1->getType(), $kb['itemtype']);
            $this->assertSame($ticket1->getID(), $kb['items_id']);
        }

        $computer21 = getItemByTypeName(\Computer::getType(), '_test_pc21');
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

        $ticket3 = getItemByTypeName(\Ticket::getType(), '_ticket03');
        $kbs = KnowbaseItem_Item::getItems($ticket3);
        $this->assertCount(0, $kbs);

        $entity = getItemByTypeName(\Entity::getType(), '_test_child_1');
        $_SESSION['glpiactiveentities'] = [$entity->getID()];

        $ticket3 = getItemByTypeName(\Ticket::getType(), '_ticket03');
        $kbs = KnowbaseItem_Item::getItems($ticket3);
        $this->assertCount(2, $kbs);

        $entity = getItemByTypeName(\Entity::getType(), '_test_child_2');
        $_SESSION['glpiactiveentities'] = [$entity->getID()];

        $ticket3 = getItemByTypeName(\Ticket::getType(), '_ticket03');
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

        $_SESSION['glpishow_count_on_tabs'] = 1;
        $name = $kb_item->getTabNameForItem($kb1);
        $this->assertSame("Associated elements 3", strip_tags($name));

        $_SESSION['glpishow_count_on_tabs'] = 0;
        $name = $kb_item->getTabNameForItem($kb1);
        $this->assertSame("Associated elements", strip_tags($name));

        $ticket3 = getItemByTypeName(\Ticket::getType(), '_ticket03');

        $_SESSION['glpishow_count_on_tabs'] = 1;
        $name = $kb_item->getTabNameForItem($ticket3, true);
        $this->assertSame("Knowledge base 2", strip_tags($name));

        $name = $kb_item->getTabNameForItem($ticket3);
        $this->assertSame("Knowledge base 2", strip_tags($name));

        $_SESSION['glpishow_count_on_tabs'] = 0;
        $name = $kb_item->getTabNameForItem($ticket3);
        $this->assertSame("Knowledge base", strip_tags($name));
    }
    public static function normalizeForDisplayProvider(): iterable
    {
        yield 'empty string is returned as-is' => [
            'html'     => '',
            'expected' => '',
        ];

        yield 'table width attribute is removed and max-width style is added' => [
            'html'     => '<table width="600"><tr><td>Cell</td></tr></table>',
            'expected' => '<table style="max-width: 100%; box-sizing: border-box;"><tr><td style="">Cell</td></tr></table>',
        ];

        yield 'table width in style is replaced by max-width' => [
            'html'     => '<table style="width: 800px;"><tr><td>Cell</td></tr></table>',
            'expected' => '<table style="max-width: 100%; box-sizing: border-box;"><tr><td style="">Cell</td></tr></table>',
        ];

        yield 'td width attribute is removed' => [
            'html'     => '<table><tr><td width="200">Cell</td></tr></table>',
            'expected' => '<table style="max-width: 100%; box-sizing: border-box;"><tr><td style="">Cell</td></tr></table>',
        ];

        yield 'img gets max-width and height style' => [
            'html'     => '<p><img src="/img.png" alt="test" /></p>',
            'expected' => '<p><img src="/img.png" alt="test" style="max-width: 100%; height: auto;"></p>',
        ];

        yield 'table border-width is preserved' => [
            'html'     => '<table style="border-width: 2px; width: 600px;"><tr><td>Cell</td></tr></table>',
            'expected' => '<table style="border-width: 2px; max-width: 100%; box-sizing: border-box;"><tr><td style="">Cell</td></tr></table>',
        ];
        yield 'img min-width in style is stripped' => [
            'html'     => '<p><img src="/img.png" style="min-width: 800px;" /></p>',
            'expected' => '<p><img src="/img.png" style="max-width: 100%; height: auto;"></p>',
        ];
        yield 'table min-width in style is replaced by max-width' => [
            'html'     => '<table style="min-width: 600px; color: red;"><tr><td>Cell</td></tr></table>',
            'expected' => '<table style="color: red; max-width: 100%; box-sizing: border-box;"><tr><td style="">Cell</td></tr></table>',
        ];
        yield 'th width attribute is removed' => [
            'html'     => '<table><tr><th width="150">Header</th></tr></table>',
            'expected' => '<table style="max-width: 100%; box-sizing: border-box;"><tr><th style="">Header</th></tr></table>',
        ];
        yield 'img width in style is stripped' => [
            'html'     => '<p><img src="/img.png" style="width: 800px;" /></p>',
            'expected' => '<p><img src="/img.png" style="max-width: 100%; height: auto;"></p>',
        ];
        yield 'table with existing max-width gets it replaced (no duplicate)' => [
            'html'     => '<table style="max-width: 500px;"><tr><td>Cell</td></tr></table>',
            'expected' => '<table style="max-width: 100%; box-sizing: border-box;"><tr><td style="">Cell</td></tr></table>',
        ];
        yield 'img width HTML attribute is removed' => [
            'html'     => '<p><img src="/img.png" width="200"></p>',
            'expected' => '<p><img src="/img.png" style="max-width: 100%; height: auto;"></p>',
        ];
        yield 'table with no width or style still gets max-width' => [
            'html'     => '<table><tr><td>Cell</td></tr></table>',
            'expected' => '<table style="max-width: 100%; box-sizing: border-box;"><tr><td style="">Cell</td></tr></table>',
        ];
        yield 'nested tables both receive max-width' => [
            'html'     => '<table width="100%"><tr><td><table width="50%"><tr><td>Inner</td></tr></table></td></tr></table>',
            'expected' => '<table style="max-width: 100%; box-sizing: border-box;"><tr><td style=""><table style="max-width: 100%; box-sizing: border-box;"><tr><td style="">Inner</td></tr></table></td></tr></table>',
        ];
        yield 'html with no tables or images is returned unchanged' => [
            'html'     => '<p>Hello <strong>world</strong></p>',
            'expected' => '<p>Hello <strong>world</strong></p>',
        ];
        yield 'td with multiple styles preserves non-width properties' => [
            'html'     => '<table><tr><td style="color: red; width: 200px; padding: 4px;">Cell</td></tr></table>',
            'expected' => '<table style="max-width: 100%; box-sizing: border-box;"><tr><td style="color: red; padding: 4px;">Cell</td></tr></table>',
        ];
    }

    #[DataProvider('normalizeForDisplayProvider')]
    public function testNormalizeForDisplay(string $html, string $expected): void
    {
        $this->assertEquals($expected, KnowbaseItem::normalizeKbRevisionDiffHtml($html));
    }
}
