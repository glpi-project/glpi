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

namespace tests\units;

use DbTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/* Test for inc/crontask.class.php */

class CronTaskTest extends DbTestCase
{
    public function testCronTemp()
    {
        //create some files
        $filenames = [
            'recent_file.txt',
            'file1.txt',
            'file2.txt',
            'file3.txt',
            'file4.txt',
        ];
        foreach ($filenames as $filename) {
            $this->assertNotFalse(file_put_contents(GLPI_TMP_DIR . '/' . $filename, bin2hex(random_bytes(20))));
        }

        //create auto_orient directory
        if (!file_exists(GLPI_TMP_DIR . '/auto_orient/')) {
            $this->assertTrue(mkdir(GLPI_TMP_DIR . '/auto_orient/', 0755, true));
        }

        $tmp_dir_iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(GLPI_TMP_DIR, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($tmp_dir_iterator as $path) {
            if (basename($path) !== 'recent_file.txt') {
                // change the modification date of the file to make it considered as "not recent"
                $this->assertTrue(touch($path, time() - (HOUR_TIMESTAMP * 2)));
            }
        }

        // launch Cron for cleaning _tmp directory
        $mode = - \CronTask::MODE_EXTERNAL; // force
        \CronTask::launch($mode, 5, 'temp');

        $nb_file = $this->getFileCountRecursively(GLPI_TMP_DIR);
        $this->assertEquals(1, $nb_file); // only recent_file.txt should be preserved
    }


    public function getFileCountRecursively($path)
    {

        $dir = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator(
            $dir,
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        return iterator_count($files);
    }

    public static function registerProvider()
    {
        return [
            [
                'itemtype'        => 'CoreNonExistent',
                'name'            => 'CoreTest1',
                'should_register' => false, // Non-existent core class
            ],
            [
                'itemtype'        => 'CronTask',
                'name'            => 'CoreTest2',
                'should_register' => true, // Existing core class
            ],
            [
                'itemtype'        => 'Glpi\Marketplace\Controller',
                'name'            => 'CoreTest3',
                'should_register' => true, // Existing core namespaced class
            ],
            [
                'itemtype'        => 'PluginTestItemtype',
                'name'            => 'PluginTest1',
                'should_register' => true, // Plugin class. Existence not checked.
            ],
            [
                'itemtype'        => 'GlpiPlugin\\Tester\\TestItemtype',
                'name'            => 'NamespacedPluginTest1',
                'should_register' => true, // Plugin class with namespace. Existence not checked.
            ],
        ];
    }

    /**
     * @dataProvider registerProvider
     */
    public function testRegister(string $itemtype, string $name, bool $should_register)
    {
        $result = \CronTask::register($itemtype, $name, 30);
        if ($should_register) {
            $this->assertNotFalse($result);
        } else {
            $this->assertFalse($result);
        }
    }

    public static function unregisterProvider()
    {
       // Only plugins are supported with the unregister method.
        return [
            [
                'plugin_name'       => 'Test',
                'itemtype'          => 'PluginTestItemtype',
                'name'              => 'PluginTest1',
                'should_unregister' => true,
            ],
            [
                'plugin_name'       => 'Tester',
                'itemtype'          => 'GlpiPlugin\\Tester\\TestItemtype',
                'name'              => 'NamespacedPluginTest1',
                'should_unregister' => true,
            ],
            [
                'plugin_name'       => 'Tester',
                'itemtype'          => 'GlpiPlugin\\TesterNg\\TestItemtype',
                'name'              => 'NamespacedPluginTest2',
                'should_unregister' => false, // plugin name does not match class namespace
            ],
        ];
    }

    /**
     * @dataProvider unregisterProvider
     */
    public function testUnregister(string $plugin_name, string $itemtype, string $name, bool $should_unregister)
    {
        global $DB;

        // Register task .
        $plugin_task = \CronTask::register($itemtype, $name, 30, []);
        $this->assertNotFalse($plugin_task);

        // Check the task has been created in DB
        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => \CronTask::getTable(),
            'WHERE'  => ['itemtype' => addslashes($itemtype), 'name' => $name]
        ]);
        $this->assertEquals(1, $iterator->count());

        // Try un-registering the task
        $result = \CronTask::unregister($plugin_name);
        $this->assertTrue($result);

        // Check delete actually worked
        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => \CronTask::getTable(),
            'WHERE'  => ['itemtype' => addslashes($itemtype), 'name' => $name]
        ]);
        $this->assertEquals($should_unregister ? 0 : 1, $iterator->count());
    }

    public static function getNeedToRunProvider()
    {
        return [
            [
                'itemtype'    => 'CronTask',
                'name'        => 'CoreTest1',
                'should_run'  => true,
            ],
            [
                'itemtype'    => 'Glpi\Marketplace\Controller',
                'name'        => 'CoreTest2',
                'should_run'  => true,
            ],
            [
                'itemtype'    => 'PluginTestItemtype',
                'name'        => 'PluginTest1',
                'should_run'  => false, // Inactive plugin
            ],
            [
                'itemtype'    => 'PluginTesterItemtype',
                'name'        => 'PluginTest2',
                'should_run'  => true,
            ],
            [
                'itemtype'    => 'GlpiPlugin\\Tester\\TestItemtype',
                'name'        => 'NamespacedPluginTest',
                'should_run'  => true,
            ],
        ];
    }

    /**
     * @dataProvider getNeedToRunProvider
     */
    public function testGetNeedToRun(string $itemtype, string $name, bool $should_run)
    {
        global $DB;

        $plugins = new \Plugin();
        $plugins->init();

        // Deactivate all registered tasks
        $crontask = new \CronTask();
        $this->assertTrue($DB->update(\CronTask::getTable(), ['state' => \CronTask::STATE_DISABLE], [1]));
        $this->assertFalse($crontask->getNeedToRun());

        // Register task for active plugin.
        $plugin_task = \CronTask::register(
            $itemtype,
            $name,
            30,
            [
                'state'   => \CronTask::STATE_WAITING,
                'hourmin' => 0,
                'hourmax' => 24,
            ]
        );
        $this->assertNotFalse($plugin_task);
        $this->assertEquals($should_run, $crontask->getNeedToRun());
        if ($should_run) {
            $this->assertEquals($itemtype, $crontask->fields['itemtype']);
            $this->assertEquals($name, $crontask->fields['name']);
        }
    }

    public function testMethodsPresence()
    {
        global $DB;

        $iterator = $DB->request(['FROM' => \CronTask::getTable()]);

        foreach ($iterator as $row) {
            $itemtype = $row['itemtype'];
            $this->assertTrue(
                class_exists($itemtype),
                sprintf(
                    'Class %1$s from crontask table does not exists.',
                    $itemtype
                )
            );

            $method = 'cron' . ucfirst($row['name']);
            $this->assertTrue(
                method_exists($itemtype, $method),
                sprintf(
                    'Method %1$s::%2$s does not exists!',
                    $itemtype,
                    $method
                )
            );
        }
    }
}
