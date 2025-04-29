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

/// Rule class for Rights management
class RuleMailCollector extends Rule
{
    // From Rule
    public static $rightname = 'rule_mailcollector';
    public $orderby   = "name";
    public $can_sort  = true;

    /**
     * @see Rule::getTitle()
     **/
    public function getTitle()
    {
        return __('Rules for assigning a ticket created through a mails receiver');
    }


    /**
     * @see Rule::getCriterias()
     **/
    public function getCriterias()
    {

        static $criterias = [];

        if (count($criterias)) {
            return $criterias;
        }

        $criterias['mailcollector']['field']            = 'name';
        $criterias['mailcollector']['name']             = __('Mails receiver');
        $criterias['mailcollector']['table']            = 'glpi_mailcollectors';
        $criterias['mailcollector']['type']             = 'dropdown';

        $criterias['_users_id_requester']['field']      = 'name';
        $criterias['_users_id_requester']['name']       = _n('Requester', 'Requesters', 1);
        $criterias['_users_id_requester']['table']      = 'glpi_users';
        $criterias['_users_id_requester']['type']       = 'dropdown';

        $criterias['subject']['name']                   = __('Subject email header');
        $criterias['subject']['field']                  = 'subject';
        $criterias['subject']['table']                  = '';
        $criterias['subject']['type']                   = 'text';

        $criterias['content']['name']                   = __('Email body');
        $criterias['content']['table']                  = '';
        $criterias['content']['type']                   = 'text';

        $criterias['from']['name']                      = __('From email header');
        $criterias['from']['table']                     = '';
        $criterias['from']['type']                      = 'text';

        $criterias['to']['name']                        = __('To email header');
        $criterias['to']['table']                       = '';
        $criterias['to']['type']                        = 'text';

        $criterias['in_reply_to']['name']               = __('In-Reply-To email header');
        $criterias['in_reply_to']['table']              = '';
        $criterias['in_reply_to']['type']               = 'text';

        $criterias['x-priority']['name']                = __('X-Priority email header');
        $criterias['x-priority']['table']               = '';
        $criterias['x-priority']['type']                = 'text';

        $criterias['x-auto-response-suppress']['name']  = __('X-Auto-Response-Suppress email header');
        $criterias['x-auto-response-suppress']['table'] = '';
        $criterias['x-auto-response-suppress']['type']  = 'text';

        $criterias['auto-submitted']['name']            = __('Auto-Submitted email header');
        $criterias['auto-submitted']['table']           = '';
        $criterias['auto-submitted']['type']            = 'text';

        /// Renater spam matching : X-UCE-Status = Yes
        $criterias['x-uce-status']['name']              = __('X-UCE-Status email header');
        $criterias['x-uce-status']['table']             = '';
        $criterias['x-uce-status']['type']              = 'text';

        $criterias['_headers'] = [
            'name'  => __('Full email headers'),
            'table' => '',
            'type'  => 'text',
        ];

        $criterias['received']['name']                  = __('Received email header');
        $criterias['received']['table']                 = '';
        $criterias['received']['type']                  = 'text';

        $criterias['_groups_id_requester']['table']     = 'glpi_groups';
        $criterias['_groups_id_requester']['field']     = 'completename';
        $criterias['_groups_id_requester']['name']      = sprintf(
            __('%1$s: %2$s'),
            User::getTypeName(1),
            Group::getTypeName(1)
        );
        $criterias['_groups_id_requester']['linkfield'] = '';
        $criterias['_groups_id_requester']['type']      = 'dropdown';
        $criterias['_groups_id_requester']['virtual']   = true;
        $criterias['_groups_id_requester']['id']        = 'groups';

        $criterias['KNOWN_DOMAIN']['field']             = 'name';
        $criterias['KNOWN_DOMAIN']['name']              = __('Known mail domain');
        $criterias['KNOWN_DOMAIN']['table']             = 'glpi_entities';
        $criterias['KNOWN_DOMAIN']['type']              = 'yesno';
        $criterias['KNOWN_DOMAIN']['virtual']           = true;
        $criterias['KNOWN_DOMAIN']['id']                = 'entitydatas';
        $criterias['KNOWN_DOMAIN']['allow_condition']   = [Rule::PATTERN_IS];

        $criterias['PROFILES']['field']                 = 'name';
        $criterias['PROFILES']['name']                  = __('User featuring the profile');
        $criterias['PROFILES']['table']                 = 'glpi_profiles';
        $criterias['PROFILES']['type']                  = 'dropdown';
        $criterias['PROFILES']['virtual']               = true;
        $criterias['PROFILES']['id']                    = 'profiles';
        $criterias['PROFILES']['allow_condition']          = [Rule::PATTERN_IS];

        if (Session::isMultiEntitiesMode()) {
            $criterias['UNIQUE_PROFILE']['field']           = 'name';
            $criterias['UNIQUE_PROFILE']['name']            = __('User featuring a single profile');
            $criterias['UNIQUE_PROFILE']['table']           = 'glpi_profiles';
            $criterias['UNIQUE_PROFILE']['type']            = 'dropdown';
            $criterias['UNIQUE_PROFILE']['virtual']         = true;
            $criterias['UNIQUE_PROFILE']['id']              = 'profiles';
            $criterias['UNIQUE_PROFILE']['allow_condition'] = [Rule::PATTERN_IS];
        }

        $criterias['ONE_PROFILE']['field']              = 'name';
        $criterias['ONE_PROFILE']['name']               = __('User with a single profile');
        $criterias['ONE_PROFILE']['table']              = '';
        $criterias['ONE_PROFILE']['type']               = 'yesonly';
        $criterias['ONE_PROFILE']['virtual']            = true;
        $criterias['ONE_PROFILE']['id']                 = 'profiles';
        $criterias['ONE_PROFILE']['allow_condition']    = [Rule::PATTERN_IS];

        return $criterias;
    }


