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

use DbTestCase;
use Glpi\Toolbox\VersionParser;

/* Test for inc/plugin.class.php */

class Plugin extends DbTestCase
{
    private $test_plugin_directory = 'test';
    private $anothertest_plugin_directory = 'anothertest';

    public function afterTestMethod($method)
    {

       // Remove directory and files generated by tests
        foreach ([$this->test_plugin_directory, $this->anothertest_plugin_directory] as $directory) {
            $test_plugin_path = $this->getTestPluginPath($directory);
            if (file_exists($test_plugin_path)) {
                \Toolbox::deleteDir($test_plugin_path);
            }
        }

        parent::afterTestMethod($method);
    }

    public function testGetGlpiVersion()
    {
        $plugin = new \Plugin();
        $this->string($plugin->getGlpiVersion())->isIdenticalTo(VersionParser::getNormalizedVersion(GLPI_VERSION, false));
    }


    public function testcheckGlpiVersion()
    {
       //$this->constant->GLPI_VERSION = '9.1';
        $plugin = new \mock\Plugin();

       // Test min compatibility
        $infos = ['min' => '0.90'];

        $this->calling($plugin)->getGlpiVersion = '9.2.0';
        $this->boolean($plugin->checkGlpiVersion($infos))->isTrue();

        $this->calling($plugin)->getGlpiVersion = '0.89.0';
        $this->output(
            function () use ($plugin, $infos) {
                $this->boolean($plugin->checkGlpiVersion($infos))->isFalse();
            }
        )->isIdenticalTo('This plugin requires GLPI &gt;= 0.90.');

       // Test max compatibility
        $infos = ['max' => '9.3'];

        $this->calling($plugin)->getGlpiVersion = '9.2.0';
        $this->boolean($plugin->checkGlpiVersion($infos))->isTrue();

        $this->calling($plugin)->getGlpiVersion = '9.3.0';
        $this->output(
            function () use ($plugin, $infos) {
                $this->boolean($plugin->checkGlpiVersion($infos))->isFalse();
            }
        )->isIdenticalTo('This plugin requires GLPI &lt; 9.3.');

       // Test min and max compatibility
        $infos = ['min' => '0.90', 'max' => '9.3'];

        $this->calling($plugin)->getGlpiVersion = '9.2.0';
        $this->boolean($plugin->checkGlpiVersion($infos))->isTrue();

        $this->calling($plugin)->getGlpiVersion = '0.89.0';
        $this->output(
            function () use ($plugin, $infos) {
                $this->boolean($plugin->checkGlpiVersion($infos, true))->isFalse();
            }
        )->isIdenticalTo('This plugin requires GLPI &gt;= 0.90 and &lt; 9.3.');

        $this->calling($plugin)->getGlpiVersion = '9.3.0';
        $this->output(
            function () use ($plugin, $infos) {
                $this->boolean($plugin->checkGlpiVersion($infos))->isFalse();
            }
        )->isIdenticalTo('This plugin requires GLPI &gt;= 0.90 and &lt; 9.3.');
    }

    public function testcheckPhpVersion()
    {
       //$this->constant->PHP_VERSION = '7.1';
        $plugin = new \mock\Plugin();

        $infos = ['min' => '5.6'];
        $this->boolean($plugin->checkPhpVersion($infos))->isTrue();

        $this->calling($plugin)->getPhpVersion = '5.4';
        $this->output(
            function () use ($plugin, $infos) {
                $this->boolean($plugin->checkPhpVersion($infos))->isFalse();
            }
        )->isIdenticalTo('This plugin requires PHP &gt;= 5.6.');

        $this->calling($plugin)->getPhpVersion = '7.1';
        $this->boolean($plugin->checkPhpVersion($infos))->isTrue();

        $this->output(
            function () use ($plugin) {
                $infos = ['min' => '5.6', 'max' => '7.0'];
                $this->boolean($plugin->checkPhpVersion($infos))->isFalse();
            }
        )->isIdenticalTo('This plugin requires PHP &gt;= 5.6 and &lt; 7.0.');

        $infos = ['min' => '5.6', 'max' => '7.2'];
        $this->boolean($plugin->checkPhpVersion($infos))->isTrue();
    }

