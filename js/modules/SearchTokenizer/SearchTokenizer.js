/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

import SearchToken from "./SearchToken.js";
import SearchTokenizerResult from "./SearchTokenizerResult.js";

/**
 * Inspired by/rebuilt from https://github.com/tatsuya/search-text-tokenizer
 */
export default class SearchTokenizer {

   /**
    * @typedef TagDefinition
    * @property {string} description
    * @property {string[]} autocomplete_values
    */
   /**
    *
    * @param {Object.<string, TagDefinition>} allowed_tags Tags the tokenizer should recognize
    *    The object keys are the tag names. Each tag can have multiple properties to store
    *    additional information such as descriptions.
    * @param {boolean} drop_unallowed_tags If true, unallowed tags are ignored. If false, the token is treated as a plain term.
    */
   constructor(allowed_tags = {}, drop_unallowed_tags = false) {
      this.token_pattern = /(\w+:|-)?("[^"]*"|'[^']*'|[^\s]+)/g;
      this.EXCLUSION_PREFIX = '-';
      this.allowed_tags = allowed_tags;
      this.drop_unallowed_tags = drop_unallowed_tags;
   }

   /**
    * Check if a given tag is allowed by the tokenizer
    * @param {string|null} tag
    * @return {boolean}
    */
   isAllowedTag(tag) {
      if (tag === null) {
         return true;
      }
      return Object.keys(this.allowed_tags).length === 0 || (tag in this.allowed_tags);
   }

   getPopoverContent(text, cursor_pos) {
      const t = text.slice(0, cursor_pos);
      if (t.endsWith(' ')) {
         return this.getTagsHelperContent();
      }
      const tokens = this.tokenize(t).tokens;
      const max = Math.max.apply(Math, tokens.map((token) => {
         return token.position;
      }));
      const last_token = tokens.find((token) => {
         return token.position === max;
      });

      return (last_token && last_token.tag) ? this.getAutocompleteHelperContent(last_token.tag) : this.getTagsHelperContent();
   }

   getTagsHelperContent() {
      const tags = this.allowed_tags;
      let helper = `${_x('filters', 'Allowed tags')}:`;
      if (Object.keys(tags).length > 0) {
         helper += '</br><ul>';
      }
      $.each(tags, (name, info) => {
         const description = info.description ? `"${info.description}"` : '';
         helper += `
            <li>
                ${name}: ${description}
            </li>
         `;
      });
      if (Object.keys(tags).length > 0) {
         helper += '</ul>';
      }
      return helper;
   }

   getAutocompleteHelperContent(tag_name) {
      const tag = this.allowed_tags[tag_name.toLowerCase()];
      if (tag === undefined) {
         return null;
      }
      let helper = `${tag_name.toLowerCase()}: ${tag.description}`;
      if (tag.autocomplete_values && tag.autocomplete_values.length > 0) {
         helper += '</br><ul>';
      }
      $.each(tag.autocomplete_values, (i, v) => {
         helper += `<li>${v}</li>`;
      });
      if (tag.autocomplete_values && tag.autocomplete_values.length > 0) {
         helper += '</ul>';
      }
      return helper;
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
    *
    * @param {string} input
    * @returns {SearchTokenizerResult}
    */
   tokenize(input) {
      input = input.trim();

      const result = new SearchTokenizerResult();

      let token = null;
      let is_exclusion = false;
      let tag = null;
      let pos = 0;

      while ((token = this.token_pattern.exec(input)) !== null) {
         let prefix = token[1];
         let term = token[2].trim();

         if (prefix) {
            if (prefix === this.EXCLUSION_PREFIX) {
               is_exclusion = true;
               [tag, term] = term.split(':', 2);
            } else {
               // Prefix without the separator
               tag = prefix.slice(0, -1);
            }
         }

         if (/^".+"$/.test(term)) {
            term = term.trim().replace(/^"/, '').replace(/"$/, '').trim();
         }
         if (/^'.+'$/.test(term)) {
            term = term.trim().replace(/^'/, '').replace(/'$/, '').trim();
         }

         if (this.isAllowedTag(tag)) {
            result.tokens.push(new SearchToken(term, tag, is_exclusion, pos++));
         } else if (!this.drop_unallowed_tags) {
            result.tokens.push(new SearchToken(token[0], null, false, pos++));
         }
      }

      return result;
   }
}
