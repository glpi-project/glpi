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

class DeviceCamera extends CommonDevice
{
    protected static $forward_entity_to = ['Item_DeviceCamera', 'Infocom'];

    public static function getTypeName($nb = 0)
    {
        return _n('Camera', 'Cameras', $nb);
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong)
         ->addImpactTab($ong, $options)
         ->addStandardTab(Item_DeviceCamera_ImageResolution::class, $ong, $options)
         ->addStandardTab(Item_DeviceCamera_ImageFormat::class, $ong, $options)
         ->addStandardTab(Infocom::class, $ong, $options)
         ->addStandardTab(Contract_Item::class, $ong, $options)
         ->addStandardTab(Item_Project::class, $ong, $options)
         ->addStandardTab(Log::class, $ong, $options);
        return $ong;
    }

    public function getAdditionalFields()
    {
        return array_merge(
            parent::getAdditionalFields(),
            [
                [
                    'name'  => 'devicecameramodels_id',
                    'label' => _n('Model', 'Models', 1),
                    'type'  => 'dropdownValue',
                ],
                [
                    'name'   => 'flashunit',
                    'label'  => __('Flashunit'),
                    'type'   => 'bool',
                ],
                [
                    'name'   => 'lensfacing',
                    'label'  => __('Lensfacing'),
                    'type'   => 'text',
                ],
                [
                    'name'   => 'orientation',
                    'label'  => __('Orientation'),
                    'type'   => 'text',
                ],
                [
                    'name'   => 'focallength',
                    'label'  => __('Focal length'),
                    'type'   => 'text',
                ],
                [
                    'name'   => 'sensorsize',
                    'label'  => __('Sensor size'),
                    'type'   => 'text',
                ],
                [
                    'name'   => 'support',
                    'label'  => __('Support'),
                    'type'   => 'text',
                ],
            ]
        );
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '10',
            'table'              => 'glpi_devicecameramodels',
            'field'              => 'name',
            'name'               => _n('Model', 'Models', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => static::getTable(),
            'field'              => 'flashunit',
            'name'               => __('Flashunit'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => static::getTable(),
            'field'              => 'lensfacing',
            'name'               => __('Lensfacing'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => static::getTable(),
            'field'              => 'orientation',
            'name'               => __('orientation'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '14',
            'table'              => static::getTable(),
            'field'              => 'focallength',
            'name'               => __('Focal length'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '15',
            'table'              => static::getTable(),
            'field'              => 'sensorsize',
            'name'               => __('Sensor size'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '17',
            'table'              => static::getTable(),
            'field'              => 'support',
            'name'               => __('Support'),
            'datatype'           => 'string',
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

        Manufacturer::getHTMLTableHeader(self::class, $base, $super, $father, $options);
        $base->addHeader('devicecamera_model', _sn('Model', 'Models', 1), $super, $father);
        $base->addHeader('flashunit', __s('Flashunit'), $super, $father);
        $base->addHeader('lensfacing', __s('lensfacing'), $super, $father);
        $base->addHeader('orientation', __s('orientation'), $super, $father);
        $base->addHeader('focallength', __s('focal length'), $super, $father);
        $base->addHeader('sensorsize', __s('sensorsize'), $super, $father);
        $base->addHeader('support', __s('support'), $super, $father);
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

        Manufacturer::getHTMLTableCellsForItem($row, $this, null, $options);

        if ($this->fields["devicecameramodels_id"]) {
            $row->addCell(
                $row->getHeaderByName('devicecamera_model'),
                htmlescape(Dropdown::getDropdownName("glpi_devicecameramodels", $this->fields["devicecameramodels_id"])),
                $father
            );
        }

        if ($this->fields["lensfacing"]) {
            $row->addCell(
                $row->getHeaderByName('lensfacing'),
                htmlescape($this->fields["lensfacing"]),
                $father
            );
        }

        if ($this->fields["flashunit"]) {
            $row->addCell(
                $row->getHeaderByName('flashunit'),
                htmlescape($this->fields["flashunit"]),
                $father
            );
        }
        return null;
    }

    public function getImportCriteria()
    {
        return [
            'designation'           => 'equal',
            'devicecameramodels_id' => 'equal',
            'manufacturers_id'      => 'equal',
        ];
    }

    public static function getIcon()
    {
        return "ti ti-camera";
    }
}
