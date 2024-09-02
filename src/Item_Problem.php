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
        /**
         * @var \DBmysql $DB
         * @var array $CFG_GLPI
         **/
        global $DB, $CFG_GLPI;

        if (in_array($item::getType(), $CFG_GLPI['asset_types']) && !$this->shouldDisplayTabForAsset($item)) {
            return '';
        }

        if (!$withtemplate) {
            $nb = 0;
            switch ($item->getType()) {
                case 'Problem':
                    /** @var Problem $item */
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForMainItem($item);
                    }
                    return self::createTabEntry(_n('Item', 'Items', Session::getPluralNumber()), $nb, $item::getType());

                case 'User':
                case 'Group':
                case 'Supplier':
                    /** @var User|Group|Supplier $item */
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $from = $item->getType() == 'Group' ? 'glpi_groups_problems' : 'glpi_problems_' . strtolower($item->getType() . 's');
                        $result = $DB->request([
                            'COUNT'  => 'cpt',
                            'FROM'   => $from,
                            'WHERE'  => [
                                $item->getForeignKeyField()   => $item->fields['id']
                            ]
                        ])->current();
                        $nb = $result['cpt'];
                    }
                    return self::createTabEntry(Problem::getTypeName(Session::getPluralNumber()), $nb, $item::getType());

                default:
                    if (
                        Session::haveRight("problem", Problem::READALL)
                        && ($item instanceof CommonDBTM)
                    ) {
                        if ($_SESSION['glpishow_count_on_tabs']) {
                              // Direct one
                              $nb = self::countForItem($item);
                              // Linked items
                              $linkeditems = $item->getLinkedItems();

                            if (count($linkeditems)) {
                                foreach ($linkeditems as $type => $tab) {
                                    $typeitem = new $type();
                                    foreach ($tab as $ID) {
                                        if ($typeitem->getFromDB($ID)) {
                                            $nb += self::countForItem($typeitem);
                                        }
                                    }
                                }
                            }
                        }
                        return self::createTabEntry(Problem::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
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
