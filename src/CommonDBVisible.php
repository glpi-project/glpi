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

use Glpi\Application\View\TemplateRenderer;

/**
 * Common DataBase visibility for items
 */
abstract class CommonDBVisible extends CommonDBTM
{
    /**
     * Entities on which item is visible.
     * Keys are ID, values are DB fields values.
     * @var array
     */
    protected $entities = [];

    /**
     * Groups for whom item is visible.
     * Keys are ID, values are DB fields values.
     * @var array
     */
    protected $groups = [];

    /**
     * Profiles for whom item is visible.
     * Keys are ID, values are DB fields values.
     * @var array
     */
    protected $profiles = [];

    /**
     * Users for whom item is visible.
     * Keys are ID, values are DB fields values.
     * @var array
     */
    protected $users = [];

    /**
     * Is the login user have access to item based on visibility configuration
     *
     * @since 0.83
     * @since 9.2 moved from each class to parent class
     *
     * @return boolean
     **/
    public function haveVisibilityAccess()
    {
        // Author
        if ($this->fields['users_id'] == Session::getLoginUserID()) {
            return true;
        }
        // Users
        if (isset($this->users[Session::getLoginUserID()])) {
            return true;
        }

        // Groups
        if (
            count($this->groups)
            && isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"])
        ) {
            foreach ($this->groups as $data) {
                foreach ($data as $group) {
                    if (in_array($group['groups_id'], $_SESSION["glpigroups"])) {
                        // All the group
                        if ($group['no_entity_restriction']) {
                            return true;
                        }
                        // Restrict to entities
                        if (Session::haveAccessToEntity($group['entities_id'], $group['is_recursive'])) {
                            return true;
                        }
                    }
                }
            }
        }

        // Entities
        if (
            count($this->entities)
            && isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"])
        ) {
            foreach ($this->entities as $data) {
                foreach ($data as $entity) {
                    if (Session::haveAccessToEntity($entity['entities_id'], $entity['is_recursive'])) {
                        return true;
                    }
                }
            }
        }

        // Profiles
        if (
            count($this->profiles)
            && isset($_SESSION["glpiactiveprofile"])
            && isset($_SESSION["glpiactiveprofile"]['id'])
        ) {
            if (isset($this->profiles[$_SESSION["glpiactiveprofile"]['id']])) {
                foreach ($this->profiles[$_SESSION["glpiactiveprofile"]['id']] as $profile) {
                    // All the profile
                    if ($profile['no_entity_restriction']) {
                        return true;
                    }
                    // Restrict to entities
                    if (Session::haveAccessToEntity($profile['entities_id'], $profile['is_recursive'])) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Count visibilities
     *
     * @since 0.83
     * @since 9.2 moved from each class to parent class
     *
     * @return integer
     */
    public function countVisibilities()
    {

        return (count($this->entities)
              + count($this->users)
              + count($this->groups)
              + count($this->profiles));
    }

    /**
     * Show visibility configuration
     *
     * @since 9.2 moved from each class to parent class
     *
     * @return bool
     **/
    public function showVisibility(): bool
    {
        $ID      = (int) $this->fields['id'];
        $canedit = $this->canEdit($ID);
        $rand    = mt_rand();

        if ($canedit) {
            TemplateRenderer::getInstance()->display('components/add_visibility_target.html.twig', [
                'type' => static::class,
                'rand' => $rand,
                'id'   => $ID,
                'add_target_msg' => __('Add a target'),
                'visiblity_dropdown_params' => $this->getShowVisibilityDropdownParams(),
            ]);
        }

        $entries = [];

        foreach ($this->users as $val) {
            foreach ($val as $data) {
                $entries[] = [
                    'itemtype' => static::class . '_User',
                    'id' => $data['id'],
                    'type' => User::getTypeName(1),
                    'recipient' => htmlescape(getUserName($data['users_id'])),
                ];
            }
        }

        foreach ($this->groups as $val) {
            foreach ($val as $data) {
                $name    = Dropdown::getDropdownName('glpi_groups', $data['groups_id']);
                $tooltip = Dropdown::getDropdownComments('glpi_groups', (int) $data['groups_id']);
                $recipient = sprintf(
                    __s('%1$s %2$s'),
                    htmlescape($name),
                    Html::showToolTip($tooltip, ['display' => false])
                );
                if ($data['entities_id'] !== null) {
                    $recipient = sprintf(
                        __s('%1$s / %2$s'),
                        $recipient,
                        htmlescape(
                            Dropdown::getDropdownName(
                                'glpi_entities',
                                $data['entities_id']
                            )
                        )
                    );
                    if ($data['is_recursive']) {
                        $recipient = sprintf(
                            __s('%1$s %2$s'),
                            $recipient,
                            "<span class='fw-bold'>(" . __s('R') . ")</span>"
                        );
                    }
                }
                $entries[] = [
                    'itemtype' => 'Group_' . static::class,
                    'id' => $data['id'],
                    'type' => Group::getTypeName(1),
                    'recipient' => $recipient,
                ];
            }
        }

        foreach ($this->entities as $val) {
            foreach ($val as $data) {
                $name    = Dropdown::getDropdownName('glpi_entities', $data['entities_id']);
                $tooltip = Dropdown::getDropdownComments('glpi_entities', (int) $data['entities_id']);
                $recipient = sprintf(
                    __s('%1$s %2$s'),
                    htmlescape($name),
                    Html::showToolTip($tooltip, ['display' => false])
                );
                if ($data['is_recursive']) {
                    $recipient = sprintf(
                        __s('%1$s %2$s'),
                        $recipient,
                        "<span class='fw-bold'>(" . __s('R') . ")</span>"
                    );
                }
                $entries[] = [
                    'itemtype' => 'Entity_' . static::class,
                    'id' => $data['id'],
                    'type' => Entity::getTypeName(1),
                    'recipient' => $recipient,
                ];
            }
        }

        foreach ($this->profiles as $val) {
            foreach ($val as $data) {
                $name    = Dropdown::getDropdownName('glpi_profiles', $data['profiles_id']);
                $tooltip = Dropdown::getDropdownComments('glpi_profiles', (int) $data['profiles_id']);
                $recipient = sprintf(
                    __s('%1$s %2$s'),
                    htmlescape($name),
                    Html::showToolTip($tooltip, ['display' => false])
                );
                if ($data['entities_id'] !== null) {
                    $recipient = sprintf(
                        __s('%1$s / %2$s'),
                        $recipient,
                        htmlescape(
                            Dropdown::getDropdownName(
                                'glpi_entities',
                                $data['entities_id']
                            )
                        )
                    );
                    if ($data['is_recursive']) {
                        $recipient = sprintf(
                            __s('%1$s %2$s'),
                            $recipient,
                            "<span class='fw-bold'>(" . __s('R') . ")</span>"
                        );
                    }
                }
                $entries[] = [
                    'itemtype' => static::class === KnowbaseItem::class ? (static::class . '_Profile') : ('Profile_' . static::class),
                    'id' => $data['id'],
                    'type' => Profile::getTypeName(1),
                    'recipient' => $recipient,
                ];
            }
        }

        $massiveactionparams = [
            'num_displayed' => count($entries),
            'container' => 'mass' . static::class . $rand,
            'specific_actions' => ['delete' => _x('button', 'Delete permanently')],
        ];
        if ($this->fields['users_id'] !== Session::getLoginUserID()) {
            $massiveactionparams['confirm'] = __('Caution! You are not the author of this item. Deleting targets can result in loss of access.');
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'type' => _n('Type', 'Types', 1),
                'recipient' => _n('Recipient', 'Recipients', 1),
            ],
            'formatters' => [
                'recipient' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => $massiveactionparams,
        ]);

        return true;
    }

    /**
     * Get dropdown parameters from showVisibility method
     *
     * @return array
     */
    protected function getShowVisibilityDropdownParams()
    {
        $params = [
            'type'          => '__VALUE__',
            'right'         => strtolower($this::getType()) . '_public',
        ];
        if (isset($this->fields['entities_id'])) {
            $params['entity'] = $this->fields['entities_id'];
        }
        if (isset($this->fields['is_recursive'])) {
            $params['is_recursive'] = $this->fields['is_recursive'];
        }
        return $params;
    }
}
