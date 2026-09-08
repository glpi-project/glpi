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

namespace tests\units\Glpi\Error;

use Glpi\Error\InputTruncationReason;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InputTruncationReasonTest extends TestCase
{
    public static function phpErrorMessageProvider(): iterable
    {
        // Messages below are the actual ones produced by PHP 8.3/8.4/8.5.
        yield 'max_input_vars' => [
            'message' => 'PHP Request Startup: Input variables exceeded 1000. To increase the limit change max_input_vars in php.ini.',
            'expected' => InputTruncationReason::MAX_INPUT_VARS,
        ];
        yield 'post_max_size' => [
            'message' => 'PHP Request Startup: POST Content-Length of 2503 bytes exceeds the limit of 1024 bytes',
            'expected' => InputTruncationReason::POST_MAX_SIZE,
        ];
        yield 'max_file_uploads' => [
            'message' => 'PHP Request Startup: Maximum number of allowable file uploads has been exceeded',
            'expected' => InputTruncationReason::MAX_FILE_UPLOADS,
        ];
        yield 'unrelated startup error' => [
            'message' => 'PHP Startup: Unable to load dynamic library \'foo.so\'',
            'expected' => null,
        ];
        yield 'empty message' => [
            'message' => '',
            'expected' => null,
        ];
    }

    #[DataProvider('phpErrorMessageProvider')]
    public function testFromPhpErrorMessage(string $message, ?InputTruncationReason $expected): void
    {
        $this->assertSame($expected, InputTruncationReason::fromPhpErrorMessage($message));
    }

    public function testStatusCodes(): void
    {
        $this->assertSame(400, InputTruncationReason::MAX_INPUT_VARS->getStatusCode());
        $this->assertSame(413, InputTruncationReason::POST_MAX_SIZE->getStatusCode());
        $this->assertSame(400, InputTruncationReason::MAX_FILE_UPLOADS->getStatusCode());
    }

    public function testMessageToDisplayContainsDirectiveAndItsValue(): void
    {
        foreach (InputTruncationReason::cases() as $reason) {
            $message = $reason->getMessageToDisplay();

            $this->assertStringContainsString($reason->getIniDirective(), $message);
            $this->assertNotSame('', $reason->getIniValue());
            $this->assertStringContainsString($reason->getIniValue(), $message);
        }
    }
}
