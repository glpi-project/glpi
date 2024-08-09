<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
use Glpi\Plugin\Hooks;
use Log;
use PHPMailer\PHPMailer\PHPMailer;
use Profile;
use Session;

/* Test for inc/config.class.php */

class ConfigTest extends DbTestCase
{
    public function testGetTypeName()
    {
        $this->assertSame('Setup', \Config::getTypeName());
    }

    public function testAcls()
    {
       //check ACLs when not logged
        $this->assertFalse(\Config::canView());
        $this->assertFalse(\Config::canCreate());

        $conf = new \Config();
        $this->assertFalse($conf->canViewItem());

       //check ACLs from superadmin profile
        $this->login();
        $this->assertTrue((bool)\Config::canView());
        $this->assertFalse(\Config::canCreate());
        $this->assertFalse($conf->canViewItem());

        $this->assertTrue($conf->getFromDB(1));
        $this->assertTrue($conf->canViewItem());

       //check ACLs from tech profile
        $auth = new \Auth();
        $this->assertTrue((bool)$auth->login('tech', 'tech', true));
        $this->assertFalse((bool)\Config::canView());
        $this->assertFalse(\Config::canCreate());
        $this->assertTrue($conf->canViewItem());
    }

    public function testGetMenuContent()
    {
        $this->assertFalse(\Config::getMenuContent());

        $this->login();
        $this->assertEquals(
            ['title', 'page', 'icon', 'options'],
            array_keys(\Config::getMenuContent())
        );
    }

    public function testDefineTabs()
    {
        $expected = [
            'Config$1'      => 'General setup',
            'Config$2'      => 'Default values',
            'Config$3'      => 'Assets',
            'Config$4'      => 'Assistance',
            'Config$12'     => 'Management',
            'GLPINetwork$1' => 'GLPI Network',
        ];

        $instance = new \Config();
        $this->assertSame($expected, $instance->defineTabs());

        //Standards users do not have extra tabs
        $auth = new \Auth();
        $this->assertTrue((bool)$auth->login('tech', 'tech', true));

        $instance = new \Config();
        $this->assertSame($expected, $instance->defineTabs());

        //check extra tabs from superadmin profile
        $this->login();
        $expected = [
            'Config$1'      => 'General setup',
            'Config$2'      => 'Default values',
            'Config$3'      => 'Assets',
            'Config$4'      => 'Assistance',
            'Config$12'     => 'Management',
            'Config$9'      => 'Logs purge',
            'Config$5'      => 'System',
            'Config$10'     => 'Security',
            'Config$7'      => 'Performance',
            'Config$8'      => 'API',
            'Config$11'      => \Impact::getTypeName(),
            'GLPINetwork$1' => 'GLPI Network',
            'Log$1'         => 'Historical',
        ];

        $instance = new \Config();
        $this->assertSame($expected, $instance->defineTabs());
    }

    public function testUnsetUndisclosedFields()
    {
        $input = [
            'context'   => 'core',
            'name'      => 'name',
            'value'     => 'value'
        ];
        $expected = $input;

        \Config::unsetUndisclosedFields($input);
        $this->assertSame($expected, $input);

        $input = [
            'context'   => 'core',
            'name'      => 'proxy_passwd',
            'value'     => 'value'
        ];
        $expected = $input;
        unset($expected['value']);

        \Config::unsetUndisclosedFields($input);
        $this->assertSame($expected, $input);

        $input = [
            'context'   => 'core',
            'name'      => 'smtp_passwd',
            'value'     => 'value'
        ];
        $expected = $input;
        unset($expected['value']);

        \Config::unsetUndisclosedFields($input);
        $this->assertSame($expected, $input);
    }

