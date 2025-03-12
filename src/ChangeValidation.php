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
 * ChangeValidation class
 */
class ChangeValidation extends CommonITILValidation
{
   // From CommonDBChild
    public static $itemtype           = 'Change';
    public static $items_id           = 'changes_id';

    public static $rightname                 = 'changevalidation';


    public static function getTypeName($nb = 0)
    {
        return _n('Change approval', 'Change approvals', $nb);
    }

    /**
     * Compute the validation status
     *
     * @param $item CommonITILObject
     *
     * @return integer
     **/
    public static function computeValidationStatus(CommonITILObject $item)
    {
        // Percent of validation
        $validation_percent = $item->fields['validation_percent'];

        $statuses           = [self::ACCEPTED => 0,
            self::WAITING  => 0,
            self::REFUSED  => 0
        ];
        // @todoseb voir si je reprend ça dans ma fonction de compute
        $validations        = getAllDataFromTable(
            static::getTable(),
            [
                static::$items_id => $item->getID()
            ]
        );

        if ($total = count($validations)) {
            foreach ($validations as $validation) {
                $statuses[$validation['status']]++;
            }
        }

        $accepted = 0;
        $refused  = 0;
        if ($total) {
            $accepted = round($statuses[self::ACCEPTED] * 100 / $total);
            $refused  = round($statuses[self::REFUSED]  * 100 / $total);
        }

        return static::computeValidation(
            $accepted,
            $refused,
            $validation_percent
        );
    }

    /**
     * Compute the validation status from the percentage of acceptation, the
     * percentage of refusals and the target acceptation threshold
     *
     * @param int $accepted             0-100 (percentage of acceptation)
     * @param int $refused              0-100 (percentage of refusals)
     * @param int $validation_percent   0-100 (target accepation threshold)
     *
     * @return int the validation status : ACCEPTED|REFUSED|WAITING
     */
    public static function computeValidation(
        int $accepted,
        int $refused,
        int $validation_percent
    ): int {
        if ($validation_percent > 0) {
            if ($accepted >= $validation_percent) {
                // We have reached the acceptation threshold
                return self::ACCEPTED;
            } else if ($refused + $validation_percent > 100) {
                // We can no longer reach the acceptation threshold
                return self::REFUSED;
            }
        } else {
            // No validation threshold set, one approval or denial is enough
            if ($accepted > 0) {
                return self::ACCEPTED;
            } else if ($refused > 0) {
                return self::REFUSED;
            }
        }

        return self::WAITING;
    }
}
