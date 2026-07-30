/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

import { GlpiFormRendererController } from '/js/modules/Forms/RendererController.js';
import { GlpiFormConditionEngine } from '/js/modules/Forms/Condition/Engine.js';

const emptyVisibilityResults = (form_visibility) => ({
    form_visibility: form_visibility,
    sections_visibility: {},
    questions_visibility: {},
    comments_visibility: {},
});

const appendFixture = (withAltcha = true) => {
    $('body').append(`
        <form data-glpi-form-render-layout="single_page">
            <input type="text" name="test_input" />
            <button type="submit" data-glpi-form-renderer-action="submit"></button>
            ${withAltcha ? '<div data-glpi-form-renderer-altcha></div>' : ''}
        </form>
    `);
};

// Trigger the recompute flow (debounced by 400ms) and wait for the mocked
// async condition engine call to resolve.
const triggerRecompute = async () => {
    vi.useFakeTimers({ doNotFake: ['nextTick'] });
    $('input[name="test_input"]').trigger('input');
    vi.advanceTimersByTime(400);
    await new Promise(process.nextTick);
    vi.useRealTimers();
};

describe('GlpiFormRendererController', () => {
    let compute_visibility_spy;

    beforeEach(() => {
        $('body').empty();
        compute_visibility_spy = vi.spyOn(GlpiFormConditionEngine.prototype, 'computeVisiblity');
    });

    afterEach(() => {
        vi.clearAllMocks();
        vi.useRealTimers();
    });

    test('Altcha widget follows the submit button visibility', async () => {
        appendFixture();
        new GlpiFormRendererController('form', 1);

        const submit_button = document.querySelector('[data-glpi-form-renderer-action="submit"]');
        const altcha = document.querySelector('[data-glpi-form-renderer-altcha]');

        compute_visibility_spy.mockResolvedValue(emptyVisibilityResults(false));
        await triggerRecompute();

        expect(submit_button.hasAttribute('data-glpi-form-renderer-hidden-by-condition')).toBeTruthy();
        expect(altcha.hasAttribute('data-glpi-form-renderer-hidden-by-condition')).toBeTruthy();

        compute_visibility_spy.mockResolvedValue(emptyVisibilityResults(true));
        await triggerRecompute();

        expect(submit_button.hasAttribute('data-glpi-form-renderer-hidden-by-condition')).toBeFalsy();
        expect(altcha.hasAttribute('data-glpi-form-renderer-hidden-by-condition')).toBeFalsy();
    });

    test('Missing altcha widget is ignored without error', async () => {
        appendFixture(false);
        new GlpiFormRendererController('form', 1);

        expect(document.querySelector('[data-glpi-form-renderer-altcha]')).toBeNull();

        compute_visibility_spy.mockResolvedValue(emptyVisibilityResults(false));
        await expect(triggerRecompute()).resolves.not.toThrow();

        const submit_button = document.querySelector('[data-glpi-form-renderer-action="submit"]');
        expect(submit_button.hasAttribute('data-glpi-form-renderer-hidden-by-condition')).toBeTruthy();
    });
});
