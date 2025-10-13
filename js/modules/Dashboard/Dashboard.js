/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

/* eslint prefer-template: 0 */
/* global GridStack, GoInFullscreen, GoOutFullscreen, EasyMDE, getUuidV4, _, sortable */
/* global glpi_ajax_dialog, glpi_close_all_dialogs */

window.GLPI = window.GLPI || {};
window.GLPI.Dashboard = {
    /**
     * @var {Object<string, GLPIDashboard>} dashboards
     */
    dashboards: {},

    /**
     * @return {GLPIDashboard}
     */
    getActiveDashboard: function () {
        let current_dashboard_index = "";

        $.each(this.dashboards, (index, dashboard) => {
            if ($(dashboard.elem_dom).is(':visible')) {
                current_dashboard_index = index;
                return false; // Break
            }
        });

        return this.dashboards[current_dashboard_index];
    }
};

/**
 * @typedef GLPIDashboardParams
 * @property {number} [cols] Number of columns
 * @property {number} [rows] Number of rows
 * @property {number} [cell_length] Length of a cell
 * @property {number} [cell_margin] Margin of a cell
 * @property {string} [rand] Random string to identify the dashboard instance
 * @property {boolean} [embed] Embed mode
 * @property {string|null} [token] Token
 * @property {number|null} [entities_id] Entities ID
 * @property {number|boolean|null} [is_recursive] Recursive
 * @property {{}[]} [all_cards] All cards
 * @property {string} [context] Dashboard context
 * @property {string} [current] Current dashboard
 */

class GLPIDashboard {
    /**
     * @param {GLPIDashboardParams|undefined} params
     */
    constructor(params) {
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
        this.filter_mode = false;
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

        GridStack.renderCB = (el, w) => {
            el.parentElement.innerHTML = w.content;
        };

        // get passed options and merge it with default ones
        let options = (typeof params !== 'undefined') ? params: {};
        /** @type {GLPIDashboardParams} */
        const default_options = {
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

        this.rand         = parseInt(options.rand);
        this.elem_id      = "#dashboard-" + this.rand;
        this.element      = $(this.elem_id);
        this.elem_dom     = this.element[0];
        this.current_name = $(`${this.elem_id} .dashboard-select`).val() || options.current;
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
        const elem_domRect = this.elem_dom.getBoundingClientRect();
        const width_offset = elem_domRect.left + (window.innerWidth - elem_domRect.right) + 0.02;

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
        }, `#grid-stack-${options.rand}`);

        // set grid in static to prevent edition (unless user click on edit button)
        // previously in option, but current version of gridstack has a bug with one column mode (responsive)
        // see https://github.com/gridstack/gridstack.js/issues/1229
        this.grid.setStatic(true);

        // set the width of the select box to match the selected option
        this.resizeSelect();

        // init filters from storage
        this.initFilters();
        this.refreshDashboard();

        // animate the dashboards once all card are loaded (single ajax mode)
        if (!this.ajax_cards) {
            this.fitNumbers();
            this.animateNumbers();
        }

        // change dashboard
        $(`${this.elem_id} .toolbar .dashboard_select`).change((e) => {
            const dropdown = $(e.currentTarget);
            this.current_name = dropdown.val();
            const selected_label = dropdown.find('option:selected').text();
            $(".dashboard-name").val(selected_label);
            this.refreshDashboard();
            this.setLastDashboard();
            this.initFilters();
        });

        // add dashboard
        $(`${this.elem_id} .toolbar .add-dashboard`).click(() => {
            this.addForm();
        });
        $(document).on('submit', '.display-add-dashboard-form', (e) => {
            e.preventDefault();

            glpi_close_all_dialogs();
            const button    = $(e.currentTarget);
            const form_data = {};
            $.each(button.closest('.display-add-dashboard-form').serializeArray(), function() {
                form_data[this.name] = this.value;
            });

            this.addNew(form_data);
        });

        // delete dashboard
        $(`${this.elem_id} .toolbar .delete-dashboard`).click(() => {
            this.delete();
        });

        //clone dashboard
        $(`${this.elem_id} .toolbar .clone-dashboard`).click(() => {
            this.clone();
        });

