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

use Glpi\Socket;

/**
 * Manage Netpoint.
 * @deprecated 9.5.0 Use Socket
 */
class Netpoint extends Socket
{
    public static function getTable($classname = null)
    {
        return Socket::getTable();
    }

    public function prepareInputForAdd($input)
    {
       //Copy input to match new format

        if (!isset($input['wiring_side'])) {
            $input['wiring_side'] = Socket::FRONT;
        }

        if (!isset($input['itemtype'])) {
            $input['itemtype'] = 'Computer';
        }

        if (!isset($input['items_id'])) {
            $input['items_id'] = 0;
        }

        if (!isset($input['socketmodels_id'])) {
            $input['socketmodels_id'] = 0;
        }

        if (!isset($input['networkports_id'])) {
            $input['networkports_id'] = 0;
        }

        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {

        if (!isset($input['wiring_side'])) {
            $input['wiring_side'] = Socket::FRONT;
        }

        if (!isset($input['itemtype'])) {
            $input['itemtype'] = 'Computer';
        }

        if (!isset($input['items_id'])) {
            $input['items_id'] = 0;
        }

        if (!isset($input['socketmodels_id'])) {
            $input['socketmodels_id'] = 0;
        }

        if (!isset($input['networkports_id'])) {
            $input['networkports_id'] = 0;
        }

       //Copy input to match new format
        return parent::prepareInputForUpdate($input);
    }
}
