<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Entity;
use Glpi\Form\QuestionType\QuestionTypeAssignee;
use Glpi\PHPUnit\Tests\Glpi\Form\QuestionType\AbstractQuestionTypeActorsTest;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Group;
use Profile;
use Profile_User;
use Supplier;
use User;

final class QuestionTypeAssigneeTest extends AbstractQuestionTypeActorsTest
{
    use FormTesterTrait;

    public static function getQuestionType(): string
    {
        return QuestionTypeAssignee::class;
    }

    public static function actorAnswerIsDisplayedInTicketDescriptionProvider(): iterable
    {
        $glpi_id = getItemByTypeName(User::class, "glpi", true);
        $tech_id = getItemByTypeName(User::class, "tech", true);
        $test_group_1_id = getItemByTypeName(Group::class, "_test_group_1", true);
        $test_group_2_id = getItemByTypeName(Group::class, "_test_group_2", true);
        $supplier_01_id = getItemByTypeName(Supplier::class, "_suplier01_name", true);
        $supplier_02_id = getItemByTypeName(Supplier::class, "_suplier02_name", true);

        yield 'simple user' => [
            'answer' => ["users_id-$glpi_id"],
            'expected' => "glpi",
            'is_multiple' => false,
        ];

        yield 'simple group' => [
            'answer' => ["groups_id-$test_group_1_id"],
            'expected' => "_test_group_1",
            'is_multiple' => false,
        ];

        yield 'simple supplier' => [
            'answer' => ["suppliers_id-$supplier_01_id"],
            'expected' => "_suplier01_name",
            'is_multiple' => false,
        ];

        yield 'multiple users' => [
            'answer' => [
                "users_id-$glpi_id",
                "users_id-$tech_id",
            ],
            'expected' => "glpi, tech",
            'is_multiple' => true,
        ];

        yield 'multiple groups' => [
            'answer' => [
                "groups_id-$test_group_1_id",
                "groups_id-$test_group_2_id",
            ],
            'expected' => "_test_group_1, _test_group_2",
            'is_multiple' => true,
        ];

        yield 'multiple suppliers' => [
            'answer' => [
                "suppliers_id-$supplier_01_id",
                "suppliers_id-$supplier_02_id",
            ],
            'expected' => "_suplier01_name, _suplier02_name",
            'is_multiple' => true,
        ];
    }

    public static function invalidActorsProvider(): iterable
    {
        yield 'invalid user' => [
            [User::getForeignKeyField() . "-999999"],
            'expected_exception' => \Exception::class,
            'expected_message' => "Invalid actor ID: 999999",
        ];

        yield 'invalid group' => [
            [Group::getForeignKeyField() . "-999999"],
            'expected_exception' => \Exception::class,
            'expected_message' => "Invalid actor ID: 999999",
        ];

        yield 'invalid supplier' => [
            [Supplier::getForeignKeyField() . "-999999"],
            'expected_exception' => \Exception::class,
            'expected_message' => "Invalid actor ID: 999999",
        ];

        yield 'invalid user and group' => [
            [User::getForeignKeyField() . "-999999", Group::getForeignKeyField() . "-999999"],
            'expected_exception' => \Exception::class,
            'expected_message' => "Invalid actor ID: 999999",
        ];

        yield 'valid user and invalid group' => [
            [
                User::getForeignKeyField() . "-" . getItemByTypeName(User::class, "glpi", true),
                Group::getForeignKeyField() . "-999999"
            ],
            'expected_exception' => \Exception::class,
            'expected_message' => "Invalid actor ID: 999999",
        ];

        yield 'multiple valid actors for single actors question' => [
            [
                User::getForeignKeyField() . "-" . getItemByTypeName(User::class, "glpi", true),
                Group::getForeignKeyField() . "-" . getItemByTypeName(Group::class, "_test_group_1", true),
            ],
            'expected_exception' => \Exception::class,
            'expected_message' => "Multiple actors are not allowed",
        ];
    }

    public static function validActorsProvider(): iterable
    {
        $glpi_id = getItemByTypeName(User::class, "glpi", true);
        $tech_id = getItemByTypeName(User::class, "tech", true);
        $test_group_1_id = getItemByTypeName(Group::class, "_test_group_1", true);
        $test_group_2_id = getItemByTypeName(Group::class, "_test_group_2", true);
        $supplier_01_id = getItemByTypeName(Supplier::class, "_suplier01_name", true);
        $supplier_02_id = getItemByTypeName(Supplier::class, "_suplier02_name", true);

        yield 'valid user' => [
            [User::getForeignKeyField() . "-$glpi_id"],
            [
                [
                    'itemtype' => User::class,
                    'items_id' => $glpi_id,
                ]
            ],
            false
        ];

        yield 'valid group' => [
            [Group::getForeignKeyField() . "-$test_group_1_id"],
            [
                [
                    'itemtype' => Group::class,
                    'items_id' => $test_group_1_id,
                ]
            ],
            false
        ];

        yield 'valid supplier' => [
            [Supplier::getForeignKeyField() . "-$supplier_01_id"],
            [
                [
                    'itemtype' => Supplier::class,
                    'items_id' => $supplier_01_id,
                ]
            ]
        ];

        yield 'multiple valid users' => [
            [
                User::getForeignKeyField() . "-$glpi_id",
                User::getForeignKeyField() . "-$tech_id",
            ],
            [
                [
                    'itemtype' => User::class,
                    'items_id' => $glpi_id,
                ],
                [
                    'itemtype' => User::class,
                    'items_id' => $tech_id,
                ]
            ],
            true
        ];

        yield 'multiple valid groups' => [
            [
                Group::getForeignKeyField() . "-$test_group_1_id",
                Group::getForeignKeyField() . "-$test_group_2_id",
            ],
            [
                [
                    'itemtype' => Group::class,
                    'items_id' => $test_group_1_id,
                ],
                [
                    'itemtype' => Group::class,
                    'items_id' => $test_group_2_id,
                ]
            ],
            true
        ];

        yield 'multiple valid suppliers' => [
            [
                Supplier::getForeignKeyField() . "-$supplier_01_id",
                Supplier::getForeignKeyField() . "-$supplier_02_id",
            ],
            [
                [
                    'itemtype' => Supplier::class,
                    'items_id' => $supplier_01_id,
                ],
                [
                    'itemtype' => Supplier::class,
                    'items_id' => $supplier_02_id,
                ]
            ],
            true
        ];
    }

