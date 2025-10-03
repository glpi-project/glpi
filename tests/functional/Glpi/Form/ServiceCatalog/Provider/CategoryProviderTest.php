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
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\Category;
use Glpi\Form\ServiceCatalog\ItemRequest;
use Glpi\Form\ServiceCatalog\Provider\CategoryProvider;
use Session;

class CategoryProviderTest extends DbTestCase
{
    private CategoryProvider $provider;

    public function setUp(): void
    {
        parent::setUp();
        $this->provider = new CategoryProvider();
    }

    /**
     * Test that all categories are returned when no category filter is applied
     */
    public function testGetItemsWithoutCategoryFilter()
    {
        $this->login();

        // Create categories
        $category1 = $this->createItem(Category::class, [
            'name' => 'IT Support',
            'description' => 'Information Technology support requests',
        ]);

        $category2 = $this->createItem(Category::class, [
            'name' => 'HR Services',
            'description' => 'Human Resources related services',
        ]);

        $category3 = $this->createItem(Category::class, [
            'name' => 'Facilities',
            'description' => 'Building and office facilities',
        ]);

        // Create request without category filter
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: ''
        );

        $categories = $this->provider->getItems($request);

        // All categories should be returned
        $category_names = array_column(array_map(fn($category) => $category->fields, $categories), 'name');
        $this->assertContains('IT Support', $category_names);
        $this->assertContains('HR Services', $category_names);
        $this->assertContains('Facilities', $category_names);
    }

    /**
     * Test that only the specified category is returned when category filter is applied
     */
    public function testGetItemsWithCategoryFilter()
    {
        $this->login();

        // Create parent and child categories
        $parent_category = $this->createItem(Category::class, [
            'name' => 'IT Services',
            'description' => 'All IT related services',
        ]);

        $child_category = $this->createItem(Category::class, [
            'name' => 'Software Support',
            'description' => 'Software-specific support',
            'forms_categories_id' => $parent_category->getID(),
        ]);

        $unrelated_category = $this->createItem(Category::class, [
            'name' => 'HR Services',
            'description' => 'Human Resources services',
        ]);

        // Create request with category filter for the child category
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: '',
            category_id: $parent_category->getID()
        );

        $categories = $this->provider->getItems($request);

        // Only the child category should be returned
        $category_names = array_column(array_map(fn($category) => $category->fields, $categories), 'name');
        $this->assertContains('Software Support', $category_names);
        $this->assertNotContains('IT Services', $category_names);
        $this->assertNotContains('HR Services', $category_names);
    }

    /**
     * Test that categories are filtered by name using fuzzy matching
     */
    public function testCategoriesFilteredByName()
    {
        $this->login();

        // Create categories with different names
        $category1 = $this->createItem(Category::class, [
            'name' => 'Technical Support',
            'description' => 'General technical assistance',
        ]);

        $category2 = $this->createItem(Category::class, [
            'name' => 'Hardware Requests',
            'description' => 'Equipment and hardware needs',
        ]);

        $category3 = $this->createItem(Category::class, [
            'name' => 'Software Installation',
            'description' => 'Software deployment and setup',
        ]);

        $category4 = $this->createItem(Category::class, [
            'name' => 'Access Management',
            'description' => 'User access and permissions',
        ]);

        // Test filter by partial name match
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: 'tech'
        );

        $categories = $this->provider->getItems($request);
        $category_names = array_column(array_map(fn($category) => $category->fields, $categories), 'name');

        // Should match "Technical Support" (name contains "tech")
        $this->assertContains('Technical Support', $category_names);
        $this->assertNotContains('Hardware Requests', $category_names);
        $this->assertNotContains('Software Installation', $category_names);
        $this->assertNotContains('Access Management', $category_names);
    }

    /**
     * Test that categories are filtered by description using fuzzy matching
     */
    public function testCategoriesFilteredByDescription()
    {
        $this->login();

        // Create categories with different descriptions
        $category1 = $this->createItem(Category::class, [
            'name' => 'IT Support',
            'description' => 'Technical assistance for hardware and software',
        ]);

        $category2 = $this->createItem(Category::class, [
            'name' => 'Equipment',
            'description' => 'Request new technical equipment and devices',
        ]);

        $category3 = $this->createItem(Category::class, [
            'name' => 'Training',
            'description' => 'Educational courses and workshops',
        ]);

        // Test filter by description content
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: 'technical'
        );

        $categories = $this->provider->getItems($request);
        $category_names = array_column(array_map(fn($category) => $category->fields, $categories), 'name');

        // Should match both categories that contain "technical" in description
        $this->assertContains('IT Support', $category_names);
        $this->assertContains('Equipment', $category_names);
        $this->assertNotContains('Training', $category_names);
    }

    /**
     * Test that categories are filtered by both name and description
     */
    public function testCategoriesFilteredByNameAndDescription()
    {
        $this->login();

        // Create categories
        $category1 = $this->createItem(Category::class, [
            'name' => 'Software Issues',
            'description' => 'Report bugs and software problems',
        ]);

        $category2 = $this->createItem(Category::class, [
            'name' => 'Hardware Support',
            'description' => 'Physical equipment and software installation',
        ]);

        $category3 = $this->createItem(Category::class, [
            'name' => 'Training',
            'description' => 'Educational programs',
        ]);

        // Test filter that matches both name and description
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: 'software'
        );

        $categories = $this->provider->getItems($request);
        $category_names = array_column(array_map(fn($category) => $category->fields, $categories), 'name');

        // Should match "Software Issues" (name) and "Hardware Support" (description)
        $this->assertContains('Software Issues', $category_names);
        $this->assertContains('Hardware Support', $category_names);
        $this->assertNotContains('Training', $category_names);
    }

    /**
     * Test that no categories are returned when filter doesn't match anything
     */
    public function testNoMatchingCategories()
    {
        $this->login();

        // Create a category
        $category = $this->createItem(Category::class, [
            'name' => 'IT Support',
            'description' => 'Technical assistance',
        ]);

        // Test filter that doesn't match
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: 'nonexistent'
        );

        $categories = $this->provider->getItems($request);

        // No categories should be returned
        $this->assertEmpty($categories);
    }

    /**
     * Test that categories are sorted by name
     */
    public function testCategoriesSortedByName()
    {
        $this->login();

        // Create categories in non-alphabetical order
        $category_z = $this->createItem(Category::class, [
            'name' => 'Zebra Category',
            'description' => 'Last in alphabet',
        ]);

        $category_a = $this->createItem(Category::class, [
            'name' => 'Alpha Category',
            'description' => 'First in alphabet',
        ]);

        $category_m = $this->createItem(Category::class, [
            'name' => 'Middle Category',
            'description' => 'Middle of alphabet',
        ]);

        // Create request without filter
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: ''
        );

        $categories = $this->provider->getItems($request);
        $category_names = array_map(fn($category) => $category->fields['name'], $categories);

        // Categories should be sorted alphabetically by name
        $expected_order = ['Alpha Category', 'Middle Category', 'Zebra Category'];
        $this->assertEquals($expected_order, $category_names);
    }

    /**
     * Test that empty filter returns all categories
     */
    public function testEmptyFilterReturnsAllCategories()
    {
        $this->login();

        // Create categories
        $category1 = $this->createItem(Category::class, [
            'name' => 'Category One',
            'description' => 'First category',
        ]);

        $category2 = $this->createItem(Category::class, [
            'name' => 'Category Two',
            'description' => 'Second category',
        ]);

        // Test with empty filter
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: ''
        );

        $categories = $this->provider->getItems($request);

        // All categories should be returned
        $this->assertCount(2, $categories);
        $category_names = array_column(array_map(fn($category) => $category->fields, $categories), 'name');
        $this->assertContains('Category One', $category_names);
        $this->assertContains('Category Two', $category_names);
    }

    /**
     * Test that categories with null/empty name or description are handled properly
     */
    public function testCategoriesWithEmptyFields()
    {
        $this->login();

        // Create category with empty description
        $category1 = $this->createItem(Category::class, [
            'name' => 'Category with empty description',
            'description' => '',
        ]);

        // Create category with only description
        $category2 = $this->createItem(Category::class, [
            'name' => '',
            'description' => 'Only description here',
        ]);

        // Test filter that should match the description
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: 'description'
        );

        $categories = $this->provider->getItems($request);
        $category_ids = array_map(fn($category) => $category->fields['id'], $categories);

        // Should match the category with "description" in name and description
        $this->assertContains($category1->getID(), $category_ids);
        $this->assertContains($category2->getID(), $category_ids);
    }

    public function testCategoriesAncestor()
    {
        $this->login();

        $category1 = $this->createItem(Category::class, [
            'name' => 'Category 1',
        ]);

        // Child of category1
        $category1_1 = $this->createItem(Category::class, [
            'name' => 'Category 1.1',
            'forms_categories_id' => $category1->getID(),
        ]);
        // Child of category1_1
        $category1_1_1 = $this->createItem(Category::class, [
            'name' => 'Category 1.1.1',
            'forms_categories_id' => $category1_1->getID(),
        ]);
        $category1_1_2 = $this->createItem(Category::class, [
            'name' => 'Category 1.1.2',
            'forms_categories_id' => $category1_1->getID(),
        ]);
        // Child of category1_1_1
        $category1_1_1_1 = $this->createItem(Category::class, [
            'name' => 'Category 1.1.1.1',
            'forms_categories_id' => $category1_1_1->getID(),
        ]);

        $category2 = $this->createItem(Category::class, [
            'name' => 'Category 2',
        ]);
        $category2_1 = $this->createItem(Category::class, [
            'name' => 'Category 2.1',
            'forms_categories_id' => $category2->getID(),
        ]);

        // We verify the category provider return the correcte ancestors (used for breadcrumbs)

        $item_request = new ItemRequest(
            access_parameters: new FormAccessParameters(),
            category_id: 0,
        );

        // Root has no ancestors
        $ancestors = $this->provider->getAncestors($item_request);
        $this->assertCount(0, $ancestors);

        // Category 1
        $item_request->category_id = $category1->getID();
        $ancestors = $this->provider->getAncestors($item_request);
        $this->assertCount(1, $ancestors);
        $this->assertEquals($category1->getID(), $ancestors[0]['id']);

        // Category 1.1
        $item_request->category_id = $category1_1->getID();
        $ancestors = $this->provider->getAncestors($item_request);
        $this->assertCount(2, $ancestors);
        $this->assertEquals($category1->getID(), $ancestors[0]['id']);
        $this->assertEquals($category1_1->getID(), $ancestors[1]['id']);

        // Category 1.1.2
        $item_request->category_id = $category1_1_2->getID();
        $ancestors = $this->provider->getAncestors($item_request);
        $this->assertCount(3, $ancestors);
        $this->assertEquals($category1->getID(), $ancestors[0]['id']);
        $this->assertEquals($category1_1->getID(), $ancestors[1]['id']);
        $this->assertEquals($category1_1_2->getID(), $ancestors[2]['id']);

        // Category 1.1.1.1
        $item_request->category_id = $category1_1_1_1->getID();
        $ancestors = $this->provider->getAncestors($item_request);
        $this->assertCount(4, $ancestors);
        $this->assertEquals($category1->getID(), $ancestors[0]['id']);
        $this->assertEquals($category1_1->getID(), $ancestors[1]['id']);
        $this->assertEquals($category1_1_1->getID(), $ancestors[2]['id']);
        $this->assertEquals($category1_1_1_1->getID(), $ancestors[3]['id']);

        // Category 2
        $item_request->category_id = $category2->getID();
        $ancestors = $this->provider->getAncestors($item_request);
        $this->assertCount(1, $ancestors);
        $this->assertEquals($category2->getID(), $ancestors[0]['id']);

        // Category 2.1
        $item_request->category_id = $category2_1->getID();
        $ancestors = $this->provider->getAncestors($item_request);
        $this->assertCount(2, $ancestors);
        $this->assertEquals($category2->getID(), $ancestors[0]['id']);
        $this->assertEquals($category2_1->getID(), $ancestors[1]['id']);
    }
}
