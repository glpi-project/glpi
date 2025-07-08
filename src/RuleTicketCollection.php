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

class RuleTicketCollection extends RuleCommonITILObjectCollection
{
    // From RuleCollection
    public static $rightname    = 'rule_ticket';
    public $menu_option         = 'ticket';

    public function getTitle()
    {
        return __('Business rules for tickets');
    }

    public function prepareInputDataForProcess($input, $params)
    {
        // Pass x-priority header if exists
        if (isset($input['_head']['x-priority'])) {
            $input['_x-priority'] = $input['_head']['x-priority'];
        }

        // Pass From header if exists
        if (isset($input['_head']['from'])) {
            $input['_from'] = $input['_head']['from'];
        }

        // Pass Subject header if exists
        if (isset($input['_head']['subject'])) {
            $input['_subject'] = $input['_head']['subject'];
        }

        // Pass Reply-To header if exists
        if (isset($input['_head']['reply-to'])) {
            $input['_reply-to'] = $input['_head']['reply-to'];
        }

        // Pass In-Reply-To header if exists
        if (isset($input['_head']['in-reply-to'])) {
            $input['_in-reply-to'] = $input['_head']['in-reply-to'];
        }

        // Pass To header if exists
        if (isset($input['_head']['to'])) {
            $input['_to'] = $input['_head']['to'];
        }

        return parent::prepareInputDataForProcess($input, $params);
    }
}
