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

// Constants to represent nodes and edges
var NODE = 1;
var EDGE = 2;

// Constants for graph direction (bitmask)
var DEFAULT  = 0;   // 0b00
var FORWARD  = 1;   // 0b01
var BACKWARD = 2;   // 0b10
var BOTH     = 3;   // 0b11

// Constants for graph edition mode
var EDITION_DEFAULT      = 1;
var EDITION_ADD_NODE     = 2;
var EDITION_ADD_EDGE     = 3;
var EDITION_DELETE       = 4;
var EDITION_ADD_COMPOUND = 5;

// Constants for ID separator
var NODE_ID_SEPERATOR = "::";
var EDGE_ID_SEPERATOR = "->";

// Constants for delta action
var DELTA_ACTION_ADD    = 1;
var DELTA_ACTION_UPDATE = 2;
var DELTA_ACTION_DELETE = 3;

// Load cytoscape
var cytoscape = window.cytoscape;

// Needed for JS lint validation
var _ = window._;
var showMenu = window.showMenu;

var impact = {

   // Store the initial state of the graph
   initialState: null,

   // Store translated labels
   locales: {},

   // Store the visibility settings of the different direction of the graph
   directionVisibility: {},

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

   // Form
   form: null,

   // Maximum depth of the graph
   maxDepth: 5,

   // Store registered dialogs and their inputs
   dialogs: {
      addNode: {
         id: null,
         inputs: {
            itemType: null,
            itemID  : null
         }
      },
      configColor: {
         id: null,
         inputs: {
            dependsColor         : null,
            impactColor          : null,
            impactAndDependsColor: null
         }
      },
      ongoingDialog: {
         id: null
      },
      editCompoundDialog: {
         id: null,
         inputs: {
            name : null,
            color: null
         }
      }
   },

   // Store registered toolbar items
   toolbar: {
      helpText     : null,
      tools        : null,
      save         : null,
      addNode      : null,
      addEdge      : null,
      addCompound  : null,
      deleteElement: null,
      export       : null,
      expandToolbar: null,
      toggleImpact : null,
      toggleDepends: null,
      colorPicker  : null,
      maxDepth     : null,
      maxDepthView : null,
   },

   // Data that needs to be stored/shared between events
   eventData: {
      addEdgeStart : null,   // Store starting node of a new edge
      tmpEles      : null,   // Temporary collection used when adding an edge
      lastClick    : null,   // Store last click timestamp
      boxSelected  : [],
      grabNodeStart: null,
      boundingBox  : null
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
            selector: '[hidden=1], [depth > ' + impact.maxDepth + ']',
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
            selector: '[flag=' + FORWARD + ']',
            style: {
               'line-color'        : this.edgeColors[FORWARD],
               'target-arrow-color': this.edgeColors[FORWARD],
            }
         },
         {
            selector: '[flag=' + BACKWARD + ']',
            style: {
               'line-color'        : this.edgeColors[BACKWARD],
               'target-arrow-color': this.edgeColors[BACKWARD],
            }
         },
         {
            selector: '[flag=' + BOTH + ']',
            style: {
               'line-color'        : this.edgeColors[BOTH],
               'target-arrow-color': this.edgeColors[BOTH],
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
      impact.cy.edges().forEach(function(edge) {
         data.edges[edge.data('id')] = {
            source: edge.data('source'),
            target: edge.data('target'),
         };
      });

      // Load compounds
      impact.cy.filter("node:parent").forEach(function(compound) {
         data.compounds[compound.data('id')] = {
            name: compound.data('label'),
            color: compound.data('color'),
         };
      });

      // Load items
      impact.cy.filter("node:childless").forEach(function(node) {
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
      Object.keys(impact.initialState.edges).forEach(function(edgeID) {
         var edge = impact.initialState.edges[edgeID];
         if (currentEdges.hasOwnProperty(edgeID)) {
            // If the edge is still here in the current state, nothing happened
            // Remove it from the currentEdges data so we can skip it later
            delete currentEdges[edgeID];
         } else {
            // If the edge is missing in the current state, it has been deleted
            var source = edge.source.split(NODE_ID_SEPERATOR);
            var target = edge.target.split(NODE_ID_SEPERATOR);
            edgesDelta[edgeID] = {
               action           : DELTA_ACTION_DELETE,
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
         var source = edge.source.split(NODE_ID_SEPERATOR);
         var target = edge.target.split(NODE_ID_SEPERATOR);
         edgesDelta[edgeID] = {
            action           : DELTA_ACTION_ADD,
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
      Object.keys(impact.initialState.compounds).forEach(function(compoundID) {
         var compound = impact.initialState.compounds[compoundID];
         if (currentCompounds.hasOwnProperty(compoundID)) {
            // If the compound is still here in the current state
            var currentCompound = currentCompounds[compoundID];

            // Check for updates ...
            if (compound.name != currentCompound.name
               || compound.color != currentCompound.color) {
               compoundsDelta[compoundID] = {
                  action: DELTA_ACTION_UPDATE,
                  name  : currentCompound.name,
                  color : currentCompound.color
               };
            }

            // Remove it from the currentCompounds data
            delete currentCompounds[compoundID];
         } else {
            // If the compound is missing in the current state, it's been deleted
            compoundsDelta[compoundID] = {
               action           : DELTA_ACTION_DELETE,
            };
         }
      });

      // Now iterate on the compounds we have in the current state
      Object.keys(currentCompounds).forEach(function (compoundID) {
         compoundsDelta[compoundID] = {
            action: DELTA_ACTION_ADD,
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

      // Prepare position map
      var nodesPositions = {};
      impact.cy.filter("node:childless").forEach(function(node) {
         nodesPositions[node.data('id')] = node.position();
      });

      // Now iterate on the parents we have in the current state
      Object.keys(currentNodes).forEach(function (nodeID) {
         var node = currentNodes[nodeID];
         itemsDelta[node.impactitem_id] = {
            action   : DELTA_ACTION_UPDATE,
            parent_id: node.parent,
         };

         // Set parent to 0 if null
         if (node.parent == undefined) {
            node.parent = 0;
         }

         if (nodeID == impact.startNode) {
            // Starting node of the graph, save viewport and edge colors
            itemsDelta[node.impactitem_id] = {
               action                  : DELTA_ACTION_UPDATE,
               parent_id               : node.parent,
               position_x              : node.position.x,
               position_y              : node.position.y,
               zoom                    : impact.cy.zoom(),
               pan_x                   : impact.cy.pan().x,
               pan_y                   : impact.cy.pan().y,
               impact_color            : impact.edgeColors[FORWARD],
               depends_color           : impact.edgeColors[BACKWARD],
               impact_and_depends_color: impact.edgeColors[BOTH],
               nodes_positions         : nodesPositions,
               show_depends            : impact.directionVisibility[BACKWARD],
               show_impact             : impact.directionVisibility[FORWARD],
               max_depth               : impact.maxDepth,
            };
         } else {
            // Others nodes of the graph, store only their parents and position
            itemsDelta[node.impactitem_id] = {
               action    : DELTA_ACTION_UPDATE,
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
            content        : '<i class="fas fa-link"></i>' + this.getLocale("goTo"),
            tooltipText    : this.getLocale("goTo+"),
            selector       : 'node',
            onClickFunction: this.menuOnGoTo
         },
         {
            id             : 'showOngoing',
            content        : '<i class="fas fa-list"></i>' + this.getLocale("showOngoing"),
            tooltipText    : this.getLocale("showOngoing+"),
            selector       : 'node[hasITILObjects=1]',
            onClickFunction: this.menuOnShowOngoing
         },
         {
            id             : 'editCompound',
            content        : '<i class="fas fa-edit"></i>' + this.getLocale("compoundProperties"),
            tooltipText    : this.getLocale("compoundProperties+"),
            selector       : 'node:parent',
            onClickFunction: this.menuOnEditCompound
         },
         {
            id             : 'removeFromCompound',
            content        : '<i class="fas fa-external-link-alt"></i>' + this.getLocale("removeFromCompound"),
            tooltipText    : this.getLocale("removeFromCompound+"),
            selector       : 'node:child',
            onClickFunction: this.menuOnRemoveFromCompound
         },
         {
            id             : 'delete',
            content        : '<i class="fas fa-trash"></i>' + this.getLocale("delete"),
            tooltipText    : this.getLocale("delete+"),
            selector       : 'node, edge',
            onClickFunction: this.menuOnDelete
         },
         {
            id             : 'new',
            content        : '<i class="fas fa-plus"></i>' + this.getLocale("new"),
            tooltipText    : this.getLocale("new+"),
            coreAsWell     : true,
            onClickFunction: this.menuOnNew
         }
      ];
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
   getAddNodeDialog: function(itemID, itemType, position) {
      // Build a new graph from the selected node and insert it
      var buttonAdd = {
         text: impact.getLocale("add"),
         click: function() {
            var node = {
               itemtype: $(itemID).val(),
               items_id: $(itemType).val(),
            };
            var nodeID = impact.makeID(NODE, node.itemtype, node.items_id);

            // Check if the node is already on the graph
            if (impact.cy.filter('node[id="' + nodeID + '"]').length > 0) {
               alert(impact.getLocale("duplicateAsset"));
               return;
            }

            // Build the new subgraph
            $.when(impact.buildGraphFromNode(node)).then(
               function (graph) {
                  // Insert the new graph data into the current graph
                  impact.insertGraph(graph, {
                     id: nodeID,
                     x: position.x,
                     y: position.y
                  });
                  impact.updateFlags();
                  $(impact.dialogs.addNode.id).dialog("close");
                  impact.setEditionMode(EDITION_DEFAULT);
               },
               function () {
                  // Ajax failed
                  alert(impact.getLocale("unexpectedError"));
               }
            );
         }
      };

      // Exit edit mode
      var buttonCancel = {
         text: impact.getLocale("cancel"),
         click: function() {
            $(this).dialog("close");
            impact.setEditionMode(EDITION_DEFAULT);
         }
      };

      return {
         title: this.getLocale("newAsset"),
         modal: true,
         position: {
            my: 'center',
            at: 'center',
            of: impact.impactContainer
         },
         buttons: [buttonAdd, buttonCancel]
      };
   },

   /**
    * Build the color picker dialog
    *
    * @param {JQuery} backward
    * @param {JQuery} forward
    * @param {JQuery} both
    *
    * @returns {Object}
    */
   getColorPickerDialog: function(backward, forward, both) {
      // Update color fields to match saved values
      $(impact.dialogs.configColor.inputs.dependsColor).spectrum(
         "set",
         impact.edgeColors[BACKWARD]
      );
      $(impact.dialogs.configColor.inputs.impactColor).spectrum(
         "set",
         impact.edgeColors[FORWARD]
      );
      $(impact.dialogs.configColor.inputs.impactAndDependsColor).spectrum(
         "set",
         impact.edgeColors[BOTH]
      );

      var buttonUpdate = {
         text: "Update",
         click: function() {
            impact.setEdgeColors({
               backward: backward.val(),
               forward : forward.val(),
               both    : both.val(),
            });
            impact.updateStyle();
            $(this).dialog( "close" );
            impact.cy.trigger("change");
         }
      };

      return {
         modal: true,
         width: 'auto',
         position: {
            my: 'center',
            at: 'center',
            of: impact.impactContainer
         },
         draggable: false,
         title: this.getLocale("colorConfiguration"),
         buttons: [buttonUpdate]
      };
   },

   /**
    * Build the add node dialog
    *
    * @returns {Object}
    */
   getOngoingDialog: function() {
      return {
         title: impact.getLocale("ongoingTickets"),
         modal: true,
         position: {
            my: 'center',
            at: 'center',
            of: impact.impactContainer
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
      $(impact.dialogs.editCompoundDialog.inputs.name).val(
         compound.data('label')
      );
      $(impact.dialogs.editCompoundDialog.inputs.color).spectrum(
         "set",
         compound.data('color')
      );

      // Save group details
      var buttonSave = {
         text: impact.getLocale("save"),
         click: function() {
            // Save compound name
            compound.data(
               'label',
               $(impact.dialogs.editCompoundDialog.inputs.name).val()
            );

            // Save compound color
            compound.data(
               'color',
               $(impact.dialogs.editCompoundDialog.inputs.color).val()
            );

            // Close dialog
            $(this).dialog("close");
            impact.cy.trigger("change");
         }
      };

      return {
         title: impact.getLocale("editGroup"),
         modal: true,
         position: {
            my: 'center',
            at: 'center',
            of: impact.impactContainer
         },
         buttons: [buttonSave]
      };
   },

   /**
    * Register the dialogs generated by the backend server
    *
    * @param {string} key
    * @param {string} id
    * @param {Object} inputs
    */
   registerDialog: function(key, id, inputs) {
      impact.dialogs[key]['id'] = id;
      if (inputs) {
         Object.keys(inputs).forEach(function (inputKey){
            impact.dialogs[key]['inputs'][inputKey] = inputs[inputKey];
         });
      }
   },

   /**
    * Register the toolbar elements generated by the backend server
    *
    * @param {string} key
    * @param {string} id
    */
   registerToobar: function(key, id) {
      impact.toolbar[key] = id;
   },

   /**
    * Create a tooltip for a toolbar's item
    *
    * @param {string} content
    *
    * @returns {Object}
    */
   getTooltip: function(content) {
      return {
         position: {
            my: 'bottom center',
            at: 'top center'
         },
         content: this.getLocale(content),
         style: {
            classes: 'qtip-shadow qtip-bootstrap'
         },
         show: {
            solo: true,
            delay: 100
         },
         hide: {
            fixed: true,
            delay: 100
         }
      };
   },

   /**
    * Initialise variables
    *
    * @param {JQuery} impactContainer
    * @param {string} locales json
    * @param {Object} colors properties: default, forward, backward, both
    * @param {string} startNode
    * @param {string} dialogs json
    * @param {string} toolbar json
    */
   prepareNetwork: function(
      impactContainer,
      locales,
      colors,
      startNode,
      form,
      dialogs,
      toolbar
   ) {

      // Set container
      this.impactContainer = impactContainer;

      // Set locales from json
      this.locales = JSON.parse(locales);

      // Init directionVisibility
      this.directionVisibility[FORWARD] = true;
      this.directionVisibility[BACKWARD] = true;

      // Set colors for edges
      this.setEdgeColors(colors);

      // Set start node
      this.startNode = startNode;

      // Register form
      this.form = form;

      // Register dialogs
      JSON.parse(dialogs).forEach(function(dialog) {
         impact.registerDialog(dialog.key, dialog.id, dialog.inputs);
      });

      // Register toolbars
      JSON.parse(toolbar).forEach(function(element) {
         impact.registerToobar(element.key, element.id);
      });
      this.initToolbar();
   },

   /**
    * Build the network graph
    *
    * @param {string} data (json)
    */
   buildNetwork: function(data, params) {
      // Init workspace status
      impact.showDefaultWorkspaceStatus();

      // Apply custom colors if defined
      if (params.impact_color != '') {
         this.setEdgeColors({
            forward : params.impact_color,
            backward: params.depends_color,
            both    : params.impact_and_depends_color,
         });
      }

      // Preset layout
      var layout = this.getPresetLayout();

      // Init cytoscape
      this.cy = cytoscape({
         container: this.impactContainer,
         elements : data,
         style    : this.getNetworkStyle(),
         layout   : layout,
         wheelSensitivity: 0.25,
      });

      // Store initial data
      this.initialState = this.getCurrentState();

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

      // Apply saved visibility
      if (!parseInt(params.show_depends)) {
         impact.toggleVisibility(BACKWARD);
      }
      if (!parseInt(params.show_impact)) {
         impact.toggleVisibility(FORWARD);
      }

      // Apply max depth
      this.maxDepth = params.max_depth;
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
         var viewport = impact.cy.extent();
         var empty = true;
         impact.cy.nodes().forEach(function(node) {
            if (node.position().x > viewport.x1
               && node.position().x < viewport.x2
               && node.position().y > viewport.x1
               && node.position().y < viewport.x2
            ){
               empty = false;
            }
         });

         if (empty || impact.cy.filter("node:childless").length == 1) {
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

      // Enter EDITION_DEFAULT mode
      this.setEditionMode(EDITION_DEFAULT);

      // Init depth value
      var text = impact.maxDepth;
      if (impact.maxDepth >= 10) {
         text = "infinity";
      }
      $(impact.toolbar.maxDepthView).html("Max depth: " + text);
      $(impact.toolbar.maxDepth).val(impact.maxDepth);
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
         case NODE:
            return a + "::" + b;
         case EDGE:
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
         var source = impact.cy.filter(impact.makeIDSelector(edge.data('source')));
         var target = impact.cy.filter(impact.makeIDSelector(edge.data('target')));
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
      this.exploreGraph(exploredNodes, FORWARD, this.startNode, 0);

      // Run through the graph backward
      exploredNodes = {};
      exploredNodes[this.startNode] = true;
      this.exploreGraph(exploredNodes, BACKWARD, this.startNode, 0);

      this.updateStyle();
   },

   /**
    * Toggle impact/depends visibility
    *
    * @param {*} toToggle
    */
   toggleVisibility: function(toToggle) {
      // Update toolbar icons
      if (toToggle == FORWARD) {
         $(impact.toolbar.toggleImpact).find('i').toggleClass("fa-eye fa-eye-slash");
      } else {
         $(impact.toolbar.toggleDepends).find('i').toggleClass("fa-eye fa-eye-slash");
      }

      // Update visibility setting
      impact.directionVisibility[toToggle] = !impact.directionVisibility[toToggle];

      // Compute direction
      var direction;
      var forward = impact.directionVisibility[FORWARD];
      var backward = impact.directionVisibility[BACKWARD];

      if (forward && backward) {
         direction = BOTH;
      } else if (!forward && backward) {
         direction = BACKWARD;
      } else if (forward && !backward) {
         direction = FORWARD;
      } else {
         direction = 0;
      }

      // Hide all nodes
      impact.cy.filter("node").data('hidden', 1);

      // Show/Hide edges according to the direction
      impact.cy.filter("edge").forEach(function(edge) {
         if (edge.data('flag') & direction) {
            edge.data('hidden', 0);

            // If the edge is visible, show the nodes they are connected to it
            var sourceFilter = "node[id='" + edge.data('source') + "']";
            var targetFilter = "node[id='" + edge.data('target') + "']";
            impact.cy.filter(sourceFilter + ", " + targetFilter)
               .data("hidden", 0);

            // Make the parents of theses node visibles too
            impact.cy.filter(sourceFilter + ", " + targetFilter)
               .parent()
               .data("hidden", 0);
         } else {
            edge.data('hidden', 1);
         }
      });

      // Start node should always be visible
      impact.cy.filter(impact.makeIDSelector(impact.startNode))
         .data("hidden", 0);

      impact.updateStyle();
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
         case FORWARD:
            sourceOrTarget = "source";
            nextNode       = "target";
            break;
         case BACKWARD:
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
               impact.exploreGraph(exploredNodes, direction, targetNode, depth);
            }
         });
   },

   /**
    * Get translated value for a given key
    *
    * @param {string} key
    */
   getLocale: function(key) {
      return this.locales[key];
   },

   /**
    * Ask the backend to build a graph from a specific node
    *
    * @param {Object} node
    * @returns {Array|null}
    */
   buildGraphFromNode: function(node) {
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
      var options = impact.getDagreLayout();

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
            eles : impact.cy.filter(""),
         },
      });
   },

   /**
    * Set the colors
    *
    * @param {object} colors default, backward, forward, both
    */
   setEdgeColors: function (colors) {
      this.setColorIfExist(DEFAULT, colors.default);
      this.setColorIfExist(BACKWARD, colors.backward);
      this.setColorIfExist(FORWARD, colors.forward);
      this.setColorIfExist(BOTH, colors.both);
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
         mode = EDITION_DEFAULT;
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
         case EDITION_DEFAULT:
            impact.cy.nodes().ungrabify();
            break;

         case EDITION_ADD_NODE:
            $(this.toolbar.addNode).removeClass("active");
            break;

         case EDITION_ADD_EDGE:
            $(impact.toolbar.addEdge).removeClass("active");
            // Empty event data and remove tmp node
            impact.eventData.addEdgeStart = null;
            impact.cy.filter("#tmp_node").remove();
            break;

         case EDITION_DELETE:
            this.cy.filter().unselect();
            this.cy.data('todelete', 0);
            $(impact.toolbar.deleteElement).removeClass("active");
            break;

         case EDITION_ADD_COMPOUND:
            impact.cy.panningEnabled(true);
            impact.cy.boxSelectionEnabled(false);
            $(impact.toolbar.addCompound).removeClass("active");
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
         case EDITION_DEFAULT:
            this.clearHelpText();
            impact.cy.nodes().grabify();
            $(this.impactContainer).css('cursor', "move");
            break;

         case EDITION_ADD_NODE:
            this.showHelpText("addNodeHelpText");
            $(this.toolbar.addNode).addClass("active");
            $(this.impactContainer).css('cursor', "copy");
            break;

         case EDITION_ADD_EDGE:
            this.showHelpText("addEdgeHelpText");
            $(this.toolbar.addEdge).addClass("active");
            $(this.impactContainer).css('cursor', "crosshair");
            break;

         case EDITION_DELETE:
            this.cy.filter().unselect();
            this.showHelpText("deleteHelpText");
            $(this.toolbar.deleteElement).addClass("active");
            break;

         case EDITION_ADD_COMPOUND:
            impact.cy.panningEnabled(false);
            impact.cy.boxSelectionEnabled(true);
            this.showHelpText("addCompoundHelpText");
            $(this.toolbar.addCompound).addClass("active");
            $(this.impactContainer).css('cursor', "crosshair");
            break;
      }
   },

   /**
    * Hide the toolbar and show an help text
    *
    * @param {string} text
    */
   showHelpText: function(text) {
      $(impact.toolbar.helpText).html(this.getLocale(text)).show();
   },

   /**
    * Hide the help text and show the toolbar
    */
   clearHelpText: function() {
      $(impact.toolbar.helpText).hide();
   },

   /**
    * Export the graph in the given format
    *
    * @param {string} format
    * @param {boolean} transparentBackground (png only)
    * @param {JQuery} link
    *
    * @returns {Object} filename, filecontent
    */
   download: function(format, transparentBackground, link) {
      var filename;
      var filecontent;

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

      link.prop('download', filename);
      link.prop("href", filecontent);
      link[0].click();
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
      $(impact.toolbar.save).removeClass('dirty');
      $(impact.toolbar.save).addClass('clean');
      $(impact.toolbar.save).find('i').removeClass("fas fa-exclamation-triangle");
      $(impact.toolbar.save).find('i').addClass("fas fa-check");
      $(impact.toolbar.save).find('i').qtip(impact.getTooltip("workspaceSaved"));
   },

   /**
    * Enable the save button
    */
   showDirtyWorkspaceStatus: function() {
      $(impact.toolbar.save).removeClass('clean');
      $(impact.toolbar.save).addClass('dirty');
      $(impact.toolbar.save).find('i').removeClass("fas fa-check");
      $(impact.toolbar.save).find('i').addClass("fas fa-exclamation-triangle");
      $(impact.toolbar.save).find('i').qtip(this.getTooltip("unsavedChanges"));
   },

   /**
    * Enable the save button
    */
   showDefaultWorkspaceStatus: function() {
      $(impact.toolbar.save).removeClass('clean');
      $(impact.toolbar.save).removeClass('dirty');
      $(impact.toolbar.save).find('i').removeClass("fas fa-check");
      $(impact.toolbar.save).find('i').removeClass("fas fa-exclamation-triangle");
   },

   /**
    * Build the ongoing dialog content according to the list of ITILObjects
    *
    * @param {Object} ITILObjects requests, incidents, changes, problems
    *
    * @returns {string}
    */
   buildOngoingDialogContent: function(ITILObjects) {
      return this.listElements("requests", ITILObjects.requests, "ticket")
         + this.listElements("incidents", ITILObjects.incidents, "ticket")
         + this.listElements("changes", ITILObjects.changes , "change")
         + this.listElements("problems", ITILObjects.problems, "problem");
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
         html += "<h3>" + this.getLocale(title) + "</h3>";
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
      if (impact.eventData.boxSelected.length < 2) {
         alert(impact.getLocale("notEnoughItems"));
      } else {
         // Create the compound
         var newCompound = impact.cy.add({group: 'nodes'});

         // Set parent for coumpound member
         impact.eventData.boxSelected.forEach(function(ele) {
            ele.move({'parent': newCompound.data('id')});
         });

         // Show edit dialog
         $(impact.dialogs.editCompoundDialog.id).dialog(
            impact.getEditCompoundDialog(newCompound)
         );

         // Back to default mode
         impact.setEditionMode(EDITION_DEFAULT);
      }

      // Clear the selection
      impact.eventData.boxSelected = [];
      impact.cy.filter(":selected").unselect();
   }, 100, false),

   /**
    * Remove an element from the graph
    *
    * @param {object} ele
    */
   deleteFromGraph: function(ele) {
      if (ele.data('id') == impact.startNode) {
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
      impact.updateFlags();
   },

   /**
    * Handle global click events
    *
    * @param {JQuery.Event} event
    */
   onClick: function (event) {
      switch (impact.editionMode) {
         case EDITION_DEFAULT:
            break;

         case EDITION_ADD_NODE:
            // Click in EDITION_ADD_NODE : add a new node
            $(impact.dialogs.addNode.id).dialog(impact.getAddNodeDialog(
               impact.dialogs.addNode.inputs.itemType,
               impact.dialogs.addNode.inputs.itemID,
               event.position
            ));
            break;

         case EDITION_ADD_EDGE:
            break;

         case EDITION_DELETE:
            break;
      }
   },

   /**
    * Handle click on edge
    *
    * @param {JQuery.Event} event
    */
   edgeOnClick: function (event) {
      switch (impact.editionMode) {
         case EDITION_DEFAULT:
            break;

         case EDITION_ADD_NODE:
            break;

         case EDITION_ADD_EDGE:
            break;

         case EDITION_DELETE:
            // Remove the edge from the graph
            impact.deleteFromGraph(event.target);
            break;
      }
   },

   /**
    * Handle click on node
    *
    * @param {JQuery.Event} event
    */
   nodeOnClick: function (event) {
      switch (impact.editionMode) {
         case EDITION_DEFAULT:
            if (impact.eventData.lastClick != null) {
               // Trigger homemade double click event
               if (event.timeStamp - impact.eventData.lastClick < 500) {
                  event.target.trigger('doubleClick', event);
               }
            }

            impact.eventData.lastClick = event.timeStamp;
            break;

         case EDITION_ADD_NODE:
            break;

         case EDITION_ADD_EDGE:
            break;

         case EDITION_DELETE:
            impact.deleteFromGraph(event.target);
            break;
      }
   },

   /**
    * Handle end of box selection event
    *
    * @param {JQuery.Event} event
    */
   onBox: function (event) {
      switch (impact.editionMode) {
         case EDITION_DEFAULT:
            break;

         case EDITION_ADD_NODE:
            break;

         case EDITION_ADD_EDGE:
            break;

         case EDITION_DELETE:
            break;

         case EDITION_ADD_COMPOUND:
            var ele = event.target;
            // Add node to selected list if he is not part of a compound already
            if (ele.isNode() && ele.isOrphan() && !ele.isParent()) {
               impact.eventData.boxSelected.push(ele);
            }
            impact.addCompoundFromSelection();
            break;
      }
   },

   /**
    * Handle any graph modification
    *
    * @param {*} event
    */
   onChange: function() {
      impact.showDirtyWorkspaceStatus();
   },

   /**
    * Double click handler
    * @param {JQuery.Event} event
    */
   onDoubleClick: function(event) {
      // Open edit dialog on compound nodes
      if (event.target.isParent()) {
         $(impact.dialogs.editCompoundDialog.id).dialog(
            impact.getEditCompoundDialog(event.target)
         );
      }
   },

   /**
    * Handler for key down events
    *
    * @param {JQuery.Event} event
    */
   onKeyDown: function(event) {
      switch (event.which) {
         // ESC
         case 27:
            // Exit specific edition mode
            if (impact.editionMode != EDITION_DEFAULT) {
               impact.setEditionMode(EDITION_DEFAULT);
            }
            break;

         // Delete
         case 46:
            // Delete selected elements
            impact.cy.filter(":selected").forEach(function(ele) {
               impact.deleteFromGraph(ele);
            });
            break;
      }
   },

   /**
    * Handle mousedown events on nodes
    *
    * @param {JQuery.Event} event
    */
   nodeOnMousedown: function (event) {
      switch (impact.editionMode) {
         case EDITION_DEFAULT:
            $(impact.impactContainer).css('cursor', "grabbing");

            // If we are not on a compound node or a node already inside one
            if (event.target.isOrphan() && !event.target.isParent()) {
               impact.eventData.grabNodeStart = event.target;
            }
            break;

         case EDITION_ADD_NODE:
            break;

         case EDITION_ADD_EDGE:
            if (!event.target.isParent()) {
               impact.eventData.addEdgeStart = this.data('id');
            }
            break;

         case EDITION_DELETE:
            break;

         case EDITION_ADD_COMPOUND:
            break;
      }
   },

   /**
    * Handle mouseup events on nodes
    *
    * @param {JQuery.Event} event
    */
   nodeOnMouseup: function (event) {
      switch (impact.editionMode) {
         case EDITION_DEFAULT:
            $(impact.impactContainer).css('cursor', "grab");

            // Check if we were grabbing a node
            if (impact.eventData.grabNodeStart != null) {
               // Reset eventData for node grabbing
               impact.eventData.grabNodeStart = null;
               impact.eventData.boundingBox = null;
            }

            break;

         case EDITION_ADD_NODE:
            break;

         case EDITION_ADD_EDGE:
            // Exit if no start node
            if (impact.eventData.addEdgeStart == null) {
               return;
            }

            // Reset addEdgeStart
            var startEdge = impact.eventData.addEdgeStart; // Keep a copy to use later
            impact.eventData.addEdgeStart = null;

            // Remove current tmp collection
            event.cy.remove(impact.eventData.tmpEles);
            impact.eventData.tmpEles = null;

            // Option 1: Edge between a node and the fake tmp_node -> ignore
            if (this.data('id') == 'tmp_node') {
               return;
            }

            // Option 2: Edge between two nodes that already exist -> ignore
            var edgeID = impact.makeID(EDGE, startEdge, this.data('id'));
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
            impact.updateFlags();
            break;

         case EDITION_DELETE:
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

      switch (impact.editionMode) {
         case EDITION_DEFAULT:
            // No action if we are not grabbing a node
            if (impact.eventData.grabNodeStart == null) {
               return;
            }

            // Look for a compound at the cursor position
            node = impact.getNodeAt(event.position, function(node) {
               return node.isParent();
            });

            if (node) {
               // If we have a bounding box defined, the grabbed node is already
               // being placed into a compound, we need to check if it was moved
               // outside this original bouding box to know if the user is trying
               // to move if away from the compound
               if (impact.eventData.boundingBox != null) {
                  // If the user tried to move out of the compound
                  if (impact.eventData.boundingBox.x1 > event.position.x
                     || impact.eventData.boundingBox.x2 < event.position.x
                     || impact.eventData.boundingBox.y1 > event.position.y
                     || impact.eventData.boundingBox.y2 < event.position.y) {
                     // Remove it from the compound
                     impact.eventData.grabNodeStart.move({parent: null});
                     impact.eventData.boundingBox = null;
                  }
               } else {
                  // If we found a compound, add the grabbed node inside
                  impact.eventData.grabNodeStart.move({parent: node.data('id')});

                  // Store the original bouding box of the compound
                  impact.eventData.boundingBox = node.boundingBox();
               }
            } else {
               // Else; reset it's parent so it can be removed from any temporary
               // compound while the user is stil grabbing
               impact.eventData.grabNodeStart.move({parent: null});
            }

            break;

         case EDITION_ADD_NODE:
            break;

         case EDITION_ADD_EDGE:
            // No action if we are not placing an edge
            if (impact.eventData.addEdgeStart == null) {
               return;
            }

            // Remove current tmp collection
            if (impact.eventData.tmpEles != null) {
               event.cy.remove(impact.eventData.tmpEles);
            }

            node = impact.getNodeAt(event.position, function(node) {
               var nodeID = node.data('id');

               // Can't link to itself
               if (nodeID == impact.eventData.addEdgeStart) {
                  return false;
               }

               // Can't link to parent
               if (node.isParent()) {
                  return false;
               }

               // The created edge shouldn't already exist
               var edgeID = impact.makeID(EDGE, impact.eventData.addEdgeStart, nodeID);
               if (impact.cy.filter('edge[id="' + edgeID + '"]').length > 0) {
                  return false;
               }

               // The node must be visible
               if (impact.cy.getElementById(nodeID).data('hidden')) {
                  return false;
               }

               return true;
            });

            if (node != null) {
               node = node.data('id');

               // Add temporary edge to node hovered by the user
               impact.eventData.tmpEles = event.cy.add([
                  {
                     group: 'edges',
                     data: {
                        id: impact.makeID(EDGE, impact.eventData.addEdgeStart, node),
                        source: impact.eventData.addEdgeStart,
                        target: node
                     }
                  }
               ]);
            } else {
               // Add temporary edge to a new invisible node at mouse position
               impact.eventData.tmpEles = event.cy.add([
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
                        id: impact.makeID(
                           EDGE,
                           impact.eventData.addEdgeStart,
                           "tmp_node"
                        ),
                        source: impact.eventData.addEdgeStart,
                        target: 'tmp_node',
                     }
                  }
               ]);
            }
            break;

         case EDITION_DELETE:
            break;
      }
   }, 25),

   /**
    * Handle global mouseover events
    *
    * @param {JQuery.Event} event
    */
   onMouseover: function(event) {
      switch (impact.editionMode) {
         case EDITION_DEFAULT:
            if (event.target.data('id') == undefined || !event.target.isNode()) {
               break;
            }
            $(impact.impactContainer).css('cursor', "grab");
            break;

         case EDITION_ADD_NODE:
            break;

         case EDITION_ADD_EDGE:
            break;

         case EDITION_DELETE:
            if (event.target.data('id') == undefined) {
               break;
            }

            $(impact.impactContainer).css('cursor', "default");
            var id = event.target.data('id');

            // Remove red overlay
            event.cy.filter().data('todelete', 0);
            event.cy.filter().unselect();

            // Store here if one default node
            if (event.target.data('id') == impact.startNode) {
               $(impact.impactContainer).css('cursor', "not-allowed");
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
      switch (impact.editionMode) {
         case EDITION_DEFAULT:
            $(impact.impactContainer).css('cursor', "move");
            break;

         case EDITION_ADD_NODE:
            break;

         case EDITION_ADD_EDGE:
            break;

         case EDITION_DELETE:
            // Remove red overlay
            $(impact.impactContainer).css('cursor', "move");
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
      window.open(event.target.data('link'), 'blank');
   },

   /**
    * Handle "showOngoing" menu event
    *
    * @param {JQuery.Event} event
    */
   menuOnShowOngoing: function(event) {
      $(impact.dialogs.ongoingDialog.id).html(
         impact.buildOngoingDialogContent(event.target.data('ITILObjects'))
      );
      $(impact.dialogs.ongoingDialog.id).dialog(impact.getOngoingDialog());
   },

   /**
    * Handle "EditCompound" menu event
    *
    * @param {JQuery.Event} event
    */
   menuOnEditCompound: function (event) {
      $(impact.dialogs.editCompoundDialog.id).dialog(
         impact.getEditCompoundDialog(event.target)
      );
   },

   /**
    * Handler for "removeFromCompound" action
    *
    * @param {JQuery.Event} event
    */
   menuOnRemoveFromCompound: function(event) {
      var parent = impact.cy.getElementById(
         event.target.data('parent')
      );

      // Remove node from compound
      event.target.move({parent: null});

      // Destroy compound if only one or zero member left
      if (parent.children().length < 2) {
         parent.children().move({parent: null});
         impact.cy.remove(parent);
      }
   },

   /**
    * Handler for "delete" menu action
    *
    * @param {JQuery.Event} event
    */
   menuOnDelete: function(event){
      impact.deleteFromGraph(event.target);
   },

   /**
    * Handler for "new" menu action
    *
    * @param {JQuery.Event} event
    */
   menuOnNew: function(event) {
      $(impact.dialogs.addNode.id).dialog(impact.getAddNodeDialog(
         impact.dialogs.addNode.inputs.itemType,
         impact.dialogs.addNode.inputs.itemID,
         event.position
      ));
   },

   /**
    * Set event handler for toolbar events
    */
   initToolbar: function() {
      // Save the graph
      $(impact.toolbar.save).click(function() {
         impact.showCleanWorkspaceStatus();
         // Send data as JSON on submit
         $.ajax({
            type: "POST",
            url: $(impact.form).prop('action'),
            data: {
               'impacts': JSON.stringify(impact.computeDelta())
            },
            success: function(){
               impact.initialState = impact.getCurrentState();
            },
            error: function(){
               impact.showDirtyWorkspaceStatus();
               alert("error");
            },
         });
      });

      // Add a new node on the graph
      $(impact.toolbar.addNode).click(function() {
         impact.setEditionMode(EDITION_ADD_NODE);
      });
      $(impact.toolbar.addNode).qtip(this.getTooltip("addNodeTooltip"));

      // Add a new edge on the graph
      $(impact.toolbar.addEdge).click(function() {
         impact.setEditionMode(EDITION_ADD_EDGE);
      });
      $(impact.toolbar.addEdge).qtip(this.getTooltip("addEdgeTooltip"));

      // Add a new compound on the graph
      $(impact.toolbar.addCompound).click(function() {
         impact.setEditionMode(EDITION_ADD_COMPOUND);
      });
      $(impact.toolbar.addCompound).qtip(this.getTooltip("addCompoundTooltip"));

      // Enter delete mode
      $(impact.toolbar.deleteElement).click(function() {
         impact.setEditionMode(EDITION_DELETE);
      });
      $(impact.toolbar.deleteElement).qtip(this.getTooltip("deleteTooltip"));

      // Export graph
      $(impact.toolbar.export).click(function() {
         impact.download(
            'png',
            false,
            $(impact.dialogs.exportDialog.inputs.link)
         );
      });
      $(impact.toolbar.export).qtip(this.getTooltip("downloadTooltip"));

      // "More" dropdown menu
      $(impact.toolbar.expandToolbar).click(showMenu);

      // Toggle impact visibility
      $(impact.toolbar.toggleImpact).click(function() {
         impact.toggleVisibility(FORWARD);
         impact.cy.trigger("change");
      });

      // Toggle depends visibility
      $(impact.toolbar.toggleDepends).click(function() {
         impact.toggleVisibility(BACKWARD);
         impact.cy.trigger("change");
      });

      // Color picker
      $(impact.toolbar.colorPicker).click(function() {
         $(impact.dialogs.configColor.id).dialog(impact.getColorPickerDialog(
            $(impact.dialogs.configColor.inputs.dependsColor),
            $(impact.dialogs.configColor.inputs.impactColor),
            $(impact.dialogs.configColor.inputs.impactAndDependsColor)
         ));
      });

      // Depth selector
      $(impact.toolbar.maxDepth).on('input', function() {
         var max = $(impact.toolbar.maxDepth).val();
         impact.maxDepth = max;

         if (max == 10) {
            max = "infinity";
            impact.maxDepth = Number.MAX_VALUE;
         }

         $(impact.toolbar.maxDepthView).html("Max depth: " + max);
         impact.updateStyle();
         impact.cy.trigger("change");
      });
   }
};


