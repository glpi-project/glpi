<script setup>
    /* global sortable */
    /* global glpi_toast_error, glpi_confirm */
    /* global _ */

    import { Rights } from "./Rights.js";
    import Column from "./Column.vue";
    import {computed, nextTick, onMounted, ref, watch} from "vue";
    import SearchInput from "./SearchInput.js";
    import {TeamBadgeProvider} from "./TeamBadgeProvider.js";

    const props = defineProps({
        /** @type {Rights} */
        rights: {
            type: Object,
            required: true,
        },
        element_id: {
            type: String,
            default: 'kanban'
        },
        /** @type {Object.<string, {name: string, allow_create: boolean}>} */
        supported_itemtypes: {
            type: Object,
            required: true
        },
        max_team_images: {
            type: Number,
            default: 3
        },
        column_field: {
            type: Object,
            required: true
        },
        bg_refresh_interval: {
            type: Number,
            default: 0
        },
        /** @type {itemtype: string, items_id: number} */
        item: {
            type: Object,
            required: true,
            validator(value) {
                return typeof value.itemtype === 'string' && typeof value.items_id === 'number';
            }
        },
        supported_filters: {
            type: Object,
            required: true,
            validator(value) {
                // each entry must be an object with a 'description' and 'supported_prefixes' property
                return Object.values(value).every(filter => typeof filter.description === 'string' && Array.isArray(filter.supported_prefixes));
            }
        },
        display_initials: {
            type: Boolean,
            default: true
        },
    });

    const emit = defineEmits([
        'kanban:pre_init', 'kanban:post_init', 'kanban:refresh_sortables', 'kanban:card_move', 'kanban:card_delete',
        'kanban:card_restore', 'kanban:refresh', 'kanban:refresh_tokenizer', 'kanban:pre_filter', 'kanban:filter',
        'kanban:post_filter', 'kanban:pre_load_state', 'kanban:post_load_state', 'kanban:pre_save_state',
        'kanban:post_save_state'
    ]);

    const rights = new Rights(props.rights);
    const filters = ref({
        _text: ''
    });
    /** @type SearchInput */
    let filter_input = null;
    const show_toolbar = ref(true);
    const columns = ref({});
    const all_columns = ref({});
    /**
     * @type {Ref<UnwrapRef<{
     *     state: {}|{order_index: {column: number, folded: boolean, cards: {Array}}}
     * }>>}
     */
    const user_state = ref({});
    let last_refresh = null;
    const _background_refresh_timer = ref(null);
    /**
     * Internal refresh function
     * @type {function}
     * @private
     */
    let _background_refresh = null;
    const all_kanbans = ref({});
    const kanban_switcher = ref(null);
    const debug_mode = $('body.debug-active').length > 0;
    let mutation_observer = null;
    let is_sorting_active = false;
    let sort_data = undefined;

    const team_badge_provider = new TeamBadgeProvider(props.display_initials, props.max_team_images);

    watch(kanban_switcher, (new_value) => {
        // If selection is new. Treats all 0 and negative values as the same thing (Global).
        if (new_value !== props.item.items_id && !(new_value <= 0 && props.item.items_id <= 0)) {
            $.ajax({
                type: "GET",
                url: CFG_GLPI.root_doc + '/ajax/kanban.php',
                data: {
                    action: "get_url",
                    itemtype: props.item.itemtype,
                    items_id: new_value
                }
            }).then((url) => {
                window.location = url;
            });
        }
    });

    function initMutationObserver() {
        mutation_observer = new MutationObserver((records) => {
            records.forEach(r => {
                if (r.addedNodes.length > 0) {
                    if (is_sorting_active) {
                        const sortable_placeholders = [...r.addedNodes].filter(n => n.classList.contains('sortable-placeholder'));
                        if (sortable_placeholders.length > 0) {
                            const placeholder = $(sortable_placeholders[0]);

                            const current_column = placeholder.closest('.kanban-column').attr('id');

                            // Compute current position based on list of sortable elements without current card.
                            // Indeed, current card is still in DOM (but invisible), making placeholder index in DOM
                            // not always corresponding to its position inside list of visible elements.
                            const sortable_elements = $('#' + CSS.escape(current_column) + ' ul.kanban-body > li:not([id="' + CSS.escape(sort_data.card_id) + '"])');
                            const current_position = sortable_elements.index(placeholder.get(0));
                            const card = $('#' + CSS.escape(sort_data.card_id));
                            card.data('current-pos', current_position);

                            if (!rights.canOrderCard()) {
                                if (current_column === sort_data.source_column) {
                                    if (current_position !== sort_data.source_position) {
                                        placeholder.addClass('invalid-position');
                                    } else {
                                        placeholder.removeClass('invalid-position');
                                    }
                                } else {
                                    if (!$(placeholder).is(':last-child')) {
                                        placeholder.addClass('invalid-position');
                                    } else {
                                        placeholder.removeClass('invalid-position');
                                    }
                                }
                            }
                        }
                    }
                }
            });
        });
        mutation_observer.observe($(`#${CSS.escape(props.element_id)}`).get(0), {
            subtree: true,
            childList: true
        });
    }

    /**
     * (Re-)Initialize JQuery sortable for all items and columns.
     * This should be called every time a new column or item is added to the board.
     */
    function refreshSortables() {
        $(`#${CSS.escape(props.element_id)}`).trigger('kanban:refresh_sortables');
        // Make sure all items in the columns can be sorted
        const bodies = $(`#${CSS.escape(props.element_id)} .kanban-body`);
        $.each(bodies, function(b) {
            const body = $(b);
            if (body.data('sortable')) {
                sortable(b, 'destroy');
            }
        });

        sortable(bodies, {
            forcePlaceholderSize: true,
            acceptFrom: '.kanban-body',
            items: '.kanban-item:not(.readonly):not(.temporarily-readonly):not(.filtered-out)',
        });

        bodies.off('sortstart').on('sortstart', (e) => {
            is_sorting_active = true;

            const card = $(e.detail.item);
            // Track the column and position the card was picked up from
            const current_column = card.closest('.kanban-column').attr('id');
            card.data('source-col', current_column);
            card.data('source-pos', e.detail.origin.index);
            sort_data = {
                card_id: card.attr('id'),
                source_column: current_column,
                source_position: e.detail.origin.index
            };
        });

        bodies.off('sortupdate').on('sortupdate', function(e) {
            const card = e.detail.item;
            if (this === $(card).parent()[0]) {
                return onKanbanCardSort(e, this);
            }
        });

        bodies.off('sortstop').on('sortstop', (e) => {
            is_sorting_active = false;
            $(e.detail.item).closest('.kanban-column').trigger('mouseenter'); // force readonly states refresh
        });

        if (rights.canModifyView()) {
            // Enable column sorting
            sortable(`#${CSS.escape(props.element_id)} .kanban-columns`, {
                acceptFrom: `#${CSS.escape(props.element_id)} .kanban-columns`,
                appendTo: '.kanban-container',
                items: '.kanban-column:not(.kanban-protected)',
                handle: '.kanban-column-header',
                orientation: 'horizontal',
                forcePlaceholderSize: true
            });
            $(`#${CSS.escape(props.element_id)} .kanban-columns .kanban-column:not(.kanban-protected) .kanban-column-header`).addClass('grab');
        }

        $(`#${props.element_id} .kanban-columns`).off('sortstop').on('sortstop', (e) => {
            e.stopPropagation();
            const column = e.detail.item;
            const column_id = column.id.split('-').pop();
            updateColumnPosition(column_id, $(column).index());
        });
    }

    function refreshSearchTokenizer() {
        filter_input.tokenizer.clearAutocomplete();

        // Refresh core tags autocomplete
        filter_input.tokenizer.setAutocomplete('type', Object.keys(props.supported_itemtypes).map(k => `<i class="${_.escape(props.supported_itemtypes[k].icon)} me-1"></i>` + _.escape(k)));
        filter_input.tokenizer.setAutocomplete('milestone', ["true", "false"]);
        filter_input.tokenizer.setAutocomplete('deleted', ["true", "false"]);

        emit('kanban:refresh_tokenizer', filter_input.tokenizer);
    }

    /**
     * Initialize the background refresh mechanism.
     */
    function backgroundRefresh() {
        if (props.bg_refresh_interval <= 0) {
            return;
        }
        _background_refresh = function() {
            const sorting = $('.sortable-placeholder');
            // Check if the user is current sorting items
            if (sorting.length > 0) {
                // Wait 10 seconds and try the background refresh again
                delayRefresh();
                return;
            }
            // Refresh and then schedule the next refresh (minutes)
            refresh(false).then(() => {
                _background_refresh_timer.value = window.setTimeout(_background_refresh, props.bg_refresh_interval * 60 * 1000);
            });
        };
        // Schedule initial background refresh (minutes)
        _background_refresh_timer.value = window.setTimeout(_background_refresh, props.bg_refresh_interval * 60 * 1000);
    }

    /**
     * Delay the background refresh for a short amount of time.
     * This should be called any time the user is in the middle of an action so that the refresh is not disruptive.
     * @param {number} delay_ms Delay in milliseconds
     */
    function delayRefresh(delay_ms = 10000) {
        window.clearTimeout(_background_refresh_timer.value);
        _background_refresh_timer.value = window.setTimeout(_background_refresh, delay_ms);
    }

    function toggleSubDropdown(e) {
        $(e.currentTarget).parent().toggleClass('active')
            .find('ul').toggle();
    }

    function getUpdatedColumnState() {
        const new_state = {};

        $.each(ordered_columns.value, (i, col) => {
            const column_id = col.id || i;
            new_state[i] = {
                column: column_id,
                folded: col.folded,
                visible: col.visible,
                cards: []
            };
            $.each(col.items, (j, card) => {
                new_state[i].cards.push(card.id);
            });
            // Sort cards based on their index in the column
            new_state[i].cards.sort((a, b) => {
                // Handle the case where the card is not in the column
                const card_a = $(`#${CSS.escape(a)}`);
                const card_b = $(`#${CSS.escape(b)}`);
                if (card_a.length === 0) {
                    return -1;
                }
                if (card_b.length === 0) {
                    return 1;
                }
                const a_index = card_a.index();
                const b_index = card_b.index();
                return a_index - b_index;
            });
        });

        return new_state;
    }

    /**
     * Saves the current state of the Kanban to the DB for the user.
     * This saves the visible columns and their collapsed state.
     * This should only be done if there is no state stored on the server, so one needs to be built.
     * Do NOT use this for changes to the state such as moving cards/columns!
     * @return {Promise<*>}
     */
    async function saveState() {
        emit('kanban:pre_save_state');
        return $.ajax({
            type: "POST",
            url: CFG_GLPI.root_doc + '/ajax/kanban.php',
            data: {
                action: "save_column_state",
                itemtype: props.item.itemtype,
                items_id: props.item.items_id,
                state: getUpdatedColumnState()
            },
        }).always(() => {
            emit('kanban:post_save_state');
        });
    }

    /**
     * Restore the Kanban state for the user from the DB if it exists.
     * This restores the visible columns and their collapsed state.
     * @return {Promise<*>}
     */
    async function loadState() {
        emit('kanban:pre_load_state');
        return $.ajax({
            type: "GET",
            url: CFG_GLPI.root_doc + '/ajax/kanban.php',
            data: {
                action: "load_column_state",
                itemtype: props.item.itemtype,
                items_id: props.item.items_id,
                last_load: last_refresh
            }
        }).then(async function(state) {
            if (state['state'] === undefined || state['state'] === null) {
                return;
            }
            user_state.value = {
                state: state['state']
            };

            const indices = Object.keys(state['state']);
            const promises = [];
            for (let i = 0; i < indices.length; i++) {
                const index = parseInt(indices[i]);
                const entry = state['state'][index];
                entry.folded = entry.folded === 'true' || entry.folded === true;
                const element = $(`#column-${CSS.escape(props.column_field.id)}-${CSS.escape(entry.column)}`);
                if (element.length === 0) {
                    promises.push(loadColumn(entry.column, true, false));
                }
                $(`#${CSS.escape(props.element_id)} .kanban-columns .kanban-column:nth-child(${index})`).after(element);
                if (entry.folded) {
                    element.addClass('collapsed');
                }
            }
            await Promise.all(promises);
            last_refresh = state['timestamp'];
            emit('kanban:post_load_state');
        });
    }

    /**
     * Clears the Kanban state for the user from the DB.
     * Useful if the state somehow gets corrupted.
     * @param ask_confirmation Whether to ask the user for confirmation
     */
    function clearState(ask_confirmation = true) {
        if (!rights.canModifyView()) {
            return;
        }
        const _clearState = () => {
            $.ajax({
                type: "POST",
                url: CFG_GLPI.root_doc + '/ajax/kanban.php',
                data: {
                    action: "clear_column_state",
                    itemtype: props.item.itemtype,
                    items_id: props.item.items_id
                }
            }).done(() => {
                // Reload page
                window.location.reload();
            }).fail(() => {
                glpi_toast_error(__('Failed to reset Kanban view'));
            });
        };
        if (ask_confirmation) {
            glpi_confirm({
                title: __('Reset view'),
                message: __('Resetting the view will reset the shown columns and remove custom card ordering'),
                confirm_callback: () => {
                    _clearState();
                }
            });
        } else {
            _clearState();
        }
    }

    /**
     * Load a column from the server and append it to the Kanban if it is visible.
     * @param {number} column_id The ID of the column to load.
     *    This is useful if an item is changed in another tab or by another user to be in the new column after the original column was added.
     * @return {Promise<void>}
     */
    async function loadColumn(column_id) {
        let skip_load = false;
        if (user_state.value.state !== undefined) {
            $.each(user_state.value.state, function (i, c) {
                if (parseInt(c['column']) === parseInt(column_id)) {
                    if (!c['visible']) {
                        skip_load = true;
                    }
                    return false;
                }
            });
        }
        if (skip_load) {
            return Promise.resolve(null);
        }

        try {
            return $.ajax({
                method: 'GET',
                url: CFG_GLPI.root_doc + '/ajax/kanban.php',
                data: {
                    action: "get_column",
                    itemtype: props.item.itemtype,
                    items_id: props.item.items_id,
                    column_field: props.column_field.id,
                    column_id: column_id
                }
            }).then((column) => {
                if (column !== undefined && Object.keys(column).length > 0) {
                    // Add the correct icons to the items
                    $.each(column[column_id].items, function(i, item) {
                        const itemtype = item.id.split('-')[0];
                        item['icon'] = props.supported_itemtypes[itemtype]['icon'] || '';
                    });
                    column[column_id].items = Object.values(column[column_id]?.items || {});
                    const state_for_col = Object.values(user_state.value.state).find((c) => parseInt(c.column) === parseInt(column_id));
                    column[column_id].folded = state_for_col?.folded || false;
                    columns.value[column_id] = column[column_id];
                    refreshSortables();

                    // If there are no cards in the state for this column, force a state save
                    if (state_for_col === undefined || state_for_col.cards.length === 0) {
                        saveState();
                    }
                }
            });
        } catch {
            return Promise.resolve(null);
        }
    }

    /**
     * Refresh the Kanban with the new set of columns.
     * @param {boolean} initial_load True if this is the first load. On the first load, the user state is not saved.
     * @return {Promise<*>}
     */
    async function refresh(initial_load = false) {
        const _refresh = async () => {
            const promise = $.ajax({
                method: 'GET',
                url: CFG_GLPI.root_doc + '/ajax/kanban.php',
                data: {
                    action: "refresh",
                    itemtype: props.item.itemtype,
                    items_id: props.item.items_id,
                    column_field: props.column_field.id
                }
            });
            promise.then((new_columns) => {
                columns.value = new_columns;
                if (initial_load && (user_state.value.state === undefined || Object.keys(user_state.value.state).length === 0)) {
                    // Save the state for the first time
                    saveState();
                }
                // Set the folded state of the columns to false initially in case they are not in the user state
                $.each(columns.value, (i, col) => {
                    col.folded = false;
                });

                // If cards exist in the user_state for this column, make sure those cards are in the right order. All other cards come after.
                $.each(user_state.value.state, (i, column) => {
                    const column_id = parseInt(column.column);
                    if (columns.value[column_id] === undefined) {
                        return;
                    }
                    columns.value[column_id].folded = column.folded || false;
                    columns.value[column_id].visible = column.visible ?? true;
                });
                emit('kanban:refresh');
                // Have to delay the badge image fetch until the next tick for it to work properly
                nextTick(() => {
                    team_badge_provider.fetchRequiredUserPictures();
                });
            });
            return promise;
        };
        return _refresh();
    }

    /**
     * Get the folded state of the column from the user state.
     * @param column_id The ID of the column.
     * @return {boolean} True if the column is folded, false otherwise (default if nothing specified in the user state for this column).
     */
    function getFoldedState(column_id) {
        if (user_state.value.state === undefined) {
            return false;
        }
        const match = Object.values(user_state.value.state).find((c) => parseInt(c.column) === parseInt(column_id));
        const folded = match !== undefined ? match.folded : false;
        return folded === true || folded === 'true';
    }

    /**
     * Update the collapsed state of the specified column in the event.
     * After toggling the collapse state, the server is notified of the change.
     * @param e
     */
    function updateFoldColumn(e) {
        const matching_col = Object.values(columns.value).find((c) => parseInt(c.id) === parseInt(e.column_id));
        if (matching_col) {
            matching_col.folded = e.folded;
        }
        $.ajax({
            type: "POST",
            url: CFG_GLPI.root_doc + '/ajax/kanban.php',
            data: {
                action: e.folded ? 'collapse_column' : 'expand_column',
                column: e.column_id,
                kanban: props.item
            }
        });
    }

    /**
     * Notify the server that the column's position has changed.
     * @param {number} column The ID of the column.
     * @param {number} position The position of the column.
     */
    function updateColumnPosition(column, position) {
        $.ajax({
            type: "POST",
            url: CFG_GLPI.root_doc + '/ajax/kanban.php',
            data: {
                action: "move_column",
                column: column,
                position: position,
                kanban: props.item
            }
        });
    }

    /**
     * Callback function for when a kanban item is moved.
     * @param {Object} e  Event
     * @param {Element} sortable Sortable object
     * @returns {Boolean}
     */
    function onKanbanCardSort(e, sortable) {
        const target = sortable.parentElement;
        const source = $(e.detail.origin.container);
        const card = $(e.detail.item);
        const el_params = card.attr('id').split('-');
        const target_params = $(target).attr('id').split('-');
        const column_id = target_params[target_params.length - 1];

        if (el_params.length === 2 && source !== null && !(!rights.canOrderCard() && source.length === 0)) {
            $.ajax({
                type: "POST",
                url: CFG_GLPI.root_doc + '/ajax/kanban.php',
                data: {
                    action: "update",
                    itemtype: el_params[0],
                    items_id: el_params[1],
                    column_field: props.column_field.id,
                    column_value: column_id
                },
                error: function() {
                    window.sortable(sortable, 'cancel');
                    return false;
                },
                success: function() {
                    let pos = card.data('current-pos');
                    if (!rights.canOrderCard()) {
                        card.appendTo($(target).find('.kanban-body').first());
                        pos = card.index();
                    }
                    // Update counters. Always pass the column element instead of the kanban body (card container)
                    card.removeData('source-col');
                    updateCardPosition(card.attr('id'), target.id, pos);
                    return true;
                }
            });
        } else {
            window.sortable(sortable, 'cancel');
            return false;
        }
    }

    /**
     * Send the new card position to the server.
     * @param {string} card The ID of the card being moved
     * @param {string|number} column The ID or element of the column the card resides in
     * @param {number} position The position in the column that the card is at
     */
    function updateCardPosition(card, column, position) {
        if (typeof column === 'string' && column.lastIndexOf('column', 0) === 0) {
            column = column.split('-').pop();
        }
        return $.ajax({
            type: "POST",
            url: CFG_GLPI.root_doc + '/ajax/kanban.php',
            data: {
                action: "move_item",
                card: card,
                column: column,
                position: position,
                kanban: props.item
            }
        });
    }

    function openCardDetailsPanel(e) {
        const id_parts = e.card_id.split('-', 2);
        const itemtype = id_parts[0];
        const items_id = id_parts[1];

        closeCardDetailsPanel();
        $.ajax({
            method: 'GET',
            url: CFG_GLPI.root_doc + '/ajax/kanban.php',
            data: {
                itemtype: itemtype,
                items_id: items_id,
                action: 'load_item_panel'
            }
        }).done((result) => {
            $(`#${CSS.escape(props.element_id)} .offcanvas`).remove();
            $(`#${CSS.escape(props.element_id)}`).append(`
                <div class="offcanvas offcanvas-end show position-absolute h-100" tabindex="-1">
                    <div class="offcanvas-body p-0"></div>
                </div>
            `);
            const offcanvas_body = $(`#${CSS.escape(props.element_id)} .offcanvas .offcanvas-body`);
            offcanvas_body.append(result);
            offcanvas_body.find(`.card-title button`).on('click', () => {
                closeCardDetailsPanel();
            });
            offcanvas_body.find('button.kanban-item-edit-team').on('click', () => {
                showTeamModal(itemtype, items_id);
            });
            // Load badges
            $('.item-details-panel ul.team-list li').each((i, l) => {
                l = $(l);
                const member_itemtype = l.attr('data-itemtype');
                const member_items_id = l.attr('data-items_id');
                let member_item = team_badge_provider.getTeamBadge({
                    itemtype: member_itemtype,
                    id: member_items_id,
                    name: l.attr('data-name'),
                    realname: l.attr('data-realname'),
                    firstname: l.attr('data-firstname')
                });
                l.append(`
                    <div class="member-details">
                        ${member_item}
                        ${_.escape(l.attr('data-name')) || `${_.escape(member_itemtype)} (${_.escape(member_items_id)})`}
                    </div>
                    <button type="button" name="delete" class="btn btn-ghost-danger">
                        <i class="ti ti-x" title="${__('Delete')}"></i>
                    </button>
                `);
            });
        });

        $(`#${props.element_id}`)
            .off('click', '.item-details-panel ul.team-list button[name="delete"]')
            .on('click', '.item-details-panel ul.team-list button[name="delete"]', (e) => {
                const list_item = $(e.target).closest('li');
                const member_itemtype = list_item.attr('data-itemtype');
                const member_items_id = list_item.attr('data-items_id');
                const panel = $(e.target).closest('.item-details-panel');
                const itemtype = panel.attr('data-itemtype');
                const items_id = panel.attr('data-items_id');
                const role = list_item.closest('.list-group').attr('data-role');

                if (itemtype && items_id) {
                    removeTeamMember(itemtype, items_id, member_itemtype, member_items_id, role);
                    list_item.remove();
                }
            });
    }

    function closeCardDetailsPanel() {
        $(`#${props.element_id} .offcanvas`).remove();
    }

    function showTeamModal(card_itemtype, card_items_id) {
        const modal = $('#kanban-modal');
        modal
            .off('click', 'button[name="add"]')
            .on('click', 'button[name="add"]', () => {
                $('.actor_entry').each(function() {
                    let itemtype = $(this).data('itemtype');
                    let items_id = $(this).data('items-id');
                    let role = $(this).data('actortype');
                    if (itemtype && items_id) {
                        addTeamMember(card_itemtype, card_items_id, itemtype, items_id, role).then(() => {
                            openCardDetailsPanel({
                                card_id: `${card_itemtype}-${card_items_id}`
                            });
                        });
                    }
                });
                modal.modal('hide');
            });
        $.ajax({
            method: 'GET',
            url: CFG_GLPI.root_doc + '/ajax/kanban.php',
            data: {
                itemtype: card_itemtype,
                items_id: card_items_id,
                action: 'load_teammember_form'
            }
        }).done((result) => {
            const teammember_types_dropdown = $(`#kanban-teammember-item-dropdown-${CSS.escape(card_itemtype)}`).html();
            const content = `
                ${teammember_types_dropdown}
                ${result}
                <button type="button" name="add" class="btn btn-primary">${_x('button', 'Add')}</button>
            `;
            modal.find('.modal-body').html(content);
            modal.modal('show');
        });
    }

    /**
     * Hide the column and notify the server of the change.
     * @param {number} column_id The ID of the column
     * @return {Promise<*>}
     */
    async function hideColumn(column_id) {
        return $.ajax({
            method: 'POST',
            url: CFG_GLPI.root_doc + '/ajax/kanban.php',
            data: {
                action: "hide_column",
                column: column_id,
                kanban: props.item
            }
        }).then(() => {
            delete columns.value[column_id];
            $.each(user_state.value.state, function(i, c) {
                if (parseInt(c['column']) === parseInt(column_id)) {
                    user_state.value.state[i]['visible'] = false;
                    return false;
                }
            });
        });
    }

    /**
     * Show the column and notify the server of the change.
     * @param {number} column_id The ID of the column
     * @return {Promise<*>}
     */
    async function showColumn(column_id) {
        return $.ajax({
            method: 'POST',
            url: CFG_GLPI.root_doc + '/ajax/kanban.php',
            data: {
                action: "show_column",
                column: column_id,
                kanban: props.item
            }
        }).then(() => {
            $.each(user_state.value.state, function(i, c) {
                if (parseInt(c['column']) === parseInt(column_id)) {
                    user_state.value.state[i]['visible'] = true;
                    return false;
                }
            });
            loadColumn(column_id);
        });
    }

    async function addTeamMember(itemtype, items_id, member_type, members_id, role) {
        return $.ajax({
            method: 'POST',
            url: CFG_GLPI.root_doc + '/ajax/kanban.php',
            data: {
                action: "add_teammember",
                itemtype: itemtype,
                items_id: items_id,
                itemtype_teammember: member_type,
                items_id_teammember: members_id,
                role: role
            }
        }).then(() => {
            refresh(false).then(() => {
                delayRefresh(props.bg_refresh_interval * 60 * 1000);
            });
        }, () => {
            glpi_toast_error(__('Failed to add team member'));
        });
    }

    /**
     * @typedef FilterData
     * @property {string} prefix
     * @property {string} term
     * @property {boolean} exclusion
     */
    const filter_matchers = {
        /**
         * @param {FilterData} filter_data The filter data
         * @param {string} target The string to match on
         * @return {boolean} True if the target matches the filter
         */
        regex: (filter_data, target) => {
            try {
                return ((!target.trim().match(filter_data.term)) === filter_data.exclusion);
            } catch (e) {
                // Invalid regex
                glpi_toast_error(
                    __('The regular expression you entered is invalid. Please check it and try again.'),
                    __('Invalid regular expression')
                );
            }
            return false;
        },
        /**
         * @param {FilterData} filter_data The filter data
         * @param {string} target The string to match on
         * @return {boolean} True if the target matches the filter
         */
        equal: (filter_data, target) => {
            return ((target !== filter_data.term) === filter_data.exclusion);
        },
        /**
         *
         * @param {FilterData} filter_data The filter data
         * @param {string} target The string to match on
         */
        include: (filter_data, target) => {
            return ((!target.toLowerCase().includes(filter_data.term.toLowerCase())) === filter_data.exclusion);
        },
        /**
         * @param {FilterData} filter_data The filter data
         * @param {string} target The string to match on
         * @param {string[]} sub_matchers The sub matchers to use
         * @return {boolean} True if the target matches the filter
         */
        text: (filter_data, target, sub_matchers = ['regex', 'includes']) => {
            if (filter_data.prefix === '#' && sub_matchers.includes('regex')) {
                return filter_matchers.regex(filter_data, target);
            } else {
                if (sub_matchers.includes('includes')) {
                    return filter_matchers.include(filter_data, target);
                }
                if (sub_matchers.includes('equals')) {
                    return filter_matchers.equal(filter_data, target);
                }
            }
        },
        /**
         * @param {FilterData} filter_data The filter data
         * @param {string} target The value to match on
         * @return {boolean} True if the target matches the filter
         */
        boolean: (filter_data, target) => {
            const negative_values = ['false', 'no', '0', 0, false, undefined];
            const negative_filter = negative_values.includes(typeof filter_data.term === 'string' ? filter_data.term.toLowerCase() : filter_data.term);
            const negative_target = negative_values.includes(typeof target === 'string' ? target.toLowerCase() : target);
            return ((negative_target !== negative_filter) === filter_data.exclusion);
        },
        teammember: (filter_data, itemtype, team) => {
            let has_matching_member = false;
            $.each(team, (i, m) => {
                if (m.itemtype === itemtype && (m.name.toLowerCase().includes(filter_data.term.toLowerCase()) !== filter_data.exclusion)) {
                    has_matching_member = true;
                }
            });
            return has_matching_member;
        }
    };
    watch(filters, (new_filters) => {
        emit('kanban:pre_filter', new_filters);

        $.each(columns.value, (i, c) => {
            $.each(c.items, (i2, item) => {
                const title = item.title;
                let shown = true;
                if (new_filters._text) {
                    try {
                        if (!title.match(new RegExp(new_filters._text, 'i'))) {
                            shown = false;
                        }
                    } catch (err) {
                        // Probably not a valid regular expression. Use simple contains matching.
                        if (!title.toLowerCase().includes(new_filters._text.toLowerCase())) {
                            shown = false;
                        }
                    }
                }
                if (new_filters.deleted !== undefined) {
                    if (!filter_matchers.boolean(new_filters.deleted, item._metadata.is_deleted)) {
                        shown = false;
                    }
                }

                if (new_filters.title !== undefined) {
                    if (!filter_matchers.text(new_filters.title, title)) {
                        shown = false;
                    }
                }

                if (new_filters.type !== undefined) {
                    if (!filter_matchers.text(new_filters.type, item.id.split('-')[0], ['regex', 'equals'])) {
                        shown = false;
                    }
                }

                if (new_filters.milestone !== undefined) {
                    if (!filter_matchers.boolean(new_filters.milestone, item._metadata.is_milestone)) {
                        shown = false;
                    }
                }

                if (new_filters.category !== undefined) {
                    if (!filter_matchers.text(new_filters.category, item._metadata.category)) {
                        shown = false;
                    }
                }

                if (new_filters.content !== undefined) {
                    if (!filter_matchers.text(new_filters.content, item._metadata.content)) {
                        shown = false;
                    }
                }

                const team_members = item._team;
                if (new_filters.team !== undefined) {
                    const team_search = new_filters.team.term.toLowerCase();
                    let has_matching_member = false;
                    $.each(team_members, (i, m) => {
                        if (m.name.toLowerCase().includes(team_search)) {
                            has_matching_member = true;
                        }
                    });
                    if (!has_matching_member) {
                        shown = false;
                    }
                }

                if (new_filters.user !== undefined) {
                    if (!filter_matchers.teammember(new_filters.user, 'User', team_members)) {
                        shown = false;
                    }
                }

                if (new_filters.group !== undefined) {
                    if (!filter_matchers.teammember(new_filters.group, 'Group', team_members)) {
                        shown = false;
                    }
                }

                if (new_filters.supplier !== undefined) {
                    if (!filter_matchers.teammember(new_filters.supplier, 'Supplier', team_members)) {
                        shown = false;
                    }
                }

                if (new_filters.contact !== undefined) {
                    if (!filter_matchers.teammember(new_filters.contact, 'Contact', team_members)) {
                        shown = false;
                    }
                }

                item._filtered_out = !shown;
            });
        });

        emit('kanban:filter', {
            filters: new_filters,
            kanban_element: $(`#${CSS.escape(props.element_id)}`),
            columns: columns.value
        });
        emit('kanban:post_filter', new_filters);
    }, {deep: true});

    async function removeTeamMember(itemtype, items_id, member_type, members_id, role) {
        return $.ajax({
            method: 'POST',
            url: CFG_GLPI.root_doc + '/ajax/kanban.php',
            data: {
                action: "delete_teammember",
                itemtype: itemtype,
                items_id: items_id,
                itemtype_teammember: member_type,
                items_id_teammember: members_id,
                role: role
            }
        }).then(() => {
            refresh(false).then(() => {
                delayRefresh(props.bg_refresh_interval * 60 * 1000);
            });
        }, () => {
            glpi_toast_error(__('Failed to remove team member'));
        });
    }

    /**
     * Delete a card
     * @param {{card_id: number}} e Event data
     * @return {Promise<*>}
     */
    async function deleteCard(e) {
        const [itemtype, items_id] = e.card_id.split('-', 2);
        let card = null;
        $.each(columns.value, (i, col) => {
            $.each(col.items, (i2, item) => {
                if (item.id === e.card_id) {
                    card = item;
                    return false;
                }
            });
            if (card !== null) {
                return false;
            }
        });
        if (card === null) {
            return Promise.reject();
        }
        const force = card._metadata.is_deleted;
        return $.ajax({
            type: "POST",
            url: CFG_GLPI.root_doc + '/ajax/kanban.php',
            data: {
                action: "delete_item",
                itemtype: itemtype,
                items_id: items_id,
                force: force ? 1 : 0
            }
        }).then((response) => {
            if (response.purged === true || response.purged === 'true') {
                $.each(columns.value, (i, col) => {
                    $.each(col.items, (i2, item) => {
                        if (item.id === e.card_id) {
                            delete col.items[i2];
                        }
                    });
                });
            } else {
                card._metadata.is_deleted = true;
            }
            emit('kanban:card_delete', {
                card_id: e.card_id,
                purged: response.purged || false
            });
            refresh();
        });
    }

    /**
     * Restore a trashed card
     * @param {{card_id: number}} e Event data
     * @return {Promise<*>}
     */
    async function restoreCard(e) {
        const [itemtype, items_id] = e.card_id.split('-', 2);
        let card = null;
        $.each(columns.value, (i, col) => {
            $.each(col.items, (i2, item) => {
                if (item.id === e.card_id) {
                    card = item;
                    return false;
                }
            });
            if (card !== null) {
                return false;
            }
        });
        if (card === null || !card._metadata.is_deleted) {
            return Promise.reject();
        }
        return $.ajax({
            type: "POST",
            url: CFG_GLPI.root_doc + '/ajax/kanban.php',
            data: {
                action: "restore_item",
                itemtype: itemtype,
                items_id: items_id,
            },
        }).then(() => {
            card._metadata.is_deleted = false;
            emit('kanban:card_restore', {
                card_id: e.card_id
            });
        });
    }

    function isColumnVisible(column_id) {
        const col = Object.values(user_state.value.state).find((c) => parseInt(c.column) === parseInt(column_id));
        if (col === undefined) {
            return false;
        }
        return col.visible ?? true;
    }

    async function updateAllColumnsList() {
        return $.ajax({
            method: 'GET',
            url: CFG_GLPI.root_doc + '/ajax/kanban.php',
            data: {
                action: "list_columns",
                itemtype: props.item.itemtype,
                column_field: props.column_field.id
            }
        }).then((data) => {
            all_columns.value = data;
        });
    }

    function updateColumnVisibility(e) {
        const trigger_checkbox = $(e.target);
        const column_id = trigger_checkbox.val();
        const new_visibility = trigger_checkbox.is(':checked');
        if (new_visibility) {
            showColumn(column_id);
        } else {
            hideColumn(column_id);
        }
    }

    /**
     * Create a new column and send it to the server.
     * This will create a new item in the DB based on the item type used for columns.
     * It does not automatically add it to the Kanban.
     * @param e Create status button click event
     * @return {Promise<*>}
     */
    async function createColumn(e) {
        const btn = $(e.target);
        const form = btn.closest('.kanban-form');
        const values = {};
        form.find('input').each((i, input) => {
            input = $(input);
            values[input.attr('name')] = input.val();
        });
        if (!values['name']) {
            return Promise.reject();
        }
        const column_name = values['name'];
        delete values['name'];
        return $.ajax({
            method: 'POST',
            url: CFG_GLPI.root_doc + '/ajax/kanban.php',
            data: {
                action: "create_column",
                itemtype: props.item.itemtype,
                items_id: props.item.items_id,
                column_field: props.column_field.id,
                column_name: column_name,
                params: values
            }
        }).then(() => {
            updateAllColumnsList();
        });
    }

    const column_filter = ref('');
    const filtered_columns = computed(() => {
        const visible = {};
        $.each(all_columns.value, (column_id, column) => {
            if (column.name.toLowerCase().includes(column_filter.value.toLowerCase())) {
                visible[column_id] = column;
                visible[column_id].id = column_id;
            }
        });
        let sorted_data = Object.values(visible); // Cast Object to array
        const collator = new Intl.Collator(undefined, {
            numeric: true,
            sensitivity: 'base'
        });
        return sorted_data.sort((a, b)  => collator.compare(a.name, b.name));
    });

    const ordered_columns = computed(() => {
        // If there is a column without an ID or <= 0, it should be first (No status column and other special columns)
        // Then, the legacy_user_state should be used to determine the order of all the columns referenced by it
        // Finally, the remaining columns will be shown in the order they are in the columns Object at the end
        const ordered = [];
        const added = [];
        $.each(columns.value, (column_id, column) => {
            const i_column_id = parseInt(column.id || column_id);
            if (i_column_id <= 0) {
                column.id = i_column_id;
                ordered.push(column);
                added.push(i_column_id);
            }
        });
        if (user_state.value.state !== undefined) {
            $.each(user_state.value.state, (i, c) => {
                const i_column_id = parseInt(c.column);
                if (!added.includes(i_column_id) && columns.value[i_column_id] !== undefined) {
                    columns.value[i_column_id].id = i_column_id;
                    ordered.push(columns.value[i_column_id]);
                    added.push(i_column_id);
                }
            });
        }
        $.each(columns.value, (column_id, column) => {
            const i_column_id = parseInt(column_id);
            column.id = i_column_id;
            if (!added.includes(i_column_id)) {
                ordered.push(column);
                added.push(i_column_id);
            }
        });

        // Order the cards
        // If the card is in the user state, use that position
        // The remaining cards will be shown in the order they are in the column
        $.each(ordered, (i, column) => {
            const ordered_cards = [];
            const added_cards = [];
            let col_state = Object.values(user_state.value.state || {}).filter((c) => parseInt(c.column) === parseInt(column.id));
            if (col_state.length > 0) {
                col_state = col_state[0];
            } else {
                col_state = null;
            }
            if (col_state !== null) {
                $.each(col_state.cards || {}, (i2, card_id) => {
                    $.each(column.items || {}, (i3, card) => {
                        if (card.id === card_id) {
                            ordered_cards.push(card);
                            added_cards.push(card_id);
                        }
                    });
                });
            }
            $.each(column.items || {}, (i2, card) => {
                if (!added_cards.includes(card.id)) {
                    ordered_cards.push(card);
                    added_cards.push(card.id);
                }
            });
            column.items = ordered_cards;
        });

        // Remove non-visible columns
        return ordered.filter((c) => c.visible !== false);

        return ordered;
    });

    emit('kanban:pre_init');
    await loadState();
    await $.ajax({
        type: 'GET',
        url: CFG_GLPI.root_doc + '/ajax/kanban.php',
        data: {
            action: 'get_kanbans',
            itemtype: props.item.itemtype,
            items_id: props.item.items_id,
        }
    }).then((kanbans) => {
        all_kanbans.value = kanbans;
        kanban_switcher.value = props.item.items_id <= 0 ? -1 : props.item.items_id;
    });

    await refresh(true);
    backgroundRefresh();
    onMounted(() => {
        initMutationObserver();
        filter_input = new SearchInput($(`#${CSS.escape(props.element_id)} input[name="filter"]`), {
            allowed_tags: props.supported_filters,
            on_result_change: (e, result) => {
                filters.value = {
                    _text: ''
                };
                filters.value._text = result.getFullPhrase();
                result.getTaggedTerms().forEach(t => filters.value[t.tag] = {
                    term: t.term || '',
                    exclusion: t.exclusion || false,
                    prefix: t.prefix
                });
            },
            tokenizer_options: {
                custom_prefixes: {
                    '#': { // Regex prefix
                        label: __('Regex'),
                        token_color: '#00800080'
                    }
                }
            }
        });
        refreshSearchTokenizer();
        refreshSortables();
        emit('kanban:post_init');
    });
