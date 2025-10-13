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

abstract class AbstractRightsDropdown
{
    /**
     * Max limit per itemtype
     */
    public const LIMIT = 50;

    public const ALL_USERS = "all";

    /**
     * To be redefined by subclasses, URL to front file
     *
     * @return string
     */
    abstract protected static function getAjaxUrl(): string;

    /**
     * To be redefined by subclasses, specify enabled types
     *
     * @param array $options Additional options
     *
     * @return array
     */
    abstract protected static function getTypes(array $options = []): array;

    protected static function addAllUsersOption(): bool
    {
        return false;
    }

    /**
     * Check if a given type is enabled
     *
     * @param string $type Class to check
     * @param array $options Additional options
     *
     * @return bool
     */
    protected static function isTypeEnabled(string $type, array $options = []): bool
    {
        $types = array_flip(static::getTypes($options));
        return isset($types[$type]);
    }

    /**
     * Get possible data for profiles
     *
     * @param string $name  Field name
     * @param array $values Selected values
     *
     * @return string
     */
    public static function show(string $name, array $values, array $params = []): string
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
        $params = array_merge([
            'name'        => $name . "[]",
            'multiple'    => true,
            'width'       => '100%',
        ], $params);

        if ($params['multiple']) {
            $params['values'] = $dropdown_values;
            $params['valuesnames'] = static::getValueNames($dropdown_values);
        } elseif (count($dropdown_values) > 0) {
            $params['value'] = $dropdown_values[0];
            $params['valuename'] = static::getValueNames($dropdown_values)[0];
        }
        return Html::jsAjaxDropdown($params['name'], $field_id, $url, $params);
    }

    /**
     * Get possible data for profiles
     *
     * @param string $text Search string
     * @param array $options Additional options
     *
     * @return array
     */
    public static function fetchValues(string $text = "", array $options = []): array
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
        if (self::isTypeEnabled(User::getType(), $options)) {
            $possible_rights[User::getType()] = self::getUsers($text, $options);
        }

        // Add groups if enabled
        if (self::isTypeEnabled(Group::getType(), $options)) {
            $possible_rights[Group::getType()] = self::getGroups($text, $options);
        }

        // Add contacts if enabled
        if (self::isTypeEnabled(Contact::getType())) {
            $possible_rights[Contact::getType()] = self::getContacts($text);
        }

        // Add suppliers if enabled
        if (self::isTypeEnabled(Supplier::getType())) {
            $possible_rights[Supplier::getType()] = self::getSuppliers($text, $options);
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
            'results' => $results,
            'count' =>  count($results),
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
            $items_id = $data[1];
            $item = getItemForForeignKeyField($data[0]);

            if ($items_id == self::ALL_USERS) {
                return $item::getTypeName(1) . " - " . __("All users");
            }

            return $item::getTypeName(1) . " - " . Dropdown::getDropdownName(
                $item->getTable(),
                (int) $items_id
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
            'name' => ["LIKE", "%$text%"],
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
                'name' => ["LIKE", "%$text%"],
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
     * @param array $options Additional options
     *
     * @return array
     */
    protected static function getUsers(string $text, array $options): array
    {
        $page = $options['page'] ?? 1;
        $page_size = $options['page_size'] ?? self::LIMIT;
        $start = ($page - 1) * $page_size;

        $users = User::getSqlSearchResult(false, "all", -1, 0, [], $text, $start, $page_size);
        $users_items = [];

        if (static::addAllUsersOption()) {
            $new_key = 'users_id-' . self::ALL_USERS;
            $users_items[$new_key] = __("All users");
        }

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
     * @param array $options Additional options
     *
     * @return array
     */
    protected static function getGroups(string $text, array $options): array
    {
        /** @var DBmysql $DB */
        global $DB;

        $page = $options['page'] ?? 1;
        $page_size = $options['page_size'] ?? self::LIMIT;
        $start = ($page - 1) * $page_size;

        $additional_conditions = [];
        if (isset($options['group_conditions'])) {
            $additional_conditions = $options['group_conditions'];
        }

        $groups = $DB->request([
            'FROM' => Group::getTable(),
            'WHERE' => [
                'name' => ["LIKE", "%$text%"],
            ] + getEntitiesRestrictCriteria(Group::getTable()) + $additional_conditions,
            'START' => $start,
            'LIMIT' => $page_size,
        ]);
        $groups_items = [];
        foreach ($groups as $group) {
            $new_key = 'groups_id-' . $group['id'];
            $groups_items[$new_key] = $group['name'];
        }

        return $groups_items;
    }

    /**
     * Get possible values for contacts
     *
     * @param string $text Search string
     *
     * @return array
     */
    protected static function getContacts(string $text): array
    {
        $contact_item = new Contact();
        $contacts = $contact_item->find(
            [
                'name' => ["LIKE", "%$text%"],
            ] + getEntitiesRestrictCriteria(Contact::getTable()),
            [],
            self::LIMIT
        );
        $contacts_item = [];
        foreach ($contacts as $contact) {
            $new_key = 'contacts_id-' . $contact['id'];
            $contacts_item[$new_key] = $contact['name'];
        }

        return $contacts_item;
    }

    /**
     * Get possible values for suppliers
     *
     * @param string $text Search string
     *
     * @return array
     */
    protected static function getSuppliers(string $text, array $options = []): array
    {
        /** @var DBmysql $DB */
        global $DB;

        $page = $options['page'] ?? 1;
        $page_size = $options['page_size'] ?? self::LIMIT;
        $start = ($page - 1) * $page_size;

        $suppliers = $DB->request([
            'FROM' => Supplier::getTable(),
            'WHERE' => [
                'name' => ["LIKE", "%$text%"],
            ] + getEntitiesRestrictCriteria(Supplier::getTable()),
            'START' => $start,
            'LIMIT' => $page_size,
        ]);

        $suppliers_item = [];
        foreach ($suppliers as $supplier) {
            $new_key = 'suppliers_id-' . $supplier['id'];
            $suppliers_item[$new_key] = $supplier['name'];
        }

        return $suppliers_item;
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
     * into an array containing the ids of the specified $class parameter:
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
            if (is_numeric($value)) {
                $value = (int) $value;
            }

            if ($fkey == $class::getForeignKeyField()) {
                $inflated_values[] = $value;
            }
        }

        return $inflated_values;
    }
}