        // embed mode toggle
        $(`${this.elem_id} .toolbar .open-embed`).click(() => {
            glpi_ajax_dialog({
                title: __("Share or embed this dashboard"),
                url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
                params: {
                    action:  'display_embed_form',
                    dashboard: this.current_name
                },
            });
        });

        // edit mode toggle
        $(`${this.elem_id} .toolbar .edit-dashboard`).click((e) => {
            const activate = !$(e.currentTarget).hasClass('active');

            this.setEditMode(activate);
            this.setFilterMode(activate);
        });

        // filter mode toggle
        $(`${this.elem_id} .toolbar .filter-dashboard`).on('click', (e) => {
            const activate = !$(e.currentTarget).hasClass('active');

            this.setFilterMode(activate);
        });

        // fullscreen mode toggle
        $(`${this.elem_id} .toggle-fullscreen`).click((e) => {
            this.toggleFullscreenMode($(e.currentTarget));
        });
        // trigger fullscreen off (by esc key)
        $(document).on('fullscreenchange webkitfullscreenchange mozfullscreenchange MSFullscreenChange', () => {
            if (!document.webkitIsFullScreen
                && !document.mozFullScreen
                && !document.msFullscreenElement) {
                this.removeFullscreenModeClass();
            }
        });

        // night mode toggle
        $(`${this.elem_id} .toolbar .night-mode`).click((e) => {
            $(e.currentTarget).toggleClass('active');
            this.element.toggleClass('theme-dark');
        });

        // refresh mode toggle
        $(`${this.elem_id} .toolbar .auto-refresh`).click((e) => {
            const target = $(e.currentTarget);
            target.toggleClass('active');
            const active = target.hasClass('active');

            if (active) {
                let minutes = parseInt(CFG_GLPI.refresh_views);
                if (minutes === 0 || Number.isNaN(minutes)) {
                    minutes = 30;
                }
                const seconds = minutes * 60;
                this.interval = setInterval(() => {
                    this.refreshDashboard();
                }, seconds * 1000);
            } else {
                clearInterval(this.interval);
            }
        });

        // browser resized (use debounce to delay generation of css)
        let debounce;
        $(window).on('resize', (event) => {
            if (event.target.constructor.name !== "Window") {
                return;
            }

            window.clearTimeout(debounce);
            debounce = window.setTimeout(() => {
                // fit again numbers
                this.fitNumbers();
            }, 200);
        });

        // publish rights
        $(document).on('click', '.display-rights-form .save_rights', (e) => {
            glpi_close_all_dialogs();

            const button    = $(e.target);
            const form_data = {};
            $.each(button.closest('.display-rights-form').serializeArray(), (i, v) => {
                const current_val = v.value.split('-');
                if (current_val.length !== 2) {
                    return;
                }
                const right_name  = current_val[0];
                const value       = current_val[1];
                if (!(right_name in form_data)) {
                    form_data[right_name] = [];
                }
                form_data[right_name].push(value);
            });
            const is_private = button.closest('.display-rights-form').find('select[name="is_private"]').val();

            $.post({
                url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
                data: {
                    action:     'save_rights',
                    dashboard:  this.current_name,
                    rights:     form_data,
                    is_private: is_private,
                }
            });
        });

        // event: moving item
        this.grid.on('dragstop', () => {
            this.saveDashboard();
        });

        // event: resize item
        this.grid.on('resizestop', (event, elem) => {
            this.saveDashboard();

            // Used after "resize.fittext" event to reset our custom width "trick"
            // See computeWidth() function for more info on the trick
            this.resetComputedWidth($('body').find('.big-number').find('.formatted-number'));
            this.resetComputedWidth($('body').find('.big-number').find('.label'));

            // animate the number
            this.fitNumbers($(elem));
            this.animateNumbers($(elem));
        });


