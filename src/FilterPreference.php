<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Glpi\Application\View\TemplateRenderer;

final class FilterPreference extends CommonDBTM
{
    public static function getTypeName($nb = 0)
    {
        return _n('Status filter', 'Status filters', $nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case Preference::class:
                return self::createTabEntry(self::getTypeName(), 0, $item::getType());
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case Preference::class:
                $user = User::getById(Session::getLoginUserID());
                $filter = new FilterPreference();
                return $filter->showForUser($user);
        }

        return false;
    }

    public static function canView()
    {
        return true;
    }

    public static function canCreate()
    {
        return true;
    }

    public static function canUpdate()
    {
        return true;
    }

    public static function canDelete()
    {
        return true;
    }

    public function showForUser(User $user): bool
    {
        if ($user->isNewItem()) {
            return false;
        }

        $can_edit = ($user->fields['id'] == Session::getLoginUserID());
        if (!$can_edit) {
            return false;
        }

        TemplateRenderer::getInstance()->display('pages/admin/user.filters.html.twig', [
            'item'        => $this,
            'user'        => $user,
            'filters'     => $user->getFilters(),
            'canedit'     => $can_edit,
        ]);

        return true;
    }

    public function updateFilters($inputs): bool
    {
        foreach ($inputs as $key => $value) {
            if (preg_match('/^_(\w+)_defined$/', $key, $matches)) {
                list($prefix, $itemtype, $field) = explode('_', $matches[1], 3);
                $input = [
                    'users_id'  => $inputs['users_id'],
                    'itemtype'  => $itemtype,
                    'field'     => $field
                ];
                if (isset($inputs[$matches[1]])) {
                    $value = $inputs[$matches[1]];
                } else {
                    $value = [];
                }
                if (!$this->getFromDBByCrit($input)) {
                    $input['values'] = exportArrayToDB($value);
                    $this->add($input);
                } elseif ($this->fields['values'] != exportArrayToDB($value)) {
                    $input = [
                        'id'        => $this->getID(),
                        'values'    => exportArrayToDB($value)
                    ];
                    $this->update($input);
                }
            }
        }

        return true;
    }

    public static function getCriteriaForItemtype($itemtype): array
    {
        $criteria = [];
        $user = User::getById(Session::getLoginUserID());
        $filters = new FilterPreference();
        $filters = $filters->find([
            'users_id' => $user->fields['id'],
            'itemtype' => $itemtype,
        ]);
        foreach ($filters as $filter) {
            $values = json_decode($filter['values'], true);
            if (count($values) == 0) {
                continue;
            }
            $item = new $itemtype();
            $so = $item->rawSearchOptions();
            $item_table = getTableForItemType(getItemtypeForForeignKeyField($filter['field']));

            $field = 0;
            foreach ($so as $value) {
                if (isset($value['field'])) {
                    if (($value['field'] == 'name' || $value['field'] == 'completename') && $value['table'] == $item_table) {
                        $field = $value['id'];
                    }
                }
            }
            if (count($values) == 1) {
                $criteria[] = [
                    'link'          => "AND",
                    'field'         => $field,
                    'searchtype'    => "equals",
                    'virtual'       => true,
                    'value'         => $values[0],
                ];
            } elseif (count($values) > 1) {
                foreach ($values as $value) {
                    $or[] = [
                        'link'          => "OR",
                        'field'         => $field,
                        'searchtype'    => "equals",
                        'virtual'       => true,
                        'value'         => $value,
                    ];
                }
                $criteria[] = [
                    'link'     => "AND",
                    'criteria' => $or,
                ];
            }
        }

        return $criteria;
    }
}
