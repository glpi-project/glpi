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
 * Item_Problem Class
 *
 *  Relation between Problems and Items
 **/
class Item_Problem extends CommonItilObject_Item
{
   // From CommonDBRelation
    public static $itemtype_1          = 'Problem';
    public static $items_id_1          = 'problems_id';

    public static $itemtype_2          = 'itemtype';
    public static $items_id_2          = 'items_id';
    public static $checkItem_2_Rights  = self::HAVE_VIEW_RIGHT_ON_ITEM;

    public static function getTypeName($nb = 0)
    {
        return _n('Problem item', 'Problem items', $nb);
    }

    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    public function prepareInputForAdd($input)
    {

       // Avoid duplicate entry
        if (
            countElementsInTable($this->getTable(), ['problems_id' => $input['problems_id'],
                'itemtype'    => $input['itemtype'],
                'items_id'    => $input['items_id']
            ]) > 0
        ) {
            return false;
        }
        return parent::prepareInputForAdd($input);
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (in_array($item::getType(), $CFG_GLPI['asset_types'], true) && !$this->shouldDisplayTabForAsset($item)) {
            return '';
        }

        if (!$withtemplate) {
            switch ($item::class) {
                case Problem::class:
                    return self::createTabEntry(
                        text: _n('Item', 'Items', Session::getPluralNumber()),
                        nb: static fn () => self::countForMainItem($item),
                        form_itemtype: $item::class
                    );

                case User::class:
                case Group::class:
                case Supplier::class:
                    $from = $item::class === Group::class ? 'glpi_groups_problems' : 'glpi_problems_' . strtolower($item::class . 's');
                    return self::createTabEntry(
                        text: Problem::getTypeName(Session::getPluralNumber()),
                        nb: static fn () => countElementsInTable($from, [$item::getForeignKeyField() => $item->fields['id']]),
                        form_itemtype: $item::class
                    );

                default:
                    if (Session::haveRight("problem", Problem::READALL)) {
                        return self::createTabEntry(
                            text: Problem::getTypeName(Session::getPluralNumber()),
                            nb: static fn () => self::countForItemAndLinks($item),
                            form_itemtype: $item::class
                        );
                    }
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case 'Problem':
                self::showForObject($item);
                break;

            default:
                Problem::showListForItem($item, $withtemplate);
        }
        return true;
    }
}
