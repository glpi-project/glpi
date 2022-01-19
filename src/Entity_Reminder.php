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

/// Class Entity_Reminder
/// @since 0.83
class Entity_Reminder extends CommonDBRelation
{
   // From CommonDBRelation
    public static $itemtype_1          = 'Reminder';
    public static $items_id_1          = 'reminders_id';
    public static $itemtype_2          = 'Entity';
    public static $items_id_2          = 'entities_id';

    public static $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;
    public static $logs_for_item_2     = false;


    /**
     * Get entities for a reminder
     *
     * @param Reminder $reminder Reminder instance
     *
     * @return array of entities linked to a reminder
     **/
    public static function getEntities($reminder)
    {
        global $DB;

        $ent   = [];
        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'reminders_id' => $reminder->fields['id']
            ]
        ]);

        foreach ($iterator as $data) {
            $ent[$data['entities_id']][] = $data;
        }
        return $ent;
    }
}
