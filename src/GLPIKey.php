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

use Glpi\Plugin\Hooks;
use Glpi\Toolbox\Sanitizer;

/**
 *  GLPI security key
 **/
class GLPIKey
{
    /**
     * Key file path.
     *
     * @var string
     */
    private $keyfile;

    /**
     * Legacy key file path.
     *
     * @var string
     */
    private $legacykeyfile;

    /**
     * List of crypted DB fields.
     *
     * @var array
     */
    protected $fields = [
        'glpi_authldaps.rootdn_passwd',
        'glpi_mailcollectors.passwd',
        'glpi_snmpcredentials.auth_passphrase',
        'glpi_snmpcredentials.priv_passphrase',
    ];

    /**
     * List of crypted configuration values.
     * Each key corresponds to a configuration context, and contains list of configs names.
     *
     * @var array
     */
    protected $configs = [
        'core'   => [
            'glpinetwork_registration_key',
            'proxy_passwd',
            'smtp_passwd',
            'smtp_oauth_client_secret',
            'smtp_oauth_refresh_token',
        ]
    ];

    public function __construct(string $config_dir = GLPI_CONFIG_DIR)
    {
        $this->keyfile = $config_dir . '/glpicrypt.key';
        $this->legacykeyfile = $config_dir . '/glpi.key';
    }

    /**
     * Returns expected key path for given GLPI version.
     * Will return null for GLPI versions that was not yet handling a custom security key.
     *
     * @param string $glpi_version
     *
     * @return string|null
     */
    public function getExpectedKeyPath(string $glpi_version): ?string
    {
        if (version_compare($glpi_version, '9.4.6', '<')) {
            return null;
        } else if (version_compare($glpi_version, '9.5.x', '<')) {
            return $this->legacykeyfile;
        } else {
            return $this->keyfile;
        }
    }

    /**
     * Check if GLPI security key used for decryptable passwords exists
     *
     * @return string
     */
    public function keyExists()
    {
        return file_exists($this->keyfile);
    }

    /**
     * Get GLPI security key used for decryptable passwords
     *
     * @return string|null
     */
    public function get(): ?string
    {
        if (!file_exists($this->keyfile)) {
            trigger_error('You must create a security key, see security:change_key command.', E_USER_WARNING);
            return null;
        }
        if (!is_readable($this->keyfile) || ($key = file_get_contents($this->keyfile)) === false) {
            trigger_error('Unable to get security key file contents.', E_USER_WARNING);
            return null;
        }
        if (strlen($key) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES) {
            trigger_error('Invalid security key file contents.', E_USER_WARNING);
            return null;
        }
        return $key;
    }

    /**
     * Get GLPI security legacy key that was used for decryptable passwords.
     * Usage of this key should only be used during migration from GLPI < 9.5 to GLPI >= 9.5.0.
     *
     * @return string|null
     */
    public function getLegacyKey(): ?string
    {
        if (!file_exists($this->legacykeyfile)) {
            return GLPIKEY;
        }
       //load key from existing config file
        if (!is_readable($this->legacykeyfile) || ($key = file_get_contents($this->legacykeyfile)) === false) {
            trigger_error('Unable to get security legacy key file contents.', E_USER_WARNING);
            return null;
        }
        return $key;
    }

    /**
     * Generate GLPI security key used for decryptable passwords
     * and update values in DB if necessary.
     *
     * @return bool
     */
    public function generate(): bool
    {
        global $DB;

       // Check ability to create/update key file.
        if (
            (file_exists($this->keyfile) && !is_writable($this->keyfile))
            || (!file_exists($this->keyfile) && !is_writable(dirname($this->keyfile)))
        ) {
            trigger_error(sprintf('Security key file path (%s) is not writable.', $this->keyfile), E_USER_WARNING);
            return false;
        }

       // Fetch old key before generating the new one (but only if DB exists and there is something to migrate)
        $previous_key = null;
        if ($DB instanceof DBmysql && $DB->connected) {
            if ($this->keyExists()) {
                $previous_key = $this->get();
                if ($previous_key === null) {
                    // Do not continue if unable to get previous key.
                    // Detailed warning has already been triggered by `get()` method.
                    return false;
                }
            }
        }

        $key = sodium_crypto_aead_chacha20poly1305_ietf_keygen();
        $written_bytes = file_put_contents($this->keyfile, $key);
        if ($written_bytes !== strlen($key)) {
            trigger_error('Unable to write security key file contents.', E_USER_WARNING);
            return false;
        }

        if ($DB instanceof DBmysql && $DB->connected) {
            if (!$this->migrateFieldsInDb($previous_key) || !$this->migrateConfigsInDb($previous_key)) {
                trigger_error('Error during encrypted data update in database.', E_USER_WARNING);
                return false;
            }
        }

        return true;
    }

    /**
     * Get fields
     *
     * @return array
     */
    public function getFields(): array
    {
        global $PLUGIN_HOOKS;

        $fields = $this->fields;
        if (isset($PLUGIN_HOOKS[Hooks::SECURED_FIELDS])) {
            foreach ($PLUGIN_HOOKS[Hooks::SECURED_FIELDS] as $plugfields) {
                $fields = array_merge($fields, $plugfields);
            }
        }

        return $fields;
    }

