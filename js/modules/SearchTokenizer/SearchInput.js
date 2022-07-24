/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

import SearchTokenizer from "./SearchTokenizer.js";

/**
 * @typedef SearchInputOptions
 * @property {{}} [popover] Popover options
 * @property {'edit'|'remove'} [backspace_action='edit'] The action when pressing the backspace key at the start of the input
 * @property {function} [on_result_change] Callback when the result changes
 * @property {TokenizerOptions} [tokenizer_options] Tokenizer options
 * @property {boolean} filter_on_type Whether to filter the suggestions on typing
 * @property {{}} [input_options] Options for the new input element
 * @property {[]|'copy'} [input_options.classes] Classes for the new input element. If set to "copy", the classes of the original input will be copied
 * @property {{}|'copy'} [input_options.attributes] Attributes for the new input element. If set to "copy", the attributes of the original input will be copied
 * @property {{}|'copy'} [input_options.data] Data for the new input element. If set to "copy", the attributes of the original input will be copied
 */

export default class SearchInput {

    constructor(input, options) {
        /**
       * @type {jQuery}
       */
        this.original_input = $(input);

        /**
       * @type {SearchInputOptions}
       */
        this.options = Object.assign({
            backspace_action: 'edit',
            tokenizer_options: {},
            filter_on_type: true,
            input_options: {
                classes: [],
                attributes: {},
                data: {}
            }
        }, options || {});
        this.tokenizer = new SearchTokenizer(this.options.allowed_tags || {}, this.options.drop_unallowed_tags || false, this.options.tokenizer_options);

        this.displayed_input = $(`
         <div class="form-control search-input d-flex overflow-auto" tabindex="0"></div>
      `).insertBefore(input);
        this.displayed_input.append(`<span class="search-input-tag-input flex-grow-1" contenteditable="true"></span>`);
        this.applyInputOptions();

        this.original_input.hide();

        this.last_result = null;

        this.registerListeners();
    }

    applyInputOptions() {
        let new_attrs = {};

        if (typeof this.options.input_options.attributes === 'object') {
            new_attrs = this.options.input_options.attributes;
        } else if (this.options.input_options.attributes === 'copy') {
            const original_attr = this.original_input.get(0).attributes;
            for (let i = 0; i < original_attr.length; i++) {
                // Get only non-data attributes
                if (!original_attr[i].name.startsWith('data-') && original_attr[i].name !== 'class') {
                    new_attrs[original_attr[i].name] = original_attr[i].value;
                }
            }
        }

        let new_data = {};
        let old_data_attrs = {};
        if (typeof this.options.input_options.data === 'object') {
            new_data = this.options.input_options.data;
        } else if (this.options.input_options.data === 'copy') {
            new_data = this.original_input.data();
            const original_attr = this.original_input.get(0).attributes;
            // Get data attributes in case they aren't in jQuery data
            for (let i = 0; i < original_attr.length; i++) {
                // Get only data attributes
                if (original_attr[i].name.startsWith('data-')) {
                    old_data_attrs[original_attr[i].name] = original_attr[i].value;
                }
            }
        }

        // Add data attributes. We don't use $.data() because having the DOM attribute may be needed and using $.data doesn't add them.
        // Information from $.data will override any data attributes of the same name
        new_attrs = Object.assign(old_data_attrs, Object.keys(new_data).reduce((obj, key) => {
            obj['data-' + key] = new_data[key];
            return obj;
        }, new_attrs));

        // Apply attributes including data attributes
        this.displayed_input.attr(new_attrs);

        // Apply classes
        if (Array.isArray(this.options.input_options.classes)) {
            this.displayed_input.addClass(this.options.input_options.classes.join(' '));
        } else if (this.options.input_options.classes === 'copy') {
            this.displayed_input.addClass(this.original_input.attr('class'));
        }
    }

    registerListeners() {

        const input = this.displayed_input;

        input.on('input change', () => {
            if (this.isSelectionUntagged()) {
                this.refreshPopover();
            }
        });

        input.popover(Object.assign({
            trigger: 'manual',
            html: true,
            container: this.displayed_input.parent(),
            customClass: 'search-input-popover shadow',
            placement: 'bottom', // Option from Bootstrap (fallback)
            popperConfig: {
                placement: 'bottom-start', // Option only available directly in popper.js (Preferred)
            },
            delay: {
                hide: 300
            },
            sanitize: false,
            content: () => {
                return this.getPopoverContent();
            }
        }, this.options.popover || {}));

        input.parent().on('mousedown', '.search-input-popover', (e) => {
            e.preventDefault();
        });

        input.parent().on('click', '.search-input-popover .tags-list li', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const tag = $(e.target).closest('li').attr('data-tag');
            const node = $('<span class="search-input-tag-input">'+tag.trim()+':</span>').insertBefore($('.search-input-tag-input:last-of-type'));
            //Clear selected node's text
            const selected_node = this.getSelectedNode();
            $(selected_node).text('');
            const new_node = this.tagifyInputNode(node);
            this.makeTagEditable(new_node);
            new_node.focus();
        });

