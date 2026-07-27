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

import { MfaCodeInput } from '/js/modules/Security/MfaCodeInput.js';

function setup(digits = 6, { wrapInForm = false } = {}) {
    document.body.innerHTML = '';
    const container = document.createElement('div');
    container.setAttribute('data-mfa-code-input', '');
    for (let i = 0; i < digits; i++) {
        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'totp_code[]';
        input.maxLength = 1;
        container.appendChild(input);
    }
    let root = container;
    if (wrapInForm) {
        root = document.createElement('form');
        root.appendChild(container);
    }
    document.body.appendChild(root);
    new MfaCodeInput(container);
    return { inputs: [...container.querySelectorAll('input')], form: wrapInForm ? root : null };
}

function type(input, char) {
    input.focus();
    input.value = char;
    input.dispatchEvent(new Event('input', { bubbles: true }));
}

function keydown(input, key) {
    input.focus();
    const ev = new KeyboardEvent('keydown', { key, bubbles: true, cancelable: true });
    input.dispatchEvent(ev);
    return ev;
}

function paste(input, text) {
    input.focus();
    const ev = new Event('paste', { bubbles: true, cancelable: true });
    ev.clipboardData = { getData: () => text };
    input.dispatchEvent(ev);
    return ev;
}

describe('MfaCodeInput', () => {
    test('Class exists', () => {
        expect(MfaCodeInput).toBeDefined();
    });

    test('typing a digit advances focus to the next field', () => {
        const { inputs } = setup();
        type(inputs[0], '1');
        expect(document.activeElement).toBe(inputs[1]);
    });

    test('typing in the last field keeps focus and does not auto-submit', () => {
        const { inputs, form } = setup(6, { wrapInForm: true });
        const onSubmit = vi.fn((e) => e.preventDefault());
        form.addEventListener('submit', onSubmit);

        type(inputs[5], '6');

        expect(document.activeElement).toBe(inputs[5]);
        expect(onSubmit).not.toHaveBeenCalled();
    });

    test('non-numeric input is ignored and focus does not advance', () => {
        const { inputs } = setup();
        type(inputs[0], 'a');
        expect(inputs[0].value).toBe('');
        expect(document.activeElement).toBe(inputs[0]);
    });

    test('backspace on an empty field clears and focuses the previous one', () => {
        const { inputs } = setup();
        inputs[0].value = '1';
        const ev = keydown(inputs[1], 'Backspace');
        expect(ev.defaultPrevented).toBe(true);
        expect(document.activeElement).toBe(inputs[0]);
        expect(inputs[0].value).toBe('');
    });

    test('backspace on a filled field lets the native delete happen', () => {
        const { inputs } = setup();
        inputs[2].value = '3';
        const ev = keydown(inputs[2], 'Backspace');
        expect(ev.defaultPrevented).toBe(false);
        expect(document.activeElement).toBe(inputs[2]);
    });

    test('arrow keys move focus between fields', () => {
        const { inputs } = setup();
        keydown(inputs[2], 'ArrowLeft');
        expect(document.activeElement).toBe(inputs[1]);
        keydown(inputs[1], 'ArrowRight');
        expect(document.activeElement).toBe(inputs[2]);
    });

    test('pasting a full code fills every field and focuses the last', () => {
        const { inputs } = setup();
        const ev = paste(inputs[0], '123456');
        expect(ev.defaultPrevented).toBe(true);
        expect(inputs.map((i) => i.value).join('')).toBe('123456');
        expect(document.activeElement).toBe(inputs[5]);
    });

    test('pasting a partial code fills available fields and focuses the next empty', () => {
        const { inputs } = setup();
        paste(inputs[0], '123');
        expect(inputs.map((i) => i.value).join('')).toBe('123');
        expect(document.activeElement).toBe(inputs[3]);
    });

    test('pasting strips non-digit characters', () => {
        const { inputs } = setup();
        paste(inputs[0], '12 34-56');
        expect(inputs.map((i) => i.value).join('')).toBe('123456');
    });

    test('init() wires every container found on the page', () => {
        document.body.innerHTML = '';
        const container = document.createElement('div');
        container.setAttribute('data-mfa-code-input', '');
        for (let i = 0; i < 6; i++) {
            const input = document.createElement('input');
            input.maxLength = 1;
            container.appendChild(input);
        }
        document.body.appendChild(container);

        MfaCodeInput.init();

        const inputs = [...container.querySelectorAll('input')];
        type(inputs[0], '1');
        expect(document.activeElement).toBe(inputs[1]);
    });

    test('init() does not bind the same container twice', () => {
        document.body.innerHTML = '';
        const container = document.createElement('div');
        container.setAttribute('data-mfa-code-input', '');
        const input = document.createElement('input');
        input.maxLength = 1;
        container.appendChild(input);
        document.body.appendChild(container);

        MfaCodeInput.init();
        expect(() => MfaCodeInput.init()).not.toThrow();
        expect(container.dataset.mfaCodeInputReady).toBe('true');
    });
});
