/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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
/* global hexToRgb */
/* global contrast */

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

   // Constants for action stack
   ACTION_MOVE                         : 1,
   ACTION_ADD_NODE                     : 2,
   ACTION_ADD_EDGE                     : 3,
   ACTION_ADD_COMPOUND                 : 4,
   ACTION_ADD_GRAPH                    : 5,
   ACTION_EDIT_COMPOUND                : 6,
   ACTION_REMOVE_FROM_COMPOUND         : 7,
   ACTION_DELETE                       : 8,
   ACTION_EDIT_MAX_DEPTH               : 9,
   ACTION_EDIT_IMPACT_VISIBILITY       : 10,
   ACTION_EDIT_DEPENDS_VISIBILITY      : 11,
   ACTION_EDIT_DEPENDS_COLOR           : 12,
   ACTION_EDIT_IMPACT_COLOR            : 13,
   ACTION_EDIT_IMPACT_AND_DEPENDS_COLOR: 14,

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

   // Start node of the graph (id)
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

   // Action stack for undo/redo
   undoStack: [],
   redoStack: [],

   // Buffer used when generating positions for unset nodes
   no_positions: [],

   // Register badges hitbox so they can be clicked
   badgesHitboxes: [],

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
      undo            : "#impact_undo",
      redo            : "#impact_redo",

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
      addEdgeStart       : null,        // Store starting node of a new edge
      tmpEles            : null,        // Temporary collection used when adding an edge
      lastClicktimestamp : null,        // Store last click timestamp
      lastClickTarget    : null,        // Store last click target
      boxSelected        : [],
      grabNodeStart      : null,
      boundingBox        : null,
      showPointerForBadge: false,
      previousCursor     : "default",
      ctrlDown           : false,
   },

   /**
    * Add given action to undo stack and reset redo stack
    * @param {Number} action_code const ACTION_XXXX
    * @param {Object} data        data specific to the action
    */
   addToUndo : function(action_code, data) {
      // Add new item to undo list
      this.undoStack.push({
         code: action_code,
         data: data
      });
      $(this.selectors.undo).removeClass("impact-disabled");

      // Clear redo list
      this.redoStack = [];
      $(this.selectors.redo).addClass("impact-disabled");
   },

   /**
    * Undo last action
    */
   undo: function() {
      // Empty stack, stop here
      if (this.undoStack.length === 0) {
         return;
      }

      var action = this.undoStack.pop();
      var data = action.data;

      // Add action to redo stack
      this.redoStack.push(action);
      $(this.selectors.redo).removeClass("impact-disabled");

      switch (action.code) {
         // Set node to old position
         // Available data: node, oldPosition, newPosition and newParent
         case this.ACTION_MOVE:
            this.cy.filter("node" + this.makeIDSelector(data.node))
               .position({
                  x: data.oldPosition.x,
                  y: data.oldPosition.y,
               });

            if (data.newParent !== null) {
               this.cy.filter("node" + this.makeIDSelector(data.node))
                  .move({parent: null});
            }
            break;

         // Remove node
         // Available data: toAdd
         case this.ACTION_ADD_NODE:
            this.cy.getElementById(data.toAdd.data.id).remove();
            break;

         // Delete edge
         // Available data; id, data
         case this.ACTION_ADD_EDGE:
            this.cy.remove("edge" + this.makeIDSelector(data.id));
            this.updateFlags();
            break;

         // Delete compound
         // Available data: data, children
         case this.ACTION_ADD_COMPOUND:
            data.children.forEach(function(id) {
               GLPIImpact.cy.filter("node" + GLPIImpact.makeIDSelector(id))
                  .move({parent: null});
            });
            this.cy.remove("node" + this.makeIDSelector(data.data.id));
            this.updateFlags();
            break;

         // Remove the newly added graph
         // Available data: edges, nodes, compounds
         case this.ACTION_ADD_GRAPH:
            // Delete edges
            data.edges.forEach(function(edge) {
               GLPIImpact.cy.getElementById(edge.id).remove();
            });

            // Delete compounds
            data.compounds.forEach(function(compound) {
               compound.compoundChildren.forEach(function(nodeId) {
                  GLPIImpact.cy.getElementById(nodeId).move({
                     parent: null
                  });
               });

               GLPIImpact.cy.getElementById(compound.compoundData.id).remove();
            });

            // Delete nodes
            data.nodes.forEach(function(node) {
               GLPIImpact.cy.getElementById(node.nodeData.id).remove();
            });

            this.updateFlags();

            break;

         // Revert edit
         // Available data: id, label, color, oldLabel, oldColor
         case this.ACTION_EDIT_COMPOUND:
            this.cy.filter("node" + this.makeIDSelector(data.id)).data({
               label: data.oldLabel,
               color: data.oldColor,
            });
            GLPIImpact.cy.trigger("change");
            break;

         // Re-add node to the compound (and recreate it needed)
         // Available data: nodeData, compoundData, children
         case this.ACTION_REMOVE_FROM_COMPOUND:
            if (data.children.length <= 2) {
               // Recreate the compound and re-add every nodes
               this.cy.add({
                  group: "nodes",
                  data: data.compoundData,
               });

               data.children.forEach(function(childId) {
                  GLPIImpact.cy.getElementById(childId)
                     .move({parent: data.compoundData.id});
               });
            } else {
               // Add the node that was removed
               this.cy.getElementById(data.nodeData.id)
                  .move({parent: data.compoundData.id});
            }

            break;

         // Re-add given nodes, edges and compounds
         // Available data: nodes, edges, compounds
         case this.ACTION_DELETE:
            // Add nodes
            data.nodes.forEach(function(node) {
               var newNode = GLPIImpact.cy.add({
                  group: "nodes",
                  data: node.nodeData,
               });
               newNode.position(node.nodePosition);
            });

            // Add compound
            data.compounds.forEach(function(compound) {
               GLPIImpact.cy.add({
                  group: "nodes",
                  data: compound.compoundData,
               });

               compound.compoundChildren.forEach(function(nodeId) {
                  GLPIImpact.cy.getElementById(nodeId).move({
                     parent: compound.compoundData.id
                  });
               });
            });

            // Add edges
            data.edges.forEach(function(edge) {
               GLPIImpact.cy.add({
                  group: "edges",
                  data: edge,
               });
            });

            this.updateFlags();

            break;

         // Toggle impact visibility
         case this.ACTION_EDIT_IMPACT_VISIBILITY:
            this.toggleVisibility(this.FORWARD);
            $(GLPIImpact.selectors.toggleImpact).prop(
               'checked',
               !$(GLPIImpact.selectors.toggleImpact).prop('checked')
            );
            break;

         // Toggle depends visibility
         case this.ACTION_EDIT_DEPENDS_VISIBILITY:
            this.toggleVisibility(this.BACKWARD);
            $(GLPIImpact.selectors.toggleDepends).prop(
               'checked',
               !$(GLPIImpact.selectors.toggleDepends).prop('checked')
            );
            break;

         // Set previous value for "depends" color
         // Available data: oldColor, newColor
         case this.ACTION_EDIT_DEPENDS_COLOR:
            this.setEdgeColors({
               backward: data.oldColor,
            });
            $(GLPIImpact.selectors.dependsColor).val(
               GLPIImpact.edgeColors[GLPIImpact.BACKWARD]
            );
            this.updateStyle();
            this.cy.trigger("change");
            break;

         // Set previous value for "impact" color
         // Available data: oldColor, newColor
         case this.ACTION_EDIT_IMPACT_COLOR:
            this.setEdgeColors({
               forward: data.oldColor,
            });
            $(GLPIImpact.selectors.impactColor).val(
               GLPIImpact.edgeColors[GLPIImpact.FORWARD]
            );
            this.updateStyle();
            this.cy.trigger("change");
            break;

         // Set previous value for "impact and depends" color
         // Available data: oldColor, newColor
         case this.ACTION_EDIT_IMPACT_AND_DEPENDS_COLOR:
            this.setEdgeColors({
               both: data.oldColor,
            });
            $(GLPIImpact.selectors.impactAndDependsColor).val(
               GLPIImpact.edgeColors[GLPIImpact.BOTH]
            );
            this.updateStyle();
            this.cy.trigger("change");
            break;

         // Set previous value for max depth
         // Available data: oldDepth, newDepth
         case this.ACTION_EDIT_MAX_DEPTH:
            this.setDepth(data.oldDepth);
            $(GLPIImpact.selectors.maxDepth).val(data.oldDepth);
            break;
      }

      if (this.undoStack.length === 0) {
         $(this.selectors.undo).addClass("impact-disabled");
      }

   },

   /**
    * Redo last undoed action
    */
   redo: function() {
      // Empty stack, stop here
      if (this.redoStack.length === 0) {
         return;
      }

      var action = this.redoStack.pop();
      var data = action.data;

      // Add action to undo stack
      this.undoStack.push(action);
      $(this.selectors.undo).removeClass("impact-disabled");

      switch (action.code) {
         // Set node to new position
         // Available data: node, oldPosition, newPosition and newParent
         case this.ACTION_MOVE:
            this.cy.filter("node" + this.makeIDSelector(data.node))
               .position({
                  x: data.newPosition.x,
                  y: data.newPosition.y,
               });

            if (data.newParent !== null) {
               this.cy.filter("node" + this.makeIDSelector(data.node))
                  .move({parent: data.newParent});
            }
            break;

         // Add the node again
         // Available data: toAdd
         case this.ACTION_ADD_NODE:
            this.cy.add(data.toAdd);
            break;

         // Add edge
         // Available data; id, data
         case this.ACTION_ADD_EDGE:
            this.cy.add({
               group: "edges",
               data: data,
            });
            this.updateFlags();

            break;

         // Add compound and update its children
         // Available data: data, children
         case this.ACTION_ADD_COMPOUND:
            this.cy.add({
               group: "nodes",
               data: data.data,
            });
            data.children.forEach(function(id) {
               GLPIImpact.cy.filter("node" + GLPIImpact.makeIDSelector(id))
                  .move({parent: data.data.id});
            });
            this.updateFlags();

            break;

         // Insert again the graph
         // Available data: edges, nodes, compounds
         case this.ACTION_ADD_GRAPH:
            // Add nodes
            data.nodes.forEach(function(node) {
               var newNode = GLPIImpact.cy.add({
                  group: "nodes",
                  data: node.nodeData,
               });
               newNode.position(node.nodePosition);
            });

            // Add compound
            data.compounds.forEach(function(compound) {
               GLPIImpact.cy.add({
                  group: "nodes",
                  data: compound.compoundData,
               });

               compound.compoundChildren.forEach(function(nodeId) {
                  GLPIImpact.cy.getElementById(nodeId).move({
                     parent: compound.compoundData.id
                  });
               });
            });

            // Add edges
            data.edges.forEach(function(edge) {
               GLPIImpact.cy.add({
                  group: "edges",
                  data: edge,
               });
            });

            this.updateFlags();

            break;

         // Reapply edit
         // Available data : id, label, color, previousLabel, previousColor
         case this.ACTION_EDIT_COMPOUND:
            this.cy.filter("node" + this.makeIDSelector(data.id)).data({
               label: data.label,
               color: data.color,
            });
            GLPIImpact.cy.trigger("change");
            break;

         // Remove node from the compound (and delete if needed)
         // Available data: nodeData, compoundData, children
         case this.ACTION_REMOVE_FROM_COMPOUND:
            if (data.children.length <= 2) {
               // Remove every nodes and delete the compound
               data.children.forEach(function(childId) {
                  GLPIImpact.cy.getElementById(childId)
                     .move({parent: null});
               });

               this.cy.getElementById(data.compoundData.id).remove();
            } else {
               // Remove only he node that was re-added
               this.cy.getElementById(data.nodeData.id)
                  .move({parent: null});
            }

            break;

         // Re-delete given nodes, edges and compounds
         // Available data: nodes, edges, compounds
         case this.ACTION_DELETE:
            // Delete edges
            data.edges.forEach(function(edge) {
               GLPIImpact.cy.getElementById(edge.id).remove();
            });

            // Delete compounds
            data.compounds.forEach(function(compound) {
               compound.compoundChildren.forEach(function(nodeId) {
                  GLPIImpact.cy.getElementById(nodeId).move({
                     parent: null
                  });
               });

               GLPIImpact.cy.getElementById(compound.compoundData.id).remove();
            });

            // Delete nodes
            data.nodes.forEach(function(node) {
               GLPIImpact.cy.getElementById(node.id).remove();
            });

            this.updateFlags();

            break;

         // Toggle impact visibility
         case this.ACTION_EDIT_IMPACT_VISIBILITY:
            this.toggleVisibility(this.FORWARD);
            $(GLPIImpact.selectors.toggleImpact).prop(
               'checked',
               !$(GLPIImpact.selectors.toggleImpact).prop('checked')
            );
            break;

         // Toggle depends visibility
         case this.ACTION_EDIT_DEPENDS_VISIBILITY:
            this.toggleVisibility(this.BACKWARD);
            $(GLPIImpact.selectors.toggleDepends).prop(
               'checked',
               !$(GLPIImpact.selectors.toggleDepends).prop('checked')
            );
            break;

         // Set new value for "depends" color
         // Available data: oldColor, newColor
         case this.ACTION_EDIT_DEPENDS_COLOR:
            this.setEdgeColors({
               backward: data.newColor,
            });
            $(GLPIImpact.selectors.dependsColor).val(
               GLPIImpact.edgeColors[GLPIImpact.BACKWARD]
            );
            this.updateStyle();
            this.cy.trigger("change");
            break;

         // Set new value for "impact" color
         // Available data: oldColor, newColor
         case this.ACTION_EDIT_IMPACT_COLOR:
            this.setEdgeColors({
               forward: data.newColor,
            });
            $(GLPIImpact.selectors.forwardColor).val(
               "set",
               GLPIImpact.edgeColors[GLPIImpact.FORWARD]
            );
            this.updateStyle();
            this.cy.trigger("change");
            break;

         // Set new value for "impact and depends" color
         // Available data: oldColor, newColor
         case this.ACTION_EDIT_IMPACT_AND_DEPENDS_COLOR:
            this.setEdgeColors({
               both: data.newColor,
            });
            $(GLPIImpact.selectors.impactAndDependsColor).val(
               GLPIImpact.edgeColors[GLPIImpact.BOTH]
            );
            this.updateStyle();
            this.cy.trigger("change");
            break;

         // Set new value for max depth
         // Available data: oldDepth, newDepth
         case this.ACTION_EDIT_MAX_DEPTH:
            this.setDepth(data.newDepth);
            $(GLPIImpact.selectors.maxDepth).val(data.newDepth);
            break;
      }

      if (this.redoStack.length === 0) {
         $(this.selectors.redo).addClass("impact-disabled");
      }
   },

   /**
    * Selector for nodes to hide according to depth and flag settings
    */
   getHiddenSelector: function() {
      var depthSelector = '[depth > ' + this.maxDepth + '][depth !> ' + Number.MAX_SAFE_INTEGER + ']';
      var flagSelector;

      // We have to compute the flags ourselves as bit comparison operators are
      // not supported by cytoscape selectors
      var forward = this.directionVisibility[this.FORWARD];
      var backward = this.directionVisibility[this.BACKWARD];

      if (forward && backward) {
         // Hide nothing
         flagSelector = "[flag = -1]";
      } else if (forward && !backward) {
         // Hide backward
         flagSelector = "[flag = " + this.BACKWARD + "]";
      } else if (!forward && backward) {
         // Hide forward
         flagSelector = "[flag = " + this.FORWARD + "]";
      } else {
         // Hide all but start node and not connected nodes
         flagSelector = '[flag != 0]';
      }

      return flagSelector + ', ' + depthSelector;
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
               'overlay-opacity'   : 0.01,
               'overlay-color'     : "white",
            }
         },
         {
            selector: 'node[highlight=1]',
            style: {
               'font-weight': 'bold',
            }
         },
         {
            selector: ':selected',
            style: {
               'overlay-opacity': 0.2,
               'overlay-color'  : "gray",
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
            selector: GLPIImpact.getHiddenSelector(),
            style: {
               'display': 'none',
            }
         },
         {
            selector: '[id="tmp_node"]',
            style: {
               // Use opacity instead of display none here as this will make
               // the edges connected to this node still visible
               'opacity': 0,
            }
         },
         {
            selector: 'edge',
            style: {
               'width'                    : 1,
               'line-color'               : this.edgeColors[0],
               'target-arrow-color'       : this.edgeColors[0],
               'target-arrow-shape'       : 'triangle',
               'arrow-scale'              : 0.7,
               'curve-style'              : 'bezier',
               'source-endpoint'          : 'outside-to-node-or-label',
               'target-endpoint'          : 'outside-to-node-or-label',
               'source-distance-from-node': '2px',
               'target-distance-from-node': '2px',
            }
         },
         {
            selector: 'edge[target="tmp_node"]',
            style: {
               // We want the arrow to go exactly where the cursor of the user
               // is on the graph, no padding.
               'source-endpoint'          : 'inside-to-node',
               'target-endpoint'          : 'inside-to-node',
               'source-distance-from-node': '0px',
               'target-distance-from-node': '0px',
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
   getPresetLayout: function (positions) {
      this.no_positions = [];

      return {
         name: 'preset',
         positions: function(node) {
            var x = 0;
            var y = 0;

            if (!node.isParent() && positions[node.data('id')] !== undefined) {
               x = parseFloat(positions[node.data('id')].x);
               y = parseFloat(positions[node.data('id')].y);
            }

            return {
               x: x,
               y: y,
            };
         }
      };
   },

   /**
    * Generate postion for nodes that are not saved in the current context
    *
    * Firstly, order the positionless nodes in a way that the one that depends
    * on others positionless nodes are placed after their respective
    * dependencies
    *
    * Secondly, try to place each nodes on the graph:
    *    1) take a random non positionless neighbor of our node
    *    2) Find the closest node to this neighbor, save the distance (if this
    *    neighbor has no neighbor of its own use a set value for the distance)
    *    3) Try to place the node at the left or the right of the neighbor (
    *    depending on the edge direction, we want the graph to flow from left
    *    to right) at the saved position.
    *    4) If the position is not avaible, try at various angles bewteen -75°
    *    and 75°
    *    5) If the position is still not available, increase the distance and
    *    try again until a valid position is found
    */
   generateMissingPositions: function() {
      // Safety check, should not happen
      if (this.cy.filter("node:childless").length == this.no_positions.length) {
         // Set a random node as valid
         this.no_positions.pop();
      }

      // Keep tracks of the id of all the no yet placed nodes
      var not_placed = [];
      this.no_positions.forEach(function(node){
         not_placed.push(node.data('id'));
      });

      // First we need to order no_positions in a way that the ones that depend
      // on the positions of other nodes with no position are used last
      var clean_order = [];
      var np_valid = [];
      while (this.no_positions.length !== 0) {
         this.no_positions.forEach(function(node, index) {
            // Check that any neibhor is either valid (no in not placed) or has
            // just been validated (in np_valid)

            var valid = false;
            node.neighborhood().forEach(function(ele) {
               if (valid) {
                  return;
               }

               // We don't need edges
               if (!ele.isNode()) {
                  return;
               }

               if (not_placed.indexOf(ele.data('id')) === -1
                  || np_valid.indexOf(ele.data('id')) !== -1) {
                  valid = true;
               }
            });

            if (valid) {
               // Add to the list of validated nodes, set order and remove it
               // from buffer
               np_valid.push(node.data('id'));
               clean_order.push(node);
               // not_placed.splice(index, 1);
               GLPIImpact.no_positions.splice(index, 1);
            }
         });
      }

      this.no_positions = clean_order;

      // Generate positions for nodes which lake them
      this.no_positions.forEach(function(node){
         // Find random neighbor with a valid position
         var neighbor = null;
         node.neighborhood().forEach(function(ele) {
            // We already found a valid neighor, skip until the end
            if (neighbor !== null) {
               return;
            }

            if (!ele.isNode()) {
               return;
            }

            // Ignore our starting node
            if (ele.data('id') == node.data('id')) {
               return;
            }

            // Ignore node with no positions not yet placed
            if (not_placed.indexOf(ele.data('id')) !== -1) {
               return;
            }

            // Valid neighor, let's pick it
            neighbor = ele;
         });

         // Should not happen if no_positions is correctly sorted
         if (neighbor === null) {
            return;
         }

         // We now need to find the closest node to the neighor
         var closest = null;
         var distance = Number.MAX_SAFE_INTEGER;
         neighbor.neighborhood().forEach(function(ele){
            if (!ele.isNode()) {
               return;
            }

            var ele_distance = GLPIImpact.getDistance(neighbor.position(), ele.position());
            if (ele_distance < distance) {
               distance = ele_distance;
               closest = ele;
            }
         });

         // If our neighbor node has no neighors himself, use a set distance
         if (closest === null) {
            distance = 100;
         }

         // Find the edge between our node and the chosen neighbor
         var edge = node.edgesTo(neighbor)[0];
         if (edge == undefined) {
            edge = neighbor.edgesTo(node)[0];
         }

         // Set direction factor according to the edge direction (are we the
         // source or the target of this edge ?). This factor will be used to
         // know if the node must be placed before or after the neighbor
         var direction_factor;
         if (edge.data('target') == node.data('id')) {
            direction_factor = 1;
         } else {
            direction_factor = -1;
         }

         // Keep trying to place the node until we succeed$
         var success = false;
         while(!success) {
            var angle = 0;
            var angle_mirror = false;

            // Try all possible angles bewteen -75° and 75°
            while (angle !== -75) {
               // Calculate the position
               var position = {
                  x: direction_factor * (distance * Math.cos(angle * (Math.PI / 180))) + (neighbor.position().x),
                  y: distance * Math.sin(angle * (Math.PI / 180)) + neighbor.position().y,
               };

               // Check if position is available
               var available = true;
               GLPIImpact.cy.filter().forEach(function(ele){
                  var bdb = ele.boundingBox();
                  // var bdb = ele.renderedBoundingBox();

                  if ((bdb.x1 - 20) < position.x && (bdb.x2 + 20) > position.x
                     && (bdb.y1 - 20) < position.y && (bdb.y2 + 20) > position.y) {
                     available = false;
                  }
               });

               // Success, set the node position and go to the next one
               if (available) {
                  node.position(position);
                  var np_index = not_placed.indexOf(node.data('id'));
                  not_placed.splice(np_index, 1);
                  success = true;
                  break;
               }

               if (!angle_mirror && angle !== 0) {
                  // We tried X°, lets try the "mirror angle" -X°]
                  angle = angle * -1;
                  angle_mirror = true;
               } else {
                  // Add 15° and return to positive number
                  if (angle < 0) {
                     angle = 0 - angle;
                     angle_mirror = false;
                  }

                  angle += 15;
               }
            }

            // Increase distance and try again
            distance += 30;
         }
      });

      // Reset buffer
      this.no_positions = [];
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
   computeContext: function(currentNodes) {
      var positions = {};

      Object.keys(currentNodes).forEach(function (nodeID) {
         var node = currentNodes[nodeID];
         positions[nodeID] = {
            x: node.position.x,
            y: node.position.y
         };
      });

      return {
         node_id                 : this.startNode,
         positions               : JSON.stringify(positions),
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

         // Store parent
         itemsDelta[node.impactitem_id] = {
            action    : GLPIImpact.DELTA_ACTION_UPDATE,
            parent_id : node.parent,
         };
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
      result.context = this.computeContext(currentState.items);

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
            selector       : 'node[link]',
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
      $.when(GLPIImpact.buildGraphFromNode(node))
         .done(
            function (graph, params) {
               // Insert the new graph data into the current graph
               GLPIImpact.insertGraph(graph, params, {
                  id: nodeID,
                  x: position.x,
                  y: position.y
               });
               GLPIImpact.updateFlags();
            }
         ).fail(
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
      var previousLabel = compound.data('label');
      var previousColor = compound.data('color');
      // Reset inputs:
      $(GLPIImpact.selectors.compoundName).val(previousLabel);
      $(GLPIImpact.selectors.compoundColor).val(previousColor);

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

            // Log for undo (only if not first edit, see "close" function below)
            if (GLPIImpact.eventData.newCompound == null) {
               GLPIImpact.addToUndo(GLPIImpact.ACTION_EDIT_COMPOUND, {
                  id      : compound.data('id'),
                  label   : compound.data('label'),
                  color   : compound.data('color'),
                  oldLabel: previousLabel,
                  oldColor: previousColor,
               });
            }
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
         buttons: [buttonSave],
         close: function() {
            var label = $(GLPIImpact.selectors.compoundName).val();
            var color = $(GLPIImpact.selectors.compoundColor).val();

            if (GLPIImpact.eventData.newCompound != null) {
               // This compound was just added, we will keep only one action for
               // the creation + edit in the undo stack
               GLPIImpact.eventData.newCompound.data.label = label;
               GLPIImpact.eventData.newCompound.data.color = color;

               GLPIImpact.addToUndo(
                  GLPIImpact.ACTION_ADD_COMPOUND,
                  _.cloneDeep(GLPIImpact.eventData.newCompound)
               );

               GLPIImpact.eventData.newCompound = null;
            }
         },
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
      var layout;

      // Init workspace status
      GLPIImpact.showDefaultWorkspaceStatus();

      // Load params - phase1 (before cytoscape creation)
      if (params.impactcontexts_id !== undefined && params.impactcontexts_id !== 0) {
         // Apply custom colors if defined
         this.setEdgeColors({
            forward : params.impact_color,
            backward: params.depends_color,
            both    : params.impact_and_depends_color,
         });

         // Apply max depth
         this.maxDepth = params.max_depth;

         // Preset layout based on node positions
         layout = this.getPresetLayout(JSON.parse(params.positions));
      } else {
         // Default params if no context was found
         this.setEdgeColors(this.defaultColors);
         this.maxDepth = this.DEFAULT_DEPTH;

         // Procedural layout
         layout = this.getDagreLayout();
      }

      // Init cytoscape
      this.cy = cytoscape({
         container: this.impactContainer,
         elements : data,
         style    : this.getNetworkStyle(),
         layout   : layout,
         wheelSensitivity: 0.25,
      });

      // If we used the preset layout, some nodes might lack positions
      this.generateMissingPositions();

      this.cy.minZoom(0.5);

      // Store initial data
      this.initialState = this.getCurrentState();

      // Enable editing if not readonly
      if (!readonly) {
         this.enableGraphEdition();
      }

      // Highlight starting node
      this.cy.filter("node[start]").data({
         highlight: 1,
         start_node: 1,
      });

      // Enable context menu
      this.cy.contextMenus({
         menuItems: this.getContextMenuItems(),
         menuItemClasses: [],
         contextMenuClasses: []
      });

      // Enable grid
      this.cy.gridGuide({
         gridStackOrder: 0,
         snapToGridOnRelease: false,
         snapToGridDuringDrag: true,
         gridSpacing: 12,
         drawGrid: true,
         panGrid: true,
      });

      // Disable box selection as we don't need it
      this.cy.boxSelectionEnabled(false);

      // Load params - phase 2 (after cytoscape creation)
      if (params.impactcontexts_id !== undefined && params.impactcontexts_id !== 0) {
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
      } else {
         // Default params if no context was found
         this.cy.fit();

         if (this.cy.zoom() > 2.3) {
            this.cy.zoom(2.3);
            this.cy.center();
         }
      }

      // Register events handlers for cytoscape object
      this.cy.on('mousedown', 'node', this.nodeOnMousedown);
      this.cy.on('mouseup', this.onMouseUp);
      this.cy.on('mousemove', this.onMousemove);
      this.cy.on('mouseover', this.onMouseover);
      this.cy.on('mouseout', this.onMouseout);
      this.cy.on('click', this.onClick);
      this.cy.on('click', 'edge', this.edgeOnClick);
      this.cy.on('click', 'node', this.nodeOnClick);
      this.cy.on('box', this.onBox);
      this.cy.on('drag add remove change', this.onChange);
      this.cy.on('doubleClick', this.onDoubleClick);
      this.cy.on('remove', this.onRemove);
      this.cy.on('grabon', this.onGrabOn);
      this.cy.on('freeon', this.onFreeOn);
      this.initCanvasOverlay();

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

      // Set color widgets default values
      $(GLPIImpact.selectors.dependsColor).val(
         GLPIImpact.edgeColors[GLPIImpact.BACKWARD]
      );
      $(GLPIImpact.selectors.impactColor).val(
         GLPIImpact.edgeColors[GLPIImpact.FORWARD]
      );
      $(GLPIImpact.selectors.impactAndDependsColor).val(
         GLPIImpact.edgeColors[GLPIImpact.BOTH]
      );
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
    * Compute flags and depth for each nodes
    */
   updateFlags: function() {
      /**
       * Assuming A is our starting node and B is a random node on the graph,
       * the depth of B is the shortest distance between AB and BA.
       */

      // Init flag to GLPIImpact.DEFAULT for all elements of the graph
      this.cy.elements().forEach(function(ele) {
         ele.data('flag', GLPIImpact.DEFAULT);
      });

      // First, calculate AB: Apply dijkstra on A and get distances for each
      // nodes
      var startNodeDijkstra = this.cy.elements().dijkstra(
         this.makeIDSelector(this.startNode),
         function() { return 1; }, // Same weight for each path
         true                      // Do not ignore edge directions
      );

      this.cy.$("node:childless").forEach(function(node) {
         var distanceAB = startNodeDijkstra.distanceTo(node);
         node.data('depth', distanceAB);

         // Set node as part of the "Forward" graph
         if (distanceAB !== Infinity) {
            node.data('flag', node.data('flag') | GLPIImpact.FORWARD);
         }
      });

      // Now, calculate BA: apply dijkstra on each nodes of the graph and
      // get the distance to A
      this.cy.$("node:childless").forEach(function(node) {
         // Skip A
         if (node.data('id') == GLPIImpact.startNode) {
            return;
         }

         var otherNodeDijkstra = GLPIImpact.cy.elements().dijkstra(
            node,
            function() { return 1; }, // Same weight for each path
            true                      // Do not ignore edge directions
         );

         var distanceBA = otherNodeDijkstra.distanceTo(
            GLPIImpact.makeIDSelector(GLPIImpact.startNode)
         );

         // If distance BA is shorter than distance AB, use it instead
         if (node.data('depth') > distanceBA) {
            node.data('depth', distanceBA);
         }

         // Set node as part of the "Backward" graph
         if (distanceBA !== Infinity) {
            node.data('flag', node.data('flag') | GLPIImpact.BACKWARD);
         }
      });

      // Set start node to this.BOTH so it doen't impact the computation of it's neighbors
      GLPIImpact.cy.$(GLPIImpact.makeIDSelector(GLPIImpact.startNode)).data(
         'flag',
         this.BOTH
      );

      // Handle compounds nodes, their depth should be the lowest depth amongst
      // their children
      this.cy.filter("node:parent").forEach(function(compound) {
         var lowestDepth = Infinity;
         var flag = GLPIImpact.DEFAULT;

         compound.children().forEach(function(childNode) {
            var childNodeDepth = childNode.data('depth');
            if (childNodeDepth < lowestDepth) {
               lowestDepth = childNodeDepth;
            }

            flag = flag | childNode.data('flag');
         });

         compound.data('depth', lowestDepth);
         compound.data('flag', flag);
      });

      // Apply flag to edges so they can get the right colors
      this.cy.edges().forEach(function(edge) {
         var source = GLPIImpact.cy.$(GLPIImpact.makeIDSelector(edge.data('source')));
         var target = GLPIImpact.cy.$(GLPIImpact.makeIDSelector(edge.data('target')));

         edge.data('flag', source.data('flag') & target.data('flag'));
      });

      // Set start node to this.DEFAULT when all calculation are down so he is
      // always shown
      GLPIImpact.cy.$(GLPIImpact.makeIDSelector(GLPIImpact.startNode)).data(
         'flag',
         this.DEFAULT
      );

      GLPIImpact.updateStyle();
   },

   /**
    * Toggle impact/depends visibility
    *
    * @param {*} toToggle
    */
   toggleVisibility: function(toToggle) {
      // Update visibility setting
      GLPIImpact.directionVisibility[toToggle] = !GLPIImpact.directionVisibility[toToggle];
      GLPIImpact.updateFlags();
      GLPIImpact.cy.trigger("change");
   },

   /**
    * Set max depth of the graph
    * @param {Number} max max depth
    */
   setDepth: function(max) {
      GLPIImpact.maxDepth = max;

      if (max >= GLPIImpact.MAX_DEPTH) {
         max = "infinity";
         GLPIImpact.maxDepth = GLPIImpact.NO_DEPTH_LIMIT;
      }

      $(GLPIImpact.selectors.maxDepthView).html(max);
      GLPIImpact.updateStyle();
      GLPIImpact.cy.trigger("change");
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
            dfd.resolve(JSON.parse(data.graph), JSON.parse(data.params));
         },
         error: function () {
            dfd.reject();
         }
      });

      return dfd.promise();
   },

   /**
    * Get distance between two point A and B
    * @param {Object} a x, y
    * @param {Object} b x, y
    * @returns {Number}
    */
   getDistance: function(a, b) {
      return Math.sqrt(Math.pow(b.x - a.x, 2) + Math.pow(b.y - a.y, 2));
   },

   /**
    * Insert another new graph into the current one
    *
    * @param {Array}  graph
    * @param {Object} params
    * @param {Object} startNode data, x, y
    */
   insertGraph: function(graph, params, startNode) {
      var toAdd = [];
      var mainBoundingBox = this.cy.filter().boundingBox();

      // Try to add the new graph nodes
      var i;
      for (i=0; i<graph.length; i++) {
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
         this.addToUndo(this.ACTION_ADD_NODE, {
            toAdd: toAdd[0]
         });
         return;
      }

      // Add nodes and apply layout
      var eles = this.cy.add(toAdd);

      var options;
      if (params.positions === undefined) {
         options = this.getDagreLayout();
      } else {
         options = this.getPresetLayout(JSON.parse(params.positions));
      }

      // Place the layout anywhere to compute it's bounding box
      var layout = eles.layout(options);
      layout.run();
      this.generateMissingPositions();

      // First, position the graph on the clicked areaa
      var newGraphBoundingBox = eles.boundingBox();
      var center = {
         x: (newGraphBoundingBox.x1 + newGraphBoundingBox.x2) / 2,
         y: (newGraphBoundingBox.y1 + newGraphBoundingBox.y2) / 2,
      };

      var centerToClickVector = [
         startNode.x - center.x,
         startNode.y - center.y,
      ];

      // Apply vector to each node
      eles.nodes().forEach(function(node) {
         if (!node.isParent()) {
            node.position({
               x: node.position().x + centerToClickVector[0],
               y: node.position().y + centerToClickVector[1],
            });
         }
      });

      newGraphBoundingBox = eles.boundingBox();

      // If the two bouding box overlap
      if (!(mainBoundingBox.x1 > newGraphBoundingBox.x2
         || newGraphBoundingBox.x1 > mainBoundingBox.x2
         || mainBoundingBox.y1 > newGraphBoundingBox.y2
         || newGraphBoundingBox.y1 > mainBoundingBox.y2)) {

         // We want to find the point "intersect", which is the closest
         // intersection between the point at the center of the new bounding box
         // and the main bouding bouding box.
         // We then want to find the point "closest" which is the vertice of
         // the new bounding box which is the closest to the center of the
         // main bouding box

         // Then the vector betwteen "intersect" and "closest" can be applied
         // to the new graph to make it "slide" out of the main graph

         // Center of the new graph
         center = {
            x: Math.round((newGraphBoundingBox.x1 + newGraphBoundingBox.x2) / 2),
            y: Math.round((newGraphBoundingBox.y1 + newGraphBoundingBox.y2) / 2),
         };

         var directions = [
            [1, 0], [0, 1], [-1, 0], [0, -1], [1, 1], [-1, 1], [-1, -1], [1, -1]
         ];

         var edges = [
            {
               a: {x: Math.round(mainBoundingBox.x1), y: Math.round(mainBoundingBox.y1)},
               b: {x: Math.round(mainBoundingBox.x2), y: Math.round(mainBoundingBox.y1)},
            },
            {
               a: {x: Math.round(mainBoundingBox.x2), y: Math.round(mainBoundingBox.y1)},
               b: {x: Math.round(mainBoundingBox.x1), y: Math.round(mainBoundingBox.y2)},
            },
            {
               a: {x: Math.round(mainBoundingBox.x1), y: Math.round(mainBoundingBox.y2)},
               b: {x: Math.round(mainBoundingBox.x2), y: Math.round(mainBoundingBox.y2)},
            },
            {
               a: {x: Math.round(mainBoundingBox.x2), y: Math.round(mainBoundingBox.y2)},
               b: {x: Math.round(mainBoundingBox.x1), y: Math.round(mainBoundingBox.y1)},
            }
         ];

         i = 0; // Safegard, no more than X tries
         var intersect;
         while (i < 50000) {
            directions.forEach(function(vector) {
               if (intersect !== undefined) {
                  return;
               }

               var point = {
                  x: center.x + (vector[0] * i),
                  y: center.y + (vector[1] * i),
               };

               // Check if the point intersect with one of the edges
               edges.forEach(function(edge) {
                  if (intersect !== undefined) {
                     return;
                  }

                  if ((GLPIImpact.getDistance(point, edge.a)
                     + GLPIImpact.getDistance(point, edge.b))
                     == GLPIImpact.getDistance(edge.a, edge.b)) {
                     // Found intersection
                     intersect = {
                        x: point.x,
                        y: point.y,
                     };
                  }
               });
            });

            i++;

            if (intersect !== undefined) {
               break;
            }
         }

         if (intersect !== undefined) {
            // Center of the main graph
            center = {
               x: (mainBoundingBox.x1 + mainBoundingBox.x2) / 2,
               y: (mainBoundingBox.y1 + mainBoundingBox.y2) / 2,
            };

            var vertices = [
               {x: newGraphBoundingBox.x1, y: newGraphBoundingBox.y1},
               {x: newGraphBoundingBox.x1, y: newGraphBoundingBox.y2},
               {x: newGraphBoundingBox.x2, y: newGraphBoundingBox.y1},
               {x: newGraphBoundingBox.x2, y: newGraphBoundingBox.y2},
            ];

            var closest;
            var min_dist;

            vertices.forEach(function(vertice) {
               var dist = GLPIImpact.getDistance(vertice, center);
               if (min_dist == undefined || dist < min_dist) {
                  min_dist = dist;
                  closest = vertice;
               }
            });

            // Compute vector between closest and intersect
            var vector = [
               intersect.x - closest.x,
               intersect.y - closest.y,
            ];

            // Apply vector to each node
            eles.nodes().forEach(function(node) {
               if (!node.isParent()) {
                  node.position({
                     x: node.position().x + vector[0],
                     y: node.position().y + vector[1],
                  });
               }
            });
         }
      }

      this.generateMissingPositions();
      this.cy.animate({
         center: {
            eles : GLPIImpact.cy.filter(""),
         },
      });

      this.cy.getElementById(startNode.id).data("highlight", 1);

      // Set undo/redo data
      var data = {
         edges: eles.edges().map(function(edge){ return edge.data(); }),
         compounds: [],
         nodes: [],
      };
      eles.nodes().forEach(function(node) {
         if (node.isParent()) {
            data.compounds.push({
               compoundData    : _.clone(node.data()),
               compoundChildren: node.children().map(function(n) {
                  return n.data('id');
               }),
            });
         } else {
            data.nodes.push({
               nodeData    : _.clone(node.data()),
               nodePosition: _.clone(node.position()),
            });
         }
      });
      this.addToUndo(this.ACTION_ADD_GRAPH, data);
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
            $(GLPIImpact.impactContainer).css('cursor', "move");
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
      $(GLPIImpact.selectors.save).removeClass('clean'); // Needed for animations if the workspace is not dirty
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
            var link = CFG_GLPI.root_doc + "/front/" + url + ".form.php?id=" + element.id;
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
         var newCompound = GLPIImpact.cy.add({
            group: 'nodes',
            data: {color: '#dadada'},
         });

         // Log event data (for undo)
         GLPIImpact.eventData.newCompound = {
            data: {id: newCompound.data('id')},
            children: [],
         };

         // Set parent for coumpound member
         GLPIImpact.eventData.boxSelected.forEach(function(ele) {
            ele.move({'parent': newCompound.data('id')});
            GLPIImpact.eventData.newCompound.children.push(ele.data('id'));
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

      // Log for undo/redo
      var deleted = {
         edges: [],
         nodes: [],
         compounds: []
      };

      if (ele.isEdge()) {
         // Case 1: removing an edge
         deleted.edges.push(_.clone(ele.data()));
         ele.remove();
      } else if (ele.isParent()) {
         // Case 2: removing a compound

         // Set undo/redo data
         deleted.compounds.push({
            compoundData    : _.clone(ele.data()),
            compoundChildren: ele.children().map(function(node) {
               return node.data('id');
            }),
         });

         // Remove only the parent
         ele.children().move({parent: null});
         ele.remove();
      } else {
         // Case 3: removing a node
         // Remove parent if last child of a compound
         if (!ele.isOrphan() && ele.parent().children().length <= 2) {
            var parent = ele.parent();

            // Set undo/redo data
            deleted.compounds.push({
               compoundData    : _.clone(parent.data()),
               compoundChildren: parent.children().map(function(node) {
                  return node.data('id');
               }),
            });

            parent.children().move({parent: null});
            parent.remove();
         }

         // Set undo/redo data
         deleted.nodes.push({
            nodeData: _.clone(ele.data()),
            nodePosition: _.clone(ele.position()),
         });
         deleted.edges = deleted.edges.concat(ele.connectedEdges(function(edge) {
            // Check for duplicates
            var exist = false;
            deleted.edges.forEach(function(deletedEdge) {
               if (deletedEdge.id == edge.data('id')) {
                  exist = true;
               }
            });

            // In case of multiple deletion, check in the buffer too
            if (GLPIImpact.eventData.multipleDeletion != null) {
               GLPIImpact.eventData.multipleDeletion.edges.forEach(
                  function(deletedEdge) {
                     if (deletedEdge.id == edge.data('id')) {
                        exist = true;
                     }
                  }
               );
            }

            return !exist;
         }).map(function(ele){
            return ele.data();
         }));

         // Remove all edges connected to this node from graph and delta
         ele.remove();
      }

      // Update flags
      GLPIImpact.updateFlags();

      // Multiple deletion, set the data in eventData buffer so it can be added
      // as a simple undo/redo entry later
      if (this.eventData.multipleDeletion != null) {
         this.eventData.multipleDeletion.edges = this.eventData.multipleDeletion.edges.concat(deleted.edges);
         this.eventData.multipleDeletion.nodes = this.eventData.multipleDeletion.nodes.concat(deleted.nodes);
         this.eventData.multipleDeletion.compounds = this.eventData.multipleDeletion.compounds.concat(deleted.compounds);
      } else {
         this.addToUndo(this.ACTION_DELETE, deleted);
      }
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
    * Check if a given position match the hitbox of a badge
    *
    * @param {Object}   renderedPosition  {x, y}
    * @param {Boolean}  trigger           should we trigger the link if there
    *                                     is a match ?
    * @param {Boolean}  blank
    * @returns {Boolean}
    */
   checkBadgeHitboxes: function (renderedPosition, trigger, blank) {
      var hit = false;
      var margin = 5 * GLPIImpact.cy.zoom();

      GLPIImpact.badgesHitboxes.forEach(function(badgeHitboxDetails) {
         if (hit) {
            return;
         }

         var position = badgeHitboxDetails.position;
         var bb = {
            x1: position.x - margin,
            x2: position.x + margin,
            y1: position.y - margin,
            y2: position.y + margin,
         };

         if (bb.x1 < renderedPosition.x && bb.x2 > renderedPosition.x
            && bb.y1 < renderedPosition.y && bb.y2 > renderedPosition.y) {
            hit = true;

            if (trigger) {
               var target = badgeHitboxDetails.target + "?is_deleted=0&as_map=0&search=Search&itemtype=Ticket";

               // Add items_id criteria
               target += "&criteria[0][link]=AND&criteria[0][field]=13&criteria[0][searchtype]=contains&criteria[0][value]=" + badgeHitboxDetails.id;
               // Add itemtype criteria
               target += "&criteria[1][link]=AND&criteria[1][field]=131&criteria[1][searchtype]=equals&criteria[1][value]=" + badgeHitboxDetails.itemtype;
               // Add type criteria (incident)
               target += "&criteria[2][link]=AND&criteria[2][field]=14&criteria[2][searchtype]=equals&criteria[2][value]=1";
               // Add status criteria (not solved)
               target += "&criteria[3][link]=AND&criteria[3][field]=12&criteria[3][searchtype]=equals&criteria[3][value]=notold";

               if (blank) {
                  window.open(target);
               } else {
                  window.location.href = target;
               }
            }
         }
      });

      return hit;
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
            break;

         case GLPIImpact.EDITION_ADD_EDGE:
            break;

         case GLPIImpact.EDITION_DELETE:
            break;
      }

      GLPIImpact.checkBadgeHitboxes(event.renderedPosition, true, GLPIImpact.eventData.ctrlDown);
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
            if (GLPIImpact.eventData.lastClicktimestamp != null) {
               // Trigger homemade double click event
               if (event.timeStamp - GLPIImpact.eventData.lastClicktimestamp < 500
                  && event.target == GLPIImpact.eventData.lastClickTarget) {
                  event.target.trigger('doubleClick', event);
               }
            }

            GLPIImpact.eventData.lastClicktimestamp = event.timeStamp;
            GLPIImpact.eventData.lastClickTarget = event.target;
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

      // Remove hightligh for recently inserted graph
      GLPIImpact.cy.$("[highlight][!start_node]").data("highlight", 0);
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
    * Handle "grab" event
    *
    * @param {Jquery.event} event
    */
   onGrabOn: function(event) {
      // Store original position (shallow copy)
      GLPIImpact.eventData.grabNodePosition = {
         x: event.target.position().x,
         y: event.target.position().y,
      };

      // Store original parent (shallow copy)
      var parent = null;
      if (event.target.parent() !== undefined) {
         parent = event.target.parent().data('id');
      }
      GLPIImpact.eventData.grabNodeParent = parent;
   },

   /**
    * Handle "free" event
    * @param {Jquery.Event} event
    */
   onFreeOn: function(event) {
      var parent = null;
      if (event.target.parent() !== undefined) {
         parent = event.target.parent().data('id');
      }

      var newParent = null;
      if (parent !== GLPIImpact.eventData.grabNodeParent) {
         newParent = parent;
      }

      // If there was a real position change
      if (GLPIImpact.eventData.grabNodePosition.x !== event.target.position().x
         || GLPIImpact.eventData.grabNodePosition.y !== event.target.position().y) {

         GLPIImpact.addToUndo(GLPIImpact.ACTION_MOVE, {
            node: event.target.data('id'),
            oldPosition: GLPIImpact.eventData.grabNodePosition,
            newPosition: {
               x: event.target.position().x,
               y: event.target.position().y,
            },
            newParent: newParent,
         });
      }
   },

   /**
    * Remove handler
    * @param {JQuery.Event} event
    */
   onRemove: function(event) {
      if (event.target.isNode() && !event.target.isParent()) {
         var itemtype = event.target.data('id')
            .split(GLPIImpact.NODE_ID_SEPERATOR)[0];

         // If a node was deleted and its itemtype is the same as the one
         // selected in the add node panel, refresh the search
         if (itemtype == GLPIImpact.selectedItemtype) {
            $(GLPIImpact.selectors.sideSearchResults).html("");
            GLPIImpact.searchAssets(
               GLPIImpact.selectedItemtype,
               JSON.stringify(GLPIImpact.getUsedAssets()),
               $(GLPIImpact.selectors.sideFilterAssets).val(),
               0
            );
         }
      }
   },

   /**
    * Handler for key down events
    *
    * @param {JQuery.Event} event
    */
   onKeyDown: function(event) {
      // Ignore key events if typing inside input
      if (event.target.nodeName == "INPUT") {
         return;
      }

      switch (event.which) {
         // Shift
         case 16:
            if (event.ctrlKey) {
               // Enter add compound edge mode
               if (GLPIImpact.editionMode != GLPIImpact.EDITION_ADD_COMPOUND) {
                  if (GLPIImpact.eventData.previousEditionMode === undefined) {
                     GLPIImpact.eventData.previousEditionMode = GLPIImpact.editionMode;
                  }
                  GLPIImpact.setEditionMode(GLPIImpact.EDITION_ADD_COMPOUND);
               }
            } else {
               // Enter edit edge mode
               if (GLPIImpact.editionMode != GLPIImpact.EDITION_ADD_EDGE) {
                  if (GLPIImpact.eventData.previousEditionMode === undefined) {
                     GLPIImpact.eventData.previousEditionMode = GLPIImpact.editionMode;
                  }
                  GLPIImpact.setEditionMode(GLPIImpact.EDITION_ADD_EDGE);
               }
            }
            break;

         // Ctrl
         case 17:
            GLPIImpact.eventData.ctrlDown = true;
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

            // Prepare multiple deletion buffer (for undo/redo)
            GLPIImpact.eventData.multipleDeletion = {
               edges    : [],
               nodes    : [],
               compounds: [],
            };

            // Delete selected element(s)
            GLPIImpact.cy.filter(":selected").forEach(function(ele) {
               GLPIImpact.deleteFromGraph(ele);
            });

            // Set undo/redo data
            GLPIImpact.addToUndo(
               GLPIImpact.ACTION_DELETE,
               GLPIImpact.eventData.multipleDeletion
            );

            // Reset multiple deletion buffer (for undo/redo)
            GLPIImpact.eventData.multipleDeletion = null;
            break;

         // CTRL + Y
         case 89:
            if (!event.ctrlKey) {
               break;
            }

            GLPIImpact.redo();
            break;

         // CTRL + Z / CTRL + SHIFT + Z
         case 90:
            if (!event.ctrlKey) {
               break;
            }

            if (event.shiftKey) {
               GLPIImpact.redo();
            } else {
               GLPIImpact.undo();
            }

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
            if (GLPIImpact.eventData.previousEditionMode !== undefined
               && (GLPIImpact.editionMode == GLPIImpact.EDITION_ADD_EDGE
                  || GLPIImpact.editionMode == GLPIImpact.EDITION_ADD_COMPOUND)
            ) {
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
            GLPIImpact.eventData.ctrlDown = false;
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
    * Handle mouseup events
    *
    * @param {JQuery.Event} event
    */
   onMouseUp: function(event) {
      if (event.target.data('id') != undefined && event.target.isNode()) {
         // Handler for nodes
         GLPIImpact.nodeOnMouseup();
      }
      switch (GLPIImpact.editionMode) {
         case GLPIImpact.EDITION_DEFAULT:
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
            var edgeID = GLPIImpact.eventData.tmpEles.data('id');
            GLPIImpact.eventData.tmpEles = null;

            // Option 1: Edge between a node and the fake tmp_node -> ignore
            if (edgeID == 'tmp_node') {
               return;
            }

            var edgeDetails = edgeID.split(GLPIImpact.EDGE_ID_SEPERATOR);

            // Option 2: Edge between two nodes that already exist -> ignore
            if (event.cy.filter('edge[id="' + edgeID + '"]').length > 0) {
               return;
            }

            // Option 3: Both end of the edge are actually the same node -> ignore
            if (startEdge == edgeDetails[1]) {
               return;
            }

            // Option 4: Edge between two nodes that does not exist yet -> create it!
            var data = {
               id: edgeID,
               source: startEdge,
               target: edgeDetails[1]
            };
            event.cy.add({
               group: 'edges',
               data: data,
            });
            GLPIImpact.addToUndo(GLPIImpact.ACTION_ADD_EDGE, _.clone(data));

            // Update dependencies flags according to the new link
            GLPIImpact.updateFlags();

            break;

         case GLPIImpact.EDITION_DELETE:
            break;
      }
   },

   /**
    * Handle mouseup events on nodes
    *
    * @param {JQuery.Event} event
    */
   nodeOnMouseup: function () {
      switch (GLPIImpact.editionMode) {
         case GLPIImpact.EDITION_DEFAULT:
            $(GLPIImpact.impactContainer).css('cursor', "grab");

            // Reset eventData for node grabbing
            GLPIImpact.eventData.grabNodeStart = null;
            GLPIImpact.eventData.boundingBox = null;

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
    * Handle mousemove events on nodes
    *
    * @param {JQuery.Event} event
    */
   onMousemove: _.throttle(function(event) {
      var node;

      // Check for badges hitboxes
      if (GLPIImpact.checkBadgeHitboxes(event.renderedPosition, false, false)
         && !GLPIImpact.eventData.showPointerForBadge) {
         // Entering a badge hitbox
         GLPIImpact.eventData.showPointerForBadge = true;

         // Store previous cursor and show pointer
         GLPIImpact.eventData.previousCursor = $(GLPIImpact.impactContainer).css('cursor');
         $(GLPIImpact.impactContainer).css('cursor', "pointer");
      } else if (GLPIImpact.eventData.showPointerForBadge
         && !GLPIImpact.checkBadgeHitboxes(event.renderedPosition, false, false)) {
         // Exiiting a badge hitbox
         GLPIImpact.eventData.showPointerForBadge = false;

         // Reset to previous cursor
         $(GLPIImpact.impactContainer).css(
            'cursor',
            GLPIImpact.eventData.previousCursor
         );
      }

      switch (GLPIImpact.editionMode) {
         case GLPIImpact.EDITION_DEFAULT:
         case GLPIImpact.EDITION_ADD_NODE:

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
               // outside this original bouding box to know if the user is
               // trying to move it away from the compound
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
               if (!GLPIImpact.cy.getElementById(nodeID).visible()) {
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
                        target: node,
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
               if (!GLPIImpact.eventData.showPointerForBadge) {
                  // Don't alter the cursor if hovering a badge
                  $(GLPIImpact.impactContainer).css('cursor', "grab");
               }
            } else if (event.target.isEdge()) {
               // If mouseover on edge, show default cursor and disable panning
               GLPIImpact.cy.panningEnabled(false);
               if (!GLPIImpact.eventData.showPointerForBadge) {
                  // Don't alter the cursor if hovering a badge
                  $(GLPIImpact.impactContainer).css('cursor', "default");
               }
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
            if (!GLPIImpact.eventData.showPointerForBadge) {
               // Don't alter the cursor if hovering a badge
               $(GLPIImpact.impactContainer).css('cursor', "move");
            }

            // Re-enable panning in case the mouse was over an edge
            GLPIImpact.cy.panningEnabled(true);
            break;

         case GLPIImpact.EDITION_ADD_NODE:
            if (!GLPIImpact.eventData.showPointerForBadge) {
               // Don't alter the cursor if hovering a badge
               $(GLPIImpact.impactContainer).css('cursor', "move");
            }
            // Re-enable panning in case the mouse was over an edge
            GLPIImpact.cy.panningEnabled(true);
            break;

         case GLPIImpact.EDITION_ADD_EDGE:
            break;

         case GLPIImpact.EDITION_DELETE:
            // Remove red overlay
            event.cy.filter().data('todelete', 0);
            event.cy.filter().unselect();
            if (!GLPIImpact.eventData.showPointerForBadge) {
               // Don't alter the cursor if hovering a badge
               $(GLPIImpact.impactContainer).css('cursor', "move");
            }
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

      // Undo log
      GLPIImpact.addToUndo(GLPIImpact.ACTION_REMOVE_FROM_COMPOUND, {
         nodeData    : _.clone(event.target.data()),
         compoundData: _.clone(parent.data()),
         children    : parent.children().map(function(node) {
            return node.data('id');
         }),
      });

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
      var hidden = GLPIImpact.cy
         .nodes(GLPIImpact.getHiddenSelector())
         .filter(function(node) {
            return !node.isParent();
         })
         .map(function(node) {
            return node.data('id');
         });

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
               var graph_id = itemtype + GLPIImpact.NODE_ID_SEPERATOR + value['id'];
               var isHidden = hidden.indexOf(graph_id) !== -1;
               var cssClass = "";

               if (isHidden) {
                  cssClass = "impact-res-disabled";
               }

               var str = '<p class="' + cssClass + '" data-id="' + value['id'] + '" data-type="' + itemtype + '">';
               str += '<img src="' + $(GLPIImpact.selectors.sideSearch + " img").attr('src') + '"></img>';
               str += value["name"];

               if (isHidden) {
                  str += '<i class="fas fa-eye-slash impact-res-hidden"></i>';
               }

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
      GLPIImpact.cy.nodes().not(GLPIImpact.getHiddenSelector()).forEach(function(node) {
         if (node.isParent()) {
            return;
         }

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
    * @param   {Number}  clientX
    * @param   {Number}  clientY
    * @param   {Boolean} rendered
    * @returns {Object}
    */
   projectIntoViewport: function (clientX, clientY, rendered) {
      var cy = this.cy;
      var offsets = this.findContainerClientCoords();
      var offsetLeft = offsets[0];
      var offsetTop = offsets[1];
      var scale = offsets[4];
      var pan = cy.pan();
      var zoom = cy.zoom();

      if (rendered) {
         return {
            x: clientX - offsetLeft,
            y: clientY - offsetTop
         };
      } else {
         return {
            x: ((clientX - offsetLeft) / scale - pan.x) / zoom,
            y: ((clientY - offsetTop) / scale - pan.y) / zoom
         };
      }
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

      $(GLPIImpact.selectors.undo).click(function() {
         GLPIImpact.undo();
      });

      // Redo button
      $(GLPIImpact.selectors.redo).click(function() {
         GLPIImpact.redo();
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
         GLPIImpact.addToUndo(GLPIImpact.ACTION_EDIT_IMPACT_VISIBILITY, {});
      });

      // Toggle depends visibility
      $(GLPIImpact.selectors.toggleDepends).click(function() {
         GLPIImpact.toggleVisibility(GLPIImpact.BACKWARD);
         GLPIImpact.addToUndo(GLPIImpact.ACTION_EDIT_DEPENDS_VISIBILITY, {});
      });

      // Depth selector
      $(GLPIImpact.selectors.maxDepth).on('input', function() {
         var previous = GLPIImpact.maxDepth;
         GLPIImpact.setDepth($(GLPIImpact.selectors.maxDepth).val());
         GLPIImpact.addToUndo(GLPIImpact.ACTION_EDIT_MAX_DEPTH, {
            oldDepth: previous,
            newDepth: GLPIImpact.maxDepth,
         });
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
         $(GLPIImpact.selectors.sideSearch + " > h4 > span").html($(img).attr('title'));
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
         var previous = GLPIImpact.edgeColors[GLPIImpact.BACKWARD];
         GLPIImpact.setEdgeColors({
            backward: $(GLPIImpact.selectors.dependsColor).val(),
         });
         GLPIImpact.updateStyle();
         GLPIImpact.cy.trigger("change");
         GLPIImpact.addToUndo(GLPIImpact.ACTION_EDIT_DEPENDS_COLOR, {
            oldColor: previous,
            newColor: GLPIImpact.edgeColors[GLPIImpact.BACKWARD]
         });
      });

      // Watch for color changes (impact)
      $(GLPIImpact.selectors.impactColor).change(function(){
         var previous = GLPIImpact.edgeColors[GLPIImpact.FORWARD];
         GLPIImpact.setEdgeColors({
            forward: $(GLPIImpact.selectors.impactColor).val(),
         });
         GLPIImpact.updateStyle();
         GLPIImpact.cy.trigger("change");
         GLPIImpact.addToUndo(GLPIImpact.ACTION_EDIT_IMPACT_COLOR, {
            oldColor: previous,
            newColor: GLPIImpact.edgeColors[GLPIImpact.FORWARD]
         });
      });

      // Watch for color changes (impact and depends)
      $(GLPIImpact.selectors.impactAndDependsColor).change(function(){
         var previous = GLPIImpact.edgeColors[GLPIImpact.BOTH];
         GLPIImpact.setEdgeColors({
            both: $(GLPIImpact.selectors.impactAndDependsColor).val(),
         });
         GLPIImpact.updateStyle();
         GLPIImpact.cy.trigger("change");
         GLPIImpact.addToUndo(GLPIImpact.ACTION_EDIT_IMPACT_AND_DEPENDS_COLOR, {
            oldColor: previous,
            newColor: GLPIImpact.edgeColors[GLPIImpact.BOTH]
         });
      });

      // Handle drag & drop on add node search result
      $(document).on('mousedown', GLPIImpact.selectors.sideSearchResults + ' p', function(e) {
         // Only on left click and not for disabled item
         if (e.which !== 1
            || $(e.target).hasClass('impact-res-disabled')
            || $(e.target).parent().hasClass('impact-res-disabled')) {
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
         // Middle click on badge, open link in new tab
         if (event.which == 2) {
            GLPIImpact.checkBadgeHitboxes(
               GLPIImpact.projectIntoViewport(e.clientX, e.clientY, true),
               true,
               true
            );
         }

         if (GLPIImpact.eventData.addNodeStart === undefined) {
            return;
         }

         if (e.target.nodeName == "CANVAS") {
            // Add node at event position
            GLPIImpact.addNode(
               GLPIImpact.eventData.addNodeStart.id,
               GLPIImpact.eventData.addNodeStart.type,
               GLPIImpact.projectIntoViewport(e.clientX, e.clientY, false)
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

   /**
    * Init and render the canvas overlay used to show the badges
    */
   initCanvasOverlay: function() {
      var layer = GLPIImpact.cy.cyCanvas();
      var canvas = layer.getCanvas();
      var ctx = canvas.getContext('2d');

      GLPIImpact.cy.on("render cyCanvas.resize", function() {
         layer.resetTransform(ctx);
         layer.clear(ctx);
         GLPIImpact.badgesHitboxes = [];

         GLPIImpact.cy.filter("node:childless:visible").forEach(function(node) {
            // Stop here if the node has no badge defined
            if (!node.data('badge')) {
               return;
            }

            // Set badge color, adjust contract as needed (target ratio is > 1.8)
            var rgb = hexToRgb(node.data('badge').color);
            while (contrast([255, 255, 255], [rgb.r, rgb.g, rgb.b]) < 1.8) {
               rgb.r *= 0.95;
               rgb.g *= 0.95;
               rgb.b *= 0.95;
            }

            // Set badge position (bottom right corner of the node)
            var bbox = node.renderedBoundingBox({
               includeLabels  : false,
               includeOverlays: false,
               includeNodes   : true,
            });
            var pos = {
               x: bbox.x2 + GLPIImpact.cy.zoom(),
               y: bbox.y2 + GLPIImpact.cy.zoom(),
            };

            // Register badge position so it can be clicked
            GLPIImpact.badgesHitboxes.push({
               position: pos,
               target  : node.data('badge').target,
               itemtype: node.data('id').split(GLPIImpact.NODE_ID_SEPERATOR)[0],
               id      : node.data('id').split(GLPIImpact.NODE_ID_SEPERATOR)[1],
            });

            // Draw the badge
            ctx.beginPath();
            ctx.arc(pos.x, pos.y, 4 * GLPIImpact.cy.zoom(), 0, 2 * Math.PI, false);
            ctx.fillStyle = "rgb(" + rgb.r + ", " + rgb.g + ", " + rgb.b + ")";
            ctx.fill();

            // Check if text should be light or dark by calculating the
            // grayscale of the background color
            var greyscale = (
               Math.round(rgb.r * 299)
               + Math.round(rgb.g * 587)
               + Math.round(rgb.b * 114)
            ) / 1000;
            ctx.fillStyle = (greyscale >= 138) ? '#4e4e4e' : 'white';

            // Print number
            ctx.font = 6 * GLPIImpact.cy.zoom() + "px sans-serif";
            ctx.fillText(
               node.data('badge').count,
               pos.x - (1.95 * GLPIImpact.cy.zoom()),
               pos.y + (2.23 * GLPIImpact.cy.zoom())
            );
         });
      });
   }
};

var searchAssetsDebounced = _.debounce(GLPIImpact.searchAssets, 400, false);
