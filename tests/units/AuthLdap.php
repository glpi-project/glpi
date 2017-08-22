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

/* Test for inc/authldap.class.php */

class AuthLDAP extends DbTestCase {
   private $ldap;

   public function beforeTestMethod($method) {
      $this->ldap = getItemByTypeName('AuthLDAP', '_local_ldap');

      //make sure bootstrapped ldap is active and is default
      $this->boolean(
         $this->ldap->update([
            'id'           => $this->ldap->getID(),
            'is_active'    => 1,
            'is_default'   => 1
         ])
      )->isTrue();
   }

   public function afterTestMethod($method) {
      unset($_SESSION['ldap_import']);
      parent::afterTestMethod($method);
   }

   private function addLdapServers() {
      $ldap = new \AuthLDAP();
      $this->integer(
         (int)$ldap->add([
            'name'        => 'LDAP1',
            'is_active'   => 1,
            'is_default'  => 0,
            'basedn'      => 'ou=people,dc=mycompany',
            'login_field' => 'uid',
            'phone_field' => 'phonenumber'
         ])
      )->isGreaterThan(0);
      $this->integer(
         (int)$ldap->add([
            'name'         => 'LDAP2',
            'is_active'    => 0,
            'is_default'   => 0,
            'basedn'       => 'ou=people,dc=mycompany',
            'login_field'  => 'uid',
            'phone_field'  => 'phonenumber',
            'email1_field' => 'email'
         ])
      )->isGreaterThan(0);
      $this->integer(
         (int)$ldap->add([
            'name'        => 'LDAP3',
            'is_active'   => 1,
            'is_default'  => 1,
            'basedn'      => 'ou=people,dc=mycompany',
            'login_field' => 'email',
            'phone_field' => 'phonenumber',
            'email1_field' => 'email'
         ])
      )->isGreaterThan(0);
   }

   public function testGetTypeName() {
      $this->string(\AuthLDAP::getTypeName(1))->isIdenticalTo('LDAP directory');
      $this->string(\AuthLDAP::getTypeName(0))->isIdenticalTo('LDAP directories');
      $this->string(\AuthLDAP::getTypeName(\Session::getPluralNumber()))->isIdenticalTo('LDAP directories');
   }

   public function testPost_getEmpty() {
      $ldap = new \AuthLDAP();
      $ldap->post_getEmpty();
      $this->array($ldap->fields)->hasSize(23);
   }

   public function testUnsetUndisclosedFields() {
      $fields = ['login_field' => 'test', 'rootdn_passwd' => 'mypassword'];
      \AuthLDAP::unsetUndisclosedFields($fields);
      $this->array($fields)
         ->notHasKey('rootdn_passwd');
   }

   public function testPreconfig() {
      $ldap = new \Authldap();
      //Use Active directory preconfiguration :
      //login_field and sync_field must be filled
      $ldap->preconfig('AD');
      $this->array($ldap->fields)
         ->string['login_field']->isIdenticalTo('samaccountname')
         ->string['sync_field']->isIdenticalTo('objectguid');

      //No preconfiguration model
      $ldap->preconfig('');
      //Login_field is set to uid (default)
      $this->string($ldap->fields['login_field'])->isIdenticalTo('uid');
      $this->variable($ldap->fields['sync_field'])->isNull();
   }

