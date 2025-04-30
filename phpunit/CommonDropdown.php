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

namespace tests\units;

use DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class CommonDropdown extends DbTestCase
{
    /**
     * Get object class name
     *
     */
    abstract protected function getObjectClass();

    abstract public static function typenameProvider();
    #[DataProvider('typenameProvider')]
    public function testGetTypeName($string, $expected)
    {
        $this->assertSame($expected, $string);
    }

    public function testMaybeTranslated()
    {
        $instance = $this->newInstance();
        $this->assertFalse($instance->maybeTranslated());
    }

    public function testGetMenuContent()
    {
        $class = $this->getObjectClass();
        $this->assertFalse($class::getMenuContent());
    }

    public function testGetAdditionalFields()
    {
        $instance = $this->newInstance();
        $this->assertSame([], $instance->getAdditionalFields());
    }

    abstract protected function getTabs();

    public function testDefineTabs()
    {
        $instance = $this->newInstance();
        $tabs = array_map('strip_tags', $instance->defineTabs());
        $this->assertSame($this->getTabs(), $tabs);
    }

    public function testPre_deleteItem()
    {
        $instance = $this->newInstance();
        $this->assertTrue($instance->pre_deleteItem());
    }

    public function testPrepareInputForAdd()
    {
        $instance = $this->newInstance();

        $input = [];
        $this->assertSame($input, $instance->prepareInputForAdd($input));

        $input = ['name' => 'Any name', 'comment' => 'Any comment'];
        $this->assertSame($input, $instance->prepareInputForAdd($input));

        $loc = getItemByTypeName('Location', '_location01');
        $input['locations_id'] = $loc->getID();
        $this->assertSame(
            $input + ['entities_id' => $loc->fields['entities_id']],
            $instance->prepareInputForAdd($input)
        );
    }

    public function testPrepareInputForUpdate()
    {
        $instance = $this->newInstance();

        $input = [];
        $this->assertSame($input, $instance->prepareInputForUpdate($input));

        $input = ['name' => 'Any name', 'comment' => 'Any comment'];
        $this->assertSame($input, $instance->prepareInputForUpdate($input));

        $loc = getItemByTypeName('Location', '_location01');
        $input['locations_id'] = $loc->getID();
        //to make sure existing entities_id will not be changed on update
        $input['entities_id'] = $loc->fields['entities_id'] + 1;
        $this->assertSame(
            $input + ['entities_id' => $loc->fields['entities_id']],
            $instance->prepareInputForUpdate($input)
        );
    }

    /**
     * Create new object in database
     *
     * @return \CommonDBTM
     */
    abstract protected function newInstance(): \CommonDBTM;

    public function testGetDropdownName()
    {
        $instance = $this->newInstance();
        $ret = \Dropdown::getDropdownName($instance::getTable(), $instance->getID());
        $this->assertSame($instance->getName(), $ret);
    }

    public function testAddUpdate()
    {
        $instance = $this->newInstance();

        $this->assertGreaterThan(
            0,
            $instance->add([])
        );
        $this->assertTrue($instance->getFromDB($instance->getID()));

        $keys = ['name', 'comment', 'date_mod', 'date_creation'];
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $instance->fields);
        }

        $this->assertNotEquals('', $instance->fields['date_mod']);
        $this->assertNotEquals('', $instance->fields['date_creation']);

        $this->assertGreaterThan(
            0,
            $instance->add(['name' => 'Tested name'])
        );
        $this->assertTrue(
            $instance->getFromDB($instance->getID())
        );

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $instance->fields);
        }
        $this->assertSame('Tested name', $instance->fields['name']);
        $this->assertNotEquals('', $instance->fields['date_mod']);
        $this->assertNotEquals('', $instance->fields['date_creation']);

        $this->assertGreaterThan(
            0,
            $instance->add([
                'name'      => 'Another name',
                'comment'   => 'A comment on an object',
            ])
        );
        $this->assertTrue(
            $instance->getFromDB($instance->getID())
        );

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $instance->fields);
        }
        $this->assertSame('Another name', $instance->fields['name']);
        $this->assertSame('A comment on an object', $instance->fields['comment']);
        $this->assertNotEquals('', $instance->fields['date_mod']);
        $this->assertNotEquals('', $instance->fields['date_creation']);

        $this->assertTrue(
            $instance->update([
                'id'     => $instance->getID(),
                'name'   => 'Changed name',
            ])
        );
        $this->assertTrue(
            $instance->getFromDB($instance->getID())
        );
        $this->assertSame('Changed name', $instance->fields['name']);

        //cannot update if id is missing
        $this->assertFalse(
            $instance->update(['name' => 'Will not change'])
        );
    }
}
