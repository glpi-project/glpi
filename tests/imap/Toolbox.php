<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

namespace tests\units;

/* Test for inc/toolbox.class.php */

class Toolbox extends \GLPITestCase {

   protected function mailServerProtocolsProvider() {
      return [
         [
            'cnx_string'        => '',
            'expected_type'     => '',
            'expected_protocol' => null,
            'expected_storage'  => null,
         ],
         [
            'cnx_string'        => '{mail.domain.org/imap}',
            'expected_type'     => 'imap',
            'expected_protocol' => \Laminas\Mail\Protocol\Imap::class,
            'expected_storage'  => \Laminas\Mail\Storage\Imap::class,
         ],
         [
            'cnx_string'        => '{mail.domain.org/imap/ssl/debug}INBOX',
            'expected_type'     => 'imap',
            'expected_protocol' => \Laminas\Mail\Protocol\Imap::class,
            'expected_storage'  => \Laminas\Mail\Storage\Imap::class,
         ],
         [
            'cnx_string'        => '{mail.domain.org/pop}',
            'expected_type'     => 'pop',
            'expected_protocol' => \Laminas\Mail\Protocol\Pop3::class,
            'expected_storage'  => \Laminas\Mail\Storage\Pop3::class,
         ],
         [
            'cnx_string'        => '{mail.domain.org/pop/ssl/tls}',
            'expected_type'     => 'pop',
            'expected_protocol' => \Laminas\Mail\Protocol\Pop3::class,
            'expected_storage'  => \Laminas\Mail\Storage\Pop3::class,
         ],
         [
            'cnx_string'        => '{mail.domain.org/unknown-type/ssl}',
            'expected_type'     => '',
            'expected_protocol' => null,
            'expected_storage'  => null,
         ],
      ];
   }

   /**
    * @dataProvider mailServerProtocolsProvider
    */
   public function testGetMailServerProtocols(
      string $cnx_string,
      string $expected_type,
      ?string $expected_protocol,
      ?string $expected_storage
   ) {
      $type = \Toolbox::parseMailServerConnectString($cnx_string)['type'];

      $this->string($type)->isEqualTo($expected_type);

      if ($expected_protocol !== null) {
         $this->object(\Toolbox::getMailServerProtocolInstance($type))->isInstanceOf($expected_protocol);
      } else {
         $this->variable(\Toolbox::getMailServerProtocolInstance($type))->isNull();
      }

      $params = [
         'host'     => '127.0.0.1',
         'user'     => 'testuser',
         'password' => 'applesauce',
      ];
      if ($expected_storage !== null) {
         $this->object(\Toolbox::getMailServerStorageInstance($type, $params))->isInstanceOf($expected_storage);
      } else {
         $this->variable(\Toolbox::getMailServerStorageInstance($type, $params))->isNull();
      }
   }


