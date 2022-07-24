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

use Glpi\Toolbox\Sanitizer;

abstract class AbstractRightsDropdown
{
    /**
     * Max limit per itemtype
     */
    public const LIMIT = 50;

    /**
     * To be redefined by subclasses, URL to front file
     *
     * @return string
     */
    abstract protected static function getAjaxUrl(): string;

    /**
     * To be redefined by subclasses, specify enabled types
     *
     * @return array
     */
    abstract protected static function getTypes(): array;

    /**
     * Check if a given type is enabled
     *
     * @param string $type Class to check
     *
     * @return bool
     */
    protected static function isTypeEnabled(string $type): bool
    {
        $types = array_flip(static::getTypes());
        return isset($types[$type]);
    }

    /**
     * Get possible data for profiles
     *
     * @param string $name  Field name
     * @param array $values Selected values
     *
     * @return array
     */
    public static function show(string $name, array $values): string
    {
        // Flatten values
        $dropdown_values = [];
        foreach ($values as $fkey => $ids) {
            foreach ($ids as $id) {
                $dropdown_values[] = $fkey . "-" . $id;
            }
        }

        // Build DOM id
        $field_id = $name . "_" . mt_rand();

        // Build url
        $url = static::getAjaxUrl();

        // Build params
        $params = [
            'name'        => $name . "[]",
            'values'      => $dropdown_values,
            'valuesnames' => self::getValueNames($dropdown_values),
            'multiple'    => true,
        ];
        return Html::jsAjaxDropdown($params['name'], $field_id, $url, $params);
    }

    /**
     * Get possible data for profiles
     *
     * @param string $text Search string
     *
     * @return array
     */
    public static function fetchValues(string $text = ""): array
    {
        $possible_rights = [];

       // Add profiles if enabled
        if (self::isTypeEnabled(Profile::getType())) {
            $possible_rights[Profile::getType()] = self::getProfiles($text);
        }

       // Add entities if enabled
        if (self::isTypeEnabled(Entity::getType())) {
            $possible_rights[Entity::getType()] = self::getEntities($text);
        }

       // Add users if enabled
        if (self::isTypeEnabled(User::getType())) {
            $possible_rights[User::getType()] = self::getUsers($text);
        }

       // Add groups if enabled
        if (self::isTypeEnabled(Group::getType())) {
            $possible_rights[Group::getType()] = self::getGroups($text);
        }

        $results = [];
        foreach ($possible_rights as $itemtype => $ids) {
            $new_group = [];
            foreach ($ids as $id => $label) {
                $new_group[] = [
                    'id' => $id,
                    'text' => $label,
                    "selection_text" => "$itemtype - $label",
                ];
            }
            $results[] = [
                "text" => $itemtype::getTypeName(1),
                "children" => $new_group,
            ];
        }

        $ret = [
            'results' => Sanitizer::unsanitize($results),
            'count' =>  count($results)
        ];

        return $ret;
    }

    /**
     * Get names for each selected values
     *
     * @param array $values Selected values
     *
     * @return array
     */
    protected static function getValueNames(array $values): array
    {
        return array_map(function ($value) {
            $data = explode("-", $value);
            $itemtype = getItemtypeForForeignKeyField($data[0]);
            $items_id = $data[1];
            $item = new $itemtype();

            return $itemtype::getTypeName(1) . " - " . Dropdown::getDropdownName(
                $item->getTable(),
                $items_id
            );
        }, $values);
    }

    /**
     * Get possible values for profiles
     *
     * @param string $text Search string
     *
     * @return array
     */
    protected static function getProfiles(string $text): array
    {
        $profile_item = new Profile();
        $profiles = $profile_item->find([
            'name' => ["LIKE", "%$text%"]
        ], [], self::LIMIT);
        $profiles_items = [];
        foreach ($profiles as $profile) {
            $new_key = 'profiles_id-' . $profile['id'];
            $profiles_items[$new_key] = $profile['name'];
        }

        return $profiles_items;
    }

    /**
     * Get possible values for entities
     *
     * @param string $text Search string
     *
     * @return array
     */
    protected static function getEntities(string $text): array
    {
        $entity_item = new Entity();
        $entities = $entity_item->find(
            [
                'name' => ["LIKE", "%$text%"]
            ] + getEntitiesRestrictCriteria(Entity::getTable()),
            [],
            self::LIMIT
        );
        $entities_items = [];
        foreach ($entities as $entity) {
            $new_key = 'entities_id-' . $entity['id'];
            $entities_items[$new_key] = $entity['completename'];
        }

        return $entities_items;
    }

    /**
     * Get possible values for users
     *
     * @param string $text Search string
     *
     * @return array
     */
    protected static function getUsers(string $text): array
    {
        $users = User::getSqlSearchResult(false, "all", -1, 0, [], $text, 0, self::LIMIT);
        $users_items = [];
        foreach ($users as $user) {
            $new_key = 'users_id-' . $user['id'];
            $users_items[$new_key] = $user['name'];
        }

        return $users_items;
    }

    /**
     * Get possible values for groups
     *
     * @param string $text Search string
     *
     * @return array
     */
    protected static function getGroups(string $text): array
    {
        $group_item = new Group();
        $groups = $group_item->find(
            [
                'name' => ["LIKE", "%$text%"]
            ] + getEntitiesRestrictCriteria(Group::getTable()),
            [],
            self::LIMIT
        );
        $groups_items = [];
        foreach ($groups as $group) {
            $new_key = 'groups_id-' . $group['id'];
            $groups_items[$new_key] = $group['name'];
        }

        return $groups_items;
    }

    /**
     * To be used in front files dealing with dropdown input created by
     * static::show()
     * Read values from a "flattened" select 2 multi itemtype dropdown like this:
     * [
     *    0 => 'users_id-3',
     *    1 => 'users_id-14',
     *    2 => 'groups_id-2',
     *    3 => 'groups_id-78',
     *    4 => 'profiles_id-1',
     * ]
     * into an array containings the ids of the specified $class parameter:
     * $class = User -> [3, 14]
     * $class = Group -> [2, 78]
     * $class = Profile -> [1]
     *
     * @param array  $values Flattened array containing multiple itemtypes and ids
     * @param string $class  Class to filter results on
     *
     * @return array List of ids
     */
    public static function getPostedIds(array $values, string $class): array
    {
        $inflated_values = [];

        foreach ($values as $value) {
            // Split fkey and ids
            $parsed_values = explode("-", $value);
            $fkey  = $parsed_values[0];
            $value = $parsed_values[1];

            if ($fkey == $class::getForeignKeyField()) {
                $inflated_values[] = $value;
            }
        }

        return $inflated_values;
    }
}
