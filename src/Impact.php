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

use Glpi\Application\View\TemplateRenderer;

/**
 * @since 9.5.0
 */
class Impact extends CommonGLPI
{
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

   // Config values
    const CONF_ENABLED = 'impact_enabled_itemtypes';

    public static function getTypeName($nb = 0)
    {
        return __('Impact analysis');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        global $DB;

       // Class of the current item
        $class = get_class($item);

       // Only enabled for CommonDBTM
        if (!is_a($item, "CommonDBTM", true)) {
            throw new \InvalidArgumentException(
                "Argument \$item ($class) must be a CommonDBTM."
            );
        }

        $is_enabled_asset = self::isEnabled($class);
        $is_itil_object = is_a($item, "CommonITILObject", true);

       // Check if itemtype is valid
        if (!$is_enabled_asset && !$is_itil_object) {
            throw new \InvalidArgumentException(
                "Argument \$item ($class) is not a valid target for impact analysis."
            );
        }

        if (
            !$_SESSION['glpishow_count_on_tabs']
            || !isset($item->fields['id'])
            || $is_itil_object
        ) {
           // Count is disabled in config OR no item loaded OR ITIL object -> no count
            $total = 0;
        } else if ($is_enabled_asset) {
           // If on an asset, get the number of its direct dependencies
            $total = count($DB->request([
                'FROM'  => ImpactRelation::getTable(),
                'WHERE' => [
                    'OR' => [
                        [
                     // Source item is our item
                            'itemtype_source' => get_class($item),
                            'items_id_source' => $item->fields['id'],
                        ],
                        [
                     // Impacted item is our item AND source item is enabled
                            'itemtype_impacted' => get_class($item),
                            'items_id_impacted' => $item->fields['id'],
                            'itemtype_source'   => self::getEnabledItemtypes()
                        ]
                    ]
                ]
            ]));
        }

        return self::createTabEntry(__("Impact analysis"), $total);
    }

    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
       // Impact analysis should not be available outside of central
        if (Session::getCurrentInterface() !== "central") {
            return false;
        }

        $class = get_class($item);

