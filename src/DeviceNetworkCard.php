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

/**
 * DeviceNetworkCard Class
 **/
class DeviceNetworkCard extends CommonDevice
{
    protected static $forward_entity_to = ['Item_DeviceNetworkCard', 'Infocom'];

    public static function getTypeName($nb = 0)
    {
        return _n('Network card', 'Network cards', $nb);
    }


    /**
     * Criteria used for import function
     *
     * @since 0.84
     **/
    public function getImportCriteria()
    {

        return [
            'designation' => 'equal',
            'bandwidth' => 'equal',
        ];
    }


    public function getAdditionalFields()
    {

        return array_merge(
            parent::getAdditionalFields(),
            [['name'  => 'mac_default',
                'label' => __('MAC address by default'),
                'type'  => 'text'
            ],
                ['name'  => 'bandwidth',
                    'label' => __('Flow'),
                    'type'  => 'text'
                ],
                ['name'  => 'devicenetworkcardmodels_id',
                    'label' => _n('Model', 'Models', 1),
                    'type'  => 'dropdownValue'
                ],
                ['name'  => 'none',
                    'label' => RegisteredID::getTypeName(Session::getPluralNumber()) .
                                        RegisteredID::showAddChildButtonForItemForm(
                                            $this,
                                            '_registeredID',
                                            null,
                                            false
                                        ),
                    'type'  => 'registeredIDChooser'
                ]
            ]
        );
    }


    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '11',
            'table'              => $this->getTable(),
            'field'              => 'mac_default',
            'name'               => __('MAC address by default'),
            'datatype'           => 'mac',
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => $this->getTable(),
            'field'              => 'bandwidth',
            'name'               => __('Flow'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => 'glpi_devicenetworkcardmodels',
            'field'              => 'name',
            'name'               => _n('Model', 'Models', 1),
            'datatype'           => 'dropdown'
        ];

        return $tab;
    }


    public static function getHTMLTableHeader(
        $itemtype,
        HTMLTableBase $base,
        HTMLTableSuperHeader $super = null,
        HTMLTableHeader $father = null,
        array $options = []
    ) {

        $column_name = __CLASS__;

        if (isset($options['dont_display'][$column_name])) {
            return;
        }

        if (in_array($itemtype, NetworkPort::getNetworkPortInstantiations())) {
            $base->addHeader($column_name, __('Interface'), $super, $father);
        } else {
            $column = parent::getHTMLTableHeader($itemtype, $base, $super, $father, $options);
            if ($column == $father) {
                return $father;
            }
            Manufacturer::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
            $base->addHeader('devicenetworkcard_bandwidth', __('Flow'), $super, $father);
        }
    }


    public static function getHTMLTableCellsForItem(
        HTMLTableRow $row = null,
        CommonDBTM $item = null,
        HTMLTableCell $father = null,
        array $options = []
    ) {

        $column_name = __CLASS__;

        if (isset($options['dont_display'][$column_name])) {
            return;
        }

        if (empty($item)) {
            if (empty($father)) {
                return;
            }
            $item = $father->getItem();
        }

        if (in_array($item->getType(), NetworkPort::getNetworkPortInstantiations())) {
            $link = new Item_DeviceNetworkCard();
            if ($link->getFromDB($item->fields['items_devicenetworkcards_id'])) {
                $device = $link->getOnePeer(1);
                if ($device) {
                    $row->addCell($row->getHeaderByName($column_name), $device->getLink(), $father);
                }
            }
        }
    }


    public function getHTMLTableCellForItem(
        HTMLTableRow $row = null,
        CommonDBTM $item = null,
        HTMLTableCell $father = null,
        array $options = []
    ) {

        $column = parent::getHTMLTableCellForItem($row, $item, $father, $options);

        if ($column == $father) {
            return $father;
        }

        switch ($item->getType()) {
            case 'Computer':
                Manufacturer::getHTMLTableCellsForItem($row, $this, null, $options);
                if ($this->fields["bandwidth"]) {
                    $row->addCell(
                        $row->getHeaderByName('devicenetworkcard_bandwidth'),
                        $this->fields["bandwidth"],
                        $father
                    );
                }
                break;
        }
    }

    public static function rawSearchOptionsToAdd($itemtype, $main_joinparams)
    {
        $tab = [];

        $tab[] = [
            'id'                 => '112',
            'table'              => 'glpi_devicenetworkcards',
            'field'              => 'designation',
            'name'               => NetworkInterface::getTypeName(1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'string',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_items_devicenetworkcards',
                    'joinparams'         => $main_joinparams
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '113',
            'table'              => 'glpi_items_devicenetworkcards',
            'field'              => 'mac',
            'name'               => __('MAC address'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'string',
            'joinparams'         => $main_joinparams
        ];

        return $tab;
    }


    public static function getIcon()
    {
        return "fas fa-network-wired";
    }
}