    public function testValidatePassword()
    {
        global $CFG_GLPI;
        $this->assertFalse((bool)$CFG_GLPI['use_password_security']);

        $this->assertTrue(\Config::validatePassword('mypass'));

        $CFG_GLPI['use_password_security'] = 1;
        $this->assertSame(8, (int)$CFG_GLPI['password_min_length']);
        $this->assertSame(1, (int)$CFG_GLPI['password_need_number']);
        $this->assertSame(1, (int)$CFG_GLPI['password_need_letter']);
        $this->assertSame(1, (int)$CFG_GLPI['password_need_caps']);
        $this->assertSame(1, (int)$CFG_GLPI['password_need_symbol']);
        $this->assertFalse(\Config::validatePassword(''));

        $expected = [
            'Password too short!',
            'Password must include at least a digit!',
            'Password must include at least a lowercase letter!',
            'Password must include at least a uppercase letter!',
            'Password must include at least a symbol!'
        ];
        $this->hasSessionMessages(ERROR, $expected);
        $expected = [
            'Password must include at least a digit!',
            'Password must include at least a uppercase letter!',
            'Password must include at least a symbol!'
        ];
        $this->assertFalse(\Config::validatePassword('mypassword'));
        $this->hasSessionMessages(ERROR, $expected);

        $CFG_GLPI['password_min_length'] = strlen('mypass');
        $this->assertFalse(\Config::validatePassword('mypass'));
        $CFG_GLPI['password_min_length'] = 8; //reset

        $this->hasSessionMessages(ERROR, $expected);

        $expected = [
            'Password must include at least a uppercase letter!',
            'Password must include at least a symbol!'
        ];
        $this->assertFalse(\Config::validatePassword('my1password'));
        $this->hasSessionMessages(ERROR, $expected);

        $CFG_GLPI['password_need_number'] = 0;
        $this->assertFalse(\Config::validatePassword('mypassword'));
        $CFG_GLPI['password_need_number'] = 1; //reset
        $this->hasSessionMessages(ERROR, $expected);

        $expected = [
            'Password must include at least a symbol!'
        ];
        $this->assertFalse(\Config::validatePassword('my1paSsword'));
        $this->hasSessionMessages(ERROR, $expected);

        $CFG_GLPI['password_need_caps'] = 0;
        $this->assertFalse(\Config::validatePassword('my1password'));
        $CFG_GLPI['password_need_caps'] = 1; //reset
        $this->hasSessionMessages(ERROR, $expected);

        $this->assertTrue(\Config::validatePassword('my1paSsw@rd'));
        $this->hasNoSessionMessage(ERROR);

        $CFG_GLPI['password_need_symbol'] = 0;
        $this->assertTrue(\Config::validatePassword('my1paSsword'));
        $CFG_GLPI['password_need_symbol'] = 1; //reset
        $this->hasNoSessionMessage(ERROR);
    }

    public function testGetLibraries()
    {
        $actual = $expected = [];
        $deps = \Config::getLibraries(true);
        foreach ($deps as $dep) {
           // composer names only (skip htmlLawed)
            if (strpos($dep['name'], '/')) {
                $actual[] = $dep['name'];
            }
        }
        sort($actual);
        $this->assertNotEmpty($actual);
        $composer = json_decode(file_get_contents(__DIR__ . '/../../composer.json'), true);
        foreach (array_keys($composer['require']) as $dep) {
           // composer names only (skip php, ext-*, ...)
            if (strpos($dep, '/')) {
                $expected[] = $dep;
            }
        }
        sort($expected);
        $this->assertNotEmpty($expected);
        $this->assertSame($expected, $actual);
    }

    public function testGetLibraryDir()
    {
        $this->assertFalse(\Config::getLibraryDir(''));
        $this->assertFalse(\Config::getLibraryDir('abcde'));

        $expected = realpath(__DIR__ . '/../../vendor/phpmailer/phpmailer/src');
        if (is_dir($expected)) { // skip when system library is used
            $this->assertSame($expected, \Config::getLibraryDir('PHPMailer\PHPMailer\PHPMailer'));

            $mailer = new PHPMailer();
            $this->assertSame($expected, \Config::getLibraryDir($mailer));
        }

        $expected = realpath(__DIR__ . '/../../tests/src/autoload');
        $this->assertSame($expected, \Config::getLibraryDir('getItemByTypeName'));
    }

