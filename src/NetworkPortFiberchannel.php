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

use Glpi\Socket;

/**
 * NetworkPortFiberchannel class : Fiberchannel instantiation of NetworkPort
 *
 * @since 9.1
 */
class NetworkPortFiberchannel extends NetworkPortInstantiation
{
    public static function getTypeName($nb = 0)
    {
        return __('Fiber channel port');
    }


    public function getNetworkCardInterestingFields()
    {
        return ['link.mac' => 'mac'];
    }


    public function prepareInput($input)
    {

        if (isset($input['speed']) && ($input['speed'] == 'speed_other_value')) {
            if (!isset($input['speed_other_value'])) {
                unset($input['speed']);
            } else {
                $speed = self::transformPortSpeed($input['speed_other_value'], false);
                if ($speed === false) {
                    unset($input['speed']);
                } else {
                    $input['speed'] = $speed;
                }
            }
        }
        return $input;
    }


    public function prepareInputForAdd($input)
    {
        return parent::prepareInputForAdd($this->prepareInput($input));
    }


    public function prepareInputForUpdate($input)
    {
        return parent::prepareInputForUpdate($this->prepareInput($input));
    }


    public function showInstantiationForm(NetworkPort $netport, $options, $recursiveItems)
    {

        if (!$options['several']) {
            echo "<tr class='tab_bg_1'>";
            $this->showSocketField($netport, $options, $recursiveItems);
            $this->showNetworkCardField($netport, $options, $recursiveItems);
            echo "</tr>\n";
        }

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('World Wide Name') . "</td><td>\n";
        echo Html::input('wwn', ['value' => $this->fields['wwn']]);
        echo "</td>";
        echo "<td>" . __('Fiber channel port speed') . "</td><td>\n";

        $standard_speeds = self::getPortSpeed();
        if (
            !isset($standard_speeds[$this->fields['speed']])
            && !empty($this->fields['speed'])
        ) {
            $speed = self::transformPortSpeed($this->fields['speed'], true);
        } else {
            $speed = true;
        }

        Dropdown::showFromArray(
            'speed',
            $standard_speeds,
            ['value' => $this->fields['speed'],
                'other' => $speed
            ]
        );
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'>\n";
        $this->showMacField($netport, $options);
        echo "<td>" . __('Connected to') . '</td><td>';
        self::showConnection($netport, true);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . NetworkPortFiberchannelType::getTypeName(0) . "</td><td>\n";
        Dropdown::show('NetworkPortFiberchannelType', ['name' => 'networkportfiberchanneltypes_id', 'value' => $this->fields['networkportfiberchanneltypes_id']]);
        echo "</td></tr>\n";
    }


    public function getInstantiationHTMLTableHeaders(
        HTMLTableGroup $group,
        HTMLTableSuperHeader $super,
        HTMLTableSuperHeader $internet_super = null,
        HTMLTableHeader $father = null,
        array $options = []
    ) {

        $display_options = &$options['display_options'];
        $header          = $group->addHeader('Connected', __('Connected to'), $super);

        DeviceNetworkCard::getHTMLTableHeader(
            'NetworkPortFiberchannel',
            $group,
            $super,
            $header,
            $options
        );

        $group->addHeader('speed', __('Fiber channel port speed'), $super, $header);
        $group->addHeader('wwn', __('World Wide Name'), $super, $header);
        $group->addHeader('Socket', _n('Network socket', 'Network sockets', 1), $super, $header);

        parent::getInstantiationHTMLTableHeaders($group, $super, $internet_super, $header, $options);
        return $header;
    }


    protected function getPeerInstantiationHTMLTable(
        NetworkPort $netport,
        HTMLTableRow $row,
        HTMLTableCell $father = null,
        array $options = []
    ) {

        DeviceNetworkCard::getHTMLTableCellsForItem($row, $this, $father, $options);

        if (!empty($this->fields['speed'])) {
            $row->addCell(
                $row->getHeaderByName('Instantiation', 'speed'),
                self::getPortSpeed($this->fields["speed"]),
                $father
            );
        }

        if (!empty($this->fields['wwn'])) {
            $row->addCell($row->getHeaderByName('Instantiation', 'wwn'), $this->fields["wwn"], $father);
        }

        parent::getInstantiationHTMLTable($netport, $row, $father, $options);
        Socket::getHTMLTableCellsForItem($row, $this, $father, $options);
    }


