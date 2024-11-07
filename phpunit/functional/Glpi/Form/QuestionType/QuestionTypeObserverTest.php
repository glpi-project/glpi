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

namespace tests\units\Glpi\Form\QuestionType;

use Glpi\Form\QuestionType\QuestionTypeObserver;
use Glpi\PHPUnit\Tests\Glpi\Form\QuestionType\AbstractQuestionTypeActorsTest;
use Glpi\Tests\FormTesterTrait;
use Group;
use User;

final class QuestionTypeObserverTest extends AbstractQuestionTypeActorsTest
{
    use FormTesterTrait;

    public static function getQuestionType(): string
    {
        return QuestionTypeObserver::class;
    }

    public static function actorAnswerIsDisplayedInTicketDescriptionProvider(): iterable
    {
        $glpi_id = getItemByTypeName(User::class, "glpi", true);
        $tech_id = getItemByTypeName(User::class, "tech", true);
        $test_group_1_id = getItemByTypeName(Group::class, "_test_group_1", true);
        $test_group_2_id = getItemByTypeName(Group::class, "_test_group_2", true);

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
    }

    public static function groupActorProvider(): iterable
    {
        yield 'valid group' => [
            'questionType' => QuestionTypeObserver::class,
            'actorField'   => "is_watcher",
            'canBeActor'   => true,
        ];

        yield 'invalid group' => [
            'questionType' => QuestionTypeObserver::class,
            'actorField'   => "is_watcher",
            'canBeActor'   => false,
            'expectedMessage' => "Invalid actor: must be an observer",
        ];
    }
}
