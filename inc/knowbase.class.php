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

      $cat_id = 'false';
      if (array_key_exists('knowbaseitemcategories_id', $_REQUEST)) {
         $cat_id = $_REQUEST['knowbaseitemcategories_id'];
      }

      $category_list = json_encode(self::getJstreeCategoryList());

      $JS = <<<JAVASCRIPT
         $(function() {
            $('#tree_category$rand').jstree({
               'plugins' : [
                  'search',
                  'wholerow',
                  'state' // remember (on browser navigation) the last node open in tree
               ],
               'state' : {
                  'key'    : "kb_tree_state",
                  'filter' : function (state) {
                     // Prevent restoring selected state if category is in URL
                     if ($cat_id) {
                        state.core.selected = [];
                     }
                     return state;
                  }
               },
               'search': {
                  'case_insensitive': true,
                  'show_only_matches': true
               },
               'core': {
                  'themes': {
                     'name': 'glpi'
                  },
                  'animation': 0,
                  'data': $category_list
               }
            })
            .on('ready.jstree', function(event, instance) {
               if ($cat_id) {
                  // force category if id found in URL parameters
                  $('#tree_category$rand').jstree('select_node', $cat_id);
                  $('#tree_category$rand').jstree('open_node', $cat_id);
               } else if (instance.instance.restore_state() === false) {
                  // if no state stored, select root node
                  $('#tree_category$rand').jstree('select_node', 0);
                  $('#tree_category$rand').jstree('open_node', 0);
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

            $('#items_list$rand').on('click', 'a.kb-category', function(event) {
               event.preventDefault();

               var cat_id = $(event.target).data('category-id');
               $('#tree_category$rand').jstree('select_node', cat_id);
               $('#tree_category$rand').jstree('open_node', cat_id);
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
    * Get list of knowbase categories in jstree format.
    *
    * @since 9.4
    *
    * @return array
    */
   static function getJstreeCategoryList() {

      global $DB;

      $cat_table = KnowbaseItemCategory::getTable();
      $cat_fk  = KnowbaseItemCategory::getForeignKeyField();

      $kbitem_visibility_crit = KnowbaseItem::getVisibilityCriteria(true);

      $items_subquery = new QuerySubQuery(
         array_merge_recursive(
            [
               'COUNT' => 'cpt',
               'FROM'  => KnowbaseItem::getTable(),
               'WHERE' => [
                  KnowbaseItem::getTableField($cat_fk) => new QueryExpression(
                     DB::quoteName(KnowbaseItemCategory::getTableField('id'))
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
         'ORDER' => KnowbaseItemCategory::getTableField('name')
      ]);

      // Add root category (which is not a real category)
      $root_items_count = $DB->request(
         array_merge_recursive(
            [
               'COUNT' => 'cpt',
               'FROM'  => KnowbaseItem::getTable(),
               'WHERE' => [
                  KnowbaseItem::getTableField($cat_fk) => 0,
               ]
            ],
            $kbitem_visibility_crit
         )
      )->next();

      $categories = [
         [
            'id'          => '0',
            'name'        => __('Root category'),
            $cat_fk       => '#',
            'items_count' => $root_items_count['cpt'],
         ]
      ];
      foreach ($cat_iterator as $category) {
         $categories[] = $category;
      }

      $nodes   = [];

      foreach ($categories as $category) {
         $node = [
            'id'     => $category['id'],
            'parent' => $category[$cat_fk],
            'text'   => $category['name'],
            'a_attr' => [
               'data-id' => $category['id']
            ],
         ];

         if ($category['items_count'] > 0) {
            $node['text'] .= ' <strong title="' . __('This category contains articles') . '">'
               . '(' . $category['items_count'] . ')'
               . '</strong>';
         }

         $nodes[] = $node;
      }

      return $nodes;
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
