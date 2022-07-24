<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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
 * @since 0.85
 */

$AJAX_INCLUDE = 1;
include('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

/** @global array $CFG_GLPI */

if (isset($_POST["validatortype"])) {
    switch ($_POST["validatortype"]) {
        case 'user':
            echo "<input type='hidden' name='groups_id' value=0 />";
            User::dropdown(['name'   => 'users_id_validate',
                'entity' => $_SESSION["glpiactive_entity"],
                'right'  => ['validate_request', 'validate_incident']
            ]);

            echo "<br><br>" . __('Comments') . " ";
            echo "<textarea name='comment_submission' cols='50' rows='6'></textarea>&nbsp;";

            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
            break;

        case 'group':
            echo "<input type='hidden' name='users_id_validate' value=0 />";
            $rand = Group::dropdown(['name'      => 'groups_id',
                'entity'    => $_SESSION["glpiactive_entity"]
            ]);

            $param = ['validatortype'      => 'group_user',
                'groups_id' => '__VALUE__',
                'right'     => ['validate_request', 'validate_incident']
            ];

            Ajax::updateItemOnSelectEvent(
                "dropdown_groups_id$rand",
                "show_groups_users",
                $CFG_GLPI["root_doc"] . "/ajax/dropdownMassiveActionAddValidator.php",
                $param
            );

            echo "<br><span id='show_groups_users'>&nbsp;</span>\n";
            break;

        case 'group_user':
            $opt = ['groups_id'   => $_POST["groups_id"],
                'right'     => $_POST['right'],
                'entity'    => $_SESSION["glpiactive_entity"]
            ];

            $groups_users = TicketValidation::getGroupUserHaveRights($opt);

            $users           = [];
            $param['values'] =  [];
            foreach ($groups_users as $data) {
                $users[$data['id']] = formatUserName(
                    $data['id'],
                    $data['name'],
                    $data['realname'],
                    $data['firstname']
                );
            }

            if (
                isset($_POST['all_users'])
                && $_POST['all_users']
            ) {
                $param['values'] =  array_keys($users);
            }

            $param['multiple'] = true;
            $param['display'] = true;
            $param['size']    = count($users);

            Dropdown::showFromArray("users_id_validate", $users, $param);

           // Display all/none buttons to select all or no users in group
            if (!empty($_POST['groups_id'])) {
                echo "<a id='all_users' class='btn btn-primary'>" . __('All') . "</a>";
                $param_button = [
                    'validatortype'     => 'group_user',
                    'users_id_validate' => '',
                    'all_users'         => 1,
                    'groups_id'         => $_POST['groups_id'],
                    'right'             => ['validate_request', 'validate_incident'],
                    'entity'            => $_SESSION["glpiactive_entity"],
                ];
                Ajax::updateItemOnEvent(
                    'all_users',
                    'show_groups_users',
                    $CFG_GLPI["root_doc"] . "/ajax/dropdownMassiveActionAddValidator.php",
                    $param_button,
                    ['click']
                );

                echo "&nbsp;<a id='no_users' class='btn btn-primary'>" . __('None') . "</a>";
                $param_button['all_users'] = 0;
                Ajax::updateItemOnEvent(
                    'no_users',
                    'show_groups_users',
                    $CFG_GLPI["root_doc"] . "/ajax/dropdownMassiveActionAddValidator.php",
                    $param_button,
                    ['click']
                );
            }

            echo "<br><br>" . __('Comments') . " ";
            echo "<textarea name='comment_submission' cols='50' rows='6'></textarea>&nbsp;";

            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
            break;
    }
}