    public function testCheckExtensions()
    {
        $check = \Config::checkExtensions();
        $this->assertArrayHasKey('error', $check);
        $this->assertArrayHasKey('good', $check);
        $this->assertArrayHasKey('missing', $check);
        $this->assertArrayHasKey('may', $check);

        $expected = [
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
        $this->assertSame($expected, $report);

       //check extension from method name
        $list = [
            'mysqli' => [
                'required'  => true,
                'function'  => 'mysqli_commit'
            ]
        ];
        $report = \Config::checkExtensions($list);
        $this->assertSame($expected, $report);

        //check extension from its name
        $list = [
            'mysqli' => [
                'required'  => true
            ]
        ];
        $report = \Config::checkExtensions($list);
        $this->assertSame($expected, $report);

        //required, missing extension
        $list['notantext'] = [
            'required'  => true
        ];
        $report = \Config::checkExtensions($list);
        $expected = [
            'error'     => 2,
            'good'      => [
                'mysqli' => 'mysqli extension is installed',
            ],
            'missing'   => [
                'notantext' => 'notantext extension is missing'
            ],
            'may'       => []
        ];
        $this->assertSame($expected, $report);

        //not required, missing extension
        unset($list['notantext']);
        $list['totally_optionnal'] = ['required' => false];
        $report = \Config::checkExtensions($list);
        $expected = [
            'error'     => 1,
            'good'      => [
                'mysqli' => 'mysqli extension is installed',
            ],
            'missing'   => [],
            'may'       => [
                'totally_optionnal' => 'totally_optionnal extension is not present'
            ]
        ];
        $this->assertSame($expected, $report);
    }

    public function testGetConfigurationValues()
    {
        $conf = \Config::getConfigurationValues('core');
        $this->assertArrayHasKey('version', $conf);
        $this->assertArrayHasKey('dbversion', $conf);
        $this->assertGreaterThan(170, count($conf));

        $conf = \Config::getConfigurationValues('core', ['version', 'dbversion']);
        $this->assertEquals(
            [
                'dbversion' => GLPI_SCHEMA_VERSION,
                'version'   => GLPI_VERSION
            ],
            $conf
        );
    }

    public function testSetConfigurationValues()
    {
        $conf = \Config::getConfigurationValues('core', ['version', 'notification_to_myself']);
        $this->assertEquals(
            [
                'notification_to_myself'   => '1',
                'version'                  => GLPI_VERSION
            ],
            $conf
        );

        //update configuration value
        \Config::setConfigurationValues('core', ['notification_to_myself' => 0]);
        $conf = \Config::getConfigurationValues('core', ['version', 'notification_to_myself']);
        $this->assertEquals(
            [
                'notification_to_myself'   => '0',
                'version'                  => GLPI_VERSION
            ],
            $conf
        );
        \Config::setConfigurationValues('core', ['notification_to_myself' => 1]); //reset

        //check new configuration key does not exists
        $conf = \Config::getConfigurationValues('core', ['version', 'new_configuration_key']);
        $this->assertEquals(
            [
                'version' => GLPI_VERSION
            ],
            $conf
        );

        //add new configuration key
        \Config::setConfigurationValues('core', ['new_configuration_key' => 'test']);
        $conf = \Config::getConfigurationValues('core', ['version', 'new_configuration_key']);
        $this->assertEquals(
            [
                'new_configuration_key' => 'test',
                'version'               => GLPI_VERSION
            ],
            $conf
        );

        //drop new configuration key
        \Config::deleteConfigurationValues('core', ['new_configuration_key']);
        $conf = \Config::getConfigurationValues('core', ['version', 'new_configuration_key']);
        $this->assertEquals(
            [
                'version' => GLPI_VERSION
            ],
            $conf
        );
    }

    public function testGetRights()
    {
        $conf = new \Config();
        $this->assertSame(
            [
                READ     => 'Read',
                UPDATE   => 'Update'
            ],
            $conf->getRights()
        );
    }

    public function testGetPalettes()
    {
        $expected = [
            'aerialgreen'     => 'Aerialgreen',
            'auror'           => 'Auror',
            'auror_dark'      => 'Auror_dark',
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
            'midnight'        => 'Midnight',
            'premiumred'      => 'Premiumred',
            'purplehaze'      => 'Purplehaze',
            'teclib'          => 'Teclib',
            'vintage'         => 'Vintage',
        ];

        $instance = new \Config();
        $this->assertSame($expected, $instance->getPalettes());
    }

    /**
     * Database engines data provider
     *
     * @return array
     */
    public static function dbEngineProvider()
    {
        return [
            [
                'raw'       => '10.1.48-MariaDB',
                'version'   => '10.1.48',
                'compat'    => false
            ], [
                'raw'       => '10.2.14-MariaDB',
                'version'   => '10.2.14',
                'compat'    => true
            ], [
                'raw'       => '10.3.28-MariaDB',
                'version'   => '10.3.28',
                'compat'    => true
            ], [
                'raw'       => '10.4.8-MariaDB-1:10.4.8+maria~bionic',
                'version'   => '10.4.8',
                'compat'    => true
            ], [
                'raw'       => '10.5.9-MariaDB',
                'version'   => '10.5.9',
                'compat'    => true
            ], [
                'raw'       => '5.6.38-log',
                'version'   => '5.6.38',
                'compat'    => false
            ],  [
                'raw'       => '5.7.50-log',
                'version'   => '5.7.50',
                'compat'    => true
            ], [
                'raw'       => '8.0.23-standard',
                'version'   => '8.0.23',
                'compat'    => true
            ],
        ];
    }

    /**
     * @dataProvider dbEngineProvider
     */
    public function testCheckDbEngine($raw, $version, $compat)
    {
        global $DB;

        $orig_db = clone $DB;
        $DB = $this->getMockBuilder(\DB::class)
            ->onlyMethods(['getVersion'])
            ->getMock();
        $DB->method('getVersion')->willReturn($raw);

        $result = \Config::checkDbEngine();
        $this->assertSame(
            [$version => $compat],
            $result
        );

        $DB = $orig_db;
    }

    public function testGetLanguage()
    {
        $instance = new \Config();
        $this->assertSame('fr_FR', $instance->getLanguage('fr'));
        $this->assertSame('fr_FR', $instance->getLanguage('fr_FR'));
        $this->assertSame('fr_FR', $instance->getLanguage('Français'));
        $this->assertSame('fr_FR', $instance->getLanguage('french'));
        $this->assertSame('', $instance->getLanguage('notalang'));
    }

    /**
     * Provides list of classes that can be linked to configuration.
     *
     * @return array
     */
    public static function itemtypeLinkedToConfigurationProvider()
    {
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
    public function testCleanRelationDataOfLinkedItems($key, $itemtype)
    {

        // Case 1: used item is cleaned without replacement
        $item = new $itemtype();
        $item->fields = ['id' => 15];

        \Config::setConfigurationValues('core', [$key => $item->fields['id']]);

        if (is_a($itemtype, 'CommonDropdown', true)) {
            $this->assertTrue($item->isUsed());
        }
        $item->cleanRelationData();
        if (is_a($itemtype, 'CommonDropdown', true)) {
            $this->assertFalse($item->isUsed());
        }
        $this->assertArrayHasKey($key, \Config::getConfigurationValues('core', [$key]));
        $this->assertEquals(0, \Config::getConfigurationValues('core', [$key])[$key]);

        // Case 2: unused item is cleaned without effect
        $item = new $itemtype();
        $item->fields = ['id' => 15];

        $random_id = mt_rand(20, 100);

        \Config::setConfigurationValues('core', [$key => $random_id]);

        if (is_a($itemtype, 'CommonDropdown', true)) {
            $this->assertFalse($item->isUsed());
        }
        $item->cleanRelationData();
        if (is_a($itemtype, 'CommonDropdown', true)) {
            $this->assertFalse($item->isUsed());
        }
        $this->assertArrayHasKey($key, \Config::getConfigurationValues('core', [$key]));
        $this->assertEquals($random_id, \Config::getConfigurationValues('core', [$key])[$key]);

        // Case 3: used item is cleaned with replacement (CommonDropdown only)
        if (is_a($itemtype, 'CommonDropdown', true)) {
            $replacement_item = new $itemtype();
            $replacement_item->fields = ['id' => 12];

            $item = new $itemtype();
            $item->fields = ['id' => 15];
            $item->input = ['_replace_by' => $replacement_item->fields['id']];

            \Config::setConfigurationValues('core', [$key => $item->fields['id']]);

            $this->assertTrue($item->isUsed());
            $this->assertFalse($replacement_item->isUsed());
            $item->cleanRelationData();
            $this->assertFalse($item->isUsed());
            $this->assertTrue($replacement_item->isUsed());
            $this->assertArrayHasKey($key, \Config::getConfigurationValues('core', [$key]));
            $this->assertEquals($replacement_item->fields['id'], \Config::getConfigurationValues('core', [$key])[$key]);
        }
    }

    public function testDevicesInMenu()
    {
        global $CFG_GLPI, $DB;
        $bkp_devices_in_menu = $CFG_GLPI['devices_in_menu'];

        $conf = new \Config();
        $this->assertSame(
            ['Item_DeviceSimcard'],
            $CFG_GLPI['devices_in_menu']
        );

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
        ])->current();
        $this->assertSame(
            ['value' => exportArrayToDB(['Item_DeviceSimcard', 'Item_DeviceBattery'])],
            $res
        );

        $CFG_GLPI['devices_in_menu'] = $bkp_devices_in_menu;
    }

