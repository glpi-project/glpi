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

namespace tests\units\Glpi\Log;

use Glpi\Log\ConsoleCommandRedactionProcessor;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class ConsoleCommandRedactionProcessorTest extends TestCase
{
    public function test_Invoke_RedactsPositionalArgumentsAndOptionValues(): void
    {
        $record = new \Monolog\LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'glpi_test',
            level: Level::Error,
            message: 'Command failed: {command}',
            context: ['command' => 'user:reset-password jdoe --password=secret --force'],
        );

        $result = (new ConsoleCommandRedactionProcessor())($record);

        $this->assertSame('user:reset-password *** --password=*** --force', $result->context['command']);
    }

    public function test_GlobalLogger_RedactsCommandBeforePlaceholdersAreInterpolated(): void
    {
        global $PHPLOGGER;

        // Reuse the processors configured by SystemConfigurator (same order), but with an in-memory handler
        // to prevent writing in the real log files.
        $handler = new TestHandler();
        $logger = new Logger('glpi_test', [$handler], $PHPLOGGER->getProcessors());

        $logger->error(
            'Command failed: {command}',
            ['command' => 'user:reset-password jdoe --password=secret']
        );

        $this->assertTrue($handler->hasErrorRecords());
        $records = $handler->getRecords();
        $this->assertCount(1, $records);
        $this->assertSame('Command failed: user:reset-password *** --password=***', $records[0]->message);
        $this->assertStringNotContainsString('jdoe', $records[0]->message);
        $this->assertStringNotContainsString('secret', $records[0]->message);
    }
}
