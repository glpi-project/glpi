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

namespace tests\units;

use \DbTestCase;

/* Test for inc/authldap.class.php */

class AuthLDAP extends DbTestCase {
   private $ldap;

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
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

      //make sure bootstrapped ldap is not active and is default
      $this->boolean(
         $this->ldap->update([
            'id'           => $this->ldap->getID(),
            'is_active'    => 1,
            'is_default'   => 1
         ])
      )->isTrue();

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
      $this->array($ldap->fields)->hasSize(24);
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
      $expected = \Toolbox::encrypt($password, GLPIKEY);
      $this->string($result['rootdn_passwd'])->isIdenticalTo($expected);

      $password = 'tot\'o';
      $input    = ['name' => 'ldap', 'rootdn_passwd' => $password];
      $result   = $ldap->prepareInputForUpdate($input);

      //Expected value to be encrypted using GLPIKEY key
      $expected = \Toolbox::encrypt($password, GLPIKEY);
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
      $options  = $ldap->rawSearchOptions();
      $this->array($options)->hasSize(32);
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
      $DB->update('glpi_authldaps', ['is_active' => 0], [true]);
      $this->boolean(\AuthLDAP::useAuthLdap())->isFalse();
   }

   public function testGetNumberOfServers() {
      global $DB;
      $this->addLdapServers();

      $this->integer((int)\AuthLDAP::getNumberOfServers())->isIdenticalTo(3);
      $DB->update('glpi_authldaps', ['is_active' => 0], [true]);
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

      $ldap->update([
         'id'        => $ldap->getID(),
         'is_active' => 0
      ]);
      $this->integer((int)\AuthLDAP::getDefault())->isIdenticalTo(0);
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
      $this->login();
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

   public function testIsValidGuid() {
      $this->boolean(\AuthLDAP::isValidGuid(''))->isFalse();
      $this->boolean(\AuthLDAP::isValidGuid('00000000-0000-0000-0000-000000000000'))->isTrue();
      $this->boolean(\AuthLDAP::isValidGuid('AB52DFB8-A352-BA53-CC58-ABFD5E9D200E'))->isTrue();
      $this->boolean(\AuthLDAP::isValidGuid('ZB52DFH8-AH52-BH53-CH58-ABFD5E9D200E'))->isFalse();
   }

   public function testGuidToHex() {
      $guid       = '891b903c-9982-4e64-9c2a-a6caff69f5b0';
      $expected   = '\3c\90\1b\89\82\99\64\4e\9c\2a\a6\ca\ff\69\f5\b0';
      $this->string(\AuthLDAP::guidToHex($guid))->isIdenticalTo($expected);
   }

   public function testGetFieldValue() {
      $infos = ['field' => 'value'];
      $this->string(\AuthLDAP::getFieldValue($infos, 'field'))->isIdenticalTo('value');

      $infos = ['objectguid' => 'value'];
      $this->string(\AuthLDAP::getFieldValue($infos, 'objectguid'))->isIdenticalTo('value');
   }
}
