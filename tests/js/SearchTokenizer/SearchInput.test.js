/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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

/* global GLPI */

import SearchInput from "../../../js/modules/SearchTokenizer/SearchInput.js";
require('@tabler/core/src/js/src/popover.js');

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
        expect(search_input.original_input).toStrictEqual(test_input);

        expect(search_input.displayed_input).toBeDefined();
        const displayed_input = search_input.displayed_input;
        expect(displayed_input.hasClass('search-input')).toBeTrue();

        const default_tag_input = displayed_input.find('.search-input-tag-input');
        expect(default_tag_input.length).toBe(1);
        expect(default_tag_input.attr('contenteditable') === 'true').toBeTrue();
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
