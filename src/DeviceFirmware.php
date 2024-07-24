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

class DeviceFirmware extends CommonDevice
{
    protected static $forward_entity_to = ['Item_DeviceFirmware', 'Infocom'];

    public static function getTypeName($nb = 0)
    {
        return _n('Firmware', 'Firmware', $nb);
    }


    public function getAdditionalFields()
    {

        return array_merge(
            parent::getAdditionalFields(),
            [
                [
                    'name'  => 'devicefirmwaretypes_id',
                    'label' => _n('Type', 'Types', 1),
                    'type'  => 'dropdownValue'
                ],
                [
                    'name'   => 'date',
                    'label'  => __('Release date'),
                    'type'   => 'date'
                ],
                [
                    'name'   => 'version',
                    'label'  => _n('Version', 'Versions', 1),
                    'type'   => 'text'
                ],
                [
                    'name'   => 'devicefirmwaremodels_id',
                    'label'  => _n('Model', 'Models', 1),
                    'type'   => 'dropdownValue'
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
            'field'              => 'date',
            'name'               => __('Release date'),
            'datatype'           => 'date'
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => 'glpi_devicefirmwaremodels',
            'field'              => 'name',
            'name'               => _n('Model', 'Models', 1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => 'glpi_devicefirmwaretypes',
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '14',
            'table'              => 'glpi_devicefirmwares',
            'field'              => 'version',
            'name'               => _n('Version', 'Versions', 1),
        ];

        return $tab;
    }

    public static function rawSearchOptionsToAdd($itemtype, $main_joinparams)
    {
        $tab = [];

        //SO defined from glpi_devicefirmwares table
        $tab[] = [
            'id'                 => '1313',
            'table'              => 'glpi_devicefirmwares',
            'field'              => 'designation',
            'name'               => self::getTypeName(1),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'datatype'           => 'string',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_items_devicefirmwares',
                    'joinparams'         => $main_joinparams
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '1314',
            'table'              => 'glpi_devicefirmwares',
            'field'              => 'version',
            'name'               => sprintf(__('%1$s: %2$s'), self::getTypeName(1), _n('Version', 'Versions', 1)),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'datatype'           => 'string',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_items_devicefirmwares',
                    'joinparams'         => $main_joinparams
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '1315',
            'table'              => 'glpi_devicefirmwaretypes',
            'field'              => 'name',
            'name'               => sprintf(__('%1$s: %2$s'), self::getTypeName(1), _n('Type', 'Types', 1)),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'beforejoin' => [
                    'table'      => DeviceFirmware::getTable(),
                    'joinparams' => [
                        'beforejoin' => [
                            'table'      => Item_DeviceFirmware::getTable(),
                            'joinparams' => ['jointype' => 'itemtype_item']
                        ]
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '1316',
            'table'              => 'glpi_devicefirmwaremodels',
            'field'              => 'name',
            'name'               => sprintf(__('%1$s: %2$s'), self::getTypeName(1), _n('Model', 'Models', 1)),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'beforejoin' => [
                    'table'      => DeviceFirmware::getTable(),
                    'joinparams' => [
                        'beforejoin' => [
                            'table'      => Item_DeviceFirmware::getTable(),
                            'joinparams' => ['jointype' => 'itemtype_item']
                        ]
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '1317',
            'table'              => 'glpi_manufacturers',
            'field'              => 'name',
            'name'               => sprintf(__('%1$s: %2$s'), self::getTypeName(1), Manufacturer::getTypeName(1)),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'beforejoin' => [
                    'table'      => DeviceFirmware::getTable(),
                    'joinparams' => [
                        'beforejoin' => [
                            'table'      => Item_DeviceFirmware::getTable(),
                            'joinparams' => ['jointype' => 'itemtype_item']
                        ]
                    ]
                ]
            ]
        ];

        //SO defined from relation (glpi_items_devicefirmwares) table
        $tab[] = [
            'id'                 => '1318',
            'table'              => 'glpi_items_devicefirmwares',
            'field'              => 'serial',
            'name'               => sprintf(__('%1$s: %2$s'), self::getTypeName(1), __('Serial Number')),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'string',
            'massiveaction'      => false,
            'joinparams'         => $main_joinparams,
        ];

        $tab[] = [
            'id'                 => '1319',
            'table'              => 'glpi_items_devicefirmwares',
            'field'              => 'otherserial',
            'name'               => sprintf(__('%1$s: %2$s'), self::getTypeName(1), __('Inventory number')),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'string',
            'massiveaction'      => false,
            'joinparams'         => $main_joinparams,
        ];

        return $tab;
    }

    public static function getHTMLTableHeader(
        $itemtype,
        HTMLTableBase $base,
        ?HTMLTableSuperHeader $super = null,
        ?HTMLTableHeader $father = null,
        array $options = []
    ) {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;
        $column = parent::getHTMLTableHeader($itemtype, $base, $super, $father, $options);

        if ($column == $father) {
            return $father;
        }

        if (in_array($itemtype, $CFG_GLPI['itemdevicefirmware_types'])) {
            Manufacturer::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
            $base->addHeader('devicefirmware_type', _n('Type', 'Types', 1), $super, $father);
            $base->addHeader('version', _n('Version', 'Versions', 1), $super, $father);
            $base->addHeader('date', __('Release date'), $super, $father);
        }
    }

    public function getHTMLTableCellForItem(
        ?HTMLTableRow $row = null,
        ?CommonDBTM $item = null,
        ?HTMLTableCell $father = null,
        array $options = []
    ) {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;
        $column = parent::getHTMLTableCellForItem($row, $item, $father, $options);

        if ($column == $father) {
            return $father;
        }

        if (in_array($item->getType(), $CFG_GLPI['itemdevicefirmware_types'])) {
            Manufacturer::getHTMLTableCellsForItem($row, $this, null, $options);

            if ($this->fields["devicefirmwaretypes_id"]) {
                $row->addCell(
                    $row->getHeaderByName('devicefirmware_type'),
                    Dropdown::getDropdownName(
                        "glpi_devicefirmwaretypes",
                        $this->fields["devicefirmwaretypes_id"]
                    ),
                    $father
                );
            }
            $row->addCell(
                $row->getHeaderByName('version'),
                $this->fields["version"],
                $father
            );

            if ($this->fields["date"]) {
                $row->addCell(
                    $row->getHeaderByName('date'),
                    Html::convDate($this->fields["date"]),
                    $father
                );
            }
        }
    }

    public function getImportCriteria()
    {

        return [
            'designation'              => 'equal',
            'devicefirmwaretypes_id'   => 'equal',
            'manufacturers_id'         => 'equal',
            'version'                  => 'equal'
        ];
    }


    public static function getIcon()
    {
        return "fas fa-microchip";
    }
}