    /**
     * @see Rule::getActions()
     **/
    public function getActions()
    {

        $actions                                              = parent::getActions();

        $actions['entities_id']['name']                       = Entity::getTypeName(1);
        $actions['entities_id']['type']                       = 'dropdown';
        $actions['entities_id']['table']                      = 'glpi_entities';

        $actions['_affect_entity_by_domain']['name']          = __('Entity from domain');
        $actions['_affect_entity_by_domain']['type']          = 'text';
        $actions['_affect_entity_by_domain']['force_actions'] = ['regex_result'];

        $actions['_affect_entity_by_tag']['name']             = __('Entity from TAG');
        $actions['_affect_entity_by_tag']['type']             = 'text';
        $actions['_affect_entity_by_tag']['force_actions']    = ['regex_result'];

        $actions['_affect_entity_by_user_entity']['name']     = __("Entity based on user's profile");
        $actions['_affect_entity_by_user_entity']['type']     = 'yesonly';
        $actions['_affect_entity_by_user_entity']['table']    = '';

        $actions['_refuse_email_no_response']['name']    = sprintf(
            __('%1$s (%2$s)'),
            __('Reject email'),
            __('without email response')
        );
        $actions['_refuse_email_no_response']['type']    = 'yesonly';
        $actions['_refuse_email_no_response']['table']   = '';

        $actions['_refuse_email_with_response']['name']  = sprintf(
            __('%1$s (%2$s)'),
            __('Reject email'),
            __('with email response')
        );
        $actions['_refuse_email_with_response']['type']  = 'yesonly';
        $actions['_refuse_email_with_response']['table'] = '';

        return $actions;
    }