</script>

<template>
    <div :id="element_id" class="kanban">
        <div class="kanban-container">
            <div v-if="show_toolbar" class="kanban-toolbar flex-column flex-md-row btn-group shadow-none">
                <select name="kanban-board-switcher" v-model="kanban_switcher">
                    <option v-for="(k_name, k_id) in all_kanbans" :value="k_id" :selected="k_id === item.items_id" :key="k_id">{{ k_name }}</option>
                </select>
                <input name="filter" class="form-control ms-1" type="text" :placeholder="__('Search or filter results')" autocomplete="off"/>
                <div class="dropdown">
                    <button type="button" class="kanban-add-column btn btn-outline-secondary ms-1" v-if="rights.canModifyView()"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside" @click="updateAllColumnsList()">{{ __('Add column') }}</button>
                    <div class="dropdown-menu kanban-form kanban-add-column-form p-2" role="menu">
                        <div class="kanban-item-header">
                            <span class="kanban-item-title" v-text="__('Add a column from existing status')"></span>
                        </div>
                        <div class="kanban-item-content">
                            <input type="text" class="form-control" name="column-name-filter" :placeholder=" __('Search')"
                                   v-model="column_filter"/>
                            <ul class="kanban-columns-list ps-1">
                                <li v-for="column in filtered_columns" :key="column.id">
                                    <input v-if="isColumnVisible(column.id)" type="checkbox" class="form-check-input"
                                           :value="column.id" checked="checked" @change="updateColumnVisibility"/>
                                    <input v-else type="checkbox" class="form-check-input"
                                           :value="column.id" @change="updateColumnVisibility"/>
                                    <span :class="`kanban-color-preview ${column.color_class ? column.color_class : ''} ms-1`"
                                          :style="`${column.color_class ? '' : ('background-color: ' + column.header_color)}`"></span>
                                    {{ column.name }}
                                </li>
                            </ul>
                        </div>
                        <template v-if="rights.canCreateColumn()">
                            <hr>{{ __('Or add a new status') }}
                            <div class="dropdown">
                                <button type="button" class="btn btn-primary kanban-create-column d-block" v-text="__('Create status')"
                                        data-bs-toggle="dropdown" data-bs-auto-close="outside"></button>
                                <div class="dropdown-menu kanban-form kanban-create-column-form p-2" role="menu">
                                    <div class="kanban-item-header">
                                        <span class="kanban-item-title" v-text="__('Create status')"></span>
                                    </div>
                                    <div class="kanban-item-content">
                                        <input type="text" name="name" class="form-control"/>
                                        <template v-for="(field, field_name) in column_field.extra_fields">
                                            <input :key="`${field_name}_${field.type}`" v-if="field.type === undefined || field.type === 'text'" type="text" :name="field_name"
                                                   class="form-control" :value="field.value"/>
                                            <input :key="`${field_name}_${field.type}`" v-else-if="field.type === 'color'" type="color" :name="field_name"
                                                   class="form-control" :value="field.value"/>
                                        </template>
                                    </div>
                                    <button type="button" class="btn btn-primary" v-text="__('Create status')" @click="createColumn($event)"></button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="dropdown">
                    <button type="button" class="btn btn-outline-secondary btn-icon ms-1 kanban-extra-toolbar-options" v-if="rights.canModifyView()"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside">
                        <i class="ti ti-dots-vertical"></i>
                    </button>
                    <ul class="kanban-dropdown dropdown-menu kanban-extra-toolbar-options-menu" role="menu">
                        <li class="dropdown-item cursor-pointer" @click="clearState(true)">
                            <span>
                                <i class="ti ti-trash"></i>{{ __('Reset view') }}
                            </span>
                        </li>
                        <template v-if="debug_mode">
                            <li class="dropdown-item cursor-pointer" @click="loadState()">
                                <span><i class="ti ti-cloud-download"></i>Load state (Debug)</span>
                            </li>
                            <li class="dropdown-item cursor-pointer" @click="saveState(true, true)">
                                <span><i class="ti ti-cloud-upload"></i>Save state (Debug)</span>
                            </li>
                            <li class="dropdown-item cursor-pointer" @click="refresh(false)">
                                <span><i class="ti ti-refresh"></i>Refresh (Debug)</span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
            <div class="kanban-columns">
                <Column v-show="ordered_columns.length > 0" v-for="column in ordered_columns" :key="column.id"
                        :column_id="parseInt(column.id)" :column_data="column" :rights="rights" :column_field_id="column_field.id"
                        :supported_itemtypes="supported_itemtypes" @vue:mounted="refreshSortables()"
                        @kanban:column_fold="updateFoldColumn($event)" :team_badge_provider="team_badge_provider"
                        @kanban:card_delete="deleteCard" @kanban:card_restore="restoreCard" @kanban:refresh="refresh"
                        @kanban:column_hide="hideColumn(column.id)" @kanban:card_show_details="openCardDetailsPanel($event)"></Column>
                <div v-show="ordered_columns.length === 0" class="w-100">
                    <div class="alert alert-info">{{ __('There are no columns added to this Kanban yet') }}</div>
                </div>
            </div>
            <ul id="kanban-item-overflow-dropdown" class="kanban-dropdown dropdown-menu d-none">
                <li class="kanban-item-goto dropdown-item">
                    <a href="#" @click.prevent.stop="toggleSubDropdown($event)">
                        <i class="ti ti-share-3"></i>{{ __('Go to') }}
                    </a>
                </li>
                <li class="kanban-item-restore dropdown-item d-none" v-if="rights.canDeleteItem()">
                    <span>
                        <i class="ti ti-trash-off"></i>{{ __('Restore') }}
                    </span>
                </li>
                <li class="kanban-item-remove dropdown-item d-none" v-if="rights.canDeleteItem()">
                    <span>
                        <i class="ti ti-trash"></i>{{ __('Delete') }}
                    </span>
                </li>
            </ul>
        </div>
    </div>
