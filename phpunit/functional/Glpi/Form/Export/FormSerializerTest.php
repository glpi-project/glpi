<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace tests\units\Glpi\Form;

use Computer;
use Entity;
use Glpi\Form\Category;
use Glpi\Form\Comment;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsField;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsFieldConfig;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsFieldStrategy;
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
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeActorsExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeActorsDefaultValueConfig;
use Glpi\Form\QuestionType\QuestionTypeDropdown;
use Glpi\Form\QuestionType\QuestionTypeDropdownExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeItemDefaultValueConfig;
use Glpi\Form\QuestionType\QuestionTypeItemExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Form\QuestionType\QuestionTypeRequester;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\Section;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use ITILCategory;
use Location;
use Monitor;
use Session;

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
            'entities_id' => $this->getTestRootEntity(only_id: true)
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
            $form->fields['name'] => ImportError::MISSING_DATA_REQUIREMENT
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
        $mapper->addMappedItem(Entity::class, 'My entity', $another_entity_id);

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
                'entities_id' => $this->getTestRootEntity(only_id: true)
            ]
        );

        // Arrange: create a form with multiple sections and questions
        $dropdown_config = new QuestionTypeDropdownExtraDataConfig([
            '123456789' => 'Option 1',
            '987654321' => 'Option 2',
            true,
        ]);
        $item_default_value_config = new QuestionTypeItemDefaultValueConfig($location->getID());
        $item_extra_data_config = new QuestionTypeItemExtraDataConfig(Location::class);
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
                json_encode($dropdown_config->jsonSerialize()),
                'My dropdown question description'
            )
            ->addSection("My second section")
            ->addQuestion(
                "My item dropdown question",
                QuestionTypeItemDropdown::class,
                $location->getID(),
                json_encode($item_extra_data_config->jsonSerialize()),
                'My item dropdown question description',
                true
            )
            ->addQuestion(
                "My requester question",
                QuestionTypeRequester::class,
                ['users_id-' . $user->getID()],
                json_encode($actors_extra_data_config->jsonSerialize()),
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
                'extra_data'        => json_encode($dropdown_config->jsonSerialize()),
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
                'extra_data'        => json_encode($item_extra_data_config->jsonSerialize()),
                'forms_sections_id' => array_values($form_copy->getSections())[1]->fields['id'],
            ],
            [
                'name'              => 'My requester question',
                'type'              => QuestionTypeRequester::class,
                'is_mandatory'      => (int) false,
                'vertical_rank'     => 1,
                'horizontal_rank'   => null,
                'description'       => '',
                'default_value'     => json_encode(array_intersect_key(
                    $actors_default_value_config->jsonSerialize(),
                    ['users_ids' => '']
                )),
                'extra_data'        => json_encode($actors_extra_data_config->jsonSerialize()),
                'forms_sections_id' => array_values($form_copy->getSections())[1]->fields['id'],
            ]
        ], $questions_data);
    }

    public function testExportAndImportDestinations(): void
    {
        $this->login();

        // Create an ITIL category
        $itil_category = $this->createItem('ITILCategory', ['name' => 'My ITIL Category']);

        // Create a Computer
        $computer = $this->createItem('Computer', [
            'name' => 'My computer',
            'entities_id' => $this->getTestRootEntity(only_id: true)
        ]);

        // Create a monitor
        $monitor = $this->createItem('Monitor', [
            'name' => 'My monitor',
            'entities_id' => $this->getTestRootEntity(only_id: true)
        ]);

        $form = $this->createForm((new FormBuilder())->addQuestion(
            "My ITIL Category question",
            QuestionTypeItemDropdown::class,
            json_encode((new QuestionTypeItemDefaultValueConfig($itil_category->getID()))->jsonSerialize()),
            json_encode((new QuestionTypeItemExtraDataConfig(ITILCategory::class))->jsonSerialize()),
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
                    $computer->getID()
                ],
                Monitor::class => [
                    $monitor->getID()
                ]
            ]
        );

        // Insert config
        $destinations = $form->getDestinations();
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            [
                'config' => [
                    TitleField::getKey() => $title_field_config->jsonSerialize(),
                    ITILCategoryField::getKey() => $itil_category_field_config->jsonSerialize(),
                    AssociatedItemsField::getKey() => $associated_items_config->jsonSerialize()
                ]
            ],
            ["config"],
        );

        // Export and import process
        $imported_form = $this->exportAndImportForm($form);
        $imported_destinations = $imported_form->getDestinations();
        $imported_configs = current($imported_destinations)->getConfig();

        $this->assertCount(1, $imported_destinations);

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
        $this->assertEquals([$form->fields['name']], $preview->getValidForms());
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
        $this->assertEquals([$form->fields['name']], $preview->getInvalidForms());
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
        $this->assertEquals([$form->fields['name']], $preview->getSkippedForms());
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
        $this->assertEquals([$form->fields['name']], $preview->getInvalidForms());

        // Add mapped item to fix the form
        $mapper = new DatabaseMapper([$this->getTestRootEntity(only_id: true)]);
        $mapper->addMappedItem(Entity::class, 'My entity', $this->getTestRootEntity(only_id: true));

        // Act: preview the import again
        $preview = self::$serializer->previewImport($json, $mapper, []);

        // Assert: the form should be fixed
        $this->assertEquals([$form->fields['name']], $preview->getValidForms());
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
            $form->fields['name'] => ImportError::MISSING_DATA_REQUIREMENT
        ], $import_result->getFailedFormImports());

        // Add mapped item to fix the form
        $mapper = new DatabaseMapper([$this->getTestRootEntity(only_id: true)]);
        $mapper->addMappedItem(Entity::class, 'My entity', $this->getTestRootEntity(only_id: true));

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
        ], array_map(fn (Form $form) => $form->fields['name'], $import_result->getImportedForms()));
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
            '_glpi_forms_sections'                      => ['uuid'],
            '_glpi_forms_questions'                     => ['uuid', 'forms_sections_uuid'],
            '_glpi_forms_comments'                      => ['uuid', 'forms_sections_uuid'],
            '_glpi_forms_destinations_formdestinations' => ['config'],
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

                    if ($table_name === '_glpi_forms_destinations_formdestinations') {
                        $default_config = json_decode($default_value['config'], true);
                        $imported_config = json_decode($imported_value['config'], true);

                        $this->removeEmptyValues($default_config);
                        $this->removeEmptyValues($imported_config);

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
                    fn ($key) => $key === $relation_item->getForeignKeyField(),
                    ARRAY_FILTER_USE_KEY
                );
                $b = array_filter(
                    $ids_for_imported_form,
                    fn ($key) => $key === $relation_item->getForeignKeyField(),
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