    public function testCheckPhpExtensions()
    {
        $plugin = new \Plugin();

        $this->output(
            function () use ($plugin) {
                $exts = ['gd' => ['required' => true]];
                $this->boolean($plugin->checkPhpExtensions($exts))->isTrue();
            }
        )->isEmpty();

        $this->output(
            function () use ($plugin) {
                $exts = ['myext' => ['required' => true]];
                $this->boolean($plugin->checkPhpExtensions($exts))->isFalse();
            }
        )->isIdenticalTo('This plugin requires PHP extension myext<br/>');
    }

    public function testCheckGlpiParameters()
    {
        global $CFG_GLPI;

        $params = ['my_param'];

        $plugin = new \Plugin();

        $this->output(
            function () use ($plugin, $params) {
                $this->boolean($plugin->checkGlpiParameters($params))->isFalse();
            }
        )->isIdenticalTo('This plugin requires GLPI parameter my_param<br/>');

        $CFG_GLPI['my_param'] = '';
        $this->output(
            function () use ($plugin, $params) {
                $this->boolean($plugin->checkGlpiParameters($params))->isFalse();
            }
        )->isIdenticalTo('This plugin requires GLPI parameter my_param<br/>');

        $CFG_GLPI['my_param'] = '0';
        $this->output(
            function () use ($plugin, $params) {
                $this->boolean($plugin->checkGlpiParameters($params))->isFalse();
            }
        )->isIdenticalTo('This plugin requires GLPI parameter my_param<br/>');

        $CFG_GLPI['my_param'] = 'abc';
        $this->output(
            function () use ($plugin, $params) {
                $this->boolean($plugin->checkGlpiParameters($params))->isTrue();
            }
        )->isEmpty();
    }

    public function testCheckGlpiPlugins()
    {
        $plugin = new \mock\Plugin();

        $this->calling($plugin)->isInstalled = false;
        $this->calling($plugin)->isActivated = false;

        $this->output(
            function () use ($plugin) {
                $this->boolean($plugin->checkGlpiPlugins(['myplugin']))->isFalse();
            }
        )->isIdenticalTo('This plugin requires myplugin plugin<br/>');

        $this->calling($plugin)->isInstalled = true;

        $this->output(
            function () use ($plugin) {
                $this->boolean($plugin->checkGlpiPlugins(['myplugin']))->isFalse();
            }
        )->isIdenticalTo('This plugin requires myplugin plugin<br/>');

        $this->calling($plugin)->isInstalled = true;
        $this->calling($plugin)->isActivated = true;

        $this->output(
            function () use ($plugin) {
                $this->boolean($plugin->checkGlpiPlugins(['myplugin']))->isTrue();
            }
        )->isEmpty();
    }

    /**
     * Test state checking on an invalid directory corresponding to an unknown plugin.
     * Should have no effect.
     */
    public function testCheckPluginStateForInvalidUnknownPlugin()
    {

        $this->doTestCheckPluginState(null, null, null);
    }

    /**
     * Test state checking on an invalid directory corresponding to a known plugin.
     * Should results in no change in plugin state, as "TOBECLEANED" state is realtime computed.
     */
    public function testCheckPluginStateForInvalidKnownPlugin()
    {

        $initial_data = [
            'directory' => $this->test_plugin_directory,
            'name'      => 'Test plugin',
            'version'   => '1.0',
            'state'     => \Plugin::ACTIVATED,
        ];
        $expected_data = $initial_data;

        $this->doTestCheckPluginState(
            $initial_data,
            null,
            $expected_data,
            'Unable to load plugin "' . $this->test_plugin_directory . '" information.'
        );

       // check also Plugin::isActivated method
        $plugin_inst = new \Plugin();
        $this->boolean($plugin_inst->isActivated($this->test_plugin_directory));
    }

