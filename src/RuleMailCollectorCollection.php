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

/// Collector Rules collection class
class RuleMailCollectorCollection extends RuleCollection
{
   // From RuleCollection
    public $stop_on_first_match = true;
    public static $rightname           = 'rule_mailcollector';
    public $menu_option         = 'mailcollector';


    public function getTitle()
    {
        return __('Rules for assigning a ticket created through a mails receiver');
    }


    /**
     * @see RuleCollection::prepareInputDataForProcess()
     **/
    public function prepareInputDataForProcess($input, $params)
    {

        if (isset($params['mailcollector'])) {
            $input['mailcollector'] = $params['mailcollector'];
        }
        if (isset($params['_users_id_requester'])) {
            $input['_users_id_requester'] = $params['_users_id_requester'];
        }

        $fields = $this->getFieldsToLookFor();

       //Add needed ticket datas for rules processing
        if (isset($params['ticket']) && is_array($params['ticket'])) {
            foreach ($params['ticket'] as $key => $value) {
                if (in_array($key, $fields) && !isset($input[$key])) {
                    $input[$key] = $value;
                }
            }
        }

       //Add needed headers for rules processing
        if (isset($params['headers']) && is_array($params['headers'])) {
            foreach ($params['headers'] as $key => $value) {
                if (in_array($key, $fields) && !isset($input[$key])) {
                    $input[$key] = $value;
                }
            }
        }

       //Add all user's groups
        if (in_array('_groups_id_requester', $fields)) {
            foreach (Group_User::getUserGroups($input['_users_id_requester']) as $group) {
                $input['_groups_id_requester'][] = $group['id'];
            }
        }

       //Add all user's profiles
        if (in_array('profiles', $fields)) {
            foreach (Profile_User::getForUser($input['_users_id_requester']) as $profile) {
                $input['PROFILES'][$profile['profiles_id']] = $profile['profiles_id'];
            }
        }

       //If the criteria is "user has only one time the profile xxx"
        if (in_array('unique_profile', $fields)) {
           //Get all profiles
            $profiles = Profile_User::getForUser($input['_users_id_requester']);
            foreach ($profiles as $profile) {
                if (
                    Profile_User::haveUniqueRight(
                        $input['_users_id_requester'],
                        $profile['profiles_id']
                    )
                ) {
                    $input['UNIQUE_PROFILE'][$profile['profiles_id']] = $profile['profiles_id'];
                }
            }
        }

       //Store the number of profiles of which the user belongs to
        if (in_array('one_profile', $fields)) {
            $profiles = Profile_User::getForUser($input['_users_id_requester']);
            if (count($profiles) == 1) {
                $tmp = array_pop($profiles);
                $input['ONE_PROFILE'] = $tmp['profiles_id'];
            }
        }

       //Store the number of profiles of which the user belongs to
        if (in_array('known_domain', $fields)) {
            if (preg_match("/@(.*)/", $input['from'], $results)) {
                if (Entity::getEntityIDByDomain($results[1]) != -1) {
                    $input['KNOWN_DOMAIN'] = 1;
                } else {
                    $input['KNOWN_DOMAIN'] = 0;
                }
            }
        }

        return $input;
    }


    /**
     * @see RuleCollection::canList()
     **/
    public function canList()
    {

        return static::canView()
             && MailCollector::countCollectors();
    }
}
