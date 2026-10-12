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
use Glpi\Error\StartupErrors;
use PHPUnit\Framework\TestCase;

final class StartupErrorsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        StartupErrors::reset();
    }

    protected function tearDown(): void
    {
        StartupErrors::reset();
        parent::tearDown();
    }

    public function testNoErrorCapturedByDefault(): void
    {
        $this->assertNull(StartupErrors::get());
        $this->assertNull(StartupErrors::getTruncationReason());
    }

    public function testCaptureStoresTheGivenError(): void
    {
        $error = $this->makeError('PHP Request Startup: Input variables exceeded 1000. To increase the limit change max_input_vars in php.ini.');

        StartupErrors::capture($error);

        $this->assertSame($error, StartupErrors::get());
        $this->assertSame(InputTruncationReason::MAX_INPUT_VARS, StartupErrors::getTruncationReason());
    }

    public function testCaptureIsIdempotent(): void
    {
        $first = $this->makeError('PHP Request Startup: POST Content-Length of 2503 bytes exceeds the limit of 1024 bytes');

        StartupErrors::capture($first);
        // Any later error is triggered by the GLPI code itself and must not be captured.
        StartupErrors::capture($this->makeError('Something that happened later'));

        $this->assertSame($first, StartupErrors::get());
        $this->assertSame(InputTruncationReason::POST_MAX_SIZE, StartupErrors::getTruncationReason());
    }

    public function testCaptureOfANullErrorPreventsAnyLaterCapture(): void
    {
        StartupErrors::capture(null);
        StartupErrors::capture($this->makeError('PHP Request Startup: Input variables exceeded 1000.'));

        $this->assertNull(StartupErrors::get());
    }

    public function testUnrelatedStartupErrorHasNoTruncationReason(): void
    {
        StartupErrors::capture($this->makeError('PHP Startup: Unable to load dynamic library \'foo.so\''));

        $this->assertNotNull(StartupErrors::get());
        $this->assertNull(StartupErrors::getTruncationReason());
    }

    /**
     * @return array{type: int, message: string, file: string, line: int}
     */
    private function makeError(string $message): array
    {
        return ['type' => E_WARNING, 'message' => $message, 'file' => 'Unknown', 'line' => 0];
    }
}
