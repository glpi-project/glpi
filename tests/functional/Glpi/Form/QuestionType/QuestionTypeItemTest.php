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

namespace tests\units\Glpi\Form\QuestionType;

use Computer;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeItemExtraDataConfig;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Ticket;
use User;

final class QuestionTypeItemTest extends DbTestCase
{
    use FormTesterTrait;

    /**
     * Each case returns a callable that, when invoked inside the test method
     * (after the DB transaction has started), creates the necessary items and
     * returns a description array:
     *
     *   [
     *     'itemtype'    => class-string,
     *     'items_id'    => int,
     *     'extra_data'  => string (JSON),
     *     'question'    => string,
     *     'contains'    => string,   // substring that MUST appear in ticket content
     *     'not_contains'=> ?string,  // substring that MUST NOT appear (optional)
     *   ]
     */
    public static function itemAnswerInTicketProvider(): array
    {
        return [
            'basic asset — only name' => [
                fn(self $t) => [
                    'itemtype'   => Computer::class,
                    'items_id'   => $t->createItem(Computer::class, [
                        'name'        => 'My Computer',
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID(),
                    'extra_data' => json_encode(
                        (new QuestionTypeItemExtraDataConfig(itemtype: Computer::class))->jsonSerialize()
                    ),
                    'question'     => 'Asset',
                    'content'      => '1) Asset: My Computer', 
                ], 
            ],

            'user — friendly name (realname firstname)' => [
                fn(self $t) => [
                    'itemtype'  => User::class,
                    'items_id'  => $t->createItem(User::class, [
                        'name'      => 'jdoe',
                        'firstname' => 'John',
                        'realname'  => 'Doe',
                    ])->getID(),
                    'extra_data' => json_encode(
                        (new QuestionTypeItemExtraDataConfig(itemtype: User::class))->jsonSerialize()
                    ),
                    'question'  => 'Technician',
                    'content'   => '1) Technician: Doe John',
                ],
            ],

            'asset with serial only' => [
                fn(self $t) => [
                    'itemtype'  => Computer::class,
                    'items_id'  => $t->createItem(Computer::class, [
                        'name'        => 'My Laptop',
                        'serial'      => 'SN-1234',
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID(),
                    'extra_data' => json_encode(
                        (new QuestionTypeItemExtraDataConfig(itemtype: Computer::class))->jsonSerialize()
                    ),
                    'question'  => 'Asset',
                    'content'   => '1) Asset: My Laptop - SN-1234',
                ],
            ],

            'asset with serial and otherserial' => [
                fn(self $t) => [
                    'itemtype'  => Computer::class,
                    'items_id'  => $t->createItem(Computer::class, [
                        'name'        => 'My Laptop',
                        'serial'      => 'SN-1234',
                        'otherserial' => 'INV-5678',
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID(),
                    'extra_data' => json_encode(
                        (new QuestionTypeItemExtraDataConfig(itemtype: Computer::class))->jsonSerialize()
                    ),
                    'question'  => 'Asset',
                    'content'   => '1) Asset: My Laptop - SN-1234 - INV-5678',
                ],
            ],

            'asset with linked user' => [
                fn(self $t) => [
                    'itemtype'  => Computer::class,
                    'items_id'  => $t->createItem(Computer::class, [
                        'name'        => 'My Desktop',
                        'users_id'    => $t->createItem(User::class, [
                            'name'      => 'jdoe',
                            'firstname' => 'John',
                            'realname'  => 'Doe',
                        ])->getID(),
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID(),
                    'extra_data' => json_encode(
                        (new QuestionTypeItemExtraDataConfig(itemtype: Computer::class))->jsonSerialize()
                    ),
                    'question'  => 'Asset',
                    'content'   => '1) Asset: My Desktop - Doe John',
                ],
            ],

            'asset with all extra fields' => [
                fn(self $t) => [
                    'itemtype'  => Computer::class,
                    'items_id'  => $t->createItem(Computer::class, [
                        'name'        => 'My Workstation',
                        'serial'      => 'SN-AAAA',
                        'otherserial' => 'INV-BBBB',
                        'users_id'    => $t->createItem(User::class, [
                            'name'      => 'jdoe',
                            'firstname' => 'John',
                            'realname'  => 'Doe',
                        ])->getID(),
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID(),
                    'extra_data' => json_encode(
                        (new QuestionTypeItemExtraDataConfig(itemtype: Computer::class))->jsonSerialize()
                    ),
                    'question'  => 'Asset',
                    'content'   => '1) Asset: My Workstation - SN-AAAA - INV-BBBB - Doe John',
                ],
            ],

            'asset with empty serial — no parentheses' => [
                fn(self $t) => [
                    'itemtype'  => Computer::class,
                    'items_id'  => $t->createItem(Computer::class, [
                        'name'        => 'My Server',
                        'serial'      => '',
                        'otherserial' => '',
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID(),
                    'extra_data' => json_encode(
                        (new QuestionTypeItemExtraDataConfig(itemtype: Computer::class))->jsonSerialize()
                    ),
                    'question'     => 'Asset',
                    'content'      => '1) Asset: My Server',
                ],
            ],

            'ITIL type — ID appended when not visible in session' => [
                fn(self $t) => (static function () use ($t) {
                    $_SESSION['glpiis_ids_visible'] = false;
                    $linked = $t->createItem(Ticket::class, [
                        'name'        => 'Some Ticket',
                        'content'     => 'content',
                        'entities_id' => $t->getTestRootEntity(true),
                    ]);
                    return [
                        'itemtype'  => Ticket::class,
                        'items_id'  => $linked->getID(),
                        'extra_data' => json_encode(        
                            (new QuestionTypeItemExtraDataConfig(itemtype: Ticket::class))->jsonSerialize()
                        ),
                        'question'  => 'Linked Ticket',
                        'content'   => '1) Linked Ticket: Some Ticket - ' . $linked->getID(),
                    ];
                })(),
            ],

            'ITIL type — no ID appended when already visible in session' => [
                fn(self $t) => (static function () use ($t) {
                    $_SESSION['glpiis_ids_visible'] = true;
                    $linked = $t->createItem(Ticket::class, [
                        'name'        => 'Some Ticket',
                        'content'     => 'content',
                        'entities_id' => $t->getTestRootEntity(true),
                    ]);
                    return [
                        'itemtype'     => Ticket::class,
                        'items_id'     => $linked->getID(),
                        'extra_data'   => json_encode(
                            (new QuestionTypeItemExtraDataConfig(itemtype: Ticket::class))->jsonSerialize()
                        ),
                        'question'     => 'Linked Ticket',
                        'content'      => '1) Linked Ticket: Some Ticket',
                    ];
                })(),
            ],
        ];
    }

    #[DataProvider('itemAnswerInTicketProvider')]
    public function testItemAnswerIsDisplayedInTicketDescription(callable $setup): void
    {
        $this->login();

        $case = $setup($this);

        $builder = new FormBuilder();
        $builder->addQuestion(
            name: $case['question'],
            type: QuestionTypeItem::class,
            extra_data: $case['extra_data'],
        );
        $form = $this->createForm($builder);

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            $case['question'] => [
                'itemtype' => $case['itemtype'],
                'items_id' => $case['items_id'],
            ],
        ]);

        $content = strip_tags($ticket->fields['content']);

        $this->assertEquals($case['content'], $content);

        // Clean up any session flags set by the case
        unset($_SESSION['glpiis_ids_visible']);
    }
}