   protected function mailServerProtocolsHookProvider() {
      // Create valid classes
      eval(<<<CLASS
class PluginTesterFakeProtocol implements Glpi\Mail\Protocol\ProtocolInterface {
   public function setNoValidateCert(bool \$novalidatecert) {}
   public function connect(\$host, \$port = null, \$ssl = false) {}
   public function login(\$user, \$password) {}
}
class PluginTesterFakeStorage extends Laminas\Mail\Storage\Imap {
   public function __construct(\$params) {}
   public function close() {}
}
CLASS
      );

      return [
         // Check that invalid hook result does not alter core protocols specs
         [
            'hook_result'        => 'invalid result',
            'type'               => 'imap',
            'expected_warning'   => 'Invalid value returned by "mail_server_protocols" hook.',
            'expected_protocol'  => 'Laminas\Mail\Protocol\Imap',
            'expected_storage'   => 'Laminas\Mail\Storage\Imap',
         ],
         // Check that hook cannot alter core protocols specs
         [
            'hook_result'        => [
               'imap' => [
                  'label'    => 'Override test',
                  'protocol' => 'SomeClass',
                  'storage'  => 'SomeClass',
               ],
            ],
            'type'               => 'imap',
            'expected_warning'   => 'Protocol "imap" is already defined and cannot be overwritten.',
            'expected_protocol'  => 'Laminas\Mail\Protocol\Imap',
            'expected_storage'   => 'Laminas\Mail\Storage\Imap',
         ],
         // Check that hook cannot alter core protocols specs
         [
            'hook_result'        => [
               'pop' => [
                  'label'    => 'Override test',
                  'protocol' => 'SomeClass',
                  'storage'  => 'SomeClass',
               ],
            ],
            'type'               => 'pop',
            'expected_warning'   => 'Protocol "pop" is already defined and cannot be overwritten.',
            'expected_protocol'  => 'Laminas\Mail\Protocol\Pop3',
            'expected_storage'   => 'Laminas\Mail\Storage\Pop3',
         ],
         // Check that class must exists
         [
            'hook_result'        => [
               'custom-protocol' => [
                  'label'    => 'Invalid class',
                  'protocol' => 'SomeClass1',
                  'storage'  => 'SomeClass2',
               ],
            ],
            'type'               => 'custom-protocol',
            'expected_warning'   => 'Invalid specs for protocol "custom-protocol".',
            'expected_protocol'  => null,
            'expected_storage'   => null,
         ],
         // Check that class must implements expected functions
         [
            'hook_result'        => [
               'custom-protocol' => [
                  'label'    => 'Invalid class',
                  'protocol' => 'Plugin',
                  'storage'  => 'Migration',
               ],
            ],
            'type'               => 'custom-protocol',
            'expected_warning'   => 'Invalid specs for protocol "custom-protocol".',
            'expected_protocol'  => null,
            'expected_storage'   => null,
         ],
         // Check valid case using class names
         [
            'hook_result'        => [
               'custom-protocol' => [
                  'label'    => 'Custom email protocol',
                  'protocol' => 'PluginTesterFakeProtocol',
                  'storage'  => 'PluginTesterFakeStorage',
               ],
            ],
            'type'               => 'custom-protocol',
            'expected_warning'   => null,
            'expected_protocol'  => 'PluginTesterFakeProtocol',
            'expected_storage'   => 'PluginTesterFakeStorage',
         ],
         // Check valid case using callback
         [
            'hook_result'        => [
               'custom-protocol' => [
                  'label'    => 'Custom email protocol',
                  'protocol' => function () { return new \PluginTesterFakeProtocol(); },
                  'storage'  => function (array $params) { return new \PluginTesterFakeStorage($params); },
               ],
            ],
            'type'               => 'custom-protocol',
            'expected_warning'   => null,
            'expected_protocol'  => 'PluginTesterFakeProtocol',
            'expected_storage'   => 'PluginTesterFakeStorage',
         ],
      ];
   }

   /**
    * @dataProvider mailServerProtocolsHookProvider
    */
   public function testGetAdditionnalMailServerProtocols(
      $hook_result,
      string $type,
      ?string $expected_warning,
      ?string $expected_protocol,
      ?string $expected_storage
   ) {
      global $PLUGIN_HOOKS;

      $hooks_backup = $PLUGIN_HOOKS;

      $PLUGIN_HOOKS['mail_server_protocols']['tester'] = function () use ($hook_result) {
         return $hook_result;
      };

      // Get protocol
      $protocol  = null;
      $getProtocol = function () use ($type, &$protocol) {
         $protocol = \Toolbox::getMailServerProtocolInstance($type);
      };
      if ($expected_warning !== null) {
         $this->when($getProtocol)
            ->error()
            ->withType(E_USER_WARNING)
            ->withMessage($expected_warning)
            ->exists();
      } else {
         $getProtocol();
      }

      // Get storage
      $storage   = null;
      $getStorage = function () use ($type, &$storage) {
         $params = [
            'host'     => '127.0.0.1',
            'user'     => 'testuser',
            'password' => 'applesauce',
         ];
         $storage = \Toolbox::getMailServerStorageInstance($type, $params);
      };
      if ($expected_warning !== null) {
         $this->when($getStorage)
         ->error()
         ->withType(E_USER_WARNING)
         ->withMessage($expected_warning)
         ->exists();
      } else {
         $getStorage();
      }

      $PLUGIN_HOOKS = $hooks_backup;

      if ($expected_protocol !== null) {
         $this->object($protocol)->isInstanceOf($expected_protocol);
      } else {
         $this->variable($protocol)->isNull();
      }

      if ($expected_storage !== null) {
         $this->object($storage)->isInstanceOf($expected_storage);
      } else {
         $this->variable($storage)->isNull();
      }
   }
}