       // Only enabled for CommonDBTM
        if (!is_a($item, "CommonDBTM")) {
            throw new \InvalidArgumentException(
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
            $items_data = [];
            foreach ($linked_items as $itemtype => $linked_item_ids) {
                $class = $itemtype;
                if (self::isEnabled($class)) {
                    $item = new $class();
                    foreach ($linked_item_ids as $linked_item_id) {
                        if (!$item->getFromDB($linked_item_id)) {
                             continue;
                        }
                        $items_data[] = [
                            'itemtype' => $itemtype,
                            'items_id' => $linked_item_id,
                            'name'     => $item->getNameID(),
                        ];
                    }
                }
            }

           // No valid linked item were found, tab shouldn't be visible
            if (empty($items_data)) {
                return false;
            }

            self::printAssetSelectionForm($items_data);
        }

       // Check is the impact analysis is enabled for $class
        if (!self::isEnabled($class)) {
            return false;
        }

       // Build graph and params
        $graph = self::buildGraph($item);
        $params = self::prepareParams($item);
        $readonly = !$item->can($item->fields['id'], UPDATE);

       // Print header
        self::printHeader(self::makeDataForCytoscape($graph), $params, $readonly);

       // Displays views
        self::displayGraphView($item);
        self::displayListView($item, $graph, true);

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
        array $graph,
        bool $scripts = false
    ) {
        global $CFG_GLPI;

        $impact_item = ImpactItem::findForItem($item);
        $impact_context = ImpactContext::findForImpactItem($impact_item);

        if (!$impact_context) {
            $max_depth = self::DEFAULT_DEPTH;
        } else {
            $max_depth = $impact_context->fields['max_depth'];
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
            $data = self::buildListData($graph, $direction, $item, $max_depth);

            if (!count($data)) {
                continue;
            }

            $has_impact = true;
            echo '<table class="tab_cadre_fixehov impact-list-group">';

           // Header
            echo '<thead>';
            echo '<tr class="noHover">';
            echo '<th class="impact-list-header" colspan="6" width="90%"><h3>' . $label . '';
            echo '<i class="fas fa-2x fa-caret-down impact-toggle-subitems-master impact-pointer"></i></h3></th>';
            echo '</tr>';
            echo '<tr class="noHover">';
            echo '<th>' . _n('Item', 'Items', 1) . '</th>';
            echo '<th>' . __('Relation') . '</th>';
            echo '<th>' . Ticket::getTypeName(Session::getPluralNumber()) . '</th>';
            echo '<th>' . Problem::getTypeName(Session::getPluralNumber()) . '</th>';
            echo '<th>' . Change::getTypeName(Session::getPluralNumber()) . '</th>';
            echo '<th width="50px"></th>';
            echo '</tr>';
            echo '</thead>';

            foreach ($data as $itemtype => $items) {
                echo '<tbody>';

               // Subheader
                echo '<tr class="tab_bg_1">';
                echo '<td class="left subheader impact-left" colspan="6">';
                $total = count($items);
                echo '<a>' . $itemtype::getTypeName() . '</a>' . ' (' . $total . ')';
                echo '<i class="fas fa-2x fa-caret-down impact-toggle-subitems impact-pointer"></i>';
                echo '</td>';
                echo '</tr>';

                foreach ($items as $itemtype_item) {
                   // Content: one row per item
                    echo '<tr class=tab_bg_1><div></div>';
                    echo '<td class="impact-left" width="15%">';
                    echo '<div><a target="_blank" href="' .
                    $itemtype_item['stored']->getLinkURL() . '">' .
                    $itemtype_item['stored']->getFriendlyName() . '</a></div>';
                    echo '</td>';
                    echo '<td width="40%"><div>';

                    $path = [];
                    foreach ($itemtype_item['node']['path'] as $node) {
                        if ($node['id'] == $start_node_id) {
                            $path[] = '<b>' . $node['label'] . '</b>';
                        } else {
                            $path[] = $node['label'];
                        }
                    }
                    $separator = '<i class="fas fa-angle-right"></i>';
                    echo implode(" $separator ", $path);

                    echo '</div></td>';

                    self::displayListNumber(
                        $itemtype_item['node']['ITILObjects']['incidents'],
                        Ticket::class,
                        $itemtype_item['node']['id']
                    );
                    self::displayListNumber(
                        $itemtype_item['node']['ITILObjects']['problems'],
                        Problem::class,
                        $itemtype_item['node']['id']
                    );
                    self::displayListNumber(
                        $itemtype_item['node']['ITILObjects']['changes'],
                        Change::class,
                        $itemtype_item['node']['id']
                    );

                    echo '<td class="center"><div></div></td>';
                    echo '</tr>';
                }

                echo '</tbody>';
            }

            echo '</table>';
        }

        if (!$has_impact) {
            echo '<p>' . __("This asset doesn't have any dependencies.") . '</p>';
        }

        echo '</div>';

        $can_update = $item->can($item->fields['id'], UPDATE);

       // Toolbar
        echo '<div class="impact-list-toolbar">';
        if ($has_impact) {
            echo '<a target="_blank" href="' . $CFG_GLPI['root_doc'] . '/front/impactcsv.php?itemtype=' . $impact_item->fields['itemtype'] . '&items_id=' . $impact_item->fields['items_id'] . '">';
            echo '<i class="fas fa-download impact-pointer impact-list-tools" title="' . __('Export to csv') . '"></i>';
            echo '</a>';
        }
        if ($can_update && $impact_context) {
            echo '<i id="impact-list-settings" class="fas fa-cog impact-pointer impact-list-tools" title="' . __('Settings') . '"></i>';
        }
        echo '</div>';

       // Settings dialog
        $setting_dialog = "";
        if ($can_update && $impact_context) {
            $rand = mt_rand();

            $setting_dialog .= '<form id="list_depth_form" action="' . $CFG_GLPI['root_doc'] . '/front/impactitem.form.php" method="POST">';
            $setting_dialog .= '<table class="tab_cadre_fixe">';
            $setting_dialog .= '<tr>';
            $setting_dialog .= '<td><label for="impact_max_depth_' . $rand . '">' . __("Max depth") . '</label></td>';
            $setting_dialog .= '<td>' . Html::input("max_depth", [
                'id'    => "impact_max_depth_$rand",
                'value' => $max_depth >= self::MAX_DEPTH ? '' : $max_depth,
            ]) . '</td>';
            $setting_dialog .= '</tr>';
            $setting_dialog .= '<tr>';
            $setting_dialog .= '<td><label for="check_no_limit_' . $rand . '">' . __("No limit") . '</label></td>';
            $setting_dialog .= '<td>' . Html::getCheckbox([
                'name'    => 'no_limit',
                'id'      => "check_no_limit_$rand",
                'checked' => $max_depth >= self::MAX_DEPTH,
            ]) . '</td>';
            $setting_dialog .= '</tr>';
            $setting_dialog .= '</table>';
            $setting_dialog .= Html::input('id', [
                'type'  => "hidden",
                'value' => $impact_context->fields['id'],
            ]);
            $setting_dialog .=  Html::submit(__('Save'), ['name' => 'update']);
            $setting_dialog .= Html::closeForm(false);
            $setting_dialog = json_encode($setting_dialog);
        }

        echo '</div>';

       // Stop here if we do not need to generate scripts
        if (!$scripts) {
            return;
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

         $(document).on("click", ".impact-toggle-subitems", function(e) {
            if ($(e.target).hasClass("fa-caret-up")) {
               impactListDown($(e.target));
            } else {
               impactListUp($(e.target));
            }
         });

         $(document).on("click", ".impact-toggle-subitems-master", function(e) {
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
                  showGraphView();
               },
            });
         });
      ');

        if ($can_update) {
           // Handle settings actions
            echo Html::scriptBlock('
            $("#impact-list-settings").click(function() {
               glpi_html_dialog({
                  title: __("Settings"),
                  body: ' . ($setting_dialog || '{}') . ',
               });
            });

            $(document).on("submit","#list_depth_form", function() {
               if ($("input[name=\'no_limit\']:checked").length > 0) {
                  $("input[name=\'max_depth\']").val(' . self::NO_DEPTH_LIMIT . ');
               }
            });
         ');
        }
    }

    /**
     * Display "number" cell in list view
     * The cell is empty if no itilobjets are found, else it contains the
     * number of iitilobjets found, use the highest priority as it's background
     * color and is a link to matching search result
     *
     * @param array   $itil_objects
     * @param string  $type
     * @param string  $node_id
     */
    private static function displayListNumber($itil_objects, $type, $node_id)
    {
        $user = new User();
        $user->getFromDB(Session::getLoginUserID());
        $user->computePreferences();

        $count = count($itil_objects) ?: "";
        $extra = "";
        $node_details = explode(self::NODE_ID_DELIMITER, $node_id);

        if ($count) {
            $priority = 1;
            $id = "impact_list_itilcount_" . mt_rand();
            $link = "";

            switch ($type) {
                case Ticket::class:
                    $link = Ticket::getSearchURL();
                    $link .= "?is_deleted=0&as_map=0&search=Search&itemtype=Ticket";
                    $link .= "&criteria[0][link]=AND&criteria[0][field]=13&criteria[0][searchtype]=contains&criteria[0][value]=" . $node_details[1];
                    $link .= "&criteria[1][link]=AND&criteria[1][field]=131&criteria[1][searchtype]=equals&criteria[1][value]=" . $node_details[0];
                    $link .= "&criteria[2][link]=AND&criteria[2][field]=14&criteria[2][searchtype]=equals&criteria[2][value]=1";
                    $link .= "&criteria[3][link]=AND&criteria[3][field]=12&criteria[3][searchtype]=equals&criteria[3][value]=notold";
                    break;

                case Problem::class:
                    $link = Problem::getSearchURL();
                    $link .= "?is_deleted=0&as_map=0&search=Search&itemtype=Problem";
                    $link .= "&criteria[0][link]=AND&criteria[0][field]=13&criteria[0][searchtype]=contains&criteria[0][value]=" . $node_details[1];
                    $link .= "&criteria[1][link]=AND&criteria[1][field]=131&criteria[1][searchtype]=equals&criteria[1][value]=" . $node_details[0];
                    $link .= "&criteria[3][link]=AND&criteria[3][field]=12&criteria[3][searchtype]=equals&criteria[3][value]=notold";
                    break;

                case Change::class:
                    $link = Change::getSearchURL();
                    $link .= "?is_deleted=0&as_map=0&search=Search&itemtype=Change";
                    $link .= "&criteria[0][link]=AND&criteria[0][field]=13&criteria[0][searchtype]=contains&criteria[0][value]=" . $node_details[1];
                    $link .= "&criteria[1][link]=AND&criteria[1][field]=131&criteria[1][searchtype]=equals&criteria[1][value]=" . $node_details[0];
                    $link .= "&criteria[3][link]=AND&criteria[3][field]=12&criteria[3][searchtype]=equals&criteria[3][value]=notold";
                    break;
            }

           // Compute max priority
            foreach ($itil_objects as $itil_object) {
                if ($priority < $itil_object['priority']) {
                    $priority = $itil_object['priority'];
                }
            }
            $extra = 'id="' . $id . '" style="background-color:' .  $user->fields["priority_$priority"] . '; cursor:pointer;"';

            echo Html::scriptBlock('
            $(document).on("click", "#' . $id . '", function(e) {
               window.open("' . $link . '");
            });
         ');
        }

        echo '<td class="center" ' . $extra . '><div>' . $count . '</div></td>';
    }

    /**
     * Build the data used to represent the impact graph as a semi-flat list
     *
     * @param array      $graph        array containing the graph nodes and egdes
     * @param int        $direction    should the list be build for item that are
     *                                 impacted by $item or that impact $item ?
     * @param CommonDBTM $item         starting point of the graph
     * @param int        $max_depth    max depth from context
     *
     * @return array
     */
    public static function buildListData(
        array $graph,
        int $direction,
        CommonDBTM $item,
        int $max_depth
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
                if (count($path) - 1 <= $max_depth) {
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
            $itemtype_item = new $itemtype();
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
    public static function filterGraph(array $graph, int $direction)
    {
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
    public static function bfs(array $graph, array $a, array $b, int $direction)
    {
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
        echo "<a id='sviewlist' href='#list'><i class='pointer ti ti-list' title='" . __('View as list') . "'></i></a>";
        echo "<a id='sviewgraph' href='#graph'><i class='pointer ti ti-hierarchy-2' title='" . __('View graphical representation') . "'></i></a>";
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
            $('#save_impact').removeClass('clean');
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
     * Load the cytoscape library
     *
     * @since 9.5
     */
    public static function loadLibs()
    {
        echo Html::css('public/lib/cytoscape.css');
        echo Html::script("public/lib/cytoscape.js");
    }

    /**
     * Print the asset selection form used in the impact tab of ITIL objects
     *
     * @param array $items
     *    Each array should contains "itemtype", "items_id" and "name".
     *
     * @since 9.5
     */
    public static function printAssetSelectionForm(array $items)
    {
        global $CFG_GLPI;

       // Dropdown values
        $values = [];

       // Add a value in the dropdown for each items, grouped by type
        foreach ($items as $item) {
            if (self::isEnabled($item['itemtype'])) {
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
                  url: "' . $CFG_GLPI['root_doc'] . '/ajax/impact.php",
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
        global $DB;

       // Check if this type is enabled in config
        if (!self::isEnabled($itemtype)) {
            throw new \InvalidArgumentException(
                "itemtype ($itemtype) must be enabled in config"
            );
        }

       // Check class exist and is a child of CommonDBTM
        if (!is_subclass_of($itemtype, "CommonDBTM", true)) {
            throw new \InvalidArgumentException(
                "itemtype ($itemtype) must be a valid child of CommonDBTM"
            );
        }

       // Return empty result if the user doesn't have READ rights
        if (!Session::haveRight($itemtype::$rightname, READ)) {
            return [
                "items" => [],
                "total" => 0
            ];
        }

       // This array can't be empty since we will use it in the NOT IN part of the reqeust
        if (!count($used)) {
            $used[] = -1;
        }

       // Search for items
        $table = $itemtype::getTable();
        $base_request = [
            'FROM'   => $table,
            'WHERE'  => [
                'NOT' => [
                    "$table.id" => $used
                ],
            ],
        ];

       // Add friendly name search criteria
        $base_request['WHERE'] = array_merge(
            $base_request['WHERE'],
            $itemtype::getFriendlyNameSearchCriteria($filter)
        );

        if (is_subclass_of($itemtype, "ExtraVisibilityCriteria", true)) {
            $base_request = array_merge_recursive(
                $base_request,
                $itemtype::getVisibilityCriteria()
            );
        }

        $item = new $itemtype();
        if ($item->isEntityAssign()) {
            $base_request['WHERE'] = array_merge_recursive(
                $base_request['WHERE'],
                getEntitiesRestrictCriteria($itemtype::getTable())
            );
        }

        if ($item->mayBeDeleted()) {
            $base_request['WHERE']["$table.is_deleted"] = 0;
        }

        if ($item->mayBeTemplate()) {
            $base_request['WHERE']["$table.is_template"] = 0;
        }

        $select = [
            'SELECT' => ["$table.id", $itemtype::getFriendlyNameFields()],
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
            "total" => iterator_to_array($total, false)[0]['total'],
        ];
    }

    /**
     * Load the impact network container
     *
     * @since 9.5
     */
    public static function printImpactNetworkContainer()
    {
        global $CFG_GLPI;

        $action = $CFG_GLPI['root_doc'] . '/ajax/impact.php';
        $formName = "form_impact_network";

        echo "<form name=\"$formName\" action=\"$action\" method=\"post\" class='no-track'>";
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
        $itemtypes = $CFG_GLPI["impact_asset_types"];
       // Sort by translated itemtypes
        uksort($itemtypes, function ($a, $b) {
            return strcasecmp($a::getTypeName(), $b::getTypeName());
        });
        foreach ($itemtypes as $itemtype => $icon) {
           // Do not display this itemtype if the user doesn't have READ rights
            if (!Session::haveRight($itemtype::$rightname, READ)) {
                continue;
            }

           // Skip if not enabled
            if (!self::isEnabled($itemtype)) {
                continue;
            }

            $icon = self::checkIcon($icon);

            echo '<div class="impact-side-filter-itemtypes-item">';
            echo '<h4><img class="impact-side-icon" src="' . $CFG_GLPI['root_doc'] . '/' . $icon . '" title="' . $itemtype::getTypeName() . '" data-itemtype="' . $itemtype . '">';
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
        echo '<p>' . __("No results") . '</p>';
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
        echo Html::getCheckbox([
            'id'      => "toggle_impact",
            'name'    => "toggle_impact",
            'checked' => "true",
        ]);
        echo '<span class="impact-checkbox-label">' . __("Show impact") . '</span>';
        echo '</div>';

        echo '<div class="impact-side-settings-item">';
        echo Html::getCheckbox([
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
        echo '<li id="save_impact" title="' . __("Save") . '"><i class="fa-fw far fa-save"></i></li>';
        echo '<li id="impact_undo" class="impact-disabled" title="' . __("Undo") . '"><i class="fa-fw fas fa-undo"></i></li>';
        echo '<li id="impact_redo" class="impact-disabled" title="' . __("Redo") . '"><i class="fa-fw fas fa-redo"></i></li>';
        echo '<li class="impact-separator"></li>';
        echo '<li id="add_node" title="' . __("Add asset") . '"><i class="fa-fw ti ti-plus"></i></li>';
        echo '<li id="add_edge" title="' . __("Add relation") . '"><i class="fa-fw ti ti-line"></i></li>';
        echo '<li id="add_compound" title="' . __("Add group") . '"><i class="far fa-fw fa-object-group"></i></li>';
        echo '<li id="delete_element" title="' . __("Delete element") . '"><i class="fa-fw ti ti-trash"></i></li>';
        echo '<li class="impact-separator"></li>';
        echo '<li id="export_graph" title="' . __("Download") . '"><i class="fa-fw ti ti-download"></i></li>';
        echo '<li id="toggle_fullscreen" title="' . __("Fullscreen") . '"><i class="fa-fw ti ti-maximize"></i></li>';
        echo '<li id="impact_settings" title="' . __("Settings") . '"><i class="fa-fw ti ti-adjustments"></i></li>';
        echo '</ul>';
        echo '<span class="impact-side-toggle"><i class="fa-fw ti ti-chevron-left"></i></span>';
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
    public static function buildGraph(CommonDBTM $item)
    {
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

       // Add special flag to start node
        $nodes[self::getNodeID($item)]['start'] = 1;

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
                throw new \InvalidArgumentException(
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
           // Do not explore disabled itemtypes
            if (!self::isEnabled($related_item['itemtype_' . $source])) {
                continue;
            }

           // Add the related node
            if (!($related_node = getItemForItemtype($related_item['itemtype_' . $source]))) {
                continue;
            }
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
     * Check if the icon path is valid, if not return a fallback path
     *
     * @param string $icon_path
     * @return string
     */
    private static function checkIcon(string $icon_path): string
    {
       // Special case for images returned dynamicly
        if (strpos($icon_path, ".php") !== false) {
            return $icon_path;
        }

       // Check if icon exist on the filesystem
        $file_path = GLPI_ROOT . "/$icon_path";
        if (file_exists($file_path) && is_file($file_path)) {
            return $icon_path;
        }

       // Fallback "default" icon
        return "pics/impact/default.png";
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
    private static function addNode(array &$nodes, CommonDBTM $item)
    {
        global $CFG_GLPI;

       // Check if the node already exist
        $key = self::getNodeID($item);
        if (isset($nodes[$key])) {
            return false;
        }

       // Get web path to the image matching the itemtype from config
        $image_name = $CFG_GLPI["impact_asset_types"][get_class($item)] ?? "";
        $image_name = self::checkIcon($image_name);

       // Define basic data of the new node
        $new_node = [
            'id'          => $key,
            'label'       => $item->getFriendlyName(),
            'image'       => $CFG_GLPI['root_doc'] . "/$image_name",
            'ITILObjects' => $item->getITILTickets(true),
        ];

       // Only set GOTO link if the user have READ rights
        if ($item::canView()) {
            $new_node['link'] = $item->getLinkURL();
        }

        // Set incident badge if needed
        $nb_incidents = count($new_node['ITILObjects']['incidents']);
        $nb_problems = count($new_node['ITILObjects']['problems']);
        if ($nb_incidents + $nb_problems > 0) {
            $priority = 0;
            foreach ($new_node['ITILObjects']['incidents'] as $incident) {
                if ($priority < $incident['priority']) {
                    $priority = $incident['priority'];
                }
            }
            foreach ($new_node['ITILObjects']['problems'] as $problem) {
                if ($priority < $problem['priority']) {
                    $priority = $problem['priority'];
                }
            }

            if ($nb_problems && !$nb_incidents) {
                // If at least one problems and zero incidents, link to problems search
                $target = Problem::getSearchURL() . "?is_deleted=0&as_map=0&search=Search&itemtype=Problem";
            } else {
                // Link to tickets search
                $target = Ticket::getSearchURL() . "?is_deleted=0&as_map=0&search=Search&itemtype=Ticket";
            }

            $user = new User();
            $user->getFromDB(Session::getLoginUserID());
            $user->computePreferences();
            $new_node['badge'] = [
                'color'  => $user->fields["priority_$priority"],
                'count'  => $nb_incidents + $nb_problems,
                'target' => $target,
            ];
        }

        // Alter the label if we found some linked ITILObjects
        $itil_tickets_count = $new_node['ITILObjects']['count'];
        if ($itil_tickets_count > 0) {
            $new_node['label'] .= " ($itil_tickets_count)";
            $new_node['hasITILObjects'] = 1;
        }

       // Load or create a new ImpactItem object
        $impact_item = ImpactItem::findForItem($item);

       // Load node position and parent
        $new_node['impactitem_id'] = $impact_item->fields['id'];
        $new_node['parent']        = $impact_item->fields['parent_id'];

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
     * @return void
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
                throw new \InvalidArgumentException(
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
    public static function prepareParams(CommonDBTM $item)
    {
        $impact_item = ImpactItem::findForItem($item);

        $params = array_intersect_key($impact_item->fields, [
            'parent_id'         => 1,
            'impactcontexts_id' => 1,
            'is_slave'          => 1,
        ]);

       // Load context if exist
        if ($params['impactcontexts_id']) {
            $impact_context = ImpactContext::findForImpactItem($impact_item);

            if ($impact_context) {
                $params = $params + array_intersect_key(
                    $impact_context->fields,
                    [
                        'positions'                => 1,
                        'zoom'                     => 1,
                        'pan_x'                    => 1,
                        'pan_y'                    => 1,
                        'impact_color'             => 1,
                        'depends_color'            => 1,
                        'impact_and_depends_color' => 1,
                        'show_depends'             => 1,
                        'show_impact'              => 1,
                        'max_depth'                => 1,
                    ]
                );
            }
        }

        return json_encode($params);
    }

    /**
     * Convert the php array reprensenting the graph into the format required by
     * the Cytoscape library
     *
     * @param array $graph
     *
     * @return string json data
     */
    public static function makeDataForCytoscape(array $graph)
    {
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
    public static function printShowOngoingDialog()
    {
       // This dialog will be built dynamically by the front end
        TemplateRenderer::getInstance()->display('impact/ongoing_modal.html.twig');
    }

    /**
     * Load the "edit compound" dialog
     *
     * @since 9.5
     */
    public static function printEditCompoundDialog()
    {
        TemplateRenderer::getInstance()->display('impact/edit_compound_modal.html.twig');
    }

    /**
     * Prepare the impact network
     *
     * @since 9.5
     *
     * @param CommonDBTM $item The specified item
     */
    public static function prepareImpactNetwork(CommonDBTM $item)
    {
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
    public static function assetExist(string $itemtype, string $items_id)
    {
        try {
           // Check this asset type is enabled
            if (!self::isEnabled($itemtype)) {
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
        } catch (\ReflectionException $e) {
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
    public static function getNodeID(CommonDBTM $item)
    {
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
                throw new \InvalidArgumentException(
                    "Invalid value for argument \$direction ($direction)."
                );
        }
    }


    /**
     * Clean impact records for a given item that has been purged form the db
     *
     * @param CommonDBTM $item The item being purged
     */
    public static function clean(\CommonDBTM $item)
    {
        global $DB;

       // Skip if not a valid impact type
        if (!self::isEnabled($item::getType())) {
            return;
        }

       // Remove each relations
        $DB->delete(\ImpactRelation::getTable(), [
            'OR' => [
                [
                    'itemtype_source' => get_class($item),
                    'items_id_source' => $item->fields['id']
                ],
                [
                    'itemtype_impacted' => get_class($item),
                    'items_id_impacted' => $item->fields['id']
                ],
            ]
        ]);

       // Remove associated ImpactItem
        $impact_item = ImpactItem::findForItem($item, false);
        if (!$impact_item) {
           // Stop here if no impactitem, nothing more to delete
            return;
        }

        $impact_item->delete($impact_item->fields);

       // Remove impact context if defined and not a slave, update others
       // contexts if they are slave to us
        if (
            $impact_item->fields['impactcontexts_id'] != 0
            && $impact_item->fields['is_slave'] != 0
        ) {
            $DB->update(ImpactItem::getTable(), [
                'impactcontexts_id' => 0,
            ], [
                'impactcontexts_id' => $impact_item->fields['impactcontexts_id'],
            ]);

            $DB->delete(ImpactContext::getTable(), [
                'id' => $impact_item->fields['impactcontexts_id']
            ]);
        }

       // Delete group if less than two children remaining
        if ($impact_item->fields['parent_id'] != 0) {
            $count = countElementsInTable(ImpactItem::getTable(), [
                'parent_id' => $impact_item->fields['parent_id']
            ]);

            if ($count < 2) {
                $DB->update(ImpactItem::getTable(), [
                    'parent_id' => 0,
                ], [
                    'parent_id' => $impact_item->fields['parent_id']
                ]);

                 $DB->delete(ImpactCompound::getTable(), [
                     'id' => $impact_item->fields['parent_id']
                 ]);
            }
        }
    }

    /**
     * Check if the given itemtype is enabled in impact config
     *
     * @param string $itemtype
     * @return bool
     */
    public static function isEnabled(string $itemtype): bool
    {
        return in_array($itemtype, self::getEnabledItemtypes());
    }

    /**
     * Return enabled itemtypes
     *
     * @return array
     */
    public static function getEnabledItemtypes(): array
    {
       // Get configured values
        $conf = Config::getConfigurationValues('core');

        if (!isset($conf[self::CONF_ENABLED])) {
            return [];
        }

        $enabled = importArrayFromDB($conf[self::CONF_ENABLED]);

       // Remove any forbidden values
        return array_filter($enabled, function ($itemtype) {
            global $CFG_GLPI;

            return isset($CFG_GLPI['impact_asset_types'][$itemtype]);
        });
    }

    /**
     * Return default itemtypes
     *
     * @return array
     */
    public static function getDefaultItemtypes()
    {
        global $CFG_GLPI;

        $values = $CFG_GLPI["default_impact_asset_types"];
        return array_keys($values);
    }

    /**
     * Print the impact config tab
     */
    public static function showConfigForm()
    {
        global $CFG_GLPI;

       // Form head
        $action = Toolbox::getItemTypeFormURL(Config::getType());
        echo "<form name='form' action='$action' method='post'>";

       // Table head
        echo '<table class="tab_cadre_fixe">';
        echo '<tr><th colspan="2">' . __('Impact analysis configuration') . '</th></tr>';

       // First row: enabled itemtypes
        $input_name = self::CONF_ENABLED;
        $values = $CFG_GLPI["impact_asset_types"];
        foreach ($values as $itemtype => $icon) {
            $values[$itemtype] = $itemtype::getTypeName();
        }
        echo '<tr class="tab_bg_2">';

        echo '<td width="40%">';
        echo "<label for='$input_name'>";
        echo __('Enabled itemtypes');
        echo '</label>';
        echo '</td>';

        $core_config = Config::getConfigurationValues("core");
        $db_values = importArrayFromDB($core_config[self::CONF_ENABLED]);
        echo '<td>';
        Dropdown::showFromArray($input_name, $values, [
            'multiple' => true,
            'values'   => $db_values
        ]);
        echo "</td>";

        echo "</tr>";

        echo '</table>';

       // Submit button
        echo '<div style="text-align:center">';
        echo Html::submit(__('Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
        echo '</div>';

        Html::closeForm();
    }
}
