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

use CommonDBRelation;
use DbTestCase;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\ItemLinkException;

class CommonDBRelationTest extends DbTestCase
{
    public function testCreateCheck(): void
    {
        /** both specific, both attached */
        $instance = new class extends CommonDBRelation {
            public static $itemtype_1 = \Calendar::class;
            public static $items_id_1 = 'calendars_id';
            public static $mustBeAttached_1 = true;

            public static $itemtype_2 = \Holiday::class;
            public static $items_id_2 = 'holidays_id';
            public static $mustBeAttached_2 = true;
        };

        //nothing in input
        $exception_thrown = false;
        try {
            $input = [];
            $instance->check(-1, CREATE, $input);
        } catch (ItemLinkException $e) {
            $exception_thrown = true;
            $this->assertEquals(
                sprintf(
                    'Post data must contain (greater than 0): %1$s',
                    implode(', ', [$instance::$items_id_1, $instance::$items_id_2])
                ),
                $e->getMessage()
            );
        }
        $this->assertTrue($exception_thrown);
        $this->hasSessionMessages(
            ERROR,
            [
                sprintf(
                    'Mandatory fields are not filled. Please correct: %1$s',
                    implode(', ', [$instance::$itemtype_1::getTypeName(1), $instance::$itemtype_2::getTypeName(1)])
                ),
            ]
        );

        //zeroes in input
        $exception_thrown = false;
        try {
            $input = [$instance::$items_id_1 => 0, $instance::$items_id_2 => 0];
            $instance->check(-1, CREATE, $input);
        } catch (ItemLinkException $e) {
            $exception_thrown = true;
            $this->assertEquals(
                sprintf(
                    'Post data must contain (greater than 0): %1$s',
                    implode(', ', [$instance::$items_id_1, $instance::$items_id_2])
                ),
                $e->getMessage()
            );
        }
        $this->assertTrue($exception_thrown);
        $this->hasSessionMessages(
            ERROR,
            [
                sprintf(
                    'Mandatory fields are not filled. Please correct: %1$s',
                    implode(', ', [$instance::$itemtype_1::getTypeName(1), $instance::$itemtype_2::getTypeName(1)])
                ),
            ]
        );

        //only first in input
        $exception_thrown = false;
        try {
            $input = [$instance::$items_id_1 => 42];
            $instance->check(-1, CREATE, $input);
        } catch (ItemLinkException $e) {
            $exception_thrown = true;
            $this->assertEquals(
                sprintf(
                    'Post data must contain (greater than 0): %1$s',
                    implode(', ', [$instance::$items_id_2])
                ),
                $e->getMessage()
            );
        }
        $this->assertTrue($exception_thrown);
        $this->hasSessionMessages(
            ERROR,
            [
                sprintf(
                    'Mandatory fields are not filled. Please correct: %1$s',
                    implode(', ', [$instance::$itemtype_2::getTypeName(1)])
                ),
            ]
        );

        //only second in input
        $exception_thrown = false;
        try {
            $input = [$instance::$items_id_2 => 42];
            $instance->check(-1, CREATE, $input);
        } catch (ItemLinkException $e) {
            $exception_thrown = true;
            $this->assertEquals(
                sprintf(
                    'Post data must contain (greater than 0): %1$s',
                    implode(', ', [$instance::$items_id_1])
                ),
                $e->getMessage()
            );
        }
        $this->assertTrue($exception_thrown);
        $this->hasSessionMessages(
            ERROR,
            [
                sprintf(
                    'Mandatory fields are not filled. Please correct: %1$s',
                    implode(', ', [$instance::$itemtype_1::getTypeName(1)])
                ),
            ]
        );

        //null input (default) is OK
        try {
            $instance->check(-1, CREATE);
        } catch (\RuntimeException $e) {
            // CommonDBTM::getTable() will fail because we're using a fake object
            $this->assertStringContainsString('SHOW COLUMNS FROM `glpi_commondbrelation', $e->getMessage());
        }

        //both in input is OK
        $input = [\Calendar::getForeignKeyField() => 42, \Holiday::getForeignKeyField() => 42];
        try {
            $instance->check(-1, CREATE, $input);
        } catch (\RuntimeException $e) {
            // CommonDBTM::getTable() will fail because we're using a fake object
            $this->assertStringContainsString('SHOW COLUMNS FROM `glpi_commondbrelation', $e->getMessage());
        }

        //both in input is OK - try with a real object
        $input = [\Calendar::getForeignKeyField() => 42, \Holiday::getForeignKeyField() => 42];
        $instance = new \Calendar_Holiday();
        try {
            $instance->check(-1, CREATE, $input);
        } catch (AccessDeniedHttpException $e) {
            //this exception sounds not logical here; but this is not the point of current tests.
        }
        /** /both specific, both attached */

        /** both specific, first attached */
        $instance = new class extends CommonDBRelation {
            public static $itemtype_1 = \Calendar::class;
            public static $items_id_1 = 'calendars_id';
            public static $mustBeAttached_1 = true;

            public static $itemtype_2 = \Holiday::class;
            public static $items_id_2 = 'holidays_id';
            public static $mustBeAttached_2 = false;
        };

        $exception_thrown = false;
        try {
            $input = [];
            $instance->check(-1, CREATE, $input);
        } catch (ItemLinkException $e) {
            $exception_thrown = true;
            $this->assertEquals(
                sprintf(
                    'Post data must contain (greater than 0): %1$s',
                    implode(', ', [$instance::$items_id_1])
                ),
                $e->getMessage()
            );
        }
        $this->assertTrue($exception_thrown);
        $this->hasSessionMessages(
            ERROR,
            [
                sprintf(
                    'Mandatory fields are not filled. Please correct: %1$s',
                    implode(', ', [$instance::$itemtype_1::getTypeName(1)])
                ),
            ]
        );
        /** /both specific, first attached */

        /** both specific, second attached */
        $instance = new class extends CommonDBRelation {
            public static $itemtype_1 = \Calendar::class;
            public static $items_id_1 = 'calendars_id';
            public static $mustBeAttached_1 = false;

            public static $itemtype_2 = \Holiday::class;
            public static $items_id_2 = 'holidays_id';
            public static $mustBeAttached_2 = true;
        };

        $exception_thrown = false;
        try {
            $input = [];
            $instance->check(-1, CREATE, $input);
        } catch (ItemLinkException $e) {
            $exception_thrown = true;
            $this->assertEquals(
                sprintf(
                    'Post data must contain (greater than 0): %1$s',
                    implode(', ', [$instance::$items_id_2])
                ),
                $e->getMessage()
            );
        }
        $this->assertTrue($exception_thrown);
        $this->hasSessionMessages(
            ERROR,
            [
                sprintf(
                    'Mandatory fields are not filled. Please correct: %1$s',
                    implode(', ', [$instance::$itemtype_2::getTypeName(1)])
                ),
            ]
        );
        /** both specific, second attached */

        /** both specific, none attached */
        $instance = new class extends CommonDBRelation {
            public static $itemtype_1 = \Calendar::class;
            public static $items_id_1 = 'calendars_id';
            public static $mustBeAttached_1 = false;

            public static $itemtype_2 = \Holiday::class;
            public static $items_id_2 = 'holidays_id';
            public static $mustBeAttached_2 = false;
        };

        //nothing in input is OK
        $input = [\Calendar::getForeignKeyField() => 42, \Holiday::getForeignKeyField() => 42];
        try {
            $instance->check(-1, CREATE, $input);
        } catch (\RuntimeException $e) {
            // CommonDBTM::getTable() will fail because we're using a fake object
            $this->assertStringContainsString('SHOW COLUMNS FROM `glpi_commondbrelation', $e->getMessage());
        }

        //both in input is OK
        $input = [\Calendar::getForeignKeyField() => 42, \Holiday::getForeignKeyField() => 42];
        try {
            $instance->check(-1, CREATE, $input);
        } catch (\RuntimeException $e) {
            // CommonDBTM::getTable() will fail because we're using a fake object
            $this->assertStringContainsString('SHOW COLUMNS FROM `glpi_commondbrelation', $e->getMessage());
        }
        /** /both specific, none attached */

        /** first only specific, all attached */
        $instance = new class extends CommonDBRelation {
            public static $itemtype_1 = \Calendar::class;
            public static $items_id_1 = 'calendars_id';
            public static $mustBeAttached_1 = true;

            public static $itemtype_2 = 'itemtype';
            public static $items_id_2 = 'items_id';
            public static $mustBeAttached_2 = true;
        };

        $exception_thrown = false;
        try {
            $input = [];
            $instance->check(-1, CREATE, $input);
        } catch (ItemLinkException $e) {
            $exception_thrown = true;
            $this->assertEquals(
                sprintf(
                    'Post data must contain (greater than 0): %1$s',
                    implode(', ', [$instance::$items_id_1, $instance::$items_id_2])
                ),
                $e->getMessage()
            );
        }
        $this->assertTrue($exception_thrown);
        $this->hasSessionMessages(
            ERROR,
            [
                sprintf(
                    'Mandatory fields are not filled. Please correct: %1$s',
                    implode(', ', [$instance::$itemtype_1::getTypeName(1), 'itemtype'])
                ),
            ]
        );
        /** /first only specific, all attached */

        /** first only specific, second suffixed, all attached */
        $instance = new class extends CommonDBRelation {
            public static $itemtype_1 = \Calendar::class;
            public static $items_id_1 = 'calendars_id';
            public static $mustBeAttached_1 = true;

            public static $itemtype_2 = 'itemtype_peripheral';
            public static $items_id_2 = 'items_id';
            public static $mustBeAttached_2 = true;
        };

        $exception_thrown = false;
        try {
            $input = [];
            $instance->check(-1, CREATE, $input);
        } catch (ItemLinkException $e) {
            $exception_thrown = true;
            $this->assertEquals(
                sprintf(
                    'Post data must contain (greater than 0): %1$s',
                    implode(', ', [$instance::$items_id_1, $instance::$items_id_2])
                ),
                $e->getMessage()
            );
        }
        $this->assertTrue($exception_thrown);
        $this->hasSessionMessages(
            ERROR,
            [
                sprintf(
                    'Mandatory fields are not filled. Please correct: %1$s',
                    implode(', ', [$instance::$itemtype_1::getTypeName(1), 'itemtype_peripheral'])
                ),
            ]
        );
        /** /first only specific, second suffixed, all attached */
    }
}
