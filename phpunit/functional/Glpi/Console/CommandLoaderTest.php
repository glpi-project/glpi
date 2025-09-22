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

namespace tests\units\Glpi\Console;

use Error;
use Glpi\Console\CommandLoader;
use org\bovigo\vfs\vfsStream;

/* Test for inc/console/commandloader.class.php */

class CommandLoaderTest extends \GLPITestCase
{
    public function testLoader()
    {
        $structure = [
            'src' => [
                // Not instanciable case
                'AbstractCommand.php' => <<<PHP
<?php
abstract class AbstractCommand extends \\Symfony\\Component\\Console\\Command\\Command { }
PHP,

                // Base command case with alias
                'InstallCommand.php' => <<<PHP
<?php
class InstallCommand extends \\Symfony\\Component\\Console\\Command\\Command {
   protected function configure() {
      \$this->setName('database:install');
      \$this->setAliases(['db:install']);
   }
}
PHP,

                // Not a command case
                'SomeName.php' => '<?php class SomeName {}',

                'Glpi' => [
                    'Console' => [
                        // Namespaced command case
                        'TestCommand.php' => <<<PHP
<?php
namespace Glpi\\Console;
class TestCommand extends \\Symfony\\Component\\Console\\Command\\Command {
   protected function configure() {
      \$this->setName('test');
   }
}
PHP,
                    ],
                ],
            ],
            'tools' => [
                // Base command case with alias
                'DebugCommand.php' => <<<PHP
<?php
class DebugCommand extends \\Symfony\\Component\\Console\\Command\\Command {
   protected function configure() {
      \$this->setName('tools:debug');
   }
}
PHP,

                // Not a command case
                'oldscript.php' => '<?php echo("Hi!");',
            ],
            'plugins' => [
                'awesome' => [
                    'inc' => [
                        // Not recognized due to bad filename pattern
                        'basecmd.class.php' => <<<PHP
<?php
class PluginAwesomeBaseCmd extends \\Symfony\\Component\\Console\\Command\\Command {
   protected function configure() {
      \$this->setName('plugins:awesome:base');
   }
}
PHP,

                        // Plugin command case
                        'updatecommand.class.php' => <<<PHP
<?php
class PluginAwesomeUpdateCommand extends \\Symfony\\Component\\Console\\Command\\Command {
   protected function configure() {
      \$this->setName('plugins:awesome:update');
   }
}
PHP,

                        // Plugin namespaced command case (inside "inc" dir)
                        'namespacedcommand.class.php' => <<<PHP
<?php
namespace GlpiPlugin\\Awesome;
class NamespacedCommand extends \\Symfony\\Component\\Console\\Command\\Command {
   protected function configure() {
      \$this->setName('plugins:awesome:namespaced');
   }
}
PHP,

                        'console' => [
                            // Plugin namespaced command case (inside a sub dir)
                            'anothercommand.class.php' => <<<PHP
<?php
namespace GlpiPlugin\\Awesome\\Console;
class AnotherCommand extends \\Symfony\\Component\\Console\\Command\\Command {
   protected function configure() {
      \$this->setName('plugins:awesome:another');
   }
}
PHP,
                        ],
                    ],
                    'src' => [
                        // Plugin PSR-4 compliant with namespace command case
                        'PluginAwesomePsr4Command.php' => <<<PHP
<?php
class PluginAwesomePsr4Command extends \\Symfony\\Component\\Console\\Command\\Command {
   protected function configure() {
      \$this->setName('plugins:awesome:psr4');
   }
}
PHP,

                        'Console' => [
                            // Plugin PSR-4 compliant without namespace command case
                            'YetAnotherCommand.php' => <<<PHP
<?php
namespace GlpiPlugin\\Awesome\\Console;
class YetAnotherCommand extends \\Symfony\\Component\\Console\\Command\\Command {
   protected function configure() {
      \$this->setName('plugins:awesome:yetanother');
   }
}
PHP,

                            // Misnamed command
                            'MisnamedCommand.php' => <<<PHP
<?php
namespace GlpiPlugin\\Awesome\\Console;
class MisnamedCommand extends \\Symfony\\Component\\Console\\Command\\Command {
   protected function configure() {
      \$this->setName('awesome:misnamed');
   }
}
PHP,

                            // Command located in another plugin namespace
                            'FooBarCommand.php' => <<<PHP
<?php
namespace GlpiPlugin\\Awesome\\Console;
class FooBarCommand extends \\Symfony\\Component\\Console\\Command\\Command {
   protected function configure() {
      \$this->setName('plugins:anotherplugin:foobar');
   }
}
PHP,
                        ],
                    ],
                ],
                'misc' => [
                    'inc' => [
                        // Not a command case
                        'misc.class.php' => '<?php class PluginMiscMisc {}',
                    ],
                ],
            ],
            'tests' => [
                'fixtures' => [
                    'plugins' => [
                        'random' => [
                            'inc' => [
                                // Not recognized due to bad filename pattern
                                'testcmd.class.php' => <<<PHP
<?php
class PluginRandomTestCmd extends \\Symfony\\Component\\Console\\Command\\Command {
   protected function configure() {
      \$this->setName('plugins:random:test');
   }
}
PHP,

                                // Plugin command case
                                'randomcommand.class.php' => <<<PHP
<?php
class PluginRandomRandomCommand extends \\Symfony\\Component\\Console\\Command\\Command {
   protected function configure() {
      \$this->setName('plugins:random:random');
   }
}
PHP,

                                // Plugin namespaced command case (inside "inc" dir)
                                'checkcommand.class.php' => <<<PHP
<?php
namespace GlpiPlugin\\Random;
class CheckCommand extends \\Symfony\\Component\\Console\\Command\\Command {
   protected function configure() {
      \$this->setName('plugins:random:check');
   }
}
PHP,

                                'console' => [
                                    // Plugin namespaced command case (inside a sub dir)
                                    'foocommand.class.php' => <<<PHP
<?php
namespace GlpiPlugin\\Random\\Console;
class FooCommand extends \\Symfony\\Component\\Console\\Command\\Command {
   protected function configure() {
      \$this->setName('plugins:random:foo');
   }
}
PHP,
                                ],
                            ],
                        ],
                        'misc' => [
                            'inc' => [
                                // Not a command case
                                'something.class.php' => '<?php class PluginRandomSomething {}',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        vfsStream::setup('glpi', null, $structure);

        $core_names_to_class = [
            'database:install' => 'InstallCommand',
            'db:install'       => 'InstallCommand',
            'test'             => 'Glpi\\Console\\TestCommand',
            'tools:debug'      => 'DebugCommand',
        ];

        $plugins_names_to_class = [
            'plugins:awesome:update'     => 'PluginAwesomeUpdateCommand',
            'plugins:awesome:namespaced' => 'GlpiPlugin\\Awesome\\NamespacedCommand',
            'plugins:awesome:another'    => 'GlpiPlugin\\Awesome\\Console\\AnotherCommand',
            'plugins:awesome:psr4'       => 'PluginAwesomePsr4Command',
            'plugins:awesome:yetanother' => 'GlpiPlugin\\Awesome\\Console\\YetAnotherCommand',
            'plugins:random:random'      => 'PluginRandomRandomCommand',
            'plugins:random:check'       => 'GlpiPlugin\\Random\\CheckCommand',
            'plugins:random:foo'         => 'GlpiPlugin\\Random\\Console\\FooCommand',
        ];

        $all_names_to_class = array_merge($core_names_to_class, $plugins_names_to_class);

        // Mock plugin
        $plugin = new class extends \Plugin {
            public static function getPlugins()
            {
                return ['awesome', 'random'];
            }

            public static function getPhpDir(string $plugin_key = "", $full = true)
            {
                return match ($plugin_key) {
                    'awesome' => vfsStream::url('glpi/plugins/awesome'),
                    'random' => vfsStream::url('glpi/tests/fixtures/plugins/random'),
                };
            }
        };

        // Check with plugins
        $errors = [];
        // PHPUnit won't let us expect errors so we need our own handler
        set_error_handler(static function ($code, $message) use (&$errors) {
            $errors[] = new Error($message, $code);
        }, E_USER_WARNING);
        $command_loader = new CommandLoader(true, vfsStream::url('glpi'), $plugin);
        $this->assertEquals(array_keys($all_names_to_class), $command_loader->getNames());
        foreach ($all_names_to_class as $name => $classname) {
            $this->assertTrue($command_loader->has($name));
            $this->assertInstanceOf($classname, $command_loader->get($name));
        }
        $this->assertCount(2, $errors);
        $this->assertEquals('Plugin command `awesome:misnamed` must be moved in the `plugins:awesome` namespace.', $errors[0]->getMessage());
        $this->assertEquals('Plugin command `plugins:anotherplugin:foobar` must be moved in the `plugins:awesome` namespace.', $errors[1]->getMessage());

        // Check without plugins
        $errors = [];
        $command_loader = new CommandLoader(false, vfsStream::url('glpi'), $plugin);
        $this->assertEquals(array_keys($core_names_to_class), $command_loader->getNames());
        foreach ($core_names_to_class as $name => $classname) {
            $this->assertTrue($command_loader->has($name));
            $this->assertInstanceOf($classname, $command_loader->get($name));
        }
        $this->assertCount(0, $errors);

        // Check async plugin registration
        $command_loader = new CommandLoader(false, vfsStream::url('glpi'), $plugin);
        $command_loader->setIncludePlugins(true);
        $this->assertEquals(array_keys($all_names_to_class), $command_loader->getNames());
        foreach ($all_names_to_class as $name => $classname) {
            $this->assertTrue($command_loader->has($name));
            $this->assertInstanceOf($classname, $command_loader->get($name));
        }
        $this->assertCount(2, $errors);
        $this->assertEquals('Plugin command `awesome:misnamed` must be moved in the `plugins:awesome` namespace.', $errors[0]->getMessage());
        $this->assertEquals('Plugin command `plugins:anotherplugin:foobar` must be moved in the `plugins:awesome` namespace.', $errors[1]->getMessage());
        restore_error_handler();
    }
}
