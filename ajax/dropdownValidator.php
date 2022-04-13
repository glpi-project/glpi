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
    if (array_key_exists('name', $_POST)) {
        Toolbox::deprecated('Usage of "name" parameter is deprecated in "ajax/dropdownValidator.php". Use "prefix" instead.');
        $itemtype_name      = 'itemtype_validate';
        $items_id_name      = !empty($_POST['name']) ? $_POST['name'] . '[]' : 'users_id_validate[]';
        $groups_id_name     = 'groups_id';
    } else {
        $itemtype_name      = $_POST['prefix'] . '[itemtype]';
        $items_id_name      = $_POST['prefix'] . '[items_id]';
        $groups_id_name     = $_POST['prefix'] . '[groups_id]';
    }

    if (array_key_exists('users_id_validate', $_POST)) {
        Toolbox::deprecated('Usage of "users_id_validate" parameter is deprecated in "ajax/dropdownValidator.php". Use "items_id_target" instead.');
        if (isset($_POST['users_id_validate']['groups_id'])) {
            $_POST['groups_id'] = $_POST['users_id_validate']['groups_id'];
        } else {
            $_POST['itemtype'] = User::class;
            $_POST['items_id'] = $_POST["validatortype"] !== 'list_users'
                ? (isset($_POST['users_id_validate'][0]) ? $_POST['users_id_validate'][0] : 0)
                : (is_array($_POST['users_id_validate']) ? $_POST['users_id_validate'] : []);
        }
    }

    switch (strtolower($_POST['validatortype'])) {
        case 'user':
            User::dropdown([
                'name'   => $items_id_name,
                'entity' => $_POST['entity'],
                'value'  => $_POST['items_id'],
                'right'  => $_POST['right'],
                'width'  => '100%',
            ]);
            echo Html::hidden($itemtype_name, ['value' => 'User']);
            break;

        case 'group':
            Group::dropdown([
                'name'   => $items_id_name,
                'entity' => $_POST['entity'],
                'value'  => $_POST['items_id'],
                'right'  => $_POST['right'],
                'width'  => '100%',
            ]);
            echo Html::hidden($itemtype_name, ['value' => 'Group']);
            break;

        case 'group_user':
            $value = $_POST['groups_id'] ?? 0;

            $rand = Group::dropdown([
                'name'   => $groups_id_name,
                'value'  => $value,
                'entity' => $_POST["entity"],
                'width'  => '100%',
            ]);
            echo Html::hidden($itemtype_name, ['value' => 'User']);

            $param = [
                'prefix'        => $_POST['prefix'],
                'validatortype' => 'list_users',
                'right'         => $_POST['right'],
                'entity'        => $_POST['entity'],
                'groups_id'     => '__VALUE__',
            ];
            if (array_key_exists('name', $_POST)) {
                // TODO Drop in GLPI 11.0
                $param['name'] = !empty($_POST['name']) ? $_POST['name'] : '';
            }
            Ajax::updateItemOnSelectEvent(
                "dropdown_{$groups_id_name}{$rand}",
                "show_list_users",
                $CFG_GLPI["root_doc"] . "/ajax/dropdownValidator.php",
                $param
            );
            if ($value) {
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
            $opt             = [
                'groups_id' => $_POST["groups_id"],
                'right'     => $_POST['right'],
                'entity'    => $_POST["entity"]
            ];
            $data_users      = TicketValidation::getGroupUserHaveRights($opt);
            $users           = [];
            $param['values'] = [];
            $values          = [];
            if (isset($_POST['items_id']) && is_array($_POST['items_id'])) {
                $values = $_POST['items_id'];
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

            $param['multiple'] = true;
            $param['display'] = true;
            $param['size']    = count($users);

            $rand  = Dropdown::showFromArray(
                $items_id_name,
                $users,
                $param
            );
            break;
    }
}
