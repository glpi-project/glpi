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
 * Ticket Recurrent class
 *
 * @since 0.83
 **/
class TicketRecurrent extends CommonITILRecurrent
{
    /**
     * @var string CommonDropdown
     */
    public $second_level_menu = "ticketrecurrent";

    /**
     * @var string Right managements
     */
    public static $rightname = 'ticketrecurrent';

    public static function getTypeName($nb = 0)
    {
        return __('Recurrent tickets');
    }

    public static function getConcreteClass()
    {
        return Ticket::class;
    }

    public static function getTemplateClass()
    {
        return TicketTemplate::class;
    }

    public static function getPredefinedFieldsClass()
    {
        return TicketTemplatePredefinedField::class;
    }

    public function handlePredefinedFields(
        array $predefined,
        array $input
    ): array {
        $input = parent::handlePredefinedFields($predefined, $input);

       // Compute internal_time_to_resolve if predefined based on create date
        if (isset($predefined['internal_time_to_resolve'])) {
            $input['internal_time_to_resolve'] = Html::computeGenericDateTimeSearch(
                $predefined['internal_time_to_resolve'],
                false,
                $this->getCreateTime()
            );
        }

        return $input;
    }
}
