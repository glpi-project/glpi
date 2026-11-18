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

namespace tests\units\Glpi\Form\Export\Context;

use Computer;
use Glpi\Form\Comment;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Question;
use Glpi\Form\Section;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class DatabaseMapperTest extends GLPITestCase
{
    public function testTryGetItemIdReturnsMappedId(): void
    {
        // Arrange: map an item using its id and its uuid
        $mapper = new DatabaseMapper([0]);
        $mapper->addMappedItem(Question::class, 12, 34);
        $mapper->addMappedItem(Question::class, 'some-uuid', 34);

        // Act: get the mapped ids
        $id_by_id = $mapper->tryGetItemId(Question::class, 12);
        $id_by_uuid = $mapper->tryGetItemId(Question::class, 'some-uuid');

        // Assert: both keys are mapped to the same id
        $this->assertSame(34, $id_by_id);
        $this->assertSame(34, $id_by_uuid);
    }

    public function testTryGetItemIdReturnsNullForUnknownKey(): void
    {
        // Arrange: map an item of another type
        $mapper = new DatabaseMapper([0]);
        $mapper->addMappedItem(Computer::class, 'My computer', 5);

        // Act: get an unknown key of a known type and a key of an unknown type
        $unknown_key = $mapper->tryGetItemId(Computer::class, 'Other computer');
        $unknown_type = $mapper->tryGetItemId(Question::class, 'My computer');

        // Assert: no id is found
        $this->assertNull($unknown_key);
        $this->assertNull($unknown_type);
    }

    public static function formItemsProvider(): iterable
    {
        yield 'Question' => [Question::class];
        yield 'Section'  => [Section::class];
        yield 'Comment'  => [Comment::class];
    }

    #[DataProvider('formItemsProvider')]
    public function testTryGetItemIdReturnsNullForUnknownFormItem(string $itemtype): void
    {
        // Arrange: an empty mapper
        $mapper = new DatabaseMapper([0]);

        // Act: get an unknown form item
        $id = $mapper->tryGetItemId($itemtype, 42);

        // Assert: no id is found
        $this->assertNull($id);
    }
}
