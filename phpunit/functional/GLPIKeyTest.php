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

use Glpi\Plugin\Hooks;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LogLevel;

/* Test for inc/glpikey.class.php */

class GLPIKeyTest extends \DbTestCase
{
    public static function getExpectedKeyPathProvider()
    {
        return [
            ['0.90.5', null],
            ['9.3.5', null],
            ['9.4.0', null],
            ['9.4.5', null],
            ['9.4.6', GLPI_CONFIG_DIR . '/glpi.key'],
            ['9.4.9', GLPI_CONFIG_DIR . '/glpi.key'],
            ['9.5.0-dev', GLPI_CONFIG_DIR . '/glpicrypt.key'],
            ['9.5.0', GLPI_CONFIG_DIR . '/glpicrypt.key'],
            ['9.5.3', GLPI_CONFIG_DIR . '/glpicrypt.key'],
            ['9.6.1', GLPI_CONFIG_DIR . '/glpicrypt.key'],
            ['15.3.0', GLPI_CONFIG_DIR . '/glpicrypt.key'],
        ];
    }

    #[DataProvider('getExpectedKeyPathProvider')]
    public function testGetExpectedKeyPath($glpi_version, $expected_path)
    {
        $glpikey = new \GLPIKey();
        $this->assertEquals($expected_path, $glpikey->getExpectedKeyPath($glpi_version));
    }

    public function testKeyExists()
    {
        $structure = vfsStream::setup('glpi', null, ['config' => []]);
        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        $this->assertFalse($glpikey->keyExists());

        vfsStream::create(['glpicrypt.key' => 'keyfilecontents'], $structure->getChild('config'));
        $this->assertTrue($glpikey->keyExists());
    }

    public static function legacyEncryptedProvider()
    {
        // basic string, default key
        yield [
            'encrypted' => 'G6y/xA==',
            'decrypted' => 'test',
            'key'       => null,
        ];

        // string with special chars, default key
        yield [
            'encrypted' => 'IYx+rrgV1IqUtqSD1repTQ==',
            'decrypted' => 'zE2^oS1!mC6"dD6&',
            'key'       => null,
        ];

        // basic string, simple custom key
        yield [
            'encrypted' => '7cjo5w==',
            'decrypted' => 'test',
            'key'       => 'custom_k3y',
        ];

        // string with special chars, complex custom  key
        yield [
            'encrypted' => 'n7iLkqvGhVeXsoFVwqWEVg==',
            'decrypted' => 'zE2^oS1!mC6"dD6&',
            'key'       => 'sY4<sT6*oK3^aN0%',
        ];
    }

    #[DataProvider('legacyEncryptedProvider')]
    public function testDecryptUsingLegacyKey(string $encrypted, string $decrypted, ?string $key)
    {
        $glpikey = new \GLPIKey();
        $this->assertEquals($decrypted, $glpikey->decryptUsingLegacyKey($encrypted, $key));
    }

    public function testGetWithoutKey()
    {
        vfsStream::setup('glpi', null, ['config' => []]);
        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        $glpikey->get();
        $this->hasPhpLogRecordThatContains(
            'You must create a security key, see security:change_key command.',
            LogLevel::WARNING
        );
    }

    public function testGetUnreadableKey()
    {
        $structure = vfsStream::setup('glpi', null, ['config' => ['glpicrypt.key' => 'unreadable file']]);
        $structure->getChild('config/glpicrypt.key')->chmod(0o222);

        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        $glpikey->get();
        $this->hasPhpLogRecordThatContains(
            'Unable to get security key file contents.',
            LogLevel::WARNING
        );
    }

    public function testGetInvalidKey()
    {
        vfsStream::setup('glpi', null, ['config' => ['glpicrypt.key' => 'not a valid key']]);

        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        $glpikey->get();
        $this->hasPhpLogRecordThatContains(
            'Invalid security key file contents.',
            LogLevel::WARNING
        );
    }

    public function testGet()
    {
        $valid_key = 'abcdefghijklmnopqrstuvwxyz123456';
        vfsStream::setup('glpi', null, ['config' => ['glpicrypt.key' => $valid_key]]);

        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        $key = $glpikey->get();

        $this->assertEquals($valid_key, $key);
    }

