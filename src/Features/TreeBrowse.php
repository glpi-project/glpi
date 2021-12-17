<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Features;

use CommonITILObject;
use CommonTreeDropdown;
use DB;
use DropdownTranslation;
use Html;
use QuerySubQuery;
use QueryExpression;
use Search;

/**
 * TreeBrowse
 **/
trait TreeBrowse
{
   /**
    * Show the document browse view
   **/
    public static function showBrowseView($itemtype, $params)
    {
        global $CFG_GLPI;

        $cat_field   = strtolower($itemtype) . "categories_id";
        $item = new $itemtype();
        if ($item instanceof CommonITILObject) {
            $cat_field   = "itilcategories_id";
        }

        $rand        = mt_rand();
        $ajax_url    = $CFG_GLPI["root_doc"] . "/ajax/treebrowse.php";
        $loading_txt = addslashes(__('Loading...'));
        $start       = isset($_REQUEST['start'])
                            ? $_REQUEST['start']
                            : 0;
        $browse      = isset($_REQUEST['browse'])
                            ? $_REQUEST['browse']
                            : 0;
        $is_deleted  = isset($_REQUEST['is_deleted'])
                            ? $_REQUEST['is_deleted']
                            : 0;
        $criteria    = json_encode($params['criteria']);

        $category_list = json_encode(self::getTreeCategoryList($itemtype, $cat_field, $params));
        $no_cat_found  = __("No category found");

        $JS = <<<JAVASCRIPT
        $(function() {
            $('#tree_category$rand').fancytree({
                // load plugins
                extensions: ['filter', 'glyph'],

                // Scroll node into visible area, when focused by keyboard
                autoScroll: true,

                // enable font-awesome icons
                glyph: {
                    preset: "awesome5",
                    map: {}
                },

                // load json data
                source: {$category_list},

                // filter plugin options
                filter: {
                    mode: "hide", // remove unmatched nodes
                    autoExpand: true, // if results found in children, auto-expand parent
                    nodata: '{$no_cat_found}', // message when no data found
                },

                // events
                activate: function(event, data) {
                    var node = data.node;
                    var key  = node.key;

                    loadNode(key);
                },

            });

            var loadingindicator  = $("<div class='loadingindicator'>$loading_txt</div>");
            $('#items_list$rand').html(loadingindicator); // loadingindicator on doc ready
            var loadNode = function(cat_id) {
                $('#items_list$rand').html(loadingindicator);
                $('#items_list$rand').load('$ajax_url', {
                    'action': 'getItemslist',
                    'cat_id': cat_id,
                    'cat_field': '$cat_field',
                    'itemtype': '$itemtype',
                    'start': $start,
                    'browse': $browse,
                    'is_deleted': $is_deleted,
                    'criteria': $criteria
                });
            };
            loadNode(0);

            $(document).on('keyup', '#kb_tree_search$rand', function() {
                var search_text = $(this).val();
                $.ui.fancytree.getTree("#tree_category$rand").filterNodes(search_text);
            });
        });

        JAVASCRIPT;
        echo Html::scriptBlock($JS);
        echo "<div id='kb_browse'>
        <div class='kb_tree d-flex flex-column'>
            <input type='text' class='kb_tree_search' placeholder='" . __("Search…") . "' id='kb_tree_search$rand'>
            <div id='tree_category$rand' class='kb-tree-container'></div>
        </div>
        <div id='items_list$rand' class='kb_items'></div>
        </div>";
    }

