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

/**
 * @since 0.85
 */

/** @var array $CFG_GLPI */
global $CFG_GLPI;

$AJAX_INCLUDE = 1;
include('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (isset($_POST["validatortype"])) {
    switch ($_POST["validatortype"]) {
        case 'user':
        case 'User':
            if (isset($_POST['users_id_validate']['groups_id'])) {
                $_POST['users_id_validate'] = [];
            }
            $value = ($_POST['users_id_validate'][0] ?? 0);
            User::dropdown([
                'name'   => !empty($_POST['name']) ? $_POST['name'] . '[]' : 'users_id_validate[]',
                'entity' => $_POST['entity'],
                'value'  => $value,
                'right'  => $_POST['right'],
                'width'  => '100%',
            ]);
            break;

        case 'group':
        case 'Group':
            $name = !empty($_POST['name']) ? $_POST['name'] . '[groups_id]' : 'groups_id';
            $value = ($_POST['users_id_validate']['groups_id'] ?? $_POST['groups_id']);

            $rand = Group::dropdown([
                'name'   => $name,
                'value'  => $value,
                'entity' => $_POST["entity"],
                'width'  => '100%',
            ]);

            $param                        = ['validatortype' => 'list_users'];
            $param['name']                = !empty($_POST['name']) ? $_POST['name'] : '';
            $param['users_id_validate']   = $_POST['users_id_validate'] ?? '';
            $param['right']               = $_POST['right'];
            $param['entity']              = $_POST["entity"];
            $param['groups_id']           = '__VALUE__';
            Ajax::updateItemOnSelectEvent(
                "dropdown_$name$rand",
                "show_list_users",
                $CFG_GLPI["root_doc"] . "/ajax/dropdownValidator.php",
                $param
            );
            if ($value) {
                $param['validatortype'] = 'list_users';
                $param['groups_id']     = $value;
                unset($param['users_id_validate']['groups_id']);
                Ajax::updateItem(
                    'show_list_users',
                    $CFG_GLPI["root_doc"] . "/ajax/dropdownValidator.php",
                    $param
                );
            }
            echo "<br><span id='show_list_users'>&nbsp;</span>";
            break;

        case 'list_users':
            if (isset($_POST['users_id_validate']['groups_id'])) {
                $_POST['users_id_validate'] = [];
            }
            $opt             = ['groups_id' => $_POST["groups_id"],
                'right'     => $_POST['right'],
                'entity'    => $_POST["entity"],
            ];
            $data_users      = TicketValidation::getGroupUserHaveRights($opt);
            $users           = [];
            $param['values'] = [];
            $values          = [];
            if (isset($_POST['users_id_validate']) && is_array($_POST['users_id_validate'])) {
                $values = $_POST['users_id_validate'];
            }
            foreach ($data_users as $data) {
                $users[$data['id']] = formatUserName(
                    $data['id'],
                    $data['name'],
                    $data['realname'],
                    $data['firstname']
                );
                if (in_array($data['id'], $values)) {
                    $param['values'][] = $data['id'];
                }
            }

            // Display all users
            if (
                isset($_POST['all_users'])
                && $_POST['all_users']
            ) {
                $param['values'] =  array_keys($users);
            }
            $param['multiple'] = true;
            $param['display'] = true;
            $param['size']    = count($users);

            $rand  = Dropdown::showFromArray(
                !empty($_POST['name']) ? $_POST['name'] : 'users_id_validate',
                $users,
                $param
            );

            // Display all/none buttons to select all or no users in group
            if (!empty($_POST['groups_id'])) {
                $param_button = [
                    'validatortype'     => 'list_users',
                    'name'              => !empty($_POST['name']) ? $_POST['name'] : '',
                    'users_id_validate' => '',
                    'all_users'         => 1,
                    'groups_id'         => $_POST['groups_id'],
                    'entity'            => $_POST['entity'],
                    'right'             => $_POST['right'],
                ];
                Ajax::updateItemOnEvent(
                    'all_users',
                    'show_list_users',
                    $CFG_GLPI["root_doc"] . "/ajax/dropdownValidator.php",
                    $param_button,
                    ['click']
                );

                $param_button['all_users'] = 0;
                Ajax::updateItemOnEvent(
                    'no_users',
                    'show_list_users',
                    $CFG_GLPI["root_doc"] . "/ajax/dropdownValidator.php",
                    $param_button,
                    ['click']
                );
            }
            break;
    }
}
