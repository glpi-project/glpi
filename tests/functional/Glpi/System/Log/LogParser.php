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

    public function beforeTestMethod($method)
    {
        parent::beforeTestMethod($method);

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

    public function afterTestMethod($method)
    {
        parent::afterTestMethod($method);

        // Clean up the log file created for the test
        if (file_exists($this->log_file_path)) {
            \Safe\unlink($this->log_file_path);
        }
    }

    public function testConstructor()
    {
        $this->exception(
            function () {
                $this->newTestedInstance('/dir/not/exists');
            }
        )->hasMessage('Invalid directory "/dir/not/exists".');
    }


    public function testGetLogsFilesList()
    {
        $this->newTestedInstance();

        touch($this->log_file_path, strtotime('2022-09-20 00:00:00'));
        $log_files = $this->testedInstance->getLogsFilesList();
        $this->array($log_files)->hasKey('test.log');
        $this->array($log_files['test.log'])
            ->isIdenticalTo(
                [
                    'filepath' => 'test.log',
                    'datemod'  => '2022-09-20 00:00:00',
                    'size'     => 229,
                ]
            );
    }


    public function testParseLogFile()
    {
        $this->newTestedInstance();

        $log_entries = $this->testedInstance->parseLogFile('test.log');
        $this->array($log_entries)
            ->isIdenticalTo(
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
                ]
            );
    }


    public function testDownloadFile()
    {
        $this->newTestedInstance();

        $this->output(
            function () {
                $this->testedInstance->download('test.log');
            }
        )
            ->isNotEmpty()
            ->isEqualToContentsOfFile($this->log_file_path);
    }


    public function testEmptyFile()
    {
        $this->newTestedInstance();

        $this->boolean($this->testedInstance->empty('test.log'))->isTrue();
        $this->string(file_get_contents($this->log_file_path))
            ->isEmpty();
    }


    public function testDeleteFile()
    {
        $this->newTestedInstance();

        $this->boolean(file_exists($this->log_file_path))->isTrue();
        $this->boolean($this->testedInstance->delete('test.log'))->isTrue();
        $this->boolean(file_exists($this->log_file_path))->isFalse();
    }
}
