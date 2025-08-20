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

namespace Glpi\Features;

use CommonDBTM;
use CommonDropdown;
use CommonITILObject;
use CommonTreeDropdown;
use DropdownTranslation;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QuerySubQuery;
use Html;
use ITILCategory;
use Search;

use function Safe\json_encode;
use function Safe\preg_match;
use function Safe\preg_replace;

/**
 * TreeBrowse
 *
 * @since 10.0.0
 */
trait TreeBrowse
{
    /** @see TreeBrowseInterface::showBrowseView() */
    public static function showBrowseView(string $itemtype, array $params, $update = false)
    {
        global $CFG_GLPI;

        $ajax_url    = \jsescape($CFG_GLPI["root_doc"] . "/ajax/treebrowse.php");

        $start       = isset($params['start'])
                            ? (int) $params['start']
                            : 0;
        $browse      = isset($params['browse'])
                            ? (int) $params['browse']
                            : 0;
        $is_deleted  = isset($params['is_deleted'])
                            ? (int) $params['is_deleted']
                            : 0;
        $unpublished = isset($params['unpublished'])
                            ? (int) $params['unpublished']
                            : 1;

        $js_itemtype = \jsescape($itemtype);
        $criteria    = json_encode($params['criteria']);
        $sort        = json_encode($_REQUEST['sort'] ?? []);
        $order       = json_encode($_REQUEST['order'] ?? []);

        $category_list = json_encode(self::getTreeCategoryList($itemtype, $params));

        $JS = <<<JAVASCRIPT
            $('#items_list').html(`<span class="spinner-border spinner-border position-absolute m-5 start-50" role="status" aria-hidden="true"></span>`);
            window.loadNode = function(cat_id) {
                $('#items_list').html(`<span class="spinner-border spinner-border position-absolute m-5 start-50" role="status" aria-hidden="true"></span>`);
                $('#items_list').load('$ajax_url', {
                    'action': 'getItemslist',
                    'cat_id': cat_id,
                    'itemtype': '$js_itemtype',
                    'start': $start,
                    'browse': $browse,
                    'is_deleted': $is_deleted,
                    'unpublished': $unpublished,
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
            $no_cat_found  = jsescape(__("No category found"));

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
                        cookiePrefix: '$js_itemtype',
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
            echo "<div id='tree_browse' data-testid='tree-browse'>
            <div class='browser_tree d-flex flex-column'>
                <input type='text' class='browser_tree_search' placeholder='" . __s("Search…") . "' id='browser_tree_search'>
                <div id='tree_category' class='browser-tree-container'></div>
            </div>
            <div id='items_list' class='browser_items'></div>
            </div>";
        }
        echo Html::scriptBlock($JS);
    }

    /** @see TreeBrowseInterface::getTreeCategoryList() */
    public static function getTreeCategoryList(string $itemtype, array $params): array
    {
        global $DB;

        $cat_item = static::getCategoryItem($itemtype);

        $params['export_all'] = true;

        $data = Search::prepareDatasForSearch($itemtype, $params);
        Search::constructSQL($data);
        // This query is used to get the IDs of all results matching the search criteria
        $sql = $data['sql']['search'];
        // We can remove all the SELECT fields and replace it with just the ID field
        $raw_select = $data['sql']['raw']['SELECT'];
        $replacement_select = 'SELECT DISTINCT ' . $itemtype::getTableField('id');
        $sql_id = preg_replace('/^' . preg_quote($raw_select, '/') . '/', $replacement_select, $sql, 1);
        // Remove GROUP BY and ORDER BY clauses
        $sql_id = str_replace([$data['sql']['raw']['GROUPBY'], $data['sql']['raw']['ORDER']], '', $sql_id);

        $id_criteria = new QueryExpression($itemtype::getTableField('id') . ' IN ( SELECT * FROM (' . $sql_id . ') AS id_criteria )');

        $cat_table = $cat_item::getTable();
        $cat_fk    = $cat_item::getForeignKeyField();
        $cat_join = $itemtype . '_' . $cat_item::class;

        if (class_exists($cat_join)) {
            $cat_criteria = [new QueryExpression('true')];
            // If there is a category filter, apply this filter to the tree too
            if (preg_match("/$cat_table/", $data['sql']['raw']['WHERE'])) {
                // This query is used to get the IDs of all results matching the search criteria
                // We can remove all the SELECT fields and replace it with just the ID field
                $replacement_select = "SELECT DISTINCT " . $cat_join::getTableField($cat_fk);
                $sql_cat = preg_replace('/^' . preg_quote($raw_select, '/') . '/', $replacement_select, $sql, 1);
                // Remove GROUP BY and ORDER BY clauses
                $sql_cat = str_replace([$data['sql']['raw']['GROUPBY'], $data['sql']['raw']['ORDER']], '', $sql_cat);

                $cat_criteria = new QueryExpression($cat_join::getTableField($cat_fk) . ' IN ( SELECT * FROM (' . $sql_cat . ') AS cat_criteria )');
            }

            $join = [
                $cat_join::getTable() => [
                    'ON'  => [
                        $cat_join::getTable() => $itemtype::getForeignKeyField(),
                        $itemtype::getTable() => 'id',
                    ],
                    $cat_criteria,
                ],
            ];
        } else {
            $join = [];
            $cat_join = $itemtype;
        }

        $items_subquery = new QuerySubQuery(
            [
                'SELECT' => ['COUNT DISTINCT' => $itemtype::getTableField('id') . ' AS cpt'],
                'FROM'   => $itemtype::getTable(),
                'LEFT JOIN' => $join,
                'WHERE'  => [
                    $cat_join::getTableField($cat_fk) => new QueryExpression(
                        $DB->quoteName($cat_item::getTableField('id'))
                    ),
                    $id_criteria,
                ],
            ],
            'items_count'
        );

        $select[] = $cat_item::getTableField('id');
        $select[] = $cat_item::getTableField('name');
        if ($cat_item instanceof CommonTreeDropdown) {
            $select[] = $cat_item::getTableField($cat_fk);
        }
        $select[] = $items_subquery;

        if ($cat_item instanceof CommonTreeDropdown) {
            $order[] = $cat_item::getTableField('level') . ' DESC';
            $order[] = $cat_item::getTableField('name');
        } else {
            $order[] = $cat_item::getTableField('name') . ' DESC';
        }

        $cat_iterator = $DB->request([
            'SELECT' => $select,
            'FROM' => $cat_table,
            'ORDER' => $order,
        ]);

        $categories = [];
        $parents = [];
        foreach ($cat_iterator as $category) {
            if ($category instanceof CommonDropdown && $category->maybeTranslated()) {
                $tname = DropdownTranslation::getTranslatedValue(
                    $category['id'],
                    $cat_item::class
                );
                if (!empty($tname)) {
                    $category['name'] = $tname;
                }
            }
            if (($category['items_count'] > 0) || (in_array($category['id'], $parents))) {
                $parents[] = $category[$cat_fk];
                $categories[] = $category;
            }
        }

        // Without category
        $join[$cat_table] = [
            'ON' => [
                $cat_join::getTable() => $cat_item::getForeignKeyField(),
                $cat_table => 'id',
            ],
        ];
        $no_cat_count = $DB->request(
            [
                'SELECT' => ['COUNT DISTINCT' => $itemtype::getTableField('id') . ' as cpt'],
                'FROM'   => $itemtype::getTable(),
                'LEFT JOIN' => $join,
                'WHERE'  => [
                    $cat_item::getTableField('id') => null,
                    $id_criteria,
                ],
            ]
        )->current();
        $categories[] = [
            'id'          => -1,
            'name'        => __('Without Category'),
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
                    'data-id' => $cat_id,
                ],
            ];

            if ($category['items_count'] > 0) {
                $node['title'] .= ' <span class="badge bg-azure-lt" title="' . \htmlescape(\sprintf(__('This category contains %s'), $itemtype::getTypeName())) . '">'
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

    /** @see TreeBrowseInterface::getCategoryItem() */
    public static function getCategoryItem(string $itemtype): ?CommonDBTM
    {
        if (\is_a($itemtype, CommonITILObject::class, true)) {
            return new ITILCategory();
        }

        $expected_class = $itemtype . 'Category';
        if (is_a($expected_class, CommonDBTM::class, true)) {
            return new $expected_class();
        }

        return null;
    }
}
