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

/// Class DeviceMotherboard
class DeviceMotherboard extends CommonDevice
{
    protected static $forward_entity_to = ['Item_DeviceMotherboard', 'Infocom'];

    public static function getTypeName($nb = 0)
    {
        return _n('System board', 'System boards', $nb);
    }

    public function getAdditionalFields()
    {
        return array_merge(
            parent::getAdditionalFields(),
            [['name'  => 'chipset',
                'label' => __('Chipset'),
                'type'  => 'text',
            ],
                ['name'  => 'devicemotherboardmodels_id',
                    'label' => _n('Model', 'Models', 1),
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
            'field'              => 'chipset',
            'name'               => __('Chipset'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => 'glpi_devicemotherboardmodels',
            'field'              => 'name',
            'name'               => _n('Model', 'Models', 1),
            'datatype'           => 'dropdown',
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

        $column = parent::getHTMLTableHeader($itemtype, $base, $super, $father, $options);

        if ($column == $father) {
            return $father;
        }

        switch ($itemtype) {
            case 'Computer':
                Manufacturer::getHTMLTableHeader(self::class, $base, $super, $father, $options);
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

        switch ($item::class) {
            case Computer::class:
                Manufacturer::getHTMLTableCellsForItem($row, $this, null, $options);
                break;
        }
        return null;
    }

    public function getImportCriteria()
    {
        return [
            'designation'      => 'equal',
            'manufacturers_id' => 'equal',
            'chipset'          => 'equal',
        ];
    }

    public static function rawSearchOptionsToAdd($itemtype, $main_joinparams)
    {
        $tab = [];

        $tab[] = [
            'id'                 => '14',
            'table'              => 'glpi_devicemotherboards',
            'field'              => 'designation',
            'name'               => static::getTypeName(1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'string',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_items_devicemotherboards',
                    'joinparams'         => $main_joinparams,
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '1328',
            'table'              => 'glpi_items_devicemotherboards',
            'field'              => 'serial',
            'name'               => sprintf(__('%1$s: %2$s'), self::getTypeName(1), __('Serial Number')),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'string',
            'massiveaction'      => false,
            'joinparams'         => $main_joinparams,
        ];

        $tab[] = [
            'id'                 => '1329',
            'table'              => 'glpi_items_devicemotherboards',
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
}
