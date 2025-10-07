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

namespace Glpi\Form\Dropdown;

use AbstractRightsDropdown;
use Dropdown;
use Group;
use Override;
use Supplier;
use User;

final class FormActorsDropdown extends AbstractRightsDropdown
{
    #[Override]
    protected static function getAjaxUrl(): string
    {
        global $CFG_GLPI;

        return $CFG_GLPI['root_doc'] . "/Form/Question/ActorsDropdown";
    }

    #[Override]
    protected static function getTypes(array $options = []): array
    {
        $allowed_types = [
            User::getType(),
            Group::getType(),
            Supplier::getType(),
        ];

        if (isset($options['allowed_types'])) {
            $allowed_types = array_intersect($allowed_types, $options['allowed_types']);
        }

        return $allowed_types;
    }

    #[Override]
    public static function show(string $name, array $values, array $params = []): string
    {
        $params['width'] = '100%';
        $params['templateSelection'] = <<<JS
            function (data) {
                let icon = '';
                let text = _.escape(data.text);
                let title = _.escape(data.title);
                if (
                    (data.itemtype && data.itemtype === 'User')
                    || (data.id && data.id.startsWith('users_id-'))
                ) {
                    icon = '<i class="ti ti-user mx-1" title="' + title + '"></i>';
                } else if (
                    (data.itemtype && data.itemtype === 'Group')
                    || (data.id && data.id.startsWith('groups_id-'))
                ) {
                    icon = '<i class="ti ti-users mx-1" title="' + title + '"></i>';
                } else if (
                    (data.itemtype && data.itemtype === 'Supplier')
                    || (data.id && data.id.startsWith('suppliers_id-'))
                ) {
                    icon = '<i class="ti ti-package mx-1" title="' + title + '"></i>';
                }

                return $('<span class="actor_entry">' + icon + text + '</span>');
            }
        JS;
        $params['templateResult'] = $params['templateSelection'];

        return parent::show($name, $values, $params);
    }

    #[Override]
    protected static function getValueNames(array $values): array
    {
        return array_map(function ($value) {
            $data     = explode("-", $value);
            $item     = getItemForForeignKeyField($data[0]);
            $items_id = (int) $data[1];

            return Dropdown::getDropdownName(
                $item->getTable(),
                $items_id
            );
        }, $values);
    }

    #[Override]
    protected static function getUsers(string $text, array $options): array
    {
        $right = 'all';
        if (isset($options['right_for_users'])) {
            $right = $options['right_for_users'];
        }

        $page = $options['page'] ?? 1;
        $page_size = $options['page_size'] ?? self::LIMIT;
        $start = ($page - 1) * $page_size;

        $users = User::getSqlSearchResult(false, $right, -1, 0, [], $text, $start, $page_size);
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
    public static function fetchValues(string $text = "", array $options = []): array
    {
        $possible_rights = [];

        // Add users if enabled
        if (self::isTypeEnabled(User::getType(), $options)) {
            $possible_rights[User::getType()] = self::getUsers($text, $options);
        }

        // Add groups if enabled
        if (self::isTypeEnabled(Group::getType(), $options)) {
            $possible_rights[Group::getType()] = self::getGroups($text, $options);
        }

        // Add suppliers if enabled
        if (self::isTypeEnabled(Supplier::getType(), $options)) {
            $possible_rights[Supplier::getType()] = self::getSuppliers($text, $options);
        }

        $results = [];
        $count = 0;
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

            if (count($new_group)) {
                $results[] = [
                    'itemtype' => $itemtype,
                    'text' => $itemtype::getTypeName(1),
                    'title' => $itemtype::getTypeName(1),
                    'children' => $new_group,
                ];
                $count += count($new_group);
            }
        }

        $ret = [
            'results' => $results,
            'count' => $count,
        ];

        return $ret;
    }
}
