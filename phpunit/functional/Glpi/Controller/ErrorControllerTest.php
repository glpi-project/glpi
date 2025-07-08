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

namespace tests\units\Glpi\Controller;

use DbTestCase;
use Glpi\Controller\ErrorController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\HttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use PHPUnit\Framework\Attributes\DataProvider;
use Session;
use Symfony\Component\HttpFoundation\Request;

class ErrorControllerTest extends DbTestCase
{
    public static function requestProvider(): iterable
    {
        $users_credentials = [
            null,
            ['glpi', 'glpi'],
            ['post-only', 'postonly'],
        ];
        foreach ($users_credentials as $credentials) {
            foreach ([true, false] as $debug_mode) {
                yield [
                    'credentials'       => $credentials,
                    'debug_mode'        => $debug_mode,
                    'exception'         => new AccessDeniedHttpException(),
                    'expected_code'     => 403,
                    'expected_title'    => 'Access denied',
                    'expected_message'  => 'You don\'t have permission to perform this action.',
                ];
                yield [
                    'credentials'       => $credentials,
                    'debug_mode'        => $debug_mode,
                    'exception'         => new HttpException(403),
                    'expected_code'     => 403,
                    'expected_title'    => 'Access denied',
                    'expected_message'  => 'You don\'t have permission to perform this action.',
                ];
                yield [
                    'credentials'       => $credentials,
                    'debug_mode'        => $debug_mode,
                    'exception'         => new BadRequestHttpException(),
                    'expected_code'     => 400,
                    'expected_title'    => 'Invalid request',
                    'expected_message'  => 'Invalid request parameters.',
                ];
                yield [
                    'credentials'       => $credentials,
                    'debug_mode'        => $debug_mode,
                    'exception'         => new HttpException(400),
                    'expected_code'     => 400,
                    'expected_title'    => 'Invalid request',
                    'expected_message'  => 'Invalid request parameters.',
                ];
                yield [
                    'credentials'       => $credentials,
                    'debug_mode'        => $debug_mode,
                    'exception'         => new NotFoundHttpException(),
                    'expected_code'     => 404,
                    'expected_title'    => 'Item not found',
                    'expected_message'  => 'The requested item has not been found.',
                ];
                yield [
                    'credentials'       => $credentials,
                    'debug_mode'        => $debug_mode,
                    'exception'         => new HttpException(404),
                    'expected_code'     => 404,
                    'expected_title'    => 'Item not found',
                    'expected_message'  => 'The requested item has not been found.',
                ];

                // Check some random 4xx codes
                foreach ([405, 410, 413] as $code) {
                    yield [
                        'credentials'       => $credentials,
                        'debug_mode'        => $debug_mode,
                        'exception'         => new HttpException($code),
                        'expected_code'     => $code,
                        'expected_title'    => 'Invalid request',
                        'expected_message'  => 'The request is invalid and cannot be processed.',
                    ];
                }

                yield [
                    'credentials'       => $credentials,
                    'debug_mode'        => $debug_mode,
                    'exception'         => new \Exception(),
                    'expected_code'     => 500,
                    'expected_title'    => 'Error',
                    'expected_message'  => 'An unexpected error occurred',
                ];

                // Check some random 5xx codes
                foreach ([500, 502, 508] as $code) {
                    yield [
                        'credentials'       => $credentials,
                        'debug_mode'        => $debug_mode,
                        'exception'         => new HttpException($code),
                        'expected_code'     => $code,
                        'expected_title'    => 'Error',
                        'expected_message'  => 'An unexpected error occurred',
                    ];
                }
            }
        }
    }

    #[DataProvider('requestProvider')]
    public function testErrorPageRendering(
        ?array $credentials,
        bool $debug_mode,
        \Throwable $exception,
        int $expected_code,
        string $expected_title,
        string $expected_message
    ): void {
        if ($credentials !== null) {
            $this->login(...$credentials);
        }
        if ($debug_mode) {
            $_SESSION['glpi_use_mode'] = Session::DEBUG_MODE;
        }

        $controller = new ErrorController();
        $response = $controller->__invoke(
            new Request(),
            $exception
        );

        $content = $response->getContent();

        $this->assertEquals($expected_code, $response->getStatusCode());

        $this->assertStringContainsString(sprintf('<title>%s - GLPI</title>', \htmlspecialchars($expected_title)), $content);
        $this->assertStringContainsString(\htmlspecialchars($expected_message), $content);

        // Validates that response contains a valid page (doctype + <html><head>.*</head><body>.*</body></html>)
        $this->assertMatchesRegularExpression(
            '#^\s*<!DOCTYPE html>\s*<html( [^>]*)?>\s*<head( [^>]*)?>.*</head>\s*<body( [^>]*)?>.*</body>\s*</html>\s*$#s',
            $content
        );

        if ($debug_mode) {
            $this->assertStringContainsString('<pre data-testid="stack-trace">', $content);
        } else {
            $this->assertStringNotContainsString('<pre data-testid="stack-trace">', $content);
        }
    }

    #[DataProvider('requestProvider')]
    public function testErrorBlockRendering(
        ?array $credentials,
        bool $debug_mode,
        \Throwable $exception,
        int $expected_code,
        string $expected_title,
        string $expected_message
    ): void {
        if ($credentials !== null) {
            $this->login(...$credentials);
        }
        if ($debug_mode) {
            $_SESSION['glpi_use_mode'] = Session::DEBUG_MODE;
        }

        $controller = new ErrorController();
        $response = $controller->__invoke(
            new Request(server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']),
            $exception
        );

        $content = $response->getContent();

        $this->assertEquals($expected_code, $response->getStatusCode());
        $this->assertStringContainsString(\htmlspecialchars($expected_message), $content);

        // Validates that response contains only a div (not a full page)
        $this->assertMatchesRegularExpression(
            '#^\s*<div class="card">.*</div>\s*$#s',
            $content
        );

        if ($debug_mode) {
            $this->assertStringContainsString('<pre data-testid="stack-trace">', $content);
        } else {
            $this->assertStringNotContainsString('<pre data-testid="stack-trace">', $content);
        }
    }

    #[DataProvider('requestProvider')]
    public function testErrorJsonRendering(
        ?array $credentials,
        bool $debug_mode,
        \Throwable $exception,
        int $expected_code,
        string $expected_title,
        string $expected_message
    ): void {
        if ($credentials !== null) {
            $this->login(...$credentials);
        }
        if ($debug_mode) {
            $_SESSION['glpi_use_mode'] = Session::DEBUG_MODE;
        }

        $controller = new ErrorController();
        $response = $controller->__invoke(
            new Request(
                server: [
                    'HTTP_ACCEPT'           => 'application/json',
                    'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', // should be ignored
                ]
            ),
            $exception
        );

        $content = $response->getContent();

        $this->assertEquals($expected_code, $response->getStatusCode());

        $this->assertJson($content);

        $decoded_content = \json_decode($content, true);

        $this->assertArrayHasKey('error', $decoded_content);
        $this->assertTrue($decoded_content['error']);

        $this->assertArrayHasKey('title', $decoded_content);
        $this->assertEquals($expected_title, $decoded_content['title']);

        $this->assertArrayHasKey('message', $decoded_content);
        $this->assertEquals($expected_message, $decoded_content['message']);

        $this->assertArrayHasKey('trace', $decoded_content);
        $this->assertEquals($debug_mode, $decoded_content['trace'] !== null);
    }
}
