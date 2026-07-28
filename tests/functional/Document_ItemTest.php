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

namespace tests\units;

use Document_Item;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasDocumentsCapacity;
use Glpi\Features\Clonable;
use Glpi\Tests\DbTestCase;
use Psr\Log\LogLevel;
use Toolbox;

use function Safe\ob_start;

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
        $tickets_id = $this->createItem(
            \Ticket::class,
            [
                'name' => 'Test modification date not updated from Document_Item',
                'content' => 'Test modification date not updated from Document_Item',
                'date_mod' => '2020-01-01 00:00:00',
            ],
        )->getID();

        // Document and Document_Item
        $doc_id = $this->createItem(
            \Document::class,
            [
                'users_id'     => $uid,
                'tickets_id'   => $tickets_id,
                'name'         => 'A simple document object',
            ],
        )->getID();

        // Link the document to the ticket via Document_Item
        $this->createItem(
            Document_Item::class,
            [
                'users_id'      => $uid,
                'items_id'      => $tickets_id,
                'itemtype'      => \Ticket::class,
                'documents_id'  => $doc_id,
            ],
        );

        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertGreaterThan('2020-01-01 00:00:00', $ticket->fields['date_mod']);
        $this->assertEquals(
            $_SESSION["glpi_currenttime"],
            $ticket->fields['date_mod'],
        );
    }

    /**
     * The "Associate an existing document" dropdown (rendered on an item's
     * Documents tab through ajax/dropdownRubDocument.php) must be scoped to the
     * item's entity regardless of whether the item is recursive.
     *
     * Document_Item::showAddFormForItem() sends the entity as a scalar id for a
     * non-recursive item, but as an array (the entities subtree from
     * getSonsOf()) for a recursive one. The endpoint used to run that value
     * through intval(), which collapses a non-empty array to the integer 1,
     * mis-scoping (or emptying) the document list so documents living in the
     * item's own entity became unselectable once the item was recursive.
     */
    public function testAssociateExistingDocumentDropdownScopesEntityForRecursiveItems(): void
    {
        $this->login();

        // Both the document and the item live in the same NON-root sub-entity.
        // A non-root entity is required to expose the bug: the buggy code
        // collapses the scope to entity id 1, and because the root entity
        // (id 0) is an ancestor of every entity, a document placed in the root
        // entity would stay visible even with the bug present.
        $entity_id = getItemByTypeName(\Entity::class, '_test_child_2', true);
        $this->assertGreaterThan(1, $entity_id, 'This test requires a non-root sub-entity');
        $this->setEntity('_test_root_entity', true);

        // DocumentCategory is a global tree dropdown (no entities_id column).
        $category = $this->createItem(\DocumentCategory::class, [
            'name' => 'testcat_' . mt_rand(),
        ]);

        $filename = uniqid('glpitest_', true) . '.txt';
        file_put_contents(GLPI_TMP_DIR . '/' . $filename, random_bytes(128));
        $this->createItem(\Document::class, [
            'filename'              => $filename,
            'documentcategories_id' => $category->getID(),
            'entities_id'           => $entity_id,
            '_filename'             => [$filename],
        ]);

        // Render the dropdown for both value shapes produced by
        // Document_Item::showAddFormForItem().
        $render = function ($entity_param) use ($category): string {
            $_POST['rubdoc'] = $category->getID();
            $_POST['entity'] = $entity_param;
            $_POST['rand']   = mt_rand();
            $_POST['myname'] = 'documents_id';
            $_POST['used']   = [];
            $_POST['value']  = -1;

            ob_start();
            include GLPI_ROOT . '/ajax/dropdownRubDocument.php';
            $output = ob_get_clean();

            unset(
                $_POST['rubdoc'],
                $_POST['entity'],
                $_POST['rand'],
                $_POST['myname'],
                $_POST['used'],
                $_POST['value']
            );

            return $output;
        };

        $assertScopedToEntity = function (string $output) use ($entity_id): void {
            $count = preg_match_all(
                '/entity_restrict"?\s*:\s*("[^"]*"|\[[^\]]*\]|\d+)/',
                $output,
                $matches
            );
            $this->assertGreaterThan(0, $count, 'entity_restrict must be present in the rendered dropdown');

            $scoped = false;
            foreach ($matches[1] as $value) {
                if (str_contains($value, (string) $entity_id)) {
                    $scoped = true;
                    break;
                }
            }
            $this->assertTrue(
                $scoped,
                'The document dropdown must be scoped to the item entity (' . $entity_id
                . '), got entity_restrict=' . implode(', ', $matches[1])
            );
        };

        // Non-recursive item: scalar entity id (this path always worked).
        $assertScopedToEntity($render($entity_id));

        // Recursive item: the entities subtree as an array (getSonsOf() form).
        // This is the case the intval() bug used to break.
        $assertScopedToEntity($render(getSonsOf('glpi_entities', $entity_id)));
    }
}
