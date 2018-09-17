<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
*/

namespace tests\units\Glpi\Console;

use org\bovigo\vfs\vfsStream;

/* Test for inc/console/commandloader.class.php */

class CommandLoader extends \GLPITestCase {

   public function testLoader() {

      $structure = [
         'inc' => [
            // Not instanciable case
            'abstractcommand.class.php' => <<<PHP
<?php
abstract class AbstractCommand extends \\Symfony\\Component\\Console\\Command\\Command { }
PHP
            ,

            // Base command case with alias
            'installcommand.class.php' => <<<PHP
<?php
class InstallCommand extends \\Symfony\\Component\\Console\\Command\\Command {
   protected function configure() {
      \$this->setName('glpi:database:install');
      \$this->setAliases(['db:install']);
   }
}
PHP
            ,

            // Not a command case
            'somename.class.php' => '<?php class SomeName {}',

            'console' => [
               // Namespaced command case
               'testcommand.class.php' => <<<PHP
<?php
namespace Glpi\\Console;
class TestCommand extends \\Symfony\\Component\\Console\\Command\\Command {
   protected function configure() {
      \$this->setName('glpi:test');
   }
}
PHP
            ],
         ],
         'plugins' => [
            'awesome' => [
               'inc' => [
                  // Not recognized due to bad filename pattern
                  'basecmd.class.php' => <<<PHP
<?php
class PluginAwesomeBaseCmd extends \\Symfony\\Component\\Console\\Command\\Command {
   protected function configure() {
      \$this->setName('plugin_awesome:base');
   }
}
PHP
                  ,

                  // Plugin command case
                  'updatecommand.class.php' => <<<PHP
<?php
class PluginAwesomeUpdateCommand extends \\Symfony\\Component\\Console\\Command\\Command {
   protected function configure() {
      \$this->setName('plugin_awesome:update');
   }
}
PHP
               ]
            ],
            'misc' => [
               'inc' => [
                  // Not a command case
                  'misc.class.php' => '<?php class PluginMiscMisc {}',
               ]
            ],
         ],
      ];
      vfsStream::setup('glpi', null, $structure);

      $names_to_class = [
         'glpi:database:install' => 'InstallCommand',
         'db:install'            => 'InstallCommand',
         'glpi:test'             => 'Glpi\\Console\\TestCommand',
         'plugin_awesome:update' => 'PluginAwesomeUpdateCommand',
      ];

      $command_loader = new \Glpi\Console\CommandLoader(vfsStream::url('glpi'));

      $this->array($command_loader->getNames())->isIdenticalTo(array_keys($names_to_class));

      foreach ($names_to_class as $name => $classname) {
         $this->boolean($command_loader->has($name))->isTrue();
         $this->object($command_loader->get($name))->isInstanceOf($classname);
      }
   }
}
