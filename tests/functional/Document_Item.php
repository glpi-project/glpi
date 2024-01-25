<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

/* Test for inc/document_item.class.php */

class Document_Item extends DbTestCase
{
    public function testGetForbiddenStandardMassiveAction()
    {
        $this->newTestedInstance();
        $this->array(
            $this->testedInstance->getForbiddenStandardMassiveAction()
        )->isIdenticalTo(['clone', 'update']);
    }

    public function testPrepareInputForAdd()
    {
        $input = [];
        $ditem = $this->newTestedInstance;

        $this->when(
            function () use ($input) {
                $this->boolean($this->testedInstance->add($input))->isFalse();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Item type is mandatory')
         ->exists();

        $input['itemtype'] = '';
        $this->when(
            function () use ($input) {
                $this->boolean($this->testedInstance->add($input))->isFalse();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Item type is mandatory')
         ->exists();

        $input['itemtype'] = 'NotAClass';
        $this->when(
            function () use ($input) {
                $this->boolean($this->testedInstance->add($input))->isFalse();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('No class found for type NotAClass')
         ->exists();

        $input['itemtype'] = 'Computer';
        $this->when(
            function () use ($input) {
                $this->boolean($this->testedInstance->add($input))->isFalse();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Item ID is mandatory')
         ->exists();

        $input['items_id'] = 0;
        $this->when(
            function () use ($input) {
                $this->boolean($this->testedInstance->add($input))->isFalse();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Item ID is mandatory')
         ->exists();

        $cid = getItemByTypeName('Computer', '_test_pc01', true);
        $input['items_id'] = $cid;

        $this->when(
            function () use ($input) {
                $this->boolean($this->testedInstance->add($input))->isFalse();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Document ID is mandatory')
         ->exists();

        $input['documents_id'] = 0;
        $this->when(
            function () use ($input) {
                $this->boolean($this->testedInstance->add($input))->isFalse();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Document ID is mandatory')
         ->exists();

        $document = new \Document();
        $this->integer(
            (int)$document->add([
                'name'   => 'Test document to link'
            ])
        )->isGreaterThan(0);
        $input['documents_id'] = $document->getID();

        $expected = [
            'itemtype'     => 'Computer',
            'items_id'     => $cid,
            'documents_id' => $document->getID(),
            'users_id'     => false,
            'entities_id'  => 0,
            'is_recursive' => 0
        ];

        $this->array(
            $this->testedInstance->prepareInputForAdd($input)
        )->isIdenticalTo($expected);
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
                        'glpi_documents_items.items_id'  => 1
                    ]
                ]
            ],
            'ORDER'           => 'itemtype'
        ];
        $this->array(\Document_Item::getDistinctTypesParams(1))->isIdenticalTo($expected);

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
                        'glpi_documents_items.items_id'  => 1
                    ]
                ],
                [
                    'date_mod'  => [
                        '>',
                        '2000-01-01'
                    ]
                ]
            ],
            'ORDER'           => 'itemtype'
        ];
        $this->array(\Document_Item::getDistinctTypesParams(1, $extra_where))->isIdenticalTo($expected);
    }


    public function testPostAddItem()
    {
        $uid = getItemByTypeName('User', TU_USER, true);

        $ticket = new \Ticket();
        $tickets_id = $ticket->add([
            'name' => '',
            'content' => 'Test modification date not updated from Document_Item',
            'date_mod' => '2020-01-01'
        ]);

        $this->integer($tickets_id)->isGreaterThan(0);

       // Document and Document_Item
        $doc = new \Document();
        $this->integer(
            (int)$doc->add([
                'users_id'     => $uid,
                'tickets_id'   => $tickets_id,
                'name'         => 'A simple document object'
            ])
        )->isGreaterThan(0);

       //do not change ticket modification date
        $doc_item = new \Document_Item();
        $this->integer(
            (int)$doc_item->add([
                'users_id'      => $uid,
                'items_id'      => $tickets_id,
                'itemtype'      => 'Ticket',
                'documents_id'  => $doc->getID(),
                '_do_update_ticket' => false
            ])
        )->isGreaterThan(0);

        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->string($ticket->fields['date_mod'])->isIdenticalTo('2020-01-01 00:00:00');

       //do change ticket modification date
        $_SESSION["glpi_currenttime"] = '2021-01-01 00:00:01';
        $doc = new \Document();
        $this->integer(
            (int)$doc->add([
                'users_id'     => $uid,
                'tickets_id'   => $tickets_id,
                'name'         => 'A simple document object'
            ])
        )->isGreaterThan(0);

        $doc_item = new \Document_Item();
        $this->integer(
            (int)$doc_item->add([
                'users_id'      => $uid,
                'items_id'      => $tickets_id,
                'itemtype'      => 'Ticket',
                'documents_id'  => $doc->getID(),
            ])
        )->isGreaterThan(0);

        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();
        $this->string($ticket->fields['date_mod'])->isNotEqualTo('2021-01-01 00:00:01');
    }
}
