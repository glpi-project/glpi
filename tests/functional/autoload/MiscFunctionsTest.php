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

namespace tests\units\autoload;

use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LogLevel;

class MiscFunctionsTest extends DbTestCase
{
    public static function htmlescapeProvider(): iterable
    {
        yield [
            'input'  => '1 > 0 & 1 < 2',
            'output' => '1 &gt; 0 &amp; 1 &lt; 2',
        ];
        yield [
            'input'  => 'with quotes : "hello" and \'Good bye !\'',
            'output' => 'with quotes : &quot;hello&quot; and &#039;Good bye !&#039;',
        ];
        yield [
            'input'  => null,
            'output' => '',
        ];
        yield [
            'input'  => true,
            'output' => '1',
        ];
        yield [
            'input'  => false,
            'output' => '',
        ];
        yield [
            'input'  => 1,
            'output' => '1',
        ];
        yield [
            'input'  => 0,
            'output' => '0',
        ];
    }

    #[DataProvider('htmlescapeProvider')]
    public function testHtmlescape(mixed $input, string $output): void
    {
        $this->assertEquals($output, \htmlescape($input));
    }

    public function testHtmlescapeWithUnexpectedType(): void
    {
        $this->assertEquals('Array', \htmlescape(['an', 'array']));
        $this->hasPhpLogRecordThatContains(
            'Array to string conversion',
            LogLevel::WARNING
        );
    }
    public static function jsescapeProvider(): iterable
    {
        yield [
            'input'  => 'simple quote (\')',
            'output' => 'simple\u0020quote\u0020\u0028\u0027\u0029',
        ];
        yield [
            'input'  => 'double quote (")',
            'output' => 'double\u0020quote\u0020\u0028\u0022\u0029',
        ];
        yield [
            'input'  => 'backslash \\',
            'output' => 'backslash\u0020\\\\',
        ];
        yield [
            'input'  => null,
            'output' => '',
        ];
        yield [
            'input'  => true,
            'output' => '1',
        ];
        yield [
            'input'  => false,
            'output' => '',
        ];
        yield [
            'input'  => 1,
            'output' => '1',
        ];
        yield [
            'input'  => 0,
            'output' => '0',
        ];
    }

    #[DataProvider('jsescapeProvider')]
    public function testJsescape(mixed $input, string $output): void
    {
        $this->assertEquals($output, \jsescape($input));
    }

    public function testJsescapeWithUnexpectedType(): void
    {
        $this->assertEquals('Array', \jsescape(['an', 'array']));
        $this->hasPhpLogRecordThatContains(
            'Array to string conversion',
            LogLevel::WARNING
        );
    }

