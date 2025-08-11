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

global $CFG_GLPI;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (isset($_POST["validatortype"])) {
    $validation_class   = $_POST['validation_class'];
    if (array_key_exists('name', $_POST)) {
        Toolbox::deprecated('Usage of "name" parameter is deprecated in "ajax/dropdownValidator.php". Use "prefix" instead.');
        $itemtype_name      = 'itemtype_validate';
        $items_id_name      = !empty($_POST['name']) ? $_POST['name'] . '[]' : 'users_id_validate[]';
        $groups_id_name     = 'groups_id';
    } elseif (isset($_POST['prefix']) && !empty($_POST['prefix'])) {
        $itemtype_name      = $_POST['prefix'] . '[itemtype_target]';
        $items_id_name      = $_POST['prefix'] . '[items_id_target]';
        $groups_id_name     = $_POST['prefix'] . '[groups_id]';
    } else {
        $itemtype_name      = 'itemtype_target';
        $items_id_name      = 'items_id_target';
        $groups_id_name     = 'groups_id';
    }

    if (array_key_exists('users_id_validate', $_POST)) {
        if (isset($_POST['users_id_validate']['groups_id'])) {
            $_POST['groups_id'] = $_POST['users_id_validate']['groups_id'];
        } else {
            $_POST['itemtype_target'] = User::class;
            $_POST['items_id_target'] = $_POST["validatortype"] !== 'list_users'
                ? ($_POST['users_id_validate'][0] ?? 0)
                : (is_array($_POST['users_id_validate']) ? $_POST['users_id_validate'] : []);
        }
    }

    switch (strtolower($_POST['validatortype'])) {
        case 'user':
            if ($_POST['parents_id'] != "") {
                // Existing ticket
                $itilObjectType = $validation_class::$itemtype;
                $itilObject = $itilObjectType::getById($_POST['parents_id']);
                $requester_users = $itilObject->getUsers(CommonITILActor::REQUESTER);
            } else {
                // New ticket
                $requester_users = [];
                foreach ($_POST['users_id_requester'] as $requester) {
                    $requester_users[] = ['users_id' => $requester];
                };
            }
            $added_supervisors = [];
            foreach ($requester_users as $requester) {
                $requester = User::getById($requester['users_id']);
                if (!is_object($requester) || User::isNewId($requester->fields['users_id_supervisor'])) {
                    // the user is not found or has no supervisor
                    continue;
                }
                $supervisor = User::getById($requester->fields['users_id_supervisor']);
                if (!is_object($supervisor)) {
                    // the user does not have any supervisor
                    continue;
                }
                $added_supervisors[] = [
                    'id'    => $supervisor->getID(),
                    'text'  => sprintf(__('%1$s (supervisor of %2$s)'), $supervisor->getFriendlyName(), $requester->getFriendlyName()),
                    'title' => sprintf(__('%1$s - %2$s'), $supervisor->getFriendlyName(), $supervisor->getID()),
                ];
            }
            User::dropdown([
                'name'       => $items_id_name,
                'entity'     => $_POST['entity'],
                'value'      => $_POST['items_id_target'],
                'right'      => $_POST['right'],
                'width'      => '100%',
                'toadd'      => $added_supervisors,
                'rand'       => $_POST['rand'],
                'aria_label' => __('Select a user'),
            ]);
            echo Html::hidden($itemtype_name, ['value' => 'User']);
            break;

        case 'group':
            Group::dropdown([
                'name'       => $items_id_name,
                'entity'     => $_POST['entity'],
                'value'      => !is_array($_POST['items_id_target']) ? $_POST['items_id_target'] : '',
                'right'      => $_POST['right'],
                'width'      => '100%',
                'rand'       => $_POST['rand'],
                'aria_label' => __('Select a group'),
            ]);
            echo Html::hidden($itemtype_name, ['value' => 'Group']);
            break;

        case 'group_user':
            $value = $_POST['groups_id'] ?? 0;

            $rand = Group::dropdown([
                'name'       => $groups_id_name,
                'value'      => $value,
                'entity'     => $_POST["entity"],
                'width'      => '100%',
                'rand'       => $_POST['rand'],
                'aria_label' => __('Select a group'),
            ]);
            echo Html::hidden($itemtype_name, ['value' => 'User']);

            $param = [
                'prefix'        => $_POST['prefix'],
                'validatortype' => 'list_users',
                'right'         => $_POST['right'],
                'entity'        => $_POST['entity'],
                'groups_id'     => '__VALUE__',
                'items_id'      => $_POST['items_id_target'],
                'rand'          => $_POST['rand'],
            ];
            if (array_key_exists('validation_class', $_POST)) {
                $param['validation_class'] = $_POST['validation_class'];
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
                'entity'    => $_POST["entity"],
            ];
            $data_users      = $validation_class::getGroupUserHaveRights($opt);
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

            $param['multiple']   = true;
            $param['display']    = true;
            $param['size']       = count($users);
            $param['rand']       = $_POST['rand'];
            $param['aria_label'] = __('Select users');

            $rand  = Dropdown::showFromArray(
                $items_id_name,
                $users,
                $param
            );
            break;
    }
}
