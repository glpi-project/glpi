<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Inventory\Inventory;

/**
 * SNMP credentials
 */
class SNMPCredential extends CommonDBTM
{
    // From CommonDBTM
    public $dohistory                   = true;
    public static $rightname = 'snmpcredential';

    public static function getTypeName($nb = 0)
    {
        return _n('SNMP credential', 'SNMP credentials', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['admin', Inventory::class, self::class];
    }

    public static function rawSearchOptionsToAdd()
    {
        $tab = [];

        $tab[] = [
            'id'                => 'snmpcredential',
            'name'              => SNMPCredential::getTypeName(0),
        ];

        $tab[] = [
            'id'                => '108',
            'table'             => 'glpi_snmpcredentials',
            'field'             => 'name',
            'name'              => __('Name'),
            'datatype'          => 'dropdown',
            'massiveaction'     => false,
        ];

        $tab[] = [
            'id'                => '109',
            'table'             => 'glpi_snmpcredentials',
            'field'             => 'community',
            'name'              => __('Community'),
            'datatype'          => 'string',
            'massiveaction'     => false,
        ];

        return $tab;
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'            => '2',
            'table'         => $this->getTable(),
            'field'         => 'community',
            'name'          => __('Community'),
            'datatype'      => 'string',
            'massiveaction' => false,
        ];

        return $tab;
    }

    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('components/form/snmpcredential.html.twig', [
            'item'   => $this,
            'params' => $options,
        ]);

        return true;
    }

    /**
     * Real version of SNMP
     *
     * @return string
     */
    public function getRealVersion(): string
    {
        switch ($this->fields['snmpversion']) {
            case 1:
            case 3:
                return (string) $this->fields['snmpversion'];
            case 2:
                return '2c';
            default:
                return '';
        }
    }

    /**
     * Get SNMP authentication protocol
     *
     * @return string
     */
    public function getAuthProtocol(): string
    {
        switch ($this->fields['authentication']) {
            case 1:
                return 'MD5';
            case 2:
                return 'SHA';
            case 3:
                return 'SHA224';
            case 4:
                return 'SHA256';
            case 5:
                return 'SHA384';
            case 6:
                return 'SHA512';
            default:
                return '';
        }
    }

    /**
     * Get SNMP encryption protocol
     *
     * @return string
     */
    public function getEncryption(): string
    {
        switch ($this->fields['encryption']) {
            case 1:
                return 'DES';
            case 2:
                return 'AES';
            case 3:
                return '3DES';
            case 4:
                return 'AES192C';
            case 5:
                return 'AES256C';
            default:
                return '';
        }
    }

    protected function prepareInputs(array $input): array
    {
        $key = new GLPIKey();
        // Handle setting passwords
        if (isset($input['auth_passphrase']) && !empty($input['auth_passphrase'])) {
            $input['auth_passphrase'] = $key->encrypt($input['auth_passphrase']);
        } else {
            unset($input['auth_passphrase']);
        }
        if (isset($input['priv_passphrase']) && !empty($input['priv_passphrase'])) {
            $input['priv_passphrase'] = $key->encrypt($input['priv_passphrase']);
        } else {
            unset($input['priv_passphrase']);
        }

        // Handle unsetting passwords
        if (isset($input['_blank_auth_passphrase'])) {
            $input['auth_passphrase'] = 'NULL';
        }
        if (isset($input['_blank_priv_passphrase'])) {
            $input['priv_passphrase'] = 'NULL';
        }

        return $input;
    }

    private function checkRequiredFields($input): bool
    {
        // Require a snmpversion
        if (!isset($input['snmpversion']) || $input['snmpversion'] == '0') {
            Session::addMessageAfterRedirect(__s('You must select an SNMP version'), false, ERROR);
            return false;
        }

        // Require username if using version 3
        if ($input['snmpversion'] == 3) {
            if (empty($input['username'])) {
                Session::addMessageAfterRedirect(__s('You must enter a username'), false, ERROR);
                return false;
            }
        }

        return true;
    }

    public function prepareInputForAdd($input)
    {
        $input = parent::prepareInputForAdd($input);
        if (!$this->checkRequiredFields($input)) {
            return false;
        }
        return $this->prepareInputs($input);
    }

    public function prepareInputForUpdate($input)
    {
        $input = parent::prepareInputForUpdate($input);
        if (!$this->checkRequiredFields($input)) {
            return false;
        }
        return $this->prepareInputs($input);
    }

    public static function getIcon()
    {
        return "ti ti-key";
    }
}
