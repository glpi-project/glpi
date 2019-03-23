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

namespace tests\units\Glpi;

use org\bovigo\vfs\vfsStream;

/* Test for src/Glpi/Kernel.php */

class Kernel extends \GLPITestCase {

   /**
    * Test container compilation with empty configuration.
    */
   public function testContainerCompilation() {

      $structure = [
         'src' => [
            'resources' => [
                'services.yaml' => ''
            ],
         ],
      ];
      $directory = vfsStream::setup('glpi', null, $structure);
      // Make a not writable dir to prevent cache on container compilation
      $directory->addChild(vfsStream::newDirectory('nocache', 0555));

      $kernel = new \Glpi\Kernel(vfsStream::url('glpi'), vfsStream::url('glpi/nocache'));

      $container = $kernel->getContainer();
      $this->object($container)->isInstanceOf(\Psr\Container\ContainerInterface::class);

      // Check Glpi synthetic services
      $this->boolean($container->has('database'))->isTrue();
      $this->object($container->get('database'))->isInstanceOf(\Glpi\Database\AbstractDatabase::class);
   }

   /**
    * Test compilation of services configured in src/resources/service.yaml file.
    */
   public function testContainerConfiguredServicesCompilation() {

      $structure = [
         'src' => [
            'resources' => [
                'services.yaml' => <<<YAML
services:
    _defaults:
        autowire: true
        public: true

    database:
        synthetic: true

    fake_service:
        class: FakeService
        arguments: ['@database']
YAML
            ],
            'FakeService.php' => <<<PHP
<?php
class FakeService {
   private \$db;

   public function __construct(\Glpi\Database\AbstractDatabase \$db) {
      \$this->db = \$db;
   }

   public function getDb() {
      return \$this->db;
   }
}
PHP
            ,
         ],
      ];
      $directory = vfsStream::setup('glpi', null, $structure);
      // Make a not writable dir to prevent cache on container compilation
      $directory->addChild(vfsStream::newDirectory('nocache', 0555));

      // Autoload does not work for files inside vfsStream
      include_once(vfsStream::url('glpi/src/FakeService.php'));

      $kernel = new \Glpi\Kernel(vfsStream::url('glpi'), vfsStream::url('glpi/nocache'));

      $container = $kernel->getContainer();
      $this->object($container)->isInstanceOf(\Psr\Container\ContainerInterface::class);

      // Check Glpi service declared in services.yaml
      $this->boolean($container->has('fake_service'))->isTrue();
      $service = $container->get('fake_service');
      $this->object($service)->isInstanceOf('FakeService');
      $this->object($service->getDb())->isInstanceOf(\Glpi\Database\AbstractDatabase::class);
   }

   /**
    * Test compilation of services configured in a plugin.
    *
    * @ignore
    */
   public function testContainerPluginServicesCompilation() {

      $structure = [
         'src' => [
            'resources' => [
                'services.yaml' =>  <<<YAML
services:
    DBmysql:
        synthetic: true
        public: true
YAML
            ],
         ],
         'plugins' => [
            'random' => [
               'di' => [
                  'services.yaml' => <<<YAML
services:
    _defaults:
        autowire: true
        public: true

    random_service:
        class: RandomPlugin\\MyService

YAML
               ],
               'src' => [
                   'RandomPlugin' => [
                      'MyService.php' => <<<PHP
<?php
namespace RandomPlugin;
class MyService {
   private \$db;

   public function __construct(\\DBMysql \$db) {
      \$this->db = \$db;
   }

   public function getDb() {
      return \$this->db;
   }
}
PHP
                  ],
               ],
               'setup.php' => <<<PHP
<?php
function plugin_version_random() {
   return [
     'di-container-config' => [
        'di/services.yaml'
     ]
   ];
}
PHP
            ]
         ]
      ];
      $directory = vfsStream::setup('glpi', null, $structure);
      // Make a not writable dir to prevent cache on container compilation
      $directory->addChild(vfsStream::newDirectory('nocache', 0555));

      // Autoload does not work for files inside vfsStream
      include_once(vfsStream::url('glpi/plugins/random/src/RandomPlugin/MyService.php'));

      // FIXME Force active plugin list

      $kernel = new \Glpi\Kernel(vfsStream::url('glpi'), vfsStream::url('glpi/nocache'));

      $container = $kernel->getContainer();
      $this->object($container)->isInstanceOf(\Psr\Container\ContainerInterface::class);

      // Check plugin services
      $this->boolean($container->has('random_service'))->isTrue();
      $service = $container->get('random_service');
      $this->object($service)->isInstanceOf('RandomPlugin\\MyService');
      $this->object($service->getDb())->isInstanceOf(\Glpi\Database\MySql::class);
   }

