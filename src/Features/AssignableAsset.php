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
                if ($input[$field] === null) {
                    $input[$field] = [];
                } else if (!is_array($input[$field])) {
                    $input[$field] = [$input[$field]];
                }
                $input[$field] = json_encode($input[$field]);
            }
        }
        return $input;
    }

    public function prepareInputForAdd($input): array|false
    {
        if ($input === false) {
            return false;
        }
        $input = $this->prepareGroupFields($input);
        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input): array|false
    {
        if ($input === false) {
            return false;
        }
        $input = $this->prepareGroupFields($input);
        return parent::prepareInputForUpdate($input);
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
    }

    public function post_getFromDB()
    {
        $group_fields = ['groups_id', 'groups_id_tech'];
        foreach ($group_fields as $field) {
            if (array_key_exists($field, $this->fields)) {
                $this->fields[$field] = json_decode($this->fields[$field] ?? '[]', false) ?? [];
                if ($this->fields[$field] === 0) {
                    $this->fields[$field] = [];
                }
                if (!is_array($this->fields[$field])) {
                    $this->fields[$field] = [$this->fields[$field]];
                }
            }
        }
    }
}
