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

namespace tests\units\Glpi\Log;

use DateTimeImmutable;
use Glpi\Log\ErrorLogLineFormatter;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ErrorLogLineFormatterTest extends TestCase
{
    public static function logRecordProvider(): iterable
    {
        yield [
            'record' => new LogRecord(
                datetime: new DateTimeImmutable('2022-08-14 11:45:32'),
                channel: 'glpisyslog',
                level: Level::Emergency,
                message: 'Fatal Error ...',
            ),
            'expected' => '[2022-08-14 11:45:32] glpisyslog.EMERGENCY:   *** Fatal Error ...',
        ];
        yield [
            'record' => new LogRecord(
                datetime: new DateTimeImmutable('2024-04-12 21:45:32'),
                channel: 'glpiphplog',
                level: Level::Alert,
                message: 'An unexpected error occurred',
            ),
            'expected' => '[2024-04-12 21:45:32] glpiphplog.ALERT:   *** An unexpected error occurred',
        ];
        yield [
            'record' => new LogRecord(
                datetime: new DateTimeImmutable('2024-08-12 21:45:32'),
                channel: 'glpiphplog',
                level: Level::Critical,
                message: 'Uncaught Exception ...',
            ),
            'expected' => '[2024-08-12 21:45:32] glpiphplog.CRITICAL:   *** Uncaught Exception ...',
        ];
        yield [
            'record' => new LogRecord(
                datetime: new DateTimeImmutable('2024-08-12 21:45:32'),
                channel: 'glpiphplog',
                level: Level::Error,
                message: 'Unable to do something',
            ),
            'expected' => '[2024-08-12 21:45:32] glpiphplog.ERROR:   *** Unable to do something',
        ];
        yield [
            'record' => new LogRecord(
                datetime: new DateTimeImmutable('2024-08-12 21:45:32'),
                channel: 'glpiphplog',
                level: Level::Warning,
                message: 'Failed to format the value XXX',
            ),
            'expected' => '[2024-08-12 21:45:32] glpiphplog.WARNING:   *** Failed to format the value XXX',
        ];
        yield [
            'record' => new LogRecord(
                datetime: new DateTimeImmutable('2019-03-17 09:12:46'),
                channel: 'glpiphplog',
                level: Level::Notice,
                message: 'Value seems weird, but is valid',
            ),
            'expected' => '[2019-03-17 09:12:46] glpiphplog.NOTICE:   *** Value seems weird, but is valid',
        ];
        yield [
            'record' => new LogRecord(
                datetime: new DateTimeImmutable('2019-03-17 09:12:46'),
                channel: 'glpiphplog',
                level: Level::Info,
                message: 'Connection established to the remote server',
            ),
            'expected' => '[2019-03-17 09:12:46] glpiphplog.INFO:   *** Connection established to the remote server',
        ];
        yield [
            'record' => new LogRecord(
                datetime: new DateTimeImmutable('2019-03-17 09:12:46'),
                channel: 'glpiphplog',
                level: Level::Debug,
                message: 'debug trace',
            ),
            'expected' => '[2019-03-17 09:12:46] glpiphplog.DEBUG:   *** debug trace',
        ];
    }

    /**
     * Test CommonDBTM::getTable() method.
     *
     * @return void
     */
    #[DataProvider('logRecordProvider')]
    public function testFormatMainLine(LogRecord $record, string $expected): void
    {
        $formatter = new ErrorLogLineFormatter();
        $formatted = $formatter->format($record);
        $lines = explode("\n", $formatted);
        $this->assertSame($expected, $lines[0]);
    }

    public function testFormatExceptionTrace(): void
    {
        $formatter = new ErrorLogLineFormatter();

        $record = new LogRecord(
            datetime: new DateTimeImmutable('2024-08-12 21:45:32'),
            channel: 'glpiphplog',
            level: Level::Critical,
            message: 'Uncaught PHP Exception RuntimeException: "Operation failed!"',
            context: ['exception' => new \RuntimeException()],
            extra: [],
        );

        $formatted = $formatter->format($record);

        $lines = explode("\n", $formatted);

        $this->assertSame(
            '[2024-08-12 21:45:32] glpiphplog.CRITICAL:   *** Uncaught PHP Exception RuntimeException: "Operation failed!"',
            $lines[0]
        );
        $this->assertSame(
            '  Backtrace :',
            $lines[1]
        );
        $this->assertSame(
            '  ...onal/Glpi/Log/ErrorLogLineFormatterTest.php:145 ',
            $lines[2]
        );
        $this->assertMatchesRegularExpression(
            '#.*phpunit/src/Framework/TestCase\.php:\d+ tests\\\units\\\Glpi\\\Log\\\ErrorLogLineFormatterTest->testFormatExceptionTrace\(\)#',
            $lines[3]
        );
        // Testing the following lines in the trace would produce an unstable test.
        // Unfortunately, it is imposssible to mock an exception, as all its methods are final.
    }
}