    /**
     * Test state checking on a valid directory corresponding to an unknown plugin.
     * Should results in creating plugin with "NOTINSTALLED" state.
     */
    public function testCheckPluginStateForNewPlugin()
    {

        $setup_informations = [
            'name'      => 'Test plugin',
            'version'   => '1.0',
        ];
        $expected_data = array_merge(
            $setup_informations,
            [
                'directory' => $this->test_plugin_directory,
                'state'     => \Plugin::NOTINSTALLED,
            ]
        );

        $this->doTestCheckPluginState(
            null,
            $setup_informations,
            $expected_data
        );
    }

    /**
     * Test state checking on a valid directory corresponding to an unknown plugin that has a replacement plugin.
     * Should results in creating plugin with "REPLACED" state.
     */
    public function testCheckPluginStateForNewPluginThatHasBeenReplaced()
    {
        // Create files for replacement plugin
        $new_informations = [
            'name'    => 'Test plugin revamped',
            'oldname' => $this->test_plugin_directory,
            'version' => '2.0',
        ];
        $new_directory = $this->anothertest_plugin_directory;
        $this->createTestPluginFiles(
            true,
            $new_informations,
            $new_directory
        );

        // Check plugin state
        $setup_informations = [
            'name'      => 'Old plugin',
            'version'   => '1.0',
        ];
        $expected_data = array_merge(
            $setup_informations,
            [
                'directory' => $this->test_plugin_directory,
                'state'     => \Plugin::REPLACED,
            ]
        );

        $this->doTestCheckPluginState(
            null,
            $setup_informations,
            $expected_data
        );
    }

    /**
     * Test state checking on a valid directory corresponding to a known and installed plugin
     * with a different version.
     * Should results in changing plugin state to "NOTUPDATED".
     */
    public function testCheckPluginStateForInstalledAndUpdatablePlugin()
    {

        $initial_data = [
            'directory' => $this->test_plugin_directory,
            'name'      => 'Test plugin',
            'version'   => '1.0',
            'state'     => \Plugin::ACTIVATED,
        ];
        $setup_informations = [
            'name'    => 'Test plugin NG',
            'version' => '2.0',
        ];
        $expected_data = array_merge(
            $initial_data,
            $setup_informations,
            [
                'state' => \Plugin::NOTUPDATED,
            ]
        );

        $this->doTestCheckPluginState(
            $initial_data,
            $setup_informations,
            $expected_data,
            'Plugin "' . $this->test_plugin_directory . '" version changed. It has been deactivated as its update process has to be launched.'
        );

       // check also Plugin::isUpdatable method
        $plugin_inst = new \Plugin();
        $this->boolean($plugin_inst->isUpdatable($this->test_plugin_directory));
    }

    /**
     * Test state checking on a valid directory corresponding to a known and NOT installed plugin
     * with a different version.
     * Should results in keeping plugin state to "NOTINSTALLED".
     */
    public function testCheckPluginStateForNotInstalledAndUpdatablePlugin()
    {

        $initial_data = [
            'directory' => $this->test_plugin_directory,
            'name'      => 'Test plugin',
            'version'   => '1.0',
            'state'     => \Plugin::NOTINSTALLED,
        ];
        $setup_informations = [
            'name'    => 'Test plugin NG',
            'version' => '2.0',
        ];
        $expected_data = array_merge(
            $initial_data,
            $setup_informations,
            [
                'state' => \Plugin::NOTINSTALLED,
            ]
        );

        $this->doTestCheckPluginState(
            $initial_data,
            $setup_informations,
            $expected_data
        );
    }

