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

      // Print header
      self::printHeader();

      // Check is the impact analysis is enabled for $class
      if (!isset($CFG_GLPI['impact_asset_types'][$class])) {
         return false;
      }

      // Build graph and params
      $graph = Impact::buildGraph($item);

      // Displays views
      self::displayGraphView($item, self::makeDataForCytoscape($graph));
      self::displayListView($item, $graph);

      return true;
   }

   /**
    * Display the impact analysis as an interactive graph
    *
    * @param CommonDBTM $item    starting point of the graph
    * @param string     $graph   graph in the format expected by cytoscape (json)
    * @param string     $params  saved graph params (json)
    */
   public static function displayGraphView(
      CommonDBTM $item,
      string $graph
   ) {
      self::loadLibs();
      $params = self::prepareParams($item);
      $readonly = !$item->can($item->fields['id'], UPDATE);

      echo '<div id="impact_graph_view">';
      self::prepareImpactNetwork($item);
      self::buildNetwork($graph, $params, $readonly);
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
      foreach ($lists as $label => $direction) {
         $start_node_id = self::getNodeID($item);
         $data = self::buildListData($graph, $direction, $item, $impact_item);

         echo '<table class="tab_cadre_fixehov impact-list-group">';

         // Header
         echo '<thead>';
         echo '<tr class="noHover">';
         echo '<th colspan="2" width="90%"><h3>' . $label . '</h3></th>';
         echo '<th><i class="fas fa-2x fa-caret-down impact-toggle-subitems-master impact-pointer"></i></th>';
         echo '</tr>';
         echo '</thead>';

         foreach ($data as $itemtype => $items) {
            echo '<tbody>';

            // Subheader
            echo '<tr class="tab_bg_1">';
            echo '<td class="left subheader impact-left" width="20%">';
            $total = count($items);
            echo '<a>' . _n($itemtype, $itemtype, $total) . '</a>' . ' (' . $total . ')';
            echo '</td>';
            echo '<td class="left subheader" width="70%"></td>';
            echo '<td class="subheader" width="10%">';
            echo '<i class="fas fa-2x fa-caret-down impact-toggle-subitems impact-pointer"></i>';
            echo '</td>';
            echo '</tr>';

            foreach ($items as $itemtype_item) {
               // Content: one row per item
               echo '<tr class=tab_bg_1><div></div>';
               echo '<td class="impact-left"><div>' . $itemtype_item['stored']->fields['name']  . '</div></td>';
               echo '<td><div>';

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
               echo '<td class="center"></td>';
               echo '</tr>';
            }

            echo '</tbody>';
         }

         echo '</table>';
      }

      echo '</div>';

      $can_update = $item->can($item->fields['id'], UPDATE);

      // Toolbar
      echo '<div class="impact-list-toolbar">';
      echo '<a target="_blank" href="'.$CFG_GLPI['root_doc'].'/front/impactcsv.php?itemtype=' . $impact_item->fields['itemtype'] . '&items_id=' . $impact_item->fields['items_id'] .'">';
      echo '<i class="fas fa-2x fa-download impact-pointer"></i>';
      echo '</a>';
      if ($can_update) {
         echo '<i id="impact-list-settings" class="fas fa-2x fa-cog impact-pointer"></i>';
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
                  view: "list",
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
    * Print the title and view swtich
    */
   public static function printHeader() {
      echo '<div class="impact-header">';
      echo "<h2>" . __("Impact analysis") . "</h2>";
      echo "<div id='switchview'>";
      echo "<a id='sviewgraph' href='#graph'><i class='pointer fa fa-project-diagram' title='".__('View graphical representation')."'></i></a>";
      echo "<a id='sviewlist' href='#list'><i class='pointer fa fa-list-alt' title='".__('View as list')."'></i></a>";
      echo "</div>";
      echo "</div>";

      // View selection
      echo Html::scriptBlock("
         function showGraphView() {
            $('#impact_list_view').hide();
            $('#impact_graph_view').show();
            $('#sviewlist i').removeClass('selected');
            $('#sviewgraph i').addClass('selected');

            if (window.GLPIImpact !== undefined) {
               // Force cytoscape render
               window.GLPIImpact.cy.resize();

               // Force grid guide render
               $(document).trigger('resize');
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

         // Select default view
         $(document).ready(function() {
            if (location.hash == '#list') {
               showListView();
            } else {
               showGraphView();
            }
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
                     itemtype:   values[0],
                     items_id:     values[1],
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
      echo '<div class="impact_toolbar">';
      echo '<span id="help_text"></span>';
      echo '<div id="impact_tools">';
      echo '<span id="save_impact" style="display: none">' . __("Save") . '&nbsp;<i></i></span>';
      echo '<span id="add_node" style="display: none"><i class="fas fa-plus"></i></span>';
      echo '<span id="add_edge" style="display: none"><i class="fas fa-marker"></i></span>';
      echo '<span id="add_compound" style="display: none"><i class="far fa-square"></i></span>';
      echo '<span id="delete_element" style="display: none"><i class="fas fa-trash"></i></span>';
      echo '<span id="export_graph"><i class="fas fa-download"></i></span>';
      echo '<span id="toggle_fullscreen"><i class="fas fa-expand"></i></span>';
      echo '<span id="expand_toolbar" style="display: none"><i class="fas fa-ellipsis-v "></i></span>';
      echo '</div>';
      self::printDropdownMenu();
      echo '</div>';
      echo '<div id="network_container"></div>';
      echo "</td></tr>";
      echo "</table>";
      Html::closeForm();
   }

   /**
    * Print the dropdown menu at the end of the toolbar
    */
   public static function printDropdownMenu() {
      echo
         '<div class="more">' .
            '<div class="more-menu" style="display: none;">' .
               '<div class="more-menu-caret">' .
                  '<div class="more-menu-caret-outer"></div>' .
                  '<div class="more-menu-caret-inner"></div>' .
               '</div>' .
               '<ul class="more-menu-items" tabindex="-1">' .
                  '<li id="toggle_impact" class="more-menu-item">' .
                     '<button type="button" class="more-menu-btn">' .
                        '<i class="fas fa-eye"></i> Toggle impact' .
                     '</button>' .
                  '</li>' .
                  '<li id="toggle_depends" class="more-menu-item">' .
                     '<button type="button" class="more-menu-btn">' .
                        '<i class="fas fa-eye"></i> Toggle depends' .
                     '</button>' .
                  '</li>' .
                  '<li id="color_picker" class="more-menu-item">' .
                     '<button type="button" class="more-menu-btn">' .
                        '<i class="fas fa-palette"></i> Colors' .
                     '</button>' .
                  '</li>' .
                  '<hr>' .
                  '<li id="color_picker" class="more-menu-item">' .
                     '<button type="button" class="more-menu-btn more-disabled" id="max_depth_view">' .
                        'Max depth : 5' .
                     '</button>' .
                  '</li>' .
                  '<li id="color_picker" class="more-menu-item">' .
                     '<span class="more-menu-btn">' .
                        '<input id="max_depth" type="range" class="impact-range" min="1" max ="10" step="1" value="5">' .
                     '</span>' .
                  '</li>' .
               '</ul>' .
            '</div>' .
         "</div>";

      // JS to show/hide the dropdown
      echo Html::scriptBlock("
         var el = document.querySelector('.more');
         var btn = $('.more')[0];
         var menu = el.querySelector('.more-menu');
         var visible = false;

         function showMenu(e) {
            e.preventDefault();
            if (!visible) {
               visible = true;
               el.classList.add('show-more-menu');
               $(menu).show();
               document.addEventListener('mousedown', hideMenu, false);
            } else {
               visible = false;
               el.classList.remove('show-more-menu');
               $(menu).hide();
               document.removeEventListener('mousedown', hideMenu);
            }
         }

         function hideMenu(e) {
            if (e.target.id == 'expand_toolbar') {
               return;
            }
            if (btn.contains(e.target)) {
               return;
            }
            if (visible) {
               visible = false;
               el.classList.remove('show-more-menu');
               $(menu).hide();
               document.removeEventListener('mousedown', hideMenu);
            }
         }
      ");
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
    * @param string  $graph   The network graph (json)
    * @param string  $params  Params of the graph (json)
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
    * Load the add node dialog
    *
    * @since 9.5
    */
   public static function printAddNodeDialog() {
      global $CFG_GLPI;
      $rand = mt_rand();

      echo '<div id="add_node_dialog" class="impact-dialog">';
      echo '<table class="tab_cadre_fixe">';

      // First row: itemtype field
      echo "<tr>";
      echo "<td> <label>" . __('Item type') . "</label> </td>";
      echo "<td>";
      Dropdown::showItemTypes(
         'item_type',
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

      // Second row: items_id field
      echo "<tr>";
      echo "<td> <label>" . __('Item') . "</label> </td>";
      echo "<td>";
      Ajax::updateItemOnSelectEvent("dropdown_item_type$rand", "results",
         $CFG_GLPI["root_doc"].
         "/ajax/dropdownTrackingDeviceType.php",
         [
            'itemtype'        => '__VALUE__',
            'entity_restrict' => 0,
            'multiple'        => 1,
            'admin'           => 1,
            'rand'            => $rand,
            'myname'          => "item_id",
            'context'         => "impact"
         ]
      );
      echo "<span id='results'>\n";
      echo "</span>\n";
      echo "</td>";
      echo "</tr>";

      echo "</table>";
      echo "</div>";
   }

   /**
    * Load the "show ongoing tickets" dialog
    *
    * @since 9.5
    */
   public static function printShowOngoingDialog() {
      // This dialog will be built dynamically on the front end
      echo '<div id="ongoing_dialog"></div>';
   }

   /**
    * Load the color configuration dialog
    *
    * @since 9.5
    */
   public static function printColorConfigDialog() {
      echo '<div id="color_config_dialog" class="impact-dialog">';
      echo "<table class='tab_cadre_fixe'>";

      // First row: depends color field
      echo "<tr>";
      echo "<td>";
      Html::showColorField("depends_color", []);
      echo "<label>&nbsp;" . __("Depends") . "</label>";
      echo "</td>";
      echo "</tr>";

      // Second row: impact color field
      echo "<tr>";
      echo "<td>";
      Html::showColorField("impact_color", []);
      echo "<label>&nbsp;" . __("Impact") . "</label>";
      echo "</td>";
      echo "</tr>";

      // Third row: impact and depends color field
      echo "<tr>";
      echo "<td>";
      Html::showColorField("impact_and_depends_color", []);
      echo "<label>&nbsp;" . __("Impact and depends") . "</label>";
      echo "</td>";
      echo "</tr>";

      echo "</table>";
      echo "</div>";
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
    * Export the dialogs defined in the backend
    *
    * @return string
    */
   public static function exportDialogs() {
      return json_encode([
         [
            'key'    => 'addNode',
            'id'     => "#add_node_dialog",
            'inputs' => [
               'itemType' => "select[name=item_type]",
               'itemID'   => "select[name=item_id]"
            ]
         ],
         [
            'key'    => 'configColor',
            'id'     => '#color_config_dialog',
            'inputs' => [
               'dependsColor'          => "input[name=depends_color]",
               'impactColor'           => "input[name=impact_color]",
               'impactAndDependsColor' => "input[name=impact_and_depends_color]",
            ]
         ],
         [
            'key' => "ongoingDialog",
            'id'  => "#ongoing_dialog"
         ],
         [
            'key'    => "editCompoundDialog",
            'id'     => "#edit_compound_dialog",
            'inputs' => [
               'name'  => "input[name=compound_name]",
               'color' => "input[name=compound_color]",
            ]
         ]
      ]);
   }

   /**
    * Export the toolbar defined in the backend
    *
    * @return string
    */
   public static function exportToolbar() {
      return json_encode([
         ['key'    => 'helpText',            'id' => "#help_text"],
         ['key'    => 'tools',               'id' => "#impact_tools"],
         ['key'    => 'save',                'id' => "#save_impact"],
         ['key'    => 'addNode',             'id' => "#add_node"],
         ['key'    => 'addEdge',             'id' => "#add_edge"],
         ['key'    => 'addCompound',         'id' => "#add_compound"],
         ['key'    => 'deleteElement',       'id' => "#delete_element"],
         ['key'    => 'export',              'id' => "#export_graph"],
         ['key'    => 'expandToolbar',       'id' => "#expand_toolbar"],
         ['key'    => 'toggleImpact',        'id' => "#toggle_impact"],
         ['key'    => 'toggleDepends',       'id' => "#toggle_depends"],
         ['key'    => 'colorPicker',         'id' => "#color_picker"],
         ['key'    => 'maxDepth',            'id' => "#max_depth"],
         ['key'    => 'maxDepthView',        'id' => "#max_depth_view"],
         ['key'    => 'toggleFullscreen',    'id' => "#toggle_fullscreen"],
      ]);
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
      self::printAddNodeDialog();
      self::printColorConfigDialog();
      self::printEditCompoundDialog();
      echo Html::script("js/impact.js");

      // Load backend values
      $locales   = self::getLocales();
      $default   = self::DEFAULT_COLOR;
      $forward   = self::IMPACT_COLOR;
      $backward  = self::DEPENDS_COLOR;
      $both      = self::IMPACT_AND_DEPENDS_COLOR;
      $start_node = self::getNodeID($item);
      $form      = "form[name=form_impact_network]";
      $dialogs   = self::exportDialogs();
      $toolbar   = self::exportToolbar();

      // Bind the backend values to the client and start the network
      echo  Html::scriptBlock("
         $(function() {
            GLPIImpact.prepareNetwork(
               $(\"#network_container\"),
               '$locales',
               {
                  default : '$default',
                  forward : '$forward',
                  backward: '$backward',
                  both    : '$both',
               },
               '$start_node',
               '$form',
               '$dialogs',
               '$toolbar'
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

   /**
    * Build the locales that will be used in the client side
    *
    * @return string json encoded locales array
    */
   public static function getLocales() {
      $locales = [
         'add'                  => __('Add'),
         'addDescription'       => __('Click in an empty space to place a new asset.'),
         'addEdge'              => __('Add Impact relation'),
         'addEdgeHelpText'      => __("Draw a line between two assets to add an impact relation"),
         'addNode'              => __('Add Asset'),
         'addNodeHelpText'      => __("Click anywhere to add a new asset"),
         'addCompoundHelpText'  => __("Draw a square containing the assets you wish to group"),
         'addCompoundTooltip'   => __("Create a new group"),
         'addEdgeTooltip'       => __("Add a new impact relation"),
         'addNodeTooltip'       => __("Add a new asset to the impact network"),
         'back'                 => __('Back'),
         'cancel'               => __('Cancel'),
         'changes'              => __("Changes"),
         'colorConfiguration'   => __("Colors"),
         'compoundProperties'   => __("Group properties..."),
         'compoundProperties+'  => __("Set name and/or color for this group"),
         'createEdgeError'      => __('Cannot link edges to a cluster.'),
         'del'                  => __('Delete selected'),
         'delete'               => __("Delete"),
         'delete+'              => __("Delete element"),
         'deleteClusterError'   => __('Clusters cannot be deleted.'),
         'deleteHelpText'       => __("Click on an element to remove it from the network"),
         'deleteTooltip'        => __("Delete an element from the impact network"),
         'downloadTooltip'      => __("Export the impact network"),
         'duplicateAsset'       => __('This asset already exists.'),
         'duplicateEdge'        => __("An identical link already exist between theses two asset."),
         'edgeDescription'      => __('Click on an asset and drag the link to another asset to connect them.'),
         'edit'                 => __('Edit'),
         'editEdge'             => __('Edit Impact relation'),
         'editEdgeDescription'  => __('Click on the control points and drag them to a asset to connect to it.'),
         'editGroup'            => __("Edit group"),
         'editNode'             => __('Edit Asset'),
         'editClusterError'     => __('Clusters cannot be edited.'),
         'expandToolbarTooltip' => __("Show more options ..."),
         'export'               => __("Export"),
         'goTo'                 => __("Go to"),
         'goTo+'                => __("Open this element in a new tab"),
         'incidents'            => __("Incidents"),
         'linkToSelf'           => __("Can't link an asset to itself."),
         'new'                  => __("Add asset"),
         'new+'                 => __("Add a new asset to the graph"),
         'newAsset'             => __("New asset"),
         'notEnoughItems'       => __("You need to select at least 2 assets to make a group"),
         'ongoingTickets'       => __("Ongoing tickets"),
         'problems'             => __("Problems"),
         'removeFromCompound'   => __("Remove from group"),
         'removeFromCompound+'  => __("Remove this asset from the group"),
         'requests'             => __("Requests"),
         'save'                 => __("Save"),
         'showDepends'          => __("Depends"),
         'showImpact'           => __("Impact"),
         'showColorsTooltip'    => __("Edit relation's color"),
         'showDependsTooltip'   => __("Toggle \"depends\" visibility"),
         'showImpactTooltip'    => __("Toggle \"impacted\" visibility"),
         'showOngoing'          => __("Show ongoing tickets"),
         'showOngoing+'         => __("Show ongoing tickets for this item"),
         'unexpectedError'      => __("Unexpected error."),
         'unsavedChanges'       => __("You have unsaved changes"),
         'workspaceSaved'       => __("No unsaved changes"),
      ];

      return addslashes(json_encode($locales));
   }
}
