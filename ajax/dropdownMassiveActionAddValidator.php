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
    $itemtype_name      = 'itemtype_target';
    $items_id_name      = 'items_id_target';
    $groups_id_name     = 'groups_id';
    $types_mapping = [
        'group'      => 'group',
        'group_user' => 'group_user',
        'list_users' => 'list_users',
    ];

    switch (strtolower($_POST['validatortype'])) {
        case 'user':
            User::dropdown([
                'name'   => $items_id_name,
                'entity' => $_SESSION["glpiactive_entity"],
                'right'  => $_POST['right'],
            ]);
            echo Html::hidden($itemtype_name, ['value' => 'User']);

            echo "<br><br>" . __s('Comments') . " ";
            echo "<textarea name='comment_submission' cols='50' rows='6'></textarea>&nbsp;";

            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
            break;

        case $types_mapping['group']:
            Group::dropdown([
                'name'   => $items_id_name,
                'entity' => $_POST['entity'],
                'right'  => $_POST['right'],
                'width'  => '100%',
            ]);
            echo Html::hidden($itemtype_name, ['value' => 'Group']);

            echo "<br><br>" . __s('Comments') . " ";
            echo "<textarea name='comment_submission' cols='50' rows='6'></textarea>&nbsp;";

            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
            break;

        case $types_mapping['group_user']:
            $rand = Group::dropdown([
                'name'      => $groups_id_name,
                'entity'    => $_SESSION["glpiactive_entity"],
            ]);
            echo Html::hidden($itemtype_name, ['value' => 'User']);

            $param = [
                'validatortype' => $types_mapping['list_users'],
                'groups_id'     => '__VALUE__',
                'right'         => ['validate_request', 'validate_incident'],
            ];
            if (array_key_exists('validation_class', $_POST)) {
                $param['validation_class'] = $_POST['validation_class'];
            }

            Ajax::updateItemOnSelectEvent(
                "dropdown_{$groups_id_name}{$rand}",
                "show_groups_users",
                $CFG_GLPI["root_doc"] . "/ajax/dropdownMassiveActionAddValidator.php",
                $param
            );

            echo "<br><span id='show_groups_users'>&nbsp;</span>";
            break;

        case $types_mapping['list_users']:
            $opt = [
                'groups_id' => $_POST["groups_id"],
                'right'     => $_POST['right'],
                'entity'    => $_SESSION["glpiactive_entity"],
            ];

            $groups_users = $validation_class::getGroupUserHaveRights($opt);

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

            $param['multiple'] = true;
            $param['display'] = true;
            $param['size']    = count($users);

            Dropdown::showFromArray($items_id_name, $users, $param);

            echo "<br><br>" . __s('Comments') . " ";
            echo "<textarea name='comment_submission' cols='50' rows='6'></textarea>&nbsp;";

            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
            break;
    }
}
