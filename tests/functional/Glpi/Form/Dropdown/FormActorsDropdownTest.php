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

namespace tests\units\Glpi\Form\Dropdown;

use DbTestCase;
use Glpi\Form\Dropdown\FormActorsDropdown;
use Glpi\Tests\FormTesterTrait;
use Group;
use PHPUnit\Framework\Attributes\DataProvider;
use Supplier;
use User;

final class FormActorsDropdownTest extends DbTestCase
{
    use FormTesterTrait;

    public static function fetchValuesProvider(): iterable
    {
        $default_dataset = [
            // Default users: _test_user, glpi, normal, post-only, tech, Smith John
            'users' => [],
            // Default groups: _test_group_1, _test_group_2
            'groups' => [],
            // Default suppliers: _suplier01_name, _suplier02_name
            'suppliers' => [],
        ];

        yield 'No parameters' => [
            'dataset' => $default_dataset,
            'search' => '',
            'options' => [],
            'expected' => [
                "_test_user",
                "glpi",
                "normal",
                "post-only",
                "tech",
                "Smith John",
                "_test_group_1",
                "_test_group_2",
                "_suplier01_name",
                "_suplier02_name",
            ],
        ];
        yield 'With "Smith" filter' => [
            'dataset' => $default_dataset,
            'search' => 'Smith',
            'options' => [],
            'expected' => [
                "Smith John",
            ],
        ];
        yield 'With "1" filter' => [
            'dataset' => $default_dataset,
            'search' => '1',
            'options' => [],
            'expected' => [
                "Smith John", // Login is jsmith123
                "_test_group_1",
                "_suplier01_name",
            ],
        ];
        yield 'Only users' => [
            'dataset' => $default_dataset,
            'search' => '',
            'options' => ['allowed_types' => [User::class]],
            'expected' => [
                "_test_user",
                "glpi",
                "normal",
                "post-only",
                "tech",
                "Smith John",
            ],
        ];
        yield 'Only groups' => [
            'dataset' => $default_dataset,
            'search' => '',
            'options' => ['allowed_types' => [Group::class]],
            'expected' => [
                "_test_group_1",
                "_test_group_2",
            ],
        ];
        yield 'Only suppliers' => [
            'dataset' => $default_dataset,
            'search' => '',
            'options' => ['allowed_types' => [Supplier::class]],
            'expected' => [
                "_suplier01_name",
                "_suplier02_name",
            ],
        ];
        yield 'User + Groups' => [
            'dataset' => $default_dataset,
            'search' => '',
            'options' => ['allowed_types' => [User::class, Group::class]],
            'expected' => [
                "_test_user",
                "glpi",
                "normal",
                "post-only",
                "tech",
                "Smith John",
                "_test_group_1",
                "_test_group_2",
            ],
        ];
        yield 'User + Supplier' => [
            'dataset' => $default_dataset,
            'search' => '',
            'options' => ['allowed_types' => [User::class, Supplier::class]],
            'expected' => [
                "_test_user",
                "glpi",
                "normal",
                "post-only",
                "tech",
                "Smith John",
                "_suplier01_name",
                "_suplier02_name",
            ],
        ];
        yield 'Groups + Supplier' => [
            'dataset' => $default_dataset,
            'search' => '',
            'options' => ['allowed_types' => [Group::class, Supplier::class]],
            'expected' => [
                "_test_group_1",
                "_test_group_2",
                "_suplier01_name",
                "_suplier02_name",
            ],
        ];

        // Create a lot of items to make sure we can trigger pagination
        $big_dataset = $default_dataset;
        for ($i = 0; $i < 10; $i++) {
            $big_dataset['users'][]     = ['name' => "User $i - test pagination"];
            $big_dataset['groups'][]    = ['name' => "Group $i - test pagination"];
            $big_dataset['suppliers'][] = ['name' => "Supplier $i - test pagination"];
        }

        yield 'Page 1' => [
            'dataset' => $big_dataset,
            'search' => 'test pagination',
            'options' => [
                'page' => 1,
                'page_size' => 2,
            ],
            'expected' => [
                "User 0 - test pagination",
                "User 1 - test pagination",
                "Group 0 - test pagination",
                "Group 1 - test pagination",
                "Supplier 0 - test pagination",
                "Supplier 1 - test pagination",
            ],
        ];
        yield 'Page 2' => [
            'dataset' => $big_dataset,
            'search' => 'test pagination',
            'options' => [
                'page' => 2,
                'page_size' => 2,
            ],
            'expected' => [
                "User 2 - test pagination",
                "User 3 - test pagination",
                "Group 2 - test pagination",
                "Group 3 - test pagination",
                "Supplier 2 - test pagination",
                "Supplier 3 - test pagination",
            ],
        ];
    }

    #[DataProvider('fetchValuesProvider')]
    public function testFetchValuesWithDefaultDataset(
        array $dataset,
        string $search,
        array $options,
        array $expected,
    ): void {
        // Arrange: create a set of users
        $this->login();
        $entities_id = $this->getTestRootEntity(only_id: true);
        foreach ($dataset['users'] as $user) {
            $user['_entities_id'] = $entities_id;
            $this->createItem(User::class, $user);
        }
        foreach ($dataset['groups'] as $group) {
            $group['entities_id'] = $entities_id;
            $this->createItem(Group::class, $group);
        }
        foreach ($dataset['suppliers'] as $supplier) {
            $supplier['entities_id'] = $entities_id;
            $this->createItem(Supplier::class, $supplier);
        }

        // Act: fetch dropdown values
        $values = FormActorsDropdown::fetchValues($search, $options);
        $text_values = $this->extractTextFromOutput($values);

        // Assert: compare the values with the expectations
        $this->assertEquals($expected, $text_values);
        $this->assertEquals(count($expected), $values['count']);
    }

    private function extractTextFromOutput(array $dropdown_output): array
    {
        // Helper method to compare expected results more easily
        $text_values = [];
        foreach ($dropdown_output['results'] as $results) {
            foreach ($results['children'] as $item) {
                $text_values[] = $item['text'];
            }
        }
        return $text_values;
    }
}
