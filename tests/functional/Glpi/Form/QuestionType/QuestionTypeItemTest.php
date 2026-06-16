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
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeItemExtraDataConfig;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Location;
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
     *     'itemtype'   => class-string,
     *     'items_ids'   => array of int,
     *     'extra_data' => string (JSON),
     *     'question'   => string,
     *     'content'    => string, // expected content in the ticket description (after stripping HTML tags)
     *   ]
     */
    public static function itemAnswerInTicketProvider(): array
    {
        return [
            'basic asset — only name' => [
                fn(self $t) => [
                    'itemtype'   => Computer::class,
                    'items_ids'   => [$t->createItem(Computer::class, [
                        'name'        => 'My Computer',
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID()],
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
                    'items_ids'  => [$t->createItem(User::class, [
                        'name'      => 'jdoe',
                        'firstname' => 'John',
                        'realname'  => 'Doe',
                    ])->getID()],
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
                    'items_ids'  => [$t->createItem(Computer::class, [
                        'name'        => 'My Laptop',
                        'serial'      => 'SN-1234',
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID()],
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
                    'items_ids'  => [$t->createItem(Computer::class, [
                        'name'        => 'My Laptop',
                        'serial'      => 'SN-1234',
                        'otherserial' => 'INV-5678',
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID()],
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
                    'items_ids'  => [$t->createItem(Computer::class, [
                        'name'        => 'My Desktop',
                        'users_id'    => $t->createItem(User::class, [
                            'name'      => 'jdoe',
                            'firstname' => 'John',
                            'realname'  => 'Doe',
                        ])->getID(),
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID()],
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
                    'items_ids'  => [$t->createItem(Computer::class, [
                        'name'        => 'My Workstation',
                        'serial'      => 'SN-AAAA',
                        'otherserial' => 'INV-BBBB',
                        'users_id'    => $t->createItem(User::class, [
                            'name'      => 'jdoe',
                            'firstname' => 'John',
                            'realname'  => 'Doe',
                        ])->getID(),
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID()],
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
                    'items_ids'  => [$t->createItem(Computer::class, [
                        'name'        => 'My Server',
                        'serial'      => '',
                        'otherserial' => '',
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID()],
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
                        'items_ids'  => [$linked->getID()],
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
                        'items_ids'     => [$linked->getID()],
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

    public static function itemAnswerInTicketMultipleProvider(): iterable
    {
        yield 'multiple assets' => [
            fn(self $t) => [
                'itemtype'  => Computer::class,
                'items_ids'  => [
                    $t->createItem(Computer::class, [
                        'name'        => 'Computer A',
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID(),
                    $t->createItem(Computer::class, [
                        'name'        => 'Computer B',
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID(),
                ],
                'extra_data' => json_encode(
                    (new QuestionTypeItemExtraDataConfig(itemtype: Computer::class, is_multiple_items: true))->jsonSerialize()
                ),
                'question'  => 'Assets',
                'content'   => '1) Assets: Computer A, Computer B',
            ],
        ];

        yield 'multiple users' => [
            fn(self $t) => [
                'itemtype'  => User::class,
                'items_ids'  => [
                    $t->createItem(User::class, [
                        'name'      => 'jdoe',
                        'firstname' => 'John',
                        'realname'  => 'Doe',
                    ])->getID(),
                    $t->createItem(User::class, [
                        'name'      => 'asmith',
                        'firstname' => 'Alice',
                        'realname'  => 'Smith',
                    ])->getID(),
                ],
                'extra_data' => json_encode(
                    (new QuestionTypeItemExtraDataConfig(itemtype: User::class, is_multiple_items: true))->jsonSerialize()
                ),
                'question'  => 'Technicians',
                'content'   => '1) Technicians: Doe John, Smith Alice',
            ],
        ];

        yield 'multiple assets with some having empty serials' => [
            fn(self $t) => [
                'itemtype'  => Computer::class,
                'items_ids'  => [
                    $t->createItem(Computer::class, [
                        'name'        => 'Computer A',
                        'serial'      => '',
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID(),
                    $t->createItem(Computer::class, [
                        'name'        => 'Computer B',
                        'serial'      => 'SN-5678',
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID(),
                ],
                'extra_data' => json_encode(
                    (new QuestionTypeItemExtraDataConfig(itemtype: Computer::class, is_multiple_items: true))->jsonSerialize()
                ),
                'question'  => 'Assets',
                'content'   => '1) Assets: Computer A, Computer B - SN-5678',
            ],
        ];

        yield 'multiple ITIL type — IDs appended when not visible in session' => [
            fn(self $t) => (static function () use ($t) {
                $_SESSION['glpiis_ids_visible'] = false;
                $linked1 = $t->createItem(Ticket::class, [
                    'name'        => 'Ticket A',
                    'content'     => 'content A',
                    'entities_id' => $t->getTestRootEntity(true),
                ]);
                $linked2 = $t->createItem(Ticket::class, [
                    'name'        => 'Ticket B',
                    'content'     => 'content B',
                    'entities_id' => $t->getTestRootEntity(true),
                ]);
                return [
                    'itemtype'  => Ticket::class,
                    'items_ids'  => [$linked1->getID(), $linked2->getID()],
                    'extra_data' => json_encode(
                        (new QuestionTypeItemExtraDataConfig(itemtype: Ticket::class, is_multiple_items: true))->jsonSerialize()
                    ),
                    'question'  => 'Linked Tickets',
                    'content'   => '1) Linked Tickets: Ticket A - ' . $linked1->getID() . ', Ticket B - ' . $linked2->getID(),
                ];
            })(),
        ];
    }

    #[DataProvider('itemAnswerInTicketProvider')]
    #[DataProvider('itemAnswerInTicketMultipleProvider')]
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
                'items_ids' => $case['items_ids'],
            ],
        ]);

        $content = strip_tags($ticket->fields['content']);

        $this->assertEquals($case['content'], $content);

        // Clean up any session flags set by the case
        unset($_SESSION['glpiis_ids_visible']);
    }

    /**
     * Data provider for testing the transformation of condition values for comparisons.
     * Each case provides a question configuration, an input itemtype_id value, and the expected output after transformation.
     */
    public static function transformConditionValueForComparisonsProvider()
    {
        yield [
            fn(self $t) => [
                'question_config' => new QuestionTypeItemExtraDataConfig(itemtype: Computer::class),
                'itemtype_id' => ['items_ids' => $t->createItem(Computer::class, [
                    'name'        => 'ComputerTest',
                    'entities_id' => $t->getTestRootEntity(true),
                ])->getID()],
                'expected_name' => 'ComputerTest',
            ],
        ];

        yield [
            fn(self $t) => [
                'question_config' => new QuestionTypeItemExtraDataConfig(itemtype: \ITILCategory::class),
                'itemtype_id' => ['items_ids' => $t->createItem(\ITILCategory::class, [
                    'name' => 'Parent Category',
                    'entities_id' => $t->getTestRootEntity(true),
                ])->getID()],
                'expected_name' => 'Parent Category',
            ],
        ];

        yield [
            fn(self $t) => [
                'question_config' => new QuestionTypeItemExtraDataConfig(itemtype: \ITILCategory::class),
                'itemtype_id' => ['items_ids' => $t->createItem(\ITILCategory::class, [
                    'name' => 'Child Category',
                    'entities_id' => $t->getTestRootEntity(true),
                    'itilcategories_id' => $t->createItem(\ITILCategory::class, [
                        'name' => 'Parent Category',
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID(),
                ])->getID()],
                'expected_name' => 'Parent Category > Child Category',
            ],
        ];
    }

    /**
     * Data provider for testing the transformation of condition values for comparisons.
     * Each case provides a question configuration, an input itemtype_id value with multiple IDs, and the expected output after transformation.
     */
    public static function transformConditionValueForComparisonsMultipleProvider()
    {
        yield 'multiple assets' => [
            fn(self $t) => [
                'question_config' => new QuestionTypeItemExtraDataConfig(itemtype: Computer::class, is_multiple_items: true),
                'itemtype_id' => ['items_ids' => [
                    $t->createItem(Computer::class, [
                        'name'        => 'Computer A',
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID(),
                    $t->createItem(Computer::class, [
                        'name'        => 'Computer B',
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID(),
                ]],
                'expected_name' => 'Computer A, Computer B',
            ],
        ];

        yield 'multiple itil categories - flat dropdown' => [
            fn(self $t) => [
                'question_config' => new QuestionTypeItemExtraDataConfig(itemtype: \ITILCategory::class),
                'itemtype_id' => ['items_ids' => [
                    $t->createItem(\ITILCategory::class, [
                        'name' => 'Parent Category A',
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID(),
                    $t->createItem(\ITILCategory::class, [
                        'name' => 'Parent Category B',
                        'entities_id' => $t->getTestRootEntity(true),
                    ])->getID(),
                ]],
                'expected_name' => 'Parent Category A, Parent Category B',
            ],
        ];

        yield 'multiple itil categories - hierarchical dropdown with parent-child relationship' => [
            fn(self $t) => [
                'question_config' => new QuestionTypeItemExtraDataConfig(itemtype: \ITILCategory::class),
                'itemtype_id' => ['items_ids' => [
                    $t->createItem(\ITILCategory::class, [
                        'name' => 'Child Category A',
                        'entities_id' => $t->getTestRootEntity(true),
                        'itilcategories_id' => $t->createItem(\ITILCategory::class, [
                            'name' => 'Parent Category A',
                            'entities_id' => $t->getTestRootEntity(true),
                        ])->getID(),
                    ])->getID(),
                    $t->createItem(\ITILCategory::class, [
                        'name' => 'Child Category B',
                        'entities_id' => $t->getTestRootEntity(true),
                        'itilcategories_id' => $t->createItem(\ITILCategory::class, [
                            'name' => 'Parent Category B',
                            'entities_id' => $t->getTestRootEntity(true),
                        ])->getID(),
                    ])->getID(),
                ]],
                'expected_name' => 'Parent Category A > Child Category A, Parent Category B > Child Category B',
            ],
        ];
    }

    #[DataProvider('transformConditionValueForComparisonsProvider')]
    #[DataProvider('transformConditionValueForComparisonsMultipleProvider')]
    public function testTransformConditionValueForComparisons(callable $setup): void
    {
        $this->login();

        $question_type = new QuestionTypeItem();
        $case = $setup($this);
        $name = $question_type->transformConditionValueForComparisons(
            $case['itemtype_id'],
            $case['question_config']
        );
        $this->assertEquals($case['expected_name'], $name);
    }

    public static function formatRawAnswerProvider(): array
    {
        return [
            'item is a user' => [
                fn(self $t) => [
                    'answer'   => [
                        'itemtype'  => User::class,
                        'items_ids' => [$t->createItem(User::class, [
                            'name'      => 'jdoe',
                            'firstname' => 'John',
                            'realname'  => 'Doe',
                        ])->getID()],
                    ],
                    'expected' => 'Doe John',
                ],
            ],

            'location without parent' => [
                fn(self $t) => [
                    'answer'   => [
                        'itemtype'  => Location::class,
                        'items_ids' => [$t->createItem(Location::class, [
                            'name'        => 'Headquarters',
                            'entities_id' => $t->getTestRootEntity(true),
                        ])->getID()],
                    ],
                    'expected' => 'Headquarters',
                ],
            ],

            'location with deep hierarchy' => [
                fn(self $t) => (static function () use ($t) {
                    $root = $t->createItem(Location::class, [
                        'name'        => 'France',
                        'entities_id' => $t->getTestRootEntity(true),
                    ]);
                    $mid = $t->createItem(Location::class, [
                        'name'        => 'Paris',
                        'locations_id' => $root->getID(),
                        'entities_id' => $t->getTestRootEntity(true),
                    ]);
                    $leaf = $t->createItem(Location::class, [
                        'name'        => 'Office',
                        'locations_id' => $mid->getID(),
                        'entities_id' => $t->getTestRootEntity(true),
                    ]);
                    return [
                        'answer'   => [
                            'itemtype'  => Location::class,
                            'items_ids' => [$leaf->getID()],
                        ],
                        'expected' => 'France > Paris > Office',
                    ];
                })(),
            ],

            'non-existent item' => [
                fn(self $t) => [
                    'answer'   => [
                        'itemtype'  => Computer::class,
                        'items_ids' => [PHP_INT_MAX],
                    ],
                    'expected' => '',
                ],
            ],
        ];
    }

    public static function formatRawAnswerMultipleProvider(): iterable
    {
        yield 'multiple users' => [
            fn(self $t) => [
                'answer'   => [
                    'itemtype'  => User::class,
                    'items_ids' => [
                        $t->createItem(User::class, [
                            'name'      => 'jdoe',
                            'firstname' => 'John',
                            'realname'  => 'Doe',
                        ])->getID(),
                        $t->createItem(User::class, [
                            'name'      => 'asmith',
                            'firstname' => 'Alice',
                            'realname'  => 'Smith',
                        ])->getID(),
                    ],
                ],
                'expected' => 'Doe John, Smith Alice',
            ],
        ];

        yield 'multiple locations without hierarchy' => [
            fn(self $t) => (static function () use ($t) {
                $loc1 = $t->createItem(Location::class, [
                    'name'        => 'New York Office',
                    'entities_id' => $t->getTestRootEntity(true),
                ]);
                $loc2 = $t->createItem(Location::class, [
                    'name'        => 'Berlin Office',
                    'entities_id' => $t->getTestRootEntity(true),
                ]);
                return [
                    'answer'   => [
                        'itemtype'  => Location::class,
                        'items_ids' => [$loc1->getID(), $loc2->getID()],
                    ],
                    'expected' => 'New York Office, Berlin Office',
                ];
            })(),
        ];

        yield 'multiple locations with hierarchy' => [
            fn(self $t) => (static function () use ($t) {
                $root1 = $t->createItem(Location::class, [
                    'name'        => 'USA',
                    'entities_id' => $t->getTestRootEntity(true),
                ]);
                $leaf1 = $t->createItem(Location::class, [
                    'name'        => 'New York Office',
                    'locations_id' => $root1->getID(),
                    'entities_id' => $t->getTestRootEntity(true),
                ]);
                $root2 = $t->createItem(Location::class, [
                    'name'        => 'Germany',
                    'entities_id' => $t->getTestRootEntity(true),
                ]);
                $leaf2 = $t->createItem(Location::class, [
                    'name'        => 'Berlin Office',
                    'locations_id' => $root2->getID(),
                    'entities_id' => $t->getTestRootEntity(true),
                ]);
                return [
                    'answer'   => [
                        'itemtype'  => Location::class,
                        'items_ids' => [$leaf1->getID(), $leaf2->getID()],
                    ],
                    'expected' => 'USA > New York Office, Germany > Berlin Office',
                ];
            })(),
        ];

        yield 'multiple items with all non-existent' => [
            fn(self $t) => [
                'answer'   => [
                    'itemtype'  => Computer::class,
                    'items_ids' => [PHP_INT_MAX - 1, PHP_INT_MAX],
                ],
                'expected' => '',
            ],
        ];

        yield 'multiple items with some non-existent' => [
            fn(self $t) => [
                'answer'   => [
                    'itemtype'  => Computer::class,
                    'items_ids' => [
                        $t->createItem(Computer::class, [
                            'name'        => 'Existing Computer',
                            'entities_id' => $t->getTestRootEntity(true),
                        ])->getID(),
                        PHP_INT_MAX, // Non-existent ID
                    ],
                ],
                'expected' => 'Existing Computer',
            ],
        ];
    }

    #[DataProvider('formatRawAnswerProvider')]
    #[DataProvider('formatRawAnswerMultipleProvider')]
    public function testFormatRawAnswer(callable $setup): void
    {
        $this->login();

        $case = $setup($this);
        $result = (new QuestionTypeItem())->formatRawAnswer($case['answer'], new Question());

        $this->assertEquals($case['expected'], $result);
    }
}
