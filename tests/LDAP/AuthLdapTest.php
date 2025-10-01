<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use AuthLDAP;
use DbTestCase;
use Glpi\DBAL\QueryExpression;
use GLPIKey;
use Group;
use Group_User;
use LDAP\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Profile_User;
use Psr\Log\LogLevel;
use RuleBuilder;
use RuleRight;
use UserTitle;

/* Test for inc/authldap.class.php */

class AuthLdapTest extends DbTestCase
{
    /**
     * @var AuthLDAP
     */
    private $ldap;

    /**
     * Prepare test data
     *
     * Data setup is in tests/src/autoload/functions.php::loadDataset() (as usual)
     *
     * Ensure that:
     * - Correct data is present in the LDAP server
     * - The data is synchronized
     *
     * To configure proper LDAP server settings, use .github/actions/init_initialize-ldap-fixtures.sh
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->ldap = getItemByTypeName('AuthLDAP', '_local_ldap');

        // remove the `_e2e_ldap` server
        $this->deleteItem(AuthLDAP::class, getItemByTypeName(AuthLDAP::class, '_e2e_ldap', true));

        //make sure bootstrapped ldap is active and is default
        $this->assertTrue(
            $this->ldap->update([
                'id'                => $this->ldap->getID(),
                'is_active'         => 1,
                'is_default'        => 1,
                'responsible_field' => "manager",
            ])
        );
    }

    public function tearDown(): void
    {
        //make sure bootstrapped ldap is not active and is default
        $this->assertTrue(
            $this->ldap->update([
                'id'           => $this->ldap->getID(),
                'is_active'    => 1,
                'is_default'   => 1,
            ])
        );

        parent::tearDown();
    }

    private function addLdapServers()
    {
        $ldap = new AuthLDAP();
        $this->assertGreaterThan(
            0,
            (int) $ldap->add([
                'name'        => 'LDAP1',
                'is_active'   => 1,
                'is_default'  => 0,
                'basedn'      => 'ou=people,dc=mycompany',
                'login_field' => 'uid',
                'phone_field' => 'phonenumber',
            ])
        );
        $this->assertGreaterThan(
            0,
            (int) $ldap->add([
                'name'         => 'LDAP2',
                'is_active'    => 0,
                'is_default'   => 0,
                'basedn'       => 'ou=people,dc=mycompany',
                'login_field'  => 'uid',
                'phone_field'  => 'phonenumber',
                'email1_field' => 'email',
            ])
        );
        $this->assertGreaterThan(
            0,
            (int) $ldap->add([
                'name'        => 'LDAP3',
                'is_active'   => 1,
                'is_default'  => 1,
                'basedn'      => 'ou=people,dc=mycompany',
                'login_field' => 'email',
                'phone_field' => 'phonenumber',
                'email1_field' => 'email',
            ])
        );
    }

    public function testGetTypeName()
    {
        $this->assertSame('LDAP directory', AuthLDAP::getTypeName(1));
        $this->assertSame('LDAP directories', AuthLDAP::getTypeName(0));
        $this->assertSame('LDAP directories', AuthLDAP::getTypeName(\Session::getPluralNumber()));
    }

    public function testPost_getEmpty()
    {
        $ldap = new AuthLDAP();
        $ldap->post_getEmpty();
        $this->assertCount(30, $ldap->fields);
    }

    public function testUnsetUndisclosedFields()
    {
        $fields = ['login_field' => 'test', 'rootdn_passwd' => 'mypassword'];
        AuthLDAP::unsetUndisclosedFields($fields);
        $this->assertArrayNotHasKey('rootdn_passwd', $fields);
    }

    public function testPreconfig()
    {
        $ldap = new AuthLDAP();
        //Use Active directory preconfiguration :
        //login_field and sync_field must be filled
        $ldap->preconfig('AD');
        $this->assertSame('samaccountname', $ldap->fields['login_field']);
        $this->assertSame('objectguid', $ldap->fields['sync_field']);

        //Use OpenLDAP preconfiguration :
        //login_field and sync_field must be filled
        $ldap->preconfig('OpenLDAP');
        $this->assertSame('uid', $ldap->fields['login_field']);
        $this->assertSame('entryuuid', $ldap->fields['sync_field']);

        //No preconfiguration model
        $ldap->preconfig('');
        //Login_field is set to uid (default)
        $this->assertSame('uid', $ldap->fields['login_field']);
        $this->assertNull($ldap->fields['sync_field']);
    }

    public function testPrepareInputForUpdate()
    {
        $ldap = $this->getMockBuilder(AuthLDAP::class)
            ->onlyMethods(['isSyncFieldUsed'])
            ->getMock();
        $ldap->method('isSyncFieldUsed')->willReturn(true);

        //------------ Password tests --------------------//
        $input  = ['name' => 'ldap', 'rootdn_passwd' => ''];
        $result = $ldap->prepareInputForUpdate($input);
        //empty rootdn_passwd set : should not appear in the response array
        $this->assertArrayNotHasKey('rootdn_passwd', $result);

        //no rootdn_passwd set : should not appear in the response array
        $input  = ['name' => 'ldap'];
        $result = $ldap->prepareInputForUpdate($input);
        $this->assertArrayNotHasKey('rootdn_passwd', $result);

        //Field name finishing with _field : set the value in lower case
        $input['_login_field'] = 'TEST';
        $result         = $ldap->prepareInputForUpdate($input);
        $this->assertSame('test', $result['_login_field']);

        $input['sync_field'] = 'sync_field';
        $result = $ldap->prepareInputForUpdate($input);
        $this->assertSame('sync_field', $result['sync_field']);

        //test sync_field update
        $ldap->fields['sync_field'] = 'sync_field';
        $result = $ldap->prepareInputForUpdate($input);
        $this->assertArrayNotHasKey('sync_field', $result);

        $input['sync_field'] = 'another_field';
        $result = $ldap->prepareInputForUpdate($input);
        $this->assertFalse($result);
        $this->hasSessionMessages(ERROR, ['Synchronization field cannot be changed once in use.']);

        $ldap = $this->getMockBuilder(AuthLDAP::class)
            ->onlyMethods(['isSyncFieldUsed'])
            ->getMock();
        $ldap->method('isSyncFieldUsed')->willReturn(false);
        $result = $ldap->prepareInputForUpdate($input);
        $this->assertArrayHasKey('sync_field', $result);
    }

    public function testgetGroupSearchTypeName()
    {
        //Get all group search type values
        $search_type = AuthLDAP::getGroupSearchTypeName();
        $this->assertCount(3, $search_type);

        //Give a wrong number value
        $search_type = AuthLDAP::getGroupSearchTypeName(4);
        $this->assertSame(NOT_AVAILABLE, $search_type);

        //Give a wrong string value
        $search_type = AuthLDAP::getGroupSearchTypeName('toto');
        $this->assertSame(NOT_AVAILABLE, $search_type);

        //Give a existing values
        $search_type = AuthLDAP::getGroupSearchTypeName(0);
        $this->assertSame('In users', $search_type);

        $search_type = AuthLDAP::getGroupSearchTypeName(1);
        $this->assertSame('In groups', $search_type);

        $search_type = AuthLDAP::getGroupSearchTypeName(2);
        $this->assertSame('In users and groups', $search_type);
    }

    public function testGetSpecificValueToDisplay()
    {
        $ldap = new AuthLDAP();

        //Value as an array
        $values = ['group_search_type' => 0];
        $result = $ldap->getSpecificValueToDisplay('group_search_type', $values);
        $this->assertSame('In users', $result);

        //Value as a single value
        $values = 1;
        $result = $ldap->getSpecificValueToDisplay('group_search_type', $values);
        $this->assertSame('In groups', $result);

        //Value as a single value
        $values = ['name' => 'ldap'];
        $result = $ldap->getSpecificValueToDisplay('name', $values);
        $this->assertEmpty($result);
    }

    public function testDefineTabs()
    {
        $ldap     = new AuthLDAP();
        $tabs     = $ldap->defineTabs();
        $expected = [
            'AuthLDAP$main' => "LDAP directory",
        ];
        $tabs = array_map('strip_tags', $tabs);
        $this->assertSame($expected, $tabs);
    }

    public function testGetSearchOptionsNew()
    {
        $ldap     = new AuthLDAP();
        $options  = $ldap->rawSearchOptions();
        $this->assertCount(36, $options);
    }

    public function testGetSyncFields()
    {
        $ldap     = new AuthLDAP();
        $values   = ['login_field' => 'value'];
        $result   = $ldap->getSyncFields($values);
        $this->assertSame(['name' => 'value'], $result);

        $result   = $ldap->getSyncFields([]);
        $this->assertEmpty($result);
    }

    public function testLdapStamp2UnixStamp()
    {
        //Good timestamp
        $result = AuthLDAP::ldapStamp2UnixStamp('20161114100339Z');
        $this->assertSame(1479117819, $result);

        //Bad timestamp format
        $result = AuthLDAP::ldapStamp2UnixStamp(20161114100339);
        $this->assertEmpty($result);

        //Bad timestamp format
        $result = AuthLDAP::ldapStamp2UnixStamp("201611141003");
        $this->assertEmpty($result);
    }

    public function testDate2ldapTimeStamp()
    {
        $result = AuthLDAP::date2ldapTimeStamp("2017-01-01 22:35:00");
        $this->assertSame("20170101223500.0Z", $result);

        //Bad date => 01/01/1970
        $result = AuthLDAP::date2ldapTimeStamp("2017-25-25 22:35:00");
        $this->assertSame("19700101000000.0Z", $result);
    }

    public function testDnExistsInLdap()
    {
        $ldap_infos = [ ['uid'      => 'jdoe',
            'cn'       => 'John Doe',
            'user_dn'  => 'uid=jdoe, ou=people, dc=mycompany',
        ],
            ['uid'      => 'asmith',
                'cn'       => 'Agent Smith',
                'user_dn'  => 'uid=asmith, ou=people, dc=mycompany',
            ],
        ];

        //Ask for a non-existing user_dn : result is false
        $this->assertFalse(
            AuthLDAP::dnExistsInLdap(
                $ldap_infos,
                'uid=jdupont, ou=people, dc=mycompany'
            )
        );

        //Ask for a dn that exists : result is the user's infos as an array
        $result = AuthLDAP::dnExistsInLdap(
            $ldap_infos,
            'uid=jdoe, ou=people, dc=mycompany'
        );
        $this->assertCount(3, $result);
    }

    public function testGetLdapServers()
    {
        $this->addLdapServers();

        //The list of ldap server show the default server in first position
        $result = AuthLDAP::getLdapServers();
        $this->assertCount(4, $result);
        $this->assertSame('LDAP3', current($result)['name']);
    }

    public function testUseAuthLdap()
    {
        global $DB;
        $this->addLdapServers();

        $this->assertTrue(AuthLDAP::useAuthLdap());
        $DB->update('glpi_authldaps', ['is_active' => 0], [new QueryExpression('true')]);
        $this->assertFalse(AuthLDAP::useAuthLdap());
    }

    public function testGetNumberOfServers()
    {
        global $DB;
        $this->addLdapServers();

        $this->assertSame(3, (int) AuthLDAP::getNumberOfServers());
        $DB->update('glpi_authldaps', ['is_active' => 0], [new QueryExpression('true')]);
        $this->assertSame(0, (int) AuthLDAP::getNumberOfServers());
    }

    public function testBuildLdapFilter()
    {
        $this->addLdapServers();

        /** @var AuthLDAP $ldap */
        $ldap = getItemByTypeName('AuthLDAP', 'LDAP3');
        $result = AuthLDAP::buildLdapFilter($ldap);
        $this->assertSame("(& (email=*) )", $result);

        $_REQUEST['interface'] = AuthLDAP::SIMPLE_INTERFACE;
        $_REQUEST['criterias'] = ['name'        => 'foo',
            'phone_field' => '+33454968584',
        ];
        $result = AuthLDAP::buildLdapFilter($ldap);
        $this->assertSame('(& (LDAP3=*foo*)(phonenumber=*+33454968584*) )', $result);

