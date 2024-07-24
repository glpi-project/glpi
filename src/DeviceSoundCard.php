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

/**
 * DeviceSoundCard Class
 **/
class DeviceSoundCard extends CommonDevice
{
    protected static $forward_entity_to = ['Item_DeviceSoundCard', 'Infocom'];

    public static function getTypeName($nb = 0)
    {
        return _n('Soundcard', 'Soundcards', $nb);
    }


    public function getAdditionalFields()
    {

        return array_merge(
            parent::getAdditionalFields(),
            [['name'  => 'type',
                'label' => _n('Type', 'Types', 1),
                'type'  => 'text'
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
                ],
                ['name'  => 'devicesoundcardmodels_id',
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
            'id'                 => '12',
            'table'              => $this->getTable(),
            'field'              => 'type',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => 'glpi_devicesoundcardmodels',
            'field'              => 'name',
            'name'               => _n('Model', 'Models', 1),
            'datatype'           => 'dropdown'
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
                $base->addHeader('devicesoundcard_type', _n('Type', 'Types', 1), $super, $father);
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
                if ($this->fields["type"]) {
                    $row->addCell(
                        $row->getHeaderByName('devicesoundcard_type'),
                        $this->fields["type"],
                        $father
                    );
                }
        }
    }

    public static function rawSearchOptionsToAdd($itemtype, $main_joinparams)
    {
        $tab = [];

        $tab[] = [
            'id'                 => '12',
            'table'              => 'glpi_devicesoundcards',
            'field'              => 'designation',
            'name'               => static::getTypeName(1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'string',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_items_devicesoundcards',
                    'joinparams'         => $main_joinparams
                ]
            ]
        ];

        return $tab;
    }


    public static function getIcon()
    {
        return "ti ti-volume-2";
    }
}
