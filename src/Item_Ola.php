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

class Item_Ola extends CommonDBRelation
{
    public static $itemtype_1 = 'itemtype'; // Only Ticket at the moment
    public static $items_id_1 = 'items_id';

    public static $itemtype_2 = OLA::class;
    public static $items_id_2 = 'olas_id';

    //    public static $rightname = 'device'; // @todoseb
    //      public static $checkItem_1_Rights = self::DONT_CHECK_ITEM_RIGHTS;  // @todoseb voir implications
    //      public static $checkItem_2_Rights = self::DONT_CHECK_ITEM_RIGHTS;  // @todoseb voir implications

    //    public static $mustBeAttached_1 = true; // @todoseb voir implications
    //    public static $mustBeAttached_2 = true; // @todoseb voir implications

    /**
     * Prepare the input for add
     *
     * add start_time and due_time values.
     * @param $input
     * @return array|false
     */
    public function prepareInputForAdd($input)
    {
        // @todoseb attention filter si TTO ou TTR ?
        if (in_array(['due_time', 'start_time'], array_keys($input))) {
            throw new \RuntimeException('due_time and start_time are not allowed in the input. Values are computed.');
        }

        // get the related ola (cannot use getConnexityItem() ou getOnePeer() because it is not in the database yet)
        $_ola = new OLA();
        if (!$_ola->getFromDB($input[static::$items_id_2])) {
            throw new \RuntimeException('OLA not found #' . $input[static::$items_id_2]);
        }

        return parent::prepareInputForAdd([
            'due_time' => $_ola->computeDate($_SESSION['glpi_currenttime']),
            'start_time' => $_SESSION['glpi_currenttime'],
        ] + $input);
    }

    public function getOla(): OLA
    {
        $item = $this->getConnexityItem(self::$itemtype_2, self::$items_id_2);
        if ($item instanceof OLA) {
            return $item;
        }

        throw new \RuntimeException('Linked OLA not found');
    }
}
