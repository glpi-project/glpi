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

/// Class Profile_RSSFeed
/// @since 0.84
class Profile_RSSFeed extends CommonDBRelation
{
   // From CommonDBRelation
    public static $itemtype_1          = 'RSSFeed';
    public static $items_id_1          = 'rssfeeds_id';
    public static $itemtype_2          = 'Profile';
    public static $items_id_2          = 'profiles_id';

    public static $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;
    public static $logs_for_item_2     = false;


    /**
     * Get profiles for a rssfeed
     *
     * @param $rssfeeds_id ID of the rssfeed
     *
     * @return array of profiles linked to a rssfeed
     **/
    public static function getProfiles($rssfeeds_id)
    {
        global $DB;

        $prof  = [];
        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => ['rssfeeds_id' => $rssfeeds_id]
        ]);

        foreach ($iterator as $data) {
            $prof[$data['profiles_id']][] = $data;
        }
        return $prof;
    }
}
