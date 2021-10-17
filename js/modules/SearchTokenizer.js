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

/**
 * Inspired by/rebuilt from https://github.com/tatsuya/search-text-tokenizer
 */
export default class SearchTokenizer {

   /**
    *
    * @param {string[]} allowed_tags Array of tags the tokenizer should recognize
    * @param {boolean} drop_unallowed_tags If true, unallowed tags are ignored. If false, the token is treated as a plain term.
    */
   constructor(allowed_tags = [], drop_unallowed_tags = false) {
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
      return this.allowed_tags.length === 0 || this.allowed_tags.includes(tag);
   }

   /**
    *
    * @param {string} input
    * @returns {TokenizerResult}
    */
   tokenize(input) {
      input = input.trim();

      const result = new TokenizerResult();

      let token = null;
      let is_exclusion = false;
      let tag = null;

      while ((token = this.token_pattern.exec(input)) !== null) {
         let prefix = token[1];
         let term = token[2].trim();

         if (/^".+"$/.test(term)) {
            term = term.trim().replace(/^"/, '').replace(/"$/, '').trim();
         }
         if (/^'.+'$/.test(term)) {
            term = term.trim().replace(/^'/, '').replace(/'$/, '').trim();
         }

         if (prefix) {
            if (prefix === this.EXCLUSION_PREFIX) {
               is_exclusion = true;
               [tag, term] = term.split(':', 2);
            } else {
               // Prefix without the separator
               tag = prefix.slice(0, -1);
            }
         }

         if (this.isAllowedTag(tag)) {
            result.tokens.push(new Token(term, tag, is_exclusion));
         } else if (!this.drop_unallowed_tags) {
            result.tokens.push(new Token(token[0], null, false));
         }
      }

      return result;
   }
}

class Token {
   constructor(term, tag, exclusion) {
      this.term = term;
      this.tag = tag;
      this.exclusion = exclusion;
   }
}

class TokenizerResult {

   constructor() {
      /**
       * @type {Token[]}
       */
      this.tokens = [];
   }

   /**
    * Get all tokens with a specific tag
    * @param name
    * @return {Token}
    */
   getTag(name) {
      return this.tokens.filter(t => t.tag === name);
   }

   /**
    * Get all tokens with a tag
    * @return {Token[]}
    */
   getTaggedTerms() {
      return this.tokens.filter(t => t.tag !== null);
   }

   /**
    * Get all tokens without a tag
    * @return {Token[]}
    */
   getUntaggedTerms() {
      return this.tokens.filter(t => t.tag === null);
   }

   /**
    * Get all untagged terms as a concatenated string
    *
    * The terms in the resulting string should be in the same order they appeared in the tokenizer input string.
    * @return {string}
    */
   getFullPhrase() {
      let full_phrase = '';
      this.getUntaggedTerms().forEach(t => full_phrase += ' ' + t.term);
      return full_phrase.trim();
   }
}
