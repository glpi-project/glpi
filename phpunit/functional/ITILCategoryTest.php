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

namespace tests\units;

use DbTestCase;

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

        $category_id = (int)$category->add([
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
}
