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
     * @param {object} options Other options for the editor
     */
    constructor(element_id, language, value = '', completions = [], options = {}) {
        const el = document.getElementById(element_id);
        const trigger_characters = {
            twig: ['{', ' '],
        };

        // Stupid workaround to allow multiple Monaco editors to be created for the same language but with different completions
        // since it registers completions by langauge in a global variable rather than allowing it to be instance-specific
        const existing_lang = window.monaco.languages.getLanguages().find((lang) => lang.id === language);
        const new_lang_id = 'glpi_' + language + '_' + Math.random().toString(36).substring(2, 15);

        // register new language based on existing one's tokenizer
        window.monaco.languages.register({
            id: new_lang_id,
            extensions: existing_lang.extensions,
            aliases: existing_lang.aliases,
            mimetypes: existing_lang.mimetypes
        });

        // Can't just specify the loader when registering the language apparently...
        async function registerNewLangLoaderData() {
            const loader = await existing_lang.loader();
            window.monaco.languages.setMonarchTokensProvider(new_lang_id, loader.language);
            window.monaco.languages.setLanguageConfiguration(new_lang_id, loader.conf);
        }
        registerNewLangLoaderData();

        window.monaco.languages.registerCompletionItemProvider(new_lang_id, {
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
                                label: {label: completion.name, detail: completion.detail || ''},
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
        this.editor = window.monaco.editor.create(el, Object.assign({
            value: value,
            language: new_lang_id,
        }, options));

        if (options._single_line_editor) {
            // force cursor to stay on the first line
            this.editor.onDidChangeCursorPosition((e) => {
                if (e.position.lineNumber !== 1) {
                    this.editor.setValue(this.editor.getValue().trim());
                    this.editor.setPosition({lineNumber: 1, column: Infinity});
                }
            });
            this.editor.addAction({
                id: 'blockNewline',
                label: 'Block newline',
                keybindings: [window.monaco.KeyCode.Enter],
                run: () => {
                    this.editor.setValue(this.editor.getValue().trim());
                    this.editor.setPosition({lineNumber: 1, column: Infinity});
                }
            });
        }
    }

    /**
     * Get a set of options for Monaco editor that are suitable for a single line editor (similar to a text input but with syntax highlighting and suggestions)
     */
    static get singleLineEditorOptions() {
        const font_size = $(document.body).css('font-size').replace('px', '');
        return {
            _single_line_editor: true, // Used by us only. The constructor will see this and do extra stuff.
            acceptSuggestionOnEnter: "on",
            contextmenu: false,
            cursorStyle: "line-thin",
            find: {
                addExtraSpaceOnTop: false,
                autoFindInSelection: "never",
                seedSearchStringFromSelection: "never"
            },
            fixedOverflowWidgets: true,
            folding: false,
            fontSize: font_size,
            fontWeight: "normal",
            glyphMargin: false,
            hideCursorInOverviewRuler: true,
            hover: {
                delay: 100
            },
            lineDecorationsWidth: 0,
            lineNumbers: "off",
            lineNumbersMinChars: 0,
            links: false,
            minimap: {
                enabled: false
            },
            occurrencesHighlight: false,
            overviewRulerBorder: false,
            overviewRulerLanes: 0,
            quickSuggestions: false,
            renderLineHighlight: "none",
            roundedSelection: false,
            scrollBeyondLastColumn: 0,
            scrollbar: {
                horizontal: "hidden",
                vertical: "hidden",
                alwaysConsumeMouseWheel: false
            },
            wordBasedSuggestions: false,
            wordWrap: "off",
        };
    }
}

window.GLPI.MonacoEditor = MonacoEditor;