    /**
     * Test state checking on a valid directory corresponding to a known and NOT UPDATED plugin
     * with a different version.
     * Should results in keeping plugin state to "NOTUPDATED".
     */
    public function testCheckPluginStateForNotUpdatededAndUpdatablePlugin()
    {

        $initial_data = [
            'directory' => $this->test_plugin_directory,
            'name'      => 'Test plugin',
            'version'   => '1.0',
            'state'     => \Plugin::NOTUPDATED,
        ];
        $setup_informations = [
            'name'    => 'Test plugin NG',
            'version' => '2.0',
        ];
        $expected_data = array_merge(
            $initial_data,
            $setup_informations,
            [
                'state' => \Plugin::NOTUPDATED,
            ]
        );

        $this->doTestCheckPluginState(
            $initial_data,
            $setup_informations,
            $expected_data
        );
    }

    /**
     * Test state checking on a valid directory corresponding to a known plugin that has a replacement plugin.
     * Should results changing state to "REPLACED".
     */
    public function testCheckPluginStateForKnownPluginThatHasBeenReplaced()
    {

        $plugin = new \Plugin();

        // Create files for replacement plugin
        $new_informations = [
            'name'    => 'Test plugin revamped',
            'oldname' => $this->test_plugin_directory,
            'version' => '2.0',
        ];
        $new_directory = $this->anothertest_plugin_directory;
        $this->createTestPluginFiles(
            true,
            $new_informations,
            $new_directory
        );

        // Create initial data in DB
        $old_directory = $this->test_plugin_directory;
        $initial_data = [
            'directory' => $this->test_plugin_directory,
            'name'      => 'Old plugin',
            'version'   => '1.0',
            'state'     => \Plugin::ACTIVATED,
        ];
        $plugin_id = $plugin->add($initial_data);
        $this->integer((int)$plugin_id)->isGreaterThan(0);

        // Create files for original plugin
        $old_information = [
            'name'      => 'Old plugin',
            'version'   => '1.0',
        ];
        $this->createTestPluginFiles(
            true,
            $old_information,
            $old_directory
        );

       // Check state without checking if there is a replacement plugin
        $this->when(
            function () use ($plugin, $old_directory) {
                $plugin->checkPluginState($old_directory);
            }
        )->error()->notExists();

        // Assert old plugin entry has not been updated
        $this->boolean($plugin->getFromDBByCrit(['directory' => $old_directory]))->isTrue();
        $this->string($plugin->fields['directory'])->isIdenticalTo($old_directory);
        $this->string($plugin->fields['name'])->isIdenticalTo($old_information['name']);
        $this->string($plugin->fields['version'])->isIdenticalTo($old_information['version']);
        $this->integer((int)$plugin->fields['state'])->isIdenticalTo(\Plugin::ACTIVATED);

       // Check state and check if there is a replacement plugin
        $this->when(
            function () use ($plugin, $old_directory) {
                $plugin->checkPluginState($old_directory, true);
            }
        )->error()
         ->withType(E_USER_WARNING)
         ->withMessage('Plugin "' . $old_directory . '" has been replaced by "' . $new_directory . '" and therefore has been deactivated.')
            ->exists();

        // Assert old plugin entry has been updated and status set to REPLACED
        $this->boolean($plugin->getFromDBByCrit(['directory' => $old_directory]))->isTrue();
        $this->string($plugin->fields['directory'])->isIdenticalTo($old_directory);
        $this->string($plugin->fields['name'])->isIdenticalTo($old_information['name']);
        $this->string($plugin->fields['version'])->isIdenticalTo($old_information['version']);
        $this->integer((int)$plugin->fields['state'])->isIdenticalTo(\Plugin::REPLACED);
    }

    /**
     * Test state checking on a valid directory corresponding to a known inactive plugin with no modifications.
     * Should results in no changes.
     */
    public function testCheckPluginStateForInactiveAndNotUpdatedPlugin()
    {

        $initial_data = [
            'directory' => $this->test_plugin_directory,
            'name'      => 'Test plugin',
            'version'   => '1.0',
            'state'     => \Plugin::NOTACTIVATED,
        ];
        $setup_informations = [
            'name'      => 'Test plugin',
            'version'   => '1.0',
        ];
        $expected_data = $initial_data;

        $this->doTestCheckPluginState(
            $initial_data,
            $setup_informations,
            $expected_data
        );
    }

