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

use CommonDBChild;
use Computer;
use DbTestCase;

class CommonDBChildTest extends DbTestCase
{
    public function testPrepareInputForAddWithMandatoryFkeyRelation(): void
    {
        $instance = new class extends CommonDBChild {
            public static $itemtype = Computer::class;
            public static $items_id = 'computers_id';
            public static $mustBeAttached = true;

            public static $disableAutoEntityForwarding = true; // prevent DB accesses
        };

        $this->assertFalse($instance->prepareInputForAdd(['foo' => 'bar']));
        $this->hasSessionMessages(ERROR, ['Parent item Computer #null is invalid.']);

        $this->assertFalse($instance->prepareInputForAdd(['computers_id' => 9999999999, 'foo' => 'bar']));
        $this->hasSessionMessages(ERROR, ['Parent item Computer #9999999999 is invalid.']);

        $valid_id = \getItemByTypeName(Computer::class, '_test_pc01', true);
        $this->assertEquals(
            ['computers_id' => $valid_id, 'foo' => 'bar'],
            $instance->prepareInputForAdd(['computers_id' => $valid_id, 'foo' => 'bar'])
        );
    }

    public function testPrepareInputForAddWithOptionalFkeyRelation(): void
    {
        $instance = new class extends CommonDBChild {
            public static $itemtype = Computer::class;
            public static $items_id = 'computers_id';
            public static $mustBeAttached = false;

            public static $disableAutoEntityForwarding = true; // prevent DB accesses
        };

        $this->assertEquals(
            ['foo' => 'bar'],
            $instance->prepareInputForAdd(['foo' => 'bar'])
        );

        $this->assertEquals(
            ['computers_id' => 0, 'foo' => 'bar'],
            $instance->prepareInputForAdd(['computers_id' => 9999999999, 'foo' => 'bar'])
        );

        $valid_id = \getItemByTypeName(Computer::class, '_test_pc01', true);
        $this->assertEquals(
            ['computers_id' => $valid_id, 'foo' => 'bar'],
            $instance->prepareInputForAdd(['computers_id' => $valid_id, 'foo' => 'bar'])
        );
    }
    public function testPrepareInputForAddWithMandatoryPolymorphicRelation(): void
    {
        $instance = new class extends CommonDBChild {
            public static $itemtype = 'itemtype';
            public static $items_id = 'items_id';
            public static $mustBeAttached = true;

            public static $disableAutoEntityForwarding = true; // prevent DB accesses
        };

        $this->assertFalse($instance->prepareInputForAdd(['foo' => 'bar']));
        $this->hasSessionMessages(ERROR, ['Parent item null #null is invalid.']);

        $this->assertFalse($instance->prepareInputForAdd(['itemtype' => 'Computer', 'foo' => 'bar']));
        $this->hasSessionMessages(ERROR, ['Parent item Computer #null is invalid.']);

        $this->assertFalse($instance->prepareInputForAdd(['itemtype' => 'NotAClass', 'foo' => 'bar']));
        $this->hasSessionMessages(ERROR, ['Parent item NotAClass #null is invalid.']);

        $this->assertFalse($instance->prepareInputForAdd(['itemtype' => 'Computer', 'items_id' => 9999999999,  'foo' => 'bar']));
        $this->hasSessionMessages(ERROR, ['Parent item Computer #9999999999 is invalid.']);

        $valid_id = \getItemByTypeName(Computer::class, '_test_pc01', true);
        $this->assertEquals(
            ['itemtype' => 'Computer', 'items_id' => $valid_id, 'foo' => 'bar'],
            $instance->prepareInputForAdd(['itemtype' => 'Computer', 'items_id' => $valid_id, 'foo' => 'bar'])
        );
    }

    public function testPrepareInputForAddWithOptionalPolymorphicRelation(): void
    {
        $instance = new class extends CommonDBChild {
            public static $itemtype = 'itemtype';
            public static $items_id = 'items_id';
            public static $mustBeAttached = false;

            public static $disableAutoEntityForwarding = true; // prevent DB accesses
        };

        $this->assertEquals(
            ['foo' => 'bar'],
            $instance->prepareInputForAdd(['foo' => 'bar'])
        );

        $this->assertEquals(
            ['items_id' => 0, 'foo' => 'bar'],
            $instance->prepareInputForAdd(['items_id' => 9999999999, 'foo' => 'bar'])
        );

        $this->assertEquals(
            ['itemtype' => '', 'foo' => 'bar'],
            $instance->prepareInputForAdd(['itemtype' => 'NotAClass', 'foo' => 'bar'])
        );

        $this->assertEquals(
            ['itemtype' => '', 'items_id' => 0, 'foo' => 'bar'],
            $instance->prepareInputForAdd(['itemtype' => 'Computer', 'items_id' => 9999999999,  'foo' => 'bar'])
        );

        $valid_id = \getItemByTypeName(Computer::class, '_test_pc01', true);
        $this->assertEquals(
            ['itemtype' => 'Computer', 'items_id' => $valid_id, 'foo' => 'bar'],
            $instance->prepareInputForAdd(['itemtype' => 'Computer', 'items_id' => $valid_id, 'foo' => 'bar'])
        );
    }
}
