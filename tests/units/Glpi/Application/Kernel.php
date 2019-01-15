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

namespace tests\units\Glpi\Application;

use org\bovigo\vfs\vfsStream;

/* Test for src/Glpi/Kernel.php */

class Kernel extends \GLPITestCase {

   /**
    * Test container compilation with empty configuration (except Plugin which is used during compilation).
    */
   public function testContainerCompilation() {

      $structure = [
         'resources' => [
            'services.yaml' => <<<YAML
services:
  Plugin:
    class: 'Plugin'
    public: true
YAML
         ],
      ];
      $directory = vfsStream::setup('glpi', null, $structure);
      // Make a not writable dir to prevent cache on container compilation
      $directory->addChild(vfsStream::newDirectory('nocache', 0555));

      $kernel = new \Glpi\Application\Kernel(vfsStream::url('glpi'), vfsStream::url('glpi/nocache'));

      $container = $kernel->getContainer();
      $this->object($container)->isInstanceOf(\Psr\Container\ContainerInterface::class);

      // Check Glpi synthetic services
      $this->boolean($container->has(\DBmysql::class))->isTrue();
      $this->object($container->get(\DBmysql::class))->isInstanceOf(\DBmysql::class);
      $this->boolean($container->has(\Glpi\Cache\SimpleCache::class))->isTrue();
      $this->object($container->get(\Glpi\Cache\SimpleCache::class))->isInstanceOf(\Glpi\Cache\SimpleCache::class);
   }

