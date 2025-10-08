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

namespace tests\units\Glpi\Form\Export;

use Computer;
use Entity;
use Glpi\Form\Category;
use Glpi\Form\Comment;
use Glpi\Form\Condition\ConditionData;
use Glpi\Form\Condition\CreationStrategy;
use Glpi\Form\Condition\LogicOperator;
use Glpi\Form\Condition\Type;
use Glpi\Form\Condition\ValidationStrategy;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\Condition\VisibilityStrategy;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsField;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsFieldConfig;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsFieldStrategy;
use Glpi\Form\Destination\CommonITILField\ContentField;
use Glpi\Form\Destination\CommonITILField\ITILCategoryField;
use Glpi\Form\Destination\CommonITILField\ITILCategoryFieldConfig;
use Glpi\Form\Destination\CommonITILField\ITILCategoryFieldStrategy;
use Glpi\Form\Destination\CommonITILField\SimpleValueConfig;
use Glpi\Form\Destination\CommonITILField\TitleField;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Result\ImportError;
use Glpi\Form\Export\Serializer\FormSerializer;
use Glpi\Form\Form;
use Glpi\Form\FormTranslation;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeActorsDefaultValueConfig;
use Glpi\Form\QuestionType\QuestionTypeActorsExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeCheckbox;
use Glpi\Form\QuestionType\QuestionTypeDropdown;
use Glpi\Form\QuestionType\QuestionTypeDropdownExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeItemDefaultValueConfig;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Form\QuestionType\QuestionTypeItemDropdownExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeItemExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeRequester;
use Glpi\Form\QuestionType\QuestionTypeSelectableExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\Section;
use Glpi\Form\Tag\AnswerTagProvider;
use Glpi\Form\Tag\CommentDescriptionTagProvider;
use Glpi\Form\Tag\CommentTitleTagProvider;
use Glpi\Form\Tag\FormTagProvider;
use Glpi\Form\Tag\QuestionTagProvider;
use Glpi\Form\Tag\SectionTagProvider;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Glpi\UI\IllustrationManager;
use ITILCategory;
use Location;
use Monitor;
use Ramsey\Uuid\Uuid;
use Session;

use function Safe\json_encode;

final class FormSerializerTest extends \DbTestCase
{
    use FormTesterTrait;

    private static FormSerializer $serializer;

    public static function setUpBeforeClass(): void
    {
        self::$serializer = new FormSerializer();
        parent::setUpBeforeClass();
    }

    public function testSingleFormExportFileName(): void
    {
        // Arrange: create a single form
        $builder = new FormBuilder("My form");
        $form = $this->createForm($builder);

        // Act: export form
        $_SESSION['glpi_currenttime'] = "2024-09-09 08:38:28";
        $result = self::$serializer->exportFormsToJson([$form]);

        // Assert: filename should be the slugified form name + .json
        $this->assertEquals("my-form-2024-09-09.json", $result->getFileName());
    }

    public function testMultipleFormExportFileName(): void
    {
        // Arrange: create 7 forms
        $forms = [];
        foreach (range(1, 7) as $i) {
            $builder = new FormBuilder("Form $i");
            $forms[] = $this->createForm($builder);
        }

        // Act: export forms
        $_SESSION['glpi_currenttime'] = "2024-09-09 08:38:28";
        $result = self::$serializer->exportFormsToJson($forms);

        // Assert: filename should reference the number of forms
        $this->assertStringMatchesFormat(
            "export-of-7-forms-2024-09-09-%s.json",
            $result->getFileName()
        );
    }

    public function testTwoExportsOfTheSameFormsHaveTheSameFileName(): void
    {
        // Arrange: create 5 forms
        $forms = [];
        foreach (range(1, 5) as $i) {
            $builder = new FormBuilder("Form $i");
            $forms[] = $this->createForm($builder);
        }

        // Act: export twice the same set of forms
        $_SESSION['glpi_currenttime'] = "2024-09-09 08:38:28";
        $result_a = self::$serializer->exportFormsToJson($forms);
        $result_b = self::$serializer->exportFormsToJson($forms);

        // Assert: file names should be equals since it is the same forms
        $this->assertEquals(
            $result_a,
            $result_b,
        );
    }

    public function testTwoExportsOfDifferentFormsHaveDifferentFileNames(): void
    {
        // Arrange: create 2 sets of 3 forms
        $forms_set_a = [];
        foreach (range(1, 3) as $i) {
            $builder = new FormBuilder("Form A$i");
            $forms_set_a[] = $this->createForm($builder);
        }
        $forms_set_b = [];
        foreach (range(1, 3) as $i) {
            $builder = new FormBuilder("Form B$i");
            $forms_set_b[] = $this->createForm($builder);
        }

        // Act: export the two sets of forms
        $_SESSION['glpi_currenttime'] = "2024-09-09 08:38:28";
        $result_a = self::$serializer->exportFormsToJson($forms_set_a);
        $result_b = self::$serializer->exportFormsToJson($forms_set_b);

        // Assert: file names should not be equals since it is different forms
        $this->assertNotEquals(
            $result_a,
            $result_b,
        );
    }

