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

/// Class DeviceMemory
class DeviceMemory extends CommonDevice
{
    protected static $forward_entity_to = ['Item_DeviceMemory', 'Infocom'];

    public static function getTypeName($nb = 0)
    {
        return _n('Memory', 'Memory', $nb);
    }


    public function getAdditionalFields()
    {

        return array_merge(
            parent::getAdditionalFields(),
            [
                [
                    'name'  => 'size_default',
                    'label' => __('Size by default'),
                    'type'  => 'integer',
                    'min'   => 0,
                    'unit'  => __('Mio')
                ],
                [
                    'name'  => 'frequence',
                    'label' => sprintf(__('%1$s (%2$s)'), __('Frequency'), __('MHz')),
                    'type'  => 'integer',
                    'min'   => 0,
                    'unit'  => __('MHz')
                ],
                [
                    'name'  => 'devicememorytypes_id',
                    'label' => _n('Type', 'Types', 1),
                    'type'  => 'dropdownValue'
                ],
                [
                    'name'  => 'devicememorymodels_id',
                    'label' => _n('Model', 'Models', 1),
                    'type'  => 'dropdownValue'
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
            'field'              => 'size_default',
            'name'               => __('Size by default'),
            'datatype'           => 'integer',
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => $this->getTable(),
            'field'              => 'frequence',
            'name'               => sprintf(__('%1$s (%2$s)'), __('Frequency'), __('MHz')),
            'datatype'           => 'integer',
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => 'glpi_devicememorytypes',
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '14',
            'table'              => 'glpi_devicememorymodels',
            'field'              => 'name',
            'name'               => _n('Model', 'Models', 1),
            'datatype'           => 'dropdown'
        ];

        return $tab;
    }


    /**
     * @since 0.85
     * @param $input
     *
     * @return number
     **/
    public function prepareInputForAddOrUpdate($input)
    {

        foreach (['size_default'] as $field) {
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
        HTMLTableSuperHeader $super = null,
        HTMLTableHeader $father = null,
        array $options = []
    ) {

        $column = parent::getHTMLTableHeader($itemtype, $base, $super, $father, $options);

        if ($column == $father) {
            return $father;
        }

        switch ($itemtype) {
            case 'Computer':
                Manufacturer::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
                $base->addHeader('devicememory_type', _n('Type', 'Types', 1), $super, $father);
                $base->addHeader('devicememory_frequency', sprintf(__('%1$s (%2$s)'), __('Frequency'), __('MHz')), $super, $father);
                break;
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
                if ($this->fields["devicememorytypes_id"]) {
                    $row->addCell(
                        $row->getHeaderByName('devicememory_type'),
                        Dropdown::getDropdownName(
                            "glpi_devicememorytypes",
                            $this->fields["devicememorytypes_id"]
                        ),
                        $father
                    );
                }

                if (!empty($this->fields["frequence"])) {
                    $row->addCell(
                        $row->getHeaderByName('devicememory_frequency'),
                        $this->fields["frequence"],
                        $father
                    );
                }
                break;
        }
    }


    public function getImportCriteria()
    {

        return ['designation'          => 'equal',
            'devicememorytypes_id' => 'equal',
            'manufacturers_id'     => 'equal',
            'frequence'            => 'delta:10'
        ];
    }

    public static function rawSearchOptionsToAdd($class, $main_joinparams)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $tab = [];

        $tab[] = [
            'id'                 => '110',
            'table'              => 'glpi_devicememories',
            'field'              => 'designation',
            'name'               => DeviceMemoryType::getTypeName(1),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'datatype'           => 'string',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_items_devicememories',
                    'joinparams'         => $main_joinparams
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '111',
            'table'              => 'glpi_items_devicememories',
            'field'              => 'size',
            'unit'               => 'auto',
            'name'               => _n('Memory', 'Memories', 1),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'mio',
            'width'              => 100,
            'massiveaction'      => false,
            'joinparams'         => $main_joinparams,
            'computation'        =>
            '(SUM(' . $DB->quoteName('TABLE.size') . ') / COUNT(' .
            $DB->quoteName('TABLE.id') . '))
            * COUNT(DISTINCT ' . $DB->quoteName('TABLE.id') . ')',
            'nometa'             => true, // cannot GROUP_CONCAT a SUM
        ];

        return $tab;
    }


    public static function getIcon()
    {
        return "fas fa-memory";
    }
}
