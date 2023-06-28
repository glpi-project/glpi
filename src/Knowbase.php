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
 * Knowbase Class
 *
 * @since 0.84
 **/
class Knowbase extends CommonGLPI
{
    public static function getTypeName($nb = 0)
    {

       // No plural
        return __('Knowledge base');
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addStandardTab(__CLASS__, $ong, $options);

        $ong['no_all_tab'] = true;
        return $ong;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if ($item->getType() == __CLASS__) {
            $tabs[1] = _x('button', 'Search');
            $tabs[2] = _x('button', 'Browse');
            if (KnowbaseItem::canUpdate()) {
                $tabs[3] = _x('button', 'Manage');
            }

            return $tabs;
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == __CLASS__) {
            switch ($tabnum) {
                case 1: // all
                    $item->showSearchView();
                    break;

                case 2:
                    $item->showBrowseView();
                    break;

                case 3:
                    $item->showManageView();
                    break;
            }
        }
        return true;
    }


    /**
     * Show the knowbase search view
     **/
    public static function showSearchView()
    {

        global $CFG_GLPI;

       // Search a solution
        if (
            !isset($_GET["contains"])
            && isset($_GET["itemtype"])
            && isset($_GET["items_id"])
        ) {
            if (in_array($_GET["item_itemtype"], $CFG_GLPI['kb_types']) && $item = getItemForItemtype($_GET["itemtype"])) {
                if ($item->can($_GET["item_items_id"], READ)) {
                    $_GET["contains"] = addslashes($item->getField('name'));
                }
            }
        }

        if (isset($_GET["contains"])) {
            $_SESSION['kbcontains'] = $_GET["contains"];
        } else if (isset($_SESSION['kbcontains'])) {
            $_GET['contains'] = $_SESSION["kbcontains"];
        }
        $ki = new KnowbaseItem();
        $ki->searchForm($_GET);

        if (!isset($_GET['contains']) || empty($_GET['contains'])) {
            echo "<div><table class='mx-auto' width='950px'><tr class='noHover'><td class='center top'>";
            KnowbaseItem::showRecentPopular("recent");
            echo "</td><td class='center top'>";
            KnowbaseItem::showRecentPopular("lastupdate");
            echo "</td><td class='center top'>";
            KnowbaseItem::showRecentPopular("popular");
            echo "</td></tr>";
            echo "</table></div>";
        } else {
            KnowbaseItem::showList($_GET, 'search');
        }
    }


    /**
     * Show the knowbase browse view
     **/
    public static function showBrowseView()
    {
        global $CFG_GLPI;

        $rand        = mt_rand();
        $ajax_url    = $CFG_GLPI["root_doc"] . "/ajax/knowbase.php";
        $loading_txt = __s('Loading...');
        $start       = (int)($_REQUEST['start'] ?? 0);
        $cat_id      = (int)($_SESSION['kb_cat_id'] ?? 0);

        $category_list = json_encode(self::getTreeCategoryList());
        $no_cat_found  = __s("No category found");

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
                  'start': $start
               });
            };
            loadNode($cat_id);
            $.ui.fancytree.getTree("#tree_category$rand").activateKey($cat_id);

            $(document).on('keyup', '#browser_tree_search$rand', function() {
               var search_text = $(this).val();
               $.ui.fancytree.getTree("#tree_category$rand").filterNodes(search_text);
            });
         });