   public function testPrepareInputForUpdate() {
      $ldap   = new \mock\Authldap();
      $this->calling($ldap)->isSyncFieldUsed = true;

      //------------ Password tests --------------------//
      $input  = ['name' => 'ldap', 'rootdn_passwd' => ''];
      $result = $ldap->prepareInputForUpdate($input);
      //empty rootdn_passwd set : should not appear in the response array
      $this->array($result)->notHasKey('rootdn_passwd');

      //no rootdn_passwd set : should not appear in the response array
      $input  = ['name' => 'ldap'];
      $result = $ldap->prepareInputForUpdate($input);
      $this->array($result)->notHasKey('rootdn_passwd');

      //rootdn_passwd is set with a value (a password, not encrypted)
      $password = 'toto';
      $input    = ['name' => 'ldap', 'rootdn_passwd' => $password];
      $result   = $ldap->prepareInputForUpdate($input);

      //Expected value to be encrypted using GLPIKEY key
      $expected = \Toolbox::encrypt(stripslashes($password), GLPIKEY);
      $this->string($result['rootdn_passwd'])->isIdenticalTo($expected);

      $password = 'tot\'o';
      $input    = ['name' => 'ldap', 'rootdn_passwd' => $password];
      $result   = $ldap->prepareInputForUpdate($input);

      //Expected value to be encrypted using GLPIKEY key
      $expected = \Toolbox::encrypt(stripslashes($password), GLPIKEY);
      $this->string($result['rootdn_passwd'])->isIdenticalTo($expected);

      $input['_blank_passwd'] = 1;
      $result   = $ldap->prepareInputForUpdate($input);
      //rootdn_passwd is set but empty
      $this->string($result['rootdn_passwd'])->isEmpty();

      //Field name finishing with _field : set the value in lower case
      $input['_login_field'] = 'TEST';
      $result         = $ldap->prepareInputForUpdate($input);
      $this->string($result['_login_field'])->isIdenticalTo('test');

      $input['sync_field'] = 'sync_field';
      $result = $ldap->prepareInputForUpdate($input);
      $this->string($result['sync_field'])->isIdenticalTo('sync_field');

      //test sync_field update
      $ldap->fields['sync_field'] = 'sync_field';
      $result = $ldap->prepareInputForUpdate($input);
      $this->array($result)->notHasKey('sync_field');

      $this->calling($ldap)->isSyncFieldUsed = false;
      $result = $ldap->prepareInputForUpdate($input);
      $this->array($result)->hasKey('sync_field');
      $this->calling($ldap)->isSyncFieldUsed = true;

      $input['sync_field'] = 'another_field';
      $result = $ldap->prepareInputForUpdate($input);
      $this->boolean($result)->isFalse();
   }

   public function testgetGroupSearchTypeName() {
      //Get all group search type values
      $search_type = \AuthLDAP::getGroupSearchTypeName();
      $this->array($search_type)->hasSize(3);

      //Give a wrong number value
      $search_type = \AuthLDAP::getGroupSearchTypeName(4);
      $this->string($search_type)->isIdenticalTo(NOT_AVAILABLE);

      //Give a wrong string value
      $search_type = \AuthLDAP::getGroupSearchTypeName('toto');
      $this->string($search_type)->isIdenticalTo(NOT_AVAILABLE);

      //Give a existing values
      $search_type = \AuthLDAP::getGroupSearchTypeName(0);
      $this->string($search_type)->isIdenticalTo('In users');

      $search_type = \AuthLDAP::getGroupSearchTypeName(1);
      $this->string($search_type)->isIdenticalTo('In groups');

      $search_type = \AuthLDAP::getGroupSearchTypeName(2);
      $this->string($search_type)->isIdenticalTo('In users and groups');
   }

   public function testGetSpecificValueToDisplay() {
      $ldap = new \AuthLDAP();

      //Value as an array
      $values = ['group_search_type' => 0];
      $result = $ldap->getSpecificValueToDisplay('group_search_type', $values);
      $this->string($result)->isIdenticalTo('In users');

      //Value as a single value
      $values = 1;
      $result = $ldap->getSpecificValueToDisplay('group_search_type', $values);
      $this->string($result)->isIdenticalTo('In groups');

      //Value as a single value
      $values = ['name' => 'ldap'];
      $result = $ldap->getSpecificValueToDisplay('name', $values);
      $this->string($result)->isEmpty();
   }

