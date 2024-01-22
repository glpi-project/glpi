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

namespace Glpi\Asset\Capacity;

use DisplayPreference;
use Glpi\Asset\Asset;
use Log;
use Profile;
use ProfileRight;

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
     * Declared as final to ensure that constructor can be call without having to pass any parameter.
     */
    final public function __construct()
    {
    }

    public function getSearchOptions(string $classname): array
    {
        return [];
    }

    public function getSpecificRights(): array
    {
        return [];
    }

    public function onClassBootstrap(string $classname): void
    {
    }

    public function onObjectInstanciation(Asset $object): void
    {
    }

    public function onCapacityDisabled(string $classname): void
    {
    }

    /**
     * Delete logs related to relations between two itemtypes.
     *
     * @param string $source_itemtype
     * @param string $linked_itemtype
     * @return void
     */
    protected function deleteRelationLogs(string $source_itemtype, string $linked_itemtype): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        // Do not use `CommonDBTM::deleteByCriteria()` to prevent performances issues
        $DB->delete(
            Log::getTable(),
            [
                'OR' => [
                    ['itemtype' => $source_itemtype, 'itemtype_link' => $linked_itemtype],
                    ['itemtype' => $linked_itemtype, 'itemtype_link' => $source_itemtype],
                ],
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
        /** @var \DBmysql $DB */
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
     * Delete logs related to given itemtypes (identified by the "itemtype_link"
     * field in the logs table).
     *
     * If possible, you should use the deleteFieldsLogs() method instead.
     * However, the searchoptions id are sometimes not set correctly in the
     * database for some itemtypes (e.g. OperatingSystem and
     * Item_OperatingSystem), which require us to use this method to properly
     * delete logs.
     *
     * TODO: investigate the missing searchoptions id issue to see if it can
     * be prevented (in this case, this method should be deleted once its fixed)
     *
     * @param string $itemtype The itemtype to delete logs for
     * @param array $itemtypes The target linked itemtypes
     *
     * @return void
     */
    protected function deleteFieldsLogsByItemtypeLink(
        string $itemtype,
        array $linked_itemtypes
    ): void {
        /** @var \DBmysql $DB */
        global $DB;

        $link_criteria = [];
        foreach ($linked_itemtypes as $link) {
            // Search for "itemtype" and "itemtype#%"
            $link_criteria[] = ['itemtype_link' => $link];
            $link_criteria[] = ['itemtype_link' => ['LIKE', $link . "#%"]];
        }

        // Do not use `CommonDBTM::deleteByCriteria()` to prevent performances
        // issues
        $DB->delete(
            Log::getTable(),
            [
                'itemtype' => $itemtype,
                'OR'       => $link_criteria,
            ]
        );
    }

    /**
     * Delete display preferences for given search options.
     *
     * @param string $source_itemtype
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
}
