/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

/* global GridStack, GoInFullscreen, GoOutFullscreen, EasyMDE, getUuidV4, _, sortable */
/* global glpi_ajax_dialog, glpi_close_all_dialogs */

const Dashboard = {
    dashboards: {},

    getActiveDashboard: function() {
        var current_dashboard_index = "";
        $.each(this.dashboards, function(index, dashboard) {
            if ($(dashboard.elem_dom).is(':visible')) {
                current_dashboard_index = index;
                return false; // Break
            }
        });

        return this.dashboards[current_dashboard_index];
    }
};

class GLPIDashboard {
    constructor(params) {
        const that = this;

        this.grid = null;
        this.elem_id = "";
        this.element = null;
        this.elem_dom = null;
        this.rand = null;
        this.interval = null;
        this.current_name = null;
        this.markdown_editors = [];
        this.all_cards = [];
        this.all_widgets = [];
        this.edit_mode = false;
        this.embed = false;
        this.ajax_cards = false;
        this.context = "core";
        this.markdown_contents = [];
        this.dash_width = 0;
        this.cell_margin = 3;
        this.cols = 26;
        this.cache_key = "";
        this.filters = "{}";
        this.filters_selector = "";

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
            token:       null,
            entities_id: null,
            is_recursive:null,
            ajax_cards:  true,
            all_cards:   [],
            context:     "core"
        };
        options = Object.assign({}, default_options, options);

        this.rand         = options.rand;
        this.elem_id      = "#dashboard-"+options.rand;
        this.element      = $(this.elem_id);
        this.elem_dom     = this.element[0];
        this.current_name = $(this.elem_id+' .dashboard_select').val() || options.current;
        this.embed        = options.embed;
        this.token        = options.token;
        this.entities_id  = options.entities_id;
        this.is_recursive = options.is_recursive;
        this.ajax_cards   = options.ajax_cards;
        this.all_cards    = options.all_cards;
        this.all_widgets  = options.all_widgets;
        this.context      = options.context;
        this.dash_width   = this.element.width();
        this.cell_margin  = options.cell_margin;
        this.cols         = options.cols;
        this.cache_key    = options.cache_key || "";
        this.filters_selector = this.elem_id + ' .filters';

        // compute the width offset of gridstack container relatively to viewport
        var elem_domRect = this.elem_dom.getBoundingClientRect();
        var width_offset = elem_domRect.left + (window.innerWidth - elem_domRect.right) + 0.02;

        this.grid = GridStack.init({
            column: options.cols,
            maxRow: (options.rows + 1), // +1 for a hidden item at bottom (to fix height)
            margin : this.cell_margin,
            float: true, // widget can be placed anywhere on the grid, not only on top
            animate: false, // as we don't move widget automatically, we don't need animation
            draggable: { // override jquery ui draggable options
                'cancel': 'textarea' // avoid draggable on some child elements
            },
            'minWidth': 768 -  width_offset, // breakpoint of one column mode (based on the dashboard container width), trying to reduce to match the `-md` breakpoint of bootstrap (this last is based on viewport width)
        }, "#grid-stack-" + options.rand);

        // set grid in static to prevent edition (unless user click on edit button)
        // previously in option, but current version of gridstack has a bug with one column mode (responsive)
        // see https://github.com/gridstack/gridstack.js/issues/1229
        this.grid.setStatic(true);

        // generate the css based on the grid width
        this.generateCss();

        // init filters from storage
        this.initFilters();
        this.refreshDashboard();

        // animate the dashboards once all card are loaded (single ajax mode)
        if (!this.ajax_cards) {
            this.fitNumbers();
            this.animateNumbers();
        }

        // change dashboard
        $("#dashboard-"+options.rand+" .toolbar .dashboard_select").change(function() {
            that.current_name = $(this).val();
            var selected_label = $(this).find("option:selected").text();
            $(".dashboard-name").val(selected_label);
            that.refreshDashboard();
            that.setLastDashboard();
            that.initFilters();
        });

        // add dashboard
        $("#dashboard-"+options.rand+" .toolbar .add-dashboard").click(function() {
            that.addForm();
        });
        $(document).on('submit', '.display-add-dashboard-form', function(event) {
            event.preventDefault();

            glpi_close_all_dialogs();
            var button    = $(this);
            var form_data = {};
            $.each(button.closest('.display-add-dashboard-form').serializeArray(), function() {
                form_data[this.name] = this.value;
            });

            that.addNew(form_data);
        });

