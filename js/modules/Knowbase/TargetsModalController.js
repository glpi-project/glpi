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

export class GlpiKnowbaseTargetsModalController
{
    constructor()
    {
        this.#init();
    }

    #init()
    {
        const navWrapper = document.querySelector('[data-glpi-targets-nav]');
        const navList = navWrapper.querySelector('ul');
        const modalBody = navWrapper.closest('.modal-body');

        if (modalBody) {
            const modalContent = modalBody.closest('.modal-content');
            const modalDialog = modalContent.closest('.modal-dialog');
            const modalHeader = modalContent.querySelector('.modal-header');
            const closeBtn = modalHeader.querySelector('.btn-close');

            modalDialog.classList.add('modal-dialog-centered');
            modalHeader.className = 'modal-header border-0 pb-0 pt-3';
            modalHeader.replaceChildren(navList, closeBtn);
            modalBody.classList.add('p-4');
            navWrapper.remove();
        }
    }
}
