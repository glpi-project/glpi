<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units;

use Glpi\Tests\DbTestCase;

class ITILCategoryTest extends DbTestCase
{
    public function testPrepareInputForAdd()
    {
        $this->login();

        $category = new \ITILCategory();
        $input = [
            'name' => '_test_itilcategory_1',
            'comment' => '_test_itilcategory_1',
        ];
        $expected = [
            'name' => '_test_itilcategory_1',
            'comment' => '_test_itilcategory_1',
            'itilcategories_id' => 0,
            'level' => 1,
            'completename' => '_test_itilcategory_1',
            'code' => '',
        ];
        $this->assertSame($expected, $category->prepareInputForAdd($input));

        $input = [
            'name' => '_test_itilcategory_2',
            'comment' => '_test_itilcategory_2',
            'code' => 'code2',
        ];
        $expected = [
            'name' => '_test_itilcategory_2',
            'comment' => '_test_itilcategory_2',
            'code' => 'code2',
            'itilcategories_id' => 0,
            'level' => 1,
            'completename' => '_test_itilcategory_2',
        ];
        $this->assertSame($expected, $category->prepareInputForAdd($input));

        $input = [
            'name' => '_test_itilcategory_3',
            'comment' => '_test_itilcategory_3',
            'code' => ' code 3 ',
        ];
        $expected = [
            'name' => '_test_itilcategory_3',
            'comment' => '_test_itilcategory_3',
            'code' => 'code 3',
            'itilcategories_id' => 0,
            'level' => 1,
            'completename' => '_test_itilcategory_3',
        ];
        $this->assertSame($expected, $category->prepareInputForAdd($input));
    }

    public function testPrepareInputForUpdate()
    {
        $this->login();

        $category = new \ITILCategory();

        $category_id = (int) $category->add([
            'name' => '_test_itilcategory_1',
            'comment' => '_test_itilcategory_1',
        ]);
        $this->assertGreaterThan(0, $category_id);

        $input = [
            'id' => $category_id,
            'code' => ' code 1 ',
        ];

        $this->assertTrue($category->update($input));
        $this->assertTrue($category->getFromDB($category_id));

        $this->assertSame('_test_itilcategory_1', $category->fields['name']);
        $this->assertSame('_test_itilcategory_1', $category->fields['comment']);
        $this->assertSame('code 1', $category->fields['code']);

        $input = [
            'id' => $category_id,
            'comment' => 'new comment',
        ];

        $this->assertTrue($category->update($input));
        $this->assertTrue($category->getFromDB($category_id));

        $this->assertSame('_test_itilcategory_1', $category->fields['name']);
        $this->assertSame('new comment', $category->fields['comment']);
        $this->assertSame('code 1', $category->fields['code']);

        $input = [
            'id' => $category_id,
            'code' => '',
        ];

        $this->assertTrue($category->update($input));
        $this->assertTrue($category->getFromDB($category_id));

        $this->assertSame('_test_itilcategory_1', $category->fields['name']);
        $this->assertSame('new comment', $category->fields['comment']);
        $this->assertSame('', $category->fields['code']);
    }

    /**
     * Test that recursive ITIL categories cannot be edited from child entities
     *
     * When a user is in a child entity, they should:
     * - Be able to READ recursive objects from parent entities
     * - NOT be able to UPDATE/DELETE recursive objects from parent entities
     */
    public function testRecursiveITILCategoryRights(): void
    {
        $this->login();

        $root_entity_id = getItemByTypeName('Entity', '_test_root_entity', true);
        $child_entity_id = getItemByTypeName('Entity', '_test_child_1', true);

        $category = new \ITILCategory();

        // Create a recursive ITIL category in root entity
        $category_id = $category->add([
            'name'         => 'Recursive Category Test',
            'entities_id'  => $root_entity_id,
            'is_recursive' => 1,
        ]);
        $this->assertGreaterThan(0, $category_id);

        // Create a non-recursive ITIL category in root entity
        $non_recursive_category_id = $category->add([
            'name'         => 'Non-Recursive Category Test',
            'entities_id'  => $root_entity_id,
            'is_recursive' => 0,
        ]);
        $this->assertGreaterThan(0, $non_recursive_category_id);

        // Create a category in child entity
        $child_category_id = $category->add([
            'name'         => 'Child Category Test',
            'entities_id'  => $child_entity_id,
            'is_recursive' => 0,
        ]);
        $this->assertGreaterThan(0, $child_category_id);

        // Switch to child entity only
        $this->assertTrue(\Session::changeActiveEntities($child_entity_id));

        // Verify READ rights: recursive objects from parent should be visible
        $this->assertTrue($category->can($category_id, READ), "Should be able to read recursive category from parent entity");

        // Verify that non-recursive objects from parent are NOT visible
        $this->assertFalse($category->can($non_recursive_category_id, READ), "Should NOT be able to read non-recursive category from parent entity");

        // Verify UPDATE rights: recursive objects from parent should NOT be editable
        $this->assertTrue($category->getFromDB($category_id));
        $this->assertFalse($category->canUpdateItem(), "Should NOT be able to update recursive category from parent entity when in child entity");

        // Verify DELETE rights: recursive objects from parent should NOT be deletable
        $this->assertFalse($category->canDeleteItem(), "Should NOT be able to delete recursive category from parent entity when in child entity");

        // Verify PURGE rights: recursive objects from parent should NOT be purgeable
        $this->assertFalse($category->canPurgeItem(), "Should NOT be able to purge recursive category from parent entity when in child entity");

        // Verify that local category CAN be edited
        $this->assertTrue($category->getFromDB($child_category_id));
        $this->assertTrue($category->canUpdateItem(), "Should be able to update local category");
        $this->assertTrue($category->canDeleteItem(), "Should be able to delete local category");
        $this->assertTrue($category->canPurgeItem(), "Should be able to purge local category");

        // Switch back to root entity
        $this->assertTrue(\Session::changeActiveEntities($root_entity_id));

        // Verify that recursive category CAN be edited from root entity
        $this->assertTrue($category->getFromDB($category_id));
        $this->assertTrue($category->canUpdateItem(), "Should be able to update recursive category from its own entity");
        $this->assertTrue($category->canDeleteItem(), "Should be able to delete recursive category from its own entity");
        $this->assertTrue($category->canPurgeItem(), "Should be able to purge recursive category from its own entity");
    }
}
