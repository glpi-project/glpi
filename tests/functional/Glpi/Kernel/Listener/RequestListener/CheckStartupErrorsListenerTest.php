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

namespace tests\units\Glpi\Kernel\Listener\RequestListener;

use Glpi\Error\StartupErrors;
use Glpi\Exception\Http\HttpException;
use Glpi\Kernel\Listener\RequestListener\CheckStartupErrorsListener;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class CheckStartupErrorsListenerTest extends GLPITestCase
{
    public function setUp(): void
    {
        parent::setUp();
        StartupErrors::reset();
    }

    public function tearDown(): void
    {
        StartupErrors::reset();
        parent::tearDown();
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testNothingHappensWhenThereIsNoStartupError(): void
    {
        (new CheckStartupErrorsListener())->onKernelRequest($this->makeRequestEvent());

        $this->assertTrue(true); // no exception, no log record
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testUnrelatedStartupErrorIsLoggedButDoesNotStopTheRequest(): void
    {
        StartupErrors::capture($this->makeError('PHP Startup: Unable to load dynamic library \'foo.so\''));

        (new CheckStartupErrorsListener())->onKernelRequest($this->makeRequestEvent());

        $this->hasPhpLogRecordThatContains(
            'PHP startup error: PHP Startup: Unable to load dynamic library \'foo.so\'',
            LogLevel::WARNING
        );
    }

    public static function truncationProvider(): iterable
    {
        yield 'max_input_vars' => [
            'message'     => 'PHP Request Startup: Input variables exceeded 1000. To increase the limit change max_input_vars in php.ini.',
            'status_code' => 400,
            'directive'   => 'max_input_vars',
        ];
        yield 'post_max_size' => [
            'message'     => 'PHP Request Startup: POST Content-Length of 2503 bytes exceeds the limit of 1024 bytes',
            'status_code' => 413,
            'directive'   => 'post_max_size',
        ];
        yield 'max_file_uploads' => [
            'message'     => 'PHP Request Startup: Maximum number of allowable file uploads has been exceeded',
            'status_code' => 400,
            'directive'   => 'max_file_uploads',
        ];
    }

    #[DataProvider('truncationProvider')]
    #[AllowMockObjectsWithoutExpectations]
    public function testTruncatedInputStopsTheRequest(string $message, int $status_code, string $directive): void
    {
        StartupErrors::capture($this->makeError($message));

        $exception = null;
        try {
            (new CheckStartupErrorsListener())->onKernelRequest($this->makeRequestEvent());
        } catch (HttpException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception, 'The request should have been stopped.');
        $this->assertSame($status_code, $exception->getStatusCode());
        $this->assertStringContainsString($directive, (string) $exception->getMessageToDisplay());

        $this->hasPhpLogRecordThatContains('PHP startup error: ' . $message, LogLevel::WARNING);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testSubRequestsAreIgnored(): void
    {
        StartupErrors::capture($this->makeError('PHP Request Startup: Input variables exceeded 1000.'));

        (new CheckStartupErrorsListener())->onKernelRequest(
            $this->makeRequestEvent(HttpKernelInterface::SUB_REQUEST)
        );

        $this->assertTrue(true); // no exception, no log record
    }

    private function makeRequestEvent(int $type = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent(
            $this->createMock(KernelInterface::class),
            new Request(),
            $type
        );
    }

    /**
     * @return array{type: int, message: string, file: string, line: int}
     */
    private function makeError(string $message): array
    {
        return ['type' => E_WARNING, 'message' => $message, 'file' => 'Unknown', 'line' => 0];
    }
}