    /**
     * Test state checking on a valid directory corresponding to a known inactive plugin with no modifications
     * but not validating config.
     * Should results in changing plugin state to "TOBECONFIGURED".
     */
    public function testCheckPluginStateForInactiveAndNotUpdatedPluginNotValidatingConfig()
    {

        $initial_data = [
            'directory' => $this->test_plugin_directory,
            'name'      => 'Test plugin',
            'version'   => '1.0',
            'state'     => \Plugin::NOTACTIVATED,
        ];
        $setup_informations = [
            'name'    => 'Test plugin',
            'version' => '1.0',
        ];
        $expected_data = array_merge(
            $initial_data,
            [
                'state' => \Plugin::TOBECONFIGURED,
            ]
        );

        $this->function->plugin_test_check_config = false;

        $this->doTestCheckPluginState(
            $initial_data,
            $setup_informations,
            $expected_data,
            'Plugin "' . $this->test_plugin_directory . '" must be configured.'
        );
    }

    /**
     * Test state checking on a valid directory corresponding to a known active plugin with no modifications
     * but not matching versions.
     * Should results in changing plugin state to "NOTACTIVATED".
     */
    public function testCheckPluginStateForActiveAndNotUpdatedPluginNotMatchingVersions()
    {

        $initial_data = [
            'directory' => $this->test_plugin_directory,
            'name'      => 'Test plugin',
            'version'   => '1.0',
            'state'     => \Plugin::ACTIVATED,
        ];
        $setup_informations = [
            'name'         => 'Test plugin',
            'version'      => '1.0',
            'requirements' => [
                'glpi' => [
                    'min' => '15.0',
                ],
            ],
        ];
        $expected_data = array_merge(
            $initial_data,
            [
                'state' => \Plugin::NOTACTIVATED,
            ]
        );

        $this->doTestCheckPluginState(
            $initial_data,
            $setup_informations,
            $expected_data,
            'Plugin "' . $this->test_plugin_directory . '" prerequisites are not matched. It has been deactivated.'
        );

       // check also Plugin::isUpdatable method
        $plugin_inst = new \Plugin();
        $this->boolean($plugin_inst->isUpdatable($this->test_plugin_directory));
    }

    /**
     * Test state checking on a valid directory corresponding to a known active plugin with no modifications
     * but not matching prerequisites.
     * Should results in changing plugin state to "NOTACTIVATED".
     */
    public function testCheckPluginStateForActiveAndNotUpdatedPluginNotMatchingPrerequisites()
    {

        $initial_data = [
            'directory' => $this->test_plugin_directory,
            'name'      => 'Test plugin',
            'version'   => '1.0',
            'state'     => \Plugin::ACTIVATED,
        ];
        $setup_informations = [
            'name'    => 'Test plugin',
            'version' => '1.0',
        ];
        $expected_data = array_merge(
            $initial_data,
            [
                'state' => \Plugin::NOTACTIVATED,
            ]
        );

        $this->function->plugin_test_check_prerequisites = false;

        $this->doTestCheckPluginState(
            $initial_data,
            $setup_informations,
            $expected_data,
            'Plugin "' . $this->test_plugin_directory . '" prerequisites are not matched. It has been deactivated.'
        );
    }

    /**
     * Test state checking on a valid directory corresponding to a known active plugin with no modifications
     * but not validating config.
     * Should results in changing plugin state to "TOBECONFIGURED".
     */
    public function testCheckPluginStateForActiveAndNotUpdatedPluginNotValidatingConfig()
    {

        $initial_data = [
            'directory' => $this->test_plugin_directory,
            'name'      => 'Test plugin',
            'version'   => '1.0',
            'state'     => \Plugin::ACTIVATED,
        ];
        $setup_informations = [
            'name'    => 'Test plugin',
            'version' => '1.0',
        ];
        $expected_data = array_merge(
            $initial_data,
            [
                'state' => \Plugin::TOBECONFIGURED,
            ]
        );

        $this->function->plugin_test_check_config = false;

        $this->doTestCheckPluginState(
            $initial_data,
            $setup_informations,
            $expected_data,
            'Plugin "' . $this->test_plugin_directory . '" must be configured.'
        );
    }

