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

/*
 * @since 9.2
 */
class DeviceSensor extends CommonDevice
{
    protected static $forward_entity_to = ['Item_DeviceSensor', 'Infocom'];

    public static function getTypeName($nb = 0)
    {
        return _n('Sensor', 'Sensors', $nb);
    }

    public function getAdditionalFields()
    {
        return array_merge(
            parent::getAdditionalFields(),
            [
                [
                    'name'  => 'devicesensortypes_id',
                    'label' => _n('Type', 'Types', 1),
                    'type'  => 'dropdownValue',
                ],
            ]
        );
    }

    public function rawSearchOptions()
    {
        $tab                 = parent::rawSearchOptions();

        $tab[] = ['id'       => '12',
            'table'    => 'glpi_devicesensortypes',
            'field'    => 'name',
            'name'     => _n('Type', 'Types', 1),
            'datatype' => 'dropdown',
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
            case 'Peripheral':
                Manufacturer::getHTMLTableHeader(self::class, $base, $super, $father, $options);
                $base->addHeader('devicesensor_type', _sn('Type', 'Types', 1), $super, $father);
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
            case Peripheral::class:
                Manufacturer::getHTMLTableCellsForItem($row, $this, null, $options);
                break;
        }
        return null;
    }

    /**
     * Criteria used for import function
     */
    public function getImportCriteria()
    {
        return [
            'designation'          => 'equal',
            'manufacturers_id'     => 'equal',
            'devicesensortypes_id' => 'equal',
            'locations_id'         => 'equal',
        ];
    }
}
