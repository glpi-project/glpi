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

namespace tests\units {

    use AppendIterator;
    use DbTestCase;
    use DirectoryIterator;
    use Error;
    use Glpi\Toolbox\VersionParser;
    use org\bovigo\vfs\vfsStream;
    use PHPUnit\Framework\Attributes\DataProvider;
    use PHPUnit\Framework\Attributes\RunInSeparateProcess;
    use Plugin;
    use Random\RandomException;

    class PluginTest extends DbTestCase
    {
        private $test_plugin_directory = 'test';
        public static bool $plugin_test_check_config = true;
        public static bool $plugin_test_check_prerequisites = true;

        public function setUp(): void
        {
            parent::setUp();
            self::$plugin_test_check_config = true;
            self::$plugin_test_check_prerequisites = true;

            vfsStream::setup('glpi', null, [
                'plugins' => [],
            ]);
        }

        public function tearDown(): void
        {
            parent::tearDown();
        }

        /**
         * @return string a randomized plugin directory name composed only of lowercase letters
         * @throws RandomException
         */
        private function getRandomPluginName()
        {
            $name = '';
            for ($i = 0; $i < 15; $i++) {
                $name .= chr(random_int(97, 122));
            }
            return $name;
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
            return vfsStream::url('glpi/plugins/' . $directory);
        }

        public function testGetWebDir(): void
        {
            global $CFG_GLPI;

            $this->assertEquals('plugins/tester', @Plugin::getWebDir('tester', false, false));
            $this->assertEquals('marketplace/myplugin', @Plugin::getWebDir('myplugin', false, false));

            foreach (['', '/glpi', '/path/to/app'] as $root_doc) {
                $CFG_GLPI['root_doc'] = $root_doc;
                $this->assertEquals($root_doc . '/plugins/tester', @Plugin::getWebDir('tester', true, false));
                $this->assertEquals($root_doc . '/marketplace/myplugin', @Plugin::getWebDir('myplugin', true, false));
            }

            foreach (['http://localhost', 'https://www.example.org/glpi'] as $url_base) {
                $CFG_GLPI['url_base'] = $url_base;
                $this->assertEquals($url_base . '/plugins/tester', @Plugin::getWebDir('tester', true, true));
                $this->assertEquals($url_base . '/marketplace/myplugin', @Plugin::getWebDir('myplugin', true, true));
            }

            $this->assertFalse(@Plugin::getWebDir('notaplugin', true, true));
        }

        public function testGetGlpiVersion()
        {
            $plugin = new Plugin();
            $this->assertEquals(VersionParser::getNormalizedVersion(GLPI_VERSION, false), $plugin->getGlpiVersion());
        }

        public function testCheckGlpiVersion()
        {
            // Test min compatibility
            $infos = ['min' => '0.90'];
            $this->assertTrue($this->getPluginMock(['_mock_getGlpiVersion' => '9.2.0'])->checkGlpiVersion($infos));
            ob_start();
            $this->assertFalse($this->getPluginMock(['_mock_getGlpiVersion' => '0.89.0'])->checkGlpiVersion($infos));
            $this->assertEquals('This plugin requires GLPI >= 0.90.', trim(ob_get_clean()));

            // Test max compatibility
            $infos = ['max' => '9.3'];
            $this->assertTrue($this->getPluginMock(['_mock_getGlpiVersion' => '9.2.0'])->checkGlpiVersion($infos));
            ob_start();
            $this->assertFalse($this->getPluginMock(['_mock_getGlpiVersion' => '9.3.0'])->checkGlpiVersion($infos));
            $this->assertEquals('This plugin requires GLPI < 9.3.', trim(ob_get_clean()));

            // Test min and max compatibility
            $infos = ['min' => '0.90', 'max' => '9.3'];
            $this->assertTrue($this->getPluginMock(['_mock_getGlpiVersion' => '9.2.0'])->checkGlpiVersion($infos));
            ob_start();
            $this->assertFalse($this->getPluginMock(['_mock_getGlpiVersion' => '0.89.0'])->checkGlpiVersion($infos));
            $this->assertEquals('This plugin requires GLPI >= 0.90 and < 9.3.', trim(ob_get_clean()));
            ob_start();
            $this->assertFalse($this->getPluginMock(['_mock_getGlpiVersion' => '9.3.0'])->checkGlpiVersion($infos));
            $this->assertEquals('This plugin requires GLPI >= 0.90 and < 9.3.', trim(ob_get_clean()));
        }

