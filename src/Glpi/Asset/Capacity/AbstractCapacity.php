<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Asset\Capacity;

use CommonDBRelation;
use CommonDBTM;
use DisplayPreference;
use Exception;
use Glpi\Asset\Asset;
use Glpi\Asset\AssetDefinition;
use Glpi\Asset\CapacityConfig;
use Log;

/**
 * Abstract capacity that provides, among others, an empty implementation
 * of some `\Glpi\Asset\Capacity\CapacityInterface`
 * methods that can legitimately be effectless.
 */
abstract class AbstractCapacity implements CapacityInterface
{
    /**
     * Constructor.
     *
     * Declared as final to ensure that constructor can be called without having to pass any parameter.
     */
    final public function __construct() {}

    public function getDescription(): string
    {
        return '';
    }

    public function getConfigurationForm(string $fieldname_prefix, ?CapacityConfig $current_config): ?string
    {
        return null;
    }

    public function getSearchOptions(string $classname): array
    {
        return [];
    }

    public function getSpecificRights(): array
    {
        return [];
    }

    public function getCloneRelations(): array
    {
        return [];
    }

    /**
     * @param class-string<Asset> $classname
     * @return bool
     */
    public function isUsed(string $classname): bool
    {
        return $classname::getDefinition()->hasCapacityEnabled($this);
    }

    /**
     * Count the number of peer item used.
     *
     * The count is based on the number of distinct peer items IDs found in the table of the relation class
     * in rows linked to the given asset class.
     *
     * @param class-string<CommonDBTM> $asset_classname
     * @param class-string<CommonDBTM> $relation_classname
     * @param array $specific_criteria
     * @return int
     */
    final protected function countPeerItemsUsage(string $asset_classname, string $relation_classname, array $specific_criteria = []): int
    {
        global $DB;

        if (is_a($relation_classname, CommonDBRelation::class, true)) {
            // We assume that asset type/id are always store in `itemtype`/`items_id` fields
            // and we use the other `items_id_X` property as the peer item ID storage field.
            $distinct_field  = null;
            if ($relation_classname::$items_id_1 === 'items_id') {
                $distinct_field  = $relation_classname::$items_id_2;
            } elseif ($relation_classname::$items_id_2 === 'items_id') {
                $distinct_field  = $relation_classname::$items_id_1;
            }
            if ($distinct_field === null) {
                throw new Exception('Unable to compute peer item foreign key field.');
            }

            return countDistinctElementsInTable(
                $relation_classname::getTable(),
                $distinct_field,
                [
                    'itemtype' => $asset_classname,
                ] + $specific_criteria
            );
        }

        if (
            is_a($relation_classname, CommonDBRelation::class, true)
            || $DB->fieldExists($relation_classname::getTable(), 'itemtype')
        ) {
            // We assume that asset type/id are always store in `itemtype`/`items_id` fields.
            return countElementsInTable(
                $relation_classname::getTable(),
                [
                    'itemtype' => $asset_classname,
                ] + $specific_criteria
            );
        }

        throw new Exception('Unable to compute peer items usage.');
    }

    /**
     * Count the number assets that are linked to a peer item.
     *
     * The count is based on the number of distinct assets IDs found in the table of the relation class.
     *
     * @param class-string<CommonDBTM> $asset_classname
     * @param class-string<CommonDBTM> $relation_classname
     * @param array $specific_criteria
     * @return int
     */
    final protected function countAssetsLinkedToPeerItem(string $asset_classname, string $relation_classname, array $specific_criteria = []): int
    {
        // We assume that asset type/id are always store in `itemtype`/`items_id` fields.
        return countDistinctElementsInTable(
            $relation_classname::getTable(),
            'items_id',
            [
                'itemtype' => $asset_classname,
            ] + $specific_criteria
        );
    }

    /**
     * Count the number of assets for the given asset definition.
     *
     * @param class-string<Asset> $classname
     * @param array<string, mixed>            $where_clause
     *
     * @return int
     */
    protected function countAssets(string $classname, array $where_clause = []): int
    {
        return countElementsInTable(
            Asset::getTable(),
            $where_clause + [
                AssetDefinition::getForeignKeyField() => $classname::getDefinition()->fields['id'],
            ]
        );
    }