JAVASCRIPT;
        echo Html::scriptBlock($JS);
        echo "<div id='tree_browse'>
         <div class='browser_tree'>
            <input type='text' class='browser_tree_search' id='browser_tree_search$rand'>
            <div id='tree_category$rand'></div>
         </div>
         <div id='items_list$rand' class='browser_items'></div>
      </div>";
    }

    /**
     * Get list of knowbase categories in fancytree format.
     *
     * @since 9.4
     *
     * @return array
     */
    public static function getTreeCategoryList()
    {

        global $DB;

        $cat_table = KnowbaseItemCategory::getTable();
        $cat_fk    = KnowbaseItemCategory::getForeignKeyField();

        $kbitem_visibility_crit = KnowbaseItem::getVisibilityCriteria(true);

        $items_subquery = new QuerySubQuery(
            array_merge_recursive(
                [
                    'SELECT' => ['COUNT DISTINCT' => KnowbaseItem::getTableField('id') . ' as cpt'],
                    'FROM'   => KnowbaseItem::getTable(),
                    'INNER JOIN' => [
                        KnowbaseItem_KnowbaseItemCategory::getTable() => [
                            'ON'  => [
                                KnowbaseItem_KnowbaseItemCategory::getTable()   => KnowbaseItem::getForeignKeyField(),
                                KnowbaseItem::getTable()            => 'id'
                            ]
                        ]
                    ],
                    'WHERE'  => [
                        KnowbaseItem_KnowbaseItemCategory::getTableField($cat_fk) => new QueryExpression(
                            $DB->quoteName(KnowbaseItemCategory::getTableField('id'))
                        ),
                    ]
                ],
                $kbitem_visibility_crit
            ),
            'items_count'
        );

        $cat_iterator = $DB->request([
            'SELECT' => [
                KnowbaseItemCategory::getTableField('id'),
                KnowbaseItemCategory::getTableField('name'),
                KnowbaseItemCategory::getTableField($cat_fk),
                $items_subquery,
            ],
            'FROM' => $cat_table,
            'ORDER' => [
                KnowbaseItemCategory::getTableField('level') . ' DESC',
                KnowbaseItemCategory::getTableField('name'),
            ]
        ]);

        $inst = new KnowbaseItemCategory();
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
                function ($element) use ($category, $cat_fk) {
                    return $category['id'] == $element[$cat_fk];
                }
            );

            if (empty($children) && 0 == $category['items_count']) {
                unset($categories[$index]);
            }
        }

       // Add root category (which is not a real category)
        $root_items_count = $DB->request(
            array_merge_recursive(
                [
                    'SELECT' => ['COUNT DISTINCT' => KnowbaseItem::getTableField('id') . ' as cpt'],
                    'FROM'   => KnowbaseItem::getTable(),
                    'LEFT JOIN' => [
                        KnowbaseItem_KnowbaseItemCategory::getTable() => [
                            'ON'  => [
                                KnowbaseItem_KnowbaseItemCategory::getTable()   => KnowbaseItem::getForeignKeyField(),
                                KnowbaseItem::getTable()            => 'id'
                            ]
                        ],
                        KnowbaseItemCategory::getTable() => [
                            'ON'  => [
                                KnowbaseItem_KnowbaseItemCategory::getTable()   => KnowbaseItemCategory::getForeignKeyField(),
                                KnowbaseItemCategory::getTable()    => 'id'
                            ]
                        ]
                    ],
                    'WHERE'  => [
                        KnowbaseItemCategory::getTableField('id') => null,
                    ]
                ],
                $kbitem_visibility_crit
            )
        )->current();
        $categories[] = [
            'id'          => 0,
            'name'        => __s('Root category'),
            'items_count' => $root_items_count['cpt'],
        ];

       // construct flat data
        $nodes   = [];
        foreach ($categories as $category) {
            $cat_id = intval($category['id']);
            $node = [
                'key'    => $cat_id,
                'title'  => $category['name'],
                'parent' => $category[$cat_fk] ?? null,
                'a_attr' => [
                    'data-id' => $cat_id
                ],
            ];

            if ($category['items_count'] > 0) {
                $node['title'] .= ' <span class="badge bg-azure-lt" title="' . __s('This category contains articles') . '">'
                . $category['items_count']
                . '</span>';
            }

            $nodes[] = $node;
        }

       // recursive construct tree data
        $buildtree = function (array &$elements, $parent = null) use (&$buildtree) {
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
     * Show the knowbase Manage view
     **/
    public static function showManageView()
    {

        if (isset($_GET["unpublished"])) {
            $_SESSION['kbunpublished'] = $_GET["unpublished"];
        } else if (isset($_SESSION['kbunpublished'])) {
            $_GET["unpublished"] = $_SESSION['kbunpublished'];
        }
        if (!isset($_GET["unpublished"])) {
            $_GET["unpublished"] = 'myunpublished';
        }
        $ki = new KnowbaseItem();
        $ki->showManageForm($_GET);
        KnowbaseItem::showList($_GET, $_GET["unpublished"]);
    }
}