     /**
    * Get list of document categories in fancytree format.
    *
    * @since 10
    *
    * @return array
    */
    public static function getTreeCategoryList($itemtype, $cat_field, $params)
    {

        global $DB;

        $cat_itemtype = getItemtypeForForeignKeyField($cat_field);
        $cat_item     = new $cat_itemtype();
        $data = Search::getDatas($itemtype, $params);

        if ($data['data']['count'] > 0) {
            $ids = array_keys($data['data']['items']);
        } else {
            $ids = 0;
        }

        $cat_table = $cat_itemtype::getTable();
        $cat_fk    = $cat_itemtype::getForeignKeyField();

        $items_subquery = new QuerySubQuery(
            [
                'SELECT' => ['COUNT DISTINCT' => $itemtype::getTableField('id') . ' as cpt'],
                'FROM'   => $itemtype::getTable(),
                'WHERE'  => [
                    $itemtype::getTableField($cat_fk) => new QueryExpression(
                        DB::quoteName($cat_itemtype::getTableField('id'))
                    ),
                    $itemtype::getTableField('id') => $ids,
                ]
            ],
            'items_count'
        );

        $select[] = $cat_itemtype::getTableField('id');
        $select[] = $cat_itemtype::getTableField('name');
        if ($cat_item instanceof CommonTreeDropdown) {
            $select[] = $cat_itemtype::getTableField($cat_fk);
        }
        $select[] = $items_subquery;

        if ($cat_item instanceof CommonTreeDropdown) {
            $order[] = $cat_itemtype::getTableField('level') . ' DESC';
            $order[] = $cat_itemtype::getTableField('name');
        } else {
            $order[] = $cat_itemtype::getTableField('name') . ' DESC';
        }

        $cat_iterator = $DB->request([
            'SELECT' => $select,
            'FROM' => $cat_table,
            'ORDER' => $order
        ]);

        $inst = new $cat_itemtype();
        $categories = [];
        foreach ($cat_iterator as $category) {
            if (DropdownTranslation::canBeTranslated($inst)) {
                $tname = DropdownTranslation::getTranslatedValue(
                    $category['id'],
                    $inst->getType()
                );
                if (!empty($tname)) {
                    $category['name'] = $tname;
                }
            }
            $categories[] = $category;
        }

        // Remove categories that have no items and no children
        // Requires category list to be sorted by level DESC
        foreach ($categories as $index => $category) {
            $children = array_filter(
                $categories,
                function ($element) use ($category, $cat_fk, $cat_item) {
                    if ($cat_item instanceof CommonTreeDropdown) {
                        return $category['id'] == $element[$cat_fk];
                    }
                }
            );

            if (empty($children) && 0 == $category['items_count']) {
                unset($categories[$index]);
            }
        }

        // Without category
        $no_cat_count = $DB->request(
            [
                'SELECT' => ['COUNT DISTINCT' => $itemtype::getTableField('id') . ' as cpt'],
                'FROM'   => $itemtype::getTable(),
                'WHERE'  => [
                    $itemtype::getTableField($cat_fk) => 0,
                    $itemtype::getTableField('id') => $ids,
                ]
            ]
        )->current();
        if ($no_cat_count['cpt'] > 0) {
            $categories[] = [
                'id'          => '-1',
                'name'        => __('Without Category'),
                'items_count' => $no_cat_count['cpt'],
                $cat_fk       => 0,
            ];
        }

        // construct flat data
        $nodes   = [];
        foreach ($categories as $category) {
            $cat_id = intval($category['id']);
            $node = [
                'key'    => $cat_id,
                'title'  => $category['name'],
                'parent' => $category[$cat_fk] ?? 0,
                'a_attr' => [
                    'data-id' => $cat_id
                ],
            ];

            if ($category['items_count'] > 0) {
                $node['title'] .= ' <span class="badge bg-azure-lt" title="' . __('This category contains ') . $itemtype::getTypeName() . '">'
                . $category['items_count']
                . '</span>';
            }

            $nodes[] = $node;
        }

       // recursive construct tree data
        $buildtree = function (array &$elements, $parent = 0) use (&$buildtree) {
            $branch = [];

            foreach ($elements as $index => $element) {
                if ($element['parent'] === $parent) {
                    $children = $buildtree($elements, $element['key']);
                    if (count($children) > 0) {
                         $element['children'] = $children;
                    }
                    $branch[] = $element;
                    unset($elements[$index]);
                }
            }
            return $branch;
        };

        $newtree = $buildtree($nodes);

        return $newtree;
    }
}
