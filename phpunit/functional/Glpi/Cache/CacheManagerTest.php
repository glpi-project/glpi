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

namespace tests\units\Glpi\Cache;

use Monolog\Logger;
use org\bovigo\vfs\vfsStream;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * @backupStaticAttributes disabled
 */
class CacheManagerTest extends \GLPITestCase
{
    public static function contextProvider(): iterable
    {
        yield [
            'context'         => 'tempcache',
            'is_valid'        => false,
            'is_configurable' => false,
        ];
        yield [
            'context'         => 'core',
            'is_valid'        => true,
            'is_configurable' => true,
        ];
        yield [
            'context'         => 'translations',
            'is_valid'        => true,
            'is_configurable' => false,
        ];
        yield [
            'context'         => 'installer',
            'is_valid'        => true,
            'is_configurable' => false,
        ];
        yield [
            'context'         => 'plugin:tester',
            'is_valid'        => true,
            'is_configurable' => true,
        ];
    }

    /**
     * @dataProvider contextProvider
     */
    public function testIsContextValid(string $context, bool $is_valid, bool $is_configurable): void
    {
        vfsStream::setup('glpi', null, ['config' => [], 'files' => ['_cache' => []]]);

        $instance = new \Glpi\Cache\CacheManager(
            vfsStream::url('glpi/config'),
            vfsStream::url('glpi/files/_cache')
        );

        $this->assertEquals($is_valid, $instance->isContextValid($context, false));
        $this->assertEquals($is_valid && $is_configurable, $instance->isContextValid($context, true));

        // Also test argument checks on other methods
        if (!$is_configurable) {
            $exception_msg = sprintf('Invalid or non configurable context: "%s".', $context);
            $this->expectExceptionMessage($exception_msg);
            $instance->setConfiguration($context, 'memcached://localhost');

            $this->expectExceptionMessage($exception_msg);
            $instance->unsetConfiguration($context);
        } else {
            $this->assertTrue($instance->setConfiguration($context, 'memcached://localhost'));
            $this->assertTrue($instance->unsetConfiguration($context));
        }
        if (!$is_valid) {
            $exception_msg = sprintf('Invalid context: "%s".', $context);
            $this->expectExceptionMessage($exception_msg);
            $instance->getCacheInstance($context);
        } else {
            $this->assertInstanceOf(CacheInterface::class, $instance->getCacheInstance($context));
        }
    }


    public function testGetNonConfigurableCache(): void
    {
        vfsStream::setup('glpi', null, ['config' => [], 'files' => ['_cache' => []]]);

        $instance = new \Glpi\Cache\CacheManager(
            vfsStream::url('glpi/config'),
            vfsStream::url('glpi/files/_cache')
        );

        // Test 'installer' context
        $this->assertInstanceOf(CacheInterface::class, $instance->getInstallerCacheInstance());
        $this->assertInstanceOf(CacheInterface::class, $instance->getCacheInstance('installer'));
        $this->assertInstanceOf(FilesystemAdapter::class, $instance->getCacheStorageAdapter('installer'));

        // Test 'translations' context
        $this->assertInstanceOf(CacheInterface::class, $instance->getTranslationsCacheInstance());
        $this->assertInstanceOf(CacheInterface::class, $instance->getCacheInstance('translations'));
        $this->assertInstanceOf(FilesystemAdapter::class, $instance->getCacheStorageAdapter('translations'));
    }

