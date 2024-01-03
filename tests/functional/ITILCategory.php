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

class ITILCategory extends DbTestCase
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
        $this->array($category->prepareInputForAdd($input))->isIdenticalTo($expected);

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
        $this->array($category->prepareInputForAdd($input))->isIdenticalTo($expected);

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
        $this->array($category->prepareInputForAdd($input))->isIdenticalTo($expected);
    }

    public function testPrepareInputForUpdate()
    {
        $this->login();

        $category = new \ITILCategory();

        $category_id = (int)$category->add([
            'name' => '_test_itilcategory_1',
            'comment' => '_test_itilcategory_1',
        ]);
        $this->integer($category_id)->isGreaterThan(0);

        $input = [
            'id' => $category_id,
            'code' => ' code 1 ',
        ];

        $this->boolean($category->update($input))->isTrue();
        $this->boolean($category->getFromDB($category_id))->isTrue();

        $this->string($category->fields['name'])->isIdenticalTo('_test_itilcategory_1');
        $this->string($category->fields['comment'])->isIdenticalTo('_test_itilcategory_1');
        $this->string($category->fields['code'])->isIdenticalTo('code 1');

        $input = [
            'id' => $category_id,
            'comment' => 'new comment',
        ];

        $this->boolean($category->update($input))->isTrue();
        $this->boolean($category->getFromDB($category_id))->isTrue();

        $this->string($category->fields['name'])->isIdenticalTo('_test_itilcategory_1');
        $this->string($category->fields['comment'])->isIdenticalTo('new comment');
        $this->string($category->fields['code'])->isIdenticalTo('code 1');

        $input = [
            'id' => $category_id,
            'code' => '',
        ];

        $this->boolean($category->update($input))->isTrue();
        $this->boolean($category->getFromDB($category_id))->isTrue();

        $this->string($category->fields['name'])->isIdenticalTo('_test_itilcategory_1');
        $this->string($category->fields['comment'])->isIdenticalTo('new comment');
        $this->string($category->fields['code'])->isIdenticalTo('');
    }
}
