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

use Glpi\Application\View\TemplateRenderer;

/**
 * @since 9.1
 */

class APIClient extends CommonDBTM
{
    const DOLOG_DISABLED   = 0;
    const DOLOG_LOGS       = 1;
    const DOLOG_HISTORICAL = 2;

    public static $rightname = 'config';
    protected $displaylist = false;

   // From CommonDBTM
    public $dohistory                   = true;

    public static $undisclosedFields = [
        'app_token'
    ];

    public static function canCreate()
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public static function canPurge()
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public static function getTypeName($nb = 0)
    {
        return _n("API client", "API clients", $nb);
    }

    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong)
           ->addStandardTab('Log', $ong, $options);

        return $ong;
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => self::GetTypeName()
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'is_active',
            'name'               => __('Active'),
            'datatype'           => 'bool'
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'dolog_method',
            'name'               => __('Log connections'),
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => 'filter',
            'name'               => __('Filter access')
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'ipv4_range_start',
            'name'               => __('IPv4 address range') . " - " . __("Start"),
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'ipv4_range_end',
            'name'               => __('IPv4 address range') . " - " . __("End"),
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'ipv6',
            'name'               => __('IPv6 address'),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'app_token',
            'name'               => __('Application token'),
            'massiveaction'      => false,
            'datatype'           => 'text',
        ];

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        switch ($field) {
            case 'dolog_method':
                $methods = self::getLogMethod();
                return $methods[$values[$field]];

            case 'ipv4_range_start':
            case 'ipv4_range_end':
                if (empty($values[$field])) {
                    return '';
                }
                return long2ip((int)$values[$field]);
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    /**
     * Show form
     *
     * @param integer $ID      Item ID
     * @param array   $options Options
     *
     * @return void
     */
    public function showForm($ID, $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('pages/setup/apiclient.html.twig', [
            'item'   => $this,
            'params' => $options,
            'log_methods' => self::getLogMethod()
        ]);
        return true;
    }

    public function prepareInputForAdd($input)
    {
        return $this->prepareInputForUpdate($input);
    }

    public function prepareInputForUpdate($input)
    {

        if (isset($input['ipv4_range_start'])) {
            $input['ipv4_range_start'] = ip2long($input['ipv4_range_start']);
        }

        if (isset($input['ipv4_range_end'])) {
            $input['ipv4_range_end'] = ip2long($input['ipv4_range_end']);
        }

        if (isset($input['ipv4_range_start']) && isset($input['ipv4_range_end'])) {
            if (empty($input['ipv4_range_start'])) {
                $input['ipv4_range_start'] = "NULL";
                $input['ipv4_range_end'] = "NULL";
            } else {
                if (empty($input['ipv4_range_end'])) {
                    $input['ipv4_range_end'] = $input['ipv4_range_start'];
                }

                if ($input['ipv4_range_end'] < $input['ipv4_range_start']) {
                    $tmp = $input['ipv4_range_end'];
                    $input['ipv4_range_end'] = $input['ipv4_range_start'];
                    $input['ipv4_range_start'] = $tmp;
                }
            }
        }

        if (isset($input['ipv6']) && empty($input['ipv6'])) {
            $input['ipv6'] = "NULL";
        }

        if (isset($input['_reset_app_token']) && $input['_reset_app_token']) {
            $input['app_token']      = self::getUniqueAppToken();
            $input['app_token_date'] = $_SESSION['glpi_currenttime'];
        }

        return $input;
    }

    /**
     * Get log methods
     *
     * @return array
     */
    public static function getLogMethod()
    {

        return [self::DOLOG_DISABLED   => __('Disabled'),
            self::DOLOG_HISTORICAL => __('Historical'),
            self::DOLOG_LOGS       => _n(
                'Log',
                'Logs',
                Session::getPluralNumber()
            )
        ];
    }

    /**
     * Get app token checking that it is unique
     *
     * @return string app token
     */
    public static function getUniqueAppToken()
    {

        $ok = false;
        do {
            $key    = Toolbox::getRandomString(40);
            if (countElementsInTable(self::getTable(), ['app_token' => $key]) == 0) {
                return $key;
            }
        } while (!$ok);
    }

    public static function getIcon()
    {
        return "ti ti-browser";
    }
}
