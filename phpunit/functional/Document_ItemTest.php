<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace tests\units;

use DbTestCase;
use Document_Item;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasDocumentsCapacity;
use Glpi\Features\Clonable;
use Psr\Log\LogLevel;
use Toolbox;

class Document_ItemTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasDocumentsCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['itemdevices_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('Document_Item$1', $tabs, $itemtype);
        }
    }

    public function testRelatedItemCloneRelations()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasDocumentsCapacity::class)]);

        foreach ($CFG_GLPI['itemdevices_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            $item = \getItemForItemtype($itemtype);
            $this->assertContains(Document_Item::class, $item->getCloneRelations(), $itemtype);
        }
    }

    public function testGetForbiddenStandardMassiveAction()
    {
        $ditem = new Document_Item();
        $this->assertSame(
            ['clone', 'update'],
            $ditem->getForbiddenStandardMassiveAction()
        );
    }

    public function testPrepareInputForAdd()
    {
        $input = [];
        $ditem = new Document_Item();

        $res = $ditem->add($input);
        $this->assertFalse($res);
        $this->hasPhpLogRecordThatContains(
            'Item type is mandatory',
            LogLevel::WARNING
        );

        $input['itemtype'] = '';
        $res = $ditem->add($input);
        $this->assertFalse($res);
        $this->hasPhpLogRecordThatContains(
            'Item type is mandatory',
            LogLevel::WARNING
        );

        $input['itemtype'] = 'NotAClass';
        $res = $ditem->add($input);
        $this->assertFalse($res);
        $this->hasPhpLogRecordThatContains(
            'No class found for type NotAClass',
            LogLevel::WARNING
        );

        $input['itemtype'] = 'Computer';
        $res = $ditem->add($input);
        $this->assertFalse($res);
        $this->hasPhpLogRecordThatContains(
            'Item ID is mandatory',
            LogLevel::WARNING
        );

        $input['items_id'] = 0;
        $res = $ditem->add($input);
        $this->assertFalse($res);
        $this->hasPhpLogRecordThatContains(
            'Item ID is mandatory',
            LogLevel::WARNING
        );

        $cid = getItemByTypeName('Computer', '_test_pc01', true);
        $input['items_id'] = $cid;

        $res = $ditem->add($input);
        $this->assertFalse($res);
        $this->hasPhpLogRecordThatContains(
            'Document ID is mandatory',
            LogLevel::WARNING
        );

        $input['documents_id'] = 0;
        $res = $ditem->add($input);
        $this->assertFalse($res);
        $this->hasPhpLogRecordThatContains(
            'Document ID is mandatory',
            LogLevel::WARNING
        );

        $document = new \Document();
        $this->assertGreaterThan(
            0,
            $document->add([
                'name'   => 'Test document to link',
            ])
        );
        $input['documents_id'] = $document->getID();

        $expected = [
            'itemtype'     => 'Computer',
            'items_id'     => $cid,
            'documents_id' => $document->getID(),
            'users_id'     => false,
            'entities_id'  => 0,
            'is_recursive' => 0,
        ];

        $this->assertSame(
            $expected,
            $ditem->prepareInputForAdd($input)
        );
    }


    public function testGetDistinctTypesParams()
    {
        $expected = [
            'SELECT'          => 'itemtype',
            'DISTINCT'        => true,
            'FROM'            => 'glpi_documents_items',
            'WHERE'           => [
                'OR'  => [
                    'glpi_documents_items.documents_id'  => 1,
                    [
                        'glpi_documents_items.itemtype'  => 'Document',
                        'glpi_documents_items.items_id'  => 1,
                    ],
                ],
            ],
            'ORDER'           => 'itemtype',
        ];
        $this->assertSame($expected, Document_Item::getDistinctTypesParams(1));

        $extra_where = ['date_mod' => ['>', '2000-01-01']];
        $expected = [
            'SELECT'          => 'itemtype',
            'DISTINCT'        => true,
            'FROM'            => 'glpi_documents_items',
            'WHERE'           => [
                'OR'  => [
                    'glpi_documents_items.documents_id'  => 1,
                    [
                        'glpi_documents_items.itemtype'  => 'Document',
                        'glpi_documents_items.items_id'  => 1,
                    ],
                ],
                [
                    'date_mod'  => [
                        '>',
                        '2000-01-01',
                    ],
                ],
            ],
            'ORDER'           => 'itemtype',
        ];
        $this->assertSame($expected, Document_Item::getDistinctTypesParams(1, $extra_where));
    }


    public function testPostAddItem()
    {
        $uid = getItemByTypeName('User', TU_USER, true);

        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name' => '',
            'content' => 'Test modification date not updated from Document_Item',
            'date_mod' => '2020-01-01',
        ]);

        $this->assertGreaterThan(0, $tickets_id);

        // Document and Document_Item
        $doc = new \Document();
        $this->assertGreaterThan(
            0,
            $doc->add([
                'users_id'     => $uid,
                'tickets_id'   => $tickets_id,
                'name'         => 'A simple document object',
            ])
        );

        //do not change ticket modification date
        $doc_item = new Document_Item();
        $this->assertGreaterThan(
            0,
            $doc_item->add([
                'users_id'      => $uid,
                'items_id'      => $tickets_id,
                'itemtype'      => 'Ticket',
                'documents_id'  => $doc->getID(),
                '_do_update_ticket' => false,
            ])
        );

        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertSame('2020-01-01 00:00:00', $ticket->fields['date_mod']);

        //do change ticket modification date
        $_SESSION["glpi_currenttime"] = '2021-01-01 00:00:01';
        $doc = new \Document();
        $this->assertGreaterThan(
            0,
            $doc->add([
                'users_id'     => $uid,
                'tickets_id'   => $tickets_id,
                'name'         => 'A simple document object',
            ])
        );

        $doc_item = new Document_Item();
        $this->assertGreaterThan(
            0,
            $doc_item->add([
                'users_id'      => $uid,
                'items_id'      => $tickets_id,
                'itemtype'      => 'Ticket',
                'documents_id'  => $doc->getID(),
            ])
        );

        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertNotEquals(
            '2021-01-01 00:00:01',
            $ticket->fields['date_mod']
        );
    }
}