    public static function configurationProvider(): iterable
    {
        foreach (['core', 'plugin:tester'] as $context) {
            // Invalid unique DSN
            yield [
                'context'            => $context,
                'dsn'                => 'whoot://invalid',
                'options'            => [],
                'expected_set_error' => 'Invalid DSN: "whoot://invalid".',
                'expected_get_error' => [
                    'level' => Logger::WARNING,
                    'message' => sprintf('Invalid configuration for cache context "%s".', $context)
                ],
                'expected_adapter'   => FilesystemAdapter::class, // Fallback adapter
            ];

            // Invalid multiple DSN
            yield [
                'context'            => $context,
                'dsn'                => ['redis://cache1.glpi-project.org', 'redis://cache2.glpi-project.org'],
                'options'            => [],
                'expected_set_error' => 'Invalid DSN: ["redis://cache1.glpi-project.org","redis://cache2.glpi-project.org"].',
                'expected_get_error' => [
                    'level' => Logger::WARNING,
                    'message' => sprintf('Invalid configuration for cache context "%s".', $context)
                ],
                'expected_adapter'   => FilesystemAdapter::class, // Fallback adapter
            ];

            if (extension_loaded('memcached')) {
                // Memcached config (unique DSN)
                yield [
                    'context'            => $context,
                    'dsn'                => 'memcached://cache.glpi-project.org',
                    'options'            => [
                        'libketama_compatible' => true,
                    ],
                    'expected_set_error' => null,
                    'expected_get_error' => null,
                    'expected_adapter'   => MemcachedAdapter::class,
                ];

                // Memcached config (multiple DSN)
                yield [
                    'context'          => $context,
                    'dsn'              => ['memcached://cache1.glpi-project.org', 'memcached://cache2.glpi-project.org'],
                    'options'          => [],
                    'expected_set_error' => null,
                    'expected_get_error' => null,
                    'expected_adapter' => MemcachedAdapter::class,
                ];
            }

            if (extension_loaded('redis')) {
                // Redis config
                yield [
                    'context'          => $context,
                    'dsn'              => 'redis://cache.glpi-project.org',
                    'options'          => [
                        'lazy'       => true,
                        'persistent' => 1,
                    ],
                    'expected_set_error' => null,
                    'expected_get_error' => null,
                    'expected_adapter' => RedisAdapter::class,
                ];
            }
        }

        // Not configurable contexts
        $contexts = [
            'installer',
            'translations',
        ];
        foreach ($contexts as $context) {
            yield [
                'context'            => $context,
                'dsn'                => 'whoot://invalid',
                'options'            => [],
                'expected_set_error' => sprintf('Invalid or non configurable context: "%s".', $context),
                'expected_get_error' => [
                    'level' => Logger::NOTICE,
                    'message' => sprintf('Invalid or non configurable context: "%s".', $context)
                ],
                'expected_adapter'   => FilesystemAdapter::class, // Fallback adapter
            ];
        }
    }

    /**
     * @dataProvider configurationProvider
     */
    public function testSetConfiguration(
        string $context,
        $dsn,
        array $options,
        ?string $expected_set_error,
        ?array $expected_get_error,
        ?string $expected_adapter
    ): void {

        vfsStream::setup('glpi', null, ['config' => [], 'files' => ['_cache' => []]]);

        $instance = new \Glpi\Cache\CacheManager(
            vfsStream::url('glpi/config'),
            vfsStream::url('glpi/files/_cache')
        );

        if ($expected_set_error !== null) {
            $this->expectExceptionMessage($expected_set_error);
            $instance->setConfiguration($context, $dsn, $options);
            return;
        }

        $this->assertTrue($instance->setConfiguration($context, $dsn, $options));

        $config_file = vfsStream::url('glpi/config/' . \Glpi\Cache\CacheManager::CONFIG_FILENAME);
        $expected_config = [
            'contexts' => [
                $context => [
                    'dsn'       => $dsn,
                    'options'   => $options,
                ],
            ],
        ];

        $this->assertTrue(file_exists($config_file));
        $this->assertEquals($expected_config, include($config_file));
    }