    public function getInstantiationHTMLTable(
        NetworkPort $netport,
        HTMLTableRow $row,
        HTMLTableCell $father = null,
        array $options = []
    ) {

        return $this->getInstantiationHTMLTableWithPeer($netport, $row, $father, $options);
    }


   // TODO why this? you don't have search engine for this object
    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => NetworkPort::getTable(),
            'field'              => 'mac',
            'datatype'           => 'mac',
            'name'               => __('MAC'),
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'empty'
            ]
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => $this->getTable(),
            'field'              => 'wwn',
            'name'               => __('World Wide Name'),
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => $this->getTable(),
            'field'              => 'speed',
            'name'               => __('Fiber channel port speed'),
            'massiveaction'      => false,
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => 'glpi_networkportfiberchanneltypes',
            'field'              => 'name',
            'name'               => __('Fiber port type'),
            'datatype'           => 'dropdown'
        ];

        return $tab;
    }


    /**
     * Transform a port speed from string to integerer and vice-versa
     *
     * @param integer|string $val        port speed
     * @param boolean        $to_string  true if we must transform the speed to string
     *
     * @return false|integer|string (regarding what is requested)
     **/
    public static function transformPortSpeed($val, $to_string)
    {

        if ($to_string) {
            if (($val % 1000) == 0) {
                //TRANS: %d is the speed
                return sprintf(__('%d Gbit/s'), $val / 1000);
            }

            if ((($val % 100) == 0) && ($val > 1000)) {
                $val /= 100;
               //TRANS: %f is the speed
                return sprintf(__('%.1f Gbit/s'), $val / 10);
            }

           //TRANS: %d is the speed
            return sprintf(__('%d Mbit/s'), $val);
        }

        $val = preg_replace('/\s+/', '', strtolower($val));

        $number = sscanf($val, "%f%s", $speed, $unit);
        if ($number != 2) {
            return false;
        }

        if (($unit == 'mbit/s') || ($unit == 'mb/s')) {
            return (int)$speed;
        }

        if (($unit == 'gbit/s') || ($unit == 'gb/s')) {
            return (int)($speed * 1000);
        }

        return false;
    }


    /**
     * Get the possible value for Ethernet port speed
     *
     * @param integer|null $val  if not set, ask for all values, else for 1 value (default NULL)
     *
     * @return array|string
     **/
    public static function getPortSpeed($val = null)
    {

        $tmp = [0     => '',
                   //TRANS: %d is the speed
            10    => sprintf(__('%d Mbit/s'), 10),
            100   => sprintf(__('%d Mbit/s'), 100),
                   //TRANS: %d is the speed
            1000  => sprintf(__('%d Gbit/s'), 1),
            10000 => sprintf(__('%d Gbit/s'), 10)
        ];

        if (is_null($val)) {
            return $tmp;
        }
        if (isset($tmp[$val])) {
            return $tmp[$val];
        }
        return self::transformPortSpeed($val, true);
    }


    /**
     * @param $field
     * @param $values
     * @param $options   array
     **/
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'speed':
                return self::getPortSpeed($values[$field]);
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    /**
     * @param $field
     * @param $name            (default '')
     * @param $values          (defaul '')
     * @param $options   array
     */
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;

        switch ($field) {
            case 'speed':
                $options['value'] = $values[$field];
                return Dropdown::showFromArray($name, self::getPortSpeed(), $options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    /**
     * @param $tab         array
     * @param $joinparams  array
     **/
    public static function getSearchOptionsToAddForInstantiation(array &$tab, array $joinparams)
    {
        $tab[] = [
            'id'                 => '62',
            'table'              => 'glpi_sockets',
            'field'              => 'name',
            'datatype'           => 'dropdown',
            'name'               => __('Network fiber socket'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
                'linkfield'           => 'networkports_id',
                'beforejoin'         => [
                    'table'              => 'glpi_networkportfiberchannels',
                    'joinparams'         => $joinparams
                ]
            ]
        ];
    }
}