        public function testcheckPhpVersion()
        {
            $infos = ['min' => '5.6'];
            $this->assertTrue($this->getPluginMock()->checkPhpVersion($infos));
            ob_start();
            $this->assertFalse($this->getPluginMock(['_mock_getPhpVersion' => '5.4'])->checkPhpVersion($infos));
            $this->assertEquals('This plugin requires PHP >= 5.6.', trim(ob_get_clean()));

            $this->assertTrue($this->getPluginMock(['_mock_getPhpVersion' => '7.1'])->checkPhpVersion($infos));

            $infos = ['min' => '5.6', 'max' => '7.0'];
            ob_start();
            $this->assertFalse($this->getPluginMock(['_mock_getPhpVersion' => '7.1'])->checkPhpVersion($infos));
            $this->assertEquals('This plugin requires PHP >= 5.6 and < 7.0.', trim(ob_get_clean()));

            $infos = ['min' => '5.6', 'max' => '7.2'];
            $this->assertTrue($this->getPluginMock(['_mock_getPhpVersion' => '7.1'])->checkPhpVersion($infos));
        }

        public function testCheckPhpExtensions()
        {
            $plugin = new Plugin();

            ob_start();
            $this->assertTrue($plugin->checkPhpExtensions(['gd' => ['required' => true]]));
            $this->assertEmpty(ob_get_clean());

            ob_start();
            $this->assertFalse($plugin->checkPhpExtensions(['myext' => ['required' => true]]));
            $this->assertEquals('This plugin requires PHP extension myext', trim(ob_get_clean()));
        }

        public function testCheckGlpiParameters()
        {
            global $CFG_GLPI;

            $params = ['my_param'];

            $plugin = new Plugin();

            ob_start();
            $this->assertFalse($plugin->checkGlpiParameters($params));
            $this->assertEquals('This plugin requires GLPI parameter my_param', trim(ob_get_clean()));

            $CFG_GLPI['my_param'] = '';
            ob_start();
            $this->assertFalse($plugin->checkGlpiParameters($params));
            $this->assertEquals('This plugin requires GLPI parameter my_param', trim(ob_get_clean()));

            $CFG_GLPI['my_param'] = '0';
            ob_start();
            $this->assertFalse($plugin->checkGlpiParameters($params));
            $this->assertEquals('This plugin requires GLPI parameter my_param', trim(ob_get_clean()));

            $CFG_GLPI['my_param'] = 'abc';
            ob_start();
            $this->assertTrue($plugin->checkGlpiParameters($params));
            $this->assertEmpty(ob_get_clean());
        }

        public function testCheckGlpiPlugins()
        {
            $plugin = $this->getPluginMock([
                '_mock_isInstalled' => false,
                '_mock_isActivated' => false,
            ]);
            ob_start();
            $this->assertFalse($plugin->checkGlpiPlugins(['myplugin']));
            $this->assertEquals('This plugin requires myplugin plugin', trim(ob_get_clean()));

            $plugin = $this->getPluginMock([
                '_mock_isInstalled' => true,
                '_mock_isActivated' => false,
            ]);
            ob_start();
            $this->assertFalse($plugin->checkGlpiPlugins(['myplugin']));
            $this->assertEquals('This plugin requires myplugin plugin', trim(ob_get_clean()));

            $plugin = $this->getPluginMock([
                '_mock_isInstalled' => true,
                '_mock_isActivated' => true,
            ]);
            ob_start();
            $this->assertTrue($plugin->checkGlpiPlugins(['myplugin']));
            $this->assertEmpty(ob_get_clean());
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
            $directory = $this->getRandomPluginName();
            $initial_data = [
                'directory' => $directory,
                'name' => 'Test plugin',
                'version' => '1.0',
                'state' => Plugin::ACTIVATED,
            ];
            $expected_data = $initial_data;

            $this->doTestCheckPluginState(
                $initial_data,
                null,
                $expected_data,
                'Unable to load plugin "' . $directory . '" information.',
                $directory
            );

            // check also Plugin::isActivated method
            $plugin_inst = new Plugin();
            $this->assertFalse($plugin_inst->isActivated($directory));
        }

