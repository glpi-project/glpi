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

import * as monaco from 'monaco-editor';
// Imports only used to ensure the workers are included in the bundle
import EditorWorker from 'monaco-editor/esm/vs/editor/editor.worker?worker';
import JSONWorker from 'monaco-editor/esm/vs/language/json/json.worker?worker';
import CSSWorker from 'monaco-editor/esm/vs/language/css/css.worker?worker';
import HTMLWorker from 'monaco-editor/esm/vs/language/html/html.worker?worker';
import TSWorker from 'monaco-editor/esm/vs/language/typescript/ts.worker?worker';

self.MonacoEnvironment = {
    // getWorker: function (moduleId, label) {
    //     console.log({
    //         moduleId,
    //         label,
    //     });
    //     if (label === 'json') {
    //         return new JSONWorker();
    //     }
    //     if (label === 'css' || label === 'scss' || label === 'less') {
    //         return new CSSWorker();
    //     }
    //     if (label === 'html' || label === 'handlebars' || label === 'razor') {
    //         return new HTMLWorker();
    //     }
    //     if (label === 'typescript' || label === 'javascript') {
    //         return new TSWorker();
    //     }
    //     return new EditorWorker();
    // },
    getWorkerUrl: function (moduleId, label) {
        if (label === 'json') {
            return '../lib/assets/json.worker.js';
        }
        if (label === 'css' || label === 'scss' || label === 'less') {
            return '../lib/assets/css.worker.js';
        }
        if (label === 'html' || label === 'handlebars' || label === 'razor') {
            return '../lib/assets/html.worker.js';
        }
        if (label === 'typescript' || label === 'javascript') {
            return '../lib/assets/ts.worker.js';
        }
        return '../lib/assets/editor.worker.js';
    }
}
window.monaco = monaco;
