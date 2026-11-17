/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

import * as monaco from 'monaco-editor/editor';
import 'monaco-editor/features/register.all';
import 'monaco-editor/languages/definitions/register.all';
import 'monaco-editor/languages/features/register.all';

import EditorWorkerUrl from 'monaco-editor/editor/editor.worker?worker&url';
import JSONWorkerUrl from 'monaco-editor/language/json/json.worker?worker&url';
import CSSWorkerUrl from 'monaco-editor/language/css/css.worker?worker&url';
import HTMLWorkerUrl from 'monaco-editor/language/html/html.worker?worker&url';
import TSWorkerUrl from 'monaco-editor/language/typescript/ts.worker?worker&url';

const getWorkerUrl = (emitted_url) => `${CFG_GLPI.root_doc}/lib/assets/${emitted_url.split('/').pop()}`;

self.MonacoEnvironment = {
    getWorker: function (moduleId, label) {
        let url = EditorWorkerUrl;
        if (label === 'json') {
            url = JSONWorkerUrl;
        } else if (label === 'css' || label === 'scss' || label === 'less') {
            url = CSSWorkerUrl;
        } else if (label === 'html' || label === 'handlebars' || label === 'razor') {
            url = HTMLWorkerUrl;
        } else if (label === 'typescript' || label === 'javascript') {
            url = TSWorkerUrl;
        }
        return new Worker(getWorkerUrl(url), {name: label});
    }
};
window.monaco = monaco;
