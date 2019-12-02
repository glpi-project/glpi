<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 9.5.0
 */
class Impact extends CommonGLPI {
   // Constants used to express the direction or "flow" of a graph
   // Theses constants can also be used to express if an edge is reachable
   // when exploring the graph forward, backward or both (0b11)
   const DIRECTION_FORWARD    = 0b01;
   const DIRECTION_BACKWARD   = 0b10;

   // Default colors used for the edges of the graph according to their flow
   const DEFAULT_COLOR            = 'black';   // The edge is not accessible from the starting point of the graph
   const IMPACT_COLOR             = '#ff3418'; // Forward
   const DEPENDS_COLOR            = '#1c76ff'; // Backward
   const IMPACT_AND_DEPENDS_COLOR = '#ca29ff'; // Forward and backward

   const NODE_ID_DELIMITER = "::";
   const EDGE_ID_DELIMITER = "->";

   // Consts for depth values
   const DEFAULT_DEPTH = 5;
   const MAX_DEPTH = 10;
   const NO_DEPTH_LIMIT = 10000;

   public static function getTypeName($nb = 0) {
      return _n('Asset impact', 'Asset impacts', $nb);
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      global $CFG_GLPI, $DB;

      // Class of the current item
      $class = get_class($item);

      // Only enabled for CommonDBTM
      if (!is_a($item, "CommonDBTM", true)) {
         throw new InvalidArgumentException(
            "Argument \$item ($class) must be a CommonDBTM."
         );
      }

      $isEnabledAsset = isset($CFG_GLPI['impact_asset_types'][$class]);
      $isITILObject = is_a($item, "CommonITILObject", true);

      // Check if itemtype is valid
      if (!$isEnabledAsset && !$isITILObject) {
         throw new InvalidArgumentException(
            "Argument \$item ($class) is not a valid target for impact analysis."
         );
      }

      if (!$_SESSION['glpishow_count_on_tabs']) {
         // Count is disabled in config -> 0
         $total = 0;
      } else if ($isEnabledAsset) {
         // If on an asset, get the number of its direct dependencies
         $total = count($DB->request([
            'FROM'   => ImpactRelation::getTable(),
            'WHERE'  => [
               'OR' => [
                  [
                     'itemtype_source' => get_class($item),
                     'items_id_source' => $item->fields['id'],
                  ],
                  [
                     'itemtype_impacted' => get_class($item),
                     'items_id_impacted' => $item->fields['id'],
                  ]
               ]
            ]
         ]));
      } else if ($isITILObject) {
         // Tab name for an ITIL object : always 0
         $total = 0;
      }

      return self::createTabEntry(__("Impact analysis"), $total);
   }