        // delete dashboard
        $("#dashboard-"+options.rand+" .toolbar .delete-dashboard").click(function() {
            that.delete();
        });

        //clone dashboard
        $("#dashboard-"+options.rand+" .toolbar .clone-dashboard").click(function() {
            that.clone();
        });

        // embed mode toggle
        $("#dashboard-"+options.rand+" .toolbar .open-embed").click(function() {
            glpi_ajax_dialog({
                title: __("Share or embed this dashboard"),
                url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
                params: {
                    action:  'display_embed_form',
                    dashboard: that.current_name
                },
            });
        });

        // edit mode toggle
        $("#dashboard-"+options.rand+" .toolbar .edit-dashboard").click(function() {
            var activate = !$(this).hasClass('active');

            that.setEditMode(activate);
        });

        // fullscreen mode toggle
        var expand_selector = "#dashboard-"+options.rand+" .toggle-fullscreen";
        $(expand_selector).click(function() {
            that.toggleFullscreenMode($(this));
        });
        // trigger fullscreen off (by esc key)
        $(document).on('fullscreenchange webkitfullscreenchange mozfullscreenchange MSFullscreenChange', function() {
            if (!document.webkitIsFullScreen
             && !document.mozFullScreen
             && !document.msFullscreenElement !== null) {
                that.disableFullscreenMode();
            }
        });

        // night mode toggle
        $("#dashboard-"+options.rand+" .toolbar .night-mode").click(function() {
            $(this).toggleClass('active');
            that.element.toggleClass('theme-dark');
        });