    // Minimal form import to be sure there are no failure when most properties are null or empty.
    public function testExportAndImportFormWithoutValues(): void
    {
        // Arrange: create an empty form
        $form = $this->createItem(Form::class, [
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);

        // Act: export then reimport the form as a copy
        $form_copy = $this->exportAndImportForm($form);

        // Assert: validate each fields are equals between the original and the copy
        $fields_to_check = [
            'name',
            'header',
            'entities_id',
            'is_recursive',
        ];
        foreach ($fields_to_check as $field) {
            $this->assertEquals(
                $form_copy->fields[$field],
                $form->fields[$field],
                "Failed $field:"
            );
        }
    }

    public function testExportAndImportFormBasicProperties(): void
    {
        $form = $this->createAndGetFormWithBasicPropertiesFilled();

        $form_copy = $this->exportAndImportForm($form);

        // Validate each fields
        $fields_to_check = [
            'name',
            'header',
            'description',
            'forms_categories_id',
            'entities_id',
            'is_recursive',
        ];
        foreach ($fields_to_check as $field) {
            $this->assertEquals(
                $form_copy->fields[$field],
                $form->fields[$field],
                "Failed $field:"
            );
        }
    }

    public function testExportAndImportWithMissingEntity(): void
    {
        // Need an active session to create entities
        $this->login();

        $form = $this->createAndGetFormWithBasicPropertiesFilled();
        $entity = $this->createItem(Entity::class, [
            'name' => 'Temporary entity',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);
        $form->fields['entities_id'] = $entity->getID();

        // Export then delete entity
        $json = $this->exportForm($form);
        $this->deleteItem(Entity::class, $entity->getID());

        // Import should fail as the entity can't be found
        $import_result = self::$serializer->importFormsFromJson(
            $json,
            new DatabaseMapper(Session::getActiveEntities())
        );
        $this->assertCount(0, $import_result->getImportedForms());
        $this->assertEquals([
            $form->fields['name'] => ImportError::MISSING_DATA_REQUIREMENT,
        ], $import_result->getFailedFormImports());
    }

    public function testExportAndImportInAnotherEntity(): void
    {
        // Need an active session to create entities
        $this->login();

        $form = $this->createAndGetFormWithBasicPropertiesFilled();
        $entity = $this->createItem(Entity::class, [
            'name' => 'My entity',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);
        $form->fields['entities_id'] = $entity->getID();

        // Export then delete entity
        $json = $this->exportForm($form);
        $this->deleteItem(Entity::class, $entity->getID());

        // Import into another entity
        $another_entity_id = getItemByTypeName(Entity::class, "_test_child_1", true);
        $mapper = new DatabaseMapper(Session::getActiveEntities());
        $mapper->addMappedItem(
            Entity::class,
            "Root entity > _test_root_entity > My entity",
            $another_entity_id
        );

        $form_copy = $this->importForm($json, $mapper, []);
        $this->assertEquals($another_entity_id, $form_copy->fields['entities_id']);
    }

    public function testExportAndImportSections(): void
    {
        // Arrange: create a form with multiple sections
        $builder = new FormBuilder();
        $builder->addSection("My first section", "My first section content");
        $builder->addSection("My second section", "My second section content");
        $builder->addSection("My third section", "My third section content");
        $form = $this->createForm($builder);

        // Act: export and import the form
        $form_copy = $this->exportAndImportForm($form);

        // Assert: validate sections fields
        $sections = array_values($form_copy->getSections());
        $sections_data = array_map(function (Section $section) {
            return [
                'name'        => $section->fields['name'],
                'description' => $section->fields['description'],
                'rank'        => $section->fields['rank'],
            ];
        }, $sections);
        $this->assertEquals([
            [
                'name'        => 'My first section',
                'description' => 'My first section content',
                'rank'        => 0,
            ],
            [
                'name'        => 'My second section',
                'description' => 'My second section content',
                'rank'        => 1,
            ],
            [
                'name'        => 'My third section',
                'description' => 'My third section content',
                'rank'        => 2,
            ],
        ], $sections_data);
    }

    public function testExportAndImportComments(): void
    {
        // Arrange: create a form with multiple comments in multiple sections
        $builder = new FormBuilder();
        $builder->addSection("My first section")
            ->addComment("My first comment", "My first comment in my first section")
            ->addComment("My second comment", "My second comment in my first section")
            ->addSection("My second section")
            ->addComment("My third comment", "My first comment in my second section");
        $form = $this->createForm($builder);

        // Act: export and import the form
        $form_copy = $this->exportAndImportForm($form);

        // Assert: validate comments fields
        $comments = array_values($form_copy->getFormComments());
        $comments_data = array_map(function (Comment $comment) {
            return [
                'name'              => $comment->fields['name'],
                'description'       => $comment->fields['description'],
                'vertical_rank'     => $comment->fields['vertical_rank'],
                'horizontal_rank'   => $comment->fields['horizontal_rank'],
                'forms_sections_id' => $comment->fields['forms_sections_id'],
            ];
        }, $comments);

        $this->assertEquals([
            [
                'name'              => 'My first comment',
                'description'       => 'My first comment in my first section',
                'vertical_rank'     => 0,
                'horizontal_rank'   => null,
                'forms_sections_id' => array_values($form_copy->getSections())[0]->fields['id'],
            ],
            [
                'name'              => 'My second comment',
                'description'       => 'My second comment in my first section',
                'vertical_rank'     => 1,
                'horizontal_rank'   => null,
                'forms_sections_id' => array_values($form_copy->getSections())[0]->fields['id'],
            ],
            [
                'name'              => 'My third comment',
                'description'       => 'My first comment in my second section',
                'vertical_rank'     => 0,
                'horizontal_rank'   => null,
                'forms_sections_id' => array_values($form_copy->getSections())[1]->fields['id'],
            ],
        ], $comments_data);
    }

    public function testExportAndImportQuestions(): void
    {
        $this->login();

        $user = $this->createItem('User', ['name' => 'John Doe']);
        $location = $this->createItem(
            Location::class,
            [
                'name' => 'My location',
                'entities_id' => $this->getTestRootEntity(only_id: true),
            ]
        );

        // Arrange: create a form with multiple sections and questions
        $dropdown_config = new QuestionTypeDropdownExtraDataConfig([
            '123456789' => 'Option 1',
            '987654321' => 'Option 2',
        ], true);
        $item_default_value_config = new QuestionTypeItemDefaultValueConfig($location->getID());
        $item_dropdown_extra_data_config = new QuestionTypeItemDropdownExtraDataConfig(Location::class);
        $actors_default_value_config = new QuestionTypeActorsDefaultValueConfig(
            users_ids: [$user->getID()],
        );
        $actors_extra_data_config = new QuestionTypeActorsExtraDataConfig(
            is_multiple_actors: true,
        );

        $builder = new FormBuilder();
        $builder->addSection("My first section")
            ->addQuestion(
                "My text question",
                QuestionTypeShortText::class,
                'Test default value',
                '',
                'My text question description'
            )
            ->addQuestion(
                "My dropdown question",
                QuestionTypeDropdown::class,
                '123456789',
                json_encode($dropdown_config),
                'My dropdown question description'
            )
            ->addSection("My second section")
            ->addQuestion(
                "My item dropdown question",
                QuestionTypeItemDropdown::class,
                $location->getID(),
                json_encode($item_dropdown_extra_data_config),
                'My item dropdown question description',
                true
            )
            ->addQuestion(
                "My requester question",
                QuestionTypeRequester::class,
                $actors_default_value_config->jsonSerialize(),
                json_encode($actors_extra_data_config),
            );
        $form = $this->createForm($builder);

        // Act: export and import the form
        $form_copy = $this->exportAndImportForm($form);

        // Assert: validate questions fields
        $questions = array_values($form_copy->getQuestions());
        $questions_data = array_map(function (Question $question) {
            return [
                'name'              => $question->fields['name'],
                'type'              => $question->fields['type'],
                'is_mandatory'      => $question->fields['is_mandatory'],
                'vertical_rank'     => $question->fields['vertical_rank'],
                'horizontal_rank'   => $question->fields['horizontal_rank'],
                'description'       => $question->fields['description'],
                'default_value'     => $question->fields['default_value'],
                'extra_data'        => $question->fields['extra_data'],
                'forms_sections_id' => $question->fields['forms_sections_id'],
            ];
        }, $questions);

        $this->assertEquals([
            [
                'name'              => 'My text question',
                'type'              => QuestionTypeShortText::class,
                'is_mandatory'      => (int) false,
                'vertical_rank'     => 0,
                'horizontal_rank'   => null,
                'description'       => 'My text question description',
                'default_value'     => 'Test default value',
                'extra_data'        => "",
                'forms_sections_id' => array_values($form_copy->getSections())[0]->fields['id'],
            ],
            [
                'name'              => 'My dropdown question',
                'type'              => QuestionTypeDropdown::class,
                'is_mandatory'      => (int) false,
                'vertical_rank'     => 1,
                'horizontal_rank'   => null,
                'description'       => 'My dropdown question description',
                'default_value'     => '123456789',
                'extra_data'        => json_encode($dropdown_config),
                'forms_sections_id' => array_values($form_copy->getSections())[0]->fields['id'],
            ],
            [
                'name'              => 'My item dropdown question',
                'type'              => QuestionTypeItemDropdown::class,
                'is_mandatory'      => (int) true,
                'vertical_rank'     => 0,
                'horizontal_rank'   => null,
                'description'       => 'My item dropdown question description',
                'default_value'     => json_encode($item_default_value_config->jsonSerialize()),
                'extra_data'        => json_encode($item_dropdown_extra_data_config),
                'forms_sections_id' => array_values($form_copy->getSections())[1]->fields['id'],
            ],
            [
                'name'              => 'My requester question',
                'type'              => QuestionTypeRequester::class,
                'is_mandatory'      => (int) false,
                'vertical_rank'     => 1,
                'horizontal_rank'   => null,
                'description'       => '',
                'default_value'     => json_encode($actors_default_value_config->jsonSerialize()),
                'extra_data'        => json_encode($actors_extra_data_config->jsonSerialize()),
                'forms_sections_id' => array_values($form_copy->getSections())[1]->fields['id'],
            ],
        ], $questions_data);
    }

    public function testExportAndImportExtraDataFromItemDropdownQuestion(): void
    {
        // Arrange: create a form with an item dropdown question that reference
        // a specific itil category
        $category1 = $this->createItem(ITILCategory::class, [
            'name' => "My category 1",
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);
        $category2 = $this->createItem(ITILCategory::class, [
            'name' => "My category 2",
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);
        $builder = new FormBuilder();
        $extra_data = new QuestionTypeItemDropdownExtraDataConfig(
            itemtype: ITILCategory::class,
            root_items_id: $category1->getId(),
        );
        $extra_data = json_encode($extra_data);
        $builder->addQuestion("Category", QuestionTypeItemDropdown::class, extra_data: $extra_data);
        $form = $this->createForm($builder);

        // Act: delete the category, then import the form using another category
        $this->login();
        $json = $this->exportForm($form);
        $this->deleteItem(ITILCategory::class, $category1->getID(), true);
        $mapper = new DatabaseMapper(Session::getActiveEntities());
        $mapper->addMappedItem(
            ITILCategory::class,
            "My category 1",
            $category2->getID(),
        );
        $form = $this->importForm($json, $mapper);

        // Assert: the imported form should reference the second category
        $question = Question::getByID($this->getQuestionId($form, "Category"));
        $config = $question->getExtraDataConfig();
        /** @var QuestionTypeItemDropdownExtraDataConfig $config */
        $this->assertEquals($category2->getID(), $config->getRootItemsId());
    }

    public function testExportAndImportSubmitButtonConditions(): void
    {
        $this->login();

        // Arrange: create a form with submit button conditions
        $builder = new FormBuilder();
        $builder->addSection("My first section")
            ->addQuestion("My first question", QuestionTypeShortText::class);
        $builder->setSubmitButtonVisibility(
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "My first question",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "my value",
                ],
            ]
        );
        $form = $this->createForm($builder);

        // Act: export and import the form
        $form_copy = $this->exportAndImportForm($form);

        // Assert: validate the condition exist on the submit button
        $submit_button_conditions = json_decode($form_copy->fields['submit_button_conditions'], true);
        $question_uuid = Question::getById(
            $this->getQuestionId($form_copy, "My first question")
        )->fields['uuid'];

        $expected_data = [
            (new ConditionData(
                item_uuid: $question_uuid,
                item_type: Type::QUESTION->value,
                logic_operator: LogicOperator::AND->value,
                value_operator: ValueOperator::EQUALS->value,
                value: "my value",
            ))->jsonSerialize(),
        ];
        $this->assertEquals(
            $expected_data,
            $submit_button_conditions
        );
    }

    public function testExportAndImportQuestionConditions(): void
    {
        $this->login();

        // Arrange: create a form with multiple questions and conditions
        $builder = new FormBuilder();
        $builder->addSection("My first section")
            ->addQuestion("My first question", QuestionTypeShortText::class)
            ->addQuestion("My second question", QuestionTypeShortText::class)
            ->addSection("My second section")
            ->addQuestion("My third question", QuestionTypeShortText::class)
            ->addQuestion("My fourth question", QuestionTypeShortText::class);
        $builder->setQuestionVisibility(
            "My second question",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "My first question",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => 'test',
                ],
            ]
        );
        $builder->setQuestionVisibility(
            "My third question",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "My first question",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => 'test2',
                ],
            ]
        );