    public function executeActions($output, $params, array $input = [])
    {

        if (count($this->actions)) {
            foreach ($this->actions as $action) {
                switch ($action->fields["action_type"]) {
                    case "assign":
                        switch ($action->fields["field"]) {
                            default:
                                $output[$action->fields["field"]] = $action->fields["value"];
                                break;

                            case "_affect_entity_by_user_entity":
                                //3 cases :
                                //1 - rule contains a criteria like : Profil is XXXX
                                //    -> in this case, profiles_id is stored in
                                //       $this->criterias_results['PROFILES'] (one value possible)
                                //2-   rule contains criteria "User has only one profile"
                                //    -> in this case, profiles_id is stored in
                                //       $this->criterias_results['PROFILES'] (one value possible) (same as 1)
                                //3   -> rule contains only one profile
                                $profile = 0;

                                //Case 2:
                                if (isset($this->criterias_results['ONE_PROFILE'])) {
                                    $profile = $this->criterias_results['ONE_PROFILE'];
                                } elseif (isset($this->criterias_results['UNIQUE_PROFILE'])) {
                                    //Case 3
                                    $profile = $this->criterias_results['UNIQUE_PROFILE'];
                                } elseif (isset($this->criterias_results['PROFILES'])) {
                                    //Case 1
                                    $profile = $this->criterias_results['PROFILES'];
                                }

                                if ($profile) {
                                    $entities = [];
                                    if (isset($params['_users_id_requester'])) { // Not set when testing
                                        $entities
                                        = Profile_User::getEntitiesForProfileByUser(
                                            $params['_users_id_requester'],
                                            $profile
                                        );
                                    }

                                    //Case 2 : check if there's only one profile for this user
                                    if (
                                        (isset($this->criterias_results['ONE_PROFILE'])
                                        && (count($entities) == 1))
                                        || !isset($this->criterias_results['ONE_PROFILE'])
                                    ) {
                                        if (count($entities) == 1) {
                                            //User has right on only one entity
                                            $output['entities_id'] = array_pop($entities);
                                        } elseif (isset($this->criterias_results['UNIQUE_PROFILE'])) {
                                            $output['entities_id'] = array_pop($entities);
                                        } else {
                                            //Rights on more than one entity : get the user's prefered entity
                                            if (isset($params['_users_id_requester'])) { // Not set when testing
                                                $user = new User();
                                                $user->getFromDB($params['_users_id_requester']);

                                                $tmpid = $user->getField('entities_id');

                                                // Retrieve all the entities (pref could be set on a child)
                                                $entities
                                                = Profile_User::getEntitiesForProfileByUser(
                                                    $params['_users_id_requester'],
                                                    $profile,
                                                    true
                                                );

                                                // If an entity is defined in user's preferences,
                                                // and this entity allowed for this profile, use this one
                                                // else do not set the rule as matched
                                                if (in_array($tmpid, $entities)) {
                                                    $output['entities_id'] = $user->fields['entities_id'];
                                                }
                                            }
                                        }
                                    }
                                }
                        }
                        break;

                    case "regex_result":
                        foreach ($this->regex_results as $regex_result) {
                            $entity_found = -1;
                            $res          = RuleAction::getRegexResultById(
                                $action->fields["value"],
                                $regex_result
                            );
                            if ($res != null) {
                                switch ($action->fields["field"]) {
                                    case "_affect_entity_by_domain":
                                        $entity_found = Entity::getEntityIDByDomain(addslashes($res));
                                        break;

                                    case "_affect_entity_by_tag":
                                        $entity_found = Entity::getEntityIDByTag(addslashes($res));
                                        break;
                                }

                                //If an entity was found
                                if ($entity_found > -1) {
                                    $output['entities_id'] = $entity_found;
                                    break;
                                }
                            }
                        }
                        break;
                    default:
                        //Allow plugins actions
                        $executeaction = clone $this;
                        $output = $executeaction->executePluginsActions($action, $output, $params, $input);
                        break;
                }
            }
        }
        return $output;
    }


    public static function getIcon()
    {
        return MailCollector::getIcon();
    }
}
