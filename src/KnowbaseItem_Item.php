<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
 *  Class KnowbaseItem_Item
 *
 *  @author Johan Cwiklinski <jcwiklinski@teclib.com>
 *
 *  @since 9.2
 */
class KnowbaseItem_Item extends CommonDBRelation
{
   // From CommonDBRelation
    public static $itemtype_1          = 'KnowbaseItem';
    public static $items_id_1          = 'knowbaseitems_id';
    public static $itemtype_2          = 'itemtype';
    public static $items_id_2          = 'items_id';
    public static $checkItem_2_Rights  = self::HAVE_VIEW_RIGHT_ON_ITEM;

   // From CommonDBTM
    public $dohistory          = true;

    public static function getTypeName($nb = 0)
    {
        return _n('Knowledge base item', 'Knowledge base items', $nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (static::canView()) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = self::getCountForItem($item);
            }

            $type_name = null;
            if ($item->getType() == KnowbaseItem::getType()) {
                $type_name = _n('Associated element', 'Associated elements', $nb);
            } else {
                $type_name = __('Knowledge base');
            }

            return self::createTabEntry($type_name, $nb);
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        self::showForItem($item, $withtemplate);
        return true;
    }

    /**
     * Show linked items of a knowbase item
     *
     * @param $item                     CommonDBTM object
     * @param $withtemplate    integer  withtemplate param (default 0)
     *
     **/
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

        // Total Number of KB items
        $number = self::getCountForItem($item);

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
            if ($item_type == KnowbaseItem::getType()) {
                echo  __('Add a linked item');
            } else {
                echo __('Link a knowledge base entry');
            }
            echo "</th><tr>";
            echo "<tr class='tab_bg_2'><td>";
            if ($item_type == KnowbaseItem::getType()) {
               //TODO: pass used array to restrict visible items in list
                $rand = self::dropdownAllTypes($item, 'items_id');
            } else {
                $visibility = KnowbaseItem::getVisibilityCriteria();
                $condition = (isset($visibility['WHERE']) && count($visibility['WHERE'])) ? $visibility['WHERE'] : [];
                $rand = KnowbaseItem::dropdown([
                    'entity'    => $item->getEntityID(),
                    'used'      => self::getItems($item, 0, 0, true),
                    'condition' => $condition
                ]);
            }
            echo "</td><td>";
            echo "<input type=\"submit\" name=\"add\" value=\"" . _sx('button', 'Add') . "\" class=\"btn btn-primary\">";
            echo "</td></tr>";
            echo "</table>";
            if ($item_type == KnowbaseItem::getType()) {
                echo '<input type="hidden" name="knowbaseitems_id" value="' . $item->getID() . '">';
            } else {
                echo "<input type=\"hidden\" name=\"items_id\" value=\"" . $item->getID() . "\">";
                echo "<input type=\"hidden\" name=\"itemtype\" value=\"" . $item::getType() . "\">";
            }
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
        $type_name = null;
        if ($item->getType() == KnowbaseItem::getType()) {
            $type_name = _n('Linked item', 'Linked items', 1);
        } else {
            $type_name = self::getTypeName(1);
        }
        Html::printAjaxPager($type_name, $start, $number);

       // Output events
        echo "<div class='center table-responsive'>";

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

        $header .= "<th>" . _n('Type', 'Types', 1) . "</th>";
        $header .= "<th>" . _n('Item', 'Items', 1) . "</th>";
        $header .= "<th>" . __('Creation date') . "</th>";
        $header .= "<th>" . __('Update date') . "</th>";
        $header .= "</tr>";
        echo $header;

        foreach (self::getItems($item, $start, $_SESSION['glpilist_limit']) as $data) {
            $linked_item = null;
            if ($item->getType() == KnowbaseItem::getType()) {
                $linked_item = getItemForItemtype($data['itemtype']);
                $linked_item->getFromDB($data['items_id']);
            } else {
                $linked_item = getItemForItemtype(KnowbaseItem::getType());
                $linked_item->getFromDB($data['knowbaseitems_id']);
            }

            $name = $linked_item->fields['name'];
            if (
                $_SESSION["glpiis_ids_visible"]
                || empty($name)
            ) {
                $name = sprintf(__('%1$s (%2$s)'), $name, $linked_item->getID());
            }

            $link = $linked_item::getFormURLWithID($linked_item->getID());

            // show line
            echo "<tr class='tab_bg_2'>";

            if ($canedit) {
                echo "<td width='10'>";
                Html::showMassiveActionCheckBox(__CLASS__, $data['id']);
                echo "</td>";
            }

            $type = $linked_item->getTypeName(1);
            if (isset($linked_item->fields['is_template']) && $linked_item->fields['is_template'] == 1) {
                $type .= ' (' . __('template') . ')';
            }

            echo "<td>" . $type . "</td>" .
                 "<td><a href=\"" . $link . "\">" . $name . "</a></td>" .
                 "<td class='tab_date'>" . Html::convDateTime($linked_item->fields['date_creation']) . "</td>" .
                 "<td class='tab_date'>" . Html::convDateTime($linked_item->fields['date_mod']) . "</td>";
            echo "</tr>";
        }
        echo $header;
        echo "</table>";

