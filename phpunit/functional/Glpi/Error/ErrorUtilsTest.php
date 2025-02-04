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

namespace Glpi\Error;

use PHPUnit\Framework\Attributes\DataProvider;

class ErrorUtilsTest extends \DbTestCase
{
    public function testcleanPathsOnSafeContent()
    {
        // Arrange
        assert(is_string(\GLPI_ROOT) && strlen(\GLPI_ROOT) > 0);
        $safeMessage = 'a string without GLPI_ROOT';

        // Act & Assert
        // - string without GLPI_ROOT should not be changed
        $this->assertEquals($safeMessage, ErrorUtils::cleanPaths($safeMessage));
    }

    public function testcleanPathsOnUnsafeContentsKeepOtherContents()
    {
        // Arrange
        assert(is_string(\GLPI_ROOT) && strlen(\GLPI_ROOT) > 0);

        $data = 'a string with ' . \GLPI_ROOT . ' and other content';
        // Act & Assert
        // - string without GLPI_ROOT should not be changed
        $this->assertStringContainsString('other content', ErrorUtils::cleanPaths($data));
    }

    #[DataProvider('unsafeContentsProvider')]
    public function testcleanPathsOnUnsafeContentsRemovesGLPI_ROOT($data)
    {
        // Arrange
        assert(is_string(\GLPI_ROOT) && strlen(\GLPI_ROOT) > 0);

        // Act & Assert
        // - string without GLPI_ROOT should not be changed
        $this->assertStringNotContainsString(\GLPI_ROOT, ErrorUtils::cleanPaths($data));
    }

    public static function unsafeContentsProvider(): array
    {
        return [
            [\GLPI_ROOT . 'bla bla'],
            ['bla bla' . \GLPI_ROOT],
            [\GLPI_ROOT],
            ['/path/' . \GLPI_ROOT . '/path'],
        ];
    }
}
