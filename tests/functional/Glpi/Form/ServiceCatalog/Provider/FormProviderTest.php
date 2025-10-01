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

namespace tests\units\Glpi\Form\ServiceCatalog\Provider;

use DbTestCase;
use Entity;
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\Category;
use Glpi\Form\Form;
use Glpi\Form\ServiceCatalog\ItemRequest;
use Glpi\Form\ServiceCatalog\Provider\FormProvider;
use Session;

class FormProviderTest extends DbTestCase
{
    private FormProvider $provider;

    public function setUp(): void
    {
        parent::setUp();
        $this->provider = FormProvider::getInstance();
    }

    /**
     * Test that forms without category are included when no category filter is applied
     */
    public function testGetItemsWithoutCategoryFilter()
    {
        $this->login();

        // Create a form without category
        $form_without_category = $this->createForm([
            Entity::getForeignKeyField() => $this->getTestRootEntity(true),
            'name'                       => 'Form without category',
            'is_active'                  => 1,
            'forms_categories_id'        => 0,
        ]);

        // Create a form with category
        $category = $this->createItem(Category::class, [
            'name' => 'Test Category',
        ]);
        $form_with_category = $this->createForm([
            Entity::getForeignKeyField() => $this->getTestRootEntity(true),
            'name'                       => 'Form with category',
            'is_active'                  => 1,
            'forms_categories_id'        => $category->getID(),
        ]);

        // Create request without category filter
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: ''
        );

        $forms = $this->provider->getItems($request);

        // Both forms should be returned
        $form_names = array_column(array_map(fn($form) => $form->fields, $forms), 'name');
        $this->assertContains('Form without category', $form_names);
        $this->assertContains('Form with category', $form_names);
    }

    /**
     * Test that only forms from specified category are returned when category filter is applied
     */
    public function testGetItemsWithCategoryFilter()
    {
        $this->login();

        // Create categories
        $category1 = $this->createItem(Category::class, [
            'name' => 'Category 1',
        ]);
        $category2 = $this->createItem(Category::class, [
            'name' => 'Category 2',
        ]);

        // Create forms
        $form_without_category = $this->createForm([
            Entity::getForeignKeyField() => $this->getTestRootEntity(true),
            'name'                       => 'Form without category',
            'is_active'                  => 1,
            'forms_categories_id'        => 0,
        ]);

        $form_category1 = $this->createForm([
            Entity::getForeignKeyField() => $this->getTestRootEntity(true),
            'name'                       => 'Form in category 1',
            'is_active'                  => 1,
            'forms_categories_id'        => $category1->getID(),
        ]);

        $form_category2 = $this->createForm([
            Entity::getForeignKeyField() => $this->getTestRootEntity(true),
            'name'                       => 'Form in category 2',
            'is_active'                  => 1,
            'forms_categories_id'        => $category2->getID(),
        ]);

        // Create request with category 1 filter
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: '',
            category_id: $category1->getID()
        );

        $forms = $this->provider->getItems($request);

        // Only form from category 1 should be returned
        $form_names = array_column(array_map(fn($form) => $form->fields, $forms), 'name');
        $this->assertContains('Form in category 1', $form_names);
        $this->assertNotContains('Form without category', $form_names);
        $this->assertNotContains('Form in category 2', $form_names);
    }

    /**
     * Test that pinned forms are always shown regardless of filter
     */
    public function testPinnedFormsIgnoreFilter()
    {
        $this->login();

        // Create a pinned form
        $pinned_form = $this->createForm([
            Entity::getForeignKeyField() => $this->getTestRootEntity(true),
            'name'                       => 'Pinned form with unmatching name',
            'is_active'                  => 1,
            'is_pinned'                  => 1,
            'forms_categories_id'        => 0,
        ]);

        // Create a non-pinned form
        $regular_form = $this->createForm([
            Entity::getForeignKeyField() => $this->getTestRootEntity(true),
            'name'                       => 'Regular form with unmatching name',
            'is_active'                  => 1,
            'is_pinned'                  => 0,
            'forms_categories_id'        => 0,
        ]);

        // Create request with filter that doesn't match either form name
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: 'nonexistent'
        );

        $forms = $this->provider->getItems($request);

        // Only pinned form should be returned
        $form_names = array_column(array_map(fn($form) => $form->fields, $forms), 'name');
        $this->assertContains('Pinned form with unmatching name', $form_names);
        $this->assertNotContains('Regular form with unmatching name', $form_names);
    }

    /**
     * Test that forms are filtered by name and description
     */
    public function testFormsFilteredByNameAndDescription()
    {
        $this->login();

        // Create forms with different names and descriptions
        $form1 = $this->createForm([
            Entity::getForeignKeyField() => $this->getTestRootEntity(true),
            'name'                       => 'Technical Support Request',
            'description'                => 'Submit a technical issue',
            'is_active'                  => 1,
            'forms_categories_id'        => 0,
        ]);

        $form2 = $this->createForm([
            Entity::getForeignKeyField() => $this->getTestRootEntity(true),
            'name'                       => 'Equipment Request',
            'description'                => 'Request new technical equipment',
            'is_active'                  => 1,
            'forms_categories_id'        => 0,
        ]);

        $form3 = $this->createForm([
            Entity::getForeignKeyField() => $this->getTestRootEntity(true),
            'name'                       => 'Access Request',
            'description'                => 'Request access to systems',
            'is_active'                  => 1,
            'forms_categories_id'        => 0,
        ]);

        // Test filter by name
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: 'technical'
        );

        $forms = $this->provider->getItems($request);
        $form_names = array_column(array_map(fn($form) => $form->fields, $forms), 'name');

        // Should match both forms that contain "technical" in name or description
        $this->assertContains('Technical Support Request', $form_names);
        $this->assertContains('Equipment Request', $form_names);
        $this->assertNotContains('Access Request', $form_names);
    }

    /**
     * Test that only active forms are returned
     */
    public function testOnlyActiveFormsReturned()
    {
        $this->login();

        // Create active and inactive forms
        $active_form = $this->createForm([
            Entity::getForeignKeyField() => $this->getTestRootEntity(true),
            'name'                       => 'Active form',
            'is_active'                  => 1,
            'forms_categories_id'        => 0,
        ]);

        $inactive_form = $this->createForm([
            Entity::getForeignKeyField() => $this->getTestRootEntity(true),
            'name'                       => 'Inactive form',
            'is_active'                  => 0,
            'forms_categories_id'        => 0,
        ]);

        // Create request
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: ''
        );

        $forms = $this->provider->getItems($request);
        $form_names = array_column(array_map(fn($form) => $form->fields, $forms), 'name');

        // Only active form should be returned
        $this->assertContains('Active form', $form_names);
        $this->assertNotContains('Inactive form', $form_names);
    }

    /**
     * Helper method to create a form
     */
    private function createForm(array $input): Form
    {
        return $this->createItem(Form::class, $input);
    }
}
