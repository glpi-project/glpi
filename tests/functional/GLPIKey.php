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

use Glpi\Plugin\Hooks;
use org\bovigo\vfs\vfsStream;

/* Test for inc/glpikey.class.php */

class GLPIKey extends \DbTestCase
{
    protected function getExpectedKeyPathProvider()
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

    /**
     * @dataProvider getExpectedKeyPathProvider
     */
    public function testGetExpectedKeyPath($glpi_version, $expected_path)
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->variable($this->testedInstance->getExpectedKeyPath($glpi_version))->isEqualTo($expected_path);
    }

    public function testKeyExists()
    {
        $structure = vfsStream::setup('glpi', null, ['config' => []]);

        $this->newTestedInstance(vfsStream::url('glpi/config'));

        $this->boolean($this->testedInstance->keyExists())->isFalse();

        vfsStream::create(['glpicrypt.key' => 'keyfilecontents'], $structure->getChild('config'));
        $this->boolean($this->testedInstance->keyExists())->isTrue();
    }

    protected function legacyEncryptedProvider()
    {
       // basic string, default key
        yield [
            'encrypted' => 'G6y/xA==',
            'decrypted' => 'test',
            'key'       => null,
        ];

       // string with special chars, default key
        yield [
            'encrypted' => 'IYx+rrgV1IqUtqSD1repTebaf4c=',
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
            'encrypted' => 'n7iLkqvGhVeXsoFVwqWEVkimkW8=',
            'decrypted' => 'zE2^oS1!mC6"dD6&',
            'key'       => 'sY4<sT6*oK3^aN0%',
        ];
    }

    /**
     * @dataProvider legacyEncryptedProvider
     */
    public function testDecryptUsingLegacyKey(string $encrypted, string $decrypted, ?string $key)
    {
        $this
         ->if($this->newTestedInstance)
         ->then
            ->string($this->testedInstance->decryptUsingLegacyKey($encrypted, $key))->isEqualTo($decrypted);
    }

    public function testGetWithoutKey()
    {
        vfsStream::setup('glpi', null, ['config' => []]);

        $this->newTestedInstance(vfsStream::url('glpi/config'));

        $this->when(
            function () {
                $this->testedInstance->get();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('You must create a security key, see security:change_key command.')
         ->exists();
    }

    public function testGetUnreadableKey()
    {
        $structure = vfsStream::setup('glpi', null, ['config' => ['glpicrypt.key' => 'unreadable file']]);
        $structure->getChild('config/glpicrypt.key')->chmod(0222);

        $this->newTestedInstance(vfsStream::url('glpi/config'));

        $this->when(
            function () {
                $this->testedInstance->get();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Unable to get security key file contents.')
         ->exists();
    }

    public function testGetInvalidKey()
    {
        vfsStream::setup('glpi', null, ['config' => ['glpicrypt.key' => 'not a valid key']]);

        $this->newTestedInstance(vfsStream::url('glpi/config'));

        $this->when(
            function () {
                $this->testedInstance->get();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Invalid security key file contents.')
         ->exists();
    }

    public function testGet()
    {
        $valid_key = 'abcdefghijklmnopqrstuvwxyz123456';
        vfsStream::setup('glpi', null, ['config' => ['glpicrypt.key' => $valid_key]]);

        $this->newTestedInstance(vfsStream::url('glpi/config'));

        $key = $this->testedInstance->get();

        $this->string($key)->isEqualTo($valid_key);
    }

    public function testGetLegacyKeyDefault()
    {
        vfsStream::setup('glpi', null, ['config' => []]);

        $this->newTestedInstance(vfsStream::url('glpi/config'));

        $key = $this->testedInstance->getLegacyKey();

        $this->string($key)->isEqualTo("GLPI£i'snarss'ç");
    }

    public function testGetLegacyKeyCustom()
    {
        vfsStream::setup('glpi', null, ['config' => ['glpi.key' => 'mylegacykey']]);

        $this->newTestedInstance(vfsStream::url('glpi/config'));

        $key = $this->testedInstance->getLegacyKey();

        $this->string($key)->isEqualTo('mylegacykey');
    }

    public function testGetLegacyKeyUnreadable()
    {
        $structure = vfsStream::setup('glpi', null, ['config' => ['glpi.key' => 'unreadable file']]);
        $structure->getChild('config/glpi.key')->chmod(0222);

        $this->newTestedInstance(vfsStream::url('glpi/config'));

        $this->when(
            function () {
                $this->testedInstance->getLegacyKey();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Unable to get security legacy key file contents.')
         ->exists();
    }

    public function testGenerateWithoutPreviousKey()
    {
        vfsStream::setup('glpi', null, ['config' => []]);

        $this->newTestedInstance(vfsStream::url('glpi/config'));

        $success = $this->testedInstance->generate();
        $this->boolean($success)->isTrue();

       // key file exists and key can be retrieved
        $this->boolean(file_exists(vfsStream::url('glpi/config/glpicrypt.key')))->isTrue();
        $this->string($this->testedInstance->get())->isNotEmpty();
    }

    public function testGenerateWithExistingPreviousKey()
    {
        $structure = vfsStream::setup('glpi', null, ['config' => []]);
        vfsStream::copyFromFileSystem(GLPI_CONFIG_DIR, $structure->getChild('config'));

        $this->newTestedInstance(vfsStream::url('glpi/config'));

        $success = $this->testedInstance->generate();
        $this->boolean($success)->isTrue();

       // key file exists and key can be retrieved
        $this->boolean(file_exists(vfsStream::url('glpi/config/glpicrypt.key')))->isTrue();
        $this->string($this->testedInstance->get())->isNotEmpty();

       // check that decrypted value of _local_ldap.rootdn_passwd is correct
        $ldap = getItemByTypeName('AuthLDAP', '_local_ldap');
        $this->string($this->testedInstance->decrypt($ldap->fields['rootdn_passwd']))->isEqualTo('insecure');
    }

    public function testGenerateFailureWithUnwritableConfigDir()
    {
       // Unwritable dir
        $structure = vfsStream::setup('glpi', null, ['config' => []]);
        $structure->getChild('config')->chmod(0555);

        $this->newTestedInstance(vfsStream::url('glpi/config'));

        $result = null;
        $this->when(
            function () use (&$result) {
                $result = $this->testedInstance->generate();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Security key file path (vfs://glpi/config/glpicrypt.key) is not writable.')
         ->exists();
        $this->boolean($result)->isFalse();

       // Unwritable key file
        $structure = vfsStream::setup('glpi', null, ['config' => ['glpicrypt.key' => 'previouskey']]);
        $structure->getChild('config/glpicrypt.key')->chmod(0444);

        $result = null;
        $this->when(
            function () use (&$result) {
                $result = $this->testedInstance->generate();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Security key file path (vfs://glpi/config/glpicrypt.key) is not writable.')
         ->exists();
        $this->boolean($result)->isFalse();
    }

    public function testGenerateFailureWithUnreadableKey()
    {
        $structure = vfsStream::setup('glpi', null, ['config' => ['glpicrypt.key' => 'unreadable file']]);
        $structure->getChild('config/glpicrypt.key')->chmod(0222);

        $this->newTestedInstance(vfsStream::url('glpi/config'));

        $result = null;
        $this->when(
            function () use (&$result) {
                $result = $this->testedInstance->generate();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Unable to get security key file contents.')
         ->exists();
        $this->boolean($result)->isFalse();
    }

    public function testGenerateFailureWithInvalidPreviousKey()
    {
        vfsStream::setup('glpi', null, ['config' => ['glpicrypt.key' => 'not a valid key']]);

        $this->newTestedInstance(vfsStream::url('glpi/config'));

        $result = null;
        $this->when(
            function () use (&$result) {
                $result = $this->testedInstance->generate();
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Invalid security key file contents.')
         ->exists();
        $this->boolean($result)->isFalse();
    }

    public function testEncryptDecryptUsingDefaultKey()
    {
        $structure = vfsStream::setup('glpi', null, ['config' => []]);
        vfsStream::copyFromFileSystem(GLPI_CONFIG_DIR, $structure->getChild('config'));

        $this->newTestedInstance(vfsStream::url('glpi/config'));

       // Short string with no special chars
        $string = 'MyP4ssw0rD';
        $encrypted = $this->testedInstance->encrypt($string);
        $decrypted = $this->testedInstance->decrypt($encrypted);
        $this->string($decrypted)->isEqualTo($string);

       // Empty string
        $string = '';
        $encrypted = $this->testedInstance->encrypt($string);
        $decrypted = $this->testedInstance->decrypt($encrypted);
        $this->string($decrypted)->isEqualTo($string);

       // Complex string with special chars
        $string = 'This is a string I want to crypt, with some unusual chars like %, \', @, and so on!';
        $encrypted = $this->testedInstance->encrypt($string);
        $decrypted = $this->testedInstance->decrypt($encrypted);
        $this->string($decrypted)->isEqualTo($string);
    }

    protected function encryptDecryptProvider()
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

    /**
     * @dataProvider encryptDecryptProvider
     */
    public function testEncryptUsingSpecificKey(?string $string, ?string $encrypted, ?string $key = null)
    {
        vfsStream::setup('glpi', null, ['config' => []]);

        $this->newTestedInstance(vfsStream::url('glpi/config'));

       // NONCE produce different result each time
        $this->string($this->testedInstance->encrypt($string, $key))->isNotEqualTo($encrypted);

       // As encryption produces different result each time, we cannot validate encrypted value.
       // So we validates that encryption alters string, and decryption reproduces the initial string.
        $encrypted = $this->testedInstance->encrypt($string, $key);
        $this->string($encrypted)->isNotEqualTo($string);
        $decrypted = $this->testedInstance->decrypt($encrypted, $key);
        $this->string($decrypted)->isEqualTo($string);
    }

    /**
     * @dataProvider encryptDecryptProvider
     */
    public function testDecryptUsingSpecificKey(?string $string, ?string $encrypted, ?string $key = null)
    {
        vfsStream::setup('glpi', null, ['config' => []]);

        $this->newTestedInstance(vfsStream::url('glpi/config'));

        $decrypted = $this->testedInstance->decrypt($encrypted, $key);
        $this->string($decrypted)->isEqualTo($string);
    }

    /**
     * @dataProvider encryptDecryptProvider
     */
    public function testDecryptEmptyValue(?string $string, ?string $encrypted, ?string $key = null)
    {
        $structure = vfsStream::setup('glpi', null, ['config' => []]);
        vfsStream::copyFromFileSystem(GLPI_CONFIG_DIR, $structure->getChild('config'));

        $this->newTestedInstance(vfsStream::url('glpi/config'));

        $this->variable($this->testedInstance->decrypt(null))->isNull();
        $this->string($this->testedInstance->decrypt(''))->isEmpty();
    }

    public function testDecryptInvalidString()
    {
        $structure = vfsStream::setup('glpi', null, ['config' => []]);
        vfsStream::copyFromFileSystem(GLPI_CONFIG_DIR, $structure->getChild('config'));

        $this->newTestedInstance(vfsStream::url('glpi/config'));

        $result = null;

        $this->when(
            function () use (&$result) {
                $result = $this->testedInstance->decrypt('not a valid value');
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Unable to extract nonce from string. It may not have been crypted with sodium functions.')
         ->exists();

        $this->string($result)->isEmpty();
    }

    public function testDecryptUsingBadKey()
    {
        $structure = vfsStream::setup('glpi', null, ['config' => []]);
        vfsStream::copyFromFileSystem(GLPI_CONFIG_DIR, $structure->getChild('config'));

        $this->newTestedInstance(vfsStream::url('glpi/config'));

        $result = null;

        $this->when(
            function () use (&$result) {
               // 'test' string crypted with a valid key used just for that
                $result = $this->testedInstance->decrypt('CUdPSEgzKroDOwM1F8lbC8WDcQUkGCxIZpdTEpp5W/PLSb70WmkaKP0Q7QY=');
            }
        )->error
         ->withType(E_USER_WARNING)
         ->withMessage('Unable to decrypt string. It may have been crypted with another key.')
         ->exists();

        $this->string($result)->isEmpty();
    }

    public function testGetFields()
    {
        vfsStream::setup('glpi', null, ['config' => []]);

        $this->newTestedInstance(vfsStream::url('glpi/config'));

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

        $fields = $this->testedInstance->getFields();

        unset($PLUGIN_HOOKS[Hooks::SECURED_FIELDS]);
        if ($hooks_backup !== null) {
            $PLUGIN_HOOKS[Hooks::SECURED_FIELDS] = $hooks_backup;
        }

        $this->array($fields)->isEqualTo(
            [
                'glpi_authldaps.rootdn_passwd',
                'glpi_mailcollectors.passwd',
                'glpi_snmpcredentials.auth_passphrase',
                'glpi_snmpcredentials.priv_passphrase',
                'glpi_plugin_myplugin_remote.key',
                'glpi_plugin_myplugin_remote.secret',
                'glpi_plugin_anotherplugin_link.pass',
            ]
        );
    }

    public function testGetConfigs()
    {
        vfsStream::setup('glpi', null, ['config' => []]);

        $this->newTestedInstance(vfsStream::url('glpi/config'));

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

        $configs = $this->testedInstance->getConfigs();

        unset($PLUGIN_HOOKS[Hooks::SECURED_CONFIGS]);
        if ($hooks_backup !== null) {
            $PLUGIN_HOOKS[Hooks::SECURED_CONFIGS] = $hooks_backup;
        }

        $this->array($configs)->isEqualTo(
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
            ]
        );
    }

    public function testIsConfigSecured()
    {
        vfsStream::setup('glpi', null, ['config' => []]);

        $this->newTestedInstance(vfsStream::url('glpi/config'));

        global $PLUGIN_HOOKS;
        $hooks_backup = $PLUGIN_HOOKS[Hooks::SECURED_CONFIGS] ?? null;

        $PLUGIN_HOOKS[Hooks::SECURED_CONFIGS] = [
            'myplugin' => [
                'password',
            ],
        ];

        $is_url_base_secured = $this->testedInstance->isConfigSecured('core', 'url_base');
        $is_smtp_passwd_secured = $this->testedInstance->isConfigSecured('core', 'smtp_passwd');
        $is_myplugin_password_secured = $this->testedInstance->isConfigSecured('plugin:myplugin', 'password');
        $is_myplugin_href_secured = $this->testedInstance->isConfigSecured('plugin:myplugin', 'href');
        $is_someplugin_conf_secured = $this->testedInstance->isConfigSecured('plugin:someplugin', 'conf');

        unset($PLUGIN_HOOKS[Hooks::SECURED_CONFIGS]);
        if ($hooks_backup !== null) {
            $PLUGIN_HOOKS[Hooks::SECURED_CONFIGS] = $hooks_backup;
        }

        $this->boolean($is_url_base_secured)->isFalse();
        $this->boolean($is_smtp_passwd_secured)->isTrue();
        $this->boolean($is_myplugin_password_secured)->isTrue();
        $this->boolean($is_myplugin_href_secured)->isFalse();
        $this->boolean($is_someplugin_conf_secured)->isFalse();
    }
}
