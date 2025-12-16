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

namespace Glpi\Tests\Glpi\Form\QuestionType;

use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeActorsDefaultValueConfig;
use Glpi\Form\QuestionType\QuestionTypeActorsExtraDataConfig;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Group;
use PHPUnit\Framework\Attributes\DataProvider;
use Profile;
use Profile_User;
use Supplier;
use User;

abstract class AbstractQuestionTypeActorsTest extends DbTestCase
{
    use FormTesterTrait;

    abstract public static function getQuestionType(): string;

    abstract public static function actorAnswerIsDisplayedInTicketDescriptionProvider();

    abstract public static function validActorsProvider();

    abstract public static function invalidActorsProvider();

    abstract public static function groupActorProvider(): iterable;

    #[DataProvider("actorAnswerIsDisplayedInTicketDescriptionProvider")]
    public function testActorAnswerIsDisplayedInTicketDescription(
        array $answer,
        string $expected,
        bool $is_multiple
    ): void {
        $builder = new FormBuilder();
        $builder->addQuestion(
            "Question",
            static::getQuestionType(),
            "",
            json_encode((new QuestionTypeActorsExtraDataConfig($is_multiple))->jsonSerialize())
        );
        $form = $this->createForm($builder);

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Question" => $answer,
        ]);

        $this->assertStringContainsString(
            "1) Question: $expected",
            strip_tags($ticket->fields['content']),
        );
    }

    #[DataProvider("validActorsProvider")]
    #[DataProvider("invalidActorsProvider")]
    public function testSubmitAnswerWithActor(
        array $answer,
        ?array $expected = null,
        ?bool $allow_multiple_actors = false,
        ?string $expected_exception = null,
        ?string $expected_message = null
    ): void {
        // Create form
        $builder = new FormBuilder();
        $builder->addQuestion(
            "Observer",
            static::getQuestionType(),
            "",
            json_encode((new QuestionTypeActorsExtraDataConfig($allow_multiple_actors))->jsonSerialize())
        );
        $form = $this->createForm($builder);

        if ($expected_exception) {
            $this->expectException($expected_exception);
            $this->expectExceptionMessage($expected_message);
            $this->assertNull(
                $this->sendFormAndGetAnswerSet($form, [
                    "Observer" => $answer,
                ])
            );
        } else {
            $this->assertEquals(
                $expected,
                $this->sendFormAndGetAnswerSet($form, [
                    "Observer" => $answer,
                ])->getAnswerByQuestionId($this->getQuestionId($form, "Observer"))->getRawAnswer()
            );
        }
    }

    #[DataProvider("groupActorProvider")]
    public function testSubmitAnswerWithGroupActor(
        string $questionType,
        string $actorField,
        bool $canBeActor,
        ?string $expectedMessage = null
    ): void {
        // Create a Group
        $group = $this->createItem(Group::class, [
            'name'        => "testSubmitAnswerWithGroupActor",
            'entities_id' => $this->getTestRootEntity(only_id: true),
            $actorField   => $canBeActor ? 1 : 0,
        ]);

        // Create a form
        $builder = new FormBuilder();
        $builder->setEntitiesId($this->getTestRootEntity(only_id: true));
        $builder->addQuestion("Question", $questionType);
        $form = $this->createForm($builder);

        if ($canBeActor) {
            $this->assertNotNull(
                $this->sendFormAndGetAnswerSet($form, [
                    "Question" => ["groups_id-{$group->getID()}"],
                ])
            );
        } else {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage($expectedMessage);
            $this->assertNull(
                $this->sendFormAndGetAnswerSet($form, [
                    "Question" => ["groups_id-{$group->getID()}"],
                ])
            );
        }
    }

    public function testActorUserWithFullNameIsDisplayedInTicketDescription(): void
    {
        // Create a user with a fully qualified name and allow him to be an Observer by making him super admin profile
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
        $builder->addQuestion("Question", static::getQuestionType());
        $form = $this->createForm($builder);

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Question" => ["users_id-{$john_doe->getID()}"],
        ]);

        $this->assertStringContainsString(
            "1) Question: Doe John",
            strip_tags($ticket->fields['content']),
        );
    }

    /**
     * If the user does not define a default value for an actor type question,
     * the default value will correspond to the default value used for dropdowns.
     * Thus, the returned value will be "0"
     */
    public function testDefaultValueCanBeEmpty(): void
    {
        $builder = (new FormBuilder())
            ->addQuestion("Question", static::getQuestionType());
        $form = $this->createForm($builder);

        $this->updateItem(
            Question::class,
            $this->getQuestionId($form, "Question"),
            [
                'default_value' => ["0"],
            ],
            ['default_value'] // Normally the field doesn't correspond to the defined value, don't verify it here
        );

        $question = Question::getById($this->getQuestionId($form, "Question"));
        $this->assertEquals(
            json_encode(new QuestionTypeActorsDefaultValueConfig()),
            $question->fields['default_value'],
        );
    }

    public function testIsTypeEnabledWithNullQuestion(): void
    {
        $question_type = new (static::getQuestionType())();

        $result = $question_type->isTypeEnabled(null, User::class);

        $this->assertTrue($result);
    }

    public function testIsTypeEnabledWithEnabledType(): void
    {
        $builder = new FormBuilder();
        $builder->addQuestion(
            "Question",
            static::getQuestionType(),
            "",
            json_encode([
                QuestionTypeActorsExtraDataConfig::IS_MULTIPLE_ACTORS => true,
                QuestionTypeActorsExtraDataConfig::ENABLED_TYPES => [
                    User::class => 1,
                    Group::class => 1,
                ],
            ])
        );
        $form = $this->createForm($builder);
        $question = Question::getById($this->getQuestionId($form, "Question"));
        $question_type = new (static::getQuestionType())();

        $result = $question_type->isTypeEnabled($question, User::class);

        $this->assertTrue($result);
    }

    public function testIsTypeEnabledWithDisabledType(): void
    {
        $builder = new FormBuilder();
        $builder->addQuestion(
            "Question",
            static::getQuestionType(),
            "",
            json_encode([
                QuestionTypeActorsExtraDataConfig::IS_MULTIPLE_ACTORS => true,
                QuestionTypeActorsExtraDataConfig::ENABLED_TYPES => [
                    User::class => 1,
                    Group::class => 0,
                ],
            ])
        );
        $form = $this->createForm($builder);
        $question = Question::getById($this->getQuestionId($form, "Question"));
        $question_type = new (static::getQuestionType())();

        $result = $question_type->isTypeEnabled($question, Group::class);

        $this->assertFalse($result);
    }

    public function testValidateExtraDataInputWithValidMultipleActors(): void
    {
        $question_type = new (static::getQuestionType())();
        $input = [
            QuestionTypeActorsExtraDataConfig::IS_MULTIPLE_ACTORS => true,
        ];

        $result = $question_type->validateExtraDataInput($input);

        $this->assertTrue($result);
    }

    public function testValidateExtraDataInputWithValidEnabledTypes(): void
    {
        $question_type = new (static::getQuestionType())();
        $input = [
            QuestionTypeActorsExtraDataConfig::ENABLED_TYPES => [User::class, Group::class],
        ];

        $result = $question_type->validateExtraDataInput($input);

        $this->assertTrue($result);
    }

    public function testValidateExtraDataInputWithValidBothFields(): void
    {
        $question_type = new (static::getQuestionType())();
        $input = [
            QuestionTypeActorsExtraDataConfig::IS_MULTIPLE_ACTORS => false,
            QuestionTypeActorsExtraDataConfig::ENABLED_TYPES => [User::class],
        ];

        $result = $question_type->validateExtraDataInput($input);

        $this->assertTrue($result);
    }

    public function testValidateExtraDataInputWithInvalidMultipleActors(): void
    {
        $question_type = new (static::getQuestionType())();
        $input = [
            QuestionTypeActorsExtraDataConfig::IS_MULTIPLE_ACTORS => 'invalid',
        ];

        $result = $question_type->validateExtraDataInput($input);

        $this->assertFalse($result);
    }

    public function testValidateExtraDataInputWithInvalidEnabledTypes(): void
    {
        $question_type = new (static::getQuestionType())();
        $input = [
            QuestionTypeActorsExtraDataConfig::ENABLED_TYPES => 'not_an_array',
        ];

        $result = $question_type->validateExtraDataInput($input);

        $this->assertFalse($result);
    }

    public function testValidateExtraDataInputWithUnexpectedKeys(): void
    {
        $question_type = new (static::getQuestionType())();
        $input = [
            QuestionTypeActorsExtraDataConfig::IS_MULTIPLE_ACTORS => true,
            'unexpected_key' => 'value',
        ];

        $result = $question_type->validateExtraDataInput($input);

        $this->assertFalse($result);
    }

    public function testValidateExtraDataInputWithEmptyInput(): void
    {
        $question_type = new (static::getQuestionType())();
        $input = [];

        $result = $question_type->validateExtraDataInput($input);

        $this->assertTrue($result);
    }

    public function testPrepareExtraDataWithEnabledTypes(): void
    {
        $question_type = new (static::getQuestionType())();
        $input = [
            QuestionTypeActorsExtraDataConfig::ENABLED_TYPES => [
                User::class => 1,
                Group::class => 0,
                Supplier::class => 1,
            ],
        ];

        $result = $question_type->prepareExtraData($input);

        $this->assertArrayHasKey(QuestionTypeActorsExtraDataConfig::ENABLED_TYPES, $result);
        $this->assertCount(2, $result[QuestionTypeActorsExtraDataConfig::ENABLED_TYPES]);
        $this->assertContains(User::class, $result[QuestionTypeActorsExtraDataConfig::ENABLED_TYPES]);
        $this->assertContains(Supplier::class, $result[QuestionTypeActorsExtraDataConfig::ENABLED_TYPES]);
        $this->assertNotContains(Group::class, $result[QuestionTypeActorsExtraDataConfig::ENABLED_TYPES]);
    }

    public function testPrepareExtraDataWithNoEnabledTypes(): void
    {
        $question_type = new (static::getQuestionType())();
        $input = [
            QuestionTypeActorsExtraDataConfig::IS_MULTIPLE_ACTORS => true,
        ];

        $result = $question_type->prepareExtraData($input);

        $this->assertEquals($input, $result);
    }

    public function testPrepareExtraDataWithAllTypesDisabled(): void
    {
        $question_type = new (static::getQuestionType())();
        $input = [
            QuestionTypeActorsExtraDataConfig::ENABLED_TYPES => [
                User::class => 0,
                Group::class => 0,
            ],
        ];

        $result = $question_type->prepareExtraData($input);

        $this->assertArrayHasKey(QuestionTypeActorsExtraDataConfig::ENABLED_TYPES, $result);
        $this->assertEmpty($result[QuestionTypeActorsExtraDataConfig::ENABLED_TYPES]);
    }

    public function testPrepareExtraDataPreservesOtherFields(): void
    {
        $question_type = new (static::getQuestionType())();
        $input = [
            QuestionTypeActorsExtraDataConfig::ENABLED_TYPES => [
                User::class => 1,
            ],
            QuestionTypeActorsExtraDataConfig::IS_MULTIPLE_ACTORS => false,
        ];

        $result = $question_type->prepareExtraData($input);

        $this->assertArrayHasKey(QuestionTypeActorsExtraDataConfig::IS_MULTIPLE_ACTORS, $result);
        $this->assertFalse($result[QuestionTypeActorsExtraDataConfig::IS_MULTIPLE_ACTORS]);
    }
}