    public function testGetLegacyKeyDefault()
    {
        vfsStream::setup('glpi', null, ['config' => []]);

        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        $key = $glpikey->getLegacyKey();

        $this->assertEquals("GLPI£i'snarss'ç", $key);
    }

    public function testGetLegacyKeyCustom()
    {
        vfsStream::setup('glpi', null, ['config' => ['glpi.key' => 'mylegacykey']]);

        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        $key = $glpikey->getLegacyKey();

        $this->assertEquals('mylegacykey', $key);
    }

    public function testGetLegacyKeyUnreadable()
    {
        $structure = vfsStream::setup('glpi', null, ['config' => ['glpi.key' => 'unreadable file']]);
        $structure->getChild('config/glpi.key')->chmod(0o222);

        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        $glpikey->getLegacyKey();
        $this->hasPhpLogRecordThatContains(
            'Unable to get security legacy key file contents.',
            LogLevel::WARNING
        );
    }

    public function testGenerateWithoutPreviousKey()
    {
        vfsStream::setup('glpi', null, ['config' => []]);

        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        $success = $glpikey->generate();
        $this->assertTrue($success);

        // key file exists and key can be retrieved
        $this->assertTrue(file_exists(vfsStream::url('glpi/config/glpicrypt.key')));
        $this->assertNotEmpty($glpikey->get());
    }

    public function testGenerateWithExistingPreviousKey()
    {
        $structure = vfsStream::setup('glpi', null, ['config' => []]);
        vfsStream::copyFromFileSystem(GLPI_CONFIG_DIR, $structure->getChild('config'));
        $structure->getChild('config/glpicrypt.key')->chmod(0o666);

        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        $success = $glpikey->generate();
        $this->assertTrue($success);

        // key file exists and key can be retrieved
        $this->assertTrue(file_exists(vfsStream::url('glpi/config/glpicrypt.key')));
        $this->assertNotEmpty($glpikey->get());

        // check that decrypted value of _local_ldap.rootdn_passwd is correct
        $ldap = getItemByTypeName('AuthLDAP', '_local_ldap');
        $this->assertEquals('insecure', $glpikey->decrypt($ldap->fields['rootdn_passwd']));
    }

    public function testGenerateFailureWithUnwritableConfigDir()
    {
        // Unwritable dir
        $structure = vfsStream::setup('glpi', null, ['config' => []]);
        $structure->getChild('config')->chmod(0o555);


        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        $this->assertFalse($glpikey->generate());
        $this->hasPhpLogRecordThatContains(
            'Security key file path (vfs://glpi/config/glpicrypt.key) is not writable.',
            LogLevel::WARNING
        );
    }

    public function testGenerateFailureWithUnwritableConfigFile()
    {
        // Unwritable key file
        $structure = vfsStream::setup('glpi', null, ['config' => ['glpicrypt.key' => 'previouskey']]);
        $structure->getChild('config/glpicrypt.key')->chmod(0o444);

        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        $this->assertFalse($glpikey->generate());
        $this->hasPhpLogRecordThatContains(
            'Security key file path (vfs://glpi/config/glpicrypt.key) is not writable.',
            LogLevel::WARNING
        );
    }

    public function testGenerateFailureWithUnreadableKey()
    {
        $structure = vfsStream::setup('glpi', null, ['config' => ['glpicrypt.key' => 'unreadable file']]);
        $structure->getChild('config/glpicrypt.key')->chmod(0o222);

        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        $this->assertFalse($glpikey->generate());
        $this->hasPhpLogRecordThatContains(
            'Unable to get security key file contents.',
            LogLevel::WARNING
        );
    }

    public function testGenerateFailureWithInvalidPreviousKey()
    {
        vfsStream::setup('glpi', null, ['config' => ['glpicrypt.key' => 'not a valid key']]);

        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        $this->assertFalse($glpikey->generate());
        $this->hasPhpLogRecordThatContains(
            'Invalid security key file contents.',
            LogLevel::WARNING
        );
    }

