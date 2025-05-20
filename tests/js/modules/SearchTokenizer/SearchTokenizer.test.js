/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

import SearchTokenizer from "/js/src/vue/Kanban/SearchTokenizer.js";
import SearchToken from "/js/src/vue/Kanban/SearchToken.js";

describe('Search Tokenizer', () => {

    test('Tokenize', () => {
        let untagged_tokens = [
            new SearchToken('This', null, false, 0, 'This'),
            new SearchToken('is', null, false, 1, 'is'),
            new SearchToken('a', null, false, 2, 'a'),
            new SearchToken('te:st', null, false, 3, '"te:st"'),
        ];
        let tokenizer = new SearchTokenizer();
        //strings with colons quoted to be treated as a string when no allowed tags specified (All tags allowed by default)
        let result = tokenizer.tokenize('This is a "te:st"');

        expect(result.getFullPhrase()).toBe('This is a te:st');
        expect(result.getUntaggedTerms()).toStrictEqual(untagged_tokens);
        expect(result.getTaggedTerms().length).toBe(0);

        untagged_tokens = [
            new SearchToken('This', null, false, 0, 'This'),
            new SearchToken('is', null, false, 1, 'is'),
            new SearchToken('a', null, false, 2, 'a'),
            new SearchToken('te:st', null, false, 3, 'te:st'),
        ];
        tokenizer = new SearchTokenizer({
            name: {
                description: '',
                supported_prefixes: ['!']
            }
        });
        expect(tokenizer.isAllowedTag('name')).toBeTrue();
        expect(tokenizer.isAllowedTag('content')).toBeFalse();

        result = tokenizer.tokenize('This is a te:st name:"Test"');
        expect(result.getFullPhrase()).toBe('This is a te:st');
        expect(result.getUntaggedTerms()).toStrictEqual(untagged_tokens);
        expect(result.getTaggedTerms().length).toBe(1);
        let name_tags = result.getTag('name');
        expect(name_tags.length).toBe(1);
        expect(name_tags[0]).toStrictEqual(new SearchToken('Test', 'name', false, 4, 'name:"Test"'));

        result = tokenizer.tokenize('This is a te:st !name:"Test"');
        expect(result.getFullPhrase()).toBe('This is a te:st');
        expect(result.getUntaggedTerms()).toStrictEqual(untagged_tokens);
        expect(result.getTaggedTerms().length).toBe(1);
        name_tags = result.getTag('name');
        expect(name_tags.length).toBe(1);
        expect(name_tags[0]).toStrictEqual(new SearchToken('Test', 'name', true, 4, '!name:"Test"'));

        tokenizer = new SearchTokenizer({
            name: {
                description: '',
                supported_prefixes: ['!']
            }
        }, true);
        // "te" is not an allowed tag so we expect "te:st" to be dropped
        result = tokenizer.tokenize('This is a te:st !name:"Test"');
        expect(result.getFullPhrase()).toBe('This is a');
        expect(result.getUntaggedTerms()).toStrictEqual([
            new SearchToken('This', null, false, 0, 'This'),
            new SearchToken('is', null, false, 1, 'is'),
            new SearchToken('a', null, false, 2, 'a'),
        ]);
        expect(result.getTaggedTerms().length).toBe(1);
        name_tags = result.getTag('name');
        expect(name_tags.length).toBe(1);
        expect(name_tags[0]).toStrictEqual(new SearchToken('Test', 'name', true, 3, '!name:"Test"'));

        untagged_tokens = [
            new SearchToken('This', null, false, 0, 'This'),
            new SearchToken('is', null, false, 1, 'is'),
            new SearchToken('a', null, false, 2, 'a'),
            new SearchToken('te:st', null, false, 3, '"te:st"'),
        ];
        // "te" is not an allowed tag, but it is quoted so we expect it to be treated as a string and not a tagged value
        result = tokenizer.tokenize('This is a "te:st" !name:"Test"');
        expect(result.getFullPhrase()).toBe('This is a te:st');
        expect(result.getUntaggedTerms()).toStrictEqual(untagged_tokens);
        expect(result.getTaggedTerms().length).toBe(1);
        name_tags = result.getTag('name');
        expect(name_tags.length).toBe(1);
        expect(name_tags[0]).toStrictEqual(new SearchToken('Test', 'name', true, 4, '!name:"Test"'));

        result = tokenizer.tokenize('!name:');
        expect(result.tokens.length).toBe(1);
        expect(result.getTaggedTerms().length).toBe(1);
        result.getTag('name').forEach(tag => {
            expect(tag.exclusion).toBeTrue();
            expect(tag.term).toBe('');
        });
    });

    test('Allowed Tags', () => {
        let tokenizer = new SearchTokenizer();
        // All tags allowed by default
        expect(tokenizer.isAllowedTag('name')).toBeTrue();
        expect(tokenizer.isAllowedTag('content')).toBeTrue();

        tokenizer = new SearchTokenizer({
            name: {
                description: ''
            }
        });
        expect(tokenizer.isAllowedTag('name')).toBeTrue();
        expect(tokenizer.isAllowedTag('content')).toBeFalse();
    });

    test('Autocomplete', () => {
        const tokenizer = new SearchTokenizer({
            name: {
                description: 'The name'
            },
            content: {
                description: 'The content'
            },
            milestone: {
                description: 'Is a milestone',
                autocomplete_values: ['true', 'false']
            },
            itemtype: {
                description: 'The itemtype'
            }
        });

        expect(tokenizer.getAutocomplete('itemtype').length).toBe(0);
        tokenizer.setAutocomplete('itemtype', () => {
            return ['Project', 'ProjectTask'];
        });
        expect(tokenizer.getAutocomplete('itemtype')).toIncludeAllMembers(['Project', 'ProjectTask']);
    });

    test('Prefix detection', () => {
        const tokenizer = new SearchTokenizer({
            name: {
                description: 'The name',
                supported_prefixes: ['!', '#']
            },
        }, false, {
            custom_prefixes: {
                "#": {
                    "label": "Regex",
                    "token_color": "#00800080"
                }
            }
        });

        let result = tokenizer.tokenize('!name:test');
        expect(result.getTaggedTerms().length).toBe(1);
        result.getTag('name').forEach(tag => {
            expect(tag.exclusion).toBeTrue();
            expect(tag.term).toBe('test');
        });

        result = tokenizer.tokenize('#name:test');
        expect(result.getTaggedTerms().length).toBe(1);
        result.getTag('name').forEach(tag => {
            expect(tag.exclusion).toBeFalse();
            expect(tag.term).toBe('test');
            expect(tag.prefix).toBe('#');
        });
    });
});
