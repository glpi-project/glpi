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

use DbTestCase;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\QuestionType\QuestionTypeAssignee;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Group;
use PHPUnit\Framework\Attributes\DataProvider;
use Profile;
use Profile_User;
use Supplier;
use User;

final class QuestionTypeAssigneeTest extends DbTestCase
{
    use FormTesterTrait;

    public static function assigneeAnswerIsDisplayedInTicketDescriptionProvider(): iterable
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

    #[DataProvider("assigneeAnswerIsDisplayedInTicketDescriptionProvider")]
    public function testAssigneeAnswerIsDisplayedInTicketDescription(
        array $answer,
        string $expected,
        bool $is_multiple
    ): void {
        $builder = new FormBuilder();
        $builder->addQuestion("Assigned", QuestionTypeAssignee::class);
        $builder->addDestination(
            FormDestinationTicket::class,
            "My ticket",
            ['is_multiple_actors' => $is_multiple]
        );
        $form = $this->createForm($builder);

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Assigned" => $answer,
        ]);

        $this->assertStringContainsString(
            "1) Assigned: $expected",
            strip_tags($ticket->fields['content']),
        );
    }

    public function testAssignedUserWithFullNameIsDisplayedInTicketDescription(): void
    {
        // Create a user with a fully qualified name and allow him to be an assignee by making him super admin profile
        $john_doe = $this->createItem(User::class, [
            'name' => 'jdoe',
            'firstname' => 'John',
            'realname' => 'Doe',
        ]);
        $this->createItem(Profile_User::class, [
            'users_id' => $john_doe->getID(),
            'profiles_id' => getItemByTypeName(Profile::class, 'Super-Admin', true),
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);

        $builder = new FormBuilder();
        $builder->addQuestion("Assigned", QuestionTypeAssignee::class);
        $builder->addDestination(FormDestinationTicket::class, "My ticket");
        $form = $this->createForm($builder);

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Assigned" => ["users_id-{$john_doe->getID()}"],
        ]);

        $this->assertStringContainsString(
            "1) Assigned: Doe John",
            strip_tags($ticket->fields['content']),
        );
    }

    // TODO: add a test to validate that invalid answers are refused:
    // - users/groups/suppliers that does not have the right to be assignee
    // - multiple values when the question is not configured to accept multiple answers
}