   public static function displayTabContentForItem(
      CommonGLPI $item,
      $tabnum = 1,
      $withtemplate = 0
   ) {
      global $CFG_GLPI;

      $class = get_class($item);

      // Only enabled for CommonDBTM
      if (!is_a($item, "CommonDBTM")) {
         throw new InvalidArgumentException(
            "Argument \$item ($class) must be a CommonDBTM)."
         );
      }

      $ID = $item->fields['id'];

      // Don't show the impact analysis on new object
      if ($item->isNewID($ID)) {
         return false;
      }

      // Check READ rights
      $itemtype = $item->getType();
      if (!$itemtype::canView()) {
         return false;
      }

      // For an ITIL object, load the first linked element by default
      if (is_a($item, "CommonITILObject")) {
         $linked_items = $item->getLinkedItems();

         // Search for a valid linked item of this ITILObject
         $found = false;
         foreach ($linked_items as $linked_item) {
            $class = $linked_item['itemtype'];
            if (isset($CFG_GLPI['impact_asset_types'][$class])) {
               $item = new $class;
               $found = $item->getFromDB($linked_item['items_id']);
               break;
            }
         }

         // No valid linked item were found, tab shouldn't be visible
         if (!$found) {
            return false;
         }

         self::printAssetSelectionForm($linked_items);
      }

      // Check is the impact analysis is enabled for $class
      if (!isset($CFG_GLPI['impact_asset_types'][$class])) {
         return false;
      }

      // Build graph and params
      $graph = Impact::buildGraph($item);
      $params = self::prepareParams($item);
      $readonly = !$item->can($item->fields['id'], UPDATE);

      // Print header
      self::printHeader(self::makeDataForCytoscape($graph), $params, $readonly);

      // Displays views
      self::displayGraphView($item);
      self::displayListView($item, $graph);

      // Select view
      echo Html::scriptBlock("
         // Select default view
         $(document).ready(function() {
            if (location.hash == '#list') {
               showListView();
            } else {
               showGraphView();
            }
         });
      ");

      return true;
   }

   /**
    * Display the impact analysis as an interactive graph
    *
    * @param CommonDBTM $item    starting point of the graph
    */
   public static function displayGraphView(
      CommonDBTM $item
   ) {
      self::loadLibs();

      echo '<div id="impact_graph_view">';
      self::prepareImpactNetwork($item);
      echo '</div>';
   }

   /**
    * Display the impact analysis as a list
    *
    * @param CommonDBTM $item   starting point of the graph
    * @param string     $graph  array containing the graph nodes and egdes
    */
   public static function displayListView(
      CommonDBTM $item,
      array $graph
   ) {
      global $CFG_GLPI;

      $impact_item = ImpactItem::findForItem($item);

      // Should not happen, $impact_item is created before
      if (!$impact_item) {
         throw new \InvalidArgumentException("No ImpactItem found");
      }

      echo '<div id="impact_list_view">';
      echo '<div class="impact-list-container">';

      // One table will be printed for each direction
      $lists = [
         __("Impact")      => self::DIRECTION_FORWARD,
         __("Impacted by") => self::DIRECTION_BACKWARD,
      ];
      $has_impact = false;

      foreach ($lists as $label => $direction) {
         $start_node_id = self::getNodeID($item);
         $data = self::buildListData($graph, $direction, $item, $impact_item);

         if (!count($data)) {
            continue;
         }

         $has_impact = true;
         echo '<table class="tab_cadre_fixehov impact-list-group">';

         // Header
         echo '<thead>';
         echo '<tr class="noHover">';
         echo '<th class="impact-list-header" colspan="2" width="90%"><h3>' . $label . '';
         echo '<i class="fas fa-2x fa-caret-down impact-toggle-subitems-master impact-pointer"></i></h3></th>';
         echo '</tr>';
         echo '</thead>';

         foreach ($data as $itemtype => $items) {
            echo '<tbody>';

            // Subheader
            echo '<tr class="tab_bg_1">';
            echo '<td class="left subheader impact-left" colspan="2"">';
            $total = count($items);
            echo '<a>' . _n($itemtype, $itemtype, $total) . '</a>' . ' (' . $total . ')';
            echo '<i class="fas fa-2x fa-caret-down impact-toggle-subitems impact-pointer"></i>';
            echo '</td>';
            echo '</tr>';

            foreach ($items as $itemtype_item) {
               // Content: one row per item
               echo '<tr class=tab_bg_1><div></div>';
               echo '<td class="impact-left" width="20%"><div>' . $itemtype_item['stored']->fields['name']  . '</div></td>';
               echo '<td width="80%"><div>';

               $path = [];
               foreach ($itemtype_item['node']['path'] as $node) {
                  if ($node['id'] == $start_node_id) {
                     $path[] = '<b>' . $node['name'] . '</b>';
                  } else {
                     $path[] = $node['name'];
                  }
               }
               $separator = '<i class="fas fa-angle-right"></i>';
               echo implode(" $separator ", $path);

               echo '</div></td>';
               echo '</tr>';
            }

            echo '</tbody>';
         }

         echo '</table>';
      }

      if (!$has_impact) {
         echo '<p>' . __("This asset doesn't have any impact dependencies.") . '</p>';
      }

      echo '</div>';

      $can_update = $item->can($item->fields['id'], UPDATE);

      // Toolbar
      echo '<div class="impact-list-toolbar">';
      if ($has_impact) {
         echo '<a target="_blank" href="'.$CFG_GLPI['root_doc'].'/front/impactcsv.php?itemtype=' . $impact_item->fields['itemtype'] . '&items_id=' . $impact_item->fields['items_id'] .'">';
         echo '<i class="fas fa-download impact-pointer impact-list-tools" title="' . __('Export to csv') .'"></i>';
         echo '</a>';
      }
      if ($can_update) {
         echo '<i id="impact-list-settings" class="fas fa-cog impact-pointer impact-list-tools" title="' . __('Settings') .'"></i>';
      }
      echo '</div>';

      // Settings dialog
      if ($can_update) {
         $rand = mt_rand();

         echo '<div id="list_depth_dialog" class="impact-dialog" title=' . __("Settings") . '>';
         echo '<form action="'.$CFG_GLPI['root_doc'].'/front/impactitem.form.php" method="POST">';
         echo '<table class="tab_cadre_fixe">';
         echo '<tr>';
         echo '<td><label for="impact_max_depth_' . $rand . '">' . __("Max depth") . '</label></td>';
         echo '<td>' . Html::input("max_depth", [
            'id'    => "impact_max_depth_$rand",
            'value' => $impact_item->fields['max_depth'] >= self::MAX_DEPTH ? '' : $impact_item->fields['max_depth'],
         ]) . '</td>';
         echo '</tr>';
         echo '<tr>';
         echo '<td><label for="check_no_limit_' . $rand . '">' . __("No limit") . '</label></td>';
         echo '<td>' . Html::getCheckbox([
            'name'    => 'no_limit',
            'id'      => "check_no_limit_$rand",
            'checked' => $impact_item->fields['max_depth'] >= self::MAX_DEPTH,
         ]) . '</td>';
         echo '</tr>';
         echo '</table>';
         echo Html::input('id', [
            'type'  => "hidden",
            'value' => $impact_item->fields['id'],
         ]);
         echo Html::input('update', [
            'type'  => "hidden",
            'value' => "1",
         ]);
         Html::closeForm();
         echo '</div>';
      }

      // Hide / show handler
      echo Html::scriptBlock('
         // jQuery doesn\'t allow slide animation on table elements, we need
         // to apply the animation to each cells content and then remove the
         // padding to get the desired "slide" animation

         function impactListUp(target) {
            target.removeClass("fa-caret-down");
            target.addClass("fa-caret-up");
            target.closest("tbody").find(\'tr:gt(0) td\').animate({padding: \'0px\'}, {duration: 400});
            target.closest("tbody").find(\'tr:gt(0) div\').slideUp("400");
         }

         function impactListDown(target) {
            target.addClass("fa-caret-down");
            target.removeClass("fa-caret-up");
            target.closest("tbody").find(\'tr:gt(0) td\').animate({padding: \'8px 5px\'}, {duration: 400});
            target.closest("tbody").find(\'tr:gt(0) div\').slideDown("400");
         }

         $(".impact-toggle-subitems").click(function(e) {
            if ($(e.target).hasClass("fa-caret-up")) {
               impactListDown($(e.target));
            } else {
               impactListUp($(e.target));
            }
         });

         $(".impact-toggle-subitems-master").click(function(e) {
            $(e.target).closest("table").find(".impact-toggle-subitems").each(function(i, elem) {
               if ($(e.target).hasClass("fa-caret-up")) {
                  impactListDown($(elem));
               } else {
                  impactListUp($(elem));
               }
            });

            $(e.target).toggleClass("fa-caret-up");
            $(e.target).toggleClass("fa-caret-down");
         });

         $(document).on("impactUpdated", function() {
            $.ajax({
               type: "GET",
               url: "' . $CFG_GLPI['root_doc'] . '/ajax/impact.php",
               data: {
                  itemtype: "' . get_class($item) . '",
                  items_id: "' . $item->fields['id'] . '",
                  action  : "load",
                  view    : "list",
               },
               success: function(data){
                  $("#impact_list_view").replaceWith(data);
               },
            });
         });
      ');

      if ($can_update) {
         // Handle settings actions
         echo Html::scriptBlock('
            $("#impact-list-settings").click(function() {
               $("#list_depth_dialog").dialog({
                  modal: true,
                  buttons: {
                     ' . __("Save") . ': function() {
                        if ($("input[name=\'no_limit\']:checked").length > 0) {
                           $("input[name=\'max_depth\']").val(' . self::NO_DEPTH_LIMIT . ');
                        }

                        $(this).find("form").submit();
                     },
                     ' . __("Cancel") . ': function() {
                        $(this).dialog( "close" );
                     }
                  },
               });
            });
         ');
      }

      echo '</div>';
   }

   /**
    * Build the data used to represent the impact graph as a semi-flat list
    *
    * @param array      $graph        array containing the graph nodes and egdes
    * @param int        $direction    should the list be build for item that are
    *                                 impacted by $item or that impact $item ?
    * @param CommonDBTM $item         starting point of the graph
    * @param ImpactItem $impact_item  saved params for $item
    *
    * @return array
    */
   public static function buildListData(
      array $graph,
      int $direction,
      CommonDBTM $item,
      ImpactItem $impact_item
   ) {
      $data = [];

      // Filter tree
      $sub_graph = self::filterGraph($graph, $direction);

      // Empty graph, no need to go further
      if (!count($sub_graph['nodes'])) {
         return $data;
      }

      // Evaluate path to each assets from the starting node
      $start_node_id = self::getNodeID($item);
      $start_node = $sub_graph['nodes'][$start_node_id];

      foreach ($sub_graph['nodes'] as $key => $vertex) {
         if ($key !== $start_node_id) {
            // Set path for target node using BFS
            $path = self::bfs(
               $sub_graph,
               $start_node,
               $vertex,
               $direction
            );

            // Add if path is not longer than the allowed value
            if (count($path) - 1 <= $impact_item->fields['max_depth']) {
               $sub_graph['nodes'][$key]['path'] = $path;
            }
         }
      }

      // Split the items by type
      foreach ($sub_graph['nodes'] as $node) {
         $details = explode(self::NODE_ID_DELIMITER, $node['id']);
         $itemtype = $details[0];
         $items_id = $details[1];

         // Skip start node or empty path
         if ($node['id'] == $start_node_id || !isset($node['path'])) {
            continue;
         }

         // Init itemtype if empty
         if (!isset($data[$itemtype])) {
            $data[$itemtype] = [];
         }

         // Add to itemtype
         $itemtype_item = new $itemtype;
         $itemtype_item->getFromDB($items_id);
         $data[$itemtype][] = [
            'stored' => $itemtype_item,
            'node'   => $node,
         ];
      }

      return $data;
   }

   /**
    * Return a subgraph matching the given direction
    *
    * @param array $graph      array containing the graph nodes and egdes
    * @param int   $direction  direction to match
    *
    * @return array
    */
   public static function filterGraph(array $graph, int $direction) {
      $new_graph = [
         'edges' => [],
         'nodes' => [],
      ];

      // For each edge in the graph
      foreach ($graph['edges'] as $edge) {
         // Filter on direction
         if ($edge['flag'] & $direction) {
            // Add the edge and its two connected nodes
            $source = $edge['source'];
            $target = $edge['target'];

            $new_graph['edges'][] = $edge;
            $new_graph['nodes'][$source] = $graph['nodes'][$source];
            $new_graph['nodes'][$target] = $graph['nodes'][$target];
         }
      }

      return $new_graph;
   }

   /**
    * Evaluate the path from one node to another using BFS algorithm
    *
    * @param array  $graph          array containing the graph nodes and egdes
    * @param array  $a              a node of the graph
    * @param array  $b              a node of the graph
    * @param int    $direction      direction used to travel the graph
    */
   public static function bfs(array $graph, array $a, array $b, int $direction) {
      switch ($direction) {
         case self::DIRECTION_FORWARD:
            $start = $a;
            $target = $b;
            break;

         case self::DIRECTION_BACKWARD:
            $start = $b;
            $target = $a;
            break;

         default:
            throw new \InvalidArgumentException("Invalid direction : $direction");
      }

      // Insert start node in the queue
      $queue = [];
      $queue[] = $start;
      $discovered = [$start['id'] => true];

      // Label start as discovered
      $start['discovered'] = true;

      // For each other nodes
      while (count($queue) > 0) {
         $node = array_shift($queue);

         if ($node['id'] == $target['id']) {
            // target found, build path to node
            $path = [$target];

            while (isset($node['dfs_parent'])) {
               $node = $node['dfs_parent'];
               array_unshift($path, $node);
            }

            return $path;
         }

         foreach ($graph['edges'] as $edge) {
            // Skip edge if not connected to the current node
            if ($edge['source'] !== $node['id']) {
               continue;
            }

            $nextNode = $graph['nodes'][$edge['target']];

            // Skip already discovered node
            if (isset($discovered[$nextNode['id']])) {
               continue;
            }

            $nextNode['dfs_parent'] = $node;
            $discovered[$nextNode['id']] = true;

            $queue[] = $nextNode;
         }
      }
   }

   /**
    * Print the title and view switch
    *
    * @param string  $graph      The network graph (json)
    * @param string  $params     Params of the graph (json)
    * @param bool    $readonly   Is the graph editable ?
    */
   public static function printHeader(
      string $graph,
      string $params,
      bool $readonly
   ) {
      echo '<div class="impact-header">';
      echo "<h2>" . __("Impact analysis") . "</h2>";
      echo "<div id='switchview'>";
      echo "<a id='sviewlist' href='#list'><i class='pointer fa fa-list-alt' title='".__('View as list')."'></i></a>";
      echo "<a id='sviewgraph' href='#graph'><i class='pointer fa fa-project-diagram' title='".__('View graphical representation')."'></i></a>";
      echo "</div>";
      echo "</div>";

      // View selection
      echo Html::scriptBlock("
         function showGraphView() {
            $('#impact_list_view').hide();
            $('#impact_graph_view').show();
            $('#sviewlist i').removeClass('selected');
            $('#sviewgraph i').addClass('selected');

            if (window.GLPIImpact !== undefined && GLPIImpact.cy === null) {
               GLPIImpact.buildNetwork($graph, $params, $readonly);
            }
         }

         function showListView() {
            $('#impact_graph_view').hide();
            $('#impact_list_view').show();
            $('#sviewgraph i').removeClass('selected');
            $('#sviewlist i').addClass('selected');
         }

         $('#sviewgraph').click(function() {
            showGraphView();
         });

         $('#sviewlist').click(function() {
            showListView();
         });
      ");
   }

   /**
    * Load the cytoscape and spectrum-colorpicker librairies
    *
    * @since 9.5
    */
   public static function loadLibs() {
      echo Html::css('public/lib/spectrum-colorpicker.css');
      echo Html::script("public/lib/spectrum-colorpicker.js");
      echo Html::css('public/lib/cytoscape.css');
      echo Html::script("public/lib/cytoscape.js");
   }

   /**
    * Print the asset selection form used in the impact tab of ITIL objects
    *
    * @param array $items
    *
    * @since 9.5
    */
   public static function printAssetSelectionForm(array $items) {
      global $CFG_GLPI;

      // Dropdown values
      $values = [];

      // Add a value in the dropdown for each items, grouped by type
      foreach ($items as $item) {
         if (isset($CFG_GLPI['impact_asset_types'][$item['itemtype']])) {
            // Add itemtype group if it doesn't exist in the dropdown yet
            $itemtype_label =  $item['itemtype']::getTypeName();
            if (!isset($values[$itemtype_label])) {
               $values[$itemtype_label] = [];
            }

            $key = $item['itemtype'] . "::" . $item['items_id'];
            $values[$itemtype_label][$key] = $item['name'];
         }
      }

      Dropdown::showFromArray("impact_assets_selection_dropdown", $values);
      echo '<div class="impact-mb-2"></div>';

      // Form interaction: load a new graph on value change
      echo Html::scriptBlock('
         $(function() {
            var selector = "select[name=impact_assets_selection_dropdown]";

            $(selector).change(function(){
               var values = $(selector + " option:selected").val().split("::");

               $.ajax({
                  type: "GET",
                  url: "'. $CFG_GLPI['root_doc'] . '/ajax/impact.php",
                  data: {
                     itemtype: values[0],
                     items_id: values[1],
                     action  : "load",
                  },
                  success: function(data, textStatus, jqXHR) {
                     GLPIImpact.buildNetwork(
                        JSON.parse(data.graph),
                        JSON.parse(data.params),
                        data.readonly
                     );
                  }
               });
            });
         });
      ');
   }

   /**
    * Search asset by itemtype and name
    *
    * @param string  $itemtype   type
    * @param array   $used       ids to exlude from the search
    * @param string  $filter     filter on name
    * @param int     $page       page offset
    */
   public static function searchAsset(
      string $itemtype,
      array $used,
      string $filter,
      int $page = 0
   ) {
      global $CFG_GLPI, $DB;

      // Check if this type is enabled in config
      if (!isset($CFG_GLPI['impact_asset_types'][$itemtype])) {
         throw new \InvalidArgumentException(
            "itemtype ($itemtype) must be enabled in cfg/impact_asset_types"
         );
      }

      // Check class exist and is a child of CommonDBTM
      if (!is_subclass_of($itemtype, "CommonDBTM", true)) {
         throw new \InvalidArgumentException(
            "itemtype ($itemtype) must be a valid child of CommonDBTM"
         );
      }

      // This array can't be empty since we will use it in the NOT IN part of the reqeust
      if (!count($used)) {
         $used[] = -1;
      }

      // Search for items
      $filter = strtolower($filter);
      $base_request = [
         'FROM'   => $itemtype::getTable(),
         'WHERE'  => [
            'RAW'  => [
               'LOWER(' . DBmysql::quoteName('name') . ')' => ['LIKE', "%$filter%"]
            ],
            'NOT' => [
               'id' => $used
            ],
         ],
      ];
      $select = [
         'SELECT' => ['id', 'name'],
      ];
      $limit = [
         'START' => $page * 20,
         'LIMIT' => "20",
      ];
      $count = [
         'COUNT' => "total",
      ];

      // Get items
      $rows = $DB->request($base_request + $select + $limit);

      // Get total
      $total = $DB->request($base_request + $count);

      return [
         "items" => iterator_to_array($rows, false),
         "total" =>  iterator_to_array($total, false)[0]['total'],
      ];
   }

   /**
    * Load the impact network container
    *
    * @since 9.5
    */
   public static function printImpactNetworkContainer() {
      global $CFG_GLPI;

      $action = $CFG_GLPI['root_doc'] . '/ajax/impact.php';
      $formName = "form_impact_network";

      echo "<form name=\"$formName\" action=\"$action\" method=\"post\">";
      echo "<table class='tab_cadre_fixe network-table'>";
      echo '<tr><td class="network-parent">';
      echo '<span id="help_text"></span>';

      echo '<div id="network_container"></div>';
      echo '<img class="impact-drop-preview">';

      echo '<div class="impact-side">';

      echo '<div class="impact-side-panel">';

      echo '<div class="impact-side-add-node">';
      echo '<h3>' . __('Add assets') . '</h3>';
      echo '<div class="impact-side-select-itemtype">';

      echo Html::input("impact-side-filter-itemtypes", [
         'id' => 'impact-side-filter-itemtypes',
         'placeholder' => __('Filter itemtypes...'),
      ]);

      echo '<div class="impact-side-filter-itemtypes-items">';
      foreach ($CFG_GLPI["impact_asset_types"] as $itemtype => $icon) {
         echo '<div class="impact-side-filter-itemtypes-item">';
         // Add default image if the real path doesn't lead to an existing file
         if (!file_exists(__DIR__ . "/../$icon")) {
            $icon = "pics/impact/default.png";
         }

         echo '<h4><img class="impact-side-icon" src="/../' . $icon . '" title="' . $itemtype::getTypeName() . '" data-itemtype="' . $itemtype . '">';
         echo "<span>" . $itemtype::getTypeName() . "</span></h4>";
         echo '</div>'; // impact-side-filter-itemtypes-item
      }
      echo '</div>'; // impact-side-filter-itemtypes-items
      echo '</div>'; // <div class="impact-side-select-itemtype">

      echo '<div class="impact-side-search">';
      echo '<h4><i class="fas fa-chevron-left"></i><img><span></span></h4>';
      echo Html::input("impact-side-filter-assets", [
         'id' => 'impact-side-filter-assets',
         'placeholder' => __('Filter assets...'),
      ]);

      echo '<div class="impact-side-search-panel">';
      echo '<div class="impact-side-search-results"></div>';

      echo '<div class="impact-side-search-more">';
      echo '<h4><i class="fas fa-chevron-down"></i>' . __("More...") . '</h4>';
      echo '</div>'; // <div class="impact-side-search-more">

      echo '<div class="impact-side-search-no-results">';
      echo '<p>'. __("No results") . '</p>';
      echo '</div>'; // <div class="impact-side-search-no-results">

      echo '<div class="impact-side-search-spinner">';
      echo '<i class="fas fa-spinner fa-2x fa-spin"></i>';
      echo '</div>'; // <div class="impact-side-search-spinner">

      echo '</div>'; // <div class="impact-side-search-panel">

      echo '</div>'; // <div class="impact-side-search">

      echo '</div>'; // div class="impact-side-add-node">

      echo '<div class="impact-side-settings">';
      echo '<h3>' . __('Settings') . '</h3>';

      echo '<h4>' . __('Visibility') . '</h4>';
      echo '<div class="impact-side-settings-item">';
      echo \Html::getCheckbox([
         'id'      => "toggle_impact",
         'name'    => "toggle_impact",
         'checked' => "true",
      ]);
      echo '<span class="impact-checkbox-label">' . __("Show impact") . '</span>';
      echo '</div>';

      echo '<div class="impact-side-settings-item">';
      echo \Html::getCheckbox([
         'id'      => "toggle_depends",
         'name'    => "toggle_depends",
         'checked' => "true",
      ]);
      echo '<span class="impact-checkbox-label">' . __("Show depends") . '</span>';
      echo '</div>';

      echo '<h4>' . __('Colors') . '</h4>';
      echo '<div class="impact-side-settings-item">';
      Html::showColorField("depends_color", []);
      echo '<span class="impact-checkbox-label">' . __("Depends") . '</span>';
      echo '</div>';

      echo '<div class="impact-side-settings-item">';
      Html::showColorField("impact_color", []);
      echo '<span class="impact-checkbox-label">' . __("Impact") . '</span>';
      echo '</div>';

      echo '<div class="impact-side-settings-item">';
      Html::showColorField("impact_and_depends_color", []);
      echo '<span class="impact-checkbox-label">' . __("Impact and depends") . '</span>';
      echo '</div>';

      echo '<h4>' . __('Max depth') . '</h4>';
      echo '<div class="impact-side-settings-item">';
      echo '<input id="max_depth" type="range" class="impact-range" min="1" max ="10" step="1" value="5"><span id="max_depth_view" class="impact-checkbox-label"></span>';
      echo '</div>';

      echo '</div>'; // div class="impact-side-settings">

      echo '<div class="impact-side-search-footer"></div>';
      echo '</div>'; // div class="impact-side-panel">

      echo '<ul>';
      echo '<li id="save_impact" title="' . __("Save") .'"><i class="fas fa-fw fa-save"></i></li>';
      echo '<li id="add_node" title="' . __("Add asset") .'"><i class="fas fa-fw fa-plus"></i></li>';
      echo '<li id="add_edge" title="' . __("Add relation") .'"><i class="fas fa-fw fa-pencil-alt"></i></li>';
      echo '<li id="add_compound" title="' . __("Add group") .'"><i class="far fa-fw fa-square"></i></li>';
      echo '<li id="delete_element" title="' . __("Delete element") .'"><i class="fas fa-fw fa-trash"></i></li>';
      echo '<li id="export_graph" title="' . __("Download") .'"><i class="fas fa-fw fa-download"></i></li>';
      echo '<li id="toggle_fullscreen" title="' . __("Fullscreen") .'"><i class="fas fa-fw fa-expand"></i></li>';
      echo '<li id="impact_settings" title="' . __("Settings") .'"><i class="fas fa-fw fa-cog"></i></li>';
      echo '</ul>';
      echo '<span class="impact-side-toggle"><i class="fas fa-2x fa-chevron-left"></i></span>';
      echo '</div>'; // <div class="impact-side impact-side-expanded">
      echo "</td></tr>";
      echo "</table>";
      Html::closeForm();
   }

   /**
    * Build the impact graph starting from a node
    *
    * @since 9.5
    *
    * @param CommonDBTM $item Current item
    *
    * @return array Array containing edges and nodes
    */
   public static function buildGraph(CommonDBTM $item) {
      $nodes = [];
      $edges = [];

      // Explore the graph forward
      self::buildGraphFromNode($nodes, $edges, $item, self::DIRECTION_FORWARD);

      // Explore the graph backward
      self::buildGraphFromNode($nodes, $edges, $item, self::DIRECTION_BACKWARD);

      // Add current node to the graph if no impact relations were found
      if (count($nodes) == 0) {
         self::addNode($nodes, $item);
      }

      return [
         'nodes' => $nodes,
         'edges' => $edges
      ];
   }

   /**
    * Explore dependencies of the current item, subfunction of buildGraph()
    *
    * @since 9.5
    *
    * @param array      $edges          Edges of the graph
    * @param array      $nodes          Nodes of the graph
    * @param CommonDBTM $node           Current node
    * @param int        $direction      The direction in which the graph
    *                                   is being explored : DIRECTION_FORWARD
    *                                   or DIRECTION_BACKWARD
    * @param array      $explored_nodes List of nodes that have already been
    *                                   explored
    *
    * @throws InvalidArgumentException
    */
   private static function buildGraphFromNode(
      array &$nodes,
      array &$edges,
      CommonDBTM $node,
      int $direction,
      array $explored_nodes = []
   ) {
      global $DB;

      // Source and target are determined by the direction in which we are
      // exploring the graph
      switch ($direction) {
         case self::DIRECTION_BACKWARD:
            $source = "source";
            $target = "impacted";
            break;
         case self::DIRECTION_FORWARD:
            $source = "impacted";
            $target = "source";
            break;
         default:
            throw new InvalidArgumentException(
               "Invalid value for argument \$direction ($direction)."
            );
      }

      // Get relations of the current node
      $relations = $DB->request([
         'FROM'   => ImpactRelation::getTable(),
         'WHERE'  => [
            'itemtype_' . $target => get_class($node),
            'items_id_' . $target => $node->fields['id']
         ]
      ]);

      // Add current code to the graph if we found at least one impact relation
      if (count($relations)) {
         self::addNode($nodes, $node);
      }

      // Iterate on each relations found
      foreach ($relations as $related_item) {
         // Add the related node
         $related_node = new $related_item['itemtype_' . $source];
         $related_node->getFromDB($related_item['items_id_' . $source]);
         self::addNode($nodes, $related_node);

         // Add or update the relation on the graph
         $edgeID = self::getEdgeID($node, $related_node, $direction);
         self::addEdge($edges, $edgeID, $node, $related_node, $direction);

         // Keep exploring from this node unless we already went through it
         $related_node_id = self::getNodeID($related_node);
         if (!isset($explored_nodes[$related_node_id])) {
            $explored_nodes[$related_node_id] = true;
            self::buildGraphFromNode(
               $nodes,
               $edges,
               $related_node,
               $direction,
               $explored_nodes
            );
         }
      }
   }

   /**
    * Add a node to the node list if missing
    *
    * @param array      $nodes  Nodes of the graph
    * @param CommonDBTM $item   Node to add
    *
    * @since 9.5
    *
    * @return bool true if the node was missing, else false
    */
   private static function addNode(array &$nodes, CommonDBTM $item) {
      global $CFG_GLPI;

      // Check if the node already exist
      $key = self::getNodeID($item);
      if (isset($nodes[$key])) {
         return false;
      }

      // Get web path to the image matching the itemtype from config
      $image_name = $CFG_GLPI["impact_asset_types"][get_class($item)];

      // Add default image if the real path doesn't lead to an existing file
      if (!file_exists(__DIR__ . "/../$image_name")) {
         $image_name = "pics/impact/default.png";
      }

      // Define basic data of the new node
      $new_node = [
         'id'          => $key,
         'label'       => $item->fields['name'],
         'name'        => $item->fields['name'],
         'image'       => $CFG_GLPI['root_doc'] . "/$image_name",
         'ITILObjects' => $item->getITILTickets(true),
         'link'        => $item->getLinkURL()
      ];

      // Alter the label if we found some linked ITILObjects
      $itil_tickets_count = $new_node['ITILObjects']['count'];
      if ($itil_tickets_count > 0) {
         $new_node['label'] .= " ($itil_tickets_count)";
         $new_node['hasITILObjects'] = 1;
      }

      // Load or create a new ImpactItem object
      $impact_item = ImpactItem::findForItem($item);
      if (!$impact_item) {
         $impact_item = new ImpactItem();
         $newID = $impact_item->add([
            'itemtype' => get_class($item),
            'items_id' => $item->fields['id']
         ]);
         $impact_item->getFromDB($newID);
      }

      // Load node position and parent
      $new_node['impactitem_id'] = $impact_item->fields['id'];
      $new_node['parent']        = $impact_item->fields['parent_id'];
      $new_node['position_x']    = $impact_item->fields['position_x'];
      $new_node['position_y']    = $impact_item->fields['position_y'];

      // If the node has a parent, add it to the node list aswell
      if (!empty($new_node['parent'])) {
         $compound = new ImpactCompound();
         $compound->getFromDB($new_node['parent']);

         if (!isset($nodes[$new_node['parent']])) {
            $nodes[$new_node['parent']] = [
               'id'    => $compound->fields['id'],
               'label' => $compound->fields['name'],
               'color' => $compound->fields['color'],
            ];
         }
      }

      // Insert the node
      $nodes[$key] = $new_node;
      return true;
   }

   /**
    * Add an edge to the edge list if missing, else update it's direction
    *
    * @param array      $edges      Edges of the graph
    * @param string     $key        ID of the new edge
    * @param CommonDBTM $itemA      One of the node connected to this edge
    * @param CommonDBTM $itemB      The other node connected to this edge
    * @param int        $direction  Direction of the edge : A to B or B to A ?
    *
    * @since 9.5
    *
    * @return bool true if the node was missing, else false
    *
    * @throws InvalidArgumentException
    */
   private static function addEdge(
      array &$edges,
      string $key,
      CommonDBTM $itemA,
      CommonDBTM $itemB,
      int $direction
   ) {
      // Just update the flag if the edge already exist
      if (isset($edges[$key])) {
         $edges[$key]['flag'] = $edges[$key]['flag'] | $direction;
         return;
      }

      // Assign 'from' and 'to' according to the direction
      switch ($direction) {
         case self::DIRECTION_FORWARD:
            $from = self::getNodeID($itemA);
            $to = self::getNodeID($itemB);
            break;
         case self::DIRECTION_BACKWARD:
            $from = self::getNodeID($itemB);
            $to = self::getNodeID($itemA);
            break;
         default:
            throw new InvalidArgumentException(
               "Invalid value for argument \$direction ($direction)."
            );
      }

      // Add the new edge
      $edges[$key] = [
         'id'     => $key,
         'source' => $from,
         'target' => $to,
         'flag'   => $direction
      ];
   }

   /**
    * Build the graph and the cytoscape object
    *
    * @since 9.5
    *
    * @param string  $graph      The network graph (json)
    * @param string  $params     Params of the graph (json)
    * @param bool    $readonly   Is the graph editable ?
    */
   public static function buildNetwork(
      string $graph,
      string $params,
      bool $readonly
   ) {
      echo Html::scriptBlock("
         $(function() {
            GLPIImpact.buildNetwork($graph, $params, $readonly);
         });
      ");
   }

   /**
    * Get saved graph params for the current item
    *
    * @param CommonDBTM $item
    *
    * @return string $item
    */
   public static function prepareParams(CommonDBTM $item) {
      $impact_item = ImpactItem::findForItem($item);

      return json_encode([
         'zoom'                     => $impact_item->fields['zoom'],
         'pan_x'                    => $impact_item->fields['pan_x'],
         'pan_y'                    => $impact_item->fields['pan_y'],
         'impact_color'             => $impact_item->fields['impact_color'],
         'depends_color'            => $impact_item->fields['depends_color'],
         'impact_and_depends_color' => $impact_item->fields['impact_and_depends_color'],
         'show_depends'             => $impact_item->fields['show_depends'],
         'show_impact'              => $impact_item->fields['show_impact'],
         'max_depth'                => $impact_item->fields['max_depth'],
      ]);
   }

   /**
    * Convert the php array reprensenting the graph into the format required by
    * the Cytoscape library
    *
    * @param array $graph
    *
    * @return string json data
    */
   public static function makeDataForCytoscape(array $graph) {
      $data = [];

      foreach ($graph['nodes'] as $node) {
         $data[] = [
            'group'    => 'nodes',
            'data'     => $node,
         ];
      }

      foreach ($graph['edges'] as $edge) {
         $data[] = [
            'group' => 'edges',
            'data'  => $edge,
         ];
      }

      return json_encode($data);
   }

   /**
    * Load the "show ongoing tickets" dialog
    *
    * @since 9.5
    */
   public static function printShowOngoingDialog() {
      // This dialog will be built dynamically by the front end
      echo '<div id="ongoing_dialog"></div>';
   }

   /**
    * Load the "edit compound" dialog
    *
    * @since 9.5
    */
   public static function printEditCompoundDialog() {
      echo '<div id="edit_compound_dialog"  class="impact-dialog">';
      echo "<table class='tab_cadre_fixe'>";

      // First row: name field
      echo "<tr>";
      echo "<td>";
      echo "<label>&nbsp;" . __("Name") . "</label>";
      echo "</td>";
      echo "<td>";
      echo Html::input("compound_name", []);
      echo "</td>";
      echo "</tr>";

      // Second row: color field
      echo "<tr>";
      echo "<td>";
      echo "<label>&nbsp;" . __("Color") . "</label>";
      echo "</td>";
      echo "<td>";
      Html::showColorField("compound_color", [
         'value' => '#d2d2d2'
      ]);
      echo "</td>";
      echo "</tr>";

      echo "</table>";
      echo "</div>";
   }

   /**
    * Prepare the impact network
    *
    * @since 9.5
    *
    * @param CommonDBTM $item The specified item
    */
   public static function prepareImpactNetwork(CommonDBTM $item) {
      // Load requirements
      self::printImpactNetworkContainer();
      self::printShowOngoingDialog();
      self::printEditCompoundDialog();
      echo Html::script("js/impact.js");

      // Load backend values
      $default   = self::DEFAULT_COLOR;
      $forward   = self::IMPACT_COLOR;
      $backward  = self::DEPENDS_COLOR;
      $both      = self::IMPACT_AND_DEPENDS_COLOR;
      $start_node = self::getNodeID($item);

      // Bind the backend values to the client and start the network
      echo  Html::scriptBlock("
         $(function() {
            GLPIImpact.prepareNetwork(
               $(\"#network_container\"),
               {
                  default : '$default',
                  forward : '$forward',
                  backward: '$backward',
                  both    : '$both',
               },
               '$start_node'
            )
         });
      ");
   }

   /**
    * Check that a given asset exist in the DB
    *
    * @param string $itemtype Class of the asset
    * @param string $items_id id of the asset
    */
   public static function assetExist(string $itemtype, string $items_id) {
      global $CFG_GLPI;

      try {
         // Check this asset type is enabled
         if (!isset($CFG_GLPI['impact_asset_types'][$itemtype])) {
            return false;
         }

         // Try to create an object matching the given item type
         $reflection_class = new ReflectionClass($itemtype);
         if (!$reflection_class->isInstantiable()) {
            return false;
         }

         // Look for a matching asset in the DB
         $asset = new $itemtype();
         return $asset->getFromDB($items_id);
      } catch (ReflectionException $e) {
         // Class does not exist
         return false;
      }
   }

   /**
    * Create an ID for a node (itemtype::items_id)
    *
    * @param CommonDBTM  $item Name of the node
    *
    * @return string
    */
   public static function getNodeID(CommonDBTM $item) {
      return get_class($item) . self::NODE_ID_DELIMITER . $item->fields['id'];
   }

   /**
    * Create an ID for an edge (NodeID->NodeID)
    *
    * @param CommonDBTM  $itemA     First node of the edge
    * @param CommonDBTM  $itemB     Second node of the edge
    * @param int         $direction Direction of the edge : A to B or B to A ?
    *
    * @return string|null
    *
    * @throws InvalidArgumentException
    */
   public static function getEdgeID(
      CommonDBTM $itemA,
      CommonDBTM $itemB,
      int $direction
   ) {
      switch ($direction) {
         case self::DIRECTION_FORWARD:
            return self::getNodeID($itemA) . self::EDGE_ID_DELIMITER . self::getNodeID($itemB);

         case self::DIRECTION_BACKWARD:
            return self::getNodeID($itemB) . self::EDGE_ID_DELIMITER . self::getNodeID($itemA);

         default:
            throw new InvalidArgumentException(
               "Invalid value for argument \$direction ($direction)."
            );
      }
   }

   /**
    * Print the form for the global impact page
    */
   public static function printImpactForm() {
      global $CFG_GLPI;
      $rand = mt_rand();

      echo "<form name=\"item\" action=\"{$_SERVER['PHP_SELF']}\" method=\"GET\">";

      echo '<table class="tab_cadre_fixe" style="width:30%">';

      // First row: header
      echo "<tr>";
      echo "<th colspan=\"2\">" . __('Impact analysis') . "</th>";
      echo "</tr>";

      // Second row: itemtype field
      echo "<tr>";
      echo "<td width=\"40%\"> <label>" . __('Item type') . "</label> </td>";
      echo "<td>";
      Dropdown::showItemTypes(
         'type',
         array_keys($CFG_GLPI['impact_asset_types']),
         [
            'value'        => null,
            'width'        => '100%',
            'emptylabel'   => Dropdown::EMPTY_VALUE,
            'rand'         => $rand
         ]
      );
      echo "</td>";
      echo "</tr>";

      // Third row: items_id field
      echo "<tr>";
      echo "<td> <label>" . __('Item') . "</label> </td>";
      echo "<td>";
      Ajax::updateItemOnSelectEvent("dropdown_type$rand", "form_results",
         $CFG_GLPI["root_doc"] . "/ajax/dropdownTrackingDeviceType.php",
         [
            'itemtype'        => '__VALUE__',
            'entity_restrict' => 0,
            'multiple'        => 1,
            'admin'           => 1,
            'rand'            => $rand,
            'myname'          => "id",
         ]
      );
      echo "<span id='form_results'>\n";
      echo "</span>\n";
      echo "</td>";
      echo "</tr>";

      // Fourth row: submit
      echo "<tr><td colspan=\"2\" style=\"text-align:center\">";
      echo Html::submit(__("Show impact analysis"));
      echo "</td></tr>";

      echo "</table>";
      echo "<br><br>";
      Html::closeForm();
   }
}
