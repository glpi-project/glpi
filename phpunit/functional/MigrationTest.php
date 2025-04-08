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

namespace tests\units;

use Computer;
use CronTask;
use DbTestCase;
use Glpi\Socket;
use Migration;
use PHPUnit\Framework\Attributes\DataProvider;

class MigrationTest extends DbTestCase
{
    public static function cronTaskProvider(): iterable
    {
        yield [
            'itemtype'  => Computer::class,
            'name'      => 'whatever',
            'frequency' => HOUR_TIMESTAMP,
            'param'     => 25,
            'options'   => [],
            'expected'  => [
                // specific values
                'itemtype'      => Computer::class,
                'name'          => 'whatever',
                'frequency'     => HOUR_TIMESTAMP,
                'param'         => 25,

                // default values
                'mode'          => CronTask::MODE_EXTERNAL,
                'state'         => CronTask::STATE_WAITING,
                'hourmin'       => 0,
                'hourmax'       => 24,
                'logs_lifetime' => 30,
                'allowmode'     => CronTask::MODE_INTERNAL | CronTask::MODE_EXTERNAL,
                'comment'       => '',
            ],
        ];

        yield [
            'itemtype'  => Computer::class,
            'name'      => 'foo',
            'frequency' => DAY_TIMESTAMP,
            'param'     => null,
            'options'   => [
                'mode'          => CronTask::MODE_INTERNAL,
                'state'         => CronTask::STATE_DISABLE,
                'allowmode'     => CronTask::MODE_INTERNAL,
            ],
            'expected'  => [
                // specific values
                'itemtype'      => Computer::class,
                'name'          => 'foo',
                'frequency'     => DAY_TIMESTAMP,
                'param'         => null,
                'mode'          => CronTask::MODE_INTERNAL,
                'state'         => CronTask::STATE_DISABLE,
                'allowmode'     => CronTask::MODE_INTERNAL,

                // default values
                'hourmin'       => 0,
                'hourmax'       => 24,
                'logs_lifetime' => 30,
                'comment'       => '',
            ],
        ];

        yield [
            'itemtype'  => Socket::class,
            'name'      => 'bar',
            'frequency' => HOUR_TIMESTAMP,
            'param'     => null,
            'options'   => [
                'hourmin'       => 9,
                'hourmax'       => 18,
                'logs_lifetime' => 365,
                'comment'       => 'A cron task ...',

            ],
            'expected'  => [
                // specific values
                'itemtype'      => Socket::class,
                'name'          => 'bar',
                'frequency'     => HOUR_TIMESTAMP,
                'param'         => null,
                'hourmin'       => 9,
                'hourmax'       => 18,
                'logs_lifetime' => 365,
                'comment'       => 'A cron task ...',

                // default values
                'mode'          => CronTask::MODE_EXTERNAL,
                'state'         => CronTask::STATE_WAITING,
                'allowmode'     => CronTask::MODE_INTERNAL | CronTask::MODE_EXTERNAL,
            ],
        ];
    }

    #[DataProvider('cronTaskProvider')]
    public function testAddCronTask(
        string $itemtype,
        string $name,
        int $frequency,
        ?int $param,
        array $options,
        array $expected
    ): void {
        $migration = new Migration('1.2.3');
        $migration->addCrontask($itemtype, $name, $frequency, $param, $options);

        $crontask = new CronTask();
        $this->assertTrue($crontask->getFromDBByCrit($expected));
    }

    public function testAddCronTaskThatAlreadyExists(): void
    {
        $existing_crontask = $this->createItem(
            CronTask::class,
            [
                // specific values
                'itemtype'  => Computer::class,
                'name'      => 'duplicate_test',
                'frequency' => HOUR_TIMESTAMP,
                'param'     => null,
            ]
        );

        $migration = new Migration('1.2.3');
        $migration->addCrontask(Computer::class, 'duplicate_test', MONTH_TIMESTAMP, 25);

        // Assert that the contask in DB after the migration matches the previously existing crontask
        $result_crontask = new CronTask();
        $this->assertTrue($result_crontask->getFromDBByCrit(['itemtype' => Computer::class, 'name' => 'duplicate_test']));
        $this->assertEquals($existing_crontask->getID(), $result_crontask->getID());
        $this->assertEquals($existing_crontask->fields, $result_crontask->fields);
    }
}