    /**
     * Test state checking on a valid directory corresponding to a known active plugin with no modifications,
     * matching prerequisites and validating config.
     * Should results in no changes.
     */
    public function testCheckPluginStateForActiveAndNotUpdatedPluginMatchingPrerequisitesAndConfig()
    {

        $initial_data = [
            'directory' => $this->test_plugin_directory,
            'name'      => 'Test plugin',
            'version'   => '1.0',
            'state'     => \Plugin::ACTIVATED,
        ];
        $setup_informations = [
            'name'    => 'Test plugin',
            'version' => '1.0',
        ];
        $expected_data = $initial_data;

        $this->function->plugin_test_check_prerequisites = true;
        $this->function->plugin_test_check_config = true;

        $this->doTestCheckPluginState(
            $initial_data,
            $setup_informations,
            $expected_data
        );
    }

    /**
     * Test state checking on a valid directory corresponding to a known active plugin with no modifications
     * having nor check_prerequisites nor check_config function.
     * Should results in no changes.
     */
    public function testCheckPluginStateForActiveAndNotUpdatedPluginHavingNoCheckFunctions()
    {

        $initial_data = [
            'directory' => $this->test_plugin_directory,
            'name'      => 'Test plugin',
            'version'   => '1.0',
            'state'     => \Plugin::ACTIVATED,
        ];
        $setup_informations = [
            'name'    => 'Test plugin',
            'version' => '1.0',
        ];
        $expected_data = $initial_data;

        $this->doTestCheckPluginState(
            $initial_data,
            $setup_informations,
            $expected_data
        );

       // check also Plugin::isActivated method
        $plugin_inst = new \Plugin();
        $this->boolean($plugin_inst->isActivated($this->test_plugin_directory));
    }

    /**
     * Test that state checking on a plugin directory.
     *
     * /!\ Each iteration on this method has to be done on a different test method, unless you change
     * the plugin directory on each time. Not doing this will prevent updating the `init` function of
     * the plugin on each test.
     *
     * @param array|null  $initial_data       Initial data in DB, null for none.
     * @param array|null  $setup_informations Information hosted by setup file, null for none.
     * @param array|null  $expected_data      Expected data in DB, null for none.
     * @param string|null $expected_warning   Expected warning message, null for none.
     *
     * @return void
     */
    private function doTestCheckPluginState($initial_data, $setup_informations, $expected_data, $expected_warning = null)
    {

        $plugin_directory = $this->test_plugin_directory;
        $test_plugin_path = $this->getTestPluginPath($this->test_plugin_directory);
        $plugin           = new \Plugin();

       // Fail if plugin already exists in DB or filesystem, as this is not expected
        $this->boolean($plugin->getFromDBByCrit(['directory' => $plugin_directory]))->isFalse();
        $this->boolean(file_exists($test_plugin_path))->isFalse();

       // Create initial state of plugin
        $plugin_id = null;
        if (null !== $initial_data) {
            $plugin_id = $plugin->add($initial_data);
            $this->integer((int)$plugin_id)->isGreaterThan(0);
        }

       // Create test plugin files
        $this->createTestPluginFiles(
            null !== $setup_informations,
            null !== $setup_informations ? $setup_informations : []
        );

       // Check state
        if (null !== $expected_warning) {
            $this->when(
                function () use ($plugin, $plugin_directory) {
                    $plugin->checkPluginState($plugin_directory);
                }
            )->error()
             ->withType(E_USER_WARNING)
             ->withMessage($expected_warning)
               ->exists();
        } else {
            $plugin->checkPluginState($plugin_directory, true);
        }

       // Assert that data in DB matches expected
        if (null !== $expected_data) {
            $this->boolean($plugin->getFromDBByCrit(['directory' => $plugin_directory]))->isTrue();

            $this->string($plugin->fields['directory'])->isIdenticalTo($expected_data['directory']);
            $this->string($plugin->fields['name'])->isIdenticalTo($expected_data['name']);
            $this->string($plugin->fields['version'])->isIdenticalTo($expected_data['version']);
            $this->integer((int)$plugin->fields['state'])->isIdenticalTo($expected_data['state']);
        } else {
            $this->boolean($plugin->getFromDBByCrit(['directory' => $plugin_directory]))->isFalse();
        }
    }