    /**
     * Get configs
     *
     * @return array
     */
    public function getConfigs(): array
    {
        global $PLUGIN_HOOKS;

        $configs = $this->configs;

        if (isset($PLUGIN_HOOKS[Hooks::SECURED_CONFIGS])) {
            foreach ($PLUGIN_HOOKS[Hooks::SECURED_CONFIGS] as $plugin => $plugconfigs) {
                $configs['plugin:' . $plugin] = $plugconfigs;
            }
        }

        return $configs;
    }

    /**
     * Check if configuration is secured.
     *
     * @param string $context
     * @param string $name
     *
     * @return bool
     */
    public function isConfigSecured(string $context, string $name): bool
    {

        $secured_configs = $this->getConfigs();

        return array_key_exists($context, $secured_configs)
         && in_array($name, $secured_configs[$context]);
    }

    /**
     * Migrate fields in database
     *
     * @param string|null   $sodium_key Previous key. If null, legacy key will be used.
     *
     * @return bool
     */
    protected function migrateFieldsInDb(?string $sodium_key): bool
    {
        global $DB;

        $success = true;

        foreach ($this->getFields() as $field) {
            list($table, $column) = explode('.', $field);

            $iterator = $DB->request([
                'SELECT' => ['id', $column],
                'FROM'   => $table,
                ['NOT' => [$column => null]],
            ]);

            foreach ($iterator as $row) {
                 $value = (string)$row[$column];
                if ($sodium_key !== null) {
                    $pass = $this->encrypt($this->decrypt($value, $sodium_key));
                } else {
                    $pass = $this->encrypt($this->decryptUsingLegacyKey($value));
                }
                $success = $DB->update(
                    $table,
                    [$field  => $pass],
                    ['id'    => $row['id']]
                );

                if (!$success) {
                     break;
                }
            }
        }

        return $success;
    }

    /**
     * Migrate configurations in database
     *
     * @param string|null   $sodium_key Previous key. If null, legacy key will be used.
     *
     * @return bool
     */
    protected function migrateConfigsInDb($sodium_key): bool
    {
        global $DB;

        $success = true;

        foreach ($this->getConfigs() as $context => $names) {
            $iterator = $DB->request([
                'FROM'   => Config::getTable(),
                'WHERE'  => [
                    'context'   => $context,
                    'name'      => $names,
                    ['NOT' => ['value' => null]],
                ]
            ]);

            foreach ($iterator as $row) {
                 $value = (string)$row['value'];
                if ($sodium_key !== null) {
                    $pass = $this->encrypt($this->decrypt($value, $sodium_key));
                } else {
                    $pass = $this->encrypt($this->decryptUsingLegacyKey($value));
                }
                $success = $DB->update(
                    Config::getTable(),
                    ['value' => $pass],
                    ['id'    => $row['id']]
                );

                if (!$success) {
                     break;
                }
            }
        }

        return $success;
    }

    /**
     * Encrypt a string.
     *
     * @param string        $string  String to encrypt.
     * @param string|null   $key     Key to use, fallback to default key if null.
     *
     * @return string
     */
    public function encrypt(string $string, ?string $key = null): string
    {
        if ($key === null) {
            $key = $this->get();
        }

        if ($key === null) {
           // Cannot encrypt string as key reading fails, returns a empty value
           // to ensure sensitive data is not propagated unencrypted.
            return '';
        }

        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES); // NONCE = Number to be used ONCE, for each message
        $encrypted = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $string,
            $nonce,
            $nonce,
            $key
        );
        return base64_encode($nonce . $encrypted);
    }

    /**
     * Descrypt a string.
     *
     * @param string|null   $string  String to decrypt.
     * @param string|null   $key     Key to use, fallback to default key if null.
     *
     * @return string|null
     */
    public function decrypt(?string $string, $key = null): ?string
    {
        if (empty($string)) {
           // Avoid sodium exception for blank content. Just return the null/empty value.
            return $string;
        }

        if ($key === null) {
            $key = $this->get();
        }

        if ($key === null) {
           // Cannot decrypt string as key reading fails, returns encrypted value.
            return $string;
        }

        $string = base64_decode($string);

        $nonce = mb_substr($string, 0, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES, '8bit');
        if (mb_strlen($nonce, '8bit') !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES) {
            trigger_error(
                'Unable to extract nonce from string. It may not have been crypted with sodium functions.',
                E_USER_WARNING
            );
            return '';
        }

        $ciphertext = mb_substr($string, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES, null, '8bit');

        $plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $ciphertext,
            $nonce,
            $nonce,
            $key
        );
        if ($plaintext === false) {
            trigger_error(
                'Unable to decrypt string. It may have been crypted with another key.',
                E_USER_WARNING
            );
            return '';
        }
        return $plaintext;
    }

    /**
     * Decrypt a string using a legacy key.
     * If key is not provided, the default legacy key will be used.
     *
     * @param string $string
     * @param string|null $key
     *
     * @return string
     */
    public function decryptUsingLegacyKey(string $string, ?string $key = null): string
    {

        if ($key === null) {
            $key = $this->getLegacyKey();
        }

        if ($key === null) {
           // Cannot decrypt string as key reading fails, returns encrypted value.
            return $string;
        }

        $result = '';
        $string = base64_decode($string);

        for ($i = 0; $i < strlen($string); $i++) {
            $char    = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key)) - 1, 1);
            $char    = chr(ord($char) - ord($keychar));
            $result .= $char;
        }

        return Sanitizer::unsanitize($result);
    }
}
