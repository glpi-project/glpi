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

namespace Glpi\Features;

use CommonDBTM;
use CommonITILObject;
use CommonTreeDropdown;
use DB;
use DropdownTranslation;
use Html;
use ITILCategory;
use QuerySubQuery;
use QueryExpression;
use Search;

/**
 * TreeBrowse
 *
 * @since 10.0.0
 */
trait TreeBrowse
{
    /**
     * Show the browse view
     */
    public static function showBrowseView(string $itemtype, array $params, $update = false)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $ajax_url    = $CFG_GLPI["root_doc"] . "/ajax/treebrowse.php";
        $loading_txt = __s('Loading...');
        $start       = (int)($_REQUEST['start'] ?? 0);
        $browse      = (int)($_REQUEST['browse'] ?? 0);
        $is_deleted  = (int)($_REQUEST['is_deleted'] ?? 0);
        $criteria    = json_encode($params['criteria']);
        $sort        = json_encode($_REQUEST['sort'] ?? []);
        $order       = json_encode($_REQUEST['order'] ?? []);

        $category_list = json_encode(self::getTreeCategoryList($itemtype, $params));
        $no_cat_found  = __s("No category found");

        $JS = <<<JAVASCRIPT
        var loadingindicator  = $("<div class='loadingindicator'>$loading_txt</div>");
        $('#items_list').html(loadingindicator);
        window.loadNode = function(cat_id) {
            $('#items_list').html(loadingindicator);
            $('#items_list').load('$ajax_url', {
                'action': 'getItemslist',
                'cat_id': cat_id,
                'itemtype': '$itemtype',
                'start': $start,
                'browse': $browse,
                'is_deleted': $is_deleted,
                'criteria': $criteria,
                'sort': $sort,
                'order': $order,
            });
        };
JAVASCRIPT;

        if ($update) {
            $JS .= <<<JAVASCRIPT
            $('#tree_category').fancytree('option', 'source', {$category_list});
JAVASCRIPT;

            $params['criteria'][] = $_SESSION['treebrowse'][$itemtype];
            $results = Search::getDatas($itemtype, $params);
            $results['searchform_id'] = $params['searchform_id'] ?? null;
            Search::displayData($results);
        } else {
            $JS .= <<<JAVASCRIPT
            $(function() {
                $('#tree_category').fancytree({
                    // load plugins
                    extensions: ['filter', 'glyph', 'persist'],

                    // Scroll node into visible area, when focused by keyboard
                    autoScroll: true,

                    // enable font-awesome icons
                    glyph: {
                        preset: "awesome5",
                        map: {}
                    },

                    persist: {
                        cookiePrefix: '$itemtype',
                        expandLazy: true,
                        overrideSource: true,
                        store: "auto"
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

                        window.loadNode(key);
                    },

                });

                var tree = $.ui.fancytree.getTree("#tree_category")
                if (tree.activeNode === null) {
                    tree.activateKey(-1);
                }
                $(document).on('keyup', '#browser_tree_search', function() {
                    var search_text = $(this).val();
                    $.ui.fancytree.getTree("#tree_category").filterNodes(search_text);
                });
            });

JAVASCRIPT;
            echo "<div id='tree_browse'>
            <div class='browser_tree d-flex flex-column'>
                <input type='text' class='browser_tree_search' placeholder='" . __s("Search…") . "' id='browser_tree_search'>
                <div id='tree_category' class='browser-tree-container'></div>
            </div>
            <div id='items_list' class='browser_items'></div>
            </div>";
        }
        echo Html::scriptBlock($JS);
    }

    /**
     * Get list of document categories in fancytree format.
     *
     * @param class-string<CommonDBTM> $itemtype
     * @param array $params
     *
     * @return array
     */
    public static function getTreeCategoryList(string $itemtype, array $params): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        /** @var class-string<CommonDBTM> $cat_itemtype */
        $cat_itemtype = static::getCategoryItemType($itemtype);
        $cat_item     = new $cat_itemtype();

        $params['export_all'] = true;
        $data = Search::prepareDatasForSearch($itemtype, $params);
        Search::constructSQL($data);
        // This query is used to get the IDs of all results matching the search criteria
        $sql = $data['sql']['search'];
        // We can remove all the SELECT fields and replace it with just the ID field
        $raw_select = $data['sql']['raw']['SELECT'];
        $replacement_select = 'SELECT DISTINCT ' . $itemtype::getTableField('id');
        $sql = preg_replace('/^' . preg_quote($raw_select, '/') . '/', $replacement_select, $sql, 1);
        // Remove GROUP BY and ORDER BY clauses
        $sql = str_replace([$data['sql']['raw']['GROUPBY'], $data['sql']['raw']['ORDER']], '', $sql);

        $id_criteria = new QueryExpression($itemtype::getTableField('id') . ' IN ( SELECT * FROM (' . $sql . ') AS id_criteria )');

        $cat_table = $cat_itemtype::getTable();
        $cat_fk    = $cat_itemtype::getForeignKeyField();

        $items_subquery = new QuerySubQuery(
            [
                'SELECT' => ['COUNT DISTINCT' => $itemtype::getTableField('id') . ' AS cpt'],
                'FROM'   => $itemtype::getTable(),
                'WHERE'  => [
                    $itemtype::getTableField($cat_fk) => new QueryExpression($DB::quoteName($cat_itemtype::getTableField('id'))),
                    $id_criteria
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

        // Without category
        $no_cat_count = $DB->request(
            [
                'SELECT' => ['COUNT DISTINCT' => $itemtype::getTableField('id') . ' as cpt'],
                'FROM'   => $itemtype::getTable(),
                'WHERE'  => [
                    $itemtype::getTableField($cat_fk) => 0,
                    $id_criteria,
                ]
            ]
        )->current();
        $categories[] = [
            'id'          => -1,
            'name'        => __s('Without Category'),
            'items_count' => $no_cat_count['cpt'],
            $cat_fk       => 0,
        ];

        // construct flat data
        $nodes   = [];
        foreach ($categories as $category) {
            $cat_id = $category['id'];
            $node = [
                'key'    => $cat_id,
                'title'  => $category['name'],
                'parent' => $category[$cat_fk] ?? 0,
                'a_attr' => [
                    'data-id' => $cat_id
                ],
            ];

            if ($category['items_count'] > 0) {
                $node['title'] .= ' <span class="badge bg-azure-lt" title="' . __s('This category contains ') . $itemtype::getTypeName() . '">'
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

    /**
     * Return category itemtype for given itemtype.
     *
     * @param string $itemtype
     *
     * @return string|null
     */
    public static function getCategoryItemType(string $itemtype): ?string
    {
        return is_a($itemtype, CommonITILObject::class, true)
            ? ITILCategory::class
            : $itemtype . 'Category';
    }
}
