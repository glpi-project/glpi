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

namespace Glpi\Features;

use Glpi\DBAL\QueryExpression;
use Session;

trait AssignableAsset
{
    public int $GROUP_TYPE_NORMAL = 0;
    public int $GROUP_TYPE_TECH = 1;

    public static function canView()
    {
        return Session::haveRightsOr(static::$rightname, [READ, READ_ASSIGNED]);
    }

    public function canViewItem()
    {
        if (!parent::canViewItem()) {
            return false;
        }

        $is_assigned = $this->fields['users_id_tech'] === $_SESSION['glpiID'] ||
            in_array((int) ($this->fields['groups_id_tech'] ?? 0), $_SESSION['glpigroups'] ?? [], true);

        if (!Session::haveRight(static::$rightname, READ)) {
            return $is_assigned && Session::haveRight(static::$rightname, READ_ASSIGNED);
        }

        // Has global READ right
        return true;
    }

    public static function canUpdate()
    {
        return Session::haveRightsOr(static::$rightname, [UPDATE, UPDATE_ASSIGNED]);
    }

    public function canUpdateItem()
    {
        if (!parent::canUpdateItem()) {
            return false;
        }

        $is_assigned = $this->fields['users_id_tech'] === $_SESSION['glpiID'] ||
            in_array((int) ($this->fields['groups_id_tech'] ?? 0), $_SESSION['glpigroups'] ?? [], true);

        if (!Session::haveRight(static::$rightname, UPDATE)) {
            return $is_assigned && Session::haveRight(static::$rightname, UPDATE_ASSIGNED);
        }

        // Has global UPDATE right
        return true;
    }

    public static function getAssignableVisiblityCriteria()
    {
        if (Session::haveRight(static::$rightname, READ)) {
            return [new QueryExpression('1')];
        }
        if (Session::haveRight(static::$rightname, READ_ASSIGNED)) {
            $criteria = [
                'OR' => [
                    'users_id_tech' => $_SESSION['glpiID'],
                ],
            ];
            if (count($_SESSION['glpigroups'])) {
                $criteria['OR']['groups_id_tech'] = $_SESSION['glpigroups'];
            }
            return [$criteria];
        }
        return [new QueryExpression('0')];
    }

    /**
     * @param string $interface
     * @phpstan-param 'central'|'helpdesk' $interface
     * @return array
     * @phpstan-return array<integer, string|array>
     */
    public function getRights($interface = 'central')
    {
        $rights = parent::getRights($interface);
        $rights[READ] = __('View all');
        $rights[READ_ASSIGNED] = __('View assigned');
        $rights[UPDATE] = __('Update all');
        $rights[UPDATE_ASSIGNED] = __('Update assigned');
        return $rights;
    }

    private function prepareGroupFields(array $input)
    {
        $fields = ['groups_id', 'groups_id_tech'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $input)) {
                if ($input[$field] === null || $input[$field] === 0 || $input[$field] === [0]) {
                    $input[$field] = [];
                }
                if (!is_array($input[$field])) {
                    $input[$field] = [$input[$field]];
                }
            }
            $input['_' . $field] = array_map('intval', $input[$field] ?? []);
            unset($input[$field]);
        }
        return $input;
    }

    public function prepareInputForAdd($input): array|false
    {
        if ($input === false) {
            return false;
        }
        $input = parent::prepareInputForAdd($input);
        return $this->prepareGroupFields($input);
    }

    public function prepareInputForUpdate($input): array|false
    {
        if ($input === false) {
            return false;
        }
        $input = parent::prepareInputForUpdate($input);
        return $this->prepareGroupFields($input);
    }

    /**
     * Update the values in the 'glpi_groups_assets' link table as needed based on the groups set in the 'groups_id' and 'groups_id_tech' fields.
     */
    private function updateGroupFields()
    {
        /** @var \DBmysql $DB */
        global $DB;

        // Find existing links
        $existing_links = [];
        if (!$this->isNewItem()) {
            $it = $DB->request([
                'SELECT' => ['id', 'groups_id', 'type'],
                'FROM' => 'glpi_groups_assets',
                'WHERE' => [
                    'itemtype' => static::class,
                    'items_id' => $this->getID(),
                ],
            ]);
            $existing_links = iterator_to_array($it);
        }

        // Group fields are changed to have a '_' prefix to avoid trying to update non-existent fields in the database
        $fields = [
            $this->GROUP_TYPE_NORMAL => '_groups_id',
            $this->GROUP_TYPE_TECH => '_groups_id_tech'
        ];
        foreach ($fields as $type => $field) {
            $existing_for_type = array_column(array_filter($existing_links, static fn($link) => $link['type'] === $type), 'groups_id');
            if (array_key_exists($field, $this->input)) {
                $new_links = array_diff($this->input[$field], $existing_for_type);
                $old_links = array_diff($existing_for_type, $this->input[$field]);
                foreach ($new_links as $group_id) {
                    $DB->insert('glpi_groups_assets', [
                        'itemtype' => static::class,
                        'items_id' => $this->getID(),
                        'groups_id' => $group_id,
                        'type' => $type,
                    ]);
                }
                foreach ($old_links as $group_id) {
                    $DB->delete('glpi_groups_assets', [
                        'itemtype' => static::class,
                        'items_id' => $this->getID(),
                        'groups_id' => $group_id,
                        'type' => $type,
                    ]);
                }
            }
        }

        $this->loadGroupFields();
    }

    public function post_addItem()
    {
        parent::post_addItem();
        $this->updateGroupFields();
    }

    public function post_updateItem($history = true)
    {
        parent::post_updateItem($history);
        $this->updateGroupFields();
    }

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
        /** @var \DBmysql $DB */
        global $DB;

        // Find existing links
        $existing_links = [];
        if (!$this->isNewItem()) {
            $it = $DB->request([
                'SELECT' => ['id', 'groups_id', 'type'],
                'FROM' => 'glpi_groups_assets',
                'WHERE' => [
                    'itemtype' => static::class,
                    'items_id' => $this->getID(),
                ],
            ]);
            $existing_links = iterator_to_array($it);
        }

        $group_fields = [
            $this->GROUP_TYPE_NORMAL => 'groups_id',
            $this->GROUP_TYPE_TECH => 'groups_id_tech'
        ];
        foreach ($group_fields as $type => $field) {
            if (in_array($type, $this->getGroupTypes(), true)) {
                $this->fields[$field] = array_column(array_filter($existing_links, static fn($link) => $link['type'] === $type), 'groups_id');
            }
        }
    }

    public function post_getFromDB()
    {
        $this->loadGroupFields();
    }

    /**
     * Get the types of groups supported by the asset.
     * @return array<self::GROUP_TYPE_*>
     */
    public function getGroupTypes(): array
    {
        return [$this->GROUP_TYPE_NORMAL, $this->GROUP_TYPE_TECH];
    }

//    /**
//     * Get all group type labels or a specific label.
//     * @param self::GROUP_TYPE_*|null $type
//     * @return array|string
//     * @phpstan-return $type === null ? array<self::GROUP_TYPE_*, string> : string
//     */
//    public static function getGroupTypeLabels(?int $type = null): array|string
//    {
//        $labels = [
//            self::GROUP_TYPE_NORMAL => __('Group'),
//            self::GROUP_TYPE_TECH => __('Group in charge'),
//        ];
//        return $type === null ? $labels : $labels[$type];
//    }
}