   /**
    * Test compilation of services configured in resources/service.yaml file.
    */
   public function testContainerConfiguredServicesCompilation() {

      $structure = [
         'resources' => [
            'services.yaml' => <<<YAML
services:
  _defaults:
    autowire: true
    public: true

  Plugin:
    class: 'PluginMock'
  DBmysql:
    synthetic: true

  fake_service:
    class: FakeService
YAML
         ],
         'inc' => [
            'fakeservice.class.php' => <<<PHP
<?php
class FakeService {
   private \$db;

   public function __construct(\DBMysql \$db) {
      \$this->db = \$db;
   }

   public function getDb() {
      return \$this->db;
   }
}
PHP
            ,
            'plugin.class.php' => $this->getPluginServiceMockFile(),
         ],
         'plugins' => [
            'random' => [
               'resources' => [
                  'services.yaml' => <<<YAML
services:
  _defaults:
    autowire: true
    public: true
  random_service:
    class: RandomPlugin\\MyService
YAML
               ],
               'inc' => [
                  'randomplugin' => [
                     'myservice.class.php' => <<<PHP
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
            ],
            'test' => [
               'resources' => [], // test plugin has no service.yaml file, compilation should just ignore it
            ],
         ],
      ];
      $directory = vfsStream::setup('glpi', null, $structure);
      // Make a not writable dir to prevent cache on container compilation
      $directory->addChild(vfsStream::newDirectory('nocache', 0555));

      // Autoload does not work for files inside vfsStream
      include_once(vfsStream::url('glpi/inc/fakeservice.class.php'));
      include_once(vfsStream::url('glpi/inc/plugin.class.php'));
      include_once(vfsStream::url('glpi/plugins/random/inc/randomplugin/myservice.class.php'));

      $kernel = new \Glpi\Application\Kernel(vfsStream::url('glpi'), vfsStream::url('glpi/nocache'));

      $container = $kernel->getContainer();
      $this->object($container)->isInstanceOf(\Psr\Container\ContainerInterface::class);

      // Check Glpi service declared in services.yaml
      $this->boolean($container->has('fake_service'))->isTrue();
      $service = $container->get('fake_service');
      $this->object($service)->isInstanceOf('FakeService');
      $this->object($service->getDb())->isInstanceOf(\DBmysql::class);

      // Check plugin services
      $this->boolean($container->has('random_service'))->isTrue();
      $service = $container->get('random_service');
      $this->object($service)->isInstanceOf('RandomPlugin\\MyService');
      $this->object($service->getDb())->isInstanceOf(\DBmysql::class);
   }

   /**
    * Test that event subscribers are automatically registered in event dispatcher.
    */
   public function testContainerEventSubscribersAutoregistration() {

      $structure = [
         'inc' => [
            'glpi' => [
               'eventsubscriber' => [
                  'myeventsubscriber.class.php' => <<<PHP
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
            'plugin.class.php' => $this->getPluginServiceMockFile(),
         ],
         'resources' => [
            'services.yaml' =>  <<<YAML
services:
  _defaults:
    autowire: true
    autoconfigure: true
    public: true

  Plugin:
    class: 'PluginMock'
  Glpi\EventDispatcher\EventDispatcher:
    class: Glpi\EventDispatcher\EventDispatcher
  MyEventSubscriber:
    class: Glpi\EventSubscriber\MyEventSubscriber
YAML
         ],
         'plugins' => [
            'random' => [
               'resources' => [
                  'services.yaml' => <<<YAML
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: true

    PluginSubscriber:
        class: RandomPlugin\\PluginSubscriber
YAML
               ],
               'inc' => [
                  'randomplugin' => [
                     'pluginsubscriber.class.php' => <<<PHP
<?php
namespace RandomPlugin;
class PluginSubscriber implements \Glpi\EventDispatcher\EventSubscriberInterface {

    public static function getSubscribedEvents()
    {
        return [
            'first_event' => 'doWhateverYouWant',
            'another_event' => 'doWhateverYouWant'
        ];
    }

    public function doWhateverYouWant(\Symfony\Component\EventDispatcher\Event \$event)
    {
        return;
    }
}
PHP
                  ],
               ],
            ]
         ]
      ];
      $directory = vfsStream::setup('glpi', null, $structure);
      // Make a not writable dir to prevent cache on container compilation
      $directory->addChild(vfsStream::newDirectory('nocache', 0555));

      // Autoload does not work for files inside vfsStream
      include_once(vfsStream::url('glpi/inc/glpi/eventsubscriber/myeventsubscriber.class.php'));
      include_once(vfsStream::url('glpi/inc/plugin.class.php'));
      include_once(vfsStream::url('glpi/plugins/random/inc/randomplugin/pluginsubscriber.class.php'));

      $kernel = new \Glpi\Application\Kernel(vfsStream::url('glpi'), vfsStream::url('glpi/nocache'));

      $container = $kernel->getContainer();

      // Check dispatcher service
      $this->boolean($container->has(\Glpi\EventDispatcher\EventDispatcher::class))->isTrue();
      $dispatcher = $container->get(\Glpi\EventDispatcher\EventDispatcher::class);
      $this->object($dispatcher)->isInstanceOf(\Glpi\EventDispatcher\EventDispatcher::class);

      // Check Glpi subscriber declared in services.yaml
      $this->boolean($container->has('MyEventSubscriber'))->isTrue();
      $coreSubscriber = $container->get('MyEventSubscriber');
      $this->object($coreSubscriber)->isInstanceOf('Glpi\EventSubscriber\MyEventSubscriber');

      // Check Plugin subscriber
      $this->boolean($container->has('PluginSubscriber'))->isTrue();
      $pluginSubscriber = $container->get('PluginSubscriber');
      $this->object($pluginSubscriber)->isInstanceOf('RandomPlugin\PluginSubscriber');

      // Event with unique subscriber from core
      $this->array($dispatcher->getListeners('first_event'))
         ->isEqualTo(
            [
               [
                  $coreSubscriber,
                  'doSomething'
               ],
               [
                  $pluginSubscriber,
                  'doWhateverYouWant'
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

   /**
    * Test container compilation with failure on plugin compilation.
    */
   public function testContainerPluginCompilationFailure() {

      $structure = [
         'resources' => [
            'services.yaml' => <<<YAML
services:
  Plugin:
    class: 'PluginMock'
    public: true
YAML
         ],
         'inc' => [
            'plugin.class.php' => $this->getPluginServiceMockFile(),
         ],
         'plugins' => [
            'random' => [
               'resources' => [
                  'services.yaml' => <<<YAML
services:
  invalid_service:
    public: false
    synthetic: true
YAML
               ],
            ],
         ],
      ];
      $directory = vfsStream::setup('glpi', null, $structure);
      // Make a not writable dir to prevent cache on container compilation
      $directory->addChild(vfsStream::newDirectory('nocache', 0555));

      $kernel = null;
      $this->when(
         function () use ($kernel) {
            $kernel = new \Glpi\Application\Kernel(vfsStream::url('glpi'), vfsStream::url('glpi/nocache'));
         }
      )->error()
         ->withType(E_USER_WARNING)
         ->withPattern('/Unable to compile container including plugin services, only core services are available\./')
         ->exists();

      // TODO Find a way to be able to check that container is generated with only core services
      // This is not possible for the moment as any usage of trigger_error results in exception throwing in tests context
      return;

      $container = $kernel->getContainer();
      $this->object($container)->isInstanceOf(\Psr\Container\ContainerInterface::class);

      // Check Glpi synthetic services
      $this->boolean($container->has(\DBmysql::class))->isTrue();
      $this->object($container->get(\DBmysql::class))->isInstanceOf(\DBmysql::class);
      $this->boolean($container->has(\Glpi\Cache\SimpleCache::class))->isTrue();
      $this->object($container->get(\Glpi\Cache\SimpleCache::class))->isInstanceOf(\Glpi\Cache\SimpleCache::class);
   }

   /**
    * Return plugin service mock file content that can be used to make mocked plugins uable
    * during Kernel compilation.
    *
    * @return string
    */
   private function getPluginServiceMockFile() {
      return <<<PHP
<?php
class PluginMock {
   public function getPlugins() {
      return [
         'random',
         'test'
      ];
   }
}
PHP;
   }
}
