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
 *  Class used to manage LDAP replicate config
 */
class AuthLdapReplicate extends CommonDBTM
{
    public static $rightname = 'config';

    public static function canCreate()
    {
        return static::canUpdate();
    }

    public static function canPurge()
    {
        return static::canUpdate();
    }

    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public function prepareInputForAdd($input)
    {

        if (isset($input["port"]) && (intval($input["port"]) == 0)) {
            $input["port"] = 389;
        }
        return $input;
    }

    public function prepareInputForUpdate($input)
    {

        return $this->prepareInputForAdd($input);
    }

    /**
     * Form to add a replicate to a ldap server
     *
     * @param string  $target    target page for add new replicate
     * @param integer $master_id master ldap server ID
     *
     * @return void
     */
    public static function addNewReplicateForm($target, $master_id)
    {

        echo "<form action='$target' method='post' name='add_replicate_form' id='add_replicate_form'>";
        echo "<div class='center'>";
        echo "<table class='tab_cadre_fixe'>";

        echo "<tr><th colspan='5'>" . __('Add a LDAP directory replica') . "</th></tr>";
        echo "<tr class='tab_bg_1'><td class='center'>" . __('Name') . "</td>";
        echo "<td class='center'>" . __('Server') . "</td>";
        echo "<td class='center'>" . _n('Port', 'Ports', 1) . "</td>";
        echo "<td class='center'>" . __('Timeout') . "</td><td></td></tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<td class='center'><input type='text' class='form-control' name='name'></td>";
        echo "<td class='center'><input type='text' class='form-control' name='host'></td>";
        echo "<td class='center'><input type='text' class='form-control' name='port'></td>";
        echo "<td class='center'>";
        Dropdown::showNumber('timeout', ['value'  => 10,
            'min'    => 1,
            'max'    => 30,
            'step'   => 1,
            'toadd'  => [0 => __('No timeout')]
        ]);
        echo "</td>";
        echo "<td class='center'><input type='hidden' name='next' value='extauth_ldap'>";
        echo "<input type='hidden' name='authldaps_id' value='$master_id'>";
        echo "<input type='submit' name='add_replicate' value='" . _sx('button', 'Add') . "' class='btn btn-primary'></td>";
        echo "</tr></table></div>";
        Html::closeForm();
    }
}