    public static function isAPIProvider(): iterable
    {
        // HL API routes (at server root)
        yield 'hl api v1' => [
            'uri' => '/api.php/v1/Computer',
            'expected' => true,
        ];
        yield 'hl api v2.3' => [
            'uri' => '/api.php/v2.3/Computer',
            'expected' => true,
        ];
        yield 'hl api root' => [
            'uri' => '/api.php',
            'expected' => true,
        ];
        yield 'hl api with query' => [
            'uri' => '/api.php/v2.3/Computer?expand_dropdowns=1',
            'expected' => true,
        ];

        // HL API routes (not at server root - passing)
        yield 'hl api v1 not on server root' => [
            'uri' => '/glpi/api.php/v1/Computer',
            'glpi_root' => '/glpi',
            'expected' => true,
        ];
        yield 'hl api v2.3 not on server root' => [
            'uri' => '/services/glpi/api.php/v2.3/Computer',
            'glpi_root' => '/services/glpi',
            'expected' => true,
        ];
        yield 'hl api root not on server root' => [
            'uri' => '/glpi/api.php',
            'glpi_root' => '/glpi',
            'expected' => true,
        ];
        yield 'hl api with query not on server root' => [
            'uri' => '/subfolder/another/api.php/v2.3/Computer?expand_dropdowns=1',
            'glpi_root' => '/subfolder/another',
            'expected' => true,
        ];

        // HL API routes (not at server root - failing)
        yield 'hl api v1 not on server root (fails)' => [
            'uri' => '/glpi/api.php/v1/Computer',
            'glpi_root' => '/',
            'expected' => false,
        ];
        yield 'hl api v2.3 not on server root (fails)' => [
            'uri' => '/services/glpi/api.php/v2.3/Computer',
            'glpi_root' => '/services',
            'expected' => false,
        ];
        yield 'hl api with query not on server root (fails)' => [
            'uri' => '/subfolder/another/api.php/v2.3/Computer?expand_dropdowns=1',
            'glpi_root' => '/another',
            'expected' => false,
        ];

        // Legacy REST API (at server root)
        yield 'apirest root' => [
            'uri' => '/apirest.php',
            'expected' => true,
        ];
        yield 'apirest initSession' => [
            'uri' => '/apirest.php/initSession',
            'expected' => true,
        ];
        yield 'apirest with query' => [
            'uri' => '/apirest.php/Computer?expand_dropdowns=1',
            'expected' => true,
        ];

        // Legacy REST API (not at server root - passing)
        yield 'apirest root not on server root' => [
            'uri' => '/glpi/apirest.php',
            'glpi_root' => '/glpi',
            'expected' => true,
        ];
        yield 'apirest initSession not on server root' => [
            'uri' => '/services/glpi/apirest.php/initSession',
            'glpi_root' => '/services/glpi',
            'expected' => true,
        ];
        yield 'apirest with query not on server root' => [
            'uri' => '/subfolder/another/apirest.php/Computer?expand_dropdowns=1',
            'glpi_root' => '/subfolder/another',
            'expected' => true,
        ];

        // Legacy REST API (not at server root - failing)
        yield 'apirest root not on server root (fails)' => [
            'uri' => '/glpi/apirest.php',
            'glpi_root' => '/',
            'expected' => false,
        ];
        yield 'apirest initSession not on server root (fails)' => [
            'uri' => '/services/glpi/apirest.php/initSession',
            'glpi_root' => '/services',
            'expected' => false,
        ];
        yield 'apirest with query not on server root (fails)' => [
            'uri' => '/subfolder/another/apirest.php/Computer?expand_dropdowns=1',
            'glpi_root' => '/another',
            'expected' => false,
        ];

        // Non-API paths
        yield 'front page' => [
            'uri' => '/front/ticket.php',
            'expected' => false,
        ];
        yield 'front url ending with api.php' => [
            'uri' => '/front/no_api.php',
            'expected' => false,
        ];
        yield 'front url ending with api.php 2' => [
            'uri' => '/front/api.php',
            'expected' => false,
        ];
        yield 'plugin api.php' => [
            'uri' => '/plugins/myplugin/api.php',
            'expected' => false,
        ];
        yield 'plugin apirest.php' => [
            'uri' => '/plugins/myplugin/apirest.php',
            'expected' => false,
        ];
        yield 'myapi.php' => [
            'uri' => '/myapi.php',
            'expected' => false,
        ];
        yield 'root' => [
            'uri' => '/',
            'expected' => false,
        ];
        yield 'ajax' => [
            'uri' => '/ajax/common.tabs.php',
            'expected' => false,
        ];
        yield 'api.php in query param' => [
            'uri' => '/front/ticket.php?redirect=api.php',
            'expected' => false,
        ];
        yield 'api.php in query param name' => [
            'uri' => '/front/ticket.php?api.php=redirect',
            'expected' => false,
        ];
        yield 'apirest.php in query param' => [
            'uri' => '/front/ticket.php?redirect=apirest.php',
            'expected' => false,
        ];
        yield 'api.php as full query string' => [
            'uri' => '/front/ticket.php?script=api.php/v2.3/Computer',
            'expected' => false,
        ];
    }

    #[DataProvider('isAPIProvider')]
    public function testIsAPI(string $uri, bool $expected, string $glpi_root = ''): void
    {
        // --- arrange ---
        $original = $_SERVER;
        $_SERVER['SCRIPT_FILENAME'] = GLPI_ROOT . '/index.php';
        $_SERVER['SCRIPT_NAME'] = $glpi_root . '/index.php';
        $_SERVER['REQUEST_URI'] = $uri;

        // --- act + assert ---
        $this->assertEquals($expected, \isAPI());

        // restore $_SERVER
        $_SERVER = $original;
    }

    public function testIsAPIWithMissingRequestUri(): void
    {
        // --- arrange ---
        $original = $_SERVER['REQUEST_URI'] ?? null;
        unset($_SERVER['REQUEST_URI']);

        // --- act + assert ---
        $this->assertFalse(\isAPI());

        // restore
        if ($original !== null) {
            $_SERVER['REQUEST_URI'] = $original;
        }
    }
}
