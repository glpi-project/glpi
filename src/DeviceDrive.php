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

/// Class DeviceDrive
class DeviceDrive extends CommonDevice
{
    protected static $forward_entity_to = ['Item_DeviceDrive', 'Infocom'];

    public static function getTypeName($nb = 0)
    {
        return _n('Drive', 'Drives', $nb);
    }


    public function getAdditionalFields()
    {

        return array_merge(
            parent::getAdditionalFields(),
            [['name'  => 'is_writer',
                'label' => __('Writing ability'),
                'type'  => 'bool',
            ],
                ['name'  => 'speed',
                    'label' => __('Speed'),
                    'type'  => 'text',
                ],
                ['name'  => 'interfacetypes_id',
                    'label' => __('Interface'),
                    'type'  => 'dropdownValue',
                ],
                ['name'  => 'devicedrivemodels_id',
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
            'id'                 => '12',
            'table'              => $this->getTable(),
            'field'              => 'is_writer',
            'name'               => __('Writing ability'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => $this->getTable(),
            'field'              => 'speed',
            'name'               => __('Speed'),
            'datatype'           => 'string',
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
            'table'              => 'glpi_devicedrivemodels',
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
                Manufacturer::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
                $base->addHeader('devicedrive_writer', __('Writing ability'), $super, $father);
                $base->addHeader('devicedrive_speed', __('Speed'), $super, $father);
                InterfaceType::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
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
                if ($this->fields["is_writer"]) {
                    $row->addCell(
                        $row->getHeaderByName('devicedrive_writer'),
                        Dropdown::getYesNo($this->fields["is_writer"]),
                        $father
                    );
                }

                if ($this->fields["speed"]) {
                    $row->addCell(
                        $row->getHeaderByName('devicedrive_speed'),
                        $this->fields["speed"],
                        $father
                    );
                }

                InterfaceType::getHTMLTableCellsForItem($row, $this, null, $options);
        }
    }


    /**
     * Criteria used for import function
     *
     * @see CommonDevice::getImportCriteria()
     *
     * @since 0.84
     **/
    public function getImportCriteria()
    {

        return ['designation'       => 'equal',
            'manufacturers_id'  => 'equal',
            'interfacetypes_id' => 'equal',
        ];
    }


    public static function getIcon()
    {
        return "fas fa-hdd";
    }
}