   public function testDefineTabs() {
      $ldap     = new \AuthLDAP();
      $tabs     = $ldap->defineTabs();
      $expected = ['AuthLDAP$main' => 'LDAP directory',
                   'Log$1'         => 'Historical'];
      $this->array($tabs)->isIdenticalTo($expected);
   }

   public function testGetSearchOptionsNew() {
      $ldap     = new \AuthLDAP();
      $options  = $ldap->getSearchOptionsNew();
      $this->array($options)->hasSize(31);
   }

   public function testGetSyncFields() {
      $ldap     = new \AuthLDAP();
      $values   = ['login_field' => 'value'];
      $result   = $ldap->getSyncFields($values);
      $this->array($result)->isIdenticalTo(['name' => 'value']);

      $result   = $ldap->getSyncFields([]);
      $this->array($result)->isEmpty();
   }

   public function testLdapStamp2UnixStamp() {
      //Good timestamp
      $result = \AuthLDAP::ldapStamp2UnixStamp('20161114100339Z');
      $this->integer($result)->isIdenticalTo(1479117819);

      //Bad timestamp format
      $result = \AuthLDAP::ldapStamp2UnixStamp(20161114100339);
      $this->string($result)->isEmpty();

      //Bad timestamp format
      $result = \AuthLDAP::ldapStamp2UnixStamp("201611141003");
      $this->string($result)->isEmpty();
   }

   public function testDate2ldapTimeStamp() {
      $result = \AuthLDAP::date2ldapTimeStamp("2017-01-01 22:35:00");
      $this->string($result)->isIdenticalTo("20170101223500.0Z");

      //Bad date => 01/01/1970
      $result = \AuthLDAP::date2ldapTimeStamp("2017-25-25 22:35:00");
      $this->string($result)->isIdenticalTo("19700101000000.0Z");
   }

   public function testDnExistsInLdap() {
      $ldap_infos = [ ['uid'      => 'jdoe',
                       'cn'       => 'John Doe',
                       'user_dn'  => 'uid=jdoe, ou=people, dc=mycompany'
                      ],
                      ['uid'      => 'asmith',
                       'cn'       => 'Agent Smith',
                       'user_dn'  => 'uid=asmith, ou=people, dc=mycompany'
                      ]
                    ];

      //Ask for a non existing user_dn : result is false
      $this->boolean(
         \AuthLDAP::dnExistsInLdap(
            $ldap_infos,
            'uid=jdupont, ou=people, dc=mycompany'
         )
      )->isFalse();

      //Ask for an dn that exists : result is the user's infos as an array
      $result = \AuthLDAP::dnExistsInLdap(
         $ldap_infos,
         'uid=jdoe, ou=people, dc=mycompany'
      );
      $this->array($result)->hasSize(3);
   }

   public function testGetLdapServers() {
      $this->addLdapServers();

      //The list of ldap server show the default server in first position
      $result = \AuthLDAP::getLdapServers();
      $this->array($result)
         ->hasSize(4);
      $this->array(current($result))
         ->string['name']->isIdenticalTo('LDAP3');
   }

   public function testUseAuthLdap() {
      global $DB;
      $this->addLdapServers();

      $this->boolean(\AuthLDAP::useAuthLdap())->isTrue();
      $sql = "UPDATE `glpi_authldaps` SET `is_active`='0'";
      $DB->query($sql);
      $this->boolean(\AuthLDAP::useAuthLdap())->isFalse();
   }

   public function testGetNumberOfServers() {
      global $DB;
      $this->addLdapServers();

      $this->integer((int)\AuthLDAP::getNumberOfServers())->isIdenticalTo(3);
      $sql = "UPDATE `glpi_authldaps` SET `is_active`='0'";
      $DB->query($sql);
      $this->integer((int)\AuthLDAP::getNumberOfServers())->isIdenticalTo(0);
   }

