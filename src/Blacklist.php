<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

/**
 * Blacklist Class
 *
 * @since 0.84
 **/
class Blacklist extends CommonDropdown
{
   // From CommonDBTM
    public $dohistory = true;

    public static $rightname = 'config';

    public $can_be_translated = false;

    /**
     * Loaded blacklists.
     * Used for caching purposes.
     * @var array
     */
    private $blacklists;

    const IP             = 1;
    const MAC            = 2;
    const SERIAL         = 3;
    const UUID           = 4;
    const EMAIL          = 5;
    const MODEL          = 6;
    const NAME           = 7;
    const MANUFACTURER   = 8;

    public function maxActionsCount()
    {
        return 0;
    }

    public static function canCreate()
    {
        return static::canUpdate();
    }


    /**
     * @since 0.85
     */
    public static function canPurge()
    {
        return static::canUpdate();
    }


    public function getAdditionalFields()
    {

        return [['name'  => 'value',
            'label' => __('Value'),
            'type'  => 'text',
            'list'  => true
        ],
            ['name'  => 'type',
                'label' => _n('Type', 'Types', 1),
                'type'  => '',
                'list'  => true
            ]
        ];
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Blacklist', 'Blacklists', $nb);
    }


    /**
     * Get search function for the class
     *
     * @return array of search option
     */
    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '11',
            'table'              => $this->getTable(),
            'field'              => 'value',
            'name'               => __('Value'),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => $this->getTable(),
            'field'              => 'type',
            'name'               => _n('Type', 'Types', 1),
            'searchtype'         => ['equals', 'notequals'],
            'datatype'           => 'specific'
        ];

        return $tab;
    }


    public function prepareInputForAdd($input)
    {

        if (
            (!isset($input['name']) || empty($input['name']))
            && isset($input['value'])
        ) {
            $input['name'] = $input['value'];
        }
        return $input;
    }


    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {

        if ($field['name'] == 'type') {
            self::dropdownType($field['name'], [
                'value' => $this->fields['type'],
                'width' => '100%',
            ]);
        }
    }


    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'type':
                $types = self::getTypes();
                return $types[$values[$field]];
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        switch ($field) {
            case 'type':
                $options['value']  = $values[$field];
                return self::dropdownType($name, $options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    /**
     * Dropdown of blacklist types
     *
     * @param string $name   select name
     * @param array  $options possible options:
     *    - value       : integer / preselected value (default 0)
     *    - toadd       : array / array of specific values to add at the beginning
     *    - on_change   : string / value to transmit to "onChange"
     *    - display
     *
     * @return string ID of the select
     **/
    public static function dropdownType($name, $options = [])
    {

        $params = [
            'value'     => 0,
            'toadd'     => [],
            'on_change' => '',
            'display'   => true,
        ];

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        $items = [];
        if (count($params['toadd']) > 0) {
            $items = $params['toadd'];
        }

        $items += self::getTypes();

        return Dropdown::showFromArray($name, $items, $params);
    }


    /**
     * Get blacklist types
     *
     * @return array Array of types
     **/
    public static function getTypes()
    {

        $options = [
            self::IP               => __('IP'),
            self::MAC              => __('MAC'),
            self::SERIAL           => __('Serial number'),
            self::UUID             => __('UUID'),
            self::EMAIL            => _n('Email', 'Emails', 1),
         //'Windows product key' => 'winProdKey',
            self::MODEL            => _n('Model', 'Models', 1),
            self::NAME             => __('Name'),
            self::MANUFACTURER     => _n('Manufacturer', 'Manufacturers', 1)
        ];

        return $options;
    }

    public function getBlacklists(): array
    {
        return $this->blacklists ?? $this->loadBlacklists();
    }

    private function loadBlacklists()
    {
        global $DB;

        $iterator = $DB->request(['FROM' => self::getTable()]);

        $blacklists = array_fill_keys(array_keys(self::getTypes()), []);
        foreach ($iterator as $row) {
            $blacklists[$row['type']][] = $row;
        }

        $this->blacklists = $blacklists;
        return $this->blacklists;
    }

    /**
     * Get blacklisted items for a specific type
     *
     * @param string $type type to get (see constants)
     *
     * @return array Array of blacklisted items
     **/
    public static function getBlacklistedItems($type)
    {

        $data = getAllDataFromTable('glpi_blacklists', ['type' => $type]);
        $items = [];
        if (count($data)) {
            foreach ($data as $val) {
                $items[] = $val['value'];
            }
        }
        return $items;
    }

    /**
     * Get blacklisted IP
     *
     * @return array Array of blacklisted IP
     **/
    public static function getIPs()
    {
        return self::getBlacklistedItems(self::IP);
    }


    /**
     * Get blacklisted MAC
     *
     * @return array Array of blacklisted MAC
     **/
    public static function getMACs()
    {
        return self::getBlacklistedItems(self::MAC);
    }


    /**
     * Get blacklisted Serial number
     *
     * @return array Array of blacklisted Serial number
     **/
    public static function getSerialNumbers()
    {
        return self::getBlacklistedItems(self::SERIAL);
    }


    /**
     * Get blacklisted UUID
     *
     * @return array Array of blacklisted UUID
     **/
    public static function getUUIDs()
    {
        return self::getBlacklistedItems(self::UUID);
    }


    /**
     * Get blacklisted Emails
     *
     * @return array Array of blacklisted Emails
     **/
    public static function getEmails()
    {
        return self::getBlacklistedItems(self::EMAIL);
    }

    public static function getDefaults(): array
    {
        $defaults = [];

        $serials = [
            'N/A',
            '(null string)',
            'INVALID',
            'SYS-1234567890',
            'SYS-9876543210',
            'SN-12345',
            'SN-1234567890',
            '1111111111',
            '1111111',
            '1',
            '0123456789',
            '12345',
            '123456',
            '1234567',
            '12345678',
            '123456789',
            '1234567890',
            '123456789000',
            '12345678901234567',
            '0000000000',
            '000000000',
            '00000000',
            '0000000',
            'NNNNNNN',
            'xxxxxxxxxxx',
            'EVAL',
            'IATPASS',
            'none',
            'To Be Filled By O.E.M.',
            'Tulip Computers',
            'Serial Number xxxxxx',
            'SN-123456fvgv3i0b8o5n6n7k',
            'Unknow',
            'System Serial Number',
            'MB-1234567890',
            '0',
            'empty',
            'Not Specified',
            'OEM_Serial',
            'SystemSerialNumb'
        ];
        foreach ($serials as $serial) {
            $defaults[self::SERIAL][] = [
                'name' => 'invalid serial',
                'value' => $serial
            ];
        }

        $uuids = [
            'FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF',
            '03000200-0400-0500-0006-000700080009',
            '6AB5B300-538D-1014-9FB5-B0684D007B53',
            '01010101-0101-0101-0101-010101010101',
            '2'
        ];
        foreach ($uuids as $uuid) {
            $defaults[self::UUID][] = [
                'name' => 'invalid UUID',
                'value' => $uuid
            ];
        }

        $defaults[self::MAC] = [['name' => 'empty MAC', 'value' => '']];
        $macs = [
            '20:41:53:59:4e:ff', //RACK Async Adaptater
            '02:00:4e:43:50:49', //NCP Secure Client Virtual NDIS6 Adapter
            'e2:e6:16:20:0a:35', //Fireware
            'd2:0a:2d:a0:04:be', //Fireware
            '00:a0:c6:00:00:00', //Qualcomm Gobi 2000 HS-USB Mobile Broadband Device 9225
            'd2:6b:25:2f:2c:e7', //Fireware
            '33:50:6f:45:30:30', //Miniport WAN (PPPOE)
            '0a:00:27:00:00:00', //VirtualBox
            '00:50:56:C0:00:01', //vmnet
            '00:50:56:C0:00:08', //vmnet
            '02:80:37:EC:02:00', //Dell Wireless 5530 HSPA Mobile Broadband Minicard NetworkAdapter mac address
            '50:50:54:50:30:30',
            '24:b6:20:52:41:53',
            '00:50:56:C0:00:02',
            '/00:50:56:C0:[0-9a-f]+:[0-9a-f]+/i',//VMware MAC address
            'FE:FF:FF:FF:FF:FF',
            '00:00:00:00:00:00',
            '00:0b:ca:fe:00:00'
        ];
        foreach ($macs as $mac) {
            $defaults[self::MAC][] = [
                'name' => 'invalid MAC',
                'value' => $mac
            ];
        }

        $models = [
            'Unknow',
            'To Be Filled By O.E.M.',
            '*',
            'System Product Name',
            'Product Name',
            'System Name',
            'All Series'
        ];
        foreach ($models as $model) {
            $defaults[self::MODEL][] = [
                'name' => $model,
                'value' => $model
            ];
        }

        $defaults[self::MANUFACTURER] = [['name' => 'System manufacturer', 'value' => 'System manufacturer']];

        $defaults[self::IP] = [
            [
                'name' => 'empty IP',
                'value' => ''
            ], [
                'name' => 'zero IP',
                'value' => '0.0.0.0'
            ], [
                'name' => 'localhost',
                'value' => '127.0.0.1'
            ]
        ];

        return $defaults;
    }

    public function process(int $type, string $value)
    {
        $criteria = $this->getBlacklists()[$type] ?? [];

        foreach ($criteria as $criterion) {
            if (preg_match('|/.+/(a-zZ-a)?|', $criterion['value']) && preg_match($criterion['value'], $value)) {
                return '';
            } else if (strcasecmp($value, $criterion['value']) === 0) {
                return '';
            }
        }

        return $value;
    }

    public function processBlackList(&$value)
    {

        if (
            property_exists($value, 'manufacturers_id')
            && !is_numeric($value->manufacturers_id)
            && '' == $this->process(self::MANUFACTURER, $value->manufacturers_id)
        ) {
            unset($value->manufacturers_id);
        }

        if (
            property_exists($value, 'uuid')
            && '' == $this->process(self::UUID, $value->uuid)
        ) {
            unset($value->uuid);
        }

        if (
            property_exists($value, 'mac')
            && '' == $this->process(self::MAC, $value->mac)
        ) {
            unset($value->mac);
        }

        if (
            property_exists($value, 'serial')
            && '' == $this->process(self::SERIAL, $value->serial)
        ) {
            unset($value->serial);
        }

        if (property_exists($value, 'ipaddress') || property_exists($value, 'ip')) {
            $property = property_exists($value, 'ipaddress') ? 'ipaddress' : 'ip';
            $ips = &$value->$property;
            if (is_array($ips)) {
                foreach ($ips as $k => $ip) {
                    if ('' == $this->process(self::IP, $ip)) {
                        unset($ips[$k]);
                    }
                }
            } else if ('' == $this->process(self::IP, $ips)) {
                unset($value->$property);
            }
        }

        foreach ($value as $key => $val) {
            if (
                !is_numeric($value->$key)
                && preg_match('/^.+models_id/', $key)
                && '' == $this->process(self::MODEL, $value->$key)
            ) {
                unset($value->$key);
            }
        }
    }

    public static function getIcon()
    {
        return "fas fa-ban";
    }
}