    public function testEncryptDecryptUsingDefaultKey()
    {
        $structure = vfsStream::setup('glpi', null, ['config' => []]);
        vfsStream::copyFromFileSystem(GLPI_CONFIG_DIR, $structure->getChild('config'));

        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        // Short string with no special chars
        $string = 'MyP4ssw0rD';
        $encrypted = $glpikey->encrypt($string);
        $decrypted = $glpikey->decrypt($encrypted);
        $this->assertEquals($string, $decrypted);

        // Empty string
        $string = '';
        $encrypted = $glpikey->encrypt($string);
        $decrypted = $glpikey->decrypt($encrypted);
        $this->assertEquals($string, $decrypted);

        // Complex string with special chars
        $string = 'This is a string I want to crypt, with some unusual chars like %, \', @, and so on!';
        $encrypted = $glpikey->encrypt($string);
        $decrypted = $glpikey->decrypt($encrypted);
        $this->assertEquals($string, $decrypted);
    }

    public static function encryptDecryptProvider()
    {
        $key = hex2bin('a72f621a029175008055f103fb977fe185fecdb248e42c18751afb391278d4b6');

        yield [
            'string'    => 'MyP4ssw0rD',
            'encrypted' => 'LO/9MItyVPEV1a/fn9kMehifov25XPOEqQl69GmnWFlcPG7zWk5v5CrSPRtVHd5Oy1Y=',
            'key'       => $key,
        ];

        yield [
            'string'    => 'This is a string I want to crypt, with some unusual chars like %, \', @, and so on!',
            'encrypted' => 'lBaMoLV3u0DOZS17qDBoO4uVY56WEmYQpUg+F+WfZ8zE3Nt/nQzBajs6VNY5F1CHHKKSaAR5wGdmYfY2MLX4b7KYOBuC/JYeOUnPXvhQTe8uuAdkDxjqmRqRtY2TaNhQBPBz6ul8i+YZRwW3oPe0wssZl2uV0KONNfI=',
            'key'       => $key,
        ];

        yield [
            'string'    => '',
            'encrypted' => 'tBH3MhNfobeT0tdmcYbSNqhll0OTcRSSRajXtSZ980RmzLLgJC3Owg==',
            'key'       => $key,
        ];
    }

    #[DataProvider('encryptDecryptProvider')]
    public function testEncryptUsingSpecificKey(?string $string, ?string $encrypted, ?string $key = null)
    {
        vfsStream::setup('glpi', null, ['config' => []]);

        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        // NONCE produce different result each time
        $this->assertNotEquals($encrypted, $glpikey->encrypt($string, $key));

        // As encryption produces different result each time, we cannot validate encrypted value.
        // So we validate that encryption alters string, and decryption reproduces the initial string.
        $encrypted = $glpikey->encrypt($string, $key);
        $this->assertNotEquals($string, $encrypted);
        $decrypted = $glpikey->decrypt($encrypted, $key);
        $this->assertEquals($string, $decrypted);
    }

    #[DataProvider('encryptDecryptProvider')]
    public function testDecryptUsingSpecificKey(?string $string, ?string $encrypted, ?string $key = null)
    {
        vfsStream::setup('glpi', null, ['config' => []]);

        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        $decrypted = $glpikey->decrypt($encrypted, $key);
        $this->assertEquals($string, $decrypted);
    }

    #[DataProvider('encryptDecryptProvider')]
    public function testDecryptEmptyValue(?string $string, ?string $encrypted, ?string $key = null)
    {
        $structure = vfsStream::setup('glpi', null, ['config' => []]);
        vfsStream::copyFromFileSystem(GLPI_CONFIG_DIR, $structure->getChild('config'));

        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        $this->assertNull($glpikey->decrypt(null));
        $this->assertEmpty($glpikey->decrypt(''));
    }

    public function testDecryptInvalidString()
    {
        $structure = vfsStream::setup('glpi', null, ['config' => []]);
        vfsStream::copyFromFileSystem(GLPI_CONFIG_DIR, $structure->getChild('config'));

        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        $this->assertEmpty($glpikey->decrypt('not a valid value'));
        $this->hasPhpLogRecordThatContains(
            'Unable to extract nonce from string. It may not have been crypted with sodium functions.',
            LogLevel::WARNING
        );
    }

    public function testDecryptUsingBadKey()
    {
        $structure = vfsStream::setup('glpi', null, ['config' => []]);
        vfsStream::copyFromFileSystem(GLPI_CONFIG_DIR, $structure->getChild('config'));

        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        $this->assertEmpty($glpikey->decrypt('CUdPSEgzKroDOwM1F8lbC8WDcQUkGCxIZpdTEpp5W/PLSb70WmkaKP0Q7QY='));
        $this->hasPhpLogRecordThatContains(
            'Unable to decrypt string. It may have been crypted with another key.',
            LogLevel::WARNING
        );
    }