   public function testBuildLdapFilter() {
      $this->addLdapServers();

      $ldap = getItemByTypeName('AuthLDAP', 'LDAP3');
      $result = \AuthLDAP::buildLdapFilter($ldap);
      $this->string($result)->isIdenticalTo("(& (email=*) )");

      $_SESSION['ldap_import']['interface'] = \AuthLDAP::SIMPLE_INTERFACE;
      $_SESSION['ldap_import']['criterias'] = ['name'        => 'foo',
                                               'phone_field' => '+33454968584'];
      $result = \AuthLDAP::buildLdapFilter($ldap);
      $this->string($result)->isIdenticalTo('(& (LDAP3=*foo*)(phonenumber=*+33454968584*) )');

      $_SESSION['ldap_import']['criterias']['name'] = '^foo';
      $result = \AuthLDAP::buildLdapFilter($ldap);
      $this->string($result)->isIdenticalTo('(& (LDAP3=foo*)(phonenumber=*+33454968584*) )');

      $_SESSION['ldap_import']['criterias']['name'] = 'foo$';
      $result = \AuthLDAP::buildLdapFilter($ldap);
      $this->string($result)->isIdenticalTo('(& (LDAP3=*foo)(phonenumber=*+33454968584*) )');

      $_SESSION['ldap_import']['criterias']['name'] = '^foo$';
      $result = \AuthLDAP::buildLdapFilter($ldap);
      $this->string($result)->isIdenticalTo('(& (LDAP3=foo)(phonenumber=*+33454968584*) )');

      $_SESSION['ldap_import']['criterias'] = ['name' => '^foo$'];
      $ldap->fields['condition'] = '(objectclass=inetOrgPerson)';
      $result = \AuthLDAP::buildLdapFilter($ldap);
      $ldap->fields['condition'] = '';
      $this->string($result)->isIdenticalTo('(& (LDAP3=foo) (objectclass=inetOrgPerson))');

      $_SESSION['ldap_import']['begin_date']        = '2017-04-20 00:00:00';
      $_SESSION['ldap_import']['end_date']          = '2017-04-22 00:00:00';
      $_SESSION['ldap_import']['criterias']['name'] = '^foo$';
      $result = \AuthLDAP::buildLdapFilter($ldap);
      $this->string($result)
         ->isIdenticalTo('(& (LDAP3=foo)(modifyTimestamp>=20170420000000.0Z)(modifyTimestamp<=20170422000000.0Z) )');
   }

   public function testAddTimestampRestrictions() {
      $result = \AuthLDAP::addTimestampRestrictions(
         '',
         '2017-04-22 00:00:00'
      );
      $this->string($result)
         ->isIdenticalTo("(modifyTimestamp<=20170422000000.0Z)");

      $result = \AuthLDAP::addTimestampRestrictions(
         '2017-04-20 00:00:00',
         ''
      );
      $this->string($result)
         ->isIdenticalTo("(modifyTimestamp>=20170420000000.0Z)");

      $result = \AuthLDAP::addTimestampRestrictions('', '');
      $this->string($result)->isEmpty();

      $result = \AuthLDAP::addTimestampRestrictions(
         '2017-04-20 00:00:00',
         '2017-04-22 00:00:00'
      );
      $this->string($result)
         ->isIdenticalTo("(modifyTimestamp>=20170420000000.0Z)(modifyTimestamp<=20170422000000.0Z)");
   }

   public function testGetDefault() {
      $this->integer((int)\AuthLDAP::getDefault())->isIdenticalTo((int)$this->ldap->getID());

      //Load ldap servers
      $this->addLdapServers();
      $ldap = getItemByTypeName('AuthLDAP', 'LDAP3');
      $this->integer((int)\AuthLDAP::getDefault())->isIdenticalTo((int)$ldap->getID());
   }

