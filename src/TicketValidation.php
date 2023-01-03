<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
 * TicketValidation class
 */
class TicketValidation extends CommonITILValidation
{
   // From CommonDBChild
    public static $itemtype           = 'Ticket';
    public static $items_id           = 'tickets_id';

    public static $rightname                 = 'ticketvalidation';

    const CREATEREQUEST               = 1024;
    const CREATEINCIDENT              = 2048;
    const VALIDATEREQUEST             = 4096;
    const VALIDATEINCIDENT            = 8192;



    public static function getCreateRights()
    {
        return [static::CREATEREQUEST, static::CREATEINCIDENT];
    }


    public static function getValidateRights()
    {
        return [static::VALIDATEREQUEST, static::VALIDATEINCIDENT];
    }


    /**
     * @since 0.85
     **/
    public function canCreateItem()
    {

        if ($this->canChildItem('canViewItem', 'canView')) {
            $ticket = new Ticket();
            if ($ticket->getFromDB($this->fields['tickets_id'])) {
                // No validation for closed tickets
                if (in_array($ticket->fields['status'], $ticket->getClosedStatusArray())) {
                    return false;
                }

                if ($ticket->fields['type'] == Ticket::INCIDENT_TYPE) {
                    return Session::haveRight(self::$rightname, self::CREATEINCIDENT);
                }
                if ($ticket->fields['type'] == Ticket::DEMAND_TYPE) {
                    return Session::haveRight(self::$rightname, self::CREATEREQUEST);
                }
            }
        }

        return parent::canCreateItem();
    }

    /**
     * @since 0.85
     *
     * @see commonDBTM::getRights()
     **/
    public function getRights($interface = 'central')
    {

        $values = parent::getRights();
        unset($values[UPDATE], $values[CREATE], $values[READ]);

        $values[self::CREATEREQUEST]
                              = ['short' => __('Create for request'),
                                  'long'  => __('Create a validation request for a request')
                              ];
        $values[self::CREATEINCIDENT]
                              = ['short' => __('Create for incident'),
                                  'long'  => __('Create a validation request for an incident')
                              ];
        $values[self::VALIDATEREQUEST]
                              = __('Validate a request');
        $values[self::VALIDATEINCIDENT]
                              = __('Validate an incident');

        if ($interface == 'helpdesk') {
            unset($values[PURGE]);
        }

        return $values;
    }
}
