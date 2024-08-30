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

    public function canPurgeItem()
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        self::showForItem($item, $withtemplate);
        return true;
    }

    public static function getItems(CommonDBTM $item, $start = 0, $limit = 0, $used = false)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $kbi_cat_table = self::getTable();

        $criteria = [
            'FROM'      => [$kbi_cat_table],
            'FIELDS'    => [$kbi_cat_table => '*'],
            'INNER JOIN' => [
                'glpi_knowbaseitems' => [
                    'ON'  => [
                        $kbi_cat_table        => 'knowbaseitems_id',
                        'glpi_knowbaseitems' => 'id'
                    ]
                ]
            ],
            'WHERE'     => [],
            'GROUPBY'   => [
                $kbi_cat_table . '.id'
            ]
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
            $criteria['START'] = intval($start);
            $criteria['LIMIT'] = intval($limit);
        }

        $linked_items = [];
        $results = $DB->request($criteria);
        foreach ($results as $data) {
            if ($used === false) {
                $linked_items[] = $data;
            } else {
                $key = $item::getType() == KnowbaseItem::getType() ? 'items_id' : 'knowbaseitems_id';
                $linked_items[$data[$key]] = $data[$key];
            }
        }
        return $linked_items;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (
            ($item instanceof CommonDBTM)
            && static::canView()
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable(
                    self::getTable(),
                    ['knowbaseitems_id' => $item->getID()]
                );
            }

            $type_name = _n('Category', 'Categories', 1);

            return self::createTabEntry($type_name, $nb);
        }
        return '';
    }

    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        $item_id = $item->getID();
        $item_type = $item::getType();

        if (isset($_GET["start"])) {
            $start = intval($_GET["start"]);
        } else {
            $start = 0;
        }

        $canedit = $item->can($item_id, UPDATE);

       // Total Number of events
        $number = countElementsInTable(
            self::getTable(),
            ['knowbaseitems_id' => $item->getID()]
        );

        $ok_state = true;
        if ($item instanceof CommonITILObject) {
            $ok_state = !in_array(
                $item->fields['status'],
                array_merge(
                    $item->getClosedStatusArray(),
                    $item->getSolvedStatusArray()
                )
            );
        }

        $rand = mt_rand();

        if ($canedit && $ok_state) {
            echo '<form method="post" action="' . Toolbox::getItemTypeFormURL(__CLASS__) . '">';
            echo "<div class='center'>";
            echo "<table class=\"tab_cadre_fixe\">";
            echo "<tr><th colspan=\"2\">";
            echo  __('Add a category');
            echo "</th><tr>";
            echo "<tr class='tab_bg_2'><td>";
            KnowbaseItemCategory::dropdown(['rand' => $rand]);
            echo "</td><td>";
            echo "<input type=\"submit\" name=\"add\" value=\"" . _sx('button', 'Add') . "\" class=\"btn btn-primary\">";
            echo "</td></tr>";
            echo "</table>";
            echo '<input type="hidden" name="knowbaseitems_id" value="' . $item->getID() . '">';
            echo "</div>";
            Html::closeForm();
        }

       // No Events in database
        if ($number < 1) {
            $no_txt = ($item_type == KnowbaseItem::getType()) ?
            __('No linked items') :
            __('No knowledge base entries linked');
            echo "<div class='center'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>$no_txt</th></tr>";
            echo "</table>";
            echo "</div>";
            return;
        }

       // Display the pager
        $type_name = self::getTypeName(1);
        Html::printAjaxPager($type_name, $start, $number);

       // Output events
        echo "<div class='center'>";

        if ($canedit) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams
            = ['num_displayed'
                        => min($_SESSION['glpilist_limit'], $number),
                'container'
                        => 'mass' . __CLASS__ . $rand
            ];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixehov'>";

        $header = '<tr>';

        if ($canedit) {
            $header    .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand) . "</th>";
        }

        $header .= "<th>" . __('Category name') . "</th>";
        $header .= "</tr>";
        echo $header;

        foreach (self::getItems($item, $start, $_SESSION['glpilist_limit']) as $data) {
            $linked_category = getItemForItemtype(KnowbaseItemCategory::getType());
            $linked_category->getFromDB($data['knowbaseitemcategories_id']);

            $name = $linked_category->fields['name'];
            if (
                $_SESSION["glpiis_ids_visible"]
                || empty($name)
            ) {
                $name = sprintf(__('%1$s (%2$s)'), $name, $linked_category->getID());
            }

            $link = $linked_category::getFormURLWithID($linked_category->getID());

           // show line
            echo "<tr class='tab_bg_2'>";

            if ($canedit) {
                echo "<td width='10'>";
                Html::showMassiveActionCheckBox(__CLASS__, $data['id']);
                echo "</td>";
            }

            echo "<td><a href=\"" . $link . "\">" . $name . "</a></td>";
            echo "</tr>";
        }
        echo $header;
        echo "</table>";

        $massiveactionparams['ontop'] = false;
        Html::showMassiveActions($massiveactionparams);

        echo "</div>";
        Html::printAjaxPager($type_name, $start, $number);
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
            $action_prefix = __CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR;

            $actions[$action_prefix . 'add']
            = "<i class='ma-icon fas fa-book'></i>" .
              _x('button', 'Link knowledgebase article');
        }

        parent::getMassiveActionsForItemtype($actions, $itemtype, $is_deleted, $checkitem);
    }
}
