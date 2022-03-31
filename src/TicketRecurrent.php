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

    public $userlinkclass  = 'Ticket_User';

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


    public function defineTabs($options = [])
    {
        $ong = parent::defineTabs($options);
        $this->addStandardTab('Item_TicketRecurrent', $ong, $options);
        return $ong;
    }
}
