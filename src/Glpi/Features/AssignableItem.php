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

namespace Glpi\Features;

use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QuerySubQuery;
use Group_Item;
use Session;

/**
 * @phpstan-require-implements AssignableItemInterface
 */
trait AssignableItem
{
    /** @see AssignableItemInterface::canView() */
    public static function canView(): bool
    {
        return Session::haveRightsOr(static::$rightname, [READ, READ_ASSIGNED, READ_OWNED]);
    }

    /** @see AssignableItemInterface::canViewItem() */
    public function canViewItem(): bool
    {
        if (!parent::canViewItem()) {
            return false;
        }

        $is_assigned = $this->fields['users_id_tech'] === $_SESSION['glpiID']
            || count(array_intersect($this->fields['groups_id_tech'] ?? [], $_SESSION['glpigroups'] ?? [])) > 0;
        $is_owned = $this->fields['users_id'] === $_SESSION['glpiID']
            || count(array_intersect($this->fields['groups_id'] ?? [], $_SESSION['glpigroups'] ?? [])) > 0;

        if (!Session::haveRight(static::$rightname, READ)) {
            return ($is_assigned && Session::haveRight(static::$rightname, READ_ASSIGNED))
                || ($is_owned && Session::haveRight(static::$rightname, READ_OWNED));
        }

        // Has global READ right
        return true;
    }

    /** @see AssignableItemInterface::canUpdate() */
    public static function canUpdate(): bool
    {
        return Session::haveRightsOr(static::$rightname, [UPDATE, UPDATE_ASSIGNED, UPDATE_OWNED]);
    }

    /** @see AssignableItemInterface::canUpdateItem() */
    public function canUpdateItem(): bool
    {
        if (!parent::canUpdateItem()) {
            return false;
        }

        $is_assigned = $this->fields['users_id_tech'] === $_SESSION['glpiID']
            || count(array_intersect($this->fields['groups_id_tech'] ?? [], $_SESSION['glpigroups'] ?? [])) > 0;
        $is_owned = $this->fields['users_id'] === $_SESSION['glpiID']
            || count(array_intersect($this->fields['groups_id'] ?? [], $_SESSION['glpigroups'] ?? [])) > 0;

        if (!Session::haveRight(static::$rightname, UPDATE)) {
            return ($is_assigned && Session::haveRight(static::$rightname, UPDATE_ASSIGNED))
                || ($is_owned && Session::haveRight(static::$rightname, UPDATE_OWNED));
        }

        // Has global UPDATE right
        return true;
    }

    /**
     * @param string|null $item_table_reference Table name to use in criteria, defaults to static::getTable().
     * Useful if the criteria that this will be used with uses aliases.
     * @return array[]|QueryExpression[]
     * @see AssignableItemInterface::getAssignableVisiblityCriteria()
     */
    public static function getAssignableVisiblityCriteria(?string $item_table_reference = null): array
    {
        if (!Session::haveRightsOr(static::$rightname, [READ, READ_ASSIGNED, READ_OWNED])) {
            return [new QueryExpression('0')];
        }

        if (Session::haveRight(static::$rightname, READ)) {
            return [new QueryExpression('1')];
        }

        $item_table     = $item_table_reference ?? static::getTable();
        $relation_table = Group_Item::getTable();

        $or = [];
        if (Session::haveRight(static::$rightname, READ_ASSIGNED)) {
            $or[] = [
                $item_table . '.users_id_tech' => $_SESSION['glpiID'],
            ];
            if (count($_SESSION['glpigroups']) > 0) {
                $or[] = [
                    $item_table . '.id' => new QuerySubQuery([
                        'SELECT'     => $relation_table . '.items_id',
                        'FROM'       => $relation_table,
                        'WHERE' => [
                            'itemtype'  => static::class,
                            'groups_id' => $_SESSION['glpigroups'],
                            'type'      => Group_Item::GROUP_TYPE_TECH,
                        ],
                    ]),
                ];
            }
        }
        if (Session::haveRight(static::$rightname, READ_OWNED)) {
            $or[] = [
                $item_table . '.users_id' => $_SESSION['glpiID'],
            ];
            if (count($_SESSION['glpigroups']) > 0) {
                $or[] = [
                    $item_table . '.id' => new QuerySubQuery([
                        'SELECT'     => $relation_table . '.items_id',
                        'FROM'       => $relation_table,
                        'WHERE' => [
                            'itemtype'  => static::class,
                            'groups_id' => $_SESSION['glpigroups'],
                            'type'      => Group_Item::GROUP_TYPE_NORMAL,
                        ],
                    ]),
                ];
            }
        }

        // Add another layer to the array to prevent losing duplicates keys if the
        // result of the function is merged with another array
        $criteria = [crc32(serialize($or)) => ['OR' => $or]];

        return $criteria;
    }

