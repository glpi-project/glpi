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
use Glpi\Form\Export\Serializer\FormSerializer;
use Glpi\Form\Form;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

final class FormSerializerTest extends \DbTestCase
{
    use FormTesterTrait;

    private static FormSerializer $serializer;

    public static function setUpBeforeClass(): void
    {
        self::$serializer = new FormSerializer();
        parent::setUpBeforeClass();
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
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $form->fields['entities_id'] = $entity->getID();

        // Export then delete entity
        $json = $this->exportForm($form);
        $this->deleteItem(Entity::class, $entity->getID());

        // Import should fail as the entity can't be found
        // TODO: Maybe we could create a custom exception type ?
        $this->expectException(\InvalidArgumentException::class);
        $this->importForm($json);
    }

    public function testExportAndImportInAnotherEntity(): void
    {
        // Need an active session to create entities
        $this->login();

        $form = $this->createAndGetFormWithBasicPropertiesFilled();
        $entity = $this->createItem(Entity::class, [
            'name' => 'My entity',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $form->fields['entities_id'] = $entity->getID();

        // Export then delete entity
        $json = $this->exportForm($form);
        $this->deleteItem(Entity::class, $entity->getID());

        // Import into another entity
        $another_entity_id = getItemByTypeName(Entity::class, "_test_child_1", true);
        $context = new DatabaseMapper();
        $context->addMappedItem(Entity::class, 'My entity', $another_entity_id);

        $form_copy = $this->importForm($json, $context);
        $this->assertEquals($another_entity_id, $form_copy->fields['entities_id']);
    }

    public function testDataRequirementsFromFormsAreNotExported(): void
    {
        // FormsSpecification data_requirements field must be ignored because
        // it is only a temporary value that will be moved into the parent
        // spec object.
        $form = $this->createAndGetFormWithBasicPropertiesFilled();
        $json = $this->exportForm($form);
        $this->assertEquals(1, substr_count($json, "data_requirements"), $json);
        $this->assertEquals(0, substr_count($json, "dataRequirements"), $json);
    }

    private function exportForm(Form $form): string
    {
        return self::$serializer->exportFormsToJson([$form]);
    }

    private function importForm(
        string $json,
        DatabaseMapper $context = new DatabaseMapper(),
    ): Form {
        $imported_forms = self::$serializer->importFormsFromJson($json, $context);
        $this->assertCount(1, $imported_forms);
        $form_copy = current($imported_forms);
        return $form_copy;
    }

    private function exportAndImportForm(Form $form): Form
    {
        // Export and import process
        $json = $this->exportForm($form);
        $form_copy = $this->importForm($json);

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
            ->setEntitiesId($this->getTestRootEntity(true))
            ->setIsRecursive(true)
        ;

        return $this->createForm($builder);
    }
}
