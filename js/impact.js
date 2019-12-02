/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2019 Teclib' and contributors.
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


// Load cytoscape
var cytoscape = window.cytoscape;

// Needed for JS lint validation
/* global _ */

var GLPIImpact = {

   // Constants to represent nodes and edges
   NODE: 1,
   EDGE: 2,

   // Constants for graph direction (bitmask)
   DEFAULT : 0,   // 0b00
   FORWARD : 1,   // 0b01
   BACKWARD: 2,   // 0b10
   BOTH    : 3,   // 0b11

   // Constants for graph edition mode
   EDITION_DEFAULT     : 1,
   EDITION_ADD_NODE    : 2,
   EDITION_ADD_EDGE    : 3,
   EDITION_DELETE      : 4,
   EDITION_ADD_COMPOUND: 5,
   EDITION_SETTINGS    : 6,

   // Constants for ID separator
   NODE_ID_SEPERATOR: "::",
   EDGE_ID_SEPERATOR: "->",

   // Constants for delta action
   DELTA_ACTION_ADD   : 1,
   DELTA_ACTION_UPDATE: 2,
   DELTA_ACTION_DELETE: 3,

   // Constans for depth
   DEFAULT_DEPTH: 5,
   MAX_DEPTH: 10,
   NO_DEPTH_LIMIT: 10000,

   // Store the initial state of the graph
   initialState: null,

   // Store the visibility settings of the different direction of the graph
   directionVisibility: {},

   // Store defaults colors for edge
   defaultColors: {},

   // Store color for egdes
   edgeColors: {},

   // Cytoscape instance
   cy: null,

   // The impact network container
   impactContainer: null,

   // The graph edition mode
   editionMode: null,

   // Start node of the graph
   startNode: null,

   // Maximum depth of the graph (default 5)
   maxDepth: this.DEFAULT_DEPTH,

   // Is the graph readonly ?
   readonly: true,

   // Fullscreen
   fullscreen: false,

   // Used in add assets sidebar
   selectedItemtype: "",
   addAssetPage: 0,

   // Store selectors
   selectors: {
      // Dialogs
      ongoingDialog     : "#ongoing_dialog",
      editCompoundDialog: "#edit_compound_dialog",

      // Inputs
      compoundName         : "input[name=compound_name]",
      compoundColor        : "input[name=compound_color]",
      dependsColor         : "input[name=depends_color]",
      impactColor          : "input[name=impact_color]",
      impactAndDependsColor: "input[name=impact_and_depends_color]",
      toggleImpact         : "#toggle_impact",
      toggleDepends        : "#toggle_depends",
      maxDepth             : "#max_depth",
      maxDepthView         : "#max_depth_view",

      // Toolbar
      helpText        : "#help_text",
      save            : "#save_impact",
      addNode         : "#add_node",
      addEdge         : "#add_edge",
      addCompound     : "#add_compound",
      deleteElement   : "#delete_element",
      export          : "#export_graph",
      expandToolbar   : "#expand_toolbar",
      toggleFullscreen: "#toggle_fullscreen",
      impactSettings  : "#impact_settings",
      sideToggle      : ".impact-side-toggle",
      sideToggleIcon  : ".impact-side-toggle i",

      // Sidebar content
      side                    : ".impact-side",
      sidePanel               : ".impact-side-panel",
      sideAddNode             : ".impact-side-add-node",
      sideSettings            : ".impact-side-settings",
      sideSearch              : ".impact-side-search",
      sideSearchSpinner       : ".impact-side-search-spinner",
      sideSearchNoResults     : ".impact-side-search-no-results",
      sideSearchMore          : ".impact-side-search-more",
      sideSearchResults       : ".impact-side-search-results",
      sideSearchSelectItemtype: ".impact-side-select-itemtype",
      sideSearchFilterItemtype: "#impact-side-filter-itemtypes",
      sideFilterAssets        : "#impact-side-filter-assets",
      sideFilterItem          : ".impact-side-filter-itemtypes-item",

      // Others
      form       : "form[name=form_impact_network]",
      dropPreview: ".impact-drop-preview",
   },

   // Data that needs to be stored/shared between events
   eventData: {
      addEdgeStart : null,   // Store starting node of a new edge
      tmpEles      : null,   // Temporary collection used when adding an edge
      lastClick    : null,   // Store last click timestamp
      boxSelected  : [],
      grabNodeStart: null,
      boundingBox  : null,
   },

   /**
    * Get network style
    *
    * @returns {Array}
    */
   getNetworkStyle: function() {
      return [
         {
            selector: 'core',
            style: {
               'selection-box-opacity'     : '0.2',
               'selection-box-border-width': '0',
               'selection-box-color'       : '#24acdf'
            }
         },
         {
            selector: 'node:parent',
            style: {
               'padding'           : '30px',
               'shape'             : 'roundrectangle',
               'border-width'      : '1px',
               'background-opacity': '0.5',
               'font-size'         : '1.1em',
               'background-color'  : '#d2d2d2',
               'text-margin-y'     : '20px',
               'text-opacity'      : 0.7,
            }
         },
         {
            selector: 'node:parent[label]',
            style: {
               'label': 'data(label)',
            }
         },
         {
            selector: 'node:parent[color]',
            style: {
               'border-color'      : 'data(color)',
               'background-color'  : 'data(color)',
            }
         },
         {
            selector: ':selected',
            style: {
               'overlay-opacity': 0.2,
            }
         },
         {
            selector: '[todelete=1]:selected',
            style: {
               'overlay-opacity': 0.2,
               'overlay-color': 'red',
            }
         },
         {
            selector: 'node[image]',
            style: {
               'label'             : 'data(label)',
               'shape'             : 'rectangle',
               'background-color'  : '#666',
               'background-image'  : 'data(image)',
               'background-fit'    : 'contain',
               'background-opacity': '0',
               'font-size'         : '1em',
               'text-opacity'      : 0.7,
            }
         },
         {
            selector: '[hidden=1], [depth > ' + this.maxDepth + ']',
            style: {
               'opacity': '0',
            }
         },
         {
            selector: '[id="tmp_node"]',
            style: {
               'opacity': '0',
            }
         },
         {
            selector: 'edge',
            style: {
               'width'             : 1,
               'line-color'        : this.edgeColors[0],
               'target-arrow-color': this.edgeColors[0],
               'target-arrow-shape': 'triangle',
               'arrow-scale'       : 0.7,
               'curve-style'       : 'bezier'
            }
         },
         {
            selector: '[flag=' + GLPIImpact.FORWARD + ']',
            style: {
               'line-color'        : this.edgeColors[GLPIImpact.FORWARD],
               'target-arrow-color': this.edgeColors[GLPIImpact.FORWARD],
            }
         },
         {
            selector: '[flag=' + GLPIImpact.BACKWARD + ']',
            style: {
               'line-color'        : this.edgeColors[GLPIImpact.BACKWARD],
               'target-arrow-color': this.edgeColors[GLPIImpact.BACKWARD],
            }
         },
         {
            selector: '[flag=' + GLPIImpact.BOTH + ']',
            style: {
               'line-color'        : this.edgeColors[GLPIImpact.BOTH],
               'target-arrow-color': this.edgeColors[GLPIImpact.BOTH],
            }
         }
      ];
   },

   /**
    * Get network layout
    *
    * @returns {Object}
    */
   getPresetLayout: function () {
      return {
         name: 'preset',
         positions: function(node) {
            return {
               x: parseFloat(node.data('position_x')),
               y: parseFloat(node.data('position_y')),
            };
         }
      };
   },

   /**
    * Get network layout
    *
    * @returns {Object}
    */
   getDagreLayout: function () {
      return {
         name: 'dagre',
         rankDir: 'LR',
         fit: false
      };
   },

   /**
    * Get the current state of the graph
    *
    * @returns {Object}
    */
   getCurrentState: function() {
      var data = {edges: {}, compounds: {}, items: {}};

      // Load edges
      GLPIImpact.cy.edges().forEach(function(edge) {
         data.edges[edge.data('id')] = {
            source: edge.data('source'),
            target: edge.data('target'),
         };
      });

      // Load compounds
      GLPIImpact.cy.filter("node:parent").forEach(function(compound) {
         data.compounds[compound.data('id')] = {
            name: compound.data('label'),
            color: compound.data('color'),
         };
      });

      // Load items
      GLPIImpact.cy.filter("node:childless").forEach(function(node) {
         data.items[node.data('id')] = {
            impactitem_id: node.data('impactitem_id'),
            parent       : node.data('parent'),
            position     : node.position()
         };
      });

      return data;
   },

   /**
    * Delta computation for edges
    *
    * @returns {Object}
    */
   computeEdgeDelta: function(currentEdges) {
      var edgesDelta = {};

      // First iterate on the edges we had in the initial state
      Object.keys(GLPIImpact.initialState.edges).forEach(function(edgeID) {
         var edge = GLPIImpact.initialState.edges[edgeID];
         if (Object.prototype.hasOwnProperty.call(currentEdges, edgeID)) {
            // If the edge is still here in the current state, nothing happened
            // Remove it from the currentEdges data so we can skip it later
            delete currentEdges[edgeID];
         } else {
            // If the edge is missing in the current state, it has been deleted
            var source = edge.source.split(GLPIImpact.NODE_ID_SEPERATOR);
            var target = edge.target.split(GLPIImpact.NODE_ID_SEPERATOR);
            edgesDelta[edgeID] = {
               action           : GLPIImpact.DELTA_ACTION_DELETE,
               itemtype_source  : source[0],
               items_id_source  : source[1],
               itemtype_impacted: target[0],
               items_id_impacted: target[1]
            };
         }
      });

      // Now iterate on the edges we have in the current state
      // Since we removed the edges that were not modified in the previous step,
      // the remaining edges can only be new ones
      Object.keys(currentEdges).forEach(function (edgeID) {
         var edge = currentEdges[edgeID];
         var source = edge.source.split(GLPIImpact.NODE_ID_SEPERATOR);
         var target = edge.target.split(GLPIImpact.NODE_ID_SEPERATOR);
         edgesDelta[edgeID] = {
            action           : GLPIImpact.DELTA_ACTION_ADD,
            itemtype_source  : source[0],
            items_id_source  : source[1],
            itemtype_impacted: target[0],
            items_id_impacted: target[1]
         };
      });

      return edgesDelta;
   },

   /**
    * Delta computation for compounds
    *
    * @returns {Object}
    */
   computeCompoundsDelta: function(currentCompounds) {
      var compoundsDelta = {};

      // First iterate on the compounds we had in the initial state
      Object.keys(GLPIImpact.initialState.compounds).forEach(function(compoundID) {
         var compound = GLPIImpact.initialState.compounds[compoundID];
         if (Object.prototype.hasOwnProperty.call(currentCompounds, compoundID)) {
            // If the compound is still here in the current state
            var currentCompound = currentCompounds[compoundID];

            // Check for updates ...
            if (compound.name != currentCompound.name
               || compound.color != currentCompound.color) {
               compoundsDelta[compoundID] = {
                  action: GLPIImpact.DELTA_ACTION_UPDATE,
                  name  : currentCompound.name,
                  color : currentCompound.color
               };
            }

            // Remove it from the currentCompounds data
            delete currentCompounds[compoundID];
         } else {
            // If the compound is missing in the current state, it's been deleted
            compoundsDelta[compoundID] = {
               action           : GLPIImpact.DELTA_ACTION_DELETE,
            };
         }
      });

      // Now iterate on the compounds we have in the current state
      Object.keys(currentCompounds).forEach(function (compoundID) {
         compoundsDelta[compoundID] = {
            action: GLPIImpact.DELTA_ACTION_ADD,
            name  : currentCompounds[compoundID].name,
            color : currentCompounds[compoundID].color
         };
      });

      return compoundsDelta;
   },

   /**
    * Delta computation for parents
    *
    * @returns {Object}
    */
   computeItemsDelta: function(currentNodes) {
      var itemsDelta = {};

      // Now iterate on the parents we have in the current state
      Object.keys(currentNodes).forEach(function (nodeID) {
         var node = currentNodes[nodeID];
         itemsDelta[node.impactitem_id] = {
            action   : GLPIImpact.DELTA_ACTION_UPDATE,
            parent_id: node.parent,
         };

         // Set parent to 0 if null
         if (node.parent == undefined) {
            node.parent = 0;
         }

         if (nodeID == GLPIImpact.startNode) {
            // Starting node of the graph, save viewport and edge colors
            itemsDelta[node.impactitem_id] = {
               action                  : GLPIImpact.DELTA_ACTION_UPDATE,
               parent_id               : node.parent,
               position_x              : node.position.x,
               position_y              : node.position.y,
               zoom                    : GLPIImpact.cy.zoom(),
               pan_x                   : GLPIImpact.cy.pan().x,
               pan_y                   : GLPIImpact.cy.pan().y,
               impact_color            : GLPIImpact.edgeColors[GLPIImpact.FORWARD],
               depends_color           : GLPIImpact.edgeColors[GLPIImpact.BACKWARD],
               impact_and_depends_color: GLPIImpact.edgeColors[GLPIImpact.BOTH],
               show_depends            : GLPIImpact.directionVisibility[GLPIImpact.BACKWARD],
               show_impact             : GLPIImpact.directionVisibility[GLPIImpact.FORWARD],
               max_depth               : GLPIImpact.maxDepth,
            };
         } else {
            // Others nodes of the graph, store only their parents and position
            itemsDelta[node.impactitem_id] = {
               action    : GLPIImpact.DELTA_ACTION_UPDATE,
               parent_id : node.parent,
               position_x: node.position.x,
               position_y: node.position.y,
            };
         }

      });

      return itemsDelta;
   },

   /**
    * Compute the delta betwteen the initial state and the current state
    *
    * @returns {Object}
    */
   computeDelta: function () {
      // Store the delta for edges, compounds and parent
      var result = {};

      // Get the current state of the graph
      var currentState = this.getCurrentState();

      // Compute each deltas
      result.edges = this.computeEdgeDelta(currentState.edges);
      result.compounds = this.computeCompoundsDelta(currentState.compounds);
      result.items = this.computeItemsDelta(currentState.items);

      return result;
   },

   /**
    * Get the context menu items
    *
    * @returns {Array}
    */
   getContextMenuItems: function(){
      return [
         {
            id             : 'goTo',
            content        : '<i class="fas fa-link"></i>' + __("Go to"),
            tooltipText    : __("Open this element in a new tab"),
            selector       : 'node[!color]',
            onClickFunction: this.menuOnGoTo
         },
         {
            id             : 'showOngoing',
            content        : '<i class="fas fa-list"></i>' + __("Show ongoing tickets"),
            tooltipText    :  __("Show ongoing tickets for this item"),
            selector       : 'node[hasITILObjects=1]',
            onClickFunction: this.menuOnShowOngoing
         },
         {
            id             : 'editCompound',
            content        : '<i class="fas fa-edit"></i>' + __("Group properties..."),
            tooltipText    : __("Set name and/or color for this group"),
            selector       : 'node:parent',
            onClickFunction: this.menuOnEditCompound,
            show           : !this.readonly,
         },
         {
            id             : 'removeFromCompound',
            content        : '<i class="fas fa-external-link-alt"></i>' + __("Remove from group"),
            tooltipText    : __("Remove this asset from the group"),
            selector       : 'node:child',
            onClickFunction: this.menuOnRemoveFromCompound,
            show           : !this.readonly,
         },
         {
            id             : 'delete',
            content        : '<i class="fas fa-trash"></i>' + __("Delete"),
            tooltipText    : __("Delete element"),
            selector       : 'node, edge',
            onClickFunction: this.menuOnDelete,
            show           : !this.readonly,
         },
      ];
   },

   addNode: function(itemID, itemType, position) {
      // Build a new graph from the selected node and insert it
      var node = {
         itemtype: itemType,
         items_id: itemID
      };
      var nodeID = GLPIImpact.makeID(GLPIImpact.NODE, node.itemtype, node.items_id);

      // Check if the node is already on the graph
      if (GLPIImpact.cy.filter('node[id="' + nodeID + '"]').length > 0) {
         alert(__('This asset already exists.'));
         return;
      }

      // Build the new subgraph
      $.when(GLPIImpact.buildGraphFromNode(node)).then(
         function (graph) {
            // Insert the new graph data into the current graph
            GLPIImpact.insertGraph(graph, {
               id: nodeID,
               x: position.x,
               y: position.y
            });
            GLPIImpact.updateFlags();
         },
         function () {
            // Ajax failed
            alert(__("Unexpected error."));
         }
      );
   },

   /**
    * Build the add node dialog
    *
    * @returns {Object}
    */
   getOngoingDialog: function() {
      return {
         title: __("Ongoing tickets"),
         modal: true,
         position: {
            my: 'center',
            at: 'center',
            of: GLPIImpact.impactContainer
         },
         buttons: []
      };
   },

   /**
    * Build the add node dialog
    *
    * @param {string} itemID
    * @param {string} itemType
    * @param {Object} position x, y
    *
    * @returns {Object}
    */
   getEditCompoundDialog: function(compound) {
      // Reset inputs:
      $(GLPIImpact.selectors.compoundName).val(
         compound.data('label')
      );
      $(GLPIImpact.selectors.compoundColor).spectrum(
         "set",
         compound.data('color')
      );

      // Save group details
      var buttonSave = {
         text: __("Save"),
         click: function() {
            // Save compound name
            compound.data(
               'label',
               $(GLPIImpact.selectors.compoundName).val()
            );

            // Save compound color
            compound.data(
               'color',
               $(GLPIImpact.selectors.compoundColor).val()
            );

            // Close dialog
            $(this).dialog("close");
            GLPIImpact.cy.trigger("change");
         }
      };

      return {
         title: __("Edit group"),
         modal: true,
         position: {
            my: 'center',
            at: 'center',
            of: GLPIImpact.impactContainer
         },
         buttons: [buttonSave]
      };
   },

   /**
    * Initialise variables
    *
    * @param {JQuery} impactContainer
    * @param {Object} colors properties: default, forward, backward, both
    * @param {string} startNode
    */
   prepareNetwork: function(
      impactContainer,
      colors,
      startNode
   ) {
      // Set container
      this.impactContainer = impactContainer;

      // Init directionVisibility
      this.directionVisibility[GLPIImpact.FORWARD] = true;
      this.directionVisibility[GLPIImpact.BACKWARD] = true;

      // Set colors for edges
      this.defaultColors = colors;
      this.setEdgeColors(colors);

      // Set start node
      this.startNode = startNode;

      this.initToolbar();
   },

   /**
    * Build the network graph
    *
    * @param {string} data (json)
    */
   buildNetwork: function(data, params, readonly) {
      // Init workspace status
      GLPIImpact.showDefaultWorkspaceStatus();

      // Apply custom colors if defined
      if (params.impact_color != '') {
         this.setEdgeColors({
            forward : params.impact_color,
            backward: params.depends_color,
            both    : params.impact_and_depends_color,
         });
      } else {
         this.setEdgeColors(this.defaultColors);
      }

      // Set color widgets
      $(GLPIImpact.selectors.dependsColor).spectrum(
         "set",
         GLPIImpact.edgeColors[GLPIImpact.BACKWARD]
      );
      $(GLPIImpact.selectors.impactColor).spectrum(
         "set",
         GLPIImpact.edgeColors[GLPIImpact.FORWARD]
      );
      $(GLPIImpact.selectors.impactAndDependsColor).spectrum(
         "set",
         GLPIImpact.edgeColors[GLPIImpact.BOTH]
      );

      // Preset layout
      var layout = this.getPresetLayout();

      // Apply max depth
      this.maxDepth = params.max_depth;

      // Init cytoscape
      this.cy = cytoscape({
         container: this.impactContainer,
         elements : data,
         style    : this.getNetworkStyle(),
         layout   : layout,
         wheelSensitivity: 0.25,
      });

      this.cy.minZoom(0.5);

      // Store initial data
      this.initialState = this.getCurrentState();

      // Enable editing if not readonly
      if (!readonly) {
         this.enableGraphEdition();
      }

      // Enable context menu
      this.cy.contextMenus({
         menuItems: this.getContextMenuItems(),
         menuItemClasses: [],
         contextMenuClasses: []
      });

      // Enable grid
      this.cy.gridGuide({
         gridStackOrder: 0,
         snapToGridOnRelease: true,
         snapToGridDuringDrag: true,
         gridSpacing: 12,
         drawGrid: true,
         panGrid: true,
      });

      // Disable box selection as we don't need it
      this.cy.boxSelectionEnabled(false);

      // Apply saved visibility
      if (!parseInt(params.show_depends)) {
         $(GLPIImpact.selectors.toggleImpact).prop("checked", false);
      }
      if (!parseInt(params.show_impact)) {
         $(GLPIImpact.selectors.toggleDepends).prop("checked", false);
      }
      this.updateFlags();

      // Set viewport
      if (params.zoom != '0') {
         // If viewport params are set, apply them
         this.cy.viewport({
            zoom: parseFloat(params.zoom),
            pan: {
               x: parseFloat(params.pan_x),
               y: parseFloat(params.pan_y),
            }
         });

         // Check viewport is not empty or contains only one item
         var viewport = GLPIImpact.cy.extent();
         var empty = true;
         GLPIImpact.cy.nodes().forEach(function(node) {
            if (node.position().x > viewport.x1
               && node.position().x < viewport.x2
               && node.position().y > viewport.x1
               && node.position().y < viewport.x2
            ){
               empty = false;
            }
         });

         if (empty || GLPIImpact.cy.filter("node:childless").length == 1) {
            this.cy.fit();

            if (this.cy.zoom() > 2.3) {
               this.cy.zoom(2.3);
               this.cy.center();
            }
         }
      } else {
         // Else fit the graph and reduce zoom if needed
         this.cy.fit();

         if (this.cy.zoom() > 2.3) {
            this.cy.zoom(2.3);
            this.cy.center();
         }
      }

      // Register events handlers for cytoscape object
      this.cy.on('mousedown', 'node', this.nodeOnMousedown);
      this.cy.on('mouseup', 'node', this.nodeOnMouseup);
      this.cy.on('mousemove', this.onMousemove);
      this.cy.on('mouseover', this.onMouseover);
      this.cy.on('mouseout', this.onMouseout);
      this.cy.on('click', this.onClick);
      this.cy.on('click', 'edge', this.edgeOnClick);
      this.cy.on('click', 'node', this.nodeOnClick);
      this.cy.on('box', this.onBox);
      this.cy.on('drag add remove pan zoom change', this.onChange);
      this.cy.on('doubleClick', this.onDoubleClick);

      // Global events
      $(document).keydown(this.onKeyDown);
      $(document).keyup(this.onKeyUp);

      // Enter EDITION_DEFAULT mode
      this.setEditionMode(GLPIImpact.EDITION_DEFAULT);

      // Init depth value
      var text = GLPIImpact.maxDepth;
      if (GLPIImpact.maxDepth >= GLPIImpact.MAX_DEPTH) {
         text = "infinity";
      }
      $(GLPIImpact.selectors.maxDepthView).html(text);
      $(GLPIImpact.selectors.maxDepth).val(GLPIImpact.maxDepth);
   },

   /**
    * Set readonly and show toolbar
    */
   enableGraphEdition: function() {
      // Show toolbar
      $(this.selectors.save).show();
      $(this.selectors.addNode).show();
      $(this.selectors.addEdge).show();
      $(this.selectors.addCompound).show();
      $(this.selectors.deleteElement).show();
      $(this.selectors.impactSettings).show();
      $(this.selectors.sideToggle).show();

      // Keep track of readonly so that events handler can update their behavior
      this.readonly = false;
   },

   /**
    * Create ID for nodes and egdes
    *
    * @param {number} type (NODE or EDGE)
    * @param {string} a
    * @param {string} b
    *
    * @returns {string|null}
    */
   makeID: function(type, a, b) {
      switch (type) {
         case GLPIImpact.NODE:
            return a + "::" + b;
         case GLPIImpact.EDGE:
            return a + "->" + b;
      }

      return null;
   },

   /**
    * Helper to make an ID selector
    * We can't use the short syntax "#id" because our ids contains
    * non-alpha-numeric characters
    *
    * @param {string} id
    *
    * @returns {string}
    */
   makeIDSelector: function(id) {
      return "[id='" + id + "']";
   },

   /**
    * Reload the graph style
    */
   updateStyle: function() {
      this.cy.style(this.getNetworkStyle());
      // If either the source of the target node of an edge is hidden, hide the
      // edge too by setting it's dept to the maximum value
      this.cy.edges().forEach(function(edge) {
         var source = GLPIImpact.cy.filter(GLPIImpact.makeIDSelector(edge.data('source')));
         var target = GLPIImpact.cy.filter(GLPIImpact.makeIDSelector(edge.data('target')));
         if (source.visible() && target.visible()) {
            edge.data('depth', 0);
         } else {
            edge.data('depth', Number.MAX_VALUE);
         }
      });
   },

   /**
    * Update the flags of the edges of the graph
    * Explore the graph forward then backward
    */
   updateFlags: function() {
      // Keep track of visited nodes
      var exploredNodes;

      // Set all flag to the default value (0)
      this.cy.edges().forEach(function(edge) {
         edge.data("flag", 0);
      });
      this.cy.nodes().data("depth", 0);

      // Run through the graph forward
      exploredNodes = {};
      exploredNodes[this.startNode] = true;
      this.exploreGraph(exploredNodes, GLPIImpact.FORWARD, this.startNode, 0);

      // Run through the graph backward
      exploredNodes = {};
      exploredNodes[this.startNode] = true;
      this.exploreGraph(exploredNodes, GLPIImpact.BACKWARD, this.startNode, 0);

      this.updateStyle();
   },

   /**
    * Toggle impact/depends visibility
    *
    * @param {*} toToggle
    */
   toggleVisibility: function(toToggle) {
      // Update visibility setting
      GLPIImpact.directionVisibility[toToggle] = !GLPIImpact.directionVisibility[toToggle];

      // Compute direction
      var direction;
      var forward = GLPIImpact.directionVisibility[GLPIImpact.FORWARD];
      var backward = GLPIImpact.directionVisibility[GLPIImpact.BACKWARD];

      if (forward && backward) {
         direction = GLPIImpact.BOTH;
      } else if (!forward && backward) {
         direction = GLPIImpact.BACKWARD;
      } else if (forward && !backward) {
         direction = GLPIImpact.FORWARD;
      } else {
         direction = 0;
      }

      // Hide all nodes
      GLPIImpact.cy.filter("node").data('hidden', 1);

      // Show/Hide edges according to the direction
      GLPIImpact.cy.filter("edge").forEach(function(edge) {
         if (edge.data('flag') & direction) {
            edge.data('hidden', 0);

            // If the edge is visible, show the nodes they are connected to it
            var sourceFilter = "node[id='" + edge.data('source') + "']";
            var targetFilter = "node[id='" + edge.data('target') + "']";
            GLPIImpact.cy.filter(sourceFilter + ", " + targetFilter)
               .data("hidden", 0);

            // Make the parents of theses node visibles too
            GLPIImpact.cy.filter(sourceFilter + ", " + targetFilter)
               .parent()
               .data("hidden", 0);
         } else {
            edge.data('hidden', 1);
         }
      });

      // Start node should always be visible
      GLPIImpact.cy.filter(GLPIImpact.makeIDSelector(GLPIImpact.startNode))
         .data("hidden", 0);

      GLPIImpact.updateStyle();
   },

   /**
    * Explore a graph in a given direction using recursion
    *
    * @param {Array}  exploredNodes
    * @param {number} direction
    * @param {string} currentNodeID
    * @param {number} depth
    */
   exploreGraph: function(exploredNodes, direction, currentNodeID, depth) {
      // Set node depth
      var node = this.cy.filter(this.makeIDSelector(currentNodeID));
      if (node.data('depth') == 0 || node.data('depth') > depth) {
         node.data('depth', depth);
      }

      // If node has a parent, set it's depth too
      if (node.isChild() && (
         node.parent().data('depth') == 0 ||
         node.parent().data('depth') > depth
      )) {
         node.parent().data('depth', depth);
      }

      depth++;

      // Depending on the direction, we are looking for edge that either begin
      // from the current node (source) or end on the current node (target)
      var sourceOrTarget;

      // The next node is the opposite of sourceOrTarget : if our node is at
      // the start (source) then the next is at the end (target)
      var nextNode;

      switch (direction) {
         case GLPIImpact.FORWARD:
            sourceOrTarget = "source";
            nextNode       = "target";
            break;
         case GLPIImpact.BACKWARD:
            sourceOrTarget = "target";
            nextNode       = "source";
            break;
      }

      // Find the edges connected to the current node
      this.cy.elements('edge[' + sourceOrTarget + '="' + currentNodeID + '"]')
         .forEach(function(edge) {
            // Get target node from computer nextNode att name
            var targetNode = edge.data(nextNode);

            // Set flag
            edge.data("flag", direction | edge.data("flag"));

            // Check we haven't go through this node yet
            if(exploredNodes[targetNode] == undefined) {
               exploredNodes[targetNode] = true;
               // Go to next node
               GLPIImpact.exploreGraph(exploredNodes, direction, targetNode, depth);
            }
         });
   },

   /**
    * Ask the backend to build a graph from a specific node
    *
    * @param {Object} node
    * @returns {Array|null}
    */
   buildGraphFromNode: function(node) {
      node.action = "load";
      var dfd = jQuery.Deferred();

      // Request to backend
      $.ajax({
         type: "GET",
         url: CFG_GLPI.root_doc + "/ajax/impact.php",
         dataType: "json",
         data: node,
         success: function(data) {
            dfd.resolve(JSON.parse(data.graph));
         },
         error: function () {
            dfd.reject();
         }
      });

      return dfd.promise();
   },


   getDistance: function(a, b) {
      return Math.sqrt(Math.pow(b.x - a.x, 2) + Math.pow(b.y - a.y, 2));
   },

   /**
    * Insert another new graph into the current one
    *
    * @param {Array} graph
    * @param {Object} startNode data, x, y
    */
   insertGraph: function(graph, startNode) {
      var toAdd = [];

      // Find closest available space near the graph
      var boundingBox = this.cy.filter().boundingBox();
      var distances   = {
         right: this.getDistance(
            {
               x: boundingBox.x2,
               y: (boundingBox.y1 + boundingBox.y2) / 2
            },
            startNode
         ),
         left: this.getDistance(
            {
               x: boundingBox.x1,
               y: (boundingBox.y1 + boundingBox.y2) / 2
            },
            startNode
         ),
         top: this.getDistance(
            {
               x: (boundingBox.x1 + boundingBox.x2) / 2,
               y: boundingBox.y2
            },
            startNode
         ),
         bottom: this.getDistance(
            {
               x: (boundingBox.x1 + boundingBox.x2) / 2,
               y: boundingBox.y1
            },
            startNode
         ),
      };
      var lowest = Math.min.apply(null, Object.values(distances));
      var direction = Object.keys(distances).filter(function (x) {
         return distances[x] === lowest;
      })[0];

      // Try to add the new graph nodes
      for (var i=0; i<graph.length; i++) {
         var id = graph[i].data.id;
         // Check that the element is not already on the graph,
         if (this.cy.filter('[id="' + id + '"]').length > 0) {
            continue;
         }
         // Store node to add them at once with a layout
         toAdd.push(graph[i]);

         // Remove node from side list if needed
         if (graph[i].group == "nodes" && graph[i].data.color === undefined ) {
            var node_info = graph[i].data.id.split(GLPIImpact.NODE_ID_SEPERATOR);
            var itemtype = node_info[0];
            var items_id = node_info[1];
            $("p[data-id=" + items_id + "][data-type='" + itemtype + "']").remove();
         }
      }

      // Just place the node if only one result is found
      if (toAdd.length == 1) {
         toAdd[0].position = {
            x: startNode.x,
            y: startNode.y,
         };

         this.cy.add(toAdd);
         return;
      }

      // Add nodes and apply layout
      var eles = this.cy.add(toAdd);
      var options = GLPIImpact.getDagreLayout();

      // Place the layout anywhere to compute it's bounding box
      var layout = eles.layout(options);
      layout.run();

      var newGraphBoundingBox = eles.boundingBox();
      var startingPoint;

      // Now compute the real location where we want it
      switch (direction) {
         case 'right':
            startingPoint = {
               x: newGraphBoundingBox.x1,
               y: (newGraphBoundingBox.y1 + newGraphBoundingBox.y2) / 2,
            };
            break;
         case 'left':
            startingPoint = {
               x: newGraphBoundingBox.x2,
               y: (newGraphBoundingBox.y1 + newGraphBoundingBox.y2) / 2,
            };
            break;
         case 'top':
            startingPoint = {
               x: (newGraphBoundingBox.x1 + newGraphBoundingBox.x2) / 2,
               y: newGraphBoundingBox.y1,
            };
            break;
         case 'bottom':
            startingPoint = {
               x: (newGraphBoundingBox.x1 + newGraphBoundingBox.x2) / 2,
               y: newGraphBoundingBox.y2,
            };
            break;
      }

      newGraphBoundingBox.x1 += startNode.x - startingPoint.x;
      newGraphBoundingBox.x2 += startNode.x - startingPoint.x;
      newGraphBoundingBox.y1 += startNode.y - startingPoint.y;
      newGraphBoundingBox.y2 += startNode.y - startingPoint.y;

      options.boundingBox = newGraphBoundingBox;

      // Apply layout again with correct bounding box
      layout = eles.layout(options);
      layout.run();

      this.cy.animate({
         center: {
            eles : GLPIImpact.cy.filter(""),
         },
      });
   },

   /**
    * Set the colors
    *
    * @param {object} colors default, backward, forward, both
    */
   setEdgeColors: function (colors) {
      this.setColorIfExist(GLPIImpact.DEFAULT, colors.default);
      this.setColorIfExist(GLPIImpact.BACKWARD, colors.backward);
      this.setColorIfExist(GLPIImpact.FORWARD, colors.forward);
      this.setColorIfExist(GLPIImpact.BOTH, colors.both);
   },

   /**
    * Set color if exist
    *
    * @param {object} colors default, backward, forward, both
    */
   setColorIfExist: function (index, color) {
      if (color !== undefined) {
         this.edgeColors[index] = color;
      }
   },

   /**
    * Exit current edition mode and enter a new one
    *
    * @param {number} mode
    */
   setEditionMode: function (mode) {
      // Switching to a mode we are already in -> go to default
      if (this.editionMode == mode) {
         mode = GLPIImpact.EDITION_DEFAULT;
      }

      this.exitEditionMode();
      this.enterEditionMode(mode);
      this.editionMode = mode;
   },

   /**
    * Exit current edition mode
    */
   exitEditionMode: function() {
      switch (this.editionMode) {
         case GLPIImpact.EDITION_DEFAULT:
            GLPIImpact.cy.nodes().ungrabify();
            break;

         case GLPIImpact.EDITION_ADD_NODE:
            GLPIImpact.cy.nodes().ungrabify();
            $(GLPIImpact.selectors.sideToggleIcon).addClass('fa-chevron-left');
            $(GLPIImpact.selectors.sideToggleIcon).removeClass('fa-chevron-right');
            $(GLPIImpact.selectors.side).removeClass('impact-side-expanded');
            $(GLPIImpact.selectors.sidePanel).removeClass('impact-side-expanded');
            $(GLPIImpact.selectors.addNode).removeClass("active");
            break;

         case GLPIImpact.EDITION_ADD_EDGE:
            $(GLPIImpact.selectors.addEdge).removeClass("active");
            // Empty event data and remove tmp node
            GLPIImpact.eventData.addEdgeStart = null;
            GLPIImpact.cy.filter("#tmp_node").remove();
            break;

         case GLPIImpact.EDITION_DELETE:
            GLPIImpact.cy.filter().unselect();
            GLPIImpact.cy.data('todelete', 0);
            $(GLPIImpact.selectors.deleteElement).removeClass("active");
            break;

         case GLPIImpact.EDITION_ADD_COMPOUND:
            GLPIImpact.cy.panningEnabled(true);
            GLPIImpact.cy.boxSelectionEnabled(false);
            $(GLPIImpact.selectors.addCompound).removeClass("active");
            break;

         case GLPIImpact.EDITION_SETTINGS:
            GLPIImpact.cy.nodes().ungrabify();
            $(GLPIImpact.selectors.sideToggleIcon).addClass('fa-chevron-left');
            $(GLPIImpact.selectors.sideToggleIcon).removeClass('fa-chevron-right');
            $(GLPIImpact.selectors.side).removeClass('impact-side-expanded');
            $(GLPIImpact.selectors.sidePanel).removeClass('impact-side-expanded');
            $(GLPIImpact.selectors.impactSettings).removeClass("active");
            break;
      }
   },

   /**
    * Enter a new edition mode
    *
    * @param {number} mode
    */
   enterEditionMode: function(mode) {
      switch (mode) {
         case GLPIImpact.EDITION_DEFAULT:
            GLPIImpact.clearHelpText();
            GLPIImpact.cy.nodes().grabify();
            $(GLPIImpact.impactContainer).css('cursor', "move");
            break;

         case GLPIImpact.EDITION_ADD_NODE:
            GLPIImpact.cy.nodes().grabify();
            GLPIImpact.clearHelpText();
            $(GLPIImpact.selectors.sideToggleIcon).removeClass('fa-chevron-left');
            $(GLPIImpact.selectors.sideToggleIcon).addClass('fa-chevron-right');
            $(GLPIImpact.selectors.side).addClass('impact-side-expanded');
            $(GLPIImpact.selectors.sidePanel).addClass('impact-side-expanded');
            $(GLPIImpact.selectors.addNode).addClass("active");
            $(GLPIImpact.selectors.sideSettings).hide();
            $(GLPIImpact.selectors.sideAddNode).show();
            break;

         case GLPIImpact.EDITION_ADD_EDGE:
            GLPIImpact.showHelpText(__("Draw a line between two assets to add an impact relation"));
            $(GLPIImpact.selectors.addEdge).addClass("active");
            $(GLPIImpact.impactContainer).css('cursor', "crosshair");
            break;

         case GLPIImpact.EDITION_DELETE:
            GLPIImpact.cy.filter().unselect();
            GLPIImpact.showHelpText(__("Click on an element to remove it from the network"));
            $(GLPIImpact.selectors.deleteElement).addClass("active");
            $(GLPIImpact.impactContainer).css('cursor', "move");
            break;

         case GLPIImpact.EDITION_ADD_COMPOUND:
            GLPIImpact.cy.panningEnabled(false);
            GLPIImpact.cy.boxSelectionEnabled(true);
            GLPIImpact.showHelpText(__("Draw a square containing the assets you wish to group"));
            $(GLPIImpact.selectors.addCompound).addClass("active");
            $(GLPIImpact.impactContainer).css('cursor', "crosshair");
            break;

         case GLPIImpact.EDITION_SETTINGS:
            GLPIImpact.cy.nodes().grabify();
            $(GLPIImpact.selectors.sideToggleIcon).removeClass('fa-chevron-left');
            $(GLPIImpact.selectors.sideToggleIcon).addClass('fa-chevron-right');
            $(GLPIImpact.selectors.side).addClass('impact-side-expanded');
            $(GLPIImpact.selectors.sidePanel).addClass('impact-side-expanded');
            $(GLPIImpact.selectors.impactSettings).addClass("active");
            $(GLPIImpact.selectors.sideAddNode).hide();
            $(GLPIImpact.selectors.sideSettings).show();
            break;
      }
   },

   /**
    * Hide the toolbar and show an help text
    *
    * @param {string} text
    */
   showHelpText: function(text) {
      $(GLPIImpact.selectors.helpText).html(text).show();
   },

   /**
    * Hide the help text and show the toolbar
    */
   clearHelpText: function() {
      $(GLPIImpact.selectors.helpText).hide();
   },

   /**
    * Export the graph in the given format
    *
    * @param {string} format
    * @param {boolean} transparentBackground (png only)
    *
    * @returns {Object} filename, filecontent
    */
   download: function(format, transparentBackground) {
      var filename;
      var filecontent;

      // Create fake link
      GLPIImpact.impactContainer.append("<a id='impact_download'></a>");
      var link = $('#impact_download');

      switch (format) {
         case 'png':
            filename = "impact.png";
            filecontent = this.cy.png({
               bg: transparentBackground ? "transparent" : "white"
            });
            break;

         case 'jpeg':
            filename = "impact.jpeg";
            filecontent = this.cy.jpg();
            break;
      }

      // Trigger download and remore the link
      link.prop('download', filename);
      link.prop("href", filecontent);
      link[0].click();
      link.remove();
   },

   /**
    * Get node at target position
    *
    * @param {Object} position x, y
    * @param {function} filter if false return null
    */
   getNodeAt: function(position, filter) {
      var nodes = this.cy.nodes();

      for (var i=0; i<nodes.length; i++) {
         if (nodes[i].boundingBox().x1 < position.x
          && nodes[i].boundingBox().x2 > position.x
          && nodes[i].boundingBox().y1 < position.y
          && nodes[i].boundingBox().y2 > position.y) {
            // Check if the node is excluded
            if (filter(nodes[i])) {
               return nodes[i];
            }
         }
      }

      return null;
   },

   /**
    * Enable the save button
    */
   showCleanWorkspaceStatus: function() {
      $(GLPIImpact.selectors.save).removeClass('dirty');
      $(GLPIImpact.selectors.save).addClass('clean');
      $(GLPIImpact.selectors.save).find('i').removeClass("fas fa-exclamation-triangle");
      $(GLPIImpact.selectors.save).find('i').addClass("fas fa-check");
   },

   /**
    * Enable the save button
    */
   showDirtyWorkspaceStatus: function() {
      $(GLPIImpact.selectors.save).removeClass('clean');
      $(GLPIImpact.selectors.save).addClass('dirty');
      $(GLPIImpact.selectors.save).find('i').removeClass("fas fa-check");
      $(GLPIImpact.selectors.save).find('i').addClass("fas fa-exclamation-triangle");
   },

   /**
    * Enable the save button
    */
   showDefaultWorkspaceStatus: function() {
      $(GLPIImpact.selectors.save).removeClass('clean');
      $(GLPIImpact.selectors.save).removeClass('dirty');
   },

   /**
    * Build the ongoing dialog content according to the list of ITILObjects
    *
    * @param {Object} ITILObjects requests, incidents, changes, problems
    *
    * @returns {string}
    */
   buildOngoingDialogContent: function(ITILObjects) {
      return this.listElements(__("Requests"), ITILObjects.requests, "ticket")
         + this.listElements(__("Incidents"), ITILObjects.incidents, "ticket")
         + this.listElements(__("Changes"), ITILObjects.changes , "change")
         + this.listElements(__("Problems"), ITILObjects.problems, "problem");
   },

   /**
    * Build an html list
    *
    * @param {string} title requests, incidents, changes, problems
    * @param {string} elements requests, incidents, changes, problems
    * @param {string} url key used to generate the URL
    *
    * @returns {string}
    */
   listElements: function(title, elements, url) {
      var html = "";

      if (elements.length > 0) {
         html += "<h3>" + title + "</h3>";
         html += "<ul>";

         elements.forEach(function(element) {
            var link = "./" + url + ".form.php?id=" + element.id;
            html += '<li><a target="_blank" href="' + link + '">' + element.name
               + '</a></li>';
         });
         html += "</ul>";
      }

      return html;
   },

   /**
    * Add a new compound from the selected nodes
    */
   addCompoundFromSelection: _.debounce(function(){
      // Check that there is enough selected nodes
      if (GLPIImpact.eventData.boxSelected.length < 2) {
         alert(__("You need to select at least 2 assets to make a group"));
      } else {
         // Create the compound
         var newCompound = GLPIImpact.cy.add({group: 'nodes'});

         // Set parent for coumpound member
         GLPIImpact.eventData.boxSelected.forEach(function(ele) {
            ele.move({'parent': newCompound.data('id')});
         });

         // Show edit dialog
         $(GLPIImpact.selectors.editCompoundDialog).dialog(
            GLPIImpact.getEditCompoundDialog(newCompound)
         );

         // Back to default mode
         GLPIImpact.setEditionMode(GLPIImpact.EDITION_DEFAULT);
      }

      // Clear the selection
      GLPIImpact.eventData.boxSelected = [];
      GLPIImpact.cy.filter(":selected").unselect();
   }, 100, false),

   /**
    * Remove an element from the graph
    *
    * @param {object} ele
    */
   deleteFromGraph: function(ele) {
      if (ele.data('id') == GLPIImpact.startNode) {
         alert("Can't remove starting node");
         return;
      }

      if (ele.isEdge()) {
         // Case 1: removing an edge
         ele.remove();
         // this.cy.remove(impact.makeIDSelector(ele.data('id')));
      } else if (ele.isParent()) {
         // Case 2: removing a compound
         // Remove only the parent
         ele.children().move({parent: null});
         ele.remove();

      } else {
         // Case 3: removing a node
         // Remove parent if last child of a compound
         if (!ele.isOrphan() && ele.parent().children().length <= 2) {
            this.deleteFromGraph(ele.parent());
         }

         // Remove all edges connected to this node from graph and delta
         ele.remove();
      }

      // Update flags
      GLPIImpact.updateFlags();
   },

   /**
    * Toggle fullscreen mode
    */
   toggleFullscreen: function() {
      this.fullscreen = !this.fullscreen;
      $(this.selectors.toggleFullscreen).toggleClass('active');
      $(this.impactContainer).toggleClass('fullscreen');
      $(this.selectors.side).toggleClass('fullscreen');

      if (this.fullscreen) {
         $(this.impactContainer).children("canvas:eq(0)").css({
            height: "100vh"
         });
         $('html, body').css('overflow', 'hidden');
      } else {
         $(this.impactContainer).children("canvas:eq(0)").css({
            height: "unset"
         });
         $('html, body').css('overflow', 'unset');
      }

      GLPIImpact.cy.resize();
   },

   /**
    * Handle global click events
    *
    * @param {JQuery.Event} event
    */
   onClick: function () {
      switch (GLPIImpact.editionMode) {
         case GLPIImpact.EDITION_DEFAULT:
            break;

         case GLPIImpact.EDITION_ADD_NODE:
            break;

         case GLPIImpact.EDITION_ADD_EDGE:
            break;

         case GLPIImpact.EDITION_DELETE:
            break;
      }
   },

   /**
    * Handle click on edge
    *
    * @param {JQuery.Event} event
    */
   edgeOnClick: function (event) {
      switch (GLPIImpact.editionMode) {
         case GLPIImpact.EDITION_DEFAULT:
            break;

         case GLPIImpact.EDITION_ADD_NODE:
            break;

         case GLPIImpact.EDITION_ADD_EDGE:
            break;

         case GLPIImpact.EDITION_DELETE:
            // Remove the edge from the graph
            GLPIImpact.deleteFromGraph(event.target);
            break;
      }
   },

   /**
    * Handle click on node
    *
    * @param {JQuery.Event} event
    */
   nodeOnClick: function (event) {
      switch (GLPIImpact.editionMode) {
         case GLPIImpact.EDITION_DEFAULT:
            if (GLPIImpact.eventData.lastClick != null) {
               // Trigger homemade double click event
               if (event.timeStamp - GLPIImpact.eventData.lastClick < 500) {
                  event.target.trigger('doubleClick', event);
               }
            }

            GLPIImpact.eventData.lastClick = event.timeStamp;
            break;

         case GLPIImpact.EDITION_ADD_NODE:
            break;

         case GLPIImpact.EDITION_ADD_EDGE:
            break;

         case GLPIImpact.EDITION_DELETE:
            GLPIImpact.deleteFromGraph(event.target);
            break;
      }
   },

   /**
    * Handle end of box selection event
    *
    * @param {JQuery.Event} event
    */
   onBox: function (event) {
      switch (GLPIImpact.editionMode) {
         case GLPIImpact.EDITION_DEFAULT:
            break;

         case GLPIImpact.EDITION_ADD_NODE:
            break;

         case GLPIImpact.EDITION_ADD_EDGE:
            break;

         case GLPIImpact.EDITION_DELETE:
            break;

         case GLPIImpact.EDITION_ADD_COMPOUND:
            var ele = event.target;
            // Add node to selected list if he is not part of a compound already
            if (ele.isNode() && ele.isOrphan() && !ele.isParent()) {
               GLPIImpact.eventData.boxSelected.push(ele);
            }
            GLPIImpact.addCompoundFromSelection();
            break;
      }
   },

   /**
    * Handle any graph modification
    *
    * @param {*} event
    */
   onChange: function() {
      GLPIImpact.showDirtyWorkspaceStatus();
   },

   /**
    * Double click handler
    * @param {JQuery.Event} event
    */
   onDoubleClick: function(event) {
      if (event.target.isParent()) {
         // Open edit dialog on compound nodes
         $(GLPIImpact.selectors.editCompoundDialog).dialog(
            GLPIImpact.getEditCompoundDialog(event.target)
         );
      } else if (event.target.isNode()) {
         // Go to on nodes
         window.open(event.target.data('link'));
      }
   },

   /**
    * Handler for key down events
    *
    * @param {JQuery.Event} event
    */
   onKeyDown: function(event) {
      switch (event.which) {
         // Shift
         case 16:
            // Enter edit edge mode
            if (GLPIImpact.editionMode != GLPIImpact.EDITION_ADD_EDGE) {
               if (GLPIImpact.eventData.previousEditionMode === undefined) {
                  GLPIImpact.eventData.previousEditionMode = GLPIImpact.editionMode;
               }
               GLPIImpact.setEditionMode(GLPIImpact.EDITION_ADD_EDGE);
            }
            break;

         // Ctrl
         case 17:
            // Enter add compound edge mode
            if (GLPIImpact.editionMode != GLPIImpact.EDITION_ADD_COMPOUND) {
               if (GLPIImpact.eventData.previousEditionMode === undefined) {
                  GLPIImpact.eventData.previousEditionMode = GLPIImpact.editionMode;
               }
               GLPIImpact.setEditionMode(GLPIImpact.EDITION_ADD_COMPOUND);
            }
            break;

         // ESC
         case 27:
            // Exit specific edition mode
            if (GLPIImpact.editionMode != GLPIImpact.EDITION_DEFAULT) {
               GLPIImpact.setEditionMode(GLPIImpact.EDITION_DEFAULT);
            }
            break;

         // Delete
         case 46:
            if (GLPIImpact.readonly) {
               break;
            }

            // Delete selected elements
            GLPIImpact.cy.filter(":selected").forEach(function(ele) {
               GLPIImpact.deleteFromGraph(ele);
            });
            break;
      }
   },

   /**
    * Handler for key down events
    *
    * @param {JQuery.Event} event
    */
   onKeyUp: function(event) {
      switch (event.which) {
         // Shift
         case 16:
            // Return to previous edition mode if needed
            if (GLPIImpact.editionMode == GLPIImpact.EDITION_ADD_EDGE
               && GLPIImpact.eventData.previousEditionMode !== undefined) {
               GLPIImpact.setEditionMode(GLPIImpact.eventData.previousEditionMode);

               GLPIImpact.eventData.previousEditionMode = undefined;
            }
            break;

         // Ctrl
         case 17:
            // Return to previous edition mode if needed
            if (GLPIImpact.editionMode == GLPIImpact.EDITION_ADD_COMPOUND
               && GLPIImpact.eventData.previousEditionMode !== undefined) {
               GLPIImpact.setEditionMode(GLPIImpact.eventData.previousEditionMode);

               GLPIImpact.eventData.previousEditionMode = undefined;
            }
            break;
      }
   },

   /**
    * Handle mousedown events on nodes
    *
    * @param {JQuery.Event} event
    */
   nodeOnMousedown: function (event) {
      switch (GLPIImpact.editionMode) {
         case GLPIImpact.EDITION_DEFAULT:
            $(GLPIImpact.impactContainer).css('cursor', "grabbing");

            // If we are not on a compound node or a node already inside one
            if (event.target.isOrphan() && !event.target.isParent()) {
               GLPIImpact.eventData.grabNodeStart = event.target;
            }
            break;

         case GLPIImpact.EDITION_ADD_NODE:
            break;

         case GLPIImpact.EDITION_ADD_EDGE:
            if (!event.target.isParent()) {
               GLPIImpact.eventData.addEdgeStart = this.data('id');
            }
            break;

         case GLPIImpact.EDITION_DELETE:
            break;

         case GLPIImpact.EDITION_ADD_COMPOUND:
            break;
      }
   },

   /**
    * Handle mouseup events on nodes
    *
    * @param {JQuery.Event} event
    */
   nodeOnMouseup: function (event) {
      switch (GLPIImpact.editionMode) {
         case GLPIImpact.EDITION_DEFAULT:
            $(GLPIImpact.impactContainer).css('cursor', "grab");

            // Check if we were grabbing a node
            if (GLPIImpact.eventData.grabNodeStart != null) {
               // Reset eventData for node grabbing
               GLPIImpact.eventData.grabNodeStart = null;
               GLPIImpact.eventData.boundingBox = null;
            }

            break;

         case GLPIImpact.EDITION_ADD_NODE:
            break;

         case GLPIImpact.EDITION_ADD_EDGE:
            // Exit if no start node
            if (GLPIImpact.eventData.addEdgeStart == null) {
               return;
            }

            // Reset addEdgeStart
            var startEdge = GLPIImpact.eventData.addEdgeStart; // Keep a copy to use later
            GLPIImpact.eventData.addEdgeStart = null;

            // Remove current tmp collection
            event.cy.remove(GLPIImpact.eventData.tmpEles);
            GLPIImpact.eventData.tmpEles = null;

            // Option 1: Edge between a node and the fake tmp_node -> ignore
            if (this.data('id') == 'tmp_node') {
               return;
            }

            // Option 2: Edge between two nodes that already exist -> ignore
            var edgeID = GLPIImpact.makeID(GLPIImpact.EDGE, startEdge, this.data('id'));
            if (event.cy.filter('edge[id="' + edgeID + '"]').length > 0) {
               return;
            }

            // Option 3: Both end of the edge are actually the same node -> ignore
            if (startEdge == this.data('id')) {
               return;
            }

            // Option 4: Edge between two nodes that does not exist yet -> create it!
            event.cy.add({
               group: 'edges',
               data: {
                  id: edgeID,
                  source: startEdge,
                  target: this.data('id')
               }
            });

            // Update dependencies flags according to the new link
            GLPIImpact.updateFlags();
            break;

         case GLPIImpact.EDITION_DELETE:
            break;
      }
   },

   /**
    * Handle mousemove events on nodes
    *
    * @param {JQuery.Event} event
    */
   onMousemove: _.throttle(function(event) {
      var node;

      switch (GLPIImpact.editionMode) {
         case GLPIImpact.EDITION_DEFAULT:
            // No action if we are not grabbing a node
            if (GLPIImpact.eventData.grabNodeStart == null) {
               return;
            }

            // Look for a compound at the cursor position
            node = GLPIImpact.getNodeAt(event.position, function(node) {
               return node.isParent();
            });

            if (node) {
               // If we have a bounding box defined, the grabbed node is already
               // being placed into a compound, we need to check if it was moved
               // outside this original bouding box to know if the user is trying
               // to move if away from the compound
               if (GLPIImpact.eventData.boundingBox != null) {
                  // If the user tried to move out of the compound
                  if (GLPIImpact.eventData.boundingBox.x1 > event.position.x
                     || GLPIImpact.eventData.boundingBox.x2 < event.position.x
                     || GLPIImpact.eventData.boundingBox.y1 > event.position.y
                     || GLPIImpact.eventData.boundingBox.y2 < event.position.y) {
                     // Remove it from the compound
                     GLPIImpact.eventData.grabNodeStart.move({parent: null});
                     GLPIImpact.eventData.boundingBox = null;
                  }
               } else {
                  // If we found a compound, add the grabbed node inside
                  GLPIImpact.eventData.grabNodeStart.move({parent: node.data('id')});

                  // Store the original bouding box of the compound
                  GLPIImpact.eventData.boundingBox = node.boundingBox();
               }
            } else {
               // Else; reset it's parent so it can be removed from any temporary
               // compound while the user is stil grabbing
               GLPIImpact.eventData.grabNodeStart.move({parent: null});
            }

            break;

         case GLPIImpact.EDITION_ADD_NODE:
            break;

         case GLPIImpact.EDITION_ADD_EDGE:
            // No action if we are not placing an edge
            if (GLPIImpact.eventData.addEdgeStart == null) {
               return;
            }

            // Remove current tmp collection
            if (GLPIImpact.eventData.tmpEles != null) {
               event.cy.remove(GLPIImpact.eventData.tmpEles);
            }

            node = GLPIImpact.getNodeAt(event.position, function(node) {
               var nodeID = node.data('id');

               // Can't link to itself
               if (nodeID == GLPIImpact.eventData.addEdgeStart) {
                  return false;
               }

               // Can't link to parent
               if (node.isParent()) {
                  return false;
               }

               // The created edge shouldn't already exist
               var edgeID = GLPIImpact.makeID(GLPIImpact.EDGE, GLPIImpact.eventData.addEdgeStart, nodeID);
               if (GLPIImpact.cy.filter('edge[id="' + edgeID + '"]').length > 0) {
                  return false;
               }

               // The node must be visible
               if (GLPIImpact.cy.getElementById(nodeID).data('hidden')) {
                  return false;
               }

               return true;
            });

            if (node != null) {
               node = node.data('id');

               // Add temporary edge to node hovered by the user
               GLPIImpact.eventData.tmpEles = event.cy.add([
                  {
                     group: 'edges',
                     data: {
                        id: GLPIImpact.makeID(GLPIImpact.EDGE, GLPIImpact.eventData.addEdgeStart, node),
                        source: GLPIImpact.eventData.addEdgeStart,
                        target: node
                     }
                  }
               ]);
            } else {
               // Add temporary edge to a new invisible node at mouse position
               GLPIImpact.eventData.tmpEles = event.cy.add([
                  {
                     group: 'nodes',
                     data: {
                        id: 'tmp_node',
                     },
                     position: {
                        x: event.position.x,
                        y: event.position.y
                     }
                  },
                  {
                     group: 'edges',
                     data: {
                        id: GLPIImpact.makeID(
                           GLPIImpact.EDGE,
                           GLPIImpact.eventData.addEdgeStart,
                           "tmp_node"
                        ),
                        source: GLPIImpact.eventData.addEdgeStart,
                        target: 'tmp_node',
                     }
                  }
               ]);
            }
            break;

         case GLPIImpact.EDITION_DELETE:
            break;
      }
   }, 25),

   /**
    * Handle global mouseover events
    *
    * @param {JQuery.Event} event
    */
   onMouseover: function(event) {
      switch (GLPIImpact.editionMode) {
         case GLPIImpact.EDITION_DEFAULT:
            // No valid target, no action needed
            if (event.target.data('id') == undefined) {
               break;
            }

            if (event.target.isNode()) {
               // If mouseover on node, show grab cursor
               $(GLPIImpact.impactContainer).css('cursor', "grab");
            } else if (event.target.isEdge()) {
               // If mouseover on edge, show default cursor and disable panning
               $(GLPIImpact.impactContainer).css('cursor', "default");
               GLPIImpact.cy.panningEnabled(false);
            }
            break;

         case GLPIImpact.EDITION_ADD_NODE:
            if (event.target.data('id') == undefined) {
               break;
            }

            if (event.target.isNode()) {
               // If mouseover on node, show grab cursor
               $(GLPIImpact.impactContainer).css('cursor', "grab");
            } else if (event.target.isEdge()) {
               // If mouseover on edge, show default cursor and disable panning
               $(GLPIImpact.impactContainer).css('cursor', "default");
               GLPIImpact.cy.panningEnabled(false);
            }
            break;

         case GLPIImpact.EDITION_ADD_EDGE:
            break;

         case GLPIImpact.EDITION_DELETE:
            if (event.target.data('id') == undefined) {
               break;
            }

            $(GLPIImpact.impactContainer).css('cursor', "default");
            var id = event.target.data('id');

            // Remove red overlay
            event.cy.filter().data('todelete', 0);
            event.cy.filter().unselect();

            // Store here if one default node
            if (event.target.data('id') == GLPIImpact.startNode) {
               $(GLPIImpact.impactContainer).css('cursor', "not-allowed");
               break;
            }

            // Add red overlay
            event.target.data('todelete', 1);
            event.target.select();

            if (event.target.isNode()){
               var sourceFilter = "edge[source='" + id + "']";
               var targetFilter = "edge[target='" + id + "']";
               event.cy.filter(sourceFilter + ", " + targetFilter)
                  .data('todelete', 1)
                  .select();
            }
            break;
      }
   },

   /**
    * Handle global mouseout events
    *
    * @param {JQuery.Event} event
    */
   onMouseout: function(event) {
      switch (GLPIImpact.editionMode) {
         case GLPIImpact.EDITION_DEFAULT:
            $(GLPIImpact.impactContainer).css('cursor', "move");

            // Re-enable panning in case the mouse was over an edge
            GLPIImpact.cy.panningEnabled(true);
            break;

         case GLPIImpact.EDITION_ADD_NODE:
            $(GLPIImpact.impactContainer).css('cursor', "move");

            // Re-enable panning in case the mouse was over an edge
            GLPIImpact.cy.panningEnabled(true);
            break;

         case GLPIImpact.EDITION_ADD_EDGE:
            break;

         case GLPIImpact.EDITION_DELETE:
            // Remove red overlay
            $(GLPIImpact.impactContainer).css('cursor', "move");
            event.cy.filter().data('todelete', 0);
            event.cy.filter().unselect();
            break;
      }
   },

   /**
    * Handle "goTo" menu event
    *
    * @param {JQuery.Event} event
    */
   menuOnGoTo: function(event) {
      window.open(event.target.data('link'));
   },

   /**
    * Handle "showOngoing" menu event
    *
    * @param {JQuery.Event} event
    */
   menuOnShowOngoing: function(event) {
      $(GLPIImpact.selectors.ongoingDialog).html(
         GLPIImpact.buildOngoingDialogContent(event.target.data('ITILObjects'))
      );
      $(GLPIImpact.selectors.ongoingDialog).dialog(GLPIImpact.getOngoingDialog());
   },

   /**
    * Handle "EditCompound" menu event
    *
    * @param {JQuery.Event} event
    */
   menuOnEditCompound: function (event) {
      $(GLPIImpact.selectors.editCompoundDialog).dialog(
         GLPIImpact.getEditCompoundDialog(event.target)
      );
   },

   /**
    * Handler for "removeFromCompound" action
    *
    * @param {JQuery.Event} event
    */
   menuOnRemoveFromCompound: function(event) {
      var parent = GLPIImpact.cy.getElementById(
         event.target.data('parent')
      );

      // Remove node from compound
      event.target.move({parent: null});

      // Destroy compound if only one or zero member left
      if (parent.children().length < 2) {
         parent.children().move({parent: null});
         GLPIImpact.cy.remove(parent);
      }
   },

   /**
    * Handler for "delete" menu action
    *
    * @param {JQuery.Event} event
    */
   menuOnDelete: function(event){
      GLPIImpact.deleteFromGraph(event.target);
   },

   /**
    * Ask the backend for available assets to insert into the graph
    *
    * @param {String} itemtype
    * @param {Array}  used
    * @param {String} filter
    * @param {Number} page
    */
   searchAssets: function(itemtype, used, filter, page) {
      $(GLPIImpact.selectors.sideSearchSpinner).show();
      $(GLPIImpact.selectors.sideSearchNoResults).hide();
      $.ajax({
         type: "GET",
         url: $(GLPIImpact.selectors.form).prop('action'),
         data: {
            'action'  : 'search',
            'itemtype': itemtype,
            'used'    : used,
            'filter'  : filter,
            'page'    : page,
         },
         success: function(data){
            $.each(data.items, function(index, value) {
               var str = '<p data-id="' + value['id'] + '" data-type="' + itemtype + '">';
               str += '<img src="' + $(GLPIImpact.selectors.sideSearch + " img").attr('src') + '"></img>';
               str += value["name"];
               str += "</p>";

               $(GLPIImpact.selectors.sideSearchResults).append(str);
            });

            // All data was loaded, hide "More..."
            if (data.total <= ((page + 1) * 20)) {
               $(GLPIImpact.selectors.sideSearchMore).hide();
            } else {
               $(GLPIImpact.selectors.sideSearchMore).show();
            }

            // No results
            if (data.total == 0 && page == 0) {
               $(GLPIImpact.selectors.sideSearchNoResults).show();
            }

            $(GLPIImpact.selectors.sideSearchSpinner).hide();
         },
         error: function(){
            alert("error");
         },
      });
   },

   /**
    * Get the list of assets already on the graph
    */
   getUsedAssets: function() {
      // Get used ids for this itemtype
      var used = [];
      GLPIImpact.cy.nodes().forEach(function(node) {
         var nodeId = node.data('id')
            .split(GLPIImpact.NODE_ID_SEPERATOR);
         if (nodeId[0] == GLPIImpact.selectedItemtype) {
            used.push(parseInt(nodeId[1]));
         }
      });

      return used;
   },

   /**
    * Taken from cytoscape source, get the real position of the click event on
    * the cytoscape canvas
    *
    * @param   {Number} clientX
    * @param   {Number} clientY
    * @returns {Object}
    */
   projectIntoViewport: function (clientX, clientY) {
      var cy = this.cy;
      var offsets = this.findContainerClientCoords();
      var offsetLeft = offsets[0];
      var offsetTop = offsets[1];
      var scale = offsets[4];
      var pan = cy.pan();
      var zoom = cy.zoom();
      return {
         x: ((clientX - offsetLeft) / scale - pan.x) / zoom,
         y: ((clientY - offsetTop) / scale - pan.y) / zoom
      };
   },

   /**
    * Used for projectIntoViewport
    *
    * @returns {Array}
    */
   findContainerClientCoords: function () {
      var container = this.impactContainer[0];
      var rect = container.getBoundingClientRect();
      var style = window.getComputedStyle(container);

      var styleValue = function styleValue(name) {
         return parseFloat(style.getPropertyValue(name));
      };

      var padding = {
         left  : styleValue('padding-left'),
         right : styleValue('padding-right'),
         top   : styleValue('padding-top'),
         bottom: styleValue('padding-bottom')
      };
      var border = {
         left  : styleValue('border-left-width'),
         right : styleValue('border-right-width'),
         top   : styleValue('border-top-width'),
         bottom: styleValue('border-bottom-width')
      };
      var clientWidth      = container.clientWidth;
      var clientHeight     = container.clientHeight;
      var paddingHor       = padding.left + padding.right;
      var paddingVer       = padding.top + padding.bottom;
      var borderHor        = border.left + border.right;
      var scale            = rect.width / (clientWidth + borderHor);
      var unscaledW        = clientWidth - paddingHor;
      var unscaledH        = clientHeight - paddingVer;
      var left             = rect.left + padding.left + border.left;
      var top              = rect.top + padding.top + border.top;
      return [left, top, unscaledW, unscaledH, scale];
   },

   /**
    * Set event handler for toolbar events
    */
   initToolbar: function() {
      // Save the graph
      $(GLPIImpact.selectors.save).click(function() {
         GLPIImpact.showCleanWorkspaceStatus();
         // Send data as JSON on submit
         $.ajax({
            type: "POST",
            url: $(GLPIImpact.selectors.form).prop('action'),
            data: {
               'impacts': JSON.stringify(GLPIImpact.computeDelta())
            },
            success: function(){
               GLPIImpact.initialState = GLPIImpact.getCurrentState();
               $(document).trigger('impactUpdated');
            },
            error: function(){
               GLPIImpact.showDirtyWorkspaceStatus();
               alert("error");
            },
         });
      });

      // Add a new node on the graph
      $(GLPIImpact.selectors.addNode).click(function() {
         GLPIImpact.setEditionMode(GLPIImpact.EDITION_ADD_NODE);
      });

      // Add a new edge on the graph
      $(GLPIImpact.selectors.addEdge).click(function() {
         GLPIImpact.setEditionMode(GLPIImpact.EDITION_ADD_EDGE);
      });

      // Add a new compound on the graph
      $(GLPIImpact.selectors.addCompound).click(function() {
         GLPIImpact.setEditionMode(GLPIImpact.EDITION_ADD_COMPOUND);
      });

      // Enter delete mode
      $(GLPIImpact.selectors.deleteElement).click(function() {
         GLPIImpact.setEditionMode(GLPIImpact.EDITION_DELETE);
      });

      // Export graph
      $(GLPIImpact.selectors.export).click(function() {
         GLPIImpact.download(
            'png',
            false
         );
      });

      // Show settings
      $(this.selectors.impactSettings).click(function() {
         if ($(this).find('i.fa-chevron-right').length) {
            GLPIImpact.setEditionMode(GLPIImpact.EDITION_DEFAULT);
         } else {
            GLPIImpact.setEditionMode(GLPIImpact.EDITION_SETTINGS);
         }
      });

      // Toggle expanded toolbar
      $(this.selectors.sideToggle).click(function() {
         if ($(this).find('i.fa-chevron-right').length) {
            GLPIImpact.setEditionMode(GLPIImpact.EDITION_DEFAULT);
         } else {
            GLPIImpact.setEditionMode(GLPIImpact.EDITION_ADD_NODE);
         }
      });

      // Toggle impact visibility
      $(GLPIImpact.selectors.toggleImpact).click(function() {
         GLPIImpact.toggleVisibility(GLPIImpact.FORWARD);
         GLPIImpact.cy.trigger("change");
      });

      // Toggle depends visibility
      $(GLPIImpact.selectors.toggleDepends).click(function() {
         GLPIImpact.toggleVisibility(GLPIImpact.BACKWARD);
         GLPIImpact.cy.trigger("change");
      });

      // Depth selector
      $(GLPIImpact.selectors.maxDepth).on('input', function() {
         var max = $(GLPIImpact.selectors.maxDepth).val();
         GLPIImpact.maxDepth = max;

         if (max == GLPIImpact.MAX_DEPTH) {
            max = "infinity";
            GLPIImpact.maxDepth = GLPIImpact.NO_DEPTH_LIMIT;
         }

         $(GLPIImpact.selectors.maxDepthView).html(max);
         GLPIImpact.updateStyle();
         GLPIImpact.cy.trigger("change");
      });

      $(GLPIImpact.selectors.toggleFullscreen).click(function() {
         GLPIImpact.toggleFullscreen();
      });

      // Filter available itemtypes
      $(GLPIImpact.selectors.sideSearchFilterItemtype).on('input', function() {
         var value = $(GLPIImpact.selectors.sideSearchFilterItemtype).val().toLowerCase();

         $(GLPIImpact.selectors.sideFilterItem + ' img').each(function() {
            var itemtype = $(this).attr('title').toLowerCase();
            if (value == "" || itemtype.indexOf(value) != -1) {
               $(this).parent().show();
            } else {
               $(this).parent().hide();
            }
         });
      });

      // Exit type selection and enter asset search
      $(GLPIImpact.selectors.sideFilterItem).click(function() {
         var img = $(this).find('img').eq(0);

         GLPIImpact.selectedItemtype = $(img).attr('data-itemtype');
         $(GLPIImpact.selectors.sideSearch).show();
         $(GLPIImpact.selectors.sideSearch + " img").attr('title', $(img).attr('title'));
         $(GLPIImpact.selectors.sideSearch + " img").attr('src', $(img).attr('src'));
         $(GLPIImpact.selectors.sideSearch + " span").html($(img).attr('title'));
         $(GLPIImpact.selectors.sideSearchSelectItemtype).hide();

         // Empty search
         GLPIImpact.searchAssets(
            GLPIImpact.selectedItemtype,
            JSON.stringify(GLPIImpact.getUsedAssets()),
            $(GLPIImpact.selectors.sideFilterAssets).val(),
            0
         );
      });

      // Exit asset search and return to type selection
      $(GLPIImpact.selectors.sideSearch + ' > h4 > i').click(function() {
         $(GLPIImpact.selectors.sideSearch).hide();
         $(GLPIImpact.selectors.sideSearchSelectItemtype).show();
         $(GLPIImpact.selectors.sideSearchResults).html("");
      });

      $(GLPIImpact.selectors.sideFilterAssets).on('input', function() {
         // Reset results
         $(GLPIImpact.selectors.sideSearchResults).html("");
         $(GLPIImpact.selectors.sideSearchMore).hide();
         $(GLPIImpact.selectors.sideSearchSpinner).show();
         $(GLPIImpact.selectors.sideSearchNoResults).hide();

         searchAssetsDebounced(
            GLPIImpact.selectedItemtype,
            JSON.stringify(GLPIImpact.getUsedAssets()),
            $(GLPIImpact.selectors.sideFilterAssets).val(),
            0
         );
      });

      // Load more results on "More..." click
      $(GLPIImpact.selectors.sideSearchMore).on('click', function() {
         GLPIImpact.searchAssets(
            GLPIImpact.selectedItemtype,
            JSON.stringify(GLPIImpact.getUsedAssets()),
            $(GLPIImpact.selectors.sideFilterAssets).val(),
            ++GLPIImpact.addAssetPage
         );
      });

      // Watch for color changes (depends)
      $(GLPIImpact.selectors.dependsColor).change(function(){
         GLPIImpact.setEdgeColors({
            backward: $(GLPIImpact.selectors.dependsColor).val(),
         });
         GLPIImpact.updateStyle();
         GLPIImpact.cy.trigger("change");
      });

      // Watch for color changes (impact)
      $(GLPIImpact.selectors.impactColor).change(function(){
         GLPIImpact.setEdgeColors({
            forward: $(GLPIImpact.selectors.impactColor).val(),
         });
         GLPIImpact.updateStyle();
         GLPIImpact.cy.trigger("change");
      });

      // Watch for color changes (impact and depends)
      $(GLPIImpact.selectors.impactAndDependsColor).change(function(){
         GLPIImpact.setEdgeColors({
            both: $(GLPIImpact.selectors.impactAndDependsColor).val(),
         });
         GLPIImpact.updateStyle();
         GLPIImpact.cy.trigger("change");
      });

      // Handle drag & drop on add node search result
      $(document).on('mousedown', GLPIImpact.selectors.sideSearchResults + ' p', function(e) {
         // Only on left click
         if (e.which !== 1) {
            return;
         }

         // Tmp data to be shared with mousedown event
         GLPIImpact.eventData.addNodeStart = {
            id  : $(this).attr("data-id"),
            type: $(this).attr("data-type"),
         };

         // Show preview icon at cursor location
         $(GLPIImpact.selectors.dropPreview).css({
            left: e.clientX - 24,
            top: e.clientY - 24,
         });
         $(GLPIImpact.selectors.dropPreview).attr('src', $(this).find('img').attr('src'));
         $(GLPIImpact.selectors.dropPreview).show();

         $("*").css({cursor: "grabbing"});
      });

      // Handle drag & drop on add node search result
      $(document).on('mouseup', function(e) {
         if (GLPIImpact.eventData.addNodeStart === undefined) {
            return;
         }

         if (e.target.nodeName == "CANVAS") {
            // Add node at event position
            GLPIImpact.addNode(
               GLPIImpact.eventData.addNodeStart.id,
               GLPIImpact.eventData.addNodeStart.type,
               GLPIImpact.projectIntoViewport(e.clientX, e.clientY)
            );
         }

         $(GLPIImpact.selectors.dropPreview).hide();

         // Clear tmp event data
         GLPIImpact.eventData.addNodeStart = undefined;
         $("*").css('cursor', "");
      });

      $(document).on('mousemove', function(e) {
         if (GLPIImpact.eventData.addNodeStart === undefined) {
            return;
         }

         // Show preview icon at cursor location
         $(GLPIImpact.selectors.dropPreview).css({
            left: e.clientX - 24,
            top: e.clientY - 24,
         });
      });
   },
};

var searchAssetsDebounced = _.debounce(GLPIImpact.searchAssets, 400, false);
