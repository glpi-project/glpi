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

use PHPMailer\PHPMailer\PHPMailer;
use \DbTestCase;
use Log;
use Session;

/* Test for inc/config.class.php */

class Config extends DbTestCase {

   public function testGetTypeName() {
      $this->string(\Config::getTypeName())->isIdenticalTo('Setup');
   }

   public function testAcls() {
      //check ACLs when not logged
      $this->boolean(\Config::canView())->isFalse();
      $this->boolean(\Config::canCreate())->isFalse();

      $conf = new \Config();
      $this->boolean($conf->canViewItem())->isFalse();

      //check ACLs from superadmin profile
      $this->login();
      $this->boolean((boolean)\Config::canView())->isTrue();
      $this->boolean(\Config::canCreate())->isFalse();
      $this->boolean($conf->canViewItem())->isFalse();

      $this->boolean($conf->getFromDB(1))->isTrue();
      $this->boolean($conf->canViewItem())->isTrue();

      //check ACLs from tech profile
      $auth = new \Auth();
      $this->boolean((boolean)$auth->login('tech', 'tech', true))->isTrue();
      $this->boolean((boolean)\Config::canView())->isFalse();
      $this->boolean(\Config::canCreate())->isFalse();
      $this->boolean($conf->canViewItem())->isTrue();
   }

   public function testGetMenuContent() {
      $this->boolean(\Config::getMenuContent())->isFalse();

      $this->login();
      $this->array(\Config::getMenuContent())
         ->hasSize(4)
         ->hasKeys(['title', 'page', 'options', 'icon']);
   }

   public function testDefineTabs() {
      $expected = [
         'Config$1'      => 'General setup',
         'Config$2'      => 'Default values',
         'Config$3'      => 'Assets',
         'Config$4'      => 'Assistance',
         'GLPINetwork$1' => 'GLPI Network',
         'Log$1'         => 'Historical',
      ];
      $this
         ->given($this->newTestedInstance)
            ->then
               ->array($this->testedInstance->defineTabs())
               ->isIdenticalTo($expected);

      //Standards users do not have extra tabs
      $auth = new \Auth();
      $this->boolean((boolean)$auth->login('tech', 'tech', true))->isTrue();
      $this
         ->given($this->newTestedInstance)
            ->then
               ->array($this->testedInstance->defineTabs())
               ->isIdenticalTo($expected);

      //check extra tabs from superadmin profile
      $this->login();
      $expected = [
         'Config$1'      => 'General setup',
         'Config$2'      => 'Default values',
         'Config$3'      => 'Assets',
         'Config$4'      => 'Assistance',
         'Config$9'      => 'Logs purge',
         'Config$5'      => 'System',
         'Config$10'     => 'Security',
         'Config$7'      => 'Performance',
         'Config$8'      => 'API',
         'Config$11'      => \Impact::getTypeName(),
         'GLPINetwork$1' => 'GLPI Network',
         'Log$1'         => 'Historical',
      ];
      $this
         ->given($this->newTestedInstance)
            ->then
               ->array($this->testedInstance->defineTabs())
               ->isIdenticalTo($expected);
   }

   public function testPrepareInputForUpdate() {
      // /!\ Config::prepareInputForUpdate() do store data! /!\
   }

   public function testUnsetUndisclosedFields() {
      $input = [
         'context'   => 'core',
         'name'      => 'name',
         'value'     => 'value'
      ];
      $expected = $input;

      \Config::unsetUndisclosedFields($input);
      $this->array($input)->isIdenticalTo($expected);

      $input = [
         'context'   => 'core',
         'name'      => 'proxy_passwd',
         'value'     => 'value'
      ];
      $expected = $input;
      unset($expected['value']);

      \Config::unsetUndisclosedFields($input);
      $this->array($input)->isIdenticalTo($expected);

      $input = [
         'context'   => 'core',
         'name'      => 'smtp_passwd',
         'value'     => 'value'
      ];
      $expected = $input;
      unset($expected['value']);

      \Config::unsetUndisclosedFields($input);
      $this->array($input)->isIdenticalTo($expected);
   }

