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
use Symfony\Component\Yaml\Yaml;

/**
 * Test class for src/Glpi/Application/LocalConfigurationManager.php.
 */
class LocalConfigurationManager extends \GLPITestCase {

   /**
    * Mapping between legacy cache configuration and local config file parameters.
    *
    * @return array
    */
   protected function legacyCacheConfigValuesProvider(): array {
      return [
         // Case: nothing to do
         [
            'legacy_config'   => [
            ],
            'expected_result' => [
            ],
         ],

         // Case: application cache using filesystem adapter and relative cache dir path
         [
            'legacy_config'   => [
               'cache_db' => '{"adapter":"filesystem","options":{"cache_dir":"_cache_db"}}'
            ],
            'expected_result' => [
               'application_cache' => [
                  'adapter' => 'filesystem',
                  'options' => [
                     'cache_dir' => vfsStream::url('glpi/cache/_cache_db'),
                  ]
               ]
            ],
         ],

         // Case: translation cache using dba adapter and relative db path
         [
            'legacy_config'   => [
               'cache_trans' => '{"adapter":"dba","options":{"pathname":"trans.db"}}'
            ],
            'expected_result' => [
               'translation_cache' => [
                  'adapter' => 'dba',
                  'options' => [
                     'pathname' => vfsStream::url('glpi/cache/trans.db'),
                  ]
               ]
            ],
         ],

         // Case: both application and translation cache configured
         [
            'legacy_config'   => [
               'cache_db'    => '{"adapter":"redis","options":{"server":{"host":"127.0.0.1"}},"plugins":["serializer"]}',
               'cache_trans' => '{"adapter":"memcached","options":{"servers":["127.0.0.1"]}}'
            ],
            'expected_result' => [
               'application_cache' => [
                  'adapter' => 'redis',
                  'options' => [
                     'server' => ['host' => '127.0.0.1'],
                  ],
                  'plugins' => [
                     'serializer',
                  ]
               ],
               'translation_cache' => [
                  'adapter' => 'memcached',
                  'options' => [
                     'servers' => ['127.0.0.1'],
                  ]
               ]
            ],
         ],

         // Case: application cache badly configured but translation cache correctly configured
         [
            'legacy_config'   => [
               'cache_db'    => 'this is not a JSON string',
               'cache_trans' => '{"adapter":"memcached","options":{"servers":["127.0.0.1"]}}'
            ],
            'expected_result' => [
               'translation_cache' => [
                  'adapter' => 'memcached',
                  'options' => [
                     'servers' => ['127.0.0.1'],
                  ]
               ]
            ],
         ],

         // Case: application cache correctly configured and translation cache empty
         [
            'legacy_config'   => [
               'cache_db'    => '{"adapter":"filesystem","options":{"cache_dir":"_cache_db"}}',
               'cache_trans' => '{}'
            ],
            'expected_result' => [
               'application_cache' => [
                  'adapter' => 'filesystem',
                  'options' => [
                     'cache_dir' => vfsStream::url('glpi/cache/_cache_db'),
                  ]
               ]
            ],
         ],
      ];
   }

   /**
    * Test that values set from legacy cache configuration are correct.
    *
    * @dataProvider legacyCacheConfigValuesProvider
    */
   public function testSetCacheValuesFromLegacyConfig(array $legacyConfig, array $expectedResult) {
      vfsStream::setup(
         'glpi',
         null,
         [
            'cache' => [],
            'config' => [],
         ]
      );

      $this->newTestedInstance(
         vfsStream::url('glpi/config'),
         new \mock\Symfony\Component\PropertyAccess\PropertyAccessor(),
         new \mock\Symfony\Component\Yaml\Yaml()
      );

      $this->testedInstance->setCacheValuesFromLegacyConfig(
         new \mock\Glpi\ConfigParams($legacyConfig),
         vfsStream::url('glpi/cache')
      );

      $filename = vfsStream::url('glpi/config/parameters.yaml');

      if (empty($expectedResult)) {
         // Local parameters file is not created if expected result is empty
         $this->boolean(file_exists($filename))->isFalse();
      } else {
         $this->boolean(file_exists($filename))->isTrue();

         $parameters = file_get_contents($filename);
         $this->array(Yaml::parse($parameters))->isEqualTo(['parameters' => $expectedResult]);
      }
   }

   /**
    * Test setParameterValue method.
    */
   public function testSetParameterValue() {
      vfsStream::setup(
         'glpi',
         null,
         [
            'cache' => [],
            'config' => [
               'parameters.yaml' => <<<YAML
parameters:
    param1: 'This value should be overwritten'
    param2: 'This value should be overwritten too'
    param3: 'This value should not be overwritten'
YAML
            ],
         ]
      );

      $this->newTestedInstance(
         vfsStream::url('glpi/config'),
         new \mock\Symfony\Component\PropertyAccess\PropertyAccessor(),
         new \mock\Symfony\Component\Yaml\Yaml()
      );

      $this->testedInstance->setParameterValue('[param1]', 'new value 1');
      $this->testedInstance->setParameterValue('[param2]', 'new value 2', $overwrite = true);
      $this->testedInstance->setParameterValue('[param3]', 'new value 3', $overwrite = false);

      $result = Yaml::parseFile(vfsStream::url('glpi/config/parameters.yaml'));

      $this->array($result)->hasKey('parameters');
      $this->array($result['parameters'])->hasKey('param1');
      $this->string($result['parameters']['param1'])->isEqualTo('new value 1');
      $this->array($result['parameters'])->hasKey('param2');
      $this->string($result['parameters']['param2'])->isEqualTo('new value 2');
      $this->array($result['parameters'])->hasKey('param3');
      $this->string($result['parameters']['param3'])->isEqualTo('This value should not be overwritten');
   }
}
