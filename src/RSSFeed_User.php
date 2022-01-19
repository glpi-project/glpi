<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

/// Class RSSFeed_User
/// @since 0.84
class RSSFeed_User extends CommonDBRelation
{
   // From CommonDBRelation
    public static $itemtype_1          = 'RSSFeed';
    public static $items_id_1          = 'rssfeeds_id';
    public static $itemtype_2          = 'User';
    public static $items_id_2          = 'users_id';

    public static $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;
    public static $logs_for_item_2     = false;


    /**
     * Get users for a rssfeed
     *
     * @param $rssfeeds_id ID of the rssfeed
     *
     * @return array of users linked to a rssfeed
     **/
    public static function getUsers($rssfeeds_id)
    {
        global $DB;

        $users = [];
        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => ['rssfeeds_id' => $rssfeeds_id]
        ]);

        foreach ($iterator as $data) {
            $users[$data['users_id']][] = $data;
        }
        return $users;
    }
}
