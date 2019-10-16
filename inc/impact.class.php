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

      // Show graph if the impact analysis is enable for $class
      if (isset($CFG_GLPI['impact_asset_types'][$class])) {
         self::loadLibs();
         self::prepareImpactNetwork($item);
         self::buildNetwork($item);
      }

      return true;
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

      // First row: header
      echo "<tr class='tab_bg_2'>";
      echo "<th>" . __('Impact graph') . "</th>";
      echo "</tr>";

      // Second row: network graph
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
    * @param array $nodes  Nodes of the graph
    * @param array $edges  Edges of the graph
    */
   public static function buildNetwork(CommonDBTM $item) {
      // Build the graph
      $graph = self::makeDataForCytoscape(Impact::buildGraph($item));
      $params = self::prepareParams($item);
      $readonly = !Session::haveRight(get_class($item)::$rightname, UPDATE);

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
         ['key'    => 'helpText',      'id' => "#help_text"],
         ['key'    => 'tools',         'id' => "#impact_tools"],
         ['key'    => 'save',          'id' => "#save_impact"],
         ['key'    => 'addNode',       'id' => "#add_node"],
         ['key'    => 'addEdge',       'id' => "#add_edge"],
         ['key'    => 'addCompound',   'id' => "#add_compound"],
         ['key'    => 'deleteElement', 'id' => "#delete_element"],
         ['key'    => 'export',        'id' => "#export_graph"],
         ['key'    => 'expandToolbar', 'id' => "#expand_toolbar"],
         ['key'    => 'toggleImpact',  'id' => "#toggle_impact"],
         ['key'    => 'toggleDepends', 'id' => "#toggle_depends"],
         ['key'    => 'colorPicker',   'id' => "#color_picker"],
         ['key'    => 'maxDepth',      'id' => "#max_depth"],
         ['key'    => 'maxDepthView',  'id' => "#max_depth_view"],
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
      return get_class($item) . "::" . $item->fields['id'];
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
            return self::getNodeID($itemA) . "->" . self::getNodeID($itemB);

         case self::DIRECTION_BACKWARD:
            return self::getNodeID($itemB) . "->" . self::getNodeID($itemA);

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