        // refresh mode toggle
        $("#dashboard-"+options.rand+" .toolbar .auto-refresh").click(function() {
            $(this).toggleClass('active');
            var active = $(this).hasClass('active');

            if (active) {
                var minutes = parseInt(CFG_GLPI.refresh_views);
                if (minutes == 0 || Number.isNaN(minutes)) {
                    minutes = 30;
                }
                var seconds = minutes * 60;
                that.interval = setInterval(function() {
                    that.refreshDashboard();
                }, seconds * 1000);
            } else {
                clearInterval(that.interval);
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
                that.generateCss();

                // fit again numbers
                that.fitNumbers();
            }, 200);
        });

        // publish rights
        $(document).on('click', '.display-rights-form .save_rights', function() {
            glpi_close_all_dialogs();

            var button    = $(this);
            var form_data = {};
            var is_private;
            $.each(button.closest('.display-rights-form').serializeArray(), function() {
                var current_val = this.value.split('-');
                if (current_val.length !== 2) {
                    return;
                }
                var right_name  = current_val[0];
                var value       = current_val[1];
                if (!(right_name in form_data)) {
                    form_data[right_name] = [];
                }
                form_data[right_name].push(value);
            });
            is_private = button.closest('.display-rights-form').find('select[name="is_private"]').val();

            $.post({
                url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
                data: {
                    action:     'save_rights',
                    dashboard:  that.current_name,
                    rights:     form_data,
                    is_private: is_private,
                }
            });
        });

        // event: moving item
        this.grid.on('dragstop', function() {
            that.saveDashboard();
        });

        // event: resize item
        this.grid.on('resizestop', function(event, elem) {
            that.saveDashboard();

            // resize also chart if exists
            var chart = $(elem).find('.ct-chart');
            if (chart.length > 0 && chart[0].__chartist__ != undefined)  {
                chart[0].__chartist__.update();
            }

            // Used after "resize.fittext" event to reset our custom width "trick"
            // See computeWidth() function for more info on the trick
            that.resetComputedWidth($('body').find('.big-number').find('.formatted-number'));
            that.resetComputedWidth($('body').find('.big-number').find('.label'));

            // animate the number
            that.fitNumbers($(elem));
            that.animateNumbers($(elem));
        });

        // delete item
        $(document).on('click', "#dashboard-"+options.rand+" .delete-item", function() {
            var del_ctrl = $(this);
            var item = del_ctrl.closest('.grid-stack-item')[0];

            that.grid.removeWidget(item);
            that.saveDashboard();
        });

        // refresh item
        $(document).on('click', "#dashboard-"+options.rand+" .refresh-item", function() {
            var refresh_ctrl = $(this);
            var item = refresh_ctrl.closest('.grid-stack-item');
            var id = item.attr('gs-id');

            that.getCardsAjax("[gs-id="+id+"]");
        });

        // edit item
        $(document).on('click', "#dashboard-"+options.rand+" .edit-item", function() {
            var edit_ctrl = $(this);
            var item      = edit_ctrl.parent().parent('.grid-stack-item');
            var card_opt  = item.data('card-options');

            glpi_ajax_dialog({
                title: __("Edit this card"),
                url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
                params: {
                    action:       'display_edit_widget',
                    dashboard:    that.current_name,
                    gridstack_id: item.attr('gs-id'),
                    card_id:      card_opt.card_id,
                    x:            item.attr('gs-x'),
                    y:            item.attr('gs-y'),
                    width:        item.attr('gs-w'),
                    height:       item.attr('gs-h'),
                    card_options: card_opt,
                },
            });
        });

        // add new widget form
        $(document).on("click", "#dashboard-"+options.rand+" .cell-add", function() {
            var add_ctrl = $(this);

            glpi_ajax_dialog({
                title: __("Add a card"),
                url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
                params: {
                    action: 'display_add_widget',
                    dashboard: that.current_name,
                    x: add_ctrl.data('x'),
                    y: add_ctrl.data('y')
                },
            });
        });

        // save new or existing widget (submit form)
        $(document).on('submit', '.display-widget-form ', function(event) {
            event.preventDefault();

            that.setWidgetFromForm($(this));
        });

        // add new filter
        $(document).on("click", "#dashboard-"+options.rand+" .filters_toolbar .add-filter", function() {
            glpi_close_all_dialogs();

            var filters = that.getFiltersFromDB();
            var filter_names    = Object.keys(filters);

            glpi_ajax_dialog({
                title: __("Add a filter"),
                url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
                params: {
                    action: 'display_add_filter',
                    dashboard: that.current_name,
                    used: filter_names
                },
            });
        });

        // save new filter (submit form)
        $(document).on('submit', '.display-filter-form ', function(event) {
            event.preventDefault();

            var form = $(this);

            that.setFilterFromForm(form);
        });

        // delete existing filter
        $(document).on("click", "#dashboard-"+options.rand+" .filters_toolbar .delete-filter", function() {
            var filter = $(this).closest('.filter');
            var filter_id = filter.data('filter-id');

            // remove filter from dom
            filter.remove();

            // remove filter from storage and refresh cards
            var filters = that.getFiltersFromDB();
            delete filters[filter_id];
            that.setFiltersInDB(filters);
            that.refreshCardsImpactedByFilter(filter_id);
        });

        // rename dashboard
        $(document).on('click', '.save-dashboard-name ', function(event) {
            event.preventDefault();
            // change in selector
            $('.dashboard_select option[value='+that.current_name+']')
                .text($(".dashboard-name").val());
            that.saveDashboard();

            $('.display-message')
                .addClass('success')
                .text(__("Saved"))
                .show('fade').delay(2000).hide('fade');
        });

        // display widget types after selecting a card
        $(document).on('select2:select', '.display-widget-form select[name=card_id]', function(event) {
            var select2_data      = event.params.data;
            var selected          = select2_data.id;
            var widgettype_field  = $(this).closest('.display-widget-form').find('.widgettype_field');
            var available_widgets = that.all_cards[selected].widgettype;
            var force_checked     = available_widgets.length === 1;

            widgettype_field
                .show()
                .find('input[type=radio]')
                .next('label').css('display', 'none').end()
                .filter("[value='"+available_widgets.join("'],[value='")+"']")
                .prop("checked", force_checked)
                .trigger('change')
                .next('label').css('display', 'inline-block');
        });

        // display gradient and limit after selecting a widget
        $(document).on('change', '.display-widget-form [name=widgettype]', function() {
            var widgetdom   = $(this);
            var widgettype  = widgetdom.val();
            var widget      = that.all_widgets[widgettype];
            var usegradient = widget.gradient || false;
            var pointlabels = widget.pointlbl || false;
            var uselimit    = widget.limit || false;
            var width       = widget.width  || 2;
            var height      = widget.height || 2;

            var form = widgetdom.closest('.display-widget-form');
            form.find('.gradient_field').toggle(usegradient);
            form.find('.pointlbl_field').toggle(pointlabels);
            form.find('.limit_field').toggle(uselimit);

            var width_field = form.find('[name="width"]');
            var height_field = form.find('[name="height"]');
            if (width_field.val() == 0) {
                width_field.val(width);
            }
            if (height_field.val() == 0) {
                height_field.val(height);
            }
        });

        // markdown textarea edited
        $(document).on('input', '.card.markdown textarea.markdown_content', function() {
            that.saveMarkdown($(this));
        });

        // FitText() add an event listener that recompute the font size of all
        // "fittexted" elements of the page.
        // This means we need to apply our max-width "trick" on this event
        // See computeWidth() function for more info on the trick
        $(window).on('resize.fittext', function() {
            that.computeWidth($('body').find('.big-number').find('.formatted-number'));
            that.computeWidth($('body').find('.big-number').find('.label'));
        });

        // Keep track of instance
        Dashboard.dashboards[options.rand] = this;
    }

    saveMarkdown(textarea) {
        var item = textarea.closest('.grid-stack-item');
        var content = textarea.val();
        var gs_id = item.attr('gs-id');

        item.addClass('dirty');
        this.markdown_contents[gs_id] = content;
    }

    setWidgetFromForm(form) {
        const that = this;

        glpi_close_all_dialogs();
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

        var edit_item = "old_id" in form_data && form_data.old_id.length > 0;

        // prepare options
        form_data.card_options.color        = form_data.color || null;
        form_data.card_options.widgettype   = form_data.widgettype || null;
        form_data.card_options.use_gradient = form_data.use_gradient || 0;
        form_data.card_options.point_labels = form_data.point_labels || 0;
        form_data.card_options.limit        = form_data.limit || 7;

        // specific case for markdown
        if (
            form_data.card_id === "markdown_editable"
            && !('markdown_content' in form_data.card_options)
        ) {
            form_data.card_options.markdown_content = "";
        }

        // id edit mode remove old item before adding the new
        if (edit_item === true) {
            if (form_data.old_id === "0") {
                return false;
            }
            var item = $('.grid-stack-item[gs-id='+form_data.old_id+']')[0];
            this.grid.removeWidget(item);
        }

        // complete ajax data
        var uuid = getUuidV4();
        form_data.gridstack_id = form_data.card_id+"_"+uuid;
        form_data.card_options.card_id = form_data.card_id;
        form_data.card_options.gridstack_id = form_data.gridstack_id;

        var args = form_data.card_options;
        args.force = true;
        args.apply_filters = this.getFiltersFromDB();

        // add the new widget
        var widget = this.addWidget(form_data);

        // get the html of the new card and save dashboard
        $.get({
            url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
            data: {
                action:    'get_card',
                dashboard: this.current_name,
                card_id:   form_data.card_id,
                cache_key: this.cache_key,
                args:      args,
            }
        }).done(function(card_html) {
            widget
                .children('.grid-stack-item-content')
                .append(card_html);
            that.fitNumbers(widget);
            that.animateNumbers(widget);
            that.saveDashboard();
        });
    }

    addWidget(p) {
        var gridstack_id = p.gridstack_id;
        var x            = parseInt(p.x || -1);
        var y            = parseInt(p.y || -1);
        var width        = parseInt(p.width || 2);
        var height       = parseInt(p.height || 2);
        var options      = p.card_options || {};

        var html = ' \
      <div class="grid-stack-item"> \
         <span class="controls"> \
            <i class="refresh-item ti ti-refresh" title="'+__("Refresh this card")+'"></i> \
            <i class="edit-item ti ti-edit" title="'+__("Edit this card")+'"></i> \
            <i class="delete-item ti ti-x" title="'+__("Delete this card")+'"></i> \
         </span> \
         <div class="grid-stack-item-content"> \
         </div> \
      </div>';

        // add the widget to the grid
        var widget = this.grid.addWidget(html, {
            'x': x,
            'y': y,
            'w': width,
            'h': height,
            'autoPosition': x < 0 || y < 0,
            'id': gridstack_id,
        });

        // append options
        $(widget).attr('data-card-options', JSON.stringify(options));

        return $(widget);
    }

    setFilterFromForm(form) {
        const that = this;

        glpi_close_all_dialogs();
        var form_data  = {};

        $.each(form.serializeArray(), function() {
            form_data[this.name] = this.value;
        });

        // get the html of the new card and save dashboard
        $.get({
            url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
            data: {
                action:    'get_filter',
                filter_id: form_data.filter_id,
            }
        }).done(function(filter_html) {
            $(that.filters_selector).append(filter_html);
            that.saveFilter(form_data.filter_id, []);
        });
    }

    refreshDashboard() {
        const that = this;
        var gridstack = $(this.elem_id+" .grid-stack");
        this.grid.removeAll();

        let data = {
            dashboard: this.current_name,
            action: 'get_dashboard_items',
        };
        if (this.embed) {
            data.embed        = 1;
            data.token        = this.token;
            data.entities_id  = this.entities_id;
            data.is_recursive = this.is_recursive;
        }

        $.get({
            url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
            data: data
        }).done(function(html) {
            gridstack.prepend(html);
            gridstack.find('.grid-stack-item').each(function() {
                that.grid.makeWidget($(this)[0]);
            });
            that.getCardsAjax();
        });
    }

    setLastDashboard() {
        $.post({
            url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
            data: {
                dashboard: this.current_name,
                page: (location.origin+location.pathname)
                    .replace(CFG_GLPI.url_base, ''),
                action: 'set_last_dashboard',
            }
        });
    }

    saveFilter(filter_id, value) {
        // store current filter in localStorage
        var filters = this.getFiltersFromDB();
        filters[filter_id] = value;
        this.setFiltersInDB(filters);

        // refresh sortable
        sortable(this.filters_selector, 'reload');

        // refresh all card impacted by the changed filter
        this.refreshCardsImpactedByFilter(filter_id);
    }

    refreshCardsImpactedByFilter(filter_id) {
        const that = this;
        $('.dashboard .card.filter-'+filter_id).each(function () {
            var gridstack_item = $(this).closest(".grid-stack-item");
            var card_id = gridstack_item.attr('gs-id');
            that.getCardsAjax("[gs-id="+card_id+"]");
        });
    }

    saveDashboard(force_refresh) {
        const that = this;
        force_refresh = force_refresh | false;

        var serializedData = $.makeArray(
            this.element.find('.grid-stack-item:visible:not(.grid-stack-placeholder)')
        ) .map(function (v) {
            var gs_id = $(v).attr('gs-id');
            var options = $(v).data('card-options');

            // replace markdown content (this to avoid unwanted slashing)
            if (_.keys(that.markdown_contents).length > 0
             && gs_id in that.markdown_contents) {
                options.markdown_content = that.markdown_contents[gs_id];
            }

            return gs_id ? {
                gridstack_id: $(v).attr('gs-id'),
                card_id: options.card_id,
                x: $(v).attr('gs-x'),
                y: $(v).attr('gs-y'),
                width: $(v).attr('gs-w'),
                height: $(v).attr('gs-h'),
                card_options: options
            } : null;
        });

        $.post({
            url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
            data: {
                action: 'save_items',
                dashboard: this.current_name,
                items: serializedData,
                title: $(".dashboard-name").val()
            }
        }).done(function() {
            if (force_refresh) {
                that.refreshDashboard();
            }
        });
    }

    /**
    * FitText() only use the width of an item into consideration (and ignore the height).
    * This means that if you keep increasing the width of a card without also
    * increasing the height then your text will overflow the card's height at
    * some point.
    *
    * This function fix this by reducing the available width of the parent DOM
    * element to ensure a decent height / width ratio will be used by fitText()
    *
    * @param {*} items
    */
    computeWidth(items) {
        items.each(function() {
            // Compute parent dimension
            var parent_width = $(this).parent().parent().width();
            var parent_height = $(this).parent().parent().height();

            // Only for "wide" cards
            if (parent_width > parent_height) {
            // FitText "ideal" ratio to avoid any overflow
            // This value was found by using fitText() on a ~1600px wide span and
            // checking the resulting text height.
            // It probably wont be the perfect ratio for every possible texts
            // length but it is a safe ratio to use for our calculation
                var target_ratio = 0.35;

                // Compute what our desired height would be if we want to match the
                // target ratio
                var desired_width = parent_height / target_ratio;
                var desired_width_percent = (desired_width / parent_width) * 100;

                // Keep half the space since we have two items to display (value and label)
                var desired_width_percent_half = desired_width_percent / 2;

                // Apply the width
                $(this).css('width', desired_width_percent_half + '%');
            }
        });
    }

    /**
    * Remove the custom width as it should only be used temporarily to 'trick'
    * fitText into using a different fontSize and should not be applied to the
    * actual text
    *
    * @param {*} items
    */
    resetComputedWidth(items) {
        items.each(function() {
            $(this).css('width', '100%');
        });
    }

    fitNumbers(parent_item) {
        parent_item = parent_item || $('body');

        var text_offset = 1.16;

        // responsive mode
        if (this.dash_width <= 700
          || $(this.grid.el).hasClass('grid-stack-one-column-mode')) {
            text_offset = 1.8;
        }

        // Set temporary max width to trick fitText and avoid overflow
        this.computeWidth(parent_item.find('.big-number').find('.formatted-number'));
        this.computeWidth(parent_item.find('.big-number').find('.label'));

        parent_item
            .find('.big-number')
            .find('.formatted-number').fitText(text_offset);

        parent_item
            .find('.summary-numbers')
            .find('.formatted-number').fitText(text_offset-0.65);

        parent_item
            .find('.summary-numbers')
            .find('.line .label').fitText(text_offset-0.2);

        parent_item
            .find('.big-number')
            .find('.label').fitText(text_offset - 0.2, { minFontSize: '12px'});

        // Remove temporary width
        this.resetComputedWidth(parent_item.find('.big-number').find('.formatted-number'));
        this.resetComputedWidth(parent_item.find('.big-number').find('.label'));
    }

    animateNumbers(parent_item) {
        parent_item = parent_item || $('body');

        parent_item
            .find('.multiple-numbers, .summary-numbers, .big-number')
            .find('.formatted-number')
            .each(function () {
                var count        = $(this);
                var precision    = count.data('precision');
                var number       = count.children('.number');
                var targetNumber = number.text();

                // Some custom formats may contain text in the number field, no animation in this case
                if (isNaN(number.text())) {
                    return true;
                }

                jQuery({ Counter: 0 }).animate({ Counter: number.text() }, {
                    duration: 800,
                    easing: 'swing',
                    step: function () {
                        number.text(this.Counter.toFixed(precision));
                    },
                    complete: function () {
                        number.text(targetNumber);
                    }
                });
            });
    }

    setEditMode(activate) {
        this.edit_mode = typeof activate == "undefined" ? true : activate;

        var edit_ctrl = $(this.elem_id+" .toolbar .edit-dashboard");
        edit_ctrl.toggleClass('active', activate);
        this.element.toggleClass('edit-mode', activate);
        this.grid.setStatic(!activate);

        // set filters as sortable (draggable) or not
        if ($(this.filters_selector).children().length > 0) {
            sortable(this.filters_selector, activate ? 'enable' : 'disable');
        }

        if (!this.edit_mode) {
            // save markdown textareas set as dirty
            var dirty_textareas = $(".grid-stack-item.dirty");
            if (dirty_textareas.length > 0) {
                this.saveDashboard(true);
            }
        }
    }

    toggleFullscreenMode(fs_ctrl) {
        var fs_enabled = !fs_ctrl.hasClass('active');

        this.element.toggleClass('fullscreen')
            .find('.night-mode').toggle(fs_enabled);
        fs_ctrl.toggleClass('active');

        // desactivate edit mode
        if (fs_enabled) {
            this.setEditMode(false);
        }

        // fullscreen browser api
        if (fs_enabled) {
            GoInFullscreen(this.elem_dom);
        } else {
            GoOutFullscreen();
        }
    }

    disableFullscreenMode() {
        this.element
            .removeClass('fullscreen')
            .find('.night-mode').hide().end()
            .find('.toggle-fullscreen').removeClass('active');

        GoOutFullscreen();
    }

    /**
    * Clone current dashboard
    * (clean all previous gridstack_id in cards)
    */
    clone() {
        const that = this;

        $.post({
            url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
            data: {
                dashboard: this.current_name,
                action: 'clone_dashboard',
            },
            dataType: 'json'
        }).done(function(new_dash) {
            that.addNewDashbardInSelect(new_dash.title, new_dash.key);
        });
    }

    /**
    * Delete current dashboard
    */
    delete() {
        const that = this;
        var confirm_msg = __("Are you sure you want to delete the dashboard %s ?")
            .replace('%s', this.current_name);
        if (window.confirm(confirm_msg, __("Delete this dashboard"))) {
            $.post({
                url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
                data: {
                    action: 'delete_dashboard',
                    dashboard: this.current_name,
                }
            }).done(function() {
                $("#dashboard-"+that.rand+" .toolbar .dashboard_select")
                    .find("option[value='"+that.current_name+"']").remove()
                    .end() // reset find filtering
                    .prop("selectedIndex", 0)
                    .trigger('change');
            });
        }
    }

    /**
    * Display form to add a new dashboard
    */
    addForm() {
        glpi_ajax_dialog({
            title: __("Add a new dashboard"),
            url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
            params: {
                action: 'add_new',
            }
        });
    }

    addNew(form_data) {
        const that = this;

        $.post({
            url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
            data: {
                action: 'save_new_dashboard',
                title: form_data.title,
                context: this.context,
            }
        }).done(function(dashboard_key) {
            that.addNewDashbardInSelect(form_data.title, dashboard_key);
            that.setEditMode(true);
        });
    }

    /**
    * Add a new option to top left dashboard select
    */
    addNewDashbardInSelect(label, value) {
        var newOption = new Option(label, value, false, true);
        $("#dashboard-"+this.rand+" .toolbar .dashboard_select")
            .append(newOption)
            .trigger('change');
    }

    getCardsAjax(specific_one) {
        const that = this;

        specific_one = specific_one || "";

        const filters = this.getFiltersFromDB();
        const force = (specific_one.length > 0 ? 1 : 0);

        let requested_cards = [];
        let card_ajax_data = [];
        $(this.elem_dom).find(".grid-stack-item:not(.lock-bottom)"+specific_one).each(function() {
            var card         = $(this);
            var card_opt     = card.data('card-options');
            var gridstack_id = card.attr('gs-id');
            var card_id      = card_opt.card_id || card.attr('gs-id');

            card_opt.gridstack_id = gridstack_id;

            // store markdown after card reload
            if ("markdown_content" in card_opt) {
                that.markdown_contents[gridstack_id] = card_opt.markdown_content;
            }

            // append filters
            card_opt.apply_filters = filters;

            card_ajax_data.push({
                'card_id': card_id,
                'force': force,
                'args': card_opt,
                'c_cache_key': card_opt.cache_key || ""
            });
            requested_cards.push({
                'card_el': card,
                'card_id': card_id,
                'args': card_opt,
            });
        });

        if (this.ajax_cards) {
            // Multi ajax mode, spawn a request for each card
            const promises = [];
            requested_cards.forEach(function(requested_card) {
                const card = requested_card.card_el;

                let data = {
                    'action':      'get_card',
                    'dashboard':   that.current_name,
                    'card_id':     requested_card.card_id,
                    'force':       force,
                    'args':        requested_card.args,
                    'd_cache_key': that.cache_key,
                    'c_cache_key': requested_card.args.cache_key || ""
                };
                if (that.embed) {
                    data.embed        = 1;
                    data.token        = that.token;
                    data.entities_id  = that.entities_id;
                    data.is_recursive = that.is_recursive;
                }

                promises.push($.get(CFG_GLPI.root_doc+"/ajax/dashboard.php", data).then(function(html) {
                    card.children('.grid-stack-item-content').html(html);

                    that.fitNumbers(card);
                    that.animateNumbers(card);
                }).fail(function() {
                    card.html("<div class='empty-card card-error'><i class='fas fa-exclamation-triangle'></i></div>");
                }));
            });

            return promises;
        } else {
            // Single ajax mode, spawn a single request
            let data = {
                'dashboard': this.current_name,
                'force': (specific_one.length > 0 ? 1 : 0),
                'd_cache_key': this.cache_key,
                'cards': card_ajax_data
            };
            if (this.embed) {
                data.embed        = 1;
                data.token        = this.token;
                data.entities_id  = this.entities_id;
                data.is_recursive = this.is_recursive;
            }

            return $.ajax({
                url:CFG_GLPI.root_doc+"/ajax/dashboard.php",
                method: 'POST',
                data: {
                    'action': 'get_cards',
                    data: JSON.stringify(data)
                }
            }).then(function(results) {
                $.each(requested_cards, (i2, crd) => {
                    let has_result = false;
                    const card = crd.card_el;
                    $.each(results, (card_id, card_result) => {
                        if (crd.card_id === card_id) {
                            const html = card_result;
                            has_result = true;
                            card.children('.grid-stack-item-content').html(html);

                            that.fitNumbers(card);
                            that.animateNumbers(card);
                        }
                    });
                    if (!has_result) {
                        card.html("<div class='empty-card card-error'><i class='fas fa-exclamation-triangle'></i></div>");
                    }
                });
            }).fail(function() {
                $.each(requested_cards, (i2, crd) => {
                    const card = crd.card_el;
                    card.html("<div class='empty-card card-error'><i class='fas fa-exclamation-triangle'></i></div>");
                });
            });
        }
    }

    easter() {
        var items = $(this.elem_id+" .grid-stack .grid-stack-item .card");

        setInterval(function() {
            var color = "#"+((1<<24)*Math.random()|0).toString(16);
            var no_item = Math.floor(Math.random() * items.length) + 1;
            var item = items[no_item];
            $(item).css('background-color', color);
        }, 10);
    }

    generateCss() {
        var dash_width    = Math.floor(this.element.width());
        var cell_length   = (dash_width - 1) / this.cols;
        var cell_height   = cell_length;
        var cell_fullsize = (dash_width / this.cols);
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

            style+= this.elem_id+" .grid-stack > .grid-stack-item[gs-x='"+i+"'] { \
            left: "+left+"%; \
         } \
         "+this.elem_id+" .grid-stack > .grid-stack-item[gs-w='"+(i+1)+"'] { \
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

    /**
    * init filters of the dashboard
    */
    initFilters() {
        const that = this;

        if ($(this.filters_selector).length === 0) {
            return;
        }

        var filters = this.getFiltersFromDB();

        // replace empty array by empty string to avoid jquery remove the corresponding key
        // when sending ajax query
        $.each(filters, function( index, value ) {
            if (Array.isArray(value) && value.length == 0) {
                filters[index] = "";
            }
        });

        // get html of provided filters
        $.get({
            url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
            data: {
                "action": "get_dashboard_filters",
                "filters": filters,
            }
        }).done(function(html) {
            $(that.filters_selector).html(html);
            // we must  emit an event to all filters to say them dashboard is ready
            $(document).trigger("glpiDasbhoardInitFilter");

            // start sortable on filter but disable it by default,
            // we will enable it when edit mode will be toggled on
            sortable(that.filters_selector, {
                placeholderClass: 'filter-placeholder',
                orientation: 'horizontal',
            })[0].addEventListener('sortupdate', function(e) {
            // after drag, save the order of filters in storage
                var items_after = $(e.detail.destination.items).filter(that.filters_selector);
                var filters     = that.getFiltersFromDB();
                var new_filters = {};
                $.each(items_after, function() {
                    var filter_id = $(this).data('filter-id');
                    new_filters[filter_id] = filters[filter_id];
                });

                that.setFiltersInDB(new_filters);
            });
            sortable(that.filters_selector, 'disable');
        });
    }

    /**
    * Return saved filter from server side database
    */
    getFiltersFromDB() {
        var filters;
        $.ajax({
            method: 'GET',
            url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
            async: false,
            data: {
                action:    'get_filter_data',
                dashboard: this.current_name,
            }
        }).done(function(response) {
            try {
                filters = JSON.parse(response);
            } catch (e) {
                filters = JSON.parse('{}');
            }
        });
        return filters || {};
    }

    /**
    * Save an object of filters for the current dashboard into serverside database
    *
    * @param {Object} sub_filters
    */
    setFiltersInDB(sub_filters) {
        var filters = [];
        if (this.current_name.length > 0) {
            filters[this.current_name] = sub_filters;
        }
        $.ajax({
            method: 'POST',
            url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
            data: {
                action:    'save_filter_data',
                dashboard: this.current_name,
                filters:   JSON.stringify(filters[this.current_name], function(k, v) {
                    return v === undefined ? null : v;
                }),
            }
        });
    }
}
