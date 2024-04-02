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

/* global tinymce */

var GLPI = GLPI || {};
GLPI.RichText = GLPI.RichText || {};

/**
 * User templates parameters autocompleter.
 *
 * @since 10.0.0
 */
GLPI.RichText.ContentTemplatesParameters = class {

    /**
    * @param {Editor} editor
    * @param {string} values Auto completion possible values
    */
    constructor(editor, values) {
        this.editor = editor;
        this.values = this.parseParameters(values);
    }

    /**
    * Register as autocompleter to editor.
    *
    * @returns {void}
    */
    register() {
        const that = this;

        // Register autocompleter
        this.editor.ui.registry.addAutocompleter(
            'content_templates',
            {
                trigger: '{',
                minChars: 0,
                fetch: function (pattern) {
                    return that.fetchItems(pattern);
                },
                onAction: function (autocompleteApi, range, value) {
                    that.insertTwigContent(autocompleteApi, range, value);
                }
            }
        );
    }

    /**
    * Fetch autocompleter items.
    *
    * @private
    *
    * @param {string} pattern
    *
    * @returns {Promise}
    */
    fetchItems(pattern) {
        const that = this;

        return new Promise(
            function (resolve) {
                const items = that.values.filter(
                    function(item) {
                        pattern = pattern.trim();

                        if (pattern.length === 0) {
                            return true;
                        }

                        // Check if item matches expected type ("{{ }}" or "{% %}")
                        const opening = pattern.charAt(0);
                        if (['{', '%'].includes(opening) && opening !== item.opening.charAt(1)) {
                            return false;
                        }

                        // Filter variables depending on for loops
                        var for_counter = 0;
                        var for_key = null;
                        tinymce.dom.TextSeeker(that.editor.dom, () => false).backwards(
                            that.editor.selection.getNode(),
                            0,
                            function(textNode, offset, text) {
                                // If a endfor is found, store it in a counter,
                                // to remember how many for loops opening should be ignored.
                                if (/\{%\s*endfor\s*%\}/.test(text)) {
                                    for_counter++;
                                }

                                var found = text.match(/\{%\s*for\s+\w+\s+in\s+([\w.]+)\s*%\}/);
                                if (found !== null) {
                                    if (for_counter == 0) {
                                        for_key = found[1]; // key is the first captured group
                                        return offset;
                                    }
                                    for_counter--;
                                }
                                return -1;
                            }
                        );
                        if (for_key !== null && item.parent_key !== for_key) {
                            // When inside a for loop, do not show elements that does not correspond to current loop
                            return false;
                        } else if (for_key === null && item.parent_key !== undefined) {
                            // When outside a for loop, do not show elements that should only be displayed in a loop
                            return false;
                        }

                        // Check if our item match the given pattern
                        // Search in both key and text
                        const key = pattern.replace(/^(\{|%)\s*/, '').toLowerCase();
                        let match = item.key.toLowerCase().includes(key) || item.text.toLowerCase().includes(key);

                        // Text do not match item, skip
                        if (!match) {
                            return false;
                        }

                        return true;
                    }
                );
                resolve(items);
            }
        );
    }

    /**
    * Recursive function to parse available parametes into a format that can
    * be handled by the autocompletion
    *
    * @private
    *
    * @param {Array} parameters
    * @param {string} key_prefix
    * @param {string} label_prefix
    *
    * @returns {Array} Parsed parameters
    */
    parseParameters(parameters, key_prefix = "", label_prefix = "") {
        const parsed_parameters = [];
        const that = this;

        parameters.forEach(parameter => {
            // Add key prefix, needed when we go down recursivly so we don't lose track
            // of the main item (e.g ticket.entity.name instead of entity.name)
            if (key_prefix.length > 0) {
                parameter.key = key_prefix + "." + parameter.key;
            }
            // Add label prefix to enhance lisibility
            if (label_prefix.length > 0) {
                parameter.label = label_prefix + " > " + parameter.label;
            }

            switch (parameter.type) {
            // Add a simple attribute to autocomplete
                case 'AttributeParameter': {
                    let value = '{{ ' + parameter.key;
                    if (parameter.filter && parameter.filter.length) {
                        value += ' | ' + parameter.filter;
                    }
                    value += " }}";

                    parsed_parameters.push({
                        type: 'autocompleteitem',
                        opening: '{{',
                        key: parameter.key,
                        value: value,
                        text: value + ' - ' + parameter.label,
                    });
                    break;
                }

                // Recursivly parse parameters of the given object
                case 'ObjectParameter': {
                    parsed_parameters.push(...that.parseParameters(parameter.properties, parameter.key, parameter.label));
                    break;
                }

                // Add a possible loop to the autocomplete, with extra autocomplete
                // support for the content of the array.
                case 'ArrayParameter': {
                    let value = '{% for ' + parameter.items_key + ' in ' + parameter.key + ' %}';
                    parsed_parameters.push({
                        type: 'autocompleteitem',
                        opening: '{%',
                        key: parameter.key,
                        value: value,
                        text: value + ' - ' + parameter.label,
                    });

                    // Push content of array, hidden by default unless the parent loop exist in the editor
                    const content = that.parseParameters([parameter.content]);
                    parsed_parameters.push(
                        ...content.map(
                            function(item) {
                                item.opening = '{{';
                                item.parent_key = parameter.key;
                                return item;
                            }
                        )
                    );
                    break;
                }
            }
        });

        return parsed_parameters;
    }

    /**
    * Add mention to selected user in editor.
    *
    * @private
    *
    * @param {AutocompleterInstanceApi} autocompleteApi
    * @param {Range} range
    * @param {string} value
    *
    * @returns {void}
    */
    insertTwigContent(autocompleteApi, range, value) {
        this.editor.selection.setRng(range);

        // Special case for loops, auto add closing tag
        if (value.indexOf("{% for ") == 0) {
            value = value + "<br><br>{% endfor %}";
        }

        this.editor.insertContent(value);
        autocompleteApi.hide();
    }
};
