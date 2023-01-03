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

class DeviceBattery extends CommonDevice
{
    protected static $forward_entity_to = ['Item_DeviceBattery', 'Infocom'];

    public static function getTypeName($nb = 0)
    {
        return _n('Battery', 'Batteries', $nb);
    }


    public function getAdditionalFields()
    {
        return array_merge(
            parent::getAdditionalFields(),
            [
                [
                    'name'  => 'devicebatterytypes_id',
                    'label' => _n('Type', 'Types', 1),
                    'type'  => 'dropdownValue'
                ],
                [
                    'name'   => 'capacity',
                    'label'  => __('Capacity'),
                    'type'   => 'integer',
                    'min'    => 0,
                    'unit'   => __('mWh')
                ],
                [
                    'name'   => 'voltage',
                    'label'  => __('Voltage'),
                    'type'   => 'integer',
                    'min'    => 0,
                    'unit'   => __('mV')
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
            'field'              => 'capacity',
            'name'               => __('Capacity'),
            'datatype'           => 'integer',
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => $this->getTable(),
            'field'              => 'voltage',
            'name'               => __('Voltage'),
            'datatype'           => 'integer',
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => 'glpi_devicebatterytypes',
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
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

        $column = parent::getHTMLTableHeader($itemtype, $base, $super, $father, $options);

        if ($column == $father) {
            return $father;
        }

        Manufacturer::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
        $base->addHeader('devicebattery_type', _n('Type', 'Types', 1), $super, $father);
        $base->addHeader('voltage', sprintf('%1$s (%2$s)', __('Voltage'), __('mV')), $super, $father);
        $base->addHeader('capacity', sprintf('%1$s (%2$s)', __('Capacity'), __('mWh')), $super, $father);
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

        Manufacturer::getHTMLTableCellsForItem($row, $this, null, $options);

        if ($this->fields["devicebatterytypes_id"]) {
            $row->addCell(
                $row->getHeaderByName('devicebattery_type'),
                Dropdown::getDropdownName(
                    "glpi_devicebatterytypes",
                    $this->fields["devicebatterytypes_id"]
                ),
                $father
            );
        }

        if ($this->fields["voltage"]) {
            $row->addCell(
                $row->getHeaderByName('voltage'),
                $this->fields['voltage'],
                $father
            );
        }

        if ($this->fields["capacity"]) {
            $row->addCell(
                $row->getHeaderByName('capacity'),
                $this->fields['capacity'],
                $father
            );
        }
    }


    public function getImportCriteria()
    {

        return [
            'designation'           => 'equal',
            'devicebatterytypes_id' => 'equal',
            'manufacturers_id'      => 'equal',
            'capacity'              => 'delta:10',
            'voltage'               => 'delta:10'
        ];
    }


    public static function getIcon()
    {
        return "ti ti-battery-2";
    }
}
