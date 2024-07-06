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

/**
 * Change_Item Class
 *
 * Relation between Changes and Items
 **/
class Change_Item extends CommonItilObject_Item
{
   // From CommonDBRelation
    public static $itemtype_1          = 'Change';
    public static $items_id_1          = 'changes_id';

    public static $itemtype_2          = 'itemtype';
    public static $items_id_2          = 'items_id';
    public static $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;

    public static function getTypeName($nb = 0)
    {
        return _n('Change item', 'Change items', $nb);
    }

    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    public function prepareInputForAdd($input)
    {

       // Well, if I remember my PHP: empty(0) == true ...
        if (empty($input['changes_id']) || ($input['changes_id'] == 0)) {
            return false;
        }

       // Avoid duplicate entry
        if (
            countElementsInTable($this->getTable(), ['changes_id' => $input['changes_id'],
                'itemtype' => $input['itemtype'],
                'items_id' => $input['items_id']
            ]) > 0
        ) {
            return false;
        }
        return parent::prepareInputForAdd($input);
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        /**
         * @var \DBmysql $DB
         * @var array $CFG_GLPI
         **/
        global $DB, $CFG_GLPI;

        if (in_array($item::getType(), $CFG_GLPI['asset_types']) && !$this->shouldDisplayTabForAsset($item)) {
            return '';
        }

        /** @var CommonDBTM $item */
        if (!$withtemplate) {
            switch ($item::class) {
                case 'Change':
                    $nb = static fn () => self::countForMainItem($item);
                    break;
                case 'User':
                case 'Group':
                case 'Supplier':
                    $nb = static fn () => countElementsInTable('glpi_changes_' . strtolower($item::class . 's'), [
                        $item::getForeignKeyField() => $item->getID()
                    ]);
                    break;
                default:
                    if (!Session::haveRight("change", Change::READALL)) {
                        return '';
                    }
                    $nb = static fn () => self::countForItemAndLinks($item);
            }
            $label = $item::class === Change::class ? _n('Item', 'Items', Session::getPluralNumber()) : Change::getTypeName(Session::getPluralNumber());
            return self::createTabEntry($label, $nb, $item::class);
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case 'Change':
                self::showForObject($item);
                break;

            default:
                Change::showListForItem($item, $withtemplate);
        }
        return true;
    }
}
