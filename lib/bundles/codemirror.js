/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

// Codemirror base lib

import {EditorView, basicSetup} from "codemirror";
import {css} from "@codemirror/lang-css";
import {json} from "@codemirror/lang-json";
import {sql} from "@codemirror/lang-sql";
import {highlightTree} from "@lezer/highlight";
import {defaultHighlightStyle} from "@codemirror/language";

window.CodeMirror = {
    EditorView: EditorView,
    setup:      basicSetup,
    languages:  {
        css: css,
        json: json,
        sql: sql,
    },
    defaultHighlightStyle: defaultHighlightStyle,
    runMode: (textContent, language, callback) => {
        // see https://discuss.codemirror.net/t/static-highlighting-using-cm-v6/3420
        const tree = language.parser.parse(textContent);
        let pos = 0;
        highlightTree(tree, defaultHighlightStyle, (from, to, classes) => {
            from > pos && callback(textContent.slice(pos, from), null, pos, from);
            callback(textContent.slice(from, to), classes, from, to);
            pos = to;
        });
        pos !== tree.length && callback(textContent.slice(pos, tree.length), null, pos, tree.length);
    }
};