    public function testUnsetConfiguration(): void
    {
        $config_filename = \Glpi\Cache\CacheManager::CONFIG_FILENAME;

        $expected_config = [
            'contexts' => [
                'core' => [
                    'dsn'       => 'memcached://localhost',
                ],
                'plugin:tester' => [
                    'dsn'       => 'redis://cache.glpi-project.org/glpi',
                    'options'   => ['lazy' => true],
                ],
                'plugin:another' => [
                    'dsn'       => 'redis://cache.glpi-project.org/glpi',
                    'options'   => [],
                ],
            ],
        ];

        vfsStream::setup(
            'glpi',
            null,
            [
                'config' => [
                    $config_filename => '<?php' . "\n" . 'return ' . var_export($expected_config, true) . ';',
                ],
                'files' => ['_cache' => []],
            ]
        );

        $config_file = vfsStream::url('glpi/config/' . $config_filename);

        $instance = new \Glpi\Cache\CacheManager(
            vfsStream::url('glpi/config'),
            vfsStream::url('glpi/files/_cache')
        );

        // Unsetting an invalid context does not alter config file
        $this->expectExceptionMessage('Invalid or non configurable context: "notavalidcontext".');
        $instance->unsetConfiguration('notavalidcontext');

        $this->assertTrue(file_exists($config_file));
        $this->assertEquals($expected_config, include($config_file));

        // Unsetting core config only removes core entry in config file
        $this->assertTrue($instance->unsetConfiguration('core'));
        unset($expected_config['contexts']['core']);
        $this->assertTrue(file_exists($config_file));
        $this->assertEquals($expected_config, include($config_file));

        // Unsetting a plugin config only removes this plugin entry in config file
        $this->assertTrue($instance->unsetConfiguration('plugin:tester'));
        unset($expected_config['contexts']['plugin:tester']);
        $this->assertTrue(file_exists($config_file));
        $this->assertEquals($expected_config, include($config_file));
    }

    /**
     * @dataProvider configurationProvider
     */
    public function testGetConfigurableCache(
        string $context,
        $dsn,
        array $options,
        ?string $expected_set_error,
        ?array $expected_get_error,
        ?string $expected_adapter
    ): void {

        $config = [
            'contexts' => [
                $context => [
                    'dsn'       => $dsn,
                    'options'   => $options,
                ],
            ],
        ];

        vfsStream::setup(
            'glpi',
            null,
            [
                'config' => [
                    \Glpi\Cache\CacheManager::CONFIG_FILENAME => '<?php' . "\n" . 'return ' . var_export($config, true) . ';',
                ],
                'files' => ['_cache' => []],
            ]
        );

        $instance = new \Glpi\Cache\CacheManager(
            vfsStream::url('glpi/config'),
            vfsStream::url('glpi/files/_cache')
        );

        if ($expected_get_error !== null) {
            $this->assertInstanceOf(\Glpi\Cache\SimpleCache::class, $instance->getCacheInstance($context));
            $this->hasPhpLogRecordThatContains($expected_get_error['message'], $expected_get_error['level']);
            return;
        }

        $this->assertInstanceOf(CacheInterface::class, $instance->getCacheInstance($context));
        $this->assertInstanceOf($expected_adapter, $instance->getCacheStorageAdapter($context));

        if ($context === 'core') {
            // test CacheManager::getCoreCacheInstance()
            $this->assertInstanceOf(CacheInterface::class, $instance->getCoreCacheInstance());
        }
    }

    /**
     * @dataProvider contextProvider
     */
    public function testGetCacheInstanceDefault(string $context, bool $is_valid, bool $is_configurable): void
    {
        if (!$is_valid) {
            //phpunit is not happy when no assertions are run.
            $this->assertTrue(true);
            return;
        }

        vfsStream::setup('glpi', null, ['config' => [], 'files' => ['_cache' => []]]);

        $instance = new \Glpi\Cache\CacheManager(
            vfsStream::url('glpi/config'),
            vfsStream::url('glpi/files/_cache')
        );

        $this->assertInstanceOf(CacheInterface::class, $instance->getCacheInstance($context));

        $this->assertInstanceOf(FilesystemAdapter::class, $instance->getCacheStorageAdapter($context));

        if ($context === 'core') {
            $this->assertInstanceOf(CacheInterface::class, $instance->getCoreCacheInstance());
        }
        if ($context === 'installer') {
            $this->assertInstanceOf(CacheInterface::class, $instance->getInstallerCacheInstance());
        }
        if ($context === 'translations') {
            $this->assertInstanceOf(CacheInterface::class, $instance->getTranslationsCacheInstance());
        }
    }

