<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use DbTestCase;
use GLPIKey;
use Group;
use Group_User;
use UserTitle;

/* Test for inc/authldap.class.php */

class AuthLDAP extends DbTestCase
{
    /**
     * @var \AuthLDAP
     */
    private $ldap;

    public function beforeTestMethod($method)
    {
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

    public function afterTestMethod($method)
    {
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

    private function addLdapServers()
    {
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

    public function testGetTypeName()
    {
        $this->string(\AuthLDAP::getTypeName(1))->isIdenticalTo('LDAP directory');
        $this->string(\AuthLDAP::getTypeName(0))->isIdenticalTo('LDAP directories');
        $this->string(\AuthLDAP::getTypeName(\Session::getPluralNumber()))->isIdenticalTo('LDAP directories');
    }

    public function testPost_getEmpty()
    {
        $ldap = new \AuthLDAP();
        $ldap->post_getEmpty();
        $this->array($ldap->fields)->hasSize(28);
    }

    public function testUnsetUndisclosedFields()
    {
        $fields = ['login_field' => 'test', 'rootdn_passwd' => 'mypassword'];
        \AuthLDAP::unsetUndisclosedFields($fields);
        $this->array($fields)
         ->notHasKey('rootdn_passwd');
    }

    public function testPreconfig()
    {
        $ldap = new \AuthLDAP();
        //Use Active directory preconfiguration :
        //login_field and sync_field must be filled
        $ldap->preconfig('AD');
        $this->array($ldap->fields)
         ->string['login_field']->isIdenticalTo('samaccountname')
         ->string['sync_field']->isIdenticalTo('objectguid');

        //Use OpenLDAP preconfiguration :
        //login_field and sync_field must be filled
        $ldap->preconfig('OpenLDAP');
        $this->array($ldap->fields)
         ->string['login_field']->isIdenticalTo('uid')
         ->string['sync_field']->isIdenticalTo('entryuuid');

        //No preconfiguration model
        $ldap->preconfig('');
        //Login_field is set to uid (default)
        $this->string($ldap->fields['login_field'])->isIdenticalTo('uid');
        $this->variable($ldap->fields['sync_field'])->isNull();
    }

    public function testPrepareInputForUpdate()
    {
        $ldap   = new \mock\AuthLDAP();
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
        $this->hasSessionMessages(ERROR, ['Synchronization field cannot be changed once in use.']);
    }

    public function testgetGroupSearchTypeName()
    {
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

    public function testGetSpecificValueToDisplay()
    {
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

    public function testDefineTabs()
    {
        $ldap     = new \AuthLDAP();
        $tabs     = $ldap->defineTabs();
        $expected = ['AuthLDAP$main' => 'LDAP directory',
            'Log$1'         => 'Historical'
        ];
        $this->array($tabs)->isIdenticalTo($expected);
    }

    public function testGetSearchOptionsNew()
    {
        $ldap     = new \AuthLDAP();
        $options  = $ldap->rawSearchOptions();
        $this->array($options)->hasSize(34);
    }

    public function testGetSyncFields()
    {
        $ldap     = new \AuthLDAP();
        $values   = ['login_field' => 'value'];
        $result   = $ldap->getSyncFields($values);
        $this->array($result)->isIdenticalTo(['name' => 'value']);

        $result   = $ldap->getSyncFields([]);
        $this->array($result)->isEmpty();
    }

    public function testLdapStamp2UnixStamp()
    {
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

    public function testDate2ldapTimeStamp()
    {
        $result = \AuthLDAP::date2ldapTimeStamp("2017-01-01 22:35:00");
        $this->string($result)->isIdenticalTo("20170101223500.0Z");

       //Bad date => 01/01/1970
        $result = \AuthLDAP::date2ldapTimeStamp("2017-25-25 22:35:00");
        $this->string($result)->isIdenticalTo("19700101000000.0Z");
    }

    public function testDnExistsInLdap()
    {
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

    public function testGetLdapServers()
    {
        $this->addLdapServers();

       //The list of ldap server show the default server in first position
        $result = \AuthLDAP::getLdapServers();
        $this->array($result)
         ->hasSize(4);
        $this->array(current($result))
         ->string['name']->isIdenticalTo('LDAP3');
    }

    public function testUseAuthLdap()
    {
        global $DB;
        $this->addLdapServers();

        $this->boolean(\AuthLDAP::useAuthLdap())->isTrue();
        $DB->update('glpi_authldaps', ['is_active' => 0], [true]);
        $this->boolean(\AuthLDAP::useAuthLdap())->isFalse();
    }

    public function testGetNumberOfServers()
    {
        global $DB;
        $this->addLdapServers();

        $this->integer((int)\AuthLDAP::getNumberOfServers())->isIdenticalTo(3);
        $DB->update('glpi_authldaps', ['is_active' => 0], [true]);
        $this->integer((int)\AuthLDAP::getNumberOfServers())->isIdenticalTo(0);
    }

    public function testBuildLdapFilter()
    {
        $this->addLdapServers();

        $ldap = getItemByTypeName('AuthLDAP', 'LDAP3');
        $result = \AuthLDAP::buildLdapFilter($ldap);
        $this->string($result)->isIdenticalTo("(& (email=*) )");

        $_SESSION['ldap_import']['interface'] = \AuthLDAP::SIMPLE_INTERFACE;
        $_SESSION['ldap_import']['criterias'] = ['name'        => 'foo',
            'phone_field' => '+33454968584'
        ];
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

    public function testAddTimestampRestrictions()
    {
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

    public function testGetDefault()
    {
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

    public function testPost_updateItem()
    {
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

    public function testPost_addItem()
    {
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

    public function testPrepareInputForAdd()
    {
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

    public function testGetServersWithImportByEmailActive()
    {
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

    public function testgetTabNameForItem()
    {
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

    public function testGetAllReplicateForAMaster()
    {
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

    public function testIsValidGuid()
    {
        $this->boolean(\AuthLDAP::isValidGuid(''))->isFalse();
        $this->boolean(\AuthLDAP::isValidGuid('00000000-0000-0000-0000-000000000000'))->isTrue();
        $this->boolean(\AuthLDAP::isValidGuid('AB52DFB8-A352-BA53-CC58-ABFD5E9D200E'))->isTrue();
        $this->boolean(\AuthLDAP::isValidGuid('ZB52DFH8-AH52-BH53-CH58-ABFD5E9D200E'))->isFalse();
    }

    public function testGuidToHex()
    {
        $guid       = '891b903c-9982-4e64-9c2a-a6caff69f5b0';
        $expected   = '\3c\90\1b\89\82\99\64\4e\9c\2a\a6\ca\ff\69\f5\b0';
        $this->string(\AuthLDAP::guidToHex($guid))->isIdenticalTo($expected);
    }

    public function testGetFieldValue()
    {
        $infos = ['field' => 'value'];
        $this->string(\AuthLDAP::getFieldValue($infos, 'field'))->isIdenticalTo('value');

        $infos = ['objectguid' => 'value'];
        $this->string(\AuthLDAP::getFieldValue($infos, 'objectguid'))->isIdenticalTo('value');
    }

    public function testPassword()
    {
        $ldap = new \AuthLDAP();
        $id = (int)$ldap->add([
            'name'        => 'LDAPcrypted',
            'is_active'   => 1,
            'is_default'  => 0,
            'basedn'      => 'ou=people,dc=mycompany',
            'login_field' => 'uid',
            'phone_field' => 'phonenumber'
        ]);
        $this->integer($id)->isGreaterThan(0);

       //rootdn_passwd is set with a value (a password, not encrypted)
        $password = 'toto';
        $input    = ['id' => $id, 'name' => 'ldap', 'rootdn_passwd' => $password];
        $this->boolean($ldap->update($input))->isTrue();
        $this->boolean($ldap->getFromDB($id))->isTrue();

       //Expected value to be encrypted using current  key
        $this->string((new GLPIKey())->decrypt($ldap->fields['rootdn_passwd']))->isIdenticalTo($password);

        $password = 'tot\'o';
        $input    = ['id' => $id, 'name' => 'ldap', 'rootdn_passwd' => $password];
        $this->boolean($ldap->update($input))->isTrue();
        $this->boolean($ldap->getFromDB($id))->isTrue();

       //Expected value to be encrypted using current key
        $this->string((new GLPIKey())->decrypt($ldap->fields['rootdn_passwd']))->isIdenticalTo($password);

        $input['_blank_passwd'] = 1;
        $result   = $ldap->prepareInputForUpdate($input);
       //rootdn_passwd is set but empty
        $this->string($result['rootdn_passwd'])->isEmpty();
    }

    /**
     * Test LDAP connection
     *
     * @extensions ldap
     *
     * @return void
     */
    public function testTestLDAPConnection()
    {
        $this->boolean(\AuthLDAP::testLDAPConnection(-1))->isFalse();

        $ldap = getItemByTypeName('AuthLDAP', '_local_ldap');
        $this->boolean(\AuthLDAP::testLDAPConnection($ldap->getID()))->isTrue();

        $this->checkLdapConnection($ldap->connect());
    }

    /**
     * Test get users
     *
     * @extensions ldap
     *
     * @return void
     */
    public function testGetAllUsers()
    {
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
    public function testGetAllGroups()
    {
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
    public function testLdapImportUserByServerId()
    {
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
    public function testGetGroupCNByDn()
    {
        $ldap = $this->ldap;

        $connection = $ldap->connect();
        $this->checkLdapConnection($connection);

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
    public function testGetUserByDn()
    {
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
    public function testGetGroupByDn()
    {
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
    public function testLdapImportGroup()
    {
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
    public function testLdapImportUserGroup()
    {
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
    public function testSyncUser()
    {
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

       // update the user in ldap (change phone number)
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

       // check phone number has been synced
        $this->boolean($user->getFromDB($user->getID()))->isTrue();
        $this->array($user->fields)
         ->string['name']->isIdenticalTo('ecuador0')
         ->string['phone']->isIdenticalTo('+33101010101')
         ->string['user_dn']->isIdenticalTo('uid=ecuador0,ou=people,ou=ldap3,dc=glpi,dc=org');

       // update sync field of user
        $this->boolean(
            $ldap->update([
                'id'           => $ldap->getID(),
                'sync_field'   => 'employeenumber'
            ])
        )->isTrue();

        $this->boolean($ldap->isSyncFieldEnabled())->isTrue();

       // add sync field attribute in ldap user
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

       // rename the user (uid change, syncfield keep its value)
        $this->boolean(
            ldap_rename(
                $ldap->connect(),
                'uid=ecuador0,ou=people,ou=ldap3,dc=glpi,dc=org',
                'uid=testecuador',
                '',
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
                '',
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

       // check the `name` field (corresponding to the uid) has been updated for the user
        $this->boolean($user->getFromDB($user->getID()))->isTrue();
        $this->array($synchro)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_SYNCHRONIZED)
         ->variable['id']->isEqualTo($user->getID());

        $this->variable($user->fields['sync_field'])->isEqualTo(42);
        $this->string($user->fields['name'])->isIdenticalTo('testecuador');

       // ## test we can sync the user when the syncfield is different but after we reset it manually
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
            ldap_mod_replace(
                $ldap->connect(),
                'uid=ecuador0,ou=people,ou=ldap3,dc=glpi,dc=org',
                ['employeeNumber' => '43']
            )
        )->isTrue();

       // do a simple sync
        $synchro = $ldap->forceOneUserSynchronization($user);
        $this->boolean($user->getFromDB($user->getID()))->isTrue();

       // the sync field should have been kept
       // but the user should now be in non synchronized state
        $this->variable($user->fields['sync_field'])->isEqualTo(42);
        $this->array($synchro)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_DELETED_LDAP)
         ->variable['id']->isEqualTo($user->getID());

       // sync after emptying the sync field
        $synchro2 = $ldap->forceOneUserSynchronization($user, true);
        $this->boolean($user->getFromDB($user->getID()))->isTrue();

       // the sync field should have changed
       // and the user is now synchronized again
        $this->variable($user->fields['sync_field'])->isEqualTo(43);
        $this->array($synchro2)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_SYNCHRONIZED)
         ->variable['id']->isEqualTo($user->getID());

       // reset attribute
        $this->boolean(
            ldap_mod_del(
                $ldap->connect(),
                'uid=ecuador0,ou=people,ou=ldap3,dc=glpi,dc=org',
                ['employeeNumber' => 43]
            )
        )->isTrue();

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
    public function testLdapAuth()
    {
       //try to login from a user that does not exists yet
        $auth = $this->login('brazil6', 'password', false);

        $user = new \User();
        $user->getFromDBbyName('brazil6');
        $this->array($user->fields)
         ->string['name']->isIdenticalTo('brazil6')
         ->string['user_dn']->isIdenticalTo('uid=brazil6,ou=people,ou=ldap3,dc=glpi,dc=org');
        $this->boolean($auth->user_present)->isFalse();
        $this->boolean($auth->user_dn)->isFalse();
        $this->checkLdapConnection($auth->ldap_connection);

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
        $this->checkLdapConnection($auth->ldap_connection);

       //change user login, and try again. Existing user should be updated.
        $this->boolean(
            ldap_rename(
                $ldap->connect(),
                'uid=brazil7,ou=people,ou=ldap3,dc=glpi,dc=org',
                'uid=brazil7test',
                '',
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
                '',
                true
            )
        )->isTrue();

        $this->boolean($user->getFromDB($user->getID()))->isTrue();
        $this->array($user->fields)
         ->string['name']->isIdenticalTo('brazil7test')
         ->string['user_dn']->isIdenticalTo('uid=brazil7test,ou=people,ou=ldap3,dc=glpi,dc=org');

        $this->boolean($auth->user_present)->isTrue();
        $this->checkLdapConnection($auth->ldap_connection);

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
    public function testLdapAuthSpecifyAuth()
    {
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
        $this->boolean($auth->login('brazil8', 'password', false, false, 'ldap-' . $this->ldap->getID()))->isTrue();
        $user_ldap_id = $auth->user->fields['id'];
        $this->integer($user_ldap_id)->isNotEqualTo($user_id);

        $auth = new \Auth();
        $this->boolean($auth->login('brazil8', 'passwordlocal', false, false, 'ldap-' . $this->ldap->getID()))->isFalse();

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
    public function testGetUsers()
    {
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
            'uid'       => 'remi'

        ]);
    }

    /**
     * Test removed users
     *
     * @extensions ldap
     *
     * @return void
     */
    public function testRemovedUser()
    {
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

        $this->when(
            function () use ($ldap, $user) {
                $synchro = $ldap->forceOneUserSynchronization($user);
                $this->boolean($synchro)->isFalse();
            }
        )
            ->error()
                ->withType(E_USER_WARNING)
                ->withMessage("Unable to bind to LDAP server `server-does-not-exists.org:1234` with RDN `cn=Manager,dc=glpi,dc=org`\nerror: Can't contact LDAP server (-1)")
            ->exists();

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

    /**
     * Test restoring users from LDAP
     *
     * @extensions ldap
     *
     * @return void
     */
    public function testRestoredUser()
    {
        global $CFG_GLPI;

        $ldap = $this->ldap;

       //add a new user in directory
        $this->boolean(
            ldap_add(
                $ldap->connect(),
                'uid=torestoretest,ou=people,ou=ldap3,dc=glpi,dc=org',
                [
                    'uid'          => 'torestoretest',
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
                'value'  => 'torestoretest'
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
        $this->boolean((bool)$user->fields['is_deleted'])->isFalse();
        $this->boolean((bool)$user->fields['is_deleted_ldap'])->isFalse();

       // delete the user in LDAP
        $this->boolean(
            ldap_delete(
                $ldap->connect(),
                'uid=torestoretest,ou=people,ou=ldap3,dc=glpi,dc=org'
            )
        )->isTrue();

        $user_deleted_ldap_original = $CFG_GLPI['user_deleted_ldap'] ?? 0;
       //put deleted LDAP users in trashbin
        $CFG_GLPI['user_deleted_ldap'] = 1;
        $synchro = $ldap->forceOneUserSynchronization($user);
        $CFG_GLPI['user_deleted_ldap'] = $user_deleted_ldap_original;
        $this->array($synchro)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_DELETED_LDAP)
         ->variable['id']->isEqualTo($import['id']);

       //reload user from DB
        $this->boolean($user->getFromDB($import['id']))->isTrue();
        $this->boolean((bool)$user->fields['is_deleted'])->isTrue();
        $this->boolean((bool)$user->fields['is_deleted_ldap'])->isTrue();

       // manually re-add the user in LDAP to simulate a restore
        $this->boolean(
            ldap_add(
                $ldap->connect(),
                'uid=torestoretest,ou=people,ou=ldap3,dc=glpi,dc=org',
                [
                    'uid'          => 'torestoretest',
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

        $user_restored_ldap_original = $CFG_GLPI['user_restored_ldap'] ?? 0;
        $CFG_GLPI['user_restored_ldap'] = 1;
        $synchro = $ldap->forceOneUserSynchronization($user);
        $CFG_GLPI['user_restored_ldap'] = $user_restored_ldap_original;
        $this->array($synchro)
         ->hasSize(2)
         ->integer['action']->isIdenticalTo(\AuthLDAP::USER_RESTORED_LDAP)
         ->variable['id']->isEqualTo($import['id']);

       //reload user from DB
        $this->boolean($user->getFromDB($import['id']))->isTrue();
        $this->boolean((bool)$user->fields['is_deleted'])->isFalse();
    }

    protected function ssoVariablesProvider()
    {
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
    public function testOtherAuth($sso_field_id, $sso_field_name)
    {
        global $CFG_GLPI;

        $config_values = \Config::getConfigurationValues('core', ['ssovariables_id']);
        \Config::setConfigurationValues('core', [
            'ssovariables_id' => $sso_field_id
        ]);
        $CFG_GLPI['ssovariables_id'] = $sso_field_id;
        $_SERVER[$sso_field_name] = 'brazil6';

        unset($_SESSION['glpiname']);

        $auth = new \Auth();
        $this->boolean($auth->login("", ""))->isTrue();
        $this->string($_SESSION['glpiname'])->isEqualTo('brazil6');

       //reset config
        \Config::setConfigurationValues('core', [
            'ssovariables_id' => $config_values['ssovariables_id']
        ]);
    }

    /**
     * @extensions ldap
     */
    public function testSyncLongDN()
    {
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

    /**
     * @extensions ldap
     */
    public function testSyncLongDNiCyrillic()
    {
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

    protected function testSyncWithManagerProvider()
    {
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

        return array_map(function ($dn, $key) use ($entry) {
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
     *
     * @extensions ldap
     */
    public function testSyncWithManager($manager_dn, array $manager_entry)
    {
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
        $this->boolean($user->delete(['id' => $user->fields['id']], 1))->isTrue();
        $this->boolean($user->delete(['id' => $manager->fields['id']], 1))->isTrue();
    }

    /**
     * Test if rules targeting ldap criteria are working
     *
     * @return void
     */
    public function testRuleRight()
    {
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
            if (
                isset($right['entities_id']) && $right['entities_id'] == 1
                && isset($right['profiles_id']) && $right['profiles_id'] == 5
                && isset($right['is_dynamic']) && $right['is_dynamic'] == 1
            ) {
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

    /**
     * @extensions ldap
     */
    public function testLdapUnavailable()
    {
        //Import user that doesn't exist yet
        $auth = $this->login('brazil5', 'password', false);

        $user = new \User();
        $user->getFromDBbyName('brazil5');
        $this->array($user->fields)
            ->string['name']->isIdenticalTo('brazil5')
            ->string['user_dn']->isIdenticalTo('uid=brazil5,ou=people,ou=ldap3,dc=glpi,dc=org');
        $this->boolean($auth->user_present)->isFalse();
        $this->boolean($auth->user_dn)->isFalse();
        $this->checkLdapConnection($auth->ldap_connection);

        // Add a second LDAP server that is accessible but where user will not be found.
        $input = $this->ldap->fields;
        unset($input['id']);
        $input['rootdn_passwd'] = 'insecure'; // cannot reuse encrypted password from `$this->ldap->fields`
        $input['basedn'] = 'dc=notglpi'; // use a non-matching base DN to ensure user cannot login on it
        $ldap = new \AuthLDAP();
        $this->integer($ldap->add($input))->isGreaterThan(0);

        // Update first LDAP server to make it inaccessible.
        $this->boolean(
            $this->ldap->update([
                'id'     => $this->ldap->getID(),
                'port'   => '1234',
            ])
        )->isTrue();

        $this->when(
            function () {
                $this->login('brazil5', 'password', false, false);
            }
        )
            ->error()
                ->withType(E_USER_WARNING)
                ->withMessage("Unable to bind to LDAP server `openldap:1234` with RDN `cn=Manager,dc=glpi,dc=org`\nerror: Can't contact LDAP server (-1)")
                ->exists();

        $user->getFromDBbyName('brazil5');
        // Verify trying to log in while LDAP unavailable does not disable user's GLPI account
        $this->integer($user->fields['is_active'])->isEqualTo(1);
        $this->integer($user->fields['is_deleted_ldap'])->isEqualTo(0);
    }

    /**
     * @extensions ldap
     */
    public function testLdapDeletionOnLogin()
    {
        $connection = $this->ldap->connect();
        $this->checkLdapConnection($connection);

        // Add a new user in directory
        $this->boolean(
            ldap_add(
                $connection,
                'uid=logintest,ou=people,ou=ldap3,dc=glpi,dc=org',
                [
                    'uid'          => 'logintest',
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

        //Import user that doesn't exist yet
        $auth = $this->login('logintest', 'password');

        $user = new \User();
        $user->getFromDBbyName('logintest');
        $this->array($user->fields)
            ->string['name']->isIdenticalTo('logintest')
            ->string['user_dn']->isIdenticalTo('uid=logintest,ou=people,ou=ldap3,dc=glpi,dc=org');
        $this->boolean($auth->user_present)->isFalse();
        $this->boolean($auth->user_dn)->isFalse();
        $this->checkLdapConnection($auth->ldap_connection);

        // Add a second LDAP server that is accessible but where user will not be found.
        $input = $this->ldap->fields;
        unset($input['id']);
        $input['rootdn_passwd'] = 'insecure'; // cannot reuse encrypted password from `$this->ldap->fields`
        $input['basedn'] = 'dc=notglpi'; // use a non-matching base DN to ensure user cannot login on it
        $ldap = new \AuthLDAP();
        $this->integer($ldap->add($input))->isGreaterThan(0);

        // Delete the user
        $this->boolean(
            ldap_delete(
                $connection,
                'uid=logintest,ou=people,ou=ldap3,dc=glpi,dc=org'
            )
        )->isTrue();

        $auth = new \Auth();
        $this->boolean($auth->login('logintest', 'password'))->isFalse();

        $user->getFromDBbyName('logintest');
        $this->integer($user->fields['is_deleted_ldap'])->isEqualTo(1);
    }

    /**
     * @extensions ldap
     */
    public function testLdapLoginWithWrongPassword()
    {
        $auth = new \Auth();
        $this->boolean($auth->login('brazil5', 'wrong-password', false))->isFalse();

        $user = new \User();
        $this->boolean($user->getFromDBbyName('brazil5'))->isFalse();
    }

    private function checkLdapConnection($ldap_connection)
    {
        if (version_compare(phpversion(), '8.1.0-dev', '<')) {
            $this->resource($ldap_connection)->isOfType('ldap link');
        } else {
            $this->object($ldap_connection)->isInstanceOf('\LDAP\Connection');
        }
    }

    public function testIgnoreImport()
    {
        //prepare rules
        $rules = new \RuleRight();
        $rules_id = $rules->add([
            'sub_type'     => 'RuleRight',
            'name'         => 'test ldap ignore import',
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
        $actions = new \RuleAction();
        $actions->add([
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => '_ignore_user_import',
            'value'       => '1', // Reject
        ]);
        // login the user to force synchronisation
        $this->login('brazil6', 'password', false, false);

        // Check title not created
        $ut = new UserTitle();
        $uts = $ut->find([
            'name' => 'manager',
        ]);
        $this->array($uts)->hasSize(0);
    }

    protected function connectToServerErrorsProvider(): iterable
    {
        yield [
            'host'     => 'invalidserver',
            'port'     => '3890',
            'login'    => '',
            'password' => '',
            'error'    => implode(
                "\n",
                [
                    'Unable to bind to LDAP server `invalidserver:3890` anonymously',
                    'error: Can\'t contact LDAP server (-1)',
                ]
            ),
        ];

        yield [
            'host'     => 'openldap',
            'port'     => '12345',
            'login'    => '',
            'password' => '',
            'error'    => implode(
                "\n",
                [
                    'Unable to bind to LDAP server `openldap:12345` anonymously',
                    'error: Can\'t contact LDAP server (-1)',
                ]
            ),
        ];

        yield [
            'host'     => 'openldap',
            'port'     => '3890',
            'login'    => 'notavalidrdn',
            'password' => '',
            'error'    => implode(
                "\n",
                [
                    'Unable to bind to LDAP server `openldap:3890` with RDN `notavalidrdn`',
                    'error: Invalid DN syntax (34)',
                    'extended error: invalid DN',
                    'err string: invalid DN',
                ]
            ),
        ];

        yield [
            'host'     => 'openldap',
            'port'     => '3890',
            'login'    => 'cn=Manager,dc=glpi,dc=org',
            'password' => 'wrongpassword',
            'error'    => implode(
                "\n",
                [
                    'Unable to bind to LDAP server `openldap:3890` with RDN `cn=Manager,dc=glpi,dc=org`',
                    'error: Invalid credentials (49)',
                ]
            ),
        ];
    }

    /**
     * @dataProvider connectToServerErrorsProvider
     *
     * @extensions ldap
     */
    public function testConnectToServerErrorMessage(
        string $host,
        string $port,
        string $login,
        string $password,
        string $error
    ) {
        $this->when(
            function () use ($host, $port, $login, $password) {
                \AuthLDAP::connectToServer($host, $port, $login, $password);
            }
        )->error()->withType(E_USER_WARNING)->withMessage($error)->exists();
    }

    /**
     * @extensions ldap
     */
    public function testConnectToServerTlsError()
    {
        $error = implode(
            "\n",
            [
                'Unable to start TLS connection to LDAP server `openldap:3890`',
                'error: Protocol error (2)',
                'extended error: unsupported extended operation',
                'err string: unsupported extended operation',
            ]
        );

        $this->when(
            function () {
                \AuthLDAP::connectToServer(
                    'openldap',
                    '3890',
                    'cn=Manager,dc=glpi,dc=org',
                    'insecure',
                    true,
                );
            }
        )->error()->withType(E_USER_WARNING)->withMessage($error)->exists();
    }

    /**
     * @extensions ldap
     */
    public function testGetGroupCNByDnError()
    {

        $ldap = getItemByTypeName('AuthLDAP', '_local_ldap');
        $connection = $ldap->connect();
        $this->checkLdapConnection($connection);

        $error = implode(
            "\n",
            [
                'Unable to get LDAP group having DN `notavaliddn`',
                'error: Invalid DN syntax (34)',
                'extended error: invalid DN',
                'err string: invalid DN',
            ]
        );

        $this->when(
            function () use ($connection) {
                \AuthLDAP::getGroupCNByDn($connection, 'notavaliddn');
            }
        )->error()->withType(E_USER_WARNING)->withMessage($error)->exists();
    }


    protected function getObjectGroupByDnErrorsProvider(): iterable
    {
        // invalid base DN
        yield [
            'basedn' => 'notavalidbasedn',
            'filter' => '(objectclass=inetOrgPerson)',
            'error'  => implode(
                "\n",
                [
                    'Unable to get LDAP object having DN `notavalidbasedn` with filter `(objectclass=inetOrgPerson)`',
                    'error: Invalid DN syntax (34)',
                    'extended error: invalid DN',
                    'err string: invalid DN',
                ]
            ),
        ];

        // invalid filter
        yield [
            'basedn' => 'dc=glpi,dc=org',
            'filter' => 'notavalidfilter',
            'error'  => implode(
                "\n",
                [
                    'Unable to get LDAP object having DN `dc=glpi,dc=org` with filter `notavalidfilter`',
                    'error: Bad search filter (-7)',
                ]
            ),
        ];
    }

    /**
     * @dataProvider getObjectGroupByDnErrorsProvider
     *
     * @extensions ldap
     */
    public function testGetObjectGroupByDnError(
        string $basedn,
        string $filter,
        string $error
    ) {
        $ldap = getItemByTypeName('AuthLDAP', '_local_ldap');
        $connection = $ldap->connect();
        $this->checkLdapConnection($connection);

        $this->when(
            function () use ($connection, $basedn, $filter) {
                \AuthLDAP::getObjectByDn($connection, $filter, $basedn, ['dn']);
            }
        )->error()->withType(E_USER_WARNING)->withMessage($error)->exists();
    }

    protected function searchForUsersErrorsProvider(): iterable
    {
        // error messages should be identical whether pagesize support is enabled or not
        $configs = [
            [
                'can_support_pagesize' => 0,
            ],
            [
                'can_support_pagesize' => 1,
                'pagesize'             => 100,
            ],
        ];
        foreach ($configs as $config_fields) {
            // invalid base DN
            yield [
                'config_fields' => $config_fields,
                'basedn'        => 'notavalidbasedn',
                'filter'        => '(objectclass=inetOrgPerson)',
                'error'         => implode(
                    "\n",
                    [
                        'LDAP search with base DN `notavalidbasedn` and filter `(objectclass=inetOrgPerson)` failed',
                        'error: Invalid DN syntax (34)',
                        'extended error: invalid DN',
                        'err string: invalid DN',
                    ]
                ),
            ];

            // invalid filter
            yield [
                'config_fields' => $config_fields,
                'basedn'        => 'dc=glpi,dc=org',
                'filter'        => 'notavalidfilter',
                'error'         => implode(
                    "\n",
                    [
                        'LDAP search with base DN `dc=glpi,dc=org` and filter `notavalidfilter` failed',
                        'error: Bad search filter (-7)',
                    ]
                ),
            ];
        }

        // invalid pagesize
        yield [
            'config_fields' => [
                'can_support_pagesize' => 1,
                'pagesize'             => 0,
            ],
            'basedn'        => 'dc=glpi,dc=org',
            'filter'        => '(objectclass=inetOrgPerson)',
            'error'         => implode(
                "\n",
                [
                    'LDAP search with base DN `dc=glpi,dc=org` and filter `(objectclass=inetOrgPerson)` failed',
                    'error: Bad parameter to an ldap routine (-9)',
                ]
            ),
        ];
    }

    /**
     * @dataProvider searchForUsersErrorsProvider
     *
     * @extensions ldap
     */
    public function testSearchForUsersErrorMessages(
        array $config_fields,
        string $basedn,
        string $filter,
        string $error
    ) {
        $ldap = getItemByTypeName('AuthLDAP', '_local_ldap');
        $ldap->fields = array_merge($ldap->fields, $config_fields);

        $connection = $ldap->connect();
        $this->checkLdapConnection($connection);

        $this->when(
            function () use ($ldap, $connection, $basedn, $filter) {
                $limitexceeded = $user_infos = $ldap_users = null;

                \AuthLDAP::searchForUsers(
                    $connection,
                    ['basedn' => $basedn],
                    $filter,
                    ['dn'],
                    $limitexceeded,
                    $user_infos,
                    $ldap_users,
                    $ldap
                );
            }
        )->error()->withType(E_USER_WARNING)->withMessage($error)->exists();
    }

    protected function searchUserDnErrorsProvider(): iterable
    {
        // invalid base DN
        yield [
            'options' => [
                'basedn'    => 'notavalidbasedn',
            ],
            'error'         => implode(
                "\n",
                [
                    'LDAP search with base DN `notavalidbasedn` and filter `(uid=johndoe)` failed',
                    'error: Invalid DN syntax (34)',
                    'extended error: invalid DN',
                    'err string: invalid DN',
                ]
            ),
        ];

        // invalid filter
        yield [
            'options' => [
                'basedn'    => 'dc=glpi,dc=org',
                'condition' => 'invalidfilter)',
            ],
            'error'         => implode(
                "\n",
                [
                    'LDAP search with base DN `dc=glpi,dc=org` and filter `(& (uid=johndoe) invalidfilter))` failed',
                    'error: Bad search filter (-7)',
                ]
            ),
        ];
    }

    /**
     * @dataProvider searchUserDnErrorsProvider
     *
     * @extensions ldap
     */
    public function testSearchUserDnErrorMessages(
        array $options,
        string $error
    ) {
        $ldap = getItemByTypeName('AuthLDAP', '_local_ldap');
        $connection = $ldap->connect();
        $this->checkLdapConnection($connection);

        $this->when(
            function () use ($connection, $options) {
                \AuthLDAP::searchUserDn(
                    $connection,
                    $options + [
                        'login_field'       => 'uid',
                        'search_parameters' => ['fields' => ['login' => 'uid']],
                        'user_params'       => ['value'  => 'johndoe'],
                    ]
                );
            }
        )->error()->withType(E_USER_WARNING)->withMessage($error)->exists();
    }

    protected function getGroupsFromLDAPErrorsProvider(): iterable
    {
        // error messages should be identical whether pagesize support is enabled or not
        $configs = [
            [
                'can_support_pagesize' => 0,
            ],
            [
                'can_support_pagesize' => 1,
                'pagesize'             => 100,
            ],
        ];
        foreach ($configs as $config_fields) {
            // invalid base DN
            yield [
                'config_fields' => $config_fields + ['basedn' => 'notavalidbasedn'],
                'filter'        => '(objectclass=inetOrgPerson)',
                'error'         => implode(
                    "\n",
                    [
                        'LDAP search with base DN `notavalidbasedn` and filter `(objectclass=inetOrgPerson)` failed',
                        'error: Invalid DN syntax (34)',
                        'extended error: invalid DN',
                        'err string: invalid DN',
                    ]
                ),
            ];

            // invalid filter
            yield [
                'config_fields' => $config_fields,
                'filter'        => 'notavalidfilter',
                'error'         => implode(
                    "\n",
                    [
                        'LDAP search with base DN `dc=glpi,dc=org` and filter `notavalidfilter` failed',
                        'error: Bad search filter (-7)',
                    ]
                ),
            ];
        }

        // invalid pagesize
        yield [
            'config_fields' => [
                'can_support_pagesize' => 1,
                'pagesize'             => 0,
            ],
            'filter'        => '(objectclass=groupOfNames)',
            'error'         => implode(
                "\n",
                [
                    'LDAP search with base DN `dc=glpi,dc=org` and filter `(objectclass=groupOfNames)` failed',
                    'error: Bad parameter to an ldap routine (-9)',
                ]
            ),
        ];
    }

    /**
     * @dataProvider getGroupsFromLDAPErrorsProvider
     *
     * @extensions ldap
     */
    public function testGetGroupsFromLDAPErrors(
        array $config_fields,
        string $filter,
        string $error
    ) {
        $ldap = getItemByTypeName('AuthLDAP', '_local_ldap');
        $ldap->fields = array_merge($ldap->fields, $config_fields);

        $connection = $ldap->connect();
        $this->checkLdapConnection($connection);

        $this->when(
            function () use ($ldap, $connection, $filter) {
                $limitexceeded = null;

                \AuthLDAP::getGroupsFromLDAP(
                    $connection,
                    $ldap,
                    $filter,
                    $limitexceeded
                );
            }
        )->error()->withType(E_USER_WARNING)->withMessage($error)->exists();
    }
}
