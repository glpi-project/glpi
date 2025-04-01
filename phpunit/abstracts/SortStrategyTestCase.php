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

use Glpi\Form\Category;
use Glpi\Form\ServiceCatalog\ItemRequest;
use Glpi\Form\ServiceCatalog\ServiceCatalogManager;
use Glpi\Form\ServiceCatalog\SortStrategy\SortStrategyFactory;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use KnowbaseItem;
use Override;

abstract class SortStrategyTestCase extends \DbTestCase
{
    use FormTesterTrait;

    protected static ServiceCatalogManager $manager;

    public static function setUpBeforeClass(): void
    {
        self::$manager = new ServiceCatalogManager();
        parent::setUpBeforeClass();
    }

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        // Disable default forms to not pollute the tests of this file
        $this->disableExistingForms();
    }

    /**
     * Return the sort strategy key to test
     * @return string
     */
    abstract protected function getSortStrategyKey(): string;

    /**
     * Return the expected order of sorted items
     * @return array
     */
    abstract protected function provideExpectedSortedItems(): array;

    public function testSort(): void
    {
        $this->login();

        // Populate the service catalog with test data
        $this->populateServiceCatalog();

        // Get the service catalog items
        $access_parameters = $this->getDefaultParametersForTestUser();
        $item_request = new ItemRequest(
            access_parameters: $access_parameters,
            items_per_page: 100,
            sort_strategy: 'alphabetical',
        );
        $items = self::$manager->getItems($item_request)['items'];

        // Sort the items using the strategy
        $sortedItems = SortStrategyFactory::create($this->getSortStrategyKey())
            ->sort($items);

        // Verify the sorting against the expected order
        $this->assertEquals(
            $this->provideExpectedSortedItems(),
            array_map(
                static fn($item) => $item->getServiceCatalogItemTitle(),
                $sortedItems
            )
        );
    }

    protected function populateServiceCatalog(): void
    {
        // Create service catalog categories
        $categories = $this->createItems(
            Category::class,
            [
                ['name' => 'A Category'],
                ['name' => 'B Category'],
                ['name' => 'C Category'],
                ['name' => 'Category with nested category'],
            ]
        );
        $categories[] = $this->createItem(
            Category::class,
            [
                'name' => 'Nested Category',
                'forms_categories_id' => $categories[3]->getID(),
            ]
        );

        // Create forms
        $form_builders = [
            (new FormBuilder())->setName('A Form'),
            (new FormBuilder())->setName('B Form'),
            (new FormBuilder())->setName('C Form'),
            (new FormBuilder())->setName('Pinned Form')->setIsPinned(true),
            (new FormBuilder())->setName('Form in A category')->setCategory($categories[0]->getID()),
            (new FormBuilder())->setName('Form in B category')->setCategory($categories[1]->getID()),
            (new FormBuilder())->setName('Form in C category')->setCategory($categories[2]->getID()),
            (new FormBuilder())->setName('Form in nested category')->setCategory($categories[4]->getID()),
            (new FormBuilder())->setName('Popular Form')->setUsageCount(100),
        ];
        foreach ($form_builders as $form_builder) {
            $form_builder->setIsActive(true);
            $this->createForm($form_builder);
        }

        // Create KnowbaseItems
        $this->createItems(
            KnowbaseItem::class,
            [
                ['name' => 'A KnowbaseItem', 'show_in_service_catalog' => true],
                ['name' => 'B KnowbaseItem', 'show_in_service_catalog' => true],
                ['name' => 'C KnowbaseItem', 'show_in_service_catalog' => true],
                ['name' => 'Popular KnowbaseItem', 'view' => 100, 'show_in_service_catalog' => true],
                ['name' => 'Pinned KnowbaseItem', 'is_pinned' => true, 'show_in_service_catalog' => true],
                [
                    'name' => 'KnowbaseItem with category',
                    'forms_categories_id' => $categories[1]->getID(),
                    'show_in_service_catalog' => true
                ],
            ]
        );
    }
}
