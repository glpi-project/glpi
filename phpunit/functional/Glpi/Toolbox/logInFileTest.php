<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace tests\units\Glpi\Toolbox;

use Glpi\Log\AccessLogHandler;
use Glpi\Log\ErrorLogHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test class for src/Glpi/Toolbox::logInFile
 */
class logInFileTest extends \GLPITestCase
{
    private const TEST_CUSTOM_LOG_FILE_NAME = 'test_log_file';

    public function setUp(): void
    {
        parent::setUp();
        // remove the log file if it exists
        assert(!file_exists($this->getCustomLogFilePath()) || unlink($this->getCustomLogFilePath()));
        assert(!file_exists($this->getDefaultFilePath()) || unlink($this->getDefaultFilePath()));
    }

    public function test_LogInFile_LogsAreWrittenInLogFile(): void
    {
        assert(\Toolbox::logInFile(self::TEST_CUSTOM_LOG_FILE_NAME, 'The logged message'), 'log failed');

        $this->assertFileExists($this->getCustomLogFilePath());
    }

    public function test_LogInFile_SeeExpectedContentsInLogFile(): void
    {
        $message = 'The logged message';
        assert(\Toolbox::logInFile(self::TEST_CUSTOM_LOG_FILE_NAME, $message), 'log failed');

        $this->assertStringContainsString($message, file_get_contents($this->getCustomLogFilePath()));
    }

    public function test_LogInFile_FilterRootPathInLogFile(): void
    {
        $messageWithPath = 'Error somewhere in the path ' . GLPI_ROOT . ' triggered';
        assert(\Toolbox::logInFile(self::TEST_CUSTOM_LOG_FILE_NAME, $messageWithPath), 'log failed');

        $this->assertStringNotContainsString(\GLPI_ROOT, file_get_contents($this->getCustomLogFilePath()));
    }

    public function test_Log_LogsAreWrittenInLogFile(): void
    {
        $PHPLOGGER = new Logger('glpi_test');
        $PHPLOGGER->pushHandler(new ErrorLogHandler());

        $message = 'This is a test error';
        $PHPLOGGER->error($message);

        $this->assertFileExists($this->getDefaultFilePath());
    }

    public function test_Log_SeeExpectedContentsInLogFile(): void
    {
        $PHPLOGGER = new Logger('glpi_test');
        $PHPLOGGER->pushHandler(new ErrorLogHandler());

        $message = 'This is a test error';
        $PHPLOGGER->error($message);

        $this->assertStringContainsString($message, file_get_contents($this->getDefaultFilePath()));
    }

    public function test_Log_FilterRootPathInLogFile(): void
    {
        $PHPLOGGER = new Logger('glpi_test');
        $PHPLOGGER->pushHandler(new ErrorLogHandler());

        $message = 'This is a test error at ' . GLPI_ROOT;
        $PHPLOGGER->error($message);

        $this->assertStringNotContainsString(GLPI_ROOT, file_get_contents($this->getDefaultFilePath()));
    }

    private function getCustomLogFilePath(): string
    {
        return GLPI_LOG_DIR . "/" . self::TEST_CUSTOM_LOG_FILE_NAME . ".log";
    }

    /**
     * value hardcoded in \Glpi\Log\ErrorLogHandler::__construct()
     */
    private function getDefaultFilePath(): string
    {
        return GLPI_LOG_DIR . "/php-errors.log";
    }
}