    public function testGetFields()
    {
        vfsStream::setup('glpi', null, ['config' => []]);

        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        global $PLUGIN_HOOKS;
        $hooks_backup = $PLUGIN_HOOKS[Hooks::SECURED_FIELDS] ?? null;

        $PLUGIN_HOOKS[Hooks::SECURED_FIELDS] = [
            'myplugin' => [
                'glpi_plugin_myplugin_remote.key',
                'glpi_plugin_myplugin_remote.secret',
            ],
            'anotherplugin' => [
                'glpi_plugin_anotherplugin_link.pass',
            ],
        ];

        $fields = $glpikey->getFields();

        unset($PLUGIN_HOOKS[Hooks::SECURED_FIELDS]);
        if ($hooks_backup !== null) {
            $PLUGIN_HOOKS[Hooks::SECURED_FIELDS] = $hooks_backup;
        }

        $this->assertEquals(
            [
                'glpi_authldaps.rootdn_passwd',
                'glpi_mailcollectors.passwd',
                'glpi_oauthclients.secret',
                'glpi_snmpcredentials.auth_passphrase',
                'glpi_snmpcredentials.priv_passphrase',
                'glpi_plugin_myplugin_remote.key',
                'glpi_plugin_myplugin_remote.secret',
                'glpi_plugin_anotherplugin_link.pass',
            ],
            $fields
        );
    }

    public function testGetConfigs()
    {
        vfsStream::setup('glpi', null, ['config' => []]);

        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));

        global $PLUGIN_HOOKS;
        $hooks_backup = $PLUGIN_HOOKS[Hooks::SECURED_CONFIGS] ?? null;

        $PLUGIN_HOOKS[Hooks::SECURED_CONFIGS] = [
            'myplugin' => [
                'password',
            ],
            'anotherplugin' => [
                'secret',
            ],
        ];

        $configs = $glpikey->getConfigs();

        unset($PLUGIN_HOOKS[Hooks::SECURED_CONFIGS]);
        if ($hooks_backup !== null) {
            $PLUGIN_HOOKS[Hooks::SECURED_CONFIGS] = $hooks_backup;
        }

        $this->assertEquals(
            [
                'core' => [
                    'glpinetwork_registration_key',
                    'proxy_passwd',
                    'smtp_passwd',
                    'smtp_oauth_client_secret',
                    'smtp_oauth_refresh_token',
                ],
                'plugin:myplugin' => [
                    'password',
                ],
                'plugin:anotherplugin' => [
                    'secret',
                ],
            ],
            $configs
        );
    }

    public function testIsConfigSecured()
    {
        vfsStream::setup('glpi', null, ['config' => []]);

        $glpikey = new \GLPIKey(vfsStream::url('glpi/config'));
        global $PLUGIN_HOOKS;
        $hooks_backup = $PLUGIN_HOOKS[Hooks::SECURED_CONFIGS] ?? null;

        $PLUGIN_HOOKS[Hooks::SECURED_CONFIGS] = [
            'myplugin' => [
                'password',
            ],
        ];

        $is_url_base_secured = $glpikey->isConfigSecured('core', 'url_base');
        $is_smtp_passwd_secured = $glpikey->isConfigSecured('core', 'smtp_passwd');
        $is_myplugin_password_secured = $glpikey->isConfigSecured('plugin:myplugin', 'password');
        $is_myplugin_href_secured = $glpikey->isConfigSecured('plugin:myplugin', 'href');
        $is_someplugin_conf_secured = $glpikey->isConfigSecured('plugin:someplugin', 'conf');

        unset($PLUGIN_HOOKS[Hooks::SECURED_CONFIGS]);
        if ($hooks_backup !== null) {
            $PLUGIN_HOOKS[Hooks::SECURED_CONFIGS] = $hooks_backup;
        }

        $this->assertFalse($is_url_base_secured);
        $this->assertTrue($is_smtp_passwd_secured);
        $this->assertTrue($is_myplugin_password_secured);
        $this->assertFalse($is_myplugin_href_secured);
        $this->assertFalse($is_someplugin_conf_secured);
    }
}
