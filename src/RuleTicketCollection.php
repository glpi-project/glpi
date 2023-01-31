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

class RuleTicketCollection extends RuleCollection
{
   // From RuleCollection
    public static $rightname                             = 'rule_ticket';
    public $use_output_rule_process_as_next_input = true;
    public $menu_option                           = 'ticket';


    /**
     * @param $entity (default 0)
     **/
    public function __construct($entity = 0)
    {
        $this->entity = $entity;
    }


    /**
     * @since 0.84
     **/
    public static function canView()
    {
        return Session::haveRightsOr(self::$rightname, [READ, RuleTicket::PARENT]);
    }


    public function canList()
    {
        return static::canView();
    }


    public function getTitle()
    {
        return __('Business rules for tickets');
    }


    /**
     * @see RuleCollection::preProcessPreviewResults()
     **/
    public function preProcessPreviewResults($output)
    {

        $output = parent::preProcessPreviewResults($output);
        return Ticket::showPreviewAssignAction($output);
    }


    /**
     * @see RuleCollection::showInheritedTab()
     **/
    public function showInheritedTab()
    {
        return (Session::haveRight(self::$rightname, RuleTicket::PARENT) && ($this->entity));
    }


    /**
     * @see RuleCollection::showChildrensTab()
     **/
    public function showChildrensTab()
    {

        return (Session::haveRight(self::$rightname, READ)
              && (count($_SESSION['glpiactiveentities']) > 1));
    }


    /**
     * @see RuleCollection::prepareInputDataForProcess()
     **/
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

        $input['_groups_id_of_requester'] = [];
       // Get groups of users
        if (isset($input['_users_id_requester'])) {
            if (!is_array($input['_users_id_requester'])) {
                $requesters = [$input['_users_id_requester']];
            } else {
                $requesters = $input['_users_id_requester'];
            }
            foreach ($requesters as $uid) {
                foreach (Group_User::getUserGroups($uid) as $g) {
                    $input['_groups_id_of_requester'][$g['id']] = $g['id'];
                }
            }
        }

        return $input;
    }
}