    public static function dsnProvider(): iterable
    {
        yield [
            'dsn'      => 'memcached://user:pass@127.0.0.1:1015?weight=20',
            'is_valid' => true,
            'scheme'   => 'memcached',
        ];
        yield [
            'dsn'      => ['memcached://user:pass@127.0.0.1:1015?weight=20', 'memcached://user:pass@127.0.0.1:1016?weight=30'],
            'is_valid' => true,
            'scheme'   => 'memcached',
        ];
        yield [
            'dsn'      => 'redis://localhost/glpi',
            'is_valid' => true,
            'scheme'   => 'redis',
        ];
        yield [
            'dsn'      => 'rediss://192.168.0.15',
            'is_valid' => true,
            'scheme'   => 'rediss',
        ];
        yield [
            'dsn'      => 'memcached:/localhost', // missing /
            'is_valid' => false,
            'scheme'   => null,
        ];
        yield [
            'dsn'      => 'donotknowit://127.0.0.1', // unknown scheme
            'is_valid' => false,
            'scheme'   => null,
        ];
        yield [
            'dsn'      => ['redis:///tmp/cache', 'redis://localhost/glpi'], // invalid multiple DSN
            'is_valid' => false,
            'scheme'   => null,
        ];
    }

    /**
     * @dataProvider dsnProvider
     */
    public function testIsDsnValid($dsn, bool $is_valid, ?string $scheme = null): void
    {

        vfsStream::setup('glpi', null, ['config' => [], 'files' => ['_cache' => []]]);

        $instance = new \Glpi\Cache\CacheManager(
            vfsStream::url('glpi/config'),
            vfsStream::url('glpi/files/_cache')
        );

        $this->assertSame($is_valid, $instance->isDsnValid($dsn));
    }

    /**
     * @dataProvider dsnProvider
     */
    public function testExtractScheme($dsn, bool $is_valid, ?string $scheme = null): void
    {

        vfsStream::setup('glpi', null, ['config' => [], 'files' => ['_cache' => []]]);

        $instance = new \Glpi\Cache\CacheManager(
            vfsStream::url('glpi/config'),
            vfsStream::url('glpi/files/_cache')
        );

        $this->assertSame($scheme, $instance->extractScheme($dsn));
    }

    public function testGetKnownContexts()
    {

        $vfs_structure = vfsStream::setup('glpi', null, ['config' => [], 'files' => ['_cache' => []]]);

        $instance = new \Glpi\Cache\CacheManager(
            vfsStream::url('glpi/config'),
            vfsStream::url('glpi/files/_cache')
        );

        // Check base contexts
        $expected_contexts = [
            'core',
            'installer',
            'translations',
        ];
        $this->assertSame($expected_contexts, $instance->getKnownContexts());

        // Check context extracted from config file
        foreach (['plugin:a', 'plugin:b'] as $context) {
            $instance->setConfiguration($context, 'memcached://localhost');
            $expected_contexts[] = $context;
        }
        $this->assertSame($expected_contexts, $instance->getKnownContexts());

        // Check contexts extracted from existing directories
        vfsStream::create(
            [
                'files' => [
                    '_cache' => [
                        'plugin_c' => [],
                        'plugin_d' => [],
                        'not_a_valid_directory' => [], // Should be ignored
                        'templates' => [], // Should be ignored
                    ]
                ]
            ],
            $vfs_structure
        );
        $expected_contexts[] = 'plugin:c';
        $expected_contexts[] = 'plugin:d';
        $this->assertSame($expected_contexts, $instance->getKnownContexts());
    }

    public function testSetNamespacePrefix()
    {

        vfsStream::setup('glpi', null, ['config' => [], 'files' => ['_cache' => []]]);

        $instance = new \Glpi\Cache\CacheManager(
            vfsStream::url('glpi/config'),
            vfsStream::url('glpi/files/_cache')
        );

        $config_file = vfsStream::url('glpi/config/' . \Glpi\Cache\CacheManager::CONFIG_FILENAME);

        // Defines a non empty namespace
        $this->assertTrue($instance->setNamespacePrefix('my-instance'));

        $expected_config = [
            'namespace_prefix' => 'my-instance',
            'contexts' => [],
        ];

        $this->assertTrue(file_exists($config_file));
        $this->assertEquals($expected_config, include($config_file));

       // Defines an empty namespace, should be saved as null
        $this->assertTrue($instance->setNamespacePrefix(''));

        $expected_config = [
            'namespace_prefix' => null,
            'contexts' => [],
        ];

        $this->assertTrue(file_exists($config_file));
        $this->assertEquals($expected_config, include($config_file));
    }
}