        /**
         * Test state checking on a valid directory corresponding to an unknown plugin.
         * Should results in creating plugin with "NOTINSTALLED" state.
         */
        public function testCheckPluginStateForNewPlugin()
        {
            $directory = $this->getRandomPluginName();
            $setup_informations = [
                'name' => 'Test plugin',
                'version' => '1.0',
            ];
            $expected_data = array_merge(
                $setup_informations,
                [
                    'directory' => $directory,
                    'state' => Plugin::NOTINSTALLED,
                ]
            );

            $this->doTestCheckPluginState(
                initial_data: null,
                setup_informations: $setup_informations,
                expected_data: $expected_data,
                plugin_directory: $directory
            );
        }

        /**
         * Test state checking on a valid directory corresponding to an unknown plugin that has a replacement plugin.
         * Should results in creating plugin with "REPLACED" state.
         */
        public function testCheckPluginStateForNewPluginThatHasBeenReplaced()
        {
            $old_directory = $this->getRandomPluginName();
            $new_directory = $this->getRandomPluginName();
            // Create files for replacement plugin
            $new_informations = [
                'name' => 'Test plugin revamped',
                'oldname' => $old_directory,
                'version' => '2.0',
            ];
            $this->createTestPluginFiles(
                true,
                $new_informations,
                $new_directory
            );

            // Check plugin state
            $setup_informations = [
                'name' => 'Old plugin',
                'version' => '1.0',
            ];
            $expected_data = array_merge(
                $setup_informations,
                [
                    'directory' => $old_directory,
                    'state' => Plugin::REPLACED,
                ]
            );

            $this->doTestCheckPluginState(
                initial_data: null,
                setup_informations: $setup_informations,
                expected_data: $expected_data,
                plugin_directory: $old_directory
            );
        }

        /**
         * Test state checking on a valid directory corresponding to a known and installed plugin
         * with a different version.
         * Should results in changing plugin state to "NOTUPDATED".
         */
        public function testCheckPluginStateForInstalledAndUpdatablePlugin()
        {
            $directory = $this->getRandomPluginName();
            $initial_data = [
                'directory' => $directory,
                'name' => 'Test plugin',
                'version' => '1.0',
                'state' => Plugin::ACTIVATED,
            ];
            $setup_informations = [
                'name' => 'Test plugin NG',
                'version' => '2.0',
            ];
            $expected_data = array_merge(
                $initial_data,
                $setup_informations,
                [
                    'state' => Plugin::NOTUPDATED,
                ]
            );

            $this->doTestCheckPluginState(
                $initial_data,
                $setup_informations,
                $expected_data,
                'Plugin "' . $directory . '" version changed. It has been deactivated as its update process has to be launched.',
                $directory
            );

            // check also Plugin::isUpdatable method
            $plugin_inst = $this->getPluginMock();
            $this->assertTrue($plugin_inst->isUpdatable($directory));
        }

        /**
         * Test state checking on a valid directory corresponding to a known and NOT installed plugin
         * with a different version.
         * Should results in keeping plugin state to "NOTINSTALLED".
         */
        public function testCheckPluginStateForNotInstalledAndUpdatablePlugin()
        {
            $directory = $this->getRandomPluginName();
            $initial_data = [
                'directory' => $directory,
                'name' => 'Test plugin',
                'version' => '1.0',
                'state' => Plugin::NOTINSTALLED,
            ];
            $setup_informations = [
                'name' => 'Test plugin NG',
                'version' => '2.0',
            ];
            $expected_data = array_merge(
                $initial_data,
                $setup_informations,
                [
                    'state' => Plugin::NOTINSTALLED,
                ]
            );

            $this->doTestCheckPluginState(
                initial_data: $initial_data,
                setup_informations: $setup_informations,
                expected_data: $expected_data,
                plugin_directory: $directory
            );
        }