        $_REQUEST['criterias']['name'] = '^foo';
        $result = AuthLDAP::buildLdapFilter($ldap);
        $this->assertSame('(& (LDAP3=foo*)(phonenumber=*+33454968584*) )', $result);

        $_REQUEST['criterias']['name'] = 'foo$';
        $result = AuthLDAP::buildLdapFilter($ldap);
        $this->assertSame('(& (LDAP3=*foo)(phonenumber=*+33454968584*) )', $result);

        $_REQUEST['criterias']['name'] = '^foo$';
        $result = AuthLDAP::buildLdapFilter($ldap);
        $this->assertSame('(& (LDAP3=foo)(phonenumber=*+33454968584*) )', $result);

        $_REQUEST['criterias'] = ['name' => '^foo$'];
        $ldap->fields['condition'] = '(objectclass=inetOrgPerson)';
        $result = AuthLDAP::buildLdapFilter($ldap);
        $ldap->fields['condition'] = '';
        $this->assertSame('(& (LDAP3=foo) (objectclass=inetOrgPerson))', $result);

        $_REQUEST['begin_date']        = '2017-04-20 00:00:00';
        $_REQUEST['end_date']          = '2017-04-22 00:00:00';
        $_REQUEST['criterias']['name'] = '^foo$';
        $result = AuthLDAP::buildLdapFilter($ldap);
        $this->assertSame(
            '(& (LDAP3=foo)(modifyTimestamp>=20170420000000.0Z)(modifyTimestamp<=20170422000000.0Z) )',
            $result
        );
    }

    public function testAddTimestampRestrictions()
    {
        $result = AuthLDAP::addTimestampRestrictions(
            '',
            '2017-04-22 00:00:00'
        );
        $this->assertSame("(modifyTimestamp<=20170422000000.0Z)", $result);

        $result = AuthLDAP::addTimestampRestrictions(
            '2017-04-20 00:00:00',
            ''
        );
        $this->assertSame("(modifyTimestamp>=20170420000000.0Z)", $result);

        $result = AuthLDAP::addTimestampRestrictions('', '');
        $this->assertEmpty($result);

        $result = AuthLDAP::addTimestampRestrictions(
            '2017-04-20 00:00:00',
            '2017-04-22 00:00:00'
        );
        $this->assertSame("(modifyTimestamp>=20170420000000.0Z)(modifyTimestamp<=20170422000000.0Z)", $result);
    }

    public function testPost_updateItem()
    {
        //Load ldap servers
        $this->addLdapServers();

        //Get first lDAP server
        $ldap = getItemByTypeName('AuthLDAP', 'LDAP1');

        //Set it as default server
        $this->assertTrue(
            $ldap->update(['id' => $ldap->getID(), 'is_default' => 1])
        );

        //Get first lDAP server now
        $ldap = getItemByTypeName('AuthLDAP', 'LDAP1');
        $this->assertEquals(1, $ldap->fields['is_default']);

        //Get third ldap server (former default one)
        $ldap = getItemByTypeName('AuthLDAP', 'LDAP3');
        //Check that it's not the default server anymore
        $this->assertEquals(0, $ldap->fields['is_default']);
    }

    public function testPost_addItem()
    {
        //Load ldap servers
        $this->addLdapServers();

        $ldap     = new AuthLDAP();
        $ldaps_id = $ldap->add([
            'name'        => 'LDAP4',
            'is_active'   => 1,
            'is_default'  => 1,
            'basedn'      => 'ou=people,dc=mycompany',
            'login_field' => 'email',
            'phone_field' => 'phonenumber',
        ]);
        $this->assertGreaterThan(0, (int) $ldaps_id);
        $this->assertTrue($ldap->getFromDB($ldaps_id));
        $this->assertEquals(1, $ldap->fields['is_default']);

        //Get third ldap server (former default one)
        $ldap = getItemByTypeName('AuthLDAP', 'LDAP3');
        //Check that it's not the default server anymore
        $this->assertEquals(0, $ldap->fields['is_default']);
    }

    public function testPrepareInputForAdd()
    {
        $ldap     = new AuthLDAP();

        $ldaps_id = $ldap->add([
            'name'        => 'LDAP1',
            'is_active'   => 1,
            'basedn'      => 'ou=people,dc=mycompany',
            'login_field' => 'email',
            'rootdn_passwd' => 'password',
        ]);
        $this->assertGreaterThan(0, (int) $ldaps_id);
        $this->assertTrue($ldap->getFromDB($ldaps_id));
        $this->assertEmpty(0, $ldap->fields['is_default']);
        $this->assertNotEquals('password', $ldap->fields['rootdn_passwd']);
    }

    public function testGetServersWithImportByEmailActive()
    {
        $result = AuthLDAP::getServersWithImportByEmailActive();
        $this->assertCount(1, $result);

        $this->addLdapServers();

        //Return two ldap server: because LDAP2 is disabled
        $result = AuthLDAP::getServersWithImportByEmailActive();
        $this->assertCount(2, $result);

        //Enable LDAP2
        $ldap = getItemByTypeName('AuthLDAP', 'LDAP2');
        $this->assertTrue(
            $ldap->update([
                'id' => $ldap->getID(),
                'is_active' => 1,
            ])
        );

        //Now there should be 2 enabled servers
        $result = AuthLDAP::getServersWithImportByEmailActive();
        $this->assertCount(3, $result);
    }

    public function testGetTabNameForItem()
    {
        $this->login();
        $this->addLdapServers();

        $ldap   = getItemByTypeName('AuthLDAP', 'LDAP1');
        $result = $ldap->getTabNameForItem($ldap);
        $result = array_map('strip_tags', $result);
        $expected = [
            1 => "Test",
            2 => "Users",
            3 => "Groups",
            5 => "Advanced information",
            6 => "Replicates",
        ];
        $this->assertSame($expected, $result);

        $result = $ldap->getTabNameForItem($ldap, 1);
        $this->assertEmpty($result);
    }

    public function testGetAllReplicateForAMaster()
    {
        $ldap      = new AuthLDAP();
        $replicate = new \AuthLdapReplicate();

        $ldaps_id = $ldap->add([
            'name'        => 'LDAP1',
            'is_active'   => 1,
            'is_default'  => 0,
            'basedn'      => 'ou=people,dc=mycompany',
            'login_field' => 'uid',
            'phone_field' => 'phonenumber',
        ]);
        $this->assertGreaterThan(0, (int) $ldaps_id);

        $this->assertGreaterThan(
            0,
            (int) $replicate->add([
                'name'         => 'replicate1',
                'host'         => 'myhost1',
                'port'         => 3306,
                'authldaps_id' => $ldaps_id,
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $replicate->add([
                'name'         => 'replicate2',
                'host'         => 'myhost1',
                'port'         => 3306,
                'authldaps_id' => $ldaps_id,
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $replicate->add([
                'name'         => 'replicate3',
                'host'         => 'myhost1',
                'port'         => 3306,
                'authldaps_id' => $ldaps_id,
            ])
        );

        $result = $ldap->getAllReplicateForAMaster($ldaps_id);
        $this->assertCount(3, $result);

        $result = $ldap->getAllReplicateForAMaster(100);
        $this->assertCount(0, $result);
    }

    public function testIsValidGuid()
    {
        $this->assertFalse(AuthLDAP::isValidGuid(''));
        $this->assertTrue(AuthLDAP::isValidGuid('00000000-0000-0000-0000-000000000000'));
        $this->assertTrue(AuthLDAP::isValidGuid('AB52DFB8-A352-BA53-CC58-ABFD5E9D200E'));
        $this->assertFalse(AuthLDAP::isValidGuid('ZB52DFH8-AH52-BH53-CH58-ABFD5E9D200E'));
    }

    public function testGuidToHex()
    {
        $guid       = '891b903c-9982-4e64-9c2a-a6caff69f5b0';
        $expected   = '\3c\90\1b\89\82\99\64\4e\9c\2a\a6\ca\ff\69\f5\b0';
        $this->assertSame($expected, AuthLDAP::guidToHex($guid));
    }

    public function testGetFieldValue()
    {
        $infos = ['field' => 'value'];
        $this->assertSame('value', AuthLDAP::getFieldValue($infos, 'field'));

        $infos = ['objectguid' => 'value'];
        $this->assertSame('value', AuthLDAP::getFieldValue($infos, 'objectguid'));
    }

    public function testPassword()
    {
        $ldap = new AuthLDAP();
        $id = (int) $ldap->add([
            'name'        => 'LDAPcrypted',
            'is_active'   => 1,
            'is_default'  => 0,
            'basedn'      => 'ou=people,dc=mycompany',
            'login_field' => 'uid',
            'phone_field' => 'phonenumber',
        ]);
        $this->assertGreaterThan(0, $id);

        //rootdn_passwd is set with a value (a password, not encrypted)
        $password = 'toto';
        $input    = ['id' => $id, 'name' => 'ldap', 'rootdn_passwd' => $password];
        $this->assertTrue($ldap->update($input));
        $this->assertTrue($ldap->getFromDB($id));

        //Expected value to be encrypted using current  key
        $this->assertSame($password, (new GLPIKey())->decrypt($ldap->fields['rootdn_passwd']));

        $password = 'tot\'o';
        $input    = ['id' => $id, 'name' => 'ldap', 'rootdn_passwd' => $password];
        $this->assertTrue($ldap->update($input));
        $this->assertTrue($ldap->getFromDB($id));

        //Expected value to be encrypted using current key
        $this->assertSame($password, (new GLPIKey())->decrypt($ldap->fields['rootdn_passwd']));

        $input['_blank_passwd'] = 1;
        $result   = $ldap->prepareInputForUpdate($input);
        //rootdn_passwd is set but empty
        $this->assertEmpty($result['rootdn_passwd']);
    }

    public static function hostProvider(): array
    {
        return [
            [
                'host' => 'ldap.example.com',
                'port' => 389,
                'ldapuri' => 'ldap://ldap.example.com:389',
            ],
            [
                'host' => 'ldap://ldap.example.com',
                'port' => 389,
                'ldapuri' => 'ldap://ldap.example.com:389',
            ],
            [
                'host' => 'ldaps://ldap.example.com',
                'port' => 389,
                'ldapuri' => 'ldaps://ldap.example.com:389',
            ],
            [
                'host' => 'LDAP://ldap.example.com',
                'port' => 389,
                'ldapuri' => 'ldap://ldap.example.com:389',
            ],
            [
                'host' => 'LDAPS://ldap.example.com',
                'port' => 389,
                'ldapuri' => 'ldaps://ldap.example.com:389',
            ],
        ];
    }

    #[DataProvider('hostProvider')]
    public function testBuildUri(string $host, int $port, string $ldapuri): void
    {
        $this->assertSame(
            $ldapuri,
            AuthLDAP::buildUri($host, $port)
        );
    }

    /**
     * Test LDAP connection
     *
     * @return void
     */
    #[RequiresPhpExtension('ldap')]
    public function testTestLDAPConnection()
    {
        $this->assertFalse(AuthLDAP::testLDAPConnection(-1));

        $ldap = getItemByTypeName('AuthLDAP', '_local_ldap');
        $host = $ldap->fields['host'];
        $this->assertTrue(AuthLDAP::testLDAPConnection($ldap->getID()));
        $this->checkLdapConnection($ldap->connect());

        $this->assertTrue(
            $ldap->update([
                'id' => $ldap->getID(),
                'host' => 'ldap://' . $host,
            ])
        );
        $this->assertTrue(AuthLDAP::testLDAPConnection($ldap->getID()));
        $this->checkLdapConnection($ldap->connect());

        $this->assertTrue(
            $ldap->update([
                'id' => $ldap->getID(),
                'host' => 'LDAP://' . $host,
            ])
        );
        $this->assertTrue(AuthLDAP::testLDAPConnection($ldap->getID()));
        $this->checkLdapConnection($ldap->connect());
    }

    /**
     * Test get users
     *
     * @return void
     */
    #[RequiresPhpExtension('ldap')]
    public function testGetAllUsers()
    {
        $ldap = $this->ldap;
        $results = [];
        $limit = false;

        $users = AuthLDAP::getAllUsers(
            [
                'authldaps_id' => $ldap->getID(),
                'ldap_filter'  => AuthLDAP::buildLdapFilter($ldap),
                'mode'         => AuthLDAP::ACTION_IMPORT,
            ],
            $results,
            $limit
        );

        $this->assertCount(912, $users);
        $this->assertCount(0, $results);

        $_REQUEST['interface'] = AuthLDAP::SIMPLE_INTERFACE;
        $_REQUEST['criterias'] = ['login_field' => 'brazil2'];

        $users = AuthLDAP::getAllUsers(
            [
                'authldaps_id' => $ldap->getID(),
                'ldap_filter'  => AuthLDAP::buildLdapFilter($ldap),
                'mode'         => AuthLDAP::ACTION_IMPORT,
            ],
            $results,
            $limit
        );

        $this->assertCount(12, $users);
        $this->assertCount(0, $results);

        $_REQUEST['criterias'] = ['login_field' => 'remi'];

        $users = AuthLDAP::getAllUsers(
            [
                'authldaps_id' => $ldap->getID(),
                'ldap_filter'  => AuthLDAP::buildLdapFilter($ldap),
                'mode'         => AuthLDAP::ACTION_IMPORT,
            ],
            $results,
            $limit
        );

        $this->assertCount(1, $users);
        $this->assertCount(0, $results);
    }

    /**
     * Test get groups
     *
     * @return void
     */
    #[RequiresPhpExtension('ldap')]
    public function testGetAllGroups()
    {
        $ldap = $this->ldap;
        $limit = false;

        $groups = AuthLDAP::getAllGroups(
            $ldap->getID(),
            AuthLDAP::buildLdapFilter($ldap),
            '',
            0,
            $limit
        );

        $this->assertCount(912, $groups);

        /** TODO: filter search... I do not know how to do. */
    }

    /**
     * Test import user
     *
     * @return void
     */
    #[RequiresPhpExtension('ldap')]
    public function testLdapImportUserByServerId()
    {
        $ldap = $this->ldap;
        $results = [];
        $limit = false;

        //get user to import
        $_REQUEST['interface'] = AuthLDAP::SIMPLE_INTERFACE;
        $_REQUEST['criterias'] = ['login_field' => 'ecuador0'];

        $users = AuthLDAP::getAllUsers(
            [
                'authldaps_id' => $ldap->getID(),
                'ldap_filter'  => AuthLDAP::buildLdapFilter($ldap),
                'mode'         => AuthLDAP::ACTION_IMPORT,
            ],
            $results,
            $limit
        );

        $this->assertCount(1, $users);
        $this->assertCount(0, $results);

        $import = AuthLDAP::ldapImportUserByServerId(
            [
                'method' => AuthLDAP::IDENTIFIER_LOGIN,
                'value'  => 'ecuador0',
            ],
            AuthLDAP::ACTION_IMPORT,
            $ldap->getID(),
            true
        );
        $this->assertCount(2, $import);
        $this->assertSame(AuthLDAP::USER_IMPORTED, $import['action']);
        $this->assertGreaterThan(0, $import['id']);

        //check created user
        $user = new \User();
        $this->assertTrue($user->getFromDB($import['id']));

        $this->assertSame('ecuador0', $user->fields['name']);
        $this->assertSame('034596780', $user->fields['phone']);
        $this->assertSame('dor0', $user->fields['realname']);
        $this->assertSame('ecua0', $user->fields['firstname']);
        $this->assertSame('es_ES', $user->fields['language']);
        $this->assertEquals(true, $user->fields['is_active']);
        $this->assertSame($ldap->getID(), $user->fields['auths_id']);
        $this->assertSame(\Auth::LDAP, $user->fields['authtype']);
        $this->assertSame('uid=ecuador0,ou=people,ou=R&D,dc=glpi,dc=org', $user->fields['user_dn']);

        $this->assertGreaterThan(0, (int) $user->fields['usertitles_id']);
        $this->assertGreaterThan(0, (int) $user->fields['usercategories_id']);
    }

    /**
     * Test get groups
     *
     * @return void
     */
    #[RequiresPhpExtension('ldap')]
    public function testGetGroupCNByDn()
    {
        $ldap = $this->ldap;

        $connection = $ldap->connect();
        $this->checkLdapConnection($connection);

        // Invalid group
        $cn = AuthLDAP::getGroupCNByDn($connection, 'ou=not,ou=exists,dc=glpi,dc=org');
        $this->assertFalse($cn);

        // Valid group with no special chars
        $cn = AuthLDAP::getGroupCNByDn($connection, 'cn=glpi2-group1,ou=groups,ou=usa,ou=ldap2,dc=glpi,dc=org');
        $this->assertSame('glpi2-group1', $cn);

        // OU with special `#` char protected by a `\`
        $cn = AuthLDAP::getGroupCNByDn($connection, 'cn=glpi2-group2,ou=groups,ou=\#1-test,ou=ldap2,dc=glpi,dc=org');
        $this->assertSame('glpi2-group2', $cn);

        // OU with special `#` char escaped to `\23`
        $cn = AuthLDAP::getGroupCNByDn($connection, 'cn=glpi2-group2,ou=groups,ou=\231-test,ou=ldap2,dc=glpi,dc=org');
        $this->assertSame('glpi2-group2', $cn);
    }

    /**
     * Test get user by dn
     *
     * @return void
     */
    #[RequiresPhpExtension('ldap')]
    public function testGetUserByDn()
    {
        $ldap = $this->ldap;

        $user = AuthLDAP::getUserByDn(
            $ldap->connect(),
            'uid=walid,ou=people,ou=france,ou=europe,ou=ldap1, dc=glpi,dc=org',
            []
        );

        $this->assertCount(12, $user);
        $this->assertArrayHasKey('userpassword', $user);
        $this->assertArrayHasKey('uid', $user);
        $this->assertArrayHasKey('objectclass', $user);
        $this->assertArrayHasKey('sn', $user);
    }

    public static function ldapGroupUserProvider(): iterable
    {
        yield [
            'group_dn'            => 'cn=glpi2-group1,ou=groups,ou=usa,ou=ldap2,dc=glpi,dc=org',
            'user_uid'            => 'remi',
            'expected_group_dn'   => 'cn=glpi2-group1,ou=groups,ou=usa,ou=ldap2,dc=glpi,dc=org',
            'expected_group_name' => 'glpi2-group1',
        ];

        // OU with special `#` char protected by a `\`
        yield [
            'group_dn'            => 'cn=glpi2-group2,ou=groups,ou=\#1-test,ou=ldap2,dc=glpi,dc=org',
            'user_uid'            => 'specialchar1',
            // openladap replaces `\#` by `\23` (23 is the ascii code for #)
            'expected_group_dn'   => 'cn=glpi2-group2,ou=groups,ou=\231-test,ou=ldap2,dc=glpi,dc=org',
            'expected_group_name' => 'glpi2-group2',
        ];

        // OU with special `#` char escaped to `\23`
        yield [
            'group_dn'            => 'cn=glpi2-group2,ou=groups,ou=\231-test,ou=ldap2,dc=glpi,dc=org',
            'user_uid'            => 'specialchar2',
            'expected_group_dn'   => 'cn=glpi2-group2,ou=groups,ou=\231-test,ou=ldap2,dc=glpi,dc=org',
            'expected_group_name' => 'glpi2-group2',
        ];
    }

    /**
     * Test get group
     *
     * @return void
     */
    #[RequiresPhpExtension('ldap')]
    #[DataProvider('ldapGroupUserProvider')]
    public function testGetGroupByDn(string $group_dn, string $user_uid, string $expected_group_dn, string $expected_group_name)
    {
        $ldap = $this->ldap;

        $group = AuthLDAP::getGroupByDn(
            $ldap->connect(),
            $group_dn
        );

        $this->assertSame(
            [
                'cn'     => [
                    'count' => 1,
                    0       => $expected_group_name,
                ],
                0        => 'cn',
                'count'  => 1,
                'dn'     => $expected_group_dn,
            ],
            $group
        );
    }

    /**
     * Test import group
     *
     * @return void
     */
    #[RequiresPhpExtension('ldap')]
    #[DataProvider('ldapGroupUserProvider')]
    public function testLdapImportGroup(string $group_dn, string $user_uid, string $expected_group_dn, string $expected_group_name)
    {
        $ldap = $this->ldap;

        // Valid group with no special chars
        $import = AuthLDAP::ldapImportGroup(
            $group_dn,
            [
                'authldaps_id' => $ldap->getID(),
                'entities_id'  => 0,
                'is_recursive' => true,
                'type'         => 'groups',
            ]
        );

        $this->assertGreaterThan(0, $import);

        //check group
        $group = new Group();
        $this->assertTrue($group->getFromDB($import));

        $this->assertSame($expected_group_name, $group->fields['name']);
        $this->assertSame($expected_group_name, $group->fields['completename']);
        $this->assertSame($expected_group_dn, $group->fields['ldap_group_dn']);
    }

    /**
     * Test import group and user
     *
     * @return void
     */
    #[RequiresPhpExtension('ldap')]
    #[DataProvider('ldapGroupUserProvider')]
    public function testLdapImportUserGroup(string $group_dn, string $user_uid, string $expected_group_dn, string $expected_group_name)
    {
        $ldap = $this->ldap;

        // Import group, unless it exists
        $group = new Group();
        if (!$group->getFromDBByCrit(['name' => $expected_group_name])) {
            $import = AuthLDAP::ldapImportGroup(
                $group_dn,
                [
                    'authldaps_id' => $ldap->getID(),
                    'entities_id'  => 0,
                    'is_recursive' => true,
                    'type'         => 'groups',
                ]
            );

            $this->assertGreaterThan(0, $import);
            $this->assertTrue($group->getFromDB($import));
        }

        $import = AuthLDAP::ldapImportUserByServerId(
            [
                'method' => AuthLDAP::IDENTIFIER_LOGIN,
                'value'  => $user_uid,
            ],
            AuthLDAP::ACTION_IMPORT,
            $ldap->getID(),
            true
        );
        $this->assertCount(2, $import);
        $this->assertSame(AuthLDAP::USER_IMPORTED, $import['action']);
        $this->assertGreaterThan(0, (int) $import['id']);

        //check created user
        $user = new \User();
        $this->assertTrue($user->getFromDB($import['id']));

        $usergroups = Group_User::getUserGroups($user->getID());
        $this->assertEquals($group->getID(), $usergroups[0]['id']);
        $this->assertSame($expected_group_name, $usergroups[0]['name']);
    }


    /**
     * Test sync user
     *
     * @return void
     */
    #[RequiresPhpExtension('ldap')]
    public function testSyncUser()
    {
        $ldap = $this->ldap;
        $this->assertFalse($ldap->isSyncFieldEnabled());

        $import = AuthLDAP::ldapImportUserByServerId(
            [
                'method' => AuthLDAP::IDENTIFIER_LOGIN,
                'value'  => 'ecuador0',
            ],
            AuthLDAP::ACTION_IMPORT,
            $ldap->getID(),
            true
        );
        $this->assertCount(2, $import);
        $this->assertSame(AuthLDAP::USER_IMPORTED, $import['action']);
        $this->assertGreaterThan(0, $import['id']);

        //check created user
        $user = new \User();
        $this->assertTrue($user->getFromDB($import['id']));
        $this->assertSame('ecuador0', $user->fields['name']);
        $this->assertSame('034596780', $user->fields['phone']);
        $this->assertSame('uid=ecuador0,ou=people,ou=R&D,dc=glpi,dc=org', $user->fields['user_dn']);

        // update the user in ldap (change phone number)
        $this->assertTrue(
            ldap_modify(
                $ldap->connect(),
                'uid=ecuador0,ou=people,ou=R&D,dc=glpi,dc=org',
                ['telephoneNumber' => '+33101010101']
            )
        );

        $synchro = $ldap->forceOneUserSynchronization($user);

        //reset entry before any test can fail
        $this->assertTrue(
            ldap_modify(
                $ldap->connect(),
                'uid=ecuador0,ou=people,ou=R&D,dc=glpi,dc=org',
                ['telephoneNumber' => '034596780']
            )
        );

        $this->assertCount(2, $synchro);
        $this->assertSame(AuthLDAP::USER_SYNCHRONIZED, $synchro['action']);
        $this->assertSame($user->getID(), $synchro['id']);

        // check phone number has been synced
        $this->assertTrue($user->getFromDB($user->getID()));
        $this->assertSame('ecuador0', $user->fields['name']);
        $this->assertSame('+33101010101', $user->fields['phone']);
        $this->assertSame('uid=ecuador0,ou=people,ou=R&D,dc=glpi,dc=org', $user->fields['user_dn']);

        // update sync field of user
        $this->assertTrue(
            $ldap->update([
                'id'           => $ldap->getID(),
                'sync_field'   => 'employeenumber',
            ])
        );

        $this->assertTrue($ldap->isSyncFieldEnabled());

        // add sync field attribute in ldap user
        $this->assertTrue(
            ldap_mod_add(
                $ldap->connect(),
                'uid=ecuador0,ou=people,ou=R&D,dc=glpi,dc=org',
                ['employeeNumber' => '42']
            )
        );

        $synchro = $ldap->forceOneUserSynchronization($user);

        $this->assertTrue($user->getFromDB($user->getID()));
        $this->assertCount(2, $synchro);
        $this->assertSame(AuthLDAP::USER_SYNCHRONIZED, $synchro['action']);
        $this->assertEquals($user->getID(), $synchro['id']);

        $this->assertEquals(42, $user->fields['sync_field']);

        // rename the user (uid change, syncfield keep its value)
        $this->assertTrue(
            ldap_rename(
                $ldap->connect(),
                'uid=ecuador0,ou=people,ou=R&D,dc=glpi,dc=org',
                'uid=testecuador',
                '',
                true
            )
        );

        $synchro = $ldap->forceOneUserSynchronization($user);

        //reset entry before any test can fail
        $this->assertTrue(
            ldap_rename(
                $ldap->connect(),
                'uid=testecuador,ou=people,ou=R&D,dc=glpi,dc=org',
                'uid=ecuador0',
                '',
                true
            )
        );

        $this->assertTrue(
            ldap_mod_del(
                $ldap->connect(),
                'uid=ecuador0,ou=people,ou=R&D,dc=glpi,dc=org',
                ['employeeNumber' => 42]
            )
        );

        // check the `name` field (corresponding to the uid) has been updated for the user
        $this->assertTrue($user->getFromDB($user->getID()));
        $this->assertCount(2, $synchro);
        $this->assertSame(AuthLDAP::USER_SYNCHRONIZED, $synchro['action']);
        $this->assertEquals($user->getID(), $synchro['id']);

        $this->assertEquals(42, $user->fields['sync_field']);
        $this->assertSame('testecuador', $user->fields['name']);

        // ## test we can sync the user when the syncfield is different but after we reset it manually
        $this->assertTrue(
            ldap_mod_add(
                $ldap->connect(),
                'uid=ecuador0,ou=people,ou=R&D,dc=glpi,dc=org',
                ['employeeNumber' => '42']
            )
        );

        $synchro = $ldap->forceOneUserSynchronization($user);

        $this->assertTrue($user->getFromDB($user->getID()));
        $this->assertCount(2, $synchro);
        $this->assertSame(AuthLDAP::USER_SYNCHRONIZED, $synchro['action']);
        $this->assertEquals($user->getID(), $synchro['id']);

        $this->assertEquals(42, $user->fields['sync_field']);

        $this->assertTrue(
            ldap_mod_replace(
                $ldap->connect(),
                'uid=ecuador0,ou=people,ou=R&D,dc=glpi,dc=org',
                ['employeeNumber' => '43']
            )
        );

        // do a simple sync
        $synchro = $ldap->forceOneUserSynchronization($user);
        $this->assertTrue($user->getFromDB($user->getID()));

        // the sync field should have been kept
        // but the user should now be in non synchronized state
        $this->assertEquals(42, $user->fields['sync_field']);
        $this->assertCount(2, $synchro);
        $this->assertSame(AuthLDAP::USER_DELETED_LDAP, $synchro['action']);
        $this->assertEquals($user->getID(), $synchro['id']);

        // sync after emptying the sync field
        $synchro2 = $ldap->forceOneUserSynchronization($user, true);
        $this->assertTrue($user->getFromDB($user->getID()));

        // the sync field should have changed
        // and the user is now synchronized again
        $this->assertEquals(43, $user->fields['sync_field']);
        $this->assertCount(2, $synchro2);
        $this->assertSame(AuthLDAP::USER_SYNCHRONIZED, $synchro2['action']);
        $this->assertEquals($user->getID(), $synchro2['id']);

        // reset attribute
        $this->assertTrue(
            ldap_mod_del(
                $ldap->connect(),
                'uid=ecuador0,ou=people,ou=R&D,dc=glpi,dc=org',
                ['employeeNumber' => 43]
            )
        );

        global $DB;
        $DB->update(
            'glpi_authldaps',
            ['sync_field' => null],
            ['id' => $ldap->getID()]
        );
    }

    /**
     * Test ldap authentication
     *
     * @return void
     */
    #[RequiresPhpExtension('ldap')]
    public function testLdapAuth()
    {
        //try to log in from a user that does not exist yet
        $auth = $this->realLogin('brazil6', 'password', false);

        $user = new \User();
        $user->getFromDBbyName('brazil6');
        $this->assertSame('brazil6', $user->fields['name']);
        $this->assertSame('uid=brazil6,ou=people,ou=R&D,dc=glpi,dc=org', $user->fields['user_dn']);
        $this->assertFalse($auth->user_present);
        $this->assertFalse($auth->user_dn);
        $this->checkLdapConnection($auth->ldap_connection);

        //import user; then try to log in
        $ldap = $this->ldap;
        $this->assertTrue(
            $ldap->update([
                'id'           => $ldap->getID(),
                'sync_field'   => 'employeenumber',
            ])
        );
        $this->assertTrue($ldap->isSyncFieldEnabled());

        //try to import a user from its sync_field
        $import = AuthLDAP::ldapImportUserByServerId(
            [
                'method' => AuthLDAP::IDENTIFIER_LOGIN,
                'value'  => '10',
            ],
            AuthLDAP::ACTION_IMPORT,
            $ldap->getID(),
            true
        );
        $this->assertCount(2, $import);
        $this->assertSame(AuthLDAP::USER_IMPORTED, $import['action']);
        $this->assertGreaterThan(0, $import['id']);

        //check created user
        $user = new \User();
        $this->assertTrue($user->getFromDB($import['id']));
        $this->assertSame('brazil7', $user->fields['name']);
        $this->assertSame('uid=brazil7,ou=people,ou=R&D,dc=glpi,dc=org', $user->fields['user_dn']);

        $auth = $this->realLogin('brazil7', 'password', false, true);

        $this->assertTrue($auth->user_present);
        $this->assertSame($user->fields['user_dn'], $auth->user_dn);
        $this->checkLdapConnection($auth->ldap_connection);

        //change user login, and try again. Existing user should be updated.
        $this->assertTrue(
            ldap_rename(
                $ldap->connect(),
                'uid=brazil7,ou=people,ou=R&D,dc=glpi,dc=org',
                'uid=brazil7test',
                '',
                true
            )
        );

        $this->realLogin('brazil7', 'password', false, false);
        $auth = $this->realLogin('brazil7test', 'password', false);

        //reset entry before any test can fail
        $this->assertTrue(
            ldap_rename(
                $ldap->connect(),
                'uid=brazil7test,ou=people,ou=R&D,dc=glpi,dc=org',
                'uid=brazil7',
                '',
                true
            )
        );

        $this->assertTrue($user->getFromDB($user->getID()));
        $this->assertSame('brazil7test', $user->fields['name']);
        $this->assertSame('uid=brazil7test,ou=people,ou=R&D,dc=glpi,dc=org', $user->fields['user_dn']);

        $this->assertTrue($auth->user_present);
        $this->checkLdapConnection($auth->ldap_connection);

        //ensure duplicated DN on different authldaps_id does not prevent login
        $this->assertTrue(
            $user->getFromDBByCrit(['user_dn' => 'uid=brazil6,ou=people,ou=R&D,dc=glpi,dc=org'])
        );

        $dup = $user->fields;
        unset($dup['id']);
        unset($dup['date_creation']);
        unset($dup['date_mod']);
        unset($dup['user_dn_hash']);
        $aid = $dup['auths_id'];
        $dup['auths_id'] = $aid + 1;

        $this->assertGreaterThan(
            0,
            (int) $user->add($dup)
        );

        $auth = $this->realLogin('brazil6', 'password', false);
        $this->assertSame($aid, $auth->user->fields['auths_id']);
        $this->assertSame('brazil6', $auth->user->fields['name']);
        $this->assertSame('uid=brazil6,ou=people,ou=R&D,dc=glpi,dc=org', $auth->user->fields['user_dn']);

        global $DB;
        $DB->update(
            'glpi_authldaps',
            ['sync_field' => null],
            ['id' => $ldap->getID()]
        );
    }

    /**
     * Test LDAP authentication when specify the auth source (local, LDAP...)
     *
     * @return void
     */
    #[RequiresPhpExtension('ldap')]
    public function testLdapAuthSpecifyAuth()
    {
        $_SESSION['glpicronuserrunning'] = "cron_phpunit";
        // Add a local account with same name than a LDAP user ('brazil8')
        $input = [
            'name'         => 'brazil8',
            'password'     => 'passwordlocal',
            'password2'    => 'passwordlocal',
            '_profiles_id' => 1, // add manual right (is_dynamic = 0)
            'entities_id'  => 0,
        ];
        $user = new \User();
        $user_id = $user->add($input);
        $this->assertGreaterThan(0, $user_id);

        // check user has at least one profile
        $pus = Profile_User::getForUser($user_id);
        $this->assertCount(1, $pus);
        $pu = array_shift($pus);
        $this->assertEquals(1, $pu['profiles_id']);
        $this->assertEquals(0, $pu['entities_id']);
        $this->assertEquals(0, $pu['is_recursive']);
        $this->assertEquals(0, $pu['is_dynamic']);

        // first, login with ldap mode
        $auth = new \Auth();
        $this->assertTrue($auth->login('brazil8', 'password', false, false, 'ldap-' . $this->ldap->getID()));
        $user_ldap_id = $auth->user->fields['id'];
        $this->assertNotEquals($user_id, $user_ldap_id);

        $auth = new \Auth();
        $this->assertFalse($auth->login('brazil8', 'passwordlocal', false, false, 'ldap-' . $this->ldap->getID()));

        // Then, login with local GLPI DB mode
        $auth = new \Auth();
        $this->assertFalse($auth->login('brazil8', 'password', false, false, 'local'));

        $auth = new \Auth();
        $this->assertTrue($auth->login('brazil8', 'passwordlocal', false, false, 'local'));
        $this->assertNotEquals($user_ldap_id, $auth->user->fields['id']);
    }

    /**
     * Test get users
     *
     * @return void
     */
    #[RequiresPhpExtension('ldap')]
    public function testGetUsers()
    {
        $ldap = $this->ldap;
        $results = [];
        $limit = false;

        $users = AuthLDAP::getUsers(
            [
                'authldaps_id' => $ldap->getID(),
                'ldap_filter'  => AuthLDAP::buildLdapFilter($ldap),
                'mode'         => AuthLDAP::ACTION_IMPORT,
            ],
            $results,
            $limit
        );

        $this->assertCount(912, $users);
        $this->assertCount(0, $results);

        $_REQUEST['interface'] = AuthLDAP::SIMPLE_INTERFACE;
        $_REQUEST['criterias'] = ['login_field' => 'brazil2'];
        $_REQUEST['mode'] = 0;

        $users = AuthLDAP::getUsers(
            [
                'authldaps_id' => $ldap->getID(),
                'ldap_filter'  => AuthLDAP::buildLdapFilter($ldap),
                'mode'         => AuthLDAP::ACTION_IMPORT,
            ],
            $results,
            $limit
        );

        $this->assertCount(12, $users);
        $this->assertCount(0, $results);

        $_REQUEST['criterias'] = ['login_field' => 'remi'];

        $users = AuthLDAP::getUsers(
            [
                'authldaps_id' => $ldap->getID(),
                'ldap_filter'  => AuthLDAP::buildLdapFilter($ldap),
                'mode'         => AuthLDAP::ACTION_IMPORT,
            ],
            $results,
            $limit
        );

        $this->assertCount(1, $users);
        $this->assertCount(0, $results);

        //hardcode tsmap
        $users[0]['stamp'] = 1503470443;
        $this->assertSame(
            [
                'link'      => 'remi',
                'stamp'     => 1503470443,
                'date_sync' => '-----',
                'uid'       => 'remi',

            ],
            $users[0]
        );
    }

    /**
     * Data provider for testRemovedUser
     *
     * @return iterable
     */
    public static function removedUserProvider(): iterable
    {
        $user_options = [
            AuthLDAP::DELETED_USER_ACTION_USER_DO_NOTHING,
            AuthLDAP::DELETED_USER_ACTION_USER_DISABLE,
            AuthLDAP::DELETED_USER_ACTION_USER_MOVE_TO_TRASHBIN,
        ];

        $groups_options = [
            AuthLDAP::DELETED_USER_ACTION_GROUPS_DO_NOTHING,
            AuthLDAP::DELETED_USER_ACTION_GROUPS_DELETE_DYNAMIC,
            AuthLDAP::DELETED_USER_ACTION_GROUPS_DELETE_ALL,
        ];

        $authorizations_options = [
            AuthLDAP::DELETED_USER_ACTION_AUTHORIZATIONS_DO_NOTHING,
            AuthLDAP::DELETED_USER_ACTION_AUTHORIZATIONS_DELETE_DYNAMIC,
            AuthLDAP::DELETED_USER_ACTION_AUTHORIZATIONS_DELETE_ALL,
        ];

        // Yield all possible combinations
        foreach ($user_options as $user_option) {
            foreach ($groups_options as $groups_option) {
                foreach ($authorizations_options as $authorizations_option) {
                    yield[$user_option, $groups_option, $authorizations_option];
                }
            }
        }
    }

    /**
     * Test expected behaviors when a user is deleted from ldap
     *
     * @param int $user_option_value
     * @param int $groups_option_value
     * @param int $authorizations_option_value
     *
     * @return void
     */
    #[RequiresPhpExtension('ldap')]
    #[DataProvider('removedUserProvider')]
    public function testRemovedUser(
        int $user_option_value,
        int $groups_option_value,
        int $authorizations_option_value,
    ): void {
        global $CFG_GLPI;

        $ldap = $this->ldap;

        // Set config
        $CFG_GLPI['user_deleted_ldap_user'] = $user_option_value;
        $CFG_GLPI['user_deleted_ldap_groups'] = $groups_option_value;
        $CFG_GLPI['user_deleted_ldap_authorizations'] = $authorizations_option_value;

        // Unique user for each tests
        $rand = mt_rand();
        $uid = "toremovetest$rand";

        // Add a new user in directory
        $this->assertTrue(
            ldap_add(
                $ldap->connect(),
                "uid=$uid,ou=people,ou=R&D,dc=glpi,dc=org",
                [
                    'uid'          => $uid,
                    'sn'           => 'A SN',
                    'cn'           => 'A CN',
                    'userpassword' => 'password',
                    'objectClass'  => [
                        'top',
                        'inetOrgPerson',
                    ],
                ]
            )
        );

        // Import the user
        $import = AuthLDAP::ldapImportUserByServerId(
            [
                'method' => AuthLDAP::IDENTIFIER_LOGIN,
                'value'  => $uid,
            ],
            AuthLDAP::ACTION_IMPORT,
            $ldap->getID(),
            true
        );
        $this->assertCount(2, $import);
        $this->assertSame(AuthLDAP::USER_IMPORTED, $import['action']);
        $this->assertGreaterThan(0, $import['id']);

        // Check created user
        $user = new \User();
        $this->assertTrue($user->getFromDB($import['id']));

        // Add groups
        $this->createItems("Group", [
            ["name" => "Dyn group 1 $rand"],
            ["name" => "Dyn group 2 $rand"],
            ["name" => "Group 1 $rand"],
            ["name" => "Group 2 $rand"],
            ["name" => "Group 3 $rand"],
        ]);
        $dyn_group_1 = getItemByTypeName("Group", "Dyn group 1 $rand", true);
        $dyn_group_2 = getItemByTypeName("Group", "Dyn group 2 $rand", true);
        $group_1 = getItemByTypeName("Group", "Group 1 $rand", true);
        $group_2 = getItemByTypeName("Group", "Group 2 $rand", true);
        $group_3 = getItemByTypeName("Group", "Group 3 $rand", true);
        $this->createItems("Group_User", [
            ['users_id' => $import['id'], 'groups_id' => $dyn_group_1, "is_dynamic" => true],
            ['users_id' => $import['id'], 'groups_id' => $dyn_group_2, "is_dynamic" => true],
            ['users_id' => $import['id'], 'groups_id' => $group_1],
            ['users_id' => $import['id'], 'groups_id' => $group_2],
            ['users_id' => $import['id'], 'groups_id' => $group_3],
        ]);

        // Check groups have been assigned correctly
        $gu = new Group_User();
        $this->assertCount(5, $gu->find(['users_id' => $import['id']]));
        $this->assertCount(2, $gu->find(['users_id' => $import['id'], "is_dynamic" => true]));
        $this->assertCount(3, $gu->find(['users_id' => $import['id'], "is_dynamic" => false]));

        // Create profiles
        $this->createItems("Profile", [
            ["name" => "Dyn profile 1 $rand"],
            ["name" => "Profile 1 $rand"],
            ["name" => "Profile 2 $rand"],
        ]);
        $dyn_profile_1 = getItemByTypeName("Profile", "Dyn profile 1 $rand", true);
        $profile_1 = getItemByTypeName("Profile", "Profile 1 $rand", true);
        $profile_2 = getItemByTypeName("Profile", "Profile 2 $rand", true);
        $this->createItems("Profile_User", [
            ['entities_id' => 0, 'users_id' => $import['id'], 'profiles_id' => $dyn_profile_1, "is_dynamic" => true],
            ['entities_id' => 0, 'users_id' => $import['id'], 'profiles_id' => $profile_1],
            ['entities_id' => 0, 'users_id' => $import['id'], 'profiles_id' => $profile_2],
        ]);
        // + 1 dyn profile that was already attributed on creation

        // Check profiles have been assigned correctly
        $pu = new Profile_User();
        $this->assertCount(4, $pu->find(['users_id' => $import['id']]));
        $this->assertCount(2, $pu->find(['users_id' => $import['id'], "is_dynamic" => true]));
        $this->assertCount(2, $pu->find(['users_id' => $import['id'], "is_dynamic" => false]));

        // Drop test user
        $this->assertTrue(
            ldap_delete(
                $ldap->connect(),
                "uid=$uid,ou=people,ou=R&D,dc=glpi,dc=org"
            )
        );

        $synchro = $ldap->forceOneUserSynchronization($user);
        $this->assertCount(2, $synchro);
        $this->assertSame(AuthLDAP::USER_DELETED_LDAP, $synchro['action']);
        $this->assertEquals($import['id'], $synchro['id']);

        // Refresh user
        $user = new \User();
        $user->getFromDB($import['id']);

        // Check expected behavior according to user config
        switch ($user_option_value) {
            case AuthLDAP::DELETED_USER_ACTION_USER_DO_NOTHING:
                $this->assertTrue((bool) $user->fields['is_active']);
                $this->assertFalse((bool) $user->fields['is_deleted']);
                break;

            case AuthLDAP::DELETED_USER_ACTION_USER_DISABLE:
                $this->assertFalse((bool) $user->fields['is_active']);
                $this->assertFalse((bool) $user->fields['is_deleted']);
                break;

            case AuthLDAP::DELETED_USER_ACTION_USER_MOVE_TO_TRASHBIN:
                $this->assertTrue((bool) $user->fields['is_active']);
                $this->assertTrue((bool) $user->fields['is_deleted']);
                break;
        }

        // Check expected behavior according to groups config
        switch ($groups_option_value) {
            case AuthLDAP::DELETED_USER_ACTION_GROUPS_DO_NOTHING:
                $this->assertCount(5, $gu->find(['users_id' => $import['id']]));
                $this->assertCount(2, $gu->find(['users_id' => $import['id'], "is_dynamic" => true]));
                $this->assertCount(3, $gu->find(['users_id' => $import['id'], "is_dynamic" => false]));
                break;

            case AuthLDAP::DELETED_USER_ACTION_GROUPS_DELETE_DYNAMIC:
                $this->assertCount(3, $gu->find(['users_id' => $import['id']]));
                $this->assertCount(0, $gu->find(['users_id' => $import['id'], "is_dynamic" => true]));
                $this->assertCount(3, $gu->find(['users_id' => $import['id'], "is_dynamic" => false]));
                break;

            case AuthLDAP::DELETED_USER_ACTION_GROUPS_DELETE_ALL:
                $this->assertCount(0, $gu->find(['users_id' => $import['id']]));
                break;
        }

        // Check expected behavior according to authorizations config
        switch ($authorizations_option_value) {
            case AuthLDAP::DELETED_USER_ACTION_AUTHORIZATIONS_DO_NOTHING:
                $this->assertCount(4, $pu->find(['users_id' => $import['id']]));
                $this->assertCount(2, $pu->find(['users_id' => $import['id'], "is_dynamic" => true]));
                $this->assertCount(2, $pu->find(['users_id' => $import['id'], "is_dynamic" => false]));
                break;

            case AuthLDAP::DELETED_USER_ACTION_AUTHORIZATIONS_DELETE_DYNAMIC:
                $this->assertCount(2, $pu->find(['users_id' => $import['id']]));
                $this->assertCount(0, $pu->find(['users_id' => $import['id'], "is_dynamic" => true]));
                $this->assertCount(2, $pu->find(['users_id' => $import['id'], "is_dynamic" => false]));
                break;

            case AuthLDAP::DELETED_USER_ACTION_AUTHORIZATIONS_DELETE_ALL:
                $this->assertCount(0, $pu->find(['users_id' => $import['id']]));
                break;
        }
    }

    public function testUnreachable(): void
    {
        $ldap = $this->ldap;

        $this->assertTrue(
            ldap_add(
                $ldap->connect(),
                'uid=testunreachable,ou=people,ou=R&D,dc=glpi,dc=org',
                [
                    'uid'          => 'testunreachable',
                    'sn'           => 'A SN',
                    'cn'           => 'A CN',
                    'userpassword' => 'password',
                    'objectClass'  => [
                        'top',
                        'inetOrgPerson',
                    ],
                ]
            )
        );

        $import = AuthLDAP::ldapImportUserByServerId(
            [
                'method' => AuthLDAP::IDENTIFIER_LOGIN,
                'value'  => 'testunreachable',
            ],
            AuthLDAP::ACTION_IMPORT,
            $ldap->getID(),
            true
        );
        $this->assertCount(2, $import);
        $this->assertSame(AuthLDAP::USER_IMPORTED, $import['action']);
        $this->assertGreaterThan(0, $import['id']);

        // Check created user
        $user = new \User();
        $this->assertTrue($user->getFromDB($import['id']));

        // Check sync from a non-reachable directory
        $host = $ldap->fields['host'];
        $port = $ldap->fields['port'];
        $this->assertTrue(
            $ldap->update([
                'id'     => $ldap->getID(),
                'host'   => 'server-does-not-exists.org',
                'port'   => '1234',
            ])
        );
        $ldap::$conn_cache = [];

        $synchro = $ldap->forceOneUserSynchronization($user);
        $this->assertFalse($synchro);
        $this->hasPhpLogRecordThatContains(
            "Unable to bind to LDAP server `server-does-not-exists.org:1234` with RDN `cn=Manager,dc=glpi,dc=org`\nerror: Can't contact LDAP server (-1)",
            LogLevel::WARNING
        );

        // Check that user still exists
        $uid = $import['id'];
        $this->assertTrue($user->getFromDB($uid));
        $this->assertFalse((bool) $user->fields['is_deleted']);
    }

    /**
     * Test restoring users from LDAP
     *
     * @return void
     */
    #[RequiresPhpExtension('ldap')]
    public function testRestoredUser()
    {
        global $CFG_GLPI;

        $ldap = $this->ldap;

        // add a new user in directory
        $this->assertTrue(
            ldap_add(
                $ldap->connect(),
                'uid=torestoretest,ou=people,ou=R&D,dc=glpi,dc=org',
                [
                    'uid'          => 'torestoretest',
                    'sn'           => 'A SN',
                    'cn'           => 'A CN',
                    'userpassword' => 'password',
                    'objectClass'  => [
                        'top',
                        'inetOrgPerson',
                    ],
                ]
            )
        );

        // import the user
        $import = AuthLDAP::ldapImportUserByServerId(
            [
                'method' => AuthLDAP::IDENTIFIER_LOGIN,
                'value'  => 'torestoretest',
            ],
            AuthLDAP::ACTION_IMPORT,
            $ldap->getID(),
            true
        );
        $this->assertCount(2, $import);
        $this->assertSame(AuthLDAP::USER_IMPORTED, $import['action']);
        $this->assertGreaterThan(0, $import['id']);

        // check created user
        $user = new \User();
        $this->assertTrue($user->getFromDB($import['id']));
        $this->assertFalse((bool) $user->fields['is_deleted']);
        $this->assertFalse((bool) $user->fields['is_deleted_ldap']);

        // delete the user in LDAP
        $this->assertTrue(
            ldap_delete(
                $ldap->connect(),
                'uid=torestoretest,ou=people,ou=R&D,dc=glpi,dc=org'
            )
        );

        $user_deleted_ldap_original = $CFG_GLPI['user_deleted_ldap_user'] ?? AuthLDAP::DELETED_USER_ACTION_USER_DO_NOTHING;
        // put deleted LDAP users in trashbin
        $CFG_GLPI['user_deleted_ldap_user'] = AuthLDAP::DELETED_USER_ACTION_USER_MOVE_TO_TRASHBIN;
        $synchro = $ldap->forceOneUserSynchronization($user);
        $CFG_GLPI['user_deleted_ldap_user'] = $user_deleted_ldap_original;
        $this->assertCount(2, $synchro);
        $this->assertSame(AuthLDAP::USER_DELETED_LDAP, $synchro['action']);
        $this->assertSame($import['id'], $synchro['id']);

        // reload user from DB
        $this->assertTrue($user->getFromDB($import['id']));
        $this->assertTrue((bool) $user->fields['is_deleted']);
        $this->assertTrue((bool) $user->fields['is_deleted_ldap']);

        // manually re-add the user in LDAP to simulate a restore
        $this->assertTrue(
            ldap_add(
                $ldap->connect(),
                'uid=torestoretest,ou=people,ou=R&D,dc=glpi,dc=org',
                [
                    'uid'          => 'torestoretest',
                    'sn'           => 'A SN',
                    'cn'           => 'A CN',
                    'userpassword' => 'password',
                    'objectClass'  => [
                        'top',
                        'inetOrgPerson',
                    ],
                ]
            )
        );

        $user_restored_ldap_original = $CFG_GLPI['user_restored_ldap'] ?? 0;
        $CFG_GLPI['user_restored_ldap'] = 1;
        $synchro = $ldap->forceOneUserSynchronization($user);
        $CFG_GLPI['user_restored_ldap'] = $user_restored_ldap_original;
        $this->assertCount(2, $synchro);
        $this->assertSame(AuthLDAP::USER_RESTORED_LDAP, $synchro['action']);
        $this->assertEquals($import['id'], $synchro['id']);

        // reload user from DB
        $this->assertTrue($user->getFromDB($import['id']));
        $this->assertFalse((bool) $user->fields['is_deleted']);
    }

    public static function ssoVariablesProvider()
    {
        global $DB;

        $iterator = $DB->request(['FROM' => \SsoVariable::getTable()]);
        $sso_vars = [];
        foreach ($iterator as $current) {
            $sso_vars[] = [$current['id'], $current['name']];
        }

        return $sso_vars;
    }

    #[DataProvider('ssoVariablesProvider')]
    public function testOtherAuth($sso_field_id, $sso_field_name)
    {
        global $CFG_GLPI;

        $config_values = \Config::getConfigurationValues('core', ['ssovariables_id']);
        \Config::setConfigurationValues('core', [
            'ssovariables_id' => $sso_field_id,
        ]);
        $CFG_GLPI['ssovariables_id'] = $sso_field_id;
        $_SERVER[$sso_field_name] = 'brazil6';

        unset($_SESSION['glpiname']);

        $auth = new \Auth();
        $this->assertTrue($auth->login("", ""));
        $this->assertEquals('brazil6', $_SESSION['glpiname']);

        //reset config
        \Config::setConfigurationValues('core', [
            'ssovariables_id' => $config_values['ssovariables_id'],
        ]);
    }

    #[RequiresPhpExtension('ldap')]
    public function testSyncLongDN()
    {
        $ldap = $this->ldap;

        $ldap_con = $ldap->connect();
        $this->assertTrue(
            ldap_add(
                $ldap_con,
                'ou=andyetanotheronetogetaveryhugednidentifier,ou=people,ou=R&D,dc=glpi,dc=org',
                [
                    'ou'          => 'andyetanotheronetogetaveryhugednidentifier',
                    'objectClass'  => [
                        'organizationalUnit',
                    ],
                ]
            ),
            ldap_error($ldap_con)
        );

        $this->assertTrue(
            ldap_add(
                $ldap_con,
                'ou=andyetanotherlongstring,ou=andyetanotheronetogetaveryhugednidentifier,ou=people,ou=R&D,dc=glpi,dc=org',
                [
                    'ou'          => 'andyetanotherlongstring',
                    'objectClass'  => [
                        'organizationalUnit',
                    ],
                ]
            ),
            ldap_error($ldap_con)
        );

        $this->assertTrue(
            ldap_add(
                $ldap_con,
                'ou=anotherlongstringtocheckforsynchronization,ou=andyetanotherlongstring,ou=andyetanotheronetogetaveryhugednidentifier,ou=people,ou=R&D,dc=glpi,dc=org',
                [
                    'ou'          => 'anotherlongstringtocheckforsynchronization',
                    'objectClass'  => [
                        'organizationalUnit',
                    ],
                ]
            ),
            ldap_error($ldap_con)
        );

        $this->assertTrue(
            ldap_add(
                $ldap_con,
                'ou=averylongstring,ou=anotherlongstringtocheckforsynchronization,ou=andyetanotherlongstring,ou=andyetanotheronetogetaveryhugednidentifier,ou=people,ou=R&D,dc=glpi,dc=org',
                [
                    'ou'          => 'averylongstring',
                    'objectClass'  => [
                        'organizationalUnit',
                    ],
                ]
            ),
            ldap_error($ldap_con)
        );

        //add a new user in directory
        $this->assertTrue(
            ldap_add(
                $ldap_con,
                'uid=verylongdn,ou=averylongstring,ou=anotherlongstringtocheckforsynchronization,ou=andyetanotherlongstring,ou=andyetanotheronetogetaveryhugednidentifier,ou=people,ou=R&D,dc=glpi,dc=org',
                [
                    'uid'          => 'verylongdn',
                    'sn'           => 'A SN',
                    'cn'           => 'A CN',
                    'userpassword' => 'password',
                    'objectClass'  => [
                        'top',
                        'inetOrgPerson',
                    ],
                ]
            ),
            ldap_error($ldap_con)
        );

        $import = AuthLDAP::ldapImportUserByServerId(
            [
                'method' => AuthLDAP::IDENTIFIER_LOGIN,
                'value'  => 'verylongdn',
            ],
            AuthLDAP::ACTION_IMPORT,
            $ldap->getID(),
            true
        );
        $this->assertCount(2, $import);
        $this->assertSame(AuthLDAP::USER_IMPORTED, $import['action']);
        $this->assertGreaterThan(0, $import['id']);

        //check created user
        $user = new \User();
        $this->assertTrue($user->getFromDB($import['id']));

        $this->assertSame('verylongdn', $user->fields['name']);
        $this->assertSame(
            'uid=verylongdn,ou=averylongstring,ou=anotherlongstringtocheckforsynchronization,ou=andyetanotherlongstring,ou=andyetanotheronetogetaveryhugednidentifier,ou=people,ou=R&D,dc=glpi,dc=org',
            $user->fields['user_dn']
        );

        $this->assertTrue(
            ldap_modify(
                $ldap->connect(),
                'uid=verylongdn,ou=averylongstring,ou=anotherlongstringtocheckforsynchronization,ou=andyetanotherlongstring,ou=andyetanotheronetogetaveryhugednidentifier,ou=people,ou=R&D,dc=glpi,dc=org',
                ['telephoneNumber' => '+33102020202']
            )
        );

        $synchro = $ldap->forceOneUserSynchronization($user);
        $this->assertCount(2, $synchro);
        $this->assertSame(AuthLDAP::USER_SYNCHRONIZED, $synchro['action']);
        $this->assertSame($user->getID(), $synchro['id']);

        $this->assertTrue($user->getFromDB($user->getID()));
        $this->assertSame('verylongdn', $user->fields['name']);
        $this->assertSame('+33102020202', $user->fields['phone']);
        $this->assertSame(
            'uid=verylongdn,ou=averylongstring,ou=anotherlongstringtocheckforsynchronization,ou=andyetanotherlongstring,ou=andyetanotheronetogetaveryhugednidentifier,ou=people,ou=R&D,dc=glpi,dc=org',
            $user->fields['user_dn']
        );

        //drop test user
        $this->assertTrue(
            ldap_delete(
                $ldap->connect(),
                'uid=verylongdn,ou=averylongstring,ou=anotherlongstringtocheckforsynchronization,ou=andyetanotherlongstring,ou=andyetanotheronetogetaveryhugednidentifier,ou=people,ou=R&D,dc=glpi,dc=org'
            )
        );
    }

    #[RequiresPhpExtension('ldap')]
    public function testSyncLongDNiCyrillic()
    {
        $ldap = $this->ldap;

        $ldap_con = $ldap->connect();

        $this->assertTrue(
            ldap_add(
                $ldap_con,
                'OU=Управление с очень очень длинным названием даже сложно запомнить насколько оно длинное и еле влезает в экран№123,ou=R&D,DC=glpi,DC=org',
                [
                    'ou'          => 'Управление с очень очень длинным названием даже сложно запомнить насколько оно длинное и еле влезает в экран№123',
                    'objectClass'  => [
                        'organizationalUnit',
                    ],
                ]
            ),
            ldap_error($ldap_con)
        );

        $this->assertTrue(
            ldap_add(
                $ldap_con,
                'OU=Отдел Тест,OU=Управление с очень очень длинным названием даже сложно запомнить насколько оно длинное и еле влезает в экран№123,ou=R&D,DC=glpi,DC=org',
                [
                    'ou'          => 'Отдел Тест',
                    'objectClass'  => [
                        'organizationalUnit',
                    ],
                ]
            ),
            ldap_error($ldap_con)
        );

        //add a new user in directory
        $this->assertTrue(
            ldap_add(
                $ldap_con,
                'uid=Тестов Тест Тестович,OU=Отдел Тест,OU=Управление с очень очень длинным названием даже сложно запомнить насколько оно длинное и еле влезает в экран№123,ou=R&D,DC=glpi,DC=org',
                [
                    'uid'          => 'Тестов Тест Тестович',
                    'sn'           => 'A SN',
                    'cn'           => 'A CN',
                    'userpassword' => 'password',
                    'objectClass'  => [
                        'top',
                        'inetOrgPerson',
                    ],
                ]
            ),
            ldap_error($ldap_con)
        );

        $import = AuthLDAP::ldapImportUserByServerId(
            [
                'method' => AuthLDAP::IDENTIFIER_LOGIN,
                'value'  => 'Тестов Тест Тестович',
            ],
            AuthLDAP::ACTION_IMPORT,
            $ldap->getID(),
            true
        );
        $this->assertCount(2, $import);
        $this->assertSame(AuthLDAP::USER_IMPORTED, $import['action']);
        $this->assertGreaterThan(0, $import['id']);

        //check created user
        $user = new \User();
        $this->assertTrue($user->getFromDB($import['id']));

        $this->assertSame('Тестов Тест Тестович', $user->fields['name']);
        $this->assertSame(
            'uid=Тестов Тест Тестович,ou=Отдел Тест,ou=Управление с очень очень длинным названием даже сложно запомнить насколько оно длинное и еле влезает в экран№123,ou=R&D,dc=glpi,dc=org',
            $user->fields['user_dn']
        );

        $this->assertTrue(
            ldap_modify(
                $ldap->connect(),
                'uid=Тестов Тест Тестович,ou=Отдел Тест,ou=Управление с очень очень длинным названием даже сложно запомнить насколько оно длинное и еле влезает в экран№123,ou=R&D,dc=glpi,dc=org',
                ['telephoneNumber' => '+33103030303']
            )
        );

        $synchro = $ldap->forceOneUserSynchronization($user);
        $this->assertCount(2, $synchro);
        $this->assertSame(AuthLDAP::USER_SYNCHRONIZED, $synchro['action']);
        $this->assertSame($user->getID(), $synchro['id']);

        $this->assertTrue($user->getFromDB($user->getID()));
        $this->assertSame('Тестов Тест Тестович', $user->fields['name']);
        $this->assertSame('+33103030303', $user->fields['phone']);
        $this->assertSame(
            'uid=Тестов Тест Тестович,ou=Отдел Тест,ou=Управление с очень очень длинным названием даже сложно запомнить насколько оно длинное и еле влезает в экран№123,ou=R&D,dc=glpi,dc=org',
            $user->fields['user_dn']
        );

        //drop test user
        $this->assertTrue(
            ldap_delete(
                $ldap->connect(),
                'uid=Тестов Тест Тестович,OU=Отдел Тест,OU=Управление с очень очень длинным названием даже сложно запомнить насколько оно длинное и еле влезает в экран№123,ou=R&D,DC=glpi,DC=org'
            )
        );
    }

    public static function syncWithManagerProvider()
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
                'inetOrgPerson',
            ],
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

    #[RequiresPhpExtension('ldap')]
    #[DataProvider('syncWithManagerProvider')]
    public function testSyncWithManager($manager_dn, array $manager_entry)
    {
        // Static conf
        $base_dn = "ou=people,ou=R&D,dc=glpi,dc=org";
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
                'inetOrgPerson',
            ],
        ];

        // Init ldap
        $ldap = $this->ldap;
        $ldap_con = $ldap->connect();

        // Add the manager
        $this->assertTrue(
            ldap_add($ldap_con, $manager_full_dn, $manager_entry),
            ldap_error($ldap_con)
        );

        // Add the user
        $this->assertTrue(
            ldap_add($ldap_con, $user_full_dn, $user_entry),
            ldap_error($ldap_con)
        );

        // Import manager
        $import_manager = AuthLDAP::ldapImportUserByServerId(
            [
                'method' => AuthLDAP::IDENTIFIER_LOGIN,
                'value'  => $manager_entry['uid'],
            ],
            AuthLDAP::ACTION_IMPORT,
            $ldap->getID(),
            true
        );
        $this->assertCount(2, $import_manager);
        $this->assertSame(AuthLDAP::USER_IMPORTED, $import_manager['action']);
        $this->assertGreaterThan(0, $import_manager['id']);

        // Import user
        $import_user = AuthLDAP::ldapImportUserByServerId(
            [
                'method' => AuthLDAP::IDENTIFIER_LOGIN,
                'value'  => $user_entry['uid'],
            ],
            AuthLDAP::ACTION_IMPORT,
            $ldap->getID(),
            true
        );
        $this->assertCount(2, $import_user);
        $this->assertSame(AuthLDAP::USER_IMPORTED, $import_user['action']);
        $this->assertGreaterThan(0, $import_user['id']);

        // Check created manager
        $manager = new \User();
        $this->assertTrue($manager->getFromDB($import_manager['id']));

        $this->assertSame($manager_entry['uid'], $manager->fields['name']);

        // Compare dn in a case-insensitive way as ldap_escape create filter in
        // lowercase ("," -> \2c) but some ldap software store them in uppercase
        $this->assertSame(
            strtolower($manager_full_dn),
            strtolower($manager->fields['user_dn'])
        );

        // Check created user
        $user = new \User();
        $this->assertTrue($user->getFromDB($import_user['id']));

        $this->assertSame($user_entry['uid'], $user->fields['name']);
        $this->assertSame(
            $user_full_dn,
            $user->fields['user_dn']
        );
        $this->assertSame($manager->fields['id'], $user->fields['users_id_supervisor']);

        // Drop both
        $this->assertTrue(ldap_delete($ldap->connect(), $user_full_dn));
        $this->assertTrue(ldap_delete($ldap->connect(), $manager_full_dn));
        $this->assertTrue($user->delete(['id' => $user->fields['id']], 1));
        $this->assertTrue($user->delete(['id' => $manager->fields['id']], 1));
    }

    /**
     * Test if rules targeting ldap criteria are working
     *
     * @return void
     */
    public function testRuleRight()
    {
        //prepare rules
        $rules = new RuleRight();
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
        $this->assertGreaterThan(0, $group_id);

        $actions->add([
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'specific_groups_id',
            'value'       => $group_id, // '_test_child_1' entity
        ]);

        // login the user to force a real synchronisation and get it's glpi id
        $this->realLogin('brazil6', 'password', false);
        $users_id = \User::getIdByName('brazil6');
        $this->assertGreaterThan(0, $users_id);
        // check the user got the entity/profiles assigned
        $pu = Profile_User::getForUser($users_id, true);
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
        $this->assertTrue($found);

        // Check group
        $gu = new Group_User();
        $gus = $gu->find([
            'groups_id' => $group_id,
            'users_id' => $users_id,
        ]);
        $this->assertCount(1, $gus);
    }


    /**
     * Test if rules targeting ldap criteria are working
     *
     * @return void
     */
    public function testGroupRuleRight()
    {
        $this->updateItem(
            AuthLDAP::class,
            getItemByTypeName(AuthLDAP::class, '_local_ldap', true),
            [
                'group_search_type' => AuthLDAP::GROUP_SEARCH_BOTH,
            ]
        );

        //prepare rules
        $rules_id = $this->createItem(
            'RuleRight',
            [
                'sub_type'     => 'RuleRight',
                'name'         => 'test ldap groupruleright',
                'match'        => 'AND',
                'is_active'    => 1,
                'entities_id'  => 0,
                'is_recursive' => 1,
            ]
        )->getID();

        $crit_id = $this->createItem(\RuleCriteria::class, [
            'rules_id'  => $rules_id,
            'criteria'  => 'LOGIN',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 'brazil6',
        ])->getID();

        $this->createItem(\RuleAction::class, [
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'profiles_id',
            'value'       => 5, // 'normal' profile
        ]);
        $this->createItem(\RuleAction::class, [
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'entities_id',
            'value'       => 0, // '_test_child_1' entity
        ]);

        // Create 2 dynamic group
        $group_id = $this->createItem(Group::class, ["name" => "testgroup1"])->getID();
        $this->assertGreaterThan(0, $group_id);

        $group2_id = $this->createItem(Group::class, ["name" => "testgroup2"])->getID();
        $this->assertGreaterThan(0, $group2_id);

        $group3_id = $this->createItem(Group::class, ["name" => "testgroup3", "ldap_field" => "uid", "ldap_value" => "brazil6"])->getID();
        $this->assertGreaterThan(0, $group3_id);

        // Add groups with a rule
        $act_id = $this->createItem(\RuleAction::class, [
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'specific_groups_id',
            'value'       => $group_id,
        ])->getID();

        // login the user to force a real synchronisation and get it's glpi id
        $this->realLogin('brazil6', 'password', false);
        $users_id = \User::getIdByName('brazil6');
        $this->assertGreaterThan(0, $users_id);

        $this->assertTrue(Group_User::isUserInGroup($users_id, $group_id));
        $this->assertFalse(Group_User::isUserInGroup($users_id, $group2_id));
        $this->assertTrue(Group_User::isUserInGroup($users_id, $group3_id)); // from `ldap_field`/`ldap_value` group attributes

        // Check group
        $gu = new Group_User();
        $gus = $gu->find([
            'users_id' => $users_id,
            'is_dynamic' => 1,
        ]);
        $this->assertCount(2, $gus);

        // Create 2 manual groups
        $mgroup_id = $this->createItem(Group::class, ["name" => "manualgroup1"])->getID();
        $this->assertGreaterThan(0, $mgroup_id);
        $mgroup2_id = $this->createItem(Group::class, ["name" => "manualgroup2"])->getID();
        $this->assertGreaterThan(0, $mgroup2_id);

        // Add 2 groups manualy
        $gu = new Group_User();
        $gu_id = $this->createItem(Group_User::class, [
            'users_id' => $users_id,
            'groups_id' => $mgroup_id,
        ])->getID();
        $this->assertGreaterThan(0, $gu_id);
        $gu_id = $this->createItem(Group_User::class, [
            'users_id' => $users_id,
            'groups_id' => $mgroup2_id,
        ])->getID();
        $this->assertGreaterThan(0, $gu_id);

        // Check group
        $this->assertTrue(Group_User::isUserInGroup($users_id, $mgroup_id));
        $this->assertTrue(Group_User::isUserInGroup($users_id, $mgroup2_id));
        $this->assertTrue(Group_User::isUserInGroup($users_id, $group_id));
        $this->assertFalse(Group_User::isUserInGroup($users_id, $group2_id));
        $this->assertTrue(Group_User::isUserInGroup($users_id, $group3_id)); // from `ldap_field`/`ldap_value` group attributes
        $this->assertEquals(4, \countElementsInTable(Group_User::getTable(), ['users_id' => $users_id]));

        // update action
        $this->updateItem(\RuleAction::class, $act_id, [
            'value' => $group2_id,
        ]);

        // Login
        $this->realLogin('brazil6', 'password', false);
        $users_id = \User::getIdByName('brazil6');
        $this->assertGreaterThan(0, $users_id);

        // Check the dynamic group is deleted without losing the manual groups
        $this->assertTrue(Group_User::isUserInGroup($users_id, $mgroup_id));
        $this->assertTrue(Group_User::isUserInGroup($users_id, $mgroup2_id));
        $this->assertFalse(Group_User::isUserInGroup($users_id, $group_id));
        $this->assertTrue(Group_User::isUserInGroup($users_id, $group2_id));
        $this->assertTrue(Group_User::isUserInGroup($users_id, $group3_id)); // from `ldap_field`/`ldap_value` group attributes
        $this->assertEquals(4, \countElementsInTable(Group_User::getTable(), ['users_id' => $users_id]));

        // update criteria
        $crit_id = $this->updateItem(\RuleCriteria::class, $crit_id, [
            'pattern'   => 'brazil7',
        ]);

        // Login
        $this->realLogin('brazil6', 'password', false);
        $users_id = \User::getIdByName('brazil6');
        $this->assertGreaterThan(0, $users_id);

        // Check the dynamic group is deleted without losing the manual groups
        $this->assertTrue(Group_User::isUserInGroup($users_id, $mgroup_id));
        $this->assertTrue(Group_User::isUserInGroup($users_id, $mgroup2_id));
        $this->assertFalse(Group_User::isUserInGroup($users_id, $group_id));
        $this->assertFalse(Group_User::isUserInGroup($users_id, $group2_id));
        $this->assertTrue(Group_User::isUserInGroup($users_id, $group3_id)); // from `ldap_field`/`ldap_value` group attributes
        $this->assertEquals(3, \countElementsInTable(Group_User::getTable(), ['users_id' => $users_id]));
    }

    /**
     * Test if ruleright '_groups_id' criteria is working
     *
     * @return void
     */
    public function testRuleRightGroupCriteria()
    {

        // create manual group
        $group = $this->createItem(Group::class, [
            'name'         => "test",
            'entities_id'  => 0,
            'is_recursive' => 1,
        ]);
        $group_id = $group->getID();

        // create RuleRight
        $builder = new RuleBuilder('Test _groups_id criteria', RuleRight::class);
        $builder->addCriteria('_groups_id', \Rule::PATTERN_IS, $group_id);
        $builder->addAction('assign', 'profiles_id', 4); // Super admin
        $builder->addAction('assign', 'entities_id', 1);
        $builder->setEntity(0);
        $this->createRule($builder);

        // login the user to force a real synchronisation (and creation into DB)
        $this->realLogin('brazil7', 'password', false);
        $users_id = \User::getIdByName('brazil7');

        // Add group to user
        $this->createItem(Group_User::class, [
            'groups_id' => $group_id,
            'users_id'  => $users_id,
        ]);

        // Check that the user is not attached to the profile at creation
        $rights = (new Profile_User())->find([
            'users_id' => $users_id,
            'profiles_id' => 4,
        ]);
        $this->assertCount(0, $rights);

        // Log in again to trigger rule
        $this->realLogin('brazil7', 'password', false);

        // Check that the correct profile was set
        $rights = (new Profile_User())->find([
            'users_id' => $users_id,
            'profiles_id' => 4,
        ]);

        $this->assertCount(1, $rights);
    }

    #[RequiresPhpExtension('ldap')]
    public function testLdapUnavailable()
    {
        //Import user that doesn't exist yet
        $auth = $this->realLogin('brazil5', 'password', false);

        $user = new \User();
        $user->getFromDBbyName('brazil5');
        $this->assertSame('brazil5', $user->fields['name']);
        $this->assertSame('uid=brazil5,ou=people,ou=R&D,dc=glpi,dc=org', $user->fields['user_dn']);
        $this->assertFalse($auth->user_present);
        $this->assertFalse($auth->user_dn);
        $this->checkLdapConnection($auth->ldap_connection);

        // Add a second LDAP server that is accessible but where user will not be found.
        $input = $this->ldap->fields;
        unset($input['id']);
        $input['rootdn_passwd'] = 'insecure'; // cannot reuse encrypted password from `$this->ldap->fields`
        $input['basedn'] = 'dc=notglpi'; // use a non-matching base DN to ensure user cannot login on it
        $ldap = new AuthLDAP();
        $this->assertGreaterThan(0, $ldap->add($input));

        // Update first LDAP server to make it inaccessible.
        $this->assertTrue(
            $this->ldap->update([
                'id'     => $this->ldap->getID(),
                'port'   => '1234',
            ])
        );

        $this->realLogin('brazil5', 'password', false, false);
        $this->hasPhpLogRecordThatContains(
            "Unable to bind to LDAP server `openldap:1234` with RDN `cn=Manager,dc=glpi,dc=org`\nerror: Can't contact LDAP server (-1)",
            LogLevel::WARNING
        );

        $user->getFromDBbyName('brazil5');
        // Verify trying to log in while LDAP unavailable does not disable user's GLPI account
        $this->assertEquals(1, $user->fields['is_active']);
        $this->assertEquals(0, $user->fields['is_deleted_ldap']);
    }

    #[RequiresPhpExtension('ldap')]
    public function testLdapDeletionOnLogin()
    {
        $connection = $this->ldap->connect();
        $this->checkLdapConnection($connection);

        // Add a new user in directory
        $this->assertTrue(
            ldap_add(
                $connection,
                'uid=logintest,ou=people,ou=R&D,dc=glpi,dc=org',
                [
                    'uid'          => 'logintest',
                    'sn'           => 'A SN',
                    'cn'           => 'A CN',
                    'userpassword' => 'password',
                    'objectClass'  => [
                        'top',
                        'inetOrgPerson',
                    ],
                ]
            )
        );

        //Import user that doesn't exist yet
        $auth = $this->realLogin('logintest', 'password');

        $user = new \User();
        $user->getFromDBbyName('logintest');
        $this->assertSame('logintest', $user->fields['name']);
        $this->assertSame('uid=logintest,ou=people,ou=R&D,dc=glpi,dc=org', $user->fields['user_dn']);
        $this->assertFalse($auth->user_present);
        $this->assertFalse($auth->user_dn);
        $this->checkLdapConnection($auth->ldap_connection);

        // Add a second LDAP server that is accessible but where user will not be found.
        $input = $this->ldap->fields;
        unset($input['id']);
        $input['rootdn_passwd'] = 'insecure'; // cannot reuse encrypted password from `$this->ldap->fields`
        $input['basedn'] = 'dc=notglpi'; // use a non-matching base DN to ensure user cannot login on it
        $ldap = new AuthLDAP();
        $this->assertGreaterThan(0, $ldap->add($input));

        // Delete the user
        $this->assertTrue(
            ldap_delete(
                $connection,
                'uid=logintest,ou=people,ou=R&D,dc=glpi,dc=org'
            )
        );

        // Assert user can't log in if not present in remote LDAP
        $auth = new \Auth();
        $this->assertFalse($auth->login('logintest', 'password'));

        // Assert user is marked "deleted" in GLPI database
        $user->getFromDBbyName('logintest');
        $this->assertEquals(1, $user->fields['is_deleted_ldap']);
    }

    #[RequiresPhpExtension('ldap')]
    public function testLdapLoginWithWrongPassword()
    {
        $auth = new \Auth();
        $this->assertFalse($auth->login('brazil5', 'wrong-password', false));

        $user = new \User();
        $this->assertFalse($user->getFromDBbyName('brazil5'));
    }

    private function checkLdapConnection($ldap_connection)
    {
        $this->assertInstanceOf(Connection::class, $ldap_connection);
    }

    public function testIgnoreImport()
    {
        //prepare rules
        $rules = new RuleRight();
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
        $this->realLogin('brazil6', 'password', false, false);

        // Check title not created
        $ut = new UserTitle();
        $uts = $ut->find([
            'name' => 'manager',
        ]);
        $this->assertCount(0, $uts);
    }

    public function testGetLdapDateValue()
    {
        $auth = new AuthLDAP();
        $this->assertSame('2023-02-24 15:08:00', $auth->getLdapDateValue('20230224150800.0Z'));
        $this->assertSame('2023-02-24 14:14:02', $auth->getLdapDateValue('133217216420000000'));
    }

    public static function connectToServerErrorsProvider(): iterable
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

    #[RequiresPhpExtension('ldap')]
    #[DataProvider('connectToServerErrorsProvider')]
    public function testConnectToServerErrorMessage(
        string $host,
        string $port,
        string $login,
        string $password,
        string $error
    ) {
        AuthLDAP::connectToServer($host, $port, $login, $password);
        $this->hasPhpLogRecordThatContains(
            $error,
            LogLevel::WARNING
        );
    }

    #[RequiresPhpExtension('ldap')]
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

        AuthLDAP::connectToServer(
            'openldap',
            '3890',
            'cn=Manager,dc=glpi,dc=org',
            'insecure',
            true,
        );
        $this->hasPhpLogRecordThatContains(
            $error,
            LogLevel::WARNING
        );
    }

    #[RequiresPhpExtension('ldap')]
    public function testGetGroupCNByDnError()
    {

        $ldap = getItemByTypeName('AuthLDAP', '_local_ldap');
        $connection = $ldap->connect();
        $this->assertInstanceOf(Connection::class, $connection);

        $error = implode(
            "\n",
            [
                'Unable to get LDAP group having DN `notavaliddn`',
                'error: Invalid DN syntax (34)',
                'extended error: invalid DN',
                'err string: invalid DN',
            ]
        );

        AuthLDAP::getGroupCNByDn($connection, 'notavaliddn');
        $this->hasPhpLogRecordThatContains(
            $error,
            LogLevel::WARNING
        );
    }


    public static function getObjectGroupByDnErrorsProvider(): iterable
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

    #[RequiresPhpExtension('ldap')]
    #[DataProvider('getObjectGroupByDnErrorsProvider')]
    public function testGetObjectGroupByDnError(
        string $basedn,
        string $filter,
        string $error
    ) {
        $ldap = getItemByTypeName('AuthLDAP', '_local_ldap');
        $connection = $ldap->connect();
        $this->checkLdapConnection($connection);

        AuthLDAP::getObjectByDn($connection, $filter, $basedn, ['dn']);
        $this->hasPhpLogRecordThatContains(
            $error,
            LogLevel::WARNING
        );
    }

    public static function searchForUsersErrorsProvider(): iterable
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

    #[RequiresPhpExtension('ldap')]
    #[DataProvider('searchForUsersErrorsProvider')]
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

        AuthLDAP::searchForUsers(
            $connection,
            ['basedn' => $basedn],
            $filter,
            ['dn'],
            $limitexceeded,
            $user_infos,
            $ldap_users,
            $ldap
        );
        $this->hasPhpLogRecordThatContains(
            $error,
            LogLevel::WARNING
        );
    }

    public static function searchUserDnErrorsProvider(): iterable
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

    #[RequiresPhpExtension('ldap')]
    #[DataProvider('searchUserDnErrorsProvider')]
    public function testSearchUserDnErrorMessages(
        array $options,
        string $error
    ) {
        $ldap = getItemByTypeName('AuthLDAP', '_local_ldap');
        $connection = $ldap->connect();
        $this->checkLdapConnection($connection);

        AuthLDAP::searchUserDn(
            $connection,
            $options + [
                'login_field'       => 'uid',
                'search_parameters' => ['fields' => ['login' => 'uid']],
                'user_params'       => ['value'  => 'johndoe'],
            ]
        );
        $this->hasPhpLogRecordThatContains(
            $error,
            LogLevel::WARNING
        );
    }

    public static function getGroupsFromLDAPErrorsProvider(): iterable
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

    #[RequiresPhpExtension('ldap')]
    #[DataProvider('getGroupsFromLDAPErrorsProvider')]
    public function testGetGroupsFromLDAPErrors(
        array $config_fields,
        string $filter,
        string $error
    ) {
        $ldap = getItemByTypeName('AuthLDAP', '_local_ldap');
        $ldap->fields = array_merge($ldap->fields, $config_fields);

        $connection = $ldap->connect();
        $this->checkLdapConnection($connection);

        $limitexceeded = null;

        AuthLDAP::getGroupsFromLDAP(
            $connection,
            $ldap,
            $filter,
            $limitexceeded
        );
        $this->hasPhpLogRecordThatContains(
            $error,
            LogLevel::WARNING
        );
    }
}