   public function testPost_updateItem() {
      //Load ldap servers
      $this->addLdapServers();

      //Get first lDAP server
      $ldap = getItemByTypeName('AuthLDAP', 'LDAP1');

      //Set it as default server
      $this->boolean(
         $ldap->update(['id' => $ldap->getID(), 'is_default' => 1])
      )->isTrue();

      //Get first lDAP server now
      $ldap = getItemByTypeName('AuthLDAP', 'LDAP1');
      $this->variable($ldap->fields['is_default'])->isEqualTo(1);

      //Get third ldap server (former default one)
      $ldap = getItemByTypeName('AuthLDAP', 'LDAP3');
      //Check that it's not the default server anymore
      $this->variable($ldap->fields['is_default'])->isEqualTo(0);
   }

   public function testPost_addItem() {
      //Load ldap servers
      $this->addLdapServers();

      $ldap     = new \AuthLDAP();
      $ldaps_id = $ldap->add([
         'name'        => 'LDAP4',
         'is_active'   => 1,
         'is_default'  => 1,
         'basedn'      => 'ou=people,dc=mycompany',
         'login_field' => 'email',
         'phone_field' => 'phonenumber'
      ]);
      $this->integer((int)$ldaps_id)->isGreaterThan(0);
      $this->boolean($ldap->getFromDB($ldaps_id))->isTrue();
      $this->variable($ldap->fields['is_default'])->isEqualTo(1);

      //Get third ldap server (former default one)
      $ldap = getItemByTypeName('AuthLDAP', 'LDAP3');
      //Check that it's not the default server anymore
      $this->variable($ldap->fields['is_default'])->isEqualTo(0);
   }

   public function testPrepareInputForAdd() {
      $ldap     = new \AuthLDAP();

      $ldaps_id = $ldap->add([
         'name'        => 'LDAP1',
         'is_active'   => 1,
         'basedn'      => 'ou=people,dc=mycompany',
         'login_field' => 'email',
         'rootdn_passwd' => 'password'
      ]);
      $this->integer((int)$ldaps_id)->isGreaterThan(0);
      $this->boolean($ldap->getFromDB($ldaps_id))->isTrue();
      $this->array($ldap->fields)
         ->variable['is_default']->isEqualTo(0)
         ->string['rootdn_passwd']->isNotEqualTo('password');
   }

   public function testGetServersWithImportByEmailActive() {
      $result = \AuthLDAP::getServersWithImportByEmailActive();
      $this->array($result)->hasSize(1);

      $this->addLdapServers();

      //Return two ldap server : because LDAP2 is disabled
      $result = \AuthLDAP::getServersWithImportByEmailActive();
      $this->array($result)->hasSize(2);

      //Enable LDAP2
      $ldap = getItemByTypeName('AuthLDAP', 'LDAP2');
      $this->boolean(
         $ldap->update([
            'id' => $ldap->getID(),
            'is_active' => 1
         ])
      )->isTrue();

      //Now there should be 2 enabled servers
      $result = \AuthLDAP::getServersWithImportByEmailActive();
      $this->array($result)->hasSize(3);
   }

   public function testgetTabNameForItem() {
      $this->Login();
      $this->addLdapServers();

      $ldap   = getItemByTypeName('AuthLDAP', 'LDAP1');
      $result = $ldap->getTabNameForItem($ldap);
      $expected = [1 => 'Test',
                   2 => 'Users',
                   3 => 'Groups',
                   5 => 'Advanced information',
                   6 => 'Replicates'
                  ];
      $this->array($result)->isIdenticalTo($expected);

      $result = $ldap->getTabNameForItem($ldap, 1);
      $this->string($result)->isEmpty;
   }

