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

use Glpi\DBAL\QueryExpression;

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
        return parent::genericPopulatePlanning(__CLASS__, $options);
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
        return parent::genericPopulateNotPlanned(__CLASS__, $options);
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
        return parent::genericDisplayPlanningItem(__CLASS__, $val, $who, $type, $complete);
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

    public static function getSQLDefaultWhereCriteria(): array
    {
        // Filter on is_private
        $allowed_is_private = [];
        if (Session::haveRight(self::$rightname, \CommonITILTask::SEEPRIVATE)) {
            $allowed_is_private[] = 1;
        }
        if (Session::haveRight(self::$rightname, \CommonITILTask::SEEPUBLIC)) {
            $allowed_is_private[] = 0;
        }

        // If the user can't see public and private
        if (!count($allowed_is_private)) {
            return [
                '0' => '1'
            ];
        }

        $criteria = [
            'OR' => [
                'glpi_tickettasks.is_private' => $allowed_is_private,
                // Check for assigned or created tasks
                'glpi_tickettasks.users_id' => Session::getLoginUserID(),
                'glpi_tickettasks.users_id_tech' => Session::getLoginUserID(),
            ]
        ];

        // Check for parent item visibility unless the user can see all the
        // possible parents
        if (!Session::haveRight('ticket', \Ticket::READALL)) {
            $criteria[] = [
                new QueryExpression(self::buildParentCondition())
            ];
        }
        return $criteria;
    }
}
