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

/**
 * @since 9.5.0
 */
class ImpactRelation extends CommonDBRelation
{
    // CommonDBRelation fields
    public static $itemtype_1          = 'itemtype_source';
    public static $items_id_1          = 'items_id_source';
    public static $itemtype_2          = 'itemtype_impacted';
    public static $items_id_2          = 'items_id_impacted';

    public function prepareInputForAdd($input)
    {
        global $DB;

        // Check that mandatory values are set
        $required = [
            "itemtype_source",
            "items_id_source",
            "itemtype_impacted",
            "items_id_impacted",
        ];
        if (array_diff($required, array_keys($input))) {
            return false;
        }

        // Check that source and impacted are different items
        if (
            $input['itemtype_source'] == $input['itemtype_impacted']
            && $input['items_id_source'] == $input['items_id_impacted']
        ) {
            return false;
        }

        // Check for duplicate
        $it = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'itemtype_source'   => $input['itemtype_source'],
                'items_id_source'   => $input['items_id_source'],
                'itemtype_impacted' => $input['itemtype_impacted'],
                'items_id_impacted' => $input['items_id_impacted'],
            ],
        ]);
        if (count($it)) {
            return false;
        }

        // Check if source and impacted are valid objets
        $source_exist = Impact::assetExist(
            $input['itemtype_source'],
            $input['items_id_source']
        );
        $impacted_exist = Impact::assetExist(
            $input['itemtype_impacted'],
            $input['items_id_impacted']
        );
        if (!$source_exist || !$impacted_exist) {
            return false;
        }

        return $input;
    }

    /**
     * Get an impact id from an input form
     *
     * @param array $input   Array containing the impact to be deleted
     *
     * @return bool false on failure
     */
    public static function getIDFromInput(array $input)
    {
        global $DB;

        // Check that the link exist
        $it = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'itemtype_source'   => $input['itemtype_source'],
                'items_id_source'   => $input['items_id_source'],
                'itemtype_impacted' => $input['itemtype_impacted'],
                'items_id_impacted' => $input['items_id_impacted'],
            ],
        ]);

        if (count($it)) {
            return $it->current()['id'];
        }

        return false;
    }
}
