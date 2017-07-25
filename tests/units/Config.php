<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

use \DbTestCase;

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
      $this->boolean((boolean)$auth->Login('tech', 'tech', true))->isTrue();
      $this->boolean((boolean)\Config::canView())->isFalse();
      $this->boolean(\Config::canCreate())->isFalse();
      $this->boolean($conf->canViewItem())->isTrue();
   }

   public function testGetMenuContent() {
      $this->boolean(\Config::getMenuContent())->isFalse();

      $this->login();
      $this->array(\Config::getMenuContent())
         ->hasSize(3)
         ->hasKeys(['title', 'page', 'options']);
   }

   public function testDefineTabs() {
      $expected = [
         'Config$1'  => 'General setup',
         'Config$2'  => 'Default values',
         'Config$3'  => 'Assets',
         'Config$4'  => 'Assistance'
      ];
      $this
         ->given($this->newTestedInstance)
            ->then
               ->array($this->testedInstance->defineTabs())
               ->isIdenticalTo($expected);

      //Standards users do not have extra tabs
      $auth = new \Auth();
      $this->boolean((boolean)$auth->Login('tech', 'tech', true))->isTrue();
      $this
         ->given($this->newTestedInstance)
            ->then
               ->array($this->testedInstance->defineTabs())
               ->isIdenticalTo($expected);

      //check extra tabs from superadmin profile
      $this->login();
      $expected['Config$5'] = 'System';
      $expected['Config$7'] = 'Performance';
      $expected['Config$8'] = 'API';
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

   public function testGetLibraryDir() {
      $this->boolean(\Config::getLibraryDir(''))->isFalse();
      $this->boolean(\Config::getLibraryDir('abcde'))->isFalse();

      $expected = realpath(__DIR__ . '/../../vendor/phpmailer/phpmailer');
      $this->string(\Config::getLibraryDir('PHPMailer'))->isIdenticalTo($expected);

      $mailer = new \PHPMailer();
      $this->string(\Config::getLibraryDir($mailer))->isIdenticalTo($expected);

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
}