   public function testValidatePassword() {
      global $CFG_GLPI;
      unset($_SESSION['glpicronuserrunning']);
      $this->boolean((bool)$CFG_GLPI['use_password_security'])->isFalse();

      $this->boolean(\Config::validatePassword('mypass'))->isTrue();

      $CFG_GLPI['use_password_security'] = 1;
      $this->integer((int)$CFG_GLPI['password_min_length'])->isIdenticalTo(8);
      $this->integer((int)$CFG_GLPI['password_need_number'])->isIdenticalTo(1);
      $this->integer((int)$CFG_GLPI['password_need_letter'])->isIdenticalTo(1);
      $this->integer((int)$CFG_GLPI['password_need_caps'])->isIdenticalTo(1);
      $this->integer((int)$CFG_GLPI['password_need_symbol'])->isIdenticalTo(1);
      $this->boolean(\Config::validatePassword(''))->isFalse();

      $expected = [
         ERROR => [
            'Password too short!',
            'Password must include at least a digit!',
            'Password must include at least a lowercase letter!',
            'Password must include at least a uppercase letter!',
            'Password must include at least a symbol!'
         ]
      ];
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])
         ->isIdenticalTo($expected);

      $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset
      $expected = [
         ERROR => [
            'Password must include at least a digit!',
            'Password must include at least a uppercase letter!',
            'Password must include at least a symbol!'
         ]
      ];
      $this->boolean(\Config::validatePassword('mypassword'))->isFalse();
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])
         ->isIdenticalTo($expected);

      $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset
      $CFG_GLPI['password_min_length'] = strlen('mypass');
      $this->boolean(\Config::validatePassword('mypass'))->isFalse();
      $CFG_GLPI['password_min_length'] = 8; //reset
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])
         ->isIdenticalTo($expected);

      $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset
      $expected = [
         ERROR => [
            'Password must include at least a uppercase letter!',
            'Password must include at least a symbol!'
         ]
      ];
      $this->boolean(\Config::validatePassword('my1password'))->isFalse();
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])
         ->isIdenticalTo($expected);

      $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset
      $CFG_GLPI['password_need_number'] = 0;
      $this->boolean(\Config::validatePassword('mypassword'))->isFalse();
      $CFG_GLPI['password_need_number'] = 1; //reset
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])
         ->isIdenticalTo($expected);

      $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset
      $expected = [
         ERROR => [
            'Password must include at least a symbol!'
         ]
      ];
      $this->boolean(\Config::validatePassword('my1paSsword'))->isFalse();
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])
         ->isIdenticalTo($expected);

      $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset
      $CFG_GLPI['password_need_caps'] = 0;
      $this->boolean(\Config::validatePassword('my1password'))->isFalse();
      $CFG_GLPI['password_need_caps'] = 1; //reset
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])
         ->isIdenticalTo($expected);

      $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset
      $this->boolean(\Config::validatePassword('my1paSsw@rd'))->isTrue();
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])
         ->isEmpty();

      $_SESSION['MESSAGE_AFTER_REDIRECT'] = []; //reset
      $CFG_GLPI['password_need_symbol'] = 0;
      $this->boolean(\Config::validatePassword('my1paSsword'))->isTrue();
      $CFG_GLPI['password_need_symbol'] = 1; //reset
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'])
         ->isEmpty();
   }

   public function testGetLibraries() {
      $actual = $expected = [];
      $deps = \Config::getLibraries(true);
      foreach ($deps as $dep) {
         // composer names only (skip htmlLawed)
         if (strpos($dep['name'], '/')) {
            $actual[] =$dep['name'];
         }
      }
      sort($actual);
      $this->array($actual)->isNotEmpty();
      $composer = json_decode(file_get_contents(__DIR__ . '/../../composer.json'), true);
      foreach (array_keys($composer['require']) as $dep) {
         // composer names only (skip php, ext-*, ...)
         if (strpos($dep, '/')) {
            $expected[] = $dep;
         }
      }
      sort($expected);
      $this->array($expected)->isNotEmpty();
      $this->array($actual)->isIdenticalTo($expected);
   }

   public function testGetLibraryDir() {
      $this->boolean(\Config::getLibraryDir(''))->isFalse();
      $this->boolean(\Config::getLibraryDir('abcde'))->isFalse();

      $expected = realpath(__DIR__ . '/../../vendor/phpmailer/phpmailer/src');
      if (is_dir($expected)) { // skip when system library is used
         $this->string(\Config::getLibraryDir('PHPMailer\PHPMailer\PHPMailer'))->isIdenticalTo($expected);

         $mailer = new PHPMailer();
         $this->string(\Config::getLibraryDir($mailer))->isIdenticalTo($expected);
      }

      $expected = realpath(__DIR__ . '/../');
      $this->string(\Config::getLibraryDir('getItemByTypeName'))->isIdenticalTo($expected);
   }

   public function testCheckExtensions() {
      $this->array(\Config::checkExtensions())
         ->hasKeys(['error', 'good', 'missing', 'may']);

      $expected= [
         'error'     => 0,
         'good'      => [
            'mysqli' => 'mysqli extension is installed',
         ],
         'missing'   => [],
         'may'       => []
      ];

      //check extension from class name
      $list = [
         'mysqli' => [
            'required'  => true,
            'class'     => 'mysqli'
         ]
      ];
      $report = \Config::checkExtensions($list);
      $this->array($report)->isIdenticalTo($expected);

      //check extension from method name
      $list = [
         'mysqli' => [
            'required'  => true,
            'function'  => 'mysqli_commit'
         ]
      ];
      $report = \Config::checkExtensions($list);
      $this->array($report)->isIdenticalTo($expected);

      //check extension from its name
      $list = [
         'mysqli' => [
            'required'  => true
         ]
      ];
      $report = \Config::checkExtensions($list);
      $this->array($report)->isIdenticalTo($expected);

      //required, missing extension
      $list['notantext'] = [
         'required'  => true
      ];
      $report = \Config::checkExtensions($list);
      $expected= [
         'error'     => 2,
         'good'      => [
            'mysqli' => 'mysqli extension is installed',
         ],
         'missing'   => [
            'notantext' => 'notantext extension is missing'
         ],
         'may'       => []
      ];
      $this->array($report)->isIdenticalTo($expected);

      //not required, missing extension
      unset($list['notantext']);
      $list['totally_optionnal'] = ['required' => false];
      $report = \Config::checkExtensions($list);
      $expected= [
         'error'     => 1,
         'good'      => [
            'mysqli' => 'mysqli extension is installed',
         ],
         'missing'   => [],
         'may'       => [
            'totally_optionnal' => 'totally_optionnal extension is not present'
         ]
      ];
      $this->array($report)->isIdenticalTo($expected);
   }

   public function testGetConfigurationValues() {
      $conf = \Config::getConfigurationValues('core');
      $this->array($conf)
         ->hasKeys(['version', 'dbversion'])
         ->size->isGreaterThan(170);

      $conf = \Config::getConfigurationValues('core', ['version', 'dbversion']);
      $this->array($conf)->isIdenticalTo([
         'dbversion' => GLPI_SCHEMA_VERSION,
         'version'   => GLPI_VERSION
      ]);
   }

   public function testSetConfigurationValues() {
      $conf = \Config::getConfigurationValues('core', ['version', 'notification_to_myself']);
      $this->array($conf)->isIdenticalTo([
         'notification_to_myself'   => '1',
         'version'                  => GLPI_VERSION
      ]);

      //update configuration value
      \Config::setConfigurationValues('core', ['notification_to_myself' => 0]);
      $conf = \Config::getConfigurationValues('core', ['version', 'notification_to_myself']);
      $this->array($conf)->isIdenticalTo([
         'notification_to_myself'   => '0',
         'version'                  => GLPI_VERSION
      ]);
      \Config::setConfigurationValues('core', ['notification_to_myself' => 1]); //reset

      //check new configuration key does not exists
      $conf = \Config::getConfigurationValues('core', ['version', 'new_configuration_key']);
      $this->array($conf)->isIdenticalTo([
         'version' => GLPI_VERSION
      ]);

      //add new configuration key
      \Config::setConfigurationValues('core', ['new_configuration_key' => 'test']);
      $conf = \Config::getConfigurationValues('core', ['version', 'new_configuration_key']);
      $this->array($conf)->isIdenticalTo([
         'new_configuration_key' => 'test',
         'version'               => GLPI_VERSION
      ]);

      //drop new configuration key
      \Config::deleteConfigurationValues('core', ['new_configuration_key']);
      $conf = \Config::getConfigurationValues('core', ['version', 'new_configuration_key']);
      $this->array($conf)->isIdenticalTo([
         'version' => GLPI_VERSION
      ]);
   }

   public function testGetRights() {
      $conf = new \Config();
      $this->array($conf->getRights())->isIdenticalTo([
         READ     => 'Read',
         UPDATE   => 'Update'
      ]);
   }

   public function testGetPalettes() {
      $expected = [
         'aerialgreen'     => 'Aerialgreen',
         'auror'           => 'Auror',
         'automn'          => 'Automn',
         'classic'         => 'Classic',
         'clockworkorange' => 'Clockworkorange',
         'dark'            => 'Dark',
         'darker'          => 'Darker',
         'flood'           => 'Flood',
         'greenflat'       => 'Greenflat',
         'hipster'         => 'Hipster',
         'icecream'        => 'Icecream',
         'lightblue'       => 'Lightblue',
         'premiumred'      => 'Premiumred',
         'purplehaze'      => 'Purplehaze',
         'teclib'          => 'Teclib',
         'vintage'         => 'Vintage'
      ];
      $this
         ->if($this->newTestedInstance)
         ->then
            ->array($this->testedInstance->getPalettes())
            ->isIdenticalTo($expected);

   }

   /**
    * Database engines data provider
    *
    * @return array
    */
   protected function dbEngineProvider() {
      return [
         [
            'raw'       => '10.2.14-MariaDB',
            'version'   => '10.2.14',
            'compat'    => true
         ], [
            'raw'       => '5.5.10-MariaDB',
            'version'   => '5.5.10',
            'compat'    => false
         ], [
            'raw'       => '5.6.38-log',
            'version'   => '5.6.38',
            'compat'    => true
         ], [
            'raw'       => '5-5-57',
            'version'   => '5',
            'compat'    => false
         ], [
            'raw'       => '5-6-31',
            'version'   => '5',
            'compat'    => false // since version is 5, this is not compat.
         ], [
            'raw'       => '10-2-35',
            'version'   => '10',
            'compat'    => true
         ]
      ];
   }

   /**
    * @dataProvider dbEngineProvider
    */
   public function testCheckDbEngine($raw, $version, $compat) {
      global $DB;
      $DB = new \mock\DB();
      $this->calling($DB)->getVersion = $raw;

      $result = \Config::checkDbEngine();
      $this->array($result)->isIdenticalTo([$version => $compat]);
   }

   public function testGetLanguage() {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->getLanguage('fr'))
               ->isIdenticalTo('fr_FR')
            ->string($this->testedInstance->getLanguage('fr_FR'))
               ->isIdenticalTo('fr_FR')
            ->string($this->testedInstance->getLanguage('fr-FR'))
               ->isIdenticalTo('fr_FR')
            ->string($this->testedInstance->getLanguage('Français'))
               ->isIdenticalTo('fr_FR')
            ->string($this->testedInstance->getLanguage('french'))
               ->isIdenticalTo('fr_FR')
            ->string($this->testedInstance->getLanguage('notalang'))
               ->isIdenticalTo('');

   }

   /**
    * Provides list of classes that can be linked to configuration.
    *
    * @return array
    */
   protected function itemtypeLinkedToConfigurationProvider() {
      return [
         [
            'key'      => 'documentcategories_id_forticket',
            'itemtype' => 'DocumentCategory',
         ],
         [
            'key'      => 'default_requesttypes_id',
            'itemtype' => 'RequestType',
         ],
         [
            'key'      => 'softwarecategories_id_ondelete',
            'itemtype' => 'SoftwareCategory',
         ],
         [
            'key'      => 'ssovariables_id',
            'itemtype' => 'SsoVariable',
         ],
         [
            'key'      => 'transfers_id_auto',
            'itemtype' => 'Transfer',
         ],
      ];
   }

   /**
    * Check that relation between items and configuration are correctly cleaned.
    *
    * @param string $key
    * @param string $itemtype
    *
    * @dataProvider itemtypeLinkedToConfigurationProvider
    */
   public function testCleanRelationDataOfLinkedItems($key, $itemtype) {

      // Case 1: used item is cleaned without replacement
      $item = new $itemtype();
      $item->fields = ['id' => 15];

      \Config::setConfigurationValues('core', [$key => $item->fields['id']]);

      if (is_a($itemtype, 'CommonDropdown', true)) {
         $this->boolean($item->isUsed())->isTrue();
      }
      $item->cleanRelationData();
      if (is_a($itemtype, 'CommonDropdown', true)) {
         $this->boolean($item->isUsed())->isFalse();
      }
      $this->array(\Config::getConfigurationValues('core', [$key]))
         ->hasKey($key)
         ->variable[$key]->isEqualTo(0);

      // Case 2: unused item is cleaned without effect
      $item = new $itemtype();
      $item->fields = ['id' => 15];

      $random_id = mt_rand(20, 100);

      \Config::setConfigurationValues('core', [$key => $random_id]);

      if (is_a($itemtype, 'CommonDropdown', true)) {
         $this->boolean($item->isUsed())->isFalse();
      }
      $item->cleanRelationData();
      if (is_a($itemtype, 'CommonDropdown', true)) {
         $this->boolean($item->isUsed())->isFalse();
      }
      $this->array(\Config::getConfigurationValues('core', [$key]))
         ->hasKey($key)
         ->variable[$key]->isEqualTo($random_id);

      // Case 3: used item is cleaned with replacement (CommonDropdown only)
      if (is_a($itemtype, 'CommonDropdown', true)) {
         $replacement_item = new $itemtype();
         $replacement_item->fields = ['id' => 12];

         $item = new $itemtype();
         $item->fields = ['id' => 15];
         $item->input = ['_replace_by' => $replacement_item->fields['id']];

         \Config::setConfigurationValues('core', [$key => $item->fields['id']]);

         $this->boolean($item->isUsed())->isTrue();
         $this->boolean($replacement_item->isUsed())->isFalse();
         $item->cleanRelationData();
         $this->boolean($item->isUsed())->isFalse();
         $this->boolean($replacement_item->isUsed())->isTrue();
         $this->array(\Config::getConfigurationValues('core', [$key]))
            ->hasKey($key)
            ->variable[$key]
               ->isEqualTo($replacement_item->fields['id']);
      }
   }

   public function testDevicesInMenu() {
      global $CFG_GLPI, $DB;

      $conf = new \Config();
      $this->array($CFG_GLPI['devices_in_menu'])->isIdenticalTo([
         'Item_DeviceSimcard'
      ]);

      //Config::prepareInputForUpdate() always return false.
      $conf->update([
         'id'                       => 1,
         '_update_devices_in_menu'  => 1,
         'devices_in_menu'          => ['Item_DeviceSimcard', 'Item_DeviceBattery']
      ]);

      //check values in db
      $res = $DB->request([
         'SELECT' => 'value',
         'FROM'   => $conf->getTable(),
         'WHERE'  => ['name' => 'devices_in_menu']
      ])->next();
      $this->array($res)->isIdenticalTo(
         ['value' => exportArrayToDB(['Item_DeviceSimcard', 'Item_DeviceBattery'])]
      );
   }

   /**
    * Test password expiration delay configuration update.
    */
   public function testPasswordExpirationDelayUpdate() {
      global $DB;

      $conf = new \Config();
      $crontask = new \CronTask();

      // create some non local users for the test
      foreach ([\Auth::LDAP, \Auth::EXTERNAL, \Auth::CAS] as $authtype) {
         $user = new \User();
         $user_id = $user->add(
            [
               'name'     => 'test_user_' . mt_rand(),
               'authtype' => $authtype,
            ]
         );
         $this->integer($user_id)->isGreaterThan(0);
      }

      // get count of users using local auth
      $local_users_count = countElementsInTable(
         \User::getTable(),
         ['authtype' => \Auth::DB_GLPI]
      );
      // get count of users using external auth
      $external_users_count = countElementsInTable(
         \User::getTable(),
         ['NOT' => ['authtype' => \Auth::DB_GLPI]]
      );
      // reset 'password_last_update' to null for the test
      $DB->update(\User::getTable(), ['password_last_update' => null], [true]);

      // initial data:
      //  - password expiration is not active
      //  - users from installation data have no value for password_last_update
      //  - crontask is not active
      $values = \Config::getConfigurationValues('core');
      $this->array($values)->hasKey('password_expiration_delay');
      $this->integer((int)$values['password_expiration_delay'])->isIdenticalTo(-1);
      $this->integer(
         countElementsInTable(
            \User::getTable(),
            ['authtype' => \Auth::DB_GLPI, 'password_last_update' => null]
         )
      )->isEqualTo($local_users_count);
      $this->integer(
         countElementsInTable(
            \User::getTable(),
            ['NOT' => ['authtype' => \Auth::DB_GLPI], 'password_last_update' => null]
         )
      )->isEqualTo($external_users_count);
      $this->boolean($crontask->getFromDBbyName(\User::getType(), 'passwordexpiration'))->isTrue();
      $this->integer((int)$crontask->fields['state'])->isIdenticalTo(0);

      // check that activation of password expiration reset `password_last_update` to current date
      // for all local users but not for external users
      // and activate passwordexpiration crontask
      $current_time = $_SESSION['glpi_currenttime'];
      $update_datetime = date('Y-m-d H:i:s', strtotime('-15 days')); // arbitrary date
      $_SESSION['glpi_currenttime'] = $update_datetime;
      $conf->update(
         [
            'id'                        => 1,
            'password_expiration_delay' => 30
         ]
      );
      $_SESSION['glpi_currenttime'] = $current_time;
      $values = \Config::getConfigurationValues('core');
      $this->array($values)->hasKey('password_expiration_delay');
      $this->integer((int)$values['password_expiration_delay'])->isIdenticalTo(30);
      $this->integer(
         countElementsInTable(
            \User::getTable(),
            ['authtype' => \Auth::DB_GLPI, 'password_last_update' => $update_datetime]
         )
      )->isEqualTo($local_users_count);
      $this->integer(
         countElementsInTable(
            \User::getTable(),
            ['NOT' => ['authtype' => \Auth::DB_GLPI], 'password_last_update' => null]
         )
      )->isEqualTo($external_users_count);
      $this->boolean($crontask->getFromDBbyName(\User::getType(), 'passwordexpiration'))->isTrue();
      $this->integer((int)$crontask->fields['state'])->isIdenticalTo(1);

      // check that changing password expiration delay does not reset `password_last_update` to current date
      // if password expiration was already active
      $current_time = $_SESSION['glpi_currenttime'];
      $new_update_datetime = date('Y-m-d H:i:s', strtotime('-5 days')); // arbitrary date
      $_SESSION['glpi_currenttime'] = $new_update_datetime;
      $conf->update(
         [
            'id'                        => 1,
            'password_expiration_delay' => 45
         ]
      );
      $_SESSION['glpi_currenttime'] = $current_time;
      $values = \Config::getConfigurationValues('core');
      $this->array($values)->hasKey('password_expiration_delay');
      $this->integer((int)$values['password_expiration_delay'])->isIdenticalTo(45);
      $this->integer(
         countElementsInTable(
            \User::getTable(),
            ['authtype' => \Auth::DB_GLPI, 'password_last_update' => $update_datetime] // previous config update
         )
      )->isEqualTo($local_users_count);
      $this->integer(
         countElementsInTable(
            \User::getTable(),
            ['NOT' => ['authtype' => \Auth::DB_GLPI], 'password_last_update' => null]
         )
      )->isEqualTo($external_users_count);
   }

   protected function logConfigChangeProvider() {
      global $PLUGIN_HOOKS;

      $PLUGIN_HOOKS['secured_configs']['tester'] = ['passwd'];

      return [
         [
            'context'          => 'core',
            'name'             => 'unexisting_config',
            'is_secured'       => false,
            'old_value_prefix' => 'unexisting_config ',
         ],
         [
            'context'          => 'plugin:tester',
            'name'             => 'check',
            'is_secured'       => false,
            'old_value_prefix' => 'check (plugin:tester) ',
         ],
         [
            'context'          => 'plugin:tester',
            'name'             => 'passwd',
            'is_secured'       => true,
            'old_value_prefix' => 'passwd (plugin:tester) ',
         ]
      ];
   }

   /**
    * @dataProvider logConfigChangeProvider
    */
   public function testLogConfigChange(string $context, string $name, bool $is_secured, string $old_value_prefix) {
      $history_crit = ['itemtype' => \Config::getType(), 'old_value' => ['LIKE', $name . ' %']];

      $expected_history = [];
      $history_entry_fields = [
         'itemtype'         => \Config::getType(),
         'items_id'         => 1,
         'itemtype_link'    => '',
         'linked_action'    => 0,
         'user_name'        => Session::getLoginUserID(false),
         'date_mod'         => $_SESSION['glpi_currenttime'],
         'id_search_option' => 1,
      ];

      $clean_ids = function (&$value, $key) {
         unset($value['id']);
      };

      // History on first value
      \Config::setConfigurationValues($context, [$name => 'first value']);
      $expected_history = [
         $history_entry_fields + [
            'old_value' => $old_value_prefix . ($is_secured ? '********' : ''),
            'new_value' => $is_secured ? '********' : 'first value',
         ],
      ];

      $found_history = array_values(getAllDataFromTable(Log::getTable(), $history_crit));
      array_walk($found_history, $clean_ids);
      $this->array($found_history)->isEqualTo($expected_history);

      // History on updated value
      \Config::setConfigurationValues($context, [$name => 'new value']);
      $expected_history[] = $history_entry_fields + [
         'old_value' => $old_value_prefix . ($is_secured ? '********' : 'first value'),
         'new_value' => $is_secured ? '********' : 'new value',
      ];

      $found_history = array_values(getAllDataFromTable(Log::getTable(), $history_crit));
      array_walk($found_history, $clean_ids);
      $this->array($found_history)->isEqualTo($expected_history);

      // History on config deletion
      \Config::deleteConfigurationValues($context, [$name]);
      $expected_history[] = $history_entry_fields + [
         'old_value' => $old_value_prefix . ($is_secured ? '********' : 'new value'),
         'new_value' => $is_secured ? '********' : '',
      ];

      $found_history = array_values(getAllDataFromTable(Log::getTable(), $history_crit));
      array_walk($found_history, $clean_ids);
      $this->array($found_history)->isEqualTo($expected_history);
   }
}
