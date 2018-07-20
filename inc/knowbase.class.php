<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Knowbase Class
 *
 * @since 0.84
**/
class Knowbase extends CommonGLPI {


   static function getTypeName($nb = 0) {

      // No plural
      return __('Knowledge base');
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addStandardTab(__CLASS__, $ong, $options);

      $ong['no_all_tab'] = true;
      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

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


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1 : // all
               $item->showSearchView();
               break;

            case 2 :
               $item->showBrowseView();
               break;

            case 3 :
               $item->showManageView();
               break;
         }
      }
      return true;
   }


   /**
    * Show the knowbase search view
   **/
   static function showSearchView() {

      // Search a solution
      if (!isset($_GET["contains"])
          && isset($_GET["itemtype"])
          && isset($_GET["items_id"])) {

         if ($item = getItemForItemtype($_GET["itemtype"])) {
            if ($item->getFromDB($_GET["items_id"])) {
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
         echo "<div><table class='center-h' width='950px'><tr class='noHover'><td class='center top'>";
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
   static function showBrowseView() {
      global $CFG_GLPI;

      $rand        = mt_rand();
      $ajax_url    = $CFG_GLPI["root_doc"]."/ajax/knowbase.php";
      $loading_txt = addslashes(__('Loading...'));
      $start       = isset($_REQUEST['start'])
                        ? $_REQUEST['start']
                        : 0;

      $JS = <<<JAVASCRIPT
         $(function() {
            $('#tree_category$rand').jstree({
               'plugins' : [
                  'search',
                  'wholerow',
                  'state' // remember (on browser navigation) the last node open in tree
               ],
               "state" : {
                  "key" : "kb_tree_state"
               },
               'search': {
                  'case_insensitive': true,
                  'show_only_matches': true,
                  'ajax': {
                     'type': 'POST',
                     'url': '$ajax_url?action=searchNode'
                  }
               },
               'core': {
                  'themes': {
                     'name': 'glpi'
                  },
                  'animation': 0,
                  'data': {
                     'url': function(node) {
                        return '$ajax_url?action=getCategoryNode&id='+ (node.id === '#'
                                 ? -1
                                 : node.id);
                     }
                  }
               }
            })
            .on('ready.jstree', function(event, instance) {
               // if no state stored, select root node
               if (instance.instance.restore_state() === false) {
                  $('#tree_category$rand').jstree('select_node', 0);
               }
            })
            .on('select_node.jstree', function(event, data) {
               loadNode(data.selected[0]);
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

            $(document).on('keyup', '#kb_tree_search$rand', function() {
               var inputsearch = $(this);
               typewatch(function () {
                  if (inputsearch.val().length >= 3) {
                     $('#tree_category$rand').jstree('search', inputsearch.val());
                  }
               }, 300);
            });
         });
JAVASCRIPT;
      echo Html::scriptBlock($JS);
      echo "<div id='kb_browse'>
         <div class='kb_tree'>
            <input type='text' class='kb_tree_search' id='kb_tree_search$rand'>
            <div id='tree_category$rand'></div>
         </div>
         <div id='items_list$rand' class='kb_items'></div>
      </div>";
   }

   /**
    * Get a node for knwobase category tree by its id
    * Also count children nodes
    *
    * @since 9.4
    *
    * @param integer $id
    * @return array the node
    */
   static function getJstreeCategoryNode($id = 0) {
      global $DB;

      $table_c = KnowbaseItemCategory::getTable();
      $cat_fk  = getForeignKeyFieldForItemType('KnowbaseItemCategory');
      $nodes   = [];

      if ($id === -1) {
         $id = 0;
         $node = [
            'id'    => "$id",
            'text'  => __("Root category"),
            'state' => [
               'opened' => true,
            ],
            'a_attr' => [
               'data-id' => $id
            ],
         ];

         // count child
         $iterator = $DB->request([
            'FROM'   => $table_c,
            'COUNT'  => 'cpt',
            'WHERE'  => [
               $cat_fk => $id
            ]
         ]);
         $result = $iterator->next();
         if ($result['cpt'] > 0) {
            $node['children'] = true;
         }

         // count items
         $node['text'].= self::getCountStringForNode($id);

         $nodes = [$node];
      } else {
         $iterator = $DB->request([
            'SELECT' => [
               "$table_c.id",
               "$table_c.name",
               'COUNT DISTINCT' => "sub.$cat_fk AS nb_sub_cat",
            ],
            'FROM' => $table_c,
            'LEFT JOIN' => [
               "$table_c as sub" => [
                  'FKEY' => [
                     $table_c => 'id',
                     "sub"    => $cat_fk,
                  ]
               ],
            ],
            'WHERE' => [
               "$table_c.$cat_fk" => $id
            ],
            'GROUPBY' => [
               "$table_c.id",
               "$table_c.name",
            ],
            'ORDER' => "$table_c.name"
         ]);

         foreach ($iterator as $category) {
            $node = [
               'id'     => $category['id'],
               'text'   => $category['name'].self::getCountStringForNode($category['id']),
               'a_attr' => [
                  'data-id' => $category['id']
               ],
            ];

            if ($category['nb_sub_cat'] > 0) {
               $node['children'] = true;
            }

            $nodes[] = $node;
         }
      }

      return json_encode($nodes);
   }

   /**
    * Count number of article for a category id
    *
    * @since 9.4
    *
    * @param integer $id
    * @return integer number of article
    */
   static function countSubItemsForNode($id = 0) {
      global $DB;

      $count = [
         'nb_items'     => 0,
         'nb_sub_items' => 0,
         'sons_cat'     => 0,
      ];

      $table_i  = KnowbaseItem::getTable();
      $cat_fk   = getForeignKeyFieldForItemType('KnowbaseItemCategory');
      $sons_cat = getSonsOf(KnowbaseItemCategory::getTable(), $id);
      unset($sons_cat[$id]);
      $count['sons_cat'] = $sons_cat;

      // count direct items
      $iterator = $DB->request(array_merge_recursive([
         'SELECT' => [
            'COUNT DISTINCT' => "$table_i.id AS nb_items",
         ],
         'FROM' => $table_i,
         'WHERE' => [
            "$table_i.$cat_fk" => $id
         ]
      ], KnowbaseItem::getVisibilityCriteria(true)));
      $result = $iterator->next();
      if ($result['nb_items'] > 0) {
         $count['nb_items'] = $result['nb_items'];
      }

      // count items from sub categories
      if (count($sons_cat) > 0) {
         $iterator = $DB->request(array_merge_recursive([
            'SELECT' => [
               'COUNT DISTINCT' => "$table_i.id AS nb_sub_items",
            ],
            'FROM' => $table_i,
            'WHERE' => [
               "$table_i.$cat_fk" => array_values($sons_cat)
            ]
         ], KnowbaseItem::getVisibilityCriteria(true)));
         $result = $iterator->next();
         if ($result['nb_sub_items'] > 0) {
            $count['nb_sub_items'] = $result['nb_sub_items'];
         }
      }

      return $count;
   }

   /**
    * Display string of count number of article for a category id
    *
    * @param integer $id Node id
    *
    * @return string
    */
   static function getCountStringForNode($id = 0) {
      $count = self::countSubItemsForNode($id);

      $count_str = "";
      if ($count['nb_items'] > 0) {
         $count_str = "<strong title='".__("This category contains articles")."'>
                        (".$count['nb_items'].")
                       </strong>";
      }

      return $count_str;
   }

   /**
    * Show the knowbase Manage view
   **/
   static function showManageView() {

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
