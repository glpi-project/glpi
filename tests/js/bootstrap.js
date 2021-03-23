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

// Bootstrap for all JS test modules

window.$ = window.jQuery = require('jquery');

// Set faux CFG_GLPI variable. We cannot get the real values since they are set inline in PHP.
window.CFG_GLPI = {
   root_doc: '/'
};

// Mock localization
window.__ = function (msgid, domain /* , extra */) {
   return msgid;
};
window._n = function (msgid, msgid_plural, n = 1, domain /* , extra */) {
   return n === 1 ? msgid : msgid_plural;
};
window._x = function (msgctxt, msgid, domain /* , extra */) {
   return msgid;
};
window._nx = function (msgctxt, msgid, msgid_plural, n = 1, domain /* , extra */) {
   return n === 1 ? msgid : msgid_plural;
};

window.AjaxMockResponse = class {

   constructor(url, method = 'GET', data = {}, response, is_persistent = false) {
      /**
       * The URL
       * @type string
       */
      this.url = url;
      this.method = method;
      this.data = data;
      this.response = response;
      this.is_persistent = is_persistent;
   }

   isMatch(url, method = 'GET', data = {}) {
      if (this.url !== url || this.method !== method) {
         return false;
      }
      let data_match = true;
      $.each(data, (k, v) => {
         if (this.data[k] !== undefined && this.data[k] !== v) {
            data_match = false;
            return false;
         }
      });
      return data_match;
   }

   call(data) {
      return this.response(data);
   }
};

class AjaxMock {

   constructor() {
      /**
       * @type {AjaxMockResponse[]}
       */
      this.response_stack = [];
      $._ajax = $.ajax;
   }

   start() {
      this.response_stack = [];
      $.ajax = this.ajax;
   }

   end() {
      this.response_stack = [];
      $.ajax = $._ajax;
   }

   addMockResponse(response) {
      this.response_stack.push(response);
   }

   ajax() {
      let url, settings;

      if (arguments.length === 2) {
         [url, settings] = arguments;
      } else {
         settings = arguments[0];
         url = settings.url;
      }

      let result = undefined;
      for (let i = 0; i < window.AjaxMock.response_stack.length; i++) {
         const response = window.AjaxMock.response_stack[i];
         if (response.isMatch(url, settings.method, settings.data)) {
            result = response.call(settings.data);
            if (!response.is_persistent) {
               window.AjaxMock.response_stack.splice(i, 1);
            }
         }
      }
      if (result !== undefined) {
         return Promise.resolve(result);
      }
   }
}
window.AjaxMock = new AjaxMock();
