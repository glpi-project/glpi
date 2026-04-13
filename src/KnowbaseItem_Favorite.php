<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

class KnowbaseItem_Favorite extends CommonDBRelation
{
    // From CommonDBRelation
    public static ?string $itemtype_1 = KnowbaseItem::class;
    public static ?string $items_id_1 = 'knowbaseitems_id';
    public static ?string $itemtype_2 = User::class;
    public static ?string $items_id_2 = 'users_id';

    public static int $checkItem_1_Rights = self::HAVE_VIEW_RIGHT_ON_ITEM;
    public static int $checkItem_2_Rights = self::DONT_CHECK_ITEM_RIGHTS;
    public static bool $logs_for_item_1 = false;
    public static bool $logs_for_item_2 = false;

    public static function isFavoriteForCurrentUser(int $knowbaseitems_id): bool
    {
        $user_id = Session::getLoginUserID();
        if ($user_id === false) {
            return false;
        }

        return countElementsInTable(self::getTable(), [
            'knowbaseitems_id' => $knowbaseitems_id,
            'users_id'         => $user_id,
        ]) > 0;
    }
}
