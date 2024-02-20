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
 * Vlan Class
 **/
class Vlan extends CommonDropdown
{
    public $dohistory         = true;

    public $can_be_translated = false;


    public static function getTypeName($nb = 0)
    {
        return _n('VLAN', 'VLANs', $nb);
    }


    public function getAdditionalFields()
    {

        return [['name'     => 'tag',
            'label'    => __('ID TAG'),
            'type'     => '',
            'list'     => true
        ]
        ];
    }


    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {

        if ($field['name'] == 'tag') {
            Dropdown::showNumber('tag', [
                'value' => $this->fields['tag'],
                'min'   => 1,
                'max'   => (pow(2, 12) - 2),
                'width' => '100%',
            ]);
        }
    }


    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '11',
            'table'              => $this->getTable(),
            'field'              => 'tag',
            'name'               => __('ID TAG'),
            'datatype'           => 'number',
            'min'                => 1,
            'max'                => 4094
        ];

        return $tab;
    }


    public function cleanDBonPurge()
    {

        $this->deleteChildrenAndRelationsFromDb(
            [
                IPNetwork_Vlan::class,
                NetworkPort_Vlan::class,
            ]
        );
    }


    /**
     * @since 0.84
     *
     * @param $itemtype
     * @param $base            HTMLTableBase object
     * @param $super           HTMLTableSuperHeader object (default NULL
     * @param $father          HTMLTableHeader object (default NULL)
     * @param $options   array
     **/
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

        if ($itemtype == 'NetworkPort_Vlan') {
            $base->addHeader($column_name, self::getTypeName(), $super, $father);
        }
    }


    /**
     * @since 0.84
     *
     * @param $row             HTMLTableRow object (default NULL)
     * @param $item            CommonDBTM object (default NULL)
     * @param $father          HTMLTableCell object (default NULL)
     * @param $options   array
     **/
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

        $canedit = (isset($options['canedit']) && $options['canedit']);

        if ($item->getType() == 'NetworkPort_Vlan') {
            if (isset($item->fields["tagged"]) && ($item->fields["tagged"] == 1)) {
                $tagged_msg = __('Tagged');
            } else {
                $tagged_msg = __('Untagged');
            }

            $vlan = new self();
            if ($vlan->getFromDB($options['items_id'])) {
                $content = sprintf(__('%1$s - %2$s'), $vlan->getName(), $tagged_msg);
                $content .= Html::showToolTip(
                    sprintf(
                        __('%1$s: %2$s'),
                        __('ID TAG'),
                        $vlan->fields['tag']
                    ) . "<br>" .
                                          sprintf(
                                              __('%1$s: %2$s'),
                                              __('Comments'),
                                              $vlan->fields['comment']
                                          ),
                    ['display' => false]
                );

                $this_cell = $row->addCell($row->getHeaderByName($column_name), $content, $father);
            }
        }
    }

    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong)
         ->addStandardTab('NetworkPort_Vlan', $ong, $options);

        return $ong;
    }
}
