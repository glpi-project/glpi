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

/* global GLPI */

import SearchInput from "../../../../js/modules/SearchTokenizer/SearchInput.js";

describe('Search Tokenizer Input', () => {

    const stripExtraWhitespace = (input) => {
        return input.replace(/[^\S ]+|[ ]{2,}/gi,'');
    };

    beforeAll(() => {
        document.body.innerHTML = `<input name='filter' class='form-control' type='text'/>`;
    });

    test('Constructor', () => {
        const test_input = $('input[name=filter]');
        const search_input = new SearchInput(test_input, {});
        expect(search_input.original_input).toBeDefined();
        expect(search_input.original_input.attr('name')).toBe('filter');

        expect(search_input.displayed_input).toBeDefined();
        const displayed_input = search_input.displayed_input;
        expect(displayed_input.hasClass('search-input')).toBeTrue();

        const default_tag_input = displayed_input.find('.search-input-tag-input');
        expect(default_tag_input.length).toBe(1);
        expect(default_tag_input.attr('contenteditable') === 'true').toBeTrue();
    });

    test('Construct with custom input options', () => {
        const test_input = $('input[name=filter]');
        const input_options = {
            classes: ['custom-class-1', 'custom-class-2'],
            attributes: {
                'test-attr-1': 'test-attr-1-value',
                'test-attr-2': 'test-attr-2-value'
            },
            data: {
                'test-data-1': 'test-data-1-value',
                'test-data-2': 'test-data-2-value'
            }
        };

        const search_input = new SearchInput(test_input, {
            input_options: input_options
        });
        expect(search_input.original_input).toBeDefined();
        expect(search_input.original_input.attr('name')).toBe('filter');

        const displayed_input = search_input.displayed_input.get(0);
        expect(displayed_input).toHaveClass(...input_options.classes);
        $.each(input_options.attributes, (key, value) => {
            expect(displayed_input).toHaveAttribute(key, value);
        });
        $.each(input_options.data, (key, value) => {
            expect(displayed_input).toHaveAttribute('data-' + key, value);
        });
    });

    test('Construct with copied input options', () => {
        const test_input = $('input[name=filter]');
        const input_options = {
            classes: ['custom-class-1', 'custom-class-2'],
            attributes: {
                'test-attr-1': 'test-attr-1-value',
                'test-attr-2': 'test-attr-2-value'
            },
            data: {
                'test-data-1': 'test-data-1-value',
                'test-data-2': 'test-data-2-value'
            }
        };

        //Apply input options to test_input
        $.each(input_options.attributes, (key, value) => {
            test_input.attr(key, value);
        });
        $.each(input_options.data, (key, value) => {
            test_input.attr('data-' + key, value);
        });
        test_input.addClass(input_options.classes);

        const search_input = new SearchInput(test_input, {
            input_options: {
                classes: 'copy',
                attributes: 'copy',
                data: 'copy',
            }
        });
        expect(search_input.original_input).toBeDefined();
        expect(search_input.original_input.attr('name')).toBe('filter');

        const displayed_input = search_input.displayed_input.get(0);
        expect(displayed_input).toHaveClass(...input_options.classes);
        $.each(input_options.attributes, (key, value) => {
            expect(displayed_input).toHaveAttribute(key, value);
        });
        $.each(input_options.data, (key, value) => {
            expect(displayed_input).toHaveAttribute('data-' + key, value);
        });
    });

    test('Tags Helper Content', () => {
        const test_input = $('input[name=filter]');
        const search_input = new SearchInput(test_input, {
            allowed_tags: {
                name: {
                    description: 'The name'
                },
                content: {
                    description: 'The content'
                },
                milestone: {
                    description: 'Is a milestone',
                    autocomplete_values: ['true', 'false']
                }
            }
        });

        let content = stripExtraWhitespace(search_input.getTagsHelperContent());
        expect(content).toBe(stripExtraWhitespace(`
      <ul class="list-group tags-list">
         <li class="list-group-item list-group-item-action" style="cursor: pointer" data-tag="name">
            <div class="d-flex flex-grow-1 justify-content-between">
               <b>name</b><span></span>
            </div>
            <div class="text-muted fst-italic">The name</div>
         </li>
         <li class="list-group-item list-group-item-action" style="cursor: pointer" data-tag="content">
            <div class="d-flex flex-grow-1 justify-content-between">
               <b>content</b><span></span>
            </div>
            <div class="text-muted fst-italic">The content</div>
         </li>
         <li class="list-group-item list-group-item-action" style="cursor: pointer" data-tag="milestone">
            <div class="d-flex flex-grow-1 justify-content-between">
               <b>milestone</b><span></span>
            </div>
            <div class="text-muted fst-italic">Is a milestone</div>
         </li>
      </ul>
      `));
    });

    test('Autocomplete', () => {
        const test_input = $('input[name=filter]');
        const search_input = new SearchInput(test_input, {
            allowed_tags: {
                name: {
                    description: 'The name'
                },
                content: {
                    description: 'The content'
                },
                milestone: {
                    description: 'Is a milestone',
                    autocomplete_values: ['true', 'false']
                }
            }
        });

        let content = search_input.getAutocompleteHelperContent('name');
        expect(stripExtraWhitespace(content)).toBe(`name: The name`);
        content = search_input.getAutocompleteHelperContent('content');
        expect(stripExtraWhitespace(content)).toBe(`content: The content`);
        content = search_input.getAutocompleteHelperContent('milestone');
        expect(stripExtraWhitespace(content)).toBe(stripExtraWhitespace(`
         <ul class="list-group term-suggestions-list" data-tag="milestone">
            <li class="list-group-item list-group-item-action" style="cursor: pointer">true</li>
            <li class="list-group-item list-group-item-action" style="cursor: pointer">false</li>
         </ul>`));
        expect(search_input.getAutocompleteHelperContent('invalid_tag')).toBeNull();

        search_input.tokenizer.allowed_tags['itemtype'] = {
            description: 'The itemtype'
        };
        content = search_input.getAutocompleteHelperContent('itemtype');
        expect(stripExtraWhitespace(content)).toBe(`itemtype: The itemtype`);

        search_input.tokenizer.setAutocomplete('itemtype', ['Project', 'ProjectTask']);
        content = search_input.getAutocompleteHelperContent('itemtype');
        expect(stripExtraWhitespace(content)).toBe(stripExtraWhitespace(`
         <ul class="list-group term-suggestions-list" data-tag="itemtype">
            <li class="list-group-item list-group-item-action" style="cursor: pointer">Project</li>
            <li class="list-group-item list-group-item-action" style="cursor: pointer">ProjectTask</li>
         </ul>`));

        search_input.tokenizer.clearAutocomplete();
        content = search_input.getAutocompleteHelperContent('itemtype');
        expect(stripExtraWhitespace(content)).toBe(`itemtype: The itemtype`);
    });
});
