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

window.GLPI = window.GLPI || {};

/**
 * @typedef CompletionItemDefinition
 * @property {string} name The name of the completion item.
 * @property {string} type The type of the completion item. This corresponds to a type in the {@link CompletionItemKind} enum.
 */
export default class MonacoEditor {
    /**
     *
     * @param {string} element_id The ID of the DIV to create the editor in
     * @param {string} language The code language
     * @param {string} value The default value for the editor
     * @param {CompletionItemDefinition[]} completions List of completion items
     */
    constructor(element_id, language, value = '', completions = []) {
        const el = document.getElementById(element_id);
        const trigger_characters = {
            twig: ['{', ' '],
        };
        window.monaco.languages.registerCompletionItemProvider(language, {
            triggerCharacters: trigger_characters[language] ?? [],
            provideCompletionItems: function (model, position) {
                const word = model.getWordUntilPosition(position);
                const range = {
                    startLineNumber: position.lineNumber,
                    endLineNumber: position.lineNumber,
                    startColumn: word.startColumn,
                    endColumn: word.endColumn,
                };
                let insert_prefix = '';
                let insert_suffix = '';
                if (language === 'twig') {
                    const text = model.getValueInRange({
                        startLineNumber: 1,
                        endLineNumber: position.lineNumber,
                        startColumn: 1,
                        endColumn: position.column,
                    });
                    const text_after = model.getValueInRange({
                        startLineNumber: position.lineNumber,
                        endLineNumber: position.lineNumber,
                        startColumn: position.column,
                        endColumn: position.column + 1,
                    });

                    // Check if we are in a twig tag already
                    const tag_opened = text.match(/{{\s*$/g);
                    const tag_closed = text_after.match(/\s*}}/g);

                    // If not, we will add the twig tag characters around the inserted text
                    if (tag_opened && !tag_closed) {
                        insert_prefix = ' ';
                        insert_suffix = ' }}';
                    } else if (!tag_opened && !text.match(/{\s*$/g)) {
                        insert_prefix = '{{ ';
                        if (text.match(/\s{0}$/g) && position.column > 2) {
                            insert_prefix = ' {{ ';
                        }
                        insert_suffix = ' }}';
                    } else {
                        return {
                            suggestions: []
                        };
                    }
                }
                return {
                    suggestions: ((range) => {
                        const suggestions = [];
                        // expand completions to monaco format
                        for (const completion of completions) {
                            suggestions.push({
                                label: completion.name,
                                kind: window.monaco.languages.CompletionItemKind[completion.type],
                                insertText: insert_prefix + completion.name + insert_suffix,
                                documentation: completion.name,
                                range: range,
                            });
                        }
                        return suggestions;
                    })(range)
                };
            }
        });
        this.editor = window.monaco.editor.create(el, {
            value: value,
            language: language,
        });
    }
}

window.GLPI.MonacoEditor = MonacoEditor;
