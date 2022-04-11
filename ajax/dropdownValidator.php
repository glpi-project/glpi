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
 * @since 0.85
 */

$AJAX_INCLUDE = 1;
include('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (isset($_POST["validatortype"])) {
    if (isset($_POST['users_id_validate'])) {
        Toolbox::deprecated('Usage of "users_id_validate" parameter is deprecated in "ajax/dropdownValidator.php". Use "items_id_target" instead.');
        $_POST['items_id_target'] = $_POST['users_id_validate'];
    }

    switch (strtolower($_POST["validatortype"])) {
        case 'user':
            if (isset($_POST['items_id_target']['groups_id'])) {
                $_POST['items_id_target'] = [];
            }
            $value = (isset($_POST['items_id_target'][0]) ? $_POST['items_id_target'][0] : 0);
            User::dropdown([
                'name'   => !empty($_POST['name']) ? $_POST['name'] . '[]' : 'items_id_target[]',
                'entity' => $_POST['entity'],
                'value'  => $value,
                'right'  => $_POST['right'],
                'width'  => '100%',
            ]);
            echo Html::hidden('itemtype_target', ['value' => 'User']);
            break;

        case 'group':
            $value = (isset($_POST['items_id_target'][0]) ? $_POST['items_id_target'][0] : 0);
            Group::dropdown([
                'name'   => !empty($_POST['name']) ? $_POST['name'] . '[]' : 'items_id_target[]',
                'entity' => $_POST['entity'],
                'value'  => $value,
                'right'  => $_POST['right'],
                'width'  => '100%',
            ]);
            echo Html::hidden('itemtype_target', ['value' => 'Group']);
            break;

        case 'group_user':
            $name = 'groups_id';
            $value = $_POST['groups_id'];

            $rand = Group::dropdown([
                'name'   => $name,
                'value'  => $value,
                'entity' => $_POST["entity"],
                'width'  => '100%',
            ]);
            echo Html::hidden('itemtype_target', ['value' => 'User']);

            $param                        = ['validatortype' => 'list_users'];
            $param['name']                = !empty($_POST['name']) ? $_POST['name'] : '';
            $param['items_id_target']   = isset($_POST['items_id_target'])
                                             ? $_POST['items_id_target'] : '';
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
                Ajax::updateItem(
                    'show_list_users',
                    $CFG_GLPI["root_doc"] . "/ajax/dropdownValidator.php",
                    $param
                );
            }
            echo "<br><span id='show_list_users'>&nbsp;</span>";
            break;

        case 'list_users':
            if (isset($_POST['groups_id'])) {
                $_POST['items_id_target'] = [];
            }
            $opt             = ['groups_id' => $_POST["groups_id"],
                'right'     => $_POST['right'],
                'entity'    => $_POST["entity"]
            ];
            $data_users      = TicketValidation::getGroupUserHaveRights($opt);
            $users           = [];
            $param['values'] = [];
            $values          = [];
            if (isset($_POST['items_id_target']) && is_array($_POST['items_id_target'])) {
                $values = $_POST['items_id_target'];
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
                !empty($_POST['name']) ? $_POST['name'] : 'items_id_target',
                $users,
                $param
            );

           // Display all/none buttons to select all or no users in group
            if (!empty($_POST['groups_id'])) {
                $param_button = [
                    'validatortype'     => 'list_users',
                    'name'              => !empty($_POST['name']) ? $_POST['name'] : '',
                    'items_id_target'   => '',
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
