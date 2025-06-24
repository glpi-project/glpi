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

namespace tests\units\Glpi\System\Log;

class LogParser extends \GLPITestCase
{
    private $log_file_path = GLPI_LOG_DIR . '/test.log';

    public function setUp(): void
    {
        parent::setup();

        \Safe\file_put_contents(
            $this->log_file_path,
            <<<LOG
[2022-09-20 00:00:00] test log 1 line 1
test log 1 line 2
test log 1 line 3

[2022-09-20 00:10:00] test log 2 line 1
test log 2 line 2
test log 2 line 3

[2022-09-20 01:00:00] test log 3 line 1
test log 3 line 2
test log 3 line 3
LOG
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();

        // Clean up the log file created for the test
        if (file_exists($this->log_file_path)) {
            \Safe\unlink($this->log_file_path);
        }
    }

    public function testConstructor()
    {
        $this->expectExceptionMessage('Invalid directory "/dir/not/exists".');
        new \Glpi\System\Log\LogParser('/dir/not/exists');
    }


    public function testGetLogsFilesList()
    {
        $instance = new \Glpi\System\Log\LogParser();

        touch($this->log_file_path, strtotime('2022-09-20 00:00:00'));
        $log_files = $instance->getLogsFilesList();
        $this->assertArrayHasKey('test.log', $log_files);
        $this->assertSame(
            [
                'filepath' => 'test.log',
                'datemod'  => '2022-09-20 00:00:00',
                'size'     => 229,
            ],
            $log_files['test.log']
        );
    }


    public function testParseLogFile()
    {
        $instance = new \Glpi\System\Log\LogParser();

        $log_entries = $instance->parseLogFile('test.log');
        $this->assertSame(
            [
                [
                    'id' => 'date_0_2022-09-20-00-00-00',
                    'datetime' => '2022-09-20 00:00:00',
                    'text' => "test log 1 line 1\ntest log 1 line 2\ntest log 1 line 3",
                ],
                [
                    'id' => 'date_1_2022-09-20-00-10-00',
                    'datetime' => '2022-09-20 00:10:00',
                    'text' => "test log 2 line 1\ntest log 2 line 2\ntest log 2 line 3",
                ],
                [
                    'id' => 'date_2_2022-09-20-01-00-00',
                    'datetime' => '2022-09-20 01:00:00',
                    'text' => "test log 3 line 1\ntest log 3 line 2\ntest log 3 line 3",
                ],
            ],
            $log_entries
        );
    }


    public function testDownloadFile()
    {
        $instance = new \Glpi\System\Log\LogParser();

        $this->expectOutputString(file_get_contents($this->log_file_path));
        $instance->download('test.log');
    }


    public function testEmptyFile()
    {
        $instance = new \Glpi\System\Log\LogParser();

        $this->assertTrue($instance->empty('test.log'));
        $this->assertEmpty(file_get_contents($this->log_file_path));
    }


    public function testDeleteFile()
    {
        $instance = new \Glpi\System\Log\LogParser();

        $this->assertTrue(file_exists($this->log_file_path));
        $this->assertTrue($instance->delete('test.log'));
        $this->assertFalse(file_exists($this->log_file_path));
    }
}