        /**
         * Test state checking on a valid directory corresponding to a known and NOT UPDATED plugin
         * with a different version.
         * Should results in keeping plugin state to "NOTUPDATED".
         */
        public function testCheckPluginStateForNotUpdatededAndUpdatablePlugin()
        {
            $directory = $this->getRandomPluginName();
            $initial_data = [
                'directory' => $directory,
                'name' => 'Test plugin',
                'version' => '1.0',
                'state' => Plugin::NOTUPDATED,
            ];
            $setup_informations = [
                'name' => 'Test plugin NG',
                'version' => '2.0',
            ];
            $expected_data = array_merge(
                $initial_data,
                $setup_informations,
                [
                    'state' => Plugin::NOTUPDATED,
                ]
            );

            $this->doTestCheckPluginState(
                initial_data: $initial_data,
                setup_informations: $setup_informations,
                expected_data: $expected_data,
                plugin_directory: $directory
            );
        }

        /**
         * Test state checking on a valid directory corresponding to a known plugin that has a replacement plugin.
         * Should results changing state to "REPLACED".
         */
        public function testCheckPluginStateForKnownPluginThatHasBeenReplaced()
        {
            $plugin = $this->getPluginMock();
            $old_directory = $this->getRandomPluginName();
            $new_directory = $this->getRandomPluginName();

            // Create files for replacement plugin
            $new_informations = [
                'name' => 'Test plugin revamped',
                'oldname' => $old_directory,
                'version' => '2.0',
            ];
            $this->createTestPluginFiles(
                true,
                $new_informations,
                $new_directory
            );

            // Create initial data in DB
            $initial_data = [
                'directory' => $old_directory,
                'name' => 'Old plugin',
                'version' => '1.0',
                'state' => Plugin::ACTIVATED,
            ];
            $plugin_id = $plugin->add($initial_data);
            $this->assertGreaterThan(0, (int) $plugin_id);

            // Create files for original plugin
            $old_information = [
                'name' => 'Old plugin',
                'version' => '1.0',
            ];
            $this->createTestPluginFiles(
                true,
                $old_information,
                $old_directory
            );

            $errors = [];
            // PHPUnit won't let us expect errors so we need our own handler
            set_error_handler(static function ($code, $message) use (&$errors) {
                $errors[] = new Error($message, $code);
            }, E_USER_WARNING);

            // Check state without checking if there is a replacement plugin
            $plugin->checkPluginState($old_directory);
            $this->assertEmpty($errors);

            // Assert old plugin entry has not been updated
            $this->assertTrue($plugin->getFromDBByCrit(['directory' => $old_directory]));
            $this->assertEquals($old_directory, $plugin->fields['directory']);
            $this->assertEquals($old_information['name'], $plugin->fields['name']);
            $this->assertEquals($old_information['version'], $plugin->fields['version']);
            $this->assertEquals(Plugin::ACTIVATED, (int) $plugin->fields['state']);

            // Check state and check if there is a replacement plugin
            $plugin->checkPluginState($old_directory, true);
            $this->assertCount(1, $errors);
            $this->assertEquals(E_USER_WARNING, $errors[0]->getCode());
            $this->assertEquals(
                'Plugin "' . $old_directory . '" has been replaced by "' . $new_directory . '" and therefore has been deactivated.',
                $errors[0]->getMessage()
            );
            restore_error_handler();

            // Assert old plugin entry has been updated and status set to REPLACED
            $this->assertTrue($plugin->getFromDBByCrit(['directory' => $old_directory]));
            $this->assertEquals($old_directory, $plugin->fields['directory']);
            $this->assertEquals($old_information['name'], $plugin->fields['name']);
            $this->assertEquals($old_information['version'], $plugin->fields['version']);
            $this->assertEquals(Plugin::REPLACED, (int) $plugin->fields['state']);
        }

