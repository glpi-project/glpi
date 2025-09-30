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

namespace tests\units\Glpi\Application;

use Glpi\Application\ImportMapGenerator;
use GLPITestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\SimpleCache\CacheInterface;

/**
 * Tests for the ImportMapGenerator class
 */
class ImportMapGeneratorTest extends GLPITestCase
{
    /**
     * Root URL for the import map
     */
    private const ROOT_URL = '/glpi';

    /**
     * Setup a basic virtual filesystem for testing
     *
     * @return vfsStreamDirectory The configured virtual filesystem
     */
    private function setupBasicVirtualFilesystem(): vfsStreamDirectory
    {
        return vfsStream::setup('glpi', null, [
            'js' => [
                'modules' => [
                    'Forms' => [
                        'GlpiFormConditionEditorController.js' => '// Some JS content 1',
                        'GlpiFormDestinationAutoConfigController.js' => '// Some JS content 2',
                    ],
                    'Utils' => [
                        'HelperFunctions.js' => '// Some helper functions',
                    ],
                ],
            ],
            'public' => [
                'lib' => [
                    'vendor1' => [
                        'library1.js' => '// Library 1 content',
                    ],
                    'vendor2' => [
                        'library2.js' => '// Library 2 content',
                    ],
                ],
                'build' => [
                    'compiled.js' => '// Compiled JS content',
                    'assets' => [
                        'module1.js' => '// Built module 1',
                        'module2.js' => '// Built module 2',
                    ],
                ],
            ],
            'plugins' => [
                'myplugin' => [

                    'public' => [
                        'lib' => [
                            'plugin-lib.js' => '// Plugin library',
                        ],
                        'build' => [
                            'plugin-build.js' => '// Plugin built file',
                        ],
                        'js' => [
                            'modules' => [
                                'CustomModule.js' => '// Plugin JS content',
                            ],
                        ],
                    ],
                ],
            ],
            'marketplace' => [
                'myotherplugin' => [
                    'public' => [
                        'lib' => [
                            'other-lib.js' => '// Another plugin library',
                        ],
                        'build' => [
                            'component' => [
                                'other-build.js' => '// Another plugin built file',
                            ],
                        ],
                        'js' => [
                            'modules' => [
                                'CustomModule.js' => '// Plugin JS content',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Create a mock of ImportMapGenerator with customized behavior
     *
     * @param string $virtual_fs_path Virtual filesystem path
     * @return ImportMapGenerator|MockObject Mocked generator
     */
    private function createMockGenerator(string $virtual_fs_path): MockObject
    {
        /** @var ImportMapGenerator|MockObject $generator */
        $generator = $this->getMockBuilder(ImportMapGenerator::class)
            ->setConstructorArgs([self::ROOT_URL, $virtual_fs_path, $this->createCacheMock()])
            ->onlyMethods(['getPluginDirList'])
            ->getMock();

        // Configure mock
        $generator->method('getPluginDirList')
            ->willReturn([
                $virtual_fs_path . '/plugins/myplugin',
                $virtual_fs_path . '/marketplace/myotherplugin',
            ]);

        return $generator;
    }

    /**
     * Create a mock cache for testing
     *
     * @return CacheInterface Mocked cache
     */
    private function createCacheMock(): CacheInterface
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')
            ->willReturn(null); // Simulate cache miss
        $cache->method('set')
            ->willReturn(true); // Do nothing but return success

        /** @var CacheInterface $cache */
        return $cache;
    }

    /**
     * Test the generate method for core and plugin modules
     */
    public function testGenerate()
    {
        // Set up virtual file system
        $root = $this->setupBasicVirtualFilesystem();

        // Create generator with mocked dependencies
        $generator = $this->createMockGenerator(vfsStream::url('glpi'));

        // Generate the import map
        $import_map = $generator->generate();

        // Assertions for structure and entries
        $this->assertArrayHasKey('imports', $import_map, 'Import map should have an imports key');

        // Check for core modules in /js/modules
        $this->assertArrayHasKey('/js/modules/Forms/GlpiFormConditionEditorController.js', $import_map['imports']);
        $this->assertArrayHasKey('/js/modules/Forms/GlpiFormDestinationAutoConfigController.js', $import_map['imports']);
        $this->assertArrayHasKey('/js/modules/Utils/HelperFunctions.js', $import_map['imports']);

        // Check for core modules in public/lib and public/build
        $this->assertArrayHasKey('/lib/vendor1/library1.js', $import_map['imports']);
        $this->assertArrayHasKey('/lib/vendor2/library2.js', $import_map['imports']);
        $this->assertArrayHasKey('/build/compiled.js', $import_map['imports']);
        $this->assertArrayHasKey('/build/assets/module1.js', $import_map['imports']);
        $this->assertArrayHasKey('/build/assets/module2.js', $import_map['imports']);

        // Verify all URLs have version parameters
        foreach ($import_map['imports'] as $module_name => $url) {
            $this->assertStringContainsString('?v=', $url, "URL for $module_name should include a version parameter");
        }

        // Verify URL paths are correct
        $this->assertStringContainsString(
            self::ROOT_URL . '/js/modules/Forms/GlpiFormConditionEditorController.js?v=',
            $import_map['imports']['/js/modules/Forms/GlpiFormConditionEditorController.js']
        );
        $this->assertStringContainsString(
            self::ROOT_URL . '/lib/vendor1/library1.js?v=',
            $import_map['imports']['/lib/vendor1/library1.js']
        );
    }

    /**
     * Test that version parameters change when file content changes
     */
    public function testVersionParameterChangesWhenFileContentChanges()
    {
        // Set up basic virtual filesystem with a test module
        vfsStream::setup('glpi', null, [
            'js' => [
                'modules' => [
                    'TestModule.js' => '// Initial content',
                ],
            ],
        ]);

        // Create generator and get reflection access to private method
        $generator = $this->createMockGenerator(vfsStream::url('glpi'));
        $reflection = new \ReflectionClass($generator);
        $method = $reflection->getMethod('generateVersionParam');

        // Test file path
        $file_path = vfsStream::url('glpi/js/modules/TestModule.js');

        // First version check
        $initial_version = $method->invoke($generator, $file_path);
        $this->assertNotEmpty($initial_version, 'Version parameter should not be empty');

        // Modify file and check for version change
        file_put_contents($file_path, '// Modified content');
        $new_version = $method->invoke($generator, $file_path);

        // Verify version changed
        $this->assertNotEquals(
            $initial_version,
            $new_version,
            'Version parameter should change when file content changes'
        );

        // Additional verification - changing back should match original hash
        file_put_contents($file_path, '// Initial content');
        $reverted_version = $method->invoke($generator, $file_path);
        $this->assertEquals(
            $initial_version,
            $reverted_version,
            'Version parameter should be the same when content is identical'
        );
    }

    /**
     * Test the registerModulesPath method for plugin-specific module paths
     */
    public function testRegisterModulesPath()
    {
        // Set up virtual file system
        $root = $this->setupBasicVirtualFilesystem();

        // Create generator with mocked dependencies
        $generator = $this->createMockGenerator(vfsStream::url('glpi'));

        // Register specific module paths
        $generator->registerModulesPath('myplugin', 'public/js/modules');
        $generator->registerModulesPath('myplugin', 'public/build');
        $generator->registerModulesPath('myotherplugin', 'public/build');
        $generator->registerModulesPath('myotherplugin', 'public/lib');

        // Generate the import map
        $import_map = $generator->generate();

        // Verify that only registered paths are included
        $this->assertArrayHasKey('/plugins/myplugin/js/modules/CustomModule.js', $import_map['imports'], 'Module from registered path should be included');
        $this->assertArrayHasKey('/plugins/myplugin/build/plugin-build.js', $import_map['imports'], 'Plugin build file should be included');
        $this->assertArrayHasKey('/plugins/myotherplugin/build/component/other-build.js', $import_map['imports'], 'Another plugin build file should be included');
        $this->assertArrayHasKey('/plugins/myotherplugin/lib/other-lib.js', $import_map['imports'], 'Another plugin module should be included');

        // Verify that unregistered paths are not included
        $this->assertArrayNotHasKey('/plugins/myotherplugin/js/modules/CustomModule.js', $import_map['imports'], 'Files outside registered paths should not be included');

        // Verify URL paths are correct
        $this->assertStringContainsString(
            self::ROOT_URL . '/plugins/myplugin/js/modules/CustomModule.js?v=',
            $import_map['imports']['/plugins/myplugin/js/modules/CustomModule.js']
        );
        $this->assertStringContainsString(
            self::ROOT_URL . '/plugins/myotherplugin/lib/other-lib.js?v=',
            $import_map['imports']['/plugins/myotherplugin/lib/other-lib.js']
        );
    }

    /**
     * Test for getInstance singleton method
     */
    public function testGetInstance()
    {
        // Get reflection access to static instance property
        $reflection = new \ReflectionClass(ImportMapGenerator::class);
        $instance_property = $reflection->getProperty('instance');

        // Reset singleton instance
        $instance_property->setValue(null, null);

        // Test that getInstance returns an instance
        $instance = ImportMapGenerator::getInstance();
        $this->assertInstanceOf(ImportMapGenerator::class, $instance);

        // Test that getInstance returns the same instance on subsequent calls
        $another_instance = ImportMapGenerator::getInstance();
        $this->assertSame($instance, $another_instance);
    }
}
