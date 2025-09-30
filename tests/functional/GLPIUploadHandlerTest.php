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
use GLPIUploadHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;

class GLPIUploadHandlerTest extends DbTestCase
{
    public static function filenameValidateProvider(): iterable
    {
        yield [
            'file_object' => (object) [
                'name' => '62cc4e0add3970.68031699logo.png',
                'size' => 165497,
                'type' => 'image/png',
            ],
            'error'       => null,
        ];

        yield [
            'file_object' => (object) [
                'name' => '62cc4e0add3970.68031699file.with.php.inside.jpg',
                'size' => 4654,
                'type' => 'image/jpeg',
            ],
            'error'       => null,
        ];

        yield [
            'file_object' => (object) [
                'name' => '62cc4e0add3970.68031699script.php',
                'size' => 458,
                'type' => 'application/php',
            ],
            'error'       => 'The file upload has been refused for security reasons.',
        ];

        yield [
            'file_object' => (object) [
                'name' => '62ccaaaadd3970.68035679exec.php5',
                'size' => 465465,
                'type' => 'application/php',
            ],
            'error'       => 'The file upload has been refused for security reasons.',
        ];

        yield [
            'file_object' => (object) [
                'name' => '62ccaaaadd3970.68035679archive.phar',
                'size' => 7987984,
                'type' => 'application/phar',
            ],
            'error'       => 'The file upload has been refused for security reasons.',
        ];
    }

    #[DataProvider('filenameValidateProvider')]
    public function testValidateFilename(\stdClass $file_object, ?string $error): void
    {
        // Mock the upload handler
        $instance = $this->getMockBuilder(GLPIUploadHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validate_image_file'])
            ->getMock();
        $instance->method('validate_image_file')->willReturn(true);

        $reflection = new ReflectionClass($instance);

        $options_property = $reflection->getProperty('options');
        $options_property->setValue(
            $instance,
            [
                'accept_file_types'   => '/.*/',
                'max_file_size'       => PHP_INT_MAX,
                'min_file_size'       => 0,
                'max_number_of_files' => PHP_INT_MAX,
                'upload_dir'          => GLPI_TMP_DIR . '/',
                'user_dirs'           => false,
            ]
        );

        // Check the result
        $success = $this->callPrivateMethod(
            $instance,
            'validate',
            '/tmp/uploaded-file', // $uploaded_file
            $file_object,         // $file
            0,                    // $error
            0,                    // $index
            null,                 // $content_range
        );

        if ($error !== null) {
            $this->assertEquals($error, $file_object->error);
        }
        $this->assertEquals($error === null, $success);
    }
}
