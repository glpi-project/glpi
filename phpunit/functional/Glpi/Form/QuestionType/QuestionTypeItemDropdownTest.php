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

namespace tests\units\Glpi\Form\QuestionType;

use DbTestCase;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Form\QuestionType\QuestionTypeItemDropdownExtraDataConfig;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use ITILCategory;
use PHPUnit\Framework\Attributes\DataProvider;

final class QuestionTypeItemDropdownTest extends DbTestCase
{
    use FormTesterTrait;

    public function testItemDropdownAnswerIsDisplayedInTicketDescription(): void
    {
        $this->login();

        $category = $this->createItem(ITILCategory::class, [
            'name' => 'My category',
        ]);

        $builder = new FormBuilder();
        $builder->addQuestion("Category", QuestionTypeItemDropdown::class, 0, json_encode(
            ['itemtype' => ITILCategory::getType(), 'categories_filter' => [], 'root_items_id' => 0, 'subtree_depth' => 0, 'selectable_tree_root' => false]
        ));
        $form = $this->createForm($builder);

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Category" => [
                'itemtype' => ITILCategory::class,
                'items_id' => $category->getID(),
            ],
        ]);

        $this->assertStringContainsString(
            "1) Category: My category",
            strip_tags($ticket->fields['content']),
        );
    }

    /**
     * Data provider for testGetDropdownRestrictionParams
     */
    public static function dropdownRestrictionParamsProvider(): array
    {
        return [
            'simple dropdown with active items' => [
                new QuestionTypeItemDropdownExtraDataConfig(
                    itemtype: \RequestType::class,
                    categories_filter: [],
                    root_items_id: 0,
                    subtree_depth: 0,
                    selectable_tree_root: false,
                ),
                ['WHERE' => ['glpi_requesttypes.is_active' => 1]],
            ],
            'ITILCategory with request filter' => [
                new QuestionTypeItemDropdownExtraDataConfig(
                    itemtype: ITILCategory::class,
                    categories_filter: ['request'],
                    root_items_id: 0,
                    subtree_depth: 0,
                    selectable_tree_root: false,
                ),
                ['WHERE' => [
                    ['OR' => ['glpi_itilcategories.is_request' => 1]],
                ]],
            ],
            'ITILCategory with incident filter' => [
                new QuestionTypeItemDropdownExtraDataConfig(
                    itemtype: ITILCategory::class,
                    categories_filter: ['incident'],
                    root_items_id: 0,
                    subtree_depth: 0,
                    selectable_tree_root: false,
                ),
                ['WHERE' => [
                    ['OR' => ['glpi_itilcategories.is_incident' => 1]],
                ]],
            ],
            'ITILCategory with multiple filters' => [
                new QuestionTypeItemDropdownExtraDataConfig(
                    itemtype: ITILCategory::class,
                    categories_filter: ['request', 'incident'],
                    root_items_id: 0,
                    subtree_depth: 0,
                    selectable_tree_root: false,
                ),
                ['WHERE' => [
                    ['OR' => [
                        'glpi_itilcategories.is_request' => 1,
                        'glpi_itilcategories.is_incident' => 1,
                    ]],
                ]],
            ],
            'ITILCategory with change filter' => [
                new QuestionTypeItemDropdownExtraDataConfig(
                    itemtype: ITILCategory::class,
                    categories_filter: ['change'],
                    root_items_id: 0,
                    subtree_depth: 0,
                    selectable_tree_root: false,
                ),
                ['WHERE' => [
                    ['OR' => ['glpi_itilcategories.is_change' => 1]],
                ]],
            ],
            'ITILCategory with problem filter' => [
                new QuestionTypeItemDropdownExtraDataConfig(
                    itemtype: ITILCategory::class,
                    categories_filter: ['problem'],
                    root_items_id: 0,
                    subtree_depth: 0,
                    selectable_tree_root: false,
                ),
                ['WHERE' => [
                    ['OR' => ['glpi_itilcategories.is_problem' => 1]],
                ]],
            ],
            'ITILCategory with all filters' => [
                'config' => new QuestionTypeItemDropdownExtraDataConfig(
                    itemtype: ITILCategory::class,
                    categories_filter: ['request', 'incident', 'change', 'problem'],
                    root_items_id: 0,
                    subtree_depth: 0,
                    selectable_tree_root: false,
                ),
                'expected' => ['WHERE' => [
                    ['OR' => [
                        'glpi_itilcategories.is_request' => 1,
                        'glpi_itilcategories.is_incident' => 1,
                        'glpi_itilcategories.is_change' => 1,
                        'glpi_itilcategories.is_problem' => 1,
                    ]],
                ]],
            ],
        ];
    }

    #[DataProvider('dropdownRestrictionParamsProvider')]
    public function testGetDropdownRestrictionParams(
        QuestionTypeItemDropdownExtraDataConfig $config,
        array $expected = []
    ): void {
        $this->login();

        $builder = new FormBuilder();
        $builder->addQuestion(
            name: "Test Question",
            type: QuestionTypeItemDropdown::class,
            extra_data: json_encode($config),
        );
        $form = $this->createForm($builder);
        $question = current($form->getQuestions());

        $result = (new QuestionTypeItemDropdown())->getDropdownRestrictionParams($question);

        $this->assertEquals($expected, $result);
    }

    #[DataProvider('dropdownRestrictionParamsProvider')]
    public function testGetDropdownRestrictionParamsInSelfService(
        QuestionTypeItemDropdownExtraDataConfig $config,
        array $expected = []
    ): void {
        $this->login();

        // Ensure the user has a profile that allows access to the Self-Service profile
        $this->createItem(\Profile_User::class, [
            'users_id'    => \Session::getLoginUserID(),
            'profiles_id' => getItemByTypeName(\Profile::class, 'Self-Service', true),
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // Re-login to apply the new profile
        $this->login();

        // Change the session profile to Self-Service
        \Session::changeProfile(getItemByTypeName(\Profile::class, 'Self-Service', true));

        $builder = new FormBuilder();
        $builder->addQuestion(
            name: "Test Question",
            type: QuestionTypeItemDropdown::class,
            extra_data: json_encode($config),
        );
        $form = $this->createForm($builder);
        $question = current($form->getQuestions());

        $result = (new QuestionTypeItemDropdown())->getDropdownRestrictionParams($question);

        if ($config->getItemtype() === ITILCategory::class) {
            $expected['WHERE']['is_helpdeskvisible'] = 1;
        }

        $this->assertEquals($expected, $result);
    }

    public static function dropdownRestrictionParamsWithTreeProvider(): array
    {
        return [
            'ITILCategory without dropdown tree' => [
                'itemtype' => ITILCategory::class,
                'config' => fn() => new QuestionTypeItemDropdownExtraDataConfig(
                    itemtype: ITILCategory::class,
                    categories_filter: ['request'],
                    root_items_id: 0,
                    subtree_depth: 0,
                    selectable_tree_root: false,
                ),
                'expected' => fn() => [
                    'WHERE' => [
                        ['OR' => ['glpi_itilcategories.is_request' => 1]],
                    ],
                ],
            ],
            'ITILCategory with request filter and dropdown tree' => [
                'itemtype' => ITILCategory::class,
                'config' => fn() => new QuestionTypeItemDropdownExtraDataConfig(
                    itemtype: ITILCategory::class,
                    categories_filter: ['request'],
                    root_items_id: getItemByTypeName(ITILCategory::class, 'Root Item', true),
                    subtree_depth: 0,
                    selectable_tree_root: true,
                ),
                'expected' => fn() => [
                    'WHERE' => [
                        ['OR' => ['glpi_itilcategories.is_request' => 1]],
                        'glpi_itilcategories.id' => [
                            getItemByTypeName(ITILCategory::class, 'Root Item', true) => getItemByTypeName(ITILCategory::class, 'Root Item', true),
                            getItemByTypeName(ITILCategory::class, 'Child Item', true) => getItemByTypeName(ITILCategory::class, 'Child Item', true),
                            getItemByTypeName(ITILCategory::class, 'Deep Child Item', true) => getItemByTypeName(ITILCategory::class, 'Deep Child Item', true),
                        ],
                    ],
                ],
            ],
            'ITILCategory with request filter, dropdown tree and depth' => [
                'itemtype' => ITILCategory::class,
                'config' => fn() => new QuestionTypeItemDropdownExtraDataConfig(
                    itemtype: ITILCategory::class,
                    categories_filter: ['request'],
                    root_items_id: getItemByTypeName(ITILCategory::class, 'Root Item', true),
                    subtree_depth: 1,
                    selectable_tree_root: true,
                ),
                'expected' => fn() => [
                    'WHERE' => [
                        ['OR' => ['glpi_itilcategories.is_request' => 1]],
                        'glpi_itilcategories.id' => [
                            getItemByTypeName(ITILCategory::class, 'Root Item', true) => getItemByTypeName(ITILCategory::class, 'Root Item', true),
                            getItemByTypeName(ITILCategory::class, 'Child Item', true) => getItemByTypeName(ITILCategory::class, 'Child Item', true),
                            getItemByTypeName(ITILCategory::class, 'Deep Child Item', true) => getItemByTypeName(ITILCategory::class, 'Deep Child Item', true),
                        ],
                        'glpi_itilcategories.level' => ['<=', 2],
                    ],
                ],
            ],
            'ITILCategory with request filter, dropdown tree, depth and child item as root' => [
                'itemtype' => ITILCategory::class,
                'config' => fn() => new QuestionTypeItemDropdownExtraDataConfig(
                    itemtype: ITILCategory::class,
                    categories_filter: ['request'],
                    root_items_id: getItemByTypeName(ITILCategory::class, 'Child Item', true),
                    subtree_depth: 1,
                    selectable_tree_root: true,
                ),
                'expected' => fn() => [
                    'WHERE' => [
                        ['OR' => ['glpi_itilcategories.is_request' => 1]],
                        'glpi_itilcategories.id' => [
                            getItemByTypeName(ITILCategory::class, 'Child Item', true) => getItemByTypeName(ITILCategory::class, 'Child Item', true),
                            getItemByTypeName(ITILCategory::class, 'Deep Child Item', true) => getItemByTypeName(ITILCategory::class, 'Deep Child Item', true),
                        ],
                        'glpi_itilcategories.level' => ['<=', 3],
                    ],
                ],
            ],
            'ITILCategory with request filter, dropdown tree, depth, child item as root and disabled selectable_tree_root' => [
                'itemtype' => ITILCategory::class,
                'config' => fn() => new QuestionTypeItemDropdownExtraDataConfig(
                    itemtype: ITILCategory::class,
                    categories_filter: ['request'],
                    root_items_id: getItemByTypeName(ITILCategory::class, 'Child Item', true),
                    subtree_depth: 1,
                    selectable_tree_root: false,
                ),
                'expected' => fn() => [
                    'WHERE' => [
                        ['OR' => ['glpi_itilcategories.is_request' => 1]],
                        'glpi_itilcategories.id' => [
                            getItemByTypeName(ITILCategory::class, 'Deep Child Item', true) => getItemByTypeName(ITILCategory::class, 'Deep Child Item', true),
                        ],
                        'glpi_itilcategories.level' => ['<=', 3],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('dropdownRestrictionParamsWithTreeProvider')]
    public function testGetDropdownRestrictionParamsWithDropdownTree(
        string $itemtype,
        callable $config,
        callable $expected
    ): void {
        $this->login();

        // Create a root item for the dropdown tree
        $rootItem = $this->createItem($itemtype, [
            'name' => 'Root Item',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // Create a child item for the dropdown tree
        $childItem = $this->createItem($itemtype, [
            'name' => 'Child Item',
            getForeignKeyFieldForItemType($itemtype) => $rootItem->getID(),
        ]);

        // Create a child item with a deeper level
        $deepChildItem = $this->createItem($itemtype, [
            'name' => 'Deep Child Item',
            getForeignKeyFieldForItemType($itemtype) => $childItem->getID(),
        ]);

        // Create another root item
        $anotherRootItem = $this->createItem($itemtype, [
            'name' => 'Another Root Item',
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // Create a form with the dropdown question
        $builder = new FormBuilder();
        $builder->addQuestion(
            name: "Test Question",
            type: QuestionTypeItemDropdown::class,
            extra_data: json_encode($config()),
        );
        $form = $this->createForm($builder);
        $question = current($form->getQuestions());

        $result = (new QuestionTypeItemDropdown())->getDropdownRestrictionParams($question);
        $this->assertEquals($expected(), $result);
    }
}
