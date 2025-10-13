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

use Glpi\Application\View\TemplateRenderer;

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
        if (static::canView() && $item instanceof CommonDBTM) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = self::getCountForItem($item);
            }

            if ($item::class === KnowbaseItem::class) {
                $type_name = _n('Associated element', 'Associated elements', $nb);
            } else {
                $type_name = __('Knowledge base');
            }

            return self::createTabEntry($type_name, $nb, $item::class);
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }
        self::showForItem($item, $withtemplate);
        return true;
    }

    /**
     * Show linked items of a knowbase item
     *
     * @param CommonDBTM $item
     * @param integer $withtemplate withtemplate param (default 0)
     *
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        $item_id = $item->getID();

        if (isset($_GET["start"])) {
            $start = (int) $_GET["start"];
        } else {
            $start = 0;
        }

        $canedit = $item->can($item_id, UPDATE);

        // Total Number of KB items
        $number = self::getCountForItem($item);

        $ok_state = true;
        if ($item instanceof CommonITILObject) {
            $ok_state = !in_array($item->fields['status'], array_merge(
                $item->getClosedStatusArray(),
                $item->getSolvedStatusArray()
            ), true);
        }

        $rand = mt_rand();
        if ($canedit && $ok_state) {
            if ($item::class !== KnowbaseItem::class) {
                $visibility = KnowbaseItem::getVisibilityCriteria();
                $condition = (isset($visibility['WHERE']) && count($visibility['WHERE'])) ? $visibility['WHERE'] : [];
                $used_knowbase_items = self::getItems($item, 0, 0, true);
            }
            TemplateRenderer::getInstance()->display('pages/tools/kb/knowbaseitem_item.html.twig', [
                'item' => $item,
                'visibility_condition' => $condition ?? [],
                'used_knowbase_items' => $used_knowbase_items ?? [],
            ]);
        }

        $linked_items = self::getItems($item, $start, $_SESSION['glpilist_limit']);
        $entries = [];
        foreach ($linked_items as $data) {
            $linked_item = null;
            if ($item::class === KnowbaseItem::class) {
                $linked_item = getItemForItemtype($data['itemtype']);
                $linked_item->getFromDB($data['items_id']);
            } else {
                $linked_item = getItemForItemtype(KnowbaseItem::getType());
                $linked_item->getFromDB($data['knowbaseitems_id']);
            }
            $type = $linked_item::getTypeName(1);
            if (isset($linked_item->fields['is_template']) && $linked_item->fields['is_template'] == 1) {
                $type .= ' (' . __('template') . ')';
            }

            $entries[] = [
                'itemtype'      => self::class,
                'id'            => $data['id'],
                'type'          => $type,
                'item'          => $linked_item->getLink(),
                'date_creation' => $linked_item->fields['date_creation'],
                'date_mod'      => $linked_item->fields['date_mod'],
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'start' => $start,
            'limit' => $_SESSION['glpilist_limit'],
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'type' => _n('Type', 'Types', 1),
                'item' => _n('Item', 'Items', 1),
                'date_creation' => __('Creation date'),
                'date_mod' => __('Update date'),
            ],
            'formatters' => [
                'item' => 'raw_html',
                'date_creation' => 'datetime',
                'date_mod' => 'datetime',
            ],
            'entries' => $entries,
            'total_number' => $number,
            'filtered_number' => $number,
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
            ],
        ]);
    }

    /**
     * Displays linked dropdowns to add linked items
     *
     * @param CommonDBTM $item Item instance
     * @param string     $name Field name
     *
     * @return string
     * @used-by 'templates/tools/kb/knowbaseitem_item.html.twig'
     */
    public static function dropdownAllTypes(CommonDBTM $item, $name)
    {
        global $CFG_GLPI;

        $onlyglobal = 0;
        $entity_restrict = -1;
        $checkright = true;

        return Dropdown::showSelectItemFromItemtypes([
            'items_id_name'   => $name,
            'entity_restrict' => $entity_restrict,
            'itemtypes'       => $CFG_GLPI['kb_types'],
            'onlyglobal'      => $onlyglobal,
            'checkright'      => $checkright,
        ]);
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
                'glpi_knowbaseitems_items.date_mod',
            ],
        ];

        if ($item::class === KnowbaseItem::class) {
            $criteria['WHERE'][] = [
                'glpi_knowbaseitems_items.knowbaseitems_id' => $item->getID(),
            ];
        } else {
            $criteria = array_merge_recursive($criteria, self::getVisibilityCriteriaForItem($item));
            $criteria['WHERE'][] = [
                'glpi_knowbaseitems_items.items_id' => $item->getID(),
                'glpi_knowbaseitems_items.itemtype' => $item::class,
            ];
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
            = "<i class='" . htmlescape(self::getIcon()) . "'></i>"
              . _sx('button', 'Link knowledgebase article');
        }

        parent::getMassiveActionsForItemtype($actions, $itemtype, $is_deleted, $checkitem);
    }

    public static function getIcon()
    {
        return KnowbaseItem::getIcon();
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        switch ($field) {
            case 'items_id':
                if (!empty($values['itemtype'])) {
                    $options['name']  = $name;
                    $options['value'] = $values[$field];
                    return Dropdown::show($values['itemtype'], $options);
                }
                break;
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'items_id':
                if (isset($values['itemtype']) && is_a($values['itemtype'], CommonDBTM::class, true)) {
                    if ($values[$field] > 0) {
                        $item = new $values['itemtype']();
                        $item->getFromDB($values[$field]);
                        return "<a href='" . htmlescape($item->getLinkURL()) . "'>" . htmlescape($item->fields['name']) . "</a>";
                    }
                }
                return ' ';
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    private static function getCountForItem(CommonDBTM $item): int
    {
        if ($item::class === KnowbaseItem::class) {
            $criteria['WHERE'] = [
                'glpi_knowbaseitems_items.knowbaseitems_id' => $item->getID(),
            ];
        } else {
            $criteria = self::getVisibilityCriteriaForItem($item);
            $criteria['WHERE'][] = [
                'glpi_knowbaseitems_items.itemtype' => $item::class,
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
                            'glpi_knowbaseitems'       => 'id',
                        ],
                    ],
                ],
            ],
            KnowbaseItem::getVisibilityCriteria()
        );

        $item_table = $item::getTable();
        $entity_criteria = getEntitiesRestrictCriteria($item_table, '', '', $item->maybeRecursive());
        if (!empty($entity_criteria)) {
            $criteria['INNER JOIN'][$item_table] = [
                'ON' => [
                    'glpi_knowbaseitems_items' => 'items_id',
                    $item_table                => 'id',
                ],
            ];
            $criteria['WHERE'][] = $entity_criteria;
        }

        return $criteria;
    }
}
