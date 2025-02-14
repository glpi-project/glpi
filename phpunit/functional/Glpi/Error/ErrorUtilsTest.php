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
    #[DataProvider('safeContentsProvider')]
    public function testcleanPathsOnSafeContent($input)
    {
        assert(is_string(\GLPI_ROOT) && strlen(\GLPI_ROOT) > 0);
        $this->assertEquals($input, ErrorUtils::cleanPaths($input));
    }

    #[DataProvider('unsafeContentsProvider')]
    public function testcleanPathsOnUnsafeContentsRemovesGLPI_ROOT($input, $expected)
    {
        assert(is_string(\GLPI_ROOT) && strlen(\GLPI_ROOT) > 0);

        $this->assertSame($expected, ErrorUtils::cleanPaths($input));
    }

    public static function unsafeContentsProvider(): array
    {
        return [
            ['input' => \GLPI_ROOT . '/files/ is not writtable.', 'expected' => './files/ is not writtable.'],
            ['input' => 'Base dir is writtable, fix rights in ' . \GLPI_ROOT, 'expected' => 'Base dir is writtable, fix rights in .'],
            ['input' => \GLPI_ROOT, 'expected' => '.'],
            ['input' => 'error in ' . \GLPI_ROOT . '/path : content not readable', 'expected' => 'error in ./path : content not readable'],
        ];
    }

    public static function safeContentsProvider(): array
    {
        return [
            [ '/tmp/files/ is not writtable.'],
            [ 'Base dir is writtable, fix rights in assets/images/'],
            [ '/not/in/root/'],
            [ 'file /path/file.php not found'],
        ];
    }
}