    /**
     * Test password expiration delay configuration update.
     */
    public function testPasswordExpirationDelayUpdate()
    {
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
            $this->assertGreaterThan(0, $user_id);
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
        $this->assertArrayHasKey('password_expiration_delay', $values);
        $this->assertSame(-1, (int)$values['password_expiration_delay']);
        $this->assertEquals(
            $local_users_count,
            countElementsInTable(
                \User::getTable(),
                ['authtype' => \Auth::DB_GLPI, 'password_last_update' => null]
            )
        );
        $this->assertEquals(
            $external_users_count,
            countElementsInTable(
                \User::getTable(),
                ['NOT' => ['authtype' => \Auth::DB_GLPI], 'password_last_update' => null]
            )
        );
        $this->assertTrue($crontask->getFromDBbyName(\User::getType(), 'passwordexpiration'));
        $this->assertSame(0, (int)$crontask->fields['state']);

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
        $this->assertArrayHasKey('password_expiration_delay', $values);
        $this->assertSame(30, (int)$values['password_expiration_delay']);
        $this->assertEquals(
            $local_users_count,
            countElementsInTable(
                \User::getTable(),
                ['authtype' => \Auth::DB_GLPI, 'password_last_update' => $update_datetime]
            )
        );
        $this->assertEquals(
            $external_users_count,
            countElementsInTable(
                \User::getTable(),
                ['NOT' => ['authtype' => \Auth::DB_GLPI], 'password_last_update' => null]
            )
        );
        $this->assertTrue($crontask->getFromDBbyName(\User::getType(), 'passwordexpiration'));
        $this->assertSame(1, (int)$crontask->fields['state']);

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
        $this->assertArrayHasKey('password_expiration_delay', $values);
        $this->assertSame(45, (int)$values['password_expiration_delay']);
        $this->assertEquals(
            $local_users_count,
            countElementsInTable(
                \User::getTable(),
                ['authtype' => \Auth::DB_GLPI, 'password_last_update' => $update_datetime] // previous config update
            )
        );
        $this->assertEquals(
            $external_users_count,
            countElementsInTable(
                \User::getTable(),
                ['NOT' => ['authtype' => \Auth::DB_GLPI], 'password_last_update' => null]
            )
        );
    }

