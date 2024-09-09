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

namespace tests\units\Glpi\Form;

use Entity;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Result\ImportError;
use Glpi\Form\Export\Serializer\FormSerializer;
use Glpi\Form\Form;
use Glpi\Form\Section;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
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

        $form_copy = $this->importForm($json, $mapper);
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

    // TODO: add a test later to make sure that requirements for each forms do
    // not contains a singular item multiple times.
    // For example, if a specific group is referenced multiple time by a form
    // it should only be included once in this form data requirement.
    // Can't be done now as we have only one requirement (form entity) so it
    // we it is impossible to have duplicates.

    private function exportForm(Form $form): string
    {
        return self::$serializer->exportFormsToJson([$form])->getJsonContent();
    }

    private function importForm(
        string $json,
        DatabaseMapper $mapper,
    ): Form {
        $import_result = self::$serializer->importFormsFromJson($json, $mapper);
        $imported_forms = $import_result->getImportedForms();
        $this->assertCount(1, $imported_forms);
        $form_copy = current($imported_forms);
        return $form_copy;
    }

    private function exportAndImportForm(Form $form): Form
    {
        // Export and import process
        $json = $this->exportForm($form);
        $form_copy = $this->importForm(
            $json,
            new DatabaseMapper([$this->getTestRootEntity(only_id: true)])
        );

        // Make sure it was not the same form object that was returned.
        $this->assertNotEquals($form_copy->getId(), $form->getId());

        // Make sure the new form really exist in the database.
        $this->assertNotFalse($form_copy->getFromDB($form_copy->getId()));

        return $form_copy;
    }

    private function createAndGetFormWithBasicPropertiesFilled(): Form
    {
        $form_name = "Form with basic properties fully filled " . mt_rand();
        $builder = new FormBuilder($form_name);
        $builder->setHeader("My custom header")
            ->setEntitiesId($this->getTestRootEntity(only_id: true))
            ->setIsRecursive(true)
        ;

        return $this->createForm($builder);
    }
}
