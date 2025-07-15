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

class TicketTask extends CommonITILTask
{
    public static function getTypeName($nb = 0)
    {
        return _n('Ticket task', 'Ticket tasks', $nb);
    }

    /**
     * Populate the planning with planned ticket tasks
     *
     * @param $options   array of possible options:
     *    - who          ID of the user (0 = undefined)
     *    - whogroup     ID of the group of users (0 = undefined)
     *    - begin        Date
     *    - end          Date
     *
     * @return array of planning item
     **/
    public static function populatePlanning($options = []): array
    {
        return parent::genericPopulatePlanning(self::class, $options);
    }


    /**
     * Populate the planning with planned ticket tasks
     *
     * @param $options   array of possible options:
     *    - who          ID of the user (0 = undefined)
     *    - whogroup     ID of the group of users (0 = undefined)
     *    - begin        Date
     *    - end          Date
     *
     * @return array of planning item
     **/
    public static function populateNotPlanned($options = []): array
    {
        return parent::genericPopulateNotPlanned(self::class, $options);
    }


    /**
     * Display a Planning Item
     *
     * @param array           $val       array of the item to display
     * @param integer         $who       ID of the user (0 if all)
     * @param string          $type      position of the item in the time block (in, through, begin or end)
     * @param integer|boolean $complete  complete display (more details)
     *
     * @return string
     */
    public static function displayPlanningItem(array $val, $who, $type = "", $complete = 0)
    {
        return parent::genericDisplayPlanningItem(self::class, $val, $who, $type, $complete);
    }

    /**
     * Build parent condition for search
     *
     * @return string
     */
    public static function buildParentCondition()
    {
        return "(0 = 1 " . Ticket::buildCanViewCondition("tickets_id") . ") ";
    }
}
