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

/**
 * Keyboard behaviour for the segmented one-time-code field (one input per digit).
 * Deliberately does not auto-submit: the user validates with the form button,
 * which avoids an unexpected context change for assistive technologies.
 */
export class MfaCodeInput
{
    /** @type {HTMLInputElement[]} */
    #inputs;

    /**
     * Wire every code input found under `root`, skipping already-wired ones.
     * @param {ParentNode} root
     */
    static init(root = document)
    {
        root.querySelectorAll('[data-mfa-code-input]').forEach((el) => {
            if (el.dataset.mfaCodeInputReady === 'true') {
                return;
            }
            el.dataset.mfaCodeInputReady = 'true';
            new MfaCodeInput(el);
        });
    }

    /**
     * @param {HTMLElement} container Wrapper holding the per-digit inputs.
     */
    constructor(container)
    {
        this.#inputs = [...container.querySelectorAll('input')];
        this.#inputs.forEach((input, index) => {
            input.addEventListener('input', () => this.#onInput(index));
            input.addEventListener('keydown', (e) => this.#onKeydown(e, index));
            input.addEventListener('paste', (e) => this.#onPaste(e));
        });
    }

    #onInput(index)
    {
        const input = this.#inputs[index];
        input.value = input.value.replace(/\D/g, '').slice(0, 1);
        if (input.value !== '' && index < this.#inputs.length - 1) {
            this.#inputs[index + 1].focus();
        }
    }

    #onKeydown(e, index)
    {
        if (e.key === 'Backspace') {
            // Only hijack backspace on an already empty cell: step back and clear
            // the previous one. A filled cell keeps the native delete behaviour.
            if (this.#inputs[index].value === '') {
                e.preventDefault();
                if (index > 0) {
                    this.#inputs[index - 1].value = '';
                    this.#inputs[index - 1].focus();
                }
            }
            return;
        }
        if (e.key === 'ArrowLeft' && index > 0) {
            e.preventDefault();
            this.#inputs[index - 1].focus();
        }
        if (e.key === 'ArrowRight' && index < this.#inputs.length - 1) {
            e.preventDefault();
            this.#inputs[index + 1].focus();
        }
    }

    #onPaste(e)
    {
        e.preventDefault();
        const pasted = e.clipboardData?.getData('text') ?? '';
        const digits = pasted.replace(/\D/g, '').slice(0, this.#inputs.length);
        if (digits === '') {
            return;
        }
        this.#inputs.forEach((input, i) => {
            input.value = digits[i] ?? '';
        });
        this.#inputs[Math.min(digits.length, this.#inputs.length - 1)].focus();
    }
}
