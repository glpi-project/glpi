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

namespace Glpi\Form\Dropdown;

use AbstractRightsDropdown;
use Group;
use Override;
use Supplier;
use User;

final class FormActorsDropdown extends AbstractRightsDropdown
{
    #[Override]
    protected static function getAjaxUrl(): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        return $CFG_GLPI['root_doc'] . "/ajax/getFormQuestionActorsDropdownValue.php";
    }

    #[Override]
    protected static function getTypes(): array
    {
        $allowed_types = [
            User::getType(),
            Group::getType(),
            Supplier::getType(),
        ];

        if (isset($_POST['allowed_types'])) {
            $allowed_types = array_intersect($allowed_types, $_POST['allowed_types']);
        }

        return $allowed_types;
    }

    #[Override]
    public static function show(string $name, array $values, array $params = []): string
    {
        $itemtype_name = fn($itemtype) => $itemtype::getTypeName(1);
        $params['width'] = '100%';
        $params['templateSelection'] = <<<JS
            function (data) {
                let icon = '';
                let text = data.text;
                let title = data.title;
                if (data.itemtype === 'User') {
                    icon = '<i class="ti fa-fw ti-user mx-1" title="' + title + '"></i>';
                } else if (data.itemtype === 'Group') {
                    icon = '<i class="ti fa-fw ti-users mx-1" title="' + title + '"></i>';
                } else if (data.itemtype === 'Supplier') {
                    icon = '<i class="ti fa-fw ti-package mx-1" title="' + title + '"></i>';
                }

                return $('<span class="actor_entry">' + icon + text + '</span>');
            }
        JS;
        $params['templateResult'] = $params['templateSelection'];

        return parent::show($name, $values, $params);
    }

    #[Override]
    protected static function getUsers(string $text): array
    {
        $users = User::getSqlSearchResult(false, "all", -1, 0, [], $text, 0, self::LIMIT);
        $users_items = [];
        foreach ($users as $user) {
            $new_key = 'users_id-' . $user['id'];
            $text = formatUserName($user["id"], $user["name"], $user["realname"], $user["firstname"]);
            $users_items[$new_key] = [
                'text' => $text,
                'title' => sprintf(__('%1$s - %2$s'), $text, $user['name']),
            ];
        }

        return $users_items;
    }

    #[Override]
    public static function fetchValues(string $text = ""): array
    {
        $possible_rights = [];

        // Add users if enabled
        if (self::isTypeEnabled(User::getType())) {
            $possible_rights[User::getType()] = self::getUsers($text);
        }

        // Add groups if enabled
        if (self::isTypeEnabled(Group::getType())) {
            $possible_rights[Group::getType()] = self::getGroups($text);
        }

        // Add suppliers if enabled
        if (self::isTypeEnabled(Supplier::getType())) {
            $possible_rights[Supplier::getType()] = self::getSuppliers($text);
        }

        $results = [];
        foreach ($possible_rights as $itemtype => $ids) {
            $new_group = [];
            foreach ($ids as $id => $labels) {
                $text = $labels['text'] ?? $labels;
                $title = $labels['title'] ?? $text;
                $new_group[] = [
                    'id' => $id,
                    'itemtype' => $itemtype,
                    'text' => $text,
                    'title' => $title,
                    'selection_text' => "$itemtype - $text",
                ];
            }
            $results[] = [
                'itemtype' => $itemtype,
                'text' => $itemtype::getTypeName(1),
                'title' => $itemtype::getTypeName(1),
                'children' => $new_group,
            ];
        }

        $ret = [
            'results' => $results,
            'count' =>  count($results)
        ];

        return $ret;
    }
}