        $(this.elem_id).on('click', '.delete-item', (e) => {
            // delete item
            const item = $(e.target).closest('.grid-stack-item').get(0);
            this.grid.removeWidget(item);
            this.saveDashboard();
        }).on('click', '.refresh-item', (e) => {
            // refresh item
            const refresh_ctrl = $(e.target);
            const item = refresh_ctrl.closest('.grid-stack-item');
            const id = item.attr('gs-id');

            this.getCardsAjax(`[gs-id="${CSS.escape(id)}"]`);
        }).on('click', '.edit-item', (e) => {
            // edit item
            const edit_ctrl = $(e.target);
            const item      = edit_ctrl.parent().parent('.grid-stack-item');
            const card_opt  = item.data('card-options');

            glpi_ajax_dialog({
                title: __("Edit this card"),
                url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
                params: {
                    action:       'display_edit_widget',
                    dashboard:    this.current_name,
                    gridstack_id: item.attr('gs-id'),
                    card_id:      card_opt.card_id,
                    x:            item.attr('gs-x') ?? 0,
                    y:            item.attr('gs-y') ?? 0,
                    width:        item.attr('gs-w') ?? 1,
                    height:       item.attr('gs-h') ?? 1,
                    card_options: card_opt,
                },
                modalclass: 'modal-lg',
            });
        }).on("click", '.cell-add', (e) => {
            // add new widget form
            const add_ctrl = $(e.target);

            glpi_ajax_dialog({
                title: __("Add a card"),
                url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
                params: {
                    action: 'display_add_widget',
                    dashboard: this.current_name,
                    x: add_ctrl.data('x'),
                    y: add_ctrl.data('y')
                },
                modalclass: 'modal-lg',
            });
        }).on("click", '.filters_toolbar .add-filter', () => {
            // add new filter
            glpi_close_all_dialogs();

            const filters = this.getFiltersFromDB();
            const filter_names    = Object.keys(filters);

            glpi_ajax_dialog({
                title: __("Add a filter"),
                url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
                params: {
                    action: 'display_add_filter',
                    dashboard: this.current_name,
                    used: filter_names
                },
            });
        }).on("click", '.filters_toolbar .delete-filter', (e) => {
            // delete existing filter
            const filter = $(e.target).closest('.filter');
            const filter_id = filter.data('filter-id');

            // remove filter from dom
            filter.remove();

            // remove filter from storage and refresh cards
            const filters = this.getFiltersFromDB();
            delete filters[filter_id];
            this.setFiltersInDB(filters);
            this.refreshCardsImpactedByFilter(filter_id);
        });

        // save new or existing widget (submit form)
        $(document).on('submit', '.display-widget-form ', (event) => {
            event.preventDefault();

            this.setWidgetFromForm($(event.target));
        });

        // save new filter (submit form)
        $(document).on('submit', '.display-filter-form ', (event) => {
            event.preventDefault();

            const form = $(event.target);

            this.setFilterFromForm(form);
        });

        // rename dashboard
        $(document).on('click', '.save-dashboard-name ', (event) => {
            event.preventDefault();
            // change in selector
            $('.dashboard_select option[value="' + CSS.escape(this.current_name) + '"]')
                .text($(".dashboard-name").val());
            this.saveDashboard();

            $('.display-message')
                .addClass('success')
                .text(_.unescape(__("Saved")))
                .show('fade').delay(2000).hide('fade');
        });

        // display widget types after selecting a card
        $(document).on('select2:select', '.display-widget-form select[name=card_id]', (event) => {
            const select2_data      = event.params.data;
            const selected          = select2_data.id;
            const widgettype_field  = $(event.target).closest('.display-widget-form').find('.widgettype_field');
            const available_widgets = this.all_cards[selected].widgettype;
            const force_checked     = available_widgets.length === 1;

            widgettype_field
                .show()
                .find('input[type=radio]')
                .next('label').css('display', 'none').end()
                .filter(available_widgets.map((value) => `[value="${CSS.escape(value)}"]`).join(','))
                .prop("checked", force_checked)
                .trigger('change')
                .next('label').css('display', 'inline-block');
        });

        // display gradient and limit after selecting a widget
        $(document).on('change', '.display-widget-form [name=widgettype]', (e) => {
            const widgetdom   = $(e.target);
            const widgettype  = widgetdom.val();
            const widget      = this.all_widgets[widgettype];
            const usegradient = widget.gradient || false;
            const pointlabels = widget.pointlbl || false;
            const uselimit    = widget.limit || false;
            const width       = widget.width  || 2;
            const height      = widget.height || 2;

            const form = widgetdom.closest('.display-widget-form');
            form.find('.gradient_field').toggle(usegradient);
            form.find('.pointlbl_field').toggle(pointlabels);
            form.find('.limit_field').toggle(uselimit);

            const width_field = form.find('[name="width"]');
            const height_field = form.find('[name="height"]');
            if (width_field.val() == 0) {
                width_field.val(width);
            }
            if (height_field.val() == 0) {
                height_field.val(height);
            }
        });