   public function testGetAllReplicateForAMaster() {
      $ldap      = new \AuthLDAP();
      $replicate = new \AuthLdapReplicate();

      $ldaps_id = $ldap->add([
         'name'        => 'LDAP1',
         'is_active'   => 1,
         'is_default'  => 0,
         'basedn'      => 'ou=people,dc=mycompany',
         'login_field' => 'uid',
         'phone_field' => 'phonenumber'
      ]);
      $this->integer((int)$ldaps_id)->isGreaterThan(0);

      $this->integer(
         (int)$replicate->add([
            'name'         => 'replicate1',
            'host'         => 'myhost1',
            'port'         => 3306,
            'authldaps_id' => $ldaps_id
         ])
      )->isGreaterThan(0);

      $this->integer(
         (int)$replicate->add([
            'name'         => 'replicate2',
            'host'         => 'myhost1',
            'port'         => 3306,
            'authldaps_id' => $ldaps_id
         ])
      )->isGreaterThan(0);

      $this->integer(
         (int)$replicate->add([
            'name'         => 'replicate3',
            'host'         => 'myhost1',
            'port'         => 3306,
            'authldaps_id' => $ldaps_id
         ])
      )->isGreaterThan(0);

      $result = $ldap->getAllReplicateForAMaster($ldaps_id);
      $this->array($result)->hasSize(3);

      $result = $ldap->getAllReplicateForAMaster(100);
      $this->array($result)->hasSize(0);
   }

   //LDAP server must be installed and populated for the following tests.
   /**
    * Tets LDAP connection
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

      $this->array($users)->hasSize(909);
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

      $import = \AuthLdap::ldapImportUserByServerId(
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
           'count'   => '1',
            0        => 'glpi2-group1',
         ],
         0        => 'cn',
         'count'  => '1',
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

      $import = \AuthLdap::ldapImportUserByServerId(
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
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_IMPORTED)
         ->integer['id']->isGreaterThan(0);

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

      $import = \AuthLdap::ldapImportUserByServerId(
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
      );

      $synchro = $ldap->forceOneUserSynchronization($user);

      //reset entry before any test can fail
      $this->boolean(
         ldap_modify(
            $ldap->connect(),
            'uid=ecuador0,ou=people,ou=ldap3,dc=glpi,dc=org',
            ['telephoneNumber' => '034596780']
         )
      );

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
      );

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
      );

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
      );

      $this->boolean(
         ldap_mod_del(
            $ldap->connect(),
            'uid=ecuador0,ou=people,ou=ldap3,dc=glpi,dc=org',
            ['employeeNumber' => 42]
         )
      );

      $this->boolean($user->getFromDB($user->getID()))->isTrue();
      $this->array($synchro)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_SYNCHRONIZED)
         ->variable['id']->isEqualTo($user->getID());

      $this->variable($user->fields['sync_field'])->isEqualTo(42);
      $this->string($user->fields['name'])->isIdenticalTo('testecuador');

      global $DB;
      $DB->queryOrDie("UPDATE glpi_authldaps SET sync_field=NULL WHERE id=" . $ldap->getID());
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
      $auth = new \Auth();
      $this->boolean($auth->login('brazil6', 'password'))->isTrue();

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
      $import = \AuthLdap::ldapImportUserByServerId(
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

      $auth = new \Auth();
      $this->boolean($auth->login('brazil7', 'password'))->isTrue();

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
      );

      $auth = new \Auth();
      $this->boolean($auth->login('brazil7', 'password'))->isFalse();
      $this->boolean($auth->login('brazil7test', 'password'))->isTrue();

      //reset entry before any test can fail
      $this->boolean(
         ldap_rename(
            $ldap->connect(),
            'uid=brazil7test,ou=people,ou=ldap3,dc=glpi,dc=org',
            'uid=brazil7',
            null,
            true
         )
      );

      $this->boolean($user->getFromDB($user->getID()))->isTrue();
      $this->array($user->fields)
         ->string['name']->isIdenticalTo('brazil7test')
         ->string['user_dn']->isIdenticalTo('uid=brazil7test,ou=people,ou=ldap3,dc=glpi,dc=org');

      $this->boolean($auth->user_present)->isTrue();
      $this->resource($auth->ldap_connection)->isOfType('ldap link');

      global $DB;
      $DB->queryOrDie("UPDATE glpi_authldaps SET sync_field=NULL WHERE id=" . $ldap->getID());
   }
}
