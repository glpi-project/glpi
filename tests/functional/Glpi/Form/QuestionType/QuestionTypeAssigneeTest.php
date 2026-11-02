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

use Glpi\Form\QuestionType\QuestionTypeAssignee;
use Glpi\Tests\FormTesterTrait;
use Glpi\Tests\Glpi\Form\QuestionType\AbstractQuestionTypeActorsTest;
use Group;
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
            'answer' => [User::getForeignKeyField() . "-999999"],
            'expected_exception' => \Exception::class,
            'expected_message' => "Invalid actor ID: 999999",
        ];

        yield 'invalid group' => [
            'answer' => [Group::getForeignKeyField() . "-999999"],
            'expected_exception' => \Exception::class,
            'expected_message' => "Invalid actor ID: 999999",
        ];

        yield 'invalid supplier' => [
            'answer' => [Supplier::getForeignKeyField() . "-999999"],
            'expected_exception' => \Exception::class,
            'expected_message' => "Invalid actor ID: 999999",
        ];

        yield 'invalid user and group' => [
            'answer' => [User::getForeignKeyField() . "-999999", Group::getForeignKeyField() . "-999999"],
            'expected_exception' => \Exception::class,
            'expected_message' => "Invalid actor ID: 999999",
        ];

        yield 'valid user and invalid group' => [
            'answer' => [
                User::getForeignKeyField() . "-" . getItemByTypeName(User::class, "glpi", true),
                Group::getForeignKeyField() . "-999999",
            ],
            'expected_exception' => \Exception::class,
            'expected_message' => "Invalid actor ID: 999999",
        ];

        yield 'multiple valid actors for single actors question' => [
            'answer' => [
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
            'answer' => [User::getForeignKeyField() . "-$glpi_id"],
            'expected' => [
                [
                    'itemtype' => User::class,
                    'items_id' => $glpi_id,
                ],
            ],
            'allow_multiple_actors' => false,
        ];

        yield 'valid group' => [
            'answer' => [Group::getForeignKeyField() . "-$test_group_1_id"],
            'expected' => [
                [
                    'itemtype' => Group::class,
                    'items_id' => $test_group_1_id,
                ],
            ],
            'allow_multiple_actors' => false,
        ];

        yield 'valid supplier' => [
            'answer' => [Supplier::getForeignKeyField() . "-$supplier_01_id"],
            'expected' => [
                [
                    'itemtype' => Supplier::class,
                    'items_id' => $supplier_01_id,
                ],
            ],
        ];

        yield 'multiple valid users' => [
            'answer' => [
                User::getForeignKeyField() . "-$glpi_id",
                User::getForeignKeyField() . "-$tech_id",
            ],
            'expected' => [
                [
                    'itemtype' => User::class,
                    'items_id' => $glpi_id,
                ],
                [
                    'itemtype' => User::class,
                    'items_id' => $tech_id,
                ],
            ],
            'allow_multiple_actors' => true,
        ];

        yield 'multiple valid groups' => [
            'answer' => [
                Group::getForeignKeyField() . "-$test_group_1_id",
                Group::getForeignKeyField() . "-$test_group_2_id",
            ],
            'expected' => [
                [
                    'itemtype' => Group::class,
                    'items_id' => $test_group_1_id,
                ],
                [
                    'itemtype' => Group::class,
                    'items_id' => $test_group_2_id,
                ],
            ],
            'allow_multiple_actors' => true,
        ];

        yield 'multiple valid suppliers' => [
            'answer' => [
                Supplier::getForeignKeyField() . "-$supplier_01_id",
                Supplier::getForeignKeyField() . "-$supplier_02_id",
            ],
            'expected' => [
                [
                    'itemtype' => Supplier::class,
                    'items_id' => $supplier_01_id,
                ],
                [
                    'itemtype' => Supplier::class,
                    'items_id' => $supplier_02_id,
                ],
            ],
            'allow_multiple_actors' => true,
        ];
    }

    // Whether a User actor can actually be assigned depends on the ITIL
    // object's entity, which is not known yet when answers are prepared.
    // This is covered at the point where that entity is resolved, in
    // ITILActorField::isActorAllowed() (see AssigneeFieldTest).

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