    public function testSubmitAnswerWithUserThatCanBeAssignee(): void
    {
        // Create a User
        $user = $this->createItem(User::class, [
            'name' => 'jdoe',
        ]);

        // Create a profile that allows the user to be assigned
        $this->createItem(Profile_User::class, [
            'users_id' => $user->getID(),
            'profiles_id' => getItemByTypeName(Profile::class, 'Super-Admin', true),
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);

        // Create a form
        $builder = new FormBuilder();
        $builder->setEntitiesId($this->getTestRootEntity(only_id: true));
        $builder->addQuestion("Assigned", QuestionTypeAssignee::class);
        $form = $this->createForm($builder);

        // Send the form again
        $this->assertNotNull(
            $this->sendFormAndGetAnswerSet($form, [
                "Assigned" => ["users_id-{$user->getID()}"],
            ])
        );
    }

    public function testSubmitAnswerWithUserThatCannotBeAssignee(): void
    {
        // Create a User
        $user = $this->createItem(User::class, [
            'name' => 'jdoe',
        ]);

        // Create a form
        $builder = new FormBuilder();
        $builder->setEntitiesId($this->getTestRootEntity(only_id: true));
        $builder->addQuestion("Assigned", QuestionTypeAssignee::class);
        $form = $this->createForm($builder);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Invalid actor: must be able to be assigned");
        $this->assertNull(
            $this->sendFormAndGetAnswerSet($form, [
                "Assigned" => ["users_id-{$user->getID()}"],
            ])
        );
    }

    public function testSubmitAnswerWithUserThatCanBeAssigneeWithOwnRightInParentEntityWithoutRecursiveProfile(): void
    {
        $this->login();

        // Create a User
        $user = $this->createItem(User::class, [
            'name' => 'jdoe',
        ]);

        // Create an entity
        $entity = $this->createItem(Entity::class, [
            'name'        => 'Another entity',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);

        // Create a profile that allows the user to be assigned
        $this->createItem(Profile_User::class, [
            'users_id'     => $user->getID(),
            'profiles_id'  => getItemByTypeName(Profile::class, 'Super-Admin', true),
            'entities_id'  => $this->getTestRootEntity(only_id: true),
            'is_recursive' => false,
        ]);

        // Create a form
        $builder = new FormBuilder();
        $builder->setEntitiesId($entity->getID());
        $builder->addQuestion("Assigned", QuestionTypeAssignee::class);
        $form = $this->createForm($builder);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Invalid actor: must be able to be assigned");
        $this->assertNull(
            $this->sendFormAndGetAnswerSet($form, [
                "Assigned" => ["users_id-{$user->getID()}"],
            ])
        );
    }

    public function testSubmitAnswerWithUserThatCanBeAssigneeWithRecursiveProfileInParentEntity(): void
    {
        $this->login();

        // Create a User
        $user = $this->createItem(User::class, [
            'name' => 'jdoe',
        ]);

        // Create an entity
        $entity = $this->createItem(Entity::class, [
            'name'        => 'Another entity',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);

        // Create a profile that allows the user to be assigned
        $this->createItem(Profile_User::class, [
            'users_id'     => $user->getID(),
            'profiles_id'  => getItemByTypeName(Profile::class, 'Super-Admin', true),
            'entities_id'  => $this->getTestRootEntity(only_id: true),
            'is_recursive' => true,
        ]);

        // Create a form
        $builder = new FormBuilder();
        $builder->setEntitiesId($entity->getID());
        $builder->addQuestion("Assigned", QuestionTypeAssignee::class);
        $form = $this->createForm($builder);

        $this->assertNotNull(
            $this->sendFormAndGetAnswerSet($form, [
                "Assigned" => ["users_id-{$user->getID()}"],
            ])
        );
    }

    public static function groupActorProvider(): iterable
    {
        yield 'valid group' => [
            'questionType' => QuestionTypeAssignee::class,
            'actorField'   => "is_assign",
            'canBeActor'   => true,
        ];

        yield 'invalid group' => [
            'questionType' => QuestionTypeAssignee::class,
            'actorField'   => "is_assign",
            'canBeActor'   => false,
            'expectedMessage' => "Invalid actor: must be able to be assigned",
        ];
    }
}
