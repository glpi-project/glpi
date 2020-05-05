/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

/* global GoInFullscreen, GoOutFullscreen, EasyMDE, getUuidV4, _ */

var Dashboard = {
   grid: null,
   elem_id: "",
   element: null,
   elem_dom: null,
   rand: null,
   interval: null,
   current_name: null,
   markdown_editors: [],
   all_cards: [],
   all_widgets: [],
   edit_mode: false,
   embed: false,
   ajax_cards: false,
   context: "core",
   markdown_contents: [],
   dash_width: 0,
   cell_margin: 3,
   cols: 26,

   display: function(params) {

      // get passed options and merge it with default ones
      var options = (typeof params !== 'undefined')
         ? params: {};
      var default_options = {
         cols:        24,
         rows:        24,
         cell_length: 40,
         cell_margin: 5,
         rand:        '',
         embed:       false,
         ajax_cards:  true,
         all_cards:   [],
         context:     "core"
      };
      options = Object.assign({}, default_options, options);

      this.rand         = options.rand;
      this.elem_id      = "#dashboard-"+options.rand;
      this.element      = $(Dashboard.elem_id);
      this.elem_dom     = Dashboard.element[0];
      this.current_name = $(this.elem_id+' .dashboard_select').val() || options.current;
      this.embed        = options.embed;
      this.ajax_cards   = options.ajax_cards;
      this.all_cards    = options.all_cards;
      this.all_widgets  = options.all_widgets;
      this.context      = options.context;
      this.dash_width   = this.element.width();
      this.cell_margin  = options.cell_margin;
      this.cols         = options.cols;

      $('#grid-stack-'+options.rand).gridstack({
         column: options.cols,
         maxRow: (options.rows + 1), // +1 for a hidden item at bottom (to fix height)
         verticalMargin: this.cell_margin,
         float: true, // widget can be placed anywhere on the grid, not only on top
         animate: false, // as we don't move widget automatically, we don't need animation
         draggable: { // override jquery ui draggable options
            'cancel': 'textarea' // avoid draggable on some child elements
         }
      });
      Dashboard.grid = $('#grid-stack-'+options.rand).data('gridstack');

      // set grid in static to prevent edition (unless user click on edit button)
      // previously in option, but current version of gridstack has a bug with one column mode (responsive)
      // see https://github.com/gridstack/gridstack.js/issues/1229
      Dashboard.grid.setStatic(true);

      // generate the css based on the grid width
      Dashboard.generateCss();

      // retieve cards content by ajax
      if (Dashboard.ajax_cards) {
         Dashboard.getCardsAjax();
      } else {
         Dashboard.fitNumbers();
         Dashboard.animateNumbers();
      }

      // change dashboard
      $("#dashboard-"+options.rand+" .toolbar .dashboard_select").change(function() {
         Dashboard.current_name = $(this).val();
         var selected_label = $(this).find("option:selected").text();
         $(".dashboard-name").val(selected_label);
         Dashboard.refreshDashboard();
         Dashboard.setLastDashboard();
      });

      // add dashboard
      $("#dashboard-"+options.rand+" .toolbar .add-dashboard").click(function() {
         Dashboard.addForm();
      });
      $(document).on('submit', '.display-add-dashboard-form', function(event) {
         event.preventDefault();

         $(".ui-dialog-content").dialog("close");
         var button    = $(this);
         var form_data = {};
         $.each(button.closest('.display-add-dashboard-form').serializeArray(), function() {
            form_data[this.name] = this.value;
         });

         Dashboard.addNew(form_data);
      });

      // delete dashboard
      $("#dashboard-"+options.rand+" .toolbar .delete-dashboard").click(function() {
         Dashboard.delete();
      });

      //clone dashboard
      $("#dashboard-"+options.rand+" .toolbar .clone-dashboard").click(function() {
         Dashboard.clone();
      });

      // embed mode toggle
      $("#dashboard-"+options.rand+" .toolbar .open-embed").click(function() {
         $('<div title="'+__("Share or embed this dashboard")+'"></div>')
            .load(CFG_GLPI.root_doc+"/ajax/dashboard.php", {
               action:  'display_embed_form',
               dashboard: Dashboard.current_name
            }, function() {
               $(this).dialog({
                  width: 300,
                  modal: true,
                  open: function() {
                     $(this).find('input').first().focus();
                  }
               });
            });
      });

      // edit mode toggle
      $("#dashboard-"+options.rand+" .toolbar .fa-edit").click(function() {
         var activate = !$(this).hasClass('active');

         Dashboard.setEditMode(activate);
      });

      // fullscreen mode toggle
      var expand_selector = "#dashboard-"+options.rand+" .toggle-fullscreen";
      $(expand_selector).click(function() {
         Dashboard.toggleFullscreenMode($(this));
      });
      // trigger fullscreen off (by esc key)
      $(document).on('fullscreenchange webkitfullscreenchange mozfullscreenchange MSFullscreenChange', function() {
         if (!document.webkitIsFullScreen
             && !document.mozFullScreen
             && !document.msFullscreenElement !== null) {
            Dashboard.disableFullscreenMode();
         }
      });

      // night mode toggle
      $("#dashboard-"+options.rand+" .toolbar .fa-moon").click(function() {
         $(this).toggleClass('active');
         Dashboard.element.toggleClass('nightmode');
      });

      // refresh mode toggle
      $("#dashboard-"+options.rand+" .toolbar .fa-history").click(function() {
         $(this).toggleClass('active');
         var active = $(this).hasClass('active');

         if (active) {
            var seconds = parseInt(CFG_GLPI.refresh_ticket_list) * 60 || 30;
            Dashboard.interval = setInterval(function() {
               Dashboard.refreshDashboard();
            }, seconds * 1000);
         } else {
            clearInterval(Dashboard.interval);
         }
      });

      // browser resized (use debounce to delay generation of css)
      var debounce;
      $(window).on('resize', function(event) {
         if (event.target.constructor.name !== "Window") {
            return;
         }

         window.clearTimeout(debounce);
         debounce = window.setTimeout(function() {
            Dashboard.generateCss();

            // fit again numbers
            Dashboard.fitNumbers();
            Dashboard.animateNumbers();
         }, 200);
      });

      // publish rights
      $(document).on('click', '.display-rights-form .save_rights', function() {
         $(".ui-dialog-content").dialog("close");

         var button    = $(this);
         var form_data = {};
         $.each(button.closest('.display-rights-form').serializeArray(), function() {
            var current_val = this.value.split('-');
            var right_name  = current_val[0];
            var value       = current_val[1];
            if (!(right_name in form_data)) {
               form_data[right_name] = [];
            }
            form_data[right_name].push(value);
         });

         $.ajax({
            method: 'POST',
            url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
            data: {
               action:    'save_rights',
               dashboard: Dashboard.current_name,
               rights:    form_data,
            }
         });
      });

      // event: moving item
      $('#grid-stack-'+options.rand).on('dragstop', function() {
         Dashboard.saveDashboard();
      });

      // event: resize item
      $('#grid-stack-'+options.rand).on('gsresizestop', function(event, elem) {
         Dashboard.saveDashboard();

         // resize also chart if exists
         var chart = $(elem).find('.ct-chart');
         if (chart.length > 0)  {
            chart[0].__chartist__.update();
         }

         // animate the number
         Dashboard.fitNumbers($(elem));
         Dashboard.animateNumbers($(elem));
      });

      // delete item
      $(document).on('click', "#dashboard-"+options.rand+" .delete-item", function() {
         var del_ctrl = $(this);
         var item = del_ctrl.closest('.grid-stack-item');

         Dashboard.grid.removeWidget(item);
         Dashboard.saveDashboard();
      });

      // refresh item
      $(document).on('click', "#dashboard-"+options.rand+" .refresh-item", function() {
         var refresh_ctrl = $(this);
         var item = refresh_ctrl.closest('.grid-stack-item');
         var id = item.data('gs-id');

         Dashboard.getCardsAjax("[data-gs-id="+id+"]");
      });

      // edit item
      $(document).on('click', "#dashboard-"+options.rand+" .edit-item", function() {
         var edit_ctrl = $(this);
         var item      = edit_ctrl.parent().parent('.grid-stack-item');
         var card_opt  = item.data('card-options');

         $(".ui-dialog-content").dialog("close");
         $('<div title="'+__("Edit this card")+'"></div>')
            .load(CFG_GLPI.root_doc+"/ajax/dashboard.php", {
               action:       'display_edit_widget',
               gridstack_id: item.data('gs-id'),
               card_id:      card_opt.card_id,
               x:            item.data('gs-x'),
               y:            item.data('gs-y'),
               width:        item.data('gs-width'),
               height:       item.data('gs-height'),
               card_options: card_opt,
            }, function() {
               $(this).dialog({
                  width: 'auto',
                  modal: true,
                  open: function() {
                     $(this).find('input[type=submit]').first().focus();
                  }
               });
            });
      });

      // add new widget form
      $("#dashboard-"+options.rand+" .cell-add").click(function() {
         var add_ctrl = $(this);

         $(".ui-dialog-content").dialog("close");
         $('<div title="'+__("Add a card")+'"></div>')
            .load(CFG_GLPI.root_doc+"/ajax/dashboard.php", {
               action: 'display_add_widget',
               x: add_ctrl.data('x'),
               y: add_ctrl.data('y')
            }, function() {
               $(this).dialog({
                  width: 'auto',
                  modal: true,
                  open: function() {
                     $(this).find('input[type=submit]').first().focus();
                  }
               });
            });
      });

      // save new or existing widget (submit form)
      $(document).on('submit', '.display-widget-form ', function(event) {
         event.preventDefault();

         var form = $(this);
         var edit = form.has('.edit-widget').length > 0;

         Dashboard.setWidgetFromForm(form, edit);
      });

      // rename dashboard
      $(document).on('click', '.save-dashboard-name ', function(event) {
         event.preventDefault();
         // change in selector
         $('.dashboard_select option[value='+Dashboard.current_name+']')
            .text($(".dashboard-name").val());
         Dashboard.saveDashboard();

         $('.display-message')
            .addClass('success')
            .text(__("Saved"))
            .show('fade').delay(2000).hide('fade');
      });

      // display widget types after selecting a card
      $(document).on('select2:select', '.display-widget-form select[name=card_id]', function(event) {
         var select2_data      = event.params.data;
         var selected          = select2_data.id;
         var widgettype_field  = $(this).closest('.field').siblings('.widgettype_field');
         var available_widgets = Dashboard.all_cards[selected].widgettype;
         var force_checked     = available_widgets.length === 1;

         widgettype_field
            .show()
            .find('input[type=radio]')
            .next('label').css('display', 'none').end()
            .filter("[value='"+available_widgets.join("'],[value='")+"']")
            .prop("checked", force_checked)
            .next('label').css('display', 'inline-block');
      });

      // display gradient and limit after selecting a widget
      $(document).on('change', '.display-widget-form [name=widgettype]', function() {
         var widgetdom   = $(this);
         var widgettype  = widgetdom.val();
         var widget      = Dashboard.all_widgets[widgettype];
         var usegradient = widget.gradient || false;
         var uselimit    = widget.limit || false;

         widgetdom
            .closest('.field')
            .siblings('.gradient_field')
            .hide()
            .toggle(usegradient).end()
            .siblings('.limit_field')
            .hide()
            .toggle(uselimit).end();
      });

      // markdown textarea edited
      $(document).on('input', '.card.markdown textarea.markdown_content', function() {
         Dashboard.saveMarkdown($(this));
      });
   },

   saveMarkdown:function(textarea) {
      var item = textarea.closest('.grid-stack-item');
      var content = textarea.val();
      var gs_id = item.data('gs-id');

      item.addClass('dirty');
      Dashboard.markdown_contents[gs_id] = content;
   },

   setWidgetFromForm: function(form, edit_item) {
      edit_item = edit_item || false;

      $(".ui-dialog-content").dialog("close");
      var form_data  = {};

      $.each(form.serializeArray(), function() {
         form_data[this.name] = this.value;
      });

      // no card selected
      if (form_data.card_id === "0") {
         return false;
      }

      form_data.card_options = form_data.card_options || {};
      if (typeof form_data.card_options === "string") {
         form_data.card_options = JSON.parse(form_data.card_options);
      }

      // prepare options
      form_data.card_options.color        = form_data.color || null;
      form_data.card_options.widgettype   = form_data.widgettype || null;
      form_data.card_options.use_gradient = form_data.use_gradient || 0;
      form_data.card_options.limit        = form_data.limit || 7;

      // specific case for markdown
      if (form_data.card_id === "markdown_editable"
      && !('markdown_content' in form_data.card_options)) {
         form_data.card_options.markdown_content = "";
      }

      // id edit mode remove old item before adding the new
      if (edit_item === true) {
         if (form_data.old_id === "0") {
            return false;
         }
         var item = $('.grid-stack-item[data-gs-id='+form_data.old_id+']');
         Dashboard.grid.removeWidget(item);
      }

      // complete ajax data
      var uuid = getUuidV4();
      form_data.gridstack_id = form_data.card_id+"_"+uuid;
      form_data.card_options.card_id = form_data.card_id;
      form_data.card_options.gridstack_id = form_data.gridstack_id;

      var args = form_data.card_options;
      args.force = true;

      // add the new widget
      var widget = Dashboard.addWidget(form_data);

      // get the html of the new card and save dashboard
      $.ajax({
         method: 'GET',
         url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
         data: {
            action:    'get_card',
            dashboard: Dashboard.current_name,
            card_id:   form_data.card_id,
            args:      args,
         }
      }).done(function(card_html) {
         widget
            .children('.grid-stack-item-content')
            .append(card_html);
         Dashboard.fitNumbers(widget);
         Dashboard.animateNumbers(widget);
         Dashboard.saveDashboard();
      });
   },

   addWidget: function(p) {
      var gridstack_id = p.gridstack_id;
      var x            = p.x || -1;
      var y            = p.y || -1;
      var width        = p.width || 2;
      var height       = p.height || 2;
      var options      = p.card_options || {};

      var html = ' \
      <div class="grid-stack-item"> \
         <span class="controls"> \
            <i class="refresh-item fas fa-sync-alt" title="'+__("Refresh this card")+'"></i> \
            <i class="edit-item fas fa-edit" title="'+__("Edit this card")+'"></i> \
            <i class="delete-item fas fa-times" title="'+__("Delete this card")+'"></i> \
         </span> \
         <div class="grid-stack-item-content"> \
         </div> \
      </div>';

      // add the widget to the grid
      var widget = Dashboard.grid.addWidget(
         html,
         x,
         y,
         width,
         height,
         x < 0 || y < 0,
         undefined, undefined, undefined, undefined, // min, max dimensions
         gridstack_id
      );

      // append options
      widget.data('card-options', options);

      return widget;
   },


   refreshDashboard: function() {
      var gridstack = $(Dashboard.elem_id+" .grid-stack");
      Dashboard.grid.removeAll();

      $.get({
         url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
         data: {
            dashboard: Dashboard.current_name,
            action: 'get_dashboard_items',
            embed: (Dashboard.embed ? 1 : 0),
         }
      }).done(function(html) {
         gridstack.prepend(html);
         gridstack.find('.grid-stack-item').each(function() {
            Dashboard.grid.makeWidget($(this));
         });

         if (Dashboard.ajax_cards) {
            Dashboard.getCardsAjax();
         }
      });
   },

   setLastDashboard: function() {
      $.ajax({
         method: 'POST',
         url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
         data: {
            dashboard: Dashboard.current_name,
            page: (location.origin+location.pathname)
               .replace(CFG_GLPI.url_base, ''),
            action: 'set_last_dashboard',
         }
      });
   },


   saveDashboard: function(force_refresh) {
      force_refresh = force_refresh | false;

      var serializedData = $.makeArray(
         Dashboard.element.find('.grid-stack-item:visible:not(.grid-stack-placeholder)')
      ) .map(function (v) {
         var n = $(v).data('_gridstack_node');
         var options = $(v).data('card-options');

         // replace markdown content (this to avoid unwanted slashing)
         if (_.keys(Dashboard.markdown_contents).length > 0
             && n.id in Dashboard.markdown_contents) {
            options.markdown_content = Dashboard.markdown_contents[n.id];
         }

         return n ? {
            gridstack_id: n.id,
            card_id: options.card_id,
            x: n.x,
            y: n.y,
            width: n.width,
            height: n.height,
            card_options: options
         } : null;
      });

      $.ajax({
         method: 'POST',
         url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
         data: {
            action: 'save_items',
            dashboard: Dashboard.current_name,
            items: serializedData,
            title: $(".dashboard-name").val()
         }
      }).done(function() {
         if (force_refresh) {
            Dashboard.refreshDashboard();
         }
      });
   },

   fitNumbers: function(parent_item) {
      parent_item = parent_item || $('body');

      var text_offset = 0.96;

      // responsive mode
      if (this.dash_width <= 700
          || this.grid.container.hasClass('grid-stack-one-column-mode')) {
         text_offset = 1.8;
      }

      parent_item
         .find('.big-number')
         .find('.formatted-number').fitText(text_offset);

      parent_item
         .find('.big-number')
         .find('.label').fitText(text_offset - 0.2);
   },

   animateNumbers: function(parent_item) {
      parent_item = parent_item || $('body');

      parent_item
         .find('.multiple-number, .big-number')
         .find('.formatted-number')
         .each(function () {
            var count     = $(this);
            var precision = count.data('precision');
            var number    = count.children('.number');
            var suffix    = count.children('.suffix').text();

            jQuery({ Counter: 0 }).animate({ Counter: number.text() }, {
               duration: 800,
               easing: 'swing',
               step: function () {
                  number.text(this.Counter.toFixed(precision))+suffix;
               }
            });
         });
   },

   setEditMode: function(activate) {
      Dashboard.edit_mode = typeof activate == "undefined" ? true : activate;

      var edit_ctrl = $(Dashboard.elem_id+" .toolbar .fa-edit");
      edit_ctrl.toggleClass('active', activate);
      Dashboard.element.toggleClass('edit-mode', activate);
      Dashboard.grid.setStatic(!activate);

      if (!Dashboard.edit_mode) {
         // save markdown textareas set as dirty
         var dirty_textareas = $(".grid-stack-item.dirty");
         if (dirty_textareas.length > 0) {
            Dashboard.saveDashboard(true);
         }
      }
   },

   toggleFullscreenMode: function(fs_ctrl) {
      var fs_enabled = !fs_ctrl.hasClass('active');

      Dashboard.element.toggleClass('fullscreen')
         .find('.fa-moon').toggle(fs_enabled);
      fs_ctrl.toggleClass('active');

      // desactivate edit mode
      if (fs_enabled) {
         Dashboard.setEditMode(false);
      }

      // fullscreen browser api
      if (fs_enabled) {
         GoInFullscreen(Dashboard.elem_dom);
      } else {
         GoOutFullscreen();
      }
   },

   disableFullscreenMode: function() {
      Dashboard.element
         .removeClass('fullscreen')
         .find('.fa-moon').hide().end()
         .find('.toggle-fullscreen').removeClass('active');

      GoOutFullscreen();
   },

   /**
    * Clone current dashboard
    * (clean all previous gridstack_id in cards)
    */
   clone: function() {
      $.post({
         url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
         data: {
            dashboard: Dashboard.current_name,
            action: 'clone_dashboard',
         },
         dataType: 'json'
      }).done(function(new_dash) {
         Dashboard.addNewDashbardInSelect(new_dash.title, new_dash.key);
      });
   },

   /**
    * Delete current dashboard
    */
   delete: function() {
      var confirm_msg = __("Are you sure you want to delete the dashboard %s ?")
         .replace('%s', Dashboard.current_name);
      if (window.confirm(confirm_msg, __("Delete this dashboard"))) {
         $.post({
            url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
            data: {
               action: 'delete_dashboard',
               dashboard: Dashboard.current_name,
            }
         }).done(function() {
            $("#dashboard-"+Dashboard.rand+" .toolbar .dashboard_select")
               .find("option[value='"+Dashboard.current_name+"']").remove()
               .end() // reset find filtering
               .prop("selectedIndex", 0)
               .trigger('change');
         });
      }
   },

   /**
    * Display form to add a new dashboard
    */
   addForm: function() {
      $(".ui-dialog-content").dialog("close");
      $('<div title="'+__("Add a new dashboard")+'"></div>')
         .load(CFG_GLPI.root_doc+"/ajax/dashboard.php", {
            action: 'add_new',
         }, function() {
            $(this).dialog({
               width: 'auto',
               modal: true,
               open: function() {
                  $(this).find('input').first().focus();
               }
            });
         });
   },

   addNew: function(form_data) {
      $.post({
         url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
         data: {
            action: 'save_new_dashboard',
            title: form_data.title,
            context: Dashboard.context,
         }
      }).done(function(dashboard_key) {
         Dashboard.addNewDashbardInSelect(form_data.title, dashboard_key);
         Dashboard.setEditMode(true);
      });
   },

   /**
    * Add a new option to top left dashboard select
    */
   addNewDashbardInSelect: function(label, value) {
      var newOption = new Option(label, value, false, true);
      $("#dashboard-"+Dashboard.rand+" .toolbar .dashboard_select")
         .append(newOption)
         .trigger('change');
   },

   getCardsAjax: function(specific_one) {
      specific_one = specific_one || "";

      var promises = [];
      $(".grid-stack-item:not(.lock-bottom)"+specific_one).each(function() {
         var card         = $(this);
         var card_opt     = card.data('card-options');
         var gridstack_id = card.data('gs-id');
         var card_id      = card_opt.card_id || card.data('gs-id');

         card_opt.gridstack_id = gridstack_id;

         // store markdown after card reload
         if ("markdown_content" in card_opt) {
            Dashboard.markdown_contents[gridstack_id] = card_opt.markdown_content;
         }

         promises.push($.get(CFG_GLPI.root_doc+"/ajax/dashboard.php", {
            'action':    'get_card',
            'dashboard': Dashboard.current_name,
            'card_id':   card_id,
            'force':     (specific_one.length > 0 ? 1 : 0),
            'embed':     (Dashboard.embed ? 1 : 0),
            'args':      card_opt,
         }).then(function(html) {
            card.children('.grid-stack-item-content')
               .html(html);

            Dashboard.fitNumbers(card);
            Dashboard.animateNumbers(card);
         }).fail(function() {
            card.html("<div class='empty-card card-error'><i class='fas fa-exclamation-triangle'></i></div>");
         }));
      });

      return promises;
   },


   easter: function() {
      var items = $(Dashboard.elem_id+" .grid-stack .grid-stack-item .card");

      setInterval(function() {
         var color = "#"+((1<<24)*Math.random()|0).toString(16);
         var no_item = Math.floor(Math.random() * items.length) + 1;
         var item = items[no_item];
         $(item).css('background-color', color);
      }, 10);
   },

   generateCss: function() {
      var dash_width    = Math.floor(this.element.width());
      var cell_length   = dash_width / this.cols;
      var cell_height   = cell_length;
      var cell_fullsize = (dash_width / this.cols) + this.cell_margin;
      var width_percent = 100 / this.cols;

      var style = " \
      "+this.elem_id+" .cell-add { \
         width: "+cell_length+"px; \
         height: "+cell_fullsize+"px; \
      } \
      "+this.elem_id+" .grid-guide { \
         background-size: "+cell_length+"px "+cell_fullsize+"px; \
         bottom: "+cell_fullsize+"px; \
      }";

      for (var i = 0; i < this.cols; i++) {
         var left  = i * width_percent;
         var width = (i+1) * width_percent;

         style+= this.elem_id+" .grid-stack > .grid-stack-item[data-gs-x='"+i+"'] { \
            left: "+left+"%; \
         } \
         "+this.elem_id+" .grid-stack > .grid-stack-item[data-gs-width='"+(i+1)+"'] { \
            min-width: "+width_percent+"%; \
            width: "+width+"%; \
         }";
      }

      // remove old inline styles
      $("#gs_inline_css_"+this.rand).remove();

      // add new style
      if (dash_width > 700) {
         $("<style id='gs_inline_css_"+this.rand+"'></style>")
            .prop("type", "text/css")
            .html(style)
            .appendTo("head");
      } else {
         cell_height = 60;
      }

      // apply new height to gridstack
      this.grid.cellHeight(cell_height);
   }

};
