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

    public function __get(string $property)
    {
        // TODO Deprecate access to variables in GLPI 10.1.
        $value = null;
        switch ($property) {
            case 'entities':
            case 'groups':
            case 'profiles':
            case 'users':
                $value = $this->$property;
                break;
            default:
                $trace = debug_backtrace();
                trigger_error(
                    sprintf('Undefined property: %s::%s in %s on line %d', __CLASS__, $property, $trace[0]['file'], $trace[0]['line']),
                    E_USER_WARNING
                );
                break;
        }
        return $value;
    }

    public function __set(string $property, $value)
    {
        // TODO Deprecate access to variables in GLPI 10.1.
        switch ($property) {
            case 'entities':
            case 'groups':
            case 'profiles':
            case 'users':
                $this->$property = $value;
                break;
            default:
                $trace = debug_backtrace();
                trigger_error(
                    sprintf('Undefined property: %s::%s in %s on line %d', __CLASS__, $property, $trace[0]['file'], $trace[0]['line']),
                    E_USER_WARNING
                );
                break;
        }
    }

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
     * @return void
     **/
    public function showVisibility()
    {
        global $CFG_GLPI;

        $ID      = $this->fields['id'];
        $canedit = $this->canEdit($ID);
        $rand    = mt_rand();
        $nb      = $this->countVisibilities();
        $str_type = strtolower($this::getType());
        $fk = static::getForeignKeyField();

        if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form name='{$str_type}visibility_form$rand' id='{$str_type}visibility_form$rand' ";
            echo " method='post' action='" . static::getFormURL() . "'>";
            echo "<input type='hidden' name='{$fk}' value='$ID'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'><th colspan='4'>" . __('Add a target') . "</tr>";
            echo "<tr class='tab_bg_1'><td class='tab_bg_2' width='100px'>";

            $types   = ['Entity', 'Group', 'Profile', 'User'];

            $addrand = Dropdown::showItemTypes('_type', $types);
            $params = $this->getShowVisibilityDropdownParams();

            Ajax::updateItemOnSelectEvent(
                "dropdown__type" . $addrand,
                "visibility$rand",
                $CFG_GLPI["root_doc"] . "/ajax/visibility.php",
                $params
            );

            echo "</td>";
            echo "<td><span id='visibility$rand'></span>";
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }
        echo "<div class='spaced'>";
        if ($canedit && $nb) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['num_displayed'
                              => min($_SESSION['glpilist_limit'], $nb),
                'container'
                              => 'mass' . __CLASS__ . $rand,
                'specific_actions'
                              => ['delete' => _x('button', 'Delete permanently')]
            ];

            if ($this->fields['users_id'] != Session::getLoginUserID()) {
                $massiveactionparams['confirm']
                = __('Caution! You are not the author of this element. Delete targets can result in loss of access to that element.');
            }
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixehov'>";
        $header_begin  = "<tr>";
        $header_top    = '';
        $header_bottom = '';
        $header_end    = '';
        if ($canedit && $nb) {
            $header_begin  .= "<th width='10'>";
            $header_top    .= Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_bottom .= Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_end    .= "</th>";
        }
        $header_end .= "<th>" . _n('Type', 'Types', 1) . "</th>";
        $header_end .= "<th>" . _n('Recipient', 'Recipients', Session::getPluralNumber()) . "</th>";
        $header_end .= "</tr>";
        echo $header_begin . $header_top . $header_end;

       // Users
        if (count($this->users)) {
            foreach ($this->users as $val) {
                foreach ($val as $data) {
                    echo "<tr class='tab_bg_1'>";
                    if ($canedit) {
                        echo "<td>";
                        Html::showMassiveActionCheckBox($this::getType() . '_User', $data["id"]);
                        echo "</td>";
                    }
                    echo "<td>" . User::getTypeName(1) . "</td>";
                    echo "<td>" . getUserName($data['users_id']) . "</td>";
                    echo "</tr>";
                }
            }
        }

       // Groups
        if (count($this->groups)) {
            foreach ($this->groups as $val) {
                foreach ($val as $data) {
                    echo "<tr class='tab_bg_1'>";
                    if ($canedit) {
                        echo "<td>";
                        Html::showMassiveActionCheckBox('Group_' . $this::getType(), $data["id"]);
                        echo "</td>";
                    }
                    echo "<td>" . Group::getTypeName(1) . "</td>";

                    $names   = Dropdown::getDropdownName('glpi_groups', $data['groups_id'], 1);
                    $entname = sprintf(
                        __('%1$s %2$s'),
                        $names["name"],
                        Html::showToolTip($names["comment"], ['display' => false])
                    );
                    if ($data['entities_id'] !== null) {
                        $entname = sprintf(
                            __('%1$s / %2$s'),
                            $entname,
                            Dropdown::getDropdownName(
                                'glpi_entities',
                                $data['entities_id']
                            )
                        );
                        if ($data['is_recursive']) {
                             //TRANS: R for Recursive
                             $entname = sprintf(
                                 __('%1$s %2$s'),
                                 $entname,
                                 "<span class='b'>(" . __('R') . ")</span>"
                             );
                        }
                    }
                     echo "<td>" . $entname . "</td>";
                     echo "</tr>";
                }
            }
        }

       // Entity
        if (count($this->entities)) {
            foreach ($this->entities as $val) {
                foreach ($val as $data) {
                    echo "<tr class='tab_bg_1'>";
                    if ($canedit) {
                        echo "<td>";
                        Html::showMassiveActionCheckBox('Entity_' . $this::getType(), $data["id"]);
                        echo "</td>";
                    }
                    echo "<td>" . Entity::getTypeName(1) . "</td>";
                    $names   = Dropdown::getDropdownName('glpi_entities', $data['entities_id'], 1);
                    $tooltip = Html::showToolTip($names["comment"], ['display' => false]);
                    $entname = sprintf(__('%1$s %2$s'), $names["name"], $tooltip);
                    if ($data['is_recursive']) {
                        $entname = sprintf(
                            __('%1$s %2$s'),
                            $entname,
                            "<span class='b'>(" . __('R') . ")</span>"
                        );
                    }
                    echo "<td>" . $entname . "</td>";
                    echo "</tr>";
                }
            }
        }

       // Profiles
        if (count($this->profiles)) {
            foreach ($this->profiles as $val) {
                foreach ($val as $data) {
                    echo "<tr class='tab_bg_1'>";
                    if ($canedit) {
                        echo "<td>";
                      //Knowledgebase-specific case
                        if ($this::getType() === "KnowbaseItem") {
                             Html::showMassiveActionCheckBox($this::getType() . '_Profile', $data["id"]);
                        } else {
                            Html::showMassiveActionCheckBox('Profile_' . $this::getType(), $data["id"]);
                        }
                        echo "</td>";
                    }
                    echo "<td>" . _n('Profile', 'Profiles', 1) . "</td>";

                    $names   = Dropdown::getDropdownName('glpi_profiles', $data['profiles_id'], 1);
                    $tooltip = Html::showToolTip($names["comment"], ['display' => false]);
                    $entname = sprintf(__('%1$s %2$s'), $names["name"], $tooltip);
                    if ($data['entities_id'] !== null) {
                        $entname = sprintf(
                            __('%1$s / %2$s'),
                            $entname,
                            Dropdown::getDropdownName(
                                'glpi_entities',
                                $data['entities_id']
                            )
                        );
                        if ($data['is_recursive']) {
                            $entname = sprintf(
                                __('%1$s %2$s'),
                                $entname,
                                "<span class='b'>(" . __('R') . ")</span>"
                            );
                        }
                    }
                    echo "<td>" . $entname . "</td>";
                    echo "</tr>";
                }
            }
        }

        if ($nb) {
            echo $header_begin . $header_bottom . $header_end;
        }
        echo "</table>";
        if ($canedit && $nb) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }

        echo "</div>";
       // Add items

        return true;
    }

    /**
     * Get dropdown parameters from showVisibility method
     *
     * @return array
     */
    protected function getShowVisibilityDropdownParams()
    {
        return [
            'type'  => '__VALUE__',
            'right' => strtolower($this::getType()) . '_public'
        ];
    }
}