    public static function logConfigChangeProvider()
    {
        global $PLUGIN_HOOKS;

        $PLUGIN_HOOKS[Hooks::SECURED_CONFIGS]['tester'] = ['passwd'];

        return [
            [
                'context'          => 'core',
                'name'             => 'unexisting_config',
                'is_secured'       => false,
                'old_value_prefix' => 'unexisting_config ',
                'itemtype'         => \Config::class,
            ],
            [
                'context'          => 'plugin:tester',
                'name'             => 'check',
                'is_secured'       => false,
                'old_value_prefix' => 'check (plugin:tester) ',
                'itemtype'         => \Config::class,
            ],
            [
                'context'          => 'plugin:tester',
                'name'             => 'passwd',
                'is_secured'       => true,
                'old_value_prefix' => 'passwd (plugin:tester) ',
                'itemtype'         => \Config::class,
            ],

            // Specific cases for smtp settings
            [
                'context'          => 'core',
                'name'             => 'smtp_host',
                'is_secured'       => false,
                'old_value_prefix' => 'smtp_host ',
                'itemtype'         => \NotificationMailingSetting::class,
            ],
            [
                'context'          => 'core',
                'name'             => 'smtp_oauth_refresh_token',
                'is_secured'       => true,
                'old_value_prefix' => 'smtp_oauth_refresh_token ',
                'itemtype'         => \NotificationMailingSetting::class,
            ],
        ];
    }