        // markdown textarea edited
        $(document).on('input', '.card.markdown textarea.markdown_content', (e) => {
            this.saveMarkdown($(e.target));
        });

        // FitText() add an event listener that recompute the font size of all
        // "fittexted" elements of the page.
        // This means we need to apply our max-width "trick" on this event
        // See computeWidth() function for more info on the trick
        $(window).on('resize.fittext', () => {
            this.computeWidth($('body').find('.big-number').find('.formatted-number'));
            this.computeWidth($('body').find('.big-number').find('.label'));
        });

        // Keep track of instance
        window.GLPI.Dashboard.dashboards[this.rand] = this;
    }

    /**
     * Save the textarea content to the markdown_contents array and mark the grid-stack-item as dirty (needing saved)
     * @param {jQuery} textarea jQuery textarea element
     */
    saveMarkdown(textarea) {
        const item = textarea.closest('.grid-stack-item');
        const content = textarea.val();
        const gs_id = item.attr('gs-id');

        item.addClass('dirty');
        this.markdown_contents[gs_id] = content;
    }

    /**
     * @param {jQuery} form
     * @return {boolean}
     */
    setWidgetFromForm(form) {
        glpi_close_all_dialogs();
        const form_data  = {};

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

        const edit_item = "old_id" in form_data && form_data.old_id.length > 0;

        // prepare options
        form_data.card_options.color        = form_data.color || null;
        form_data.card_options.widgettype   = form_data.widgettype || null;
        form_data.card_options.palette      = form_data.palette || null;
        form_data.card_options.use_gradient = form_data.use_gradient || 0;
        form_data.card_options.point_labels = form_data.point_labels || 0;
        form_data.card_options.legend       = form_data.legend || 0;
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
            const item = $('.grid-stack-item[gs-id="' + CSS.escape(form_data.old_id) + '"]')[0];
            this.grid.removeWidget(item);
        }

        // complete ajax data
        const uuid = getUuidV4();
        form_data.gridstack_id = form_data.card_id+"_"+uuid;
        form_data.card_options.card_id = form_data.card_id;
        form_data.card_options.gridstack_id = form_data.gridstack_id;

        const args = form_data.card_options;
        args.force = true;
        args.apply_filters = this.getFiltersFromDB();

        // add the new widget
        const widget = this.addWidget(form_data);

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
        }).then((card_html) => {
            widget
                .children('.grid-stack-item-content')
                .append(card_html);
            this.fitNumbers(widget);
            this.animateNumbers(widget);
            this.saveDashboard();
        });
    }

    addWidget(p) {
        const gridstack_id = p.gridstack_id;
        const x            = parseInt(p.x || -1);
        const y            = parseInt(p.y || -1);
        const width        = parseInt(p.width || 2);
        const height       = parseInt(p.height || 2);
        const options      = p.card_options || {};

        const html = `
            <span class="controls">
                <i class="refresh-item ti ti-refresh" title="${__("Refresh this card")}"></i>
                <i class="edit-item ti ti-edit" title="${__("Edit this card")}"></i>
                <i class="delete-item ti ti-x" title="${__("Delete this card")}"></i>
            </span>
            <div class="grid-stack-item-content"></div>`;

        // add the widget to the grid
        const widget = this.grid.addWidget({
            'x': x,
            'y': y,
            'w': width,
            'h': height,
            'autoPosition': x < 0 || y < 0,
            'id': gridstack_id,
            'content': html
        });

        // append options
        $(widget).attr('data-card-options', JSON.stringify(options));

        return $(widget);
    }

    setFilterFromForm(form) {
        glpi_close_all_dialogs();
        const form_data  = {};

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
        }).then((filter_html) => {
            $(this.filters_selector).append(filter_html);
            this.saveFilter(form_data.filter_id, []);
        });
    }

    refreshDashboard() {
        const gridstack = $(this.elem_id+" .grid-stack");
        this.grid.removeAll();

        const data = {
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
        }).then((html) => {
            gridstack.prepend(html);
            gridstack.find('.grid-stack-item').each((i, elem) => {
                this.grid.makeWidget(elem);
            });
            this.getCardsAjax();

            const is_placeholder = CFG_GLPI['is_demo_dashboards'] === "1";
            if (is_placeholder) {
                // Hide filters toolbar and show the placeholder info
                $(this.elem_id).find('.filters_toolbar').addClass('d-none');
                $(this.elem_id).find('.placeholder_info').removeClass('d-none');
            } else {
                // Hide the placeholder info and show filters toolbar
                $(this.elem_id).find('.placeholder_info').addClass('d-none');
                $(this.elem_id).find('.filters_toolbar').removeClass('d-none');
            }
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
        const filters = this.getFiltersFromDB();
        filters[filter_id] = value;
        this.setFiltersInDB(filters);

        // refresh sortable
        sortable(this.filters_selector, 'reload');

        // refresh all card impacted by the changed filter
        this.refreshCardsImpactedByFilter(filter_id);
    }

    refreshCardsImpactedByFilter(filter_id) {
        $('.dashboard .card.filter-'+filter_id).each((i, elem) => {
            const gridstack_item = $(elem).closest(".grid-stack-item");
            const card_id = gridstack_item.attr('gs-id');
            this.getCardsAjax(`[gs-id="${CSS.escape(card_id)}"]`);
        });
    }

    saveDashboard(force_refresh) {
        force_refresh = force_refresh | false;

        const serializedData = $.makeArray(
            this.element.find('.grid-stack-item:visible:not(.grid-stack-placeholder)')
        ) .map((v) => {
            const gs_id = $(v).attr('gs-id');
            const options = $(v).data('card-options');

            // replace markdown content (this to avoid unwanted slashing)
            if (_.keys(this.markdown_contents).length > 0
                && gs_id in this.markdown_contents) {
                options.markdown_content = this.markdown_contents[gs_id];
            }

            return gs_id ? {
                gridstack_id: $(v).attr('gs-id'),
                card_id: options.card_id,
                x: $(v).attr('gs-x') ?? 0,
                y: $(v).attr('gs-y') ?? 0,
                width: $(v).attr('gs-w'),
                height: $(v).attr('gs-h') ?? 1,
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
        }).then(() => {
            if (force_refresh) {
                this.refreshDashboard();
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
            const parent_width = $(this).parent().parent().width();
            const parent_height = $(this).parent().parent().height();

            // Only for "wide" cards
            if (parent_width > parent_height) {
                // FitText "ideal" ratio to avoid any overflow
                // This value was found by using fitText() on a ~1600px wide span and
                // checking the resulting text height.
                // It probably wont be the perfect ratio for every possible texts
                // length but it is a safe ratio to use for our calculation
                const target_ratio = 0.35;

                // Compute what our desired height would be if we want to match the
                // target ratio
                const desired_width = parent_height / target_ratio;
                const desired_width_percent = (desired_width / parent_width) * 100;

                // Keep half the space since we have two items to display (value and label)
                const desired_width_percent_half = desired_width_percent / 2;

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

        let text_offset = 1.16;

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
                const count        = $(this);
                const precision    = count.data('precision');
                const number       = count.children('.number');
                const targetNumber = number.text();

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

        const edit_ctrl = $(this.elem_id+" .toolbar .edit-dashboard");
        edit_ctrl.toggleClass('active', activate);
        this.element.toggleClass('edit-mode', activate);
        this.grid.setStatic(!activate);

        // set filters as sortable (draggable) or not
        if ($(this.filters_selector).children().length > 0) {
            sortable(this.filters_selector, activate ? 'enable' : 'disable');
        }

        if (!this.edit_mode) {
            // save markdown textareas set as dirty
            const dirty_textareas = $(".grid-stack-item.dirty");
            if (dirty_textareas.length > 0) {
                this.saveDashboard(true);
            }
        }
    }

    setFilterMode(activate) {
        this.filter_mode = typeof activate == "undefined" ? true : activate;

        const edit_ctrl = $(this.elem_id+" .toolbar .filter-dashboard");
        edit_ctrl.toggleClass('active', activate);
        this.element.toggleClass('filter-mode', activate);

        // set filters as sortable (draggable) or not
        sortable('.filters', activate ? 'enable' : 'disable');
    }

    toggleFullscreenMode(fs_ctrl) {
        const fs_enabled = !fs_ctrl.hasClass('active');

        this.element.toggleClass('fullscreen');
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

    removeFullscreenModeClass() {
        this.element
            .removeClass('fullscreen')
            .find('.toggle-fullscreen').removeClass('active');
    }

    /**
     * Clone current dashboard
     * (clean all previous gridstack_id in cards)
     */
    clone() {
        $.post({
            url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
            data: {
                dashboard: this.current_name,
                action: 'clone_dashboard',
            },
            dataType: 'json'
        }).then((new_dash) => {
            this.addNewDashbardInSelect(new_dash.title, new_dash.key);
        });
    }

    /**
     * Delete current dashboard
     */
    delete() {
        const confirm_msg = __("Are you sure you want to delete the dashboard %s?")
            .replace('%s', this.current_name);
        if (window.confirm(confirm_msg, __("Delete this dashboard"))) {
            $.post({
                url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
                data: {
                    action: 'delete_dashboard',
                    dashboard: this.current_name,
                }
            }).then(() => {
                $(this.elem_id + " .toolbar .dashboard_select")
                    .find(`option[value="${CSS.escape(this.current_name)}"]`).remove()
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
            title: __("Add a dashboard"),
            url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
            params: {
                action: 'add_new',
            }
        });
    }

    addNew(form_data) {
        $.post({
            url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
            data: {
                action: 'save_new_dashboard',
                title: form_data.title,
                context: this.context,
            }
        }).then((dashboard_key) => {
            this.addNewDashbardInSelect(form_data.title, dashboard_key);
            this.setEditMode(true);
        });
    }

    /**
     * Add a option to top left dashboard select
     */
    addNewDashbardInSelect(label, value) {
        const newOption = new Option(label, value, false, true);
        $(this.elem_id + " .toolbar .dashboard_select")
            .append(newOption)
            .trigger('change');
    }

    getCardsAjax(specific_one) {
        specific_one = specific_one || "";

        const filters = this.getFiltersFromDB();
        const force = (specific_one.length > 0 ? 1 : 0);

        const requested_cards = [];
        const card_ajax_data = [];
        $(this.elem_dom).find(".grid-stack-item:not(.lock-bottom)"+specific_one).each((i, card) => {
            card         = $(card);
            const card_opt     = card.data('card-options');
            const gridstack_id = card.attr('gs-id');
            const card_id      = card_opt.card_id || card.attr('gs-id');

            card_opt.gridstack_id = gridstack_id;

            // store markdown after card reload
            if ("markdown_content" in card_opt) {
                this.markdown_contents[gridstack_id] = card_opt.markdown_content;
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
            requested_cards.forEach((requested_card) => {
                const card = requested_card.card_el;

                const data = {
                    'action':      'get_card',
                    'dashboard':   this.current_name,
                    'card_id':     requested_card.card_id,
                    'force':       force,
                    'args':        requested_card.args,
                    'd_cache_key': this.cache_key,
                    'c_cache_key': requested_card.args.cache_key || ""
                };
                if (this.embed) {
                    data.embed        = 1;
                    data.token        = this.token;
                    data.entities_id  = this.entities_id;
                    data.is_recursive = this.is_recursive;
                }

                promises.push($.get(CFG_GLPI.root_doc+"/ajax/dashboard.php", data).then((html) => {
                    card.children('.grid-stack-item-content').html(html);

                    this.fitNumbers(card);
                    this.animateNumbers(card);
                }, () => {
                    card.html("<div class='empty-card card-error'><i class='ti ti-alert-triangle'></i></div>");
                }));
            });

            return promises;
        } else {
            // Single ajax mode, spawn a single request
            const data = {
                'dashboard': this.current_name,
                'force': (specific_one.length > 0 ? 1 : 0),
                'd_cache_key': this.cache_key,
                'cards': card_ajax_data,
                'action': 'get_cards'
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
                data: data
            }).then((results) => {
                $.each(requested_cards, (i2, crd) => {
                    let has_result = false;
                    const card = crd.card_el;
                    $.each(results, (card_id, card_result) => {
                        if (crd.card_id === card_id) {
                            const html = card_result;
                            has_result = true;
                            card.children('.grid-stack-item-content').html(html);

                            this.fitNumbers(card);
                            this.animateNumbers(card);
                        }
                    });
                    if (!has_result) {
                        card.html("<div class='empty-card card-error'><i class='ti ti-alert-triangle'></i></div>");
                    }
                });
            }, () => {
                $.each(requested_cards, (i2, crd) => {
                    const card = crd.card_el;
                    card.html("<div class='empty-card card-error'><i class='ti ti-alert-triangle'></i></div>");
                });
            });
        }
    }

    easter() {
        const items = $(this.elem_id+" .grid-stack .grid-stack-item .card");

        setInterval(() => {
            const color = "#"+((1<<24)*Math.random()|0).toString(16);
            const no_item = Math.floor(Math.random() * items.length);
            const item = items[no_item];
            $(item).css('background-color', color);
        }, 10);
    }


    /**
     * init filters of the dashboard
     */
    initFilters() {
        if ($(this.filters_selector).length === 0) {
            return;
        }

        const filters = this.getFiltersFromDB();

        // replace empty array by empty string to avoid jquery remove the corresponding key
        // when sending ajax query
        $.each(filters, ( index, value ) => {
            if (Array.isArray(value) && value.length === 0) {
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
        }).then((html) => {
            $(this.filters_selector).html(html);
            // we must  emit an event to all filters to say them dashboard is ready
            $(document).trigger("glpiDasbhoardInitFilter");

            // start sortable on filter but disable it by default,
            // we will enable it when edit mode will be toggled on
            sortable(this.filters_selector, {
                placeholderClass: 'filter-placeholder',
                orientation: 'horizontal',
            })[0].addEventListener('sortupdate', (e) => {
                // after drag, save the order of filters in storage
                const items_after = $(e.detail.destination.items).filter(this.filters_selector);
                const filters     = this.getFiltersFromDB();
                const new_filters = {};
                $.each(items_after, (ia) => {
                    const filter_id = $(ia).data('filter-id');
                    new_filters[filter_id] = filters[filter_id];
                });

                this.setFiltersInDB(new_filters);
            });
            sortable(this.filters_selector, 'disable');
        });
    }

    /**
     * Return saved filter from server side database
     */
    getFiltersFromDB() {
        if (this.embed) {
            // Embed dashboards are displayed inside an anonymous context,
            // there is actually no stored filter data to fetch.
            return [];
        }

        let filters;
        $.ajax({
            method: 'GET',
            url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
            async: false,
            data: {
                action:    'get_filter_data',
                dashboard: this.current_name,
            },
            success: function(response) {
                filters = response;
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
        const filters = [];
        if (this.current_name.length > 0) {
            filters[this.current_name] = sub_filters;
        }
        $.ajax({
            method: 'POST',
            url: CFG_GLPI.root_doc+"/ajax/dashboard.php",
            data: {
                action:    'save_filter_data',
                dashboard: this.current_name,
                filters:   JSON.stringify(filters[this.current_name], (k, v) => {
                    return v === undefined ? null : v;
                }),
            }
        });
    }

    /**
     * Set the width of the select box to match the selected option
     */
    resizeSelect() {
        const select = document.querySelector(`${this.elem_id} .dashboard_select`);

        // mini dashboard doesn't have any filter/select
        if (select === null) {
            return;
        }

        select.addEventListener('change', (event) => {
            const tempSelect = document.createElement('select');
            const tempOption = document.createElement('option');
            tempOption.textContent = event.target.options[event.target.selectedIndex].text;
            tempSelect.style.cssText += `
              visibility: hidden;
              position: fixed;
           `;
            tempSelect.appendChild(tempOption);
            event.target.after(tempSelect);

            const tempSelectWidth = tempSelect.getBoundingClientRect().width;
            event.target.style.width = `${tempSelectWidth}px`;
            tempSelect.remove();
        });

        select.dispatchEvent(new Event('change'));
    }
}
window.GLPI.Dashboard.GLPIDashboard = GLPIDashboard;
// Legacy reference
window.GLPIDashboard = GLPIDashboard;
export default GLPIDashboard;
export {GLPIDashboard};
