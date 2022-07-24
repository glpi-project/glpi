<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

/// Class InterfaceType (Interface is a reserved keyword)
class InterfaceType extends CommonDropdown
{
    public $can_be_translated = false;


    public static function getTypeName($nb = 0)
    {
        return _n('Interface type (Hard drive...)', 'Interface types (Hard drive...)', $nb);
    }


    /**
     * @since 0.84
     *
     * @param $itemtype
     * @param $base               HTMLTableBase object
     * @param $super              HTMLTableSuperHeader object (default NULL)
     * @param $father             HTMLTableHeader object (default NULL)
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

        $base->addHeader($column_name, __('Interface'), $super, $father);
    }


    /**
     * @since 0.84
     *
     * @param $row                HTMLTableRow object (default NULL)
     * @param $item               CommonDBTM object (default NULL)
     * @param $father             HTMLTableCell object (default NULL)
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

        if ($item->fields["interfacetypes_id"]) {
            $row->addCell(
                $row->getHeaderByName($column_name),
                Dropdown::getDropdownName(
                    "glpi_interfacetypes",
                    $item->fields["interfacetypes_id"]
                )
            );
        }
    }
}
