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
use Glpi\Form\QuestionType\QuestionTypeItemDefaultValueConfig;
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
     *     'items_id'   => int,
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

    /**
     * Data provider for testing the transformation of condition values for comparisons.
     * Each case provides a question configuration, an input itemtype_id value, and the expected output after transformation.
     */
    public static function transformConditionValueForComparisonsProvider()
    {
        yield [
            fn(self $t) => [
                'question_config' => new QuestionTypeItemExtraDataConfig(itemtype: Computer::class),
                'itemtype_id' => ['items_id' => $t->createItem(Computer::class, [
                    'name'        => 'ComputerTest',
                    'entities_id' => $t->getTestRootEntity(true),
                ])->getID()],
                'expected_name' => 'ComputerTest',
            ],
        ];

        yield [
            fn(self $t) => [
                'question_config' => new QuestionTypeItemExtraDataConfig(itemtype: \ITILCategory::class),
                'itemtype_id' => ['items_id' => $t->createItem(\ITILCategory::class, [
                    'name' => 'Parent Category',
                    'entities_id' => $t->getTestRootEntity(true),
                ])->getID()],
                'expected_name' => 'Parent Category',
            ],
        ];

        yield [
            fn(self $t) => [
                'question_config' => new QuestionTypeItemExtraDataConfig(itemtype: \ITILCategory::class),
                'itemtype_id' => ['items_id' => $t->createItem(\ITILCategory::class, [
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

    #[DataProvider('transformConditionValueForComparisonsProvider')]
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
                        'itemtype' => User::class,
                        'items_id' => $t->createItem(User::class, [
                            'name'      => 'jdoe',
                            'firstname' => 'John',
                            'realname'  => 'Doe',
                        ])->getID(),
                    ],
                    'expected' => 'Doe John',
                ],
            ],

            'location without parent' => [
                fn(self $t) => [
                    'answer'   => [
                        'itemtype' => Location::class,
                        'items_id' => $t->createItem(Location::class, [
                            'name'        => 'Headquarters',
                            'entities_id' => $t->getTestRootEntity(true),
                        ])->getID(),
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
                            'itemtype' => Location::class,
                            'items_id' => $leaf->getID(),
                        ],
                        'expected' => 'France > Paris > Office',
                    ];
                })(),
            ],

            'non-existent item' => [
                fn(self $t) => [
                    'answer'   => [
                        'itemtype' => Computer::class,
                        'items_id' => PHP_INT_MAX,
                    ],
                    'expected' => '',
                ],
            ],
        ];
    }

    public static function formatPredefinedValueProvider(): array
    {
        return [
            'valid positive integer' => [
                'value'    => '42',
                'expected' => json_encode((new QuestionTypeItemDefaultValueConfig(42))->jsonSerialize()),
            ],
            'valid id of 1' => [
                'value'    => '1',
                'expected' => json_encode((new QuestionTypeItemDefaultValueConfig(1))->jsonSerialize()),
            ],
            'zero is rejected' => [
                'value'    => '0',
                'expected' => null,
            ],
            'negative number is rejected' => [
                'value'    => '-5',
                'expected' => null,
            ],
            'non-numeric string is rejected' => [
                'value'    => 'abc',
                'expected' => null,
            ],
            'float string is rejected' => [
                'value'    => '1.5',
                'expected' => null,
            ],
            'empty string is rejected' => [
                'value'    => '',
                'expected' => null,
            ],
        ];
    }

    #[DataProvider('formatPredefinedValueProvider')]
    public function testFormatPredefinedValue(string $value, ?string $expected): void
    {
        $this->assertSame($expected, (new QuestionTypeItem())->formatPredefinedValue($value));
    }

    #[DataProvider('formatRawAnswerProvider')]
    public function testFormatRawAnswer(callable $setup): void
    {
        $this->login();

        $case = $setup($this);
        $result = (new QuestionTypeItem())->formatRawAnswer($case['answer'], new Question());

        $this->assertEquals($case['expected'], $result);
    }
}