        $form = $this->createForm($builder);

        // Act: export and import the form
        $form_copy = $this->exportAndImportForm($form);

        // Assert: validate questions fields
        $questions = array_values($form_copy->getQuestions());
        $questions_data = array_map(function (Question $question) {
            return [
                'name'                => $question->fields['name'],
                'visibility_strategy' => $question->fields['visibility_strategy'],
                'conditions'          => json_decode($question->fields['conditions'], true),
            ];
        }, $questions);

        $first_question_uuid = Question::getById($this->getQuestionId($form_copy, "My first question"))->fields['uuid'];
        $this->assertEquals([
            [
                'name'                => 'My first question',
                'visibility_strategy' => '',
                'conditions'          => [],
            ],
            [
                'name'                => 'My second question',
                'visibility_strategy' => VisibilityStrategy::VISIBLE_IF->value,
                'conditions'          => [
                    [
                        'item'           => Type::QUESTION->value . '-' . $first_question_uuid,
                        'item_type'      => Type::QUESTION->value,
                        'item_uuid'      => $first_question_uuid,
                        'value'          => 'test',
                        'value_operator' => ValueOperator::EQUALS->value,
                        'logic_operator' => LogicOperator::AND->value,
                    ],
                ],
            ],
            [
                'name'                => 'My third question',
                'visibility_strategy' => VisibilityStrategy::VISIBLE_IF->value,
                'conditions'          => [
                    [
                        'item'           => Type::QUESTION->value . '-' . $first_question_uuid,
                        'item_type'      => Type::QUESTION->value,
                        'item_uuid'      => $first_question_uuid,
                        'value'          => 'test2',
                        'value_operator' => ValueOperator::EQUALS->value,
                        'logic_operator' => LogicOperator::AND->value,
                    ],
                ],
            ],
            [
                'name'                => 'My fourth question',
                'visibility_strategy' => '',
                'conditions'          => [],
            ],
        ], $questions_data);
    }


    public function testExportAndImportQuestionValidationConditions(): void
    {
        $this->login();

        // Arrange: create a form with a question validation condition.
        $builder = new FormBuilder();
        $builder->addSection("My first section");
        $builder->addQuestion("My question", QuestionTypeShortText::class);
        $builder->setQuestionValidation(
            "My question",
            ValidationStrategy::VALID_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "My question",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::NOT_EQUALS,
                    'value'          => "forbidden value",
                ],
            ],
        );
        $form = $this->createForm($builder);

        // Act: export and import the form
        $form_copy = $this->exportAndImportForm($form);

        // Assert: validate the condition exist on the question
        $questions = $form_copy->getQuestions();
        $question = array_pop($questions);
        $question_uuid = Question::getById(
            $this->getQuestionId($form_copy, "My question")
        )->fields['uuid'];

        $expected_data = [
            (new ConditionData(
                item_uuid: $question_uuid,
                item_type: Type::QUESTION->value,
                logic_operator: LogicOperator::AND->value,
                value_operator: ValueOperator::NOT_EQUALS->value,
                value: "forbidden value",
            ))->jsonSerialize(),
        ];
        $this->assertEquals(
            $expected_data,
            json_decode($question->fields['validation_conditions'], true)
        );
    }

    public function testExportAndImportQuestionWithValidationConditionsAndVisibilityConditions(): void
    {
        $this->login();

        // Arrange: create a form with a question validation condition and visibility condition.
        $builder = new FormBuilder();
        $builder->addSection("My first section");
        $builder->addQuestion("My first question", QuestionTypeShortText::class);
        $builder->addQuestion("My second question", QuestionTypeShortText::class);
        $builder->setQuestionValidation(
            "My second question",
            ValidationStrategy::VALID_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "My first question",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "test",
                ],
            ],
        );
        $builder->setQuestionVisibility(
            "My second question",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "My first question",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "test2",
                ],
            ]
        );
        $form = $this->createForm($builder);

        // Act: export and import the form
        $form_copy = $this->exportAndImportForm($form);

        // Assert: validate the conditions exist on the question
        $questions = $form_copy->getQuestions();
        $question = array_pop($questions);
        $first_question_uuid = Question::getById(
            $this->getQuestionId($form_copy, "My first question")
        )->fields['uuid'];

        $expected_validation_data = [
            (new ConditionData(
                item_uuid: $first_question_uuid,
                item_type: Type::QUESTION->value,
                logic_operator: LogicOperator::AND->value,
                value_operator: ValueOperator::EQUALS->value,
                value: "test",
            ))->jsonSerialize(),
        ];
        $this->assertEquals(
            $expected_validation_data,
            json_decode($question->fields['validation_conditions'], true)
        );
        $this->assertEquals(
            ValidationStrategy::VALID_IF->value,
            $question->fields['validation_strategy']
        );

        $expected_visibility_data = [
            (new ConditionData(
                item_uuid: $first_question_uuid,
                item_type: Type::QUESTION->value,
                logic_operator: LogicOperator::AND->value,
                value_operator: ValueOperator::EQUALS->value,
                value: "test2",
            ))->jsonSerialize(),
        ];
        $this->assertEquals(
            $expected_visibility_data,
            json_decode($question->fields['conditions'], true)
        );
        $this->assertEquals(
            VisibilityStrategy::VISIBLE_IF->value,
            $question->fields['visibility_strategy']
        );
    }

    public function testExportAndImportCommentsConditions(): void
    {
        $this->login();

        // Arrange: create a form with conditions on a comment.
        $builder = new FormBuilder();
        $builder->addSection("My first section");
        $builder->addQuestion("My question", QuestionTypeShortText::class);
        $builder->addComment("My comment");
        $builder->setCommentVisibility(
            "My comment",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "My question",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "my value",
                ],
            ]
        );
        $form = $this->createForm($builder);

        // Act: export and import the form
        $form_copy = $this->exportAndImportForm($form);

        // Assert: validate the condition exist on the comment
        $comments = $form_copy->getFormComments();
        $comment = array_pop($comments);
        $question_uuid = Question::getById(
            $this->getQuestionId($form_copy, "My question")
        )->fields['uuid'];

        $expected_data = [
            (new ConditionData(
                item_uuid: $question_uuid,
                item_type: Type::QUESTION->value,
                logic_operator: LogicOperator::AND->value,
                value_operator: ValueOperator::EQUALS->value,
                value: "my value",
            ))->jsonSerialize(),
        ];
        $this->assertEquals(
            $expected_data,
            json_decode($comment->fields['conditions'], true)
        );
        $this->assertEquals(
            VisibilityStrategy::VISIBLE_IF->value,
            $comment->fields['visibility_strategy']
        );
    }

    public function testExportAndImportSectionsConditions(): void
    {
        $this->login();

        // Arrange: create a form with conditions on a section.
        $builder = new FormBuilder();
        $builder->addSection("My first section");
        $builder->addQuestion("My question", QuestionTypeShortText::class);
        $builder->setSectionVisibility(
            "My first section",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "My question",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "my value",
                ],
            ]
        );
        $form = $this->createForm($builder);

        // Act: export and import the form
        $form_copy = $this->exportAndImportForm($form);

        // Assert: validate the condition exist on the section
        $sections = $form_copy->getSections();
        $section = array_pop($sections);
        $question_uuid = Question::getById(
            $this->getQuestionId($form_copy, "My question")
        )->fields['uuid'];

        $expected_data = [
            (new ConditionData(
                item_uuid: $question_uuid,
                item_type: Type::QUESTION->value,
                logic_operator: LogicOperator::AND->value,
                value_operator: ValueOperator::EQUALS->value,
                value: "my value",
            ))->jsonSerialize(),
        ];
        $this->assertEquals(
            $expected_data,
            json_decode($section->fields['conditions'], true)
        );
        $this->assertEquals(
            VisibilityStrategy::VISIBLE_IF->value,
            $section->fields['visibility_strategy']
        );
    }

    public function testExportAndImportDestinationConditions(): void
    {
        $this->login();

        // Arrange: create a form with conditions on a destination.
        $builder = new FormBuilder();
        $builder->addQuestion("My question", QuestionTypeShortText::class);
        $builder->setDestinationCondition(
            "Ticket",
            CreationStrategy::CREATED_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "My question",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "my value",
                ],
            ]
        );
        $form = $this->createForm($builder);

        // Act: export and import the form
        $form_copy = $this->exportAndImportForm($form);

        // Assert: validate the condition exist on the destination
        $destinations = $form_copy->getDestinations();
        $destination = array_pop($destinations);
        $question_uuid = Question::getById(
            $this->getQuestionId($form_copy, "My question")
        )->fields['uuid'];

        $expected_data = [
            (new ConditionData(
                item_uuid: $question_uuid,
                item_type: Type::QUESTION->value,
                logic_operator: LogicOperator::AND->value,
                value_operator: ValueOperator::EQUALS->value,
                value: "my value",
            ))->jsonSerialize(),
        ];
        $this->assertEquals(
            $expected_data,
            json_decode($destination->fields['conditions'], true)
        );
        $this->assertEquals(
            CreationStrategy::CREATED_IF->value,
            $destination->fields['creation_strategy']
        );
    }

    public function testExportAndImportDestinations(): void
    {
        $this->login();

        // Create an ITIL category
        $itil_category = $this->createItem('ITILCategory', ['name' => 'My ITIL Category']);

        // Create a Computer
        $computer = $this->createItem('Computer', [
            'name' => 'My computer',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);

        // Create a monitor
        $monitor = $this->createItem('Monitor', [
            'name' => 'My monitor',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);

        $form = $this->createForm((new FormBuilder())->addQuestion(
            "My ITIL Category question",
            QuestionTypeItemDropdown::class,
            json_encode((new QuestionTypeItemDefaultValueConfig($itil_category->getID()))),
            json_encode((new QuestionTypeItemDropdownExtraDataConfig(ITILCategory::class))),
        )->addDestination(FormDestinationTicket::class, 'My ticket destination'));

        $title_field_config = new SimpleValueConfig("My ticket title");
        $itil_category_field_config = new ITILCategoryFieldConfig(
            ITILCategoryFieldStrategy::SPECIFIC_ANSWER,
            specific_question_id: $this->getQuestionId($form, "My ITIL Category question")
        );
        $associated_items_config = new AssociatedItemsFieldConfig(
            strategies: [AssociatedItemsFieldStrategy::SPECIFIC_VALUES],
            specific_associated_items: [
                Computer::class => [
                    $computer->getID(),
                ],
                Monitor::class => [
                    $monitor->getID(),
                ],
            ]
        );

        // Insert config into second destination
        $destinations = $form->getDestinations();
        $destination = end($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            [
                'config' => [
                    TitleField::getKey() => $title_field_config->jsonSerialize(),
                    ITILCategoryField::getKey() => $itil_category_field_config->jsonSerialize(),
                    AssociatedItemsField::getKey() => $associated_items_config->jsonSerialize(),
                ],
            ],
            ["config"],
        );

        // Export and import process
        $imported_form = $this->exportAndImportForm($form);
        $imported_destinations = $imported_form->getDestinations();

        $this->assertCount(2, $imported_destinations);

        // Check the default destination
        $imported_destination_1 = current($imported_destinations);
        $this->assertEquals('Ticket', $imported_destination_1->fields['name']);

        // Check the second destination
        $imported_destination_2 = next($imported_destinations);
        $imported_configs = end($imported_destinations)->getConfig();
        $this->assertEquals('My ticket destination', $imported_destination_2->fields['name']);

        // Check that the imported form has the same destination
        $this->assertEquals($title_field_config->jsonSerialize(), $imported_configs[TitleField::getKey()]);
        $this->assertEquals(
            array_diff_key(
                $itil_category_field_config->jsonSerialize(),
                ['specific_question_id' => '']
            ),
            array_diff_key(
                $imported_configs[ITILCategoryField::getKey()],
                ['specific_question_id' => '']
            )
        );
        $this->assertEquals(
            array_diff_key(
                $associated_items_config->jsonSerialize(),
                ['specific_question_ids' => '']
            ),
            array_diff_key(
                $imported_configs[AssociatedItemsField::getKey()],
                ['specific_question_ids' => '']
            )
        );

        // Extra check to ensure that the specific_question_id is the same
        $this->assertNotEquals(
            $itil_category_field_config->getSpecificQuestionId(),
            $imported_configs[ITILCategoryField::getKey()][ITILCategoryFieldConfig::SPECIFIC_QUESTION_ID]
        );

        $this->assertEquals(
            $this->getQuestionId($imported_form, "My ITIL Category question"),
            $imported_configs[ITILCategoryField::getKey()][ITILCategoryFieldConfig::SPECIFIC_QUESTION_ID]
        );
    }

    public function testExportAndImportTranslations(): void
    {
        // Arrange: create a form with multiple blocks and sections
        $builder = new FormBuilder();
        $builder->addSection("My first section")
            ->addComment("My first comment", "My first comment in my first section")
            ->addQuestion(
                "My text question",
                QuestionTypeShortText::class,
                'Test default value',
                '',
                'My text question description'
            )
            ->addSection("My second section")
            ->addQuestion(
                "My multiple choice question",
                QuestionTypeCheckbox::class,
                '123456789',
                json_encode((new QuestionTypeSelectableExtraDataConfig([
                    '123456789' => 'Option 1',
                    '987654321' => 'Option 2',
                ], true))->jsonSerialize()),
            );
        $form = $this->createForm($builder);

        // Add translations to the form
        $handlers = $form->listTranslationsHandlers();
        array_walk_recursive(
            $handlers,
            function ($handler) {
                $this->addTranslationToForm(
                    $handler->getItem(),
                    'fr_FR',
                    $handler->getKey(),
                    $handler->getKey() . ' in fr_FR'
                );

                $this->addTranslationToForm(
                    $handler->getItem(),
                    'es_ES',
                    $handler->getKey(),
                    $handler->getKey() . ' in es_ES'
                );
            }
        );

        // Act: export and import the form
        $form_copy = $this->exportAndImportForm($form);

        // Assert: validate translations
        $translations = FormTranslation::getTranslationsForForm($form);
        $translations_copy = FormTranslation::getTranslationsForForm($form_copy);
        $keys_to_exclude = ['id' => '', 'items_id' => ''];
        $this->assertEquals(
            array_map(
                fn($translation) => array_diff_key($translation->fields, $keys_to_exclude),
                $translations
            ),
            array_map(
                fn($translation) => array_diff_key($translation->fields, $keys_to_exclude),
                $translations_copy
            ),
        );
    }

    public function testPreviewImportWithValidForm(): void
    {
        // Arrange: create a valid form
        $form = $this->createAndGetFormWithBasicPropertiesFilled();

        // Act: export the form and preview the import
        $results = self::$serializer->exportFormsToJson([$form]);
        $preview = self::$serializer->previewImport(
            $results->getJsonContent(),
            new DatabaseMapper([$this->getTestRootEntity(only_id: true)])
        );

        // Assert: the form should be valid
        $this->assertEquals(
            [$form->fields['name']],
            array_values($preview->getValidForms())
        );
        $this->assertEquals([], $preview->getInvalidForms());
    }

    public function testPreviewImportWithInvalidForm(): void
    {
        // Need an active session to create entities
        $this->login();

        // Arrange: create an invalid form by setting it into a temporary entity
        // that will be deleted later
        $form = $this->createAndGetFormWithBasicPropertiesFilled();
        $entity = $this->createItem(Entity::class, [
            'name' => 'My entity',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);
        $form->fields['entities_id'] = $entity->getID();

        // Act: export the form; delete the temporary entity to make the form
        // invalid; preview the import
        $json = $this->exportForm($form);
        $this->deleteItem(Entity::class, $entity->getID());
        $preview = self::$serializer->previewImport(
            $json,
            new DatabaseMapper([$this->getTestRootEntity(only_id: true)])
        );

        // Assert: the form should be invalid
        $this->assertEquals([], $preview->getValidForms());
        $this->assertEquals(
            [$form->fields['name']],
            array_values($preview->getInvalidForms()),
        );
    }

    public function testPreviewImportWithSkippedForm(): void
    {
        // Arrange: create a valid form
        $form = $this->createAndGetFormWithBasicPropertiesFilled();

        // Act: export the form and preview the import
        $results = self::$serializer->exportFormsToJson([$form]);
        $preview = self::$serializer->previewImport(
            $results->getJsonContent(),
            new DatabaseMapper([$this->getTestRootEntity(only_id: true)]),
            [json_decode($results->getJsonContent(), true)['forms'][0]['id']],
        );

        // Assert: the form should be valid
        $this->assertEquals([], $preview->getValidForms());
        $this->assertEquals([], $preview->getInvalidForms());
        $this->assertEquals(
            [$form->fields['name']],
            array_values($preview->getSkippedForms())
        );
    }

    public function testPreviewImportWithFixedForm(): void
    {
        // Need an active session to create entities
        $this->login();

        // Arrange: create an invalid form by setting it into a temporary entity
        // that will be deleted later
        $form = $this->createAndGetFormWithBasicPropertiesFilled();
        $entity = $this->createItem(Entity::class, [
            'name' => 'My entity',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);
        $form->fields['entities_id'] = $entity->getID();

        // Act: export the form; delete the temporary entity to make the form
        // invalid; preview the import
        $json = $this->exportForm($form);
        $this->deleteItem(Entity::class, $entity->getID());
        $preview = self::$serializer->previewImport(
            $json,
            new DatabaseMapper([$this->getTestRootEntity(only_id: true)])
        );

        // Assert: the form should be invalid
        $this->assertEquals([], $preview->getValidForms());
        $this->assertEquals(
            [$form->fields['name']],
            array_values($preview->getInvalidForms())
        );

        // Add mapped item to fix the form
        $mapper = new DatabaseMapper([$this->getTestRootEntity(only_id: true)]);
        $mapper->addMappedItem(
            Entity::class,
            "Root entity > _test_root_entity > My entity",
            $this->getTestRootEntity(only_id: true)
        );

        // Act: preview the import again
        $preview = self::$serializer->previewImport($json, $mapper, []);

        // Assert: the form should be fixed
        $this->assertEquals(
            [$form->fields['name']],
            array_values($preview->getValidForms()),
        );
        $this->assertEquals([], $preview->getInvalidForms());
    }

    public function testImportRequirementsAreCheckedInVisibleEntities(): void
    {
        $test_root_entity_id = $this->getTestRootEntity(only_id: true);

        // Arrange: create a form in a sub entity
        $this->login(); // Need an active session to create entities
        $sub_entity = $this->createItem(Entity::class, [
            'name' => 'My sub entity',
            'entities_id' => $test_root_entity_id,
        ]);
        $builder = new FormBuilder("My test form");
        $builder->setEntitiesId($sub_entity->getID());
        $form = $this->createForm($builder);

        // Act: enable sub entities; export and import form
        $this->setEntity($test_root_entity_id, subtree: true);
        $result = self::$serializer->exportFormsToJson([$form]);
        $import_result = self::$serializer->importFormsFromJson(
            $result->getJsonContent(),
            new DatabaseMapper(Session::getActiveEntities())
        );

        // Assert: import should have succeeded
        $this->assertCount(1, $import_result->getImportedForms());
        $this->assertCount(0, $import_result->getFailedFormImports());
    }

    public function testImportRequirementsAreNotCheckedInHiddenEntities(): void
    {
        // Arrange: create a form in a sub entity
        $this->login(); // Need an active session to create entities
        $test_root_entity_id = $this->getTestRootEntity(only_id: true);
        $sub_entity = $this->createItem(Entity::class, [
            'name' => 'My sub entity',
            'entities_id' => $test_root_entity_id,
        ]);
        $builder = new FormBuilder("My test form");
        $builder->setEntitiesId($sub_entity->getID());
        $form = $this->createForm($builder);

        // Act: disable sub entities; export and import form
        $this->setEntity($test_root_entity_id, subtree: false);
        $result = self::$serializer->exportFormsToJson([$form]);
        $import_result = self::$serializer->importFormsFromJson(
            $result->getJsonContent(),
            new DatabaseMapper(Session::getActiveEntities())
        );

        // Assert: import should have failed
        $this->assertCount(0, $import_result->getImportedForms());
        $this->assertCount(1, $import_result->getFailedFormImports());
    }

    public function testImportRequirementsAreNotCheckedAndFixed(): void
    {
        // Need an active session to create entities
        $this->login();

        // Arrange: create a form with a temporary entity that will be deleted
        $form = $this->createAndGetFormWithBasicPropertiesFilled();
        $entity = $this->createItem(Entity::class, [
            'name' => 'My entity',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);
        $form->fields['entities_id'] = $entity->getID();

        // Act: export the form; delete the entity to make the form invalid; import the form
        $json = $this->exportForm($form);
        $this->deleteItem(Entity::class, $entity->getID());
        $import_result = self::$serializer->importFormsFromJson(
            $json,
            new DatabaseMapper([$this->getTestRootEntity(only_id: true)])
        );

        // Assert: the import should fail
        $this->assertCount(0, $import_result->getImportedForms());
        $this->assertEquals([
            $form->fields['name'] => ImportError::MISSING_DATA_REQUIREMENT,
        ], $import_result->getFailedFormImports());

        // Add mapped item to fix the form
        $mapper = new DatabaseMapper([$this->getTestRootEntity(only_id: true)]);
        $mapper->addMappedItem(
            Entity::class,
            "Root entity > _test_root_entity > My entity",
            $this->getTestRootEntity(only_id: true)
        );

        // Act: import the form again
        $import_result = self::$serializer->importFormsFromJson($json, $mapper, []);

        // Assert: the import should succeed
        $this->assertCount(1, $import_result->getImportedForms());
        $this->assertCount(0, $import_result->getFailedFormImports());
    }

    public function testImportWithSkippedForms(): void
    {
        // Arrange: create 3 forms
        $forms = [];
        foreach (range(1, 3) as $i) {
            $builder = new FormBuilder("Form $i");
            $forms[] = $this->createForm($builder);
        }

        // Act: export the forms; import only the first and the third form
        $results = self::$serializer->exportFormsToJson($forms);
        $import_result = self::$serializer->importFormsFromJson(
            $results->getJsonContent(),
            new DatabaseMapper([$this->getTestRootEntity(only_id: true)]),
            [json_decode($results->getJsonContent(), true)['forms'][1]['id']],
        );

        // Assert: only the first and the third form should have been imported
        $this->assertCount(2, $import_result->getImportedForms());
        $this->assertEquals([
            $forms[0]->fields['name'],
            $forms[2]->fields['name'],
        ], array_map(fn(Form $form) => $form->fields['name'], $import_result->getImportedForms()));
    }

    public function testAllFieldsAreExportedAndImported(): void
    {
        $this->login();

        // Retrieve a default form
        $forms_id = getItemByTypeName(Form::class, 'Report an issue', true);
        $this->assertIsNotBool($forms_id);

        // Transfer the form to the test root entity
        $transfer = new \Transfer();
        $transfer->moveItems([Form::getType() => [$forms_id]], $this->getTestRootEntity(only_id: true), []);

        // Export and import the form
        $form = Form::getById($forms_id);
        $form_copy = $this->exportAndImportForm($form);

        // Check that all fields are equals
        $this->assertArrayIsEqualToArrayIgnoringListOfKeys(
            $form->fields,
            $form_copy->fields,
            [
                'id',
                'uuid',
                'date_creation',
            ]
        );

        $ids_for_default_form = [
            Form::getForeignKeyField() => [$forms_id],
        ];
        $ids_for_imported_form = [
            Form::getForeignKeyField() => [$form_copy->getID()],
        ];

        $this->compareValuesForRelations(
            getDbRelations()[Form::getTable()],
            $ids_for_default_form,
            $ids_for_imported_form,
        );
    }

    public function testExportAndImportEntityWithCompleteName(): void
    {
        // Need an active session to create entities
        $this->login();

        // Arrange: we will create two entities with the same name but different
        // parents.
        $form = $this->createAndGetFormWithBasicPropertiesFilled();
        $parent_entity_1 = $this->createItem(Entity::class, [
            'name' => 'Parent entity 1',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);
        $parent_entity_2 = $this->createItem(Entity::class, [
            'name' => 'Parent entity 2',
            'entities_id' => $this->getTestRootEntity(only_id: true),
        ]);
        $child_entity_1 = $this->createItem(Entity::class, [
            'name' => 'Child entity',
            'entities_id' => $parent_entity_1->getID(),
        ]);
        $this->createItem(Entity::class, [
            'name' => 'Child entity',
            'entities_id' =>  $parent_entity_2->getID(),
        ]);
        $form->fields['entities_id'] = $child_entity_1->getID();

        // Act: export then import
        $form_copy = $this->exportAndImportForm($form);
        $this->assertEquals(
            $child_entity_1->getID(),
            $form_copy->fields['entities_id']
        );
    }

    public function testExportAndImportFormCategoryWithCompleteName(): void
    {
        // Need an active session to create entities
        $this->login();

        // Arrange: we will create two categories with the same name but different
        // parents.
        $form = $this->createAndGetFormWithBasicPropertiesFilled();
        $parent_category_1 = $this->createItem(Category::class, [
            'name' => 'Parent category 1',
            Category::getForeignKeyField() => 0,
        ]);
        $parent_category_2 = $this->createItem(Category::class, [
            'name' => 'Parent category 2',
            Category::getForeignKeyField() => 0,
        ]);
        $child_category_1 = $this->createItem(Category::class, [
            'name' => 'Child category',
            Category::getForeignKeyField() => $parent_category_1->getID(),
        ]);
        $this->createItem(Category::class, [
            'name' => 'Child category',
            Category::getForeignKeyField() => $parent_category_2->getID(),
        ]);
        $form->fields[Category::getForeignKeyField()] = $child_category_1->getID();

        // Act: export then import
        $form_copy = $this->exportAndImportForm($form);
        $this->assertEquals(
            $child_category_1->getID(),
            $form_copy->fields[Category::getForeignKeyField()]
        );
    }

    public function testExportAndImportItemQuestionWithInvalidItemsId(): void
    {
        $this->login();

        // Arrange: create a form with an item question
        $builder = new FormBuilder();
        $builder->addQuestion(
            "My Computer question",
            QuestionTypeItem::class,
            -1, // Invalid item ID
            json_encode((new QuestionTypeItemExtraDataConfig(Computer::class))),
        );
        $form = $this->createForm($builder);

        $questions = $form->getQuestions();
        $this->assertCount(1, $questions);
        $question = current($questions);
        $this->assertInstanceOf(Question::class, $question);
        $this->assertInstanceOf(QuestionTypeItem::class, $question->getQuestionType());
        $this->assertEquals(-1, (new QuestionTypeItem())->getDefaultValueItemId($question));

        // Act: export and import the form
        $form_copy = $this->exportAndImportForm($form);

        // Assert: the question should have been imported with an empty items_id
        $questions = $form_copy->getQuestions();
        $this->assertCount(1, $questions);
        $question = current($questions);
        $this->assertInstanceOf(Question::class, $question);
        $this->assertInstanceOf(QuestionTypeItem::class, $question->getQuestionType());
        $this->assertEquals(0, (new QuestionTypeItem())->getDefaultValueItemId($question));
    }

    public function testExportAndImportWithCustomIcon(): void
    {
        // Arrange: create a form with a custom icon
        $illustration_manager = new IllustrationManager();
        $entity_id = $this->getTestRootEntity(only_id: true);

        $custom_icon_source = GLPI_ROOT . "/tests/fixtures/uploads/foo.png";
        $file_name = Uuid::uuid4() . "-foo.png";
        $tmp_file_path = GLPI_TMP_DIR . "/$file_name";

        copy($custom_icon_source, $tmp_file_path);
        $illustration_manager->saveCustomIllustration($file_name, $tmp_file_path);

        $saved_icon_path = GLPI_PICTURE_DIR . "/illustrations/" . $file_name;
        if (!file_exists($saved_icon_path)) {
            $this->fail("Failed to save icon");
        }

        $icon_key = IllustrationManager::CUSTOM_SCENE_PREFIX . "$file_name";
        $form = $this->createItem(Form::class, [
            'name'         => "My form with a custom icon",
            'illustration' => $icon_key,
            'entities_id'  => $entity_id,
        ]);

        // Act: export the form, then delete the icon to simulate a situation
        // where we would import the form into a GLPI where the icon doesn't exist
        $json = $this->exportForm($form);
        unlink($saved_icon_path);
        if (file_exists($saved_icon_path) || file_exists($tmp_file_path)) {
            $this->fail("Failed to delete icon files");
        }
        $imported_form = $this->importForm($json, new DatabaseMapper([$entity_id]));

        // Assert: the icon should be created and used by the form
        $this->assertEquals($icon_key, $imported_form->fields['illustration']);
        $icon_key_without_prefix = substr(
            $imported_form->fields['illustration'],
            strlen(IllustrationManager::CUSTOM_SCENE_PREFIX),
        );
        $path = $illustration_manager->getCustomIllustrationFile(
            $icon_key_without_prefix
        );
        $this->assertNotNull($path);
        $this->assertFileExists($path);
        $this->assertEquals(md5_file($custom_icon_source), md5_file($path));
    }

    public function testTagsAreUpdatedWhenImporting(): void
    {
        $form_tag_provider        = new FormTagProvider();
        $section_tag_provider     = new SectionTagProvider();
        $question_tag_provider    = new QuestionTagProvider();
        $answers_tag_provider     = new AnswerTagProvider();
        $comment_tag_provider     = new CommentTitleTagProvider();
        $description_tag_provider = new CommentDescriptionTagProvider();

        // Arrange: create a form and set up some fields using tags
        $builder = new FormBuilder("My form");
        $builder->addQuestion("My question", QuestionTypeShortText::class);
        $builder->addComment("My first comment");
        $builder->addComment("My second comment");
        $form = $this->createForm($builder);

        $destinations = $form->getDestinations();
        $destination = array_pop($destinations);
        $config = [
            // Use the form name in the title
            TitleField::getKey() => (new SimpleValueConfig(
                $form_tag_provider->getTags($form)[0]->html
            ))->jsonSerialize(),
            // Use section, questions and comment data in the content
            ContentField::getKey() => (new SimpleValueConfig(
                $section_tag_provider->getTags($form)[0]->html
                . $question_tag_provider->getTags($form)[0]->html
                . $answers_tag_provider->getTags($form)[0]->html
                . $comment_tag_provider->getTags($form)[0]->html
                . $comment_tag_provider->getTags($form)[1]->html // Add a second one to make sure it works with more than 1 tag of the same type
                . $description_tag_provider->getTags($form)[0]->html
                . $description_tag_provider->getTags($form)[1]->html
            ))->jsonSerialize(),
        ];
        $this->updateItem(
            $destination::class,
            $destination->getId(),
            [
                'config' => $config,
            ],
            ["config"],
        );

        // Act: export then import the form
        $imported_form = $this->exportAndImportForm($form);

        $imported_destinations = $imported_form->getDestinations();
        $imported_destination = array_pop($imported_destinations);

        $imported_questions = $imported_form->getQuestions();
        $imported_question = array_pop($imported_questions);

        $imported_comments = $imported_form->getFormComments();
        $imported_comment_1 = array_pop($imported_comments);
        $imported_comment_2 = array_pop($imported_comments);

        $imported_sections = $imported_form->getSections();
        $imported_section = array_pop($imported_sections);

        // Assert: the id in the tags of the cloned title and content fields must
        // target the cloned form, not the original form.
        $this->assertStringContainsString(
            "data-form-tag-value=\"{$imported_form->getID()}\" data-form-tag-provider=\"Glpi\Form\Tag\FormTagProvider\"",
            $imported_destination->getConfig()[TitleField::getKey()]['value'],
        );
        $this->assertStringContainsString(
            "data-form-tag-value=\"{$imported_section->getID()}\" data-form-tag-provider=\"Glpi\Form\Tag\SectionTagProvider\"",
            $imported_destination->getConfig()[ContentField::getKey()]['value'],
        );
        $this->assertStringContainsString(
            "data-form-tag-value=\"{$imported_question->getID()}\" data-form-tag-provider=\"Glpi\Form\Tag\QuestionTagProvider\"",
            $imported_destination->getConfig()[ContentField::getKey()]['value'],
        );
        $this->assertStringContainsString(
            "data-form-tag-value=\"{$imported_question->getID()}\" data-form-tag-provider=\"Glpi\Form\Tag\AnswerTagProvider\"",
            $imported_destination->getConfig()[ContentField::getKey()]['value'],
        );
        $this->assertStringContainsString(
            "data-form-tag-value=\"{$imported_comment_1->getID()}\" data-form-tag-provider=\"Glpi\Form\Tag\CommentTitleTagProvider\"",
            $imported_destination->getConfig()[ContentField::getKey()]['value'],
        );
        $this->assertStringContainsString(
            "data-form-tag-value=\"{$imported_comment_2->getID()}\" data-form-tag-provider=\"Glpi\Form\Tag\CommentTitleTagProvider\"",
            $imported_destination->getConfig()[ContentField::getKey()]['value'],
        );
        $this->assertStringContainsString(
            "data-form-tag-value=\"{$imported_comment_1->getID()}\" data-form-tag-provider=\"Glpi\Form\Tag\CommentDescriptionTagProvider\"",
            $imported_destination->getConfig()[ContentField::getKey()]['value'],
        );
        $this->assertStringContainsString(
            "data-form-tag-value=\"{$imported_comment_2->getID()}\" data-form-tag-provider=\"Glpi\Form\Tag\CommentDescriptionTagProvider\"",
            $imported_destination->getConfig()[ContentField::getKey()]['value'],
        );
    }

    public function testWithFormWithSpecialNegativeIdForRootEntity(): void
    {
        // Arrange: create a form with a -1 value for root_items_id
        $builder = new FormBuilder("My form");
        $extra_data = new QuestionTypeItemExtraDataConfig(
            itemtype: Entity::class,
            root_items_id: -1,
        );
        $builder->addQuestion(
            name: "Entity",
            type: QuestionTypeItem::class,
            extra_data: json_encode($extra_data)
        );
        $form = $this->createForm($builder);

        // Act: export the form, no error should happen
        $this->exportAndImportForm($form);
    }

    private function compareValuesForRelations(
        $relations,
        $ids_for_default_form,
        $ids_for_imported_form
    ): void {
        $relation_table_names_to_ignore = [
            '_glpi_helpdesks_tiles_formtiles', // Do not export/import tiles
            '_glpi_forms_answerssets', // Do not export/import answers sets
        ];

        $field_keys_to_ignore_for_specific_tables = [
            '_glpi_forms_sections'                      => ['uuid', 'conditions'],
            '_glpi_forms_questions'                     => ['uuid', 'forms_sections_uuid', 'conditions', 'extra_data'],
            '_glpi_forms_comments'                      => ['uuid', 'forms_sections_uuid', 'conditions'],
            '_glpi_forms_destinations_formdestinations' => ['config', 'conditions'],
        ];

        foreach ($relations as $table_name => $field_keys) {
            // Skip some tables
            if (in_array($table_name, $relation_table_names_to_ignore)) {
                continue;
            }

            $relation_item = new (getItemTypeForTable(ltrim($table_name, '_')))();

            // Get relations recursively
            $new_relations = getDbRelations()[$relation_item->getTable()] ?? false;

            // Compare values for each fields in relation
            foreach ($field_keys as $field_key) {
                if (in_array($field_key, $ids_for_default_form)) {
                    continue;
                }

                $default_values = $relation_item->find([$ids_for_default_form]);
                $imported_values = $relation_item->find([$ids_for_imported_form]);

                foreach ($default_values as $default_value) {
                    $imported_value = array_shift($imported_values);

                    if ($new_relations !== false) {
                        foreach ($new_relations as $new_relation_table_name) {
                            $ids_for_default_form[$relation_item->getForeignKeyField()] = [$default_value['id']];
                            $ids_for_imported_form[$relation_item->getForeignKeyField()] = [$imported_value['id']];
                        }
                    }

                    $this->assertNotNull($imported_value, "No imported value found in $table_name");
                    $this->assertArrayIsEqualToArrayIgnoringListOfKeys(
                        $default_value,
                        $imported_value,
                        array_merge(
                            ['id', 'date_creation', $field_key],
                            $field_keys_to_ignore_for_specific_tables[$table_name] ?? []
                        ),
                        "Failed to compare $table_name"
                    );

                    if ($table_name === '_glpi_forms_questions') {
                        $default_extra_data = json_decode($default_value['extra_data'] ?? '[]', true);
                        $imported_extra_data = json_decode($imported_value['extra_data'] ?? '[]', true);

                        $this->removeEmptyValues($default_extra_data);
                        $this->removeEmptyValues($imported_extra_data);

                        $this->assertEquals(
                            $default_extra_data,
                            $imported_extra_data
                        );
                    }

                    if ($table_name === '_glpi_forms_destinations_formdestinations') {
                        $default_config = json_decode($default_value['config'], true);
                        $imported_config = json_decode($imported_value['config'], true);

                        $this->removeEmptyValues($default_config);
                        $this->removeEmptyValues($imported_config);

                        // These fields contains tags and will be different,
                        // they are compared in a dedicated tests.
                        unset(
                            $default_config['glpi-form-destination-commonitilfield-titlefield'],
                            $default_config['glpi-form-destination-commonitilfield-contentfield'],
                            $imported_config['glpi-form-destination-commonitilfield-titlefield'],
                            $imported_config['glpi-form-destination-commonitilfield-contentfield']
                        );

                        $this->assertEquals(
                            $default_config,
                            $imported_config
                        );
                    }
                }
            }

            if ($new_relations !== false) {
                $a = array_filter(
                    $ids_for_default_form,
                    fn($key) => $key === $relation_item->getForeignKeyField(),
                    ARRAY_FILTER_USE_KEY
                );
                $b = array_filter(
                    $ids_for_imported_form,
                    fn($key) => $key === $relation_item->getForeignKeyField(),
                    ARRAY_FILTER_USE_KEY
                );
                if (!empty($a) && !empty($b)) {
                    $this->compareValuesForRelations(
                        $new_relations,
                        $a,
                        $b
                    );
                }
            }
        }
    }

    private function removeEmptyValues(array &$array): void
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $this->removeEmptyValues($value);
                if (empty($value)) {
                    unset($array[$key]);
                }
            } elseif (empty($value)) {
                unset($array[$key]);
            }
        }
    }

    // TODO: add a test later to make sure that requirements for each forms do
    // not contains a singular item multiple times.
    // For example, if a specific group is referenced multiple time by a form
    // it should only be included once in this form data requirement.
    // Can't be done now as we have only one requirement (form entity) so it
    // we it is impossible to have duplicates.

    private function createAndGetFormWithBasicPropertiesFilled(): Form
    {
        $form_category = $this->createItem(Category::class, ['name' => 'My service catalog category']);
        $form_name = "Form with basic properties fully filled " . mt_rand();
        $builder = new FormBuilder($form_name);
        $builder->setHeader("My custom header")
            ->setDescription("My custom description for the service catalog")
            ->setCategory($form_category->getID())
            ->setEntitiesId($this->getTestRootEntity(only_id: true))
            ->setIsRecursive(true)
        ;

        return $this->createForm($builder);
    }
}