        /**
         * Test state checking on a valid directory corresponding to a known inactive plugin with no modifications.
         * Should results in no changes.
         */
        public function testCheckPluginStateForInactiveAndNotUpdatedPlugin()
        {
            $directory = $this->getRandomPluginName();
            $initial_data = [
                'directory' => $directory,
                'name' => 'Test plugin',
                'version' => '1.0',
                'state' => Plugin::NOTACTIVATED,
            ];
            $setup_informations = [
                'name' => 'Test plugin',
                'version' => '1.0',
            ];
            $expected_data = $initial_data;

            $this->doTestCheckPluginState(
                initial_data: $initial_data,
                setup_informations: $setup_informations,
                expected_data: $expected_data,
                plugin_directory: $directory
            );
        }

        /**
         * Test state checking on a valid directory corresponding to a known inactive plugin with no modifications
         * but not validating config.
         * Should results in changing plugin state to "TOBECONFIGURED".
         */
        #[RunInSeparateProcess]
        public function testCheckPluginStateForInactiveAndNotUpdatedPluginNotValidatingConfig()
        {
            $initial_data = [
                'directory' => $this->test_plugin_directory,
                'name' => 'Test plugin',
                'version' => '1.0',
                'state' => Plugin::NOTACTIVATED,
            ];
            $setup_informations = [
                'name' => 'Test plugin',
                'version' => '1.0',
            ];
            $expected_data = array_merge(
                $initial_data,
                [
                    'state' => Plugin::TOBECONFIGURED,
                ]
            );

            self::$plugin_test_check_config = false;

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
            $directory = $this->getRandomPluginName();
            $initial_data = [
                'directory' => $directory,
                'name' => 'Test plugin',
                'version' => '1.0',
                'state' => Plugin::ACTIVATED,
            ];
            $setup_informations = [
                'name' => 'Test plugin',
                'version' => '1.0',
                'requirements' => [
                    'glpi' => [
                        'min' => '15.0',
                    ],
                ],
            ];
            $expected_data = array_merge(
                $initial_data,
                [
                    'state' => Plugin::NOTACTIVATED,
                ]
            );

            $this->doTestCheckPluginState(
                initial_data: $initial_data,
                setup_informations: $setup_informations,
                expected_data: $expected_data,
                expected_warning: 'Plugin "' . $directory . '" prerequisites are not matched. It has been deactivated.',
                plugin_directory: $directory
            );

            // check also Plugin::isUpdatable method
            $plugin_inst = $this->getPluginMock();
            $this->assertFalse($plugin_inst->isUpdatable($directory));
        }

