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
 * Manage ComputerVirtualMachine.
 * @deprecated 11.0.0 Use ItemVirtualMachine
 */
class ComputerVirtualMachine extends ItemVirtualMachine
{
    public static function getTable($classname = null)
    {
        return ItemVirtualMachine::getTable();
    }

    public function prepareInputForAdd($input)
    {
        //add missing itemtype, rename computers_id to items_id
        $input['itemtype'] = 'Computer';
        if (isset($input['computers_id'])) {
            $input['itemps_id'] = $input['computers_id'];
            unset($input['computers_id']);
        }

        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        //add missing itemtype, rename computers_id to items_id
        if (!isset($input['itemtype'])) {
            $input['itemtype'] = 'Computer';
        }
        if (isset($input['computers_id'])) {
            $input['itemps_id'] = $input['computers_id'];
            unset($input['computers_id']);
        }

        return parent::prepareInputForUpdate($input);
    }
}
