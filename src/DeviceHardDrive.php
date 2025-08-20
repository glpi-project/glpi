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

use Glpi\DBAL\QueryFunction;

/// Class DeviceHardDrive
class DeviceHardDrive extends CommonDevice
{
    protected static $forward_entity_to = ['Item_DeviceHardDrive', 'Infocom'];

    public static function getTypeName($nb = 0)
    {
        return _n('Hard drive', 'Hard drives', $nb);
    }

    public function getAdditionalFields()
    {
        return array_merge(
            parent::getAdditionalFields(),
            [
                [
                    'name'  => 'capacity_default',
                    'label' => __('Capacity by default'),
                    'type'  => 'integer',
                    'min'   => 0,
                    'unit'  => __('Mio'),
                ],
                [
                    'name'  => 'rpm',
                    'label' => __('Rpm'),
                    'type'  => 'text',
                ],
                [
                    'name'  => 'cache',
                    'label' => __('Cache'),
                    'type'  => 'integer',
                    'unit'  => __('Mio'),
                ],
                [
                    'name'  => 'deviceharddrivemodels_id',
                    'label' => _n('Model', 'Models', 1),
                    'type'  => 'dropdownValue',
                ],
                [
                    'name'  => 'interfacetypes_id',
                    'label' => __('Interface'),
                    'type'  => 'dropdownValue',
                ],
                [
                    'name'  => 'deviceharddrivetypes_id',
                    'label' => _n('Type', 'Types', 1),
                    'type'  => 'dropdownValue',
                ],
            ]
        );
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '11',
            'table'              => static::getTable(),
            'field'              => 'capacity_default',
            'name'               => __('Capacity by default'),
            'datatype'           => 'integer',
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => static::getTable(),
            'field'              => 'rpm',
            'name'               => __('Rpm'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => static::getTable(),
            'field'              => 'cache',
            'name'               => __('Cache'),
            'datatype'           => 'integer',
        ];

        $tab[] = [
            'id'                 => '14',
            'table'              => 'glpi_interfacetypes',
            'field'              => 'name',
            'name'               => __('Interface'),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '15',
            'table'              => 'glpi_deviceharddrivemodels',
            'field'              => 'name',
            'name'               => _n('Model', 'Models', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '17',
            'table'              => 'glpi_deviceharddrivetypes',
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown',
        ];

        return $tab;
    }

    /**
     * @since 0.85
     * @param array $input
     *
     * @return array
     **/
    public function prepareInputForAddOrUpdate($input)
    {
        foreach (['capacity_default'] as $field) {
            if (isset($input[$field]) && !is_numeric($input[$field])) {
                $input[$field] = 0;
            }
        }
        return $input;
    }

    public function prepareInputForAdd($input)
    {
        return $this->prepareInputForAddOrUpdate($input);
    }

    public function prepareInputForUpdate($input)
    {
        return $this->prepareInputForAddOrUpdate($input);
    }

    public static function getHTMLTableHeader(
        $itemtype,
        HTMLTableBase $base,
        ?HTMLTableSuperHeader $super = null,
        ?HTMLTableHeader $father = null,
        array $options = []
    ) {
        $column = parent::getHTMLTableHeader($itemtype, $base, $super, $father, $options);

        if ($column == $father) {
            return $father;
        }

        switch ($itemtype) {
            case 'Computer':
                Manufacturer::getHTMLTableHeader(self::class, $base, $super, $father, $options);
                $base->addHeader('deviceharddriver_rpm', __s('Rpm'), $super, $father);
                $base->addHeader('deviceharddriver_cache', __s('Cache'), $super, $father);
                $base->addHeader('deviceharddriver_type', _sn('Type', 'Types', 1), $super, $father);
                InterfaceType::getHTMLTableHeader(self::class, $base, $super, $father, $options);
                break;
        }
    }

    public function getHTMLTableCellForItem(
        ?HTMLTableRow $row = null,
        ?CommonDBTM $item = null,
        ?HTMLTableCell $father = null,
        array $options = []
    ) {
        $column = parent::getHTMLTableCellForItem($row, $item, $father, $options);

        if ($column == $father) {
            return $father;
        }

        switch ($item->getType()) {
            case 'Computer':
                Manufacturer::getHTMLTableCellsForItem($row, $this, null, $options);
                if ($this->fields["rpm"]) {
                    $row->addCell(
                        $row->getHeaderByName('deviceharddriver_rpm'),
                        htmlescape($this->fields["rpm"])
                    );
                }

                if ($this->fields["cache"]) {
                    $row->addCell(
                        $row->getHeaderByName('deviceharddriver_cache'),
                        htmlescape($this->fields["cache"])
                    );
                }

                if ($this->fields["deviceharddrivetypes_id"]) {
                    $row->addCell(
                        $row->getHeaderByName('deviceharddriver_type'),
                        htmlescape(Dropdown::getDropdownName("glpi_deviceharddrivetypes", $this->fields["deviceharddrivetypes_id"])),
                        $father
                    );
                }
                InterfaceType::getHTMLTableCellsForItem($row, $this, null, $options);
                break;
        }
        return null;
    }

    public function getImportCriteria()
    {
        return ['designation'       => 'equal',
            'manufacturers_id'  => 'equal',
            'interfacetypes_id' => 'equal',
            'deviceharddrivetypes_id' => 'equal',
        ];
    }

    public static function rawSearchOptionsToAdd($itemtype, $main_joinparams)
    {
        $tab = [];

        $tab[] = [
            'id'                 => '114',
            'table'              => 'glpi_deviceharddrives',
            'field'              => 'designation',
            'name'               => sprintf(__('%1$s: %2$s'), self::getTypeName(1), __('Name')),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'datatype'           => 'string',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_items_deviceharddrives',
                    'joinparams'         => $main_joinparams,
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '115',
            'table'              => 'glpi_items_deviceharddrives',
            'field'              => 'capacity',
            'name'               => sprintf(__('%1$s: %2$s'), self::getTypeName(1), __('Capacity by default')),
            'unit'               => 'auto',
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'number',
            'width'              => 1000,
            'massiveaction'      => false,
            'joinparams'         => $main_joinparams,
            'computation'        => '('
                . QueryFunction::sum('TABLE.capacity') . ' / '
                . QueryFunction::count('TABLE.id') . ') * '
                . QueryFunction::count(expression: 'TABLE.id', distinct: true),
            'nometa'             => true, // cannot GROUP_CONCAT a SUM
        ];

        $tab[] = [
            'id'                 => '116',
            'table'              => 'glpi_deviceharddrivetypes',
            'field'              => 'name',
            'name'               => sprintf(__('%1$s: %2$s'), self::getTypeName(1), _n('Type', 'Types', 1)),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'beforejoin' => [
                    'table'      => self::getTable(),
                    'joinparams' => [
                        'beforejoin' => [
                            'table'      => Item_DeviceHardDrive::getTable(),
                            'joinparams' => ['jointype' => 'itemtype_item'],
                        ],
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '1324',
            'table'              => 'glpi_items_deviceharddrives',
            'field'              => 'serial',
            'name'               => sprintf(__('%1$s: %2$s'), self::getTypeName(1), __('Serial Number')),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'string',
            'massiveaction'      => false,
            'joinparams'         => $main_joinparams,
        ];

        $tab[] = [
            'id'                 => '1325',
            'table'              => 'glpi_items_deviceharddrives',
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

    public static function getIcon()
    {
        return "ti ti-server-2";
    }
}
