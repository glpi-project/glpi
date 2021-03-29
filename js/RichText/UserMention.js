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

/* global tinymce */

var GLPI = GLPI || {};
GLPI.RichText = GLPI.RichText || {};

/**
 * User mention rich text autocompleter.
 *
 * @since 10.0.0
 */
GLPI.RichText.UserMention = class {

   /**
    * @param {Editor} editor
    * @param {number} activeEntity
    * @param {string} idorToken
    */
   constructor(editor, activeEntity, idorToken) {
      this.editor = editor;
      this.activeEntity = activeEntity;
      this.idorToken = idorToken;
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
         'user_mention',
         {
            ch: '@',
            minChars: 0,
            fetch: function (pattern) {
               return that.fetchItems(pattern);
            },
            onAction: function (autocompleteApi, range, value) {
               that.mentionUser(autocompleteApi, range, value);
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
    * @returns {tinymce.util.Promise}
    */
   fetchItems(pattern) {
      const that = this;
      return new tinymce.util.Promise(
         function (resolve) {
            $.post(
               CFG_GLPI.root_doc + '/ajax/getDropdownUsers.php',
               {
                  entity_restrict: that.activeEntity,
                  right: 'all',
                  display_emptychoice: 0,
                  searchText: pattern,
                  _idor_token: that.idorToken,
               }
            ).done(
               function(data) {
                  const items = data.results.map(
                     function (user) {
                        return {
                           type: 'autocompleteitem',
                           value: JSON.stringify({id: user.id, name: user.text}),
                           text: user.text,
                           // TODO user picture icon: ''
                        };
                     }
                  );
                  resolve(items);
               }
            );
         }
      );
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
   mentionUser(autocompleteApi, range, value) {
      const user = JSON.parse(value);

      this.editor.selection.setRng(range);
      this.editor.insertContent(this.generateUserMentionHtml(user));

      autocompleteApi.hide();
   }

   /**
    * Generates HTML code to insert in editor.
    *
    * @private
    *
    * @param {Object} user
    *
    * @returns {string}
    */
   generateUserMentionHtml(user) {
      return `<span contenteditable="false"
                    data-user-mention="true"
                    data-user-id="${user.id}">@${user.name}</span>&nbsp;`;
   }
};
