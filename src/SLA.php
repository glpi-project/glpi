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
 * @since 9.2
 */


/**
 * SLA Class
 **/
class SLA extends LevelAgreement
{
    protected static $prefix            = 'sla';
    protected static $prefixticket      = '';
    protected static $levelclass        = 'SlaLevel';
    protected static $levelticketclass  = 'SlaLevel_Ticket';
    protected static $forward_entity_to = ['SlaLevel'];

    public static function getTypeName($nb = 0)
    {
       // Acronymous, no plural
        return __('SLA');
    }

    public function showFormWarning()
    {
    }

    public function getAddConfirmation()
    {
        return [__("The assignment of a SLA to a ticket causes the recalculation of the date."),
            __("Escalations defined in the SLA will be triggered under this new date.")
        ];
    }
}
