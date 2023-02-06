<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace tests\units\Glpi\Console;

use org\bovigo\vfs\vfsStream;

/* Test for inc/console/commandloader.class.php */

class CommandLoader extends \GLPITestCase
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

                // Namespaced command case located in root of source dir
                'ValidateCommand.php' => <<<PHP
<?php
namespace Glpi;
class ValidateCommand extends \\Symfony\\Component\\Console\\Command\\Command {
   protected function configure() {
      \$this->setName('validate');
   }
}
PHP,

                // Not a command case
                'SomeName.php' => '<?php class SomeName {}',

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
                    ]
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
                            ]
                        ],
                    ],
                ],
            ]
        ];
        vfsStream::setup('glpi', null, $structure);

        $core_names_to_class = [
            'database:install' => 'InstallCommand',
            'db:install'       => 'InstallCommand',
            'validate'         => 'Glpi\\ValidateCommand',
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
        $plugin = $this->newMockInstance('Plugin');
        $this->calling($plugin)->isActivated = true;

        // Check with plugins
        $this->when(
            function () use ($plugin, $all_names_to_class) {
                $command_loader = new \Glpi\Console\CommandLoader(true, vfsStream::url('glpi'), $plugin);
                $this->array($command_loader->getNames())->isIdenticalTo(array_keys($all_names_to_class));
                foreach ($all_names_to_class as $name => $classname) {
                    $this->boolean($command_loader->has($name))->isTrue();
                    $this->object($command_loader->get($name))->isInstanceOf($classname);
                }
            }
        )->error()
            ->withType(E_USER_WARNING)
            ->withMessage('Plugin command `awesome:misnamed` must be moved in the `plugins:awesome` namespace.')
            ->exists()
         ->error()
            ->withType(E_USER_WARNING)
            ->withMessage('Plugin command `plugins:anotherplugin:foobar` must be moved in the `plugins:awesome` namespace.')
            ->exists();

        // Check without plugins
        $command_loader = new \Glpi\Console\CommandLoader(false, vfsStream::url('glpi'), $plugin);
        $this->array($command_loader->getNames())->isIdenticalTo(array_keys($core_names_to_class));
        foreach ($core_names_to_class as $name => $classname) {
            $this->boolean($command_loader->has($name))->isTrue();
            $this->object($command_loader->get($name))->isInstanceOf($classname);
        }

        // Check async plugin registration
        $this->when(
            function () use ($plugin, $all_names_to_class) {
                $command_loader = new \Glpi\Console\CommandLoader(false, vfsStream::url('glpi'), $plugin);
                $command_loader->setIncludePlugins(true);
                $this->array($command_loader->getNames())->isIdenticalTo(array_keys($all_names_to_class));
                foreach ($all_names_to_class as $name => $classname) {
                    $this->boolean($command_loader->has($name))->isTrue();
                    $this->object($command_loader->get($name))->isInstanceOf($classname);
                }
            }
        )->error()
            ->withType(E_USER_WARNING)
            ->withMessage('Plugin command `awesome:misnamed` must be moved in the `plugins:awesome` namespace.')
            ->exists()
         ->error()
            ->withType(E_USER_WARNING)
            ->withMessage('Plugin command `plugins:anotherplugin:foobar` must be moved in the `plugins:awesome` namespace.')
            ->exists();
    }
}