    /** @see AssignableItemInterface::getRights() */
    public function getRights($interface = 'central')
    {
        $rights = parent::getRights($interface);
        $rights[READ] = __('View all');
        $rights[READ_ASSIGNED] = __('View assigned');
        $rights[READ_OWNED] = __('View owned');
        $rights[UPDATE] = __('Update all');
        $rights[UPDATE_ASSIGNED] = __('Update assigned');
        $rights[UPDATE_OWNED] = __('Update owned');
        return $rights;
    }

    /** @see AssignableItemInterface::prepareGroupFields() */
    public function prepareGroupFields(array $input)
    {
        $fields = ['groups_id', 'groups_id_tech'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $input)) {
                if (!is_array($input[$field])) {
                    $input[$field] = [$input[$field]];
                }
                $input['_' . $field] = array_filter(array_map('intval', $input[$field] ?? []), static fn($v) => $v > 0);
                unset($input[$field]);
            }
        }
        return $input;
    }

    /** @see AssignableItemInterface::prepareInputForAdd() */
    public function prepareInputForAdd($input)
    {
        if ($input === false) {
            return false;
        }
        $input = parent::prepareInputForAdd($input);
        if ($input === false) {
            return false;
        }
        return $this->prepareGroupFields($input);
    }

    /** @see AssignableItemInterface::prepareInputForUpdate() */
    public function prepareInputForUpdate($input)
    {
        if ($input === false) {
            return false;
        }
        $input = parent::prepareInputForUpdate($input);
        if ($input === false) {
            return false;
        }
        return $this->prepareGroupFields($input);
    }

    /** @see AssignableItemInterface::updateGroupFields() */
    public function updateGroupFields()
    {
        global $DB;

        // Find existing links
        $existing_links = [];
        if (!$this->isNewItem()) {
            $it = $DB->request([
                'SELECT' => ['id', 'groups_id', 'type'],
                'FROM' => 'glpi_groups_items',
                'WHERE' => [
                    'itemtype' => static::class,
                    'items_id' => $this->getID(),
                ],
            ]);
            $existing_links = iterator_to_array($it);
        }

        // Group fields are changed to have a '_' prefix to avoid trying to update non-existent fields in the database
        $fields = [
            Group_Item::GROUP_TYPE_NORMAL => '_groups_id',
            Group_Item::GROUP_TYPE_TECH   => '_groups_id_tech',
        ];
        foreach ($fields as $type => $field) {
            $existing_for_type = array_column(array_filter($existing_links, static fn($link) => $link['type'] === $type), 'groups_id');
            if (array_key_exists($field, $this->input)) {
                $new_links = array_diff($this->input[$field], $existing_for_type);
                $old_links = array_diff($existing_for_type, $this->input[$field]);
                foreach ($new_links as $group_id) {
                    $group_item = new Group_Item();
                    $group_item->add(
                        [
                            'itemtype' => static::class,
                            'items_id' => $this->getID(),
                            'groups_id' => $group_id,
                            'type' => $type,
                        ]
                    );
                }
                foreach ($old_links as $group_id) {
                    $group_item = new Group_Item();
                    $group_item->deleteByCriteria(
                        [
                            'itemtype' => static::class,
                            'items_id' => $this->getID(),
                            'groups_id' => $group_id,
                            'type' => $type,
                        ],
                        true
                    );
                }
            }
        }

        $this->loadGroupFields();
    }

    /** @see AssignableItemInterface::post_addItem() */
    public function post_addItem()
    {
        parent::post_addItem();
        $this->updateGroupFields();
    }

    /** @see AssignableItemInterface::post_updateItem() */
    public function post_updateItem($history = true)
    {
        parent::post_updateItem($history);
        $this->updateGroupFields();
    }

    /** @see AssignableItemInterface::getEmpty() */
    public function getEmpty()
    {
        if (!parent::getEmpty()) {
            return false;
        }
        $group_fields = ['groups_id', 'groups_id_tech'];
        foreach ($group_fields as $field) {
            $this->fields[$field] = [];
        }
        return true;
    }

    private function loadGroupFields()
    {
        global $DB;

        // Find existing links
        $it = $DB->request([
            'SELECT' => ['id', 'groups_id', 'type'],
            'FROM' => 'glpi_groups_items',
            'WHERE' => [
                'itemtype' => static::class,
                'items_id' => $this->getID(),
            ],
        ]);
        $existing_links = iterator_to_array($it);

        $group_fields = [
            Group_Item::GROUP_TYPE_NORMAL => 'groups_id',
            Group_Item::GROUP_TYPE_TECH   => 'groups_id_tech',
        ];
        foreach ($group_fields as $type => $field) {
            $this->fields[$field] = array_column(array_filter($existing_links, static fn($link) => $link['type'] === $type), 'groups_id');
        }
    }

    /** @see AssignableItemInterface::post_getFromDB() */
    public function post_getFromDB()
    {
        $this->loadGroupFields();
    }
}
