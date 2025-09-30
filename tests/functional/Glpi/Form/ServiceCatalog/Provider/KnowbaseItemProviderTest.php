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
use Glpi\Form\ServiceCatalog\Provider\KnowbaseItemProvider;
use KnowbaseItem;
use Session;

class KnowbaseItemProviderTest extends DbTestCase
{
    private KnowbaseItemProvider $provider;

    public function setUp(): void
    {
        parent::setUp();
        $this->provider = new KnowbaseItemProvider();
    }

    /**
     * Test that knowbase items without category are included when no category filter is applied
     */
    public function testGetItemsWithoutCategoryFilter()
    {
        $this->login();

        // Create knowbase items without category
        $kb_item1 = $this->createKnowbaseItem([
            'name' => 'How to reset password',
            'description' => 'Password reset procedure',
            'answer' => 'Step by step guide to reset your password',
            'show_in_service_catalog' => 1,
            'forms_categories_id' => 0,
        ]);

        // Create knowbase item with category
        $category = $this->createItem(Category::class, [
            'name' => 'IT Support',
        ]);
        $kb_item2 = $this->createKnowbaseItem([
            'name' => 'VPN Setup Guide',
            'description' => 'How to configure VPN',
            'answer' => 'Detailed VPN configuration steps',
            'show_in_service_catalog' => 1,
            'forms_categories_id' => $category->getID(),
        ]);

        // Create request without category filter
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: ''
        );

        $knowbase_items = $this->provider->getItems($request);

