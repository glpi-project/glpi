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

import SearchToken from "./SearchToken.js";
import SearchTokenizerResult from "./SearchTokenizerResult.js";

/**
 * Inspired by/rebuilt from https://github.com/tatsuya/search-text-tokenizer
 */
export default class SearchTokenizer {

    /**
    * @typedef TagDefinition
    * @property {string} description
    * @property {string[]|function} autocomplete_values
    * @property {string[]} supported_prefixes
    */

    /**
    * @typedef TokenizerOptions
    * @property {Object.<string, {}>} custom_prefixes Object of custom prefixes as keys and properties. These characters can be located at the start of a token's tag and will be stripped from the token's tag.
    *    The stripped prefix is then stored in the token's `prefix` property. The properties for the custom prefixes can be unique for the implementation of the tokenizer.
    *    The only built-in properties are `token_color` which can be used by {@link SearchInput} to change the color of the token, and `label` which can be used by {@link SearchInput} for buttons within suggestions.
    */

    /**
    *
    * @param {Object.<string, TagDefinition>} allowed_tags Tags the tokenizer should recognize
    *    The object keys are the tag names. Each tag can have multiple properties to store
    *    additional information such as descriptions.
    * @param {boolean} drop_unallowed_tags If true, unallowed tags are ignored. If false, the token is treated as a plain term.
    * @param {TokenizerOptions} options Additional tokenizer options
    */
    constructor(allowed_tags = {}, drop_unallowed_tags = false, options = {}) {
        this.token_pattern = /([^\s"']?\w+:)?("[^"]*"|'[^']*'|[^\s]+)/g;
        this.EXCLUSION_PREFIX = '!';
        this.allowed_tags = allowed_tags;
        this.drop_unallowed_tags = drop_unallowed_tags;

        this.options = Object.assign({
            custom_prefixes: {},
        }, options);

        // Ignore custom prefixes used by core
        delete this.options.custom_prefixes[this.EXCLUSION_PREFIX];
        delete this.options.custom_prefixes['\''];
        delete this.options.custom_prefixes['"'];
    }

    /**
    * Check if a given tag is allowed by the tokenizer
    * @param {string|null} tag
    * @return {boolean}
    */
    isAllowedTag(tag) {
        if (tag === null || tag === undefined) {
            return true;
        }
        const result = Object.keys(this.allowed_tags).length === 0 || (tag in this.allowed_tags);
        return result;
    }

    clearAutocomplete() {
        Object.keys(this.allowed_tags).forEach((k) => {
            this.allowed_tags[k].autocomplete_values = [];
        });
    }

    setAutocomplete(tag, values) {
        if (tag in this.allowed_tags) {
            this.allowed_tags[tag].autocomplete_values = values;
        }
    }

    /**
    * Get autocomplete values for a given tag
    * @param tag
    * @return {string[]}
    */
    getAutocomplete(tag) {
        let result = [];
        if (tag in this.allowed_tags) {
            if (typeof this.allowed_tags[tag].autocomplete_values === 'function') {
                result = this.allowed_tags[tag].autocomplete_values();
            } else {
                result = this.allowed_tags[tag].autocomplete_values;
            }
        }
        return result || [];
    }

    /**
    *
    * @param {string} input
    * @returns {SearchTokenizerResult}
    */
    tokenize(input) {
        input = input || '';
        input = input.trim();

        const result = new SearchTokenizerResult();

        let token = null;
        let pos = 0;

        while ((token = this.token_pattern.exec(input)) !== null) {
            let is_exclusion = false;
            let tag = token[1] || null;
            let term = token[2].trim();

            // Tag without the separator
            if (tag) {
                tag = tag.slice(0, -1);
            }

            if (tag === null && term.endsWith(':')) {
                tag = term.slice(0, -1);
                term = '';
            }

            let token_prefix = null;
            // Handle custom prefixes
            if (tag && tag.length > 1) {
                const custom_prefix = tag.slice(0, 1);
                const allowed_prefixes = Object.keys(this.options.custom_prefixes);
                if (custom_prefix === this.EXCLUSION_PREFIX) {
                    is_exclusion = true;
                    tag = tag.slice(1);
                } else {
                    if (allowed_prefixes.includes(custom_prefix)) {
                        const new_tag = tag.slice(1);
                        if (this.allowed_tags[new_tag] && this.allowed_tags[new_tag].supported_prefixes.includes(custom_prefix)) {
                            token_prefix = custom_prefix;
                            tag = new_tag;
                        }
                    }
                }
            }

            // Remove exclusion if the tag doesn't support that prefix
            if (!this.allowed_tags[tag] || !(this.allowed_tags[tag].supported_prefixes || []).includes(this.EXCLUSION_PREFIX)) {
                is_exclusion = false;
            }

            term = term || '';
            if (term.length > 0) {
                if (/^".+"$/.test(term)) {
                    term = term.trim().replace(/^"/, '').replace(/"$/, '').trim();
                }
                if (/^'.+'$/.test(term)) {
                    term = term.trim().replace(/^'/, '').replace(/'$/, '').trim();
                }
            }

            if (this.isAllowedTag(tag)) {
                result.tokens.push(new SearchToken(term, tag, is_exclusion, pos++, token[0], token_prefix));
            } else if (!this.drop_unallowed_tags) {
                result.tokens.push(new SearchToken(token[0], null, false, pos++, token[0]));
            }
        }

        return result;
    }
}
