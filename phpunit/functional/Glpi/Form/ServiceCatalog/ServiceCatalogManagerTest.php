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

namespace tests\units\Glpi\Form;

use AbstractRightsDropdown;
use Computer;
use ComputerType;
use Entity;
use Entity_KnowbaseItem;
use Glpi\Form\AccessControl\ControlType\AllowList;
use Glpi\Form\AccessControl\ControlType\AllowListConfig;
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\Category;
use Glpi\Form\Form;
use Glpi\Form\ServiceCatalog\ItemRequest;
use Glpi\Form\ServiceCatalog\ServiceCatalogItemInterface;
use Glpi\Form\ServiceCatalog\ServiceCatalogManager;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use GlpiPlugin\Tester\Form\ComputerForServiceCatalog;
use KnowbaseItem;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Session;
use User;

final class ServiceCatalogManagerTest extends \DbTestCase
{
    use FormTesterTrait;

    private static ServiceCatalogManager $manager;

    public static function setUpBeforeClass(): void
    {
        self::$manager = ServiceCatalogManager::getInstance();
        parent::setUpBeforeClass();
    }

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        // Disable default forms to not pollute the tests of this file
        $this->disableExistingForms();
    }

    public function testOnlyActiveFormsAreDisplayed(): void
    {
        // Arrange: create a mix of active/inactive forms
        $builders = [
            (new FormBuilder("Active form 1"))->setIsActive(true),
            (new FormBuilder("Active form 2"))->setIsActive(true),
            (new FormBuilder("Inactive form 1"))->setIsActive(false),
            (new FormBuilder("Inactive form 2"))->setIsActive(false),
            (new FormBuilder("Inactive form 3"))->setIsActive(false),
        ];
        foreach ($builders as $builder) {
            $this->createForm($builder);
        }

        // Act: get the forms from the catalog manager and extract their names
        $this->login();
        $item_request = new ItemRequest(
            new FormAccessParameters(Session::getCurrentSessionInfo())
        );
        $forms = self::$manager->getItems($item_request)['items'];
        $forms_names = array_map(fn(Form $form) => $form->fields['name'], $forms);

        // Assert: only active forms must be found.
        $this->assertEquals([
            "Active form 1",
            "Active form 2",
        ], $forms_names);
    }

    public function testItemsAreOrderedByPinnedStatus(): void
    {
        // Arrange: create forms with pinned status
        $builders = [
            (new FormBuilder("Pinned form 1"))->setIsPinned(true),
            (new FormBuilder("Pinned form 2"))->setIsPinned(true),
            (new FormBuilder("Not pinned form 1"))->setIsPinned(false),
            (new FormBuilder("Not pinned form 2"))->setIsPinned(false),
        ];
        foreach ($builders as $builder) {
            $builder->setIsActive(true);
            $this->createForm($builder);
        }

        // Act: get the forms from the catalog manager and extract their names
        $this->login();
        $item_request = new ItemRequest(
            new FormAccessParameters(Session::getCurrentSessionInfo())
        );
        $forms = self::$manager->getItems($item_request)['items'];
        $forms_names = array_map(fn(Form $form) => $form->fields['name'], $forms);

        // Assert: pinned forms must be displayed first
        $this->assertEquals([
            "Pinned form 1",
            "Pinned form 2",
            "Not pinned form 1",
            "Not pinned form 2",
        ], $forms_names);
    }

    public function testItemsAreOrderedByNames(): void
    {
        // Arrange: create forms and categories with unordered names
        $category_1 = $this->createItem(Category::class, ['name' => 'BBB']);
        $category_2 = $this->createItem(Category::class, ['name' => 'QQQ']);
        $builders = [
            new FormBuilder("ZZZ"),
            new FormBuilder("AAA"),
            new FormBuilder("CCC"),
            // This two forms won't be displayed, they are needed to make sure
            // the categories are not empty.
            (new FormBuilder("child 1"))->setCategory($category_1->getID()),
            (new FormBuilder("child 2"))->setCategory($category_2->getID()),
        ];
        foreach ($builders as $builder) {
            $builder->setIsActive(true);
            $this->createForm($builder);
        }

        // Act: get the forms from the catalog manager and extract their names
        $this->login();
        $item_request = new ItemRequest(
            access_parameters: new FormAccessParameters(Session::getCurrentSessionInfo()),
            category_id: 0,
        );
        $forms = self::$manager->getItems($item_request)['items'];
        $forms_names = array_map(fn(ServiceCatalogItemInterface $item) => $item->getServiceCatalogItemTitle(), $forms);

        // Assert: forms must be ordered by name
        $this->assertEquals([
            "BBB",
            "QQQ",
            // Categories are always at the top
            "AAA",
            "CCC",
            "ZZZ",
        ], $forms_names);
    }

    public function testItemsAreOrderedByPinnedStatusAndNames(): void
    {
        // Arrange: create forms with pinned status and unordered names
        $builders = [
            (new FormBuilder("C Not pinned form"))->setIsPinned(false),
            (new FormBuilder("B Pinned form"))->setIsPinned(true),
            (new FormBuilder("B Not pinned form"))->setIsPinned(false),
            (new FormBuilder("A Pinned form"))->setIsPinned(true),
            (new FormBuilder("C Pinned form"))->setIsPinned(true),
            (new FormBuilder("A Not pinned form"))->setIsPinned(false),
        ];
        foreach ($builders as $builder) {
            $builder->setIsActive(true);
            $this->createForm($builder);
        }

        // Act: get the forms from the catalog manager and extract their names
        $this->login();
        $item_request = new ItemRequest(
            new FormAccessParameters(Session::getCurrentSessionInfo())
        );
        $forms = self::$manager->getItems($item_request)['items'];
        $forms_names = array_map(fn(ServiceCatalogItemInterface $item) => $item->getServiceCatalogItemTitle(), $forms);

        // Assert: pinned forms must be displayed first, then forms are ordered by name
        $this->assertEquals([
            "A Pinned form",
            "B Pinned form",
            "C Pinned form",
            "A Not pinned form",
            "B Not pinned form",
            "C Not pinned form",
        ], $forms_names);
    }

    public function testFormsWithActiveAccessPoliciesAreFound(): void
    {
        // Arrange: create a form with an active policy
        $builder = new FormBuilder("Form with active policy");
        $builder->setUseDefaultAccessPolicies(false);
        $builder->setUseDefaultAccessPolicies(false);
        $builder->addAccessControl(
            strategy: AllowList::class,
            config: new AllowListConfig(
                user_ids: [AbstractRightsDropdown::ALL_USERS]
            ),
            is_active: true,
        );
        $builder->setIsActive(true);
        $this->createForm($builder);

        // Act: get the forms from the catalog manager and extract their names
        $this->login();
        $item_request = new ItemRequest(
            new FormAccessParameters(Session::getCurrentSessionInfo())
        );
        $forms = self::$manager->getItems($item_request)['items'];
        $forms_names = array_map(fn(Form $form) => $form->fields['name'], $forms);

        // Assert: our form must be found
        $this->assertEquals(["Form with active policy"], $forms_names);
    }

    public function testFormWithoutAccessPoliciesAreNotFound(): void
    {
        // Arrange: create a form without any policies
        $builder = new FormBuilder("Form without policies");
        $builder->setIsActive(true);
        $builder->setUseDefaultAccessPolicies(false);
        $this->createForm($builder);

        // Act: get the forms from the catalog manager and extract their names
        $this->login();
        $item_request = new ItemRequest(
            new FormAccessParameters(Session::getCurrentSessionInfo())
        );
        $forms = self::$manager->getItems($item_request)['items'];
        $forms_names = array_map(fn(Form $form) => $form->fields['name'], $forms);

        // Assert: our form must not be found
        $this->assertEquals([], $forms_names);
    }

    public function testFormWithInactiveAccessPoliciesAreNotFound(): void
    {
        // Arrange: create a form with an inactive policy
        $builder = new FormBuilder("Form with inactive policy");
        $builder->setUseDefaultAccessPolicies(false);
        $builder->addAccessControl(
            strategy: AllowList::class,
            config: new AllowListConfig(
                user_ids: [AbstractRightsDropdown::ALL_USERS]
            ),
            is_active: false,
        );
        $builder->setIsActive(true);
        $this->createForm($builder);

        // Act: get the forms from the catalog manager and extract their names
        $this->login();
        $item_request = new ItemRequest(
            new FormAccessParameters(Session::getCurrentSessionInfo())
        );
        $forms = self::$manager->getItems($item_request)['items'];
        $forms_names = array_map(fn(Form $form) => $form->fields['name'], $forms);

        // Assert: our form must not be found
        $this->assertEquals([], $forms_names);
    }

    public static function onlyFormThatMatchAllowListCriteriaAreFoundProvider(): iterable
    {
        $allow_list = new AllowListConfig(
            user_ids: [
                getItemByTypeName(User::class, "tech", true),
                getItemByTypeName(User::class, "normal", true),
            ],
        );
        yield 'glpi' => [
            'config'   => $allow_list,
            'user'     => 'glpi',
            'password' => 'glpi',
            'expected' => false,
        ];
        yield 'tech' => [
            'config'   => $allow_list,
            'user'     => 'tech',
            'password' => 'tech',
            'expected' => true,
        ];
        yield 'normal' => [
            'config'   => $allow_list,
            'user'     => 'normal',
            'password' => 'normal',
            'expected' => true,
        ];
        yield 'post-only' => [
            'config'   => $allow_list,
            'user'     => 'post-only',
            'password' => 'postonly',
            'expected' => false,
        ];
    }

    #[DataProvider('onlyFormThatMatchAllowListCriteriaAreFoundProvider')]
    public function testOnlyFormThatMatchAllowListCriteriaAreFound(
        AllowListConfig $config,
        string $user,
        string $password,
        bool $expected,
    ): void {
        // Arrange: create a form with an allow list
        $builder = new FormBuilder();
        $builder->setEntitiesId(0);
        $builder->setUseDefaultAccessPolicies(false);
        $builder->addAccessControl(
            strategy: AllowList::class,
            config: $config,
            is_active: true,
        );
        $builder->setIsActive(true);
        $this->createForm($builder);

        // Act: as the specified user, get the number of forms from the catalog manager
        $this->login($user, $password);
        $item_request = new ItemRequest(
            new FormAccessParameters(Session::getCurrentSessionInfo())
        );
        $forms = self::$manager->getItems($item_request)['items'];
        $nb_forms = count($forms);

        // Assert: list should be empty if we don't expect the user to see the form
        $this->assertEquals($expected, $nb_forms === 1);
    }

    public static function formsCanBeFilteredProvider(): iterable
    {
        // Simple tests cases using string comparison
        $forms_data = [
            ['name' => "Red form", 'description'  => 'My first description'],
            ['name' => "Blue form", 'description' => 'My second description'],
            ['name' => "Pink form", 'description' => 'My third description'],
        ];
        yield 'Simple comparison using name' => [
            'forms_data'           => $forms_data,
            'filter'               => "Blue",
            'expected_forms_names' => ["Blue form"],
        ];
        yield 'Simple comparison using name (case insensitive)' => [
            'forms_data'           => $forms_data,
            'filter'               => "pInK",
            'expected_forms_names' => ["Pink form"],
        ];
        yield 'Simple contains filter on description' => [
            'forms_data'           => $forms_data,
            'filter'               => "third",
            'expected_forms_names' => ["Pink form"],
        ];
        yield 'Simple contains filter on description (case insensitive)' => [
            'forms_data'           => $forms_data,
            'filter'               => "fIrSt",
            'expected_forms_names' => ["Red form"],
        ];

        // Test using fuzzy search
        $forms_data = [
            ['name' => "Fruits form", 'description'  => 'Banana, apple, orange'],
            ['name' => "Vegetable form", 'description' => 'Carrot, tomato, cucumber'],
        ];
        yield 'Partial match on name' => [
            'forms_data'           => $forms_data,
            'filter'               => "Fruit form",
            'expected_forms_names' => ["Fruits form"],
        ];
        yield 'Invalid partial match on name' => [
            'forms_data'           => $forms_data,
            'filter'               => "Frui and veg form",
            'expected_forms_names' => [],
        ];
        yield 'Partial match on description' => [
            'forms_data'           => $forms_data,
            'filter'               => "banana orange",
            'expected_forms_names' => ["Fruits form"],
        ];
        yield 'Invalid partial match on description' => [
            'forms_data'           => $forms_data,
            'filter'               => "banana lemon orange",
            'expected_forms_names' => [],
        ];
        yield 'Partial match on name with spelling mistakes' => [
            'forms_data'           => $forms_data,
            'filter'               => "Vegetoble form",
            'expected_forms_names' => ["Vegetable form"],
        ];
        yield 'Partial match on description with spelling mistakes' => [
            'forms_data'           => $forms_data,
            'filter'               => "cuccummber",
            'expected_forms_names' => ["Vegetable form"],
        ];
        yield 'Too many spelling mistakes' => [
            'forms_data'           => $forms_data,
            'filter'               => "Bonanonano",
            'expected_forms_names' => [],
        ];
    }

    #[DataProvider('formsCanBeFilteredProvider')]
    public function testFormsCanBeFiltered(
        array $forms_data,
        string $filter,
        array $expected_forms_names,
    ): void {
        // Arrange: create a form with the specified names
        foreach ($forms_data as $form_data) {
            $builder = new FormBuilder($form_data['name']);
            $builder->setDescription($form_data['description']);
            $builder->setIsActive(true);
            $this->createForm($builder);
        }

        // Act: filter the forms
        $this->login();
        $item_request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                Session::getCurrentSessionInfo()
            ),
            filter: $filter,
        );
        $forms = self::$manager->getItems($item_request)['items'];

        // Assert: only the expected forms must be found
        $forms_names = array_map(fn(Form $form) => $form->fields['name'], $forms);
        $this->assertEquals($expected_forms_names, $forms_names);
    }

    public function testOnlyFormsFromVisibleEntitiesAreFound(): void
    {
        $test_entity_id = $this->getTestRootEntity(only_id: true);

        // Act: create multiple forms with different entity configuration
        $builder = new FormBuilder("Root + recursive");
        $builder->setEntitiesId(0);
        $builder->setIsRecursive(true);
        $this->createForm($builder);

        $builder = new FormBuilder("Root");
        $builder->setEntitiesId(0);
        $builder->setIsRecursive(false);
        $this->createForm($builder);

        $builder = new FormBuilder("Test entity");
        $builder->setEntitiesId($test_entity_id);
        $builder->setIsRecursive(false);
        $this->createForm($builder);

        // Act: get the forms from the service catalog
        $this->login();
        $item_request = new ItemRequest(
            new FormAccessParameters(Session::getCurrentSessionInfo())
        );
        $forms = self::$manager->getItems($item_request)['items'];

        // Assert: only the forms visible from the test entity should be found
        $forms_names = array_map(fn(Form $form) => $form->fields['name'], $forms);
        $this->assertEquals([
            "Root + recursive",
            "Test entity",
        ], $forms_names);
    }

    public function testRootContent(): void
    {
        // Arrange: create a few forms with and without categories
        $category_a = $this->createItem(Category::class, ['name' => 'Category A']);
        $category_b = $this->createItem(Category::class, [
            'name' => 'Category B',
            Category::getForeignKeyField() => $category_a->getID(),
        ]);
        $builders = [
            new FormBuilder("Root form 1"),
            new FormBuilder("Root form 2"),
            (new FormBuilder("Form from category A"))->setCategory($category_a->getID()),
            (new FormBuilder("Form from category B"))->setCategory($category_b->getID()),
        ];
        foreach ($builders as $builder) {
            $builder->setIsActive(true);
            $this->createForm($builder);
        }

        // Act: get the root items from the catalog manager and extract their names
        $this->login();
        $item_request = new ItemRequest(
            access_parameters: new FormAccessParameters(Session::getCurrentSessionInfo()),
            category_id: 0,
        );
        $items = self::$manager->getItems($item_request)['items'];
        $items_names = array_map(
            fn(ServiceCatalogItemInterface $item) => $item->getServiceCatalogItemTitle(),
            $items
        );

        // Assert: only root items must be found
        $this->assertEquals([
            "Category A",
            "Root form 1",
            "Root form 2",
        ], $items_names);
    }

    public function testCategoryContent(): void
    {
        // Arrange: create a few forms with and without categories
        $category_a = $this->createItem(Category::class, ['name' => 'Category A']);
        $category_b = $this->createItem(Category::class, [
            'name' => 'Category B',
            Category::getForeignKeyField() => $category_a->getID(),
        ]);
        $builders = [
            new FormBuilder("Root form 1"),
            new FormBuilder("Root form 2"),
            (new FormBuilder("Form from category A"))->setCategory($category_a->getID()),
            (new FormBuilder("Form from category B"))->setCategory($category_b->getID()),
        ];
        foreach ($builders as $builder) {
            $builder->setIsActive(true);
            $this->createForm($builder);
        }

        // Act: get the items from the category A and extract their names
        $this->login();
        $item_request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                Session::getCurrentSessionInfo()
            ),
            category_id: $category_a->getID(),
        );
        $items = self::$manager->getItems($item_request)['items'];
        $items_names = array_map(
            fn(ServiceCatalogItemInterface $item) => $item->getServiceCatalogItemTitle(),
            $items
        );

        // Assert: only forms and categories inside "Category A" must be found.
        $this->assertEquals([
            "Category B",
            "Form from category A",
        ], $items_names);
    }

    public function testCategoriesCanBeFiltered(): void
    {
        // Arrange: create a few forms with categories
        $category_a = $this->createItem(Category::class, ['name' => 'A']);
        $category_b = $this->createItem(Category::class, ['name' => 'B']);
        $category_c1 = $this->createItem(Category::class, ['name' => 'C1']);
        $category_c2 = $this->createItem(Category::class, ['name' => 'C2']);
        $builders = [
            (new FormBuilder("Form from category A"))->setCategory($category_a->getID()),
            (new FormBuilder("Form from category B"))->setCategory($category_b->getID()),
            (new FormBuilder("Form from category C1"))->setCategory($category_c1->getID()),
            (new FormBuilder("Form from category C2"))->setCategory($category_c2->getID()),
        ];
        foreach ($builders as $builder) {
            $builder->setIsActive(true);
            $this->createForm($builder);
        }

        // Act: get the items using a filter
        $this->login();
        $item_request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                Session::getCurrentSessionInfo()
            ),
            filter: 'C',
            category_id: 0,
        );
        $items = self::$manager->getItems($item_request)['items'];
        $items_names = array_map(
            fn(ServiceCatalogItemInterface $item) => $item->getServiceCatalogItemTitle(),
            $items
        );

        // Assert: only categories that contains "C" must be found
        $this->assertEquals([
            "C1",
            "C2",
        ], $items_names);
    }

    public function testEmptyCategoriesAreNotFound(): void
    {
        // Arrange: create a few forms with categories
        // Category A -> has a form
        // Category B -> no forms
        // Category C -> Category C1 -> no forms
        // Category D -> Category D1 -> has a form
        $category_a = $this->createItem(Category::class, ['name' => 'Category A']);
        $this->createItem(Category::class, ['name' => 'Category B']);
        $category_c = $this->createItem(Category::class, ['name' => 'Category C']);
        $this->createItem(Category::class, [
            'name' => 'Category C1',
            Category::getForeignKeyField() => $category_c->getID(),
        ]);
        $category_d = $this->createItem(Category::class, ['name' => 'Category D']);
        $category_d1 = $this->createItem(Category::class, [
            'name' => 'Category D1',
            Category::getForeignKeyField() => $category_d->getID(),
        ]);
        $builders = [
            (new FormBuilder("Form from category A"))->setCategory($category_a->getID()),
            (new FormBuilder("Form from category D1"))->setCategory($category_d1->getID()),
        ];
        foreach ($builders as $builder) {
            $builder->setIsActive(true);
            $this->createForm($builder);
        }

        // Act: get the root items
        $this->login();
        $item_request = new ItemRequest(
            access_parameters: new FormAccessParameters(Session::getCurrentSessionInfo()),
            category_id: 0,
        );
        $items = self::$manager->getItems($item_request)['items'];
        $items_names = array_map(
            fn(ServiceCatalogItemInterface $item) => $item->getServiceCatalogItemTitle(),
            $items
        );

        // Assert: only categories with (direct or indirect) children must be found
        $this->assertEquals([
            "Category A",
            "Category D",
        ], $items_names);
    }

    public function testKnowledgeBaseItemsAreDisplayed(): void
    {
        $this->login();

        // Create a category for knowledge base items
        $category = $this->createItem(Category::class, ['name' => 'KB Category']);

        // Create knowledge base items
        $kb_items = [
            $this->createItem(KnowbaseItem::class, [
                'name'                    => 'KB Item 1',
                'answer'                  => 'KB Item 1 content',
                'is_faq'                  => 1,
                'show_in_service_catalog' => 1,
                'forms_categories_id'     => $category->getID(),
                'users_id'                => Session::getLoginUserID(),
            ]),
            $this->createItem(KnowbaseItem::class, [
                'name'                    => 'KB Item 2',
                'answer'                  => 'KB Item 2 content',
                'is_faq'                  => 1,
                'show_in_service_catalog' => 1,
                'forms_categories_id'     => 0,                            // Root level
                'users_id'                => Session::getLoginUserID(),
            ]),
            $this->createItem(KnowbaseItem::class, [
                'name'                    => 'KB Item 3',
                'answer'                  => 'KB Item 3 content',
                'is_faq'                  => 1,
                'show_in_service_catalog' => 0,
                'forms_categories_id'     => 0,                            // Root level
                'users_id'                => Session::getLoginUserID(),
            ]),
        ];

        // Get the root items
        $this->login();
        $item_request = new ItemRequest(
            access_parameters: new FormAccessParameters(Session::getCurrentSessionInfo()),
            category_id: 0,
        );
        $items = self::$manager->getItems($item_request)['items'];
        $items_names = array_map(
            fn(ServiceCatalogItemInterface $item) => $item->getServiceCatalogItemTitle(),
            $items
        );

        // Assert: KB items should be displayed
        $this->assertContains('KB Item 2', $items_names);
        $this->assertContains('KB Category', $items_names);
        $this->assertNotContains('KB Item 1', $items_names);
        $this->assertNotContains('KB Item 3', $items_names);

        // Get the items from the category
        $this->login();
        $item_request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                Session::getCurrentSessionInfo()
            ),
            category_id: $category->getID(),
        );
        $items = self::$manager->getItems($item_request)['items'];
        $items_names = array_map(
            fn(ServiceCatalogItemInterface $item) => $item->getServiceCatalogItemTitle(),
            $items
        );

        // Assert: KB items in the category should be displayed
        $this->assertContains('KB Item 1', $items_names);
        $this->assertNotContains('KB Item 2', $items_names);
        $this->assertNotContains('KB Item 3', $items_names);
        $this->assertNotContains('KB Category', $items_names);
    }

    public function testKnowledgeBaseItemsCanBeFiltered(): void
    {
        $this->login();

        // Create knowledge base items with different content
        $kb_items = [
            $this->createItem(KnowbaseItem::class, [
                'name'                    => 'Technical KB',
                'answer'                  => 'Technical content about servers',
                'description'             => 'Server knowledge',
                'is_faq'                  => 1,
                'show_in_service_catalog' => 1,
                'users_id'                => Session::getLoginUserID(),
            ]),
            $this->createItem(KnowbaseItem::class, [
                'name'                    => 'User Guide KB',
                'answer'                  => 'How to use the application',
                'description'             => 'User documentation',
                'is_faq'                  => 1,
                'show_in_service_catalog' => 1,
                'users_id'                => Session::getLoginUserID(),
            ]),
            $this->createItem(KnowbaseItem::class, [
                'name'                    => 'Hidden KB',
                'answer'                  => 'This KB should not be visible in service catalog',
                'description'             => 'Hidden documentation',
                'is_faq'                  => 1,
                'show_in_service_catalog' => 0,  // Not shown in service catalog
                'users_id'                => Session::getLoginUserID(),
            ]),
        ];

        // Search with a filter matching name
        $this->login();
        $item_request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                Session::getCurrentSessionInfo()
            ),
            filter: 'Technical',
        );
        $items = self::$manager->getItems($item_request)['items'];
        $items_names = array_map(
            fn(ServiceCatalogItemInterface $item) => $item->getServiceCatalogItemTitle(),
            $items
        );

        // Assert: Only the technical KB item should be found
        $this->assertContains('Technical KB', $items_names);
        $this->assertNotContains('User Guide KB', $items_names);
        $this->assertNotContains('Hidden KB', $items_names);

        // Search with a filter matching description
        $item_request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                Session::getCurrentSessionInfo()
            ),
            filter: 'documentation',
        );
        $items = self::$manager->getItems($item_request)['items'];
        $items_names = array_map(
            fn(ServiceCatalogItemInterface $item) => $item->getServiceCatalogItemTitle(),
            $items
        );

        // Assert: Only the user guide KB item should be found
        $this->assertContains('User Guide KB', $items_names);
        $this->assertNotContains('Technical KB', $items_names);
        $this->assertNotContains('Hidden KB', $items_names);

        // Search with a filter matching content
        $item_request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                Session::getCurrentSessionInfo()
            ),
            filter: 'servers',
        );
        $items = self::$manager->getItems($item_request)['items'];
        $items_names = array_map(
            fn(ServiceCatalogItemInterface $item) => $item->getServiceCatalogItemTitle(),
            $items
        );

        // Assert: Only the technical KB item should be found
        $this->assertContains('Technical KB', $items_names);
        $this->assertNotContains('User Guide KB', $items_names);
        $this->assertNotContains('Hidden KB', $items_names);

        // Verify hidden KB isn't shown even with matching filter
        $item_request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                Session::getCurrentSessionInfo()
            ),
            filter: 'Hidden',
        );
        $items = self::$manager->getItems($item_request)['items'];
        $items_names = array_map(
            fn(ServiceCatalogItemInterface $item) => $item->getServiceCatalogItemTitle(),
            $items
        );

        // Assert: Hidden KB should not be found regardless of matching filter
        $this->assertNotContains('Hidden KB', $items_names);
    }

    public function testKnowledgeBaseItemOrderingByPinnedStatus(): void
    {
        $this->login();

        // Create knowledge base items with different pinned status
        $kb_items = [
            $this->createItem(KnowbaseItem::class, [
                'name'                    => 'Not pinned KB',
                'answer'                  => 'Regular KB item',
                'is_faq'                  => 1,
                'is_pinned'               => 0,
                'show_in_service_catalog' => 1,
                'users_id'                => Session::getLoginUserID(),
            ]),
            $this->createItem(KnowbaseItem::class, [
                'name'                    => 'Pinned KB',
                'answer'                  => 'Important KB item',
                'is_faq'                  => 1,
                'is_pinned'               => 1,
                'show_in_service_catalog' => 1,
                'users_id'                => Session::getLoginUserID(),
            ]),
            $this->createItem(KnowbaseItem::class, [
                'name'                    => 'Hidden KB',
                'answer'                  => 'This should not appear in results',
                'is_faq'                  => 1,
                'is_pinned'               => 1,
                'show_in_service_catalog' => 0, // Not shown in service catalog
                'users_id'                => Session::getLoginUserID(),
            ]),
        ];

        // Get all items
        $item_request = new ItemRequest(
            new FormAccessParameters(Session::getCurrentSessionInfo()),
        );
        $items = self::$manager->getItems($item_request)['items'];

        // Extract just the KB items from results (filtering out categories)
        $kb_items = array_filter($items, function ($item) {
            return $item instanceof KnowbaseItem;
        });

        // Get the names in order
        $items_names = array_map(
            fn($item) => $item->getServiceCatalogItemTitle(),
            array_values($kb_items)
        );

        // Assert: Pinned item should appear first and hidden item should not appear
        $this->assertEquals(['Pinned KB', 'Not pinned KB'], $items_names);
        $this->assertNotContains('Hidden KB', $items_names);
    }

    public function testOnlyKBFromVisibleEntitiesAreFound(): void
    {
        $test_entity_id = $this->getTestRootEntity(only_id: true);

        // Act: create multiple KB with different entity configuration
        $kb1 = $this->createItem(KnowbaseItem::class, [
            'name'                    => 'KB root + recursive',
            'answer'                  => 'My answer',
            'is_faq'                  => true,
            'show_in_service_catalog' => true,
            'users_id'                => 2, // Important: not our current user
        ]);
        $this->createItem(Entity_KnowbaseItem::class, [
            KnowbaseItem::getForeignKeyField() => $kb1->getID(),
            Entity::getForeignKeyField()       => 0,
            'is_recursive'                     => true,
        ]);

        $kb2 = $this->createItem(KnowbaseItem::class, [
            'name'                    => 'KB root',
            'answer'                  => 'My answer',
            'is_faq'                  => true,
            'show_in_service_catalog' => true,
            'users_id'                => 2, // Important: not our current user
        ]);
        $this->createItem(Entity_KnowbaseItem::class, [
            KnowbaseItem::getForeignKeyField() => $kb2->getID(),
            Entity::getForeignKeyField()       => 0,
            'is_recursive'                     => false,
        ]);

        $kb3 = $this->createItem(KnowbaseItem::class, [
            'name'                    => 'KB test entity',
            'answer'                  => 'My answer',
            'is_faq'                  => true,
            'show_in_service_catalog' => true,
            'users_id'                => 2, // Important: not our current user
        ]);
        $this->createItem(Entity_KnowbaseItem::class, [
            KnowbaseItem::getForeignKeyField() => $kb3->getID(),
            Entity::getForeignKeyField()       => $this->getTestRootEntity(true),
            'is_recursive'                     => true,
        ]);

        // Act: get the KB from the service catalog
        $this->login('post-only', 'postonly'); // Need to be a non-admin.
        $this->setEntity($this->getTestRootEntity(true), true);
        $item_request = new ItemRequest(
            new FormAccessParameters(Session::getCurrentSessionInfo())
        );
        $kbs = self::$manager->getItems($item_request)['items'];

        // Assert: only the kb visible from the test entity should be found
        $kb_names = array_map(fn(KnowbaseItem $kb) => $kb->fields['name'], $kbs);
        $this->assertEquals([
            "KB root + recursive",
            "KB test entity",
        ], $kb_names);
    }

    public function testMixedFormsAndKnowledgeBaseItems(): void
    {
        $this->login();

        // Create a form
        $builder = new FormBuilder("Test Form");
        $builder->setIsActive(true);
        $builder->setIsPinned(true);
        $this->createForm($builder);

        // Create a KB item
        $kb_item = $this->createItem(KnowbaseItem::class, [
            'name'                    => 'Test KB',
            'answer'                  => 'KB content',
            'is_faq'                  => 1,
            'is_pinned'               => 0,
            'show_in_service_catalog' => 1,
            'users_id'                => Session::getLoginUserID(),
        ]);

        // Get all items
        $item_request = new ItemRequest(
            new FormAccessParameters(Session::getCurrentSessionInfo()),
        );
        $items = self::$manager->getItems($item_request)['items'];
        $items_names = array_map(
            fn(ServiceCatalogItemInterface $item) => $item->getServiceCatalogItemTitle(),
            $items
        );

        // Assert: Both forms and KB items should be displayed
        $this->assertContains('Test Form', $items_names);
        $this->assertContains('Test KB', $items_names);
    }

    public function testPinnedFormsAreNotImpactedByFilters(): void
    {
        // Arrange: create multiple pinned and not pinned forms
        $builders = [
            (new FormBuilder("Pinned form 1"))->setIsPinned(true),
            (new FormBuilder("Pinned form 2"))->setIsPinned(true),
            (new FormBuilder("Not pinned form 1"))->setIsPinned(false),
            (new FormBuilder("Not pinned form 2"))->setIsPinned(false),
        ];
        foreach ($builders as $builder) {
            $builder->setIsActive(true);
            $this->createForm($builder);
        }

        // Act: filter the forms
        $this->login();
        $item_request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                Session::getCurrentSessionInfo()
            ),
            filter: "aaaaaaaaaaaaaaaaaaaaa",
        );
        $forms = self::$manager->getItems($item_request)['items'];

        // Assert: only the pinned forms must be found
        $forms_names = array_map(fn(Form $form) => $form->fields['name'], $forms);
        $this->assertEquals([
            "Pinned form 1",
            "Pinned form 2",
        ], $forms_names);
    }

    public static function provideFormsAvailabilityFromSpecificEntities(): iterable
    {
        yield 'Forms from child entities are found' => [
            'entity_name'          => '_test_root_entity',
            'subtree'              => true,
            'is_recursive'         => true,
            'expected_forms_names' => [
                "Form from child entity",
                "Form from test root entity",
            ],
        ];

        yield 'Forms from child entities are not found when not subtree' => [
            'entity_name'          => '_test_root_entity',
            'subtree'              => false,
            'is_recursive'         => true,
            'expected_forms_names' => [
                "Form from test root entity",
            ],
        ];

        yield 'Forms from parent entities are found' => [
            'entity_name'          => 'Child Entity',
            'subtree'              => true,
            'is_recursive'         => true,
            'expected_forms_names' => [
                "Form from child entity",
                "Form from test root entity",
            ],
        ];

        yield 'Forms from parent entities are not found when not recursive' => [
            'entity_name'          => 'Child Entity',
            'subtree'              => true,
            'is_recursive'         => false,
            'expected_forms_names' => [
                "Form from child entity",
            ],
        ];
    }

    #[DataProvider('provideFormsAvailabilityFromSpecificEntities')]
    public function testFormsAvailabilityFromSpecificEntities(
        string $entity_name,
        bool $subtree,
        bool $is_recursive,
        array $expected_forms_names
    ): void {
        $this->login();

        // Arrange: create a form in the test root entity
        $builder = new FormBuilder("Form from test root entity");
        $builder->setEntitiesId($this->getTestRootEntity(true));
        $builder->setIsActive(true);
        $builder->setIsRecursive($is_recursive);
        $this->createForm($builder);

        // Create a child entity and a form in it
        $child_entity = $this->createItem(Entity::class, [
            'name'        => 'Child Entity',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $builder = new FormBuilder("Form from child entity");
        $builder->setEntitiesId($child_entity->getID());
        $builder->setIsActive(true);
        $builder->setIsRecursive($is_recursive);
        $this->createForm($builder);

        // Set the current entity to the specified entity
        $this->setEntity($entity_name, $subtree);

        // Act: get the forms from the catalog manager
        $item_request = new ItemRequest(
            new FormAccessParameters(Session::getCurrentSessionInfo())
        );
        $forms = self::$manager->getItems($item_request)['items'];

        // Assert: expected forms must be found
        $forms_names = array_map(fn(Form $form) => $form->fields['name'], $forms);
        $this->assertEquals($expected_forms_names, $forms_names);
    }

    public function testPluginCanRegisterProviders(): void
    {
        // Arrange: create some computers in the 'test' type
        $type = $this->createItem(ComputerType::class, [
            'name' => 'test',
        ]);
        $to_create = ['Computer 1', 'Computer 2', 'Computer 3'];
        foreach ($to_create as $name) {
            $this->createItem(Computer::class, [
                'name' => $name,
                'entities_id' => $this->getTestRootEntity(only_id: true),
                ComputerType::getForeignKeyField() => $type->getID(),
            ]);
        }

        // Act: get items
        $this->login('post-only');
        $item_request = new ItemRequest(
            new FormAccessParameters(Session::getCurrentSessionInfo())
        );
        $results = ServiceCatalogManager::getInstance()->getItems($item_request);

        // Assert: computers should be found thanks to the ComputerProvider from
        // the tester plugin
        $names = array_map(
            fn(ComputerForServiceCatalog $c) => $c->getServiceCatalogItemTitle(),
            $results["items"],
        );
        $this->assertEquals(['Computer 1', 'Computer 2', 'Computer 3'], $names);
    }
}
