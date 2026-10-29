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

use Computer;
use Entity;
use FieldUnicity;
use Glpi\Tests\DbTestCase;
use Location;
use Manufacturer;
use PHPUnit\Framework\Attributes\DataProvider;
use State;

class FieldUnicityTest extends DbTestCase
{
    public static function inputProvider(): iterable
    {
        yield [
            'input'  => [
                'itemtype' => Computer::class,
            ],
            'result' => false,
            'errors' => ['It&#039;s mandatory to select a type and at least one field'],
        ];

        yield [
            'input'  => [
                'fields'   => '',
            ],
            'result' => false,
            'errors' => ['It&#039;s mandatory to select a type and at least one field'],
        ];

        yield [
            'input'  => [
                '_fields'  => [],
            ],
            'result' => false,
            'errors' => ['It&#039;s mandatory to select a type and at least one field'],
        ];

        yield [
            'input'  => [
                'itemtype' => Computer::class,
                'fields'   => '',
            ],
            'result' => false,
            'errors' => ['It&#039;s mandatory to select a type and at least one field'],
        ];

        yield [
            'input'  => [
                'itemtype' => Computer::class,
                'fields'   => 'name',
            ],
            'result' => [
                'itemtype' => Computer::class,
                'fields'   => 'name',
            ],
            'errors' => [],
        ];

        yield [
            'input'  => [
                'itemtype' => Computer::class,
                '_fields'  => ['name', 'serial'],
            ],
            'result' => [
                'itemtype' => Computer::class,
                'fields'   => 'name,serial',
            ],
            'errors' => [],
        ];
    }

    public static function addInputProvider(): iterable
    {
        yield [
            'input'  => [],
            'result' => false,
            'errors' => ['It&#039;s mandatory to select a type and at least one field'],
        ];

        yield from self::inputProvider();
    }

    #[DataProvider('addInputProvider')]
    public function testPrepareInputForAdd(
        array $input,
        mixed $result,
        array $errors
    ): void {
        $this->login();

        $fieldunicity = new FieldUnicity();
        $this->assertEquals($result, $fieldunicity->prepareInputForAdd($input));

        if (count($errors) > 0) {
            $this->hasSessionMessages(ERROR, $errors);
        }
    }

    public static function updateInputProvider(): iterable
    {
        yield from self::inputProvider();
    }

    #[DataProvider('updateInputProvider')]
    public function testPrepareInputForUpdate(
        array $input,
        mixed $result,
        array $errors
    ): void {
        $this->login();

        $fieldunicity = new FieldUnicity();
        $this->assertEquals($result, $fieldunicity->prepareInputForUpdate($input));

        if (count($errors) > 0) {
            $this->hasSessionMessages(ERROR, $errors);
        }
    }

    public function testDropdownFieldsIncludesForeignKeys(): void
    {
        $this->login();

        $html = FieldUnicity::dropdownFields(Computer::class, ['display' => false]);
        $this->assertStringContainsString("value='serial'", $html);
        $this->assertStringContainsString("value='manufacturers_id'", $html);
        $this->assertStringContainsString("value='computermodels_id'", $html);
        $this->assertStringContainsString("value='users_id_tech'", $html);
        $this->assertStringNotContainsString("value='entities_id'", $html);
    }

    public function testGetSpecificValueToDisplayWithForeignKeys(): void
    {
        $this->login();

        $this->assertEquals(
            'Serial number, Manufacturer, Model, Technician in charge',
            FieldUnicity::getSpecificValueToDisplay('fields', [
                'itemtype' => Computer::class,
                'fields'   => 'serial,manufacturers_id,computermodels_id,users_id_tech',
            ])
        );
    }

    public static function foreignKeyUnicityProvider(): iterable
    {
        yield [Manufacturer::class, 'manufacturers_id', 'Manufacturer'];
        // Location and State search options use 'completename' instead of 'name'
        yield [Location::class, 'locations_id', 'Location'];
        yield [State::class, 'states_id', 'Status'];
    }

    #[DataProvider('foreignKeyUnicityProvider')]
    public function testCheckUnicityWithForeignKey(string $dropdown_class, string $field, string $label): void
    {
        $this->login();

        $entities_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $this->createItem(FieldUnicity::class, [
            'name'          => 'serial and dropdown uniqueness',
            'itemtype'      => Computer::class,
            '_fields'       => ['serial', $field],
            'is_active'     => 1,
            'action_refuse' => 1,
            'entities_id'   => $entities_id,
        ]);

        $dropdown_1 = $this->createItem($dropdown_class, ['name' => __FUNCTION__ . '_1']);
        $dropdown_2 = $this->createItem($dropdown_class, ['name' => __FUNCTION__ . '_2']);

        $this->createItem(Computer::class, [
            'name'        => __FUNCTION__ . '_1',
            'entities_id' => $entities_id,
            'serial'      => 'SN-123',
            $field        => $dropdown_1->getID(),
        ]);

        // Same serial number with another dropdown value is allowed
        $this->createItem(Computer::class, [
            'name'        => __FUNCTION__ . '_2',
            'entities_id' => $entities_id,
            'serial'      => 'SN-123',
            $field        => $dropdown_2->getID(),
        ]);

        $computer = new Computer();
        $this->assertFalse($computer->add([
            'name'        => __FUNCTION__ . '_3',
            'entities_id' => $entities_id,
            'serial'      => 'SN-123',
            $field        => $dropdown_1->getID(),
        ]));
        $messages = $_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR] ?? [];
        $this->assertCount(1, $messages);
        $this->assertStringContainsString(
            'Impossible record for Serial number = SN-123 &amp; ' . $label . ' = ' . __FUNCTION__ . '_1<br>',
            reset($messages)
        );
        unset($_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR]);
    }
}