        // Both items should be returned
        $kb_names = array_column(array_map(fn($kb) => $kb->fields, $knowbase_items), 'name');
        $this->assertContains('How to reset password', $kb_names);
        $this->assertContains('VPN Setup Guide', $kb_names);
    }

    /**
     * Test that only knowbase items from specified category are returned when category filter is applied
     */
    public function testGetItemsWithCategoryFilter()
    {
        $this->login();

        // Create categories
        $category1 = $this->createItem(Category::class, [
            'name' => 'IT Support',
        ]);
        $category2 = $this->createItem(Category::class, [
            'name' => 'HR Services',
        ]);

        // Create knowbase items
        $kb_without_category = $this->createKnowbaseItem([
            'name' => 'General FAQ',
            'description' => 'Frequently asked questions',
            'answer' => 'Common questions and answers',
            'show_in_service_catalog' => 1,
            'forms_categories_id' => 0,
        ]);

        $kb_category1 = $this->createKnowbaseItem([
            'name' => 'IT Troubleshooting',
            'description' => 'Technical problem solutions',
            'answer' => 'How to solve common IT issues',
            'show_in_service_catalog' => 1,
            'forms_categories_id' => $category1->getID(),
        ]);

        $kb_category2 = $this->createKnowbaseItem([
            'name' => 'HR Policies',
            'description' => 'Company HR policies',
            'answer' => 'Employee handbook and policies',
            'show_in_service_catalog' => 1,
            'forms_categories_id' => $category2->getID(),
        ]);

        // Create request with category 1 filter
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: '',
            category_id: $category1->getID()
        );

        $knowbase_items = $this->provider->getItems($request);

        // Only item from category 1 should be returned
        $kb_names = array_column(array_map(fn($kb) => $kb->fields, $knowbase_items), 'name');
        $this->assertContains('IT Troubleshooting', $kb_names);
        $this->assertNotContains('General FAQ', $kb_names);
        $this->assertNotContains('HR Policies', $kb_names);
    }

    /**
     * Test that only items marked for service catalog are returned
     */
    public function testOnlyServiceCatalogItemsReturned()
    {
        $this->login();

        // Create knowbase items
        $kb_in_catalog = $this->createKnowbaseItem([
            'name' => 'Service Catalog Item',
            'description' => 'Item visible in service catalog',
            'answer' => 'This item should appear in service catalog',
            'show_in_service_catalog' => 1,
        ]);

        $kb_not_in_catalog = $this->createKnowbaseItem([
            'name' => 'Internal Documentation',
            'description' => 'Internal use only',
            'answer' => 'This item should not appear in service catalog',
            'show_in_service_catalog' => 0,
        ]);

        // Create request
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: ''
        );

        $knowbase_items = $this->provider->getItems($request);
        $kb_names = array_column(array_map(fn($kb) => $kb->fields, $knowbase_items), 'name');

        // Only item marked for service catalog should be returned
        $this->assertContains('Service Catalog Item', $kb_names);
        $this->assertNotContains('Internal Documentation', $kb_names);
    }

    /**
     * Test that knowbase items are filtered by name using fuzzy matching
     */
    public function testKnowbaseItemsFilteredByName()
    {
        $this->login();

        // Create knowbase items with different names
        $kb1 = $this->createKnowbaseItem([
            'name' => 'Password Reset Guide',
            'description' => 'How to reset passwords',
            'answer' => 'Step by step password reset',
            'show_in_service_catalog' => 1,
        ]);

        $kb2 = $this->createKnowbaseItem([
            'name' => 'Email Configuration',
            'description' => 'Setting up email clients',
            'answer' => 'Configure your email application',
            'show_in_service_catalog' => 1,
        ]);

        $kb3 = $this->createKnowbaseItem([
            'name' => 'VPN Access Guide',
            'description' => 'Remote access setup',
            'answer' => 'Connect to company VPN',
            'show_in_service_catalog' => 1,
        ]);

        // Test filter by name
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: 'password'
        );

        $knowbase_items = $this->provider->getItems($request);
        $kb_names = array_column(array_map(fn($kb) => $kb->fields, $knowbase_items), 'name');

        // Should match "Password Reset Guide" (name contains "password")
        $this->assertContains('Password Reset Guide', $kb_names);
        $this->assertNotContains('Email Configuration', $kb_names);
        $this->assertNotContains('VPN Access Guide', $kb_names);
    }

    /**
     * Test that knowbase items are filtered by description using fuzzy matching
     */
    public function testKnowbaseItemsFilteredByDescription()
    {
        $this->login();

        // Create knowbase items with different descriptions
        $kb1 = $this->createKnowbaseItem([
            'name' => 'Account Management',
            'description' => 'User account troubleshooting and support',
            'answer' => 'Manage user accounts effectively',
            'show_in_service_catalog' => 1,
        ]);

        $kb2 = $this->createKnowbaseItem([
            'name' => 'Software Installation',
            'description' => 'How to install business applications',
            'answer' => 'Step by step software setup',
            'show_in_service_catalog' => 1,
        ]);

        $kb3 = $this->createKnowbaseItem([
            'name' => 'Network Issues',
            'description' => 'Connectivity problems and solutions',
            'answer' => 'Resolve network connectivity issues',
            'show_in_service_catalog' => 1,
        ]);

        // Test filter by description content
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: 'troubleshooting'
        );

        $knowbase_items = $this->provider->getItems($request);
        $kb_names = array_column(array_map(fn($kb) => $kb->fields, $knowbase_items), 'name');

        // Should match "Account Management" (description contains "troubleshooting")
        $this->assertContains('Account Management', $kb_names);
        $this->assertNotContains('Software Installation', $kb_names);
        $this->assertNotContains('Network Issues', $kb_names);
    }

    /**
     * Test that knowbase items are filtered by answer using fuzzy matching
     */
    public function testKnowbaseItemsFilteredByAnswer()
    {
        $this->login();

        // Create knowbase items with different answer content
        $kb1 = $this->createKnowbaseItem([
            'name' => 'System Access',
            'description' => 'Getting system access',
            'answer' => 'Contact administrator for authentication setup',
            'show_in_service_catalog' => 1,
        ]);

        $kb2 = $this->createKnowbaseItem([
            'name' => 'File Sharing',
            'description' => 'Share files securely',
            'answer' => 'Use the company file sharing platform',
            'show_in_service_catalog' => 1,
        ]);

        $kb3 = $this->createKnowbaseItem([
            'name' => 'Backup Procedures',
            'description' => 'Data backup guidelines',
            'answer' => 'Regular backup ensures data safety',
            'show_in_service_catalog' => 1,
        ]);

        // Test filter by answer content
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: 'administrator'
        );

        $knowbase_items = $this->provider->getItems($request);
        $kb_names = array_column(array_map(fn($kb) => $kb->fields, $knowbase_items), 'name');

        // Should match "System Access" (answer contains "administrator")
        $this->assertContains('System Access', $kb_names);
        $this->assertNotContains('File Sharing', $kb_names);
        $this->assertNotContains('Backup Procedures', $kb_names);
    }

    /**
     * Test that knowbase items are filtered by name, description, and answer
     */
    public function testKnowbaseItemsFilteredByAllFields()
    {
        $this->login();

        // Create knowbase items
        $kb1 = $this->createKnowbaseItem([
            'name' => 'Security Guidelines',
            'description' => 'Information security best practices',
            'answer' => 'Follow these guidelines for data protection',
            'show_in_service_catalog' => 1,
        ]);

        $kb2 = $this->createKnowbaseItem([
            'name' => 'Password Policy',
            'description' => 'Company password requirements',
            'answer' => 'Create strong passwords for security',
            'show_in_service_catalog' => 1,
        ]);

        $kb3 = $this->createKnowbaseItem([
            'name' => 'Email Usage',
            'description' => 'Best practices for email',
            'answer' => 'Best practices for business email',
            'show_in_service_catalog' => 1,
        ]);

        // Test filter that matches different fields
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: 'security'
        );

        $knowbase_items = $this->provider->getItems($request);
        $kb_names = array_column(array_map(fn($kb) => $kb->fields, $knowbase_items), 'name');

        // Should match "Security Guidelines" (name) and "Password Policy" (answer)
        $this->assertContains('Security Guidelines', $kb_names);
        $this->assertContains('Password Policy', $kb_names);
        $this->assertNotContains('Email Usage', $kb_names);
    }

    /**
     * Test that knowbase items are sorted by name
     */
    public function testKnowbaseItemsSortedByName()
    {
        $this->login();

        // Create knowbase items in non-alphabetical order
        $kb_z = $this->createKnowbaseItem([
            'name' => 'Zoom Usage Guide',
            'description' => 'How to use Zoom',
            'answer' => 'Video conferencing with Zoom',
            'show_in_service_catalog' => 1,
        ]);

        $kb_a = $this->createKnowbaseItem([
            'name' => 'Account Setup',
            'description' => 'New user account setup',
            'answer' => 'Setting up new accounts',
            'show_in_service_catalog' => 1,
        ]);

        $kb_m = $this->createKnowbaseItem([
            'name' => 'Mobile Device Policy',
            'description' => 'Company mobile device guidelines',
            'answer' => 'Using mobile devices at work',
            'show_in_service_catalog' => 1,
        ]);

        // Create request without filter
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: ''
        );

        $knowbase_items = $this->provider->getItems($request);
        $kb_names = array_map(fn($kb) => $kb->fields['name'], $knowbase_items);

        // Items should be sorted alphabetically by name
        $expected_order = ['Account Setup', 'Mobile Device Policy', 'Zoom Usage Guide'];
        $this->assertEquals($expected_order, $kb_names);
    }

    /**
     * Test that no knowbase items are returned when filter doesn't match anything
     */
    public function testNoMatchingKnowbaseItems()
    {
        $this->login();

        // Create a knowbase item
        $kb = $this->createKnowbaseItem([
            'name' => 'IT Support Guide',
            'description' => 'Technical assistance',
            'answer' => 'Get help with technical issues',
            'show_in_service_catalog' => 1,
        ]);

        // Test filter that doesn't match
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: 'nonexistent'
        );

        $knowbase_items = $this->provider->getItems($request);

        // No items should be returned
        $this->assertEmpty($knowbase_items);
    }

    /**
     * Test that empty filter returns all service catalog knowbase items
     */
    public function testEmptyFilterReturnsAllServiceCatalogItems()
    {
        $this->login();

        // Create knowbase items
        $kb1 = $this->createKnowbaseItem([
            'name' => 'Item One',
            'description' => 'First item',
            'answer' => 'First item content',
            'show_in_service_catalog' => 1,
        ]);

        $kb2 = $this->createKnowbaseItem([
            'name' => 'Item Two',
            'description' => 'Second item',
            'answer' => 'Second item content',
            'show_in_service_catalog' => 1,
        ]);

        // Test with empty filter
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: ''
        );

        $knowbase_items = $this->provider->getItems($request);

        // All service catalog items should be returned
        $this->assertCount(2, $knowbase_items);
        $kb_names = array_column(array_map(fn($kb) => $kb->fields, $knowbase_items), 'name');
        $this->assertContains('Item One', $kb_names);
        $this->assertContains('Item Two', $kb_names);
    }

    /**
     * Test that knowbase items with empty fields are handled properly
     */
    public function testKnowbaseItemsWithEmptyFields()
    {
        $this->login();

        // Create knowbase item with empty description
        $kb1 = $this->createKnowbaseItem([
            'name' => 'Item with empty description',
            'description' => '',
            'answer' => 'Only answer here',
            'show_in_service_catalog' => 1,
        ]);

        // Create knowbase item with empty content
        $kb2 = $this->createKnowbaseItem([
            'name' => 'Item with empty name',
            'description' => 'Only description here',
            'answer' => '',
            'show_in_service_catalog' => 1,
        ]);

        // Test filter that should match the content/description
        $request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo()
            ),
            filter: 'description'
        );

        $knowbase_items = $this->provider->getItems($request);
        $kb_ids = array_map(fn($kb) => $kb->fields['id'], $knowbase_items);

        // Should match both items
        $this->assertContains($kb1->getID(), $kb_ids);
        $this->assertContains($kb2->getID(), $kb_ids);
    }

    /**
     * Helper method to create a knowbase item
     */
    private function createKnowbaseItem(array $input): KnowbaseItem
    {
        return $this->createItem(KnowbaseItem::class, $input);
    }
}
