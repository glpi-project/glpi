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


    /**
     * Print the HTML array for Items linked to a change
     *
     * @param $change Change object
     *
     * @return boolean|void
     **/
    public static function showForChange(Change $change)
    {
        Toolbox::deprecated();
        static::showForObject($change);
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        /** @var \DBmysql $DB */
        global $DB;

        /** @var CommonDBTM $item */
        if (!$withtemplate) {
            $nb = 0;
            switch ($item->getType()) {
                case 'Change':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForMainItem($item);
                    }
                    return self::createTabEntry(_n('Item', 'Items', Session::getPluralNumber()), $nb, $item::getType());

                case 'User':
                case 'Group':
                case 'Supplier':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $from = 'glpi_changes_' . strtolower($item->getType() . 's');
                        $result = $DB->request([
                            'COUNT'  => 'cpt',
                            'FROM'   => $from,
                            'WHERE'  => [
                                $item->getForeignKeyField()   => $item->fields['id']
                            ]
                        ])->current();
                        $nb = $result['cpt'];
                    }
                    return self::createTabEntry(Change::getTypeName(Session::getPluralNumber()), $nb, $item::getType());

                default:
                    if (Session::haveRight("change", Change::READALL)) {
                        if ($_SESSION['glpishow_count_on_tabs']) {
                              // Direct one
                              $nb = self::countForItem($item);
                              // Linked items
                              $linkeditems = $item->getLinkedItems();

                            if (count($linkeditems)) {
                                foreach ($linkeditems as $type => $tab) {
                                    foreach ($tab as $ID) {
                                        $typeitem = new $type();
                                        if ($typeitem->getFromDB($ID)) {
                                            $nb += self::countForItem($typeitem);
                                        }
                                    }
                                }
                            }
                        }
                        return self::createTabEntry(Change::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
                    }
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case 'Change':
                self::showForChange($item);
                break;

            default:
                Change::showListForItem($item, $withtemplate);
        }
        return true;
    }


    public static function showForObject($change, $options = [])
    {
        // Get requester
        $class        = new $change->userlinkclass();
        $tickets_user = $class->getActors($change->fields['id']);
        $options['_users_id_requester'] = 0;
        if (
            isset($tickets_user[CommonITILActor::REQUESTER])
            && (count($tickets_user[CommonITILActor::REQUESTER]) == 1)
        ) {
            foreach ($tickets_user[CommonITILActor::REQUESTER] as $user_id_single) {
                $options['_users_id_requester'] = $user_id_single['users_id'];
            }
        }
        return parent::showForObject($change, $options);
    }
}
