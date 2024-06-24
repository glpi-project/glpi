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

namespace Glpi\System\Diagnostic;

use CommonDBTM;
use CommonTreeDropdown;

/**
 * @since 10.0.0
 */
class DatabaseKeysChecker extends AbstractDatabaseChecker
{
    /**
     * Get list of missing keys, basing detection on column names.
     *
     * Array keys are expected key names, and array values are fields that should be contained in these keys.
     *
     * @param string $table_name
     *
     * @return array
     */
    public function getMissingKeys(string $table_name): array
    {
        $missing_keys = [];

        $misnamed_keys = $this->getMisnamedKeys($table_name);

        $columns = $this->getColumnsNames($table_name);
        foreach ($columns as $column_name) {
            $itemtype = getItemTypeForTable($table_name);
            $column_name_matches = [];
            if (
                is_a($itemtype, CommonDBTM::class, true)
                && $column_name === $itemtype::getNameField()
                && preg_match('/text$/', $this->getColumnType($table_name, $column_name)) !== 1
            ) {
                if (!$this->areFieldsCorrecltyIndexed($table_name, [$column_name], $column_name)) {
                  // Expect a key with same name as field.
                    $missing_keys[$column_name] = [$column_name];
                }
            } else if (preg_match('/^items_id(?<suffix>_.+)?/', $column_name, $column_name_matches)) {
                $suffix = $column_name_matches['suffix'] ?? '';
                $expected_key = 'item' . $suffix;
                if (
                    in_array('itemtype' . $suffix, $columns)
                    && !in_array($expected_key, $misnamed_keys)
                    && !$this->areFieldsCorrecltyIndexed($table_name, ['itemtype' . $suffix, 'items_id' . $suffix], $expected_key)
                ) {
                    // Expect a key named item for itemtype/items_id polymorphic foreign keys
                    $missing_keys[$expected_key] = ['itemtype' . $suffix, 'items_id' . $suffix];
                }
            } else if (
                isForeignKeyField($column_name)
                || preg_match('/^date(_(mod|creation))$/', $column_name)
                || preg_match('/^is_(active|deleted|dynamic|recursive|template)$/', $column_name)
                || (is_a($itemtype, CommonTreeDropdown::class, true) && $column_name === 'level')
            ) {
                $ignored_fields = [
                    'glpi_ipaddresses.mainitems_id', // FIXME Should be renamed to glpi_ipaddresses.items_id_main to fit naming conventions.
                    'glpi_networkportaggregates.networkports_id_list', // FIXME Should be replaced by a relation table.
                    'glpi_planningexternalevents.users_id_guests', // FIXME Should be replaced by a relation table.
                    'glpi_dashboards_items.card_id', // FIXME This is not a foreign key.
                    'glpi_dashboards_items.gridstack_id', // FIXME This is not a foreign key.
                ];
                if (in_array("$table_name.$column_name", $ignored_fields)) {
                    continue;
                }
                $expected_key = $column_name;
                if (!in_array($expected_key, $misnamed_keys) && !$this->areFieldsCorrecltyIndexed($table_name, [$column_name], $expected_key)) {
                   // Expect a key with same name as field or a key that contains multiple fields including current one.
                    $missing_keys[$expected_key] = [$column_name];
                }
            }
        }

        return $missing_keys;
    }

    /**
     * Get list of keys having a name that mismatch conventions.
     *
     * Array keys are misnamed key names, and array values are expected key names.
     *
     * @param string $table_name
     *
     * @return array
     */
    public function getMisnamedKeys(string $table_name): array
    {
        $misnamed_keys = [];

        $index = $this->getIndex($table_name);
        foreach ($index as $key => $fields) {
            if ($key === 'PRIMARY' || $key === 'unicity') {
                continue; // PRIMARY and 'unicity' cannot be misnamed.
            }

            if (count($fields) === 1 && $key !== reset($fields)) {
               // A key corresponding to a unique field should be named like this field.
                $misnamed_keys[$key] = reset($fields);
            } else if (count($fields) === 2 && count($matching_fields = preg_grep('/^items_id(_.+)?/', $fields)) === 1) {
                $items_id_field = reset($matching_fields);
                $suffix = str_replace('items_id', '', $items_id_field);
                $expected_key = 'item' . $suffix;
                if (in_array('itemtype' . $suffix, $fields) && $key !== $expected_key) {
                    $misnamed_keys[$key] = $expected_key;
                }
            }
        }

        return $misnamed_keys;
    }

    /**
     * Get list of keys that are included in larger keys.
     *
     * Array keys are useless key names, and array values are expected larger key names.
     *
     * @param string $table_name
     *
     * @return array
     */
    public function getUselessKeys(string $table_name): array
    {
        $useless_keys = [];

        $index = $this->getIndex($table_name);
        foreach ($index as $checked_key => $checked_fields) {
            if ($checked_key === 'PRIMARY' || $checked_key === 'unicity') {
                continue; // PRIMARY and 'unicity' cannot be useless.
            }

            foreach ($index as $other_key => $other_fields) {
                if ($checked_key === $other_key) {
                    continue; // This is not another key.
                }
                if (count($checked_fields) >= count($other_fields)) {
                    continue; // Other key does not contains more that expected fields, it is not a larger key.
                }

                foreach ($checked_fields as $i => $checked_field) {
                    if (!array_key_exists($i, $other_fields) || $other_fields[$i] !== $checked_field) {
                        break;
                    }
                   // Fields are considered as part of a larger index only if they are located at the beginning of
                   // this larger index. Otherwise, MySQL will not be able to use the index if preceding fields
                   // are not tested in the query.
                    $useless_keys[$checked_key] = $other_key;
                }
            }
        }

        return $useless_keys;
    }

    /**
     * Check if fields are correctly indexed.
     *
     * @param string $table_name
     * @param array $fields
     * @param string $expected_key
     *
     * @return bool
     */
    private function areFieldsCorrecltyIndexed(string $table_name, array $fields, string $expected_key): bool
    {
        $index = $this->getIndex($table_name);

       // Check if primary key matches matches given fields.
        if (array_key_exists('PRIMARY', $index) && $index['PRIMARY'] === $fields) {
            return true;
        }

       // Check if expected key exists and matches given fields.
        if (array_key_exists($expected_key, $index) && $index[$expected_key] === $fields) {
            return true;
        }

       // Check if unicity key exists and matches given fields.
        if (array_key_exists('unicity', $index) && $index['unicity'] === $fields) {
            return true;
        }

       // Check if a larger key exists and contains given fields.
        foreach ($index as $key_fields) {
            if (count($fields) >= count($key_fields)) {
                continue; // Key does not contains more that expected fields, it is not a larger key.
            }
            foreach ($fields as $i => $field) {
                if (!array_key_exists($i, $key_fields) || $key_fields[$i] !== $field) {
                    break;
                }
               // Fields are considered as part of a larger index only if they are located at the beginning of
               // this larger index. Otherwise, MySQL will not be able to use the index if preceding fields
               // are not tested in the query.
                return true;
            }
        }

        return false;
    }
}
