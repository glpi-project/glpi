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
/* global showMenu */

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

   // Constants for ID separator
   NODE_ID_SEPERATOR: "::",
   EDGE_ID_SEPERATOR: "->",

   // Constants for delta action
   DELTA_ACTION_ADD   : 1,
   DELTA_ACTION_UPDATE: 2,
   DELTA_ACTION_DELETE: 3,

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

   // Is the graph readonly ?
   readonly: true,

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
            selector: '[hidden=1], [depth > ' + GLPIImpact.maxDepth + ']',
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
            content        : '<i class="fas fa-link"></i>' + this.getLocale("goTo"),
            tooltipText    : this.getLocale("goTo+"),
            selector       : 'node[!color]',
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
            onClickFunction: this.menuOnEditCompound,
            show           : !this.readonly,
         },
         {
            id             : 'removeFromCompound',
            content        : '<i class="fas fa-external-link-alt"></i>' + this.getLocale("removeFromCompound"),
            tooltipText    : this.getLocale("removeFromCompound+"),
            selector       : 'node:child',
            onClickFunction: this.menuOnRemoveFromCompound,
            show           : !this.readonly,
         },
         {
            id             : 'delete',
            content        : '<i class="fas fa-trash"></i>' + this.getLocale("delete"),
            tooltipText    : this.getLocale("delete+"),
            selector       : 'node, edge',
            onClickFunction: this.menuOnDelete,
            show           : !this.readonly,
         },
         {
            id             : 'new',
            content        : '<i class="fas fa-plus"></i>' + this.getLocale("new"),
            tooltipText    : this.getLocale("new+"),
            coreAsWell     : true,
            onClickFunction: this.menuOnNew,
            show           : !this.readonly,
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
         text: GLPIImpact.getLocale("add"),
         click: function() {
            var node = {
               itemtype: $(itemID).val(),
               items_id: $(itemType).val(),
            };
            var nodeID = GLPIImpact.makeID(GLPIImpact.NODE, node.itemtype, node.items_id);

            // Check if the node is already on the graph
            if (GLPIImpact.cy.filter('node[id="' + nodeID + '"]').length > 0) {
               alert(GLPIImpact.getLocale("duplicateAsset"));
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
                  $(GLPIImpact.dialogs.addNode.id).dialog("close");
                  GLPIImpact.setEditionMode(GLPIImpact.EDITION_DEFAULT);
               },
               function () {
                  // Ajax failed
                  alert(GLPIImpact.getLocale("unexpectedError"));
               }
            );
         }
      };

      // Exit edit mode
      var buttonCancel = {
         text: GLPIImpact.getLocale("cancel"),
         click: function() {
            $(this).dialog("close");
            GLPIImpact.setEditionMode(GLPIImpact.EDITION_DEFAULT);
         }
      };

      return {
         title: this.getLocale("newAsset"),
         modal: true,
         position: {
            my: 'center',
            at: 'center',
            of: GLPIImpact.impactContainer
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
      $(GLPIImpact.dialogs.configColor.inputs.dependsColor).spectrum(
         "set",
         GLPIImpact.edgeColors[GLPIImpact.BACKWARD]
      );
      $(GLPIImpact.dialogs.configColor.inputs.impactColor).spectrum(
         "set",
         GLPIImpact.edgeColors[GLPIImpact.FORWARD]
      );
      $(GLPIImpact.dialogs.configColor.inputs.impactAndDependsColor).spectrum(
         "set",
         GLPIImpact.edgeColors[GLPIImpact.BOTH]
      );

      var buttonUpdate = {
         text: "Update",
         click: function() {
            GLPIImpact.setEdgeColors({
               backward: backward.val(),
               forward : forward.val(),
               both    : both.val(),
            });
            GLPIImpact.updateStyle();
            $(this).dialog( "close" );
            GLPIImpact.cy.trigger("change");
         }
      };

      return {
         modal: true,
         width: 'auto',
         position: {
            my: 'center',
            at: 'center',
            of: GLPIImpact.impactContainer
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
         title: GLPIImpact.getLocale("ongoingTickets"),
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
      $(GLPIImpact.dialogs.editCompoundDialog.inputs.name).val(
         compound.data('label')
      );
      $(GLPIImpact.dialogs.editCompoundDialog.inputs.color).spectrum(
         "set",
         compound.data('color')
      );

      // Save group details
      var buttonSave = {
         text: GLPIImpact.getLocale("save"),
         click: function() {
            // Save compound name
            compound.data(
               'label',
               $(GLPIImpact.dialogs.editCompoundDialog.inputs.name).val()
            );

            // Save compound color
            compound.data(
               'color',
               $(GLPIImpact.dialogs.editCompoundDialog.inputs.color).val()
            );

            // Close dialog
            $(this).dialog("close");
            GLPIImpact.cy.trigger("change");
         }
      };

      return {
         title: GLPIImpact.getLocale("editGroup"),
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
    * Register the dialogs generated by the backend server
    *
    * @param {string} key
    * @param {string} id
    * @param {Object} inputs
    */
   registerDialog: function(key, id, inputs) {
      GLPIImpact.dialogs[key]['id'] = id;
      if (inputs) {
         Object.keys(inputs).forEach(function (inputKey){
            GLPIImpact.dialogs[key]['inputs'][inputKey] = inputs[inputKey];
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
      GLPIImpact.toolbar[key] = id;
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
      this.directionVisibility[GLPIImpact.FORWARD] = true;
      this.directionVisibility[GLPIImpact.BACKWARD] = true;

      // Set colors for edges
      this.setEdgeColors(colors);

      // Set start node
      this.startNode = startNode;

      // Register form
      this.form = form;

      // Register dialogs
      JSON.parse(dialogs).forEach(function(dialog) {
         GLPIImpact.registerDialog(dialog.key, dialog.id, dialog.inputs);
      });

      // Register toolbars
      JSON.parse(toolbar).forEach(function(element) {
         GLPIImpact.registerToobar(element.key, element.id);
      });
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
         GLPIImpact.toggleVisibility(GLPIImpact.BACKWARD);
      }
      if (!parseInt(params.show_impact)) {
         GLPIImpact.toggleVisibility(GLPIImpact.FORWARD);
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

      // Enter EDITION_DEFAULT mode
      this.setEditionMode(GLPIImpact.EDITION_DEFAULT);

      // Init depth value
      var text = GLPIImpact.maxDepth;
      if (GLPIImpact.maxDepth >= 10) {
         text = "infinity";
      }
      $(GLPIImpact.toolbar.maxDepthView).html("Max depth: " + text);
      $(GLPIImpact.toolbar.maxDepth).val(GLPIImpact.maxDepth);
   },

   /**
    * Set readonly and show toolbar
    */
   enableGraphEdition: function() {
      // Show toolbar
      $(this.toolbar.save).show();
      $(this.toolbar.addNode).show();
      $(this.toolbar.addEdge).show();
      $(this.toolbar.addCompound).show();
      $(this.toolbar.deleteElement).show();
      $(this.toolbar.expandToolbar).show();

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
      // Update toolbar icons
      if (toToggle == GLPIImpact.FORWARD) {
         $(GLPIImpact.toolbar.toggleImpact).find('i').toggleClass("fa-eye fa-eye-slash");
      } else {
         $(GLPIImpact.toolbar.toggleDepends).find('i').toggleClass("fa-eye fa-eye-slash");
      }

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
            $(this.toolbar.addNode).removeClass("active");
            break;

         case GLPIImpact.EDITION_ADD_EDGE:
            $(GLPIImpact.toolbar.addEdge).removeClass("active");
            // Empty event data and remove tmp node
            GLPIImpact.eventData.addEdgeStart = null;
            GLPIImpact.cy.filter("#tmp_node").remove();
            break;

         case GLPIImpact.EDITION_DELETE:
            this.cy.filter().unselect();
            this.cy.data('todelete', 0);
            $(GLPIImpact.toolbar.deleteElement).removeClass("active");
            break;

         case GLPIImpact.EDITION_ADD_COMPOUND:
            GLPIImpact.cy.panningEnabled(true);
            GLPIImpact.cy.boxSelectionEnabled(false);
            $(GLPIImpact.toolbar.addCompound).removeClass("active");
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
            this.clearHelpText();
            GLPIImpact.cy.nodes().grabify();
            $(this.impactContainer).css('cursor', "move");
            break;

         case GLPIImpact.EDITION_ADD_NODE:
            this.showHelpText("addNodeHelpText");
            $(this.toolbar.addNode).addClass("active");
            $(this.impactContainer).css('cursor', "copy");
            break;

         case GLPIImpact.EDITION_ADD_EDGE:
            this.showHelpText("addEdgeHelpText");
            $(this.toolbar.addEdge).addClass("active");
            $(this.impactContainer).css('cursor', "crosshair");
            break;

         case GLPIImpact.EDITION_DELETE:
            this.cy.filter().unselect();
            this.showHelpText("deleteHelpText");
            $(this.toolbar.deleteElement).addClass("active");
            break;

         case GLPIImpact.EDITION_ADD_COMPOUND:
            GLPIImpact.cy.panningEnabled(false);
            GLPIImpact.cy.boxSelectionEnabled(true);
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
      $(GLPIImpact.toolbar.helpText).html(this.getLocale(text)).show();
   },

   /**
    * Hide the help text and show the toolbar
    */
   clearHelpText: function() {
      $(GLPIImpact.toolbar.helpText).hide();
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
      $(GLPIImpact.toolbar.save).removeClass('dirty');
      $(GLPIImpact.toolbar.save).addClass('clean');
      $(GLPIImpact.toolbar.save).find('i').removeClass("fas fa-exclamation-triangle");
      $(GLPIImpact.toolbar.save).find('i').addClass("fas fa-check");
      $(GLPIImpact.toolbar.save).find('i').qtip(GLPIImpact.getTooltip("workspaceSaved"));
   },

   /**
    * Enable the save button
    */
   showDirtyWorkspaceStatus: function() {
      $(GLPIImpact.toolbar.save).removeClass('clean');
      $(GLPIImpact.toolbar.save).addClass('dirty');
      $(GLPIImpact.toolbar.save).find('i').removeClass("fas fa-check");
      $(GLPIImpact.toolbar.save).find('i').addClass("fas fa-exclamation-triangle");
      $(GLPIImpact.toolbar.save).find('i').qtip(this.getTooltip("unsavedChanges"));
   },

   /**
    * Enable the save button
    */
   showDefaultWorkspaceStatus: function() {
      $(GLPIImpact.toolbar.save).removeClass('clean');
      $(GLPIImpact.toolbar.save).removeClass('dirty');
      $(GLPIImpact.toolbar.save).find('i').removeClass("fas fa-check");
      $(GLPIImpact.toolbar.save).find('i').removeClass("fas fa-exclamation-triangle");
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
      if (GLPIImpact.eventData.boxSelected.length < 2) {
         alert(GLPIImpact.getLocale("notEnoughItems"));
      } else {
         // Create the compound
         var newCompound = GLPIImpact.cy.add({group: 'nodes'});

         // Set parent for coumpound member
         GLPIImpact.eventData.boxSelected.forEach(function(ele) {
            ele.move({'parent': newCompound.data('id')});
         });

         // Show edit dialog
         $(GLPIImpact.dialogs.editCompoundDialog.id).dialog(
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
    * Handle global click events
    *
    * @param {JQuery.Event} event
    */
   onClick: function (event) {
      switch (GLPIImpact.editionMode) {
         case GLPIImpact.EDITION_DEFAULT:
            break;

         case GLPIImpact.EDITION_ADD_NODE:
            // Click in EDITION_ADD_NODE : add a new node
            $(GLPIImpact.dialogs.addNode.id).dialog(GLPIImpact.getAddNodeDialog(
               GLPIImpact.dialogs.addNode.inputs.itemType,
               GLPIImpact.dialogs.addNode.inputs.itemID,
               event.position
            ));
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
         $(GLPIImpact.dialogs.editCompoundDialog.id).dialog(
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
         // ESC
         case 27:

            // Put toolbar on top
            $('.impact_toolbar').css({
               position: "fixed",
               top: "5px",
               "z-index":        81,
            });

            // Put impact container on top
            $("#network_container").css({
               position          : "fixed",
               left              : 0,
               right             : 0,
               top               : 0,
               bottom            : 0,
               width             : "100%",
               height            : "100%",
               "background-color": "white",
               "z-index":        80,
            });

            $("#network_container > canvas:eq(0)").css({
               height: "100vh"
            });

            $("#network_container > canvas:eq(0)").css({
               height: "100vh"
            });

            $('html, body').css('overflow', 'hidden');
            GLPIImpact.cy.resize();

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
      $(GLPIImpact.dialogs.ongoingDialog.id).html(
         GLPIImpact.buildOngoingDialogContent(event.target.data('ITILObjects'))
      );
      $(GLPIImpact.dialogs.ongoingDialog.id).dialog(GLPIImpact.getOngoingDialog());
   },

   /**
    * Handle "EditCompound" menu event
    *
    * @param {JQuery.Event} event
    */
   menuOnEditCompound: function (event) {
      $(GLPIImpact.dialogs.editCompoundDialog.id).dialog(
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
    * Handler for "new" menu action
    *
    * @param {JQuery.Event} event
    */
   menuOnNew: function(event) {
      $(GLPIImpact.dialogs.addNode.id).dialog(GLPIImpact.getAddNodeDialog(
         GLPIImpact.dialogs.addNode.inputs.itemType,
         GLPIImpact.dialogs.addNode.inputs.itemID,
         event.position
      ));
   },

   /**
    * Set event handler for toolbar events
    */
   initToolbar: function() {
      // Save the graph
      $(GLPIImpact.toolbar.save).click(function() {
         GLPIImpact.showCleanWorkspaceStatus();
         // Send data as JSON on submit
         $.ajax({
            type: "POST",
            url: $(GLPIImpact.form).prop('action'),
            data: {
               'impacts': JSON.stringify(GLPIImpact.computeDelta())
            },
            success: function(){
               GLPIImpact.initialState = GLPIImpact.getCurrentState();
            },
            error: function(){
               GLPIImpact.showDirtyWorkspaceStatus();
               alert("error");
            },
         });
      });

      // Add a new node on the graph
      $(GLPIImpact.toolbar.addNode).click(function() {
         GLPIImpact.setEditionMode(GLPIImpact.EDITION_ADD_NODE);
      });
      $(GLPIImpact.toolbar.addNode).qtip(this.getTooltip("addNodeTooltip"));

      // Add a new edge on the graph
      $(GLPIImpact.toolbar.addEdge).click(function() {
         GLPIImpact.setEditionMode(GLPIImpact.EDITION_ADD_EDGE);
      });
      $(GLPIImpact.toolbar.addEdge).qtip(this.getTooltip("addEdgeTooltip"));

      // Add a new compound on the graph
      $(GLPIImpact.toolbar.addCompound).click(function() {
         GLPIImpact.setEditionMode(GLPIImpact.EDITION_ADD_COMPOUND);
      });
      $(GLPIImpact.toolbar.addCompound).qtip(this.getTooltip("addCompoundTooltip"));

      // Enter delete mode
      $(GLPIImpact.toolbar.deleteElement).click(function() {
         GLPIImpact.setEditionMode(GLPIImpact.EDITION_DELETE);
      });
      $(GLPIImpact.toolbar.deleteElement).qtip(this.getTooltip("deleteTooltip"));

      // Export graph
      $(GLPIImpact.toolbar.export).click(function() {
         GLPIImpact.download(
            'png',
            false
         );
      });
      $(GLPIImpact.toolbar.export).qtip(this.getTooltip("downloadTooltip"));

      // "More" dropdown menu
      $(GLPIImpact.toolbar.expandToolbar).click(showMenu);

      // Toggle impact visibility
      $(GLPIImpact.toolbar.toggleImpact).click(function() {
         GLPIImpact.toggleVisibility(GLPIImpact.FORWARD);
         GLPIImpact.cy.trigger("change");
      });

      // Toggle depends visibility
      $(GLPIImpact.toolbar.toggleDepends).click(function() {
         GLPIImpact.toggleVisibility(GLPIImpact.BACKWARD);
         GLPIImpact.cy.trigger("change");
      });

      // Color picker
      $(GLPIImpact.toolbar.colorPicker).click(function() {
         $(GLPIImpact.dialogs.configColor.id).dialog(GLPIImpact.getColorPickerDialog(
            $(GLPIImpact.dialogs.configColor.inputs.dependsColor),
            $(GLPIImpact.dialogs.configColor.inputs.impactColor),
            $(GLPIImpact.dialogs.configColor.inputs.impactAndDependsColor)
         ));
      });

      // Depth selector
      $(GLPIImpact.toolbar.maxDepth).on('input', function() {
         var max = $(GLPIImpact.toolbar.maxDepth).val();
         GLPIImpact.maxDepth = max;

         if (max == 10) {
            max = "infinity";
            GLPIImpact.maxDepth = Number.MAX_VALUE;
         }

         $(GLPIImpact.toolbar.maxDepthView).html("Max depth: " + max);
         GLPIImpact.updateStyle();
         GLPIImpact.cy.trigger("change");
      });
   }
};