        input.parent().on('click', '.search-input-popover .tags-list li button.tag-prefix', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const prefix = $(e.target).closest('button.tag-prefix').attr('data-prefix');
            const tag = $(e.target).closest('li').attr('data-tag');
            const node = $('<span class="search-input-tag-input">'+(prefix || '')+tag.trim()+':</span>').insertBefore($('.search-input-tag-input:last-of-type'));
            //Clear selected node's text
            const selected_node = this.getSelectedNode();
            $(selected_node).text('');
            const new_node = this.tagifyInputNode(node);
            this.makeTagEditable(new_node);
            new_node.focus();
        });

        input.parent().on('click', '.search-input-popover .term-suggestions-list li', (e) => {
            e.preventDefault();
            const li = $(e.target).closest('li');
            const tag = li.closest('ul').attr('data-tag');
            const selected_term = li.text().trim();
            const editing_node = input.find('.search-input-tag-input[data-tag="'+tag+'"]');
            editing_node.text(`${tag}:${selected_term}`);
            this.tagifyInputNode(editing_node);
            this.placeCaretInDefaultInput();
        });

        input.on('input click focus', () => {
            this.refreshPopover();
            input.popover('show');
        });

        $(document.body).on('click', (e) => {
            if ($(e.target).closest(input, this.original_input, input.parent().find('.search-input-popover')).length === 0) {
                input.popover('hide');
            }
        });

        input.on('blur', '.search-input-tag-input', (e) => {
            const tag_input = $(e.target).closest('.search-input-tag-input');
            this.tagifyInputNode(tag_input);
        });

        input.on('keydown', '.search-input-tag-input', (e) => {
            if (e.keyCode === 9) { // Tab
            // Prevent losing focus when pressing tab key
                e.preventDefault();
            } else if (e.keyCode === 8) { // Backspace
                const selected_node = this.getSelectedNode();
                if (!selected_node || selected_node.classList.contains('search-input-tag-input')) {
                    const selection = document.getSelection();
                    if (!selection.anchorNode.isSameNode(selection.focusNode)) {
                        // Prevent removing the input placeholder tag
                        e.preventDefault();
                    }
                    // if end selection is at the beginning of the input, do the backspace_action
                    if (selection.anchorOffset === 0) {
                        if (this.options.backspace_action === 'remove') {
                            // Remove tag element before the selected_node
                            const prev_node = selected_node.previousSibling;
                            if (prev_node) {
                                prev_node.remove();
                                this.displayed_input.trigger('result_change');
                            }
                        } else if (this.options.backspace_action === 'edit') {
                            // Make the tag element before the selected_node editable
                            const prev_node = $(selected_node.previousSibling);
                            if (prev_node) {
                                this.makeTagEditable(prev_node);
                                e.preventDefault();
                            }
                        }
                    }
                }
            } else if (e.keyCode === 13) { // Enter
                e.preventDefault();
                const popover_list = input.parent().find('.search-input-popover ul');
                if (popover_list.length > 0) {
                    const active_item = popover_list.find('li.active');
                    if (active_item.length > 0) {
                        const active_tag_prefix = active_item.find('button.tag-prefix.active');
                        if (active_tag_prefix.length > 0) {
                            active_tag_prefix.click();
                        } else {
                            active_item.click();
                        }
                    } else {
                        this.tagifySelectedNode();
                    }
                } else {
                    this.tagifySelectedNode();
                }
            } else if (e.keyCode === 40) { // Down arrow
                const popover_list = input.parent().find('.search-input-popover ul');
                if (popover_list.length > 0) {
                    const active_item = popover_list.find('li.active');
                    if (active_item.length === 0) {
                        popover_list.find('li:first-of-type').addClass('active');
                    } else {
                        const next_item = active_item.next();
                        if (next_item.length > 0) {
                            active_item.removeClass('active');
                            next_item.addClass('active');
                        }
                    }
                    //deactivate all tag-prefix buttons in the popover
                    popover_list.find('button.tag-prefix').removeClass('active');
                }
            } else if (e.keyCode === 38) { // Up arrow
                const popover_list = input.parent().find('.search-input-popover ul');
                if (popover_list.length > 0) {
                    const active_item = popover_list.find('li.active');
                    if (active_item.length === 0) {
                        popover_list.find('li:last-of-type').addClass('active');
                    } else {
                        const prev_item = active_item.prev();
                        if (prev_item.length > 0) {
                            active_item.removeClass('active');
                            prev_item.addClass('active');
                        }
                    }
                    //deactivate all tag-prefix buttons in the popover
                    popover_list.find('button.tag-prefix').removeClass('active');
                }
            } else if (e.keyCode === 37) { // Left arrow
                const popover_list = input.parent().find('.search-input-popover ul');
                if (popover_list.length > 0) {
                    const active_item = popover_list.find('li.active');
                    if (active_item.length > 0) {
                        const active_tag_prefix = active_item.find('button.tag-prefix.active');
                        if (active_tag_prefix.length === 0) {
                            active_item.find('button.tag-prefix:last-of-type').addClass('active');
                        } else {
                            const prev_tag_prefix = active_tag_prefix.prev();
                            active_tag_prefix.removeClass('active');
                            if (prev_tag_prefix.length > 0) {
                                prev_tag_prefix.addClass('active');
                            }
                        }
                    }
                }
            } else if (e.keyCode === 39) { // Right arrow
                const popover_list = input.parent().find('.search-input-popover ul');
                if (popover_list.length > 0) {
                    const active_item = popover_list.find('li.active');
                    if (active_item.length > 0) {
                        const active_tag_prefix = active_item.find('button.tag-prefix.active');
                        if (active_tag_prefix.length === 0) {
                            active_item.find('button.tag-prefix:first-of-type').addClass('active');
                        } else {
                            const next_tag_prefix = active_tag_prefix.next();
                            active_tag_prefix.removeClass('active');
                            if (next_tag_prefix.length > 0) {
                                next_tag_prefix.addClass('active');
                            }
                        }
                    }
                }
            }
        });

        input.on('keypress', '.search-input-tag-input', (e) => {
            // Prevent default behavior of the enter key
            if (e.keyCode === 13) {
                e.preventDefault();
            }
        });

        input.on('keyup', 'search-input-tag-input', (e) => {
            if (e.keyCode === 9) { // Tab
                e.preventDefault();
                this.tagifySelectedNode();
            }
        });

        input.on('click', '.search-input-tag', (e) => {
            const tag = $(e.target).closest('.search-input-tag');
            this.makeTagEditable(tag);
        });

        input.on('click', '.search-input-tag i', (e) => {
            $(e.target).closest('.search-input-tag').remove();
            this.displayed_input.trigger('result_change');
        });

        input.on('result_change', (e) => {
            let text = this.getRawInput();

            const result = this.tokenizer.tokenize(text);
            const result_changed = JSON.stringify(result) !== JSON.stringify(this.last_result);
            if (this.options.on_result_change && result_changed) {
                this.options.on_result_change(e, result);
            }
            this.last_result = result;
        });
    }

    tagifySelectedNode() {
        const selected_node = $(this.getSelectedNode());
        if (selected_node && this.isSelectionUntagged()) {
            return this.tagifyInputNode(selected_node);
        }
        return null;
    }

    /**
    *
    * @param {SearchToken} token
    */
    tokenToTagHtml(token) {
        const tag_display = token.tag ? `<b>${token.exclusion ? this.tokenizer.EXCLUSION_PREFIX : ''}${token.prefix ? token.prefix : ''}${token.tag}</b>:` : '';
        let tag_color_override = null;
        if (this.tokenizer.options.custom_prefixes[token.prefix]) {
            tag_color_override = this.tokenizer.options.custom_prefixes[token.prefix].token_color || null;
        } else if (token.exclusion) {
            tag_color_override = '#80000080';
        }
        const dark_mode = $('html').css('--is-dark').trim() === 'true';
        const text_color = $(document.body).css('color');
        let style_overrides = '';
        if (!token.tag) {
            tag_color_override = text_color;
        }
        if (dark_mode) {
            tag_color_override = tag_color_override || '#b3b3b3';
            // Remove alpha from hex color
            if (tag_color_override.indexOf('#') === 0) {
                tag_color_override = tag_color_override.replace(/[^#]*#([0-9a-f]{6})([0-9a-f]{2})?/i, '#$1');
            }
            style_overrides = tag_color_override ? `style="border-color: ${tag_color_override} !important; background-color: unset !important;"` : '';
        } else {
            style_overrides = tag_color_override ? `style="background-color: ${tag_color_override} !important"` : '';
        }
        return `<span class="search-input-tag badge bg-secondary me-1" contenteditable="false" data-tag="${token.tag}" ${style_overrides}>
                  <span class="search-input-tag-value" contenteditable="false">${tag_display}${token.term || ''}</span>
                  <i class="ti ti-x cursor-pointer ms-1" title="${__('Delete')}" contenteditable="false"></i>
               </span>`;
    }

    tagifyInputNode(node) {
        const tokenized = this.tokenizer.tokenize(node.text());
        const tagged_tokens = tokenized.getTaggedTerms();
        const untagged_tokens = tokenized.getUntaggedTerms();

        let last_inserted = null;
        for (let i = 0; i < tagged_tokens.length; i++) {
            const t = tagged_tokens[i];
            last_inserted = $(this.tokenToTagHtml(t)).insertBefore(node);
            last_inserted.data('token', t);
            this.transformTagTermFromAutocomplete(last_inserted);
        }

        if (node.data('token') !== undefined && node.data('token').tag) {
            const untagged_text = tokenized.getFullPhrase();
            node.text(untagged_text);
        } else {
            for (let i = 0; i < untagged_tokens.length; i++) {
                const t = untagged_tokens[i];
                last_inserted = $(this.tokenToTagHtml(t)).insertBefore(node);
                last_inserted.data('token', t);
            }
            node.text('');
        }

        if (node.text().length === 0) {
            // if node is the last child of the container, empty it. Otherwise, remove the selected node
            if (node.is(':last-child')) {
                node.empty();
            } else {
                try {
                    node.remove();
                } catch (e) {
                    // node is already removed. In some cases, this can be attempted to be removed twice
                }
            }
            if (last_inserted) {
                this.displayed_input.find('.search-input-tag-input:last-of-type').focus();
                this.refreshPopover();
            }
        } else {
            // place cursor at end of the selected_node text
            this.placeCaretAtEndOfNode(node.get(0));
        }

        this.displayed_input.trigger('result_change');

        return last_inserted;
    }

    transformTagTermFromAutocomplete(node) {
        const tokenized = this.tokenizer.tokenize(node.text());
        const tagged_tokens = tokenized.getTaggedTerms();
        const last_token = tagged_tokens[tagged_tokens.length - 1];
        const autocomplete_info = this.tokenizer.getAutocomplete(last_token.tag);

        if (autocomplete_info) {
            autocomplete_info.forEach((t) => {
                const autocomplete_value = $(`<span>${t}</span>`).text();
                const term_text = $(`<span>${last_token.term}</span>`).text();
                if (autocomplete_value.localeCompare(term_text, undefined, { sensitivity: 'accent' }) === 0) {
                    last_token.term = t;
                    node.replaceWith($(this.tokenToTagHtml(last_token)));
                }
            });
        }
    }

    makeTagEditable(tag) {
        if (tag && tag.hasClass('search-input-tag')) {
            tag.removeClass('search-input-tag');
            tag.addClass('search-input-tag-input');
            tag.attr('contenteditable', 'true');
            const v = tag.text().trim();
            tag.empty();
            tag.text(v);
            tag.focus();
            // place cursor at end of the tag text
            this.placeCaretAtEndOfNode(tag.get(0));
            // Refresh popover to get up to date suggestions
            this.refreshPopover();
            this.displayed_input.trigger('result_change');
        }
    }

    getSelectedNode() {
        const selection = document.getSelection();
        let result = null;
        if (selection) {
            result = selection.anchorNode;
            if (result && result.nodeType === Node.TEXT_NODE) {
                result = result.parentNode;
            }
        }
        return result || null;
    }

    isSelectionUntagged() {
        const node = this.getSelectedNode();
        return node !== null && node.classList.contains('search-input-tag-input');
    }

    placeCaretAfterNode(node) {
        if (!node || !node.parentNode) {
            return;
        }
        const nextSibling = node.nextSibling;
        const sel = document.getSelection();
        const range = sel.getRangeAt(0);

        if (sel.rangeCount) {
            range.setStartAfter(nextSibling || node);
            range.collapse(true);

            sel.removeAllRanges();
            sel.addRange(range);

            this.refreshPopover();
        }
    }

    placeCaretAtStartOfNode(node) {
        if (!node || !node.parentNode) {
            return;
        }
        const sel = document.getSelection();
        const range = sel.getRangeAt(0);

        if (sel.rangeCount) {
            range.setStart(node, 0);
            range.collapse(true);

            sel.removeAllRanges();
            sel.addRange(range);

            this.refreshPopover();
        }
    }

    placeCaretAtEndOfNode(node) {
        const selection = document.getSelection();
        const range = document.createRange();

        if (node.lastChild && node.lastChild.nodeType === Node.TEXT_NODE) {
            range.setStart(node.lastChild, node.lastChild.length);
        } else {
            range.setStart(node, node.childNodes.length);
        }
        selection.removeAllRanges();
        selection.addRange(range);

        this.refreshPopover();
    }

    placeCaretInDefaultInput() {
        const default_input = this.displayed_input.find('.search-input-tag-input:last-of-type');
        if (default_input.length > 0) {
            this.placeCaretAtStartOfNode(default_input.get(0));
        }
    }

    getRawInput() {
        return this.displayed_input.get(0).textContent;
    }

    refreshPopover() {
        const content = this.getPopoverContent();
        this.displayed_input.parent().find('.popover-body').html(content);
    }

    getPopoverContent() {
        const input = this.displayed_input;

        const selected = $(this.getSelectedNode());
        let last_token = null;

        if (this.isSelectionUntagged()) {
            if (selected.closest(input)) {
                const text = selected.text();
                const cursor_pos = document.getSelection().anchorOffset;

                const t = text.slice(0, cursor_pos);
                if (t.endsWith(' ')) {
                    return this.getTagsHelperContent();
                }
                const tokens = this.tokenizer.tokenize(t).tokens;
                const max = Math.max.apply(Math, tokens.map((token) => {
                    return token.position;
                }));
                last_token = tokens.find((token) => {
                    return token.position === max;
                });
            }
        }

        return (last_token && last_token.tag) ? this.getAutocompleteHelperContent(last_token.tag) : this.getTagsHelperContent();
    }

    getTagsHelperContent() {
        const tags = this.tokenizer.allowed_tags;
        const selected = $(this.getSelectedNode());
        let selected_text = (selected ? selected.text() : '').trim();

        const selected_phrases = selected_text.match(/(?:[^\s"]+|"[^"]*")+/g);
        selected_text = selected_phrases ? selected_phrases[selected_phrases.length - 1] : '';

        let helper = '';
        if (Object.keys(tags).length > 0) {
            helper += '<ul class="list-group tags-list">';
        }
        $.each(tags, (name, info) => {
            if ((this.options.filter_on_type && selected_text.length > 0) && !name.toLowerCase().startsWith(selected_text.toLowerCase())) {
                return; // continue
            }
            const description = info.description || '';
            let prefix_content = '';
            const prefix_count = Object.keys(info.supported_prefixes || {}).length;

            $.each(info.supported_prefixes, (i, prefix) => {
                const custom_prefix = this.tokenizer.options.custom_prefixes[prefix];
                let label = custom_prefix ? (custom_prefix.label || prefix) : prefix;
                if (prefix === this.tokenizer.EXCLUSION_PREFIX) {
                    label = __('Exclude');
                }
                prefix_content += `<button type="button" class="btn btn-outline-secondary btn-sm ${prefix_count > 1 ? 'ms-1' : ''} tag-prefix" title="${label}" data-prefix="${prefix}">${prefix}</button>`;
            });
            helper += `
            <li class="list-group-item list-group-item-action" style="cursor: pointer" data-tag="${name}">
                <div class="d-flex flex-grow-1 justify-content-between">
                   <b>${name}</b>
                   <span>${prefix_content}</span>
                </div>
                <div class="text-muted fst-italic">${description}</div>
            </li>
         `;
        });
        if (Object.keys(tags).length > 0) {
            helper += '</ul>';
        }
        return helper;
    }

    getAutocompleteHelperContent(tag_name) {
        tag_name = tag_name.toLowerCase();
        const tag = this.tokenizer.allowed_tags[tag_name];
        if (tag === undefined) {
            return null;
        }
        const selected = $(this.getSelectedNode());
        const selected_text = (selected ? selected.text() : '').trim();
        const tokens = this.tokenizer.tokenize(selected_text).getTaggedTerms();
        const current_term = (tokens.length > 0 ? tokens[0].term : '').trim();

        let helper = '';
        const autocomplete_values = this.tokenizer.getAutocomplete(tag_name);
        if (autocomplete_values.length > 0) {
            helper += `<ul class="list-group term-suggestions-list" data-tag="${tag_name}">`;
        } else {
            helper = `${tag_name.toLowerCase()}: ${tag.description}`;
        }
        $.each(autocomplete_values, (i, v) => {
            if ((this.options.filter_on_type && selected_text.length > 0) && !v.toLowerCase().startsWith(current_term.toLowerCase())) {
                return; // continue
            }
            helper += `<li class="list-group-item list-group-item-action" style="cursor: pointer">${v}</li>`;
        });
        if (autocomplete_values.length > 0) {
            helper += '</ul>';
        }
        return helper;
    }
}