    /**
     * Returns test plugin files path.
     *
     * @param string $directory
     *
     * @return string
     */
    private function getTestPluginPath($directory)
    {

        return implode(
            DIRECTORY_SEPARATOR,
            [GLPI_ROOT, 'plugins', $directory]
        );
    }

    /**
     * Create test plugin files.
     *
     * @param boolean     $withsetup     Include setup file ?
     * @param array       $informations  Information to put in setup files.
     * @param null|string $directory     Directory where to create files, null to use default location.
     *
     * @return void
     */
    private function createTestPluginFiles($withsetup = true, array $informations = [], $directory = null)
    {

        if (null === $directory) {
            $directory = $this->test_plugin_directory;
        }
        $plugin_path = $this->getTestPluginPath($directory);

        $this->boolean(
            mkdir($plugin_path, 0700, true)
        )->isTrue();

        if ($withsetup) {
            $informations_str = var_export($informations, true);

            $this->variable(
                file_put_contents(
                    implode(DIRECTORY_SEPARATOR, [$plugin_path, 'setup.php']),
                    <<<PHP
<?php
function plugin_version_{$directory}() {
   return {$informations_str};
}
PHP
                )
            )->isNotEqualTo(false);
        }
    }

    public function testGetPluginOptionsWithExpectedResult()
    {
        $key = $this->test_plugin_directory;
        $plugin_path = $this->getTestPluginPath($key);

        $this->boolean(
            mkdir($plugin_path, 0700, true)
        )->isTrue();
        $this->variable(
            file_put_contents(
                implode(DIRECTORY_SEPARATOR, [$plugin_path, 'setup.php']),
                <<<PHP
<?php
function plugin_version_{$key}() {
    return [
        'name'    => 'Test plugin',
        'version' => '1.0',
    ];
}
function plugin_{$key}_options() {
    return [
        'autoinstall_disabled' => true,
        'another_option'       => 'abc',
    ];
}
PHP
            )
        )->isNotEqualTo(false);

        $plugin = new \Plugin();
        $plugin_id = $plugin->add(
            [
                'directory' => $key,
                'name'      => 'Test plugin',
                'version'   => '1.0',
                'state'     => \Plugin::ACTIVATED,
            ]
        );
        $this->integer((int)$plugin_id)->isGreaterThan(0);

        $this->array($plugin->getPluginOptions($key))->isEqualTo(
            [
                'autoinstall_disabled' => true,
                'another_option'       => 'abc',
            ]
        );
    }

    public function testGetPluginOptionsWithoutDeclaredFunction()
    {
        $key = $this->test_plugin_directory;
        $plugin_path = $this->getTestPluginPath($key);

        $this->boolean(
            mkdir($plugin_path, 0700, true)
        )->isTrue();
        $this->variable(
            file_put_contents(
                implode(DIRECTORY_SEPARATOR, [$plugin_path, 'setup.php']),
                <<<PHP
<?php
function plugin_version_{$key}() {
    return [
        'name'    => 'Test plugin',
        'version' => '1.0',
    ];
}
PHP
            )
        )->isNotEqualTo(false);

        $plugin = new \Plugin();
        $plugin_id = $plugin->add(
            [
                'directory' => $key,
                'name'      => 'Test plugin',
                'version'   => '1.0',
                'state'     => \Plugin::ACTIVATED,
            ]
        );
        $this->integer((int)$plugin_id)->isGreaterThan(0);

        $this->array($plugin->getPluginOptions($key))->isEqualTo([]);
    }

