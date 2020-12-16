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

use \DbTestCase;
use Group;
use Group_User;

/* Test for inc/authldap.class.php */

class AuthLDAP extends DbTestCase {
   private $ldap;

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
      $this->ldap = getItemByTypeName('AuthLDAP', '_local_ldap');

      //make sure bootstrapped ldap is active and is default
      $this->boolean(
         $this->ldap->update([
            'id'                => $this->ldap->getID(),
            'is_active'         => 1,
            'is_default'        => 1,
            'responsible_field' => "manager",
         ])
      )->isTrue();
   }

   public function afterTestMethod($method) {
      unset($_SESSION['ldap_import']);
      parent::afterTestMethod($method);
   }

   /**
    * Test LDAP connection
    *
    * @extensions ldap
    *
    * @return void
    */
   public function testTestLDAPConnection() {
      $this->boolean(\AuthLDAP::testLDAPConnection(-1))->isFalse();

      $ldap = getItemByTypeName('AuthLDAP', '_local_ldap');
      $this->boolean(\AuthLDAP::testLDAPConnection($ldap->getID()))->isTrue();

      $this->resource($ldap->connect())->isOfType('ldap link');
   }

   /**
    * Test get users
    *
    * @extensions ldap
    *
    * @return void
    */
   public function testGetAllUsers() {
      $ldap = $this->ldap;
      $results = [];
      $limit = false;

      $users = \AuthLDAP::getAllUsers(
         [
            'authldaps_id' => $ldap->getID(),
            'ldap_filter'  => \AuthLDAP::buildLdapFilter($ldap),
            'mode'         => \AuthLDAP::ACTION_IMPORT
         ],
         $results,
         $limit
      );

      $this->array($users)->hasSize(910);
      $this->array($results)->hasSize(0);

      $_SESSION['ldap_import']['interface'] = \AuthLDAP::SIMPLE_INTERFACE;
      $_SESSION['ldap_import']['criterias'] = ['login_field' => 'brazil2'];

      $users = \AuthLDAP::getAllUsers(
         [
            'authldaps_id' => $ldap->getID(),
            'ldap_filter'  => \AuthLDAP::buildLdapFilter($ldap),
            'mode'         => \AuthLDAP::ACTION_IMPORT,
         ],
         $results,
         $limit
      );

      $this->array($users)->hasSize(12);
      $this->array($results)->hasSize(0);

      $_SESSION['ldap_import']['criterias'] = ['login_field' => 'remi'];

      $users = \AuthLDAP::getAllUsers(
         [
            'authldaps_id' => $ldap->getID(),
            'ldap_filter'  => \AuthLDAP::buildLdapFilter($ldap),
            'mode'         => \AuthLDAP::ACTION_IMPORT,
         ],
         $results,
         $limit
      );

      $this->array($users)->hasSize(1);
      $this->array($results)->hasSize(0);
   }

   /**
    * Test get groups
    *
    * @extensions ldap
    *
    * @return void
    */
   public function testGetAllGroups() {
      $ldap = $this->ldap;
      $limit = false;

      $groups = \AuthLDAP::getAllGroups(
         $ldap->getID(),
         \AuthLDAP::buildLdapFilter($ldap),
         '',
         0,
         $limit
      );

      $this->array($groups)->hasSize(910);

      /** TODO: filter search... I do not know how to do. */
   }

   /**
    * Test import user
    *
    * @extensions ldap
    *
    * @return void
    */
   public function testLdapImportUserByServerId() {
      $ldap = $this->ldap;
      $results = [];
      $limit = false;

      //get user to import
      $_SESSION['ldap_import']['interface'] = \AuthLDAP::SIMPLE_INTERFACE;
      $_SESSION['ldap_import']['criterias'] = ['login_field' => 'ecuador0'];

      $users = \AuthLDAP::getAllUsers(
         [
            'authldaps_id' => $ldap->getID(),
            'ldap_filter'  => \AuthLDAP::buildLdapFilter($ldap),
            'mode'         => \AuthLDAP::ACTION_IMPORT,
         ],
         $results,
         $limit
      );

      $this->array($users)->hasSize(1);
      $this->array($results)->hasSize(0);

      $import = \AuthLDAP::ldapImportUserByServerId(
         [
            'method' => \AuthLDAP::IDENTIFIER_LOGIN,
            'value'  => 'ecuador0'
         ],
         \AuthLDAP::ACTION_IMPORT,
         $ldap->getID(),
         true
      );
      $this->array($import)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_IMPORTED)
         ->integer['id']->isGreaterThan(0);

      //check created user
      $user = new \User();
      $this->boolean($user->getFromDB($import['id']))->isTrue();

      $this->array($user->fields)
         ->string['name']->isIdenticalTo('ecuador0')
         ->string['phone']->isIdenticalTo('034596780')
         ->string['realname']->isIdenticalTo('dor0')
         ->string['firstname']->isIdenticalTo('ecua0')
         ->string['language']->isIdenticalTo('es_ES')
         ->variable['is_active']->isEqualTo(true)
         ->variable['auths_id']->isEqualTo($ldap->getID())
         ->variable['authtype']->isEqualTo(\Auth::LDAP)
         ->string['user_dn']->isIdenticalTo('uid=ecuador0,ou=people,ou=ldap3,dc=glpi,dc=org');

      $this->integer((int)$user->fields['usertitles_id'])->isGreaterThan(0);
      $this->integer((int)$user->fields['usercategories_id'])->isGreaterThan(0);
   }

   /**
    * Test get groups
    *
    * @extensions ldap
    *
    * @return void
    */
   public function testGetGroupCNByDn() {
      $ldap = $this->ldap;

      $connection = $ldap->connect();
      $this->resource($connection)->isOfType('ldap link');

      $cn = \AuthLDAP::getGroupCNByDn($connection, 'ou=not,ou=exists,dc=glpi,dc=org');
      $this->boolean($cn)->isFalse();

      $cn = \AuthLDAP::getGroupCNByDn($connection, 'cn=glpi2-group1,ou=groups,ou=usa,ou=ldap2, dc=glpi,dc=org');
      $this->string($cn)->isIdenticalTo('glpi2-group1');
   }

   /**
    * Test get user by dn
    *
    * @extensions ldap
    *
    * @return void
    */
   public function testGetUserByDn() {
      $ldap = $this->ldap;

      $user = \AuthLDAP::getUserByDn(
         $ldap->connect(),
         'uid=walid,ou=people,ou=france,ou=europe,ou=ldap1, dc=glpi,dc=org',
         []
      );

      $this->array($user)
         ->hasSize(12)
         ->hasKeys(['userpassword', 'uid', 'objectclass', 'sn']);
   }

   /**
    * Test get group
    *
    * @extensions ldap
    *
    * @return void
    */
   public function testGetGroupByDn() {
      $ldap = $this->ldap;

      $group = \AuthLDAP::getGroupByDn(
         $ldap->connect(),
         'cn=glpi2-group1,ou=groups,ou=usa,ou=ldap2, dc=glpi,dc=org'
      );

      $this->array($group)->isIdenticalTo([
         'cn'     => [
           'count'   => 1,
            0        => 'glpi2-group1',
         ],
         0        => 'cn',
         'count'  => 1,
         'dn'     => 'cn=glpi2-group1,ou=groups,ou=usa,ou=ldap2,dc=glpi,dc=org'
      ]);
   }

   /**
    * Test import group
    *
    * @extensions ldap
    *
    * @return void
    */
   public function testLdapImportGroup() {
      $ldap = $this->ldap;

      $import = \AuthLDAP::ldapImportGroup(
         'cn=glpi2-group1,ou=groups,ou=usa,ou=ldap2,dc=glpi,dc=org',
         [
            'authldaps_id' => $ldap->getID(),
            'entities_id'  => 0,
            'is_recursive' => true,
            'type'         => 'groups'
         ]
      );

      $this->integer($import)->isGreaterThan(0);

      //check group
      $group = new \Group();
      $this->boolean($group->getFromDB($import))->isTrue();

      $this->array($group->fields)
         ->string['name']->isIdenticalTo('glpi2-group1')
         ->string['completename']->isIdenticalTo('glpi2-group1')
         ->string['ldap_group_dn']->isIdenticalTo('cn=glpi2-group1,ou=groups,ou=usa,ou=ldap2,dc=glpi,dc=org');
   }

   /**
    * Test import group and user
    *
    * @extensions ldap
    *
    * @return void
    */
   public function testLdapImportUserGroup() {
      $ldap = $this->ldap;

      $import = \AuthLDAP::ldapImportGroup(
         'cn=glpi2-group1,ou=groups,ou=usa,ou=ldap2,dc=glpi,dc=org',
         [
            'authldaps_id' => $ldap->getID(),
            'entities_id'  => 0,
            'is_recursive' => true,
            'type'         => 'groups'
         ]
      );

      $this->integer($import)->isGreaterThan(0);

      //check group
      $group = new \Group();
      $this->boolean($group->getFromDB($import))->isTrue();

      $import = \AuthLDAP::ldapImportUserByServerId(
         [
            'method' => \AuthLDAP::IDENTIFIER_LOGIN,
            'value'  => 'remi'
         ],
         \AuthLDAP::ACTION_IMPORT,
         $ldap->getID(),
         true
      );
      $this->array($import)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_IMPORTED);
      $this->integer((int)$import['id'])->isGreaterThan(0);

      //check created user
      $user = new \User();
      $this->boolean($user->getFromDB($import['id']))->isTrue();

      $usergroups = \Group_User::getUserGroups($user->getID());
      $this->array($usergroups[0])
         ->variable['id']->isEqualTo($group->getID())
         ->string['name']->isIdenticalTo($group->fields['name']);
   }


   /**
    * Test sync user
    *
    * @extensions ldap
    *
    * @return void
    */
   public function testSyncUser() {
      $ldap = $this->ldap;
      $this->boolean($ldap->isSyncFieldEnabled())->isFalse();

      $import = \AuthLDAP::ldapImportUserByServerId(
         [
            'method' => \AuthLDAP::IDENTIFIER_LOGIN,
            'value'  => 'ecuador0'
         ],
         \AuthLDAP::ACTION_IMPORT,
         $ldap->getID(),
         true
      );
      $this->array($import)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_IMPORTED)
         ->integer['id']->isGreaterThan(0);

      //check created user
      $user = new \User();
      $this->boolean($user->getFromDB($import['id']))->isTrue();
      $this->array($user->fields)
         ->string['name']->isIdenticalTo('ecuador0')
         ->string['phone']->isIdenticalTo('034596780')
         ->string['user_dn']->isIdenticalTo('uid=ecuador0,ou=people,ou=ldap3,dc=glpi,dc=org');

      $this->boolean(
         ldap_modify(
            $ldap->connect(),
            'uid=ecuador0,ou=people,ou=ldap3,dc=glpi,dc=org',
            ['telephoneNumber' => '+33101010101']
         )
      )->isTrue();

      $synchro = $ldap->forceOneUserSynchronization($user);

      //reset entry before any test can fail
      $this->boolean(
         ldap_modify(
            $ldap->connect(),
            'uid=ecuador0,ou=people,ou=ldap3,dc=glpi,dc=org',
            ['telephoneNumber' => '034596780']
         )
      )->isTrue();

      $this->array($synchro)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_SYNCHRONIZED)
         ->variable['id']->isEqualTo($user->getID());

      $this->boolean($user->getFromDB($user->getID()))->isTrue();
      $this->array($user->fields)
         ->string['name']->isIdenticalTo('ecuador0')
         ->string['phone']->isIdenticalTo('+33101010101')
         ->string['user_dn']->isIdenticalTo('uid=ecuador0,ou=people,ou=ldap3,dc=glpi,dc=org');

      $this->boolean(
         $ldap->update([
            'id'           => $ldap->getID(),
            'sync_field'   => 'employeenumber'
         ])
      )->isTrue();

      $this->boolean($ldap->isSyncFieldEnabled())->isTrue();

      $this->boolean(
         ldap_mod_add(
            $ldap->connect(),
            'uid=ecuador0,ou=people,ou=ldap3,dc=glpi,dc=org',
            ['employeeNumber' => '42']
         )
      )->isTrue();

      $synchro = $ldap->forceOneUserSynchronization($user);

      $this->boolean($user->getFromDB($user->getID()))->isTrue();
      $this->array($synchro)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_SYNCHRONIZED)
         ->variable['id']->isEqualTo($user->getID());

      $this->variable($user->fields['sync_field'])->isEqualTo(42);

      $this->boolean(
         ldap_rename(
            $ldap->connect(),
            'uid=ecuador0,ou=people,ou=ldap3,dc=glpi,dc=org',
            'uid=testecuador',
            null,
            true
         )
      )->isTrue();

      $synchro = $ldap->forceOneUserSynchronization($user);

      //reset entry before any test can fail
      $this->boolean(
         ldap_rename(
            $ldap->connect(),
            'uid=testecuador,ou=people,ou=ldap3,dc=glpi,dc=org',
            'uid=ecuador0',
            null,
            true
         )
      )->isTrue();

      $this->boolean(
         ldap_mod_del(
            $ldap->connect(),
            'uid=ecuador0,ou=people,ou=ldap3,dc=glpi,dc=org',
            ['employeeNumber' => 42]
         )
      )->isTrue();

      $this->boolean($user->getFromDB($user->getID()))->isTrue();
      $this->array($synchro)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_SYNCHRONIZED)
         ->variable['id']->isEqualTo($user->getID());

      $this->variable($user->fields['sync_field'])->isEqualTo(42);
      $this->string($user->fields['name'])->isIdenticalTo('testecuador');

      global $DB;
      $DB->updateOrDie(
         'glpi_authldaps',
         ['sync_field' => null],
         ['id' => $ldap->getID()]
      );
   }

   /**
    * Test ldap authentication
    *
    * @extensions ldap
    *
    * @return void
    */
   public function testLdapAuth() {
      //try to login from a user that does not exists yet
      $auth = $this->login('brazil6', 'password', false);

      $user = new \User();
      $user->getFromDBbyName('brazil6');
      $this->array($user->fields)
         ->string['name']->isIdenticalTo('brazil6')
         ->string['user_dn']->isIdenticalTo('uid=brazil6,ou=people,ou=ldap3,dc=glpi,dc=org');
      $this->boolean($auth->user_present)->isFalse();
      $this->boolean($auth->user_dn)->isFalse();
      $this->resource($auth->ldap_connection)->isOfType('ldap link');

      //import user; then try to login
      $ldap = $this->ldap;
      $this->boolean(
         $ldap->update([
            'id'           => $ldap->getID(),
            'sync_field'   => 'employeenumber'
         ])
      )->isTrue();
      $this->boolean($ldap->isSyncFieldEnabled())->isTrue();

      //try to import an user from its sync_field
      $import = \AuthLDAP::ldapImportUserByServerId(
         [
            'method' => \AuthLDAP::IDENTIFIER_LOGIN,
            'value'  => '10'
         ],
         \AuthLDAP::ACTION_IMPORT,
         $ldap->getID(),
         true
      );
      $this->array($import)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_IMPORTED)
         ->integer['id']->isGreaterThan(0);

      //check created user
      $user = new \User();
      $this->boolean($user->getFromDB($import['id']))->isTrue();
      $this->array($user->fields)
         ->string['name']->isIdenticalTo('brazil7')
         ->string['user_dn']->isIdenticalTo('uid=brazil7,ou=people,ou=ldap3,dc=glpi,dc=org');

      $auth = $this->login('brazil7', 'password', false, true);

      $this->boolean($auth->user_present)->isTrue();
      $this->string($auth->user_dn)->isIdenticalTo($user->fields['user_dn']);
      $this->resource($auth->ldap_connection)->isOfType('ldap link');

      //change user login, and try again. Existing user should be updated.
      $this->boolean(
         ldap_rename(
            $ldap->connect(),
            'uid=brazil7,ou=people,ou=ldap3,dc=glpi,dc=org',
            'uid=brazil7test',
            null,
            true
         )
      )->isTrue();

      $this->login('brazil7', 'password', false, false);
      $auth = $this->login('brazil7test', 'password', false);

      //reset entry before any test can fail
      $this->boolean(
         ldap_rename(
            $ldap->connect(),
            'uid=brazil7test,ou=people,ou=ldap3,dc=glpi,dc=org',
            'uid=brazil7',
            null,
            true
         )
      )->isTrue();

      $this->boolean($user->getFromDB($user->getID()))->isTrue();
      $this->array($user->fields)
         ->string['name']->isIdenticalTo('brazil7test')
         ->string['user_dn']->isIdenticalTo('uid=brazil7test,ou=people,ou=ldap3,dc=glpi,dc=org');

      $this->boolean($auth->user_present)->isTrue();
      $this->resource($auth->ldap_connection)->isOfType('ldap link');

      //ensure duplicated DN on different authldaps_id does not prevent login
      $this->boolean(
         $user->getFromDBByCrit(['user_dn' => 'uid=brazil6,ou=people,ou=ldap3,dc=glpi,dc=org'])
      )->isTrue();

      $dup = $user->fields;
      unset($dup['id']);
      unset($dup['date_creation']);
      unset($dup['date_mod']);
      $aid = $dup['auths_id'];
      $dup['auths_id'] = $aid + 1;

      $this->integer(
         (int)$user->add($dup)
      )->isGreaterThan(0);

      $auth = $this->login('brazil6', 'password', false);
      $this->array($auth->user->fields)
         ->integer['auths_id']->isIdenticalTo($aid)
         ->string['name']->isIdenticalTo('brazil6')
         ->string['user_dn']->isIdenticalTo('uid=brazil6,ou=people,ou=ldap3,dc=glpi,dc=org');

      global $DB;
      $DB->updateOrDie(
         'glpi_authldaps',
         ['sync_field' => null],
         ['id' => $ldap->getID()]
      );
   }

   /**
    * Test LDAP authentication when specify the auth source (local, LDAP...)
    *
    * @extensions ldap
    *
    * @return void
    */
   public function testLdapAuthSpecifyAuth() {
      $_SESSION['glpicronuserrunning'] = "cron_phpunit";
      // Add a local account with same name than a LDAP user ('brazil8')
      $input = [
         'name'         => 'brazil8',
         'password'     => 'passwordlocal',
         'password2'    => 'passwordlocal',
         '_profiles_id' => 1, // add manual right (is_dynamic = 0)
         'entities_id'  => 0
      ];
      $user = new \User();
      $user_id = $user->add($input);
      $this->integer($user_id)->isGreaterThan(0);

      // check user has at least one profile
      $pus = \Profile_User::getForUser($user_id);
      $this->array($pus)->size->isEqualTo(1);
      $pu = array_shift($pus);
      $this->integer($pu['profiles_id'])->isEqualTo(1);
      $this->integer($pu['entities_id'])->isEqualTo(0);
      $this->integer($pu['is_recursive'])->isEqualTo(0);
      $this->integer($pu['is_dynamic'])->isEqualTo(0);

      // first, login with ldap mode
      $auth = new \Auth();
      $this->boolean($auth->login('brazil8', 'password', false, false, 'ldap-'.$this->ldap->getID()))->isTrue();
      $user_ldap_id = $auth->user->fields['id'];
      $this->integer($user_ldap_id)->isNotEqualTo($user_id);

      $auth = new \Auth();
      $this->boolean($auth->login('brazil8', 'passwordlocal', false, false, 'ldap-'.$this->ldap->getID()))->isFalse();

      // Then, login with local GLPI DB mode
      $auth = new \Auth();
      $this->boolean($auth->login('brazil8', 'password', false, false, 'local'))->isFalse();

      $auth = new \Auth();
      $this->boolean($auth->login('brazil8', 'passwordlocal', false, false, 'local'))->isTrue();
      $this->integer($auth->user->fields['id'])->isNotEqualTo($user_ldap_id);
   }

   /**
    * Test get users
    *
    * @extensions ldap
    *
    * @return void
    */
   public function testGetUsers() {
      $ldap = $this->ldap;
      $results = [];
      $limit = false;

      $users = \AuthLDAP::getUsers(
         [
            'authldaps_id' => $ldap->getID(),
            'ldap_filter'  => \AuthLDAP::buildLdapFilter($ldap),
            'mode'         => \AuthLDAP::ACTION_IMPORT
         ],
         $results,
         $limit
      );

      $this->array($users)->hasSize(910);
      $this->array($results)->hasSize(0);

      $_SESSION['ldap_import']['interface'] = \AuthLDAP::SIMPLE_INTERFACE;
      $_SESSION['ldap_import']['criterias'] = ['login_field' => 'brazil2'];
      $_SESSION['ldap_import']['mode'] = 0;

      $users = \AuthLDAP::getUsers(
         [
            'authldaps_id' => $ldap->getID(),
            'ldap_filter'  => \AuthLDAP::buildLdapFilter($ldap),
            'mode'         => \AuthLDAP::ACTION_IMPORT,
         ],
         $results,
         $limit
      );

      $this->array($users)->hasSize(12);
      $this->array($results)->hasSize(0);

      $_SESSION['ldap_import']['criterias'] = ['login_field' => 'remi'];

      $users = \AuthLDAP::getUsers(
         [
            'authldaps_id' => $ldap->getID(),
            'ldap_filter'  => \AuthLDAP::buildLdapFilter($ldap),
            'mode'         => \AuthLDAP::ACTION_IMPORT,
         ],
         $results,
         $limit
      );

      $this->array($users)->hasSize(1);
      $this->array($results)->hasSize(0);

      //hardcode tsmap
      $users[0]['stamp'] = 1503470443;
      $this->array($users[0])->isIdenticalTo([
         'link'      => 'remi',
         'stamp'     => 1503470443,
         'date_sync' => '-----',
         'uid'       =>'remi'

      ]);
   }

   /**
    * Test removed users
    *
    * @extensions ldap
    *
    * @return void
    */
   public function testRemovedUser() {
      global $CFG_GLPI;

      $ldap = $this->ldap;

      //put deleted LDAP users in trashbin
      $CFG_GLPI['user_deleted_ldap'] = 1;

      //add a new user in directory
      $this->boolean(
         ldap_add(
            $ldap->connect(),
            'uid=toremovetest,ou=people,ou=ldap3,dc=glpi,dc=org',
            [
               'uid'          => 'toremovetest',
               'sn'           => 'A SN',
               'cn'           => 'A CN',
               'userpassword' => 'password',
               'objectClass'  => [
                  'top',
                  'inetOrgPerson'
               ]
            ]
         )
      )->isTrue();

      //import the user
      $import = \AuthLDAP::ldapImportUserByServerId(
         [
            'method' => \AuthLDAP::IDENTIFIER_LOGIN,
            'value'  => 'toremovetest'
         ],
         \AuthLDAP::ACTION_IMPORT,
         $ldap->getID(),
         true
      );
      $this->array($import)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_IMPORTED)
         ->integer['id']->isGreaterThan(0);

      //check created user
      $user = new \User();
      $this->boolean($user->getFromDB($import['id']))->isTrue();

      //check sync from an non reachable directory
      $host = $ldap->fields['host'];
      $port = $ldap->fields['port'];
      $this->boolean(
         $ldap->update([
            'id'     => $ldap->getID(),
            'host'   => 'server-does-not-exists.org',
            'port'   => '1234'
         ])
      )->isTrue();
      $ldap::$conn_cache = [];

      $synchro = $ldap->forceOneUserSynchronization($user);
      $this->boolean($synchro)->isFalse();

      //reset directory configuration
      $this->boolean(
         $ldap->update([
            'id'     => $ldap->getID(),
            'host'   => $host,
            'port'   => $port
         ])
      )->isTrue();

      //check that user still exists
      $uid = $import['id'];
      $this->boolean($user->getFromDB($uid))->isTrue();
      $this->boolean((bool)$user->fields['is_deleted'])->isFalse();

      //drop test user
      $this->boolean(
         ldap_delete(
            $ldap->connect(),
            'uid=toremovetest,ou=people,ou=ldap3,dc=glpi,dc=org'
         )
      )->isTrue();

      $synchro = $ldap->forceOneUserSynchronization($user);
      $this->array($synchro)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_DELETED_LDAP)
         ->variable['id']->isEqualTo($uid);
      $CFG_GLPI['user_deleted_ldap'] = 0;

      //check that user no longer exists
      $this->boolean($user->getFromDB($uid))->isTrue();
      $this->boolean((bool)$user->fields['is_deleted'])->isTrue();
   }

   protected function ssoVariablesProvider() {
      global $DB;

      $iterator = $DB->request(\SsoVariable::getTable());
      $sso_vars = [];
      foreach ($iterator as $current) {
         $sso_vars[] = [$current['id'], $current['name']];
      }

      return $sso_vars;
   }

   /**
    * @dataProvider ssoVariablesProvider
    */
   public function testOtherAuth($sso_field_id, $sso_field_name) {
      global $CFG_GLPI;

      $config_values = \Config::getConfigurationValues('core', ['ssovariables_id']);
      \Config::setConfigurationValues('core', [
         'ssovariables_id' => $sso_field_id
      ]);
      $CFG_GLPI['ssovariables_id'] = $sso_field_id;
      $_SERVER[$sso_field_name] = 'brazil6';

      unset($_SESSION['glpiname']);

      $auth = new \Auth;
      $this->boolean($auth->login("", ""))->isTrue();
      $this->string($_SESSION['glpiname'])->isEqualTo('brazil6');

      //reset config
      \Config::setConfigurationValues('core', [
         'ssovariables_id' => $config_values['ssovariables_id']
      ]);
   }

   public function testSyncLongDN() {
      $ldap = $this->ldap;

      $ldap_con = $ldap->connect();
      $this->boolean(
         ldap_add(
            $ldap_con,
            'ou=andyetanotheronetogetaveryhugednidentifier,ou=people,ou=ldap3,dc=glpi,dc=org',
            [
               'ou'          => 'andyetanotheronetogetaveryhugednidentifier',
               'objectClass'  => [
                  'organizationalUnit'
               ]
            ]
         )
      )->isTrue(ldap_error($ldap_con));

      $this->boolean(
         ldap_add(
            $ldap_con,
            'ou=andyetanotherlongstring,ou=andyetanotheronetogetaveryhugednidentifier,ou=people,ou=ldap3,dc=glpi,dc=org',
            [
               'ou'          => 'andyetanotherlongstring',
               'objectClass'  => [
                  'organizationalUnit'
               ]
            ]
         )
      )->isTrue(ldap_error($ldap_con));

      $this->boolean(
         ldap_add(
            $ldap_con,
            'ou=anotherlongstringtocheckforsynchronization,ou=andyetanotherlongstring,ou=andyetanotheronetogetaveryhugednidentifier,ou=people,ou=ldap3,dc=glpi,dc=org',
            [
               'ou'          => 'anotherlongstringtocheckforsynchronization',
               'objectClass'  => [
                  'organizationalUnit'
               ]
            ]
         )
      )->isTrue(ldap_error($ldap_con));

      $this->boolean(
         ldap_add(
            $ldap_con,
            'ou=averylongstring,ou=anotherlongstringtocheckforsynchronization,ou=andyetanotherlongstring,ou=andyetanotheronetogetaveryhugednidentifier,ou=people,ou=ldap3,dc=glpi,dc=org',
            [
               'ou'          => 'averylongstring',
               'objectClass'  => [
                  'organizationalUnit'
               ]
            ]
         )
      )->isTrue(ldap_error($ldap_con));

      //add a new user in directory
      $this->boolean(
         ldap_add(
            $ldap_con,
            'uid=verylongdn,ou=averylongstring,ou=anotherlongstringtocheckforsynchronization,ou=andyetanotherlongstring,ou=andyetanotheronetogetaveryhugednidentifier,ou=people,ou=ldap3,dc=glpi,dc=org',
            [
               'uid'          => 'verylongdn',
               'sn'           => 'A SN',
               'cn'           => 'A CN',
               'userpassword' => 'password',
               'objectClass'  => [
                  'top',
                  'inetOrgPerson'
               ]
            ]
         )
      )->isTrue(ldap_error($ldap_con));

      $import = \AuthLDAP::ldapImportUserByServerId(
         [
            'method' => \AuthLDAP::IDENTIFIER_LOGIN,
            'value'  => 'verylongdn'
         ],
         \AuthLDAP::ACTION_IMPORT,
         $ldap->getID(),
         true
      );
      $this->array($import)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_IMPORTED)
         ->integer['id']->isGreaterThan(0);

      //check created user
      $user = new \User();
      $this->boolean($user->getFromDB($import['id']))->isTrue();

      $this->array($user->fields)
         ->string['name']->isIdenticalTo('verylongdn')
         ->string['user_dn']->isIdenticalTo('uid=verylongdn,ou=averylongstring,ou=anotherlongstringtocheckforsynchronization,ou=andyetanotherlongstring,ou=andyetanotheronetogetaveryhugednidentifier,ou=people,ou=ldap3,dc=glpi,dc=org');

      $this->boolean(
         ldap_modify(
            $ldap->connect(),
            'uid=verylongdn,ou=averylongstring,ou=anotherlongstringtocheckforsynchronization,ou=andyetanotherlongstring,ou=andyetanotheronetogetaveryhugednidentifier,ou=people,ou=ldap3,dc=glpi,dc=org',
            ['telephoneNumber' => '+33102020202']
         )
      )->isTrue();

      $synchro = $ldap->forceOneUserSynchronization($user);
      $this->array($synchro)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_SYNCHRONIZED)
         ->variable['id']->isEqualTo($user->getID());

      $this->boolean($user->getFromDB($user->getID()))->isTrue();
      $this->array($user->fields)
         ->string['name']->isIdenticalTo('verylongdn')
         ->string['phone']->isIdenticalTo('+33102020202')
         ->string['user_dn']->isIdenticalTo('uid=verylongdn,ou=averylongstring,ou=anotherlongstringtocheckforsynchronization,ou=andyetanotherlongstring,ou=andyetanotheronetogetaveryhugednidentifier,ou=people,ou=ldap3,dc=glpi,dc=org');

      //drop test user
      $this->boolean(
         ldap_delete(
            $ldap->connect(),
            'uid=verylongdn,ou=averylongstring,ou=anotherlongstringtocheckforsynchronization,ou=andyetanotherlongstring,ou=andyetanotheronetogetaveryhugednidentifier,ou=people,ou=ldap3,dc=glpi,dc=org'
         )
      )->isTrue();
   }

   public function testSyncLongDNiCyrillic() {
      $ldap = $this->ldap;

      $ldap_con = $ldap->connect();

      $this->boolean(
         ldap_add(
            $ldap_con,
            'OU=Управление с очень очень длинным названием даже сложно запомнить насколько оно длинное и еле влезает в экран№123,ou=ldap3,DC=glpi,DC=org',
            [
               'ou'          => 'Управление с очень очень длинным названием даже сложно запомнить насколько оно длинное и еле влезает в экран№123',
               'objectClass'  => [
                  'organizationalUnit'
               ]
            ]
         )
      )->isTrue(ldap_error($ldap_con));

      $this->boolean(
         ldap_add(
            $ldap_con,
            'OU=Отдел Тест,OU=Управление с очень очень длинным названием даже сложно запомнить насколько оно длинное и еле влезает в экран№123,ou=ldap3,DC=glpi,DC=org',
            [
               'ou'          => 'Отдел Тест',
               'objectClass'  => [
                  'organizationalUnit'
               ]
            ]
         )
      )->isTrue(ldap_error($ldap_con));

      //add a new user in directory
      $this->boolean(
         ldap_add(
            $ldap_con,
            'uid=Тестов Тест Тестович,OU=Отдел Тест,OU=Управление с очень очень длинным названием даже сложно запомнить насколько оно длинное и еле влезает в экран№123,ou=ldap3,DC=glpi,DC=org',
            [
               'uid'          => 'Тестов Тест Тестович',
               'sn'           => 'A SN',
               'cn'           => 'A CN',
               'userpassword' => 'password',
               'objectClass'  => [
                  'top',
                  'inetOrgPerson'
               ]
            ]
         )
      )->isTrue(ldap_error($ldap_con));

      $import = \AuthLDAP::ldapImportUserByServerId(
         [
            'method' => \AuthLDAP::IDENTIFIER_LOGIN,
            'value'  => 'Тестов Тест Тестович'
         ],
         \AuthLDAP::ACTION_IMPORT,
         $ldap->getID(),
         true
      );
      $this->array($import)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_IMPORTED)
         ->integer['id']->isGreaterThan(0);

      //check created user
      $user = new \User();
      $this->boolean($user->getFromDB($import['id']))->isTrue();

      $this->array($user->fields)
         ->string['name']->isIdenticalTo('Тестов Тест Тестович')
         ->string['user_dn']->isIdenticalTo('uid=Тестов Тест Тестович,ou=Отдел Тест,ou=Управление с очень очень длинным названием даже сложно запомнить насколько оно длинное и еле влезает в экран№123,ou=ldap3,dc=glpi,dc=org');

      $this->boolean(
         ldap_modify(
            $ldap->connect(),
            'uid=Тестов Тест Тестович,ou=Отдел Тест,ou=Управление с очень очень длинным названием даже сложно запомнить насколько оно длинное и еле влезает в экран№123,ou=ldap3,dc=glpi,dc=org',
            ['telephoneNumber' => '+33103030303']
         )
      )->isTrue();

      $synchro = $ldap->forceOneUserSynchronization($user);
      $this->array($synchro)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_SYNCHRONIZED)
         ->variable['id']->isEqualTo($user->getID());

      $this->boolean($user->getFromDB($user->getID()))->isTrue();
      $this->array($user->fields)
         ->string['name']->isIdenticalTo('Тестов Тест Тестович')
         ->string['phone']->isIdenticalTo('+33103030303')
         ->string['user_dn']->isIdenticalTo('uid=Тестов Тест Тестович,ou=Отдел Тест,ou=Управление с очень очень длинным названием даже сложно запомнить насколько оно длинное и еле влезает в экран№123,ou=ldap3,dc=glpi,dc=org');

      //drop test user
      $this->boolean(
         ldap_delete(
            $ldap->connect(),
            'uid=Тестов Тест Тестович,OU=Отдел Тест,OU=Управление с очень очень длинным названием даже сложно запомнить насколько оно длинное и еле влезает в экран№123,ou=ldap3,DC=glpi,DC=org'
         )
      )->isTrue();
   }

   protected function testSyncWithManagerProvider() {
      $dns = [
         "Test Test",
         "Test - Test",
         "Test, Test",
         "Test'Test",
         "Test \ Test",
      ];

      $entry = [
         'sn'           => 'Test',
         'cn'           => 'Test',
         'userpassword' => 'password',
         'objectClass'  => [
            'top',
            'inetOrgPerson'
         ]
      ];

      return array_map(function($dn, $key) use ($entry) {
         $ret = [
            'manager_dn' => $dn,
            'manager_entry' => $entry,
         ];

         $ret['manager_entry']['uid'] = "ttest$key";
         return $ret;
      }, $dns, array_keys($dns));
   }

   /**
    * @dataProvider testSyncWithManagerProvider
    */
   public function testSyncWithManager($manager_dn, array $manager_entry) {
      // Static conf
      $base_dn = "ou=people,ou=ldap3,dc=glpi,dc=org";
      $user_full_dn = "uid=userwithmanager,$base_dn";
      $escaped_manager_dn = ldap_escape($manager_dn, "", LDAP_ESCAPE_DN);
      $manager_full_dn = "cn=$escaped_manager_dn,$base_dn";
      $user_entry = [
         'uid'          => 'userwithmanager' . $manager_entry['uid'],
         'sn'           => 'A SN',
         'cn'           => 'A CN',
         'userpassword' => 'password',
         'manager'      => $manager_full_dn,
         'objectClass'  => [
            'top',
            'inetOrgPerson'
         ]
      ];

      // Init ldap
      $ldap = $this->ldap;
      $ldap_con = $ldap->connect();

      // Add the manager
      $this
         ->boolean(ldap_add($ldap_con, $manager_full_dn, $manager_entry))
         ->isTrue(ldap_error($ldap_con));

      // Add the user
      $this
         ->boolean(ldap_add($ldap_con, $user_full_dn, $user_entry))
         ->isTrue(ldap_error($ldap_con));

      // Import manager
      $import_manager = \AuthLdap::ldapImportUserByServerId(
         [
            'method' => \AuthLDAP::IDENTIFIER_LOGIN,
            'value'  => $manager_entry['uid']
         ],
         \AuthLDAP::ACTION_IMPORT,
         $ldap->getID(),
         true
      );
      $this
         ->array($import_manager)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_IMPORTED)
         ->integer['id']->isGreaterThan(0);

      // Import user
      $import_user = \AuthLdap::ldapImportUserByServerId(
         [
            'method' => \AuthLDAP::IDENTIFIER_LOGIN,
            'value'  => $user_entry['uid']
         ],
         \AuthLDAP::ACTION_IMPORT,
         $ldap->getID(),
         true
      );
      $this
         ->array($import_user)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_IMPORTED)
         ->integer['id']->isGreaterThan(0);

      // Check created manager
      $manager = new \User();
      $this->boolean($manager->getFromDB($import_manager['id']))->isTrue();

      $this
         ->array($manager->fields)
         ->string['name']->isIdenticalTo($manager_entry['uid']);

      // Compare dn in a case insensitive way as ldap_escape create filter in
      // lowercase ("," -> \2c) but some ldap software store them in uppercase
      $this
         ->string(strtolower($manager->fields['user_dn']))
         ->isIdenticalTo(strtolower($manager_full_dn));

      // Check created user
      $user = new \User();
      $this->boolean($user->getFromDB($import_user['id']))->isTrue();

      $this
         ->array($user->fields)
         ->string['name']->isIdenticalTo($user_entry['uid'])
         ->string['user_dn']->isIdenticalTo("$user_full_dn")
         ->integer['users_id_supervisor']->isIdenticalTo($manager->fields['id']);

      // Drop both
      $this->boolean(ldap_delete($ldap->connect(), $user_full_dn))->isTrue();
      $this->boolean(ldap_delete($ldap->connect(), $manager_full_dn))->isTrue();
      $this->boolean($user->delete(['id' => $user->fields['id']]))->isTrue();
      $this->boolean($user->delete(['id' => $manager->fields['id']]))->isTrue();
   }

   /**
    * Test if rules targeting ldap criteria are working
    *
    * @return void
    */
   public function testRuleRight() {
      //prepare rules
      $rules = new \RuleRight();
      $rules_id = $rules->add([
         'sub_type'     => 'RuleRight',
         'name'         => 'test ldap ruleright',
         'match'        => 'AND',
         'is_active'    => 1,
         'entities_id'  => 0,
         'is_recursive' => 1,
      ]);
      $criteria = new \RuleCriteria();
      $criteria->add([
         'rules_id'  => $rules_id,
         'criteria'  => 'LDAP_SERVER',
         'condition' => \Rule::PATTERN_IS,
         'pattern'   => $this->ldap->getID(),
      ]);
      $criteria->add([
         'rules_id'  => $rules_id,
         'criteria'  => 'employeenumber',
         'condition' => \Rule::PATTERN_IS,
         'pattern'   => 8,
      ]);
      $actions = new \RuleAction();
      $actions->add([
         'rules_id'    => $rules_id,
         'action_type' => 'assign',
         'field'       => 'profiles_id',
         'value'       => 5, // 'normal' profile
      ]);
      $actions->add([
         'rules_id'    => $rules_id,
         'action_type' => 'assign',
         'field'       => 'entities_id',
         'value'       => 1, // '_test_child_1' entity
      ]);

      // Test specific_groups_id rule
      $group = new Group();
      $group_id = $group->add(["name" => "testgroup"]);
      $this->integer($group_id);

      $actions->add([
         'rules_id'    => $rules_id,
         'action_type' => 'assign',
         'field'       => 'specific_groups_id',
         'value'       => $group_id, // '_test_child_1' entity
      ]);

      // login the user to force a real synchronisation and get it's glpi id
      $this->login('brazil6', 'password', false);
      $users_id = \User::getIdByName('brazil6');
      $this->integer($users_id);
      // check the user got the entity/profiles assigned
      $pu = \Profile_User::getForUser($users_id, true);
      $found = false;
      foreach ($pu as $right) {
         if (isset($right['entities_id']) && $right['entities_id'] == 1
             && isset($right['profiles_id']) && $right['profiles_id'] == 5
             && isset($right['is_dynamic']) && $right['is_dynamic'] == 1) {
            $found = true;
            break;
         }
      }
      $this->boolean($found)->isTrue();

      // Check group
      $gu = new Group_User();
      $gus = $gu->find([
         'groups_id' => $group_id,
         'users_id' => $users_id,
      ]);
      $this->array($gus)->hasSize(1);
   }
}
