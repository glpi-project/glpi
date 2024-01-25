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
 * Group_Ticket Class
 *
 * @since 0.85
 *
 * Relation between Groups and Tickets
 **/
class Group_Ticket extends CommonITILActor
{
   // From CommonDBRelation
    public static $itemtype_1 = 'Ticket';
    public static $items_id_1 = 'tickets_id';
    public static $itemtype_2 = 'Group';
    public static $items_id_2 = 'groups_id';


    public function post_addItem()
    {

        switch ($this->input['type']) {  // Values from CommonITILObject::getSearchOptionsActors()
            case CommonITILActor::REQUESTER:
                $this->_force_log_option = 71;
                break;
            case CommonITILActor::OBSERVER:
                $this->_force_log_option = 65;
                break;
            case CommonITILActor::ASSIGN:
                $this->_force_log_option = 8;
                break;
        }
        parent::post_addItem();
        unset($this->_force_log_option);
    }
}