    public function onClassBootstrap(string $classname, CapacityConfig $config): void {}

    public function onObjectInstanciation(Asset $object, CapacityConfig $config): void {}

    public function onCapacityEnabled(string $classname, CapacityConfig $config): void {}

    public function onCapacityDisabled(string $classname, CapacityConfig $config): void {}

    public function onCapacityUpdated(string $classname, CapacityConfig $old_config, CapacityConfig $new_config): void {}

    /**
     * Delete logs related to relations between two itemtypes.
     *
     * @param string $source_itemtype
     * @param string $linked_itemtype
     * @param bool $both_sides = true
     *
     * @return void
     */
    protected function deleteRelationLogs(
        string $source_itemtype,
        string $linked_itemtype,
        bool $both_sides = true
    ): void {
        global $DB;

        $criteria = [
            ['itemtype' => $source_itemtype, 'itemtype_link' => $linked_itemtype],
            // Some itemtypes are postfixed with #{fieldname}
            ['itemtype' => $source_itemtype, 'itemtype_link' => ['LIKE', $linked_itemtype . "#%"]],
        ];

        if ($both_sides) {
            $criteria[] = ['itemtype' => $linked_itemtype, 'itemtype_link' => $source_itemtype];
            // Some itemtypes are postfixed with #{fieldname}
            $criteria[] = ['itemtype' => $linked_itemtype, 'itemtype_link' => ['LIKE', $source_itemtype . "#%"]];
        }

        // Do not use `CommonDBTM::deleteByCriteria()` to prevent performances issues
        $DB->delete(
            Log::getTable(),
            [
                'OR' => $criteria,
            ]
        );
    }

    /**
     * Delete logs related to given fields (identified by their search options ID).
     *
     * @param string $itemtype
     * @param array $search_options
     * @return void
     */
    protected function deleteFieldsLogs(string $itemtype, array $search_options): void
    {
        global $DB;

        $ids = $this->extractOptionsIds($search_options);
        if (count($ids) === 0) {
            return;
        }

        // Do not use `CommonDBTM::deleteByCriteria()` to prevent performances issues
        $DB->delete(
            Log::getTable(),
            [
                'itemtype'          => $itemtype,
                'id_search_option'  => $ids,
            ]
        );
    }

    /**
     * Delete display preferences for given search options.
     *
     * @param string $itemtype
     * @param array $search_options
     * @return void
     */
    protected function deleteDisplayPreferences(string $itemtype, array $search_options): void
    {
        $ids = $this->extractOptionsIds($search_options);
        if (count($ids) === 0) {
            return;
        }

        $display_preference = new DisplayPreference();
        $display_preference->deleteByCriteria(
            [
                'itemtype' => $itemtype,
                'num'      => $ids,
            ],
            force: true,
            history: false
        );
    }

    /**
     * Extract search options IDs from a list of search options.
     *
     * @param array $search_options
     * @return array
     */
    private function extractOptionsIds(array $search_options): array
    {
        $ids = [];

        foreach ($search_options as $search_option) {
            if (
                !is_array($search_option)
                || !array_key_exists('id', $search_option)
                || (!is_int($search_option['id']) && !ctype_digit($search_option['id']))
            ) {
                continue;
            }

            $ids[] = $search_option['id'];
        }

        return $ids;
    }

    /**
     * Register the given itemtype to a type configuration.
     *
     * @param string $config_name
     * @param string $itemtype
     * @return void
     */
    protected function registerToTypeConfig(string $config_name, string $itemtype): void
    {
        global $CFG_GLPI;

        if (!in_array($itemtype, $CFG_GLPI[$config_name])) {
            $CFG_GLPI[$config_name][] = $itemtype;
        }
    }

    /**
     * Unregister the given itemtype from a type configuration.
     *
     * @param string $config_name
     * @param string $itemtype
     * @return void
     */
    protected function unregisterFromTypeConfig(string $config_name, string $itemtype): void
    {
        global $CFG_GLPI;

        $CFG_GLPI[$config_name] = array_values(
            array_diff(
                $CFG_GLPI[$config_name],
                [$itemtype]
            )
        );
    }
}