    public function testGetPluginOptionsWithUnexpectedResult()
    {
        $key = $this->test_plugin_directory;
        $plugin_path = $this->getTestPluginPath($key);

        $this->boolean(
            mkdir($plugin_path, 0700, true)
        )->isTrue();
        $this->variable(
            file_put_contents(
                implode(DIRECTORY_SEPARATOR, [$plugin_path, 'setup.php']),
                <<<PHP
<?php
function plugin_version_{$key}() {
    return [
        'name'    => 'Test plugin',
        'version' => '1.0',
    ];
}
function plugin_{$key}_options() {
    return 'malformed result';
}
PHP
            )
        )->isNotEqualTo(false);

        $plugin = new \Plugin();
        $plugin_id = $plugin->add(
            [
                'directory' => $key,
                'name'      => 'Test plugin',
                'version'   => '1.0',
                'state'     => \Plugin::ACTIVATED,
            ]
        );
        $this->integer((int)$plugin_id)->isGreaterThan(0);

        $result = null;
        $this->when(
            function () use ($plugin, $key, &$result) {
                $result = $plugin->getPluginOptions($key);
            }
        )->error()
         ->withType(E_USER_WARNING)
         ->withMessage(sprintf('Invalid "options" key provided by plugin `plugin_%s_options()` method.', $key))
            ->exists();

        $this->array($result)->isEqualTo([]);
    }

    public function testGetPluginOptionsOnUnexistingPlugin()
    {
        $plugin = new \Plugin();
        $this->array($plugin->getPluginOptions('thisplugindoesnotexists'))->isEqualTo([]);
    }

    protected function pluginDirectoryProvider(): iterable
    {
        yield [
            'directory' => 'MyAwesomePlugin',
            'is_valid'  => true,
        ];

        yield [
            'directory' => 'My4wes0mePlugin',
            'is_valid'  => true,
        ];

        yield [
            'directory' => '',
            'is_valid'  => false,
        ];

        yield [
            'directory' => 'My-Plugin', // - is not valid
            'is_valid'  => false,
        ];

        yield [
            'directory' => 'мійплагін', // only latin chars are accepted
            'is_valid'  => false,
        ];

        yield [
            'directory' => '../../anotherapp',
            'is_valid'  => false,
        ];
    }

    protected function inputProvider(): iterable
    {
        foreach ($this->pluginDirectoryProvider() as $specs) {
            $case = [
                'input'     => [
                    'directory' => $specs['directory']
                ],
            ];
            if ($specs['is_valid']) {
                $case['result'] = $case['input'];
            } else {
                $case['result'] = false;
                $case['messages'] = ['Invalid plugin directory'];
            }

            yield $case;
        }
    }

    protected function addInputProvider(): iterable
    {
        yield from $this->inputProvider();

        yield [
            'input'     => [
            ],
            'result'    => false,
            'messages'  => [
                'Invalid plugin directory',
            ],
        ];
    }

    /**
     * @dataProvider addInputProvider
     */
    public function testPrepareInputForAdd(array $input, /* array|false */ $result, array $messages = []): void
    {
        $this->variable($this->newTestedInstance()->prepareInputForAdd($input))->isEqualTo($result);

        if (count($messages) > 0) {
            $this->hasSessionMessages(ERROR, $messages);
        }
    }

    /**
     * @dataProvider inputProvider
     */
    public function testPrepareInputForUpdate(array $input, /* array|false */ $result, array $messages = []): void
    {
        $this->variable($this->newTestedInstance()->prepareInputForAdd($input))->isEqualTo($result);

        if (count($messages) > 0) {
            $this->hasSessionMessages(ERROR, $messages);
        }
    }
}
