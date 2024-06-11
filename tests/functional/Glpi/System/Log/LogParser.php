<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace tests\units\Glpi\System\Log;

use org\bovigo\vfs\vfsStream;

class LogParser extends \GLPITestCase
{
    public function beforeTestMethod($method)
    {
        parent::beforeTestMethod($method);

        vfsStream::setup(
            'glpi_logs',
            null,
            [
                'test.log' => <<<LOG
[2022-09-20 00:00:00] test log 1 line 1
test log 1 line 2
test log 1 line 3

[2022-09-20 00:10:00] test log 2 line 1
test log 2 line 2
test log 2 line 3

[2022-09-20 01:00:00] test log 3 line 1
test log 3 line 2
test log 3 line 3
LOG,
            ]
        );
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
        $this->newTestedInstance(vfsStream::url('glpi_logs'));

        touch(vfsStream::url('glpi_logs/test.log'), strtotime('2022-09-20 00:00:00'));
        $this->array($this->testedInstance->getLogsFilesList())
            ->isIdenticalTo(
                [
                    'test.log' => [
                        'filepath' => 'test.log',
                        'datemod'  => '2022-09-20 00:00:00',
                        'size'     => 229,
                    ]
                ]
            );
    }


    public function testParseLogFile()
    {
        $this->newTestedInstance(vfsStream::url('glpi_logs'));

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
        $this->newTestedInstance(vfsStream::url('glpi_logs'));

        $this->output(
            function () {
                $this->testedInstance->download('test.log');
            }
        )
            ->isNotEmpty()
            ->isEqualToContentsOfFile(vfsStream::url('glpi_logs/test.log'));
    }


    public function testEmptyFile()
    {
        $this->newTestedInstance(vfsStream::url('glpi_logs'));

        $this->boolean($this->testedInstance->empty('test.log'))->isTrue();
        $this->string(file_get_contents(vfsStream::url('glpi_logs/test.log')))
            ->isEmpty();
    }


    public function testDeleteFile()
    {
        $this->newTestedInstance(vfsStream::url('glpi_logs'));

        $this->boolean($this->testedInstance->delete('test.log'))->isTrue();
        $this->boolean(file_exists(vfsStream::url('glpi_logs/test.log')))
            ->isFalse();
    }
}