</template>

<style scoped lang="scss">
    .kanban {
        position: relative;
        height: 100%;
        --toolbar-margin: 15px;
        --toolbar-padding: 10px;

        .kanban-toolbar {
            display: flex;
            margin-bottom: var(--toolbar-margin);
            font-size: 14px;
            padding: var(--toolbar-padding);

            select[name="kanban-board-switcher"] {
                appearance: auto;
            }

            .fas,
            .fa-solid {
                margin: auto auto auto 10px;
                font-size: 1.2em;
                cursor: pointer;
            }

            .kanban-columns-list {
                max-height: 50vh;
                overflow-y: auto;
                margin: 10px 0;
                list-style: none;

                li {
                    padding: 5px 0 0;
                }
            }
        }

        .kanban-container {
            overflow-x: auto;
            overflow-y: hidden;
            height: calc(100vh - var(--glpi-contextbar-height) - var(--glpi-content-margin) - (var(--toolbar-margin) + var(--toolbar-padding) + (0.5625rem * 2) + 1.2rem + 2px));

            .kanban-dropdown {
                position: fixed;
                width: max-content;
                min-width: 100px;
                text-align: left;
                list-style: none;
                padding-left: 0;

                li {
                    margin-top: 2px;
                    position: relative;

                    a,
                    span {
                        color: inherit;
                        cursor: pointer;
                        display: block;

                        i {
                            color: inherit;
                        }
                    }

                    ul {
                        position: absolute;
                        left: 100%;
                        top: 0;
                        display: block;
                        width: max-content;
                        min-width: 100px;// Use outline instead of border on submenus to avoid them being aligned off by one pixel
                        margin-left: 1px;
                        list-style: none;
                    }

                    i {
                        margin-right: 1em;
                    }
                }

                li:first-of-type {
                    margin-top: 0;
                }

                li.dropdown-trigger {
                    a::after {
                        content: "\f054";
                        font: var(--fa-font-solid);
                        padding-left: 10px;
                    }
                }
            }

            .kanban-columns {
                display: flex;
                overflow-x: auto;
                height: calc(100% - 75px);
            }
        }

        .kanban-color-preview {
            width: 1em;
            height: 1em;
            display: inline-block;
            margin-right: 5px;
            vertical-align: middle;
            background-color: var(--status-color);
        }
    }

    .flex-break {
        width: 100%;
    }
</style>

<style lang="scss">
    .kanban .item-details-panel {
        height: 100%;

        ul.team-list li {
            img {
                border-radius: 50%;
            }

            button {
                display: none;
                padding: unset;
            }

            button:hover,
            button i:hover,
            &:hover button {
                display: inline-flex;
            }

            .member-details > span {
                margin-right: 2rem;
            }
        }
    }
</style>