    /**
     * @dataProvider logConfigChangeProvider
     */
    public function testLogConfigChange(string $context, string $name, bool $is_secured, string $old_value_prefix, string $itemtype)
    {
        $history_crit = ['itemtype' => $itemtype, 'old_value' => ['LIKE', $name . ' %']];

        $expected_history = [];
        $history_entry_fields = [
            'itemtype'         => $itemtype,
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
        $this->assertEquals($expected_history, $found_history);

        // History on updated value
        \Config::setConfigurationValues($context, [$name => 'new value']);
        $expected_history[] = $history_entry_fields + [
            'old_value' => $old_value_prefix . ($is_secured ? '********' : 'first value'),
            'new_value' => $is_secured ? '********' : 'new value',
        ];

        $found_history = array_values(getAllDataFromTable(Log::getTable(), $history_crit));
        array_walk($found_history, $clean_ids);
        $this->assertEquals($expected_history, $found_history);

        // History on config deletion
        \Config::deleteConfigurationValues($context, [$name]);
        $expected_history[] = $history_entry_fields + [
            'old_value' => $old_value_prefix . ($is_secured ? '********' : 'new value'),
            'new_value' => $is_secured ? '********' : '',
        ];

        $found_history = array_values(getAllDataFromTable(Log::getTable(), $history_crit));
        array_walk($found_history, $clean_ids);
        $this->assertEquals($expected_history, $found_history);
    }

    public function testAutoCreateInfocom()
    {
        global $CFG_GLPI;

        $infocom_types = $CFG_GLPI["infocom_types"];
        $excluded_types = [
            'Cartridge', // Should inherit from CartridgeItem
            'Consumable', // Should inherit from ConsumableItem
        ];
        $infocom_types = array_diff($infocom_types, $excluded_types);

        $auto_create_infocoms_original = $CFG_GLPI["auto_create_infocoms"] ?? 0;

        $infocom = new \Infocom();
        foreach ($infocom_types as $asset_type) {
            $CFG_GLPI['auto_create_infocoms'] = 1;
            $asset = new $asset_type();
            $asset_id = $asset->add([
                'name'                  => 'auto_infocom_test',
                'entities_id'           => 0,
                'softwares_id'          => 1, // Random ID for testing SoftwareLicense
                'itemtype'              => 'Computer', // Random item type for testing Item_DeviceSimcard
                'devicesimcards_id'     => 1, // Random ID for testing Item_DeviceSimcard
            ]);
            $CFG_GLPI['auto_create_infocoms'] = $auto_create_infocoms_original;
            // Verify an Infocom object exists for the newly created asset
            $infocom_exists = $infocom->getFromDBforDevice($asset_type, $asset_id);
            $this->assertTrue($infocom_exists);

            $CFG_GLPI['auto_create_infocoms'] = 0;
            // Verify an Infocom object does not exist for a newly created asset
            $asset_id2 = $asset->add([
                'name'                  => 'auto_infocom_test2',
                'entities_id'           => 0,
                'softwares_id'          => 1, // Random ID for testing SoftwareLicense
                'itemtype'              => 'Computer', // Random item type for testing Item_DeviceSimcard
                'devicesimcards_id'     => 1, // Random ID for testing Item_DeviceSimcard
            ]);
            $CFG_GLPI['auto_create_infocoms'] = $auto_create_infocoms_original;
            $infocom_exists = $infocom->getFromDBforDevice($asset_type, $asset_id2);
            $this->assertFalse($infocom_exists);
        }
    }

    public function testDetectRooDoc(): void
    {
        global $CFG_GLPI;
        $bkp_root_doc = $CFG_GLPI['root_doc'];

        $uri_to_scriptname = [
            '/'                        => '/index.php',
            '/front/index.php?a=b'     => '/front/index.php',
            '/api.php/endpoint/method' => '/api.php',
            '//whatever/path/is'       => '/index.php', // considered as `path=/` + `pathinfo=/whatever/path/is` by GLPI router
        ];

        foreach (['', '/glpi', '/whatever/alias/is'] as $prefix) {
            foreach ($uri_to_scriptname as $uri => $script_name) {
                unset($CFG_GLPI['root_doc']);

                chdir(GLPI_ROOT . dirname($script_name)); // cwd is expected to be the executed script dir

                $server_bck = $_SERVER;
                $_SERVER['REQUEST_URI'] = $prefix . $uri;
                $_SERVER['SCRIPT_NAME'] = $prefix . $script_name;
                \Config::detectRootDoc();
                $_SERVER = $server_bck;

                $this->assertEquals($prefix, $CFG_GLPI['root_doc']);
            }
        }

        //reset root_doc
        $CFG_GLPI['root_doc'] = $bkp_root_doc;
    }

    public function testConfigLogNotEmpty()
    {
        $itemtype = 'Config';
        $config_id = \Config::getConfigIDForContext('core');
        $this->assertGreaterThan(0, $config_id);
        $total_number = countElementsInTable("glpi_logs", ['items_id' => $config_id, 'itemtype' => $itemtype]);
        $this->assertGreaterThan(0, $total_number);
    }

    /**
     * Test the `prepareInputForUpdate` method.
     *
     * @return void
     */
    public function testPrepareInputForUpdate(): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->login();

        // Ensure the profile used for locks is valid.
        $config = new \Config();
        $default_lock_profile = $CFG_GLPI['lock_lockprofile_id']; // 8

        // Invalid profile 1: simplified interface
        $config->prepareInputForUpdate([
            'lock_lockprofile_id' => getItemByTypeName(Profile::class, "Self-Service", true),
        ]);
        $this->assertEquals($default_lock_profile, (int) $CFG_GLPI['lock_lockprofile_id']);
        $this->hasSessionMessages(ERROR, [
            "The specified profile doesn't exist or is not allowed to access the central interface."
        ]);

        // Invalid profile 2: doesn't exist
        $config->prepareInputForUpdate([
            'lock_lockprofile_id' => 674568,
        ]);
        $this->assertEquals($default_lock_profile, (int) $CFG_GLPI['lock_lockprofile_id']);
        $this->hasSessionMessages(ERROR, [
            "The specified profile doesn't exist or is not allowed to access the central interface."
        ]);

        // Valid profile
        $super_admin = getItemByTypeName(Profile::class, "Super-Admin", true);
        $this->assertNotEquals($super_admin, (int)$CFG_GLPI['lock_lockprofile_id']);
        $config->prepareInputForUpdate([
            'lock_lockprofile_id' => $super_admin,
        ]);
        $this->assertEquals($super_admin, (int) $CFG_GLPI['lock_lockprofile_id']);
    }
}
