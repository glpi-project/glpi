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
        return [
            [
                'name'     => 'tag',
                'label'    => __('ID TAG'),
                'type'     => 'integer',
                'min'      => 1,
                'max'      => 4094,
                'list'     => true,
            ],
        ];
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '11',
            'table'              => static::getTable(),
            'field'              => 'tag',
            'name'               => __('ID TAG'),
            'datatype'           => 'number',
            'min'                => 1,
            'max'                => 4094,
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
     * @param $itemtype
     * @param HTMLTableBase $base
     * @param HTMLTableSuperHeader|null $super
     * @param HTMLTableHeader|null $father
     * @param array $options
     * @since 0.84
     */
    public static function getHTMLTableHeader(
        $itemtype,
        HTMLTableBase $base,
        ?HTMLTableSuperHeader $super = null,
        ?HTMLTableHeader $father = null,
        array $options = []
    ) {
        $column_name = self::class;

        if (isset($options['dont_display'][$column_name])) {
            return;
        }

        if ($itemtype == 'NetworkPort_Vlan') {
            $base->addHeader($column_name, htmlescape(self::getTypeName()), $super, $father);
        }
    }

    /**
     * @param HTMLTableRow|null $row object (default NULL)
     * @param CommonDBTM|null $item object (default NULL)
     * @param HTMLTableCell|null $father object (default NULL)
     * @param array $options
     * @since 0.84
     */
    public static function getHTMLTableCellsForItem(
        ?HTMLTableRow $row = null,
        ?CommonDBTM $item = null,
        ?HTMLTableCell $father = null,
        array $options = []
    ) {
        $column_name = self::class;

        if (isset($options['dont_display'][$column_name])) {
            return;
        }

        if ($item === null) {
            if ($father === null) {
                return;
            }
            $item = $father->getItem();
        }

        if ($item::class === NetworkPort_Vlan::class) {
            if (isset($item->fields["tagged"]) && ($item->fields["tagged"] === 1)) {
                $tagged_msg = __('Tagged');
            } else {
                $tagged_msg = __('Untagged');
            }

            $vlan = new self();
            if ($vlan->getFromDB($options['items_id'])) {
                $content = htmlescape(sprintf(__('%1$s - %2$s'), $vlan->getName(), $tagged_msg));
                $content .= Html::showToolTip(
                    htmlescape(sprintf(
                        __('%1$s: %2$s'),
                        __('ID TAG'),
                        $vlan->fields['tag']
                    )) . "<br>"
                    . htmlescape(sprintf(
                        __('%1$s: %2$s'),
                        _n('Comment', 'Comments', Session::getPluralNumber()),
                        $vlan->fields['comment']
                    )),
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
         ->addStandardTab(NetworkPort_Vlan::class, $ong, $options);

        return $ong;
    }
}
