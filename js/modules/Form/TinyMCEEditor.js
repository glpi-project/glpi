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

/* global strip_tags, onTinyMCEChange, submitparentForm */

import '../../../lib/tinymce.js';
import '../../fileupload.js';
import '../../RichText/ContentTemplatesParameters.js';

export default class TinyMCEEditor {

    constructor(element_id, {
        language = 'en_GB',
        plugins = [],
        readonly = false,
        content_css = '',
        skin_url = `${CFG_GLPI['root_doc']}/lib/tinymce/skins/ui/oxide`,
        cache_suffix = '',
        invalid_elements = [],
        height = 150,
        add_body_class = '',
        toolbar_location = 'top',
        placeholder = '',
        show_toolbar = true,
        show_statusbar = true,
        content_style = '',
        single_line = false,
    } = {}) {
        element_id = $.escapeSelector(element_id);
        const richtext_layout = CFG_GLPI['richtext_layout'] || 'inline';
        const lang_url = `${CFG_GLPI['root_doc']}/lib/tinymce-i18n/langs6/${language}.js`;

        const config = Object.assign({
            license_key: 'gpl',

            link_default_target: '_blank',
            branding: false,
            selector: `#${element_id}`,
            text_patterns: false,
            paste_webkit_styles: 'all',

            plugins: plugins,

            // Appearance
            skin_url: skin_url,
            body_class: `rich_text_container ${add_body_class}`,
            content_css: content_css,
            content_style: content_style,
            highlight_on_focus: false,
            autoresize_bottom_margin: 1, // Avoid excessive bottom padding
            autoresize_overflow_padding: 0,

            min_height: height,
            height: height, // Must be used with min_height to prevent "height jump" when the page is loaded
            resize: true,

            // disable path indicator in bottom bar
            elementpath: false,

            placeholder: placeholder,

            // inline toolbar configuration
            menubar: false,
            toolbar_location: toolbar_location,
            toolbar: (show_toolbar && richtext_layout === 'classic')
                ? 'styles | bold italic | forecolor backcolor | bullist numlist outdent indent | emoticons table link image | code fullscreen'
                : false,
            quickbars_insert_toolbar: richtext_layout === 'inline'
                ? 'emoticons quicktable quickimage quicklink | bullist numlist | outdent indent '
                : false,
            quickbars_selection_toolbar: richtext_layout === 'inline'
                ? 'bold italic | styles | forecolor backcolor '
                : false,
            contextmenu: richtext_layout === 'classic'
                ? false
                : 'copy paste | emoticons table image link | undo redo | code fullscreen',

            statusbar: show_statusbar,

            // Content settings
            entity_encoding: 'raw',
            invalid_elements: invalid_elements,
            readonly: readonly,
            relative_urls: false,
            remove_script_host: false,

            // Misc options
            browser_spellcheck: true,
            cache_suffix: cache_suffix,

            // Security options
            // Iframes are disabled by default. We assume that administrator that enable it are aware of the potential security issues.
            sandbox_iframes: false,

            init_instance_callback: (editor) => {
                const page_root_el = $(document.documentElement);
                const root_el = $(editor.dom.doc.documentElement);
                // Copy data-glpi-theme and data-glpi-theme-dark from page html element to editor root element
                const to_copy = ['data-glpi-theme', 'data-glpi-theme-dark'];
                for (const attr of to_copy) {
                    if (page_root_el.attr(attr) !== undefined) {
                        root_el.attr(attr, page_root_el.attr(attr));
                    }
                }
            },
            setup: function(editor) {
                // "required" state handling
                if ($(`#${element_id}`).attr('required') === 'required') {
                    $(`#${element_id}`).removeAttr('required'); // Necessary to bypass browser validation

                    editor.on('submit', (e) => {
                        if ($(`#${element_id}`).val() === '') {
                            const field = $(`#${element_id}`).closest('.form-field').find('label').text().replace('*', '').trim();
                            alert(__('The %s field is mandatory').replace('%s', field));
                            e.preventDefault();

                            // Prevent other events to run
                            // Needed to not break single submit forms
                            e.stopPropagation();
                        }
                    });
                    editor.on('keyup', () => {
                        editor.save();
                        if ($(`#${element_id}`).val() === '') {
                            $(editor.container).addClass('required');
                        } else {
                            $(editor.container).removeClass('required');
                        }
                    });
                    editor.on('init', () => {
                        if (strip_tags($(`#${element_id}`).val()) === '') {
                            $(editor.container).addClass('required');
                        }
                    });
                    editor.on('paste', () => {
                        // Remove required on paste event
                        // This is only needed when pasting with right click (context menu)
                        // Pasting with Ctrl+V is already handled by keyup event above
                        $(editor.container).removeClass('required');
                    });
                }

                if (single_line) {
                    // Block creating newlines in single line mode
                    editor.on('keydown', (e) => {
                        if (e.keyCode === 13) { // Enter
                            e.preventDefault();
                        }
                    });
                }

                // Propagate click event to allow other components to
                // listen to it
                editor.on('click', (e) => {
                    $(document).trigger('tinyMCEClick', [e]);
                });

                // Simulate focus on content-editable tinymce
                editor.on('click focus', (e) => {
                    // Some focus events don't have the correct target and cant be handled
                    if (!$(e.target.editorContainer).length) {
                        return;
                    }

                    // Clear focus on other editors
                    $('.simulate-focus').removeClass('simulate-focus');

                    // Simulate input focus on our current editor
                    $(e.target.editorContainer)
                        .closest('.content-editable-tinymce')
                        .addClass('simulate-focus');
                });

                editor.on('Change', (e) => {
                    // Nothing fancy here. Since this is only used for tracking unsaved changes,
                    // we want to keep the logic in common.js with the other form input events.
                    onTinyMCEChange(e);

                    // Propagate event to the document to allow other components to listen to it
                    $(document).trigger('tinyMCEChange', [e]);
                });

                editor.on('input', (e) => {
                    // Propagate event to allow other components to listen to it
                    const textarea = $(`#${e.target.dataset.id}`);
                    textarea.trigger('tinyMCEInput', [e]);
                });

                // ctrl + enter submit the parent form
                editor.addShortcut('ctrl+13', 'submit', () => {
                    editor.save();
                    submitparentForm($(`#${element_id}`));
                });
            }
        }, language !== 'en_GB' ? {'language': language, 'language_url': lang_url} : {});

        $(`#${element_id}`).data('tinymce_config', config);
        tinyMCE.init(config);
    }
}