        /**
         * Test state checking on a valid directory corresponding to a known active plugin with no modifications
         * but not matching prerequisites.
         * Should results in changing plugin state to "NOTACTIVATED".
         */
        #[RunInSeparateProcess]
        public function testCheckPluginStateForActiveAndNotUpdatedPluginNotMatchingPrerequisites()
        {
            $initial_data = [
                'directory' => $this->test_plugin_directory,
                'name' => 'Test plugin',
                'version' => '1.0',
                'state' => Plugin::ACTIVATED,
            ];
            $setup_informations = [
                'name' => 'Test plugin',
                'version' => '1.0',
            ];
            $expected_data = array_merge(
                $initial_data,
                [
                    'state' => Plugin::NOTACTIVATED,
                ]
            );

            self::$plugin_test_check_prerequisites = false;

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
        #[RunInSeparateProcess]
        public function testCheckPluginStateForActiveAndNotUpdatedPluginNotValidatingConfig()
        {
            $initial_data = [
                'directory' => $this->test_plugin_directory,
                'name' => 'Test plugin',
                'version' => '1.0',
                'state' => Plugin::ACTIVATED,
            ];
            $setup_informations = [
                'name' => 'Test plugin',
                'version' => '1.0',
            ];
            $expected_data = array_merge(
                $initial_data,
                [
                    'state' => Plugin::TOBECONFIGURED,
                ]
            );

            self::$plugin_test_check_config = false;

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
        #[RunInSeparateProcess]
        public function testCheckPluginStateForActiveAndNotUpdatedPluginMatchingPrerequisitesAndConfig()
        {
            $initial_data = [
                'directory' => $this->test_plugin_directory,
                'name' => 'Test plugin',
                'version' => '1.0',
                'state' => Plugin::ACTIVATED,
            ];
            $setup_informations = [
                'name' => 'Test plugin',
                'version' => '1.0',
            ];
            $expected_data = $initial_data;

            self::$plugin_test_check_prerequisites = true;
            self::$plugin_test_check_config = true;

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
            $directory = $this->getRandomPluginName();
            $initial_data = [
                'directory' => $directory,
                'name' => 'Test plugin',
                'version' => '1.0',
                'state' => Plugin::ACTIVATED,
            ];
            $setup_informations = [
                'name' => 'Test plugin',
                'version' => '1.0',
            ];
            $expected_data = $initial_data;

            $this->doTestCheckPluginState(
                initial_data: $initial_data,
                setup_informations: $setup_informations,
                expected_data: $expected_data,
                plugin_directory: $directory
            );

            // check also Plugin::isActivated method
            // Plugins are not initialized, so the setup file is tried to be loaded which doesn't exist. Therefore, the plugin is not considered as activated.
            $plugin_inst = $this->getPluginMock();
            $this->assertFalse($plugin_inst->isActivated($directory));
        }

        public function testGetPluginOptionsWithExpectedResult()
        {
            $key = $this->test_plugin_directory;
            $plugin_path = $this->getTestPluginPath($key);

            $this->assertTrue(mkdir($plugin_path, 0o700, true));
            $this->assertNotFalse(
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
            );

            $plugin = $this->getPluginMock();
            $plugin_id = $plugin->add(
                [
                    'directory' => $key,
                    'name' => 'Test plugin',
                    'version' => '1.0',
                    'state' => Plugin::ACTIVATED,
                ]
            );
            $this->assertGreaterThan(0, (int) $plugin_id);

            $this->assertEquals(
                [
                    'autoinstall_disabled' => true,
                    'another_option' => 'abc',
                ],
                $plugin->getPluginOptions($key)
            );
        }

        public function testGetPluginOptionsWithoutDeclaredFunction()
        {
            $key = $this->test_plugin_directory;
            $plugin_path = $this->getTestPluginPath($key);

            $this->assertTrue(mkdir($plugin_path, 0o700, true));
            $this->assertNotFalse(
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
            );

            $plugin = new Plugin();
            $plugin_id = $plugin->add(
                [
                    'directory' => $key,
                    'name' => 'Test plugin',
                    'version' => '1.0',
                    'state' => Plugin::ACTIVATED,
                ]
            );
            $this->assertGreaterThan(0, (int) $plugin_id);
            $this->assertEmpty($plugin->getPluginOptions($key));
        }

        public function testGetPluginOptionsWithUnexpectedResult()
        {
            $directory = $this->getRandomPluginName();
            $plugin_path = $this->getTestPluginPath($directory);

            $this->assertTrue(mkdir($plugin_path, 0o700, true));
            $this->assertNotFalse(
                file_put_contents(
                    implode(DIRECTORY_SEPARATOR, [$plugin_path, 'setup.php']),
                    <<<PHP
<?php
function plugin_version_{$directory}() {
    return [
        'name'    => 'Test plugin',
        'version' => '1.0',
    ];
}
function plugin_{$directory}_options() {
    return 'malformed result';
}
PHP
                )
            );

            $plugin = $this->getPluginMock();
            $plugin_id = $plugin->add(
                [
                    'directory' => $directory,
                    'name' => 'Test plugin',
                    'version' => '1.0',
                    'state' => Plugin::ACTIVATED,
                ]
            );
            $this->assertGreaterThan(0, (int) $plugin_id);

            $result = null;
            $errors = [];

            set_error_handler(static function ($code, $message) use (&$errors) {
                $errors[] = new Error($message, $code);
            }, E_USER_WARNING);
            $result = $plugin->getPluginOptions($directory);
            restore_error_handler();
            $this->assertCount(1, $errors);
            $this->assertEquals(E_USER_WARNING, $errors[0]->getCode());
            $this->assertEquals(
                sprintf('Invalid "options" key provided by plugin `plugin_%s_options()` method.', $directory),
                $errors[0]->getMessage()
            );

            $this->assertEmpty($result);
        }

        public function testGetPluginOptionsOnUnexistingPlugin()
        {
            $plugin = new Plugin();
            $this->assertEmpty($plugin->getPluginOptions('thisplugindoesnotexists'));
        }

        protected static function pluginDirectoryProvider(): iterable
        {
            yield [
                'directory' => 'MyAwesomePlugin',
                'is_valid' => true,
            ];

            yield [
                'directory' => 'My4wes0mePlugin',
                'is_valid' => true,
            ];

            yield [
                'directory' => '',
                'is_valid' => false,
            ];

            yield [
                'directory' => 'My-Plugin', // - is not valid
                'is_valid' => false,
            ];

            yield [
                'directory' => 'мійплагін', // only latin chars are accepted
                'is_valid' => false,
            ];

            yield [
                'directory' => '../../anotherapp',
                'is_valid' => false,
            ];
        }

        public static function inputProvider(): iterable
        {
            foreach (self::pluginDirectoryProvider() as $specs) {
                $case = [
                    'input' => [
                        'directory' => $specs['directory'],
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

        public static function addInputProvider(): iterable
        {
            yield from self::inputProvider();

            yield [
                'input' => [
                ],
                'result' => false,
                'messages' => [
                    'Invalid plugin directory',
                ],
            ];
        }

        #[DataProvider('addInputProvider')]
        public function testPrepareInputForAdd(array $input, /* array|false */ $result, array $messages = []): void
        {
            $plugin = new Plugin();
            $this->assertEquals($result, $plugin->prepareInputForAdd($input));

            if (count($messages) > 0) {
                $this->hasSessionMessages(ERROR, $messages);
            }
        }

        #[DataProvider('inputProvider')]
        public function testPrepareInputForUpdate(array $input, /* array|false */ $result, array $messages = []): void
        {
            $plugin = new Plugin();
            $this->assertEquals($result, $plugin->prepareInputForAdd($input));

            if (count($messages) > 0) {
                $this->hasSessionMessages(ERROR, $messages);
            }
        }

        /**
         * Test that state checking on a plugin directory.
         *
         * /!\ Each iteration on this method has to be done on a different test method, unless you change
         * the plugin directory on each time. Not doing this will prevent updating the `init` function of
         * the plugin on each test.
         *
         * @param array|null $initial_data Initial data in DB, null for none.
         * @param array|null $setup_informations Information hosted by setup file, null for none.
         * @param array|null $expected_data Expected data in DB, null for none.
         * @param string|null $expected_warning Expected warning message, null for none.
         *
         * @return void
         */
        private function doTestCheckPluginState($initial_data, $setup_informations, $expected_data, $expected_warning = null, $plugin_directory = null)
        {
            $plugin_directory ??= $this->test_plugin_directory;
            $test_plugin_path = $this->getTestPluginPath($plugin_directory);
            $plugin = $this->getPluginMock();

            // Fail if plugin already exists in DB or filesystem, as this is not expected
            $this->assertFalse($plugin->getFromDBByCrit(['directory' => $plugin_directory]));
            $this->assertFileDoesNotExist($test_plugin_path);

            // Create initial state of plugin
            if (null !== $initial_data) {
                $plugin_id = $plugin->add($initial_data);
                $this->assertGreaterThan(0, (int) $plugin_id);
            }

            // Create test plugin files
            $this->createTestPluginFiles(
                withsetup: null !== $setup_informations,
                informations: $setup_informations ?? [],
                directory: $plugin_directory
            );

            // Check state
            if (null !== $expected_warning) {
                $errors = [];
                // PHPUnit won't let us expect errors so we need our own handler
                set_error_handler(static function ($errno, $errstr) use (&$errors) {
                    $errors[] = $errstr;
                }, E_USER_WARNING);

                $plugin->checkPluginState($plugin_directory);
                restore_error_handler();
                $this->assertCount(1, $errors);
                $this->assertEquals($expected_warning, $errors[0]);
            } else {
                $plugin->checkPluginState($plugin_directory, true);
            }

            // Assert that data in DB matches expected
            if (null !== $expected_data) {
                $this->assertTrue($plugin->getFromDBByCrit(['directory' => $plugin_directory]));

                $this->assertEquals($expected_data['directory'], $plugin->fields['directory']);
                $this->assertEquals($expected_data['name'], $plugin->fields['name']);
                $this->assertEquals($expected_data['version'], $plugin->fields['version']);
                $this->assertEquals($expected_data['state'], (int) $plugin->fields['state']);
            } else {
                $this->assertFalse($plugin->getFromDBByCrit(['directory' => $plugin_directory]));
            }
        }

        /**
         * Create test plugin files.
         *
         * @param boolean $withsetup Include setup file ?
         * @param array $informations Information to put in setup files.
         * @param null|string $directory Directory where to create files, null to use default location.
         *
         * @return void
         */
        private function createTestPluginFiles($withsetup = true, array $informations = [], $directory = null)
        {
            $directory ??= $this->test_plugin_directory;
            $plugin_path = $this->getTestPluginPath($directory);

            $this->assertTrue(mkdir($plugin_path, 0o700, true));

            if ($withsetup) {
                $informations_str = var_export($informations, true);

                $this->assertNotFalse(
                    file_put_contents(
                        implode(DIRECTORY_SEPARATOR, [$plugin_path, 'setup.php']),
                        <<<PHP
<?php
function plugin_version_{$directory}() {
   return {$informations_str};
}
PHP
                    )
                );
            }
        }

        private function getPluginMock(array $options = [])
        {
            $plugin = new class extends Plugin {
                public array $_mock_options;

                public function getGlpiVersion()
                {
                    return $this->_mock_options['_mock_getGlpiVersion'] ?? parent::getGlpiVersion();
                }

                public function getPhpVersion()
                {
                    return $this->_mock_options['_mock_getPhpVersion'] ?? parent::getPhpVersion();
                }

                public function isInstalled($directory)
                {
                    return $this->_mock_options['_mock_isInstalled'] ?? parent::isInstalled($directory);
                }

                public function isActivated($directory)
                {
                    return $this->_mock_options['_mock_isActivated'] ?? parent::isActivated($directory);
                }

                protected static function getPluginDirectories(): array
                {
                    return [vfsStream::url('glpi/plugins')];
                }

                /**
                 * Similar to the parent method but without using `getRealPath` due to vfsStream limitations.
                 * @return array
                 */
                protected function getFilesystemPluginKeys(): array
                {
                    $filesystem_plugin_keys = [];
                    $plugins_directories = new AppendIterator();
                    foreach (static::getPluginDirectories() as $base_dir) {
                        if (!is_dir($base_dir)) {
                            continue;
                        }
                        $plugins_directories->append(new DirectoryIterator($base_dir));
                    }

                    foreach ($plugins_directories as $plugin_directory) {
                        if (
                            str_starts_with($plugin_directory->getFilename(), '.') // ignore hidden files
                            || !$plugin_directory->isDir()
                        ) {
                            continue;
                        }

                        $filesystem_plugin_keys[] = $plugin_directory->getFilename();
                    }

                    return $filesystem_plugin_keys;
                }
            };

            $plugin->_mock_options = $options;

            return $plugin;
        }
    }
}

namespace {
    use tests\units\PluginTest;

    function plugin_test_check_config()
    {
        return PluginTest::$plugin_test_check_config ?? true;
    }

    function plugin_test_check_prerequisites()
    {
        return PluginTest::$plugin_test_check_prerequisites ?? true;
    }
}
