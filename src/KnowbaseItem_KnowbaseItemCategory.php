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
 *  Class KnowbaseItem_KnowbaseItemCategory
 *
 *  @since 10.0.0
 */
class KnowbaseItem_KnowbaseItemCategory extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1          = 'KnowbaseItem';
    public static $items_id_1          = 'knowbaseitems_id';
    public static $itemtype_2          = 'KnowbaseItemCategory';
    public static $items_id_2          = 'knowbaseitemcategories_id';
    public static $checkItem_2_Rights  = self::HAVE_VIEW_RIGHT_ON_ITEM;

    // From CommonDBTM
    public $dohistory = true;

    public static $rightname = 'knowbase';

    public function canPurgeItem(): bool
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public static function getItems(CommonDBTM $item, $start = 0, $limit = 0, $used = false)
    {
        global $DB;

        $kbi_cat_table = self::getTable();

        $criteria = [
            'FROM'      => [$kbi_cat_table],
            'FIELDS'    => [$kbi_cat_table => '*'],
            'INNER JOIN' => [
                'glpi_knowbaseitems' => [
                    'ON'  => [
                        $kbi_cat_table        => 'knowbaseitems_id',
                        'glpi_knowbaseitems' => 'id',
                    ],
                ],
            ],
            'WHERE'     => [],
            'GROUPBY'   => [
                $kbi_cat_table . '.id',
            ],
        ];
        $where = [];

        $items_id  = $item->fields['id'];

        $id_field = $kbi_cat_table . '.knowbaseitems_id';
        $visibility = KnowbaseItem::getVisibilityCriteria();
        if (count($visibility['LEFT JOIN'])) {
            $criteria['LEFT JOIN'] = $visibility['LEFT JOIN'];
            if (isset($visibility['WHERE'])) {
                $where = $visibility['WHERE'];
            }
        }

        $criteria['WHERE'] = [$id_field => $items_id];
        if (count($where)) {
            $criteria['WHERE'] = array_merge($criteria['WHERE'], $where);
        }

        if ($limit) {
            $criteria['START'] = (int) $start;
            $criteria['LIMIT'] = (int) $limit;
        }

        $linked_items = [];
        $results = $DB->request($criteria);
        foreach ($results as $data) {
            if ($used === false) {
                $linked_items[] = $data;
            } else {
                $key = $item::class === KnowbaseItem::class ? 'items_id' : 'knowbaseitems_id';
                $linked_items[$data[$key]] = $data[$key];
            }
        }
        return $linked_items;
    }

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public static function getMassiveActionsForItemtype(
        array &$actions,
        $itemtype,
        $is_deleted = false,
        ?CommonDBTM $checkitem = null
    ) {

        $kb_item = new KnowbaseItem();
        $kb_item->getEmpty();
        if ($kb_item->canViewItem()) {
            $action_prefix = self::class . MassiveAction::CLASS_ACTION_SEPARATOR;

            $actions[$action_prefix . 'add']
            = "<i class='ma-icon ti ti-book'></i>"
              . _sx('button', 'Link knowledgebase article');
        }

        parent::getMassiveActionsForItemtype($actions, $itemtype, $is_deleted, $checkitem);
    }
}