   /**
    * Test that event subscribers are automatically registered in event dispatcher.
    */
   public function testContainerEventSubscribersAutoregistration() {

      $structure = [
         'src' => [
            'Glpi' => [
               'EventSubscriber' => [
                  'MyEventSubscriber.php' => <<<PHP
<?php
namespace Glpi\EventSubscriber;
class MyEventSubscriber implements \Glpi\EventDispatcher\EventSubscriberInterface {

    public static function getSubscribedEvents()
    {
        return [
            'first_event' => 'doSomething',
            'second_event' => [['doSomething'], ['doSomethingElse']],
        ];
    }

    public function doSomething(\Symfony\Component\EventDispatcher\Event \$event)
    {
        return;
    }

    public function doSomethingElse(\Symfony\Component\EventDispatcher\Event \$event)
    {
        return;
    }
}
PHP
               ],
            ],
            'resources' => [
                'services.yaml' =>  <<<YAML
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: true

    Glpi\EventDispatcher\EventDispatcher:
        class: Glpi\EventDispatcher\EventDispatcher
    MyEventSubscriber:
        class: Glpi\EventSubscriber\MyEventSubscriber
YAML
            ],
         ],
      ];
      $directory = vfsStream::setup('glpi', null, $structure);
      // Make a not writable dir to prevent cache on container compilation
      $directory->addChild(vfsStream::newDirectory('nocache', 0555));

      // Autoload does not work for files inside vfsStream
      include_once(vfsStream::url('glpi/src/Glpi/EventSubscriber/MyEventSubscriber.php'));

      $kernel = new \Glpi\Kernel(vfsStream::url('glpi'), vfsStream::url('glpi/nocache'));

      $container = $kernel->getContainer();

      // Check dispatcher service
      $this->boolean($container->has(\Glpi\EventDispatcher\EventDispatcher::class))->isTrue();
      $dispatcher = $container->get(\Glpi\EventDispatcher\EventDispatcher::class);
      $this->object($dispatcher)->isInstanceOf(\Glpi\EventDispatcher\EventDispatcher::class);

      // Check Glpi subscriber declared in services.yaml
      $this->boolean($container->has('MyEventSubscriber'))->isTrue();
      $coreSubscriber = $container->get('MyEventSubscriber');
      $this->object($coreSubscriber)->isInstanceOf('Glpi\EventSubscriber\MyEventSubscriber');

      // Event with unique subscriber from core
      $this->array($dispatcher->getListeners('first_event'))
         ->isEqualTo(
            [
               [
                  $coreSubscriber,
                  'doSomething'
               ],
            ]
         );

      // Event with multiple subscribers from core
      $this->array($dispatcher->getListeners('second_event'))
         ->isEqualTo(
            [
               [
                  $coreSubscriber,
                  'doSomething'
               ],
               [
                  $coreSubscriber,
                  'doSomethingElse'
               ],
            ]
         );

      // Event with no subscribers
      $this->array($dispatcher->getListeners('some_event'))->isEmpty();
   }
}