        $massiveactionparams['ontop'] = false;
        Html::showMassiveActions($massiveactionparams);

        echo "</div>";
        Html::printAjaxPager($type_name, $start, $number);
    }

    /**
     * Displays linked dropdowns to add linked items
     *
     * @param CommonDBTM $item Item instance
     * @param string     $name Field name
     *
     * @return string
     */
    public static function dropdownAllTypes(CommonDBTM $item, $name)
    {
        global $CFG_GLPI;

        $onlyglobal = 0;
        $entity_restrict = -1;
        $checkright = true;

        $rand = Dropdown::showSelectItemFromItemtypes([
            'items_id_name'   => $name,
            'entity_restrict' => $entity_restrict,
            'itemtypes'       => $CFG_GLPI['kb_types'],
            'onlyglobal'      => $onlyglobal,
            'checkright'      => $checkright
        ]);

        return $rand;
    }

    /**
     * Retrieve items for a knowbase item
     *
     * @param CommonDBTM $item      CommonDBTM object
     * @param integer    $start     first line to retrieve (default 0)
     * @param integer    $limit     max number of line to retrive (0 for all) (default 0)
     * @param boolean    $used      whether to retrieve data for "used" records
     *
     * @return array of linked items
     **/
    public static function getItems(CommonDBTM $item, $start = 0, $limit = 0, $used = false)
    {
        global $DB;

        $criteria = [
            'FROM'      => ['glpi_knowbaseitems_items'],
            'FIELDS'    => ['glpi_knowbaseitems_items' => '*'],
            'ORDER'     => ['itemtype', 'items_id DESC'],
            'GROUPBY'   => [
                'glpi_knowbaseitems_items.id',
                'glpi_knowbaseitems_items.knowbaseitems_id',
                'glpi_knowbaseitems_items.itemtype',
                'glpi_knowbaseitems_items.items_id',
                'glpi_knowbaseitems_items.date_creation',
                'glpi_knowbaseitems_items.date_mod'
            ]
        ];

        if ($item::getType() == KnowbaseItem::getType()) {
            $criteria['WHERE'][] = [
                'glpi_knowbaseitems_items.knowbaseitems_id' => $item->getID(),
            ];
        } else {
            $criteria = array_merge_recursive($criteria, self::getVisibilityCriteriaForItem($item));
            $criteria['WHERE'][] = [
                'glpi_knowbaseitems_items.items_id' => $item->getID(),
                'glpi_knowbaseitems_items.itemtype' => $item->getType()
            ];
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

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public static function getMassiveActionsForItemtype(
        array &$actions,
        $itemtype,
        $is_deleted = 0,
        CommonDBTM $checkitem = null
    ) {

        $kb_item = new KnowbaseItem();
        $kb_item->getEmpty();
        if ($kb_item->canViewItem()) {
            $action_prefix = __CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR;

            $actions[$action_prefix . 'add']
            = "<i class='fa-fw " . self::getIcon() . "'></i>" .
              _x('button', 'Link knowledgebase article');
        }

        parent::getMassiveActionsForItemtype($actions, $itemtype, $is_deleted, $checkitem);
    }

    public static function getIcon()
    {
        return KnowbaseItem::getIcon();
    }

    private static function getCountForItem(CommonDBTM $item): int
    {
        if ($item->getType() == KnowbaseItem::getType()) {
            $criteria['WHERE'] = [
                'glpi_knowbaseitems_items.knowbaseitems_id' => $item->getID(),
            ];
        } else {
            $criteria = self::getVisibilityCriteriaForItem($item);
            $criteria['WHERE'][] = [
                'glpi_knowbaseitems_items.itemtype' => $item::getType(),
                'glpi_knowbaseitems_items.items_id' => $item->getId(),
            ];
        }

        return countElementsInTable('glpi_knowbaseitems_items', $criteria);
    }

    /**
     * Return visibility criteria that must be used to find KB items related to given item.
     */
    private static function getVisibilityCriteriaForItem(CommonDBTM $item): array
    {
        $criteria = array_merge_recursive(
            [
                'INNER JOIN' => [
                    'glpi_knowbaseitems' => [
                        'ON' => [
                            'glpi_knowbaseitems_items' => 'knowbaseitems_id',
                            'glpi_knowbaseitems'       => 'id'
                        ]
                    ]
                ]
            ],
            KnowbaseItem::getVisibilityCriteria()
        );

        $entity_criteria = getEntitiesRestrictCriteria($item->getTable(), '', '', $item->maybeRecursive());
        if (!empty($entity_criteria)) {
            $criteria['INNER JOIN'][$item->getTable()] = [
                'ON' => [
                    'glpi_knowbaseitems_items' => 'items_id',
                    $item->getTable()          => 'id'
                ]
            ];
            $criteria['WHERE'][] = $entity_criteria;
        }

        return $criteria;
    }
}
