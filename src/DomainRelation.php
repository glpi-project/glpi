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

class DomainRelation extends CommonDropdown
{
    const BELONGS = 1;
    const MANAGE = 2;
   // From CommonDBTM
    public $dohistory                   = true;
    public static $rightname                   = 'dropdown';

    public static $knowrelations = [
        [
            'id'        => self::BELONGS,
            'name'      => 'Belongs',
            'comment'   => 'Item belongs to domain'
        ], [
            'id'        => self::MANAGE,
            'name'      => 'Manage',
            'comment'   => 'Item manages domain'
        ]
    ];

    public static function getTypeName($nb = 0)
    {
        return _n('Domain relation', 'Domains relations', $nb);
    }

    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('Domain_Item', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }

    /**
     * Print the form
     *
     * @param integer $ID       Integer ID of the item
     * @param array   $options  Array of possible options:
     *     - target for the Form
     *     - withtemplate : template or basic item
     *
     * @return void|boolean (display) Returns false if there is a rights error.
     **/
    public function showForm($ID, array $options = [])
    {

        $rowspan = 3;
        if ($ID > 0) {
            $rowspan++;
        }

        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Name') . "</td>";
        echo "<td>";
        echo Html::input('name', ['value' => $this->fields['name']]);
        echo "</td>";

        echo "<td>" . __('Comments') . "</td>";
        echo "<td>
      <textarea class='form-control' name='comment' >" . $this->fields["comment"] . "</textarea>";
        echo "</td></tr>";

        $this->showFormButtons($options);
        return true;
    }

    public static function getDefaults()
    {
        return array_map(
            function ($e) {
                $e['is_recursive'] = 1;
                return $e;
            },
            self::$knowrelations
        );
    }

    public function pre_deleteItem()
    {
        if (in_array($this->fields['id'], [self::BELONGS, self::MANAGE])) {
           //keep defaults
            return false;
        }
        return true;
    }
}
